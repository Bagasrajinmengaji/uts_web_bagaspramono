<?php
/**
 * Test Connection Script
 */

header('Content-Type: application/json');

try {
    require_once 'connection.php';
    
    // Test if the connection is active
    if (isset($pdo)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Koneksi ke database nusagrid_gpu berhasil!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Koneksi gagal diinisialisasi.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kesalahan koneksi: ' . $e->getMessage()
    ]);
}
?>
