<?php
	header('Access-Control-Allow-Origin: *');
	header('Content-Type: application/json');
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	$conn	= $base->open();

$id_mg = isset($_GET['id_mg']) ? $secu->injection($_GET['id_mg']) : null;

error_log("id_mg: " . $id_mg);

if (!$id_mg) {
    echo json_encode(['success' => false, 'message' => 'ID grup tidak valid']);
    $conn = $base->close();
    exit;
}

$query = "SELECT id_out, nama_out FROM outlet WHERE id_mg = :id_mg ORDER BY nama_out ASC";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id_mg', $id_mg, PDO::PARAM_STR);

if ($stmt->execute()) {
    $outlets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Outlets: " . json_encode($outlets)); // Log hasil query
    echo json_encode(['success' => true, 'outlets' => $outlets]);
} else {
    echo json_encode(['success' => false, 'message' => 'Query gagal dijalankan.']);
}

$conn = $base->close();
exit;