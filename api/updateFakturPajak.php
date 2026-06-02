<?php
// Required headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: POST');

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
    error_log("API updateFakturPajak.php accessed: " . json_encode($_POST));
    
    // Validate encryption key
    $encrypt = $secu->injection($_POST['encrypt'] ?? '');
    $tgl = date('Y-m-d');
    $source = $data->self_apl();
    $sourceKey = $source['key_apl'];
    $admin = $secu->injection($_POST['admin'] ?? ''); // Tambahkan admin untuk tracking
    
    if (md5($tgl . "#" . $sourceKey) != $encrypt) {
        $json["message"] = "Unauthorized access";
        error_log("Unauthorized access attempt: " . $encrypt);
        goto output_json;
    }
    
    // Get POST data
    $id_tfk = $secu->injection($_POST['id_tfk'] ?? '');
    $status_f_pajak = $secu->injection($_POST['status_f_pajak'] ?? null);
    $upload_f_pajak = $secu->injection($_POST['upload_f_pajak'] ?? null);
    $jenis_faktur = $secu->injection($_POST['jenis_faktur'] ?? 'Cendo & DPE'); // Default ke Cendo & DPE jika tidak ditentukan
    
    // Normalize jenis_faktur - decode HTML entities
    $jenis_faktur = html_entity_decode($jenis_faktur, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Validate required inputs
    if (empty($id_tfk)) {
        $json["message"] = "Data tidak lengkap: ID Faktur tidak ditemukan";
        goto output_json;
    }
    
    // PENTING: Jika jenis_faktur adalah PIM, gunakan transaksi_faktur_pim terlebih dahulu
    $tableName = '';
    $old = null;
    
    if ($jenis_faktur === 'PIM') {
        // Jika tipe PIM, cari di tabel PIM terlebih dahulu
        $get = $conn->prepare("SELECT status_f_pajak, upload_f_pajak, kode_tfk FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
        $get->bindParam(':id_tfk', $id_tfk);
        $get->execute();
        $old = $get->fetch(PDO::FETCH_ASSOC);
        
        if ($old) {
            $tableName = 'transaksi_faktur_pim';
            error_log("Faktur PIM ditemukan di tabel transaksi_faktur_pim");
        } else {
            // Jika tidak ditemukan di tabel PIM, coba tabel Cendo & DPE
            error_log("Faktur tidak ditemukan di transaksi_faktur_pim dengan ID: $id_tfk, mencoba di transaksi_faktur");
            $getAlt = $conn->prepare("SELECT status_f_pajak, upload_f_pajak, kode_tfk FROM transaksi_faktur WHERE id_tfk = :id_tfk");
            $getAlt->bindParam(':id_tfk', $id_tfk);
            $getAlt->execute();
            $old = $getAlt->fetch(PDO::FETCH_ASSOC);
            
            if ($old) {
                // Update jenis_faktur karena ditemukan di tabel Cendo & DPE
                $jenis_faktur = 'Cendo & DPE';
                $tableName = 'transaksi_faktur';
                error_log("Faktur ditemukan di tabel transaksi_faktur sebagai Cendo & DPE meskipun dikirim sebagai PIM");
            }
        }
    } else {
        // Jika tipe Cendo & DPE, cari di tabel Cendo & DPE terlebih dahulu
        $get = $conn->prepare("SELECT status_f_pajak, upload_f_pajak, kode_tfk FROM transaksi_faktur WHERE id_tfk = :id_tfk");
        $get->bindParam(':id_tfk', $id_tfk);
        $get->execute();
        $old = $get->fetch(PDO::FETCH_ASSOC);
        
        if ($old) {
            $tableName = 'transaksi_faktur';
            error_log("Faktur Cendo & DPE ditemukan di tabel transaksi_faktur");
        } else {
            // Jika tidak ditemukan di tabel Cendo & DPE, coba tabel PIM
            error_log("Faktur tidak ditemukan di transaksi_faktur dengan ID: $id_tfk, mencoba di transaksi_faktur_pim");
            $getAlt = $conn->prepare("SELECT status_f_pajak, upload_f_pajak, kode_tfk FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
            $getAlt->bindParam(':id_tfk', $id_tfk);
            $getAlt->execute();
            $old = $getAlt->fetch(PDO::FETCH_ASSOC);
            
            if ($old) {
                // Update jenis_faktur karena ditemukan di tabel PIM
                $jenis_faktur = 'PIM';
                $tableName = 'transaksi_faktur_pim';
                error_log("Faktur ditemukan di tabel transaksi_faktur_pim sebagai PIM meskipun dikirim sebagai Cendo & DPE");
            }
        }
    }

    // Jika faktur tidak ditemukan di kedua tabel
    if (!$old) {
        $json["message"] = "Data faktur pajak tidak ditemukan di kedua tabel";
        $json["jenis_faktur"] = $jenis_faktur;
        error_log("Faktur tidak ditemukan di kedua tabel dengan ID: $id_tfk");
        goto output_json;
    }

    // Hanya update field jika parameter dikirim, jika tidak gunakan nilai lama
    if ($status_f_pajak === null) {
        $status_f_pajak = $old['status_f_pajak'];
    }

    // Jika upload_f_pajak tidak dikirim, gunakan nilai lama
    if ($upload_f_pajak === null) {
        $upload_f_pajak = $old['upload_f_pajak'];
    }
    
    error_log("Mengupdate faktur pajak - ID: $id_tfk, Status: $status_f_pajak, Upload: $upload_f_pajak, Tabel: $tableName");
    
    // Buat query yang hanya update field yang diperlukan
    // Jika hanya status_f_pajak yang diubah, jangan ubah upload_f_pajak
    if ($status_f_pajak !== $old['status_f_pajak'] && $upload_f_pajak === $old['upload_f_pajak']) {
        $update = "UPDATE $tableName SET 
                  status_f_pajak = :status,
                  updated_at = NOW(),
                  updated_by = :admin
                  WHERE id_tfk = :id_tfk";
        
        $stmt = $conn->prepare($update);
        $stmt->bindParam(':status', $status_f_pajak);
        $stmt->bindParam(':id_tfk', $id_tfk);
        $stmt->bindParam(':admin', $admin);
    } 
    // Jika hanya upload_f_pajak yang diubah, jangan ubah status_f_pajak
    else if ($status_f_pajak === $old['status_f_pajak'] && $upload_f_pajak !== $old['upload_f_pajak']) {
        $update = "UPDATE $tableName SET 
                  upload_f_pajak = :upload_status,
                  updated_at = NOW(),
                  updated_by = :admin
                  WHERE id_tfk = :id_tfk";
        
        $stmt = $conn->prepare($update);
        $stmt->bindParam(':upload_status', $upload_f_pajak);
        $stmt->bindParam(':id_tfk', $id_tfk);
        $stmt->bindParam(':admin', $admin);
    }
    // Jika keduanya diubah, update keduanya
    else {
        $update = "UPDATE $tableName SET 
                  status_f_pajak = :status, 
                  upload_f_pajak = :upload_status,
                  updated_at = NOW(),
                  updated_by = :admin
                  WHERE id_tfk = :id_tfk";
        
        $stmt = $conn->prepare($update);
        $stmt->bindParam(':status', $status_f_pajak);
        $stmt->bindParam(':upload_status', $upload_f_pajak);
        $stmt->bindParam(':id_tfk', $id_tfk);
        $stmt->bindParam(':admin', $admin);
    }
    
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $json["success"] = true;
        $json["message"] = "Status faktur pajak berhasil diupdate";
        $json["kode_tfk"] = $old['kode_tfk']; // Return invoice code for reference
        $json["jenis_faktur"] = $jenis_faktur; // Return invoice type for reference
        error_log("Berhasil mengupdate faktur pajak dengan ID: $id_tfk di tabel $tableName");
    } else {
        // Check if the record exists
        $check = $conn->prepare("SELECT id_tfk FROM $tableName WHERE id_tfk = :id_tfk");
        $check->bindParam(':id_tfk', $id_tfk);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            $json["success"] = true;
            $json["message"] = "Tidak ada perubahan data";
            $json["kode_tfk"] = $old['kode_tfk'];
            $json["jenis_faktur"] = $jenis_faktur;
            error_log("Tidak ada perubahan untuk faktur pajak dengan ID: $id_tfk");
        } else {
            $json["message"] = "Data faktur pajak tidak ditemukan";
            error_log("Faktur pajak dengan ID: $id_tfk tidak ditemukan");
        }
    }
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("API updateFakturPajak.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();
echo json_encode($json);
exit;
?>