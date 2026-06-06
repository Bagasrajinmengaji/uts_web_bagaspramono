-- Database Name: `nusagrid_gpu`
-- Tables Structure & Relationships

-- 1. Table structure for table `users`
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 2. Table structure for table `gpu_services`
CREATE TABLE IF NOT EXISTS `gpu_services` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_gpu` VARCHAR(100) NOT NULL,
  `harga` VARCHAR(50) NOT NULL,
  `kebutuhan` TEXT DEFAULT NULL,
  `foto` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- 3. Table structure for table `rentals`
CREATE TABLE IF NOT EXISTS `rentals` (
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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

COMMIT;
