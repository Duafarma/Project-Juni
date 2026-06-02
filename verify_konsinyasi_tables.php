<?php
// Verify konsinyasi tables
require_once("config/connection/connection.php");

$db = new DB();
$conn = $db->open();

try {
    // Check transaksi_faktur_konsinyasi
    $query1 = $conn->query("DESCRIBE transaksi_faktur_konsinyasi");
    echo "✓ Table: transaksi_faktur_konsinyasi\n";
    echo "Columns:\n";
    while ($row = $query1->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n";
    
    // Check transaksi_fakturdetail_konsinyasi
    $query2 = $conn->query("DESCRIBE transaksi_fakturdetail_konsinyasi");
    echo "✓ Table: transaksi_fakturdetail_konsinyasi\n";
    echo "Columns:\n";
    while ($row = $query2->fetch()) {
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    
    echo "\n✓ All tables are ready for fsalesk system!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$db->close();
?>
