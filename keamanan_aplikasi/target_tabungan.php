<?php
// Manggil file koneksi dan helper
require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION["user_id"];
$username = $_SESSION["username"];

// --- BACKEND HANDLERS (POST AJAX) ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    header("Content-Type: application/json");
    $action = $_POST["action"];

    try {
        // Aksi 1: Tambah Target (Create)
        if ($action === "create") {
            $nama_target = isset($_POST["nama_target"]) ? trim($_POST["nama_target"]) : "";
            $nominal_target = isset($_POST["nominal_target"]) ? trim($_POST["nominal_target"]) : "";
            $tenggat_waktu = isset($_POST["tenggat_waktu"]) && $_POST["tenggat_waktu"] !== "" ? trim($_POST["tenggat_waktu"]) : null;

            if (empty($nama_target) || empty($nominal_target)) {
                echo json_encode(["status" => "error", "message" => "Nama target dan nominal target wajib diisi."]);
                exit();
            }
            if (!is_numeric($nominal_target) || floatval($nominal_target) <= 0) {
                echo json_encode(["status" => "error", "message" => "Nominal target harus berupa angka positif."]);
                exit();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO target_tabungan (user_id, nama_target, nominal_target, nominal_terkumpul, tenggat_waktu) 
                 VALUES (:user_id, :nama_target, :nominal_target, 0.00, :tenggat_waktu)"
            );
            $stmt->execute([
                "user_id" => $user_id,
                "nama_target" => $nama_target,
                "nominal_target" => floatval($nominal_target),
                "tenggat_waktu" => $tenggat_waktu
            ]);

            echo json_encode(["status" => "success", "message" => "Target tabungan berhasil ditambahkan!"]);
            exit();
        }

        // Aksi 2: Edit Target (Update)
        if ($action === "update") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
            $nama_target = isset($_POST["nama_target"]) ? trim($_POST["nama_target"]) : "";
            $nominal_target = isset($_POST["nominal_target"]) ? trim($_POST["nominal_target"]) : "";
            $tenggat_waktu = isset($_POST["tenggat_waktu"]) && $_POST["tenggat_waktu"] !== "" ? trim($_POST["tenggat_waktu"]) : null;

            if (empty($id) || empty($nama_target) || empty($nominal_target)) {
                echo json_encode(["status" => "error", "message" => "Semua kolom wajib diisi."]);
                exit();
            }
            if (!is_numeric($nominal_target) || floatval($nominal_target) <= 0) {
                echo json_encode(["status" => "error", "message" => "Nominal target harus berupa angka positif."]);
                exit();
            }

            $stmt = $pdo->prepare(
                "UPDATE target_tabungan 
                 SET nama_target = :nama_target, nominal_target = :nominal_target, tenggat_waktu = :tenggat_waktu 
                 WHERE id = :id AND user_id = :user_id"
            );
            $stmt->execute([
                "nama_target" => $nama_target,
                "nominal_target" => floatval($nominal_target),
                "tenggat_waktu" => $tenggat_waktu,
                "id" => $id,
                "user_id" => $user_id
            ]);

            echo json_encode(["status" => "success", "message" => "Target tabungan berhasil diperbarui!"]);
            exit();
        }

        // Aksi 3: Hapus Target (Delete)
        if ($action === "delete") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;

            $stmt = $pdo->prepare("DELETE FROM target_tabungan WHERE id = :id AND user_id = :user_id");
            $stmt->execute(["id" => $id, "user_id" => $user_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["status" => "success", "message" => "Target tabungan berhasil dihapus."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Data tidak ditemukan atau akses ditolak."]);
            }
            exit();
        }

        // Aksi 4: Tambah/Tarik Tabungan (Adjust Savings)
        if ($action === "adjust") {
            $id = isset($_POST["id"]) ? intval($_POST["id"]) : 0;
            $tipe_adjust = isset($_POST["tipe_adjust"]) ? trim($_POST["tipe_adjust"]) : "";
            $nominal_adjust = isset($_POST["nominal_adjust"]) ? trim($_POST["nominal_adjust"]) : "";

            if (empty($id) || empty($tipe_adjust) || empty($nominal_adjust)) {
                echo json_encode(["status" => "error", "message" => "Semua kolom input wajib diisi."]);
                exit();
            }
            if (!is_numeric($nominal_adjust) || floatval($nominal_adjust) <= 0) {
                echo json_encode(["status" => "error", "message" => "Nominal penyesuaian harus berupa angka positif."]);
                exit();
            }

            // Ambil data target saat ini
            $stmt_get = $pdo->prepare("SELECT nominal_terkumpul, nominal_target FROM target_tabungan WHERE id = :id AND user_id = :user_id");
            $stmt_get->execute(["id" => $id, "user_id" => $user_id]);
            $target = $stmt_get->fetch();

            if (!$target) {
                echo json_encode(["status" => "error", "message" => "Data target tidak ditemukan."]);
                exit();
            }

            $current_terkumpul = floatval($target["nominal_terkumpul"]);
            $nominal_val = floatval($nominal_adjust);

            if ($tipe_adjust === "Tabung") {
                $new_terkumpul = $current_terkumpul + $nominal_val;
            } elseif ($tipe_adjust === "Tarik") {
                if ($nominal_val > $current_terkumpul) {
                    echo json_encode(["status" => "error", "message" => "Saldo tabungan impian tidak mencukupi untuk ditarik sebesar itu."]);
                    exit();
                }
                $new_terkumpul = $current_terkumpul - $nominal_val;
            } else {
                echo json_encode(["status" => "error", "message" => "Aksi penyesuaian tidak valid."]);
                exit();
            }

            $stmt_update = $pdo->prepare("UPDATE target_tabungan SET nominal_terkumpul = :new_terkumpul WHERE id = :id AND user_id = :user_id");
            $stmt_update->execute([
                "new_terkumpul" => $new_terkumpul,
                "id" => $id,
                "user_id" => $user_id
            ]);

            echo json_encode(["status" => "success", "message" => "Progress tabungan berhasil diperbarui!"]);
            exit();
        }
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(["status" => "error", "message" => "Terjadi kesalahan server backend."]);
        exit();
    }
}

// --- LOGIK UTAMA: MENAMPILKAN DATA ---
try {
    // Ambil daftar target tabungan
    $stmt = $pdo->prepare("SELECT * FROM target_tabungan WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute(["user_id" => $user_id]);
    $goals = $stmt->fetchAll();

    // Hitung ringkasan total
    $total_target = 0;
    $total_terkumpul = 0;
    foreach ($goals as $g) {
        $total_target += floatval($g["nominal_target"]);
        $total_terkumpul += floatval($g["nominal_terkumpul"]);
    }
    $total_selisih = max($total_target - $total_terkumpul, 0);
    $progress_persen_total = $total_target > 0 ? ($total_terkumpul / $total_target) * 100 : 0;
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Gagal mengambil data target tabungan.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Tabungan - DompetKu</title>
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
                    <li class="nav-item"><a class="nav-link active font-bold" href="target_tabungan.php">Target Tabungan</a></li>
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
                        <i class="bi bi-person-circle me-1"></i> Halo, <strong><?= escape($username) ?></strong>
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

        <!-- Ringkasan Target Tabungan -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm">
                    <span class="text-muted text-uppercase text-xs font-bold">Total Kebutuhan Tabungan</span>
                    <h3 class="text-primary font-bold mt-2"><?= format_rupiah($total_target) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm">
                    <span class="text-muted text-uppercase text-xs font-bold">Total Terkumpul</span>
                    <h3 class="text-success font-bold mt-2"><?= format_rupiah($total_terkumpul) ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm">
                    <span class="text-muted text-uppercase text-xs font-bold">Kekurangan Akumulasi</span>
                    <h3 class="text-danger font-bold mt-2"><?= format_rupiah($total_selisih) ?></h3>
                </div>
            </div>
        </div>

        <!-- Kemajuan Kumulatif Global -->
        <div class="card p-4 mb-4 border-0 shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="font-bold text-secondary">Progress Akumulasi Kumulatif</span>
                <span class="font-bold text-primary"><?= number_format($progress_persen_total, 1) ?>%</span>
            </div>
            <div class="progress" style="height: 15px; border-radius: 8px;">
                <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?= $progress_persen_total ?>%" aria-valuenow="<?= $progress_persen_total ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>

        <!-- Bagian Pengelolaan Impian Tabungan -->
        <div class="card p-4 border-0 shadow-sm">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h4 class="font-bold mb-1">Daftar Impian & Target Tabungan</h4>
                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Tetapkan target masa depan dan pantau progres tabungan Anda secara berkala.</p>
                </div>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalGoal" onclick="resetModal()">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Target
                </button>
            </div>

            <?php if (empty($goals)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-piggy-bank fs-1 text-secondary mb-3 d-block"></i>
                    Belum ada rencana target tabungan. Silakan buat target pertama Anda!
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($goals as $goal):
                        $persen = floatval($goal["nominal_target"]) > 0 ? ($goal["nominal_terkumpul"] / $goal["nominal_target"]) * 100 : 0;
                        $persen = min($persen, 100);
                        
                        $card_border = $persen >= 100 ? "border-success" : "";
                        $bar_color = $persen >= 100 ? "bg-success" : ($persen >= 60 ? "bg-primary" : "bg-warning");
                    ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 shadow-sm border <?= $card_border ?>" style="border-radius: 12px; overflow: hidden;">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title font-bold text-dark mb-0"><?= escape($goal["nama_target"]) ?></h5>
                                        <?php if ($persen >= 100): ?>
                                            <span class="badge bg-success text-uppercase" style="font-size: 0.7rem;">Goal Tercapai</span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="text-primary font-bold mb-3"><?= format_rupiah($goal["nominal_target"]) ?></h6>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between text-xs text-muted mb-1" style="font-size: 0.8rem;">
                                            <span>Terkumpul: <strong><?= format_rupiah($goal["nominal_terkumpul"]) ?></strong></span>
                                            <span class="font-bold text-dark"><?= number_format($persen, 1) ?>%</span>
                                        </div>
                                        <div class="progress mb-3" style="height: 8px;">
                                            <div class="progress-bar <?= $bar_color ?>" role="progressbar" style="width: <?= $persen ?>%"></div>
                                        </div>

                                        <?php if ($goal["tenggat_waktu"]): ?>
                                            <div class="text-xs text-muted mb-3" style="font-size: 0.78rem;">
                                                <i class="bi bi-calendar-event me-1"></i> Tenggat: <?= date("d M Y", strtotime($goal["tenggat_waktu"])) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between border-top pt-2">
                                            <button type="button" class="btn btn-sm btn-success btn-adjust-savings" data-id="<?= $goal['id'] ?>" data-nama="<?= escape($goal['nama_target']) ?>">
                                                <i class="bi bi-cash-coin me-1"></i> Tabung
                                            </button>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-goal" 
                                                        data-id="<?= $goal['id'] ?>" 
                                                        data-nama="<?= escape($goal['nama_target']) ?>" 
                                                        data-nominal="<?= $goal['nominal_target'] ?>" 
                                                        data-tenggat="<?= $goal['tenggat_waktu'] ?>">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-goal" data-id="<?= $goal['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Target Tabungan (Create & Update) -->
    <div class="modal fade" id="modalGoal" tabindex="-1" aria-labelledby="modalGoalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="modalGoalLabel">Tambah Target Tabungan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formGoal">
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="create">
                        <input type="hidden" name="id" id="goalId" value="">

                        <div class="mb-3">
                            <label for="nama_target" class="form-label font-bold">Nama Impian / Target</label>
                            <input type="text" class="form-control" id="nama_target" name="nama_target" required maxlength="100" placeholder="Contoh: Beli Laptop Baru, Liburan Akhir Tahun">
                        </div>
                        <div class="mb-3">
                            <label for="nominal_target" class="form-label font-bold">Nominal Target (Rp)</label>
                            <input type="number" class="form-control" id="nominal_target" name="nominal_target" required min="1" placeholder="Masukkan jumlah dana yang ditargetkan">
                        </div>
                        <div class="mb-3">
                            <label for="tenggat_waktu" class="form-label font-bold">Tenggat Waktu (Opsional)</label>
                            <input type="date" class="form-control" id="tenggat_waktu" name="tenggat_waktu">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Target</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Penyesuaian Tabungan (Tabung / Tarik) -->
    <div class="modal fade" id="modalAdjust" tabindex="-1" aria-labelledby="modalAdjustLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title font-bold" id="modalAdjustLabel">Sesuaikan Tabungan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formAdjust">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="adjust">
                        <input type="hidden" name="id" id="adjustGoalId" value="">

                        <p class="mb-3 text-muted">Aksi untuk target: <strong class="text-primary" id="adjustGoalName"></strong></p>

                        <div class="mb-3">
                            <label class="form-label font-bold">Jenis Penyesuaian</label>
                            <select class="form-select" name="tipe_adjust" id="tipe_adjust" required>
                                <option value="Tabung">Tambahkan Tabungan (Deposit)</option>
                                <option value="Tarik">Tarik Tabungan (Withdraw)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="nominal_adjust" class="form-label font-bold">Nominal Penyesuaian (Rp)</label>
                            <input type="number" class="form-control" id="nominal_adjust" name="nominal_adjust" required min="1" placeholder="Masukkan jumlah dana">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Proses Dana</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap & jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Script Kontrol Aksi Target Tabungan -->
    <script>
        $(document).ready(function() {
            // KIRIM FORM CREATE / UPDATE TARGET
            $('#formGoal').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'target_tabungan.php',
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

            // KIRIM FORM ADJUSTMENT TABUNGAN
            $('#formAdjust').on('submit', function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'target_tabungan.php',
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

            // TOMBOL TABUNG DIKLIK
            $('.btn-adjust-savings').on('click', function() {
                const id = $(this).data('id');
                const nama = $(this).data('nama');

                $('#adjustGoalId').val(id);
                $('#adjustGoalName').text(nama);
                $('#formAdjust')[0].reset();

                $('#modalAdjust').modal('show');
            });

            // TOMBOL EDIT DIKLIK
            $('.btn-edit-goal').on('click', function() {
                const id = $(this).data('id');
                const nama = $(this).data('nama');
                const nominal = $(this).data('nominal');
                const tenggat = $(this).data('tenggat');

                $('#modalGoalLabel').text('Edit Target Tabungan');
                $('#formAction').val('update');
                $('#goalId').val(id);
                $('#nama_target').val(nama);
                $('#nominal_target').val(nominal);
                $('#tenggat_waktu').val(tenggat);

                $('#modalGoal').modal('show');
            });

            // TOMBOL HAPUS DIKLIK
            $('.btn-delete-goal').on('click', function() {
                const id = $(this).data('id');

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Tindakan ini akan menghapus rencana target tabungan secara permanen.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'target_tabungan.php',
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

        // Reset state modal tambah
        function resetModal() {
            $('#modalGoalLabel').text('Tambah Target Tabungan');
            $('#formAction').val('create');
            $('#goalId').val('');
            $('#formGoal')[0].reset();
        }
    </script>
</body>
</html>
