<?php
// setup_dompet.php
// Skrip satu-kali-jalan untuk migrasi database fitur Multi Dompet

require_once "config/koneksi.php";
require_once "config/helper.php";

// Pastikan skrip hanya bisa diakses oleh admin/user yang sudah login demi keamanan.
// Izinkan eksekusi tanpa auth jika dijalankan melalui CLI (CommandLine Interface).
if (php_sapi_name() !== 'cli') {
    auth_check();
}

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Setup Fitur Multi Dompet - DompetKu</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light py-5'>
<div class='container'>
    <div class='card shadow-sm max-width-600 mx-auto'>
        <div class='card-header bg-primary text-white py-3'>
            <h4 class='mb-0'>Migrasi Database: Fitur Multi Dompet</h4>
        </div>
        <div class='card-body p-4'>";

try {
    echo "<p class='text-secondary'>1. Membuat tabel <code>dompet</code>...</p>";
    $createTableSQL = "CREATE TABLE IF NOT EXISTS `dompet` (
        `id_dompet` INT AUTO_INCREMENT PRIMARY KEY,
        `id_user` INT NOT NULL,
        `nama_dompet` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($createTableSQL);
    echo "<div class='alert alert-success py-2'>Tabel <code>dompet</code> berhasil dibuat/sudah ada.</div>";

    echo "<p class='text-secondary'>2. Memeriksa kolom <code>id_dompet</code> di tabel <code>transaksi</code>...</p>";
    $checkColumn = $pdo->query("SHOW COLUMNS FROM `transaksi` LIKE 'id_dompet'")->fetch();
    
    if (!$checkColumn) {
        echo "<p class='text-secondary'>Menambahkan kolom <code>id_dompet</code> ke tabel <code>transaksi</code>...</p>";
        // Tambahkan kolom id_dompet
        $pdo->exec("ALTER TABLE `transaksi` ADD COLUMN `id_dompet` INT NULL AFTER `id_kategori`");
        // Tambahkan foreign key constraint
        $pdo->exec("ALTER TABLE `transaksi` ADD CONSTRAINT `fk_transaksi_dompet` 
                    FOREIGN KEY (`id_dompet`) REFERENCES `dompet` (`id_dompet`) ON DELETE SET NULL");
        echo "<div class='alert alert-success py-2'>Kolom <code>id_dompet</code> dan constraint berhasil ditambahkan.</div>";
    } else {
        echo "<div class='alert alert-info py-2'>Kolom <code>id_dompet</code> sudah ada di tabel <code>transaksi</code>.</div>";
    }

    echo "<p class='text-secondary'>2b. Memeriksa kolom <code>is_transfer</code> di tabel <code>transaksi</code>...</p>";
    $checkTransferColumn = $pdo->query("SHOW COLUMNS FROM `transaksi` LIKE 'is_transfer'")->fetch();
    if (!$checkTransferColumn) {
        echo "<p class='text-secondary'>Menambahkan kolom <code>is_transfer</code> ke tabel <code>transaksi</code>...</p>";
        $pdo->exec("ALTER TABLE `transaksi` ADD COLUMN `is_transfer` TINYINT(1) DEFAULT 0 AFTER `tanggal`");
        echo "<div class='alert alert-success py-2'>Kolom <code>is_transfer</code> berhasil ditambahkan.</div>";
    } else {
        echo "<div class='alert alert-info py-2'>Kolom <code>is_transfer</code> sudah ada di tabel <code>transaksi</code>.</div>";
    }



    echo "<p class='text-secondary'>3. Memproses migrasi akun user lama & transaksi...</p>";
    
    // Start transaction for DML operations
    $pdo->beginTransaction();

    // Ambil semua user yang terdaftar
    $users = $pdo->query("SELECT id, username FROM users")->fetchAll();
    
    $userMigratedCount = 0;
    $transaksiMigratedCount = 0;

    foreach ($users as $user) {
        $uId = $user['id'];
        $uName = $user['username'];

        // Cek apakah user sudah punya dompet
        $stmtCheckDompet = $pdo->prepare("SELECT id_dompet FROM dompet WHERE id_user = :id_user LIMIT 1");
        $stmtCheckDompet->execute(['id_user' => $uId]);
        $hasDompet = $stmtCheckDompet->fetch();

        $idDompetDefault = null;

        if (!$hasDompet) {
            // Jika user belum punya dompet sama sekali, buatkan "Dompet Utama"
            $stmtInsertDompet = $pdo->prepare("INSERT INTO dompet (id_user, nama_dompet) VALUES (:id_user, :nama_dompet)");
            $stmtInsertDompet->execute([
                'id_user' => $uId,
                'nama_dompet' => 'Dompet Utama'
            ]);
            $idDompetDefault = $pdo->lastInsertId();
            $userMigratedCount++;
        } else {
            $idDompetDefault = $hasDompet['id_dompet'];
        }

        // Cari transaksi user ini yang kolom id_dompet-nya masih NULL
        $stmtUpdateTx = $pdo->prepare("UPDATE transaksi SET id_dompet = :id_dompet WHERE user_id = :user_id AND id_dompet IS NULL");
        $stmtUpdateTx->execute([
            'id_dompet' => $idDompetDefault,
            'user_id' => $uId
        ]);
        $transaksiMigratedCount += $stmtUpdateTx->rowCount();
    }

    $pdo->commit();

    echo "<div class='alert alert-success py-3'>";
    echo "<h5>Migrasi Selesai dengan Sukses!</h5>";
    echo "<ul>";
    echo "<li>User baru yang dibuatkan dompet default: <strong>$userMigratedCount</strong> user</li>";
    echo "<li>Transaksi lama yang dihubungkan ke Dompet Utama: <strong>$transaksiMigratedCount</strong> transaksi</li>";
    echo "</ul>";
    echo "</div>";
    echo "<a href='dashboard.php' class='btn btn-primary w-100'>Ke Dashboard DompetKu</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "<div class='alert alert-danger py-3'>";
    echo "<h5>Terjadi Kesalahan Saat Migrasi!</h5>";
    echo "<p class='mb-0'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
    echo "<a href='dashboard.php' class='btn btn-secondary w-100'>Kembali ke Dashboard</a>";
}

echo "        </div>
    </div>
</div>
</body>
</html>";
