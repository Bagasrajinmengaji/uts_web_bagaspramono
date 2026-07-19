<?php
// profile.php
// Halaman Pengaturan Profil Pengguna

require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan user sudah login
auth_check();

$user_id = $_SESSION["user_id"];

// Ambil info detail user dari database
$stmtUser = $pdo->prepare("SELECT email, username, foto_profile, telegram_chat_id, telegram_link_code FROM users WHERE id = :id LIMIT 1");
$stmtUser->execute(["id" => $user_id]);
$user = $stmtUser->fetch();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$email = $user["email"];
$username = $user["username"];
$foto_profile = $user["foto_profile"];
$telegram_chat_id = $user["telegram_chat_id"];
$telegram_link_code = $user["telegram_link_code"];

// Handle POST request untuk update profil
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_profile") {
    $new_username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
    
    // Validasi username
    if (empty($new_username)) {
        set_flash_message("danger", "Username tidak boleh kosong.");
        header("Location: profile.php");
        exit();
    }
    
    if (strlen($new_username) < 3 || strlen($new_username) > 50) {
        set_flash_message("danger", "Username harus berukuran antara 3 sampai 50 karakter.");
        header("Location: profile.php");
        exit();
    }

    try {
        $pdo->beginTransaction();
        
        $foto_updated_name = $foto_profile;

        // Proses Unggah Foto Profil (jika ada file diunggah)
        if (isset($_FILES["foto_profile"]) && $_FILES["foto_profile"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES["foto_profile"];
            
            // Cek error unggahan
            if ($file["error"] !== UPLOAD_ERR_OK) {
                throw new Exception("Gagal mengunggah berkas. Kode Error: " . $file["error"]);
            }
            
            // Cek ukuran berkas (Max 2MB)
            if ($file["size"] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran foto terlalu besar. Maksimum batas ukuran adalah 2 MB.");
            }
            
            // Validasi tipe berkas (MIME Type)
            $allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
            $file_info = getimagesize($file["tmp_name"]);
            if ($file_info === false || !in_array($file_info["mime"], $allowed_types)) {
                throw new Exception("Format berkas tidak didukung. Harap unggah berkas gambar JPG, JPEG, PNG, atau GIF.");
            }
            
            // Tentukan ekstensi & nama berkas unik
            $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
            $new_filename = "profile_" . $user_id . "_" . time() . "." . $ext;
            
            // Buat direktori tujuan jika belum ada
            $upload_dir = __DIR__ . "/uploads/profile/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Pindahkan berkas
            if (move_uploaded_file($file["tmp_name"], $upload_dir . $new_filename)) {
                // Hapus foto lama jika ada dan bukan bawaan/default
                if (!empty($foto_profile) && file_exists($upload_dir . $foto_profile)) {
                    @unlink($upload_dir . $foto_profile);
                }
                $foto_updated_name = $new_filename;
            } else {
                throw new Exception("Gagal memindahkan berkas foto ke folder penyimpanan.");
            }
        }

        // Update database
        $stmtUpdate = $pdo->prepare("UPDATE users SET username = :username, foto_profile = :foto WHERE id = :id");
        $stmtUpdate->execute([
            "username" => $new_username,
            "foto" => $foto_updated_name,
            "id" => $user_id
        ]);
        
        $pdo->commit();
        
        // Perbarui data di Session agar langsung sinkron
        $_SESSION["username"] = $new_username;
        $_SESSION["foto_profile"] = $foto_updated_name;
        $_SESSION["foto_profile_loaded"] = $user_id; // Memicu update di auth_check()
        
        set_flash_message("success", "Profil Anda berhasil diperbarui!");
        header("Location: profile.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash_message("danger", $e->getMessage());
        header("Location: profile.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .profile-avatar-container {
            position: relative;
            width: 140px;
            height: 140px;
            margin: 0 auto 20px auto;
        }
        .profile-avatar-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border: 4px solid var(--primary-blue);
            border-radius: 50%;
            background-color: #f1f5f9;
        }
        .profile-avatar-placeholder {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            background-color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: #94a3b8;
            border: 4px solid var(--primary-blue);
        }
        .profile-upload-badge {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid #ffffff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
        }
        .profile-upload-badge:hover {
            background-color: #1d4ed8;
            transform: scale(1.1);
        }
        [data-theme="dark"] .profile-avatar-img {
            border-color: #3b82f6;
        }
        [data-theme="dark"] .profile-avatar-placeholder {
            border-color: #3b82f6;
            background-color: #334155;
            color: #64748b;
        }
        [data-theme="dark"] .profile-upload-badge {
            background-color: #3b82f6;
            border-color: #1e293b;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
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
                    <li class="nav-item"><a class="nav-link" href="dompet.php">Dompet</a></li>
                    <li class="nav-item"><a class="nav-link" href="kalender.php">Kalender</a></li>
                    <li class="nav-item"><a class="nav-link active font-bold" href="profile.php">Profil</a></li>
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
                        <?php if (!empty($foto_profile) && file_exists(__DIR__ . "/uploads/profile/" . $foto_profile)): ?>
                            <img src="uploads/profile/<?= escape($foto_profile) ?>" alt="Avatar" class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
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

        <div class="row g-4">
            <!-- Kolom Kiri: Foto Profil -->
            <div class="col-lg-4 col-md-5">
                <div class="card p-4 shadow-sm border-0 text-center">
                    <h5 class="font-bold text-secondary mb-4">Foto Profil</h5>
                    
                    <form id="formFotoProfile" action="profile.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="username" value="<?= escape($username) ?>">
                        
                        <div class="profile-avatar-container">
                            <?php if (!empty($foto_profile) && file_exists(__DIR__ . "/uploads/profile/" . $foto_profile)): ?>
                                <img src="uploads/profile/<?= escape($foto_profile) ?>?v=<?= time() ?>" alt="Foto Profil" class="profile-avatar-img" id="avatarPreview">
                            <?php else: ?>
                                <div class="profile-avatar-placeholder" id="avatarPlaceholder">
                                    <i class="bi bi-person"></i>
                                </div>
                                <img src="" alt="Foto Profil" class="profile-avatar-img d-none" id="avatarPreview">
                            <?php endif; ?>
                            
                            <label for="foto_profile_input" class="profile-upload-badge" title="Ganti Foto">
                                <i class="bi bi-camera-fill"></i>
                            </label>
                            <input type="file" id="foto_profile_input" name="foto_profile" class="d-none" accept="image/jpeg,image/jpg,image/png,image/gif">
                        </div>
                        
                        <p class="text-xs text-muted mb-3" style="font-size: 0.8rem;">
                            Ekstensi didukung: <strong>JPG, JPEG, PNG, GIF</strong><br>
                            Ukuran berkas maksimal: <strong>2 MB</strong>
                        </p>
                        
                        <button type="submit" class="btn btn-primary btn-sm px-4 d-none" id="btnSaveFoto">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Unggah Foto
                        </button>
                    </form>
                </div>
            </div>

            <!-- Kolom Kanan: Detail Akun & Pengaturan -->
            <div class="col-lg-8 col-md-7">
                <div class="card p-4 shadow-sm border-0 mb-4">
                    <h5 class="font-bold text-secondary mb-4"><i class="bi bi-person-gear text-primary me-2"></i> Pengaturan Profil</h5>
                    
                    <form action="profile.php" method="POST" id="formProfileInfo">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label text-xs font-bold text-secondary">Alamat Email (SSO Akun)</label>
                            <input type="email" class="form-control bg-light" value="<?= escape($email) ?>" disabled style="cursor: not-allowed;">
                            <small class="text-muted" style="font-size: 0.78rem;">Email tidak dapat diubah karena merupakan identitas masuk utama Anda.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="username_input" class="form-label text-xs font-bold text-secondary">Nama Pengguna (Username)</label>
                            <input type="text" class="form-control" name="username" id="username_input" value="<?= escape($username) ?>" required minlength="3" maxlength="50">
                        </div>
                        
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle me-1"></i> Simpan Perubahan</button>
                    </form>
                </div>

                <!-- Bagian Integrasi Bot Telegram -->
                <div class="card p-4 shadow-sm border-0">
                    <h5 class="font-bold text-secondary mb-3"><i class="bi bi-telegram text-info me-2"></i> Integrasi Bot Telegram</h5>
                    
                    <?php if (empty($telegram_chat_id)): ?>
                        <div class="p-3 border rounded bg-light mb-3">
                            <p class="mb-2 text-dark font-bold" style="font-size: 0.95rem;">Status: 🔴 Belum Terhubung</p>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                Menghubungkan akun Telegram memungkinkan Anda mencatat transaksi keuangan secara instan lewat chat bot **@Bagas_Dompetku_bot**.
                            </p>
                        </div>
                        <a href="https://t.me/Bagas_Dompetku_bot?start=<?= $telegram_link_code ?>" target="_blank" class="btn btn-outline-primary px-4">
                            <i class="bi bi-link me-1"></i> Mulai Menghubungkan
                        </a>
                    <?php else: ?>
                        <div class="p-3 border rounded bg-light mb-3" style="background-color: rgba(56, 189, 248, 0.05) !important;">
                            <p class="mb-2 text-dark font-bold" style="font-size: 0.95rem;">Status: 🟢 Terhubung Aktif</p>
                            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                Telegram Anda terhubung dengan Chat ID: <code><?= escape($telegram_chat_id) ?></code>.<br>
                                Anda bisa mencatat transaksi langsung di Telegram dengan format: <code>out [nominal] [keterangan]</code>.
                            </p>
                        </div>
                        <a href="dashboard.php?action=unlink_telegram" class="btn btn-outline-danger px-4">
                            <i class="bi bi-x-circle me-1"></i> Putuskan Hubungan Telegram
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Live Preview untuk unggahan foto profil
            $('#foto_profile_input').on('change', function() {
                const file = this.files[0];
                if (file) {
                    // Validasi ukuran berkas di client (2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ukuran Berkas Terlalu Besar',
                            text: 'Ukuran foto maksimal adalah 2 MB. Harap pilih berkas lain.'
                        });
                        this.value = ''; // Reset input
                        return;
                    }

                    // Tampilkan preview gambar
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#avatarPlaceholder').addClass('d-none');
                        $('#avatarPreview').removeClass('d-none').attr('src', e.target.result);
                        $('#btnSaveFoto').removeClass('d-none'); // Tampilkan tombol unggah jika foto terpilih
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Konfirmasi sebelum menyimpan nama baru
            $('#formProfileInfo').on('submit', function(e) {
                const usernameVal = $('#username_input').val().trim();
                if (usernameVal === '') {
                    e.preventDefault();
                    Swal.fire('Error', 'Username tidak boleh kosong', 'error');
                }
            });
        });
    </script>
</body>
</html>
