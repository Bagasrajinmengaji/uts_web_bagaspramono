<?php
// proses_budget.php
require_once 'config/koneksi.php';
require_once 'config/helper.php';

// Pastikan user sudah login
auth_check();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_SESSION['user_id'];
    $id_kategori = isset($_POST['id_kategori']) ? intval($_POST['id_kategori']) : 0;
    $jumlah_budget = isset($_POST['jumlah_budget']) ? floatval($_POST['jumlah_budget']) : 0.0;
    $bulan = isset($_POST['bulan']) ? intval($_POST['bulan']) : intval(date('m'));
    $tahun = isset($_POST['tahun']) ? intval($_POST['tahun']) : intval(date('Y'));

    if ($id_kategori <= 0 || $jumlah_budget <= 0 || $bulan < 1 || $bulan > 12 || $tahun < 2000) {
        set_flash_message('danger', 'Data anggaran tidak valid.');
        header('Location: budgeting.php');
        exit;
    }

    try {
        global $pdo;

        $query = "INSERT INTO anggaran (id_user, id_kategori, jumlah_budget, bulan, tahun) 
                  VALUES (:id_user, :id_kategori, :jumlah_budget, :bulan, :tahun) 
                  ON DUPLICATE KEY UPDATE jumlah_budget = :jumlah_budget_update";
                  
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'id_user'              => $id_user,
            'id_kategori'          => $id_kategori,
            'jumlah_budget'        => $jumlah_budget,
            'bulan'                => $bulan,
            'tahun'                => $tahun,
            'jumlah_budget_update' => $jumlah_budget
        ]);

        set_flash_message('success', 'Anggaran bulanan berhasil disimpan!');
    } catch (\PDOException $e) {
        error_log($e->getMessage());
        set_flash_message('danger', 'Gagal menyimpan anggaran.');
    }

    header('Location: budgeting.php');
    exit;
}
