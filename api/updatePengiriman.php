<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
$secu = new Security;
$base = new DB;
$conn = $base->open();

$tgl = $_POST['tgl'] ?? date('Y-m-d');
$encrypt = $_POST['encrypt'] ?? '';
$id_apl = $_POST['id_apl'] ?? '';
$id_tfkkb = $_POST['id_tfkkb'] ?? '';
$status_tfkkb = $_POST['status_tfkkb'] ?? '';
$ket_tfkkb = $_POST['ket_tfkkb'] ?? '';
$nama_adm = $_POST['nama_adm'] ?? ''; // UBAH: Gunakan nama_adm bukan id_adm
$action = $_POST['action'] ?? 'update';
$admin = $_POST['admin'] ?? '';

// Validasi autentikasi
$stmtApl = $conn->prepare("SELECT key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
$stmtApl->bindParam(':id_apl', $id_apl);
$stmtApl->execute();
$apl = $stmtApl->fetch(PDO::FETCH_ASSOC);

if (!$apl || md5($tgl . "#" . $apl['key_apl']) !== $encrypt) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Log untuk debugging
error_log("[PENGIRIMAN_API] Action: $action, ID_TFKKB: $id_tfkkb, Nama_ADM: $nama_adm");

try {
    if ($action === 'delete') {
        // Untuk delete, cari data yang akan dihapus
        if (empty($id_tfkkb)) {
            // Jika tidak ada ID spesifik, cari berdasarkan admin
            $search_query = $conn->prepare("
                SELECT tkb.id_tfkkb, tkb.id_tfk, tkb.status_tfkkb 
                FROM transaksi_faktur_kirim_b tkb
                LEFT JOIN adminz a ON a.id_adm = tkb.id_adm
                WHERE (tkb.created_by = :admin OR a.nama_adm = :nama_adm) 
                AND tkb.status_tfkkb = 'Belum Dikirim' 
                ORDER BY tkb.created_at DESC 
                LIMIT 1
            ");
            $search_query->bindParam(':admin', $admin);
            $search_query->bindParam(':nama_adm', $nama_adm);
            $search_query->execute();
            $found_data = $search_query->fetch(PDO::FETCH_ASSOC);
            
            if ($found_data) {
                $id_tfkkb = $found_data['id_tfkkb'];
            }
        }
        
        if (empty($id_tfkkb)) {
            echo json_encode(["status" => "error", "message" => "No data found to delete"]);
            exit;
        }
        
        // Get data yang akan dihapus
        $getData = $conn->prepare("SELECT id_tfk FROM transaksi_faktur_kirim_b WHERE id_tfkkb = :id_tfkkb");
        $getData->bindParam(':id_tfkkb', $id_tfkkb);
        $getData->execute();
        $dataRow = $getData->fetch(PDO::FETCH_ASSOC);
        
        if ($dataRow) {
            $id_tfk = $dataRow['id_tfk'];
            
            // Start transaction
            $conn->beginTransaction();
            
            // Check which table the faktur belongs to and update appropriately
            $checkPim = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
            $checkPim->bindParam(':id_tfk', $id_tfk);
            $checkPim->execute();
            $isPim = ($checkPim->fetchColumn() > 0);

            $checkC = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_c WHERE id_tfk = :id_tfk");
            $checkC->bindParam(':id_tfk', $id_tfk);
            $checkC->execute();
            $isC = ($checkC->fetchColumn() > 0);

            // Update status in the correct source table
            if ($isPim) {
                $updateSource = $conn->prepare("UPDATE transaksi_faktur_pim SET status_tfkkb = 'Belum Dikirim' WHERE id_tfk = :id_tfk");
            } else if ($isC) {
                $updateSource = $conn->prepare("UPDATE transaksi_faktur_c SET status_tfkkb = 'Belum Dikirim' WHERE id_tfk = :id_tfk");
            } else {
                $updateSource = $conn->prepare("UPDATE transaksi_faktur SET status_tfkkb = 'Belum Dikirim' WHERE id_tfk = :id_tfk");
            }
            $updateSource->bindParam(':id_tfk', $id_tfk);
            $updateSource->execute();
            
            // Delete the record from transaksi_faktur_kirim_b
            $delete = $conn->prepare("DELETE FROM transaksi_faktur_kirim_b WHERE id_tfkkb = :id_tfkkb");
            $delete->bindParam(':id_tfkkb', $id_tfkkb);
            $deleteResult = $delete->execute();
            
            // Commit changes
            $conn->commit();
            
            if ($deleteResult) {
                error_log("[PENGIRIMAN_API] Successfully deleted: $id_tfkkb");
                echo json_encode(["status" => "success"]);
            } else {
                throw new Exception("Failed to delete record");
            }
        } else {
            error_log("[PENGIRIMAN_API] Record not found for delete: $id_tfkkb");
            echo json_encode(["status" => "error", "message" => "Record not found"]);
        }
    } else {
        // Update action
        if (empty($id_tfkkb)) {
            // Jika tidak ada ID spesifik, cari berdasarkan nama admin
            $search_query = $conn->prepare("
                SELECT tkb.id_tfkkb, tkb.id_tfk, tkb.status_tfkkb 
                FROM transaksi_faktur_kirim_b tkb
                LEFT JOIN adminz a ON a.id_adm = tkb.id_adm
                WHERE (tkb.created_by = :admin OR a.nama_adm = :nama_adm) 
                AND tkb.status_tfkkb = 'Belum Dikirim' 
                ORDER BY tkb.created_at DESC 
                LIMIT 1
            ");
            $search_query->bindParam(':admin', $admin);
            $search_query->bindParam(':nama_adm', $nama_adm);
            $search_query->execute();
            $found_data = $search_query->fetch(PDO::FETCH_ASSOC);
            
            if ($found_data) {
                $id_tfkkb = $found_data['id_tfkkb'];
            }
        }
        
        if (empty($id_tfkkb)) {
            echo json_encode(["status" => "error", "message" => "No data found to update"]);
            exit;
        }
        
        // BARU: Cari id_adm berdasarkan nama_adm di cabang ini
        $id_adm_local = null;
        if (!empty($nama_adm)) {
            $getAdmQuery = $conn->prepare("SELECT id_adm FROM adminz WHERE nama_adm = :nama_adm AND jenis_adm = 'Kurir' LIMIT 1");
            $getAdmQuery->bindParam(':nama_adm', $nama_adm);
            $getAdmQuery->execute();
            $admData = $getAdmQuery->fetch(PDO::FETCH_ASSOC);
            if ($admData) {
                $id_adm_local = $admData['id_adm'];
                error_log("[PENGIRIMAN_API] Found local admin ID: $id_adm_local for nama_adm: $nama_adm");
            } else {
                error_log("[PENGIRIMAN_API] Admin not found for nama_adm: $nama_adm");
                echo json_encode(["status" => "error", "message" => "Admin tidak ditemukan di cabang ini"]);
                exit;
            }
        }
        
        // Update data
        $conn->beginTransaction();
        
        if (!empty($id_adm_local)) {
            $update = $conn->prepare("UPDATE transaksi_faktur_kirim_b SET status_tfkkb = :status, ket_tfkkb = :ket, id_adm = :id_adm, updated_at = NOW() WHERE id_tfkkb = :id_tfkkb");
            $update->bindParam(':status', $status_tfkkb);
            $update->bindParam(':ket', $ket_tfkkb);
            $update->bindParam(':id_adm', $id_adm_local);
            $update->bindParam(':id_tfkkb', $id_tfkkb);
        } else {
            $update = $conn->prepare("UPDATE transaksi_faktur_kirim_b SET status_tfkkb = :status, ket_tfkkb = :ket, updated_at = NOW() WHERE id_tfkkb = :id_tfkkb");
            $update->bindParam(':status', $status_tfkkb);
            $update->bindParam(':ket', $ket_tfkkb);
            $update->bindParam(':id_tfkkb', $id_tfkkb);
        }
        
        $updateResult = $update->execute();
        
        // Update source table if needed
        if ($updateResult && $status_tfkkb == 'Sudah Dikirim') {
            $getData = $conn->prepare("SELECT id_tfk FROM transaksi_faktur_kirim_b WHERE id_tfkkb = :id_tfkkb");
            $getData->bindParam(':id_tfkkb', $id_tfkkb);
            $getData->execute();
            $dataRow = $getData->fetch(PDO::FETCH_ASSOC);
            
            if ($dataRow) {
                $id_tfk = $dataRow['id_tfk'];
                
                // Check which table the faktur belongs to
                $checkPim = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk");
                $checkPim->bindParam(':id_tfk', $id_tfk);
                $checkPim->execute();
                $isPim = ($checkPim->fetchColumn() > 0);

                $checkC = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_c WHERE id_tfk = :id_tfk");
                $checkC->bindParam(':id_tfk', $id_tfk);
                $checkC->execute();
                $isC = ($checkC->fetchColumn() > 0);

                // Update status in the correct source table
                if ($isPim) {
                    $updateSource = $conn->prepare("UPDATE transaksi_faktur_pim SET status_tfkkb = :status WHERE id_tfk = :id_tfk");
                } else if ($isC) {
                    $updateSource = $conn->prepare("UPDATE transaksi_faktur_c SET status_tfkkb = :status WHERE id_tfk = :id_tfk");
                } else {
                    $updateSource = $conn->prepare("UPDATE transaksi_faktur SET status_tfkkb = :status WHERE id_tfk = :id_tfk");
                }
                $updateSource->bindParam(':status', $status_tfkkb);
                $updateSource->bindParam(':id_tfk', $id_tfk);
                $updateSource->execute();
            }
        }
        
        $conn->commit();
        
        if ($updateResult) {
            error_log("[PENGIRIMAN_API] Successfully updated: $id_tfkkb with nama_adm: $nama_adm");
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Update failed"]);
        }
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("[PENGIRIMAN_API] Exception: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

$conn = $base->close();
?>