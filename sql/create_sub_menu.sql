-- ============================================
-- Create table: sub_menu
-- Description: Tabel untuk sub menu sistem
-- ============================================

CREATE TABLE IF NOT EXISTS `sub_menu` (
  `id_smu` varchar(50) NOT NULL,
  `id_menu` varchar(50) NOT NULL,
  `nama_smu` varchar(255) NOT NULL,
  `url_smu` varchar(255) NOT NULL,
  `urutan_smu` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `created_by` varchar(50) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(50) NOT NULL,
  PRIMARY KEY (`id_smu`),
  KEY `idx_menu` (`id_menu`),
  KEY `idx_urutan` (`urutan_smu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- Contoh data sample (optional)
-- ============================================
-- INSERT INTO `sub_menu` VALUES
-- ('SMU0001', 'MNU0001', 'Master Produk', 'produk', 1, NOW(), 'SYSTEM', NOW(), 'SYSTEM'),
-- ('SMU0002', 'MNU0001', 'Kategori Produk', 'kategoriproduk', 2, NOW(), 'SYSTEM', NOW(), 'SYSTEM');
