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
    "exists" => false,
    "message" => "Invalid request"
];

// Initialize variables with default values
$id_tfk = '';
$cabang = '';
$jenis_faktur = 'Cendo & DPE'; // Default value

// Check if user is logged in
if ($valid == false) {
    $json["message"] = "Session login anda habis...";
    goto output_json;
}

// Get POST data - after initializing variables
$id_tfk = $secu->injection($_POST['id_tfk'] ?? '');
$cabang = $secu->injection($_POST['cabang'] ?? '');
$jenis_faktur = $secu->injection($_POST['jenis_faktur'] ?? 'Cendo & DPE');

// Now we can safely log these variables
error_log("check_invoice.php dipanggil dengan id_tfk: " . $id_tfk . ", cabang: " . $cabang . ", jenis_faktur: " . $jenis_faktur);

// Handle HTML entity decoding untuk parameter jenis_faktur
if ($jenis_faktur === 'Cendo &amp; DPE') {
    $jenis_faktur = 'Cendo & DPE';
    error_log("Memperbaiki encoding HTML entity pada jenis_faktur");
}

// Validasi jenis_faktur - pastikan nilainya valid
if ($jenis_faktur !== 'PIM' && $jenis_faktur !== 'Cendo & DPE') {
    error_log("Jenis faktur tidak valid: '$jenis_faktur', defaulting ke 'Cendo & DPE'");
    $jenis_faktur = 'Cendo & DPE';
}

// Validasi input yang diperlukan
if (empty($id_tfk)) {
    $json["message"] = "ID transaksi tidak valid";
    goto output_json;
}

try {
    // Dapatkan info cabang saat ini
    $source = $data->self_apl();
    $local_cabang = $source['nama_apl'];
    
    $isLocalCabang = ($cabang == $local_cabang);
    
    if ($isLocalCabang) {
        // Tentukan tabel berdasarkan jenis faktur
        $tableName = ($jenis_faktur === 'PIM') ? 'transaksi_faktur_pim' : 'transaksi_faktur';
        $alternativeTable = ($jenis_faktur === 'PIM') ? 'transaksi_faktur' : 'transaksi_faktur_pim';
        $alternativeType = ($jenis_faktur === 'PIM') ? 'Cendo & DPE' : 'PIM';
        
        $tablesChecked = [$tableName];
        
        // Coba di tabel utama dulu
        $stmt = $conn->prepare("SELECT id_tfk, kode_tfk FROM $tableName WHERE id_tfk = :id_tfk LIMIT 1");
        $stmt->bindParam(':id_tfk', $id_tfk);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $json["exists"] = true;
            $json["message"] = "Faktur ditemukan";
            $json["kode_tfk"] = $row['kode_tfk'];
            $json["jenis_faktur"] = $jenis_faktur;
            error_log("Faktur dengan ID $id_tfk ditemukan di tabel $tableName");
        } else {
            error_log("Faktur dengan ID $id_tfk tidak ditemukan di tabel $tableName, mencoba di $alternativeTable");
            $tablesChecked[] = $alternativeTable;
            
            $stmt = $conn->prepare("SELECT id_tfk, kode_tfk FROM $alternativeTable WHERE id_tfk = :id_tfk LIMIT 1");
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->execute();
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $json["exists"] = true;
                $json["message"] = "Faktur ditemukan (sebagai $alternativeType)";
                $json["kode_tfk"] = $row['kode_tfk'];
                $json["jenis_faktur"] = $alternativeType;
                error_log("Faktur dengan ID $id_tfk ditemukan di tabel alternative: $alternativeTable dengan jenis $alternativeType");
            } else {
                $json["message"] = "Faktur dengan ID $id_tfk tidak ditemukan di kedua tabel (coba: " . implode(", ", $tablesChecked) . ")";
                error_log($json["message"]);
            }
        }
    } else {
        // Periksa di cabang remote (kode yang sudah ada)
        $stmt = $conn->prepare("SELECT id_apl, base_url_apl, key_apl FROM aplikasi WHERE nama_apl = :cabang LIMIT 1");
        $stmt->bindParam(':cabang', $cabang);
        $stmt->execute();
        $cabangData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cabangData || empty($cabangData['base_url_apl'])) {
            $json["message"] = "Cabang $cabang tidak ditemukan";
            goto output_json;
        }
        
        // Generate otentikasi API
        $tgl = date('Y-m-d');
        $encrypt = md5($tgl . "#" . $cabangData['key_apl']);
        
        // Persiapkan data API
        $api_data = [
            'id_tfk' => $id_tfk,
            'jenis_faktur' => $jenis_faktur,
            'encrypt' => $encrypt
        ];
        
        // Bangun URL API
        $base_url = rtrim($cabangData['base_url_apl'], '/');
        $api_url = $base_url . '/api/checkInvoice.php';
        
        // Kirim permintaan API
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
        curl_close($ch);
        
        if ($curl_error) {
            $json["message"] = "Gagal terhubung ke cabang $cabang: " . $curl_error;
        } else {
            $api_result = json_decode($response, true);
            
            if (isset($api_result['exists'])) {
                $json = $api_result; // Gunakan respons dari API
            } else {
                $json["message"] = "Format respons tidak valid dari cabang $cabang";
            }
        }
    }
} catch (PDOException $e) {
    $json["message"] = "Database Error: " . $e->getMessage();
    error_log("check_invoice.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>

<script>
// Function to toggle upload_f_pajak between "belum" and "sudah"
function toggleUploadStatus(id_tfk, cabang, currentStatus, kode_tfk, nama_out, jenis_faktur) {
    // Reset modal state
    $('.modal-body .alert-success, .modal-body .alert-danger, .modal-body .loading-message').remove();
    
    // Set values to hidden fields
    $('#confirm_id_tfk').val(id_tfk);
    $('#confirm_cabang').val(cabang);
    $('#confirm_kode_tfk').text(kode_tfk || '-');
    $('#confirm_nama_out').text(nama_out || '-');
    $('#confirm_current_status').val(currentStatus);
    $('#confirm_jenis_faktur').val(jenis_faktur || 'Cendo & DPE'); // Set jenis_faktur dengan default
    
    // Update modal title and message
    if (currentStatus === 'sudah') {
        $('#confirmUpdateModalLabel').text('Konfirmasi Ubah Status');
        $('#confirmMessage').html('Apakah anda yakin ingin mengubah status upload faktur pajak <strong>' + kode_tfk + '</strong> dari <strong>SUDAH</strong> menjadi <strong>BELUM</strong> diupload?');
    } else {
        $('#confirmUpdateModalLabel').text('Konfirmasi Ubah Status');
        $('#confirmMessage').html('Apakah anda yakin ingin mengubah status upload faktur pajak <strong>' + kode_tfk + '</strong> dari <strong>BELUM</strong> menjadi <strong>SUDAH</strong> diupload?');
    }
    
    // Show the confirmation modal
    $('#confirmUpdateModal').modal('show');
}
</script>