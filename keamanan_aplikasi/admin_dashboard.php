<?php
// Koneksi database dan helper
require_once "config/koneksi.php";
require_once "config/helper.php";

// Guard: hanya admin yang boleh masuk, user biasa otomatis diarahkan + pesan "Akses ditolak"
admin_check();

$admin_username = $_SESSION["username"];

// Statistik global sistem
$stats = [];

// Total user terdaftar
$stats["total_users"] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Total transaksi seluruh aplikasi (hanya jumlah aktivitas, bukan nominal)
$stats["total_transaksi"] = $pdo->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();

// Jumlah kategori kustom yang pernah dibuat
$stats["total_kategori"] = $pdo->query("SELECT COUNT(*) FROM kategori")->fetchColumn();

// User terdaftar bulan ini
$stats["user_bulan_ini"] = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
)->fetchColumn();

// Tangani aksi POST: nonaktifkan atau hapus user
$flash_admin = null;
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action_admin"])) {
    $target_id = intval($_POST["target_user_id"] ?? 0);

    // Cegah admin menghapus/menonaktifkan dirinya sendiri
    if ($target_id === intval($_SESSION["user_id"])) {
        $flash_admin = ["type" => "danger", "msg" => "Anda tidak dapat melakukan aksi pada akun Anda sendiri."];
    } elseif ($target_id <= 0) {
        $flash_admin = ["type" => "danger", "msg" => "ID pengguna tidak valid."];
    } else {
        $action_admin = $_POST["action_admin"];

        if ($action_admin === "toggle_active") {
            // Ubah status aktif (is_active = 0/1)
            $current = $pdo->prepare("SELECT is_active FROM users WHERE id = :id");
            $current->execute(["id" => $target_id]);
            $row = $current->fetch();
            if ($row !== false) {
                $new_status = ($row["is_active"] == 1) ? 0 : 1;
                $upd = $pdo->prepare("UPDATE users SET is_active = :status WHERE id = :id");
                $upd->execute(["status" => $new_status, "id" => $target_id]);
                $label = $new_status ? "diaktifkan" : "dinonaktifkan";
                $flash_admin = ["type" => "success", "msg" => "Akun pengguna berhasil $label."];
            }
        } elseif ($action_admin === "delete") {
            // Hapus permanen (transaksi & kategori user ikut terhapus via CASCADE)
            $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $del->execute(["id" => $target_id]);
            $flash_admin = ["type" => "success", "msg" => "Akun pengguna berhasil dihapus secara permanen."];
        } elseif ($action_admin === "set_admin") {
            $upd = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = :id");
            $upd->execute(["id" => $target_id]);
            $flash_admin = ["type" => "success", "msg" => "Pengguna berhasil dijadikan Admin."];
        } elseif ($action_admin === "set_user") {
            $upd = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = :id");
            $upd->execute(["id" => $target_id]);
            $flash_admin = ["type" => "success", "msg" => "Role pengguna berhasil diubah menjadi User biasa."];
        }
    }
}

// Daftar seluruh pengguna (Tanpa data saldo finansial sama sekali)
$users_list = $pdo->query(
    "SELECT
        u.id,
        u.username,
        u.email,
        u.role,
        COALESCE(u.is_active, 1) AS is_active,
        u.google_id,
        u.created_at,
        COUNT(t.id) AS jumlah_transaksi
     FROM users u
     LEFT JOIN transaksi t ON t.user_id = u.id
     GROUP BY u.id
     ORDER BY u.created_at DESC"
)->fetchAll();


// Grafik: distribusi transaksi per hari (7 hari terakhir)
$chart_raw = $pdo->query(
    "SELECT DATE(created_at) AS tgl, COUNT(*) AS total
     FROM transaksi
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY tgl ASC"
)->fetchAll();
$chart_labels = json_encode(array_column($chart_raw, "tgl"));
$chart_data   = json_encode(array_column($chart_raw, "total"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            border-radius: 16px;
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.1) !important;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }
        .admin-badge {
            font-size: 0.68rem;
            padding: 3px 8px;
            border-radius: 6px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .table-action-btn {
            padding: 4px 10px;
            font-size: 0.78rem;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <!-- Navbar Admin -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary bg-gradient shadow-sm py-3">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="admin_dashboard.php">
                <i class="bi bi-shield-fill-check me-2"></i> DompetKu <span class="badge bg-warning text-dark ms-2" style="font-size:0.65rem;">ADMIN</span>
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white-50 d-none d-md-inline" style="font-size:0.88rem;">
                    <i class="bi bi-person-fill me-1"></i><?= escape($admin_username) ?>
                </span>
                <a class="btn btn-outline-light btn-sm d-flex align-items-center gap-1" href="https://t.me/Bagas_Dompetku_bot" target="_blank">
                    <i class="bi bi-telegram"></i> Bot Telegram
                </a>
                <a class="btn btn-outline-light btn-sm" href="dashboard.php">
                    <i class="bi bi-grid me-1"></i>Dashboard User
                </a>
                <a class="btn btn-light btn-sm text-danger" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-5">

        <!-- Judul halaman -->
        <div class="d-flex align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-0"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h2>
                <p class="mb-0" style="font-size:0.9rem;">Pantau dan kelola seluruh aktivitas aplikasi DompetKu.</p>
            </div>
        </div>

        <?php if ($flash_admin): ?>
        <div class="alert alert-<?= $flash_admin["type"] ?> alert-dismissible fade show d-flex align-items-center mb-4" role="alert">
            <i class="bi <?= $flash_admin["type"] === "success" ? "bi-check-circle-fill" : "bi-exclamation-triangle-fill" ?> me-2"></i>
            <div><?= escape($flash_admin["msg"]) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Kartu Statistik -->
        <div class="row g-4 mb-5">
            <!-- Total User -->
            <div class="col-12 col-md-4">
                <div class="card stat-card p-4 shadow-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-3"><?= number_format($stats["total_users"]) ?></div>
                            <div class="text-secondary" style="font-size:0.85rem;">Total Pengguna Terdaftar</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-success"><i class="bi bi-arrow-up-circle me-1"></i><?= $stats["user_bulan_ini"] ?> pengguna baru bulan ini</small>
                    </div>
                </div>
            </div>
            <!-- Total Transaksi -->
            <div class="col-12 col-md-4">
                <div class="card stat-card p-4 shadow-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-receipt-cutoff"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-3"><?= number_format($stats["total_transaksi"]) ?></div>
                            <div class="text-secondary" style="font-size:0.85rem;">Total Transaksi Sistem</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted"><i class="bi bi-activity me-1"></i>Jumlah frekuensi pemakaian fitur transaksi</small>
                    </div>
                </div>
            </div>
            <!-- Total Kategori -->
            <div class="col-12 col-md-4">
                <div class="card stat-card p-4 shadow-sm h-100">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                            <i class="bi bi-tags-fill"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-3"><?= number_format($stats["total_kategori"]) ?></div>
                            <div class="text-secondary" style="font-size:0.85rem;">Total Kategori Kustom</div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted"><i class="bi bi-tag-fill me-1"></i>Kategori buatan user di seluruh sistem</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafik Aktivitas Transaksi 7 Hari Terakhir -->
        <div class="row g-4 mb-5">
            <div class="col-12">
                <div class="card p-4 shadow-sm">
                    <h5 class="fw-bold mb-4"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Aktivitas Transaksi (7 Hari Terakhir)</h5>
                    <div style="height: 220px; position: relative;">
                        <canvas id="activityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Manajemen Pengguna -->
        <div class="card shadow-sm">
            <div class="card-header d-flex align-items-center justify-content-between py-3 px-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Manajemen Pengguna</h5>
                <span class="badge bg-primary rounded-pill"><?= count($users_list) ?> pengguna</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead>
                            <tr>
                                <th class="ps-4">#</th>
                                <th>Pengguna</th>
                                <th>Email</th>
                                <th class="text-center">Peran</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">SSO</th>
                                <th class="text-end">Jumlah Aktivitas Transaksi</th>
                                <th class="text-center">Terdaftar</th>
                                <th class="text-center pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_list as $i => $u): ?>
                            <?php $is_self = ($u["id"] == $_SESSION["user_id"]); ?>
                            <tr>
                                <td class="ps-4 text-secondary"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                             style="width:34px;height:34px;font-size:0.85rem;">
                                            <?= strtoupper(substr($u["username"], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold"><?= escape($u["username"]) ?></div>
                                            <?php if ($is_self): ?>
                                            <small class="text-primary" style="font-size:0.72rem;">(Anda)</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-secondary"><?= escape($u["email"]) ?></td>
                                <td class="text-center">
                                    <?php if ($u["role"] === "admin"): ?>
                                        <span class="badge bg-warning text-dark admin-badge"><i class="bi bi-shield-fill me-1"></i>Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary admin-badge"><i class="bi bi-person me-1"></i>User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php $aktif = $u["is_active"] ?? 1; ?>
                                    <span class="badge <?= $aktif ? "bg-success" : "bg-danger" ?>">
                                        <?= $aktif ? "Aktif" : "Nonaktif" ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if (!empty($u["google_id"])): ?>
                                        <i class="bi bi-google text-danger" title="Akun Google SSO"></i>
                                    <?php else: ?>
                                        <i class="bi bi-envelope-fill text-muted" title="Akun Email/Password"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-5"><?= number_format($u["jumlah_transaksi"]) ?> kali</td>
                                <td class="text-center" style="font-size:0.78rem;"><?= date("d M Y", strtotime($u["created_at"])) ?></td>
                                <td class="text-center pe-4">
                                    <?php if ($is_self): ?>
                                        <span class="text-muted" style="font-size:0.75rem;">—</span>
                                    <?php else: ?>
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <!-- Tombol Aktifkan / Nonaktifkan -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action_admin" value="toggle_active">
                                            <input type="hidden" name="target_user_id" value="<?= $u["id"] ?>">
                                            <button type="submit" class="btn btn-sm table-action-btn <?= ($u["is_active"] ?? 1) ? "btn-outline-warning" : "btn-outline-success" ?>"
                                                    title="<?= ($u["is_active"] ?? 1) ? "Nonaktifkan" : "Aktifkan" ?> akun"
                                                    onclick="return confirm('<?= ($u["is_active"] ?? 1) ? "Nonaktifkan" : "Aktifkan" ?> akun pengguna ini?')">
                                                <i class="bi <?= ($u["is_active"] ?? 1) ? "bi-pause-circle" : "bi-play-circle" ?>"></i>
                                            </button>
                                        </form>
                                        <!-- Tombol Ubah Peran -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action_admin" value="<?= $u["role"] === "admin" ? "set_user" : "set_admin" ?>">
                                            <input type="hidden" name="target_user_id" value="<?= $u["id"] ?>">
                                            <button type="submit" class="btn btn-sm table-action-btn btn-outline-info"
                                                    title="<?= $u["role"] === "admin" ? "Jadikan User biasa" : "Jadikan Admin" ?>"
                                                    onclick="return confirm('Ubah peran pengguna ini?')">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </button>
                                        </form>

                                        <!-- Tombol Hapus -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action_admin" value="delete">
                                            <input type="hidden" name="target_user_id" value="<?= $u["id"] ?>">
                                            <button type="submit" class="btn btn-sm table-action-btn btn-outline-danger"
                                                    title="Hapus permanen"
                                                    onclick="return confirm('PERINGATAN: Hapus permanen akun ini beserta seluruh datanya? Tindakan ini tidak dapat dibatalkan!')">
                                                <i class="bi bi-trash3-fill"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById("activityChart").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: <?= $chart_labels ?>,
                    datasets: [{
                        label: "Jumlah Transaksi",
                        data: <?= $chart_data ?>,
                        backgroundColor: "rgba(29, 78, 216, 0.7)",
                        borderColor: "#1d4ed8",
                        borderWidth: 1,
                        borderRadius: 8,
                        maxBarThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        });
    </script>
</body>
</html>
