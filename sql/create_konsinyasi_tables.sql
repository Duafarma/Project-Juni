-- Create konsinyasi tables for fsalesk system
-- Date: 2026-02-05

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create transaksi_faktur_konsinyasi table
CREATE TABLE IF NOT EXISTS `transaksi_faktur_konsinyasi` (
  `id_tfk` varchar(15) NOT NULL,
  `id_tsl` varchar(15) NOT NULL,
  `id_out` varchar(15) NOT NULL,
  `sj_tfk` varchar(25) NOT NULL,
  `tglsj_tfk` date NOT NULL,
  `po_tfk` varchar(70) NOT NULL,
  `tglpo_tfk` date NOT NULL,
  `kode_tfk` varchar(50) NOT NULL,
  `tgl_tfk` date NOT NULL,
  `tgl_limit` date NOT NULL,
  `pajak_tfk` varchar(50) NOT NULL DEFAULT 'A',
  `pajak_tfkt` varchar(50) NOT NULL DEFAULT 'penjualan',
  `subtot_tfk` int(11) NOT NULL,
  `ppn_tfk` int(11) NOT NULL,
  `total_tfk` int(11) NOT NULL,
  `status_tfk` enum('Konsinyasi','Selesai','Sebagian') NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(15) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(15) NOT NULL,
  PRIMARY KEY (`id_tfk`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Create transaksi_fakturdetail_konsinyasi table
CREATE TABLE IF NOT EXISTS `transaksi_fakturdetail_konsinyasi` (
  `id_tfd` int(11) NOT NULL AUTO_INCREMENT,
  `id_tfk` varchar(15) NOT NULL,
  `id_psd` int(11) NOT NULL,
  `id_pro` varchar(15) NOT NULL,
  `jumlah_tfd` int(11) NOT NULL,
  `harga_tfd` int(11) NOT NULL,
  `diskon_tfd` varchar(11) NOT NULL,
  `total_tfd` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  `created_by` varchar(15) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(15) NOT NULL,
  PRIMARY KEY (`id_tfd`),
  KEY `id_psd` (`id_psd`),
  KEY `id_pro` (`id_pro`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

COMMIT;
