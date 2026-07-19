<?php
// Secure Database Connection File (PDO)
// Suitable for default XAMPP configuration & Render.com deployment
//

// Inisialisasi environment variable jika belum di-load
if (!function_exists('load_env')) {
    require_once __DIR__ . "/helper.php";
}

$host = $_ENV["DB_HOST"];
$db   = $_ENV["DB_NAME"];
$user = $_ENV["DB_USER"];
$pass = $_ENV["DB_PASS"];
$charset = "utf8mb4";

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    // Throw exceptions on errors (easier debugging, clean error handling)
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // Fetch result sets as associative arrays
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Disable emulation of prepared statements to ensure MySQL driver does the parameterization
    // This is crucial for robust SQL Injection prevention
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In production, do not expose the raw database error message (information leakage)
    // For a student assignment, we print a user-friendly error but log the detailed message
    error_log($e->getMessage());
    die(
        "Error: Tidak dapat terhubung ke database. Pastikan MySQL di XAMPP sudah aktif."
    );
}
