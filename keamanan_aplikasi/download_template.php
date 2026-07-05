<?php
// Manggil helper untuk proteksi sesi
require_once "config/helper.php";
auth_check();

require_once "lib/SimpleXLSXGen.php";
use Shuchkin\SimpleXLSXGen;

$filename = "Template_Import_DompetKu.xlsx";

$data = [
    [
        '<style bgcolor="#5b9bd5" color="#ffffff" border="thin"><b><center>Tanggal</center></b></style>',
        '<style bgcolor="#5b9bd5" color="#ffffff" border="thin"><b><center>Jenis</center></b></style>',
        '<style bgcolor="#5b9bd5" color="#ffffff" border="thin"><b><center>Kategori</center></b></style>',
        '<style bgcolor="#5b9bd5" color="#ffffff" border="thin"><b><center>Nominal</center></b></style>',
        '<style bgcolor="#5b9bd5" color="#ffffff" border="thin"><b><center>Keterangan</center></b></style>',
    ],
    [
        '<style border="thin"><center>2026-06-25</center></style>',
        '<style border="thin"><center>Pemasukan</center></style>',
        '<style border="thin"><center>Gaji</center></style>',
        '<style border="thin"><right>500000</right></style>',
        '<style border="thin">Gaji bulanan</style>',
    ],
    [
        '<style bgcolor="#f2f5f8" border="thin"><center>2026-06-25</center></style>',
        '<style bgcolor="#f2f5f8" border="thin"><center>Pengeluaran</center></style>',
        '<style bgcolor="#f2f5f8" border="thin"><center>Makanan</center></style>',
        '<style bgcolor="#f2f5f8" border="thin"><right>50000</right></style>',
        '<style bgcolor="#f2f5f8" border="thin">Belanja kebutuhan dapur</style>',
    ],
];

$xlsx = SimpleXLSXGen::fromArray($data);
$xlsx->setColWidth(1, 15);
$xlsx->setColWidth(2, 15);
$xlsx->setColWidth(3, 18);
$xlsx->setColWidth(4, 18);
$xlsx->setColWidth(5, 35);
$xlsx->downloadAs($filename);
exit();
