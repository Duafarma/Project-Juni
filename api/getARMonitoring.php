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
header('Pragma: no-cache');
header('Expires: 0');

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl  = isset($_POST['id_apl'])  ? $secu->injection($_POST['id_apl']) : '';
$special_mode = isset($_POST['special_mode']) ? trim($_POST['special_mode']) : '';

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

    // Validasi encrypt dengan fallback ke self-config
    $apl = null;

    $stmtApl = $conn->prepare(
        "SELECT id_apl, key_apl, base_url_apl, nama_apl
         FROM aplikasi 
         WHERE id_apl = :id_apl AND active_apl = 1 
         LIMIT 1"
    );
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);

    if (!$apl) {
        // Fallback: ambil config lokal cabang (self-record pertama aktif)
        $stmtSelf = $conn->query(
            "SELECT id_apl, key_apl, base_url_apl, nama_apl 
             FROM aplikasi 
             WHERE active_apl = 1 
             ORDER BY id_apl 
             LIMIT 1"
        );
        $apl = $stmtSelf ? $stmtSelf->fetch(PDO::FETCH_ASSOC) : null;
    }

    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error" => "Konfigurasi aplikasi tidak ditemukan"]);
        exit;
    }

    $sourceKey = $apl['key_apl'];
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

    $piutangSourceSql = $specialFakturTables
        ? "
            SELECT id_tfk, id_out, total_tfk, tgl_tfk
            FROM {$specialFakturTables[0]}
            UNION ALL
            SELECT id_tfk, id_out, total_tfk, tgl_tfk
            FROM {$specialFakturTables[1]}
            UNION ALL
            SELECT id_tfk, id_out, total_tfk, tgl_tfk
            FROM {$specialFakturTables[2]}
        "
        : "
            SELECT id_tfk, id_out, total_tfk, tgl_tfk
            FROM transaksi_faktur
            UNION ALL
            SELECT id_tfk, id_out, total_tfk, tgl_tfk
            FROM transaksi_faktur_c
        ";

    if (md5($periode . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized"]);
        exit;
    }

    // Kategori AR berdasarkan UMUR FAKTUR (bukan status_pembayaran outlet)
    // Hijau  : 0 – 30 hari
    // Kuning : 31 – 60 hari
    // Orange : 61 – 90 hari
    // Merah  : > 90 hari

    $arData = [
        'hijau'  => ['count' => 0, 'nominal' => 0, 'faktur' => 0],
        'kuning' => ['count' => 0, 'nominal' => 0, 'faktur' => 0],
        'orange' => ['count' => 0, 'nominal' => 0, 'faktur' => 0],
        'merah'  => ['count' => 0, 'nominal' => 0, 'faktur' => 0],
        'total_piutang' => 0,
        'total_outlet'  => 0,
        'total_faktur'  => 0
    ];

    // Query semua faktur belum dibayar beserta umur-nya
    $sqlPiutang = "
        SELECT 
            tf.id_tfk,
            tf.id_out,
            tf.total_tfk,
            DATEDIFF(CURDATE(), tf.tgl_tfk) AS umur_hari
        FROM (
            {$piutangSourceSql}
        ) tf
        WHERE tf.id_tfk NOT IN (
            SELECT DISTINCT id_tfk 
            FROM pembayaran_faktur 
            WHERE id_tfk IS NOT NULL
        )
    ";

    $stmtPiutang = $conn->query($sqlPiutang);
    if ($stmtPiutang) {
        $outletPerBucket = ['hijau' => [], 'kuning' => [], 'orange' => [], 'merah' => []];

        foreach ($stmtPiutang->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $umur   = intval($row['umur_hari'] ?? 0);
            $nominal = floatval($row['total_tfk'] ?? 0);
            $idOut  = $row['id_out'];

            if ($umur <= 30) {
                $bucket = 'hijau';
            } elseif ($umur <= 60) {
                $bucket = 'kuning';
            } elseif ($umur <= 90) {
                $bucket = 'orange';
            } else {
                $bucket = 'merah';
            }

            $arData[$bucket]['nominal'] += $nominal;
            $arData[$bucket]['faktur']  += 1;
            $arData['total_piutang']    += $nominal;
            $arData['total_faktur']     += 1;

            if ($idOut) {
                $outletPerBucket[$bucket][$idOut] = true;
            }
        }

        // Hitung jumlah outlet unik per bucket
        foreach (['hijau', 'kuning', 'orange', 'merah'] as $b) {
            $arData[$b]['count'] = count($outletPerBucket[$b]);
        }
    }

    // Total outlet unik dari semua bucket
    $arData['total_outlet'] = count(array_unique(array_merge(
        array_keys($outletPerBucket['hijau'] ?? []),
        array_keys($outletPerBucket['kuning'] ?? []),
        array_keys($outletPerBucket['orange'] ?? []),
        array_keys($outletPerBucket['merah']  ?? [])
    )));

    echo json_encode([
        "status" => "success",
        "result" => $arData,
        "periode" => $periode,
        "nama_cabang" => $apl['nama_apl'] ?? '',
        "special_mode" => $special_mode,
        "timestamp" => date('c')
    ]);

} catch (Exception $e) {
    error_log("API AR Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
} finally {
    if (isset($base) && method_exists($base, 'close')) {
        $base->close();
    }
}
?>
