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
$upload_f_pajak = $secu->injection($_POST['upload_f_pajak']);
$jenis_faktur = $secu->injection($_POST['jenis_faktur'] ?? 'Cendo & DPE');

// Always normalize jenis_faktur with html_entity_decode to handle all cases
$jenis_faktur = html_entity_decode($jenis_faktur, ENT_QUOTES | ENT_HTML5, 'UTF-8');

error_log("Upload status update request - ID: $id_tfk, Cabang: $cabang, Status: $upload_f_pajak, Jenis: $jenis_faktur");

// Validasi input yang diperlukan
if (empty($id_tfk) || empty($upload_f_pajak)) {
    $json["message"] = "Data tidak lengkap";
    goto output_json;
}

// Dapatkan info cabang saat ini
$source = $data->self_apl();
$local_cabang = $source['nama_apl'];

// Periksa apakah kita perlu memproses API atau data lokal
$isLocalCabang = ($cabang == $local_cabang);

try {
    if ($isLocalCabang) {
        // Update lokal
        // Check in both tables regardless of jenis_faktur to be safe
        $found = false;
        $tableName = '';
        $currentData = null;
        
        // First try in table determined by jenis_faktur
        $primaryTable = ($jenis_faktur === 'PIM') ? 'transaksi_faktur_pim' : 'transaksi_faktur';
        $checkStmt = $conn->prepare("SELECT upload_f_pajak, kode_tfk FROM $primaryTable WHERE id_tfk = :id_tfk");
        $checkStmt->bindParam(':id_tfk', $id_tfk);
        $checkStmt->execute();
        $currentData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentData) {
            $found = true;
            $tableName = $primaryTable;
            error_log("Faktur ditemukan di tabel primer $primaryTable");
        } else {
            // Try alternative table if not found
            $alternativeTable = ($jenis_faktur === 'PIM') ? 'transaksi_faktur' : 'transaksi_faktur_pim';
            $altCheckStmt = $conn->prepare("SELECT upload_f_pajak, kode_tfk FROM $alternativeTable WHERE id_tfk = :id_tfk");
            $altCheckStmt->bindParam(':id_tfk', $id_tfk);
            $altCheckStmt->execute();
            $currentData = $altCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($currentData) {
                $found = true;
                $tableName = $alternativeTable;
                // Update jenis_faktur based on the table where we found it
                $jenis_faktur = ($alternativeTable === 'transaksi_faktur_pim') ? 'PIM' : 'Cendo & DPE';
                error_log("Faktur ditemukan di tabel alternatif $alternativeTable, jenis_faktur diperbarui menjadi $jenis_faktur");
            }
        }
        
        if (!$found) {
            $json["message"] = "Data faktur pajak tidak ditemukan di kedua tabel";
            $json["jenis_faktur"] = $jenis_faktur;
            error_log("Faktur tidak ditemukan di kedua tabel dengan ID: $id_tfk");
            goto output_json;
        }
        
        // Hanya update jika statusnya berbeda
        if ($currentData['upload_f_pajak'] != $upload_f_pajak) {
            $update = "UPDATE $tableName SET 
                      upload_f_pajak = :upload_status,
                      updated_at = NOW(),
                      updated_by = :admin
                      WHERE id_tfk = :id_tfk";
            
            $stmt = $conn->prepare($update);
            $stmt->bindParam(':upload_status', $upload_f_pajak);
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->bindParam(':admin', $admin);
            
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $json["success"] = true;
                $json["message"] = "Status upload faktur pajak berhasil diubah menjadi " . $upload_f_pajak;
                $json["kode_tfk"] = $currentData['kode_tfk'];
                $json["jenis_faktur"] = $jenis_faktur;
                error_log("Berhasil mengupdate status upload untuk faktur {$currentData['kode_tfk']} menjadi $upload_f_pajak");
            } else {
                $json["message"] = "Tidak ada perubahan data";
            }
        } else {
            $json["success"] = true;
            $json["message"] = "Status upload sudah " . $upload_f_pajak;
            $json["kode_tfk"] = $currentData['kode_tfk'];
            $json["jenis_faktur"] = $jenis_faktur;
        }
    } else {
        // Update remote via API
        error_log("Processing remote upload status update for ID: $id_tfk, Cabang: $cabang, Jenis: $jenis_faktur");
        
        // Get API info for the specified branch
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
        
        // Important: Verify invoice existence first before attempting update
        $check_api_url = rtrim($cabangData['base_url_apl'], '/') . '/api/checkInvoice.php';
        
        $check_data = [
            'id_tfk' => $id_tfk,
            'jenis_faktur' => $jenis_faktur,
            'encrypt' => $encrypt
        ];
        
        // First check if invoice exists
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
            // Update jenis_faktur if returned from check API
            if (isset($check_result['jenis_faktur'])) {
                $jenis_faktur = $check_result['jenis_faktur'];
                error_log("Using corrected jenis_faktur from API: $jenis_faktur");
            }
            
            // Proceed with update
            $api_url = rtrim($cabangData['base_url_apl'], '/') . '/api/updateUploadStatus.php';
            
            $api_data = [
                'id_tfk' => $id_tfk,
                'upload_f_pajak' => $upload_f_pajak,
                'jenis_faktur' => $jenis_faktur,
                'encrypt' => $encrypt,
                'admin' => $admin
            ];
            
            error_log("Sending update request with data: " . json_encode($api_data));
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($api_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("Update API Response: $response");
            
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
                $json["message"] = "Status upload faktur pajak berhasil diubah di " . $cabang;
                $json["kode_tfk"] = $api_result['kode_tfk'] ?? '';
                $json["jenis_faktur"] = $api_result['jenis_faktur'] ?? $jenis_faktur;
            } else {
                $message = isset($api_result['message']) ? $api_result['message'] : 'Unknown error';
                $json["message"] = "API Error: " . $message;
            }
        } else {
            $json["message"] = "Data faktur pajak tidak ditemukan di cabang $cabang";
            goto output_json;
        }
    }
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("upload_status_api.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>

<script>
// Add this function to the <script> section in monitoringfi.php
function checkInvoiceExists(id_tfk, cabang, jenis_faktur) {
    return new Promise(function(resolve, reject) {
        // Create URL for checking invoice existence
        var checkUrl = '<?php echo $sistem; ?>/json/monitoringfi/check_invoice.php';
        
        $.ajax({
            url: checkUrl,
            type: 'POST',
            data: {
                id_tfk: id_tfk,
                cabang: cabang,
                jenis_faktur: jenis_faktur
            },
            dataType: 'json',
            success: function(response) {
                if (response.exists) {
                    resolve(true);
                } else {
                    reject(response.message || "Invoice not found");
                }
            },
            error: function(xhr, status, error) {
                reject("Error checking invoice: " + error);
            }
        });
    });
}

// Modify the toggleUploadStatus function to check first
$('#btnConfirmUpdate').on('click', function() {
    var id_tfk = $('#confirm_id_tfk').val();
    var cabang = $('#confirm_cabang').val();
    var currentStatus = $('#confirm_current_status').val();
    var jenis_faktur = $('#confirm_jenis_faktur').val() || 'Cendo & DPE'; // Get jenis_faktur
    
    // Determine status to change to
    var newStatus = (currentStatus === 'sudah') ? 'belum' : 'sudah';
    
    // Disable button while processing
    $('#btnConfirmUpdate').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Proses...');
    
    // Remove any previous messages
    $('.modal-body .alert-success, .modal-body .alert-danger, .modal-body .loading-message').remove();
    $('.modal-body .status-result').remove();
    
    // Add loading message
    $('.modal-body').append('<div class="loading-message text-center mt-3"><i class="fa fa-spinner fa-spin"></i> Sedang memverifikasi...</div>');
    
    // First check if invoice exists
    checkInvoiceExists(id_tfk, cabang, jenis_faktur)
        .then(function() {
            // Invoice exists, proceed with update
            $('.loading-message').html('<i class="fa fa-spinner fa-spin"></i> Sedang memproses perubahan status...');
            
            // Send AJAX request to update status
            $.ajax({
                url: '<?php echo $sistem; ?>/json/monitoringfi/upload_status_api.php',
                type: 'POST',
                data: {
                    id_tfk: id_tfk,
                    cabang: cabang,
                    upload_f_pajak: newStatus,
                    jenis_faktur: jenis_faktur
                },
                dataType: 'json',
                success: function(response) {
                    // Process response as before
                    $('#btnConfirmUpdate').prop('disabled', false).html('Konfirmasi');
                    $('.loading-message').remove();
                    
                    if (response.success) {
                        // Rest of your success handling
                    } else {
                        // Rest of your error handling
                    }
                },
                error: function(xhr, status, error) {
                    // Error handling
                }
            });
        })
        .catch(function(error) {
            // Invoice doesn't exist, show error
            $('#btnConfirmUpdate').prop('disabled', false).html('Konfirmasi');
            $('.loading-message').remove();
            $('.modal-body').append('<div class="alert alert-danger mt-3">' + error + '</div>');
        });
});
</script>