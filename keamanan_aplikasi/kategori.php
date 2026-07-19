<?php
// kategori.php
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
        if ($action === "create") {
            $nama_kategori = isset($_POST["nama_kategori"])
                ? trim($_POST["nama_kategori"])
                : "";
            $tipe = isset($_POST["tipe"]) ? trim($_POST["tipe"]) : "";

            if (empty($nama_kategori) || empty($tipe)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Semua field wajib diisi.",
                ]);
                exit();
            }
            if (!in_array($tipe, ["Pemasukan", "Pengeluaran"], true)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Tipe kategori tidak valid.",
                ]);
                exit();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO kategori (id_user, nama_kategori, tipe) VALUES (:id_user, :nama_kategori, :tipe)",
            );
            $stmt->execute([
                "id_user" => $user_id,
                "nama_kategori" => $nama_kategori,
                "tipe" => $tipe,
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Kategori berhasil ditambahkan!",
            ]);
            exit();
        }

        if ($action === "update") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
            $nama_kategori = isset($_POST["nama_kategori"])
                ? trim($_POST["nama_kategori"])
                : "";
            $tipe = isset($_POST["tipe"]) ? trim($_POST["tipe"]) : "";

            if ($id <= 0 || empty($nama_kategori) || empty($tipe)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "Semua field wajib diisi.",
                ]);
                exit();
            }

            $stmt = $pdo->prepare(
                "UPDATE kategori SET nama_kategori = :nama_kategori, tipe = :tipe WHERE id_kategori = :id AND id_user = :id_user",
            );
            $stmt->execute([
                "nama_kategori" => $nama_kategori,
                "tipe" => $tipe,
                "id" => $id,
                "id_user" => $user_id,
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Kategori berhasil diperbarui!",
            ]);
            exit();
        }

        if ($action === "delete") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

            $stmt = $pdo->prepare(
                "DELETE FROM kategori WHERE id_kategori = :id AND id_user = :id_user",
            );
            $stmt->execute(["id" => $id, "id_user" => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    "status" => "success",
                    "message" => "Kategori berhasil dihapus!",
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

// Ambil semua kategori milik user
try {
    $stmt = $pdo->prepare(
        "SELECT * FROM kategori WHERE id_user = :id_user ORDER BY tipe ASC, nama_kategori ASC",
    );
    $stmt->execute(["id_user" => $user_id]);
    $categories = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Gagal mengambil data kategori.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kategori - DompetKu</title>
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
                    <li class="nav-item"><a class="nav-link active font-bold" href="kategori.php">Kategori</a></li>
                    <li class="nav-item"><a class="nav-link" href="budgeting.php">Anggaran</a></li>
                    <li class="nav-item"><a class="nav-link" href="target_tabungan.php">Target Tabungan</a></li>
                    <li class="nav-item"><a class="nav-link" href="dompet.php">Dompet</a></li>
                    <li class="nav-item"><a class="nav-link" href="kalender.php">Kalender</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.php">Profil</a></li>
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
                    <li class="nav-item text-white me-3 d-flex align-items-center gap-2">
                        <?php if (!empty($_SESSION["foto_profile"]) && file_exists(__DIR__ . "/uploads/profile/" . $_SESSION["foto_profile"])): ?>
                            <img src="uploads/profile/<?= escape($_SESSION["foto_profile"]) ?>" alt="Avatar" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person-circle fs-5"></i>
                        <?php endif; ?>
                        <span>Halo, <strong><?= escape($username) ?></strong></span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm me-2 d-flex align-items-center gap-1" href="https://t.me/Bagas_Dompetku_bot" target="_blank">
                            <i class="bi bi-telegram"></i> Bot Telegram
                        </a>
                    </li>
                    <li class="nav-item"><a class="btn btn-light btn-sm text-primary" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <?php display_flash_message(); ?>

        <div class="card p-4 shadow-sm border-0">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h4 class="font-bold mb-1">Kelola Kategori Kustom</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Buat kategori tersendiri untuk pemasukan dan pengeluaran Anda.</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="resetModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="80">No</th>
                            <th>Nama Kategori</th>
                            <th>Tipe Kategori</th>
                            <th class="text-center" width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada kategori kustom. Silakan klik "Tambah Kategori".</td></tr>
                        <?php else: ?>
                            <?php
                            $no = 1;
                            foreach ($categories as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="font-bold"><?= escape(
                                        $row["nama_kategori"],
                                    ) ?></td>
                                    <td>
                                        <span class="<?= $row["tipe"] ===
                                        "Pemasukan"
                                            ? "badge bg-success"
                                            : "badge bg-danger" ?>">
                                            <?= escape($row["tipe"]) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit" 
                                                    data-id="<?= $row[
                                                        "id_kategori"
                                                    ] ?>" 
                                                    data-nama="<?= escape(
                                                        $row["nama_kategori"],
                                                    ) ?>" 
                                                    data-tipe="<?= $row[
                                                        "tipe"
                                                    ] ?>" 
                                                    title="Edit Kategori">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="<?= $row[
                                                "id_kategori"
                                            ] ?>" title="Hapus Kategori">
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

    <!-- Modal Kategori -->
    <div class="modal fade" id="modalKategori" tabindex="-1" aria-labelledby="modalKategoriLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="modalKategoriLabel">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formKategori">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="kategoriId" value="">

                        <div class="mb-3">
                            <label for="nama_kategori" class="form-label font-bold">Nama Kategori</label>
                            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required maxlength="100" placeholder="Contoh: Makanan, Transportasi, Gaji">
                        </div>
                        <div class="mb-3">
                            <label for="tipe" class="form-label font-bold">Tipe Transaksi</label>
                            <select class="form-select" id="tipe" name="tipe" required>
                                <option value="">-- Pilih Tipe --</option>
                                <option value="Pemasukan">Pemasukan (Uang Masuk)</option>
                                <option value="Pengeluaran">Pengeluaran (Uang Keluar)</option>
                            </select>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // PROSES SIMPAN DATA (CREATE & UPDATE VIA AJAX)
            $('#formKategori').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'kategori.php',
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
                                location.reload();
                            });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: response.message });
                        }
                    }
                });
            });

            // TOMBOL EDIT DIKLIK
            $('.btn-edit').on('click', function() {
                const id = $(this).data('id');
                const nama = $(this).data('nama');
                const tipe = $(this).data('tipe');

                $('#modalKategoriLabel').text('Edit Kategori');
                $('#formAction').val('update');
                $('#kategoriId').val(id);
                $('#nama_kategori').val(nama);
                $('#tipe').val(tipe);

                $('#modalKategori').modal('show');
            });

            // TOMBOL HAPUS DIKLIK
            $('.btn-delete').on('click', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Menghapus kategori ini juga akan membuat transaksi terkait tidak berkategori. Data transaksi tidak akan hilang.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'kategori.php',
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

        function resetModal() {
            $('#modalKategoriLabel').text('Tambah Kategori');
            $('#formAction').val('create');
            $('#kategoriId').val('');
            $('#formKategori')[0].reset();
        }
    </script>
</body>
</html>
