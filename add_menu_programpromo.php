<?php
/**
 * Script untuk menambahkan menu Program Promo ke database
 * Jalankan sekali saja: php add_menu_programpromo.php
 */

require_once('config/connection/connection.php');
$base = new DB;
$conn = $base->open();

try {
    // Cek tabel yang ada
    echo "=== TABEL YANG ADA ===\n";
    $tables = $conn->query("SHOW TABLES LIKE '%menu%'");
    foreach ($tables->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print_r($row);
    }
    
    echo "\n=== TABEL akses ===\n";
    $tables2 = $conn->query("SHOW TABLES LIKE '%akses%'");
    foreach ($tables2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print_r($row);
    }
    
    // Cek struktur akses
    echo "\n=== STRUKTUR akses ===\n";
    $struktur = $conn->query("DESCRIBE akses");
    foreach ($struktur->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
    
    // Cek sample akses untuk masterprinciple
    echo "\n=== SAMPLE akses (masterprinciple) ===\n";
    $sample = $conn->query("SELECT * FROM akses WHERE link_akses LIKE '%masterprinciple%' LIMIT 3");
    foreach ($sample->fetchAll(PDO::FETCH_ASSOC) as $row) {
        print_r($row);
    }
    
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}

$conn = $base->close();
echo "\n[DONE] Script selesai.\n";
?>
