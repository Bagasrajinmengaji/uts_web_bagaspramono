<?php
// Include configuration and security helper files
require_once "config/koneksi.php";
require_once "config/helper.php";

// Redirect logged-in users to the dashboard//
guest_check();

// Definisi credential Google SSO
$google_client_id = $_ENV["GOOGLE_CLIENT_ID"];
$google_redirect_url = $_ENV["GOOGLE_REDIRECT_URL"];

// Generate URL Login google (Sudah diperbaiki: 'redirect_uri' & 'access_type')
$google_login_url =
    "https://accounts.google.com/o/oauth2/v2/auth?" .
    http_build_query([
        "client_id" => $google_client_id,
        "redirect_uri" => $google_redirect_url,
        "response_type" => "code",
        "scope" =>
            "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email",
        "access_type" => "offline", // Perbaikan di sini
        "prompt" => "select_account",
    ]);

$identity = "";
$errors = [];

// Enable/disable temporary debugging (set to true for college testing, can be toggled to false later)
$debug = true;
$debug_output = [];

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Trim user input
    $identity = isset($_POST["identity"]) ? trim($_POST["identity"]) : "";
    $password = isset($_POST["password"]) ? $_POST["password"] : "";

    // 1. Validation: Check empty inputs
    if (empty($identity) || empty($password)) {
        $errors[] = "Semua field wajib diisi.";
    }

    if (empty($errors)) {
        try {
            // Retrieve user details using PDO prepared statements to block SQL Injection
            $stmt = $pdo->prepare(
                "SELECT * FROM users WHERE username = :username_identity OR email = :email_identity",
            );
            $stmt->execute([
                "username_identity" => $identity,
                "email_identity" => $identity,
            ]);
            $user = $stmt->fetch();

            if ($debug) {
                if ($user) {
                    $debug_output[] =
                        "DEBUG: User ditemukan di database! (ID: " .
                        $user["id"] .
                        ", Username: " .
                        $user["username"] .
                        ", Email: " .
                        $user["email"] .
                        ")";
                } else {
                    $debug_output[] =
                        "DEBUG: User TIDAK ditemukan di database untuk identitas '" .
                        $identity .
                        "'.";
                }
            }

            // Verify password using password_verify() to process the secure hash (bcrypt)
            if ($user) {
                $password_matches = password_verify(
                    $password,
                    $user["password"],
                );
                if ($debug) {
                    if ($password_matches) {
                        $debug_output[] =
                            "DEBUG: Password cocok dengan hash bcrypt di database.";
                    } else {
                        $debug_output[] =
                            "DEBUG: Password TIDAK cocok dengan hash bcrypt di database.";
                    }
                }

                if ($password_matches) {
                    $user_active = isset($user["is_active"]) ? intval($user["is_active"]) : 1;
                    if ($user_active === 0) {
                        $errors[] = "Akun Anda telah dinonaktifkan oleh administrator. Silakan hubungi dukungan.";
                    } else {
                        // --- CRITICAL SECURITY: Session Fixation Prevention ---
                        session_regenerate_id(true);

                        // Set session variables (termasuk role untuk RBAC)
                        $_SESSION["user_id"] = $user["id"];
                        $_SESSION["username"] = $user["username"];
                        $_SESSION["email"] = $user["email"];
                        $_SESSION["role"] = $user["role"] ?? "user";

                        if ($debug) {
                            $debug_output[] =
                                "DEBUG: Session berhasil disimpan! (\$_SESSION['user_id'] = " .
                                $_SESSION["user_id"] .
                                ", \$_SESSION['username'] = '" .
                                $_SESSION["username"] .
                                "', \$_SESSION['email'] = '" .
                                $_SESSION["email"] .
                                "')";
                            $_SESSION["debug_log"] = $debug_output;
                        }

                        // Panggil pengiriman email (asinkron jika didukung, sinkron sebagai fallback)
                        send_email_async([
                            "email" => $user["email"],
                            "username" => $user["username"],
                            "method" => "Kredensial Standard",
                            "type" => "login"
                        ]);

                        // Arahkan admin ke dashboard admin, user biasa ke dashboard
                        $redirect = (isset($user["role"]) && $user["role"] === "admin")
                            ? "admin_dashboard.php"
                            : "dashboard.php";
                        header("Location: " . $redirect);
                        exit();
                    }
                }
            }

            // If we reach here, authentication failed
            $errors[] = "Username/Email atau Password Anda salah.";
            if ($debug && !empty($debug_output)) {
                $_SESSION["debug_log"] = $debug_output;
            }
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            if ($debug) {
                $errors[] = "Database Error (Debug): " . $e->getMessage();
            } else {
                $errors[] =
                    "Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-2" style="width: 60px; height: 60px;">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
                <h3 class="auth-title">Login DompetKu</h3>
                <p class="text-muted-custom">Masukkan username/email untuk masuk ke dashboard</p>
            </div>

            <?php display_flash_message(); ?>

            <?php if (isset($_SESSION["sso_error"])): ?>
                <div class="alert alert-danger alert-dismissible fade show font-bold" role="alert" style="font-size: 0.9rem;">
                    <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= htmlspecialchars(
                        $_SESSION["sso_error"],
                    ) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION["sso_error"]); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION["debug_log"])): ?>
                <div class="alert alert-info border-2 font-monospace mb-3 debug-panel" style="font-size: 0.82rem; border-color: #3b82f6;">
                    <strong class="text-primary"><i class="bi bi-bug-fill me-1"></i> DEBUG AUTENTIKASI:</strong>
                    <hr class="my-2 text-muted">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($_SESSION["debug_log"] as $log): ?>
                            <li><?= escape($log) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION["debug_log"]); ?>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= escape($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" autocomplete="off">
                <div class="mb-3">
                    <label for="identity" class="form-label">Username atau Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="identity" name="identity" placeholder="Username atau email" value="<?= escape(
                            $identity,
                        ) ?>" required>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label for="password" class="form-label mb-0">Password</label>
                        <a href="forgot_password.php" class="text-primary font-bold text-decoration-none" style="font-size: 0.85rem;">Lupa password?</a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control border-start-0 ps-0" id="password" name="password" placeholder="Masukkan password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                
                <div class="d-flex align-items-center my-3">
                    <hr class="flex-grow-1 text-muted my-0">
                    <span class="mx-3 text-muted-custom" style="font-size: 0.85rem;">atau masuk dengan</span>
                    <hr class="flex-grow-1 text-muted my-0">
                </div>

                <a href="<?= $google_login_url ?>" class="btn btn-light border w-100 mb-3 d-flex align-items-center justify-content-center py-2 shadow-sm style-sso-btn" style="border-radius: 8px; transition: all 0.2s ease;">
                    <img src="https://fonts.gstatic.com/s/i/productlogos/googleg/v6/24px.svg" alt="Google Logo" class="me-2" style="width: 18px; height: 18px;">
                    <span class="font-bold text-dark" style="font-size: 0.95rem;">Google Account</span>
                </a>
            </form>

            <div class="text-center mt-2">
                <p class="mb-0 text-muted-custom">Belum punya akun? <a href="register.php" class="text-primary font-bold text-decoration-none">Daftar</a></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>