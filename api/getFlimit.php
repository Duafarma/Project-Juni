<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
ini_set('display_errors', 0);

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$request  = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$tgl      = date('Y-m-d');
$catat    = date('Y-m-d H:i:s');
$cari     = $secu->injection(@$request['caridata']);
$page     = (int)($secu->injection(@$request['halaman']) ?: 1);
$maxi     = (int)($secu->injection(@$request['maximal']) ?: 15);
$encrypt  = $secu->injection(@$request['encrypt']);
$idAplReq = $secu->injection(@$request['id_apl']);
$statusFilterRaw = strtolower(trim((string)$secu->injection(@$request['statusfilter'])));
$action   = $secu->injection(@$request['action']);
if ($action === '') {
    $action = $secu->injection(@$request['act']);
}
if ($action === '') {
    $action = $secu->injection(@$_GET['act'] ?? '');  // also check URL query string (callFlimitActionApi passes act in query)
}
$idTfk    = $secu->injection(@$request['id_tfk']);
$noFaktur = $secu->injection(@$request['no_faktur']);
$idLimit  = (int)($secu->injection(@$request['id_limit']) ?: 0);
$sumber   = $secu->injection(@$request['sumber']);
$admin     = $secu->injection(@$request['admin']);
$adminNama = trim($secu->injection(@$request['admin_nama'] ?? ''));
$paginate = isset($request['paginate']) ? (int)$secu->injection(@$request['paginate']) : 1;
$mulai    = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

$source    = $data->self_apl();
$sourceKey = $source['key_apl'] ?? '';
$id_apl    = $source['id_apl'] ?? '';
$nama_apl  = $source['nama_apl'] ?? '';

function respondJson($payload, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($payload);
}

function logFlimitApiError($label, $context = array()) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $entry = array(
        'time'    => date('Y-m-d H:i:s'),
        'file'    => __FILE__,
        'label'   => $label,
        'context' => $context,
        'ip'      => $_SERVER['REMOTE_ADDR'] ?? '',
    );
    @file_put_contents($logDir . '/flimit_api.log', json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function isValidFlimitEncrypt($encrypt, $tgl, $sourceKey, $authSourceKey) {
    $candidates = array();
    if ($sourceKey !== '') {
        $candidates[] = md5($tgl . '#' . $sourceKey);
    }
    if ($authSourceKey !== '' && $authSourceKey !== $sourceKey) {
        $candidates[] = md5($tgl . '#' . $authSourceKey);
    }
    return in_array($encrypt, $candidates, true);
}

function resolveFlimitAuthSource($conn, $source, $requestedApl) {
    $requestedApl = trim((string)$requestedApl);
    if ($requestedApl === '' || strtolower($requestedApl) === 'all' || $requestedApl === (string)($source['id_apl'] ?? '')) {
        return $source;
    }
    $stmt = $conn->prepare('SELECT id_apl, nama_apl, key_apl, base_url_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
    $stmt->bindValue(':id_apl', $requestedApl, PDO::PARAM_STR);
    $stmt->execute();
    $apl = $stmt->fetch(PDO::FETCH_ASSOC);
    return $apl ?: $source;
}

function buildFlimitWhereClause($cari, $tgl1Cond, $tgl2Cond) {
    $where = '';
    if ($cari !== '') {
        $where = "(A.created_at LIKE :cari OR A.kode_tfk LIKE :cari OR A.id_tfk LIKE :cari OR B.nama_out LIKE :cari)";
    } else {
        $where = "1=1";
    }
    if ($tgl1Cond !== '') $where .= " AND A.created_at >= :tgl1";
    if ($tgl2Cond !== '') $where .= " AND A.created_at <= :tgl2";
    return $where;
}

function normalizeFlimitStatuses($statusFilterRaw) {
    $allowed = array('limit', 'approve', 'merah', 'orange');
    $statuses = array();

    foreach (explode(',', (string)$statusFilterRaw) as $item) {
        $item = strtolower(trim($item));
        if ($item === '' || !in_array($item, $allowed, true)) {
            continue;
        }
        $statuses[$item] = $item;
    }

    return array_values($statuses);
}

function fetchLocalFlimit($conn, $cari, $tgl1, $tgl2, $statusFilterRaw = '', $offset = null, $limit = null) {
    $cariLike = $cari !== '' ? '%' . $cari . '%' : '';
    $hasTgl1  = ($tgl1 !== '');
    $hasTgl2  = ($tgl2 !== '');
    $allowedStatuses = normalizeFlimitStatuses($statusFilterRaw);

    $limitLatestSql = "
        SELECT
            ld.no_faktur,
            SUBSTRING_INDEX(GROUP_CONCAT(l.id_limit   ORDER BY l.id_limit DESC), ',', 1) AS id_limit_last,
            SUBSTRING_INDEX(GROUP_CONCAT(l.tgl_limit  ORDER BY l.id_limit DESC), ',', 1) AS tgl_limit,
            SUBSTRING_INDEX(GROUP_CONCAT(l.created_by ORDER BY l.id_limit DESC), ',', 1) AS created_by_limit
        FROM limit_detail ld
        INNER JOIN `limit` l ON l.id_limit = ld.id_limit
        GROUP BY ld.no_faktur
    ";

    $limitMerahSql = "
        SELECT
            lmd.no_faktur,
            SUBSTRING_INDEX(GROUP_CONCAT(lm.id_merah    ORDER BY lm.id_merah DESC), ',', 1) AS id_merah_last,
            SUBSTRING_INDEX(GROUP_CONCAT(lm.tgl_merah   ORDER BY lm.id_merah DESC), ',', 1) AS tgl_merah,
            SUBSTRING_INDEX(GROUP_CONCAT(lm.created_by  ORDER BY lm.id_merah DESC), ',', 1) AS created_by_merah
        FROM limit_merah_detail lmd
        INNER JOIN limit_merah lm ON lm.id_merah = lmd.id_merah
        GROUP BY lmd.no_faktur
    ";

    $limitOrangeSql = "
        SELECT
            lod.no_faktur,
            SUBSTRING_INDEX(GROUP_CONCAT(lo.id_orange   ORDER BY lo.id_orange DESC), ',', 1) AS id_orange_last,
            SUBSTRING_INDEX(GROUP_CONCAT(lo.tgl_orange  ORDER BY lo.id_orange DESC), ',', 1) AS tgl_orange,
            SUBSTRING_INDEX(GROUP_CONCAT(lo.created_by  ORDER BY lo.id_orange DESC), ',', 1) AS created_by_orange
        FROM limit_orange_detail lod
        INNER JOIN limit_orange lo ON lo.id_orange = lod.id_orange
        GROUP BY lod.no_faktur
    ";

    $limitKuningSql = "
        SELECT
            lkd.no_faktur,
            SUBSTRING_INDEX(GROUP_CONCAT(lk.id_kuning   ORDER BY lk.id_kuning DESC), ',', 1) AS id_kuning_last,
            SUBSTRING_INDEX(GROUP_CONCAT(lk.tgl_kuning  ORDER BY lk.id_kuning DESC), ',', 1) AS tgl_kuning,
            SUBSTRING_INDEX(GROUP_CONCAT(lk.created_by  ORDER BY lk.id_kuning DESC), ',', 1) AS created_by_kuning
        FROM limit_kuning_detail lkd
        INNER JOIN limit_kuning lk ON lk.id_kuning = lkd.id_kuning
        GROUP BY lkd.no_faktur
    ";

    // Build WHERE for each branch of UNION
    $buildWhere = function($alias) use ($cari, $hasTgl1, $hasTgl2, $allowedStatuses) {
        $parts = array();
        if ($cari !== '') {
            $parts[] = "($alias.created_at LIKE :cari OR $alias.kode_tfk LIKE :cari OR $alias.id_tfk LIKE :cari OR B.nama_out LIKE :cari)";
        } else {
            $parts[] = "1=1";
        }
        if ($hasTgl1) $parts[] = "$alias.created_at >= :tgl1";
        if ($hasTgl2) $parts[] = "$alias.created_at <= :tgl2";

        if (!empty($allowedStatuses)) {
            $quotedStatuses = array_map(static function($status) {
                return "'" . $status . "'";
            }, $allowedStatuses);
            $parts[] = "LOWER(TRIM(COALESCE($alias.status_limit,''))) IN (" . implode(',', $quotedStatuses) . ")";
        } else {
            $parts[] = "LOWER(TRIM(COALESCE($alias.status_limit,''))) IN ('limit','approve','merah','orange')";
        }

        return implode(' AND ', $parts);
    };

    $whereA = $buildWhere('A');
    $whereA2 = $buildWhere('A');
    $whereA3 = $buildWhere('A');

    $qCount = "SELECT COUNT(*) AS total
               FROM (
                   SELECT A.id_tfk
                   FROM transaksi_faktur AS A
                   LEFT JOIN outlet AS B ON A.id_out = B.id_out
                   WHERE $whereA
                   UNION ALL
                   SELECT A.id_tfk
                   FROM transaksi_faktur_pim AS A
                   LEFT JOIN outlet AS B ON A.id_out = B.id_out
                   WHERE $whereA2
                   UNION ALL
                   SELECT A.id_tfk
                   FROM transaksi_faktur_c AS A
                   LEFT JOIN outlet AS B ON A.id_out = B.id_out
                   WHERE $whereA3
               ) AS combined_data";

    $stmtCount = $conn->prepare($qCount);
    if ($cari !== '') $stmtCount->bindValue(':cari', $cariLike, PDO::PARAM_STR);
    if ($hasTgl1)     $stmtCount->bindValue(':tgl1', $tgl1, PDO::PARAM_STR);
    if ($hasTgl2)     $stmtCount->bindValue(':tgl2', $tgl2, PDO::PARAM_STR);
    $stmtCount->execute();
    $total = (int)$stmtCount->fetchColumn();

    $qMaster = "SELECT
                    X.id_tfk,
                    X.kode_tfk,
                    X.created_at,
                    X.tgl_tfk,
                    X.total_tfk,
                    X.status_limit,
                    X.nama_out,
                    X.limit_outlet,
                    X.status_pembayaran,
                    X.sumber,
                    X.approval_nama,
                    X.tgl_approve,
                    X.approve_type
                FROM (
                    SELECT
                        A.id_tfk,
                        A.kode_tfk,
                        A.created_at,
                        A.tgl_tfk,
                        A.total_tfk,
                        A.status_limit,
                        B.nama_out,
                        B.status_pembayaran,
                        CAST(REPLACE(REPLACE(COALESCE(B.`platform`,'0'),'.',''),',','') AS UNSIGNED) AS limit_outlet,
                        'Cendo' AS sumber,
                        CONVERT(CASE WHEN M.created_by_merah IS NOT NULL
                             THEN COALESCE(ADM_M.nama_adm, M.created_by_merah)
                             WHEN O.created_by_orange IS NOT NULL
                             THEN COALESCE(ADM_O.nama_adm, O.created_by_orange)
                                WHEN K.created_by_kuning IS NOT NULL
                                THEN COALESCE(ADM_K.nama_adm, K.created_by_kuning)
                             ELSE COALESCE(AD1.nama_adm, L.created_by_limit)
                        END USING utf8mb4) COLLATE utf8mb4_general_ci AS approval_nama,
                        CASE WHEN M.tgl_merah IS NOT NULL THEN M.tgl_merah
                             WHEN O.tgl_orange IS NOT NULL THEN O.tgl_orange
                                WHEN K.tgl_kuning IS NOT NULL THEN K.tgl_kuning
                             ELSE L.tgl_limit END AS tgl_approve,
                        CASE WHEN M.created_by_merah IS NOT NULL THEN 'merah'
                             WHEN O.created_by_orange IS NOT NULL THEN 'orange'
                                WHEN K.created_by_kuning IS NOT NULL THEN 'kuning'
                             ELSE 'limit' END AS approve_type
                    FROM transaksi_faktur AS A
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out
                    LEFT JOIN ($limitLatestSql) AS L  ON L.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS AD1            ON AD1.id_adm = L.created_by_limit
                    LEFT JOIN ($limitMerahSql) AS M   ON M.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_M          ON ADM_M.id_adm = M.created_by_merah
                    LEFT JOIN ($limitOrangeSql) AS O  ON O.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_O          ON ADM_O.id_adm = O.created_by_orange
                        LEFT JOIN ($limitKuningSql) AS K  ON K.no_faktur = A.id_tfk
                        LEFT JOIN adminz AS ADM_K          ON ADM_K.id_adm = K.created_by_kuning
                    WHERE $whereA

                    UNION ALL

                    SELECT
                        A.id_tfk,
                        A.kode_tfk,
                        A.created_at,
                        A.tgl_tfk,
                        A.total_tfk,
                        A.status_limit,
                        B.nama_out,
                        B.status_pembayaran,
                        CAST(REPLACE(REPLACE(COALESCE(B.`platform`,'0'),'.',''),',','') AS UNSIGNED) AS limit_outlet,
                        'PIM' AS sumber,
                        CONVERT(CASE WHEN M2.created_by_merah IS NOT NULL
                             THEN COALESCE(ADM_M2.nama_adm, M2.created_by_merah)
                             WHEN O2.created_by_orange IS NOT NULL
                             THEN COALESCE(ADM_O2.nama_adm, O2.created_by_orange)
                                WHEN K2.created_by_kuning IS NOT NULL
                                THEN COALESCE(ADM_K2.nama_adm, K2.created_by_kuning)
                             ELSE COALESCE(AD2.nama_adm, L2.created_by_limit)
                        END USING utf8mb4) COLLATE utf8mb4_general_ci AS approval_nama,
                        CASE WHEN M2.tgl_merah IS NOT NULL THEN M2.tgl_merah
                             WHEN O2.tgl_orange IS NOT NULL THEN O2.tgl_orange
                                WHEN K2.tgl_kuning IS NOT NULL THEN K2.tgl_kuning
                             ELSE L2.tgl_limit END AS tgl_approve,
                        CASE WHEN M2.created_by_merah IS NOT NULL THEN 'merah'
                             WHEN O2.created_by_orange IS NOT NULL THEN 'orange'
                                WHEN K2.created_by_kuning IS NOT NULL THEN 'kuning'
                             ELSE 'limit' END AS approve_type
                    FROM transaksi_faktur_pim AS A
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out
                    LEFT JOIN ($limitLatestSql) AS L2  ON L2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS AD2             ON AD2.id_adm = L2.created_by_limit
                    LEFT JOIN ($limitMerahSql) AS M2   ON M2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_M2          ON ADM_M2.id_adm = M2.created_by_merah
                    LEFT JOIN ($limitOrangeSql) AS O2  ON O2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_O2          ON ADM_O2.id_adm = O2.created_by_orange
                        LEFT JOIN ($limitKuningSql) AS K2  ON K2.no_faktur = A.id_tfk
                        LEFT JOIN adminz AS ADM_K2          ON ADM_K2.id_adm = K2.created_by_kuning
                    WHERE $whereA2

                    UNION ALL

                    SELECT
                        A.id_tfk,
                        A.kode_tfk,
                        A.created_at,
                        A.tgl_tfk,
                        A.total_tfk,
                        A.status_limit,
                        B.nama_out,
                        B.status_pembayaran,
                        CAST(REPLACE(REPLACE(COALESCE(B.`platform`,'0'),'.',''),',','') AS UNSIGNED) AS limit_outlet,
                        'C' AS sumber,
                        CONVERT(CASE WHEN M3.created_by_merah IS NOT NULL
                             THEN COALESCE(ADM_M3.nama_adm, M3.created_by_merah)
                             WHEN O3.created_by_orange IS NOT NULL
                             THEN COALESCE(ADM_O3.nama_adm, O3.created_by_orange)
                                WHEN K3.created_by_kuning IS NOT NULL
                                THEN COALESCE(ADM_K3.nama_adm, K3.created_by_kuning)
                             ELSE COALESCE(AD3.nama_adm, L3.created_by_limit)
                        END USING utf8mb4) COLLATE utf8mb4_general_ci AS approval_nama,
                        CASE WHEN M3.tgl_merah IS NOT NULL THEN M3.tgl_merah
                             WHEN O3.tgl_orange IS NOT NULL THEN O3.tgl_orange
                                WHEN K3.tgl_kuning IS NOT NULL THEN K3.tgl_kuning
                             ELSE L3.tgl_limit END AS tgl_approve,
                        CASE WHEN M3.created_by_merah IS NOT NULL THEN 'merah'
                             WHEN O3.created_by_orange IS NOT NULL THEN 'orange'
                                WHEN K3.created_by_kuning IS NOT NULL THEN 'kuning'
                             ELSE 'limit' END AS approve_type
                    FROM transaksi_faktur_c AS A
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out
                    LEFT JOIN ($limitLatestSql) AS L3  ON L3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS AD3             ON AD3.id_adm = L3.created_by_limit
                    LEFT JOIN ($limitMerahSql) AS M3   ON M3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_M3          ON ADM_M3.id_adm = M3.created_by_merah
                    LEFT JOIN ($limitOrangeSql) AS O3  ON O3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_O3          ON ADM_O3.id_adm = O3.created_by_orange
                        LEFT JOIN ($limitKuningSql) AS K3  ON K3.no_faktur = A.id_tfk
                        LEFT JOIN adminz AS ADM_K3          ON ADM_K3.id_adm = K3.created_by_kuning
                    WHERE $whereA3
                ) AS X
                ORDER BY
                    CASE WHEN LOWER(TRIM(COALESCE(X.status_limit,''))) = 'limit'  THEN 0
                         WHEN LOWER(TRIM(COALESCE(X.status_limit,''))) = 'merah'  THEN 1
                         WHEN LOWER(TRIM(COALESCE(X.status_limit,''))) = 'orange' THEN 2
                            WHEN LOWER(TRIM(COALESCE(X.status_limit,''))) = 'kuning' THEN 3
                            ELSE 4 END ASC,
                    X.created_at DESC";

    if ($offset !== null && $limit !== null) {
        $qMaster .= ' LIMIT :mulai, :maxi';
    }

    $stmt = $conn->prepare($qMaster);
    if ($cari !== '') $stmt->bindValue(':cari', $cariLike, PDO::PARAM_STR);
    if ($hasTgl1)     $stmt->bindValue(':tgl1', $tgl1, PDO::PARAM_STR);
    if ($hasTgl2)     $stmt->bindValue(':tgl2', $tgl2, PDO::PARAM_STR);
    if ($offset !== null && $limit !== null) {
        $stmt->bindValue(':mulai', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':maxi', (int)$limit, PDO::PARAM_INT);
    }
    $stmt->execute();

    return array(
        'total' => $total,
        'data'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
    );
}

function fetchLocalFlimitDetail(PDO $conn, $idOrKode) {
    $qDetail = "SELECT
                    X.id_tfk,
                    X.kode_tfk,
                    X.created_at,
                    X.tgl_tfk,
                    X.total_tfk,
                    X.status_limit,
                    X.id_out,
                    X.nama_out,
                    X.limit_outlet,
                    X.status_pembayaran,
                    X.sumber,
                    X.approval_nama,
                    X.tgl_approve,
                    X.approve_type
                FROM (
                    SELECT
                        A.id_tfk,
                        A.kode_tfk,
                        A.created_at,
                        A.tgl_tfk,
                        A.total_tfk,
                        A.status_limit,
                        A.id_out,
                        B.nama_out,
                        B.status_pembayaran,
                        CAST(REPLACE(REPLACE(COALESCE(B.`platform`,'0'),'.',''),',','') AS UNSIGNED) AS limit_outlet,
                        'Cendo' AS sumber,
                        CONVERT(CASE WHEN M.created_by_merah IS NOT NULL
                             THEN COALESCE(ADM_M.nama_adm, M.created_by_merah)
                             WHEN O.created_by_orange IS NOT NULL
                             THEN COALESCE(ADM_O.nama_adm, O.created_by_orange)
                                WHEN K.created_by_kuning IS NOT NULL
                                THEN COALESCE(ADM_K.nama_adm, K.created_by_kuning)
                             ELSE COALESCE(AD1.nama_adm, L.created_by_limit)
                        END USING utf8mb4) COLLATE utf8mb4_general_ci AS approval_nama,
                        CASE WHEN M.tgl_merah IS NOT NULL THEN M.tgl_merah
                             WHEN O.tgl_orange IS NOT NULL THEN O.tgl_orange
                                WHEN K.tgl_kuning IS NOT NULL THEN K.tgl_kuning
                             ELSE L.tgl_limit END AS tgl_approve,
                        CASE WHEN M.created_by_merah IS NOT NULL THEN 'merah'
                             WHEN O.created_by_orange IS NOT NULL THEN 'orange'
                                WHEN K.created_by_kuning IS NOT NULL THEN 'kuning'
                             ELSE 'limit' END AS approve_type,
                        0 AS sumber_urut
                    FROM transaksi_faktur AS A
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out
                    LEFT JOIN (
                        SELECT
                            ld.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.id_limit ORDER BY l.id_limit DESC), ',', 1) AS id_limit_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.tgl_limit ORDER BY l.id_limit DESC), ',', 1) AS tgl_limit,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.created_by ORDER BY l.id_limit DESC), ',', 1) AS created_by_limit
                        FROM limit_detail ld
                        INNER JOIN `limit` l ON l.id_limit = ld.id_limit
                        GROUP BY ld.no_faktur
                    ) AS L ON L.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS AD1 ON AD1.id_adm = L.created_by_limit
                    LEFT JOIN (
                        SELECT
                            lmd.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.id_merah ORDER BY lm.id_merah DESC), ',', 1) AS id_merah_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.tgl_merah ORDER BY lm.id_merah DESC), ',', 1) AS tgl_merah,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.created_by ORDER BY lm.id_merah DESC), ',', 1) AS created_by_merah
                        FROM limit_merah_detail lmd
                        INNER JOIN limit_merah lm ON lm.id_merah = lmd.id_merah
                        GROUP BY lmd.no_faktur
                    ) AS M ON M.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_M ON ADM_M.id_adm = M.created_by_merah
                    LEFT JOIN (
                        SELECT
                            lod.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.id_orange ORDER BY lo.id_orange DESC), ',', 1) AS id_orange_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.tgl_orange ORDER BY lo.id_orange DESC), ',', 1) AS tgl_orange,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.created_by ORDER BY lo.id_orange DESC), ',', 1) AS created_by_orange
                        FROM limit_orange_detail lod
                        INNER JOIN limit_orange lo ON lo.id_orange = lod.id_orange
                        GROUP BY lod.no_faktur
                    ) AS O ON O.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_O ON ADM_O.id_adm = O.created_by_orange
                    LEFT JOIN (
                        SELECT
                            lkd.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.id_kuning ORDER BY lk.id_kuning DESC), ',', 1) AS id_kuning_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.tgl_kuning ORDER BY lk.id_kuning DESC), ',', 1) AS tgl_kuning,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.created_by ORDER BY lk.id_kuning DESC), ',', 1) AS created_by_kuning
                        FROM limit_kuning_detail lkd
                        INNER JOIN limit_kuning lk ON lk.id_kuning = lkd.id_kuning
                        GROUP BY lkd.no_faktur
                    ) AS K ON K.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_K ON ADM_K.id_adm = K.created_by_kuning
                    WHERE A.id_tfk = :id_exact OR A.kode_tfk = :kode_exact

                    UNION ALL

                    SELECT
                        A.id_tfk,
                        A.kode_tfk,
                        A.created_at,
                        A.tgl_tfk,
                        A.total_tfk,
                        A.status_limit,
                        A.id_out,
                        B.nama_out,
                        B.status_pembayaran,
                        CAST(REPLACE(REPLACE(COALESCE(B.`platform`,'0'),'.',''),',','') AS UNSIGNED) AS limit_outlet,
                        'PIM' AS sumber,
                        CONVERT(CASE WHEN M2.created_by_merah IS NOT NULL
                             THEN COALESCE(ADM_M2.nama_adm, M2.created_by_merah)
                             WHEN O2.created_by_orange IS NOT NULL
                             THEN COALESCE(ADM_O2.nama_adm, O2.created_by_orange)
                                WHEN K2.created_by_kuning IS NOT NULL
                                THEN COALESCE(ADM_K2.nama_adm, K2.created_by_kuning)
                             ELSE COALESCE(AD2.nama_adm, L2.created_by_limit)
                        END USING utf8mb4) COLLATE utf8mb4_general_ci AS approval_nama,
                        CASE WHEN M2.tgl_merah IS NOT NULL THEN M2.tgl_merah
                             WHEN O2.tgl_orange IS NOT NULL THEN O2.tgl_orange
                                WHEN K2.tgl_kuning IS NOT NULL THEN K2.tgl_kuning
                             ELSE L2.tgl_limit END AS tgl_approve,
                        CASE WHEN M2.created_by_merah IS NOT NULL THEN 'merah'
                             WHEN O2.created_by_orange IS NOT NULL THEN 'orange'
                                WHEN K2.created_by_kuning IS NOT NULL THEN 'kuning'
                             ELSE 'limit' END AS approve_type,
                        1 AS sumber_urut
                    FROM transaksi_faktur_pim AS A
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out
                    LEFT JOIN (
                        SELECT
                            ld.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.id_limit ORDER BY l.id_limit DESC), ',', 1) AS id_limit_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.tgl_limit ORDER BY l.id_limit DESC), ',', 1) AS tgl_limit,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.created_by ORDER BY l.id_limit DESC), ',', 1) AS created_by_limit
                        FROM limit_detail ld
                        INNER JOIN `limit` l ON l.id_limit = ld.id_limit
                        GROUP BY ld.no_faktur
                    ) AS L2 ON L2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS AD2 ON AD2.id_adm = L2.created_by_limit
                    LEFT JOIN (
                        SELECT
                            lmd.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.id_merah ORDER BY lm.id_merah DESC), ',', 1) AS id_merah_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.tgl_merah ORDER BY lm.id_merah DESC), ',', 1) AS tgl_merah,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.created_by ORDER BY lm.id_merah DESC), ',', 1) AS created_by_merah
                        FROM limit_merah_detail lmd
                        INNER JOIN limit_merah lm ON lm.id_merah = lmd.id_merah
                        GROUP BY lmd.no_faktur
                    ) AS M2 ON M2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_M2 ON ADM_M2.id_adm = M2.created_by_merah
                    LEFT JOIN (
                        SELECT
                            lod.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.id_orange ORDER BY lo.id_orange DESC), ',', 1) AS id_orange_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.tgl_orange ORDER BY lo.id_orange DESC), ',', 1) AS tgl_orange,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.created_by ORDER BY lo.id_orange DESC), ',', 1) AS created_by_orange
                        FROM limit_orange_detail lod
                        INNER JOIN limit_orange lo ON lo.id_orange = lod.id_orange
                        GROUP BY lod.no_faktur
                    ) AS O2 ON O2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_O2 ON ADM_O2.id_adm = O2.created_by_orange
                    LEFT JOIN (
                        SELECT
                            lkd.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.id_kuning ORDER BY lk.id_kuning DESC), ',', 1) AS id_kuning_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.tgl_kuning ORDER BY lk.id_kuning DESC), ',', 1) AS tgl_kuning,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.created_by ORDER BY lk.id_kuning DESC), ',', 1) AS created_by_kuning
                        FROM limit_kuning_detail lkd
                        INNER JOIN limit_kuning lk ON lk.id_kuning = lkd.id_kuning
                        GROUP BY lkd.no_faktur
                    ) AS K2 ON K2.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_K2 ON ADM_K2.id_adm = K2.created_by_kuning
                    WHERE A.id_tfk = :id_exact_pim OR A.kode_tfk = :kode_exact_pim

                    UNION ALL

                    SELECT
                        A.id_tfk,
                        A.kode_tfk,
                        A.created_at,
                        A.tgl_tfk,
                        A.total_tfk,
                        A.status_limit,
                        A.id_out,
                        B.nama_out,
                        B.status_pembayaran,
                        CAST(REPLACE(REPLACE(COALESCE(B.`platform`,'0'),'.',''),',','') AS UNSIGNED) AS limit_outlet,
                        'C' AS sumber,
                        CONVERT(CASE WHEN M3.created_by_merah IS NOT NULL
                             THEN COALESCE(ADM_M3.nama_adm, M3.created_by_merah)
                             WHEN O3.created_by_orange IS NOT NULL
                             THEN COALESCE(ADM_O3.nama_adm, O3.created_by_orange)
                                WHEN K3.created_by_kuning IS NOT NULL
                                THEN COALESCE(ADM_K3.nama_adm, K3.created_by_kuning)
                             ELSE COALESCE(AD3.nama_adm, L3.created_by_limit)
                        END USING utf8mb4) COLLATE utf8mb4_general_ci AS approval_nama,
                        CASE WHEN M3.tgl_merah IS NOT NULL THEN M3.tgl_merah
                             WHEN O3.tgl_orange IS NOT NULL THEN O3.tgl_orange
                                WHEN K3.tgl_kuning IS NOT NULL THEN K3.tgl_kuning
                             ELSE L3.tgl_limit END AS tgl_approve,
                        CASE WHEN M3.created_by_merah IS NOT NULL THEN 'merah'
                             WHEN O3.created_by_orange IS NOT NULL THEN 'orange'
                                WHEN K3.created_by_kuning IS NOT NULL THEN 'kuning'
                             ELSE 'limit' END AS approve_type,
                        2 AS sumber_urut
                    FROM transaksi_faktur_c AS A
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out
                    LEFT JOIN (
                        SELECT
                            ld.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.id_limit ORDER BY l.id_limit DESC), ',', 1) AS id_limit_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.tgl_limit ORDER BY l.id_limit DESC), ',', 1) AS tgl_limit,
                            SUBSTRING_INDEX(GROUP_CONCAT(l.created_by ORDER BY l.id_limit DESC), ',', 1) AS created_by_limit
                        FROM limit_detail ld
                        INNER JOIN `limit` l ON l.id_limit = ld.id_limit
                        GROUP BY ld.no_faktur
                    ) AS L3 ON L3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS AD3 ON AD3.id_adm = L3.created_by_limit
                    LEFT JOIN (
                        SELECT
                            lmd.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.id_merah ORDER BY lm.id_merah DESC), ',', 1) AS id_merah_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.tgl_merah ORDER BY lm.id_merah DESC), ',', 1) AS tgl_merah,
                            SUBSTRING_INDEX(GROUP_CONCAT(lm.created_by ORDER BY lm.id_merah DESC), ',', 1) AS created_by_merah
                        FROM limit_merah_detail lmd
                        INNER JOIN limit_merah lm ON lm.id_merah = lmd.id_merah
                        GROUP BY lmd.no_faktur
                    ) AS M3 ON M3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_M3 ON ADM_M3.id_adm = M3.created_by_merah
                    LEFT JOIN (
                        SELECT
                            lod.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.id_orange ORDER BY lo.id_orange DESC), ',', 1) AS id_orange_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.tgl_orange ORDER BY lo.id_orange DESC), ',', 1) AS tgl_orange,
                            SUBSTRING_INDEX(GROUP_CONCAT(lo.created_by ORDER BY lo.id_orange DESC), ',', 1) AS created_by_orange
                        FROM limit_orange_detail lod
                        INNER JOIN limit_orange lo ON lo.id_orange = lod.id_orange
                        GROUP BY lod.no_faktur
                    ) AS O3 ON O3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_O3 ON ADM_O3.id_adm = O3.created_by_orange
                    LEFT JOIN (
                        SELECT
                            lkd.no_faktur,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.id_kuning ORDER BY lk.id_kuning DESC), ',', 1) AS id_kuning_last,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.tgl_kuning ORDER BY lk.id_kuning DESC), ',', 1) AS tgl_kuning,
                            SUBSTRING_INDEX(GROUP_CONCAT(lk.created_by ORDER BY lk.id_kuning DESC), ',', 1) AS created_by_kuning
                        FROM limit_kuning_detail lkd
                        INNER JOIN limit_kuning lk ON lk.id_kuning = lkd.id_kuning
                        GROUP BY lkd.no_faktur
                    ) AS K3 ON K3.no_faktur = A.id_tfk
                    LEFT JOIN adminz AS ADM_K3 ON ADM_K3.id_adm = K3.created_by_kuning
                    WHERE A.id_tfk = :id_exact_c OR A.kode_tfk = :kode_exact_c
                ) AS X
                ORDER BY X.sumber_urut ASC, X.created_at DESC
                LIMIT 1";

    $stmt = $conn->prepare($qDetail);
    $stmt->bindValue(':id_exact', $idOrKode, PDO::PARAM_STR);
    $stmt->bindValue(':kode_exact', $idOrKode, PDO::PARAM_STR);
    $stmt->bindValue(':id_exact_pim', $idOrKode, PDO::PARAM_STR);
    $stmt->bindValue(':kode_exact_pim', $idOrKode, PDO::PARAM_STR);
    $stmt->bindValue(':id_exact_c', $idOrKode, PDO::PARAM_STR);
    $stmt->bindValue(':kode_exact_c', $idOrKode, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
}

function fetchLocalFlimitHistory(PDO $conn, $idTfk) {
    $hist = array(
        'limit_detail' => array(),
        'limit_merah' => array(),
        'limit_orange' => array(),
        'limit_kuning' => array(),
    );

    if (trim((string)$idTfk) === '') {
        return $hist;
    }

    $qLimit = $conn->prepare("SELECT ld.*, l.kode_limit, l.tgl_limit, l.created_at AS header_created_at, l.created_by AS header_created_by,
                                     COALESCE(AD.nama_adm, ld.created_by) AS created_by_name
                              FROM limit_detail ld
                              LEFT JOIN `limit` l ON l.id_limit = ld.id_limit
                              LEFT JOIN adminz AD ON AD.id_adm = ld.created_by
                              WHERE ld.no_faktur = :id_tfk
                              ORDER BY ld.created_at DESC");
    $qLimit->bindValue(':id_tfk', $idTfk, PDO::PARAM_STR);
    $qLimit->execute();
    $hist['limit_detail'] = $qLimit->fetchAll(PDO::FETCH_ASSOC);

    $qMerah = $conn->prepare("SELECT lmd.*, lm.kode_merah, lm.tgl_merah, lm.created_at AS header_created_at, lm.created_by AS header_created_by,
                                     COALESCE(ADM.nama_adm, lmd.created_by) AS created_by_name
                              FROM limit_merah_detail lmd
                              LEFT JOIN limit_merah lm ON lm.id_merah = lmd.id_merah
                              LEFT JOIN adminz ADM ON ADM.id_adm = lmd.created_by
                              WHERE lmd.no_faktur = :id_tfk
                              ORDER BY lmd.created_at DESC");
    $qMerah->bindValue(':id_tfk', $idTfk, PDO::PARAM_STR);
    $qMerah->execute();
    $hist['limit_merah'] = $qMerah->fetchAll(PDO::FETCH_ASSOC);

    $qOrange = $conn->prepare("SELECT lod.*, lo.kode_orange, lo.tgl_orange, lo.created_at AS header_created_at, lo.created_by AS header_created_by,
                                      COALESCE(ADO.nama_adm, lod.created_by) AS created_by_name
                               FROM limit_orange_detail lod
                               LEFT JOIN limit_orange lo ON lo.id_orange = lod.id_orange
                               LEFT JOIN adminz ADO ON ADO.id_adm = lod.created_by
                               WHERE lod.no_faktur = :id_tfk
                               ORDER BY lod.created_at DESC");
    $qOrange->bindValue(':id_tfk', $idTfk, PDO::PARAM_STR);
    $qOrange->execute();
    $hist['limit_orange'] = $qOrange->fetchAll(PDO::FETCH_ASSOC);

    $qKuning = $conn->prepare("SELECT lkd.*, lk.kode_kuning, lk.tgl_kuning, lk.created_at AS header_created_at, lk.created_by AS header_created_by,
                                      COALESCE(ADK.nama_adm, lkd.created_by) AS created_by_name
                               FROM limit_kuning_detail lkd
                               LEFT JOIN limit_kuning lk ON lk.id_kuning = lkd.id_kuning
                               LEFT JOIN adminz ADK ON ADK.id_adm = lkd.created_by
                               WHERE lkd.no_faktur = :id_tfk
                               ORDER BY lkd.created_at DESC");
    $qKuning->bindValue(':id_tfk', $idTfk, PDO::PARAM_STR);
    $qKuning->execute();
    $hist['limit_kuning'] = $qKuning->fetchAll(PDO::FETCH_ASSOC);

    return $hist;
}

function updateStatusLimitApi(PDO $conn, $id_tfk, $status) {
    $paramType = ($status === null) ? PDO::PARAM_NULL : PDO::PARAM_STR;
    $up1 = $conn->prepare("UPDATE transaksi_faktur SET status_limit=:st WHERE id_tfk=:id");
    $up1->bindValue(':st', $status, $paramType);
    $up1->bindValue(':id', $id_tfk, PDO::PARAM_STR);
    $up1->execute();
    if ($up1->rowCount() > 0) return true;
    $up2 = $conn->prepare("UPDATE transaksi_faktur_pim SET status_limit=:st WHERE id_tfk=:id");
    $up2->bindValue(':st', $status, $paramType);
    $up2->bindValue(':id', $id_tfk, PDO::PARAM_STR);
    $up2->execute();
    if ($up2->rowCount() > 0) return true;
    $up3 = $conn->prepare("UPDATE transaksi_faktur_c SET status_limit=:st WHERE id_tfk=:id");
    $up3->bindValue(':st', $status, $paramType);
    $up3->bindValue(':id', $id_tfk, PDO::PARAM_STR);
    $up3->execute();
    return ($up3->rowCount() > 0);
}

function updateOutletLimitApi(PDO $conn, $id_tfk) {
    $q = $conn->prepare("SELECT total_tfk, id_out FROM transaksi_faktur WHERE id_tfk = :id LIMIT 1");
    $q->bindValue(':id', $id_tfk, PDO::PARAM_STR);
    $q->execute();
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $q = $conn->prepare("SELECT total_tfk, id_out FROM transaksi_faktur_pim WHERE id_tfk = :id LIMIT 1");
        $q->bindValue(':id', $id_tfk, PDO::PARAM_STR);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);
    }
    if (!$row) {
        $q = $conn->prepare("SELECT total_tfk, id_out FROM transaksi_faktur_c WHERE id_tfk = :id LIMIT 1");
        $q->bindValue(':id', $id_tfk, PDO::PARAM_STR);
        $q->execute();
        $row = $q->fetch(PDO::FETCH_ASSOC);
    }
    if ($row && isset($row['id_out'])) {
        $u = $conn->prepare("UPDATE outlet SET `limit` = :limit_val WHERE id_out = :id_out");
        $u->bindValue(':limit_val', (float)$row['total_tfk'], PDO::PARAM_STR);
        $u->bindValue(':id_out', $row['id_out'], PDO::PARAM_STR);
        $u->execute();
        return ($u->rowCount() > 0);
    }
    return false;
}

function resolveBranchBaseUrlFlimit($branch) {
    $baseUrl = trim($branch['base_url_apl'] ?? '');
    if ($baseUrl !== '') {
        return rtrim($baseUrl, '/');
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . '/monitoring';
}

function callFlimitBranchApi($branch, $payload, $method = 'GET') {
    $apiUrl = resolveBranchBaseUrlFlimit($branch) . '/api/getFlimit.php';
    $method = strtoupper($method);
    if ($method === 'GET') {
        $apiUrl .= '?' . http_build_query($payload);
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $error !== '') {
            return array('ok' => false, 'message' => $error ?: 'Curl request gagal', 'http_code' => $httpCode);
        }
        $json = json_decode($response, true);
        if (!is_array($json)) {
            return array('ok' => false, 'message' => 'Response cabang bukan JSON valid', 'http_code' => $httpCode, 'body' => $response);
        }
        return array('ok' => ($httpCode >= 200 && $httpCode < 300), 'http_code' => $httpCode, 'json' => $json, 'body' => $response);
    }

    $context  = stream_context_create(array(
        'http' => array(
            'method'  => $method,
            'timeout' => 20,
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'content' => ($method === 'POST') ? http_build_query($payload) : null,
        ),
    ));
    $response = @file_get_contents($apiUrl, false, $context);
    if ($response === false) {
        return array('ok' => false, 'message' => 'file_get_contents gagal', 'http_code' => 0);
    }
    $httpCode = 200;
    if (!empty($http_response_header) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
        $httpCode = (int)$match[1];
    }
    $json = json_decode($response, true);
    if (!is_array($json)) {
        return array('ok' => false, 'message' => 'Response cabang bukan JSON valid', 'http_code' => $httpCode, 'body' => $response);
    }
    return array('ok' => ($httpCode >= 200 && $httpCode < 300), 'http_code' => $httpCode, 'json' => $json, 'body' => $response);
}

// ─── Validate encrypt ───────────────────────────────────────────────────────
$authSource    = resolveFlimitAuthSource($conn, $source, $idAplReq);
$authSourceKey = $authSource['key_apl'] ?? $sourceKey;

if (!isValidFlimitEncrypt($encrypt, $tgl, $sourceKey, $authSourceKey)) {
    logFlimitApiError('encrypt_invalid', array('encrypt' => $encrypt, 'id_apl' => $idAplReq));
    respondJson(array('status' => 'error', 'message' => 'Akses tidak diizinkan'), 403);
    $conn = $base->close();
    exit;
}

// ─── Action handlers ────────────────────────────────────────────────────────
try {

    // ── INPUT ────────────────────────────────────────────────────────────────
    if ($action === 'input') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respondJson(array('status' => 'error', 'message' => 'Method must be POST'), 405);
            $conn = $base->close(); exit;
        }

        $tglInput    = $secu->injection($_POST['tanggal']  ?? '');
        $noFakturArr = $_POST['no_faktur'] ?? array();
        $ketArr      = $_POST['ket']       ?? array();
        $jumlah      = is_array($noFakturArr) ? count($noFakturArr) : 0;

        if (empty($tglInput)) {
            respondJson(array('status' => 'error', 'message' => 'Tanggal wajib diisi'), 422);
            $conn = $base->close(); exit;
        }
        if ($jumlah < 1) {
            respondJson(array('status' => 'error', 'message' => 'Nomor faktur wajib diisi'), 422);
            $conn = $base->close(); exit;
        }

        // Find first valid id_tfk for header
        $firstIdTfk = '';
        $firstSumber = 'Cendo';
        for ($i = 0; $i < $jumlah; $i++) {
            $raw = $secu->injection($noFakturArr[$i] ?? '');
            if (empty($raw)) continue;
            $parts = explode('|', $raw);
            $id_tfk_check = (count($parts) >= 2) ? trim($parts[1]) : trim($parts[0]);
            $sumber_check  = (count($parts) >= 2) ? trim($parts[0]) : 'Cendo';
            if (!empty($id_tfk_check)) {
                $firstIdTfk  = $id_tfk_check;
                $firstSumber = (strtoupper($sumber_check) === 'PIM') ? 'PIM' : 'Cendo';
                break;
            }
        }
        if (empty($firstIdTfk)) {
            respondJson(array('status' => 'error', 'message' => 'Format nomor faktur tidak valid'), 422);
            $conn = $base->close(); exit;
        }

        $conn->beginTransaction();

        // Generate unique kode
        $kode = ''; $isDuplicate = true; $retry = 0; $maxRetries = 5;
        while ($isDuplicate && $retry < $maxRetries) {
            $kode = $data->basecode('LMT', 5, 'id_limit', '`limit`');
            if ($retry > 0) $kode .= rand(10, 99);
            $chk = $conn->prepare("SELECT COUNT(*) FROM `limit` WHERE kode_limit = :kode");
            $chk->bindValue(':kode', $kode, PDO::PARAM_STR);
            $chk->execute();
            $isDuplicate = ((int)$chk->fetchColumn() > 0);
            $retry++;
        }
        if ($isDuplicate) {
            $conn->rollBack();
            respondJson(array('status' => 'error', 'message' => 'Gagal generate kode unik'), 500);
            $conn = $base->close(); exit;
        }

        $insH = $conn->prepare("INSERT INTO `limit` (kode_limit, id_tfk, sumber, status_limit, tgl_limit, created_at, created_by) VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl, :catat, :admin)");
        $insH->bindValue(':kode',   $kode,         PDO::PARAM_STR);
        $insH->bindValue(':id_tfk', $firstIdTfk,   PDO::PARAM_STR);
        $insH->bindValue(':sumber', $firstSumber,  PDO::PARAM_STR);
        $insH->bindValue(':tgl',    $tglInput,     PDO::PARAM_STR);
        $insH->bindValue(':catat',  $catat,        PDO::PARAM_STR);
        $insH->bindValue(':admin',  $admin,        PDO::PARAM_STR);
        if (!$insH->execute()) {
            $conn->rollBack();
            logFlimitApiError('input_header_failed', $insH->errorInfo());
            respondJson(array('status' => 'error', 'message' => 'Gagal menyimpan header limit'), 500);
            $conn = $base->close(); exit;
        }
        $id_limit_new = (int)$conn->lastInsertId();

        $insD = $conn->prepare("INSERT INTO `limit_detail` (id_limit, no_faktur, ket, status, created_at, created_by) VALUES(:id_limit, :no_faktur, :ket, 'Approved', :catat, :admin)");
        $inserted = 0;
        for ($i = 0; $i < $jumlah; $i++) {
            $raw = $secu->injection($noFakturArr[$i] ?? '');
            $ket = $secu->injection($ketArr[$i]      ?? '');
            if (empty($raw)) continue;
            $parts   = explode('|', $raw);
            $id_tfk_d = (count($parts) >= 2) ? trim($parts[1]) : trim($parts[0]);
            if (empty($id_tfk_d)) continue;
            $insD->bindValue(':id_limit',  $id_limit_new, PDO::PARAM_INT);
            $insD->bindValue(':no_faktur', $id_tfk_d,     PDO::PARAM_STR);
            $insD->bindValue(':ket',       $ket,          PDO::PARAM_STR);
            $insD->bindValue(':catat',     $catat,        PDO::PARAM_STR);
            $insD->bindValue(':admin',     $admin,        PDO::PARAM_STR);
            $insD->execute();
            $inserted++;
            updateStatusLimitApi($conn, $id_tfk_d, 'limit');
        }

        if ($inserted < 1) {
            $conn->rollBack();
            respondJson(array('status' => 'error', 'message' => 'Tidak ada detail limit yang berhasil disimpan'), 422);
            $conn = $base->close(); exit;
        }

        $ri = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Finance Limit', 'Create', 'Input Limit', :catat, :admin)");
        $ri->bindValue(':kode',  $kode,  PDO::PARAM_STR);
        $ri->bindValue(':catat', $catat, PDO::PARAM_STR);
        $ri->bindValue(':admin', $admin, PDO::PARAM_STR);
        $ri->execute();

        $conn->commit();
        respondJson(array('status' => 'success', 'message' => 'Data limit berhasil disimpan', 'kode' => $kode));
        $conn = $base->close(); exit;
    }

    // ── DELETE ───────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respondJson(array('status' => 'error', 'message' => 'Method must be POST'), 405);
            $conn = $base->close(); exit;
        }

        // Forward to remote branch if id_apl differs from local
        if ($idAplReq !== '' && strtolower($idAplReq) !== 'all' && (string)$idAplReq !== (string)$id_apl) {
            $stmtBr = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
            $stmtBr->bindValue(':id_apl', $idAplReq, PDO::PARAM_STR);
            $stmtBr->execute();
            $branchApl = $stmtBr->fetch(PDO::FETCH_ASSOC);
            if (!$branchApl) {
                respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
                $conn = $base->close(); exit;
            }
            $remote = callFlimitBranchApi($branchApl, array_merge($_POST, array(
                'encrypt' => md5($tgl . '#' . $branchApl['key_apl']),
                'id_apl'  => $branchApl['id_apl'],
                'admin'   => $admin,
                'action'  => 'delete',
            )), 'POST');
            if (!$remote['ok']) {
                respondJson(array('status' => 'error', 'message' => isset($remote['message']) ? $remote['message'] : 'Gagal menghubungi cabang'), 502);
                $conn = $base->close(); exit;
            }
            respondJson($remote['json'], $remote['http_code'] ?? 200);
            $conn = $base->close(); exit;
        }

        $id_limit_del = (int)($secu->injection($_POST['id_limit'] ?? '') ?: $idLimit);
        if ($id_limit_del < 1) {
            respondJson(array('status' => 'error', 'message' => 'ID limit tidak valid'), 422);
            $conn = $base->close(); exit;
        }

        $conn->beginTransaction();

        $qDet = $conn->prepare("SELECT no_faktur FROM `limit_detail` WHERE id_limit = :id");
        $qDet->bindValue(':id', $id_limit_del, PDO::PARAM_INT);
        $qDet->execute();
        $detRows = $qDet->fetchAll(PDO::FETCH_ASSOC);

        foreach ($detRows as $r) {
            updateStatusLimitApi($conn, $r['no_faktur'], null);
        }

        $delD = $conn->prepare("DELETE FROM `limit_detail` WHERE id_limit = :id");
        $delD->bindValue(':id', $id_limit_del, PDO::PARAM_INT);
        $delD->execute();

        $delH = $conn->prepare("DELETE FROM `limit` WHERE id_limit = :id");
        $delH->bindValue(':id', $id_limit_del, PDO::PARAM_INT);
        $delH->execute();

        $ri = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Finance Limit', 'Delete', 'Delete Limit', :catat, :admin)");
        $ri->bindValue(':kode',  (string)$id_limit_del, PDO::PARAM_STR);
        $ri->bindValue(':catat', $catat,                PDO::PARAM_STR);
        $ri->bindValue(':admin', $admin,                PDO::PARAM_STR);
        $ri->execute();

        $conn->commit();
        respondJson(array('status' => 'success', 'message' => 'Data limit berhasil dihapus'));
        $conn = $base->close(); exit;
    }

    // ── APPROVE ──────────────────────────────────────────────────────────────
    if ($action === 'approve') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respondJson(array('status' => 'error', 'message' => 'Method must be POST'), 405);
            $conn = $base->close(); exit;
        }

        // Resolve admin display name: if admin_nama already provided (from forwarded branch request),
        // look up id_adm in local adminz by nama_adm to get the correct local id_adm.
        // If not provided, resolve nama_adm from local adminz using id_adm.
        if ($adminNama !== '') {
            // Request came forwarded from another branch — find local id_adm by nama_adm
            try {
                $qAdmByName = $conn->prepare("SELECT id_adm FROM adminz WHERE nama_adm = :nama LIMIT 1");
                $qAdmByName->bindValue(':nama', $adminNama, PDO::PARAM_STR);
                $qAdmByName->execute();
                $fetchedId = $qAdmByName->fetchColumn();
                if ($fetchedId !== false && $fetchedId !== '') $admin = $fetchedId;
            } catch (Exception $e) { /* ignore */ }
        } else if ($admin !== '') {
            // Local request — resolve nama_adm from id_adm
            try {
                $qAdmN = $conn->prepare("SELECT nama_adm FROM adminz WHERE id_adm = :id LIMIT 1");
                $qAdmN->bindValue(':id', $admin, PDO::PARAM_STR);
                $qAdmN->execute();
                $fetched = $qAdmN->fetchColumn();
                if ($fetched !== false && $fetched !== '') $adminNama = $fetched;
            } catch (Exception $e) { /* ignore */ }
        }
        if ($adminNama === '') $adminNama = $admin;

        // Forward to remote branch if id_apl differs from local
        if ($idAplReq !== '' && strtolower($idAplReq) !== 'all' && (string)$idAplReq !== (string)$id_apl) {
            $stmtBr = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
            $stmtBr->bindValue(':id_apl', $idAplReq, PDO::PARAM_STR);
            $stmtBr->execute();
            $branchApl = $stmtBr->fetch(PDO::FETCH_ASSOC);
            if (!$branchApl) {
                respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
                $conn = $base->close(); exit;
            }
            $remote = callFlimitBranchApi($branchApl, array_merge($_POST, array(
                'encrypt'    => md5($tgl . '#' . $branchApl['key_apl']),
                'id_apl'     => $branchApl['id_apl'],
                'admin'      => $admin,
                'admin_nama' => $adminNama,
                'action'     => 'approve',
            )), 'POST');
            if (!$remote['ok']) {
                respondJson(array('status' => 'error', 'message' => isset($remote['message']) ? $remote['message'] : 'Gagal menghubungi cabang'), 502);
                $conn = $base->close(); exit;
            }
            respondJson($remote['json'], $remote['http_code'] ?? 200);
            $conn = $base->close(); exit;
        }

        $singleNo    = $secu->injection($_POST['no_faktur']    ?? '');
        $approveType = strtolower(trim($secu->injection($_POST['approve_type'] ?? '')));
        $id_limit_ap = (int)($secu->injection($_POST['id_limit'] ?? '') ?: 0);
        $updated     = false;
        $kode_ref    = '';

        // Handle 3-part format: "sumber|kode_tfk|id_apl" — extract branch id_apl and strip it
        if (!empty($singleNo)) {
            $noParts3 = explode('|', $singleNo);
            if (count($noParts3) >= 3) {
                $idAplFromNo = trim($noParts3[2]);
                // Rebuild as 2-part without the branch id
                $singleNo = $noParts3[0] . '|' . $noParts3[1];
                // If faktur belongs to a different branch, forward there
                if ($idAplFromNo !== '' && $idAplFromNo !== (string)$id_apl) {
                    $stmtBrNo = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
                    $stmtBrNo->bindValue(':id_apl', $idAplFromNo, PDO::PARAM_STR);
                    $stmtBrNo->execute();
                    $branchAplNo = $stmtBrNo->fetch(PDO::FETCH_ASSOC);
                    if (!$branchAplNo) {
                        respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
                        $conn = $base->close(); exit;
                    }
                    $remoteNo = callFlimitBranchApi($branchAplNo, array_merge($_POST, array(
                        'encrypt'    => md5($tgl . '#' . $branchAplNo['key_apl']),
                        'id_apl'     => $branchAplNo['id_apl'],
                        'admin'      => $admin,
                        'admin_nama' => $adminNama,
                        'action'     => 'approve',
                        'no_faktur'  => $singleNo,
                    )), 'POST');
                    if (!$remoteNo['ok']) {
                        respondJson(array('status' => 'error', 'message' => isset($remoteNo['message']) ? $remoteNo['message'] : 'Gagal menghubungi cabang'), 502);
                        $conn = $base->close(); exit;
                    }
                    respondJson($remoteNo['json'], $remoteNo['http_code'] ?? 200);
                    $conn = $base->close(); exit;
                }
            }
        }

        $conn->beginTransaction();

        if (!empty($singleNo)) {
            // Resolve id_tfk from sumber|kode_tfk or plain id_tfk
            $maybeVal = $singleNo;
            if (strpos($singleNo, '|') !== false) {
                $parts    = explode('|', $singleNo, 2);
                $maybeVal = trim($parts[1]);
            }

            // Lookup in Cendo first, then PIM
            $single_id  = null;
            $sumberFound = 'Cendo';
            $qTF = $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur WHERE id_tfk=:nf OR kode_tfk=:nf LIMIT 1");
            $qTF->bindValue(':nf', $maybeVal, PDO::PARAM_STR);
            $qTF->execute();
            $rowTF = $qTF->fetch(PDO::FETCH_ASSOC);
            if ($rowTF) {
                $single_id   = $rowTF['id_tfk'];
                $kode_ref    = $rowTF['kode_tfk'] ?: $maybeVal;
                $sumberFound = 'Cendo';
            } else {
                $qTFP = $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur_pim WHERE id_tfk=:nf OR kode_tfk=:nf LIMIT 1");
                $qTFP->bindValue(':nf', $maybeVal, PDO::PARAM_STR);
                $qTFP->execute();
                $rowTFP = $qTFP->fetch(PDO::FETCH_ASSOC);
                if ($rowTFP) {
                    $single_id   = $rowTFP['id_tfk'];
                    $kode_ref    = $rowTFP['kode_tfk'] ?: $maybeVal;
                    $sumberFound = 'PIM';
                } else {
                    $qTFC = $conn->prepare("SELECT id_tfk, kode_tfk FROM transaksi_faktur_c WHERE id_tfk=:nf OR kode_tfk=:nf LIMIT 1");
                    $qTFC->bindValue(':nf', $maybeVal, PDO::PARAM_STR);
                    $qTFC->execute();
                    $rowTFC = $qTFC->fetch(PDO::FETCH_ASSOC);
                    if ($rowTFC) {
                        $single_id   = $rowTFC['id_tfk'];
                        $kode_ref    = $rowTFC['kode_tfk'] ?: $maybeVal;
                        $sumberFound = 'C';
                    }
                }
            }

            if (!$single_id) {
                $conn->rollBack();
                respondJson(array('status' => 'error', 'message' => 'Faktur tidak ditemukan'), 404);
                $conn = $base->close(); exit;
            }

            if ($approveType === 'orange') {
                // ── Approve ORANGE ────────────────────────────────────────────
                $qExistOrg = $conn->prepare("SELECT lo.id_orange, lo.kode_orange FROM `limit_orange` lo JOIN `limit_orange_detail` lod ON lod.id_orange = lo.id_orange WHERE lod.no_faktur = :nf LIMIT 1");
                $qExistOrg->bindValue(':nf', $single_id, PDO::PARAM_STR);
                $qExistOrg->execute();
                $existOrange = $qExistOrg->fetch(PDO::FETCH_ASSOC);

                if ($existOrange) {
                    $id_orange   = (int)$existOrange['id_orange'];
                    $kode_orange = $existOrange['kode_orange'];
                } else {
                    $kode_orange = ''; $isDup = true; $retryO = 0;
                    while ($isDup && $retryO < 5) {
                        $kode_orange = $data->basecode('ORG', 5, 'id_orange', '`limit_orange`');
                        if ($retryO > 0) $kode_orange .= rand(10, 99);
                        $chkO = $conn->prepare("SELECT COUNT(*) FROM `limit_orange` WHERE kode_orange=:kode");
                        $chkO->bindValue(':kode', $kode_orange, PDO::PARAM_STR);
                        $chkO->execute();
                        $isDup = ((int)$chkO->fetchColumn() > 0);
                        $retryO++;
                    }
                    $insHO = $conn->prepare("INSERT INTO `limit_orange` (kode_orange, id_tfk, sumber, status_orange, tgl_orange, created_at, created_by) VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl, :catat, :admin)");
                    $insHO->bindValue(':kode',   $kode_orange, PDO::PARAM_STR);
                    $insHO->bindValue(':id_tfk', $single_id,   PDO::PARAM_STR);
                    $insHO->bindValue(':sumber', $sumberFound, PDO::PARAM_STR);
                    $insHO->bindValue(':tgl',    date('Y-m-d'), PDO::PARAM_STR);
                    $insHO->bindValue(':catat',  $catat,       PDO::PARAM_STR);
                    $insHO->bindValue(':admin',  $admin,       PDO::PARAM_STR);
                    $insHO->execute();
                    $id_orange = (int)$conn->lastInsertId();
                }

                $qDChkO = $conn->prepare("SELECT COUNT(*) FROM `limit_orange_detail` WHERE no_faktur=:nf");
                $qDChkO->bindValue(':nf', $single_id, PDO::PARAM_STR);
                $qDChkO->execute();
                if ((int)$qDChkO->fetchColumn() === 0) {
                    $insDO = $conn->prepare("INSERT INTO `limit_orange_detail` (id_orange, no_faktur, ket, status, created_at, created_by) VALUES(:id_orange, :nf, 'Approve status orange', 'Approved', :catat, :admin)");
                    $insDO->bindValue(':id_orange', $id_orange,  PDO::PARAM_INT);
                    $insDO->bindValue(':nf',        $single_id,  PDO::PARAM_STR);
                    $insDO->bindValue(':catat',     $catat,      PDO::PARAM_STR);
                    $insDO->bindValue(':admin',     $admin,      PDO::PARAM_STR);
                    $insDO->execute();
                }

                updateStatusLimitApi($conn, $single_id, 'approve');

                $ri = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Finance Limit (Orange)', 'Approve', 'Approve Orange', :catat, :admin)");
                $ri->bindValue(':kode',  $kode_orange, PDO::PARAM_STR);
                $ri->bindValue(':catat', $catat,       PDO::PARAM_STR);
                $ri->bindValue(':admin', $admin,       PDO::PARAM_STR);
                $ri->execute();

                $kode_ref = $kode_orange;
                $updated  = true;

            } elseif ($approveType === 'kuning') {
                // ── Approve KUNING ────────────────────────────────────────────
                $qExistKun = $conn->prepare("SELECT lk.id_kuning, lk.kode_kuning FROM `limit_kuning` lk JOIN `limit_kuning_detail` lkd ON lkd.id_kuning = lk.id_kuning WHERE lkd.no_faktur = :nf LIMIT 1");
                $qExistKun->bindValue(':nf', $single_id, PDO::PARAM_STR);
                $qExistKun->execute();
                $existKuning = $qExistKun->fetch(PDO::FETCH_ASSOC);

                if ($existKuning) {
                    $id_kuning   = (int)$existKuning['id_kuning'];
                    $kode_kuning = $existKuning['kode_kuning'];
                } else {
                    $kode_kuning = ''; $isDup = true; $retryK = 0;
                    while ($isDup && $retryK < 5) {
                        $kode_kuning = $data->basecode('KNG', 5, 'id_kuning', '`limit_kuning`');
                        if ($retryK > 0) $kode_kuning .= rand(10, 99);
                        $chkK = $conn->prepare("SELECT COUNT(*) FROM `limit_kuning` WHERE kode_kuning=:kode");
                        $chkK->bindValue(':kode', $kode_kuning, PDO::PARAM_STR);
                        $chkK->execute();
                        $isDup = ((int)$chkK->fetchColumn() > 0);
                        $retryK++;
                    }
                    $insHK = $conn->prepare("INSERT INTO `limit_kuning` (kode_kuning, id_tfk, sumber, status_kuning, tgl_kuning, created_at, created_by) VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl, :catat, :admin)");
                    $insHK->bindValue(':kode',   $kode_kuning, PDO::PARAM_STR);
                    $insHK->bindValue(':id_tfk', $single_id,   PDO::PARAM_STR);
                    $insHK->bindValue(':sumber', $sumberFound, PDO::PARAM_STR);
                    $insHK->bindValue(':tgl',    date('Y-m-d'), PDO::PARAM_STR);
                    $insHK->bindValue(':catat',  $catat,       PDO::PARAM_STR);
                    $insHK->bindValue(':admin',  $admin,       PDO::PARAM_STR);
                    $insHK->execute();
                    $id_kuning = (int)$conn->lastInsertId();
                }

                $qDChkK = $conn->prepare("SELECT COUNT(*) FROM `limit_kuning_detail` WHERE no_faktur=:nf");
                $qDChkK->bindValue(':nf', $single_id, PDO::PARAM_STR);
                $qDChkK->execute();
                if ((int)$qDChkK->fetchColumn() === 0) {
                    $insDK = $conn->prepare("INSERT INTO `limit_kuning_detail` (id_kuning, no_faktur, ket, status, created_at, created_by) VALUES(:id_kuning, :nf, 'Approve status kuning', 'Approved', :catat, :admin)");
                    $insDK->bindValue(':id_kuning', $id_kuning,  PDO::PARAM_INT);
                    $insDK->bindValue(':nf',        $single_id,  PDO::PARAM_STR);
                    $insDK->bindValue(':catat',     $catat,      PDO::PARAM_STR);
                    $insDK->bindValue(':admin',     $admin,      PDO::PARAM_STR);
                    $insDK->execute();
                }

                updateStatusLimitApi($conn, $single_id, 'approve');

                $ri = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Finance Limit (Kuning)', 'Approve', 'Approve Kuning', :catat, :admin)");
                $ri->bindValue(':kode',  $kode_kuning, PDO::PARAM_STR);
                $ri->bindValue(':catat', $catat,       PDO::PARAM_STR);
                $ri->bindValue(':admin', $admin,       PDO::PARAM_STR);
                $ri->execute();

                $kode_ref = $kode_kuning;
                $updated  = true;

            } elseif ($approveType === 'merah') {
                // ── Approve MERAH ─────────────────────────────────────────────
                $qExist = $conn->prepare("SELECT lm.id_merah, lm.kode_merah FROM `limit_merah` lm JOIN `limit_merah_detail` lmd ON lmd.id_merah = lm.id_merah WHERE lmd.no_faktur = :nf LIMIT 1");
                $qExist->bindValue(':nf', $single_id, PDO::PARAM_STR);
                $qExist->execute();
                $existMerah = $qExist->fetch(PDO::FETCH_ASSOC);

                if ($existMerah) {
                    $id_merah   = (int)$existMerah['id_merah'];
                    $kode_merah = $existMerah['kode_merah'];
                } else {
                    $kode_merah = ''; $isDup = true; $retryM = 0;
                    while ($isDup && $retryM < 5) {
                        $kode_merah = $data->basecode('MRH', 5, 'id_merah', '`limit_merah`');
                        if ($retryM > 0) $kode_merah .= rand(10, 99);
                        $chkM = $conn->prepare("SELECT COUNT(*) FROM `limit_merah` WHERE kode_merah=:kode");
                        $chkM->bindValue(':kode', $kode_merah, PDO::PARAM_STR);
                        $chkM->execute();
                        $isDup = ((int)$chkM->fetchColumn() > 0);
                        $retryM++;
                    }
                    $insHM = $conn->prepare("INSERT INTO `limit_merah` (kode_merah, id_tfk, sumber, status_merah, tgl_merah, created_at, created_by) VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl, :catat, :admin)");
                    $insHM->bindValue(':kode',   $kode_merah,  PDO::PARAM_STR);
                    $insHM->bindValue(':id_tfk', $single_id,   PDO::PARAM_STR);
                    $insHM->bindValue(':sumber', $sumberFound, PDO::PARAM_STR);
                    $insHM->bindValue(':tgl',    date('Y-m-d'), PDO::PARAM_STR);
                    $insHM->bindValue(':catat',  $catat,       PDO::PARAM_STR);
                    $insHM->bindValue(':admin',  $admin,       PDO::PARAM_STR);
                    $insHM->execute();
                    $id_merah = (int)$conn->lastInsertId();
                }

                $qDChk = $conn->prepare("SELECT COUNT(*) FROM `limit_merah_detail` WHERE no_faktur=:nf");
                $qDChk->bindValue(':nf', $single_id, PDO::PARAM_STR);
                $qDChk->execute();
                if ((int)$qDChk->fetchColumn() === 0) {
                    $insDM = $conn->prepare("INSERT INTO `limit_merah_detail` (id_merah, no_faktur, ket, status, created_at, created_by) VALUES(:id_merah, :nf, 'Approve status merah', 'Approved', :catat, :admin)");
                    $insDM->bindValue(':id_merah', $id_merah,  PDO::PARAM_INT);
                    $insDM->bindValue(':nf',       $single_id, PDO::PARAM_STR);
                    $insDM->bindValue(':catat',    $catat,     PDO::PARAM_STR);
                    $insDM->bindValue(':admin',    $admin,     PDO::PARAM_STR);
                    $insDM->execute();
                }

                updateStatusLimitApi($conn, $single_id, 'approve');

                $ri = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Finance Limit (Merah)', 'Approve', 'Approve Merah', :catat, :admin)");
                $ri->bindValue(':kode',  $kode_merah, PDO::PARAM_STR);
                $ri->bindValue(':catat', $catat,      PDO::PARAM_STR);
                $ri->bindValue(':admin', $admin,      PDO::PARAM_STR);
                $ri->execute();

                $kode_ref = $kode_merah;
                $updated  = true;

            } else {
                // ── Approve LIMIT ────────────────────────────────────────────
                updateStatusLimitApi($conn, $single_id, 'approve');
                updateOutletLimitApi($conn, $single_id);

                $qHdr = $conn->prepare("SELECT id_limit, kode_limit, status_limit FROM `limit` WHERE id_tfk=:id LIMIT 1");
                $qHdr->bindValue(':id', $single_id, PDO::PARAM_STR);
                $qHdr->execute();
                $hdr = $qHdr->fetch(PDO::FETCH_ASSOC);

                if ($hdr) {
                    $id_limit_use = (int)$hdr['id_limit'];
                    $kode_ref     = $hdr['kode_limit'] ?: $kode_ref;
                    if (strtolower(trim((string)$hdr['status_limit'])) !== 'approve') {
                        $uLmt = $conn->prepare("UPDATE `limit` SET status_limit='Approved', updated_at=:catat, updated_by=:admin WHERE id_limit=:id");
                        $uLmt->bindValue(':catat', $catat,        PDO::PARAM_STR);
                        $uLmt->bindValue(':admin', $admin,        PDO::PARAM_STR);
                        $uLmt->bindValue(':id',    $id_limit_use, PDO::PARAM_INT);
                        $uLmt->execute();
                    }
                } else {
                    // Create new header
                    $newKode = ''; $isDup = true; $retryL = 0;
                    while ($isDup && $retryL < 5) {
                        $newKode = $data->basecode('LMT', 5, 'id_limit', '`limit`');
                        if ($retryL > 0) $newKode .= rand(10, 99);
                        $chkL = $conn->prepare("SELECT COUNT(*) FROM `limit` WHERE kode_limit=:kode");
                        $chkL->bindValue(':kode', $newKode, PDO::PARAM_STR);
                        $chkL->execute();
                        $isDup = ((int)$chkL->fetchColumn() > 0);
                        $retryL++;
                    }
                    $insHL = $conn->prepare("INSERT INTO `limit` (kode_limit, id_tfk, sumber, status_limit, tgl_limit, created_at, created_by) VALUES(:kode, :id_tfk, :sumber, 'Approved', :tgl, :catat, :admin)");
                    $insHL->bindValue(':kode',   $newKode,     PDO::PARAM_STR);
                    $insHL->bindValue(':id_tfk', $single_id,   PDO::PARAM_STR);
                    $insHL->bindValue(':sumber', $sumberFound, PDO::PARAM_STR);
                    $insHL->bindValue(':tgl',    date('Y-m-d'), PDO::PARAM_STR);
                    $insHL->bindValue(':catat',  $catat,       PDO::PARAM_STR);
                    $insHL->bindValue(':admin',  $admin,       PDO::PARAM_STR);
                    $insHL->execute();
                    $id_limit_use = (int)$conn->lastInsertId();
                    $kode_ref     = $newKode;
                }

                $qDChk2 = $conn->prepare("SELECT COUNT(*) FROM `limit_detail` WHERE id_limit=:id AND no_faktur=:nf");
                $qDChk2->bindValue(':id', $id_limit_use, PDO::PARAM_INT);
                $qDChk2->bindValue(':nf', $single_id,    PDO::PARAM_STR);
                $qDChk2->execute();
                if ((int)$qDChk2->fetchColumn() === 0) {
                    $insDL = $conn->prepare("INSERT INTO `limit_detail` (id_limit, no_faktur, ket, status, created_at, created_by) VALUES(:id_limit, :nf, 'Approve status limit', 'Approved', :catat, :admin)");
                    $insDL->bindValue(':id_limit', $id_limit_use, PDO::PARAM_INT);
                    $insDL->bindValue(':nf',       $single_id,    PDO::PARAM_STR);
                    $insDL->bindValue(':catat',    $catat,        PDO::PARAM_STR);
                    $insDL->bindValue(':admin',    $admin,        PDO::PARAM_STR);
                    $insDL->execute();
                }

                $updated = true;
            }
        }

        if (!$updated) {
            $conn->rollBack();
            respondJson(array('status' => 'error', 'message' => 'Tidak ada data yang bisa di-approve'), 400);
            $conn = $base->close(); exit;
        }

        $ri = $conn->prepare("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES(:kode, 'Finance Limit', 'Approve', 'Approve Limit', :catat, :admin)");
        $ri->bindValue(':kode',  $kode_ref, PDO::PARAM_STR);
        $ri->bindValue(':catat', $catat,    PDO::PARAM_STR);
        $ri->bindValue(':admin', $admin,    PDO::PARAM_STR);
        $ri->execute();

        $conn->commit();
        respondJson(array('status' => 'success', 'message' => 'Limit berhasil di-approve', 'kode' => $kode_ref));
        $conn = $base->close(); exit;
    }

    if ($action === 'detail') {
        if ($idAplReq !== '' && strtolower($idAplReq) !== 'all' && (string)$idAplReq !== (string)$id_apl) {
            $stmtBr = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
            $stmtBr->bindValue(':id_apl', $idAplReq, PDO::PARAM_STR);
            $stmtBr->execute();
            $branchApl = $stmtBr->fetch(PDO::FETCH_ASSOC);
            if (!$branchApl) {
                respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
                $conn = $base->close(); exit;
            }

            $detailKey = trim($idTfk !== '' ? $idTfk : $noFaktur);
            $remote = callFlimitBranchApi($branchApl, array(
                'encrypt' => md5($tgl . '#' . $branchApl['key_apl']),
                'id_apl' => $branchApl['id_apl'],
                'action' => 'detail',
                'id_tfk' => $detailKey,
                'no_faktur' => $detailKey,
            ), 'GET');

            if (!$remote['ok']) {
                respondJson(array('status' => 'error', 'message' => isset($remote['message']) ? $remote['message'] : 'Gagal menghubungi cabang'), 502);
                $conn = $base->close(); exit;
            }

            respondJson($remote['json'], $remote['http_code'] ?? 200);
            $conn = $base->close(); exit;
        }

        $detailKey = trim($idTfk !== '' ? $idTfk : $noFaktur);
        if ($detailKey === '') {
            respondJson(array('status' => 'error', 'message' => 'Parameter faktur tidak ditemukan'), 422);
            $conn = $base->close(); exit;
        }

        $detail = fetchLocalFlimitDetail($conn, $detailKey);
        if (empty($detail)) {
            respondJson(array('status' => 'error', 'message' => 'Data faktur tidak ditemukan'), 404);
            $conn = $base->close(); exit;
        }

        $history = fetchLocalFlimitHistory($conn, $detail['id_tfk'] ?? '');
        $detail['id_apl'] = $id_apl;
        $detail['nama_apl'] = $nama_apl;

        respondJson(array(
            'status' => 'success',
            'id_apl' => $id_apl,
            'nama_apl' => $nama_apl,
            'data' => $detail,
            'history' => $history,
        ));
        $conn = $base->close(); exit;
    }

    // ── LIST (default GET) ───────────────────────────────────────────────────
    $pecah   = explode('_', $cari);
    $cariStr = $data->cekcari($pecah[0], '-', ' ');
    $tgl1    = empty($pecah[1]) ? '' : $pecah[1];
    $tgl2    = empty($pecah[2]) ? '' : $pecah[2];

    $requestedApl = trim($idAplReq);

    // ── Forward to a specific single branch ──────────────────────────────────
    if ($requestedApl !== '' && strtolower($requestedApl) !== 'all' && $requestedApl !== (string)$id_apl) {
        $stmtB = $conn->prepare('SELECT id_apl, nama_apl, base_url_apl, key_apl FROM aplikasi WHERE id_apl = :id_apl AND active_apl = 1 LIMIT 1');
        $stmtB->bindValue(':id_apl', $requestedApl, PDO::PARAM_STR);
        $stmtB->execute();
        $branch = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$branch) {
            respondJson(array('status' => 'error', 'message' => 'Cabang tidak ditemukan'), 404);
            $conn = $base->close(); exit;
        }

        $remote = callFlimitBranchApi($branch, array(
            'encrypt'  => md5($tgl . '#' . $branch['key_apl']),
            'id_apl'   => $branch['id_apl'],
            'caridata' => $cari,
            'halaman'  => $page,
            'maximal'  => $maxi,
                'paginate' => $paginate,
				'statusfilter' => $statusFilterRaw,
        ), 'GET');

        if (!$remote['ok']) {
            respondJson(array('status' => 'error', 'message' => isset($remote['message']) ? $remote['message'] : 'Gagal menghubungi cabang'), 502);
            $conn = $base->close(); exit;
        }

        respondJson($remote['json'], $remote['http_code'] ?? 200);
        $conn = $base->close(); exit;
    }

    // ── Aggregate ALL branches ────────────────────────────────────────────────
    if (strtolower($requestedApl) === 'all') {
        $stmtApls = $conn->query('SELECT id_apl, nama_apl, self_apl, base_url_apl, key_apl FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl ASC');
        $aplList  = $stmtApls ? $stmtApls->fetchAll(PDO::FETCH_ASSOC) : array();

        $mergedRows = array();
        $errors     = array();

        foreach ($aplList as $aplInfo) {
            if ((string)$aplInfo['id_apl'] === (string)$id_apl || (int)($aplInfo['self_apl'] ?? 0) === 1) {
                // local
                $localResult = fetchLocalFlimit($conn, $cariStr, $tgl1, $tgl2, $statusFilterRaw, null, null);
                foreach ($localResult['data'] as $row) {
                    $row['id_apl']   = $id_apl;
                    $row['nama_apl'] = $nama_apl;
                    $mergedRows[]    = $row;
                }
                continue;
            }

            $remote = callFlimitBranchApi($aplInfo, array(
                'encrypt'  => md5($tgl . '#' . $aplInfo['key_apl']),
                'id_apl'   => $aplInfo['id_apl'],
                'caridata' => $cari,
                'halaman'  => 1,
                'maximal'  => 9999,
                'paginate' => 0,
				'statusfilter' => $statusFilterRaw,
            ), 'GET');

            if (!$remote['ok'] || ($remote['json']['status'] ?? '') !== 'success') {
                $errors[] = array(
                    'id_apl'   => $aplInfo['id_apl'],
                    'nama_apl' => $aplInfo['nama_apl'],
                    'message'  => !empty($remote['json']['message']) ? $remote['json']['message'] : (isset($remote['message']) ? $remote['message'] : 'Gagal'),
                );
                continue;
            }

            foreach (($remote['json']['data'] ?? array()) as $row) {
                $row['id_apl']   = $aplInfo['id_apl'];
                $row['nama_apl'] = $aplInfo['nama_apl'];
                $mergedRows[]    = $row;
            }
        }

        // Sort: limit > merah > orange > kuning > others, then by created_at DESC
        usort($mergedRows, function($a, $b) {
            $order = array('limit' => 0, 'merah' => 1, 'orange' => 2, 'kuning' => 3);
            $aStatus = strtolower(trim((string)($a['status_limit'] ?? '')));
            $bStatus = strtolower(trim((string)($b['status_limit'] ?? '')));
            $aOrd = isset($order[$aStatus]) ? $order[$aStatus] : 4;
            $bOrd = isset($order[$bStatus]) ? $order[$bStatus] : 4;
            if ($aOrd !== $bOrd) return $aOrd - $bOrd;
            return strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
        });

        $totalAll = count($mergedRows);
        $rows     = ($paginate === 0) ? $mergedRows : array_slice($mergedRows, $mulai, $maxi);

        respondJson(array(
            'status'   => 'success',
            'id_apl'   => 'all',
            'nama_apl' => 'Semua Cabang',
            'total'    => $totalAll,
            'halaman'  => $page,
            'maximal'  => $maxi,
            'data'     => array_values($rows),
            'errors'   => $errors,
        ));
        $conn = $base->close(); exit;
    }

    // ── Local only ────────────────────────────────────────────────────────────
    $offsetVal = ($paginate === 1) ? $mulai : null;
    $limitVal  = ($paginate === 1) ? $maxi  : null;

    $result = fetchLocalFlimit($conn, $cariStr, $tgl1, $tgl2, $statusFilterRaw, $offsetVal, $limitVal);

    respondJson(array(
        'status'   => 'success',
        'id_apl'   => $id_apl,
        'nama_apl' => $nama_apl,
        'total'    => $result['total'],
        'halaman'  => $page,
        'maximal'  => $maxi,
        'data'     => $result['data'],
    ));

} catch (PDOException $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    logFlimitApiError('pdo_exception', array('message' => $e->getMessage()));
    respondJson(array('status' => 'error', 'message' => 'Database error: ' . $e->getMessage()), 500);
}

$conn = $base->close();
?>
