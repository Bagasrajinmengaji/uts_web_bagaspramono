<?php
// Include configuration and security helper files
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Ensure user is logged in
auth_check();

$user_id = $_SESSION['user_id'];

// --- CRITICAL SECURITY: Destructive Action Protection ---
// Ensure deletion only processes via HTTP POST method to prevent CSRF / crawler triggers
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('danger', 'Metode request tidak valid. Penghapusan data harus melalui form POST.');
    header("Location: dashboard.php");
    exit;
}

// Retrieve and validate ID
$id = isset($_POST['id']) ? trim($_POST['id']) : '';
if (empty($id) || !is_numeric($id)) {
    set_flash_message('danger', 'ID Transaksi tidak valid.');
    header("Location: dashboard.php");
    exit;
}

try {
    // Execute DELETE statement with owner lock (Broken Object Level Authorization prevention)
    // Ensures a user can only delete transactions that belong directly to their account ID
    $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        'id'      => $id,
        'user_id' => $user_id
    ]);

    // Check if the deletion actually affected any rows (verifies transaction existence & ownership)
    if ($stmt->rowCount() > 0) {
        set_flash_message('success', 'Transaksi berhasil dihapus.');
    } else {
        set_flash_message('danger', 'Transaksi gagal dihapus. Data tidak ditemukan atau Anda tidak memiliki hak akses.');
    }

} catch (\PDOException $e) {
    error_log($e->getMessage());
    set_flash_message('danger', 'Gagal menghapus transaksi karena kesalahan server.');
}

// Redirect back to dashboard to display flash status alerts
header("Location: dashboard.php");
exit;
