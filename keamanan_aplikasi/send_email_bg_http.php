<?php
/**
 * Script ini dipanggil secara asinkron via HTTP loopback socket
 * untuk mengirim email tanpa membuat loading di browser user.
 */

// Jangan batasi waktu eksekusi jika pengiriman email agak lambat
set_time_limit(60);

// Abaikan jika koneksi klien terputus (karena pemanggil menutup socket segera)
ignore_user_abort(true);

require_once __DIR__ . "/config/koneksi.php";
require_once __DIR__ . "/config/helper.php";

// Ambil parameter dari POST
$email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
$username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$method = isset($_POST["method"]) ? trim($_POST["method"]) : "Kredensial Standard";
$type = isset($_POST["type"]) ? trim($_POST["type"]) : "login";
$link = isset($_POST["link"]) ? trim($_POST["link"]) : "";
$otp = isset($_POST["otp"]) ? trim($_POST["otp"]) : "";
$token = isset($_POST["token"]) ? $_POST["token"] : "";

// Keamanan: Validasi token rahasia
$expected_token = md5(($_ENV["DB_PASS"] ?? "dompetku_secret") . "dompetku_async");
if ($token !== $expected_token) {
    error_log("Background HTTP Email Error: Token tidak valid.");
    exit(403);
}

if (empty($email) || empty($username)) {
    error_log("Background HTTP Email Error: Parameter kurang.");
    exit(400);
}

if ($type === "register") {
    send_register_welcome_email($username, $email);
} else if ($type === "register_otp") {
    send_otp_email($username, $email, $otp);
} else if ($type === "forgot_password") {
    send_forgot_password_email($username, $email, $link);
} else if ($type === "admin_register") {
    notify_admin_register($username, $email);
} else {
    send_login_welcome_email($username, $email, $method);
}
