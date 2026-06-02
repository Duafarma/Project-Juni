<?php
ob_start();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ob_clean();
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$tgl  = date('Y-m-d');

// Auth — gunakan self_apl() yang tersedia di semua versi data.php
$encrypt   = $secu->injection($_GET['encrypt'] ?? '');
$appData   = $data->self_apl();
$sourceKey = $appData['key_apl'] ?? '';

if (empty($sourceKey) || md5($tgl . '#' . $sourceKey) !== $encrypt) {
    ob_clean();
    http_response_code(401);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Params — key = outlet_produk_tgl1_tgl2 (same convention as laporan XLS)
$cari  = $secu->injection($_GET['key'] ?? '');
$pecah = explode('_', $cari);
$outletVal = $pecah[0] ?? '';
$produkVal = $pecah[1] ?? '';
$tgl1Val   = $pecah[2] ?? '';
$tgl2Val   = $pecah[3] ?? '';

// Build WHERE conditions with named params to avoid injection
$principle  = 'MP0000000002';
$conditions = array(
    "A.id_tfd != ''",
    "D.status_out = 'Active'",
    'C.nama_p = :principle',
);
$params = array(':principle' => $principle);

if (!empty($outletVal)) { $conditions[] = 'B.id_out = :outlet'; $params[':outlet'] = $outletVal; }
if (!empty($produkVal)) { $conditions[] = 'A.id_pro = :produk'; $params[':produk'] = $produkVal; }
if (!empty($tgl1Val))   { $conditions[] = 'B.tgl_tfk >= :tgl1'; $params[':tgl1']   = $tgl1Val;   }
if (!empty($tgl2Val))   { $conditions[] = 'B.tgl_tfk <= :tgl2'; $params[':tgl2']   = $tgl2Val;   }

$where = implode(' AND ', $conditions);
$conn  = $base->open();

try {
    $qMaster = "SELECT
                    B.kode_tfk,
                    B.tgl_tfk,
                    D.kode_rs,
                    D.nama_out,
                    SUM(A.total_tfd) AS subtot_tfk
                FROM transaksi_fakturdetail AS A
                LEFT JOIN transaksi_faktur  AS B ON A.id_tfk = B.id_tfk
                LEFT JOIN produk            AS C ON A.id_pro = C.id_pro
                LEFT JOIN outlet            AS D ON B.id_out = D.id_out
                WHERE $where
                GROUP BY B.id_tfk, B.kode_tfk, B.tgl_tfk, D.kode_rs, D.nama_out
                ORDER BY B.tgl_tfk DESC, CAST(B.kode_tfk AS UNSIGNED) DESC";

    $stmt = $conn->prepare($qMaster);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $conn = $base->close();

    ob_clean();
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'result' => $rows]);

} catch (Throwable $e) {
    $conn = $base->close();
    ob_clean();
    http_response_code(500);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
