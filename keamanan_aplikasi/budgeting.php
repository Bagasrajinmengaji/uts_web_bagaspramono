<?php
// budgeting.php
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Ambil filter bulan dan tahun, default ke bulan & tahun berjalan
$bulan_filter = isset($_GET['bulan']) ? intval($_GET['bulan']) : intval(date('m'));
$tahun_filter = isset($_GET['tahun']) ? intval($_GET['tahun']) : intval(date('Y'));

// List nama bulan bahasa Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

try {
    // 1. Ambil semua kategori pengeluaran milik user untuk dropdown modal
    $stmt_cat = $pdo->prepare("SELECT * FROM kategori WHERE id_user = :id_user AND tipe = 'Pengeluaran' ORDER BY nama_kategori ASC");
    $stmt_cat->execute(['id_user' => $user_id]);
    $categories = $stmt_cat->fetchAll();

    // 2. Dapatkan analisis anggaran terhitung pengeluaran berjalan
    $budgets = dapatkan_analisis_anggaran($user_id, $bulan_filter, $tahun_filter);

} catch (\PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Gagal mengambil data anggaran.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anggaran Bulanan - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link active font-bold" href="budgeting.php">Anggaran</a></li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item text-white me-3">
                        <i class="bi bi-person-circle me-1"></i> Halo, <strong><?= escape($username); ?></strong>
                    </li>
                    <li class="nav-item"><a class="btn btn-light btn-sm text-primary" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php display_flash_message(); ?>

        <!-- Filter Periode Laporan -->
        <div class="card p-4 mb-4 shadow-sm border-0">
            <form action="budgeting.php" method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label font-bold text-secondary">Pilih Bulan</label>
                    <select class="form-select select2-init" name="bulan">
                        <?php foreach ($nama_bulan as $num => $name): ?>
                            <option value="<?= $num; ?>" <?= $bulan_filter === $num ? 'selected' : ''; ?>><?= $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label font-bold text-secondary">Pilih Tahun</label>
                    <select class="form-select select2-init" name="tahun">
                        <?php for ($y = date('Y') - 2; $y <= date('Y') + 2; $y++): ?>
                            <option value="<?= $y; ?>" <?= $tahun_filter === $y ? 'selected' : ''; ?>><?= $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end pt-3">
                    <button type="submit" class="btn btn-outline-primary w-100 me-2"><i class="bi bi-funnel"></i> Tampilkan Anggaran</button>
                </div>
            </form>
        </div>

        <div class="card p-4 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-bold mb-1">Rencana Anggaran Bulanan</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                        Periode: <strong><?= $nama_bulan[$bulan_filter] . ' ' . $tahun_filter; ?></strong>
                    </p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalBudget" onclick="resetModal()">
                    <i class="bi bi-piggy-bank me-1"></i> Atur Anggaran
                </button>
            </div>

            <?php if (empty($budgets)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-wallet2 fs-1 text-secondary mb-3 d-block"></i>
                    Belum ada rencana anggaran untuk periode ini. Silakan klik "Atur Anggaran".
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($budgets as $b): 
                        $persentase = min($b['persentase'], 100);
                        
                        // Tentukan warna progress bar berdasarkan persentase
                        $progress_color = 'bg-primary';
                        if ($b['persentase'] >= 90) {
                            $progress_color = 'bg-danger';
                        } elseif ($b['persentase'] >= 70) {
                            $progress_color = 'bg-warning';
                        }
                        
                        $sisa = $b['budget'] - $b['pengeluaran'];
                    ?>
                        <div class="col-md-6">
                            <div class="card p-3 border border-light-subtle shadow-sm h-100 position-relative">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="font-bold mb-1 text-dark"><?= escape($b['nama_kategori']); ?></h5>
                                        <span class="text-muted-custom" style="font-size: 0.82rem;">
                                            Terpakai: <strong><?= format_rupiah($b['pengeluaran']); ?></strong> dari <strong><?= format_rupiah($b['budget']); ?></strong>
                                        </span>
                                    </div>
                                    <div class="text-end">
                                        <span class="fs-5 font-bold <?= $b['persentase'] >= 90 ? 'text-danger' : ($b['persentase'] >= 70 ? 'text-warning' : 'text-primary'); ?>">
                                            <?= number_format($b['persentase'], 1); ?>%
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="progress mb-3" style="height: 10px; border-radius: 5px;">
                                    <div class="progress-bar <?= $progress_color; ?>" role="progressbar" style="width: <?= $persentase; ?>%" aria-valuenow="<?= $persentase; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-auto pt-2 border-top border-light-subtle">
                                    <small class="text-secondary">
                                        <?php if ($sisa >= 0): ?>
                                            Sisa: <strong class="text-success"><?= format_rupiah($sisa); ?></strong>
                                        <?php else: ?>
                                            Over Limit: <strong class="text-danger"><?= format_rupiah(abs($sisa)); ?></strong>
                                        <?php endif; ?>
                                    </small>
                                    <button class="btn btn-sm btn-link text-decoration-none py-0 px-1 btn-edit-budget" 
                                            data-kategori="<?= $b['id_kategori']; ?>" 
                                            data-budget="<?= $b['budget']; ?>">
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Atur Anggaran -->
    <div class="modal fade" id="modalBudget" tabindex="-1" aria-labelledby="modalBudgetLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="modalBudgetLabel">Atur Anggaran Bulanan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="proses_budget.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="bulan" value="<?= $bulan_filter; ?>">
                        <input type="hidden" name="tahun" value="<?= $tahun_filter; ?>">

                        <div class="mb-3">
                            <label for="id_kategori" class="form-label font-bold">Kategori Pengeluaran</label>
                            <select class="form-select modal-select2" id="id_kategori" name="id_kategori" required style="width: 100%;">
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_kategori']; ?>"><?= escape($cat['nama_kategori']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Hanya kategori bertipe 'Pengeluaran' yang dapat ditentukan anggarannya.</div>
                        </div>

                        <div class="mb-3">
                            <label for="jumlah_budget" class="form-label font-bold">Nominal Anggaran (Limit Maksimal)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="jumlah_budget" name="jumlah_budget" required placeholder="Contoh: 1500000">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Rencana</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.select2-init, .modal-select2').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalBudget').css('display') === 'none' ? null : $('#modalBudget') 
            });

            $('#modalBudget').on('shown.bs.modal', function () {
                $('.modal-select2').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#modalBudget')
                });
            });

            $('.btn-edit-budget').on('click', function() {
                const idKategori = $(this).data('kategori');
                const budget = $(this).data('budget');

                $('#id_kategori').val(idKategori).trigger('change');
                $('#jumlah_budget').val(budget);
                $('#modalBudget').modal('show');
            });
        });

        function resetModal() {
            $('#id_kategori').val('').trigger('change');
            $('#jumlah_budget').val('');
        }
    </script>
</body>
</html>
