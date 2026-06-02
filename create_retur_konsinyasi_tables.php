<?php
require 'config/connection/connection.php';

$db = new DB();
$conn = $db->open();

try {
    echo "=== Creating Retur Konsinyasi Tables ===\n\n";
    
    // Create transaksi_retur_konsinyasi
    $sql1 = "CREATE TABLE IF NOT EXISTS `transaksi_retur_konsinyasi` (
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
      `updated_by` varchar(15) NOT NULL,
      PRIMARY KEY (`id_trk`),
      KEY `id_tfk` (`id_tfk`),
      KEY `id_out` (`id_out`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Header Retur Barang Konsinyasi'";
    
    $conn->exec($sql1);
    echo "✓ Table 'transaksi_retur_konsinyasi' created successfully\n";
    
    // Create transaksi_retur_konsinyasi_detail
    $sql2 = "CREATE TABLE IF NOT EXISTS `transaksi_retur_konsinyasi_detail` (
      `id_trkd` int(11) NOT NULL AUTO_INCREMENT,
      `id_trk` varchar(15) NOT NULL,
      `id_tfd` int(11) NOT NULL COMMENT 'ID dari transaksi_fakturdetail_konsinyasi',
      `id_psd` int(11) NOT NULL,
      `id_pro` varchar(15) NOT NULL,
      `qty_retur` int(11) NOT NULL,
      `qty_sisa_sebelum` int(11) NOT NULL COMMENT 'Sisa sebelum retur',
      `qty_sisa_sesudah` int(11) NOT NULL COMMENT 'Sisa setelah retur',
      `created_at` datetime NOT NULL,
      `created_by` varchar(15) NOT NULL,
      PRIMARY KEY (`id_trkd`),
      KEY `id_trk` (`id_trk`),
      KEY `id_psd` (`id_psd`),
      KEY `id_pro` (`id_pro`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Detail Item Retur Konsinyasi'";
    
    $conn->exec($sql2);
    echo "✓ Table 'transaksi_retur_konsinyasi_detail' created successfully\n";
    
    echo "\n=== SUCCESS ===\n";
    echo "Retur Konsinyasi tables created successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
