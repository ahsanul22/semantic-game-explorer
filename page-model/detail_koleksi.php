<?php
// Halaman detail untuk game dari Koleksi Lokal (Fuseki). Rincian
// deskriptifnya tetap diperkaya dari DBpedia + Wikipedia lewat include
// bersama; berkas ini hanya menetapkan aksen hijau dan tautannya.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . "/../includes/detail_game.php";

$cfg = [
    'accent'        => '#4af219',
    'accent_soft'   => '#7dff52',
    'accent_rgb'    => '74,242,25',
    'judul_suffix'  => 'Smart Game Seeker',
    'kicker'        => 'Detail Game',
    'back_href'     => '../lokal.php',
    'back_label'    => 'Koleksi Lokal',
    'kembali_label' => 'Kembali ke Koleksi Lokal',
    'foot_jp'       => 'ゲーム詳細 · SEEKER',
];

$D = ambil_detail_game($_POST['game'] ?? $_GET['game'] ?? '');
require __DIR__ . "/../includes/detail_tampil.php";
