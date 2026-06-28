<?php
// Manggil file koneksi dan helper
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Pastikan user sudah login//
auth_check();

require_once 'lib/SimpleXLSXGen.php';
use Shuchkin\SimpleXLSXGen;

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil parameter ekspor khusus satu transaksi atau filter massal
$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$jenis_filter = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if ($id !== '') {
        $query = "SELECT * FROM transaksi WHERE id = :id AND user_id = :user_id";
        $params = ['id' => $id, 'user_id' => $user_id];
    } else {
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
    }

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
    die("Gagal mengambil data untuk export.");
}

// Tentukan judul laporan dan nama file berkas
if ($id !== '') {
    $title_report = "KUITANSI TRANSAKSI - DOMPETKU";
    $filename = "Kuitansi_Transaksi_" . $id . "_" . date('Ymd_His') . ".xlsx";
} else {
    $title_report = "LAPORAN TRANSAKSI - DOMPETKU";
    $filename = "Laporan_Transaksi_DompetKu_" . date('Ymd_His') . ".xlsx";
}

$data = [];

// Header / Metadata rows
$data[] = ["<style font-size=16 font-style=bold>$title_report</style>"];
$data[] = ["<style font-size=11 color=#555555>Aplikasi Catatan Keuangan Pribadi yang Aman</style>"];
$data[] = []; // Empty row

$data[] = ["Nama Pengguna", ":", $username];
$data[] = ["Tanggal Ekspor", ":", date('d M Y H:i:s')];

if ($jenis_filter !== '') {
    $data[] = ["Filter Jenis", ":", $jenis_filter];
}
if ($search !== '') {
    $data[] = ["Kata Kunci Pencarian", ":", '"' . $search . '"'];
}

$data[] = []; // Empty row

// Table Headers
$data[] = [
    "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>No</style>",
    "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Tanggal</style>",
    "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Jenis</style>",
    "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Keterangan</style>",
    "<style bgcolor=#0d6efd color=#ffffff font-style=bold align=center>Nominal</style>"
];

if (empty($transactions)) {
    $data[] = ["", "", "Tidak ada data transaksi.", "", ""];
} else {
    $no = 1;
    foreach ($transactions as $row) {
        $date_formatted = date('d M Y', strtotime($row['tanggal']));
        
        if ($row['jenis'] === 'Pemasukan') {
            $nominal_cell = "<style color=#198754 font-style=bold align=right>" . number_format($row['nominal'], 0, ',', '.') . "</style>";
            $jenis_cell = "<style color=#0f5132 font-style=bold align=center>Pemasukan</style>";
        } else {
            $nominal_cell = "<style color=#dc3545 font-style=bold align=right>-" . number_format($row['nominal'], 0, ',', '.') . "</style>";
            $jenis_cell = "<style color=#842029 font-style=bold align=center>Pengeluaran</style>";
        }
        
        $data[] = [
            "<style align=center>$no</style>",
            "<style align=center>$date_formatted</style>",
            $jenis_cell,
            $row['keterangan'],
            $nominal_cell
        ];
        $no++;
    }
    
    $data[] = []; // Empty row
    
    // Summary
    $data[] = [
        "<style align=right font-style=bold>Total Pemasukan:</style>", "", "", "",
        "<style color=#198754 font-style=bold align=right>" . number_format($total_pemasukan, 0, ',', '.') . "</style>"
    ];
    $data[] = [
        "<style align=right font-style=bold>Total Pengeluaran:</style>", "", "", "",
        "<style color=#dc3545 font-style=bold align=right>-" . number_format($total_pengeluaran, 0, ',', '.') . "</style>"
    ];
    
    $saldo_color = $saldo_sekarang >= 0 ? '#0d6efd' : '#dc3545';
    $data[] = [
        "<style align=right font-style=bold>Saldo Akhir:</style>", "", "", "",
        "<style color=$saldo_color font-style=bold align=right>" . number_format($saldo_sekarang, 0, ',', '.') . "</style>"
    ];
}

// Generate and download
$xlsx = SimpleXLSXGen::fromArray($data);
// Set column widths to make it look premium
$xlsx->setColWidth(1, 8);
$xlsx->setColWidth(2, 18);
$xlsx->setColWidth(3, 16);
$xlsx->setColWidth(4, 35);
$xlsx->setColWidth(5, 20);

$xlsx->downloadAs($filename);
exit;
