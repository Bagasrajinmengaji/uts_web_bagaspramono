<?php
// Manggil helper untuk proteksi sesi
require_once 'config/helper.php';
auth_check();

// Set header agar diunduh sebagai file CSV
$filename = "Template_Import_DompetKu.csv";
header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Buka output stream
$output = fopen("php://output", "w");

// Tambahkan UTF-8 BOM untuk memastikan Excel membuka karakter dengan benar
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Tulis header kolom
// Menggunakan pemisah titik koma (;) karena Excel versi regional Indonesia/Eropa membacanya sebagai pemisah kolom default
fputcsv($output, ["Tanggal", "Jenis", "Nominal", "Keterangan"], ";");

// Tulis beberapa contoh baris data
fputcsv($output, ["2026-06-25", "Pemasukan", "500000", "Gaji bulanan"], ";");
fputcsv($output, ["2026-06-25", "Pengeluaran", "50000", "Belanja kebutuhan dapur"], ";");

// Tutup stream
fclose($output);
exit;
