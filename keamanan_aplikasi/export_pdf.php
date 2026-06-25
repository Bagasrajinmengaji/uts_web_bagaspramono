<?php
// Manggil file koneksi, helper, dan class SimplePDF
require_once 'config/koneksi.php';
require_once 'config/helper.php';
require_once 'config/SimplePDF.php';

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil filter yang sama seperti di dashboard
$jenis_filter = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $query = "SELECT * FROM transaksi WHERE user_id = :user_id";
    $params = ['user_id' => $user_id];

    if ($jenis_filter === 'Pemasukan' || $jenis_filter === 'Pengeluaran') {
        $query .= " AND jenis = :jenis";
        $params['jenis'] = $jenis_filter;
    }

    if ($search !== '') {
        $query .= " AND keterangan LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY tanggal DESC, id DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Hitung ringkasan (Total Pemasukan, Pengeluaran, Saldo) dari data terfilter
    $total_pemasukan = 0;
    $total_pengeluaran = 0;
    foreach ($transactions as $row) {
        if ($row['jenis'] === 'Pemasukan') {
            $total_pemasukan += $row['nominal'];
        } else {
            $total_pengeluaran += $row['nominal'];
        }
    }
    $saldo_sekarang = $total_pemasukan - $total_pengeluaran;

} catch (\PDOException $e) {
    error_log($e->getMessage());
    die("Gagal mengambil data untuk export PDF.");
}

// Inisialisasi PDF
$pdf = new SimplePDF();

// 1. Judul Laporan
$pdf->addText(40, 790, "LAPORAN TRANSAKSI - DOMPETKU", 16, true, [13, 110, 253]);
$pdf->addText(40, 775, "Aplikasi Catatan Keuangan Pribadi yang Aman", 10, false, [100, 100, 100]);

// Garis pembatas header
$pdf->addLine(40, 765, 550, 765, [13, 110, 253], 2.0);

// 2. Informasi Laporan
$pdf->addText(40, 745, "Nama Pengguna : " . $username, 10, true);
$pdf->addText(40, 730, "Tanggal Ekspor : " . date('d M Y H:i:s'), 10, false, [80, 80, 80]);

$filter_text = "Semua Jenis";
if ($jenis_filter !== '') {
    $filter_text = $jenis_filter;
}
if ($search !== '') {
    $filter_text .= " (Cari: \"" . $search . "\")";
}
$pdf->addText(40, 715, "Filter Aktif    : " . $filter_text, 10, false, [80, 80, 80]);

// 3. Header Tabel
// Koordinat Y awal untuk header tabel
$y_header = 680;
// Gambar kotak header berwarna biru (Primary)
$pdf->addRect(40, $y_header, 510, 20, [13, 110, 253]);

// Tulis label header (Warna putih)
$white = [255, 255, 255];
$pdf->addText(45,  $y_header + 5, "No", 10, true, $white);
$pdf->addText(75,  $y_header + 5, "Tanggal", 10, true, $white);
$pdf->addText(155, $y_header + 5, "Jenis", 10, true, $white);
$pdf->addText(235, $y_header + 5, "Keterangan", 10, true, $white);
$pdf->addText(455, $y_header + 5, "Nominal", 10, true, $white);

// 4. Baris Data
$y_row = 655;
$no = 1;

if (empty($transactions)) {
    $pdf->addText(45, $y_row, "Tidak ada data transaksi.", 10, false, [120, 120, 120]);
    $pdf->addLine(40, $y_row - 5, 550, $y_row - 5, [220, 220, 220], 1.0);
    $y_row -= 20;
} else {
    // Batasi jumlah baris agar pas di satu halaman (maksimal 24 transaksi agar ringkasan pas di bawah)
    $max_rows = 24;
    $count = 0;
    
    foreach ($transactions as $row) {
        if ($count >= $max_rows) {
            $pdf->addText(45, $y_row, "... (data lainnya terpotong, silakan gunakan filter pencarian)", 9, false, [120, 120, 120]);
            $pdf->addLine(40, $y_row - 5, 550, $y_row - 5, [220, 220, 220], 1.0);
            $y_row -= 20;
            break;
        }
        
        // Zebra striping: warna latar belang-belang abu-abu tipis untuk baris genap
        if ($no % 2 === 0) {
            $pdf->addRect(40, $y_row - 4, 510, 18, [248, 249, 250]);
        }
        
        // Tulis isi kolom
        $pdf->addText(45,  $y_row, $no++, 10, false);
        $pdf->addText(75,  $y_row, date('d M Y', strtotime($row['tanggal'])), 10, false);
        
        // Warna jenis
        if ($row['jenis'] === 'Pemasukan') {
            $pdf->addText(155, $y_row, "Pemasukan", 10, true, [25, 135, 84]); // Hijau
            $nominal_text = "+" . number_format($row['nominal'], 0, ',', '.');
            $nominal_color = [25, 135, 84];
        } else {
            $pdf->addText(155, $y_row, "Pengeluaran", 10, true, [220, 53, 69]); // Merah
            $nominal_text = "-" . number_format($row['nominal'], 0, ',', '.');
            $nominal_color = [220, 53, 69];
        }
        
        // Potong keterangan jika terlalu panjang agar tidak melewati lebar kolom
        $keterangan = $row['keterangan'];
        if (strlen($keterangan) > 40) {
            $keterangan = substr($keterangan, 0, 38) . "..";
        }
        
        $pdf->addText(235, $y_row, $keterangan, 10, false);
        $pdf->addText(455, $y_row, $nominal_text, 10, true, $nominal_color);
        
        // Gambar garis pemisah baris
        $pdf->addLine(40, $y_row - 5, 550, $y_row - 5, [220, 220, 220], 0.5);
        
        $y_row -= 20;
        $count++;
    }
}

// Spasi tambahan sebelum ringkasan
$y_row -= 10;

// 5. Ringkasan Total
// Gambar box ringkasan dengan latar abu-abu terang
$pdf->addRect(40, $y_row - 50, 510, 55, [233, 236, 239], [200, 200, 200], 1.0);

// Teks Ringkasan
$pdf->addText(50, $y_row - 12, "Total Pemasukan", 10, true);
$pdf->addText(200, $y_row - 12, ": Rp " . number_format($total_pemasukan, 0, ',', '.'), 10, true, [25, 135, 84]);

$pdf->addText(50, $y_row - 27, "Total Pengeluaran", 10, true);
$pdf->addText(200, $y_row - 27, ": Rp " . number_format($total_pengeluaran, 0, ',', '.'), 10, true, [220, 53, 69]);

$saldo_color = $saldo_sekarang >= 0 ? [13, 110, 253] : [220, 53, 69];
$pdf->addText(50, $y_row - 42, "Saldo Akhir", 10, true);
$pdf->addText(200, $y_row - 42, ": Rp " . number_format($saldo_sekarang, 0, ',', '.'), 10, true, $saldo_color);

// Output berkas PDF ke browser
$filename = "Laporan_Transaksi_DompetKu_" . date('Ymd_His') . ".pdf";
header("Content-Type: application/pdf");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

echo $pdf->output();
exit;
