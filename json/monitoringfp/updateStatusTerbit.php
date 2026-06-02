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

// Data akses
$admin  = $secu->injection(@$_COOKIE['adminkuy']);
$kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
$valid  = $secu->validadmin($admin, $kunci);

// Inisialisasi respons
$json = [
    "success" => false,
    "message" => "Permintaan tidak valid"
];

// Cek apakah user sudah login
if ($valid == false) {
    $json["message"] = "Sesi login anda habis...";
    goto output_json;
}

// Ambil data POST
$id_tfk = $secu->injection($_POST['id_tfk']);
$status_terbit = $secu->injection($_POST['status_terbit']);

// Validasi input yang diperlukan
if (empty($id_tfk)) {
    $json["message"] = "ID Faktur tidak boleh kosong";
    goto output_json;
}

try {
    // Ambil nilai upload_f_pajak saat ini
    $get = $conn->prepare("SELECT upload_f_pajak FROM transaksi_faktur WHERE id_tfk = :id_tfk");
    $get->bindParam(':id_tfk', $id_tfk);
    $get->execute();
    $old = $get->fetch(PDO::FETCH_ASSOC);
    
    if (!$old) {
        $json["message"] = "Data faktur pajak tidak ditemukan";
        goto output_json;
    }
    
    // Hanya update field status_terbit, jaga nilai upload_f_pajak tetap sama
    $update = "UPDATE transaksi_faktur SET status_terbit = :status_terbit WHERE id_tfk = :id_tfk";
    $stmt_update = $conn->prepare($update);
    $stmt_update->bindParam(':id_tfk', $id_tfk);
    $stmt_update->bindParam(':status_terbit', $status_terbit);
    $stmt_update->execute();
    
    $json["success"] = true;
    $json["message"] = "Status faktur berhasil diperbarui";
    
} catch (PDOException $e) {
    $json["message"] = "Error Database: " . $e->getMessage();
    error_log("updateStatusTerbit.php error: " . $e->getMessage());
}

output_json:
$conn = $base->close();

// Bersihkan output yang tidak diharapkan
ob_end_clean();

// Kirim header dan respons JSON
header('Content-Type: application/json');
echo json_encode($json);
exit;
?>