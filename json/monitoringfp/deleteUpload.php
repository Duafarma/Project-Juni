<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$base   = new DB;
$secu   = new Security;
$data   = new Data;
$conn   = $base->open();

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
$id_tfbd = $secu->injection($_POST['id_tfbd']);

// Validate required inputs
if (empty($id_tfbd)) {
    $json["message"] = "ID tidak valid";
    goto output_json;
}

try {
    // Start transaction
    $conn->beginTransaction();
    
    // Get file info and invoice number before delete
    $query = "SELECT d.url_upload, d.no_faktur, u.id_tfb
              FROM upload_f_pajak_detail d
              JOIN upload_f_pajak u ON d.id_tfb = u.id_tfb
              WHERE d.id_tfbd = :id_tfbd";
              
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_tfbd', $id_tfbd);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $json["message"] = "Data tidak ditemukan";
        goto output_json;
    }
    
    $file_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $file_path = '../../assets/uploads/faktur_pajak/' . $file_info['url_upload'];
    $id_tfb = $file_info['id_tfb'];
    $no_faktur = $file_info['no_faktur'];  // This holds the id_tfk value
    
    // Delete the file record from database
    $delete = "DELETE FROM upload_f_pajak_detail WHERE id_tfbd = :id_tfbd";
    $stmt_delete = $conn->prepare($delete);
    $stmt_delete->bindParam(':id_tfbd', $id_tfbd);
    $stmt_delete->execute();
    
    // Check if there are more files for this invoice
    $check = "SELECT COUNT(*) AS count FROM upload_f_pajak_detail WHERE id_tfb = :id_tfb";
    $stmt_check = $conn->prepare($check);
    $stmt_check->bindParam(':id_tfb', $id_tfb);
    $stmt_check->execute();
    $remaining = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($remaining['count'] == 0) {
        // If no more files, delete the main record
        // Update the deleting user information when deleting files
        if ($remaining['count'] == 0) {
            // Add a log entry before deletion
            $log_deletion = "INSERT INTO delete_log (table_name, record_id, deleted_by, deleted_at, details) 
                             VALUES ('upload_f_pajak', :id_tfb, :admin, NOW(), :details)";
            $stmt_log = $conn->prepare($log_deletion);
            $stmt_log->bindParam(':id_tfb', $id_tfb);
            $stmt_log->bindParam(':admin', $admin);
            $stmt_log->bindParam(':details', json_encode(['id_tfk' => $id_tfk]));
            $stmt_log->execute();
            
            // Delete the main record
            $delete_main = "DELETE FROM upload_f_pajak WHERE id_tfb = :id_tfb";
            $stmt_delete_main = $conn->prepare($delete_main);
            $stmt_delete_main->bindParam(':id_tfb', $id_tfb);
            $stmt_delete_main->execute();
            
            // Check if there are any other uploads for this invoice number
            $check_other = "SELECT COUNT(*) AS count 
                           FROM upload_f_pajak_detail d
                           JOIN upload_f_pajak u ON d.id_tfb = u.id_tfb
                           WHERE d.no_faktur = :no_faktur";
            $stmt_check_other = $conn->prepare($check_other);
            $stmt_check_other->bindParam(':no_faktur', $no_faktur);
            $stmt_check_other->execute();
            $other_uploads = $stmt_check_other->fetch(PDO::FETCH_ASSOC);
            
            // If this was the last upload for this invoice, update status in transaksi_faktur
            if ($other_uploads['count'] == 0) {
                $update = "UPDATE transaksi_faktur SET upload_f_pajak = 'belum' WHERE id_tfk = :id_tfk";
                $stmt_update = $conn->prepare($update);
                $stmt_update->bindParam(':id_tfk', $no_faktur);  // Use no_faktur which holds the id_tfk
                $stmt_update->execute();
            }
        } else {
            // If just updating one record, update the updated_by and updated_at
            $update_main = "UPDATE upload_f_pajak SET updated_by = :admin, updated_at = NOW() WHERE id_tfb = :id_tfb";
            $stmt_update_main = $conn->prepare($update_main);
            $stmt_update_main->bindParam(':admin', $admin);
            $stmt_update_main->bindParam(':id_tfb', $id_tfb);
            $stmt_update_main->execute();
        }
    }
    
    // Delete the actual file if it exists
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Commit transaction
    $conn->commit();
    
    $json["success"] = true;
    $json["message"] = "Dokumen berhasil dihapus";
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("DeleteUpload.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>