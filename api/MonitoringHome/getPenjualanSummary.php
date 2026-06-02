<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');

$secu = new Security;
$base = new DB;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl  = isset($_POST['id_apl'])  ? $secu->injection($_POST['id_apl'])  : '';

if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'periode' wajib dalam format YYYY-MM"]);
    exit;
}
if ($encrypt === '' || $id_apl === '') {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

[$year, $month] = explode('-', $periode);
$year  = (int)$year;
$month = (int)$month;

try {
    $conn = $base->open();

    $stmtApl = $conn->prepare("SELECT key_apl, base_url_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->execute([':id_apl' => $id_apl]);
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);

    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error" => "Aplikasi tidak ditemukan atau tidak aktif"]);
        exit;
    }

    $sourceKey = $apl['key_apl'] ?? '';
    if (md5($periode . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }

    // Check tabel existence
    $hasFakturC = false;
    $hasDetailC = false;
    $hasFakturPim = false;
    $hasDetailPim = false;
    
    try {
        $check = $conn->query("SHOW TABLES LIKE 'transaksi_faktur_c'");
        $hasFakturC = $check && $check->fetchColumn();
    } catch (Exception $e) {}
    
    try {
        $check = $conn->query("SHOW TABLES LIKE 'transaksi_fakturdetail_c'");
        $hasDetailC = $check && $check->fetchColumn();
    } catch (Exception $e) {}
    
    try {
        $check = $conn->query("SHOW TABLES LIKE 'transaksi_faktur_pim'");
        $hasFakturPim = $check && $check->fetchColumn();
    } catch (Exception $e) {}
    
    try {
        $check = $conn->query("SHOW TABLES LIKE 'transaksi_fakturdetail_pim'");
        $hasDetailPim = $check && $check->fetchColumn();
    } catch (Exception $e) {}

    $sqlFaktur = "
        SELECT 
            id_out,
            id_tfk,
            CASE 
                WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                ELSE tgl_tfk 
            END AS tgl_eff
        FROM transaksi_faktur
    ";
    
    if ($hasFakturC) {
        $sqlFaktur .= "
            UNION ALL
            SELECT 
                id_out,
                id_tfk,
                CASE 
                    WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                    ELSE tgl_tfk 
                END AS tgl_eff
            FROM transaksi_faktur_c
        ";
    }
    
    if ($hasFakturPim) {
        $sqlFaktur .= "
            UNION ALL
            SELECT 
                id_out,
                id_tfk,
                CASE 
                    WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                    ELSE tgl_tfk 
                END AS tgl_eff
            FROM transaksi_faktur_pim
        ";
    }
    
    $sqlDetail = "
        SELECT id_tfk, SUM(total_tfd) as total_tfd
        FROM transaksi_fakturdetail
        GROUP BY id_tfk
    ";
    
    if ($hasDetailC) {
        $sqlDetail .= "
            UNION ALL
            SELECT id_tfk, SUM(total_tfd) as total_tfd
            FROM transaksi_fakturdetail_c
            GROUP BY id_tfk
        ";
    }
    
    if ($hasDetailPim) {
        $sqlDetail .= "
            UNION ALL
            SELECT id_tfk, SUM(total_tfd) as total_tfd
            FROM transaksi_fakturdetail_pim
            GROUP BY id_tfk
        ";
    }

    $stmt = $conn->prepare("
        SELECT 
            COALESCE(COUNT(DISTINCT tf.id_out), 0) as total_transaksi,
            COALESCE(SUM(tfd.total_tfd), 0) as total_sesudah_ppn
        FROM ({$sqlFaktur}) tf
        INNER JOIN ({$sqlDetail}) tfd ON tfd.id_tfk = tf.id_tfk
        WHERE YEAR(tf.tgl_eff) = :y
          AND MONTH(tf.tgl_eff) = :m
    ");
    $stmt->execute([':y' => $year, ':m' => $month]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $result = [
        "periode"           => $periode,
        "total_transaksi"   => (int)($row['total_transaksi'] ?? 0),
        "total_sesudah_ppn" => (float)($row['total_sesudah_ppn'] ?? 0),
    ];

    echo json_encode([
        "result" => $result,
        "id_apl" => $id_apl,
        "nama_apl" => $apl['nama_apl'] ?? '',
        "periode_requested" => $periode,
        "timestamp" => date('c')
    ]);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error"]);
    exit;
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}
?>