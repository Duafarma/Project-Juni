<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$base   = new DB;
$secu   = new Security;
$data   = new Data;
$conn   = $base->open();
$sistem = $data->sistem('url_sis');

// Access data
$admin  = $secu->injection(@$_COOKIE['adminkuy']);
$kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
$valid  = $secu->validadmin($admin, $kunci);

// Initialize response
$json = [
    "success" => false,
    "message" => "Invalid request",
    "data" => []
];

// Check if user is logged in
if ($valid == false) {
    $json["message"] = "Session login anda habis...";
    goto output_json;
}

// Get request data
$id_tfk = $secu->injection($_GET['id_tfk']);

// Validate required inputs
if (empty($id_tfk)) {
    $json["message"] = "ID transaksi tidak valid";
    goto output_json;
}

try {
    // Get upload history - looking for no_faktur in upload_f_pajak_detail that matches id_tfk
    $query = "SELECT 
              d.id_tfbd, d.no_faktur, d.ket, d.upload_f_pajak, d.url_upload, 
              d.file_type, d.file_size, d.created_at, u.tanggal_u_p
              FROM upload_f_pajak u
              JOIN upload_f_pajak_detail d ON u.id_tfb = d.id_tfb
              WHERE d.no_faktur = :id_tfk
              ORDER BY d.created_at DESC";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_tfk', $id_tfk);
    $stmt->execute();
    
    $result = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format the URL to be a complete path
        $row['url_upload'] = $sistem . '/assets/uploads/faktur_pajak/' . $row['url_upload'];
        
        // Format file size
        if ($row['file_size'] < 1024 * 1024) {
            $row['formatted_size'] = round($row['file_size'] / 1024, 2) . ' KB';
        } else {
            $row['formatted_size'] = round($row['file_size'] / (1024 * 1024), 2) . ' MB';
        }
        
        $result[] = $row;
    }
    
    $json["success"] = true;
    $json["message"] = "";
    $json["data"] = $result;
    
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("GetUploadHistory.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();

// Clear any unexpected output
ob_end_clean();

// Send proper headers and JSON response
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>