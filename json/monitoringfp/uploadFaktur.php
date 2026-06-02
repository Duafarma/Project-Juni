<?php
ini_set('display_errors', 0);
error_reporting(E_ERROR);
ob_start();

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
    "message" => "Invalid request"
];

// Check if user is logged in
if ($valid == false) {
    $json["message"] = "Session login anda habis...";
    goto output_json;
}

// Get POST data
$id_tfk = $secu->injection($_POST['id_tfk']);
$cabang = $secu->injection($_POST['cabang']);
$kode_tfk = $secu->injection($_POST['no_faktur']); 
$keterangan = $secu->injection($_POST['keterangan']);

// Validate required inputs
if (empty($id_tfk)) {
    $json["message"] = "ID Faktur tidak boleh kosong";
    goto output_json;
}

// Check if file was uploaded
if (!isset($_FILES['file_faktur']) || $_FILES['file_faktur']['error'] !== UPLOAD_ERR_OK) {
    // Provide more specific error messages
    $error_message = "Tidak ada file yang diunggah";
    if (isset($_FILES['file_faktur'])) {
        switch ($_FILES['file_faktur']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $error_message = "File terlalu besar (melebihi batas upload_max_filesize di php.ini)";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = "File terlalu besar (melebihi batas MAX_FILE_SIZE di form)";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = "File hanya terunggah sebagian";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $error_message = "Tidak ada direktori temporary";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $error_message = "Gagal menulis file ke disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $error_message = "Upload dihentikan oleh ekstensi PHP";
                break;
        }
    }
    $json["message"] = $error_message;
    goto output_json;
}

// BARU: Cek apakah ini upload ke cabang lain berdasarkan parameter cabang
$source = $data->self_apl();
$local_cabang = $source['nama_apl'];
$isLocalCabang = ($cabang == $local_cabang);

// Jika bukan cabang lokal, lakukan upload via API
if (!$isLocalCabang) {
    error_log("Uploading to remote branch: $cabang (ID: $id_tfk)");
    
    // Get the API details for the specified cabang
    $stmt = $conn->prepare("SELECT id_apl, base_url_apl, key_apl FROM aplikasi WHERE nama_apl = :cabang LIMIT 1");
    $stmt->bindParam(':cabang', $cabang);
    $stmt->execute();
    $cabangData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cabangData || empty($cabangData['base_url_apl'])) {
        $json["message"] = "Cabang API tidak ditemukan";
        goto output_json;
    }
    
    // Generate API authentication
    $tgl = date('Y-m-d');
    $encrypt = md5($tgl . "#" . $cabangData['key_apl']);
    
    // Build API URL
    $base_url = rtrim($cabangData['base_url_apl'], '/');
    $api_url = $base_url . '/api/uploadFakturPajak.php';
    
    // Prepare the file for upload
    $file = $_FILES['file_faktur'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_type = $file['type'];
    
    // Create cURL file object
    $cfile = new CURLFile($file_tmp, $file_type, $file_name);
    
    // Prepare API data
    $api_data = [
        'id_tfk' => $id_tfk,
        'keterangan' => $keterangan,
        'encrypt' => $encrypt,
        'admin' => $admin,
        'file_faktur' => $cfile
    ];
    
    // Set up cURL for file upload
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $api_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Longer timeout for file upload
    
    // Important: Follow redirects and disable SSL verification for development
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    
    error_log("Sending upload request to: " . $api_url);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log detailed information for debugging
    error_log("API HTTP Code: " . $http_code);
    error_log("API Response: " . $response);
    
    if ($curl_error) {
        $json["message"] = "API Error: " . $curl_error;
        goto output_json;
    }
    
    if ($http_code != 200) {
        $json["message"] = "API Error: HTTP code " . $http_code;
        goto output_json;
    }
    
    // Process API response
    $api_result = json_decode($response, true);
    
    if (isset($api_result['success']) && $api_result['success'] === true) {
        $json["success"] = true;
        $json["message"] = "Dokumen berhasil diunggah ke " . $cabang;
        $json["file_path"] = $api_result['file_path'] ?? '';
    } else {
        $message = isset($api_result['message']) ? $api_result['message'] : 'Unknown error';
        $json["message"] = "API Error: " . $message;
    }
    
    goto output_json;
}

// Semua kode yang ada untuk upload lokal tetap dipertahankan di sini
// Function to generate next UPJ ID
function generateUPJID($conn) {
    // Pendekatan 1: Coba menggunakan timestamp + random (hampir pasti unik)
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    $uniqueId = 'UPJ' . substr(str_pad($timestamp . $random, 10, '0'), -7);
    
    // Periksa keberadaan ID di database
    $checkQuery = "SELECT COUNT(*) FROM upload_f_pajak WHERE id_tfb = :id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':id', $uniqueId);
    $checkStmt->execute();
    
    // Jika ID sudah ada (sangat kecil kemungkinannya), buatkan yang baru
    if ($checkStmt->fetchColumn() > 0) {
        // Coba dengan pendekatan lain: gunakan microtime untuk presisi lebih tinggi
        $microtime = microtime(true);
        $random = mt_rand(5000, 9999);
        $uniqueId = 'UPJ' . substr(str_pad(str_replace('.', '', $microtime) . $random, 10, '0'), -7);
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
$upload_dir = '../../assets/uploads/faktur_pajak/';
$full_upload_dir = realpath(dirname(__FILE__) . '/../../') . '/assets/uploads/faktur_pajak/';

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

// Generate unique filename - use id_tfk instead of kode_tfk
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

try {
    $conn->beginTransaction();
    
    // Generate ID yang dijamin unik
    $id_tfb = generateUPJID($conn);
    error_log("Menggunakan ID: $id_tfb");
    
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
        error_log("Updated transaksi_faktur table for ID: $id_tfk");
    }
    
    // Update transaksi_faktur_pim if ID exists there
    if ($check_tfp->rowCount() > 0) {
        $update_pim = "UPDATE transaksi_faktur_pim SET upload_f_pajak = 'sudah' WHERE id_tfk = :id_tfk";
        $stmt_update_pim = $conn->prepare($update_pim);
        $stmt_update_pim->bindParam(':id_tfk', $id_tfk);
        $stmt_update_pim->execute();
        $updated = true;
        error_log("Updated transaksi_faktur_pim table for ID: $id_tfk");
    }
    
    if (!$updated) {
        error_log("Warning: ID $id_tfk not found in either transaksi_faktur or transaksi_faktur_pim");
    }
    
    // Commit transaction
    $conn->commit();
    
    $json["success"] = true;
    $json["message"] = "Dokumen berhasil diunggah";
    $json["file_path"] = $sistem . '/assets/uploads/faktur_pajak/' . $new_filename;
    
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    // Delete the uploaded file if database operation fails
    if (file_exists($full_upload_path)) {
        unlink($full_upload_path);
    }
    
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("UploadFaktur.php error: " . $e->getMessage());
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