<?php
/* =====================================================================
   PENGUJI QUERY — Smart Game Seeker
   ---------------------------------------------------------------------
   Buka lewat:
   http://localhost/semantic-game-explorer/tools/cek2.php

   Query detail dipecah menjadi potongan bertingkat. Potongan pertama
   yang menghasilkan 0 baris adalah klausa yang menggagalkan query.
   ===================================================================== */

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '1');
@set_time_limit(300);

require_once __DIR__ . '/../vendor/autoload.php';

\EasyRdf\RdfNamespace::set('dbc', 'http://dbpedia.org/resource/Category:');
\EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
\EasyRdf\RdfNamespace::set('dbpedia', 'http://dbpedia.org/property/');
\EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
\EasyRdf\RdfNamespace::set('games', 'https://example.org/schema/games');
\EasyRdf\RdfNamespace::set('dbp', 'http://dbpedia.org/property/');

$nama_game = isset($_GET['g']) && $_GET['g'] !== '' ? $_GET['g'] : 'Elden_Ring';
$nama_game = preg_replace('/[<>"{}|\^`\\\\\s]/', '', $nama_game);
$iri = 'http://dbpedia.org/resource/' . $nama_game;
$S   = '<' . $iri . '>';

$endpoint = isset($_GET['e']) && $_GET['e'] === 'http'
    ? 'http://dbpedia.org/sparql'
    : 'https://dbpedia.org/sparql';

// ---------------------------------------------------------------------
// Daftar uji, dari paling sederhana ke query penuh
// ---------------------------------------------------------------------
$uji = [

  'A. Sumber daya ada isinya?' =>
    "SELECT ?p ?o WHERE{{$S} ?p ?o} LIMIT 3",

  'B. Punya rdfs:label (bahasa apa pun)' =>
    "SELECT ?nama WHERE{{$S} rdfs:label ?nama} LIMIT 5",

  'C. rdfs:label + saringan EN' =>
    "SELECT ?nama WHERE{{$S} rdfs:label ?nama FILTER langMatches(lang(?nama),\"EN\")} LIMIT 3",

  'D. Punya dbo:abstract?' =>
    "SELECT ?desc WHERE{{$S} dbo:abstract ?desc} LIMIT 1",

  'E. dbo:abstract + saringan EN' =>
    "SELECT ?desc WHERE{{$S} dbo:abstract ?desc FILTER langMatches(lang(?desc),\"EN\")} LIMIT 1",

  'F. Bertipe owl:Thing?  <-- tersangka utama' =>
    "SELECT ?t WHERE{{$S} rdf:type ?t FILTER(?t=owl:Thing)} LIMIT 1",

  'G. Semua tipe yang dimiliki' =>
    "SELECT ?t WHERE{{$S} rdf:type ?t} LIMIT 12",

  'H. Inti query TANPA rdf:type owl:Thing' =>
    "SELECT DISTINCT ?nama ?desc WHERE{{$S} dbo:abstract ?desc; rdfs:label ?nama. " .
    "FILTER langMatches(lang(?desc),\"EN\") FILTER langMatches(lang(?nama),\"EN\")} LIMIT 1",

  'I. Inti query DENGAN rdf:type owl:Thing' =>
    "SELECT DISTINCT ?nama ?desc WHERE{{$S} rdf:type owl:Thing; dbo:abstract ?desc; rdfs:label ?nama. " .
    "FILTER langMatches(lang(?desc),\"EN\") FILTER langMatches(lang(?nama),\"EN\")} LIMIT 1",

  'J. Query penuh seperti di detail_koleksi.php' =>
    'SELECT DISTINCT ?nama ?desc ?devp ?pblsh ?wiki ?genre ?date WHERE{'
    . "{$S} rdf:type owl:Thing; dbo:abstract ?desc; rdfs:label ?nama. "
    . "OPTIONAL{{$S} (dbo:releaseDate|dbp:releaseDate|dbp:firstReleaseDate|dbo:firstReleaseDate) ?date} "
    . "OPTIONAL{{$S} dbo:developer ?devp} "
    . "OPTIONAL{{$S} (dbo:genre|dbp:genre) ?genre} "
    . "OPTIONAL{{{$S} dbo:publisher ?pblsh}UNION{{$S} dbp:publisher ?pblsh}} "
    . "OPTIONAL{{$S} foaf:isPrimaryTopicOf ?wiki} "
    . 'FILTER langMatches(lang(?desc),"EN") FILTER langMatches(lang(?nama),"EN")} LIMIT 1',

  'K. Query penuh TANPA rdf:type owl:Thing' =>
    'SELECT DISTINCT ?nama ?desc ?devp ?pblsh ?wiki ?genre ?date WHERE{'
    . "{$S} dbo:abstract ?desc; rdfs:label ?nama. "
    . "OPTIONAL{{$S} (dbo:releaseDate|dbp:releaseDate|dbp:firstReleaseDate|dbo:firstReleaseDate) ?date} "
    . "OPTIONAL{{$S} dbo:developer ?devp} "
    . "OPTIONAL{{$S} (dbo:genre|dbp:genre) ?genre} "
    . "OPTIONAL{{{$S} dbo:publisher ?pblsh}UNION{{$S} dbp:publisher ?pblsh}} "
    . "OPTIONAL{{$S} foaf:isPrimaryTopicOf ?wiki} "
    . 'FILTER langMatches(lang(?desc),"EN") FILTER langMatches(lang(?nama),"EN")} LIMIT 1',

  'L. Bentuk lama memakai prefiks dbr:' =>
    "SELECT DISTINCT ?nama ?desc WHERE{dbr:{$nama_game} dbo:abstract ?desc; rdfs:label ?nama. "
    . "FILTER langMatches(lang(?desc),\"EN\") FILTER langMatches(lang(?nama),\"EN\")} LIMIT 1",
];

$klien = new \EasyRdf\Sparql\Client($endpoint);
$hasil = [];

foreach ($uji as $label => $q) {
    $t0 = microtime(true);
    $baris = null; $galat = null; $contoh = '';
    try {
        $r = $klien->query($q);
        $baris = count($r);
        $n = 0;
        foreach ($r as $b) {
            $bagian = [];
            foreach ((array) $b as $k => $v) {
                $teks = (string) $v;
                if (mb_strlen($teks) > 70) $teks = mb_substr($teks, 0, 70) . '…';
                $bagian[] = "$k = $teks";
            }
            $contoh .= implode(' | ', $bagian) . "\n";
            if (++$n >= 3) break;
        }
    } catch (\Exception $ex) {
        $galat = get_class($ex) . ': ' . $ex->getMessage();
    }
    $hasil[] = [
        'label'  => $label,
        'q'      => $q,
        'baris'  => $baris,
        'galat'  => $galat,
        'contoh' => trim($contoh),
        'ms'     => round((microtime(true) - $t0) * 1000),
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Penguji Query</title>
<style>
  :root{--bg:#0a0418;--ink:#f2fff0;--muted:rgba(242,255,240,.6);
        --lime:#4af219;--red:#ff5a4e;--amber:#ffc447;--violet:#8b63e8;
        --line:rgba(139,99,232,.28)}
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:var(--bg);color:var(--ink);font-family:Inter,system-ui,sans-serif;
       padding:30px 18px;line-height:1.6}
  .wrap{max-width:900px;margin:0 auto}
  h1{font-size:21px;margin-bottom:5px}
  .sub{color:var(--muted);font-size:13px;margin-bottom:20px}
  form{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px}
  input,select{background:rgba(20,10,40,.6);border:1px solid var(--line);
               border-radius:9px;color:var(--ink);padding:10px 13px;font-size:13px}
  button{background:var(--lime);color:#06210a;border:none;border-radius:9px;
         padding:10px 20px;font-weight:700;font-size:12px;letter-spacing:1px;cursor:pointer}
  .kartu{border:1px solid var(--line);border-radius:11px;margin-bottom:10px;
         background:rgba(20,10,40,.5);overflow:hidden}
  .kepala{display:flex;gap:11px;align-items:center;padding:12px 15px}
  .dot{width:9px;height:9px;border-radius:50%;flex:none}
  .ok .dot{background:var(--lime);box-shadow:0 0 9px var(--lime)}
  .nol .dot{background:var(--red);box-shadow:0 0 9px var(--red)}
  .err .dot{background:var(--amber);box-shadow:0 0 9px var(--amber)}
  .label{font-weight:600;font-size:13.5px;flex:1}
  .angka{font-size:12px;color:var(--muted);white-space:nowrap}
  .ok .angka{color:var(--lime)}
  .nol .angka{color:var(--red);font-weight:700}
  details{border-top:1px solid var(--line)}
  summary{cursor:pointer;padding:9px 15px;font-size:11px;letter-spacing:1.3px;
          text-transform:uppercase;color:var(--violet)}
  pre{background:rgba(0,0,0,.4);margin:0 15px 14px;padding:11px;border-radius:8px;
      font-size:11.5px;color:var(--muted);overflow:auto;white-space:pre-wrap;
      word-break:break-word}
  .simpul{margin-top:24px;padding:16px;border-radius:11px;
          background:rgba(74,242,25,.08);border:1px solid rgba(74,242,25,.3);font-size:13.5px}
</style>
</head>
<body>
<div class="wrap">
  <h1>Penguji Query DBpedia</h1>
  <p class="sub">Baris merah pertama menandai klausa yang membuat hasil menjadi kosong.</p>

  <form method="get">
    <input type="text" name="g" value="<?= htmlspecialchars($nama_game) ?>" placeholder="Nama sumber daya">
    <select name="e">
      <option value="https" <?= $endpoint[4] === 's' ? 'selected' : '' ?>>https</option>
      <option value="http"  <?= $endpoint[4] !== 's' ? 'selected' : '' ?>>http</option>
    </select>
    <button type="submit">Uji</button>
  </form>

  <p class="sub">Sasaran: <code><?= htmlspecialchars($S) ?></code> &nbsp;·&nbsp; endpoint: <code><?= htmlspecialchars($endpoint) ?></code></p>

  <?php foreach ($hasil as $h) :
      $kelas = $h['galat'] !== null ? 'err' : ($h['baris'] > 0 ? 'ok' : 'nol'); ?>
    <div class="kartu <?= $kelas ?>">
      <div class="kepala">
        <span class="dot"></span>
        <span class="label"><?= htmlspecialchars($h['label']) ?></span>
        <span class="angka">
          <?= $h['galat'] !== null ? 'GALAT' : $h['baris'] . ' baris' ?> · <?= $h['ms'] ?> ms
        </span>
      </div>
      <?php if ($h['galat'] !== null) : ?>
        <details open><summary>Pesan galat</summary><pre><?= htmlspecialchars($h['galat']) ?></pre></details>
      <?php elseif ($h['contoh'] !== '') : ?>
        <details><summary>Cuplikan hasil</summary><pre><?= htmlspecialchars($h['contoh']) ?></pre></details>
      <?php endif; ?>
      <details><summary>Query</summary><pre><?= htmlspecialchars($h['q']) ?></pre></details>
    </div>
  <?php endforeach; ?>

  <div class="simpul">
    <strong>Cara membaca:</strong> bila <em>H</em> hijau tetapi <em>I</em> merah, penyebabnya
    adalah <code>rdf:type owl:Thing</code>. Bila <em>E</em> merah, sumber daya itu tidak
    punya ringkasan berbahasa Inggris. Bila <em>A</em> merah, nama sumber dayanya salah.
    Bila <em>K</em> hijau sedangkan <em>J</em> merah, cukup buang klausa tipe tersebut.
  </div>
</div>
</body>
</html>
