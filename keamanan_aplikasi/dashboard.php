<?php
// manggil file koneksi dan helper
require_once 'config/koneksi.php';
require_once 'config/helper.php';

//pastikan user dah log in
auth_check();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// backend crud pake post
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    try {
        // Aksi 1: Tambah Transaksi (Create)
        if ($action === 'create') {
            $jenis = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
            $nominal = isset($_POST['nominal']) ? trim($_POST['nominal']) : '';
            $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';
            $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';

            if (empty($jenis) || empty($nominal) || empty($keterangan) || empty($tanggal)) {
                echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
                exit;
            }
            if (!in_array($jenis, ['Pemasukan', 'Pengeluaran'], true)) {
                echo json_encode(['status' => 'error', 'message' => 'Jenis transaksi tidak valid.']);
                exit;
            }
            if (!is_numeric($nominal) || floatval($nominal) <= 0) {
                echo json_encode(['status' => 'error', 'message' => 'Nominal harus angka positif.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO transaksi (user_id, jenis, nominal, keterangan, tanggal) VALUES (:user_id, :jenis, :nominal, :keterangan, :tanggal)");
            $stmt->execute([
                'user_id'    => $user_id,
                'jenis'      => $jenis,
                'nominal'    => floatval($nominal),
                'keterangan' => $keterangan,
                'tanggal'    => $tanggal
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil ditambahkan!']);
            exit;
        }

        // Aksi 2: Edit Transaksi (Update)
        if ($action === 'update') {
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';
            $jenis = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
            $nominal = isset($_POST['nominal']) ? trim($_POST['nominal']) : '';
            $keterangan = isset($_POST['keterangan']) ? trim($_POST['keterangan']) : '';
            $tanggal = isset($_POST['tanggal']) ? trim($_POST['tanggal']) : '';

            if (empty($id) || empty($jenis) || empty($nominal) || empty($keterangan) || empty($tanggal)) {
                echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE transaksi SET jenis = :jenis, nominal = :nominal, keterangan = :keterangan, tanggal = :tanggal WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                'jenis'      => $jenis,
                'nominal'    => floatval($nominal),
                'keterangan' => $keterangan,
                'tanggal'    => $tanggal,
                'id'         => $id,
                'user_id'    => $user_id
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil diperbarui!']);
            exit;
        }

        // Aksi 3: Hapus Transaksi (Delete)
        if ($action === 'delete') {
            $id = isset($_POST['id']) ? trim($_POST['id']) : '';
            
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = :id AND user_id = :user_id");
            $stmt->execute(['id' => $id, 'user_id' => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['status' => 'success', 'message' => 'Transaksi berhasil dihapus!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Data tidak ditemukan atau tidak ada akses.']);
            }
            exit;
        }

    } catch (\PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan server backend.']);
        exit;
    }
}

// --- LOGIK UTAMA: MENAMPILKAN DATA (Read) ---
try {
    $stmt_income = $pdo->prepare("SELECT SUM(nominal) as total FROM transaksi WHERE user_id = :user_id AND jenis = 'Pemasukan'");
    $stmt_income->execute(['user_id' => $user_id]);
    $total_pemasukan = $stmt_income->fetch()['total'] ?? 0;

    $stmt_expense = $pdo->prepare("SELECT SUM(nominal) as total FROM transaksi WHERE user_id = :user_id AND jenis = 'Pengeluaran'");
    $stmt_expense->execute(['user_id' => $user_id]);
    $total_pengeluaran = $stmt_expense->fetch()['total'] ?? 0;

    $saldo_sekarang = $total_pemasukan - $total_pengeluaran;

    $query = "SELECT * FROM transaksi WHERE user_id = :user_id";
    $params = ['user_id' => $user_id];

    $jenis_filter = isset($_GET['jenis']) ? trim($_GET['jenis']) : '';
    if ($jenis_filter === 'Pemasukan' || $jenis_filter === 'Pengeluaran') {
        $query .= " AND jenis = :jenis";
        $params['jenis'] = $jenis_filter;
    }

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
            <div class="collapse navbar-collapse" id="navbarNav">
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
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4">
                    <span class="text-muted text-uppercase text-xs font-bold">Total Pemasukan</span>
                    <h3 class="text-success font-bold mt-2"><?= format_rupiah($total_pemasukan); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <span class="text-muted text-uppercase text-xs font-bold">Total Pengeluaran</span>
                    <h3 class="text-danger font-bold mt-2"><?= format_rupiah($total_pengeluaran); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4">
                    <span class="text-muted text-uppercase text-xs font-bold">Saldo Saat Ini</span>
                    <h3 class="font-bold mt-2 <?= $saldo_sekarang >= 0 ? 'text-primary' : 'text-danger'; ?>"><?= format_rupiah($saldo_sekarang); ?></h3>
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
                                <a class="dropdown-item py-2" href="export_excel.php?<?= http_build_query($_GET); ?>">
                                    <i class="bi bi-file-earmark-excel text-success me-2"></i> Ekspor ke Excel (.xls)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="export_docx.php?<?= http_build_query($_GET); ?>">
                                    <i class="bi bi-file-earmark-word text-primary me-2"></i> Ekspor ke Word (.doc)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="export_pdf.php?<?= http_build_query($_GET); ?>">
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

            <form action="dashboard.php" method="GET" class="row g-3 mb-4">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" placeholder="Cari keterangan..." value="<?= escape($search); ?>">
                </div>
                <div class="col-md-4">
                    <select class="form-select select2-init" name="jenis">
                        <option value="">-- Semua Jenis Transaksi --</option>
                        <option value="Pemasukan" <?= $jenis_filter === 'Pemasukan' ? 'selected' : ''; ?>>Pemasukan</option>
                        <option value="Pengeluaran" <?= $jenis_filter === 'Pengeluaran' ? 'selected' : ''; ?>>Pengeluaran</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-funnel-fill"></i> Filter</button>
                    <?php if ($search !== '' || $jenis_filter !== ''): ?>
                        <a href="dashboard.php" class="btn btn-light border"><i class="bi bi-arrow-counterclockwise"></i></a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Keterangan</th>
                            <th>Nominal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data transaksi.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($transactions as $row): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= date('d M Y', strtotime($row['tanggal'])); ?></td>
                                    <td><span class="<?= $row['jenis'] === 'Pemasukan' ? 'badge bg-success' : 'badge bg-danger'; ?>"><?= escape($row['jenis']); ?></span></td>
                                    <td><?= escape($row['keterangan']); ?></td>
                                    <td class="font-bold <?= $row['jenis'] === 'Pemasukan' ? 'text-success' : 'text-danger'; ?>">
                                        <?= ($row['jenis'] === 'Pemasukan' ? '+ ' : '- ') . format_rupiah($row['nominal']); ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-success dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Ekspor Kuitansi">
                                                    <i class="bi bi-download"></i>
                                                </button>
                                                <ul class="dropdown-menu shadow">
                                                    <li>
                                                        <a class="dropdown-item py-1" href="export_excel.php?id=<?= $row['id']; ?>">
                                                            <i class="bi bi-file-earmark-excel text-success me-2"></i> Kuitansi Excel
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1" href="export_docx.php?id=<?= $row['id']; ?>">
                                                            <i class="bi bi-file-earmark-word text-primary me-2"></i> Kuitansi Word
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item py-1" href="export_pdf.php?id=<?= $row['id']; ?>">
                                                            <i class="bi bi-file-earmark-pdf text-danger me-2"></i> Kuitansi PDF
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" 
                                                    data-id="<?= $row['id']; ?>" 
                                                    data-jenis="<?= $row['jenis']; ?>" 
                                                    data-nominal="<?= $row['nominal']; ?>" 
                                                    data-tanggal="<?= $row['tanggal']; ?>" 
                                                    data-keterangan="<?= escape($row['keterangan']); ?>"
                                                    title="Edit Transaksi">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $row['id']; ?>" title="Hapus Transaksi">
                                                <i class="bi bi-trash"></i>
                                            </button>
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
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal (Rupiah)</label>
                            <input type="number" step="0.01" min="0.01" class="form-control" id="nominal" name="nominal" required>
                        </div>
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal Transaksi</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= date('Y-m-d'); ?>" required>
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
                                <li><strong>Excel/CSV</strong>: Simpan sebagai berkas CSV.</li>
                                <li><strong>Word (DOCX)</strong>: Tulis data dalam tabel, simpan sebagai .docx.</li>
                                <li><strong>PDF</strong>: Gunakan format laporan PDF asli yang diunduh dari sistem.</li>
                            </ul>
                        </div>
                        
                        <div class="mb-4">
                            <label for="file_dokumen" class="form-label font-bold">Pilih File Laporan / Template</label>
                            <input type="file" class="form-control" id="file_dokumen" name="file_dokumen" accept=".csv, .txt, .docx, .doc, .pdf" required>
                            <div class="form-text">Mendukung format berkas <code>.csv</code>, <code>.docx</code>, dan <code>.pdf</code>.</div>
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
        $(document).ready(function() {
            // --- INSISIALISASI SELECT2 ---
            $('.select2-init, .modal-select2').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalTransaksi').css('display') === 'none' ? null : $('#modalTransaksi') 
            });

            // Perbaiki bug Select2 di dalam Bootstrap Modal agar bisa diklik/fokus pencariannya
            $('#modalTransaksi').on('shown.bs.modal', function () {
                $('.modal-select2').select2({
                    theme: 'bootstrap-5',
                    dropdownParent: $('#modalTransaksi')
                });
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
                const nominal = $(this).data('nominal');
                const tanggal = $(this).data('tanggal');
                const keterangan = $(this).data('keterangan');

                // Mengubah setelan modal menjadi Mode Edit
                $('#modalTransaksiLabel').text('Edit Transaksi');
                $('#formAction').val('update');
                $('#transaksiId').val(id);
                $('#jenis').val(jenis).trigger('change'); // Update Select2 value
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
            $('#jenis').val('').trigger('change');
        }
    </script>
</body>
</html>