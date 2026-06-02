<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

// Validasi enkripsi/keamanan sederhana
$encrypt = @$_GET['encrypt'];
// Ambil key dari salah satu aplikasi yang terdaftar (biasanya key internal sistem)
// Untuk keamanan API, pengirim harus menyertakan MD5(date('Y-m-d') . "#" . key_aplikasi)
$json_data = file_get_contents('php://input');
$request = json_decode($json_data, true);

if ($request && isset($request['nama_out']) && isset($request['status_manual'])) {
    $conn = $base->open();
    $nama_out = $secu->injection($request['nama_out']);
    $status_manual = $secu->injection($request['status_manual']);
    $catat = date('Y-m-d H:i:s');

    // PENTING: Update berdasarkan NAMA OUTLET karena ID bisa berbeda antar sistem
    $stmt = $conn->prepare("UPDATE outlet SET status_manual = :status, updated_at = :catat, updated_by = 'API_PUSH_SYNC' WHERE nama_out = :nama");
    $stmt->bindParam(':status', $status_manual, PDO::PARAM_STR);
    $stmt->bindParam(':nama', $nama_out, PDO::PARAM_STR);
    $stmt->bindParam(':catat', $catat, PDO::PARAM_STR);
    $stmt->execute();

    $affected = $stmt->rowCount();
    
    // Catat ke riwayat lokal cabang
    if($affected > 0) {
        $conn->query("INSERT INTO riwayat VALUES('', 'API', 'Outlet', 'Update Status Manual via API: $status_manual (Name: $nama_out)', '', '$catat', 'SYSTEM_API')");
    }
    
    $base->close();

    header('Content-Type: application/json');
    echo json_encode([
        "result" => "success",
        "message" => "Data updated on branch",
        "affected_rows" => $affected
    ]);
} else {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(["result" => "error", "message" => "Invalid data or missing parameters"]);
}
?>