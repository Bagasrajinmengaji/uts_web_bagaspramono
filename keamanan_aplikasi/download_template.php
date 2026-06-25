<?php
// Manggil helper untuk proteksi sesi
require_once 'config/helper.php';
auth_check();

require_once 'lib/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;

$filename = "Template_Import_DompetKu.xlsx";

$data = [
    [
        "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Tanggal</style>",
        "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Jenis</style>",
        "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Nominal</style>",
        "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Keterangan</style>"
    ],
    ["2026-06-25", "Pemasukan", 500000, "Gaji bulanan"],
    ["2026-06-25", "Pengeluaran", 50000, "Belanja kebutuhan dapur"]
];

$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->downloadAs($filename);
exit;
