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

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$gudang_filter = isset($_POST['gudang_filter']) ? trim($_POST['gudang_filter']) : '';
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

list($year, $month) = explode('-', $periode);
$year = intval($year);
$month = intval($month);

try {
    $conn = $base->open();

    // ==== FIX: jangan hard-fail kalau id_apl dari pusat tidak ada di DB cabang ====
    // Prioritas:
    // 1) kalau ada row aplikasi dengan id_apl tsb, pakai key-nya
    // 2) kalau tidak ada, pakai "self config" = row aplikasi aktif pertama (key lokal cabang)
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
        $stmtSelf = $conn->query(
            "SELECT id_apl, key_apl, base_url_apl, nama_apl
             FROM aplikasi
             WHERE active_apl = 1
             ORDER BY id_apl ASC
             LIMIT 1"
        );
        $apl = $stmtSelf ? $stmtSelf->fetch(PDO::FETCH_ASSOC) : null;
    }

    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error" => "Konfigurasi aplikasi (key) tidak ditemukan di cabang"]);
        exit;
    }

    $sourceKey = $apl['key_apl'];
    $baseUrl   = $apl['base_url_apl'] ?? '';
    $nama_apl  = $apl['nama_apl'] ?? '';
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

    $fakturSummarySourceSql = $specialFakturTables
        ? "
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk, cito
            FROM {$specialFakturTables[0]}
            UNION ALL
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk, cito
            FROM {$specialFakturTables[1]}
            UNION ALL
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk, cito
            FROM {$specialFakturTables[2]}
        "
        : "
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk, cito
            FROM transaksi_faktur
            UNION ALL
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk, cito
            FROM transaksi_faktur_c
        ";

    $fakturTimelineSourceSql = $specialFakturTables
        ? "
            SELECT id_tfk, tgl_tfk
            FROM {$specialFakturTables[0]}
            UNION ALL
            SELECT id_tfk, tgl_tfk
            FROM {$specialFakturTables[1]}
            UNION ALL
            SELECT id_tfk, tgl_tfk
            FROM {$specialFakturTables[2]}
        "
        : "
            SELECT id_tfk, tgl_tfk
            FROM transaksi_faktur
            UNION ALL
            SELECT id_tfk, tgl_tfk
            FROM transaksi_faktur_c
        ";

    if ($special_mode === 'malang_b') {
        $tukarFakturSourceSql = "
            SELECT id_tfk, kode_tfk, tgl_tfk, status_dokumen
            FROM transaksi_faktur_np_malang
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, status_dokumen
            FROM transaksi_faktur_np_malang_b
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, status_dokumen
            FROM transaksi_faktur_np_malang_c
        ";
    } elseif ($special_mode === 'medan_b') {
        $tukarFakturSourceSql = "
            SELECT id_tfk, kode_tfk, tgl_tfk, status_dokumen
            FROM transaksi_faktur_np_medan
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, status_dokumen
            FROM transaksi_faktur_b_medan
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, status_dokumen
            FROM transaksi_faktur_c_medan
        ";
    } else {
        $tukarFakturSourceSql = "
            SELECT id_tfk, kode_tfk, tgl_tfk, status_balik AS status_dokumen
            FROM transaksi_faktur
            UNION ALL
            SELECT id_tfk, kode_tfk, tgl_tfk, status_balik AS status_dokumen
            FROM transaksi_faktur_pim
        ";
    }

    if (md5($periode . "#" . $sourceKey) !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }

    $summary = [
        "periode" => $periode,
        "total_faktur_terbentuk" => 0,
        "total_cito" => 0,
        "total_outlet_cito" => 0,
        "sum_total_faktur_nominal" => 0,
        "avg_outlet_per_hari" => 0,
        "total_faktur_revisi" => 0,
        "manual_count" => 0,
        "total_pengiriman" => 0,
        "pengiriman_selesai" => 0,
        "pengiriman_pending" => 0,
        "pengiriman_h_plus_1" => 0,
        "pengiriman_h_greater_1" => 0,
        "barang_tidak_terkirim" => 0,
        "faktur_teririm" => 0,
        "faktur_kembali" => 0,
        "faktur_tidak_teririm" => 0,
        "faktur_in_process" => 0,
        "kode_faktur_terlama_belum_tertukar" => '',
        "tgl_faktur_terlama_belum_tertukar" => null,
        "umur_faktur_terlama_belum_tertukar" => 0,
        "total_po" => 0,
        "total_po_before_9" => 0,
        "total_po_from_9" => 0,
        "avg_sla_po_to_packing_minutes" => 0,
        "po_donasi_baksos" => 0
    ];

    // === transaksi_faktur + transaksi_faktur_c ===
    $q1 = $conn->prepare("
        SELECT 
            COUNT(DISTINCT id_tfk) as cnt, 
            COALESCE(SUM(total_tfk),0) as sum_nominal
        FROM (
                {$fakturSummarySourceSql}
        ) tf
        WHERE MONTH(tgl_tfk) = :m AND YEAR(tgl_tfk) = :y
    ");
    $q1->execute([':m'=>$month, ':y'=>$year]);
    $r1 = $q1->fetch(PDO::FETCH_ASSOC);
    $summary['total_faktur_terbentuk'] = intval($r1['cnt'] ?? 0);
    $summary['sum_total_faktur_nominal'] = floatval($r1['sum_nominal'] ?? 0);

    $q2 = $conn->prepare("
        SELECT ROUND(COALESCE(AVG(outlet_count),0),2) as avg_out
        FROM (
            SELECT DATE(tgl_tfk) as dt, COUNT(DISTINCT id_out) as outlet_count
            FROM (
                    {$fakturSummarySourceSql}
            ) tf
            WHERE MONTH(tgl_tfk) = :m AND YEAR(tgl_tfk) = :y
            GROUP BY DATE(tgl_tfk)
        ) as t
    ");
    $q2->execute([':m'=>$month, ':y'=>$year]);
    $summary['avg_outlet_per_hari'] = floatval(($q2->fetchColumn() ?? 0));

    $q3 = $conn->prepare("
        SELECT COUNT(DISTINCT id_tfk) as cnt
        FROM (
              {$fakturSummarySourceSql}
        ) tf
        WHERE status_tfk = 'Revisi'
          AND MONTH(tgl_tfk)=:m AND YEAR(tgl_tfk)=:y
    ");
    $q3->execute([':m'=>$month, ':y'=>$year]);
    $summary['total_faktur_revisi'] = intval($q3->fetchColumn() ?? 0);

    $qManual = $conn->prepare("
        SELECT COUNT(DISTINCT id_tfk) as cnt
        FROM (
              {$fakturSummarySourceSql}
        ) tf
        WHERE status_tfk = 'Revisi Faktur Manual'
          AND MONTH(tgl_tfk)=:m AND YEAR(tgl_tfk)=:y
    ");
    $qManual->execute([':m'=>$month, ':y'=>$year]);
    $summary['manual_count'] = intval($qManual->fetchColumn() ?? 0);

    // Hitung CITO (kolom cito = 'cito' atau '1') pada periode
    $qc = $conn->prepare("
        SELECT COUNT(DISTINCT id_tfk) as cnt
        FROM (
              {$fakturSummarySourceSql}
        ) tf
        WHERE (tf.cito = 'cito' OR tf.cito = '1' OR tf.cito = 1)
          AND MONTH(tf.tgl_tfk)=:m AND YEAR(tf.tgl_tfk)=:y
    ");
    $qc->execute([':m'=>$month, ':y'=>$year]);
    $summary['total_cito'] = intval($qc->fetchColumn() ?? 0);

    $qcOutlet = $conn->prepare("\n        SELECT COUNT(DISTINCT id_out) as cnt\n        FROM (\n              {$fakturSummarySourceSql}\n        ) tf\n        WHERE (tf.cito = 'cito' OR tf.cito = '1' OR tf.cito = 1)\n          AND MONTH(tf.tgl_tfk)=:m AND YEAR(tf.tgl_tfk)=:y\n    ");
    $qcOutlet->execute([':m'=>$month, ':y'=>$year]);
    $summary['total_outlet_cito'] = intval($qcOutlet->fetchColumn() ?? 0);

    $q4 = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE MONTH(created_at)=:m AND YEAR(created_at)=:y");
    $q4->execute([':m'=>$month, ':y'=>$year]);
    $summary['total_pengiriman'] = intval($q4->fetchColumn() ?? 0);

    $q5 = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE status_tfkkb = 'Sudah Dikirim' AND MONTH(created_at)=:m AND YEAR(created_at)=:y");
    $q5->execute([':m'=>$month, ':y'=>$year]);
    $summary['pengiriman_selesai'] = intval($q5->fetchColumn() ?? 0);

    $q6 = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE status_tfkkb = 'Belum Dikirim' AND MONTH(created_at)=:m AND YEAR(created_at)=:y");
    $q6->execute([':m'=>$month, ':y'=>$year]);
    $summary['pengiriman_pending'] = intval($q6->fetchColumn() ?? 0);

    $q7 = $conn->prepare("
        SELECT COUNT(*)
        FROM transaksi_faktur_kirim_b tfkkb
        JOIN (
              {$fakturTimelineSourceSql}
        ) tf ON tfkkb.id_tfk = tf.id_tfk
        WHERE DATE(tfkkb.tgl_tfkkb) = DATE_ADD(DATE(tf.tgl_tfk), INTERVAL 1 DAY)
          AND tfkkb.status_tfkkb = 'Sudah Dikirim'
          AND MONTH(tfkkb.created_at)=:m AND YEAR(tfkkb.created_at)=:y
    ");
    $q7->execute([':m'=>$month, ':y'=>$year]);
    $summary['pengiriman_h_plus_1'] = intval($q7->fetchColumn() ?? 0);

    $q8 = $conn->prepare("
        SELECT COUNT(*)
        FROM transaksi_faktur_kirim_b tfkkb
        JOIN (
              {$fakturTimelineSourceSql}
        ) tf ON tfkkb.id_tfk = tf.id_tfk
        WHERE DATE(tfkkb.tgl_tfkkb) > DATE_ADD(DATE(tf.tgl_tfk), INTERVAL 1 DAY)
          AND tfkkb.status_tfkkb = 'Sudah Dikirim'
          AND MONTH(tfkkb.created_at)=:m AND YEAR(tfkkb.created_at)=:y
    ");
    $q8->execute([':m'=>$month, ':y'=>$year]);
    $summary['pengiriman_h_greater_1'] = intval($q8->fetchColumn() ?? 0);

    $summary['barang_tidak_terkirim'] = $summary['pengiriman_pending'];

    // === po_outlet (terpusat di Puri B) ===
    // Jika ada gudang_filter → query hanya data gudang cabang tersebut.
    // Jika tidak ada filter → query semua (untuk total semua cabang).
    try {
        $gfWhere  = ($gudang_filter !== '') ? ' AND gudang = :gf' : '';
        $gfParams = ($gudang_filter !== '')
            ? [':m' => $month, ':y' => $year, ':gf' => $gudang_filter]
            : [':m' => $month, ':y' => $year];

        $qpo = $conn->prepare("SELECT COUNT(*) FROM po_outlet WHERE MONTH(created_at)=:m AND YEAR(created_at)=:y" . $gfWhere);
        $qpo->execute($gfParams);
        $summary['total_po'] = intval($qpo->fetchColumn() ?? 0);

        $qpo2 = $conn->prepare("SELECT COUNT(*) FROM po_outlet WHERE HOUR(created_at) < 9 AND MONTH(created_at)=:m AND YEAR(created_at)=:y" . $gfWhere);
        $qpo2->execute($gfParams);
        $summary['total_po_before_9'] = intval($qpo2->fetchColumn() ?? 0);

        $qpo3 = $conn->prepare("SELECT COUNT(*) FROM po_outlet WHERE HOUR(created_at) >= 9 AND MONTH(created_at)=:m AND YEAR(created_at)=:y" . $gfWhere);
        $qpo3->execute($gfParams);
        $summary['total_po_from_9'] = intval($qpo3->fetchColumn() ?? 0);

        $qSla = $conn->prepare("
            SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)), 1)
            FROM po_outlet
            WHERE updated_at IS NOT NULL
              AND updated_at <> created_at
              AND MONTH(created_at)=:m AND YEAR(created_at)=:y" . $gfWhere . "
        ");
        $qSla->execute($gfParams);
        $summary['avg_sla_po_to_packing_minutes'] = floatval($qSla->fetchColumn() ?? 0);
    } catch (Exception $e) {
        // po_outlet tidak ada di cabang ini — default 0 tetap
        error_log("po_outlet query error (" . ($nama_apl ?? '') . "): " . $e->getMessage());
    }

    // === transaksi_mr untuk po_donasi_baksos ===
    try {
        $qmr = $conn->prepare("
            SELECT COUNT(*) FROM transaksi_mr
            WHERE MONTH(tgl_permintaan) = :m AND YEAR(tgl_permintaan) = :y
        ");
        $qmr->execute([':m' => $month, ':y' => $year]);
        $summary['po_donasi_baksos'] = intval($qmr->fetchColumn() ?? 0);
    } catch (Exception $e) {
        // transaksi_mr tidak ada di cabang ini — default 0 tetap
        error_log("transaksi_mr query error (" . ($nama_apl ?? '') . "): " . $e->getMessage());
    }

    $q10 = $conn->prepare("SELECT COUNT(DISTINCT id_tfk) FROM transaksi_faktur_kirim_f WHERE status_tfkkf = 'Sudah Dikirim' AND MONTH(created_at)=:m AND YEAR(created_at)=:y");
    $q10->execute([':m'=>$month, ':y'=>$year]);
    $summary['faktur_teririm'] = intval($q10->fetchColumn() ?? 0);

    $q11 = $conn->prepare("SELECT COUNT(DISTINCT id_tfk) FROM transaksi_faktur_kirim_f WHERE status_tfkkf = 'Belum Dikirim' AND MONTH(created_at)=:m AND YEAR(created_at)=:y");
    $q11->execute([':m'=>$month, ':y'=>$year]);
    $summary['faktur_tidak_teririm'] = intval($q11->fetchColumn() ?? 0);

    $q12 = $conn->prepare("SELECT COUNT(DISTINCT id_tfk) FROM transaksi_faktur_kirim_f WHERE MONTH(created_at)=:m AND YEAR(created_at)=:y");
    $q12->execute([':m'=>$month, ':y'=>$year]);
    $total_faktur_f = intval($q12->fetchColumn() ?? 0);
    $summary['faktur_in_process'] = max(0, $total_faktur_f - $summary['faktur_teririm'] - $summary['faktur_tidak_teririm']);

    $q13 = $conn->prepare("\n        SELECT COUNT(DISTINCT tf.id_tfk)\n        FROM (\n            SELECT DISTINCT id_tfk\n            FROM transaksi_faktur_kirim_f\n            WHERE status_tfkkf = 'Sudah Dikirim'\n              AND MONTH(created_at)=:m\n              AND YEAR(created_at)=:y\n        ) kirim\n        INNER JOIN (\n            {$tukarFakturSourceSql}\n        ) tf ON tf.id_tfk = kirim.id_tfk\n        WHERE TRIM(LOWER(COALESCE(tf.status_dokumen, ''))) = 'sudah balik'\n    ");
    $q13->execute([':m'=>$month, ':y'=>$year]);
    $summary['faktur_kembali'] = intval($q13->fetchColumn() ?? 0);

    $q14 = $conn->prepare("\n        SELECT tf.kode_tfk, tf.tgl_tfk, DATEDIFF(CURDATE(), DATE(tf.tgl_tfk)) AS umur_hari\n        FROM (\n            SELECT DISTINCT id_tfk\n            FROM transaksi_faktur_kirim_f\n            WHERE status_tfkkf = 'Sudah Dikirim'\n              AND YEAR(created_at)=:y\n        ) kirim\n        INNER JOIN (\n            {$tukarFakturSourceSql}\n        ) tf ON tf.id_tfk = kirim.id_tfk\n        WHERE TRIM(LOWER(COALESCE(tf.status_dokumen, ''))) = 'belum balik'\n        ORDER BY tf.tgl_tfk ASC\n        LIMIT 1\n    ");
    $q14->execute([':y'=>$year]);
    $oldestBelumTertukar = $q14->fetch(PDO::FETCH_ASSOC);
    if ($oldestBelumTertukar) {
        $summary['kode_faktur_terlama_belum_tertukar'] = $oldestBelumTertukar['kode_tfk'] ?? '';
        $summary['tgl_faktur_terlama_belum_tertukar'] = $oldestBelumTertukar['tgl_tfk'] ?? null;
        $summary['umur_faktur_terlama_belum_tertukar'] = intval($oldestBelumTertukar['umur_hari'] ?? 0);
    }

    echo json_encode([
        "result" => $summary,
        "base_url_apl" => $baseUrl,
        "id_apl_requested" => $id_apl,
        "id_apl_local" => $apl['id_apl'] ?? null,
        "nama_apl" => $nama_apl,
        "special_mode" => $special_mode,
        "periode_requested" => $periode,
        "timestamp" => date('c')
    ]);

} catch (Exception $e) {
    error_log("API Sum Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
} finally {
    if (isset($base) && method_exists($base,'close')) {
        $base->close();
    }
}
?>