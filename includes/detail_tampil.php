<?php
/**
 * Templat tampilan halaman detail game — dipakai bersama oleh
 * detail_koleksi.php & detail_dbpedia.php.
 *
 * Menerima:
 *   $D    array data dari ambil_detail_game()  (lihat detail_game.php)
 *   $cfg  array konfigurasi tampilan per halaman:
 *         accent, accent_soft, accent_rgb, judul_suffix, kicker,
 *         back_href, back_label, kembali_label, foot_jp
 */
if (!isset($D) || !isset($cfg)) {
    http_response_code(500);
    exit('detail_tampil.php dipanggil tanpa $D / $cfg.');
}
$washImg  = $D['gambar_besar'] ?: $D['gambar'];
$dbpUrl   = 'https://dbpedia.org/page/' . $D['game'];

// Baris spesifikasi yang hanya tampil bila ada isinya.
$spek = [
    'Publisher'  => $D['pblsh'],
    'Platform'   => $D['platform'],
    'Mode'       => $D['modes'],
    'Engine'     => $D['engine'],
    'Seri'       => $D['series'],
    'Sutradara'  => $D['director'],
    'Penulis'    => $D['writer'],
    'Komposer'   => $D['composer'],
];
$spek = array_filter($spek, function ($v) {
    return $v !== null && $v !== '';
});
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($D['judul']) ?> — <?= htmlspecialchars($cfg['judul_suffix']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@500;700;900&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg:#0a0418; --bg-2:#140a28; --ink:#f2fff0;
      --muted:rgba(242,255,240,.60); --muted-2:rgba(242,255,240,.35);
      --lime:#4af219; --lime-soft:#7dff52;
      --violet:#5a31b3; --violet-soft:#8b63e8;
      --line:rgba(139,99,232,.28); --glass:rgba(20,10,40,.55);
      --accent:<?= $cfg['accent'] ?>; --accent-soft:<?= $cfg['accent_soft'] ?>; --accent-rgb:<?= $cfg['accent_rgb'] ?>;
      --display:'Bungee','Noto Sans JP',sans-serif;
      --body:'Inter',system-ui,sans-serif;
      --jp:'Noto Sans JP',sans-serif;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    html{scroll-behavior:smooth}
    body{background:var(--bg);color:var(--ink);font-family:var(--body);
         overflow-x:hidden;-webkit-font-smoothing:antialiased}
    a{text-decoration:none;color:inherit}

    /* ---------------- nav ---------------- */
    .nav{position:relative;z-index:40;display:flex;align-items:center;
         justify-content:space-between;gap:12px;padding:20px clamp(16px,4vw,48px)}
    .brand{display:flex;align-items:center;gap:11px;font-family:var(--display);
           font-size:clamp(14px,2vw,18px);text-shadow:0 0 18px rgba(var(--accent-rgb),.45)}
    .nav__right{display:flex;align-items:center;gap:9px}
    .pill{display:inline-flex;align-items:center;gap:9px;padding:10px 18px;
          border:1px solid var(--line);border-radius:999px;background:var(--glass);
          backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);
          font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;
          color:var(--ink);cursor:pointer;
          transition:border-color .25s ease,background .25s ease,transform .25s ease,box-shadow .25s ease}
    .pill:hover{border-color:var(--accent);background:rgba(var(--accent-rgb),.12);
                transform:translateY(-1px);box-shadow:0 0 22px rgba(var(--accent-rgb),.2)}

    /* ---------------- panggung / hero ---------------- */
    .hero{position:relative;overflow:hidden;padding-bottom:clamp(24px,4vw,42px)}
    .hero__wash{position:absolute;inset:0;z-index:0;background-size:cover;
                background-position:center;filter:blur(46px) saturate(1.5);
                transform:scale(1.25);opacity:.5}
    .hero__veil{position:absolute;inset:0;z-index:1;pointer-events:none;
                background:linear-gradient(to bottom,rgba(10,4,24,.72) 0%,
                  rgba(10,4,24,.85) 45%,var(--bg) 100%)}
    .hero__inner{position:relative;z-index:5;padding:0 clamp(16px,4vw,48px);
                 max-width:1040px;margin:0 auto}

    .sheet{display:grid;grid-template-columns:clamp(180px,24vw,280px) 1fr;gap:0;
           border:1px solid var(--line);border-radius:20px;overflow:hidden;
           background:linear-gradient(150deg,rgba(28,14,54,.78),rgba(10,4,24,.7));
           backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
           box-shadow:0 34px 90px rgba(0,0,0,.6)}
    .sheet__art{position:relative;background:#000;min-height:100%}
    .sheet__art img{width:100%;height:100%;object-fit:cover;display:block}
    .sheet__art::after{content:"";position:absolute;inset:0;
                       background:linear-gradient(to right,transparent 70%,rgba(10,4,24,.5) 100%)}
    .sheet__body{padding:clamp(22px,3vw,36px)}

    .kicker{display:inline-flex;align-items:center;gap:8px;margin-bottom:14px;
            padding:6px 13px;border:1px solid rgba(var(--accent-rgb),.34);border-radius:999px;
            background:rgba(10,4,24,.5);font-size:10px;font-weight:700;
            letter-spacing:2px;text-transform:uppercase;color:var(--accent)}
    .kicker::before{content:"";width:5px;height:5px;border-radius:50%;
                    background:var(--accent);box-shadow:0 0 8px var(--accent)}

    .judul{font-family:var(--display);font-size:clamp(20px,3.6vw,40px);
           line-height:1.12;margin-bottom:12px;
           text-shadow:0 0 30px rgba(var(--accent-rgb),.3)}
    .tagline{font-size:14px;line-height:1.6;color:var(--accent-soft);
             margin-bottom:20px;max-width:60ch}

    .fakta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px}
    .fakta__item{display:flex;flex-direction:column;gap:3px;padding:10px 15px;
                 border:1px solid var(--line);border-radius:11px;
                 background:rgba(10,4,24,.45);min-width:0}
    .fakta__k{font-size:9.5px;letter-spacing:1.8px;text-transform:uppercase;color:var(--violet-soft)}
    .fakta__v{font-size:13px;font-weight:600;color:var(--ink);
              max-width:230px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    /* Nama class SENGAJA bukan "kosong" — kelas itu sudah dipakai untuk
       kontainer besar "Detail tidak tersedia" (lihat .kosong di bawah,
       yang punya padding:clamp(60px,10vw,120px)). Berbagi satu nama class
       untuk dua tujuan berbeda membuat properti seperti padding itu
       "menembus" ke sini juga — sebuah field kosong jadi ikut setinggi
       120px padding, dan karena .fakta adalah flex row (align-items
       bawaan: stretch), SEMUA kartu Rilis/Developer/Genre ikut melar
       menyamai tinggi itu. Nama unik ini menghindarinya sepenuhnya. */
    .fakta__v.hampa{color:var(--muted-2);font-weight:400}

    /* tombol tautan (dipakai di hero & seksi jelajah) */
    .tautan{display:flex;flex-wrap:wrap;gap:10px}
    .tautan a{display:inline-flex;align-items:center;gap:9px;padding:12px 20px;
              border:1px solid var(--line);border-radius:11px;background:rgba(10,4,24,.5);
              font-size:11px;font-weight:600;letter-spacing:1.3px;text-transform:uppercase;
              transition:border-color .25s ease,background .25s ease,transform .25s ease}
    .tautan a:hover{border-color:var(--accent);background:rgba(var(--accent-rgb),.12);
                    transform:translateY(-2px)}
    .tautan svg{width:14px;height:14px;color:var(--accent);flex:none}

    /* ---------------- seksi konten bawah ---------------- */
    .wrap{max-width:1040px;margin:0 auto;
          padding:clamp(6px,2vw,18px) clamp(16px,4vw,48px) clamp(40px,6vw,72px);
          display:flex;flex-direction:column;gap:clamp(14px,2.4vw,22px)}
    .blok{border:1px solid var(--line);border-radius:18px;padding:clamp(20px,3vw,30px);
          background:linear-gradient(150deg,rgba(28,14,54,.5),rgba(10,4,24,.45))}
    .blok__judul{font-family:var(--display);font-size:clamp(13px,2vw,18px);
                 color:var(--accent);letter-spacing:.5px;margin-bottom:16px;
                 display:flex;align-items:center;gap:11px}
    .blok__judul::before{content:"";width:20px;height:3px;border-radius:3px;
                         background:var(--accent);flex:none}

    .cerita{font-size:14.5px;line-height:1.9;color:var(--muted);white-space:pre-line}
    .cerita.terpotong{display:-webkit-box;-webkit-line-clamp:6;line-clamp:6;
                      -webkit-box-orient:vertical;overflow:hidden}
    .lanjut{display:inline-flex;align-items:center;gap:8px;background:none;border:none;
            cursor:pointer;padding:0;margin-top:14px;font-family:var(--body);
            font-size:11px;font-weight:700;letter-spacing:1.6px;text-transform:uppercase;
            color:var(--accent)}
    .lanjut:hover{color:var(--accent-soft)}

    .spek{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:12px}
    .spek__item{display:flex;flex-direction:column;gap:5px;padding:13px 16px;
                border:1px solid var(--line);border-radius:12px;background:rgba(10,4,24,.4)}
    .spek__k{font-size:9.5px;letter-spacing:1.8px;text-transform:uppercase;color:var(--accent-soft)}
    .spek__v{font-size:13.5px;font-weight:600;color:var(--ink);line-height:1.45}

    /* pemutar video gameplay (lite-embed: poster dulu, iframe saat diklik) */
    .video{position:relative;width:100%;aspect-ratio:16/9;border-radius:14px;
           overflow:hidden;background:#05010e center/cover no-repeat;
           border:1px solid var(--line);box-shadow:0 20px 60px rgba(0,0,0,.5)}
    .video::before{content:"";position:absolute;inset:0;z-index:1;pointer-events:none;
                   background:radial-gradient(60% 60% at 50% 50%,transparent 40%,rgba(5,1,14,.55) 100%)}
    .video.main::before{display:none}
    .video__play{position:absolute;inset:0;margin:auto;z-index:2;width:74px;height:74px;
                 border:none;border-radius:50%;background:var(--accent);color:#05010e;
                 display:flex;align-items:center;justify-content:center;cursor:pointer;
                 box-shadow:0 0 34px rgba(var(--accent-rgb),.6);
                 transition:transform .2s ease,box-shadow .2s ease}
    .video__play:hover{transform:scale(1.08);box-shadow:0 0 46px rgba(var(--accent-rgb),.85)}
    .video__play svg{width:30px;height:30px;margin-left:4px}
    .video__cap{position:absolute;left:0;right:0;bottom:0;z-index:2;padding:26px 20px 16px;
                font-size:12px;letter-spacing:.4px;color:var(--muted);pointer-events:none;
                background:linear-gradient(to top,rgba(5,1,14,.85),transparent)}
    .video iframe{position:absolute;inset:0;width:100%;height:100%;border:0;z-index:3}
    .video__skel{position:absolute;inset:0;z-index:2;display:flex;align-items:center;
                 justify-content:center;font-size:11px;letter-spacing:2px;text-transform:uppercase;
                 color:var(--muted-2)}

    /* ---------------- keadaan kosong ---------------- */
    .kosong{text-align:center;padding:clamp(60px,10vw,120px) 20px;color:var(--muted)}
    .kosong__ikon{width:60px;height:60px;margin:0 auto 20px;border-radius:50%;
                  border:1px dashed var(--line);display:flex;align-items:center;
                  justify-content:center;color:var(--violet-soft)}
    .kosong__ikon svg{width:24px;height:24px}
    .kosong h2{font-family:var(--display);font-size:clamp(17px,3vw,24px);
               color:var(--ink);margin-bottom:12px}
    .kosong p{font-size:14px;max-width:460px;margin:0 auto 24px;line-height:1.75}
    .kosong code{color:var(--accent);background:rgba(var(--accent-rgb),.08);
                 padding:2px 7px;border-radius:5px;font-size:13px}
    .teknis{max-width:640px;margin:0 auto 26px;text-align:left;border:1px solid var(--line);
            border-radius:11px;background:rgba(20,10,40,.5);padding:14px 16px}
    .teknis summary{cursor:pointer;font-size:11.5px;letter-spacing:1.6px;
                    text-transform:uppercase;color:var(--accent);font-weight:700}
    .teknis__label{font-size:10.5px;letter-spacing:1.4px;text-transform:uppercase;
                   color:var(--violet-soft);margin:14px 0 5px}
    .teknis pre{background:rgba(0,0,0,.4);border:1px solid var(--line);border-radius:8px;
                padding:11px;font-size:12px;color:var(--muted);overflow:auto;
                white-space:pre-wrap;word-break:break-all}

    /* ---------------- modal genre ---------------- */
    .modal-content{background:linear-gradient(160deg,#170c2c,#0a0418);
                   border:1px solid var(--line);color:var(--ink);border-radius:16px}
    .modal-header{border-bottom:1px solid var(--line);padding:15px 20px}
    .modal-title{font-family:var(--display);font-size:clamp(13px,3.4vw,17px)!important;
                 letter-spacing:1px;color:var(--accent);text-shadow:0 0 18px rgba(var(--accent-rgb),.4)}
    .modal-body{padding:20px}
    .modal-body h2{font-family:var(--display);font-size:12px;letter-spacing:.5px;
                   color:var(--violet-soft);margin-bottom:8px;padding-bottom:7px;
                   border-bottom:1px solid rgba(139,99,232,.18)}
    .modal-body a{display:block;color:var(--muted);font-size:13.5px;line-height:1.35;
                  padding:7px 0;transition:color .2s ease,transform .2s ease}
    .modal-body a:hover{color:var(--accent);transform:translateX(3px)}
    .genre-col{margin-bottom:16px}

    /* ---------------- footer ---------------- */
    .foot{background:var(--bg);border-top:1px solid var(--line);
          padding:24px clamp(16px,4vw,48px);display:flex;align-items:center;
          justify-content:space-between;flex-wrap:wrap;gap:10px;font-size:11px;
          letter-spacing:1.6px;text-transform:uppercase;color:var(--muted-2)}
    .foot .jp{font-family:var(--jp);color:var(--violet-soft)}

    /* ---------------- responsive ---------------- */
    @media (max-width:820px){
      .sheet{grid-template-columns:1fr}
      .sheet__art{height:clamp(220px,52vw,340px)}
      .sheet__art img{object-position:center 22%}
      .sheet__art::after{background:linear-gradient(to bottom,transparent 55%,rgba(10,4,24,.65) 100%)}
      .fakta__v{max-width:none;white-space:normal}
    }
    @media (max-width:640px){
      .nav{padding:16px 14px}
      .pill{padding:9px 13px;font-size:10px;letter-spacing:.9px}
      .brand{font-size:13.5px}
      /* flex-grow SENGAJA 0 (bukan 1): dengan grow:1, saat salah satu
         nilai kosong ("—") total isi baris jadi sedikit, dan flexbox
         meregangkan SEMUA kartu (Rilis/Developer/Genre) sama rata untuk
         mengisi baris — termasuk yang kosong, jadi terlihat aneh melebar.
         Tanpa grow, tiap kartu menyesuaikan lebar isinya sendiri: yang
         kosong otomatis "menyusut" wajar, yang berisi tetap secukupnya. */
      .fakta{gap:7px}.fakta__item{flex:0 1 auto}
      .spek{grid-template-columns:1fr}
      .play{flex-direction:row}
    }
    @media (max-width:575px){
      .modal-dialog{margin:10px}.modal-body{padding:16px}
      .modal-body a{font-size:14px;padding:12px 0}
    }
    @media (prefers-reduced-motion:reduce){
      .pill,.tautan a,.play{transition:none}
      .tautan a:hover,.play:hover{transform:none}
    }
    :focus-visible{outline:2px solid var(--accent);outline-offset:3px}
  </style>
</head>

<body>

  <nav class="nav">
    <a href="<?= htmlspecialchars($cfg['back_href']) ?>" class="brand">SEEKER</a>
    <div class="nav__right">
      <a href="<?= htmlspecialchars($cfg['back_href']) ?>" class="pill"><?= htmlspecialchars($cfg['back_label']) ?></a>
      <button type="button" class="pill" data-bs-toggle="modal" data-bs-target="#genreModal">
        Genre &#9662;
      </button>
    </div>
  </nav>

  <?php if (!$D['ada']) : ?>
    <div class="kosong">
      <div class="kosong__ikon">
        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
          <path d="M12 7.5v5.5M12 16.2v.3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </div>
      <h2>Detail tidak tersedia</h2>
      <p>
        <?php if ($D['galat'] !== null) : ?>
          <?= htmlspecialchars($D['galat']) ?>
        <?php else : ?>
          DBpedia tidak memiliki keterangan berbahasa Inggris untuk
          <code><?= htmlspecialchars(rapikan($D['game']) ?? '-') ?></code>.
          Beberapa judul memang belum terdaftar di sana.
        <?php endif; ?>
      </p>

      <?php if ($D['galat_teknis'] !== null || $D['game'] !== '') : ?>
        <details class="teknis">
          <summary>Rincian teknis</summary>
          <?php if ($D['galat_teknis'] !== null) : ?>
            <p class="teknis__label">Pesan galat asli</p>
            <pre><?= htmlspecialchars($D['galat_teknis']) ?></pre>
          <?php endif; ?>
          <p class="teknis__label">Nilai yang diterima dari kartu</p>
          <pre><?= htmlspecialchars($D['kiriman'] === '' ? '(kosong)' : $D['kiriman']) ?></pre>
          <p class="teknis__label">Nama sumber daya hasil olahan</p>
          <pre><?= htmlspecialchars($D['game'] === '' ? '(kosong)' : $D['game']) ?></pre>
        </details>
      <?php endif; ?>
      <div class="tautan" style="justify-content:center">
        <a href="<?= htmlspecialchars($cfg['back_href']) ?>">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19 12H6M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <?= htmlspecialchars($cfg['kembali_label']) ?>
        </a>
      </div>
    </div>

  <?php else : ?>
    <section class="hero">
      <div class="hero__wash" style="background-image:url('<?= htmlspecialchars($washImg) ?>')"></div>
      <div class="hero__veil"></div>

      <div class="hero__inner">
        <div class="sheet">
          <div class="sheet__art">
            <img src="<?= htmlspecialchars($D['gambar']) ?>" alt="">
          </div>

          <div class="sheet__body">
            <span class="kicker"><?= htmlspecialchars($cfg['kicker']) ?></span>
            <h1 class="judul"><?= htmlspecialchars($D['judul']) ?></h1>
            <?php if ($D['desc_pendek'] !== null) : ?>
              <p class="tagline"><?= htmlspecialchars($D['desc_pendek']) ?></p>
            <?php endif; ?>

            <div class="fakta">
              <div class="fakta__item">
                <span class="fakta__k">Rilis</span>
                <span class="fakta__v <?= $D['tanggal'] ? '' : 'hampa' ?>"><?= htmlspecialchars($D['tanggal'] ?? '—') ?></span>
              </div>
              <div class="fakta__item">
                <span class="fakta__k">Developer</span>
                <span class="fakta__v <?= $D['devp'] ? '' : 'hampa' ?>"><?= htmlspecialchars($D['devp'] ?? '—') ?></span>
              </div>
              <div class="fakta__item">
                <span class="fakta__k">Genre</span>
                <span class="fakta__v <?= $D['genre'] ? '' : 'hampa' ?>"><?= htmlspecialchars($D['genre'] ?? '—') ?></span>
              </div>
            </div>

            <div class="tautan">
              <?php if (!empty($D['wiki'])) : ?>
                <a href="<?= htmlspecialchars($D['wiki']) ?>" target="_blank" rel="noopener">
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"
                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                  Wikipedia
                </a>
              <?php endif; ?>
              <a href="<?= htmlspecialchars($cfg['back_href']) ?>">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M19 12H6M11 6l-6 6 6 6" stroke="currentColor" stroke-width="2.2"
                        stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Kembali
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div class="wrap">
      <?php if ($D['overview'] !== null) : ?>
        <section class="blok">
          <h2 class="blok__judul">Tentang Game</h2>
          <div class="cerita terpotong" id="cerita"><?= htmlspecialchars($D['overview']) ?></div>
          <button type="button" class="lanjut" id="lanjut">Baca selengkapnya &#9662;</button>
        </section>
      <?php endif; ?>

      <!-- Pemutar video gameplay. Tersembunyi sampai JS menemukan
           videonya, lalu poster + tombol putar bergaya tema muncul;
           iframe YouTube baru dimuat setelah tombol diklik. -->
      <section class="blok" id="vidblok" hidden>
        <h2 class="blok__judul">Gameplay</h2>
        <div class="video" id="video" data-judul="<?= htmlspecialchars($D['judul']) ?>"></div>
      </section>

      <?php if (!empty($spek)) : ?>
        <section class="blok">
          <h2 class="blok__judul">Spesifikasi</h2>
          <div class="spek">
            <?php foreach ($spek as $k => $v) : ?>
              <div class="spek__item">
                <span class="spek__k"><?= htmlspecialchars($k) ?></span>
                <span class="spek__v"><?= htmlspecialchars($v) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <section class="blok">
        <h2 class="blok__judul">Jelajahi Lebih Lanjut</h2>
        <div class="tautan">
          <?php if (!empty($D['situs'])) : ?>
            <a href="<?= htmlspecialchars($D['situs']) ?>" target="_blank" rel="noopener">
              <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/>
                <path d="M3 12h18M12 3c2.5 2.5 3.8 5.6 3.8 9s-1.3 6.5-3.8 9c-2.5-2.5-3.8-5.6-3.8-9S9.5 5.5 12 3z"
                      stroke="currentColor" stroke-width="1.6"/>
              </svg>
              Situs Resmi
            </a>
          <?php endif; ?>
          <a href="<?= htmlspecialchars($dbpUrl) ?>" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="5.5" cy="6" r="2.4" fill="currentColor"/>
              <circle cx="18.5" cy="6" r="2.4" fill="currentColor"/>
              <circle cx="12" cy="18.5" r="2.4" fill="currentColor"/>
              <path d="M5.5 6 L18.5 6 M5.5 6 L12 18.5 M18.5 6 L12 18.5" stroke="currentColor" stroke-width="1.4"/>
            </svg>
            Sumber DBpedia
          </a>
        </div>
      </section>
    </div>
  <?php endif; ?>

  <footer class="foot">
    <span class="jp"><?= htmlspecialchars($cfg['foot_jp']) ?></span>
    <span>DBpedia &middot; SPARQL &middot; Wikipedia</span>
  </footer>

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
              <a href="./action_adventure.php">Action-Adventure</a>
              <a href="./action_fantasy.php">Action-Fantasy</a>
              <a href="./action_horror.php">Action-Horror</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Adventure</h2>
              <a href="./adventure_mystery.php">Adventure-Mystery</a>
              <a href="./adventure_point_and_click.php">Point &amp; Click</a>
              <a href="./adventure_visual_novel.php">Visual Novel</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Role-Playing</h2>
              <a href="./rpg_action.php">RPG-Action</a>
              <a href="./rpg_adventure.php">RPG-Adventure</a>
              <a href="./rpg_strategy.php">RPG-Strategy</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Simulation</h2>
              <a href="./simulation_car.php">Simulation-Car</a>
              <a href="./simulation_life.php">Simulation-Life</a>
              <a href="./simulation_flight.php">Simulation-Flight</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Sport</h2>
              <a href="./sport_extreme.php">Sport-Extreme</a>
              <a href="./sport_racing.php">Sport-Racing</a>
              <a href="./sport_simulation.php">Sport-Simulation</a>
            </div>
            <div class="col-12 col-sm-6 col-md-4 genre-col">
              <h2>Strategy</h2>
              <a href="./strategy_real_time.php">Strategy-Real Time</a>
              <a href="./strategy_scifi.php">Strategy-Scify</a>
              <a href="./strategy_simulation.php">Strategy-Simulation</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  <script>
    (function () {
      var teks = document.getElementById('cerita');
      var tbl  = document.getElementById('lanjut');
      if (!teks || !tbl) return;
      // Sembunyikan tombol bila teksnya memang tidak terpotong.
      if (teks.scrollHeight <= teks.clientHeight + 2) { tbl.style.display = 'none'; return; }
      tbl.addEventListener('click', function () {
        var tertutup = teks.classList.toggle('terpotong');
        tbl.innerHTML = tertutup ? 'Baca selengkapnya &#9662;' : 'Ringkas &#9652;';
      });
    })();

    // Video gameplay: ambil ID video secara asinkron (halaman sudah
    // tampil lebih dulu), tampilkan poster bergaya tema, dan baru muat
    // iframe YouTube saat diklik — supaya di keadaan diam tak terlihat
    // seperti YouTube.
    (function () {
      var blok = document.getElementById('vidblok');
      var box  = document.getElementById('video');
      if (!blok || !box) return;
      var judul = (box.getAttribute('data-judul') || '').trim();
      if (!judul) return;

      fetch('../includes/video.php?q=' + encodeURIComponent(judul))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.id) return;
          var id = d.id;
          box.style.backgroundImage = "url('https://i.ytimg.com/vi/" + id + "/hqdefault.jpg')";
          box.style.cursor = 'pointer';
          box.innerHTML =
            '<button class="video__play" type="button" aria-label="Putar video gameplay">' +
            '<svg viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">' +
            '<path d="M8 5v14l11-7z"/></svg></button>' +
            '<span class="video__cap">Klik untuk memutar</span>';
          blok.hidden = false;

          var putar = function () {
            box.classList.add('main');
            box.style.cursor = 'default';
            box.innerHTML =
              '<iframe src="https://www.youtube-nocookie.com/embed/' + id +
              '?autoplay=1&rel=0&modestbranding=1&playsinline=1" ' +
              'title="Video gameplay" allow="autoplay; encrypted-media; ' +
              'picture-in-picture; fullscreen" allowfullscreen></iframe>';
          };
          box.addEventListener('click', putar, { once: true });
        })
        .catch(function () { /* biarkan tersembunyi bila gagal */ });
    })();
  </script>
  <?php require __DIR__ . '/memuat.php'; ?>
</body>

</html>
