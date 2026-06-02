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
-- Struktur dari tabel `transaksi_retur_konsinyasi_detail`
--

CREATE TABLE `transaksi_retur_konsinyasi_detail` (
  `id_trkd` int(11) NOT NULL,
  `id_trk` varchar(15) NOT NULL,
  `id_tfd` int(11) NOT NULL COMMENT 'ID dari transaksi_fakturdetail_konsinyasi',
  `id_psd` int(11) NOT NULL,
  `id_pro` varchar(15) NOT NULL,
  `qty_retur` int(11) NOT NULL,
  `qty_sisa_sebelum` int(11) NOT NULL COMMENT 'Sisa sebelum retur',
  `qty_sisa_sesudah` int(11) NOT NULL COMMENT 'Sisa setelah retur',
  `created_at` datetime NOT NULL,
  `created_by` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Detail Item Retur Konsinyasi';

--
-- Dumping data untuk tabel `transaksi_retur_konsinyasi_detail`
--

INSERT INTO `transaksi_retur_konsinyasi_detail` (`id_trkd`, `id_trk`, `id_tfd`, `id_psd`, `id_pro`, `qty_retur`, `qty_sisa_sebelum`, `qty_sisa_sesudah`, `created_at`, `created_by`) VALUES
(5, '69856dba6e468', 19, 25335, 'PRO00069', 300, 300, 0, '2026-02-06 11:27:38', 'ADM1641787260'),
(6, '69856dba6e468', 18, 25371, 'PRO00115', 50, 50, 0, '2026-02-06 11:27:38', 'ADM1641787260');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `transaksi_retur_konsinyasi_detail`
--
ALTER TABLE `transaksi_retur_konsinyasi_detail`
  ADD PRIMARY KEY (`id_trkd`),
  ADD KEY `id_trk` (`id_trk`),
  ADD KEY `id_psd` (`id_psd`),
  ADD KEY `id_pro` (`id_pro`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `transaksi_retur_konsinyasi_detail`
--
ALTER TABLE `transaksi_retur_konsinyasi_detail`
  MODIFY `id_trkd` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
