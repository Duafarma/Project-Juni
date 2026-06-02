<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

// Tambahkan ini di awal sebelum akses $_POST
$input = file_get_contents('php://input');
if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $_POST = json_decode($input, true) ?? [];
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Validasi parameter
$tgl = isset($_POST['tgl']) ? trim($_POST['tgl']) : date('Y-m-d');
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$id_out = isset($_POST['id_out']) ? $secu->injection($_POST['id_out']) : '';
$faktur_ids = isset($_POST['faktur_ids']) ? $_POST['faktur_ids'] : [];
$nama_adm = isset($_POST['nama_adm']) ? $secu->injection($_POST['nama_adm']) : ''; // PERBAIKAN: Gunakan nama_adm
$keterangan = isset($_POST['keterangan']) ? $secu->injection($_POST['keterangan']) : '';

// Validasi input wajib
if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

if (empty($id_out)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'id_out' wajib diisi"]);
    exit;
}

if (empty($faktur_ids) || !is_array($faktur_ids)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'faktur_ids' harus berupa array dan tidak boleh kosong"]);
    exit;
}

if (empty($nama_adm)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'nama_adm' (kurir) wajib diisi"]);
    exit;
}

// Validasi format tanggal
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    http_response_code(400);
    echo json_encode(["error" => "Format tanggal tidak valid. Gunakan YYYY-MM-DD"]);
    exit;
}

// Validasi tanggal dengan DateTime
$date_obj = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$date_obj || $date_obj->format('Y-m-d') !== $tgl) {
    http_response_code(400);
    echo json_encode(["error" => "Tanggal tidak valid"]);
    exit;
}

try {
    $conn = $base->open();
    
    // Ambil data aplikasi
    $stmtApl = $conn->prepare("SELECT key_apl, base_url_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
    
    if (!$apl) {
        throw new Exception("Aplikasi tidak ditemukan atau tidak aktif");
    }
    
    $sourceKey = $apl['key_apl'];
    $baseUrl = $apl['base_url_apl'];
    $nama_apl = $apl['nama_apl'];
    
    // Validasi encrypt
    if (md5($tgl . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }
    
    // PERBAIKAN: Cari id_adm berdasarkan nama_adm yang sama dengan session login
    $id_adm_local = null;
    if (!empty($nama_adm)) {
        // Cari admin dengan nama yang sama persis
        $getAdmQuery = $conn->prepare("SELECT id_adm FROM adminz WHERE nama_adm = :nama_adm LIMIT 1");
        $getAdmQuery->bindParam(':nama_adm', $nama_adm);
        $getAdmQuery->execute();
        $admData = $getAdmQuery->fetch(PDO::FETCH_ASSOC);
        
        if ($admData) {
            $id_adm_local = $admData['id_adm'];
        } else {
            // Jika tidak ditemukan, buat admin baru dengan nama yang sama
            $new_admin_id = 'ADM' . date('ymdHis') . rand(100, 999);
            $insertAdmin = $conn->prepare("
                INSERT INTO adminz (id_adm, nama_adm, jenis_adm, status_adm, pass_adm, created_at) 
                VALUES (:id_adm, :nama_adm, 'Kurir', 'Active', MD5('default123'), :created_at)
            ");
            $created_at = date('Y-m-d H:i:s');
            $insertAdmin->bindParam(':id_adm', $new_admin_id);
            $insertAdmin->bindParam(':nama_adm', $nama_adm);
            $insertAdmin->bindParam(':created_at', $created_at);
            $insertAdmin->execute();
            $id_adm_local = $new_admin_id;
        }
    }
    
    if (!$id_adm_local) {
        echo json_encode([
            "status" => "error",
            "message" => "Gagal mengambil atau membuat admin dengan nama '$nama_adm'"
        ]);
        exit;
    }
    
    // Mulai transaksi
    $conn->beginTransaction();
    
    // Variabel untuk menyimpan hasil
    $berhasil = [];
    $gagal = [];
    $catat = date('Y-m-d H:i:s');
    
    // Generate ID base yang unik
    $datePart = date('ymd');
    $timePart = date('His');
    $microPart = sprintf('%04d', (microtime(true) * 10000) % 10000);
    $randomPart = sprintf('%03d', mt_rand(0, 999));
    
    // Validasi dan parsing data faktur
    $parsed_faktur_ids = [];
    foreach ($faktur_ids as $item) {
        if (is_array($item) && isset($item['id']) && isset($item['source'])) {
            $parsed_faktur_ids[] = [
                'id' => $secu->injection($item['id']),
                'source' => $secu->injection($item['source'])
            ];
        } else if (is_string($item)) {
            $parsed_faktur_ids[] = [
                'id' => $secu->injection($item),
                'source' => 'transaksi_faktur'
            ];
        }
    }
    
    if (empty($parsed_faktur_ids)) {
        throw new Exception("Format faktur_ids tidak valid");
    }
    
    // Process setiap faktur
    foreach ($parsed_faktur_ids as $index => $faktur) {
        $id_tfk = $faktur['id'];
        $source_table = $faktur['source'];
        
        try {
            // Cek apakah faktur sudah pernah diproses
            $checkFaktur = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE id_tfk = :id_tfk");
            $checkFaktur->bindParam(':id_tfk', $id_tfk);
            $checkFaktur->execute();
            
            if ($checkFaktur->fetchColumn() > 0) {
                $gagal[] = [
                    'id_tfk' => $id_tfk,
                    'reason' => 'Faktur sudah pernah diproses sebelumnya'
                ];
                continue;
            }
            
            // Validasi faktur exists di tabel sumber
            if ($source_table == 'transaksi_faktur_pim') {
                $checkSource = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_pim WHERE id_tfk = :id_tfk AND id_out = :id_out");
            } else if ($source_table == 'transaksi_faktur_c') {
                $checkSource = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_c WHERE id_tfk = :id_tfk AND id_out = :id_out");
            } else {
                $checkSource = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur WHERE id_tfk = :id_tfk AND id_out = :id_out");
            }
            $checkSource->bindParam(':id_tfk', $id_tfk);
            $checkSource->bindParam(':id_out', $id_out);
            $checkSource->execute();
            
            if ($checkSource->fetchColumn() == 0) {
                $gagal[] = [
                    'id_tfk' => $id_tfk,
                    'reason' => 'Faktur tidak ditemukan atau outlet tidak sesuai'
                ];
                continue;
            }
            
            // Generate ID unik untuk pengiriman
            $counterPart = sprintf('%03d', $index + 1);
            $id_tfkkb = "KB{$datePart}{$timePart}{$microPart}{$randomPart}{$counterPart}";
            $status_tfkkb = "Belum Dikirim";
            $tgl_tfkkb = $tgl;
            
            // Insert ke transaksi_faktur_kirim_b dengan id_adm_local
            $insertPengiriman = $conn->prepare("
                INSERT INTO transaksi_faktur_kirim_b 
                (id_tfkkb, id_tfk, id_out, id_adm, tgl_tfkkb, status_tfkkb, ket_tfkkb, created_at, created_by, updated_at, updated_by) 
                VALUES 
                (:id_tfkkb, :id_tfk, :id_out, :id_adm, :tgl_tfkkb, :status_tfkkb, :ket_tfkkb, :created_at, :created_by, :updated_at, :updated_by)
            ");
            
            $insertPengiriman->bindParam(':id_tfkkb', $id_tfkkb);
            $insertPengiriman->bindParam(':id_tfk', $id_tfk);
            $insertPengiriman->bindParam(':id_out', $id_out);
            $insertPengiriman->bindParam(':id_adm', $id_adm_local); // PERBAIKAN: Gunakan id_adm_local
            $insertPengiriman->bindParam(':tgl_tfkkb', $tgl_tfkkb);
            $insertPengiriman->bindParam(':status_tfkkb', $status_tfkkb);
            $insertPengiriman->bindParam(':ket_tfkkb', $keterangan);
            $insertPengiriman->bindParam(':created_at', $catat);
            $insertPengiriman->bindParam(':created_by', $id_adm_local); // PERBAIKAN: Gunakan id_adm_local
            $insertPengiriman->bindParam(':updated_at', $catat);
            $insertPengiriman->bindParam(':updated_by', $id_adm_local); // PERBAIKAN: Gunakan id_adm_local
            
            if ($insertPengiriman->execute()) {
                // Update status di tabel sumber
                if ($source_table == 'transaksi_faktur_pim') {
                    $updateStatus = $conn->prepare("UPDATE transaksi_faktur_pim SET status_tfkkb = :status WHERE id_tfk = :id_tfk");
                } else if ($source_table == 'transaksi_faktur_c') {
                    $updateStatus = $conn->prepare("UPDATE transaksi_faktur_c SET status_tfkkb = :status WHERE id_tfk = :id_tfk");
                } else {
                    $updateStatus = $conn->prepare("UPDATE transaksi_faktur SET status_tfkkb = :status WHERE id_tfk = :id_tfk");
                }
                $updateStatus->bindParam(':status', $status_tfkkb);
                $updateStatus->bindParam(':id_tfk', $id_tfk);
                $updateStatus->execute();
                
                $berhasil[] = [
                    'id_tfkkb' => $id_tfkkb,
                    'id_tfk' => $id_tfk,
                    'source_table' => $source_table,
                    'nama_adm' => $nama_adm,
                    'id_adm_local' => $id_adm_local
                ];
            } else {
                $gagal[] = [
                    'id_tfk' => $id_tfk,
                    'reason' => 'Gagal menyimpan data pengiriman'
                ];
            }
            
        } catch (Exception $e) {
            $gagal[] = [
                'id_tfk' => $id_tfk,
                'reason' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    // Evaluasi hasil dan commit/rollback
    if (count($berhasil) > 0 && count($gagal) == 0) {
        $conn->commit();
        $status = 'success';
        $message = 'Semua pengiriman berhasil disimpan';
    } else if (count($berhasil) > 0 && count($gagal) > 0) {
        $conn->commit();
        $status = 'partial_success';
        $message = 'Sebagian pengiriman berhasil disimpan';
    } else {
        $conn->rollback();
        $status = 'error';
        $message = 'Semua pengiriman gagal disimpan';
    }
    
    // Response sukses
    echo json_encode([
        "status" => $status,
        "message" => $message,
        "nama_apl" => $nama_apl,
        "base_url_apl" => $baseUrl,
        "id_apl" => $id_apl,
        "date_processed" => $tgl,
        "total_submitted" => count($parsed_faktur_ids),
        "total_success" => count($berhasil),
        "total_failed" => count($gagal),
        "success_items" => $berhasil,
        "failed_items" => $gagal,
        "nama_adm_filter" => $nama_adm,
        "id_adm_local" => $id_adm_local
    ]);
    
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollback();
    }
    
    error_log("API Error createPengiriman: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
} finally {
    if (isset($conn)) {
        $conn = $base->close();
    }
}
?>
