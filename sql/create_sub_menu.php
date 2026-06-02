<?php
/**
 * Create sub_menu table
 * Run: php create_sub_menu.php
 */

require_once('../config/connection/connection.php');

$base = new DB;
$conn = $base->open();

echo "========================================\n";
echo "Creating sub_menu table...\n";
echo "========================================\n\n";

try {
    // Read SQL file
    $sql = file_get_contents(__DIR__ . '/create_sub_menu.sql');
    
    // Execute SQL
    $conn->exec($sql);
    
    echo "✓ Table 'sub_menu' created successfully!\n\n";
    
    // Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'sub_menu'")->fetch();
    if ($check) {
        echo "✓ Table verified: sub_menu exists\n";
        
        // Show table structure
        echo "\n--- Table Structure ---\n";
        $describe = $conn->query("DESCRIBE sub_menu")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($describe as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "✗ Table verification failed\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

$conn = $base->close();

echo "\n========================================\n";
echo "Done!\n";
echo "========================================\n";
?>
