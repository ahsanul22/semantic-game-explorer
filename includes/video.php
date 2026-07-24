<?php
/**
 * Titik-akhir kecil: mengembalikan ID video gameplay teratas dari
 * pencarian YouTube sebagai JSON { "id": "..." | null }.
 *
 * Dipanggil secara asinkron oleh halaman detail (fetch) supaya
 * halaman tampil cepat lebih dulu, lalu pemutar video menyusul.
 * DBpedia tidak menyimpan video, jadi sumbernya hasil pencarian
 * YouTube untuk "<judul> gameplay".
 *
 * Catatan keamanan: query hanya dipakai untuk MENCARI di YouTube
 * (bukan URL bebas), jadi tidak membuka celah SSRF.
 */

header('Content-Type: application/json; charset=utf-8');
// Boleh di-cache sebentar oleh browser; hasilnya jarang berubah.
header('Cache-Control: public, max-age=86400');

$q = trim($_GET['q'] ?? '');
// Hanya huruf/angka/spasi/tanda umum pada judul game.
$q = preg_replace('/[^\p{L}\p{N}\s\-:_.\']/u', ' ', $q);
$q = trim(preg_replace('/\s+/', ' ', $q));

if ($q === '') {
    echo json_encode(['id' => null]);
    exit;
}

// sp=EgIQAQ%3D%3D → saring hasil hanya bertipe "Video"
// (mengesampingkan channel/playlist) agar dapat klip sungguhan.
$url = 'https://www.youtube.com/results?search_query='
     . urlencode($q . ' gameplay') . '&sp=EgIQAQ%3D%3D';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS      => 3,
    CURLOPT_CONNECTTIMEOUT => 4,
    CURLOPT_TIMEOUT        => 8,
    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    CURLOPT_HTTPHEADER     => ['Accept-Language: en-US,en;q=0.9'],
]);
$html = curl_exec($ch);
curl_close($ch);

$id = null;
if ($html && preg_match('/"videoId":"([\w-]{11})"/', $html, $m)) {
    $id = $m[1];
}

echo json_encode(['id' => $id]);
