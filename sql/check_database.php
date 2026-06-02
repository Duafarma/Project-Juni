<?php
require_once('../config/connection/connection.php');

$base = new DB;
$conn = $base->open();

$db = $conn->query('SELECT DATABASE()')->fetchColumn();
echo "Current Database: $db\n";

// Show all tables
echo "\nTables in database:\n";
$tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    echo "  - $table\n";
}

// Check if sub_menu exists
echo "\nChecking sub_menu:\n";
try {
    $check = $conn->query("SHOW TABLES LIKE 'sub_menu'")->fetch();
    if ($check) {
        echo "✓ sub_menu exists\n";
    } else {
        echo "✗ sub_menu NOT found\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

$conn = $base->close();
?>
