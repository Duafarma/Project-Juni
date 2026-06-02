<?php
// Create konsinyasi tables
require_once("config/connection/connection.php");

$db = new DB();
$conn = $db->open();

try {
    // Read SQL file
    $sql = file_get_contents("sql/create_konsinyasi_tables.sql");
    
    // Execute SQL
    $conn->exec($sql);
    
    echo "✓ Tables created successfully!\n";
    echo "- transaksi_faktur_konsinyasi\n";
    echo "- transaksi_fakturdetail_konsinyasi\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

$db->close();
?>
