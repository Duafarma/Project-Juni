<?php
require_once('config/connection/connection.php');
$base = new DB;
$conn = $base->open();

echo "=== Struktur Tabel program_promo ===\n";
$stmt = $conn->query("DESCRIBE program_promo");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | Key: ' . $row['Key'] . "\n";
}

echo "\n=== Data di tabel ===\n";
$data = $conn->query("SELECT * FROM program_promo LIMIT 5");
while($row = $data->fetch(PDO::FETCH_ASSOC)) {
    print_r($row);
}

$conn = $base->close();
?>
