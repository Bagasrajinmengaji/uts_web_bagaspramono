<?php
// Include configuration and security helper files
require_once "config/koneksi.php";
require_once "config/helper.php";

// Guard: Only guest users can access
guest_check();

// Check if there is temporary registration data in session
if (!isset($_SESSION["temp_register_user"])) {
    set_flash_message("danger", "Sesi pendaftaran Anda telah berakhir atau tidak valid. Silakan daftar kembali.");
    header("Location: register.php");
    exit();
}

$temp_user = $_SESSION["temp_register_user"];
$errors = [];

// Handle OTP Resend request
if (isset($_GET["resend"]) && $_GET["resend"] == "1") {
    // Generate new 6-digit numeric OTP
    $new_otp = sprintf("%06d", rand(100000, 999999));
    
    // Update temporary registration session
    $_SESSION["temp_register_user"]["otp"] = $new_otp;
    $_SESSION["temp_register_user"]["otp_expires"] = time() + 600; // 10 mins

    // Send new OTP in background
    $bg_script = __DIR__ . "/send_email_bg.php";
    $cmd = "start /B C:\\xampp\\php\\php.exe " . escapeshellarg($bg_script) . 
           " --email=" . escapeshellarg($temp_user["email"]) . 
           " --username=" . escapeshellarg($temp_user["username"]) . 
           " --otp=" . escapeshellarg($new_otp) .
           " --type=register_otp";
    pclose(popen($cmd, "r"));

    set_flash_message("success", "Kode OTP baru telah dikirimkan ke email Anda.");
    header("Location: verify_otp.php");
    exit();
}

// Process OTP Verification Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp_input = isset($_POST["otp"]) ? trim($_POST["otp"]) : "";

    // 1. Validation: check empty input
    if (empty($otp_input)) {
        $errors[] = "Kode OTP wajib diisi.";
    }

    if (empty($errors)) {
        // 2. Validation: check OTP matching and expiration
        if ($otp_input !== $temp_user["otp"]) {
            $errors[] = "Kode OTP yang Anda masukkan salah.";
        } else if ($temp_user["otp_expires"] <= time()) {
            $errors[] = "Kode OTP telah kedaluwarsa. Silakan kirim ulang kode baru.";
        } else {
            // OTP is correct! Complete the registration
            try {
                // Check once more to prevent race conditions (duplicate username/email check)
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
                $stmt->execute([
                    "username" => $temp_user["username"],
                    "email" => $temp_user["email"]
                ]);
                if ($stmt->fetch()) {
                    $errors[] = "Username atau email tersebut sudah terdaftar saat verifikasi.";
                } else {
                    // Start transaction for DML operations
                    $pdo->beginTransaction();

                    // Insert user details to users table
                    $insert_stmt = $pdo->prepare(
                        "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)"
                    );
                    $insert_stmt->execute([
                        "username" => $temp_user["username"],
                        "email" => $temp_user["email"],
                        "password" => $temp_user["password"]
                    ]);
                    $new_user_id = $pdo->lastInsertId();

                    // Automatically initialize main wallet (Dompet Utama)
                    $dompet_stmt = $pdo->prepare(
                        "INSERT INTO dompet (id_user, nama_dompet) VALUES (:id_user, :nama_dompet)"
                    );
                    $dompet_stmt->execute([
                        "id_user" => $new_user_id,
                        "nama_dompet" => "Dompet Utama"
                    ]);

                    $pdo->commit();

                    // Clear temporary registration session
                    unset($_SESSION["temp_register_user"]);

                    // Prevent Session Fixation: Regenerate ID and set logged-in state
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = $new_user_id;
                    $_SESSION["username"] = $temp_user["username"];
                    $_SESSION["email"] = $temp_user["email"];

                    // Trigger welcome email to user in background
                    $bg_script = __DIR__ . "/send_email_bg.php";
                    $cmd_welcome = "start /B C:\\xampp\\php\\php.exe " . escapeshellarg($bg_script) . 
                                   " --email=" . escapeshellarg($temp_user["email"]) . 
                                   " --username=" . escapeshellarg($temp_user["username"]) . 
                                   " --type=register";
                    pclose(popen($cmd_welcome, "r"));

                    // Trigger admin register notification in background
                    $cmd_admin = "start /B C:\\xampp\\php\\php.exe -r " . escapeshellarg(
                        "require_once 'config/helper.php'; notify_admin_register(" . 
                        var_export($temp_user["username"], true) . ", " . 
                        var_export($temp_user["email"], true) . ");"
                    );
                    pclose(popen($cmd_admin, "r"));

                    set_flash_message("success", "Email Anda berhasil diverifikasi! Selamat datang di DompetKu.");
                    header("Location: dashboard.php");
                    exit();
                }
            } catch (\PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $errors[] = "Gagal menyelesaikan pendaftaran akibat kesalahan database. Silakan coba lagi.";
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
    <title>Verifikasi OTP - DompetKu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="card auth-card p-4">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle p-3 mb-2" style="width: 60px; height: 60px;">
                    <i class="bi bi-envelope-check-fill fs-3"></i>
                </div>
                <h3 class="auth-title">Verifikasi Email</h3>
                <p class="text-muted-custom">Masukkan 6-digit kode verifikasi OTP yang kami kirimkan ke email <strong><?= htmlspecialchars($temp_user["email"]) ?></strong></p>
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

            <form action="verify_otp.php" method="POST" autocomplete="off">
                <div class="mb-4">
                    <label for="otp" class="form-label">Kode OTP Verifikasi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key-fill"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0 text-center font-bold font-monospace" style="font-size: 1.25rem; letter-spacing: 4px;" id="otp" name="otp" placeholder="123456" maxlength="6" pattern="\d{6}" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">Verifikasi & Buat Akun</button>
                
                <div class="d-flex justify-content-between mt-2" style="font-size: 0.88rem;">
                    <a href="register.php" class="text-secondary text-decoration-none"><i class="bi bi-arrow-left me-1"></i> Daftar Ulang</a>
                    <a href="verify_otp.php?resend=1" class="text-primary font-bold text-decoration-none"><i class="bi bi-arrow-clockwise me-1"></i> Kirim Ulang OTP</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
