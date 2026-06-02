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
$id_tfk = $secu->injection($_POST['id_tfk']);
$cabang = $secu->injection($_POST['cabang']);
$status_f_pajak = $secu->injection($_POST['status_f_pajak']);
$upload_f_pajak = $secu->injection($_POST['upload_f_pajak']);
$jenis_faktur = $secu->injection($_POST['jenis_faktur'] ?? 'Cendo & DPE'); // Added jenis_faktur

// Normalize jenis_faktur - decode HTML entities
$jenis_faktur = html_entity_decode($jenis_faktur, ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Log received data for debugging
error_log("Update request received - ID: $id_tfk, Cabang: $cabang, Status: $status_f_pajak, Upload: $upload_f_pajak, Jenis: $jenis_faktur");

// Validate required inputs
if (empty($id_tfk) || empty($status_f_pajak) || empty($upload_f_pajak)) {
    $json["message"] = "Data tidak lengkap";
    goto output_json;
}

// Get the current cabang info
$source = $data->self_apl();
$local_cabang = $source['nama_apl'];

// Check if we need to process API or local data
$isLocalCabang = ($cabang == $local_cabang);

try {
    if ($isLocalCabang) {
        // Local update
        error_log("Processing local update for ID: $id_tfk");
        
        // Determine the appropriate table based on jenis_faktur
        $primaryTable = ($jenis_faktur === 'PIM') ? 'transaksi_faktur_pim' : 'transaksi_faktur';
        $alternativeTable = ($jenis_faktur === 'PIM') ? 'transaksi_faktur' : 'transaksi_faktur_pim';
        
        // First check if the invoice exists in the primary table
        $check = $conn->prepare("SELECT id_tfk FROM $primaryTable WHERE id_tfk = :id_tfk");
        $check->bindParam(':id_tfk', $id_tfk);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            // Invoice exists in the primary table, update it
            $update = "UPDATE $primaryTable SET 
                      status_f_pajak = :status, 
                      upload_f_pajak = :upload_status,
                      updated_at = NOW(),
                      updated_by = :admin
                      WHERE id_tfk = :id_tfk";
                      
            $stmt = $conn->prepare($update);
            $stmt->bindParam(':status', $status_f_pajak);
            $stmt->bindParam(':upload_status', $upload_f_pajak);
            $stmt->bindParam(':admin', $admin);
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->execute();
            
            $rowsUpdated = $stmt->rowCount();
            error_log("Updated $rowsUpdated rows in $primaryTable");
            
            $json["success"] = true;
            $json["message"] = "Status faktur pajak berhasil diupdate";
            $json["jenis_faktur"] = $jenis_faktur;
        } else {
            // If not found in primary table, check the alternative table
            $altCheck = $conn->prepare("SELECT id_tfk FROM $alternativeTable WHERE id_tfk = :id_tfk");
            $altCheck->bindParam(':id_tfk', $id_tfk);
            $altCheck->execute();
            
            if ($altCheck->rowCount() > 0) {
                // Invoice exists in the alternative table, update it
                $update = "UPDATE $alternativeTable SET 
                          status_f_pajak = :status, 
                          upload_f_pajak = :upload_status,
                          updated_at = NOW(),
                          updated_by = :admin
                          WHERE id_tfk = :id_tfk";
                          
                $stmt = $conn->prepare($update);
                $stmt->bindParam(':status', $status_f_pajak);
                $stmt->bindParam(':upload_status', $upload_f_pajak);
                $stmt->bindParam(':admin', $admin);
                $stmt->bindParam(':id_tfk', $id_tfk);
                $stmt->execute();
                
                $rowsUpdated = $stmt->rowCount();
                error_log("Updated $rowsUpdated rows in $alternativeTable");
                
                // Update jenis_faktur based on the table where we found it
                $alternativeType = ($alternativeTable === 'transaksi_faktur_pim') ? 'PIM' : 'Cendo & DPE';
                
                $json["success"] = true;
                $json["message"] = "Status faktur pajak berhasil diupdate";
                $json["jenis_faktur"] = $alternativeType;
            } else {
                $json["message"] = "Data faktur pajak tidak ditemukan di kedua tabel";
            }
        }
    } else {
        // Remote update via API
        error_log("Processing remote update for ID: $id_tfk, Cabang: $cabang");
        
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
        
        // First verify if the invoice exists in the remote system
        $check_api_url = rtrim($cabangData['base_url_apl'], '/') . '/api/checkInvoice.php';
        
        // Set up check data
        $check_data = [
            'id_tfk' => $id_tfk,
            'jenis_faktur' => $jenis_faktur,
            'encrypt' => $encrypt
        ];
        
        error_log("Checking invoice existence at: " . $check_api_url);
        
        // Send verification request
        $ch_check = curl_init();
        curl_setopt($ch_check, CURLOPT_URL, $check_api_url);
        curl_setopt($ch_check, CURLOPT_POST, 1);
        curl_setopt($ch_check, CURLOPT_POSTFIELDS, http_build_query($check_data));
        curl_setopt($ch_check, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch_check, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch_check, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch_check, CURLOPT_SSL_VERIFYPEER, 0);
        
        $check_response = curl_exec($ch_check);
        $check_error = curl_error($ch_check);
        curl_close($ch_check);
        
        if ($check_error) {
            $json["message"] = "Error verifying invoice: " . $check_error;
            goto output_json;
        }
        
        $check_result = json_decode($check_response, true);
        
        // If invoice exists, proceed with update
        if (isset($check_result['exists']) && $check_result['exists'] === true) {
            // If the check API found the invoice in a different type/table, use that
            if (isset($check_result['jenis_faktur'])) {
                $jenis_faktur = $check_result['jenis_faktur'];
                error_log("Using jenis_faktur from remote system: $jenis_faktur");
            }
            
            // Prepare API data with the correct jenis_faktur
            $api_data = [
                'id_tfk' => $id_tfk,
                'status_f_pajak' => $status_f_pajak,
                'upload_f_pajak' => $upload_f_pajak,
                'jenis_faktur' => $jenis_faktur,  // Include the (potentially updated) jenis_faktur
                'encrypt' => $encrypt,
                'admin' => $admin
            ];
            
            // Build API URL - ensure no trailing slash issues
            $base_url = rtrim($cabangData['base_url_apl'], '/');
            $api_url = $base_url . '/api/updateFakturPajak.php';
            
            // Debug logging
            error_log("Sending update to: " . $api_url);
            error_log("Update data: " . json_encode($api_data));
            
            // Configure and send the cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($api_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Accept: application/json'
            ]);
            
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
                $json["message"] = "Status faktur pajak berhasil diupdate di " . $cabang;
                $json["jenis_faktur"] = $api_result['jenis_faktur'] ?? $jenis_faktur;
                $json["kode_tfk"] = $api_result['kode_tfk'] ?? '';
            } else {
                $message = isset($api_result['message']) ? $api_result['message'] : 'Unknown error';
                $json["message"] = "API Error: " . $message;
            }
        } else {
            // Invoice doesn't exist or check failed
            $json["message"] = "Data faktur pajak tidak ditemukan di cabang $cabang";
            goto output_json;
        }
    }
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("updatefaktur.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>