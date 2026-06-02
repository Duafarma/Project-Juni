-- Migration: rename program_produk tables to master_program_produk equivalents
-- Pastikan file ini dijalankan setelah backup database.

RENAME TABLE
  `program_produk` TO `master_program_produk`,
  `program_produk_detail` TO `master_program_produk_detail`,
  `program_produk_outlet` TO `master_program_produk_outlet`;

-- Jika Anda memerlukan fallback alias lama, jalankan setelah rename:
-- CREATE TABLE IF NOT EXISTS `program_produk` LIKE `master_program_produk`;
-- CREATE TABLE IF NOT EXISTS `program_produk_detail` LIKE `master_program_produk_detail`;
-- CREATE TABLE IF NOT EXISTS `program_produk_outlet` LIKE `master_program_produk_outlet`;
