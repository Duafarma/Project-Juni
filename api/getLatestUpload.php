<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

// Validate encryption key
$encrypt = $secu->injection($_GET['encrypt'] ?? '');
$id_tfk = $secu->injection($_GET['id_tfk'] ?? '');
$tgl = date('Y-m-d');
$source = $data->self_apl();
$sourceKey = $source['key_apl'];

if (md5($tgl . "#" . $sourceKey) != $encrypt) {
    http_response_code(401);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized access",
        "url" => ""
    ]);
    exit;
}

try {
    // Get latest uploaded document for this invoice
    $query = "SELECT url_upload, file_type
              FROM upload_f_pajak_detail 
              WHERE no_faktur = :id_tfk
              ORDER BY created_at DESC 
              LIMIT 1";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_tfk', $id_tfk);
    $stmt->execute();
    
    $response = [
        "success" => false,
        "message" => "Dokumen tidak ditemukan",
        "url" => ""
    ];
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $baseUrl = $data->sistem('url_sis');
        $response["url"] = $baseUrl . '/assets/uploads/faktur_pajak/' . $row['url_upload'];
        $response["file_type"] = $row['file_type'];
        $response["success"] = true;
        $response["message"] = "";
    }
    
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log("Database Error in getLatestUpload.php: " . $e->getMessage());
    http_response_code(500);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Database error",
        "url" => ""
    ]);
} finally {
    $conn = $base->close();
}
?>