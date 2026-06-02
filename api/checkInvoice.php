<?php
// Required headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
$base = new DB;
$secu = new Security;
$data = new Data;
$conn = $base->open();

// Initialize response
$json = [
    "exists" => false,
    "message" => "Invalid request"
];

try {
    // Validate encryption key for security
    $tgl = date('Y-m-d');
    $encrypt = $secu->injection($_POST['encrypt'] ?? '');
    
    // Get application key from database
    $source = $data->self_apl();
    $key_apl = $source['key_apl'];
    
    $validKey = md5($tgl . "#" . $key_apl);
    $valid = ($encrypt === $validKey);

    if (!$valid) {
        $json["message"] = "Invalid authentication";
        goto output_json;
    }

    // Get POST data
    $id_tfk = $secu->injection($_POST['id_tfk'] ?? '');
    $jenis_faktur = $secu->injection($_POST['jenis_faktur'] ?? 'Cendo & DPE');

    // Normalize jenis_faktur
    $jenis_faktur = html_entity_decode($jenis_faktur, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Validate input
    if (empty($id_tfk)) {
        $json["message"] = "ID transaksi tidak valid";
        goto output_json;
    }

    // Choose table based on jenis_faktur
    $tableName = ($jenis_faktur === 'PIM') ? 'transaksi_faktur_pim' : 'transaksi_faktur';
    
    // Try primary table first
    $stmt = $conn->prepare("SELECT id_tfk, kode_tfk, upload_f_pajak FROM $tableName WHERE id_tfk = :id_tfk LIMIT 1");
    $stmt->bindParam(':id_tfk', $id_tfk);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        $json["exists"] = true;
        $json["message"] = "Faktur ditemukan";
        $json["kode_tfk"] = $row['kode_tfk'];
        $json["upload_f_pajak"] = $row['upload_f_pajak'] ?? 'belum';
        $json["jenis_faktur"] = $jenis_faktur;
    } else {
        // If not found in primary table, try alternative table
        $alternativeTable = ($jenis_faktur === 'PIM') ? 'transaksi_faktur' : 'transaksi_faktur_pim';
        $alternativeType = ($jenis_faktur === 'PIM') ? 'Cendo & DPE' : 'PIM';
        
        $stmt = $conn->prepare("SELECT id_tfk, kode_tfk, upload_f_pajak FROM $alternativeTable WHERE id_tfk = :id_tfk LIMIT 1");
        $stmt->bindParam(':id_tfk', $id_tfk);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            $json["exists"] = true;
            $json["message"] = "Faktur ditemukan (sebagai $alternativeType)";
            $json["kode_tfk"] = $row['kode_tfk'];
            $json["upload_f_pajak"] = $row['upload_f_pajak'] ?? 'belum';
            $json["jenis_faktur"] = $alternativeType;
        } else {
            $json["message"] = "Faktur dengan ID $id_tfk tidak ditemukan di kedua tabel";
        }
    }
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("checkInvoice.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();
echo json_encode($json);
exit;
?>