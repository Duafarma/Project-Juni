<?php
require_once('../config/connection/connection.php');

$base = new DB;
$conn = $base->open();

echo "<h2>Update Tabel transaksi_faktur - Tambah Field Konsinyasi</h2>";

try {
    // Cek apakah kolom sudah ada
    $check = $conn->query("SHOW COLUMNS FROM transaksi_faktur LIKE 'dari_konsinyasi'");
    
    if($check->rowCount() == 0) {
        // Tambah kolom dari_konsinyasi
        $sql1 = "ALTER TABLE transaksi_faktur 
                 ADD COLUMN dari_konsinyasi ENUM('ya','tidak') NOT NULL DEFAULT 'tidak' AFTER program";
        $conn->exec($sql1);
        echo "<p style='color:green;'>✓ Kolom 'dari_konsinyasi' berhasil ditambahkan</p>";
    } else {
        echo "<p style='color:orange;'>⚠ Kolom 'dari_konsinyasi' sudah ada</p>";
    }
    
    // Cek apakah kolom id_tfk_konsinyasi sudah ada
    $check2 = $conn->query("SHOW COLUMNS FROM transaksi_faktur LIKE 'id_tfk_konsinyasi'");
    
    if($check2->rowCount() == 0) {
        // Tambah kolom id_tfk_konsinyasi
        $sql2 = "ALTER TABLE transaksi_faktur 
                 ADD COLUMN id_tfk_konsinyasi VARCHAR(15) NULL AFTER dari_konsinyasi";
        $conn->exec($sql2);
        echo "<p style='color:green;'>✓ Kolom 'id_tfk_konsinyasi' berhasil ditambahkan</p>";
    } else {
        echo "<p style='color:orange;'>⚠ Kolom 'id_tfk_konsinyasi' sudah ada</p>";
    }
    
    echo "<hr>";
    echo "<h3>Struktur Tabel Terbaru:</h3>";
    $show = $conn->query("SHOW COLUMNS FROM transaksi_faktur");
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($col = $show->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>".$col['Field']."</td>";
        echo "<td>".$col['Type']."</td>";
        echo "<td>".$col['Null']."</td>";
        echo "<td>".$col['Key']."</td>";
        echo "<td>".$col['Default']."</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color:red;'>✗ Error: " . $e->getMessage() . "</p>";
}

$conn = $base->close();
?>
