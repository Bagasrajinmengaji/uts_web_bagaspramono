-- Database initialization for DompetKu Web Application
CREATE DATABASE IF NOT EXISTS `dompetku`;
USE `dompetku`;

-- Create Table users
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `google_id` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Table kategori
CREATE TABLE IF NOT EXISTS `kategori` (
    `id_kategori` INT AUTO_INCREMENT PRIMARY KEY,
    `id_user` INT NOT NULL,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `tipe` ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Table anggaran (budgeting)
CREATE TABLE IF NOT EXISTS `anggaran` (
    `id_anggaran` INT AUTO_INCREMENT PRIMARY KEY,
    `id_user` INT NOT NULL,
    `id_kategori` INT NOT NULL,
    `jumlah_budget` DECIMAL(15, 2) NOT NULL,
    `bulan` TINYINT NOT NULL CHECK (`bulan` BETWEEN 1 AND 12),
    `tahun` YEAR NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `user_kategori_periode` (`id_user`, `id_kategori`, `bulan`, `tahun`),
    FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Table transaksi
CREATE TABLE IF NOT EXISTS `transaksi` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `id_kategori` INT DEFAULT NULL,
    `jenis` ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
    `nominal` DECIMAL(12,2) NOT NULL,
    `keterangan` VARCHAR(255) NOT NULL,
    `tanggal` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;