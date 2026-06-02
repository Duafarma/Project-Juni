<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$periode = isset($_POST['periode']) ? trim($_POST['periode']) : '';
$from    = isset($_POST['from']) ? trim($_POST['from']) : '';
$to      = isset($_POST['to']) ? trim($_POST['to']) : '';

$encrypt = isset($_POST['encrypt']) ? $secu->injection($_POST['encrypt']) : '';
$id_apl  = isset($_POST['id_apl']) ? $secu->injection($_POST['id_apl']) : '';
$special_mode = isset($_POST['special_mode']) ? trim($_POST['special_mode']) : '';

// OPTIONAL: principle (id_mp) - relasi ke produk.nama_p
$principle = isset($_POST['principle']) ? $secu->injection(trim($_POST['principle'])) : '';
if ($principle === 'all') $principle = '';

$mode = 'month';
if($from !== '' || $to !== '') $mode = 'range';

if (empty($id_apl)) {
    http_response_code(400);
    echo json_encode(["error" => "Parameter 'id_apl' wajib diisi"]);
    exit;
}

if ($mode === 'month') {
    if (empty($periode) || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter 'periode' wajib dalam format YYYY-MM"]);
        exit;
    }
    if (empty($encrypt)) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter 'encrypt' wajib diisi"]);
        exit;
    }
    list($year, $month) = explode('-', $periode);
    $year = intval($year);
    $month = intval($month);
} else {
    if (empty($from) || empty($to) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter 'from' dan 'to' wajib dalam format YYYY-MM-DD"]);
        exit;
    }
    if (empty($encrypt)) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter 'encrypt' wajib diisi"]);
        exit;
    }
    if (strtotime($from) > strtotime($to)) { $tmp = $from; $from = $to; $to = $tmp; }
}

try {
    $conn = $base->open();

    $stmtApl = $conn->prepare("SELECT key_apl, base_url_apl, nama_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1");
    $stmtApl->bindParam(':id_apl', $id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $apl = $stmtApl->fetch(PDO::FETCH_ASSOC);
    if (!$apl) {
        http_response_code(404);
        echo json_encode(["error" => "Aplikasi tidak ditemukan atau tidak aktif"]);
        exit;
    }

    $sourceKey = $apl['key_apl'];

    $specialFakturTables = null;
    $specialDetailTables  = null;
    if ($special_mode === 'malang_b') {
        $specialFakturTables = [
            'transaksi_faktur_np_malang',
            'transaksi_faktur_np_malang_b',
            'transaksi_faktur_np_malang_c',
        ];
        $specialDetailTables = [
            'transaksi_fakturdetail_np_malang',
            'transaksi_fakturdetail_np_malang_b',
            'transaksi_fakturdetail_np_malang_c',
        ];
    } elseif ($special_mode === 'medan_b') {
        $specialFakturTables = [
            'transaksi_faktur_np_medan',
            'transaksi_faktur_b_medan',
            'transaksi_faktur_c_medan',
        ];
        $specialDetailTables = [
            'transaksi_fakturdetail_np_medan',
            'transaksi_fakturdetail_medan_b',
            'transaksi_fakturdetail_c_medan',
        ];
    }

    $expected = ($mode === 'month')
        ? md5($periode . "#" . $sourceKey)
        : md5($from . "|" . $to . "#" . $sourceKey);

    // FIX: Jika principle aktif, gunakan format encrypt baru
    if ($principle !== '') {
        $expected = ($mode === 'month')
            ? md5($periode . "|" . $principle . "#" . $sourceKey)
            : md5($from . "|" . $to . "|" . $principle . "#" . $sourceKey);
    }

    if ($expected !== $encrypt) {
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized - Invalid encryption"]);
        exit;
    }

    $summary = [
        "periode" => ($mode === 'month') ? $periode : ($from . " s/d " . $to),
        "total_faktur_terbentuk" => 0,
        "sum_total_faktur_nominal" => 0,
        "avg_outlet_per_hari" => 0,
        "total_faktur_revisi" => 0,
        "total_pengiriman" => 0,
        "pengiriman_selesai" => 0,
        "pengiriman_pending" => 0,
        "pengiriman_h_plus_1" => 0,
        "pengiriman_h_greater_1" => 0,
        "barang_tidak_terkirim" => 0,
        "faktur_teririm" => 0,
        "faktur_tidak_teririm" => 0,
        "faktur_in_process" => 0
    ];

    // Filter clause tanggal
    if($mode === 'month') {
        $whereFaktur = "MONTH(tf.tgl_eff) = :m AND YEAR(tf.tgl_eff) = :y";
        $bindFaktur  = [':m'=>$month, ':y'=>$year];

        $whereCreated = "MONTH(created_at) = :m AND YEAR(created_at) = :y";
        $bindCreated  = [':m'=>$month, ':y'=>$year];
    } else {
        $whereFaktur = "DATE(tf.tgl_eff) BETWEEN :from AND :to";
        $bindFaktur  = [':from'=>$from, ':to'=>$to];

        $whereCreated = "DATE(created_at) BETWEEN :from AND :to";
        $bindCreated  = [':from'=>$from, ':to'=>$to];
    }

    // Check tabel _c existence
    $hasFakturC = false;
    try {
        $checkFakturC = $conn->query("SHOW TABLES LIKE 'transaksi_faktur_c'");
        $hasFakturC = $checkFakturC && $checkFakturC->fetchColumn();
    } catch (Exception $e) {
        $hasFakturC = false;
    }

    $hasDetailC = false;
    try {
        $checkDetailC = $conn->query("SHOW TABLES LIKE 'transaksi_fakturdetail_c'");
        $hasDetailC = $checkDetailC && $checkDetailC->fetchColumn();
    } catch (Exception $e) {
        $hasDetailC = false;
    }

    // Check tabel _pim existence
    $hasFakturPim = false;
    try {
        $checkFakturPim = $conn->query("SHOW TABLES LIKE 'transaksi_faktur_pim'");
        $hasFakturPim = $checkFakturPim && $checkFakturPim->fetchColumn();
    } catch (Exception $e) {
        $hasFakturPim = false;
    }

    $hasDetailPim = false;
    try {
        $checkDetailPim = $conn->query("SHOW TABLES LIKE 'transaksi_fakturdetail_pim'");
        $hasDetailPim = $checkDetailPim && $checkDetailPim->fetchColumn();
    } catch (Exception $e) {
        $hasDetailPim = false;
    }

    // ========================================
    // FIX CRITICAL: Query penjualan dengan principle
    // ========================================

    if ($specialFakturTables !== null) {
        // === Special mode: Malang B / Medan B — baca dari tabel non-primary ===
        $specialSourceSql = "
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk
            FROM {$specialFakturTables[0]}
            UNION ALL
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk
            FROM {$specialFakturTables[1]}
            UNION ALL
            SELECT id_tfk, total_tfk, tgl_tfk, id_out, status_tfk
            FROM {$specialFakturTables[2]}
        ";

        // Detail UNION — selalu dibutuhkan untuk nominal & principle filter
        $specialDetailSql = "
            SELECT id_tfk, SUM(total_tfd) AS total_tfd
            FROM {$specialDetailTables[0]}
            GROUP BY id_tfk
            UNION ALL
            SELECT id_tfk, SUM(total_tfd) AS total_tfd
            FROM {$specialDetailTables[1]}
            GROUP BY id_tfk
            UNION ALL
            SELECT id_tfk, SUM(total_tfd) AS total_tfd
            FROM {$specialDetailTables[2]}
            GROUP BY id_tfk
        ";

        // Detail UNION hanya id_tfk (untuk avg outlet & revisi saat principle aktif)
        $specialDetailDistinctSql = "
            SELECT DISTINCT tfd.id_tfk
            FROM {$specialDetailTables[0]} tfd
            INNER JOIN produk p ON p.id_pro = tfd.id_pro
            WHERE TRIM(p.nama_p) = :principle_sp
            UNION
            SELECT DISTINCT tfd.id_tfk
            FROM {$specialDetailTables[1]} tfd
            INNER JOIN produk p ON p.id_pro = tfd.id_pro
            WHERE TRIM(p.nama_p) = :principle_sp2
            UNION
            SELECT DISTINCT tfd.id_tfk
            FROM {$specialDetailTables[2]} tfd
            INNER JOIN produk p ON p.id_pro = tfd.id_pro
            WHERE TRIM(p.nama_p) = :principle_sp3
        ";

        $whereSpec = ($mode === 'month')
            ? "MONTH(tf.tgl_tfk) = :m AND YEAR(tf.tgl_tfk) = :y"
            : "DATE(tf.tgl_tfk) BETWEEN :from AND :to";
        $bindSpec = ($mode === 'month')
            ? [':m' => $month, ':y' => $year]
            : [':from' => $from, ':to' => $to];

        // (A) Total faktur & nominal — selalu JOIN ke detail untuk nominal akurat
        if ($principle === '') {
            $specialDetailNominalSql = "
                SELECT id_tfk, SUM(total_tfd) AS total_tfd
                FROM {$specialDetailTables[0]}
                GROUP BY id_tfk
                UNION ALL
                SELECT id_tfk, SUM(total_tfd) AS total_tfd
                FROM {$specialDetailTables[1]}
                GROUP BY id_tfk
                UNION ALL
                SELECT id_tfk, SUM(total_tfd) AS total_tfd
                FROM {$specialDetailTables[2]}
                GROUP BY id_tfk
            ";
            $qA = $conn->prepare("
                SELECT COUNT(DISTINCT tf.id_tfk) AS cnt, COALESCE(SUM(tfd.total_tfd), 0) AS sum_nominal
                FROM ({$specialSourceSql}) tf
                INNER JOIN ({$specialDetailNominalSql}) tfd ON tfd.id_tfk = tf.id_tfk
                WHERE {$whereSpec}
            ");
            $qA->execute($bindSpec);
        } else {
            $specialDetailFilteredSql = "
                SELECT tfd.id_tfk, SUM(tfd.total_tfd) AS total_tfd
                FROM {$specialDetailTables[0]} tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_a0
                GROUP BY tfd.id_tfk
                UNION ALL
                SELECT tfd.id_tfk, SUM(tfd.total_tfd) AS total_tfd
                FROM {$specialDetailTables[1]} tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_a1
                GROUP BY tfd.id_tfk
                UNION ALL
                SELECT tfd.id_tfk, SUM(tfd.total_tfd) AS total_tfd
                FROM {$specialDetailTables[2]} tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_a2
                GROUP BY tfd.id_tfk
            ";
            $bindA = $bindSpec;
            $bindA[':principle_a0'] = $principle;
            $bindA[':principle_a1'] = $principle;
            $bindA[':principle_a2'] = $principle;
            $qA = $conn->prepare("
                SELECT COUNT(DISTINCT tf.id_tfk) AS cnt, COALESCE(SUM(tfd.total_tfd), 0) AS sum_nominal
                FROM ({$specialSourceSql}) tf
                INNER JOIN ({$specialDetailFilteredSql}) tfd ON tfd.id_tfk = tf.id_tfk
                WHERE {$whereSpec}
            ");
            $qA->execute($bindA);
        }
        $rA = $qA->fetch(PDO::FETCH_ASSOC);
        $summary['total_faktur_terbentuk']   = intval($rA['cnt'] ?? 0);
        $summary['sum_total_faktur_nominal'] = floatval($rA['sum_nominal'] ?? 0);

        // (B) Avg outlet per hari
        if ($principle === '') {
            $qB = $conn->prepare("
                SELECT ROUND(COALESCE(AVG(outlet_count), 0), 2) AS avg_out
                FROM (
                    SELECT DATE(tf.tgl_tfk) AS dt, COUNT(DISTINCT tf.id_out) AS outlet_count
                    FROM ({$specialSourceSql}) tf
                    WHERE {$whereSpec}
                    GROUP BY DATE(tf.tgl_tfk)
                ) t
            ");
            $qB->execute($bindSpec);
        } else {
            $bindB = $bindSpec;
            $bindB[':principle_sp']  = $principle;
            $bindB[':principle_sp2'] = $principle;
            $bindB[':principle_sp3'] = $principle;
            $qB = $conn->prepare("
                SELECT ROUND(COALESCE(AVG(outlet_count), 0), 2) AS avg_out
                FROM (
                    SELECT DATE(tf.tgl_tfk) AS dt, COUNT(DISTINCT tf.id_out) AS outlet_count
                    FROM ({$specialSourceSql}) tf
                    INNER JOIN ({$specialDetailDistinctSql}) dp ON dp.id_tfk = tf.id_tfk
                    WHERE {$whereSpec}
                    GROUP BY DATE(tf.tgl_tfk)
                ) t
            ");
            $qB->execute($bindB);
        }
        $summary['avg_outlet_per_hari'] = floatval($qB->fetchColumn() ?? 0);

        // (C) Faktur revisi
        if ($principle === '') {
            $qC = $conn->prepare("
                SELECT COUNT(DISTINCT tf.id_tfk) AS cnt
                FROM ({$specialSourceSql}) tf
                WHERE tf.status_tfk = 'Revisi' AND {$whereSpec}
            ");
            $qC->execute($bindSpec);
        } else {
            $bindC = $bindSpec;
            $bindC[':principle_sp']  = $principle;
            $bindC[':principle_sp2'] = $principle;
            $bindC[':principle_sp3'] = $principle;
            $qC = $conn->prepare("
                SELECT COUNT(DISTINCT tf.id_tfk) AS cnt
                FROM ({$specialSourceSql}) tf
                INNER JOIN ({$specialDetailDistinctSql}) dp ON dp.id_tfk = tf.id_tfk
                WHERE tf.status_tfk = 'Revisi' AND {$whereSpec}
            ");
            $qC->execute($bindC);
        }
        $summary['total_faktur_revisi'] = intval($qC->fetchColumn() ?? 0);

    } else {
    // (A) Total Faktur & Nominal Penjualan
    if ($principle === '') {
        // TANPA PRINCIPLE - ambil semua
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

        $q1 = $conn->prepare("
            SELECT 
                COUNT(DISTINCT tf.id_tfk) AS cnt,
                COALESCE(SUM(tfd.total_tfd), 0) AS sum_nominal
            FROM (
                SELECT 
                    id_tfk,
                    id_out,
                    status_tfk,
                    CASE 
                        WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                        ELSE tgl_tfk 
                    END AS tgl_eff
                FROM transaksi_faktur
                " . ($hasFakturC ? "
                UNION ALL
                SELECT 
                    id_tfk,
                    id_out,
                    status_tfk,
                    CASE 
                        WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                        ELSE tgl_tfk 
                    END AS tgl_eff
                FROM transaksi_faktur_c
                " : "") . "
                " . ($hasFakturPim ? "
                UNION ALL
                SELECT 
                    id_tfk,
                    id_out,
                    status_tfk,
                    CASE 
                        WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                        ELSE tgl_tfk 
                    END AS tgl_eff
                FROM transaksi_faktur_pim
                " : "") . "
            ) tf
            INNER JOIN ({$sqlDetail}) tfd ON tfd.id_tfk = tf.id_tfk
            WHERE {$whereFaktur}
        ");
        $q1->execute($bindFaktur);
        
    } else {
        // DENGAN PRINCIPLE - filter berdasarkan produk.nama_p = principle ID
        $bind = $bindFaktur;
        $bind[':principle_main'] = $principle;
        if ($hasDetailC) {
            $bind[':principle_c'] = $principle;
        }
        if ($hasDetailPim) {
            $bind[':principle_pim'] = $principle;
        }

        $sqlDetailFiltered = "
            SELECT tfd.id_tfk, SUM(tfd.total_tfd) as total_tfd
            FROM transaksi_fakturdetail tfd
            INNER JOIN produk p ON p.id_pro = tfd.id_pro
            WHERE TRIM(p.nama_p) = :principle_main
            GROUP BY tfd.id_tfk
        ";
        if ($hasDetailC) {
            $sqlDetailFiltered .= "
                UNION ALL
                SELECT tfd.id_tfk, SUM(tfd.total_tfd) as total_tfd
                FROM transaksi_fakturdetail_c tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_c
                GROUP BY tfd.id_tfk
            ";
        }
        if ($hasDetailPim) {
            $sqlDetailFiltered .= "
                UNION ALL
                SELECT tfd.id_tfk, SUM(tfd.total_tfd) as total_tfd
                FROM transaksi_fakturdetail_pim tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_pim
                GROUP BY tfd.id_tfk
            ";
        }

        $q1 = $conn->prepare("
            SELECT 
                COUNT(DISTINCT tf.id_tfk) AS cnt,
                COALESCE(SUM(tfd_filtered.total_tfd), 0) AS sum_nominal
            FROM (
                SELECT 
                    id_tfk,
                    id_out,
                    status_tfk,
                    CASE 
                        WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                        ELSE tgl_tfk 
                    END AS tgl_eff
                FROM transaksi_faktur
                " . ($hasFakturC ? "
                UNION ALL
                SELECT 
                    id_tfk,
                    id_out,
                    status_tfk,
                    CASE 
                        WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                        ELSE tgl_tfk 
                    END AS tgl_eff
                FROM transaksi_faktur_c
                " : "") . "
                " . ($hasFakturPim ? "
                UNION ALL
                SELECT 
                    id_tfk,
                    id_out,
                    status_tfk,
                    CASE 
                        WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at 
                        ELSE tgl_tfk 
                    END AS tgl_eff
                FROM transaksi_faktur_pim
                " : "") . "
            ) tf
            INNER JOIN ({$sqlDetailFiltered}) tfd_filtered ON tfd_filtered.id_tfk = tf.id_tfk
            WHERE {$whereFaktur}
        ");
        $q1->execute($bind);
    }
    
    $r1 = $q1->fetch(PDO::FETCH_ASSOC);
    $summary['total_faktur_terbentuk'] = intval($r1['cnt'] ?? 0);
    $summary['sum_total_faktur_nominal'] = floatval($r1['sum_nominal'] ?? 0);

    // (B) Avg Outlet Per Hari
    if ($principle === '') {
        $q2 = $conn->prepare("
            SELECT ROUND(COALESCE(AVG(outlet_count),0),2) as avg_out
            FROM (
                SELECT DATE(tf.tgl_eff) as dt, COUNT(DISTINCT tf.id_out) as outlet_count
                FROM (
                    SELECT id_out,
                        CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                    FROM transaksi_faktur
                    " . ($hasFakturC ? "
                    UNION ALL
                    SELECT id_out,
                        CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                    FROM transaksi_faktur_c
                    " : "") . "
                    " . ($hasFakturPim ? "
                    UNION ALL
                    SELECT id_out,
                        CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                    FROM transaksi_faktur_pim
                    " : "") . "
                ) tf
                WHERE {$whereFaktur}
                GROUP BY DATE(tf.tgl_eff)
            ) as t
        ");
        $q2->execute($bindFaktur);
    } else {
        $bind = $bindFaktur;
        $bind[':principle_main'] = $principle;
        if ($hasDetailC) {
            $bind[':principle_c'] = $principle;
        }
        if ($hasDetailPim) {
            $bind[':principle_pim'] = $principle;
        }

        $q2 = $conn->prepare("
            SELECT ROUND(COALESCE(AVG(outlet_count),0),2) as avg_out
            FROM (
                SELECT DATE(tf.tgl_eff) as dt, COUNT(DISTINCT tf.id_out) as outlet_count
                FROM (
                    SELECT id_tfk, id_out,
                        CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                    FROM transaksi_faktur
                    " . ($hasFakturC ? "
                    UNION ALL
                    SELECT id_tfk, id_out,
                        CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                    FROM transaksi_faktur_c
                    " : "") . "
                    " . ($hasFakturPim ? "
                    UNION ALL
                    SELECT id_tfk, id_out,
                        CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                    FROM transaksi_faktur_pim
                    " : "") . "
                ) tf
                INNER JOIN (
                    SELECT DISTINCT tfd.id_tfk
                    FROM transaksi_fakturdetail tfd
                    INNER JOIN produk p ON p.id_pro = tfd.id_pro
                    WHERE TRIM(p.nama_p) = :principle_main
                    " . ($hasDetailC ? "
                    UNION
                    SELECT DISTINCT tfd.id_tfk
                    FROM transaksi_fakturdetail_c tfd
                    INNER JOIN produk p ON p.id_pro = tfd.id_pro
                    WHERE TRIM(p.nama_p) = :principle_c
                    " : "") . "
                    " . ($hasDetailPim ? "
                    UNION
                    SELECT DISTINCT tfd.id_tfk
                    FROM transaksi_fakturdetail_pim tfd
                    INNER JOIN produk p ON p.id_pro = tfd.id_pro
                    WHERE TRIM(p.nama_p) = :principle_pim
                    " : "") . "
                ) dp ON dp.id_tfk = tf.id_tfk
                WHERE {$whereFaktur}
                GROUP BY DATE(tf.tgl_eff)
            ) as t
        ");
        $q2->execute($bind);
    }
    $summary['avg_outlet_per_hari'] = floatval(($q2->fetchColumn() ?? 0));

    // (C) Faktur Revisi
    if ($principle === '') {
        $q3 = $conn->prepare("
            SELECT COUNT(DISTINCT tf.id_tfk) as cnt
            FROM (
                SELECT id_tfk, status_tfk,
                    CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                FROM transaksi_faktur
                " . ($hasFakturC ? "
                UNION ALL
                SELECT id_tfk, status_tfk,
                    CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                FROM transaksi_faktur_c
                " : "") . "
                " . ($hasFakturPim ? "
                UNION ALL
                SELECT id_tfk, status_tfk,
                    CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                FROM transaksi_faktur_pim
                " : "") . "
            ) tf
            WHERE tf.status_tfk = 'Revisi' AND {$whereFaktur}
        ");
        $q3->execute($bindFaktur);
    } else {
        $bind = $bindFaktur;
        $bind[':principle_main'] = $principle;
        if ($hasDetailC) {
            $bind[':principle_c'] = $principle;
        }
        if ($hasDetailPim) {
            $bind[':principle_pim'] = $principle;
        }

        $q3 = $conn->prepare("
            SELECT COUNT(DISTINCT tf.id_tfk) as cnt
            FROM (
                SELECT id_tfk, status_tfk,
                    CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                FROM transaksi_faktur
                " . ($hasFakturC ? "
                UNION ALL
                SELECT id_tfk, status_tfk,
                    CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                FROM transaksi_faktur_c
                " : "") . "
                " . ($hasFakturPim ? "
                UNION ALL
                SELECT id_tfk, status_tfk,
                    CASE WHEN tgl_tfk IS NULL OR tgl_tfk = '0000-00-00' THEN created_at ELSE tgl_tfk END AS tgl_eff
                FROM transaksi_faktur_pim
                " : "") . "
            ) tf
            INNER JOIN (
                SELECT DISTINCT tfd.id_tfk
                FROM transaksi_fakturdetail tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_main
                " . ($hasDetailC ? "
                UNION
                SELECT DISTINCT tfd.id_tfk
                FROM transaksi_fakturdetail_c tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_c
                " : "") . "
                " . ($hasDetailPim ? "
                UNION
                SELECT DISTINCT tfd.id_tfk
                FROM transaksi_fakturdetail_pim tfd
                INNER JOIN produk p ON p.id_pro = tfd.id_pro
                WHERE TRIM(p.nama_p) = :principle_pim
                " : "") . "
            ) dp ON dp.id_tfk = tf.id_tfk
            WHERE tf.status_tfk = 'Revisi' AND {$whereFaktur}
        ");
        $q3->execute($bind);
    }
    $summary['total_faktur_revisi'] = intval($q3->fetchColumn() ?? 0);

    } // end else (normal mode - transaksi_faktur tables)

    // Pengiriman/faktur kirim: tetap pakai created_at sebagai filter (tidak dipengaruhi principle)
    $q4 = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE {$whereCreated}");
    $q4->execute($bindCreated);
    $summary['total_pengiriman'] = intval($q4->fetchColumn() ?? 0);

    $q5 = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE status_tfkkb='Sudah Dikirim' AND {$whereCreated}");
    $q5->execute($bindCreated);
    $summary['pengiriman_selesai'] = intval($q5->fetchColumn() ?? 0);

    $q6 = $conn->prepare("SELECT COUNT(*) FROM transaksi_faktur_kirim_b WHERE status_tfkkb='Belum Dikirim' AND {$whereCreated}");
    $q6->execute($bindCreated);
    $summary['pengiriman_pending'] = intval($q6->fetchColumn() ?? 0);

    $summary['barang_tidak_terkirim'] = $summary['pengiriman_pending'];

    $q10 = $conn->prepare("SELECT COUNT(DISTINCT id_tfk) FROM transaksi_faktur_kirim_f WHERE status_tfkkf='Sudah Dikirim' AND {$whereCreated}");
    $q10->execute($bindCreated);
    $summary['faktur_teririm'] = intval($q10->fetchColumn() ?? 0);

    $q11 = $conn->prepare("SELECT COUNT(DISTINCT id_tfk) FROM transaksi_faktur_kirim_f WHERE status_tfkkf='Belum Dikirim' AND {$whereCreated}");
    $q11->execute($bindCreated);
    $summary['faktur_tidak_teririm'] = intval($q11->fetchColumn() ?? 0);

    $q12 = $conn->prepare("SELECT COUNT(DISTINCT id_tfk) FROM transaksi_faktur_kirim_f WHERE {$whereCreated}");
    $q12->execute($bindCreated);
    $total_faktur_f = intval($q12->fetchColumn() ?? 0);
    $summary['faktur_in_process'] = max(0, $total_faktur_f - $summary['faktur_teririm'] - $summary['faktur_tidak_teririm']);

    echo json_encode([
        "result" => $summary,
        "base_url_apl" => ($apl['base_url_apl'] ?? ''),
        "id_apl" => $id_apl,
        "nama_apl" => ($apl['nama_apl'] ?? ''),
        "mode" => $mode,
        "from" => $from,
        "to" => $to,
        "periode_requested" => $periode,
        "timestamp" => date('c'),
        "principle" => ($principle === '' ? 'all' : $principle),
        "special_mode" => ($special_mode === '' ? null : $special_mode),
    ]);

} catch (Exception $e) {
    error_log("API Sum Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["error" => "Database error"]);
} finally {
    if (isset($base) && method_exists($base,'close')) $base->close();
}
?>