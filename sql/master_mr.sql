CREATE TABLE IF NOT EXISTS `master_mr_baru` (
  `id_mr` int(11) NOT NULL AUTO_INCREMENT,
  `nama_mr` varchar(255) NOT NULL DEFAULT '',
  `area` varchar(255) NOT NULL DEFAULT '',
  `ket` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(100) NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_mr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;