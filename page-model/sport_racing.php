<?php
// EasyRdf 1.x belum selaras penuh dengan PHP 8.2, sehingga memunculkan
// banyak pemberitahuan "Deprecated" yang tercetak di atas halaman dan
// merusak tata letak. Pesan itu bukan penanda kesalahan program.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once realpath(__DIR__ . '/..') . "/vendor/autoload.php";
require_once __DIR__ . "/../includes/html_tag_helpers.php";
require_once __DIR__ . "/../vendor/easyrdf/easyrdf/lib/Graph.php";
require_once __DIR__ . "/../vendor/easyrdf/easyrdf/lib/GraphStore.php";

// Setup some additional prefixes for DBpedia
\EasyRdf\RdfNamespace::set('dbc', 'http://dbpedia.org/resource/Category:');
\EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
\EasyRdf\RdfNamespace::set('dbpedia', 'http://dbpedia.org/property/');
\EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
\EasyRdf\RdfNamespace::set('games', 'https://example.org/schema/games');
\EasyRdf\RdfNamespace::set('dbp', 'http://dbpedia.org/property/');

$sparql = new \EasyRdf\Sparql\Client('https://dbpedia.org/sparql');
$sparql_jena_Sport = new \EasyRdf\Sparql\Client('http://localhost:3030/Sport/sparql');

// ------------------------------------------------------------------
// Query ke dataset "Sport" di Fuseki (logika asli, tidak diubah)
// ------------------------------------------------------------------
$save1 = 'SELECT DISTINCT * WHERE {
    ?game rdf:type games:sport-racing;
    games:abstract ?desc;
    games:releaseDate ?date;
    games:platforms ?pltf;
    foaf:isPrimaryTopicOf ?wiki.
}';

$result = $sparql_jena_Sport->query($save1);

// Slide unggulan pada carousel
$featured = [
  [
    'bg'    => 'https://i.redd.it/jazimt9w3ck81.gif',
    'cover'    => 'https://upload.wikimedia.org/wikipedia/en/1/14/Gran_Turismo_7_cover_art.jpg',
    'uri'    => 'https://dbpedia.org/page/Gran_Turismo_7',
    'nama'    => 'Gran Turismo 7',
    'dev'    => 'Polyphony Digital',
    'year'    => '2022',
    'desc'    => 'Gran Turismo 7 is a 2022 racing simulation developed by Polyphony Digital, the eighth mainline entry in the Gran Turismo series. It combines a vast car collection with realistic physics, a returning Café career mode, and dynamic weather across real and fictional circuits.',
  ],
  [
    'bg'    => 'https://cdn.akamai.steamstatic.com/steam/apps/1038250/extras/jump.gif',
    'cover'    => 'https://upload.wikimedia.org/wikipedia/en/2/2d/Dirt_5_cover_art.jpg',
    'uri'    => 'https://dbpedia.org/page/Dirt_5',
    'nama'    => 'Dirt 5',
    'dev'    => 'Codemasters',
    'year'    => '2020',
    'desc'    => 'Dirt 5 is a 2020 simcade off-road racing game developed and published by Codemasters. Players race across twelve countries in a variety of disciplines, from rally raid to ice racing, with a dynamic weather system affecting every track.',
  ],
  [
    'bg'    => 'https://media.giphy.com/media/yzccNgU1cEZSI2S28c/giphy-downsized-large.gif',
    'cover'    => 'https://upload.wikimedia.org/wikipedia/en/8/86/Forza_Horizon_5_cover_art.jpg',
    'uri'    => 'https://dbpedia.org/page/Forza_Horizon_5',
    'nama'    => 'Forza Horizon 5',
    'dev'    => 'Playground Games',
    'year'    => '2021',
    'desc'    => 'Forza Horizon 5 is a 2021 open-world racing game developed by Playground Games, the fifth Horizon title. Set in a vibrant, fictionalised Mexico, players explore volcanoes, jungles, and deserts while competing in the ever-expanding Horizon Festival.',
  ],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Sport-Racing — Smart Game Seeker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Bungee&family=Inter:wght@400;500;600;700&family=Noto+Sans+JP:wght@500;700;900&display=swap" rel="stylesheet">

  <style>
    /* =========================================================
       TOKEN — sama persis dengan index.php & lokal.php
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

    /* ---------------- nav ---------------- */
    .nav {
      position: relative; z-index: 40;
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px;
      padding: 20px clamp(16px, 4vw, 48px);
      background: var(--bg);
    }
    .brand {
      display: flex; align-items: center; gap: 11px;
      font-family: var(--display);
      font-size: clamp(14px, 2vw, 18px);
      color: var(--ink);
      text-shadow: 0 0 18px rgba(74, 242, 25, .45);
    }
    .nav__right { display: flex; align-items: center; gap: 9px; }
    .pill {
      display: inline-flex; align-items: center; gap: 9px;
      padding: 10px 18px;
      border: 1px solid var(--line); border-radius: 999px;
      background: var(--glass);
      backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px);
      font-size: 11px; font-weight: 600; letter-spacing: 1.3px;
      text-transform: uppercase; color: var(--ink); cursor: pointer;
      transition: border-color .25s ease, background .25s ease,
                  transform .25s ease, box-shadow .25s ease;
    }
    .pill:hover {
      border-color: var(--lime); background: rgba(74,242,25,.12);
      transform: translateY(-1px); box-shadow: 0 0 22px rgba(74,242,25,.2);
    }

    /* =========================================================
       CAROUSEL UNGGULAN
       ========================================================= */
    .feat { position: relative; background: var(--bg); }

    .feat__head {
      display: flex; align-items: flex-end; justify-content: space-between;
      gap: 14px; flex-wrap: wrap;
      padding: 6px clamp(16px, 4vw, 48px) 18px;
    }
    .feat__title {
      font-family: var(--display);
      font-size: clamp(22px, 4.4vw, 42px);
      line-height: 1.05;
      text-shadow: 0 0 26px rgba(74, 242, 25, .35);
    }
    .feat__title .lime { color: var(--lime); }
    .feat__sub {
      font-family: var(--jp); font-size: 12px;
      letter-spacing: 5px; color: var(--violet-soft);
      margin-top: 8px;
    }
    .feat__meta {
      font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
      color: var(--muted); padding-bottom: 4px;
    }

    /* Tanpa border-radius & box-shadow: keduanya menggambar garis/halo
       tepi yang justru terlihat. Tepi carousel kini "ditutup" murni oleh
       bayangan gradien (.slide__veil) yang menyatu ke warna latar. */
    .stage {
      position: relative;
      margin: 0 clamp(0px, 2vw, 28px);
      overflow: hidden;
    }

    .slide {
      position: relative;
      height: clamp(420px, 62vw, 620px);
      overflow: hidden;
      background: #000;
    }
    .slide__bg {
      position: absolute; inset: 0;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      transform: scale(1.04);
      transition: transform 8s ease-out;
    }
    .carousel-item.active .slide__bg { transform: scale(1.12); }

    /* Bayangan tepi: paling pekat (sama dengan warna latar, jadi tak
       ada seam) tepat di ujung kiri-kanan, lalu memudar ke bening saat
       mendekati tengah — makin ke ujung makin tebal. Sama untuk atas
       & bawah, sehingga seluruh tepi carousel larut ke halaman. */
    .slide__veil {
      position: absolute; inset: 0; pointer-events: none; z-index: 11;
      background:
        linear-gradient(to right,
          var(--bg) 0%, var(--bg) 3%, rgba(10,4,24,.88) 11%,
          rgba(10,4,24,.42) 20%, rgba(10,4,24,0) 36%,
          rgba(10,4,24,0) 64%, rgba(10,4,24,.42) 80%,
          rgba(10,4,24,.88) 89%, var(--bg) 97%, var(--bg) 100%),
        linear-gradient(to bottom,
          var(--bg) 0%, rgba(10,4,24,.6) 6%, rgba(10,4,24,0) 24%,
          rgba(10,4,24,.5) 60%, rgba(10,4,24,.96) 90%, var(--bg) 100%);
    }
    /* garis pemindai tipis, menegaskan nuansa "layar" */
    .slide__scan {
      position: absolute; inset: 0; pointer-events: none; opacity: .16;
      background: repeating-linear-gradient(
        to bottom, rgba(255,255,255,.7) 0 1px, transparent 1px 4px);
      mix-blend-mode: overlay;
    }

    /* label slide */
    .slide__tag {
      position: absolute; top: 26px; left: 30px; z-index: 14;
      display: inline-flex; align-items: center; gap: 8px;
      padding: 7px 14px;
      border: 1px solid rgba(74,242,25,.4); border-radius: 999px;
      background: rgba(10,4,24,.6); backdrop-filter: blur(8px);
      font-size: 10px; font-weight: 700; letter-spacing: 2px;
      text-transform: uppercase; color: var(--lime);
    }

    /* kartu kaca berisi keterangan game */
    .slide__panel {
      position: absolute; z-index: 15;
      left: clamp(20px, 5vw, 64px);
      right: clamp(20px, 5vw, 64px);
      bottom: clamp(52px, 7vw, 74px);
      display: flex; justify-content: flex-start;
    }
    /* Kaca ungu tembus pandang: sengaja beralpha rendah + blur ringan
       supaya gambar carousel di belakangnya masih samar terlihat. */
    .gcard {
      display: flex; gap: 0;
      width: 100%;
      text-align: left;
      border: 1px solid rgba(139,99,232,.5);
      border-radius: 16px;
      overflow: hidden;
      background: linear-gradient(140deg, rgba(90,49,179,.34), rgba(139,99,232,.12));
      backdrop-filter: blur(11px) saturate(1.25); -webkit-backdrop-filter: blur(11px) saturate(1.25);
      box-shadow: 0 24px 60px rgba(0,0,0,.5);
      cursor: pointer;
      transition: border-color .3s ease, transform .3s ease, box-shadow .3s ease, background .3s ease;
    }
    .gcard:hover {
      border-color: var(--violet-soft);
      transform: translateY(-4px);
      background: linear-gradient(140deg, rgba(90,49,179,.44), rgba(139,99,232,.18));
      box-shadow: 0 30px 70px rgba(0,0,0,.55), 0 0 34px rgba(139,99,232,.32);
    }
    .gcard__art {
      width: clamp(110px, 17vw, 190px);
      flex: none;
      object-fit: cover;
      display: block;
      background: rgba(0,0,0,.25);
      border-right: 1px solid rgba(139,99,232,.4);
    }
    .gcard__body { padding: 20px 24px; min-width: 0; }
    .gcard__kicker {
      display: flex; align-items: center; gap: 10px;
      font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
      color: var(--violet-soft); margin-bottom: 9px;
    }
    .gcard__kicker span { color: var(--muted-2); }
    .gcard__title {
      font-family: var(--display);
      font-size: clamp(16px, 2.2vw, 26px);
      line-height: 1.15; color: var(--ink);
      margin-bottom: 10px;
    }
    .gcard__desc {
      font-size: 12.5px; line-height: 1.65; color: var(--muted);
      display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .gcard__cta {
      display: inline-flex; align-items: center; gap: 8px;
      margin-top: 14px;
      font-size: 10.5px; font-weight: 700; letter-spacing: 1.8px;
      text-transform: uppercase; color: var(--violet-soft);
    }
    .gcard__cta svg { width: 13px; height: 13px; transition: transform .25s ease; }
    .gcard:hover .gcard__cta svg { transform: translateX(4px); }

    /* kontrol carousel */
    .carousel-control-prev, .carousel-control-next {
      width: clamp(46px, 6vw, 78px);
      opacity: 1; z-index: 20;
    }
    .carousel-control-prev-icon, .carousel-control-next-icon {
      width: 42px; height: 42px;
      border-radius: 50%;
      border: 1px solid var(--line);
      background-color: rgba(10,4,24,.62);
      backdrop-filter: blur(8px);
      background-size: 42% 42%;
      transition: border-color .25s ease, background-color .25s ease, transform .25s ease;
    }
    .carousel-control-prev:hover .carousel-control-prev-icon,
    .carousel-control-next:hover .carousel-control-next-icon {
      border-color: var(--lime);
      background-color: rgba(74,242,25,.16);
      transform: scale(1.08);
    }

    /* indikator berbentuk batang */
    .carousel-indicators {
      z-index: 20; margin-bottom: 20px; gap: 8px;
    }
    .carousel-indicators [data-bs-target] {
      width: 40px; height: 4px;
      border-radius: 999px;
      border: none;
      background-color: rgba(242,255,240,.22);
      opacity: 1;
      transition: background-color .3s ease, width .3s ease;
    }
    .carousel-indicators .active {
      width: 62px;
      background-color: var(--lime);
      box-shadow: 0 0 14px rgba(74,242,25,.7);
    }

    /* =========================================================
       DAFTAR GAME — kartu kaca, 4 kolom di desktop
       ========================================================= */
    .list {
      padding: clamp(44px, 6vw, 78px) clamp(16px, 4vw, 48px) clamp(56px, 7vw, 90px);
    }
    .list__head {
      display: flex; align-items: baseline; justify-content: space-between;
      gap: 14px; flex-wrap: wrap; margin-bottom: 30px;
    }
    .list__title {
      font-family: var(--display);
      font-size: clamp(18px, 3.2vw, 30px);
      letter-spacing: .5px;
    }
    .list__title .lime { color: var(--lime); }
    .list__count {
      font-size: 11px; letter-spacing: 2px; text-transform: uppercase;
      color: var(--muted);
    }

    /* Empat kolom pada layar lebar, menurun bertahap ke satu kolom. */
    .grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 18px;
    }
    @media (max-width: 1200px) { .grid { grid-template-columns: repeat(3, minmax(0,1fr)); } }
    @media (max-width: 900px)  { .grid { grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (max-width: 520px)  { .grid { grid-template-columns: 1fr; } }

    .tile {
      position: relative;
      display: flex; flex-direction: column;
      width: 100%; height: 100%; text-align: left;
      border: 1px solid var(--line);
      border-radius: 16px;
      overflow: hidden;
      background: linear-gradient(160deg, rgba(28,14,54,.6), rgba(16,8,34,.86));
      backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
      cursor: pointer;
      transition: transform .3s cubic-bezier(.2,.8,.3,1),
                  border-color .3s ease, box-shadow .3s ease;
    }
    .tile:hover {
      transform: translateY(-7px);
      border-color: var(--lime);
      box-shadow: 0 22px 50px rgba(0,0,0,.6), 0 0 32px rgba(74,242,25,.2);
    }

    .tile__media { position: relative; overflow: hidden; height: 200px; flex: none; }
    .tile__img {
      width: 100%; height: 100%;
      object-fit: cover; display: block; background: #000;
      transition: transform .55s cubic-bezier(.2,.8,.3,1), filter .4s ease;
    }
    .tile:hover .tile__img { transform: scale(1.09); filter: saturate(1.15); }

    /* kilau diagonal yang melintas saat kursor menyentuh kartu */
    .tile__sheen {
      position: absolute; inset: 0; pointer-events: none;
      background: linear-gradient(115deg,
        transparent 38%, rgba(74,242,25,.16) 50%, transparent 62%);
      transform: translateX(-120%);
      transition: transform .75s cubic-bezier(.2,.8,.3,1);
    }
    .tile:hover .tile__sheen { transform: translateX(120%); }

    .tile__fade {
      position: absolute; left: 0; right: 0; bottom: 0; height: 62%;
      pointer-events: none;
      background: linear-gradient(to top, rgba(16,8,34,.97), rgba(16,8,34,0));
    }
    .tile__date {
      position: absolute; top: 12px; left: 12px; z-index: 3;
      padding: 5px 11px;
      border: 1px solid rgba(74,242,25,.34);
      border-radius: 999px;
      background: rgba(10,4,24,.66);
      backdrop-filter: blur(6px);
      font-size: 10px; font-weight: 700; letter-spacing: 1.2px;
      color: var(--lime);
    }

    .tile__body {
      position: relative; z-index: 3;
      padding: 0 18px 18px;
      margin-top: -34px;
      display: flex; flex-direction: column; gap: 9px; flex: 1;
    }
    .tile__title {
      font-family: var(--display);
      font-size: 14px; line-height: 1.3; color: var(--ink);
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
      overflow: hidden;
      transition: color .25s ease;
    }
    .tile:hover .tile__title { color: var(--lime-soft); }
    .tile__plat {
      font-size: 11.5px; line-height: 1.5; color: var(--muted);
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .tile__foot {
      margin-top: auto; padding-top: 12px;
      border-top: 1px solid rgba(139,99,232,.2);
      display: flex; align-items: center; justify-content: space-between;
      font-size: 10px; letter-spacing: 1.6px; text-transform: uppercase;
      color: var(--muted-2);
    }
    .tile__foot svg {
      width: 13px; height: 13px; color: var(--lime);
      transition: transform .25s ease;
    }
    .tile:hover .tile__foot svg { transform: translateX(4px); }

    /* keadaan kosong */
    .empty { text-align: center; padding: 56px 20px; color: var(--muted); }
    .empty__icon {
      width: 54px; height: 54px; margin: 0 auto 16px;
      border-radius: 50%; border: 1px dashed var(--line);
      display: flex; align-items: center; justify-content: center;
      color: var(--violet-soft);
    }
    .empty__icon svg { width: 22px; height: 22px; }
    .empty h4 { font-family: var(--display); font-size: 17px; color: var(--ink); margin-bottom: 10px; }
    .empty p { font-size: 13.5px; max-width: 440px; margin: 0 auto; line-height: 1.7; }
    .empty code {
      color: var(--lime); background: rgba(74,242,25,.08);
      padding: 2px 7px; border-radius: 5px; font-size: 12.5px;
    }

    /* ---------------- modal genre ---------------- */
    .modal-content {
      background: linear-gradient(160deg, #170c2c, #0a0418);
      border: 1px solid var(--line); color: var(--ink); border-radius: 16px;
    }
    .modal-header { border-bottom: 1px solid var(--line); padding: 15px 20px; }
    .modal-title {
      font-family: var(--display);
      font-size: clamp(13px, 3.4vw, 17px) !important;
      letter-spacing: 1px; color: var(--lime);
      text-shadow: 0 0 18px rgba(74,242,25,.4);
    }
    .modal-body { padding: 20px; }
    .modal-body h2 {
      font-family: var(--display); font-size: 12px; letter-spacing: .5px;
      color: var(--violet-soft);
      margin-bottom: 8px; padding-bottom: 7px;
      border-bottom: 1px solid rgba(139,99,232,.18);
    }
    .modal-body a {
      display: block; color: var(--muted); font-size: 13.5px;
      line-height: 1.35; padding: 7px 0;
      transition: color .2s ease, transform .2s ease;
    }
    .modal-body a:hover { color: var(--lime); transform: translateX(3px); }
    .modal-body a.is-current { color: var(--lime); font-weight: 600; }
    .genre-col { margin-bottom: 16px; }

    /* ---------------- footer ---------------- */
    .foot {
      background: var(--bg); border-top: 1px solid var(--line);
      padding: 24px clamp(16px, 4vw, 48px);
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 10px;
      font-size: 11px; letter-spacing: 1.6px; text-transform: uppercase;
      color: var(--muted-2);
    }
    .foot .jp { font-family: var(--jp); color: var(--violet-soft); }

    /* ---------------- responsive ---------------- */
    @media (max-width: 900px) {
      .gcard { flex-direction: column; }
      .gcard__art { width: 100%; height: 130px; border-right: none;
                    border-bottom: 1px solid rgba(139,99,232,.28); }
      .gcard__desc { -webkit-line-clamp: 3; }
      .slide__panel { left: 16px; right: 16px; bottom: 46px; }
      .slide { height: clamp(440px, 96vw, 620px); }
    }
    @media (max-width: 640px) {
      .nav { padding: 16px 14px; }
      .pill { padding: 9px 13px; font-size: 10px; letter-spacing: .9px; }
      .brand { font-size: 13.5px; }
      .slide__tag { top: 16px; left: 16px; padding: 6px 11px; font-size: 9px; }
      .gcard__body { padding: 15px 17px; }
      .gcard__desc { -webkit-line-clamp: 2; }
      .stage { margin: 0 8px; }
      .tile__media { height: 180px; }
    }
    @media (max-width: 575px) {
      .modal-dialog { margin: 10px; }
      .modal-body { padding: 16px; }
      .modal-body a { font-size: 14px; padding: 12px 0; }
    }
    @media (prefers-reduced-motion: reduce) {
      .slide__bg, .tile, .tile__img, .tile__sheen, .gcard, .pill { transition: none !important; }
      .carousel-item.active .slide__bg { transform: scale(1.04); }
      .tile:hover { transform: none; }
    }
    :focus-visible { outline: 2px solid var(--lime); outline-offset: 3px; }
  </style>
</head>

<body>

  <!-- ===================== NAV ===================== -->
  <nav class="nav">
    <a href="../index.php" class="brand">
      SEEKER
    </a>
    <div class="nav__right">
      <a href="../index.php" class="pill">Home</a>
      <button type="button" class="pill" data-bs-toggle="modal" data-bs-target="#genreModal">
        Genre &#9662;
      </button>
    </div>
  </nav>


  <!-- ===================== CAROUSEL ===================== -->
  <section class="feat">
    <div class="feat__head">
      <div>
        <h1 class="feat__title">SPORT <span class="lime">RACING</span></h1>
        <p class="feat__sub">レーシングスポーツ</p>
      </div>
    </div>

    <div class="stage">
      <div id="featCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
          <?php foreach ($featured as $i => $f) : ?>
            <button type="button" data-bs-target="#featCarousel" data-bs-slide-to="<?= $i ?>"
                    class="<?= $i === 0 ? 'active' : '' ?>"
                    <?= $i === 0 ? 'aria-current="true"' : '' ?>
                    aria-label="Slide <?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>

        <div class="carousel-inner">
          <?php foreach ($featured as $i => $f) : ?>
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
              <div class="slide">
                <div class="slide__bg" style="background-image: url('<?= $f['bg'] ?>');"></div>
                <div class="slide__veil"></div>
                <div class="slide__scan"></div>

                <span class="slide__tag">Featured</span>

                <div class="slide__panel">
                  <form action="detail_koleksi.php" method="post" style="width: min(880px, 100%);">
                    <input type="hidden" name="game" value="<?= $f['uri'] ?>">
                    <button type="submit" class="gcard">
                      <img class="gcard__art" src="<?= $f['cover'] ?>" alt="">
                      <div class="gcard__body">
                        <div class="gcard__kicker">
                          <?= $f['dev'] ?> <span>&middot;</span> <?= $f['year'] ?>
                        </div>
                        <div class="gcard__title"><?= $f['nama'] ?></div>
                        <p class="gcard__desc"><?= $f['desc'] ?></p>
                        <span class="gcard__cta">
                          Lihat Detail
                          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor"
                                  stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                          </svg>
                        </span>
                      </div>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#featCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#featCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
  </section>


  <!-- ===================== DAFTAR ===================== -->
  <section class="list">
    <div class="list__head">
      <h2 class="list__title">SEMUA <span class="lime">JUDUL</span></h2>
      <span class="list__count"><?= count($result) ?> game &middot; dataset lokal</span>
    </div>

    <?php if (count($result) === 0) : ?>
      <div class="empty">
        <div class="empty__icon">
          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/>
            <path d="M16.5 16.5 L21 21" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
          </svg>
        </div>
        <h4>Belum ada data Sport-Racing</h4>
        <p>
          Dataset <code>Sport</code> pada Fuseki belum berisi triple bertipe
          <code>games:sport-racing</code>, atau server di
          <code>localhost:3030</code> sedang tidak berjalan.
        </p>
      </div>

    <?php else : ?>
      <div class="grid">
        <?php foreach ($result as $row) :
          $seeker = ["wiki" => $row->wiki ?? null];
          $gambar = "https://i.pinimg.com/564x/e8/f1/51/e8f1519b1599f3fc7df008172c87261d.jpg";
          $judul  = "Untitled";

          if (!empty($seeker['wiki'])) {
            \EasyRdf\RdfNamespace::setDefault('og');
            $wiki   = \EasyRdf\Graph::newAndLoad($seeker['wiki']);
            $gambar = $wiki->image ?: $gambar;
            $judul  = str_replace(" - Wikipedia", "", $wiki->title);
          }
        ?>
          <form method="POST" action="./detail_koleksi.php">
            <input type="hidden" name="game" value="<?= $row->game ?>">
            <button type="submit" class="tile">
              <div class="tile__media">
                <img class="tile__img" src="<?= $gambar ?>" alt="">
                <span class="tile__sheen"></span>
                <span class="tile__fade"></span>
                <span class="tile__date"><?= $row->date ?></span>
              </div>
              <div class="tile__body">
                <div class="tile__title"><?= $judul ?></div>
                <div class="tile__plat"><?= $row->pltf ?></div>
                <div class="tile__foot">
                  <span>Detail</span>
                  <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 12h13M13 6l6 6-6 6" stroke="currentColor"
                          stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
              </div>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>


  <!-- ===================== FOOTER ===================== -->
  <footer class="foot">
    <span class="jp">レーシングスポーツ &middot; SEEKER</span>
    <span>Fuseki &middot; RDF &middot; SPARQL</span>
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
              <a href="./sport_racing.php" class="is-current">Sport-Racing</a>
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
  <?php require __DIR__ . '/../includes/memuat.php'; ?>
</body>

</html>