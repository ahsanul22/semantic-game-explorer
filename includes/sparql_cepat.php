<?php
/**
 * Klien SELECT SPARQL ringan berbasis curl, dipaksa IPv4.
 *
 * Dibuat karena query ke https://dbpedia.org/sparql lewat EasyRdf makan
 * 3-15 detik, sementara query yang sama lewat curl dengan IPv4 hanya
 * ~0,5-0,8 detik (diukur di mesin ini, Juli 2026). Penyebabnya: OS
 * mencoba IPv6 lebih dulu dan rute IPv6 ke dbpedia.org buruk, sedangkan
 * EasyRdf memakai stream PHP yang tidak bisa dipaksa memilih IPv4.
 *
 * Hanya untuk query SELECT. Hasilnya array objek stdClass dengan satu
 * properti per variabel SELECT (nilai string), kompatibel dengan pola
 * $row->nama yang dipakai kode EasyRdf lama di proyek ini.
 */

/**
 * @param string $endpoint URL endpoint SPARQL.
 * @param string $query    Query SELECT tanpa PREFIX (prefiks umum
 *                          DBpedia ditambahkan otomatis).
 * @param int    $timeout  Batas waktu total dalam detik.
 * @return stdClass[]
 * @throws RuntimeException bila koneksi gagal atau jawaban tidak valid.
 */
function sparql_pilih($endpoint, $query, $timeout = 30)
{
    $prefiks = 'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> '
             . 'PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> '
             . 'PREFIX owl: <http://www.w3.org/2002/07/owl#> '
             . 'PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> '
             . 'PREFIX foaf: <http://xmlns.com/foaf/0.1/> '
             . 'PREFIX dbo: <http://dbpedia.org/ontology/> '
             . 'PREFIX dbp: <http://dbpedia.org/property/> '
             . 'PREFIX dbr: <http://dbpedia.org/resource/> ';

    // CATATAN: JANGAN menambah parameter "&timeout=" di sini. Parameter
    // itu mengaktifkan "anytime query" Virtuoso yang mengembalikan hasil
    // PARSIAL saat lambat — untuk bif:contains ini bisa berarti 0 baris
    // padahal datanya ada, sehingga "zelda" tampak tak punya hasil.
    // Kelambatan sesaat ditangani lewat percobaan ulang di bawah.
    $url = $endpoint
         . '?format=' . rawurlencode('application/sparql-results+json')
         . '&query=' . rawurlencode($prefiks . $query);

    // Endpoint publik DBpedia sesekali menolak/menggantung satu
    // permintaan lalu normal lagi sedetik kemudian. Tanpa percobaan
    // ulang, cegukan sesaat itu tampil ke pengguna sebagai "tidak ada
    // hasil" — membingungkan karena kata yang sama sukses saat diulang.
    $galatAkhir = null;
    for ($coba = 1; $coba <= 2; $coba++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 4,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT        => $timeout,
            // Inti percepatannya — jangan dihapus (lihat komentar berkas).
            CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
            CURLOPT_HTTPHEADER     => ['Accept: application/sparql-results+json'],
            CURLOPT_USERAGENT      => 'SmartGameSeeker/1.0 (proyek kuliah web semantik)',
        ]);
        $badan = curl_exec($ch);
        $galat = curl_error($ch);
        $kode  = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($badan === false) {
            $galatAkhir = 'Koneksi ke endpoint gagal: ' . $galat;
        } elseif ($kode !== 200) {
            $galatAkhir = 'Endpoint menolak query (HTTP ' . $kode . '): '
                        . substr((string) $badan, 0, 200);
        } else {
            $data = json_decode($badan, true);
            if (isset($data['results']['bindings'])) {
                break; // sukses
            }
            $galatAkhir = 'Jawaban endpoint bukan hasil SELECT yang valid.';
        }
        $data = null;
        if ($coba === 1) {
            usleep(400000); // jeda 0,4 detik sebelum percobaan kedua
        }
    }
    if (!isset($data['results']['bindings'])) {
        throw new RuntimeException($galatAkhir ?? 'Query gagal tanpa keterangan.');
    }

    $baris = [];
    foreach ($data['results']['bindings'] as $b) {
        $obj = new stdClass();
        foreach ($b as $var => $nilai) {
            $obj->$var = $nilai['value'];
        }
        $baris[] = $obj;
    }
    return $baris;
}
