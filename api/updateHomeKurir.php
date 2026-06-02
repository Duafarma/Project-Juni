<?php
// API untuk update status tukar faktur dari cabang lain
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$tgl = date('Y-m-d');
$catat = date('Y-m-d H:i:s');
$hasil = "error";

// Log request for debugging
error_log("[API_REQUEST] Received request: " . json_encode($_POST));

// Validasi method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    error_log("[API_ERROR] Invalid method: " . $_SERVER['REQUEST_METHOD']);
    echo "error: Invalid method";
    exit;
}

// Ambil dan sanitasi input
$id_tfkkf     = $secu->injection(@$_POST['id_tfkkf']);
$source_table = $secu->injection(@$_POST['source_table']);
$action       = $secu->injection(@$_POST['action']);
$ket_tfkkf    = $secu->injection(@$_POST['ket_tfkkf']);
$encrypt      = $secu->injection(@$_POST['encrypt']);
$admin        = $secu->injection(@$_POST['admin']);

error_log("[API] Processing request: Action=$action, ID=$id_tfkkf, Source=$source_table");

// Validasi parameter wajib
if (empty($id_tfkkf) || empty($action) || empty($encrypt)) {
    http_response_code(400);
    error_log("[API_ERROR] Missing required parameters");
    echo "error: Missing required parameters";
    exit;
}

// Autentikasi
$source = $data->self_apl();
$sourceKey = $source['key_apl'];
$validHash = md5($tgl . "#" . $sourceKey);

if ($validHash != $encrypt) {
    http_response_code(401);
    error_log("[API_ERROR] Authentication failed. Expected: $validHash, Got: $encrypt");
    echo "error: Authentication failed";
    exit;
}

// Proses update
try {
    // Periksa apakah data ada sebelum melakukan operasi
    $check = $conn->prepare("SELECT * FROM transaksi_faktur_kirim_f WHERE id_tfkkf = :id");
    $check->bindParam(':id', $id_tfkkf, PDO::PARAM_STR);
    $check->execute();
    $data_tfkkf = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$data_tfkkf && $action != 'add') {
        error_log("[API_ERROR] Data not found: ID=$id_tfkkf");
        echo "error: Data not found";
        exit;
    }
    
    // Get id_tfk value
    $id_tfk = $data_tfkkf ? $data_tfkkf['id_tfk'] : null;
    
    if ($action == 'done') {
        // Begin transaction
        $conn->beginTransaction();
        
        // Update status menjadi Sudah Dikirim
        $update_tfkkf = $conn->prepare("UPDATE transaksi_faktur_kirim_f SET status_tfkkf='Sudah Dikirim', ket_tfkkf=:ket_tfkkf WHERE id_tfkkf=:kode");
        $update_tfkkf->bindParam(':kode', $id_tfkkf, PDO::PARAM_STR);
        $update_tfkkf->bindParam(':ket_tfkkf', $ket_tfkkf, PDO::PARAM_STR);
        $update_tfkkf->execute();
        $affected_rows = $update_tfkkf->rowCount();
        error_log("[API] Updated transaksi_faktur_kirim_f, rows: $affected_rows");

        // Update status di tabel sumber
        if ($id_tfk) {
            if ($source_table == 'transaksi_faktur_pim') {
                $update_tfk = $conn->prepare("UPDATE transaksi_faktur_pim SET status_tfkkf='Sudah Dikirim' WHERE id_tfk=:id_tfk");
            } else if ($source_table == 'transaksi_faktur_c') {
                $update_tfk = $conn->prepare("UPDATE transaksi_faktur_c SET status_tfkkf='Sudah Dikirim' WHERE id_tfk=:id_tfk");
            } else {
                $update_tfk = $conn->prepare("UPDATE transaksi_faktur SET status_tfkkf='Sudah Dikirim' WHERE id_tfk=:id_tfk");
            }
            
            $update_tfk->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $update_tfk->execute();
            $affected_source = $update_tfk->rowCount();
            error_log("[API] Updated source table: $source_table, rows: $affected_source");
            
            // Log activity
            $log = $conn->prepare("INSERT INTO riwayat(id_riwayat, kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(NULL, :id_tfk, 'Tukar Faktur', 'Update', 'Update status tukar faktur menjadi Sudah Dikirim (via API)', :catat, :admin)");
            $log->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $log->bindParam(':catat', $catat, PDO::PARAM_STR);
            $log->bindParam(':admin', $admin, PDO::PARAM_STR);
            $log->execute();
        }

        // Commit all changes
        $conn->commit();
        $hasil = "success";
        error_log("[API] Done operation successful for ID: $id_tfkkf");
        
    } elseif ($action == 'cancel') {
        // Begin transaction
        $conn->beginTransaction();
        
        if ($id_tfk) {
            // Reset status di tabel sumber sebelum menghapus
            if ($source_table == 'transaksi_faktur_pim') {
                $reset = $conn->prepare("UPDATE transaksi_faktur_pim SET status_tfkkf=NULL WHERE id_tfk=:id_tfk");
            } else if ($source_table == 'transaksi_faktur_c') {
                $reset = $conn->prepare("UPDATE transaksi_faktur_c SET status_tfkkf=NULL WHERE id_tfk=:id_tfk");
            } else {
                $reset = $conn->prepare("UPDATE transaksi_faktur SET status_tfkkf=NULL WHERE id_tfk=:id_tfk");
            }
            
            $reset->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $reset->execute();
            $affected_reset = $reset->rowCount();
            error_log("[API] Reset status in source table: $source_table, rows: $affected_reset");
            
            // Log activity
            $log = $conn->prepare("INSERT INTO riwayat(id_riwayat, kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(NULL, :id_tfk, 'Tukar Faktur', 'Cancel', 'Batalkan pengiriman faktur (via API)', :catat, :admin)");
            $log->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $log->bindParam(':catat', $catat, PDO::PARAM_STR);
            $log->bindParam(':admin', $admin, PDO::PARAM_STR);
            $log->execute();
        }
        
        // Hapus data transaksi_faktur_kirim_f
        $delete = $conn->prepare("DELETE FROM transaksi_faktur_kirim_f WHERE id_tfkkf=:kode");
        $delete->bindParam(':kode', $id_tfkkf, PDO::PARAM_STR);
        $delete->execute();
        $affected_delete = $delete->rowCount();
        error_log("[API] Deleted from transaksi_faktur_kirim_f, rows: $affected_delete");
        
        // Commit all changes
        $conn->commit();
        $hasil = "success";
        error_log("[API] Cancel operation successful for ID: $id_tfkkf");
    } else {
        $hasil = "error: action not supported";
        error_log("[API_ERROR] Unsupported action: $action");
    }
} catch (PDOException $e) {
    // Rollback jika terjadi error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    $hasil = "error: " . $e->getMessage();
    error_log("[API_ERROR] Exception: " . $e->getMessage());
}

// CLOSE CONNECTION
$conn = $base->close();

// SEND RESPONSE
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');
echo $hasil;
?>