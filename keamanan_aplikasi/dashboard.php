<?php
// Include configuration and security helper files
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Ensure user is logged in
auth_check();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    // 1. Calculate Total Pemasukan
    $stmt_income = $pdo->prepare("SELECT SUM(nominal) as total FROM transaksi WHERE user_id = :user_id AND jenis = 'Pemasukan'");
    $stmt_income->execute(['user_id' => $user_id]);
    $total_pemasukan = $stmt_income->fetch()['total'] ?? 0;

    // 2. Calculate Total Pengeluaran
    $stmt_expense = $pdo->prepare("SELECT SUM(nominal) as total FROM transaksi WHERE user_id = :user_id AND jenis = 'Pengeluaran'");
    $stmt_expense->execute(['user_id' => $user_id]);
    $total_pengeluaran = $stmt_expense->fetch()['total'] ?? 0;

    // 3. Calculate Current Balance
    $saldo_sekarang = $total_pemasukan - $total_pengeluaran;

    // 4. Fetch Transactions with Search & Type Filter (applying SQL Injection mitigation)
    $query = "SELECT * FROM transaksi WHERE user_id = :user_id";
    $params = ['user_id' => $user_id];

    // Filter by type
    $jenis_filter = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
    if ($jenis_filter === 'Pemasukan' || $jenis_filter === 'Pengeluaran') {
        $query .= " AND jenis = :jenis";
        $params['jenis'] = $jenis_filter;
    }

    // Filter by keyword search (keterangan)
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if ($search !== '') {
        $query .= " AND keterangan LIKE :search";
        $params['search'] = '%' . $search . '%';
    }

    $query .= " ORDER BY tanggal DESC, id DESC";
    $stmt_transactions = $pdo->prepare($query);
    $stmt_transactions->execute($params);
    $transactions = $stmt_transactions->fetchAll();

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
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary bg-gradient shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <i class="bi bi-wallet2 me-2"></i> DompetKu
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center mt-3 mt-lg-0">
                    <li class="nav-item me-lg-3 mb-2 mb-lg-0 text-white opacity-75">
                        <i class="bi bi-person-circle me-1"></i> Halo, <strong><?= escape($username); ?></strong>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light btn-sm text-primary px-3 shadow-sm font-bold" href="logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="container my-5">
        
        <!-- Display Flash Messages (e.g., successful additions or updates) -->
        <?php display_flash_message(); ?>

        <!-- Welcome Banner -->
        <div class="mb-4">
            <h2 class="font-bold">Ringkasan Keuangan Anda</h2>
            <p class="text-muted-custom">Pantau dan kelola pemasukan serta pengeluaran harian Anda di sini.</p>
        </div>

        <!-- 3 Financial Summary Cards (Income, Expense, Balance) -->
        <div class="row g-4 mb-5">
            <!-- Pemasukan Card -->
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted-custom font-bold text-xs text-uppercase tracking-wider">Total Pemasukan</span>
                            <h3 class="font-bold text-success mt-2 mb-0"><?= format_rupiah($total_pemasukan); ?></h3>
                        </div>
                        <div class="finance-card-icon icon-pemasukan">
                            <i class="bi bi-arrow-down-left-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pengeluaran Card -->
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted-custom font-bold text-xs text-uppercase tracking-wider">Total Pengeluaran</span>
                            <h3 class="font-bold text-danger mt-2 mb-0"><?= format_rupiah($total_pengeluaran); ?></h3>
                        </div>
                        <div class="finance-card-icon icon-pengeluaran">
                            <i class="bi bi-arrow-up-right-circle-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saldo Card -->
            <div class="col-md-4">
                <div class="card h-100 p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted-custom font-bold text-xs text-uppercase tracking-wider">Saldo Saat Ini</span>
                            <h3 class="font-bold mt-2 mb-0 <?= $saldo_sekarang >= 0 ? 'text-primary' : 'text-danger'; ?>">
                                <?= format_rupiah($saldo_sekarang); ?>
                            </h3>
                        </div>
                        <div class="finance-card-icon icon-saldo">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction Section Header -->
        <div class="card p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <h4 class="font-bold mb-0">Daftar Transaksi</h4>
                <a href="tambah_transaksi.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Transaksi
                </a>
            </div>

            <!-- Search and Filter Form -->
            <form action="dashboard.php" method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" name="search" placeholder="Cari keterangan..." value="<?= escape($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select class="form-select" name="jenis">
                        <option value="">-- Semua Jenis Transaksi --</option>
                        <option value="Pemasukan" <?= $jenis_filter === 'Pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                        <option value="Pengeluaran" <?= $jenis_filter === 'Pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-funnel-fill me-1"></i> Filter
                    </button>
                    <?php if ($search !== '' || $jenis_filter !== ''): ?>
                        <a href="dashboard.php" class="btn btn-light border" title="Reset Filter">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Responsive Transaction Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 5%;">No</th>
                            <th scope="col" style="width: 15%;">Tanggal</th>
                            <th scope="col" style="width: 15%;">Jenis</th>
                            <th scope="col" style="width: 35%;">Keterangan</th>
                            <th scope="col" style="width: 15%;">Nominal</th>
                            <th scope="col" style="width: 15%;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-clipboard-x fs-2 d-block mb-2 text-muted-custom"></i>
                                    Tidak ada data transaksi ditemukan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1; 
                            foreach ($transactions as $row): 
                                $jenis_badge = $row['jenis'] === 'Pemasukan' ? 'badge-pemasukan' : 'badge-pengeluaran';
                                $nominal_class = $row['jenis'] === 'Pemasukan' ? 'text-success' : 'text-danger';
                                $prefix_sign = $row['jenis'] === 'Pemasukan' ? '+' : '-';
                            ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td>
                                        <!-- Safe date output -->
                                        <?= date('d M Y', strtotime($row['tanggal'])); ?>
                                    </td>
                                    <td>
                                        <span class="<?= $jenis_badge; ?>">
                                            <i class="bi <?= $row['jenis'] === 'Pemasukan' ? 'bi-arrow-down-left' : 'bi-arrow-up-right'; ?> me-1"></i>
                                            <?= escape($row['jenis']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- XSS Protection: sanitizing transaction details before rendering -->
                                        <?= escape($row['keterangan']); ?>
                                    </td>
                                    <td>
                                        <span class="font-bold <?= $nominal_class; ?>">
                                            <?= $prefix_sign . ' ' . format_rupiah($row['nominal']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <!-- Edit action (GET) -->
                                            <a href="edit_transaksi.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit Transaksi">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <!-- Delete action (POST method for security/preventing CSRF) -->
                                            <form action="hapus_transaksi.php" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data transaksi ini?')">
                                                <input type="hidden" name="id" value="<?= $row['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus Transaksi">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sticky Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y'); ?> <strong>DompetKu</strong></p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
