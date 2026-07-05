<?php
// Manggil helper dan class SimplePDF
require_once "config/helper.php";
require_once "config/SimplePDF.php";

// Pastikan user sudah login
auth_check();

// Inisialisasi PDF
$pdf = new SimplePDF();

// 1. Judul Laporan Template
$pdf->addText(40, 790, "TEMPLATE IMPORT TRANSAKSI - DOMPETKU", 16, true, [
    25,
    135,
    84,
]);
$pdf->addText(
    40,
    775,
    "Gunakan tabel di bawah ini sebagai acuan pengisian data transaksi",
    10,
    false,
    [100, 100, 100],
);

// Garis pembatas header
$pdf->addLine(40, 765, 550, 765, [25, 135, 84], 2.0);

// 2. Informasi Laporan
$pdf->addText(
    40,
    745,
    "Dokumen ini adalah template resmi untuk fitur Impor PDF DompetKu.",
    10,
    true,
    [80, 80, 80],
);
$pdf->addText(
    40,
    730,
    "Panduan: Importer PDF kami membaca baris data di dalam tabel secara sekuensial.",
    9,
    false,
    [120, 120, 120],
);

// 3. Header Tabel
$y_header = 680;
// Gambar kotak header berwarna hijau (Success)
$pdf->addRect(40, $y_header, 510, 20, [25, 135, 84]);

// Tulis label header (Warna putih)
$white = [255, 255, 255];
$pdf->addText(45, $y_header + 5, "No", 10, true, $white);
$pdf->addText(75, $y_header + 5, "Tanggal", 10, true, $white);
$pdf->addText(155, $y_header + 5, "Jenis", 10, true, $white);
$pdf->addText(235, $y_header + 5, "Keterangan", 10, true, $white);
$pdf->addText(455, $y_header + 5, "Nominal", 10, true, $white);

// 4. Baris Contoh Data (Wajib ada agar dibaca oleh parser)
$y_row = 655;

// Contoh Baris 1: Pemasukan
$pdf->addRect(40, $y_row - 4, 510, 18, [248, 249, 250]); // Latar baris abu-abu
$pdf->addText(45, $y_row, "1", 10, false);
$pdf->addText(75, $y_row, "2026-06-25", 10, false);
$pdf->addText(155, $y_row, "Pemasukan", 10, true, [25, 135, 84]);
$pdf->addText(235, $y_row, "Saldo Awal Aset", 10, false);
$pdf->addText(455, $y_row, "1500000", 10, true, [25, 135, 84]);
$pdf->addLine(40, $y_row - 5, 550, $y_row - 5, [220, 220, 220], 0.5);

$y_row -= 20;

// Contoh Baris 2: Pengeluaran
$pdf->addText(45, $y_row, "2", 10, false);
$pdf->addText(75, $y_row, "2026-06-25", 10, false);
$pdf->addText(155, $y_row, "Pengeluaran", 10, true, [220, 53, 69]);
$pdf->addText(235, $y_row, "Pembelian Paket Internet", 10, false);
$pdf->addText(455, $y_row, "100000", 10, true, [220, 53, 69]);
$pdf->addLine(40, $y_row - 5, 550, $y_row - 5, [220, 220, 220], 0.5);

$y_row -= 25;

// 5. Catatan Kaki Petunjuk
$pdf->addRect(40, $y_row - 50, 510, 45, [248, 249, 250], [220, 220, 220], 1.0);
$pdf->addText(50, $y_row - 15, "Catatan Pengisian:", 9, true, [80, 80, 80]);
$pdf->addText(
    50,
    $y_row - 28,
    "1. Format Tanggal wajib YYYY-MM-DD (contoh: 2026-06-25).",
    9,
    false,
    [100, 100, 100],
);
$pdf->addText(
    50,
    $y_row - 41,
    "2. Jenis harus diisi 'Pemasukan' atau 'Pengeluaran'. Nominal harus berupa angka murni.",
    9,
    false,
    [100, 100, 100],
);

// Output berkas PDF ke browser
$filename = "Template_Import_DompetKu.pdf";
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo $pdf->output();
exit();
