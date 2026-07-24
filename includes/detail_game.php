<?php
/**
 * Lapisan data untuk halaman detail game (detail_koleksi.php &
 * detail_dbpedia.php). Keduanya kembar — beda hanya warna aksen dan
 * tautan — sehingga logika pengambilan data disatukan di sini.
 *
 * Data dirakit dari DUA sumber:
 *   1. DBpedia (SPARQL) — metadata terstruktur: developer, platform,
 *      mode, sutradara, penulis, komposer, engine, seri, dll.
 *   2. Wikipedia (MediaWiki API) — paragraf pembuka yang kaya
 *      (premis + gameplay + pengembangan) sebagai "cerita" game, plus
 *      sampul beresolusi lebih baik. dbo:abstract sudah kosong di
 *      DBpedia Live, jadi teks panjang HARUS diambil dari Wikipedia.
 */

require_once __DIR__ . '/sparql_cepat.php';
require_once __DIR__ . '/gambar_wiki.php';

/** Segmen terakhir sebuah URI/IRI (menangani "/" di ujung). */
function seg_akhir($uri)
{
    $s = rtrim(trim((string) $uri), '/');
    $pos = strrpos($s, '/');
    return $pos === false ? $s : substr($s, $pos + 1);
}

/** URI DBpedia -> nama terbaca; garis bawah jadi spasi. null bila kosong. */
function rapikan($nilai)
{
    if ($nilai === null) {
        return null;
    }
    $s = trim((string) $nilai);
    if ($s === '') {
        return null;
    }
    if (stripos($s, 'http') === 0) {
        $s = seg_akhir($s);
    }
    $s = trim(str_replace('_', ' ', $s));
    return $s === '' ? null : $s;
}

function rapikan_tanggal($nilai)
{
    $s = rapikan($nilai);
    if ($s === null) {
        return null;
    }
    $ts = strtotime($s);
    return $ts ? date('j F Y', $ts) : $s;
}

/**
 * Paragraf pembuka kaya + gambar dari MediaWiki API untuk satu artikel.
 * exintro=1 memberi seluruh bagian pembuka (bisa beberapa paragraf),
 * jauh lebih kaya daripada ringkasan satu kalimat.
 *
 * @return array|null ['overview','gambar','gambar_besar','judul']
 */
function ambil_artikel_wiki($urlWiki)
{
    $path = (string) parse_url($urlWiki, PHP_URL_PATH);
    $pos  = strrpos($path, '/');
    $nama = $pos === false ? $path : substr($path, $pos + 1);
    if ($nama === '') {
        return null;
    }

    $api = 'https://en.wikipedia.org/w/api.php?' . http_build_query([
        'action'   => 'query',
        'format'   => 'json',
        'redirects' => 1,
        'prop'     => 'extracts|pageimages',
        'exintro'  => 1,
        'explaintext' => 1,
        'piprop'   => 'original|thumbnail',
        'pithumbsize' => 640,
        'titles'   => rawurldecode($nama),
    ]);

    $ch = curl_init($api);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 4,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT        => 9,
        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT      => 'SmartGameSeeker/1.0 (proyek kuliah web semantik)',
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);
    $badan = curl_exec($ch);
    curl_close($ch);

    $data = $badan ? json_decode($badan, true) : null;
    if (!isset($data['query']['pages'])) {
        return null;
    }
    $page = reset($data['query']['pages']);
    if (!is_array($page)) {
        return null;
    }
    return [
        'overview'     => isset($page['extract']) ? trim($page['extract']) : null,
        'gambar'       => $page['thumbnail']['source'] ?? null,
        'gambar_besar' => $page['original']['source'] ?? null,
        'judul'        => $page['title'] ?? null,
    ];
}

/**
 * Rangkai seluruh data detail sebuah game.
 *
 * @param string $kiriman Nilai mentah dari form (URI resource / nama).
 * @return array Struktur lengkap; kunci 'ada' menandai apakah datanya
 *               ketemu, 'galat'/'galat_teknis' berisi pesan bila gagal.
 */
function ambil_detail_game($kiriman)
{
    $game = seg_akhir($kiriman);
    // Buang karakter yang bisa merusak kurung sudut IRI pada query.
    $game = preg_replace('/[<>"{}|\^`\\\\\s]/', '', $game);

    $D = [
        'ada' => false, 'kiriman' => $kiriman, 'game' => $game,
        'judul' => null, 'gambar' => null, 'gambar_besar' => null,
        'desc_pendek' => null, 'overview' => null,
        'devp' => null, 'pblsh' => null, 'genre' => null,
        'platform' => null, 'modes' => null, 'engine' => null, 'series' => null,
        'director' => null, 'writer' => null, 'composer' => null,
        'tanggal' => null, 'wiki' => null, 'situs' => null,
        'galat' => null, 'galat_teknis' => null,
    ];

    if ($game === '') {
        $D['galat'] = 'Halaman ini perlu dibuka melalui kartu game, bukan langsung dari alamat URL.';
        return $D;
    }

    $iri = 'http://dbpedia.org/resource/' . $game;

    // -- Query A: inti (label, deskripsi singkat, dev, pub, genre, tanggal, wiki).
    //    Bentuk yang sudah terbukti andal; tetap LIMIT 1 tanpa agregasi.
    $qa = 'SELECT DISTINCT ?nama ?dsk ?devp ?pblsh ?wiki ?genre ?date WHERE{'
        . 'VALUES ?s {<' . $iri . '>} ?s rdfs:label ?nama. '
        . 'OPTIONAL{?s dbo:description ?dsk. FILTER langMatches(lang(?dsk),"EN")} '
        . 'OPTIONAL{?s (dbo:releaseDate|dbp:releaseDate|dbp:firstReleaseDate|dbo:firstReleaseDate) ?date} '
        . 'OPTIONAL{?s dbo:developer ?devp} '
        . 'OPTIONAL{?s (dbo:genre|dbp:genre) ?genre} '
        . 'OPTIONAL{{?s dbo:publisher ?pblsh}UNION{?s dbp:publisher ?pblsh}} '
        . 'OPTIONAL{?s foaf:isPrimaryTopicOf ?wiki} '
        . 'FILTER langMatches(lang(?nama),"EN")} LIMIT 1';

    try {
        $ra = sparql_pilih('https://dbpedia.org/sparql', $qa);
    } catch (\Throwable $e) {
        $D['galat'] = 'Gagal menghubungi DBpedia. Endpoint mungkin sedang sibuk — coba muat ulang beberapa saat lagi.';
        $D['galat_teknis'] = get_class($e) . ': ' . $e->getMessage();
        return $D;
    }
    if (!$ra) {
        return $D; // tidak ketemu; 'ada' tetap false
    }

    $r = $ra[0];
    $D['ada']         = true;
    $D['judul']       = rapikan($r->nama ?? null);
    $D['desc_pendek'] = (isset($r->dsk) && trim((string) $r->dsk) !== '') ? (string) $r->dsk : null;
    $D['devp']        = rapikan($r->devp ?? null);
    $D['pblsh']       = rapikan($r->pblsh ?? null);
    $D['genre']       = rapikan($r->genre ?? null);
    $D['tanggal']     = rapikan_tanggal($r->date ?? null);
    $D['wiki']        = (isset($r->wiki) && (string) $r->wiki !== '') ? (string) $r->wiki : null;

    // -- Query B: metadata multinilai sebagai pasangan ?p ?o.
    //    Sengaja TIDAK memakai GROUP_CONCAT di SPARQL: menggabung banyak
    //    OPTIONAL multinilai memicu perkalian kartesian yang membuat
    //    Virtuoso error/kosong. Pasangan polos aman, lalu diringkas di PHP.
    $qb = 'SELECT ?p ?o WHERE{<' . $iri . '> ?p ?o. FILTER(?p IN('
        . 'dbo:computingPlatform,dbp:modes,dbo:director,dbo:writer,dbp:writer,'
        . 'dbo:musicComposer,dbp:composer,dbo:gameEngine,dbp:engine,'
        . 'dbo:series,dbp:series,dbo:wikiPageExternalLink))}';
    try {
        $rb = sparql_pilih('https://dbpedia.org/sparql', $qb);
    } catch (\Throwable $e) {
        $rb = [];
    }

    $peta = [
        'computingPlatform' => 'platform', 'modes' => 'modes',
        'director' => 'director', 'writer' => 'writer',
        'musicComposer' => 'composer', 'composer' => 'composer',
        'gameEngine' => 'engine', 'engine' => 'engine',
        'series' => 'series', 'wikiPageExternalLink' => 'situs',
    ];
    $kel = [
        'platform' => [], 'modes' => [], 'director' => [], 'writer' => [],
        'composer' => [], 'engine' => [], 'series' => [], 'situs' => [],
    ];
    foreach ($rb as $row) {
        $pred = seg_akhir($row->p ?? '');
        if (!isset($peta[$pred])) {
            continue;
        }
        $bucket = $peta[$pred];
        $val = (string) ($row->o ?? '');
        if ($val === '') {
            continue;
        }
        if ($bucket === 'situs') {
            // Tautan eksternal: simpan URL utuh, jangan dipotong.
            if (stripos($val, 'http') === 0) {
                $kel['situs'][$val] = true;
            }
        } else {
            $c = rapikan($val);
            if ($bucket === 'modes' && $c !== null) {
                // "Single-player video game" -> "Single-player".
                $c = trim(str_ireplace(' video game', '', $c));
            }
            if ($c !== null && $c !== '') {
                $kel[$bucket][$c] = true;
            }
        }
    }
    foreach (['platform', 'modes', 'director', 'writer', 'composer', 'engine', 'series'] as $b) {
        $D[$b] = $kel[$b] ? implode(', ', array_keys($kel[$b])) : null;
    }
    $D['situs'] = $kel['situs'] ? array_key_first($kel['situs']) : null;

    // -- Wikipedia: cerita kaya + sampul beresolusi baik.
    if ($D['wiki']) {
        $art = ambil_artikel_wiki($D['wiki']);
        if ($art) {
            if (!empty($art['overview']))     $D['overview']     = $art['overview'];
            if (!empty($art['gambar']))       $D['gambar']       = $art['gambar'];
            if (!empty($art['gambar_besar'])) $D['gambar_besar'] = $art['gambar_besar'];
            if (!empty($art['judul']))        $D['judul']        = $art['judul'];
        }
    }

    if ($D['gambar'] === null) {
        $D['gambar'] = 'https://i.pinimg.com/564x/e8/f1/51/e8f1519b1599f3fc7df008172c87261d.jpg';
    }
    if ($D['judul'] === null) {
        $D['judul'] = rapikan($game) ?? 'Tanpa Judul';
    }

    return $D;
}
