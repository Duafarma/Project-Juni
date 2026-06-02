<?php
require_once('config/connection/connection.php');
$base = new DB;
$conn = $base->open();

try {
    // Add dokumen_ole column to outlet_legal table
    $conn->exec("ALTER TABLE outlet_legal ADD COLUMN dokumen_ole VARCHAR(255) DEFAULT NULL AFTER expired_ole");
    echo "Column 'dokumen_ole' added successfully to outlet_legal table.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Column 'dokumen_ole' already exists.<br>";
    } else {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

// Create directory for legal documents
$dir = 'berkas/legal/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    echo "Directory '$dir' created.<br>";
} else {
    echo "Directory '$dir' already exists.<br>";
}

echo "<br>Done!";
?>
