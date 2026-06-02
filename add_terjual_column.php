<?php
require 'config/connection/connection.php';

$db = new DB();
$conn = $db->open();

try {
    // Add terjual_tfd and sisa_tfd columns
    $sql = "ALTER TABLE `transaksi_fakturdetail_konsinyasi`
            ADD COLUMN `terjual_tfd` INT(11) NOT NULL DEFAULT 0 AFTER `jumlah_tfd`,
            ADD COLUMN `sisa_tfd` INT(11) NOT NULL DEFAULT 0 AFTER `terjual_tfd`";
    $conn->exec($sql);
    echo "✓ Kolom terjual_tfd dan sisa_tfd berhasil ditambahkan\n";
    
    // Update existing data
    $sql2 = "UPDATE `transaksi_fakturdetail_konsinyasi`
             SET `sisa_tfd` = `jumlah_tfd`
             WHERE `sisa_tfd` = 0";
    $conn->exec($sql2);
    echo "✓ Data existing berhasil diupdate (sisa_tfd = jumlah_tfd)\n";
    
    echo "\n=== SUCCESS ===\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
