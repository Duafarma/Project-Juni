<?php
require_once('../config/connection/connection.php');

echo "========================================\n";
echo "Fixing sub_menu table issue...\n";
echo "========================================\n\n";

// Create separate connections for each operation
function getConnection() {
    $base = new DB;
    return $base->open();
}

// Test 1: Check if table exists
echo "1. Checking if sub_menu exists...\n";
try {
    $conn = getConnection();
    $result = $conn->query("SHOW TABLES LIKE 'sub_menu'")->fetch();
    if ($result) {
        echo "   ✓ Table exists\n\n";
    } else {
        echo "   ✗ Table NOT found\n\n";
        exit(1);
    }
    $conn = null;
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Try to query table
echo "2. Testing query on sub_menu...\n";
try {
    $conn = getConnection();
    $count = $conn->query("SELECT COUNT(*) as total FROM sub_menu")->fetchColumn();
    echo "   ✓ Query successful! Rows: $count\n\n";
    $conn = null;
    
    echo "========================================\n";
    echo "✓ Table is working fine!\n";
    echo "========================================\n";
    exit(0);
    
} catch (PDOException $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . "\n\n";
    echo "   Table appears to be corrupt. Attempting to fix...\n\n";
}

// If we reach here, table is corrupt - recreate it
echo "3. Recreating sub_menu table...\n";
try {
    $conn = getConnection();
    
    // Drop table
    echo "   - Dropping corrupt table...\n";
    $conn->exec("DROP TABLE IF EXISTS sub_menu");
    $conn = null;
    
    // Create new table
    $conn = getConnection();
    echo "   - Creating new table...\n";
    $conn->exec("
        CREATE TABLE `sub_menu` (
          `id_smu` varchar(50) NOT NULL,
          `id_menu` varchar(50) NOT NULL,
          `nama_smu` varchar(255) NOT NULL,
          `url_smu` varchar(255) NOT NULL,
          `urutan_smu` int(11) NOT NULL DEFAULT 0,
          `created_at` datetime NOT NULL,
          `created_by` varchar(50) NOT NULL,
          `updated_at` datetime NOT NULL,
          `updated_by` varchar(50) NOT NULL,
          PRIMARY KEY (`id_smu`),
          KEY `idx_menu` (`id_menu`),
          KEY `idx_urutan` (`urutan_smu`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "   ✓ Table recreated successfully!\n\n";
    $conn = null;
    
    // Verify
    $conn = getConnection();
    $count = $conn->query("SELECT COUNT(*) as total FROM sub_menu")->fetchColumn();
    echo "   ✓ Verification successful! Rows: $count\n\n";
    $conn = null;
    
    echo "========================================\n";
    echo "✓ Table fixed successfully!\n";
    echo "========================================\n";
    
} catch (PDOException $e) {
    echo "   ✗ Failed: " . $e->getMessage() . "\n\n";
    echo "========================================\n";
    echo "✗ Please fix manually via phpMyAdmin\n";
    echo "========================================\n";
    exit(1);
}
?>
