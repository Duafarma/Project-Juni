<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

header('Content-Type: application/json');

// Validasi parameter
$tgl = isset($_POST['tgl']) ? trim($_POST['tgl']) : date('Y-m-d');
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_tfkkb = isset($_POST['id_tfkkb']) ? $secu->injection($_POST['id_tfkkb']) : '';

// Validasi encrypt
$source = $data->self_apl();
$sourceKey = $source['key_apl'];

if (md5($tgl . "#" . $sourceKey) !== $encrypt) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
    exit;
}

if (empty($id_tfkkb)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'id_tfkkb' wajib diisi"]);
    exit;
}

try {
    // PERBAIKAN: Query yang lebih lengkap untuk mengambil detail pengiriman
    $query = "
        SELECT 
            tkb.*,
            tf.kode_tfk,
            tf.id_out,
            tf.sediaan,
            tf.jml_sediaan,
            o.nama_out,
            a.nama_adm,
            'transaksi_faktur' as source_table
        FROM transaksi_faktur_kirim_b tkb
        LEFT JOIN transaksi_faktur tf ON tf.id_tfk = tkb.id_tfk
        LEFT JOIN outlet o ON o.id_out = tf.id_out
        LEFT JOIN adminz a ON a.id_adm = tkb.id_adm
        WHERE tkb.id_tfkkb = :id_tfkkb
        
        UNION ALL
        
        SELECT 
            tkb.*,
            tfp.kode_tfk,
            tfp.id_out,
            tfp.sediaan,
            tfp.jml_sediaan,
            o.nama_out,
            a.nama_adm,
            'transaksi_faktur_pim' as source_table
        FROM transaksi_faktur_kirim_b tkb
        LEFT JOIN transaksi_faktur_pim tfp ON tfp.id_tfk = tkb.id_tfk
        LEFT JOIN outlet o ON o.id_out = tfp.id_out
        LEFT JOIN adminz a ON a.id_adm = tkb.id_adm
        WHERE tkb.id_tfkkb = :id_tfkkb2
        
        UNION ALL
        
        SELECT 
            tkb.*,
            tfc.kode_tfk,
            tfc.id_out,
            tfc.sediaan,
            tfc.jml_sediaan,
            o.nama_out,
            a.nama_adm,
            'transaksi_faktur_c' as source_table
        FROM transaksi_faktur_kirim_b tkb
        LEFT JOIN transaksi_faktur_c tfc ON tfc.id_tfk = tkb.id_tfk
        LEFT JOIN outlet o ON o.id_out = tfc.id_out
        LEFT JOIN adminz a ON a.id_adm = tkb.id_adm
        WHERE tkb.id_tfkkb = :id_tfkkb3
        
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id_tfkkb', $id_tfkkb);
    $stmt->bindParam(':id_tfkkb2', $id_tfkkb);
    $stmt->bindParam(':id_tfkkb3', $id_tfkkb);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // PERBAIKAN: Pastikan data tidak null/kosong
        if (empty($result['kode_tfk']) || empty($result['nama_out'])) {
            // Coba cari data dengan query alternatif jika ada masalah JOIN
            $altQuery = "
                SELECT 
                    tkb.id_tfkkb,
                    tkb.id_tfk,
                    tkb.ket_tfkkb,
                    tkb.status_tfkkb,
                    tkb.tgl_tfkkb,
                    a.nama_adm,
                    COALESCE(tf.kode_tfk, tfp.kode_tfk, tfc.kode_tfk) as kode_tfk,
                    COALESCE(tf.id_out, tfp.id_out, tfc.id_out) as id_out,
                    COALESCE(tf.sediaan, tfp.sediaan, tfc.sediaan) as sediaan,
                    COALESCE(tf.jml_sediaan, tfp.jml_sediaan, tfc.jml_sediaan) as jml_sediaan,
                    o.nama_out,
                    CASE 
                        WHEN tf.id_tfk IS NOT NULL THEN 'transaksi_faktur'
                        WHEN tfp.id_tfk IS NOT NULL THEN 'transaksi_faktur_pim'
                        WHEN tfc.id_tfk IS NOT NULL THEN 'transaksi_faktur_c'
                        ELSE 'unknown'
                    END as source_table
                FROM transaksi_faktur_kirim_b tkb
                LEFT JOIN adminz a ON a.id_adm = tkb.id_adm
                LEFT JOIN transaksi_faktur tf ON tf.id_tfk = tkb.id_tfk
                LEFT JOIN transaksi_faktur_pim tfp ON tfp.id_tfk = tkb.id_tfk
                LEFT JOIN transaksi_faktur_c tfc ON tfc.id_tfk = tkb.id_tfk
                LEFT JOIN outlet o ON o.id_out = COALESCE(tf.id_out, tfp.id_out, tfc.id_out)
                WHERE tkb.id_tfkkb = :id_tfkkb
            ";
            
            $altStmt = $conn->prepare($altQuery);
            $altStmt->bindParam(':id_tfkkb', $id_tfkkb);
            $altStmt->execute();
            $altResult = $altStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($altResult && !empty($altResult['kode_tfk']) && !empty($altResult['nama_out'])) {
                $result = $altResult;
            }
        }
        
        // PERBAIKAN: Tambahkan validasi final dan default values
        $response = [
            'id_tfkkb' => $result['id_tfkkb'] ?? $id_tfkkb,
            'kode_tfk' => $result['kode_tfk'] ?? 'Data tidak tersedia',
            'nama_out' => $result['nama_out'] ?? 'Data tidak tersedia',
            'ket_tfkkb' => $result['ket_tfkkb'] ?? '',
            'status_tfkkb' => $result['status_tfkkb'] ?? 'Unknown',
            'nama_adm' => $result['nama_adm'] ?? 'Unknown',
            'source_table' => $result['source_table'] ?? 'unknown',
            'sediaan' => $result['sediaan'] ?? '',
            'jml_sediaan' => $result['jml_sediaan'] ?? ''
        ];
        
        echo json_encode([
            "status" => "success",
            "data" => $response
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Data tidak ditemukan",
            "id_tfkkb" => $id_tfkkb
        ]);
    }
    
} catch (Exception $e) {
    error_log("API Error getPengirimanDetail: " . $e->getMessage());
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "id_tfkkb" => $id_tfkkb
    ]);
}

$conn = $base->close();
?>