-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2026 at 06:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dompetku`
--
CREATE DATABASE IF NOT EXISTS `dompetku` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `dompetku`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `google_id`, `role`, `is_active`, `created_at`) VALUES
(1, 'bagas', 'bagas@gmail.com', '$2y$10$bwA/O.KqrjxWRmLXMHZTT.mH3GfzdOf0ZnsEqPMYvVvrtEXFFxM9S', NULL, 'admin', 1, '2026-06-09 16:28:24'),
(2, 'bagas_58', '1202407009@students.itspku.ac.id', '$2y$10$aaMbpYKV2hnn7fML14aPbe6zCOam50Mr0tZL.1pjl.eGnSF6byef.', '107159312857097569522', 'user', 1, '2026-06-28 15:29:52'),
(3, 'testagent', 'testagent@example.com', '$2y$10$CsyeSNHG3FDL50V.ro8V8.Tw7QnTST2eTMo9lydcvgf93hAk789sy', NULL, 'user', 1, '2026-06-28 16:48:58'),
(4, 'bagas_15', 'pramonobagas01@gmail.com', '$2y$10$0aWmWHzbP.zsqtP0NLnnXuoSD7QmCUqRHQfJNp6I5W8t956hEySLu', '113194545666740852405', 'user', 1, '2026-06-28 17:26:03'),
(5, 'mamak', 'kasmi18022016@gmail.com', '$2y$10$Tr202zZRJUyDksZ0FrSKAuEUrNkoVTy8d41if3gJKRqZhzXTWTjAm', NULL, 'user', 1, '2026-06-28 17:26:53'),
(6, 'epep', 'asdoaskjod@gmail.com', '$2y$10$KgErie537xzPE7KGrUM3Pe9sYgaiQrlxYt6sA4Cux4wUheBQ3SZ0O', NULL, 'user', 1, '2026-06-28 17:30:50'),
(7, 'asdoaskdokd', 'ijsaidj@gmail.com', '$2y$10$0WB0YwGW.qTOYjeGZ71aWOPuQOQ2lVFFF1ZBEyLAoPd7cg0mA1c2O', NULL, 'user', 1, '2026-06-28 17:53:56'),
(8, 'cito', 'cytoplasm1324@gmail.com', '$2y$10$l7W1QcrqXv8IfQZeuDtURu27LW2PdNg.5CDgaA59dgEiVSbQ9Ldtm', NULL, 'user', 1, '2026-07-02 06:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `dompet`
--

CREATE TABLE `dompet` (
  `id_dompet` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nama_dompet` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_dompet`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `dompet_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dompet`
--

INSERT INTO `dompet` (`id_dompet`, `id_user`, `nama_dompet`, `created_at`) VALUES
(1, 1, 'Dompet Utama', '2026-06-09 16:28:24'),
(2, 2, 'Dompet Utama', '2026-06-28 15:29:52'),
(3, 3, 'Dompet Utama', '2026-06-28 16:48:58'),
(4, 4, 'Dompet Utama', '2026-06-28 17:26:03'),
(5, 5, 'Dompet Utama', '2026-06-28 17:26:53'),
(6, 6, 'Dompet Utama', '2026-07-02 06:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id_kategori` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `tipe` enum('Pemasukan','Pengeluaran') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_kategori`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `kategori_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id_kategori`, `id_user`, `nama_kategori`, `tipe`, `created_at`) VALUES
(2, 2, 'buat makan', 'Pengeluaran', '2026-06-28 16:43:00'),
(3, 1, 'Makanan', 'Pengeluaran', '2026-06-28 16:52:32'),
(4, 2, 'gojek', 'Pengeluaran', '2026-07-02 06:08:34'),
(6, 2, 'di tf mamak', 'Pemasukan', '2026-07-02 06:59:27');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `id_kategori` int(11) DEFAULT NULL,
  `id_dompet` int(11) DEFAULT NULL,
  `jenis` enum('Pemasukan','Pengeluaran') NOT NULL,
  `nominal` decimal(12,2) NOT NULL,
  `keterangan` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `is_transfer` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fk_transaksi_kategori` (`id_kategori`),
  KEY `fk_transaksi_dompet` (`id_dompet`),
  CONSTRAINT `fk_transaksi_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE SET NULL,
  CONSTRAINT `fk_transaksi_dompet` FOREIGN KEY (`id_dompet`) REFERENCES `dompet` (`id_dompet`) ON DELETE SET NULL,
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `user_id`, `id_kategori`, `id_dompet`, `jenis`, `nominal`, `keterangan`, `tanggal`, `is_transfer`, `created_at`) VALUES
(19, 2, 6, 2, 'Pemasukan', 300000.00, 'di tf mamak', '2026-06-28', 0, '2026-06-28 15:30:15'),
(27, 2, 2, 2, 'Pengeluaran', 70000.00, 'beli maem', '2026-06-28', 0, '2026-06-28 17:01:07'),
(30, 2, 2, 2, 'Pengeluaran', 25000.00, 'beli maem', '2026-07-02', 0, '2026-07-02 06:43:14');

-- --------------------------------------------------------

--
-- Table structure for table `anggaran`
--

CREATE TABLE `anggaran` (
  `id_anggaran` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `jumlah_budget` decimal(15,2) NOT NULL,
  `bulan` tinyint(4) NOT NULL CHECK (`bulan` between 1 and 12),
  `tahun` year(4) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_anggaran`),
  UNIQUE KEY `user_kategori_periode` (`id_user`,`id_kategori`,`bulan`,`tahun`),
  KEY `id_kategori` (`id_kategori`),
  CONSTRAINT `anggaran_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `anggaran_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori` (`id_kategori`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggaran`
--

INSERT INTO `anggaran` (`id_anggaran`, `id_user`, `id_kategori`, `jumlah_budget`, `bulan`, `tahun`, `created_at`) VALUES
(1, 2, 2, 300000.00, 6, '2026', '2026-06-28 16:43:45'),
(2, 2, 2, 300000.00, 7, '2026', '2026-07-02 06:40:44');

-- --------------------------------------------------------

--
-- Table structure for table `target_tabungan`
--

CREATE TABLE `target_tabungan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nama_target` varchar(100) NOT NULL,
  `nominal_target` decimal(15,2) NOT NULL,
  `nominal_terkumpul` decimal(15,2) DEFAULT 0.00,
  `tenggat_waktu` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `target_tabungan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
