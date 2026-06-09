<?php
// Include configuration and security helper files
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Redirect logged-in users to the dashboard
guest_check();

$errors = [];
$username = '';
$email = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Trim input data to remove redundant spaces
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // 1. Validation: Check empty inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "Semua field wajib diisi.";
    }

    // 2. Validation: Username characters and length (3-50 chars, alphanumeric & underscore)
    if (!empty($username) && !preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = "Username hanya boleh huruf, angka, dan underscore (_), minimal 3 karakter dan maksimal 50 karakter.";
    }

    // 3. Validation: Email format validation
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format alamat email tidak valid.";
    }

    // 4. Validation: Password minimum length (8 characters)
    if (!empty($password) && strlen($password) < 8) {
        $errors[] = "Password minimal harus 8 karakter.";
    }

    // 5. Validation: Password match verification
    if ($password !== $confirm_password) {
        $errors[] = "Konfirmasi password tidak sesuai.";
    }

    // If there are no validation errors, proceed with database check
    if (empty($errors)) {
        try {
            // Check if username or email already exists using PDO Prepared Statements (mitigating SQL Injection)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([
                'username' => $username,
                'email'    => $email
            ]);
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                $errors[] = "Username atau email sudah terdaftar.";
            } else {
                // Securely hash the password using bcrypt via PASSWORD_DEFAULT
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert new user into database
                $insert_stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $insert_stmt->execute([
                    'username' => $username,
                    'email'    => $email,
                    'password' => $hashed_password
                ]);

                // Set success flash message and redirect to login
                set_flash_message('success', 'Registrasi berhasil! Silakan login menggunakan akun Anda.');
                header("Location: login.php");
                exit;
            }
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - DompetKu</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-2" style="width: 60px; height: 60px;">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <h3 class="auth-title">Daftar Akun</h3>
                <p class="text-muted-custom">Buat akun DompetKu untuk mulai mengelola keuangan</p>
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

            <!-- Registration Form -->
            <form action="register.php" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Masukkan username" value="<?= escape($username); ?>" required>
                    </div>
                    <div class="form-text text-xs text-muted-custom">Hanya boleh huruf, angka, dan underscore (3-50 karakter).</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Alamat Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" placeholder="nama@email.com" value="<?= escape($email); ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Minimal 8 karakter" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Daftar Sekarang</button>
            </form>

            <div class="text-center mt-2">
                <p class="mb-0 text-muted-custom">Sudah punya akun? <a href="login.php" class="text-primary font-bold text-decoration-none">Login</a></p>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
