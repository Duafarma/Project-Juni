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
$tgl = isset($_POST['tgl']) ? trim($_POST['tgl']) : date('Y-m-d');
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$id_out = isset($_POST['id_out']) ? $secu->injection($_POST['id_out']) : '';
$keyword = isset($_POST['keyword']) ? $secu->injection($_POST['keyword']) : '';
$nama_adm = isset($_POST['nama_adm']) ? $secu->injection($_POST['nama_adm']) : ''; // Filter berdasarkan nama admin

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
    
    // Cek apakah outlet benar-benar ada di database
    $cekOutlet = $conn->prepare("SELECT COUNT(*) FROM outlet WHERE id_out = :id_out");
    $cekOutlet->bindParam(':id_out', $id_out, PDO::PARAM_STR);
    $cekOutlet->execute();
    if ($cekOutlet->fetchColumn() == 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Outlet tidak ditemukan di cabang ini",
            "id_out" => $id_out
        ]);
        exit;
    }
    
    // PERBAIKAN: Filter berdasarkan nama_adm yang sama dengan session login
    $adminFilter = "";
    $params = [
        ':id_out' => $id_out,
        ':id_out_pim' => $id_out,
        ':id_out_c' => $id_out
    ];

    if (!empty($nama_adm)) {
        // Filter untuk input faktur: hanya tampilkan faktur yang belum ada di pengiriman ATAU
        // faktur yang sudah ada di pengiriman tapi dibuat oleh admin dengan nama yang sama
        $adminFilter = " AND (
            B.id_tfk IS NULL OR 
            EXISTS (
                SELECT 1 FROM adminz adm 
                WHERE adm.id_adm = B.id_adm 
                AND adm.nama_adm = :nama_adm
            )
        )";
        $params[':nama_adm'] = $nama_adm;
    }

    $whereClause = "";
    if (!empty($keyword)) {
        $whereClause = " AND A.kode_tfk LIKE :keyword";
        $params[':keyword'] = '%' . $keyword . '%';
        $params[':keyword_pim'] = '%' . $keyword . '%';
        $params[':keyword_c'] = '%' . $keyword . '%';
    }

    $tahunSekarang = date('Y');
    $whereTahun = " AND YEAR(A.tgl_tfk) = :tahun ";
    $whereTahunPim = " AND YEAR(P.tgl_tfk) = :tahun ";
    $whereTahunC = " AND YEAR(C.tgl_tfk) = :tahun ";
    $params[':tahun'] = $tahunSekarang;

    // PERBAIKAN UTAMA: Query dengan LEFT JOIN untuk memastikan faktur yang sudah ada di pengiriman tidak muncul
    $query = "
        -- Data dari transaksi_faktur
        SELECT 
            A.id_tfk,
            A.kode_tfk,
            DATE_FORMAT(A.tgl_tfk, '%d-%m-%Y') AS tgl_tfk,
            A.sediaan,
            A.jml_sediaan,
            A.status_tfkkb,
            'transaksi_faktur' AS source_table,
            'Faktur B' as jenis_faktur
        FROM 
            transaksi_faktur A
        LEFT JOIN 
            transaksi_faktur_kirim_b B ON A.id_tfk = B.id_tfk
        WHERE 
            A.id_out = :id_out 
            AND A.status_tfkkb = 'Belum Dikirim'
            AND B.id_tfk IS NULL" . $whereClause . $whereTahun . "
            
        UNION ALL
        
        -- Data dari transaksi_faktur_pim
        SELECT 
            P.id_tfk,
            P.kode_tfk,
            DATE_FORMAT(P.tgl_tfk, '%d-%m-%Y') AS tgl_tfk,
            P.sediaan,
            P.jml_sediaan,
            P.status_tfkkb,
            'transaksi_faktur_pim' AS source_table,
            'PIM' as jenis_faktur
        FROM 
            transaksi_faktur_pim P
        LEFT JOIN 
            transaksi_faktur_kirim_b B ON P.id_tfk = B.id_tfk
        WHERE 
            P.id_out = :id_out_pim 
            AND P.status_tfkkb = 'Belum Dikirim'
            AND B.id_tfk IS NULL" . ($whereClause ? str_replace('A.', 'P.', $whereClause) : '') . $whereTahunPim . "
        
        UNION ALL

        -- Data dari transaksi_faktur_c
        SELECT 
            C.id_tfk,
            C.kode_tfk,
            DATE_FORMAT(C.tgl_tfk, '%d-%m-%Y') AS tgl_tfk,
            C.sediaan,
            C.jml_sediaan,
            C.status_tfkkb,
            'transaksi_faktur_c' AS source_table,
            'Faktur C' as jenis_faktur
        FROM 
            transaksi_faktur_c C
        LEFT JOIN 
            transaksi_faktur_kirim_b B ON C.id_tfk = B.id_tfk
        WHERE 
            C.id_out = :id_out_c 
            AND C.status_tfkkb = 'Belum Dikirim'
            AND B.id_tfk IS NULL" . ($whereClause ? str_replace('A.', 'C.', $whereClause) : '') . $whereTahunC . "

        ORDER BY 
            tgl_tfk DESC
        LIMIT 100";
    
    $master = $conn->prepare($query);

    // Bind parameters
    foreach ($params as $param => $value) {
        $master->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $master->execute();
    $hasil = $master->fetchAll(PDO::FETCH_ASSOC);

    // Jika tidak ada data, tetap kembalikan array kosong
    if (!$hasil || count($hasil) == 0) {
        echo json_encode([
            "status" => "success",
            "result" => [],
            "message" => "Tidak ada faktur belum dikirim untuk outlet ini",
            "id_out" => $id_out,
            "nama_apl" => $nama_apl,
            "filter_nama_adm" => $nama_adm
        ]);
        exit;
    }

    // Pastikan field id_apl, id_out_api, api_source, nama_apl selalu ada
    foreach ($hasil as &$item) {
        $item['api_source'] = $nama_apl ?? $id_apl;
        $item['id_apl'] = $id_apl;
        $item['id_out_api'] = $id_out;
        $item['nama_apl'] = $nama_apl ?? '';
        $item['formatted_sediaan'] = !empty($item['sediaan']) ? $item['sediaan'] : '-';
        $item['formatted_jml_sediaan'] = !empty($item['jml_sediaan']) ? number_format($item['jml_sediaan'], 0, ',', '.') : '-';
        $item['sediaan_display'] = $item['formatted_sediaan'] . ' (' . $item['formatted_jml_sediaan'] . ')';
    }
    unset($item);

    // Response sukses
    echo json_encode([
        "status" => "success",
        "result" => $hasil,
        "base_url_apl" => $baseUrl,
        "id_apl" => $id_apl,
        "nama_apl" => $nama_apl,
        "id_out" => $id_out,
        "keyword" => $keyword,
        "date_requested" => $tgl,
        "total_records" => count($hasil),
        "filter_nama_adm" => $nama_adm
    ]);
    
} catch (Exception $e) {
    error_log("API Error getFakturBelumKirim: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn = $base->close();
    }
}
?>
