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

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$nama_out_raw = isset($_POST['nama_out']) ? trim($_POST['nama_out']) : '';
$encrypt      = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl       = isset($_POST['id_apl'])  ? $secu->injection($_POST['id_apl'])  : '';

if (empty($nama_out_raw) || empty($encrypt) || empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameters 'nama_out', 'encrypt' dan 'id_apl' wajib diisi"]);
    exit;
}

try {
    $conn = $base->open();

    $stmtApl = $conn->prepare("SELECT key_apl, base_url_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
    if (!$apl) {
        throw new Exception("Aplikasi tidak ditemukan atau tidak aktif");
    }

    $sourceKey = $apl['key_apl'];
    $baseUrl   = $apl['base_url_apl'];
    $nama_apl  = $apl['nama_apl'];

    if (md5($nama_out_raw . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }

    // compute months 0..2 (0 = current month, 1 = previous, 2 = two months ago)
    $firstOfThisMonth = new DateTime(date('Y-m-01'));
    $months = [];
    for ($i = 0; $i <= 2; $i++) {
        $d = clone $firstOfThisMonth;
        $d->modify("-{$i} month");
        $months[$i] = [
            'start' => $d->format('Y-m-01 00:00:00'),
            'end'   => (clone $d)->modify('last day of this month')->format('Y-m-d 23:59:59'),
            'label' => $d->format('M Y')
        ];
    }

    $namaLike = '%' . $nama_out_raw . '%';

    // ============================================================
    // FIX 1: Gunakan nama parameter UNIK untuk setiap penggunaan
    //         PDO tidak mendukung reuse nama parameter yang sama
    //         dalam 1 prepared statement.
    //
    // FIX 2: Gabungkan transaksi_faktur + transaksi_faktur_pim
    //         menjadi 1 subquery menggunakan UNION ALL,
    //         sehingga SUM dilakukan 1 kali saja per outlet.
    //         Ini menghindari double-count dan duplikasi parameter.
    //
    // FIX 3: Subquery outlet_alamat dan outlet_diskon sudah
    //         menggunakan GROUP BY untuk hindari duplikasi baris.
    // ============================================================

    $sql = "
        SELECT
            cb.id_cb,
            cb.id_out,
            o.nama_out,
            kot.nama_kot          AS tipe_outlet,
            rp.nama_rpo           AS provinsi,
            rk.nama_rkb           AS kota,
            o.ofcode_out,
            od.top_odi,
            COALESCE(cb.diskon_off, 0) AS diskon_off,
            cb.pic,
            cb.bank,
            cb.nomor_rekening,
            cb.status,
            cb.created_at,
            -- FIX: total dari UNION ALL transaksi_faktur + transaksi_faktur_pim
            COALESCE(tf_union.m0, 0) AS total_m0,
            COALESCE(tf_union.m1, 0) AS total_m1,
            COALESCE(tf_union.m2, 0) AS total_m2
        FROM cashback cb
        LEFT JOIN outlet o ON cb.id_out = o.id_out
        LEFT JOIN kategori_outlet kot ON o.id_kot = kot.id_kot

        -- satu baris alamat per outlet
        LEFT JOIN (
            SELECT id_out,
                   MIN(id_rpo) AS id_rpo,
                   MIN(id_rkb) AS id_rkb
            FROM outlet_alamat
            GROUP BY id_out
        ) oa ON o.id_out = oa.id_out
        LEFT JOIN regional_provinsi rp ON oa.id_rpo = rp.id_rpo
        LEFT JOIN regional_kabupaten rk ON oa.id_rkb = rk.id_rkb

        -- satu baris diskon per outlet
        LEFT JOIN (
            SELECT id_out,
                   MAX(top_odi) AS top_odi
            FROM outlet_diskon
            GROUP BY id_out
        ) od ON o.id_out = od.id_out

        -- FIX: UNION ALL dua tabel transaksi, lalu SUM sekali
        LEFT JOIN (
            SELECT
                id_out,
                SUM(CASE WHEN created_at >= :s0 AND created_at <= :e0
                         THEN COALESCE(total_tfk, 0) ELSE 0 END) AS m0,
                SUM(CASE WHEN created_at >= :s1 AND created_at <= :e1
                         THEN COALESCE(total_tfk, 0) ELSE 0 END) AS m1,
                SUM(CASE WHEN created_at >= :s2 AND created_at <= :e2
                         THEN COALESCE(total_tfk, 0) ELSE 0 END) AS m2
            FROM (
                SELECT id_out, created_at, total_tfk FROM transaksi_faktur
                UNION ALL
                SELECT id_out, created_at, total_tfk FROM transaksi_faktur_pim
            ) AS gabungan_tf
            GROUP BY id_out
        ) tf_union ON o.id_out = tf_union.id_out

        WHERE o.nama_out LIKE :nama
        ORDER BY cb.created_at DESC
    ";

    $st = $conn->prepare($sql);

    // Setiap nama parameter hanya digunakan 1x — tidak ada duplikasi
    $st->bindValue(':s0',   $months[0]['start'], PDO::PARAM_STR);
    $st->bindValue(':e0',   $months[0]['end'],   PDO::PARAM_STR);
    $st->bindValue(':s1',   $months[1]['start'], PDO::PARAM_STR);
    $st->bindValue(':e1',   $months[1]['end'],   PDO::PARAM_STR);
    $st->bindValue(':s2',   $months[2]['start'], PDO::PARAM_STR);
    $st->bindValue(':e2',   $months[2]['end'],   PDO::PARAM_STR);
    $st->bindValue(':nama', $namaLike,           PDO::PARAM_STR);
    $st->execute();

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $result = [];
    foreach ($rows as $r) {
        $diskonPerc = is_numeric($r['diskon_off']) ? (float)$r['diskon_off'] : 0.0;

        $m0 = (float)($r['total_m0'] ?? 0);
        $m1 = (float)($r['total_m1'] ?? 0);
        $m2 = (float)($r['total_m2'] ?? 0);

        $cashback_m0 = round(($m0 * $diskonPerc) / 100, 2);
        $cashback_m1 = round(($m1 * $diskonPerc) / 100, 2);
        $cashback_m2 = round(($m2 * $diskonPerc) / 100, 2);

        $result[] = [
            'id_cb'           => $r['id_cb'],
            'id_out'          => $r['id_out'],
            'nama_out'        => $r['nama_out'],
            'tipe_outlet'     => $r['tipe_outlet'],
            'provinsi'        => $r['provinsi'],
            'kota'            => $r['kota'],
            'ofcode_out'      => $r['ofcode_out'],
            'top_odi'         => $r['top_odi'],
            'diskon_off'      => $diskonPerc,
            'pic'             => $r['pic'],
            'bank'            => $r['bank'],
            'nomor_rekening'  => $r['nomor_rekening'],
            'status'          => $r['status'],
            'created_at'      => $r['created_at'],
            'total_m2'        => number_format($m2, 2, '.', ''),
            'cashback_m2'     => number_format($cashback_m2, 2, '.', ''),
            'total_m1'        => number_format($m1, 2, '.', ''),
            'cashback_m1'     => number_format($cashback_m1, 2, '.', ''),
            'total_m0'        => number_format($m0, 2, '.', ''),
            'cashback_m0'     => number_format($cashback_m0, 2, '.', ''),
            'nama_apl'        => $nama_apl
        ];
    }

    echo json_encode([
        "result"         => $result,
        "base_url_apl"   => $baseUrl,
        "id_apl"         => $id_apl,
        "nama_apl"       => $nama_apl,
        "query_nama_out" => $nama_out_raw,
        "months"         => [
            'm2' => $months[2]['label'],
            'm1' => $months[1]['label'],
            'm0' => $months[0]['label'],
        ],
        "total_records"  => count($result)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
    error_log("getFakturCashback Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
} finally {
    if (isset($conn)) $base->close();
}
?>