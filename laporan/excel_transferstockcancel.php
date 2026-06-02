<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$valid = $secu->validadmin($admin, $kunci);

if (!$valid) {
    die('Session habis. Silakan login kembali.');
}

$search = $secu->injection(@$_GET['search']);
$tgl1   = $secu->injection(@$_GET['tgl1']);
$tgl2   = $secu->injection(@$_GET['tgl2']);

$wTgl1 = !empty($tgl1) ? "AND DATE(A.transfer_at) >= '$tgl1'" : '';
$wTgl2 = !empty($tgl2) ? "AND DATE(A.transfer_at) <= '$tgl2'" : '';

$namaAdmin  = $data->myadmin($admin, 'nama_adm');
$filterInfo = 'Semua Data';
if (!empty($tgl1) && !empty($tgl2)) {
    $filterInfo = 'Periode: ' . date('d/m/Y', strtotime($tgl1)) . ' s/d ' . date('d/m/Y', strtotime($tgl2));
} elseif (!empty($tgl1)) {
    $filterInfo = 'Dari: ' . date('d/m/Y', strtotime($tgl1));
} elseif (!empty($tgl2)) {
    $filterInfo = 'Sampai: ' . date('d/m/Y', strtotime($tgl2));
}
if (!empty($search)) {
    $filterInfo .= ' | Pencarian: ' . htmlspecialchars($search);
}

// Set header download
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Transfer_StockCancel_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Query master
$qMaster = "SELECT
                A.id_tsc,
                A.kode_transfer,
                A.nomor_transfer,
                A.kode_faktur,
                A.tgl_faktur,
                A.id_inventory_tujuan,
                A.total_item,
                A.total_qty,
                A.keterangan_transfer,
                A.status_transfer,
                A.transfer_at,
                A.transfer_by,
                C.nama_adm AS transfer_by_name,
                OutInfo.nama_out AS nama_outlet
            FROM transfer_stockcancel AS A
            LEFT JOIN adminz AS C ON A.transfer_by = C.id_adm
            LEFT JOIN (
                SELECT TSCD.id_tsc, O2.nama_out
                FROM transfer_stockcancel_detail TSCD
                JOIN produk_stockdetail_cancel PSC ON TSCD.id_psc = PSC.id_psc
                LEFT JOIN transaksi_faktur TF ON PSC.id_tfk = TF.id_tfk
                LEFT JOIN transaksi_faktur_junk TFJ ON PSC.id_tfk = TFJ.id_tfk_original
                LEFT JOIN outlet O2 ON COALESCE(TF.id_out, TFJ.id_out) = O2.id_out
                GROUP BY TSCD.id_tsc
            ) AS OutInfo ON OutInfo.id_tsc = A.id_tsc
            WHERE 1=1 $wTgl1 $wTgl2
            AND (
                A.nomor_transfer LIKE '%$search%'
                OR A.kode_transfer LIKE '%$search%'
                OR A.kode_faktur LIKE '%$search%'
            )
            ORDER BY A.transfer_at DESC";
$master = $conn->query($qMaster);
$rows   = $master->fetchAll(PDO::FETCH_ASSOC);

// Query detail semua id_tsc yang akan tampil
$idList = array_column($rows, 'id_tsc');
$detailMap = [];
if (!empty($idList)) {
    $idIn = "'" . implode("','", $idList) . "'";
    $qDetail = "SELECT
                    A.id_tsc,
                    A.id_psc,
                    A.id_psd_baru,
                    A.jumlah_transfer,
                    A.no_bcode,
                    A.tgl_expired,
                    B.nama_pro,
                    B.kode_produk_jadi,
                    B.kategori_obat,
                    C.gudang AS gudang_tujuan
                FROM transfer_stockcancel_detail AS A
                LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                LEFT JOIN produk_stokdetail AS C ON A.id_psd_baru = C.id_psd
                WHERE A.id_tsc IN ($idIn)
                ORDER BY B.nama_pro ASC";
    $qd = $conn->query($qDetail);
    while ($d = $qd->fetch(PDO::FETCH_ASSOC)) {
        $detailMap[$d['id_tsc']][] = $d;
    }
}

$totalQty  = 0;
$totalItem = 0;
foreach ($rows as $r) {
    $totalQty  += intval($r['total_qty']);
    $totalItem += intval($r['total_item']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        h2 { text-align: center; margin: 0; }
        p.sub { text-align: center; margin: 2px 0; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th {
            background-color: #2d6a9f;
            color: white;
            border: 1px solid #aaa;
            padding: 6px 8px;
            text-align: center;
            font-size: 11px;
        }
        td {
            border: 1px solid #ccc;
            padding: 5px 8px;
            font-size: 11px;
            vertical-align: top;
        }
        tr:nth-child(even) td { background-color: #f5f5f5; }
        .th-detail {
            background-color: #4a7fa5;
            color: white;
            border: 1px solid #aaa;
            padding: 5px 8px;
            font-size: 10px;
            text-align: center;
        }
        .td-detail {
            background-color: #eef4fb;
            border: 1px solid #ccc;
            padding: 4px 8px;
            font-size: 10px;
        }
        .footer { background-color: #dce6f1; font-weight: bold; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .badge-success { color: #155724; }
        .badge-danger  { color: #721c24; }
    </style>
</head>
<body>

<h2>LAPORAN TRANSFER STOCK </h2>
<p class="sub"><?php echo $filterInfo; ?></p>
<p class="sub">Diekspor oleh: <?php echo htmlspecialchars($namaAdmin); ?> &nbsp;|&nbsp; <?php echo date('d/m/Y H:i:s'); ?></p>

<table>
    <thead>
        <tr>
            <th rowspan="2">#</th>
            <th rowspan="2">Nomor Transfer</th>
            <th rowspan="2">Kode Faktur</th>
            <th rowspan="2">Nama Outlet</th>
            <th rowspan="2">Tgl. Faktur</th>
            <th rowspan="2">Inventory Tujuan</th>
            <th rowspan="2">Tgl. Transfer</th>
            <th rowspan="2">Ditransfer Oleh</th>
            <th rowspan="2" class="text-center">Total Item</th>
            <th rowspan="2" class="text-center">Total Qty</th>
            <th rowspan="2">Status</th>
            <th rowspan="2">Keterangan</th>
            <th colspan="6">Detail Item</th>
        </tr>
        <tr>
            <th>Kode Produk</th>
            <th>Nama Produk</th>
            <th>Kategori Produk</th>
            <th>Batch</th>
            <th>Expired</th>
            <th class="text-center">Qty</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($rows)): ?>
        <tr><td colspan="17" class="text-center">Tidak ada data</td></tr>
    <?php else: ?>
    <?php $no = 0; foreach ($rows as $row): $no++; ?>
    <?php
        $tglFaktur  = !empty($row['tgl_faktur'])  ? date('d/m/Y', strtotime($row['tgl_faktur']))       : '-';
        $transferAt = !empty($row['transfer_at'])  ? date('d/m/Y H:i', strtotime($row['transfer_at']))  : '-';
        $nomorTrans = !empty($row['nomor_transfer']) ? $row['nomor_transfer'] : $row['kode_transfer'];
        $inventory  = ($row['id_inventory_tujuan'] == 'AUTO') ? 'Gudang Asal Masing-masing' : $row['id_inventory_tujuan'];
        $statusText = ucfirst($row['status_transfer']);
        $detailRows = $detailMap[$row['id_tsc']] ?? [];
        $rowspan    = max(1, count($detailRows));
    ?>
        <?php if (empty($detailRows)): ?>
        <tr>
            <td class="text-center"><?php echo $no; ?></td>
            <td><strong><?php echo htmlspecialchars($nomorTrans); ?></strong></td>
            <td><?php echo htmlspecialchars($row['kode_faktur']); ?></td>
            <td><?php echo htmlspecialchars($row['nama_outlet'] ?? '-'); ?></td>
            <td class="text-center"><?php echo $tglFaktur; ?></td>
            <td><?php echo htmlspecialchars($inventory); ?></td>
            <td class="text-center"><?php echo $transferAt; ?></td>
            <td><?php echo htmlspecialchars($row['transfer_by_name'] ?? '-'); ?></td>
            <td class="text-center"><?php echo number_format($row['total_item']); ?></td>
            <td class="text-center"><?php echo number_format($row['total_qty']); ?></td>
            <td class="text-center <?php echo ($row['status_transfer'] == 'completed') ? 'badge-success' : 'badge-danger'; ?>"><?php echo $statusText; ?></td>
            <td><?php echo htmlspecialchars($row['keterangan_transfer'] ?? '-'); ?></td>
            <td colspan="5" class="text-center text-muted">-</td>
        </tr>
        <?php else: ?>
        <?php foreach ($detailRows as $idx => $det): ?>
        <tr>
            <?php if ($idx === 0): ?>
            <td class="text-center" rowspan="<?php echo $rowspan; ?>"><?php echo $no; ?></td>
            <td rowspan="<?php echo $rowspan; ?>"><strong><?php echo htmlspecialchars($nomorTrans); ?></strong></td>
            <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($row['kode_faktur']); ?></td>
            <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($row['nama_outlet'] ?? '-'); ?></td>
            <td rowspan="<?php echo $rowspan; ?>" class="text-center"><?php echo $tglFaktur; ?></td>
            <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($inventory); ?></td>
            <td rowspan="<?php echo $rowspan; ?>" class="text-center"><?php echo $transferAt; ?></td>
            <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($row['transfer_by_name'] ?? '-'); ?></td>
            <td rowspan="<?php echo $rowspan; ?>" class="text-center"><?php echo number_format($row['total_item']); ?></td>
            <td rowspan="<?php echo $rowspan; ?>" class="text-center"><?php echo number_format($row['total_qty']); ?></td>
            <td rowspan="<?php echo $rowspan; ?>" class="text-center <?php echo ($row['status_transfer'] == 'completed') ? 'badge-success' : 'badge-danger'; ?>"><?php echo $statusText; ?></td>
            <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($row['keterangan_transfer'] ?? '-'); ?></td>
            <?php endif; ?>
            <td><?php echo htmlspecialchars($det['kode_produk_jadi'] ?? '-'); ?></td>
            <td><?php echo htmlspecialchars($det['nama_pro'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($det['kategori_obat'] ?? '-'); ?></td>

            <td><?php echo htmlspecialchars($det['no_bcode'] ?? '-'); ?></td>
            <td class="text-center"><?php echo !empty($det['tgl_expired']) ? date('d/m/Y', strtotime($det['tgl_expired'])) : '-'; ?></td>
            <td class="text-center"><?php echo number_format($det['jumlah_transfer']); ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>
        <!-- TOTAL ROW -->
        <tr class="footer">
            <td colspan="8" class="text-right">TOTAL</td>
            <td class="text-center"><?php echo number_format($totalItem); ?></td>
            <td class="text-center"><?php echo number_format($totalQty); ?></td>
            <td colspan="7"></td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
<?php $conn = $base->close(); ?>
