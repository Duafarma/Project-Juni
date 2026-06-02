<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
        "message" => "Unauthorized"
    ]);
    exit;
}

$id_out = $secu->injection($_POST['id_out'] ?? '');
$profit = $secu->injection($_POST['profit'] ?? '');
$admin  = $secu->injection($_POST['admin'] ?? '');
$catat  = date('Y-m-d H:i:s');

if(empty($id_out) || $profit === '') {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit;
}

$edit = $conn->prepare("UPDATE outlet SET profit=:profit, updated_at=:catat, updated_by=:admin WHERE id_out=:id_out");
$edit->bindParam(":id_out", $id_out, PDO::PARAM_STR);
$edit->bindParam(":profit", $profit, PDO::PARAM_STR);
$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
$success = $edit->execute();

if($success) {
    $conn->query("INSERT INTO riwayat VALUES('', '$id_out', 'Margin Profit', 'Update', '', '$catat', '$admin')");
}

header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode([
    "success" => $success,
    "message" => $success ? "Update success" : "Update failed"
]);
$conn = $base->close();
?>