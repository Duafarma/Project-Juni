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
    "url" => ""
];

// Check if user is logged in
if ($valid == false) {
    $json["message"] = "Session login anda habis...";
    goto output_json;
}

// Get request data
$id_tfk = $secu->injection($_GET['id_tfk']);
$id_apl = $secu->injection($_GET['id_apl'] ?? ''); // Parameter id_apl
$cabang = $secu->injection($_GET['cabang'] ?? ''); // Parameter cabang

// Validate required inputs
if (empty($id_tfk)) {
    $json["message"] = "ID transaksi tidak valid";
    goto output_json;
}

try {
    // Cek apakah ini cabang lokal atau cabang lain
    $isLocalBranch = true;
    $baseUrl = $sistem; // Default ke sistem lokal
    
    // Jika id_apl tidak ada tapi cabang ada, cari id_apl berdasarkan nama cabang
    if (empty($id_apl) && !empty($cabang)) {
        $cabangQuery = "SELECT id_apl FROM aplikasi WHERE nama_apl = :nama_apl LIMIT 1";
        $cabangStmt = $conn->prepare($cabangQuery);
        $cabangStmt->bindParam(':nama_apl', $cabang);
        $cabangStmt->execute();
        
        if ($cabangRow = $cabangStmt->fetch(PDO::FETCH_ASSOC)) {
            $id_apl = $cabangRow['id_apl'];
        }
    }
    
    // Ambil info aplikasi dari database jika id_apl tersedia
    if (!empty($id_apl)) {
        $aplQuery = "SELECT nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl LIMIT 1";
        $aplStmt = $conn->prepare($aplQuery);
        $aplStmt->bindParam(':id_apl', $id_apl);
        $aplStmt->execute();
        
        if ($aplRow = $aplStmt->fetch(PDO::FETCH_ASSOC)) {
            $baseUrl = rtrim($aplRow['base_url_apl'], '/');
            
            // Cek apakah ini cabang lokal dengan membandingkan nama_apl dengan data sistem
            $localInfo = $data->self_apl();
            $isLocalBranch = ($aplRow['nama_apl'] == $localInfo['nama_apl']);
        }
    }
    
    // Jika cabang lokal, ambil data dari database lokal
    if ($isLocalBranch) {
        // Get latest uploaded document for this invoice
        $query = "SELECT url_upload, file_type
                  FROM upload_f_pajak_detail 
                  WHERE no_faktur = :id_tfk
                  ORDER BY created_at DESC 
                  LIMIT 1";
                  
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id_tfk', $id_tfk);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $json["url"] = $baseUrl . '/assets/uploads/faktur_pajak/' . $row['url_upload'];
            $json["file_type"] = $row['file_type'];
            $json["success"] = true;
            $json["message"] = "";
        } else {
            $json["message"] = "Dokumen tidak ditemukan";
        }
    }
    // Jika bukan cabang lokal, gunakan API getLatestUpload.php di cabang tersebut
    else {
        try {
            // Buat enkripsi untuk otorisasi API
            $tgl = date('Y-m-d');
            $key = $aplRow['key_apl'];
            $encrypt = md5($tgl . "#" . $key);
            
            // Buat URL API endpoint ke getLatestUpload.php di cabang tujuan
            $apiUrl = $baseUrl . '/api/getLatestUpload.php?encrypt=' . $encrypt . '&id_tfk=' . urlencode($id_tfk);
            
            // Log untuk debugging
            error_log("Mencoba akses API: " . $apiUrl);
            
            // Panggil API via curl dengan opsi yang lebih lengkap
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Ikuti redirect jika ada
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'MonitoringFI/1.0');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            error_log("Response HTTP Code: " . $httpCode);
            error_log("Response Body: " . $response);
            
            if ($httpCode == 200 && !empty($response)) {
                $remoteData = json_decode($response, true);
                
                // Periksa apakah respons valid dan berhasil
                if ($remoteData && isset($remoteData['success'])) {
                    if ($remoteData['success'] === true) {
                        $remoteUrl = $remoteData['url'];
                        
                        // Cek jika URL mengandung path faktur_pajak
                        if (strpos($remoteUrl, '/assets/uploads/faktur_pajak/') !== false) {
                            // Pisahkan URL untuk mendapatkan nama file saja
                            $parts = explode('/assets/uploads/faktur_pajak/', $remoteUrl);
                            if (isset($parts[1])) {
                                // Buat URL baru dengan base_url_apl yang benar
                                $json["url"] = $baseUrl . '/assets/uploads/faktur_pajak/' . $parts[1];
                            } else {
                                $json["url"] = $remoteUrl;
                            }
                        } else {
                            // URL tidak sesuai format yang diharapkan
                            $json["url"] = $remoteUrl;
                        }
                        
                        $json["file_type"] = $remoteData['file_type'] ?? 'application/pdf';
                        $json["success"] = true;
                        $json["message"] = "";
                    } else {
                        $json["message"] = "File upload tidak ditemukan untuk faktur ini di cabang {$aplRow['nama_apl']}";
                    }
                } else {
                    $json["message"] = "Format data dari API cabang {$aplRow['nama_apl']} tidak sesuai";
                }
            } else {
                // Jika API call gagal
                $json["message"] = "Error menghubungi API cabang {$aplRow['nama_apl']}: HTTP " . $httpCode . 
                                  (!empty($error) ? " - " . $error : "");
            }
        } catch (Exception $e) {
            $json["message"] = "Error dalam komunikasi dengan cabang {$aplRow['nama_apl']}: " . $e->getMessage();
            error_log("getLatestUpload.php remote connection error: " . $e->getMessage());
        }
    }
    
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("getLatestUpload.php error: " . $e->getMessage());
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