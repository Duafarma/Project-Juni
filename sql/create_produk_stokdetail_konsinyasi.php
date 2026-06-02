<?php
require_once('../config/connection/connection.php');

$base = new DB;
$conn = $base->open();

try {
    $sql = "CREATE TABLE IF NOT EXISTS `produk_stokdetail_konsinyasi` (
      `id_psd` int(11) NOT NULL AUTO_INCREMENT,
      `id_pro` varchar(15) NOT NULL,
      `id_tfk` varchar(15) NOT NULL,
      `no_bcode` varchar(25) NOT NULL,
      `tgl_expired` date NOT NULL,
      `tgl_psd` date NOT NULL,
      `masuk_psd` int(11) NOT NULL,
      `keluar_psd` int(11) NOT NULL,
      `sisa_psd` int(11) NOT NULL,
      `gudang` varchar(50) NOT NULL,
      `qty_so` int(12) NOT NULL,
      `status_barang` enum('inactive','active') NOT NULL,
      `created_at` datetime NOT NULL,
      `created_by` varchar(15) NOT NULL,
      `updated_at` datetime NOT NULL,
      `updated_by` varchar(15) NOT NULL,
      PRIMARY KEY (`id_psd`),
      KEY `id_tfk` (`id_tfk`),
      KEY `id_pro` (`id_pro`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    $conn->exec($sql);
    echo "Tabel produk_stokdetail_konsinyasi berhasil dibuat!\n";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$conn = $base->close();
?>
