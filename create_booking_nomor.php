<?php
require_once('config/connection/connection.php');
$base = new DB;
$conn = $base->open();

$sql = "CREATE TABLE IF NOT EXISTS `nomor_faktur_booking` (
    `id_booking` INT(11) NOT NULL AUTO_INCREMENT,
    `id_tfk` VARCHAR(100) NOT NULL,
    `id_out` VARCHAR(50) NOT NULL,
    `sj_tfk` VARCHAR(100) NOT NULL,
    `kode_tfk` VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_booking`),
    UNIQUE KEY `unique_id_tfk` (`id_tfk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

try {
    $conn->exec($sql);
    echo "Tabel nomor_faktur_booking berhasil dibuat!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
$base->close();
?>
