<?php
// Halaman detail untuk game yang berasal dari halaman Explore (DBpedia).
// Data + tampilan dirakit oleh include bersama; berkas ini hanya
// menetapkan identitas visual (aksen ungu) dan tautan halaman ini.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

require_once __DIR__ . "/../includes/detail_game.php";

$cfg = [
    'accent'        => '#8b63e8',
    'accent_soft'   => '#a98cf0',
    'accent_rgb'    => '139,99,232',
    'judul_suffix'  => 'Explore DBpedia',
    'kicker'        => 'Hasil DBpedia',
    'back_href'     => '../index.php',
    'back_label'    => 'Explore',
    'kembali_label' => 'Kembali ke Explore',
    'foot_jp'       => '世界のゲーム · SEEKER',
];

$D = ambil_detail_game($_POST['game'] ?? $_GET['game'] ?? '');
require __DIR__ . "/../includes/detail_tampil.php";
