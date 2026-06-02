<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');

$secu = new Security;
$base = new DB;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$encrypt = isset($_POST['encrypt']) ? trim($_POST['encrypt']) : '';
$id_apl  = isset($_POST['id_apl'])  ? $secu->injection($_POST['id_apl']) : '';
$search  = isset($_POST['search'])  ? trim($_POST['search'])              : '';
$special_mode = isset($_POST['special_mode']) ? trim($_POST['special_mode']) : '';
$page    = isset($_POST['page'])    ? max(1, intval($_POST['page']))       : 1;
$limit   = isset($_POST['limit'])   ? max(1, intval($_POST['limit']))      : 10;
$offset  = ($page - 1) * $limit;

if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'periode' wajib dalam format YYYY-MM"]);
    exit;
}
if (empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

try {
    $conn = $base->open();

    // ==== FIX: validasi encrypt tanpa bergantung id_apl pusat ===-
    // Ambil semua key aktif di cabang, lalu cocokkan encrypt.
    $stmtKeys = $conn->query("SELECT id_apl, key_apl FROM aplikasi WHERE active_apl = 1");
    $keys = $stmtKeys ? $stmtKeys->fetchAll(PDO::FETCH_ASSOC) : [];

    if (!$keys) {
        http_response_code(404);
        echo json_encode(["error" => "Konfigurasi aplikasi (key) tidak ditemukan"]);
        exit;
    }

    $authorized = false;
    $matchedLocalId = null;
    foreach ($keys as $k) {
        if (md5($periode . "#" . $k['key_apl']) === $encrypt) {
            $authorized = true;
            $matchedLocalId = $k['id_apl'];
            break;
        }
    }

    if (!$authorized) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    // =====================================================================
    // VALIDASI KEY: cari key_apl dari tabel aplikasi berdasarkan id_apl
    // Tabel aplikasi di cabang harus ada record dengan id_apl yang sesuai,
    // ATAU gunakan key dari config lokal cabang
    // =====================================================================
    $keyApl = null;

    // Coba cari di tabel aplikasi lokal (self-record cabang ini)
    $stmtApl = $conn->prepare(
        "SELECT key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1"
    );
    $stmtApl->execute([':id_apl' => $id_apl]);
    $aplRow = $stmtApl->fetch(PDO::FETCH_ASSOC);

    if ($aplRow) {
        $keyApl = $aplRow['key_apl'];
    } else {
        // Fallback: ambil key_apl dari record pertama aktif (self-cabang)
        $stmtSelf = $conn->query(
            "SELECT key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY id_apl ASC LIMIT 1"
        );
        $selfRow = $stmtSelf->fetch(PDO::FETCH_ASSOC);
        if ($selfRow) {
            $keyApl = $selfRow['key_apl'];
        }
    }

    if (!$keyApl) {
        http_response_code(404);
        echo json_encode(["error" => "Konfigurasi aplikasi tidak ditemukan"]);
        exit;
    }

    // Validasi encrypt
    if (md5($periode . "#" . $keyApl) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    $specialFakturTables = null;
    if ($special_mode === 'malang_b') {
        $specialFakturTables = [
            'transaksi_faktur_np_malang',
            'transaksi_faktur_np_malang_b',
            'transaksi_faktur_np_malang_c',
        ];
    } elseif ($special_mode === 'medan_b') {
        $specialFakturTables = [
            'transaksi_faktur_np_medan',
            'transaksi_faktur_b_medan',
            'transaksi_faktur_c_medan',
        ];
    }

    $fakturSourceSql = $specialFakturTables
        ? "
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, total_tfk
            FROM {$specialFakturTables[0]}
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, total_tfk
            FROM {$specialFakturTables[1]}
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, total_tfk
            FROM {$specialFakturTables[2]}
        "
        : "
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, total_tfk
            FROM transaksi_faktur
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, id_out, total_tfk
            FROM transaksi_faktur_c
        ";

    // =====================================================================
    // Query faktur belum dibayar (gabungan transaksi_faktur + transaksi_faktur_c)
    // =====================================================================
    $whereClause = "WHERE tf.id_tfk NOT IN (
                        SELECT DISTINCT pf.id_tfk 
                        FROM pembayaran_faktur pf 
                        WHERE pf.id_tfk IS NOT NULL
                    )";
    $params = [];

    if (!empty($search)) {
        $whereClause .= " AND (tf.kode_tfk LIKE :search OR CAST(tf.id_tfk AS CHAR) LIKE :search2)";
        $params[':search']  = '%' . $search . '%';
        $params[':search2'] = '%' . $search . '%';
    }

    // Count total
    $sqlCount = "
        SELECT COUNT(*) as total 
        FROM (
            {$fakturSourceSql}
        ) tf
        LEFT JOIN outlet o ON tf.id_out = o.id_out
        $whereClause
    ";
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRow = $stmtCount->fetch(PDO::FETCH_ASSOC);
    $total = intval($totalRow['total'] ?? 0);

    // Fetch data dengan pagination
    $sql = "
        SELECT tf.id_tfk, tf.kode_tfk, tf.tgl_tfk, 
               COALESCE(o.nama_out, '-') as nama_out, 
               tf.total_tfk
        FROM (
            {$fakturSourceSql}
        ) tf
        LEFT JOIN outlet o ON tf.id_out = o.id_out
        $whereClause
        ORDER BY tf.tgl_tfk ASC
        LIMIT :limitVal OFFSET :offsetVal
    ";
    $stmt = $conn->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limitVal',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offsetVal', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data"        => $rows,
        "total"       => $total,
        "page"        => $page,
        "limit"       => $limit,
        "id_apl_requested" => $id_apl,
        "id_apl_local"     => $matchedLocalId,
        "special_mode" => $special_mode,
        "timestamp"   => date('c')
    ]);

} catch (Exception $e) {
    error_log("getFakturTerlama Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} finally {
    if (isset($base) && method_exists($base, 'close')) {
        $base->close();
    }
}
?>