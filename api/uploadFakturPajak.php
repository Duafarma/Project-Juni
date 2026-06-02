<?php
// Required headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');

ini_set('display_errors', 0);
error_reporting(E_ERROR);
ob_start();

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
$base   = new DB;
$secu   = new Security;
$data   = new Data;
$conn   = $base->open(); 

// Initialize response
$json = [
    "success" => false,
    "message" => "Invalid request"
];

try {
    // For debugging
    error_log("API uploadFakturPajak.php accessed: " . json_encode($_POST));
    
    // Validate encryption key
    $encrypt = $secu->injection($_POST['encrypt'] ?? '');
    $tgl = date('Y-m-d');
    $source = $data->self_apl();
    $sourceKey = $source['key_apl'];
    $sistem = $data->sistem('url_sis');
    
    if (md5($tgl . "#" . $sourceKey) != $encrypt) {
        $json["message"] = "Unauthorized access";
        goto output_json;
    }
    
    // Get data from request
    $id_tfk = $secu->injection($_POST['id_tfk'] ?? '');
    $admin = $secu->injection($_POST['admin'] ?? '');
    $keterangan = $secu->injection($_POST['keterangan'] ?? '');
    
    // Validate required parameters
    if (empty($id_tfk)) {
        $json["message"] = "ID Faktur tidak valid";
        goto output_json;
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['file_faktur']) || $_FILES['file_faktur']['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Tidak ada file yang diunggah";
        if (isset($_FILES['file_faktur'])) {
            switch ($_FILES['file_faktur']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $error_message = "File terlalu besar (melebihi batas upload_max_filesize di php.ini)";
                    break;
                // ... other error cases
            }
        }
        $json["message"] = $error_message;
        goto output_json;
    }
    
    // Function to generate next UPJ ID
    function generateUPJID($conn) {
        // Gunakan kombinasi timestamp + random untuk menjamin keunikan
        $timestamp = time();
        $random = mt_rand(10000, 99999);
        
        // Buat ID yang hampir pasti unik
        $uniqueId = 'UPJ' . substr(str_pad($timestamp . $random, 10, '0'), -7);
        
        // Double check apakah ID sudah ada di database
        $checkQuery = "SELECT id_tfb FROM upload_f_pajak WHERE id_tfb = :id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':id', $uniqueId);
        $checkStmt->execute();
        
        // Jika masih konflik (sangat tidak mungkin), tambahkan angka random lagi
        if ($checkStmt->rowCount() > 0) {
            $uniqueId = 'UPJ' . substr(str_pad($timestamp . mt_rand(10000, 99999), 10, '0'), -7);
        }
        
        return $uniqueId;
    }
    
    // Validate file
    $file = $_FILES['file_faktur'];
    $allowed_types = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB

    // Get file info
    $file_name = $file['name'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_type = $file['type'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate file type
    if (!in_array($file_type, $allowed_types)) {
        $json["message"] = "Format file tidak diizinkan. Hanya PDF, JPG, dan PNG yang diperbolehkan.";
        goto output_json;
    }

    // Validate file size
    if ($file_size > $max_size) {
        $json["message"] = "Ukuran file terlalu besar. Maksimum 5MB.";
        goto output_json;
    }

    // Create upload directory path
    $upload_dir = '../assets/uploads/faktur_pajak/';
    $full_upload_dir = realpath(dirname(__FILE__) . '/../') . '/assets/uploads/faktur_pajak/';

    // Check if directory exists and is writable
    if (!file_exists($full_upload_dir)) {
        if (!mkdir($full_upload_dir, 0777, true)) {
            $error = error_get_last();
            $json["message"] = "Gagal membuat direktori upload: " . ($error ? $error['message'] : 'Unknown error');
            goto output_json;
        }
        chmod($full_upload_dir, 0777);
    } elseif (!is_writable($full_upload_dir)) {
        chmod($full_upload_dir, 0777);
        if (!is_writable($full_upload_dir)) {
            $json["message"] = "Direktori upload tidak dapat ditulis";
            goto output_json;
        }
    }

    // Generate unique filename
    $new_filename = 'faktur_' . preg_replace('/[^a-zA-Z0-9]/', '_', $id_tfk) . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    $full_upload_path = $full_upload_dir . '/' . $new_filename;

    // Try to upload the file
    if (!move_uploaded_file($file_tmp, $full_upload_path)) {
        $error = error_get_last();
        $json["message"] = "Gagal mengunggah file. Error: " . ($error ? $error['message'] : 'Unknown error');
        goto output_json;
    }

    // Ensure the file was actually uploaded
    if (!file_exists($full_upload_path)) {
        $json["message"] = "File berhasil diunggah tetapi tidak ditemukan di server";
        goto output_json;
    }

    // Database operations
    try {
        // Database operations
        $conn->beginTransaction();
        
        // Generate unique ID
        $id_tfb = generateUPJID($conn);
        error_log("API using ID: $id_tfb");
        
        // Create new record in upload_f_pajak
        $insert_main = "INSERT INTO upload_f_pajak (id_tfb, tanggal_u_p, created_by, created_at) 
                        VALUES (:id_tfb, CURDATE(), :created_by, NOW())";
        $stmt_main = $conn->prepare($insert_main);
        $stmt_main->bindParam(':id_tfb', $id_tfb);
        $stmt_main->bindParam(':created_by', $admin);
        $stmt_main->execute();
        
        // Insert into upload_f_pajak_detail
        $insert_detail = "INSERT INTO upload_f_pajak_detail 
                         (id_tfb, no_faktur, ket, upload_f_pajak, url_upload, file_type, file_size, created_by, created_at) 
                         VALUES (:id_tfb, :id_tfk, :ket, 'sudah', :url_upload, :file_type, :file_size, :created_by, NOW())";
        
        $stmt_detail = $conn->prepare($insert_detail);
        $stmt_detail->bindParam(':id_tfb', $id_tfb);
        $stmt_detail->bindParam(':id_tfk', $id_tfk);
        $stmt_detail->bindParam(':ket', $keterangan);
        $stmt_detail->bindParam(':url_upload', $new_filename);
        $stmt_detail->bindParam(':file_type', $file_ext);
        $stmt_detail->bindParam(':file_size', $file_size);
        $stmt_detail->bindParam(':created_by', $admin);
        $stmt_detail->execute();
        
        // Check which table contains this ID
        $check_tfk = $conn->prepare("SELECT id_tfk FROM transaksi_faktur WHERE id_tfk = :id_tfk");
        $check_tfk->bindParam(':id_tfk', $id_tfk);
        $check_tfk->execute();
        
        $check_tfp = $conn->prepare("SELECT id_tfk FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
        $check_tfp->bindParam(':id_tfk', $id_tfk);
        $check_tfp->execute();
        
        $updated = false;
        
        // Update transaksi_faktur if ID exists there
        if ($check_tfk->rowCount() > 0) {
            $update = "UPDATE transaksi_faktur SET upload_f_pajak = 'sudah' WHERE id_tfk = :id_tfk";
            $stmt_update = $conn->prepare($update);
            $stmt_update->bindParam(':id_tfk', $id_tfk);
            $stmt_update->execute();
            $updated = true;
            error_log("API: Updated transaksi_faktur table for ID: $id_tfk");
        }
        
        // Update transaksi_faktur_pim if ID exists there
        if ($check_tfp->rowCount() > 0) {
            $update_pim = "UPDATE transaksi_faktur_pim SET upload_f_pajak = 'sudah' WHERE id_tfk = :id_tfk";
            $stmt_update_pim = $conn->prepare($update_pim);
            $stmt_update_pim->bindParam(':id_tfk', $id_tfk);
            $stmt_update_pim->execute();
            $updated = true;
            error_log("API: Updated transaksi_faktur_pim table for ID: $id_tfk");
        }
        
        if (!$updated) {
            throw new Exception("ID faktur tidak ditemukan di database");
        }
        
        // Commit transaction
        $conn->commit();
        
        $json["success"] = true;
        $json["message"] = "Dokumen berhasil diunggah via API";
        $json["file_path"] = $sistem . '/assets/uploads/faktur_pajak/' . $new_filename;
        
    } catch (PDOException $e) {
        if (isset($conn) && $conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Delete the uploaded file if database operation fails
        if (isset($full_upload_path) && file_exists($full_upload_path)) {
            unlink($full_upload_path);
        }
        
        $json["message"] = "Database Error: " . $e->getMessage();
        error_log("API uploadFakturPajak.php error: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Delete the uploaded file if database operation fails
    if (isset($full_upload_path) && file_exists($full_upload_path)) {
        unlink($full_upload_path);
    }
    
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("API uploadFakturPajak.php error: " . $e->getMessage());
}

output_json:
if (isset($conn)) {
    $conn = $base->close();
}

// Clear any unexpected output
ob_end_clean();

// Send proper headers and JSON response
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>