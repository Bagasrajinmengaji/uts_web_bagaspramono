-- ============================================================
-- Migrasi: Tambah Kolom 'role' ke Tabel 'users'
-- Jalankan sekali di phpMyAdmin atau MySQL CLI
-- ============================================================

-- Tambah kolom role dengan nilai default 'user'
ALTER TABLE `users`
    ADD COLUMN `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user'
    AFTER `google_id`;

-- Tetapkan user pertama sebagai admin (sesuaikan id-nya)
-- UPDATE `users` SET `role` = 'admin' WHERE `id` = 1;
