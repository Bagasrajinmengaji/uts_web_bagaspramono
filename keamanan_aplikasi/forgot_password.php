<?php
// Include configuration and security helper files
require_once "config/koneksi.php";
require_once "config/helper.php";

// Redirect logged-in users to the dashboard
guest_check();

$email = "";
$errors = [];
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";

    // 1. Validation: Check empty inputs
    if (empty($email)) {
        $errors[] = "Alamat email wajib diisi.";
    }

    // 2. Validation: Email format validation
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format alamat email tidak valid.";
    }

    if (empty($errors)) {
        try {
            // Check if user exists with this email
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :email");
            $stmt->execute(["email" => $email]);
            $user = $stmt->fetch();

            if ($user) {
                // 3. Security: Generate cryptographically secure random token
                $token = bin2hex(random_bytes(32));
                // Store only SHA-256 hash of token to prevent token theft from database leak
                $token_hash = hash("sha256", $token);
                // Expiry time set to 1 hour from now
                $expires_at = date("Y-m-d H:i:s", time() + 3600);

                // Update user with token hash and expiry
                $update = $pdo->prepare(
                    "UPDATE users SET reset_token_hash = :hash, reset_token_expires_at = :expires WHERE id = :id"
                );
                $update->execute([
                    "hash" => $token_hash,
                    "expires" => $expires_at,
                    "id" => $user["id"]
                ]);

                // Construct secure reset password link
                $protocol = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on") ? "https://" : "http://";
                // dirname() handles subdirectory automatically
                $current_dir = rtrim(dirname($_SERVER["PHP_SELF"]), '/\\');
                $key = rtrim(base64_encode($email . ":" . $token), '=');
                $link = $protocol . $_SERVER["HTTP_HOST"] . $current_dir . "/reset_password.php?key=" . urlencode($key);

                // Run email sending (asynchronous if supported, synchronous fallback)
                send_email_async([
                    "email" => $email,
                    "username" => $user["username"],
                    "link" => $link,
                    "type" => "forgot_password"
                ]);
            }

            // Mitigation of User Enumeration: Output same success message regardless of email existence
            $success_message = "Instruksi pemulihan kata sandi telah dikirim ke email Anda (silakan periksa folder spam jika tidak ditemukan).";
            $email = ""; // Clear input on success
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Terjadi kesalahan sistem. Silakan coba lagi nanti.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-2" style="width: 60px; height: 60px;">
                    <i class="bi bi-shield-lock fs-3"></i>
                </div>
                <h3 class="auth-title">Lupa Password</h3>
                <p class="text-muted-custom">Masukkan email terdaftar untuk menerima link reset kata sandi</p>
            </div>

            <?php display_flash_message(); ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div style="font-size: 0.9rem;"><?= htmlspecialchars($success_message) ?></div>
                </div>
            <?php endif; ?>

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

            <form action="forgot_password.php" method="POST" autocomplete="off">
                <div class="mb-4">
                    <label for="email" class="form-label">Alamat Email</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control border-start-0 ps-0" id="email" name="email" placeholder="contoh@email.com" value="<?= escape($email) ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Kirim Link Reset</button>
                
                <div class="text-center mt-2">
                    <a href="login.php" class="text-primary font-bold text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Kembali ke Login</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
