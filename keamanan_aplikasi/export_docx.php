<?php
// Manggil file koneksi dan helper
require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan user sudah login//
auth_check();

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Ambil parameter ekspor khusus satu transaksi atau filter massal
$id = isset($_GET["id"]) ? trim($_GET["id"]) : "";
$jenis_filter = isset($_GET["jenis"]) ? trim($_GET["jenis"]) : "";
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

try {
    if ($id !== "") {
        $query =
            "SELECT t.*, k.nama_kategori, d.nama_dompet FROM transaksi t LEFT JOIN kategori k ON t.id_kategori = k.id_kategori LEFT JOIN dompet d ON t.id_dompet = d.id_dompet WHERE t.id = :id AND t.user_id = :user_id";
        $params = ["id" => $id, "user_id" => $user_id];
    } else {
        $query = "SELECT t.*, k.nama_kategori, d.nama_dompet FROM transaksi t LEFT JOIN kategori k ON t.id_kategori = k.id_kategori LEFT JOIN dompet d ON t.id_dompet = d.id_dompet WHERE t.user_id = :user_id";
        $params = ["user_id" => $user_id];

        if ($jenis_filter === "Pemasukan" || $jenis_filter === "Pengeluaran") {
            $query .= " AND t.jenis = :jenis";
            $params["jenis"] = $jenis_filter;
        }

        if ($search !== "") {
            $query .= " AND t.keterangan LIKE :search";
            $params["search"] = "%" . $search . "%";
        }

        $query .= " ORDER BY t.tanggal DESC, t.id DESC";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    // Hitung ringkasan
    $total_pemasukan = 0;
    $total_pengeluaran = 0;
    foreach ($transactions as $row) {
        if ($row["jenis"] === "Pemasukan") {
            $total_pemasukan += $row["nominal"];
        } else {
            $total_pengeluaran += $row["nominal"];
        }
    }
    $saldo_sekarang = $total_pemasukan - $total_pengeluaran;
} catch (\PDOException $e) {
    error_log($e->getMessage());
    die("Gagal mengambil data untuk export Word.");
}

// Tentukan judul laporan dan nama file berkas
if ($id !== "") {
    $title_report = "KUITANSI TRANSAKSI - DOMPETKU";
    $filename = "Kuitansi_Transaksi_" . $id . "_" . date("Ymd_His") . ".doc";
} else {
    $title_report = "LAPORAN TRANSAKSI - DOMPETKU";
    $filename = "Laporan_Transaksi_DompetKu_" . date("Ymd_His") . ".doc";
}
header("Content-Type: application/vnd.ms-word");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Render HTML yang dibaca oleh MS Word
?>
<!DOCTYPE html>
<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
<head>
    <meta charset="UTF-8">
    <title>Laporan Transaksi - DompetKu</title>
    <!-- XML khusus agar MS Word membuka halaman dalam tampilan "Print Layout" secara default -->
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument>
            <w:View>Print</w:View>
            <w:Zoom>100</w:Zoom>
            <w:DoNotOptimizeForBrowser/>
        </w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.3;
        }
        .title {
            font-size: 18pt;
            font-weight: bold;
            color: #0d6efd;
            text-align: center;
            margin-bottom: 3px;
        }
        .subtitle {
            font-size: 10pt;
            color: #666666;
            text-align: center;
            margin-bottom: 25px;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 10.5pt;
        }
        .info-table td {
            padding: 2px 0;
        }
        .data-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 10px;
            font-size: 10pt;
        }
        .data-table th {
            background-color: #0d6efd;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            padding: 8px;
            border: 1px solid #cccccc;
        }
        .data-table td {
            padding: 8px;
            border: 1px solid #cccccc;
            vertical-align: middle;
        }
        .data-table tr.even {
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
        .summary-row {
            background-color: #e9ecef;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="title"><?= $title_report ?></div>
    <div class="subtitle">Aplikasi Catatan Keuangan Pribadi yang Aman</div>

    <table class="info-table" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="130"><strong>Nama Pengguna</strong></td>
            <td width="15">:</td>
            <td><?= escape($username) ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Ekspor</strong></td>
            <td>:</td>
            <td><?= date("d M Y H:i:s") ?></td>
        </tr>
        <?php if ($jenis_filter !== ""): ?>
        <tr>
            <td><strong>Filter Jenis</strong></td>
            <td>:</td>
            <td><?= escape($jenis_filter) ?></td>
        </tr>
        <?php endif; ?>
        <?php if ($search !== ""): ?>
        <tr>
            <td><strong>Kata Kunci Cari</strong></td>
            <td>:</td>
            <td>"<?= escape($search) ?>"</td>
        </tr>
        <?php endif; ?>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th width="40" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">No</th>
                <th width="120" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Tanggal</th>
                <th width="100" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Jenis</th>
                <th width="100" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Dompet</th>
                <th width="180" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Keterangan</th>
                <th width="120" style="background-color: #0d6efd; color: #ffffff; font-weight: bold; border: 1px solid #cccccc;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #777777; border: 1px solid #cccccc;">Tidak ada data transaksi.</td>
                </tr>
            <?php else: ?>
                <?php
                $no = 1;
                foreach ($transactions as $row):
                    $class = $no % 2 === 0 ? "even" : ""; ?>
                    <tr class="<?= $class ?>">
                        <td class="text-center" style="border: 1px solid #cccccc;"><?= $no++ ?></td>
                        <td class="text-center" style="border: 1px solid #cccccc;"><?= date(
                            "d M Y",
                            strtotime($row["tanggal"]),
                        ) ?></td>
                        <td class="text-center" style="border: 1px solid #cccccc; font-weight: bold; color: <?= $row[
                            "jenis"
                        ] === "Pemasukan"
                            ? "#198754"
                            : "#dc3545" ?>;">
                            <?= escape($row["jenis"]) ?>
                        </td>
                        <td class="text-center" style="border: 1px solid #cccccc;">
                            <?= $row["nama_dompet"] ? escape($row["nama_dompet"]) : "Tanpa Dompet" ?>
                        </td>
                        <td style="border: 1px solid #cccccc;"><?= escape(
                            $row["keterangan"],
                        ) ?></td>
                        <td class="text-right font-bold <?= $row["jenis"] ===
                        "Pemasukan"
                            ? "text-success"
                            : "text-danger" ?>" style="border: 1px solid #cccccc;">
                            <?=
                            $row["jenis"] === "Pemasukan" ? "+" : "-"
                            ?>
                            <?= number_format($row["nominal"], 0, ",", ".") ?>
                        </td>
                    </tr>
                <?php
                endforeach;
                ?>
                
                <!-- Spacer Row -->
                <tr><td colspan="6" style="border: none; height: 15px; background-color: transparent;"></td></tr>
                
                <!-- Summary Rows -->
                <tr class="summary-row" style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="5" class="text-right" style="border: 1px solid #cccccc; padding: 8px;">Total Pemasukan:</td>
                    <td class="text-right text-success" style="border: 1px solid #cccccc; padding: 8px;"><?= number_format(
                        $total_pemasukan,
                        0,
                        ",",
                        ".",
                    ) ?></td>
                </tr>
                <tr class="summary-row" style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="5" class="text-right" style="border: 1px solid #cccccc; padding: 8px;">Total Pengeluaran:</td>
                    <td class="text-right text-danger" style="border: 1px solid #cccccc; padding: 8px;">-<?= number_format(
                        $total_pengeluaran,
                        0,
                        ",",
                        ".",
                    ) ?></td>
                </tr>
                <tr class="summary-row" style="background-color: #e9ecef; font-weight: bold;">
                    <td colspan="5" class="text-right" style="border: 1px solid #cccccc; padding: 8px;">Saldo Akhir:</td>
                    <td class="text-right <?= $saldo_sekarang >= 0
                        ? "text-primary"
                        : "text-danger" ?>" style="border: 1px solid #cccccc; padding: 8px;">
                        <?= number_format($saldo_sekarang, 0, ",", ".") ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

</body>
</html>
