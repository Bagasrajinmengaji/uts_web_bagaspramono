<?php
/**
 * Script ini berjalan di latar belakang (background CLI) 
 * untuk mengirim email tanpa membuat loading di browser user.
 */
ini_set('display_errors', 0);

// Parse parameter dari CLI
$options = getopt("", ["email:", "username:", "method:", "type:", "link:", "otp:"]);
$email = isset($options["email"]) ? trim($options["email"]) : "";
$username = isset($options["username"]) ? trim($options["username"]) : "";
$method = isset($options["method"]) ? trim($options["method"]) : "Kredensial Standard";
$type = isset($options["type"]) ? trim($options["type"]) : "login";
$link = isset($options["link"]) ? trim($options["link"]) : "";
$otp = isset($options["otp"]) ? trim($options["otp"]) : "";

if (empty($email) || empty($username)) {
    error_log("Background Email Error: Parameter kurang.");
    exit(1);
}

// Load koneksi & helper
require_once __DIR__ . "/config/koneksi.php";
require_once __DIR__ . "/config/helper.php";

if ($type === "register") {
    send_register_welcome_email($username, $email);
} else if ($type === "register_otp") {
    send_otp_email($username, $email, $otp);
} else if ($type === "forgot_password") {
    send_forgot_password_email($username, $email, $link);
} else {
    send_login_welcome_email($username, $email, $method);
}
