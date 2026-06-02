<?php
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
header("Content-Disposition: attachment; filename=ApproveLimit.xls");

require_once('../../../config/connection/connection.php');
require_once('../../../config/connection/security.php');
require_once('../../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

$admin = $secu->injection($_COOKIE['adminkuy'] ?? '');
$kunci = $secu->injection($_COOKIE['kuncikuy'] ?? '');

if ($secu->validadmin($admin, $kunci) == false) {
    header('location:' . $data->sistem('url_sis') . '/signout');
    exit;
}

$conn = $base->open();

// key format mengikuti halaman flimit: "cari_tgl1_tgl2"
$key   = $secu->injection($_GET['key'] ?? '');
$pecah = explode('_', $key);

$cariRaw = $pecah[0] ?? '';
$cari    = $data->cekcari($cariRaw, '-', ' ');

$tgl1 = $pecah[1] ?? '';
$tgl2 = $pecah[2] ?? '';

// WHERE dinamis (SAMA PERSIS dengan json/flimit/flimit.php)
$where = "(
        A.created_at LIKE :cari
     OR A.kode_tfk   LIKE :cari
     OR A.id_tfk     LIKE :cari
     OR B.nama_out   LIKE :cari
)";
$params = [':cari' => '%' . $cari . '%'];

if (!empty($tgl1)) {
    $where .= " AND A.created_at >= :tgl1";
    $params[':tgl1'] = $tgl1;
}
if (!empty($tgl2)) {
    $where .= " AND A.created_at <= :tgl2";
    $params[':tgl2'] = $tgl2;
}

$statusFilterSql = "LOWER(TRIM(COALESCE(A.status_limit,''))) IN ('limit','approve','kuning')";
$approveHistoryFilterSql = "(
    LOWER(TRIM(COALESCE(A.status_limit,''))) <> 'approve'
    OR EXISTS (
        SELECT 1
        FROM `limit` AS LF
        WHERE LF.id_tfk = A.id_tfk
    )
    OR EXISTS (
        SELECT 1
        FROM limit_kuning AS LK
        WHERE LK.id_tfk = A.id_tfk
    )
)";

/**
 * Ambil LIMIT TERBARU per faktur (berdasarkan id_limit terbesar).
 * SAMA PERSIS dengan json/flimit/flimit.php
 */
$limitLatestSql = "
    SELECT
        ld.no_faktur,
        SUBSTRING_INDEX(GROUP_CONCAT(l.id_limit    ORDER BY l.id_limit DESC), ',', 1) AS id_limit_last,
        SUBSTRING_INDEX(GROUP_CONCAT(l.tgl_limit   ORDER BY l.id_limit DESC), ',', 1) AS tgl_limit,
        SUBSTRING_INDEX(GROUP_CONCAT(l.created_by  ORDER BY l.id_limit DESC), ',', 1) AS created_by_limit
    FROM limit_detail ld
    INNER JOIN `limit` l ON l.id_limit = ld.id_limit
    GROUP BY ld.no_faktur
";

/**
 * Ambil LIMIT_KUNING TERBARU per faktur (jika ada).
 * Sinkron dengan json/flimit/flimit.php
 */
$limitKuningSql = "
    SELECT
        lmd.no_faktur,
        SUBSTRING_INDEX(GROUP_CONCAT(lm.id_kuning ORDER BY lm.id_kuning DESC), ',', 1) AS id_kuning_last,
        SUBSTRING_INDEX(GROUP_CONCAT(lm.tgl_kuning ORDER BY lm.id_kuning DESC), ',', 1) AS tgl_kuning,
        SUBSTRING_INDEX(GROUP_CONCAT(lm.created_by ORDER BY lm.id_kuning DESC), ',', 1) AS created_by_kuning
    FROM limit_kuning_detail lmd
    INNER JOIN limit_kuning lm ON lm.id_kuning = lmd.id_kuning
    GROUP BY lmd.no_faktur
";

// QUERY MASTER - sinkron dengan logic json/flimit/flimit.php
$qMaster = "
    SELECT
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
        X.jenis_approve
    FROM (
        SELECT
            CONVERT(A.id_tfk USING utf8mb4) AS id_tfk,
            CONVERT(A.kode_tfk USING utf8mb4) AS kode_tfk,
            A.created_at,
            A.tgl_tfk,
            A.total_tfk,
            CONVERT(A.status_limit USING utf8mb4) AS status_limit,
            CONVERT(B.nama_out USING utf8mb4) AS nama_out,
            CONVERT(B.status_pembayaran USING utf8mb4) AS status_pembayaran,
            CAST(REPLACE(REPLACE(COALESCE(B.`platform`, '0'), '.', ''), ',', '') AS UNSIGNED) AS limit_outlet,
            CONVERT('Cendo' USING utf8mb4) AS sumber,
            CONVERT(CASE WHEN M.tgl_kuning IS NOT NULL
                     THEN COALESCE(ADM_M.nama_adm, M.created_by_kuning)
                     ELSE COALESCE(AD1.nama_adm, L.created_by_limit)
                END USING utf8mb4) AS approval_nama,
            COALESCE(M.tgl_kuning, L.tgl_limit) AS tgl_approve,
            CONVERT(CASE WHEN M.tgl_kuning IS NOT NULL THEN 'kuning' ELSE 'limit' END USING utf8mb4) AS jenis_approve
        FROM transaksi_faktur AS A
        LEFT JOIN outlet AS B ON A.id_out = B.id_out
        LEFT JOIN ($limitLatestSql) AS L ON L.no_faktur = A.id_tfk
        LEFT JOIN adminz AS AD1 ON AD1.id_adm = L.created_by_limit
        LEFT JOIN ($limitKuningSql) AS M ON M.no_faktur = A.id_tfk
        LEFT JOIN adminz AS ADM_M ON ADM_M.id_adm = M.created_by_kuning
                WHERE $where
                    AND $statusFilterSql
                    AND $approveHistoryFilterSql

        UNION ALL

        SELECT
            CONVERT(A.id_tfk USING utf8mb4) AS id_tfk,
            CONVERT(A.kode_tfk USING utf8mb4) AS kode_tfk,
            A.created_at,
            A.tgl_tfk,
            A.total_tfk,
            CONVERT(A.status_limit USING utf8mb4) AS status_limit,
            CONVERT(B.nama_out USING utf8mb4) AS nama_out,
            CONVERT(B.status_pembayaran USING utf8mb4) AS status_pembayaran,
            CAST(REPLACE(REPLACE(COALESCE(B.`platform`, '0'), '.', ''), ',', '') AS UNSIGNED) AS limit_outlet,
            CONVERT('PIM' USING utf8mb4) AS sumber,
            CONVERT(CASE WHEN M2.tgl_kuning IS NOT NULL
                     THEN COALESCE(ADM_M2.nama_adm, M2.created_by_kuning)
                     ELSE COALESCE(AD2.nama_adm, L2.created_by_limit)
                END USING utf8mb4) AS approval_nama,
            COALESCE(M2.tgl_kuning, L2.tgl_limit) AS tgl_approve,
            CONVERT(CASE WHEN M2.tgl_kuning IS NOT NULL THEN 'kuning' ELSE 'limit' END USING utf8mb4) AS jenis_approve
        FROM transaksi_faktur_pim AS A
        LEFT JOIN outlet AS B ON A.id_out = B.id_out
        LEFT JOIN ($limitLatestSql) AS L2 ON L2.no_faktur = A.id_tfk
        LEFT JOIN adminz AS AD2 ON AD2.id_adm = L2.created_by_limit
        LEFT JOIN ($limitKuningSql) AS M2 ON M2.no_faktur = A.id_tfk
        LEFT JOIN adminz AS ADM_M2 ON ADM_M2.id_adm = M2.created_by_kuning
                WHERE $where
                    AND $statusFilterSql
                    AND $approveHistoryFilterSql
    ) AS X
    ORDER BY
        CASE
            WHEN LOWER(TRIM(COALESCE(X.status_limit, ''))) = 'limit' THEN 0
            WHEN LOWER(TRIM(COALESCE(X.status_limit, ''))) = 'kuning' THEN 1
            ELSE 2
        END ASC,
        X.created_at DESC
";

$stmt = $conn->prepare($qMaster);
$stmt->execute($params);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Approve Limit</title>
</head>
<body>
    <table>
        <tr><th colspan="10">Laporan Approve Limit</th></tr>
        <tr><td colspan="10"></td></tr>
        <tr>
            <td colspan="9"><?php echo htmlspecialchars($key, ENT_QUOTES); ?></td>
        </tr>
        <tr><td colspan="10"></td></tr>
    </table>

    <table border="1">
        <thead>
            <tr>
                <th><center>NO</center></th>
                <th>Nomor Faktur</th>
                <th>Sumber</th>
                <th>Nama Outlet</th>
                <th>Tgl Faktur</th>
                <th>Limit Outlet</th>
                <th>Nominal</th>
                <th>Tgl Approve</th>
                <th>Status Limit</th>
                <th>Approval</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $status = trim((string)($row['status_limit'] ?? ''));
                $total  = (float)($row['total_tfk'] ?? 0);
                $limitOutlet = (float)($row['limit_outlet'] ?? 0);
                $tglApprove  = (string)($row['tgl_approve'] ?? '');
                $approvalNama = trim((string)($row['approval_nama'] ?? ''));
                $statusPembayaran = strtolower(trim((string)($row['status_pembayaran'] ?? '')));
                $statusLower = strtolower($status);
                $jenisApprove = strtolower(trim((string)($row['jenis_approve'] ?? 'limit')));

                $displayStatus = 'approve';
                if ($statusLower === 'kuning' || ($statusLower === 'approve' && $jenisApprove === 'kuning')) {
                    $displayStatus = 'kuning';
                } elseif ($statusLower === 'limit' || ($statusLower === 'approve' && $jenisApprove === 'limit')) {
                    $displayStatus = 'limit';
                }
            ?>
            <tr>
                <td><center><?php echo $no; ?></center></td>
                <td><?php echo htmlspecialchars((string)$row['kode_tfk'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)$row['sumber'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)$row['nama_out'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars((string)$row['tgl_tfk'], ENT_QUOTES); ?></td>
                <td style="text-align:right;"><?php
                    if ($displayStatus === 'kuning') {
                        echo 'Status Kuning';
                    } else {
                        echo 'Rp ' . number_format($limitOutlet, 0, ',', '.');
                    }
                ?></td>
                <td style="text-align:right;">Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                <td style="text-align:center;"><?php echo htmlspecialchars($tglApprove !== '' ? $tglApprove : '-', ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($displayStatus !== '' ? ucfirst($displayStatus) : '-', ENT_QUOTES); ?></td>
                <td style="text-align:center;"><?php echo htmlspecialchars($approvalNama !== '' ? $approvalNama : '-', ENT_QUOTES); ?></td>
            </tr>
            <?php
                $no++;
            }
            ?>
        </tbody>
    </table>
</body>
</html>
<?php
$base->close();
?>
