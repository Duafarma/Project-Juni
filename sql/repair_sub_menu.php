<?php
require_once('../config/connection/connection.php');

$base = new DB;
$conn = $base->open();

echo "========================================\n";
echo "Repairing sub_menu table...\n";
echo "========================================\n\n";

try {
    // Try to repair table
    echo "1. Attempting REPAIR TABLE...\n";
    $conn->exec("REPAIR TABLE sub_menu");
    echo "   ✓ Repair command executed\n\n";
    
} catch (PDOException $e) {
    echo "   ✗ Repair failed: " . $e->getMessage() . "\n\n";
}

try {
    // Check table status
    echo "2. Checking table status...\n";
    $status = $conn->query("CHECK TABLE sub_menu")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($status as $row) {
        echo "   - {$row['Msg_type']}: {$row['Msg_text']}\n";
    }
    echo "\n";
    
} catch (PDOException $e) {
    echo "   ✗ Check failed: " . $e->getMessage() . "\n\n";
}

try {
    // Try to SELECT from table
    echo "3. Testing SELECT query...\n";
    $test = $conn->query("SELECT COUNT(*) as total FROM sub_menu")->fetch();
    echo "   ✓ Query successful! Total rows: {$test['total']}\n\n";
    
} catch (PDOException $e) {
    echo "   ✗ Query failed: " . $e->getMessage() . "\n\n";
    
    // If SELECT fails, try to recreate table
    echo "4. Attempting to recreate table...\n";
    try {
        // Backup data first (if possible)
        echo "   - Trying to backup data...\n";
        $backup = [];
        try {
            $backup = $conn->query("SELECT * FROM sub_menu")->fetchAll(PDO::FETCH_ASSOC);
            echo "     ✓ Backed up " . count($backup) . " rows\n";
        } catch (Exception $e2) {
            echo "     ✗ Cannot backup: " . $e2->getMessage() . "\n";
        }
        
        // Drop and recreate
        echo "   - Dropping table...\n";
        $conn->exec("DROP TABLE IF EXISTS sub_menu");
        echo "     ✓ Table dropped\n";
        
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
        echo "     ✓ Table created\n";
        
        // Restore data
        if (count($backup) > 0) {
            echo "   - Restoring data...\n";
            $stmt = $conn->prepare("INSERT INTO sub_menu VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($backup as $row) {
                $stmt->execute(array_values($row));
            }
            echo "     ✓ Restored " . count($backup) . " rows\n";
        }
        
        echo "\n✓ Table recreated successfully!\n";
        
    } catch (PDOException $e3) {
        echo "   ✗ Recreate failed: " . $e3->getMessage() . "\n";
    }
}

$conn = $base->close();

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n";
?>
