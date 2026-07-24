<?php
// EasyRdf 1.x belum selaras penuh dengan PHP 8.2, sehingga memunculkan
// banyak pemberitahuan "Deprecated" yang tercetak di atas halaman dan
// merusak tata letak. Pesan itu bukan penanda kesalahan program.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . "/includes/html_tag_helpers.php";
// Halaman ini tidak lagi memakai EasyRdf: query DBpedia lewat klien
// curl ber-IPv4 (sparql_cepat). Gambar sampul kartu diambil belakangan
// oleh BROWSER lewat REST API Wikipedia (lihat <script> dekat footer),
// bukan lagi oleh server — lihat catatan di dekat <div class="grid">.
require_once __DIR__ . "/includes/sparql_cepat.php";

// ------------------------------------------------------------------
// PENCARIAN NAMA GAME LANGSUNG KE DBPEDIA
//
// Dulu form ini menerima nama kategori DBpedia yang harus ditulis
// persis (Survival_horror, dst). Sekarang yang dicari adalah NAMA
// GAME-nya: masukan dipecah per kata lalu dicocokkan lewat indeks
// full-text Virtuoso (bif:contains). CONTAINS/regex biasa tidak
// dipakai karena memindai semua label (>30 detik); bif:contains
// menjawab dalam ~1 detik.
//
// Aturan pencocokan: kata sepanjang >= 4 huruf diberi wildcard
// (elden -> elden*) supaya potongan nama pun ketemu; kata pendek
// dicocokkan utuh karena Virtuoso menolak wildcard di bawah 4 huruf
// (galat FT370). Hasil dibatasi tipe dbo:VideoGame agar film/album
// berjudul mirip tidak ikut muncul.
//
// Bentuk query: SAMPLE()+GROUP BY dan OPTIONAL sengaja dihindari —
// dikombinasikan dengan bif:contains, Virtuoso mengembalikan label
// yang sama untuk semua baris. DISTINCT polos terbukti bersih.
// URL Wikipedia tidak perlu ikut di-query karena selalu bisa
// diturunkan langsung dari URI resource-nya.
// ------------------------------------------------------------------
$result  = null;   // null = belum ada pencarian; array = hasil pencarian
$telusur = trim($_GET['q'] ?? '');
$galat   = null;

if ($telusur !== '') {
  // Buang karakter selain huruf/angka/spasi/tanda hubung supaya
  // literal bif:contains tidak bisa dirusak lewat masukan pengguna.
  $bersih = preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $telusur);
  $kata   = preg_split('/\s+/', trim($bersih), -1, PREG_SPLIT_NO_EMPTY);

  // Token satu huruf (sisa apostrof dsb.) hanya dipakai bila memang
  // tidak ada token lain yang lebih berarti.
  $berarti = array_values(array_filter($kata, function ($k) {
    return mb_strlen($k) >= 2;
  }));
  if ($berarti) {
    $kata = $berarti;
  }

  $result = [];
  if ($kata) {
    $token = array_map(function ($k) {
      return '"' . $k . (mb_strlen($k) >= 4 ? '*' : '') . '"';
    }, $kata);

    $q = 'SELECT DISTINCT ?game ?nama WHERE{'
       . '?game a dbo:VideoGame; rdfs:label ?nama. '
       . "?nama bif:contains '" . implode(' AND ', $token) . "'. "
       . 'FILTER langMatches(lang(?nama),"EN")} LIMIT 300';

    try {
      $result = sparql_pilih('https://dbpedia.org/sparql', $q);
      // ORDER BY di sisi Virtuoso tidak konsisten bila digabung
      // bif:contains, jadi pengurutan dilakukan di sini.
      usort($result, function ($a, $b) {
        return strcasecmp((string) $a->nama, (string) $b->nama);
      });
    } catch (\Exception $e) {
      $result = [];
      $galat  = 'Gagal menghubungi DBpedia — endpoint mungkin sedang sibuk. Coba muat ulang beberapa saat lagi.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Game Seeker — Explore DBpedia</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@500;700;900&display=swap" rel="stylesheet">

  <style>
    /* =========================================================
       TOKEN — identik dengan lokal.php agar satu bahasa visual
       ========================================================= */
    :root {
      --bg:          #0a0418;
      --bg-2:        #140a28;
      --ink:         #f2fff0;
      --muted:       rgba(242, 255, 240, 0.60);
      --muted-2:     rgba(242, 255, 240, 0.35);

      --lime:        #4af219;
      --lime-soft:   #7dff52;
      --violet:      #5a31b3;
      --violet-soft: #8b63e8;

      --line:        rgba(139, 99, 232, 0.28);
      --glass:       rgba(20, 10, 40, 0.55);

      --display:     'Bungee', 'Noto Sans JP', sans-serif;
      --body:        'Inter', system-ui, sans-serif;
      --jp:          'Noto Sans JP', sans-serif;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }

    body {
      background: var(--bg);
      color: var(--ink);
      font-family: var(--body);
      overflow-x: hidden;
      -webkit-font-smoothing: antialiased;
    }
    a { text-decoration: none; color: inherit; }

    /* =========================================================
       HERO — konten di tengah, mengisi "panggung" gelap bg.png
       ========================================================= */
    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      background: var(--bg);
    }

    /* Lapis 1: ilustrasi */
    .hero__bg {
      position: absolute;
      inset: 0;
      background: url('img/bg.png') center center / cover no-repeat;
      z-index: 0;
    }

    /* Lapis 2: vignette — menggelapkan pusat agar teks terbaca,
       lalu fade ke warna bg di sisi bawah. Fade bawah ini juga
       yang menutup watermark di pojok kanan bawah gambar. */
    .hero__veil {
      position: absolute;
      inset: 0;
      z-index: 1;
      pointer-events: none;
      background:
        radial-gradient(26% 24% at 0% 0%,
          var(--bg) 0%,
          rgba(10, 4, 24, 0.92) 40%,
          rgba(10, 4, 24, 0) 100%),
        radial-gradient(58% 55% at 50% 46%,
          rgba(10, 4, 24, 0.88) 0%,
          rgba(10, 4, 24, 0.72) 45%,
          rgba(10, 4, 24, 0.15) 78%,
          rgba(10, 4, 24, 0) 100%),
        linear-gradient(to bottom,
          rgba(10, 4, 24, 0.85) 0%,
          rgba(10, 4, 24, 0) 18%,
          rgba(10, 4, 24, 0) 62%,
          rgba(10, 4, 24, 0.92) 88%,
          var(--bg) 100%);
    }

    /* Lapis 3: partikel spora hijau yang naik pelan */
    .mote {
      position: absolute;
      bottom: -20px;
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--lime);
      box-shadow: 0 0 12px var(--lime);
      opacity: 0;
      z-index: 2;
      animation: rise linear infinite;
      pointer-events: none;
    }
    .mote:nth-child(1){ left: 14%; animation-duration: 14s; animation-delay: 0s;   }
    .mote:nth-child(2){ left: 27%; animation-duration: 18s; animation-delay: 3s;   width: 4px; height: 4px; }
    .mote:nth-child(3){ left: 41%; animation-duration: 16s; animation-delay: 6.5s; }
    .mote:nth-child(4){ left: 63%; animation-duration: 20s; animation-delay: 1.8s; width: 5px; height: 5px; }
    .mote:nth-child(5){ left: 76%; animation-duration: 15s; animation-delay: 8s;   }
    .mote:nth-child(6){ left: 88%; animation-duration: 19s; animation-delay: 4.4s; width: 4px; height: 4px; }
    @keyframes rise {
      0%   { transform: translateY(0) translateX(0);     opacity: 0; }
      10%  { opacity: 0.75; }
      90%  { opacity: 0.35; }
      100% { transform: translateY(-104vh) translateX(26px); opacity: 0; }
    }

    /* ---------------- nav ---------------- */
    .nav {
      position: relative;
      z-index: 6;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 24px clamp(18px, 4vw, 56px);
    }
    .brand {
      display: flex; align-items: center; gap: 11px;
      font-family: var(--display);
      font-size: clamp(15px, 2vw, 19px);
      letter-spacing: 0.5px;
      color: var(--ink);
      text-shadow: 0 0 18px rgba(74, 242, 25, 0.45);
    }

    .nav__right { display: flex; align-items: center; gap: 10px; }

    .pill {
      display: inline-flex; align-items: center; gap: 9px;
      padding: 11px 20px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: var(--glass);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      font-size: 11.5px; font-weight: 600;
      letter-spacing: 1.4px; text-transform: uppercase;
      color: var(--ink);
      cursor: pointer;
      transition: border-color .25s ease, background .25s ease, transform .25s ease, box-shadow .25s ease;
    }
    .pill:hover {
      border-color: var(--lime);
      background: rgba(74, 242, 25, 0.12);
      transform: translateY(-1px);
      box-shadow: 0 0 22px rgba(74, 242, 25, 0.22);
    }

    /* ---------------- konten tengah ---------------- */
    .hero__inner {
      position: relative;
      z-index: 5;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 20px clamp(18px, 4vw, 56px) 96px;
    }
    .stage { width: 100%; max-width: 780px; }

    .eyebrow {
      font-family: var(--jp);
      font-size: 12.5px;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: var(--lime-soft);
      margin-bottom: 20px;
      text-shadow: 0 0 16px rgba(74, 242, 25, 0.5);
    }

    .display {
      font-family: var(--display);
      font-weight: 400;
      line-height: 1.02;
      font-size: clamp(34px, 7.4vw, 82px);
      letter-spacing: 0.5px;
      color: var(--ink);
      text-shadow:
        0 0 26px rgba(74, 242, 25, 0.42),
        0 0 70px rgba(90, 49, 179, 0.55),
        0 4px 0 rgba(0, 0, 0, 0.55);
    }
    .display .lime {
      display: block;
      color: var(--lime);
      text-shadow:
        0 0 22px rgba(74, 242, 25, 0.85),
        0 0 60px rgba(74, 242, 25, 0.45),
        0 4px 0 rgba(0, 0, 0, 0.55);
    }

    .kanji-strip {
      font-family: var(--jp);
      font-weight: 700;
      font-size: clamp(13px, 1.8vw, 17px);
      letter-spacing: 10px;
      color: var(--violet-soft);
      margin-top: 16px;
      opacity: 0.9;
    }

    .lede {
      max-width: 560px;
      margin: 22px auto 30px;
      color: var(--muted);
      font-size: clamp(13.5px, 1.5vw, 15px);
      line-height: 1.75;
    }

    /* ---------------- pencarian ---------------- */
    .seek {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
      max-width: 620px;
      margin: 0 auto;
    }
    .seek__field {
      flex: 1 1 300px;
      display: flex;
      align-items: stretch;
      border: 1px solid var(--line);
      border-radius: 12px;
      background: var(--glass);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      overflow: hidden;
      transition: border-color .25s ease, box-shadow .25s ease;
    }
    .seek__field:focus-within {
      border-color: var(--lime);
      box-shadow: 0 0 26px rgba(74, 242, 25, 0.25);
    }
    .seek__field input {
      flex: 1; min-width: 0;
      background: transparent; border: none; outline: none;
      color: var(--ink);
      padding: 0 18px; height: 52px;
      font-size: 14px; font-family: var(--body);
    }
    .seek__field input::placeholder { color: var(--muted-2); }
    .seek__field button {
      background: var(--lime);
      color: #06210a;
      border: none;
      padding: 0 26px;
      font-family: var(--display);
      font-size: 12px;
      letter-spacing: 1px;
      cursor: pointer;
      transition: background .2s ease;
    }
    .seek__field button:hover { background: var(--lime-soft); }

    /* chip teknologi */
    .chips {
      display: flex; flex-wrap: wrap; gap: 8px;
      justify-content: center;
      margin-top: 26px;
    }
    .chip {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 7px 14px;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: rgba(10, 4, 24, 0.5);
      font-size: 10.5px; font-weight: 600;
      letter-spacing: 1.6px; text-transform: uppercase;
      color: var(--muted);
    }
    .chip::before {
      content: ""; width: 5px; height: 5px; border-radius: 50%;
      background: var(--lime); flex: none;
    }
    .chip:nth-child(2)::before { background: var(--violet-soft); }
    .chip:nth-child(3)::before { background: var(--lime); }

    /* =========================================================
       HASIL PENCARIAN
       ========================================================= */
    .results {
      position: relative; z-index: 2;
      background: var(--bg);
      padding: clamp(34px, 5vw, 64px) clamp(16px, 4vw, 56px) clamp(50px, 6vw, 80px);
      border-top: 1px solid var(--line);
    }
    .results__head {
      display: flex; align-items: baseline; justify-content: space-between;
      gap: 14px; flex-wrap: wrap; margin-bottom: 28px;
    }
    .results__title {
      font-family: var(--display);
      font-size: clamp(19px, 3.4vw, 34px);
      letter-spacing: .5px;
      word-break: break-word;
      text-shadow: 0 0 24px rgba(74, 242, 25, .3);
    }
    .results__title .lime { color: var(--lime); }
    .results__count {
      font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
      color: var(--muted);
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 16px;
    }
    .g-card {
      display: block; width: 100%; text-align: left;
      border: 1px solid var(--line); border-radius: 14px;
      overflow: hidden; background: var(--bg-2); cursor: pointer;
      transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
    }
    .g-card:hover {
      transform: translateY(-4px);
      border-color: var(--lime);
      box-shadow: 0 16px 40px rgba(0,0,0,.55), 0 0 26px rgba(74, 242, 25, .18);
    }
    .g-card__img {
      width: 100%; height: 168px;
      object-fit: cover; display: block; background: #000;
    }
    .g-card__body { padding: 15px 17px 18px; }
    .g-card__title {
      font-family: var(--display);
      font-size: 14px; line-height: 1.3;
      color: var(--ink);
    }
    .g-card__src {
      display: inline-flex; align-items: center; gap: 6px;
      margin-top: 10px;
      font-size: 10px; letter-spacing: 1.4px; text-transform: uppercase;
      color: var(--muted-2);
    }
    .g-card__src::before {
      content: ""; width: 4px; height: 4px; border-radius: 50%;
      background: var(--lime); flex: none;
    }

    .pager { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 30px; }
    .pager a {
      min-width: 40px; padding: 9px 12px; text-align: center;
      border: 1px solid var(--line); border-radius: 9px;
      color: var(--muted); font-size: 13px; font-weight: 600;
      transition: border-color .2s ease, color .2s ease, background .2s ease;
    }
    .pager a:hover {
      border-color: var(--lime); color: var(--ink);
      background: rgba(74, 242, 25, .12);
    }
    .pager a.aktif {
      border-color: var(--lime); color: var(--ink);
      background: rgba(74, 242, 25, .18);
      box-shadow: 0 0 14px rgba(74, 242, 25, .25);
    }
    .pager__jeda {
      min-width: 24px; padding: 9px 2px; text-align: center;
      color: var(--muted-2); font-size: 13px; user-select: none;
    }

    /* keadaan kosong */
    .empty { text-align: center; padding: 44px 20px; color: var(--muted); }
    .empty__icon {
      width: 54px; height: 54px; margin: 0 auto 16px;
      border-radius: 50%;
      border: 1px dashed var(--line);
      display: flex; align-items: center; justify-content: center;
      color: var(--lime);
    }
    .empty__icon svg { width: 22px; height: 22px; }
    .empty h4 {
      font-family: var(--display); font-size: 17px;
      color: var(--ink); margin-bottom: 10px;
    }
    .empty p { font-size: 13.5px; max-width: 430px; margin: 0 auto; line-height: 1.7; }
    .empty code {
      color: var(--lime);
      background: rgba(74, 242, 25, .08);
      padding: 2px 7px; border-radius: 5px;
      font-size: 12.5px;
    }

    /* ---------------- footer ---------------- */
    .foot {
      position: relative; z-index: 2;
      background: var(--bg); border-top: 1px solid var(--line);
      padding: 24px clamp(16px, 4vw, 56px);
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 10px;
      font-size: 11px; letter-spacing: 1.6px; text-transform: uppercase;
      color: var(--muted-2);
    }
    .foot .jp { font-family: var(--jp); color: var(--violet-soft); }

    /* =========================================================
       RESPONSIVE
       ========================================================= */
    @media (max-width: 900px) {
      .hero__bg { background-position: 50% 42%; }
    }
    @media (max-width: 640px) {
      .nav { padding: 16px 14px; gap: 10px; }
      .pill { padding: 9px 14px; font-size: 10px; letter-spacing: 1px; }
      .brand { font-size: 14px; gap: 8px; }
      .kanji-strip { letter-spacing: 6px; }
      .hero__inner { padding-bottom: 60px; }
      .g-card__img { height: 150px; }
    }
    @media (max-width: 360px) {
      .pill { padding: 8px 11px; font-size: 9px; letter-spacing: .6px; }
      .brand { font-size: 12.5px; }
    }
    @media (prefers-reduced-motion: reduce) {
      .mote { animation: none; }
      .mote { display: none; }
      .g-card, .pill, .pager a { transition: none; }
      .g-card:hover { transform: none; }
    }
    :focus-visible { outline: 2px solid var(--lime); outline-offset: 3px; }
  </style>
</head>

<body>

  <!-- ===================== HERO ===================== -->
  <section class="hero">
    <div class="hero__bg"></div>
    <div class="hero__veil"></div>
    <span class="mote"></span><span class="mote"></span><span class="mote"></span>
    <span class="mote"></span><span class="mote"></span><span class="mote"></span>

    <nav class="nav">
      <a href="index.php" class="brand">
        SEEKER
      </a>

      <div class="nav__right">
        <a href="lokal.php" class="pill">
          <span class="label">Koleksi Lokal</span>
        </a>
      </div>
    </nav>

    <div class="hero__inner">
      <div class="stage">
        <p class="eyebrow">Linked Open Data &middot; DBpedia</p>

        <h1 class="display">
          SMART GAME
          <span class="lime">SEEKER</span>
        </h1>

        <p class="kanji-strip">ゲーム探索</p>

        <p class="lede">
          Temukan game yang kamu cari! 🎮
          Ketik nama game atau sebagian judulnya, lalu jelajahi berbagai game yang cocok dari graf pengetahuan DBpedia.
        </p>

        <form class="seek" method="get" action="">
          <div class="seek__field">
            <input type="search" name="q" id="catInput" autocomplete="off"
                   value="<?= htmlspecialchars($telusur) ?>"
                   placeholder="Nama game — zelda, elden ring, car…">
            <button type="submit">CARI</button>
          </div>
        </form>

        <div class="chips">
          <span class="chip">DBpedia</span>
          <span class="chip">SPARQL</span>
          <span class="chip">Linked Open Data</span>
        </div>
      </div>
    </div>
  </section>


  <!-- ===================== HASIL ===================== -->
  <section class="results" id="results">
    <?php if ($result === null) : ?>
      <div class="empty">
        <div class="empty__icon">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
            <path d="M16.5 16.5 L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
        <h4>Mulai dari sebuah nama</h4>
        <p>
          Ketik nama game pada kolom di atas — tidak perlu lengkap, misalnya
          <code>zelda</code>, <code>elden</code>, atau <code>car</code>.
          Semua judul yang memuat kata itu akan muncul, lalu tinggal pilih
          untuk membuka detailnya.
        </p>
      </div>

    <?php else : ?>
      <div class="results__head">
        <h2 class="results__title">
          <span class="lime"><?= htmlspecialchars($telusur) ?></span>
        </h2>
        <span class="results__count"><?= count($result) ?> hasil dari DBpedia</span>
      </div>

      <?php if (count($result) === 0) : ?>
        <div class="empty">
          <div class="empty__icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
              <path d="M16.5 16.5 L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
          </div>
          <?php if ($galat !== null) : ?>
            <h4>DBpedia tidak merespons</h4>
            <p><?= htmlspecialchars($galat) ?></p>
          <?php else : ?>
            <h4>Tidak ada hasil</h4>
            <p>
              Tidak ada video game di DBpedia yang namanya memuat
              <code><?= htmlspecialchars($telusur) ?></code>. Coba kata yang
              lebih pendek atau ejaan lain — kata berpanjang empat huruf ke
              atas dicocokkan sebagai awalan, kata pendek harus persis.
            </p>
          <?php endif; ?>
        </div>

      <?php else : ?>
        <?php
        $totalData    = count($result);
        $dataPerPage  = 10;
        // (int) penting: ceil() menghasilkan float, sedangkan nomor
        // halaman ($p) berupa int. Tanpa cast, perbandingan ketat
        // "$p === $totalPages" selalu gagal sehingga halaman terakhir
        // (dan elipsisnya) tak pernah muncul di jendela pagination.
        $totalPages   = (int) ceil($totalData / $dataPerPage);
        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
        $current_page = max(1, min($totalPages, intval($current_page)));
        $startIndex   = ($current_page - 1) * $dataPerPage;
        $endIndex     = min($startIndex + $dataPerPage - 1, $totalData - 1);
        ?>
        <?php
        // Sampul TIDAK lagi diambil di server (curl_multi paralel ke
        // Wikipedia dari PHP). Di hosting gratis (InfinityFree) request
        // keluar yang banyak/paralel begini kerap dibunuh di tengah
        // jalan oleh host sebelum sempat mencetak <div class="grid">,
        // sehingga seluruh kartu ikut hilang meski jumlah hasil sudah
        // tercetak duluan. Sekarang tiap kartu tampil dulu dengan
        // gambar bawaan, lalu BROWSER pengguna sendiri yang mengambil
        // gambar asli dari REST API Wikipedia lewat fetch() di bawah —
        // request ini jalan dari sisi klien jadi tidak kena batasan
        // outbound connection punya host.
        $gambarBawaan = "https://i.pinimg.com/564x/e8/f1/51/e8f1519b1599f3fc7df008172c87261d.jpg";
        ?>
        <div class="grid">
          <?php
          for ($i = $startIndex; $i <= $endIndex; $i++) {
            $row     = $result[$i];
            $artikel = basename(str_replace(
              'http://dbpedia.org/resource/',
              '',
              (string) $row->game
            ));
          ?>
            <form method="POST" action="./page-model/detail_dbpedia.php">
              <button type="submit" class="g-card">
                <input type="hidden" name="game" value="<?= htmlspecialchars($row->game) ?>" />
                <img class="g-card__img" src="<?= htmlspecialchars($gambarBawaan) ?>" alt=""
                     data-wiki-artikel="<?= htmlspecialchars($artikel) ?>" loading="lazy">
                <div class="g-card__body">
                  <div class="g-card__title"><?= htmlspecialchars($row->nama) ?></div>
                  <div class="g-card__src">DBpedia</div>
                </div>
              </button>
            </form>
          <?php } ?>
        </div>

        <?php if ($totalPages > 1) : ?>
          <?php
          // Jendela nomor halaman: selalu tampilkan halaman pertama,
          // terakhir, dan tetangga halaman aktif; sisanya diringkas
          // menjadi elipsis — 25 halaman tidak lagi berjejer semua.
          $tautan = function ($p) use ($telusur) {
            return '?q=' . urlencode($telusur) . '&page=' . $p . '#results';
          };
          $tampil = [];
          for ($p = 1; $p <= $totalPages; $p++) {
            if ($p === 1 || $p === $totalPages || abs($p - $current_page) <= 1) {
              $tampil[] = $p;
            }
          }
          ?>
          <div class="pager">
            <?php if ($current_page > 1) : ?>
              <a href="<?= $tautan($current_page - 1) ?>" aria-label="Halaman sebelumnya">&lsaquo;</a>
            <?php endif; ?>

            <?php $sebelumnya = 0; foreach ($tampil as $p) : ?>
              <?php if ($p - $sebelumnya > 1) : ?>
                <span class="pager__jeda">&hellip;</span>
              <?php endif; ?>
              <?php if ($p === $current_page) : ?>
                <a class="aktif" href="<?= $tautan($p) ?>" aria-current="page"><?= $p ?></a>
              <?php else : ?>
                <a href="<?= $tautan($p) ?>"><?= $p ?></a>
              <?php endif; ?>
              <?php $sebelumnya = $p; ?>
            <?php endforeach; ?>

            <?php if ($current_page < $totalPages) : ?>
              <a href="<?= $tautan($current_page + 1) ?>" aria-label="Halaman berikutnya">&rsaquo;</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </section>


  <!-- ===================== FOOTER ===================== -->
  <footer class="foot">
    <span class="jp">ゲーム探索 &middot; SEEKER</span>
    <span>DBpedia &middot; SPARQL &middot; Linked Open Data</span>
  </footer>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  <?php if ($result !== null) : ?>
    <script>
      // Hero setinggi satu layar penuh membuat hasil pencarian tersembunyi
      // di bawah lipatan; setelah mencari, halaman digulirkan otomatis ke
      // bagian hasil supaya pengguna tidak mengira tidak terjadi apa-apa.
      (function () {
        var hasil = document.getElementById('results');
        if (!hasil) return;
        var langsung = matchMedia('(prefers-reduced-motion: reduce)').matches;
        hasil.scrollIntoView({ behavior: langsung ? 'auto' : 'smooth' });
      })();
    </script>
    <script>
      // Gambar sampul diambil DI SINI, dari browser pengguna, bukan
      // dari server — lihat catatan di dekat <div class="grid"> di atas.
      // Tiap kartu tetap tampil dengan gambar bawaan walau fetch ini
      // gagal/lambat (mis. Wikipedia sedang sibuk atau tidak ada
      // artikelnya); kartunya sendiri tidak pernah bergantung ke sini.
      (function () {
        document.querySelectorAll('.g-card__img[data-wiki-artikel]').forEach(function (img) {
          var artikel = img.getAttribute('data-wiki-artikel');
          if (!artikel) return;
          fetch('https://en.wikipedia.org/api/rest_v1/page/summary/' + encodeURIComponent(artikel), {
            headers: { 'Accept': 'application/json' }
          })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
              if (!data) return;
              var src = (data.thumbnail && data.thumbnail.source)
                || (data.originalimage && data.originalimage.source);
              if (src) img.src = src;
            })
            .catch(function () { /* gambar bawaan tetap dipakai */ });
        });
      })();
    </script>
  <?php endif; ?>
  <?php require __DIR__ . '/includes/memuat.php'; ?>
</body>

</html>
