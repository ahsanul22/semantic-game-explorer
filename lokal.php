<?php
require_once realpath(__DIR__ . '/.') . "/vendor/autoload.php";
require_once __DIR__ . "/includes/html_tag_helpers.php";
require_once __DIR__ . "/includes/gambar_wiki.php";
require_once __DIR__ . "/vendor/easyrdf/easyrdf/lib/Graph.php";
require_once __DIR__ . "/vendor/easyrdf/easyrdf/lib/GraphStore.php";

// Setup some additional prefixes for DBpedia
\EasyRdf\RdfNamespace::set('dbc', 'http://dbpedia.org/resource/Category:');
\EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
\EasyRdf\RdfNamespace::set('dbpedia', 'http://dbpedia.org/property/');
\EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
\EasyRdf\RdfNamespace::set('games', 'https://example.org/schema/games');
\EasyRdf\RdfNamespace::set('dbp', 'http://dbpedia.org/property/');

$sparql_jena = new \EasyRdf\Sparql\Client('http://localhost:3030/mainCategory/sparql');

// ------------------------------------------------------------------
// SEMANTIC QUERY (logika asli, tidak diubah)
// ------------------------------------------------------------------
// $result diawali array kosong (bukan ""), dan $getCategory diinisialisasi,
// supaya analisstatis tidak salah menduga tipe di bagian tampilan hasil.
// Perilakunya sama: array kosong tetap "falsy" seperti string kosong.
$result      = [];
$getCategory = '';
if (isset($_GET['category'])) {
  $getCategory = $_GET['category'];
  $getCategory = str_replace([' ', "'", '(', ')', '!'], ['_', '%27', '%28', '%29', '%21'], ucwords($getCategory));

  if ($getCategory == 'Action') {
    $result = $sparql_jena->query("SELECT * WHERE {
        ?game rdf:type games:action;
        games:abstract ?desc;
        games:releaseDate ?date;
        games:platforms ?pltf;
        foaf:isPrimaryTopicOf ?wiki.
    }");
  } else if ($getCategory == 'Adventure') {
    $result = $sparql_jena->query("SELECT * WHERE {
        ?game rdf:type games:adventure;
        games:abstract ?desc;
        games:releaseDate ?date;
        games:platforms ?pltf;
        foaf:isPrimaryTopicOf ?wiki.
    }");
  } else if ($getCategory == 'Rpg') {
    $result = $sparql_jena->query("SELECT * WHERE {
        ?game rdf:type games:RPG;
        games:abstract ?desc;
        games:releaseDate ?date;
        games:platforms ?pltf;
        foaf:isPrimaryTopicOf ?wiki.
        }");
  } else if ($getCategory == 'Simulation') {
    $result = $sparql_jena->query("SELECT * WHERE {
        ?game rdf:type games:simulation;
        games:abstract ?desc;
        games:releaseDate ?date;
        games:platforms ?pltf;
        foaf:isPrimaryTopicOf ?wiki.
        }");
  } else if ($getCategory == 'Sport') {
    $result = $sparql_jena->query("SELECT * WHERE {
        ?game rdf:type games:sport;
        games:abstract ?desc;
        games:releaseDate ?date;
        games:platforms ?pltf;
        foaf:isPrimaryTopicOf ?wiki.
        }");
  } else if ($getCategory == 'Strategy') {
    $result = $sparql_jena->query("SELECT * WHERE {
        ?game rdf:type games:strategy;
        games:abstract ?desc;
        games:releaseDate ?date;
        games:platforms ?pltf;
        foaf:isPrimaryTopicOf ?wiki.
        }");
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Koleksi Lokal — Smart Game Seeker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@500;700;900&display=swap" rel="stylesheet">

  <style>
    /* =========================================================
       DESIGN TOKENS — warna diambil langsung dari bg.png
       ========================================================= */
    :root {
      --bg:          #0a0418;   /* violet-black, senada tembok  */
      --bg-2:        #140a28;   /* panel / kartu                */
      --ink:         #f2fff0;
      --muted:       rgba(242, 255, 240, 0.60);
      --muted-2:     rgba(242, 255, 240, 0.35);

      --lime:        #4af219;   /* slime neon                   */
      --lime-soft:   #7dff52;
      --violet:      #5a31b3;   /* pipa ungu                    */
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
        /* pojok kiri atas — menutup badge generator pada gambar */
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
      background: var(--violet-soft);
      box-shadow: 0 0 12px var(--violet-soft);
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
      text-shadow: 0 0 18px rgba(139, 99, 232, 0.45);
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
      border-color: var(--violet-soft);
      background: rgba(139, 99, 232, 0.12);
      transform: translateY(-1px);
      box-shadow: 0 0 22px rgba(139, 99, 232, 0.22);
    }
    .pill--ghost { background: transparent; }

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
      color: var(--violet-soft);
      margin-bottom: 20px;
      text-shadow: 0 0 16px rgba(139, 99, 232, 0.5);
    }

    .display {
      font-family: var(--display);
      font-weight: 400;
      line-height: 1.02;
      font-size: clamp(34px, 7.4vw, 82px);
      letter-spacing: 0.5px;
      color: var(--ink);
      text-shadow:
        0 0 26px rgba(139, 99, 232, 0.42),
        0 0 70px rgba(90, 49, 179, 0.55),
        0 4px 0 rgba(0, 0, 0, 0.55);
    }
    .display .lime {
      display: block;
      color: var(--violet-soft);
      text-shadow:
        0 0 22px rgba(139, 99, 232, 0.85),
        0 0 60px rgba(139, 99, 232, 0.45),
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
      border-color: var(--violet-soft);
      box-shadow: 0 0 26px rgba(139, 99, 232, 0.25);
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
      background: var(--violet-soft);
      color: #06210a;
      border: none;
      padding: 0 26px;
      font-family: var(--display);
      font-size: 12px;
      letter-spacing: 1px;
      cursor: pointer;
      transition: background .2s ease;
    }
    .seek__field button:hover { background: var(--violet-soft); }

    .seek__cat {
      display: inline-flex; align-items: center; gap: 8px;
      height: 52px; padding: 0 24px;
      border: 1px solid var(--line); border-radius: 12px;
      background: var(--glass);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      color: var(--ink);
      font-size: 11.5px; font-weight: 600;
      letter-spacing: 1.4px; text-transform: uppercase;
      cursor: pointer;
      transition: border-color .25s ease, background .25s ease, box-shadow .25s ease;
    }
    .seek__cat:hover {
      border-color: var(--violet-soft);
      background: rgba(139, 99, 232, 0.16);
      box-shadow: 0 0 22px rgba(139, 99, 232, 0.25);
    }

    /* chip teknologi — pengganti kartu "Linked Data" yang dulu
       mengambang di kanan (posisinya bentrok di layout tengah) */
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
      background: var(--violet-soft); flex: none;
    }
    .chip:nth-child(2)::before { background: var(--violet-soft); }
    .chip:nth-child(3)::before { background: var(--violet-soft); }


    /* =========================================================
       HASIL PENCARIAN
       ========================================================= */
    .results {
      position: relative; z-index: 2;
      background: var(--bg);
      padding: clamp(44px, 6vw, 84px) clamp(18px, 4vw, 56px);
      border-top: 1px solid var(--line);
    }
    .results__head {
      display: flex; align-items: baseline; justify-content: space-between;
      gap: 14px; flex-wrap: wrap; margin-bottom: 32px;
    }
    .results__title {
      font-family: var(--display);
      font-size: clamp(22px, 4vw, 40px);
      letter-spacing: 0.5px;
      text-shadow: 0 0 24px rgba(139, 99, 232, 0.3);
    }
    .results__title .lime { color: var(--violet-soft); }
    .results__count {
      font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
      color: var(--muted);
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
      gap: 16px;
    }
    .g-card {
      display: block; width: 100%; text-align: left;
      border: 1px solid var(--line);
      border-radius: 14px;
      overflow: hidden;
      background: var(--bg-2);
      cursor: pointer;
      transition: transform .25s ease, border-color .25s ease, box-shadow .25s ease;
    }
    .g-card:hover {
      transform: translateY(-4px);
      border-color: var(--violet-soft);
      box-shadow: 0 16px 40px rgba(0, 0, 0, .55), 0 0 26px rgba(139, 99, 232, .18);
    }
    .g-card__img {
      width: 100%; height: 160px;
      object-fit: cover; display: block;
      background: #000;
    }
    .g-card__body { padding: 15px 17px 19px; }
    .g-card__title {
      font-family: var(--display);
      font-size: 15px;
      line-height: 1.28;
      color: var(--ink);
      margin-bottom: 9px;
    }
    .g-card__meta { font-size: 12px; color: var(--muted); line-height: 1.5; }
    .g-card__date { color: var(--violet-soft); font-weight: 600; margin-top: 3px; }

    .pager { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 32px; }
    .pager a {
      min-width: 40px; padding: 9px 12px; text-align: center;
      border: 1px solid var(--line); border-radius: 9px;
      color: var(--muted); font-size: 13px; font-weight: 600;
      transition: border-color .2s ease, color .2s ease, background .2s ease;
    }
    .pager a:hover {
      border-color: var(--violet-soft); color: var(--ink);
      background: rgba(139, 99, 232, 0.1);
    }

    .empty { text-align: center; padding: 40px 20px; color: var(--muted); }
    .empty h4 {
      font-family: var(--display); font-size: 19px;
      color: var(--ink); margin-bottom: 11px;
    }
    .empty p { font-size: 13.5px; max-width: 460px; margin: 0 auto; line-height: 1.65; }

    /* ---------------- modal genre ---------------- */
    .modal-content {
      background: linear-gradient(160deg, #170c2c, #0a0418);
      border: 1px solid var(--line);
      color: var(--ink);
      border-radius: 16px;
    }
    .modal-header {
      border-bottom: 1px solid var(--line);
      padding: 15px 20px;
    }
    .modal-title {
      font-family: var(--display);
      font-size: clamp(13px, 3.4vw, 17px) !important;
      letter-spacing: 1px;
      color: var(--violet-soft);
      text-shadow: 0 0 18px rgba(139, 99, 232, .4);
    }
    .modal-body { padding: 20px; }
    .modal-body h2 {
      font-family: var(--display);
      font-size: 12px;
      letter-spacing: .5px;
      color: var(--violet-soft);
      margin-bottom: 8px;
      padding-bottom: 7px;
      border-bottom: 1px solid rgba(139, 99, 232, .18);
    }
    /* Link dibuat block agar tiap genre punya baris & area sentuh
       sendiri — versi inline-block sebelumnya terlalu rapat di HP. */
    .modal-body a {
      display: block;
      color: var(--muted);
      font-size: 13.5px;
      line-height: 1.35;
      padding: 7px 0;
      transition: color .2s ease, transform .2s ease;
    }
    .modal-body a:hover { color: var(--violet-soft); transform: translateX(3px); }
    .genre-col { margin-bottom: 16px; }

    @media (max-width: 575px) {
      .modal-dialog { margin: 10px; }
      .modal-body { padding: 16px; }
      .modal-body a { font-size: 14px; padding: 12px 0; }
      .genre-col { margin-bottom: 12px; }
    }

    /* ---------------- footer ---------------- */
    .foot {
      position: relative; z-index: 2;
      background: var(--bg);
      border-top: 1px solid var(--line);
      padding: 24px clamp(18px, 4vw, 56px);
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
      /* Di layar sempit, gambar di-crop ke tengah supaya lorongnya
         tetap terbaca dan tidak cuma menampilkan tembok samping. */
      .hero__bg { background-size: cover; background-position: 50% 42%; }
      .hero__veil {
        background:
          radial-gradient(75% 48% at 50% 44%,
            rgba(10,4,24,.92) 0%, rgba(10,4,24,.78) 50%, rgba(10,4,24,.25) 85%, rgba(10,4,24,0) 100%),
          linear-gradient(to bottom,
            rgba(10,4,24,.88) 0%, rgba(10,4,24,0) 20%,
            rgba(10,4,24,0) 55%, rgba(10,4,24,.95) 86%, var(--bg) 100%);
      }
    }
    @media (max-width: 640px) {
      .nav { padding: 16px 14px; gap: 10px; }
      /* Label tetap ditampilkan — hanya dikecilkan. Menyembunyikannya
         akan menyisakan lingkaran kecil yang tidak jelas maksudnya. */
      .pill {
        padding: 9px 14px;
        font-size: 10px;
        letter-spacing: 1px;
      }
      .brand { font-size: 14px; gap: 8px; }
      .kanji-strip { letter-spacing: 6px; }
      .hero__inner { padding-bottom: 60px; }
    }
    @media (max-width: 360px) {
      .pill { padding: 8px 11px; font-size: 9px; letter-spacing: .6px; }
      .brand { font-size: 12.5px; }
    }
    @media (prefers-reduced-motion: reduce) {
      .mote { animation: none; }
      .mote { display: none; }
      .g-card, .pill, .pager a, .modal-body a { transition: none; }
      .g-card:hover { transform: none; }
    }
    :focus-visible { outline: 2px solid var(--violet-soft); outline-offset: 3px; }
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
        <a href="index.php" class="pill">
          <span class="label">Explore DBpedia</span>
        </a>
      </div>
    </nav>

    <div class="hero__inner">
      <div class="stage">
        <p class="eyebrow">Koleksi Kurasi &middot; Apache Jena Fuseki</p>

        <h1 class="display">
          KOLEKSI
          <span class="lime">LOKAL</span>
        </h1>

        <p class="kanji-strip">収蔵ゲーム</p>

        <p class="lede">
          Dataset game pilihan yang kami rakit sendiri di triplestore Fuseki —
          telusuri per genre, setiap judul tetap terhubung ke DBpedia dan Wikipedia.
        </p>

        <form class="seek" method="get" action="">
          <div class="seek__field">
            <input type="search" name="category" autocomplete="off"
                   placeholder="Ketik genre — Action, Rpg, Strategy…">
            <button type="submit">CARI</button>
          </div>
          <button type="button" class="seek__cat"
                  data-bs-toggle="modal" data-bs-target="#genreModal">
            Kategori &#9662;
          </button>
        </form>

        <div class="chips">
          <span class="chip">Apache Jena Fuseki</span>
          <span class="chip">Linked Open Data</span>
          <span class="chip">EasyRdf</span>
        </div>
      </div>
    </div>
  </section>


  <!-- ===================== HASIL ===================== -->
  <?php if ($result) : ?>
    <section class="results" id="results">
      <div class="results__head">
        <h2 class="results__title">
          <?= htmlspecialchars($getCategory) ?> <span class="lime">/ GAMES</span>
        </h2>
        <span class="results__count"><?= count($result) ?> hasil</span>
      </div>

      <?php if (count($result) === 0) : ?>
        <div class="empty">
          <h4>Tidak ada game ditemukan</h4>
          <p>Kategori ini belum punya triple yang ter-load, atau dataset Fuseki-nya
             perlu di-restart. Coba pilih genre lain dari menu Genres.</p>
        </div>
      <?php else : ?>
        <?php
        $totalData    = count($result);
        $dataPerPage  = 6;
        $totalPages   = ceil($totalData / $dataPerPage);
        $current_page = isset($_GET['page']) ? $_GET['page'] : 1;
        $current_page = max(1, min($totalPages, intval($current_page)));
        $startIndex   = ($current_page - 1) * $dataPerPage;
        $endIndex     = min($startIndex + $dataPerPage - 1, $totalData - 1);
        ?>
        <?php
        // Sampul & judul semua kartu di halaman ini diambil SEKALIGUS
        // secara paralel (lihat includes/gambar_wiki.php) — dulu tiap
        // kartu memuat seluruh halaman HTML Wikipedia satu per satu.
        $gambarBawaan = "https://i.pinimg.com/564x/e8/f1/51/e8f1519b1599f3fc7df008172c87261d.jpg";
        $urlWiki      = [];
        for ($i = $startIndex; $i <= $endIndex; $i++) {
          $urlWiki[$i] = isset($result[$i]->wiki) ? (string) $result[$i]->wiki : '';
        }
        $ringkasan = ambil_ringkasan_wiki(array_filter($urlWiki));
        ?>
        <div class="grid">
          <?php
          for ($i = $startIndex; $i <= $endIndex; $i++) {
            $row    = $result[$i];
            $info   = $ringkasan[$urlWiki[$i]] ?? ['gambar' => null, 'judul' => null];
            $gambar = $info['gambar'] ?: $gambarBawaan;
            $judul  = $info['judul'] ?: "Untitled";
          ?>
            <form method="POST" action="./page-model/detail_koleksi.php">
              <button type="submit" class="g-card">
                <input type="hidden" name="game" value="<?= $row->game ?>" />
                <img class="g-card__img" src="<?= $gambar ?>" alt="">
                <div class="g-card__body">
                  <div class="g-card__title"><?= $judul ?></div>
                  <div class="g-card__meta"><?= $row->pltf ?></div>
                  <div class="g-card__meta g-card__date">Rilis: <?= $row->date ?></div>
                </div>
              </button>
            </form>
          <?php } ?>
        </div>

        <div class="pager">
          <?php for ($page = 1; $page <= $totalPages; $page++) : ?>
            <a href="?category=<?= $getCategory ?>&page=<?= $page ?>#results"><?= $page ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>


  <!-- ===================== FOOTER ===================== -->
  <footer class="foot">
    <span class="jp">収蔵ゲーム &middot; SEEKER</span>
    <span>RDF &middot; SPARQL &middot; Fuseki &middot; DBpedia</span>
  </footer>


  <!-- ===================== MODAL INFO KOLEKSI LOKAL ===================== -->
  <!-- Halaman ini query ke Fuseki di localhost:3030 — hanya berjalan bila
       proyeknya di-clone & dijalankan sendiri, bukan versi yang di-deploy
       online. Popup ini muncul otomatis begitu halaman dibuka supaya
       pengunjung tidak mengira datanya cuma kosong/rusak. -->
  <div class="modal fade" id="lokalInfoModal" tabindex="-1" aria-labelledby="lokalInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title" id="lokalInfoModalLabel">Halaman Ini Butuh Setup Lokal</h1>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p style="color:var(--muted);font-size:13.5px;line-height:1.7;margin-bottom:14px;">
            "Koleksi Lokal" ini mengambil data dari triplestore
            <strong style="color:var(--ink);">Apache Jena Fuseki</strong> yang jalan di
            <code style="color:var(--lime);background:rgba(74,242,25,.08);padding:2px 7px;border-radius:5px;">localhost:3030</code>
            di komputer masing-masing — jadi hanya bisa dicoba dengan meng-clone
            &amp; menjalankan proyek ini sendiri, bukan lewat versi yang sudah di-deploy online.
          </p>
          <p style="color:var(--muted);font-size:13.5px;line-height:1.7;margin-bottom:18px;">
            Langkah-langkah menyiapkan Fuseki, dataset, dan dependency-nya
            ada lengkap di README repo GitHub-nya.
          </p>
          <a class="pill" href="https://github.com/ahsanul22/semantic-game-explorer.git"
             target="_blank" rel="noopener noreferrer"
             style="display:flex;width:100%;justify-content:center;text-align:center;">
            Buka Repo &amp; README di GitHub
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- ===================== MODAL GENRE ===================== -->
  <div class="modal fade" id="genreModal" tabindex="-1" aria-labelledby="genreModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title" id="genreModalLabel">GENRES</h1>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Action</h2>
              <a href="page-model/action_adventure.php">Action-Adventure</a>
              <a href="page-model/action_fantasy.php">Action-Fantasy</a>
              <a href="page-model/action_horror.php">Action-Horror</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Adventure</h2>
              <a href="page-model/adventure_mystery.php">Adventure-Mystery</a>
              <a href="page-model/adventure_point_and_click.php">Point &amp; Click</a>
              <a href="page-model/adventure_visual_novel.php">Visual Novel</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Role-Playing</h2>
              <a href="page-model/rpg_action.php">RPG-Action</a>
              <a href="page-model/rpg_adventure.php">RPG-Adventure</a>
              <a href="page-model/rpg_strategy.php">RPG-Strategy</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Simulation</h2>
              <a href="page-model/simulation_car.php">Simulation-Car</a>
              <a href="page-model/simulation_life.php">Simulation-Life</a>
              <a href="page-model/simulation_flight.php">Simulation-Flight</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Sport</h2>
              <a href="page-model/sport_extreme.php">Sport-Extreme</a>
              <a href="page-model/sport_racing.php">Sport-Racing</a>
              <a href="page-model/sport_simulation.php">Sport-Simulation</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Strategy</h2>
              <a href="page-model/strategy_real_time.php">Strategy-Real Time</a>
              <a href="page-model/strategy_scifi.php">Strategy-Scify</a>
              <a href="page-model/strategy_simulation.php">Strategy-Simulation</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  <script>
    // Tampil otomatis begitu halaman dibuka — lihat catatan di dekat
    // <div id="lokalInfoModal"> di atas.
    (function () {
      var el = document.getElementById('lokalInfoModal');
      if (!el || !window.bootstrap) return;
      new bootstrap.Modal(el).show();
    })();
  </script>
  <?php if ($result) : ?>
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
  <?php endif; ?>
  <?php require __DIR__ . '/includes/memuat.php'; ?>
</body>

</html>