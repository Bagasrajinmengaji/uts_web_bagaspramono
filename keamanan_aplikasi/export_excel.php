<?php
// Manggil file koneksi dan helper
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Pastikan user sudah login
auth_check();

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
    $filename = "Kuitansi_Transaksi_" . $id . "_" . date('Ymd_His') . ".xls";
} else {
    $title_report = "LAPORAN TRANSAKSI - DOMPETKU";
    $filename = "Laporan_Transaksi_DompetKu_" . date('Ymd_His') . ".xls";
}

// Set header agar didownload sebagai file Excel (.xls)
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Render HTML Table yang akan dibaca oleh Excel dengan styling yang rapi
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 11pt;
            text-align: center;
            margin-bottom: 20px;
            color: #555555;
        }
        .info-table {
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .info-table td {
            padding: 3px 0;
        }
        .data-table {
            border-collapse: collapse;
            width: 100%;
            font-size: 10pt;
        }
        .data-table th {
            background-color: #0d6efd;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #dddddd;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #dddddd;
            vertical-align: middle;
        }
        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-success {
            color: #198754;
        }
        .text-danger {
            color: #dc3545;
        }
        .text-primary {
            color: #0d6efd;
        }
        .badge-pemasukan {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        .badge-pengeluaran {
            background-color: #f8d7da;
            color: #842029;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        .summary-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <!-- Judul Laporan -->
    <div class="title"><?= $title_report; ?></div>
    <div class="subtitle">Aplikasi Catatan Keuangan Pribadi yang Aman</div>

    <!-- Informasi Laporan -->
    <table class="info-table">
        <tr>
            <td width="150"><strong>Nama Pengguna</strong></td>
            <td width="10">:</td>
            <td><?= escape($username); ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Ekspor</strong></td>
            <td>:</td>
            <td><?= date('d M Y H:i:s'); ?></td>
        </tr>
        <?php if ($jenis_filter !== ''): ?>
        <tr>
            <td><strong>Filter Jenis</strong></td>
            <td>:</td>
            <td><?= escape($jenis_filter); ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($search !== ''): ?>
        <tr>
            <td><strong>Kata Kunci Pencarian</strong></td>
            <td>:</td>
            <td>"<?= escape($search); ?>"</td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Tabel Data Transaksi -->
    <table class="data-table">
        <thead>
            <tr>
                <th width="50" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #dddddd;">No</th>
                <th width="120" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #dddddd;">Tanggal</th>
                <th width="120" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #dddddd;">Jenis</th>
                <th width="300" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #dddddd;">Keterangan</th>
                <th width="150" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #dddddd;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="5" class="text-center" style="padding: 20px; color: #777777; border: 1px solid #dddddd;">Tidak ada data transaksi.</td>
                </tr>
            <?php else: ?>
                <?php $no = 1; foreach ($transactions as $row): ?>
                    <tr>
                        <td class="text-center" style="border: 1px solid #dddddd;"><?= $no++; ?></td>
                        <td class="text-center" style="border: 1px solid #dddddd;"><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                        <td class="text-center" style="border: 1px solid #dddddd;">
                            <?php if ($row['jenis'] === 'Pemasukan'): ?>
                                <span class="badge-pemasukan">Pemasukan</span>
                            <?php else: ?>
                                <span class="badge-pengeluaran">Pengeluaran</span>
                            <?php endif; ?>
                        </td>
                        <td style="border: 1px solid #dddddd;"><?= escape($row['keterangan']); ?></td>
                        <td class="text-right font-bold <?= $row['jenis'] === 'Pemasukan' ? 'text-success' : 'text-danger'; ?>" style="border: 1px solid #dddddd;">
                            <?= ($row['jenis'] === 'Pemasukan' ? '' : '-') ?><?= number_format($row['nominal'], 0, ',', '.'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <!-- Spacer Baris Kosong -->
                <tr><td colspan="5" style="border: none; height: 15px; background-color: transparent;"></td></tr>
                
                <!-- Ringkasan Total -->
                <tr class="summary-row" style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="4" class="text-right" style="border: 1px solid #dddddd; padding: 8px;">Total Pemasukan:</td>
                    <td class="text-right text-success" style="border: 1px solid #dddddd; padding: 8px;"><?= number_format($total_pemasukan, 0, ',', '.'); ?></td>
                </tr>
                <tr class="summary-row" style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="4" class="text-right" style="border: 1px solid #dddddd; padding: 8px;">Total Pengeluaran:</td>
                    <td class="text-right text-danger" style="border: 1px solid #dddddd; padding: 8px;">-<?= number_format($total_pengeluaran, 0, ',', '.'); ?></td>
                </tr>
                <tr class="summary-row" style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="4" class="text-right" style="border: 1px solid #dddddd; padding: 8px;">Saldo Akhir:</td>
                    <td class="text-right <?= $saldo_sekarang >= 0 ? 'text-primary' : 'text-danger'; ?>" style="border: 1px solid #dddddd; padding: 8px;">
                        <?= number_format($saldo_sekarang, 0, ',', '.'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
