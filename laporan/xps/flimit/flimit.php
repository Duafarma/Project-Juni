<?php
require_once('../../../config/connection/connection.php');
require_once('../../../config/connection/security.php');
require_once('../../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

$conn = $base->open();

$id = $secu->injection($_GET['id'] ?? '');
$sumber = $secu->injection($_GET['sumber'] ?? '');

if (empty($id)) {
    echo "<h3>Parameter id kosong</h3>";
    exit;
}

// Ambil data faktur (prioritas transaksi_faktur, fallback transaksi_faktur_pim)
$q = $conn->prepare("
        SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, A.total_tfk, A.status_limit,
            B.id_out, B.nama_out, B.status_pembayaran, COALESCE(B.`limit`, B.platform) AS limit_outlet
    FROM transaksi_faktur A
    LEFT JOIN outlet B ON A.id_out = B.id_out
    WHERE A.id_tfk = :id OR A.kode_tfk = :id
    LIMIT 1
");
$q->bindValue(':id', $id, PDO::PARAM_STR);
$q->execute();
$row = $q->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $q2 = $conn->prepare("
         SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, A.total_tfk, A.status_limit,
             B.id_out, B.nama_out, B.status_pembayaran, COALESCE(B.`limit`, B.platform) AS limit_outlet
        FROM transaksi_faktur_pim A
        LEFT JOIN outlet B ON A.id_out = B.id_out
        WHERE A.id_tfk = :id OR A.kode_tfk = :id
        LIMIT 1
    ");
    $q2->bindValue(':id', $id, PDO::PARAM_STR);
    $q2->execute();
    $row = $q2->fetch(PDO::FETCH_ASSOC);
}

if (!$row) {
    echo "<h3>Data faktur tidak ditemukan</h3>";
    exit;
}

// Ambil riwayat limit / limit_detail dan limit_merah jika ada
$hist = [];
$h = $conn->prepare("
    SELECT ld.*, l.kode_limit, l.tgl_limit, l.created_at AS header_created_at, l.created_by AS header_created_by,
           COALESCE(AD.nama_adm, ld.created_by) AS created_by_name
    FROM limit_detail ld
    LEFT JOIN `limit` l ON l.id_limit = ld.id_limit
    LEFT JOIN adminz AD ON AD.id_adm = ld.created_by
    WHERE ld.no_faktur = :idf
    ORDER BY ld.created_at DESC
");
$h->bindValue(':idf', $row['id_tfk'], PDO::PARAM_STR);
$h->execute();
$hist['limit_detail'] = $h->fetchAll(PDO::FETCH_ASSOC);

$hm = $conn->prepare("
    SELECT lmd.*, lm.kode_kuning, lm.tgl_kuning, lm.created_at AS header_created_at, lm.created_by AS header_created_by,
           COALESCE(ADM.nama_adm, lmd.created_by) AS created_by_name
    FROM limit_kuning_detail lmd
    LEFT JOIN limit_kuning lm ON lm.id_kuning = lmd.id_kuning
    LEFT JOIN adminz ADM ON ADM.id_adm = lmd.created_by
    WHERE lmd.no_faktur = :idf
    ORDER BY lmd.created_at DESC
");
$hm->bindValue(':idf', $row['id_tfk'], PDO::PARAM_STR);
$hm->execute();
$hist['limit_kuning'] = $hm->fetchAll(PDO::FETCH_ASSOC);

// Tampilkan HTML print-friendly (browser -> Save as PDF/XPS)
?><!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Detail Faktur - <?php echo htmlspecialchars($row['kode_tfk']); ?></title>
<style>
    body{font-family:Arial,Helvetica,sans-serif;color:#222}
    .header{margin-bottom:20px;}
    .header h2{margin:0}
    table{border-collapse:collapse;width:100%;margin-bottom:12px}
    table th, table td{border:1px solid #ccc;padding:8px;text-align:left;font-size:13px}
    .no-border td{border:none;padding:4px}
    .right{text-align:right}
    .center{text-align:center}
    @media print{
        .no-print{display:none}
    }
    .page-break{page-break-after:always}
</style>
</head>
<body>
<div>
    <div style="float:left; font-size:60px;">
        <img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" />
    </div>
    <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
    <div align="right" style="font-size:10px;"><?php echo($data->sistem('alamat_sis')); ?></div>
    <div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
    <div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
    <div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
    <div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
    <div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
    <div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
</div>
<hr class="garis" />
<div style="text-align:center; font-weight:bold; margin:10px 0px 10px 0px;">Detail Faktur</div>
<div style="text-align:center; font-weight:bold;">NO. Faktur : <?php echo htmlspecialchars($row['kode_tfk']); ?></div>
<div style="text-align:center; font-weight:bold; margin-bottom:20px;">Tanggal : <?php echo htmlspecialchars($row['tgl_tfk']); ?></div>

<div class="no-print" style="margin-bottom:10px; text-align:right;">
    <button onclick="window.print()" style="padding:8px 12px">Print / Save as PDF</button>
</div>

<table>
    <tr><th>Nomor Faktur</th><td><?php echo htmlspecialchars($row['kode_tfk']); ?></td></tr>
    <tr><th>Sumber</th><td><?php echo htmlspecialchars($sumber ?: 'Cendo'); ?></td></tr>
    <tr><th>Nama Outlet</th><td><?php echo htmlspecialchars($row['nama_out']); ?></td></tr>
    <tr><th>Tgl Faktur</th><td><?php echo htmlspecialchars($row['tgl_tfk']); ?></td></tr>
    <tr><th>Status Limit</th><td><?php echo htmlspecialchars($row['status_limit']); ?></td></tr>
    <?php if (strtolower(trim((string)($row['status_pembayaran'] ?? ''))) !== 'kuning'): ?>
        <tr><th>Limit Outlet</th><td><?php echo 'Rp ' . number_format((float)($row['limit_outlet'] ?? 0),0,',','.'); ?></td></tr>
    <?php endif; ?>
    <tr><th>Nominal</th><td>Rp <?php echo number_format((float)$row['total_tfk'],0,',','.'); ?></td></tr>
</table>

<?php
$statusLimit      = strtolower(trim((string)($row['status_limit'] ?? '')));
$statusPembayaran = strtolower(trim((string)($row['status_pembayaran'] ?? '')));
$hasKuning        = !empty($hist['limit_kuning']);
$hasLimit         = !empty($hist['limit_detail']);

/**
 * Jika status_limit = approve, tentukan jenis berdasarkan tabel yang benar-benar
 * memiliki record untuk id_tfk ini (limit_kuning_detail atau limit_detail).
 * Jika keduanya ada, tampilkan keduanya.
 * Jika tidak ada data sama sekali, fallback ke status_pembayaran untuk menentukan label.
 */
if ($statusLimit === 'approve') {
    $showKuning = $hasKuning;
    $showLimit  = $hasLimit;
    // Jika keduanya kosong, fallback ke status_pembayaran
    if (!$showKuning && !$showLimit) {
        $showKuning = ($statusPembayaran === 'kuning');
        $showLimit  = !$showKuning;
    }
} else {
    // Untuk status selain approve, gunakan status aktual sebagai panduan utama,
    // tapi tetap tampilkan seksi yang memiliki data
    $showKuning = ($statusLimit === 'kuning' || $statusPembayaran === 'kuning') ? true : $hasKuning;
    $showLimit  = ($statusLimit === 'limit') ? true : $hasLimit;
    if (!$showKuning && !$showLimit) {
        $showLimit = true; // default tampilkan seksi limit
    }
}
?>

<?php if($showKuning): ?>
    <h4>Riwayat Status Kuning</h4>
    <?php if($hasKuning): ?>
    <table>
        <thead>
            <tr><th>Kode Kuning</th><th>Keterangan</th><th>Status</th><th>Tgl</th><th>Approval</th></tr>
        </thead>
        <tbody>
        <?php foreach($hist['limit_kuning'] as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['kode_kuning'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['ket']); ?></td>
                <td><?php echo htmlspecialchars($r['status']); ?></td>
                <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                <td><?php echo htmlspecialchars($r['created_by_name'] ?? $r['created_by']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div>Tidak ada detail kuning.</div>
    <?php endif; ?>
<?php endif; ?>

<?php if($showLimit): ?>
    <h4>Detail Status Limit</h4>
    <?php if($hasLimit): ?>
    <table>
        <thead>
            <tr><th>Kode Limit</th><th>Keterangan</th><th>Status</th><th>Tgl</th><th>Approval</th></tr>
        </thead>
        <tbody>
        <?php foreach($hist['limit_detail'] as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['kode_limit'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['ket']); ?></td>
                <td><?php echo htmlspecialchars($r['status']); ?></td>
                <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                <td><?php echo htmlspecialchars($r['created_by_name'] ?? $r['created_by']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <div>Tidak ada detail limit.</div>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>
<?php
$base->close();
?>