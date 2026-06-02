<?php
header('Content-Type: application/json');
require_once '../config/connection/connection.php'; // Pastikan path sesuai struktur Anda

$id_cabang = isset($_GET['id_cabang']) ? $_GET['id_cabang'] : '';

try {
    $sql = "SELECT * FROM cabang WHERE id_cabang != :id_cabang ORDER BY nama_cabang ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id_cabang', $id_cabang, PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'status' => 'success',
        'data' => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
