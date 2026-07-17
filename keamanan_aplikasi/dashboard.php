<?php
// manggil file koneksi dan helper
require_once "config/koneksi.php";
require_once "config/helper.php";

//pastikan user dah log in
auth_check();

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// backend crud pake post
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    file_put_contents(
        "post_log.txt",
        "POST Data: " . print_r($_POST, true) . "\n",
        FILE_APPEND,
    );
    header("Content-Type: application/json");
    $action = $_POST["action"];

    try {
        // Aksi 1: Tambah Transaksi (Create)
        if ($action === "create") {
            $jenis = isset($_POST["jenis"]) ? trim($_POST["jenis"]) : "";

            if ($jenis === "Transfer") {
                $id_dompet_asal = isset($_POST["id_dompet_asal"]) && $_POST["id_dompet_asal"] !== "" ? intval($_POST["id_dompet_asal"]) : null;
                $id_dompet_tujuan = isset($_POST["id_dompet_tujuan"]) && $_POST["id_dompet_tujuan"] !== "" ? intval($_POST["id_dompet_tujuan"]) : null;
                $id_kategori = isset($_POST["id_kategori"]) && $_POST["id_kategori"] !== "" ? intval($_POST["id_kategori"]) : null;
                $nominal = isset($_POST["nominal"]) ? trim($_POST["nominal"]) : "";
                $keterangan = isset($_POST["keterangan"]) ? trim($_POST["keterangan"]) : "";
                $tanggal = isset($_POST["tanggal"]) ? trim($_POST["tanggal"]) : "";

                if (empty($id_dompet_asal) || empty($id_dompet_tujuan) || empty($nominal) || empty($keterangan) || empty($tanggal)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Semua field transfer wajib diisi.",
                    ]);
                    exit();
                }
                if ($id_dompet_asal === $id_dompet_tujuan) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Dompet asal dan tujuan tidak boleh sama.",
                    ]);
                    exit();
                }
                if (!is_numeric($nominal) || floatval($nominal) <= 0) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Nominal harus angka positif.",
                    ]);
                    exit();
                }

                // Ambil nama dompet untuk dicatat di keterangan otomatis
                $stmtDompet = $pdo->prepare("SELECT id_dompet, nama_dompet FROM dompet WHERE id_dompet IN (:asal, :tujuan) AND id_user = :user_id");
                $stmtDompet->execute([
                    "asal" => $id_dompet_asal,
                    "tujuan" => $id_dompet_tujuan,
                    "user_id" => $user_id
                ]);
                $dompetNames = [];
                foreach ($stmtDompet->fetchAll() as $d) {
                    $dompetNames[$d['id_dompet']] = $d['nama_dompet'];
                }

                $nama_asal = isset($dompetNames[$id_dompet_asal]) ? $dompetNames[$id_dompet_asal] : "E-Wallet Asal";
                $nama_tujuan = isset($dompetNames[$id_dompet_tujuan]) ? $dompetNames[$id_dompet_tujuan] : "E-Wallet Tujuan";

                $pdo->beginTransaction();

                // 1. Catat Pengeluaran dari Dompet Asal (ditandai is_transfer = 1)
                $stmt1 = $pdo->prepare(
                    "INSERT INTO transaksi (user_id, id_kategori, id_dompet, jenis, nominal, keterangan, tanggal, is_transfer) VALUES (:user_id, :id_kategori, :id_dompet, 'Pengeluaran', :nominal, :keterangan, :tanggal, 1)"
                );
                $stmt1->execute([
                    "user_id" => $user_id,
                    "id_kategori" => $id_kategori,
                    "id_dompet" => $id_dompet_asal,
                    "nominal" => floatval($nominal),
                    "keterangan" => "Transfer ke " . $nama_tujuan . " (" . $keterangan . ")",
                    "tanggal" => $tanggal,
                ]);

                // 2. Catat Pemasukan ke Dompet Tujuan (ditandai is_transfer = 1)
                $stmt2 = $pdo->prepare(
                    "INSERT INTO transaksi (user_id, id_kategori, id_dompet, jenis, nominal, keterangan, tanggal, is_transfer) VALUES (:user_id, :id_kategori, :id_dompet, 'Pemasukan', :nominal, :keterangan, :tanggal, 1)"
                );
                $stmt2->execute([
                    "user_id" => $user_id,
                    "id_kategori" => $id_kategori,
                    "id_dompet" => $id_dompet_tujuan,
                    "nominal" => floatval($nominal),
                    "keterangan" => "Transfer dari " . $nama_asal . " (" . $keterangan . ")",
                    "tanggal" => $tanggal,
                ]);

                $pdo->commit();

                echo json_encode([
                    "status" => "success",
                    "message" => "Transfer saldo berhasil dilakukan!",
                ]);
                exit();
            }
            $id_kategori =
                isset($_POST["id_kategori"]) && $_POST["id_kategori"] !== ""
                    ? intval($_POST["id_kategori"])
                    : null;
            $nominal = isset($_POST["nominal"]) ? trim($_POST["nominal"]) : "";
            $keterangan = isset($_POST["keterangan"])
                ? trim($_POST["keterangan"])
                : "";
            $tanggal = isset($_POST["tanggal"]) ? trim($_POST["tanggal"]) : "";

            if (
                empty($jenis) ||
                empty($nominal) ||
                empty($keterangan) ||
                empty($tanggal)
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Semua field wajib diisi.",
                ]);
                exit();
            }
            if (!in_array($jenis, ["Pemasukan", "Pengeluaran"], true)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Jenis transaksi tidak valid.",
                ]);
                exit();
            }
            if (!is_numeric($nominal) || floatval($nominal) <= 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Nominal harus angka positif.",
                ]);
                exit();
            }

            $id_dompet =
                isset($_POST["id_dompet"]) && $_POST["id_dompet"] !== ""
                    ? intval($_POST["id_dompet"])
                    : null;

            $stmt = $pdo->prepare(
                "INSERT INTO transaksi (user_id, id_kategori, id_dompet, jenis, nominal, keterangan, tanggal) VALUES (:user_id, :id_kategori, :id_dompet, :jenis, :nominal, :keterangan, :tanggal)",
            );
            $stmt->execute([
                "user_id" => $user_id,
                "id_kategori" => $id_kategori,
                "id_dompet" => $id_dompet,
                "jenis" => $jenis,
                "nominal" => floatval($nominal),
                "keterangan" => $keterangan,
                "tanggal" => $tanggal,
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Transaksi berhasil ditambahkan!",
            ]);
            exit();
        }

        // Aksi 2: Edit Transaksi (Update)
        if ($action === "update") {
            $id = isset($_POST["id"]) ? trim($_POST["id"]) : "";
            $jenis = isset($_POST["jenis"]) ? trim($_POST["jenis"]) : "";
            $id_kategori =
                isset($_POST["id_kategori"]) && $_POST["id_kategori"] !== ""
                    ? intval($_POST["id_kategori"])
                    : null;
            $id_dompet =
                isset($_POST["id_dompet"]) && $_POST["id_dompet"] !== ""
                    ? intval($_POST["id_dompet"])
                    : null;
            $nominal = isset($_POST["nominal"]) ? trim($_POST["nominal"]) : "";
            $keterangan = isset($_POST["keterangan"])
                ? trim($_POST["keterangan"])
                : "";
            $tanggal = isset($_POST["tanggal"]) ? trim($_POST["tanggal"]) : "";

            if (
                empty($id) ||
                empty($jenis) ||
                empty($nominal) ||
                empty($keterangan) ||
                empty($tanggal)
            ) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Semua field wajib diisi.",
                ]);
                exit();
            }

            $stmt = $pdo->prepare(
                "UPDATE transaksi SET id_kategori = :id_kategori, id_dompet = :id_dompet, jenis = :jenis, nominal = :nominal, keterangan = :keterangan, tanggal = :tanggal WHERE id = :id AND user_id = :user_id",
            );
            $stmt->execute([
                "id_kategori" => $id_kategori,
                "id_dompet" => $id_dompet,
                "jenis" => $jenis,
                "nominal" => floatval($nominal),
                "keterangan" => $keterangan,
                "tanggal" => $tanggal,
                "id" => $id,
                "user_id" => $user_id,
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Transaksi berhasil diperbarui!",
            ]);
            exit();
        }

        // Aksi 3: Hapus Transaksi (Delete)
        if ($action === "delete") {
            $id = isset($_POST["id"]) ? trim($_POST["id"]) : "";

            $stmt = $pdo->prepare(
                "DELETE FROM transaksi WHERE id = :id AND user_id = :user_id",
            );
            $stmt->execute(["id" => $id, "user_id" => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Transaksi berhasil dihapus!",
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Data tidak ditemukan atau tidak ada akses.",
                ]);
            }
            exit();
        }
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => "Kesalahan server backend.",
        ]);
        exit();
    }
}

// --- LOGIK UTAMA: MENAMPILKAN DATA (Read) ---
try {
    // Hitung total pemasukan & pengeluaran TANPA menyertakan transaksi transfer antar dompet
    $stmt_income = $pdo->prepare(
        "SELECT SUM(nominal) as total FROM transaksi WHERE user_id = :user_id AND jenis = 'Pemasukan' AND is_transfer = 0",
    );
    $stmt_income->execute(["user_id" => $user_id]);
    $total_pemasukan = $stmt_income->fetch()["total"] ?? 0;

    $stmt_expense = $pdo->prepare(
        "SELECT SUM(nominal) as total FROM transaksi WHERE user_id = :user_id AND jenis = 'Pengeluaran' AND is_transfer = 0",
    );
    $stmt_expense->execute(["user_id" => $user_id]);
    $total_pengeluaran = $stmt_expense->fetch()["total"] ?? 0;

    $saldo_sekarang = $total_pemasukan - $total_pengeluaran;

    // Ambil daftar kategori kustom milik user
    $stmt_cat = $pdo->prepare(
        "SELECT * FROM kategori WHERE id_user = :user_id ORDER BY nama_kategori ASC",
    );
    $stmt_cat->execute(["user_id" => $user_id]);
    $categories = $stmt_cat->fetchAll();

    // Ambil daftar dompet milik user beserta kalkulasi saldo aktifnya
    $stmt_wallets = $pdo->prepare(
        "SELECT d.id_dompet, d.nama_dompet,
               (COALESCE((SELECT SUM(t.nominal) FROM transaksi t WHERE t.id_dompet = d.id_dompet AND t.jenis = 'Pemasukan'), 0) -
                COALESCE((SELECT SUM(t.nominal) FROM transaksi t WHERE t.id_dompet = d.id_dompet AND t.jenis = 'Pengeluaran'), 0)) AS saldo
         FROM dompet d
         WHERE d.id_user = :user_id
         ORDER BY d.nama_dompet ASC"
    );
    $stmt_wallets->execute(["user_id" => $user_id]);
    $wallets = $stmt_wallets->fetchAll();

    // Query Transaksi dengan Left Join Kategori dan Dompet
    $query =
        "SELECT t.*, k.nama_kategori, d.nama_dompet FROM transaksi t LEFT JOIN kategori k ON t.id_kategori = k.id_kategori LEFT JOIN dompet d ON t.id_dompet = d.id_dompet WHERE t.user_id = :user_id";
    $params = ["user_id" => $user_id];

    $jenis_filter = isset($_GET["jenis"]) ? trim($_GET["jenis"]) : "";
    if ($jenis_filter === "Pemasukan" || $jenis_filter === "Pengeluaran") {
        $query .= " AND t.jenis = :jenis";
        $params["jenis"] = $jenis_filter;
    }

    $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
    if ($search !== "") {
        $query .= " AND t.keterangan LIKE :search";
        $params["search"] = "%" . $search . "%";
    }

    // Filter Lanjutan 1: Rentang Tanggal
    $tanggal_mulai = isset($_GET["tanggal_mulai"]) ? trim($_GET["tanggal_mulai"]) : "";
    if ($tanggal_mulai !== "") {
        $query .= " AND t.tanggal >= :tanggal_mulai";
        $params["tanggal_mulai"] = $tanggal_mulai;
    }
    $tanggal_selesai = isset($_GET["tanggal_selesai"]) ? trim($_GET["tanggal_selesai"]) : "";
    if ($tanggal_selesai !== "") {
        $query .= " AND t.tanggal <= :tanggal_selesai";
        $params["tanggal_selesai"] = $tanggal_selesai;
    }

    // Filter Lanjutan 2: Kategori Spesifik
    $id_kategori_filter = isset($_GET["id_kategori_filter"]) ? trim($_GET["id_kategori_filter"]) : "";
    if ($id_kategori_filter !== "") {
        if ($id_kategori_filter === "NULL") {
            $query .= " AND t.id_kategori IS NULL";
        } else {
            $query .= " AND t.id_kategori = :id_kategori_filter";
            $params["id_kategori_filter"] = intval($id_kategori_filter);
        }
    }

    // Filter Lanjutan 2b: Dompet Spesifik
    $id_dompet_filter = isset($_GET["id_dompet_filter"]) ? trim($_GET["id_dompet_filter"]) : "";
    if ($id_dompet_filter !== "") {
        if ($id_dompet_filter === "NULL") {
            $query .= " AND t.id_dompet IS NULL";
        } else {
            $query .= " AND t.id_dompet = :id_dompet_filter";
            $params["id_dompet_filter"] = intval($id_dompet_filter);
        }
    }

    // Filter Lanjutan 3: Rentang Nominal
    $nominal_min = isset($_GET["nominal_min"]) ? trim($_GET["nominal_min"]) : "";
    if ($nominal_min !== "" && is_numeric($nominal_min)) {
        $query .= " AND t.nominal >= :nominal_min";
        $params["nominal_min"] = floatval($nominal_min);
    }
    $nominal_max = isset($_GET["nominal_max"]) ? trim($_GET["nominal_max"]) : "";
    if ($nominal_max !== "" && is_numeric($nominal_max)) {
        $query .= " AND t.nominal <= :nominal_max";
        $params["nominal_max"] = floatval($nominal_max);
    }

    $query .= " ORDER BY t.tanggal DESC, t.id DESC";
    $stmt_transactions = $pdo->prepare($query);
    $stmt_transactions->execute($params);
    $transactions = $stmt_transactions->fetchAll();

    // Query Data untuk Grafik Pengeluaran per Kategori
    $stmt_cat_chart = $pdo->prepare(
        "SELECT COALESCE(k.nama_kategori, 'Tanpa Kategori') as nama_kategori, SUM(t.nominal) as total 
         FROM transaksi t 
         LEFT JOIN kategori k ON t.id_kategori = k.id_kategori 
         WHERE t.user_id = :user_id AND t.jenis = 'Pengeluaran' AND t.is_transfer = 0 
         GROUP BY t.id_kategori, k.nama_kategori"
    );
    $stmt_cat_chart->execute(["user_id" => $user_id]);
    $cat_chart_data = $stmt_cat_chart->fetchAll();

    $cat_labels = [];
    $cat_totals = [];
    foreach ($cat_chart_data as $c_row) {
        $cat_labels[] = $c_row['nama_kategori'];
        $cat_totals[] = floatval($c_row['total']);
    }
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Gagal mengambil data dari database.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary bg-gradient shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php"><i class="bi bi-wallet2 me-2"></i> DompetKu</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-3">
                    <li class="nav-item"><a class="nav-link active font-bold" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="budgeting.php">Anggaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="target_tabungan.php">Target Tabungan</a></li>
                    <li class="nav-item"><a class="nav-link" href="dompet.php">Dompet</a></li>
                    <li class="nav-item"><a class="nav-link" href="kalender.php">Kalender</a></li>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link d-flex align-items-center gap-1" href="admin_dashboard.php">
                            <span class="badge bg-warning text-dark" style="font-size: 0.7rem; padding: 3px 7px; border-radius: 6px;">
                                <i class="bi bi-shield-fill me-1"></i>Admin
                            </span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item text-white me-3">
                        <i class="bi bi-person-circle me-1"></i> Halo, <strong><?= escape(
                            $username,
                        ) ?></strong>
                    </li>
                    <li class="nav-item"><a class="btn btn-light btn-sm text-primary" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php display_flash_message(); ?>

        <!-- Bagian Alert Peringatan Anggaran -->
        <div class="row mb-4">
            <div class="col-12">
                <?php
                $bulan_sekarang = intval(date("m"));
                $tahun_sekarang = intval(date("Y"));
                $daftar_anggaran = dapatkan_analisis_anggaran(
                    $user_id,
                    $bulan_sekarang,
                    $tahun_sekarang,
                );

                foreach ($daftar_anggaran as $anggaran):
                    $persentase = $anggaran["persentase"];
                    $nama_kategori = escape($anggaran["nama_kategori"]);
                    $sisa = $anggaran["budget"] - $anggaran["pengeluaran"];

                    if ($persentase >= 90): ?>
                        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-2" role="alert">
                            <i class="bi bi-exclamation-octagon-fill fs-5 me-3"></i>
                            <div class="flex-grow-1">
                                <strong>Kritis!</strong> Anggaran untuk kategori <strong><?= $nama_kategori ?></strong> hampir habis atau telah terlampaui.
                                <br>
                                <small>Terpakai: <strong><?= format_rupiah(
                                    $anggaran["pengeluaran"],
                                ) ?></strong> dari limit <strong><?= format_rupiah(
    $anggaran["budget"],
) ?></strong> (<?= number_format($persentase, 1) ?>%)</small>
                            </div>
                            <span class="badge bg-danger p-2 fs-7 text-uppercase">Limit Kritis</span>
                        </div>

                    <?php elseif ($persentase >= 70 && $persentase < 90): ?>
                        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill fs-5 me-3 text-warning"></i>
                            <div class="flex-grow-1">
                                <strong>Peringatan!</strong> Pengeluaran untuk kategori <strong><?= $nama_kategori ?></strong> sudah mendekati batas limit anggaran bulanan.
                                <br>
                                <small>Sisa Saldo Anggaran: <strong><?= format_rupiah(
                                    $sisa,
                                ) ?></strong> lagi dari total <strong><?= format_rupiah(
    $anggaran["budget"],
) ?></strong> (<?= number_format($persentase, 1) ?>% terpakai)</small>
                            </div>
                            <span class="badge bg-warning text-dark p-2 fs-7 text-uppercase">Waspada</span>
                        </div>
                <?php endif;
                endforeach;
                ?>
            </div>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4">
                    <span class="text-muted text-uppercase text-xs font-bold">Total Pemasukan</span>
                    <h3 class="text-success font-bold mt-2"><?= format_rupiah(
                        $total_pemasukan,
                    ) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <span class="text-muted text-uppercase text-xs font-bold">Total Pengeluaran</span>
                    <h3 class="text-danger font-bold mt-2"><?= format_rupiah(
                        $total_pengeluaran,
                    ) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <span class="text-muted text-uppercase text-xs font-bold">Saldo Saat Ini</span>
                    <h3 class="font-bold mt-2 <?= $saldo_sekarang >= 0
                        ? "text-primary"
                        : "text-danger" ?>"><?= format_rupiah(
                        $saldo_sekarang,
                    ) ?></h3>
                </div>
            </div>
        </div>

        <!-- Bagian Rincian Saldo per Dompet -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card p-4 shadow-sm border-0">
                    <h5 class="font-bold mb-3 text-secondary"><i class="bi bi-wallet2 text-primary me-2"></i> Rincian Saldo per Dompet</h5>
                    <div class="row g-3">
                        <?php if (empty($wallets)): ?>
                            <div class="col-12 text-muted" style="font-size: 0.9rem;">
                                Belum ada dompet terdaftar. Silakan kelola di menu <a href="dompet.php">Dompet</a>.
                            </div>
                        <?php else: ?>
                            <?php foreach ($wallets as $wallet): ?>
                                <div class="col-md-3">
                                    <div class="p-3 border rounded bg-light">
                                        <span class="text-muted text-uppercase text-xs font-bold"><?= escape($wallet['nama_dompet']) ?></span>
                                        <h5 class="font-bold mt-1 mb-0 <?= $wallet['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= format_rupiah($wallet['saldo']) ?>
                                        </h5>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Row untuk Grafik Ringkasan Keuangan -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card p-4 shadow-sm border-0 h-100">
                    <h5 class="font-bold mb-3 text-secondary"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Distribusi Pengeluaran per Kategori</h5>
                    <div style="position: relative; height: 260px;">
                        <canvas id="categoryExpenseChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card p-4 shadow-sm border-0 h-100">
                    <h5 class="font-bold mb-3 text-secondary"><i class="bi bi-bar-chart-line-fill me-2 text-success"></i>Perbandingan Aliran Dana (Pemasukan vs Pengeluaran)</h5>
                    <div style="position: relative; height: 260px;">
                        <canvas id="flowComparisonChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="font-bold mb-0">Daftar Transaksi</h4>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalImport">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Transaksi
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="dropdownExport" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi w-4 h-4 bi-download me-1"></i> Ekspor Laporan
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownExport">
                            <li>
                                <a class="dropdown-item py-2" href="export_excel.php?<?= http_build_query(
                                    $_GET,
                                ) ?>">
                                    <i class="bi bi-file-earmark-excel text-success me-2"></i> Ekspor ke Excel (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="export_docx.php?<?= http_build_query(
                                    $_GET,
                                ) ?>">
                                    <i class="bi bi-file-earmark-word text-primary me-2"></i> Ekspor ke Word (.doc)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="export_pdf.php?<?= http_build_query(
                                    $_GET,
                                ) ?>">
                                    <i class="bi bi-file-earmark-pdf text-danger me-2"></i> Ekspor ke PDF (.pdf)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTransaksi" onclick="resetModal()">
                        <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi
                    </button>
                </div>
            </div>

            <!-- Filter Transaksi Lanjutan -->
            <form action="dashboard.php" method="GET" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label text-xs font-bold text-secondary">Cari Deskripsi</label>
                        <input type="text" class="form-control" name="search" placeholder="Cari keterangan..." value="<?= escape($search) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-xs font-bold text-secondary">Jenis</label>
                        <select class="form-select select2-init" name="jenis">
                            <option value="">-- Semua Jenis --</option>
                            <option value="Pemasukan" <?= $jenis_filter === "Pemasukan" ? "selected" : "" ?>>Pemasukan</option>
                            <option value="Pengeluaran" <?= $jenis_filter === "Pengeluaran" ? "selected" : "" ?>>Pengeluaran</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-xs font-bold text-secondary">Kategori</label>
                        <select class="form-select select2-init" name="id_kategori_filter">
                            <option value="">-- Semua Kategori --</option>
                            <option value="NULL" <?= $id_kategori_filter === "NULL" ? "selected" : "" ?>>Tanpa Kategori</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id_kategori'] ?>" <?= $id_kategori_filter == $cat['id_kategori'] ? "selected" : "" ?>>
                                    <?= escape($cat['nama_kategori']) ?> (<?= escape($cat['tipe']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-xs font-bold text-secondary">Dompet</label>
                        <select class="form-select select2-init" name="id_dompet_filter">
                            <option value="">-- Semua Dompet --</option>
                            <option value="NULL" <?= $id_dompet_filter === "NULL" ? "selected" : "" ?>>Tanpa Dompet</option>
                            <?php foreach ($wallets as $w): ?>
                                <option value="<?= $w['id_dompet'] ?>" <?= $id_dompet_filter == $w['id_dompet'] ? "selected" : "" ?>>
                                    <?= escape($w['nama_dompet']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilterCollapse" aria-expanded="false" aria-controls="advancedFilterCollapse" title="Filter Lanjutan">
                            <i class="bi bi-sliders"></i>
                        </button>
                        <?php if ($search !== "" || $jenis_filter !== "" || $id_kategori_filter !== "" || $id_dompet_filter !== "" || $tanggal_mulai !== "" || $tanggal_selesai !== "" || $nominal_min !== "" || $nominal_max !== ""): ?>
                            <a href="dashboard.php" class="btn btn-light border" title="Reset Filter"><i class="bi bi-arrow-counterclockwise"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Collapsible Advanced Filters -->
                <div class="collapse <?= ($tanggal_mulai !== "" || $tanggal_selesai !== "" || $nominal_min !== "" || $nominal_max !== "") ? "show" : "" ?> mt-3" id="advancedFilterCollapse">
                    <div class="card card-body bg-light border-0 py-3 shadow-none">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label text-xs font-bold text-secondary">Tanggal Mulai</label>
                                <input type="date" class="form-control" name="tanggal_mulai" value="<?= escape($tanggal_mulai) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-xs font-bold text-secondary">Tanggal Selesai</label>
                                <input type="date" class="form-control" name="tanggal_selesai" value="<?= escape($tanggal_selesai) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-xs font-bold text-secondary">Nominal Minimal (Rp)</label>
                                <input type="number" class="form-control" name="nominal_min" placeholder="Contoh: 10000" value="<?= escape($nominal_min) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-xs font-bold text-secondary">Nominal Maksimal (Rp)</label>
                                <input type="number" class="form-control" name="nominal_max" placeholder="Contoh: 500000" value="<?= escape($nominal_max) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Kategori</th>
                            <th>Dompet</th>
                            <th>Keterangan</th>
                            <th>Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data transaksi.</td></tr>
                        <?php else: ?>
                            <?php
                            $no = 1;
                            foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= date(
                                        "d M Y",
                                        strtotime($row["tanggal"]),
                                    ) ?></td>
                                    <td>
                                        <?php if (isset($row["is_transfer"]) && $row["is_transfer"] == 1): ?>
                                            <span class="badge bg-primary">Transfer</span>
                                        <?php else: ?>
                                            <span class="<?= $row["jenis"] === "Pemasukan" ? "badge bg-success" : "badge bg-danger" ?>"><?= escape($row["jenis"]) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $row[
                                        "nama_kategori"
                                    ]
                                        ? escape($row["nama_kategori"])
                                        : "Tanpa Kategori" ?></span></td>
                                    <td><span class="badge bg-info text-dark"><?= $row[
                                        "nama_dompet"
                                    ]
                                        ? escape($row["nama_dompet"])
                                        : "Tanpa Dompet" ?></span></td>
                                    <td><?= escape($row["keterangan"]) ?></td>
                                    <td class="font-bold <?= (isset($row["is_transfer"]) && $row["is_transfer"] == 1) ? "text-primary" : ($row["jenis"] === "Pemasukan" ? "text-success" : "text-danger") ?>">
                                        <?= ($row["jenis"] === "Pemasukan"
                                            ? "+ "
                                            : "- ") .
                                            format_rupiah($row["nominal"]) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-success dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Ekspor Kuitansi">
                                                    <i class="bi bi-download"></i>
                                                </button>
                                                <ul class="dropdown-menu shadow">
                                                    <li>
                                                        <a class="dropdown-item py-1" href="export_excel.php?id=<?= $row[
                                                            "id"
                                                        ] ?>">
                                                            <i class="bi bi-file-earmark-excel text-success me-2"></i> Kuitansi Excel
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1" href="export_docx.php?id=<?= $row[
                                                            "id"
                                                        ] ?>">
                                                            <i class="bi bi-file-earmark-word text-primary me-2"></i> Kuitansi Word
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1" href="export_pdf.php?id=<?= $row[
                                                            "id"
                                                        ] ?>">
                                                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i> Kuitansi PDF
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" 
                                                    data-id="<?= $row["id"] ?>" 
                                                    data-jenis="<?= $row[
                                                        "jenis"
                                                    ] ?>" 
                                                    data-kategori="<?= $row[
                                                        "id_kategori"
                                                    ] ?>" 
                                                    data-dompet="<?= $row[
                                                        "id_dompet"
                                                    ] ?>" 
                                                    data-nominal="<?= $row[
                                                        "nominal"
                                                    ] ?>" 
                                                    data-tanggal="<?= $row[
                                                        "tanggal"
                                                    ] ?>" 
                                                    data-keterangan="<?= escape(
                                                        $row["keterangan"],
                                                    ) ?>"
                                                    title="Edit Transaksi">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $row[
                                                "id"
                                            ] ?>" title="Hapus Transaksi">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                            ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalTransaksi"  tabindex="-1" aria-labelledby="modalTransaksiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="modalTransaksiLabel">Tambah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-shadow="none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formTransaksi">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="transaksiId" value="">

                        <div class="mb-3">
                            <label for="jenis" class="form-label">Jenis Transaksi</label>
                            <select class="form-select modal-select2" id="jenis" name="jenis" required style="width: 100%;">
                                <option value="">-- Pilih Jenis --</option>
                                <option value="Pemasukan">Pemasukan (Uang Masuk)</option>
                                <option value="Pengeluaran">Pengeluaran (Uang Keluar)</option>
                                <option value="Transfer">Transfer Saldo (Pindah E-Wallet)</option>
                            </select>
                        </div>
                        
                        <!-- Kategori Field (Visible for Pemasukan, Pengeluaran, and Transfer) -->
                        <div class="mb-3" id="kategori_field_container">
                            <label for="id_kategori" class="form-label">Kategori</label>
                            <select class="form-select modal-select2" id="id_kategori" name="id_kategori" style="width: 100%;">
                                <option value="">-- Tanpa Kategori --</option>
                            </select>
                        </div>

                        <!-- Bidang standar untuk Pemasukan / Pengeluaran -->
                        <div id="standard_fields">
                            <div class="mb-3">
                                <label for="id_dompet" class="form-label">Dompet / Rekening</label>
                                <select class="form-select modal-select2" id="id_dompet" name="id_dompet" required style="width: 100%;">
                                    <option value="">-- Pilih Dompet --</option>
                                    <?php foreach ($wallets as $w): ?>
                                        <option value="<?= $w['id_dompet'] ?>"><?= escape($w['nama_dompet']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Bidang khusus untuk Transfer Saldo -->
                        <div id="transfer_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="id_dompet_asal" class="form-label">Dompet Asal (Dikurangi)</label>
                                <select class="form-select modal-select2" id="id_dompet_asal" name="id_dompet_asal" style="width: 100%;">
                                    <option value="">-- Pilih Dompet Asal --</option>
                                    <?php foreach ($wallets as $w): ?>
                                        <option value="<?= $w['id_dompet'] ?>"><?= escape($w['nama_dompet']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="id_dompet_tujuan" class="form-label">Dompet Tujuan (Ditambahkan)</label>
                                <select class="form-select modal-select2" id="id_dompet_tujuan" name="id_dompet_tujuan" style="width: 100%;">
                                    <option value="">-- Pilih Dompet Tujuan --</option>
                                    <?php foreach ($wallets as $w): ?>
                                        <option value="<?= $w['id_dompet'] ?>"><?= escape($w['nama_dompet']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal (Rupiah)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="nominal" name="nominal" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal Transaksi</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= date(
                                "Y-m-d",
                            ) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <input type="text" class="form-control" id="keterangan" name="keterangan" required maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSimpan">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Import Transaksi -->
    <div class="modal fade" id="modalImport" tabindex="-1" aria-labelledby="modalImportLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="modalImportLabel">Import Transaksi dari Dokumen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="import_document.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="alert alert-info py-2 px-3 mb-3" style="font-size: 0.88rem;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Petunjuk Format Dokumen:</strong>
                            <ul class="mb-0 ps-3 mt-1">
                                <li><strong>Excel (.xlsx)</strong>: Unggah langsung berkas Excel Anda.</li>
                                <li><strong>Word (DOCX)</strong>: Tulis data dalam tabel, simpan sebagai .docx.</li>
                                <li><strong>PDF</strong>: Gunakan format laporan PDF asli yang diunduh dari sistem.</li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <label for="file_dokumen" class="form-label font-bold">Pilih File Laporan / Template</label>
                            <input type="file" class="form-control" id="file_dokumen" name="file_dokumen" accept=".xlsx, .docx, .doc, .pdf" required>
                            <div class="form-text">Mendukung format berkas <code>.xlsx</code>, <code>.docx</code>, dan <code>.pdf</code>.</div>
                        </div>

                        <!-- Area Unduh Template -->
                        <div class="card bg-light border-0 p-3 mb-2">
                            <h6 class="font-bold mb-3 text-secondary" style="font-size: 0.85rem; text-transform: uppercase;">Unduh Template Pengisian:</h6>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                                <a href="download_template.php" class="btn btn-sm btn-outline-success w-100 text-nowrap">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Template Excel
                                </a>
                                <a href="download_template_docx.php" class="btn btn-sm btn-outline-primary w-100 text-nowrap">
                                    <i class="bi bi-file-earmark-word me-1"></i> Template Word
                                </a>
                                <a href="download_template_pdf.php" class="btn btn-sm btn-outline-danger w-100 text-nowrap">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Template PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Unggah & Proses Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Data Kategori dari PHP
        const categories = <?= json_encode($categories) ?>;

        $(document).ready(function() {
            // --- INISIALISASI SELECT2 ---
            $('.select2-init').select2({
                theme: 'bootstrap-5'
            });

            $('#jenis').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTransaksi')
            });

            $('#id_kategori').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTransaksi')
            });

            $('#id_dompet').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTransaksi')
            });

            $('#id_dompet_asal').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTransaksi')
            });

            $('#id_dompet_tujuan').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTransaksi')
            });

            // --- PILIHAN KATEGORI DINAMIS ---
            function updateCategoryOptions(selectedJenis, selectedKategoriId = null) {
                const $catSelect = $('#id_kategori');
                
                // Hancurkan Select2 jika sudah aktif sebelum memodifikasi DOM
                if ($catSelect.hasClass("select2-hidden-accessible")) {
                    $catSelect.select2('destroy');
                }
                
                $catSelect.empty().append('<option value="">-- Tanpa Kategori --</option>');
                
                if (selectedJenis) {
                    let filtered = [];
                    if (selectedJenis === 'Transfer') {
                        filtered = categories; // Tampilkan semua kategori untuk transfer
                    } else {
                        filtered = categories.filter(c => c.tipe === selectedJenis);
                    }
                    filtered.forEach(c => {
                        const isSelected = (selectedKategoriId && parseInt(c.id_kategori) === parseInt(selectedKategoriId)) ? 'selected' : '';
                        const labelTipe = (selectedJenis === 'Transfer') ? ` (${c.tipe === 'Pemasukan' ? 'Masuk' : 'Keluar'})` : '';
                        $catSelect.append(`<option value="${c.id_kategori}" ${isSelected}>${c.nama_kategori}${labelTipe}</option>`);
                    });
                }
                
                // Bangun ulang Select2 setelah option terpasang
                $catSelect.select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#modalTransaksi')
                });
                
                $catSelect.trigger('change');
            }

            $('#jenis').on('change', function() {
                const selectedJenis = $(this).val();
                if (selectedJenis === 'Transfer') {
                    $('#standard_fields').hide();
                    $('#id_dompet').prop('required', false);
                    
                    $('#transfer_fields').show();
                    $('#id_dompet_asal').prop('required', true);
                    $('#id_dompet_tujuan').prop('required', true);
                } else {
                    $('#transfer_fields').hide();
                    $('#id_dompet_asal').prop('required', false);
                    $('#id_dompet_tujuan').prop('required', false);
                    
                    $('#standard_fields').show();
                    $('#id_dompet').prop('required', true);
                }
                updateCategoryOptions(selectedJenis);
            });

            // --- PROSES SIMPAN DATA (CREATE & UPDATE VIA AJAX) ---
            $('#formTransaksi').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'dashboard.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload(); // Reload halaman untuk memperbarui tabel & saldo
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message });
                        }
                    }
                });
            });

            // --- TOMBOL EDIT DIKLIK ---
            $('.btn-edit').on('click', function() {
                const id = $(this).data('id');
                const jenis = $(this).data('jenis');
                const kategoriId = $(this).data('kategori');
                const dompetId = $(this).data('dompet');
                const nominal = $(this).data('nominal');
                const tanggal = $(this).data('tanggal');
                const keterangan = $(this).data('keterangan');

                // Mengubah setelan modal menjadi Mode Edit
                $('#modalTransaksiLabel').text('Edit Transaksi');
                $('#formAction').val('update');
                $('#transaksiId').val(id);
                
                // Matikan opsi Transfer saat dalam Mode Edit
                $('#jenis option[value="Transfer"]').prop('disabled', true);
                $('#jenis').val(jenis).trigger('change'); // Update Select2 value
                
                // Perbarui opsi kategori dan set terpilih
                updateCategoryOptions(jenis, kategoriId);

                // Set nilai dompet
                $('#id_dompet').val(dompetId).trigger('change');

                $('#nominal').val(nominal);
                $('#tanggal').val(tanggal);
                $('#keterangan').val(keterangan);

                // Tampilkan Modal
                $('#modalTransaksi').modal('show');
            });

            // --- TOMBOL HAPUS DIKLIK (DELETE VIA SWEETALERT2 CONFIRMATION) ---
            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data transaksi ini akan dihapus permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'dashboard.php',
                            type: 'POST',
                            data: { action: 'delete', id: id },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire('Terhapus!', response.message, 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Gagal!', response.message, 'error');
                                }
                            }
                        });
                    }
                });
            });
        });

        // --- RESET MODAL KE MODE TAMBAH ---
        function resetModal() {
            $('#modalTransaksiLabel').text('Tambah Transaksi');
            $('#formAction').val('create');
            $('#transaksiId').val('');
            $('#formTransaksi')[0].reset();
            
            // Aktifkan kembali opsi Transfer untuk transaksi baru
            $('#jenis option[value="Transfer"]').prop('disabled', false);
            $('#jenis').val('').trigger('change');
            
            $('#id_kategori').empty().append('<option value="">-- Tanpa Kategori --</option>').trigger('change');
            $('#id_dompet').val('').trigger('change');
            $('#id_dompet_asal').val('').trigger('change');
            $('#id_dompet_tujuan').val('').trigger('change');
        }
    </script>
    
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Data untuk Grafik Pie Kategori Pengeluaran
            const catLabels = <?= json_encode($cat_labels) ?>;
            const catTotals = <?= json_encode($cat_totals) ?>;
            const totalIncome = <?= floatval($total_pemasukan) ?>;
            const totalExpense = <?= floatval($total_pengeluaran) ?>;

            // 1. Doughnut Chart - Distribusi Pengeluaran Kategori
            const ctxPie = document.getElementById('categoryExpenseChart').getContext('2d');
            if (catLabels.length === 0) {
                // Tampilkan teks jika belum ada pengeluaran
                ctxPie.font = "14px Arial";
                ctxPie.fillStyle = "#888";
                ctxPie.textAlign = "center";
                ctxPie.textBaseline = "middle";
                ctxPie.fillText("Belum ada pengeluaran untuk divisualisasikan.", ctxPie.canvas.width / 2, ctxPie.canvas.height / 2);
            } else {
                new Chart(ctxPie, {
                    type: 'doughnut',
                    data: {
                        labels: catLabels,
                        datasets: [{
                            data: catTotals,
                            backgroundColor: [
                                '#ff6384', '#36a2eb', '#ffce56', '#4bc0c0', '#9966ff', 
                                '#ff9f40', '#00a86b', '#c9cbcf', '#ff3366', '#33cc99'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                                labels: {
                                    boxWidth: 10,
                                    font: { size: 10 }
                                }
                            }
                        }
                    }
                });
            }

            // 2. Bar Chart - Pemasukan vs Pengeluaran
            const ctxBar = document.getElementById('flowComparisonChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['Pemasukan', 'Pengeluaran'],
                    datasets: [{
                        label: 'Total Dana (Rp)',
                        data: [totalIncome, totalExpense],
                        backgroundColor: ['#2e7d32', '#c62828'], // Green untuk pemasukan, red untuk pengeluaran
                        borderRadius: 5,
                        maxBarThickness: 45
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString('id-ID');
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Rp ' + context.raw.toLocaleString('id-ID');
                                }
                            }
                        }
                    }
                }
            });

            // --- Scroll Restoration untuk Form Filter ---
            // Menyimpan koordinat scroll halaman sesaat sebelum melakukan submit filter/reload
            window.addEventListener('beforeunload', function() {
                localStorage.setItem('dashboard_scroll_pos', window.scrollY);
            });

            // Mengembalikan koordinat scroll jika sebelumnya tersimpan
            const savedScrollPos = localStorage.getItem('dashboard_scroll_pos');
            if (savedScrollPos !== null) {
                // Gunakan setTimeout agar browser menyelesaikan render layout Bootstrap & Select2 terlebih dahulu
                setTimeout(function() {
                    window.scrollTo({
                        top: parseInt(savedScrollPos),
                        behavior: 'instant' // Langsung ke koordinat tanpa animasi transisi lambat
                    });
                    localStorage.removeItem('dashboard_scroll_pos'); // Bersihkan setelah digunakan
                }, 100);
            }
        });
    </script>
</body>
</html>