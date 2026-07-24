<?php
/* =====================================================================
   ALAT DIAGNOSA — Smart Game Seeker
   ---------------------------------------------------------------------
   Buka lewat:  http://localhost/semantic-game-explorer/tools/cek.php

   Berkas ini hanya membaca dan menguji; tidak mengubah apa pun.
   ===================================================================== */

error_reporting(E_ALL);
ini_set('display_errors', '1');
@set_time_limit(120);

$hasil = [];
function catat(&$h, $nama, $status, $pesan, $saran = null)
{
    $h[] = ['nama' => $nama, 'status' => $status, 'pesan' => $pesan, 'saran' => $saran];
}

// ---------------------------------------------------------------------
// 1. Lingkungan PHP
// ---------------------------------------------------------------------
catat($hasil, 'Versi PHP', 'info', PHP_VERSION);

$ext_wajib = ['openssl', 'mbstring', 'sockets'];
foreach ($ext_wajib as $e) {
    $ada = extension_loaded($e);
    catat(
        $hasil,
        "Ekstensi: $e",
        $ada ? 'ok' : ($e === 'sockets' ? 'warn' : 'gagal'),
        $ada ? 'aktif' : 'TIDAK aktif',
        $ada ? null : "Buka php.ini di XAMPP, hapus tanda ; pada baris extension=$e, lalu restart Apache."
    );
}

// Transport ssl wajib ada bila endpoint memakai https
$transport = stream_get_transports();
$ada_ssl = in_array('ssl', $transport) || in_array('tls', $transport);
catat(
    $hasil,
    'Transport ssl:// tersedia',
    $ada_ssl ? 'ok' : 'gagal',
    $ada_ssl ? implode(', ', $transport) : 'tidak ada — koneksi https akan gagal',
    $ada_ssl ? null : 'Aktifkan extension=openssl pada php.ini, lalu restart Apache.'
);

// ---------------------------------------------------------------------
// 2. Koneksi mentah ke DBpedia
// ---------------------------------------------------------------------
function uji_soket($host, $port, $label)
{
    $mulai = microtime(true);
    $s = @fsockopen($host, $port, $errno, $errstr, 10);
    $lama = round((microtime(true) - $mulai) * 1000);
    if ($s) {
        fclose($s);
        return ['ok', "tersambung dalam {$lama} ms"];
    }
    return ['gagal', "gagal: $errstr (kode $errno)"];
}

list($st, $ms) = uji_soket('dbpedia.org', 80, 'http');
catat($hasil, 'Soket dbpedia.org:80 (http)', $st, $ms,
    $st === 'gagal' ? 'Periksa koneksi internet, firewall Windows, atau antivirus yang memblokir Apache.' : null);

list($st2, $ms2) = uji_soket('ssl://dbpedia.org', 443, 'https');
catat($hasil, 'Soket dbpedia.org:443 (https)', $st2, $ms2,
    $st2 === 'gagal' ? 'Biasanya karena extension=openssl belum aktif di php.ini.' : null);

// ---------------------------------------------------------------------
// 3. Apakah endpoint mengalihkan (redirect)?
// ---------------------------------------------------------------------
$kode_http = null;
$lokasi_baru = null;
if ($st === 'ok') {
    $s = @fsockopen('dbpedia.org', 80, $e1, $e2, 10);
    if ($s) {
        $req = "GET /sparql?query=" . urlencode('SELECT * WHERE {?s ?p ?o} LIMIT 1') . " HTTP/1.1\r\n"
             . "Host: dbpedia.org\r\n"
             . "Accept: application/sparql-results+json\r\n"
             . "Connection: close\r\n\r\n";
        fwrite($s, $req);
        $kepala = '';
        while (!feof($s)) {
            $baris = fgets($s);
            if ($baris === false || trim($baris) === '') break;
            $kepala .= $baris;
        }
        fclose($s);
        if (preg_match('#^HTTP/[\d.]+ (\d{3})#', $kepala, $m)) {
            $kode_http = $m[1];
        }
        if (preg_match('#^Location:\s*(.+)$#mi', $kepala, $m)) {
            $lokasi_baru = trim($m[1]);
        }
        $ket = "status $kode_http";
        if ($lokasi_baru) $ket .= " -> dialihkan ke: $lokasi_baru";
        catat($hasil, 'Respons endpoint DBpedia', $kode_http == '200' ? 'ok' : 'warn', $ket,
            ($lokasi_baru && stripos($lokasi_baru, 'https') === 0 && !$ada_ssl)
              ? 'Endpoint mengalihkan ke https tetapi openssl belum aktif — inilah penyebab kegagalannya.'
              : null);
    }
}

// ---------------------------------------------------------------------
// 4. Uji EasyRdf sungguhan
// ---------------------------------------------------------------------
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    catat($hasil, 'vendor/autoload.php', 'gagal', 'tidak ditemukan di ' . $autoload,
        'Pastikan berkas cek.php diletakkan sejajar dengan index.php.');
} else {
    require_once $autoload;

    \EasyRdf\RdfNamespace::set('dbo', 'http://dbpedia.org/ontology/');
    \EasyRdf\RdfNamespace::set('dbr', 'http://dbpedia.org/resource/');
    \EasyRdf\RdfNamespace::set('dbp', 'http://dbpedia.org/property/');

    // 4a. Query paling sederhana
    try {
        $c = new \EasyRdf\Sparql\Client('http://dbpedia.org/sparql');
        $r = $c->query('SELECT ?s WHERE { ?s a owl:Thing } LIMIT 1');
        catat($hasil, 'EasyRdf: query sederhana', 'ok', 'berhasil, ' . count($r) . ' baris');
    } catch (\Exception $ex) {
        catat($hasil, 'EasyRdf: query sederhana', 'gagal', get_class($ex) . ': ' . $ex->getMessage(),
            'Pesan di atas adalah galat sebenarnya — inilah yang selama ini tersembunyi.');
    }

    // 4b. Query detail yang dipakai halaman detail_koleksi.php
    $contoh = 'Elden_Ring';
    $iri = 'http://dbpedia.org/resource/' . $contoh;
    $q = 'SELECT DISTINCT ?nama ?desc ?devp ?pblsh ?wiki ?genre ?date WHERE {
            <' . $iri . '> rdf:type owl:Thing;
            dbo:abstract ?desc ;
            rdfs:label ?nama .
            OPTIONAL { <' . $iri . '> (dbo:releaseDate|dbp:releaseDate|dbp:firstReleaseDate|dbo:firstReleaseDate) ?date } .
            OPTIONAL { <' . $iri . '> dbo:developer ?devp } .
            OPTIONAL { <' . $iri . '> (dbo:genre|dbp:genre) ?genre } .
            OPTIONAL { { <' . $iri . '> dbo:publisher ?pblsh } UNION { <' . $iri . '> dbp:publisher ?pblsh } } .
            OPTIONAL { <' . $iri . '> foaf:isPrimaryTopicOf ?wiki } .
            FILTER langMatches(lang(?desc), "EN") .
            FILTER langMatches(lang(?nama), "EN") .
        } LIMIT 1';
    try {
        $c2 = new \EasyRdf\Sparql\Client('http://dbpedia.org/sparql');
        $r2 = $c2->query($q);
        $n = count($r2);
        $judul = '';
        foreach ($r2 as $b) { $judul = (string)($b->nama ?? ''); break; }
        catat($hasil, 'EasyRdf: query detail (Elden Ring)',
            $n > 0 ? 'ok' : 'warn',
            $n > 0 ? "berhasil, judul terbaca: \"$judul\"" : 'query berjalan tetapi 0 baris');
    } catch (\Exception $ex) {
        catat($hasil, 'EasyRdf: query detail (Elden Ring)', 'gagal',
            get_class($ex) . ': ' . $ex->getMessage());
    }

    // 4c. Uji versi https
    try {
        $c3 = new \EasyRdf\Sparql\Client('https://dbpedia.org/sparql');
        $r3 = $c3->query('SELECT ?s WHERE { ?s a owl:Thing } LIMIT 1');
        catat($hasil, 'EasyRdf lewat https', 'ok', 'berhasil, ' . count($r3) . ' baris',
            'Bila baris http gagal namun ini berhasil, ganti endpoint menjadi https di semua berkas.');
    } catch (\Exception $ex) {
        catat($hasil, 'EasyRdf lewat https', 'gagal', get_class($ex) . ': ' . $ex->getMessage());
    }

    // 4d. Fuseki
    try {
        $cf = new \EasyRdf\Sparql\Client('http://localhost:3030/Action/sparql');
        $rf = $cf->query('SELECT * WHERE { ?s ?p ?o } LIMIT 1');
        catat($hasil, 'Fuseki dataset "Action"', 'ok', 'berhasil, ' . count($rf) . ' baris');
    } catch (\Exception $ex) {
        catat($hasil, 'Fuseki dataset "Action"', 'gagal', $ex->getMessage(),
            'Pastikan fuseki-server sedang berjalan dan dataset bernama persis "Action".');
    }
}

// ---------------------------------------------------------------------
// 5. Isi POST bila halaman ini dijadikan sasaran form
// ---------------------------------------------------------------------
$post_info = empty($_POST) ? '(kosong — halaman dibuka langsung)' : print_r($_POST, true);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Diagnosa — Smart Game Seeker</title>
<style>
  :root{--bg:#0a0418;--ink:#f2fff0;--muted:rgba(242,255,240,.6);
        --lime:#4af219;--red:#ff5a4e;--amber:#ffc447;--violet:#8b63e8;
        --line:rgba(139,99,232,.28)}
  *{margin:0;padding:0;box-sizing:border-box}
  body{background:var(--bg);color:var(--ink);
       font-family:Inter,system-ui,sans-serif;padding:32px 20px;line-height:1.6}
  .wrap{max-width:880px;margin:0 auto}
  h1{font-size:22px;margin-bottom:6px;letter-spacing:.5px}
  .sub{color:var(--muted);font-size:13px;margin-bottom:28px}
  .row{display:flex;gap:14px;align-items:flex-start;padding:14px 16px;
       border:1px solid var(--line);border-radius:11px;margin-bottom:9px;
       background:rgba(20,10,40,.5)}
  .dot{width:9px;height:9px;border-radius:50%;flex:none;margin-top:8px}
  .ok .dot{background:var(--lime);box-shadow:0 0 9px var(--lime)}
  .gagal .dot{background:var(--red);box-shadow:0 0 9px var(--red)}
  .warn .dot{background:var(--amber);box-shadow:0 0 9px var(--amber)}
  .info .dot{background:var(--violet)}
  .nama{font-weight:600;font-size:13.5px;min-width:230px}
  .pesan{font-size:13px;color:var(--muted);word-break:break-word;flex:1}
  .saran{display:block;margin-top:7px;padding:9px 12px;border-radius:8px;
         background:rgba(255,196,71,.1);border:1px solid rgba(255,196,71,.3);
         color:#ffd98a;font-size:12.5px}
  pre{background:rgba(0,0,0,.4);border:1px solid var(--line);border-radius:10px;
      padding:14px;overflow:auto;font-size:12px;color:var(--muted);margin-top:10px}
  h2{font-size:14px;margin:26px 0 10px;letter-spacing:1px;
     text-transform:uppercase;color:var(--violet)}
  @media(max-width:640px){.row{flex-direction:column;gap:6px}.nama{min-width:0}}
</style>
</head>
<body>
<div class="wrap">
  <h1>Diagnosa Koneksi</h1>
  <p class="sub">Tiap baris menguji satu lapisan. Cari baris merah pertama — di situlah pangkal masalahnya.</p>

  <?php foreach ($hasil as $h) : ?>
    <div class="row <?= $h['status'] ?>">
      <span class="dot"></span>
      <span class="nama"><?= htmlspecialchars($h['nama']) ?></span>
      <span class="pesan">
        <?= htmlspecialchars($h['pesan']) ?>
        <?php if (!empty($h['saran'])) : ?>
          <span class="saran"><?= htmlspecialchars($h['saran']) ?></span>
        <?php endif; ?>
      </span>
    </div>
  <?php endforeach; ?>

  <h2>Isi $_POST</h2>
  <pre><?= htmlspecialchars($post_info) ?></pre>

  <h2>Catatan</h2>
  <pre>allow_url_fopen : <?= ini_get('allow_url_fopen') ? 'On' : 'Off' ?>

default_socket_timeout : <?= ini_get('default_socket_timeout') ?>s
berkas php.ini : <?= php_ini_loaded_file() ?: '(tidak terdeteksi)' ?></pre>
</div>
</body>
</html>
