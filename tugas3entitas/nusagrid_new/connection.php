<?php
/**
 * Database Connection for nusagrid_gpu
 * Using PDO for secure and modern database operations.
 */

$host = 'localhost';
$db   = 'nusagrid_gpu';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // 1. Automatic Users Table Initialization
     $tableUsersQuery = "SHOW TABLES LIKE 'users'";
     $usersExists = $pdo->query($tableUsersQuery)->rowCount() > 0;
     
     if (!$usersExists) {
         $sql = "CREATE TABLE `users` (
             `id` INT(11) NOT NULL AUTO_INCREMENT,
             `username` VARCHAR(100) NOT NULL,
             `email` VARCHAR(100) NOT NULL,
             `password` VARCHAR(255) NOT NULL,
             PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";
         $pdo->exec($sql);
         
         // Seed default users (with password_hash)
         $passHash1 = password_hash('password123', PASSWORD_DEFAULT);
         $passHash2 = password_hash('budi2026', PASSWORD_DEFAULT);
         $passHash3 = password_hash('siti99', PASSWORD_DEFAULT);
         
         $seedUsers = "INSERT INTO `users` (`username`, `email`, `password`) VALUES
             ('admin', 'admin@nusagrid.com', '$passHash1'),
             ('budi_santoso', 'budi@gmail.com', '$passHash2'),
             ('siti_aminah', 'siti@yahoo.com', '$passHash3')";
         $pdo->exec($seedUsers);
     }
     
     // 2. Automatic GPU Services Table Initialization
     $tableGpuQuery = "SHOW TABLES LIKE 'gpu_services'";
     $gpuExists = $pdo->query($tableGpuQuery)->rowCount() > 0;
     
     if (!$gpuExists) {
         $sql = "CREATE TABLE `gpu_services` (
             `id` INT(11) NOT NULL AUTO_INCREMENT,
             `nama_gpu` VARCHAR(100) NOT NULL,
             `harga` VARCHAR(50) NOT NULL,
             `kebutuhan` TEXT DEFAULT NULL,
             `foto` VARCHAR(255) DEFAULT NULL,
             PRIMARY KEY (`id`)
         ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";
         $pdo->exec($sql);
         
         // Seed default GPU services
         $seedGpu = "INSERT INTO `gpu_services` (`nama_gpu`, `harga`, `kebutuhan`, `foto`) VALUES
             ('NVIDIA GeForce RTX 4090', 'Rp 15.000 / Jam', 'Minimum RAM 32GB, Intel i7 Gen 12 / Ryzen 7, PCIe Gen 4', NULL),
             ('NVIDIA A100 Tensor Core (80GB)', 'Rp 45.000 / Jam', 'Ubuntu 22.04 LTS, CUDA 12.0+, Docker installed, RAM 64GB', NULL),
             ('NVIDIA H100 Tensor Core (80GB)', 'Rp 95.000 / Jam', 'Ubuntu 22.04 LTS, CUDA 12.2+, Deep Learning Stack, RAM 128GB', NULL)";
         $pdo->exec($seedGpu);
     }
     
     // 3. Automatic Rentals Table Initialization
     $tableRentalsQuery = "SHOW TABLES LIKE 'rentals'";
     $rentalsExists = $pdo->query($tableRentalsQuery)->rowCount() > 0;
     
     if (!$rentalsExists) {
         $sql = "CREATE TABLE `rentals` (
             `id` INT(11) NOT NULL AUTO_INCREMENT,
             `user_id` INT(11) NOT NULL,
             `gpu_id` INT(11) NOT NULL,
             `tanggal_sewa` DATETIME DEFAULT CURRENT_TIMESTAMP(),
             `durasi_jam` INT(11) NOT NULL,
             `total_harga` DECIMAL(10,2) NOT NULL,
             `status_pembayaran` ENUM('pending','lunas','batal') DEFAULT 'pending',
             PRIMARY KEY (`id`),
             KEY `user_id` (`user_id`),
             KEY `gpu_id` (`gpu_id`),
             CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
             CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`gpu_id`) REFERENCES `gpu_services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
         ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";
         $pdo->exec($sql);
         
         // Seed default rentals (first user rents RTX 4090 for 10 hours, second user rents A100 for 5 hours)
         $seedRentals = "INSERT INTO `rentals` (`user_id`, `gpu_id`, `durasi_jam`, `total_harga`, `status_pembayaran`) VALUES
             (1, 1, 10, 150000.00, 'lunas'),
             (2, 2, 5, 225000.00, 'pending')";
         $pdo->exec($seedRentals);
     }
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
