<?php
// Include configuration and security helper files
require_once "config/koneksi.php";
require_once "config/helper.php";

// Redirect logged-in users to the dashboard
guest_check();

$key = isset($_GET["key"]) ? trim($_GET["key"]) : (isset($_POST["key"]) ? trim($_POST["key"]) : "");
$email = "";
$token = "";

if (!empty($key)) {
    $decoded = base64_decode($key);
    if ($decoded !== false && strpos($decoded, ":") !== false) {
        list($email, $token) = explode(":", $decoded, 2);
    }
}

if (empty($email) || empty($token)) {
    set_flash_message("danger", "Parameter pemulihan tidak lengkap atau tidak valid. Silakan minta tautan baru.");
    header("Location: forgot_password.php");
    exit();
}

$token_hash = hash("sha256", $token);


$errors = [];
$is_token_valid = false;
$user_id = null;

try {
    // Check if there is a matching user with active token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND reset_token_hash = :hash");
    $stmt->execute([
        "email" => $email,
        "hash" => $token_hash
    ]);
    $user = $stmt->fetch();

    if ($user) {
        $is_token_valid = true;
        $user_id = $user["id"];
    }
} catch (\PDOException $e) {
    error_log($e->getMessage());
    $errors[] = "Terjadi kesalahan sistem saat memproses token.";
}

// If token is invalid or expired, block user immediately
if (!$is_token_valid) {
    set_flash_message("danger", "Tautan pemulihan kata sandi tidak valid atau telah kedaluwarsa. Silakan minta tautan baru.");
    header("Location: forgot_password.php");
    exit();
}

// Process Password Reset Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = isset($_POST["password"]) ? $_POST["password"] : "";
    $confirm_password = isset($_POST["confirm_password"]) ? $_POST["confirm_password"] : "";

    // 1. Validation: Empty input check
    if (empty($password) || empty($confirm_password)) {
        $errors[] = "Semua field wajib diisi.";
    }

    // 2. Validation: Strength requirements matching register.php
    if (!empty($password)) {
        if (strlen($password) < 8) {
            $errors[] = "Password minimal harus 8 karakter.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password harus mengandung minimal satu huruf besar (A-Z).";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password harus mengandung minimal satu huruf kecil (a-z).";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password harus mengandung minimal satu angka (0-9).";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password harus mengandung minimal satu karakter spesial (contoh: @, #, $, %, dll.).";
        }
    }

    // 3. Validation: Match check
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak sesuai.";
    }

    if (empty($errors)) {
        try {
            // Hash password securely using bcrypt
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Update user password and clear token columns (mitigating reuse)
            $update = $pdo->prepare(
                "UPDATE users SET password = :password, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE id = :id"
            );
            $update->execute([
                "password" => $new_password_hash,
                "id" => $user_id
            ]);

            set_flash_message("success", "Kata sandi Anda berhasil diperbarui! Silakan masuk dengan kata sandi baru.");
            header("Location: login.php");
            exit();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Gagal memperbarui kata sandi. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atur Ulang Password - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-2" style="width: 60px; height: 60px;">
                    <i class="bi bi-key-fill fs-3"></i>
                </div>
                <h3 class="auth-title">Atur Ulang Password</h3>
                <p class="text-muted-custom">Masukkan kata sandi baru yang kuat untuk akun Anda</p>
            </div>

            <?php display_flash_message(); ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <ul class="mb-0 ps-3" style="font-size: 0.9rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form action="reset_password.php" method="POST" autocomplete="off">
                <!-- Keep credentials state safe across POST submissions -->
                <input type="hidden" name="key" value="<?= escape($key) ?>">

                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Masukkan password baru" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-check"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="confirm_password" name="confirm_password" placeholder="Konfirmasi password baru" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Perbarui Kata Sandi</button>
                
                <div class="text-center mt-2">
                    <a href="login.php" class="text-primary font-bold text-decoration-none">Batal dan kembali ke Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
