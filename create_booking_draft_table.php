<?php
require_once('config/connection/connection.php');
require_once('config/function/data.php');
$base = new DB;
$conn = $base->open();

echo "<h3>Create Tabel transaksi_booking_draft</h3>";

$sql = "CREATE TABLE IF NOT EXISTS `transaksi_booking_draft` (
    `id_bd` INT(11) NOT NULL AUTO_INCREMENT,
    `id_tfk` VARCHAR(100) NOT NULL COMMENT 'ID Faktur yang sedang di-input',
    `id_psd` VARCHAR(100) NOT NULL COMMENT 'ID Batch dari produk_stokdetail',
    `jumlah` INT(11) NOT NULL DEFAULT 1 COMMENT 'Jumlah yang di-booking',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_bd`),
    UNIQUE KEY `unique_booking` (`id_tfk`, `id_psd`),
    KEY `idx_id_psd` (`id_psd`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Tabel temporary untuk booking stok sebelum faktur disimpan';";

try {
    $conn->exec($sql);
    echo "<div style='color:green'>✓ Tabel transaksi_booking_draft berhasil dibuat!</div>";
} catch (PDOException $e) {
    echo "<div style='color:red'>✗ Error: " . $e->getMessage() . "</div>";
}

// Tambah event scheduler untuk auto-cleanup booking lama (lebih dari 24 jam)
echo "<h4>Info</h4>";
echo "<p>Tabel ini menyimpan booking sementara saat user memilih produk di halaman input item faktur.</p>";
echo "<p>Data booking akan otomatis dihapus saat faktur disimpan (Simpan).</p>";
echo "<p>Untuk membersihkan data lama (lebih dari 24 jam), jalankan query:</p>";
echo "<code>DELETE FROM transaksi_booking_draft WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);</code>";

$base->close();
?>
