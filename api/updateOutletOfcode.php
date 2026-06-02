<?php
// API Endpoint untuk Update Ofcode Outlet dari Sistem Lain
// Mengikuti pola API yang sudah ada di sistem

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

// Check encryption key
$encrypt = $secu->injection($_POST['encrypt'] ?? '');
$tgl = date('Y-m-d');
$source = $data->self_apl();
$sourceKey = $source['key_apl'];

if (md5($tgl . "#" . $sourceKey) != $encrypt) {
    http_response_code(401);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized - Invalid encryption key"
    ]);
    exit;
}

// Validasi parameter
$nama_out = $secu->injection($_POST['nama_out'] ?? '');
$ofcode_out = $secu->injection($_POST['ofcode_out'] ?? '');
$admin = $secu->injection($_POST['admin'] ?? 'API_SYNC');
$catat = date('Y-m-d H:i:s');

// Validasi required fields
if (empty($nama_out) || empty($ofcode_out)) {
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: nama_out and ofcode_out are required"
    ]);
    exit;
}

$conn = $base->open();

try {
    // Update semua outlet dengan nama_out yang sama
    $edit = $conn->prepare("UPDATE outlet SET ofcode_out=:ofcode, updated_at=:catat, updated_by=:admin WHERE nama_out=:nama_out");
    $edit->bindParam(":nama_out", $nama_out, PDO::PARAM_STR);
    $edit->bindParam(":ofcode", $ofcode_out, PDO::PARAM_STR);
    $edit->bindParam(":catat", $catat, PDO::PARAM_STR);
    $edit->bindParam(":admin", $admin, PDO::PARAM_STR);
    $edit->execute();
    
    $affectedRows = $edit->rowCount();
    
    // Catat riwayat
    $conn->query("INSERT INTO riwayat VALUES('', '', 'Ofcode API Update', 'Update via API - nama_out: $nama_out, ofcode: $ofcode_out, affected: $affectedRows rows', '', '$catat', '$admin')");
    
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => true,
        "message" => "Updated $affectedRows outlets with nama_out: $nama_out",
        "affected_rows" => $affectedRows,
        "data" => [
            "nama_out" => $nama_out,
            "ofcode_out" => $ofcode_out,
            "updated_at" => $catat,
            "updated_by" => $admin
        ]
    ]);
    
} catch (Exception $e) {
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ]);
}

$conn = $base->close();
?>
