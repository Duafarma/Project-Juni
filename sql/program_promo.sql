-- =====================================================
-- TABEL PROGRAM PROMO
-- Untuk mengontrol program diskon yang aktif
-- =====================================================

-- Buat tabel program_promo
CREATE TABLE IF NOT EXISTS `program_promo` (
  `id_program` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_program` VARCHAR(50) NOT NULL COMMENT 'Kode unik program: program_vb, program_diskon, dll',
  `nama_program` VARCHAR(100) NOT NULL COMMENT 'Nama tampilan program',
  `deskripsi` TEXT DEFAULT NULL COMMENT 'Deskripsi program',
  `icon_class` VARCHAR(50) DEFAULT 'fas fa-tag' COMMENT 'Font Awesome icon class',
  `status_program` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `urutan` INT(11) DEFAULT 0 COMMENT 'Urutan tampil',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_program`),
  UNIQUE KEY `kode_program_unique` (`kode_program`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert data program default
INSERT INTO `program_promo` (`kode_program`, `nama_program`, `deskripsi`, `icon_class`, `status_program`, `urutan`) VALUES
('program_vb', 'Program VB (Volume Bonus)', 'Diskon untuk produk VISION BLU 7 ML dan VISION BLU EXTRA 7 ML - Diskon 30%', 'fas fa-percentage', 'Active', 1),
('program_diskon', 'Program Diskon Khusus', 'Diskon khusus untuk produk RHEA berdasarkan jumlah pembelian', 'fas fa-tag', 'Active', 2),
('program_cashback', 'Program Cashback', 'Program cashback untuk pembelian tertentu', 'fas fa-money-bill-wave', 'Active', 3),
('program_bundling', 'Program Bundling', 'Program bundling produk dengan harga spesial', 'fas fa-box-open', 'Active', 4);

-- =====================================================
-- CARA PENGGUNAAN:
-- 1. Jalankan SQL ini di phpMyAdmin atau MySQL client
-- 2. Untuk menonaktifkan program, ubah status_program menjadi 'Inactive'
-- 3. Kode program (kode_program) adalah yang digunakan di JavaScript
--    - program_vb = untuk VISION BLU
--    - program_diskon = untuk RHEA
-- =====================================================
