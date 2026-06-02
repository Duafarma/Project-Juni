-- SQL untuk menambahkan dukungan outlet pada master program produk
-- Jalankan file ini sekali saja setelah tabel program_produk sudah ada.

ALTER TABLE `program_produk`
    ADD COLUMN `jenis_program` VARCHAR(50) DEFAULT NULL COMMENT 'Jenis program: Paket Starter, Growth Pack, Custom',
    ADD COLUMN `min_qty` INT(11) NOT NULL DEFAULT 1 COMMENT 'Minimum total qty untuk program',
    ADD COLUMN `diskon_persen` INT(11) NOT NULL DEFAULT 0 COMMENT 'Diskon persen untuk program',
    ADD COLUMN `status_program` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active' COMMENT 'Status program';

CREATE TABLE IF NOT EXISTS `program_produk_outlet` (
  `id_ppo` INT(11) NOT NULL AUTO_INCREMENT,
  `id_pp` INT(11) NOT NULL COMMENT 'ID program produk',
  `id_out` INT(11) NOT NULL COMMENT 'ID outlet yang mendapat program',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id_ppo`),
  KEY `idx_id_pp` (`id_pp`),
  KEY `idx_id_out` (`id_out`),
  UNIQUE KEY `unique_program_outlet` (`id_pp`,`id_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
