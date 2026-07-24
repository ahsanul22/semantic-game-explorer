<?php
/**
 * Pengambil sampul & judul artikel Wikipedia secara massal.
 *
 * Cara lama: tiap kartu memuat SELURUH halaman HTML Wikipedia (~300 KB)
 * lewat EasyRdf hanya untuk membaca tag og:image — dan dilakukan satu
 * per satu. Sepuluh kartu berarti sepuluh unduhan berurutan, ~8-10 detik
 * hanya untuk gambar.
 *
 * Cara ini: REST API ringkasan Wikipedia (JSON ~2 KB per artikel) dan
 * semua permintaan ditembakkan SEKALIGUS lewat curl_multi. Total
 * waktunya kira-kira sama dengan satu permintaan yang paling lambat.
 */

/**
 * @param string[] $urlWiki Daftar URL artikel,
 *                          mis. "http://en.wikipedia.org/wiki/Elden_Ring".
 * @return array Peta url => ['gambar' => string|null, 'judul' => string|null].
 *               Entri selalu ada untuk tiap URL masukan; nilai null berarti
 *               Wikipedia tidak punya datanya (pemanggil yang memilih
 *               gambar/judul pengganti).
 */
function ambil_ringkasan_wiki(array $urlWiki)
{
    $hasil = [];
    $multi = curl_multi_init();
    $peta  = [];

    foreach (array_unique($urlWiki) as $url) {
        $hasil[$url] = ['gambar' => null, 'judul' => null];

        // Nama artikel = segmen terakhir path. basename() dihindari
        // karena di Windows perilakunya aneh terhadap titik dua.
        $path = (string) parse_url($url, PHP_URL_PATH);
        $pos  = strrpos($path, '/');
        $nama = $pos === false ? $path : substr($path, $pos + 1);
        if ($nama === '') {
            continue;
        }

        $api = 'https://en.wikipedia.org/api/rest_v1/page/summary/'
             . rawurlencode(rawurldecode($nama));

        $ch = curl_init($api);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT        => 8,
            // Rute IPv6 di lingkungan ini buruk (lihat catatan di
            // includes/sparql_cepat.php) — paksa IPv4.
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            // Wikipedia meminta User-Agent deskriptif; tanpa ini
            // permintaan bisa ditolak 403.
            CURLOPT_USERAGENT      => 'SmartGameSeeker/1.0 (proyek kuliah web semantik)',
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        curl_multi_add_handle($multi, $ch);
        $peta[(int) $ch] = ['url' => $url, 'ch' => $ch];
    }

    // Jalankan semua permintaan bersamaan sampai selesai.
    do {
        $status = curl_multi_exec($multi, $aktif);
        if ($aktif) {
            curl_multi_select($multi, 1.0);
        }
    } while ($aktif && $status === CURLM_OK);

    foreach ($peta as $item) {
        $badan = curl_multi_getcontent($item['ch']);
        $data  = $badan ? json_decode($badan, true) : null;
        if (is_array($data)) {
            $hasil[$item['url']] = [
                'gambar' => $data['thumbnail']['source']
                    ?? ($data['originalimage']['source'] ?? null),
                'judul'  => $data['title'] ?? null,
            ];
        }
        curl_multi_remove_handle($multi, $item['ch']);
        curl_close($item['ch']);
    }
    curl_multi_close($multi);

    return $hasil;
}
