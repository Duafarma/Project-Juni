-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Feb 2026 pada 09.16
-- Versi server: 10.4.25-MariaDB
-- Versi PHP: 7.4.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `program`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaksi_retur_konsinyasi`
--

CREATE TABLE `transaksi_retur_konsinyasi` (
  `id_trk` varchar(15) NOT NULL,
  `id_tfk` varchar(15) NOT NULL COMMENT 'ID Faktur Konsinyasi',
  `no_retur` varchar(50) NOT NULL,
  `tgl_retur` date NOT NULL,
  `id_out` varchar(15) NOT NULL,
  `total_item` int(11) NOT NULL DEFAULT 0,
  `total_qty` int(11) NOT NULL DEFAULT 0,
  `keterangan` text NOT NULL,
  `status_trk` enum('Draft','Approved','Selesai') NOT NULL DEFAULT 'Selesai',
  `created_at` datetime NOT NULL,
  `created_by` varchar(15) NOT NULL,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Header Retur Barang Konsinyasi';

--
-- Dumping data untuk tabel `transaksi_retur_konsinyasi`
--

INSERT INTO `transaksi_retur_konsinyasi` (`id_trk`, `id_tfk`, `no_retur`, `tgl_retur`, `id_out`, `total_item`, `total_qty`, `keterangan`, `status_trk`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
('69856ae1e9e16', 'FAK1770350938', '0001/RTK/II/26', '2026-02-06', 'OUT1594889247', 2, 60, 'retur balik tidak laku', 'Selesai', '2026-02-06 11:15:29', 'ADM1641787260', '2026-02-06 11:15:29', 'ADM1641787260'),
('69856dba6e468', 'FAK1770351785', '0002/RTK/II/26', '2026-02-06', 'OUT1594889247', 2, 350, 'balikin lagi barangnya tidak laku', 'Selesai', '2026-02-06 11:27:38', 'ADM1641787260', '2026-02-06 11:27:38', 'ADM1641787260');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `transaksi_retur_konsinyasi`
--
ALTER TABLE `transaksi_retur_konsinyasi`
  ADD PRIMARY KEY (`id_trk`),
  ADD KEY `id_tfk` (`id_tfk`),
  ADD KEY `id_out` (`id_out`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
