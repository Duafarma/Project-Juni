<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
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
$tgl = isset($_POST['tgl']) ? trim($_POST['tgl']) : '';
$tgl_3_months_ago = isset($_POST['tgl_3_months_ago']) ? trim($_POST['tgl_3_months_ago']) : '';
$key = isset($_POST['key']) ? trim($_POST['key']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$filter_pending = isset($_POST['filter_pending']) ? $_POST['filter_pending'] : 'false';

// Validasi input
if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

// Validasi format tanggal
if (empty($tgl) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    http_response_code(400);
    echo json_encode(["error" => "Format tanggal tidak valid. Gunakan YYYY-MM-DD"]);
    exit;
}

// Jika tgl_3_months_ago kosong, hitung sendiri
if (empty($tgl_3_months_ago)) {
    $tgl_3_months_ago = date('Y-m-d', strtotime($tgl . ' -3 months'));
}

// Validasi tanggal dengan DateTime
$date_obj = DateTime::createFromFormat('Y-m-d', $tgl);
$date_obj_3_months = DateTime::createFromFormat('Y-m-d', $tgl_3_months_ago);
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
    
    // WHERE clause dengan filter pending dan 3 bulan terakhir
    $whereClause = "WHERE A.id_tfk IS NOT NULL 
                    AND A.tgl_tfk >= :tgl_3_months_ago 
                    AND A.tgl_tfk <= :tgl";
    
    // Tambahkan filter pending jika diminta
    if ($filter_pending === 'true') {
        // hanya anggap selesai kalau TFK (A.status_tfkkb) sudah dikirim,
        // dokumen sudah balik, dan TFK final (A.status_tfkkf) sudah selesai.
        $whereClause .= " AND NOT (
                            A.status_tfkkb = 'Sudah Dikirim' 
                            AND A.status_dokumen = 'sudah balik'
                            AND COALESCE(A.status_tfkkf, 'Belum Dikirim') = 'Sudah Dikirim'
                        )";
    }
    
    $params = [
        ':tgl_3_months_ago' => $tgl_3_months_ago,
        ':tgl' => $tgl
    ];
    
    if (!empty($key)) {
        $whereClause .= " AND (A.kode_tfk LIKE :key OR B.nama_out LIKE :key)";
        $params[':key'] = '%' . $key . '%';
    }
    
    $query = "SELECT 
                'api' as source,
                A.id_tfk,
                A.kode_tfk,
                A.id_out,
                A.cito,
                A.status_failing,
                A.status_dokumen,
                A.upload_f_pajak,
                A.tgl_tfk,
                A.created_at,
                A.status_ceklis,
                COALESCE(A.status_tfkkb, 'Belum Dikirim') AS status_tfkkb,                        
                B.nama_out,
                B.ofcode_out,
                COALESCE(A.status_tfkkf, 'Belum Dikirim') AS status_tfkkf,
                'Cendo & DPE' as jenis_faktur
            FROM transaksi_faktur AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            LEFT JOIN transaksi_faktur_kirim_b AS C ON A.id_tfk = C.id_tfk
            $whereClause
            
            UNION ALL
            
            SELECT 
                'api' as source,
                A.id_tfk,
                A.kode_tfk,
                A.id_out,
                A.cito,
                A.status_failing,
                A.status_dokumen,
                A.upload_f_pajak,
                A.tgl_tfk,
                A.created_at,
                A.status_ceklis,
                COALESCE(A.status_tfkkb, 'Belum Dikirim') AS status_tfkkb,                        
                B.nama_out,
                B.ofcode_out,
                COALESCE(A.status_tfkkf, 'Belum Dikirim') AS status_tfkkf,
                'PIM' as jenis_faktur
            FROM transaksi_faktur_pim AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out 
            LEFT JOIN transaksi_faktur_kirim_b AS C ON A.id_tfk = C.id_tfk
            $whereClause
            
            ORDER BY tgl_tfk DESC, created_at DESC";
    
    $master = $conn->prepare($query);
    
    // Bind parameters
    foreach ($params as $param => $value) {
        $master->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $master->execute();
    $hasil = $master->fetchAll(PDO::FETCH_ASSOC);
    
    // Add nama_apl to each result item dan persingkat kode_tfk
    foreach ($hasil as &$item) {
        $item['nama_apl'] = $nama_apl;
        
        // Persingkat kode_tfk - hapus segmen terakhir setelah slash terakhir, pertahankan trailing slash
        $displayKodeTfk = $item['kode_tfk'];
        $lastSlashPos = strrpos($displayKodeTfk, '/');
        if ($lastSlashPos !== false && $lastSlashPos < strlen($displayKodeTfk) - 1) {
            // Potong sampai slash terakhir (termasuk slash)
            $displayKodeTfk = substr($displayKodeTfk, 0, $lastSlashPos + 1);
        }

        // Jika ada slash di string tetapi tidak ada trailing slash, tambahkan satu
        if (strpos($displayKodeTfk, '/') !== false && substr($displayKodeTfk, -1) !== '/') {
            $displayKodeTfk .= '/';
        }

        // Batasi maksimal 15 karakter, usahakan tidak memotong trailing slash/nama segmen
        if (strlen($displayKodeTfk) > 15) {
            $trunc = substr($displayKodeTfk, 0, 15);
            if (substr($trunc, -1) !== '/') {
                $p = strrpos($trunc, '/');
                if ($p !== false) {
                    $trunc = substr($trunc, 0, $p + 1);
                }
            }
            $item['kode_tfk'] = $trunc;
        } else {
            $item['kode_tfk'] = $displayKodeTfk;
        }
    }
    
    // Response sukses
    echo json_encode([
        "result" => $hasil,
        "base_url_apl" => $baseUrl,
        "id_apl" => $id_apl,
        "nama_apl" => $nama_apl,
        "date_requested" => $tgl,
        "date_range_start" => $tgl_3_months_ago,
        "filter_pending" => $filter_pending,
        "total_records" => count($hasil)
    ]);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} finally {
    if (isset($conn)) {
        $base->close();
    }
}
?>