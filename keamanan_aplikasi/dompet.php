<?php
// dompet.php
// Halaman Kelola Dompet Kustom

require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// Backend CRUD via POST
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    header("Content-Type: application/json");
    $action = $_POST["action"];

    try {
        // Aksi 1: Tambah Dompet (Create)
        if ($action === "create") {
            $nama_dompet = isset($_POST["nama_dompet"]) ? trim($_POST["nama_dompet"]) : "";

            if (empty($nama_dompet)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Nama dompet wajib diisi.",
                ]);
                exit();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO dompet (id_user, nama_dompet) VALUES (:id_user, :nama_dompet)",
            );
            $stmt->execute([
                "id_user" => $user_id,
                "nama_dompet" => $nama_dompet,
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Dompet berhasil ditambahkan!",
            ]);
            exit();
        }

        // Aksi 2: Edit Dompet (Update)
        if ($action === "update") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
            $nama_dompet = isset($_POST["nama_dompet"]) ? trim($_POST["nama_dompet"]) : "";

            if ($id <= 0 || empty($nama_dompet)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Nama dompet wajib diisi.",
                ]);
                exit();
            }

            $stmt = $pdo->prepare(
                "UPDATE dompet SET nama_dompet = :nama_dompet WHERE id_dompet = :id AND id_user = :id_user",
            );
            $stmt->execute([
                "nama_dompet" => $nama_dompet,
                "id" => $id,
                "id_user" => $user_id,
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Nama dompet berhasil diperbarui!",
            ]);
            exit();
        }

        // Aksi 3: Hapus Dompet (Delete)
        if ($action === "delete") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

            // Aturan Keamanan UX: Cek jumlah dompet saat ini
            $stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM dompet WHERE id_user = :id_user");
            $stmtCount->execute(['id_user' => $user_id]);
            $totalDompet = $stmtCount->fetch()['total'];

            if ($totalDompet <= 1) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Gagal! Anda wajib menyisakan minimal satu dompet aktif.",
                ]);
                exit();
            }

            // Lakukan penghapusan dompet. Transaksi yang terikat akan diset NULL via constraint ON DELETE SET NULL
            $stmt = $pdo->prepare(
                "DELETE FROM dompet WHERE id_dompet = :id AND id_user = :id_user",
            );
            $stmt->execute(["id" => $id, "id_user" => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Dompet berhasil dihapus! Transaksi lama yang terkait kini tidak berasosiasi dengan dompet manapun.",
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Data dompet tidak ditemukan atau Anda tidak memiliki akses.",
                ]);
            }
            exit();
        }
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        echo json_encode([
            "status" => "error",
            "message" => "Terjadi kesalahan server internal.",
        ]);
        exit();
    }
}

// Ambil semua dompet milik user beserta kalkulasi saldo aktifnya
try {
    $stmt = $pdo->prepare(
        "SELECT d.id_dompet, d.nama_dompet, d.created_at,
               (COALESCE((SELECT SUM(t.nominal) FROM transaksi t WHERE t.id_dompet = d.id_dompet AND t.jenis = 'Pemasukan'), 0) -
                COALESCE((SELECT SUM(t.nominal) FROM transaksi t WHERE t.id_dompet = d.id_dompet AND t.jenis = 'Pengeluaran'), 0)) AS saldo
         FROM dompet d
         WHERE d.id_user = :id_user
         ORDER BY d.nama_dompet ASC"
    );
    $stmt->execute(["id_user" => $user_id]);
    $wallets = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Gagal memuat data dompet.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dompet - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="budgeting.php">Anggaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="target_tabungan.php">Target Tabungan</a></li>
                    <li class="nav-item"><a class="nav-link active font-bold" href="dompet.php">Dompet</a></li>
                </ul>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item text-white me-3">
                        <i class="bi bi-person-circle me-1"></i> Halo, <strong><?= escape($username) ?></strong>
                    </li>
                    <li class="nav-item"><a class="btn btn-light btn-sm text-primary" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php display_flash_message(); ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4"><?= $error_msg ?></div>
        <?php endif; ?>

        <div class="card p-4 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="font-bold mb-1">Kelola Dompet Keuangan</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Kelompokkan dana Anda ke berbagai rekening atau e-wallet terpisah.</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalDompet" onclick="resetModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Dompet
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="80">No</th>
                            <th>Nama Dompet</th>
                            <th>Saldo Aktif</th>
                            <th>Dibuat Pada</th>
                            <th class="text-center" width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($wallets)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada dompet terdaftar. Silakan klik "Tambah Dompet".</td></tr>
                        <?php else: ?>
                            <?php
                            $no = 1;
                            foreach ($wallets as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="font-bold">
                                        <i class="bi bi-wallet text-primary me-2"></i><?= escape($row["nama_dompet"]) ?>
                                    </td>
                                    <td>
                                        <span class="font-bold <?= $row["saldo"] >= 0 ? "text-success" : "text-danger" ?>">
                                            <?= format_rupiah($row["saldo"]) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size: 0.9rem;">
                                        <?= date("d M Y H:i", strtotime($row["created_at"])) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" 
                                                    data-id="<?= $row["id_dompet"] ?>" 
                                                    data-nama="<?= escape($row["nama_dompet"]) ?>">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" 
                                                    data-id="<?= $row["id_dompet"] ?>">
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

    <!-- Modal Form (Tambah/Edit Dompet) -->
    <div class="modal fade" id="modalDompet" tabindex="-1" aria-labelledby="modalDompetLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title font-bold" id="modalDompetLabel">Tambah Dompet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formDompet">
                    <div class="modal-body py-4">
                        <!-- Input hidden untuk identifikasi aksi dan ID jika update -->
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="dompetId" value="">

                        <div class="mb-3">
                            <label for="nama_dompet" class="form-label font-bold text-secondary">Nama Dompet / Rekening</label>
                            <input type="text" class="form-control" name="nama_dompet" id="nama_dompet" required placeholder="Contoh: Dompet Utama, Bank BCA, GoPay">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary px-4">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Script JS Jquery, Bootstrap, SweetAlert2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // --- SUBMIT FORM (CREATE & UPDATE VIA AJAX) ---
            $('#formDompet').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: 'dompet.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#modalDompet').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Gagal memproses permintaan ke server.'
                        });
                    }
                });
            });

            // --- TOMBOL EDIT DIKLIK ---
            $('.btn-edit').on('click', function() {
                const id = $(this).data('id');
                const nama = $(this).data('nama');

                $('#modalDompetLabel').text('Edit Dompet');
                $('#formAction').val('update');
                $('#dompetId').val(id);
                $('#nama_dompet').val(nama);

                $('#modalDompet').modal('show');
            });

            // --- TOMBOL HAPUS DIKLIK ---
            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Menghapus dompet ini tidak menghapus transaksi, tapi catatan transaksi terkait akan diset 'Tanpa Dompet'.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'dompet.php',
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
                            },
                            error: function() {
                                Swal.fire('Error!', 'Gagal menghubungi server.', 'error');
                            }
                        });
                    }
                });
            });
        });

        // --- RESET MODAL ---
        function resetModal() {
            $('#modalDompetLabel').text('Tambah Dompet');
            $('#formAction').val('create');
            $('#dompetId').val('');
            $('#formDompet')[0].reset();
        }
    </script>
</body>
</html>
