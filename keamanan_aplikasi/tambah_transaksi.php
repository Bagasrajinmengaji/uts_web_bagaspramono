<?php
// Include configuration and security helper files
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Ensure user is logged in
auth_check();

$user_id = $_SESSION['user_id'];
$errors = [];
$jenis = '';
$nominal = '';
$keterangan = '';
$tanggal = date('Y-m-d'); // Default to today's date

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim input data
    $jenis = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
    $nominal = isset($_POST['nominal']) ? trim($_POST['nominal']) : '';
    $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';
    $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';

    // 1. Validation: Fields not empty
    if (empty($jenis) || empty($nominal) || empty($keterangan) || empty($tanggal)) {
        $errors[] = "Semua field wajib diisi.";
    }

    // Validasi jenis transaksi
if (!empty($jenis) && !in_amount_type($jenis)) {
    $errors[] = "Jenis transaksi tidak valid.";
}

    // Helper function checking logic inline or direct match
    function in_amount_type($val) {
        return in_array($val, ['Pemasukan', 'Pengeluaran'], true);
    }

    // 3. Validation: Positive numeric check for nominal
    if (!empty($nominal)) {
        if (!is_numeric($nominal) || floatval($nominal) <= 0) {
            $errors[] = "Nominal transaksi harus berupa angka positif.";
        }
    }

    // 4. Validation: Date format check (YYYY-MM-DD pattern validation)
    if (!empty($tanggal) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $errors[] = "Format tanggal tidak valid. Harus YYYY-MM-DD.";
    }

    // 5. Validation: Length limit for description
    if (!empty($keterangan) && strlen($keterangan) > 255) {
        $errors[] = "Keterangan terlalu panjang (maksimal 255 karakter).";
    }

    // If validation succeeds, perform insertion via Prepared Statements
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO transaksi (user_id, jenis, nominal, keterangan, tanggal) VALUES (:user_id, :jenis, :nominal, :keterangan, :tanggal)");
            $stmt->execute([
                'user_id'    => $user_id,
                'jenis'      => $jenis,
                'nominal'    => floatval($nominal),
                'keterangan' => $keterangan,
                'tanggal'    => $tanggal
            ]);

            set_flash_message('success', 'Transaksi berhasil ditambahkan.');
            header("Location: dashboard.php");
            exit;
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Gagal menyimpan transaksi. Terjadi kesalahan internal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Transaksi - DompetKu</title>
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
            <div class="ms-auto">
                <a href="dashboard.php" class="btn btn-light btn-sm text-primary px-3 shadow-sm font-bold">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container my-5" style="max-width: 600px;">
        <div class="card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-2" style="width: 50px; height: 50px;">
                    <i class="bi bi-plus-circle-fill fs-4"></i>
                </div>
                <h3 class="font-bold">Tambah Transaksi</h3>
                <p class="text-muted-custom">Masukkan rincian pemasukan atau pengeluaran baru</p>
            </div>

            <!-- Error Alerts (if any) -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= escape($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="tambah_transaksi.php" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label for="jenis" class="form-label">Jenis Transaksi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-tags"></i></span>
                        <select class="form-select" id="jenis" name="jenis" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="Pemasukan" <?= $jenis === 'Pemasukan' ? 'selected' : ''; ?>>Pemasukan (Uang Masuk)</option>
                            <option value="Pengeluaran" <?= $jenis === 'Pengeluaran' ? 'selected' : ''; ?>>Pengeluaran (Uang Keluar)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nominal" class="form-label">Nominal (Rupiah)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted">Rp</span>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="nominal" name="nominal" placeholder="Contoh: 50000" value="<?= escape($nominal); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="tanggal" class="form-label">Tanggal Transaksi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-calendar-date"></i></span>
                        <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= escape($tanggal); ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="keterangan" class="form-label">Keterangan / Deskripsi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-chat-left-text"></i></span>
                        <input type="text" class="form-control" id="keterangan" name="keterangan" placeholder="Contoh: Gaji bulanan / Beli makan malam" value="<?= escape($keterangan); ?>" required maxlength="255">
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-6">
                        <a href="dashboard.php" class="btn btn-light border w-100">Batal</a>
                    </div>
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary w-100">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Sticky Footer -->
    <footer class="text-center">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y'); ?> <strong>DompetKu</strong>. Dibuat dengan &hearts; untuk Tugas Keamanan Aplikasi Web.</p>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
