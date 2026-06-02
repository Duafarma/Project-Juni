<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$filterPrinciple = $secu->injection(@$_GET['principle'] ?? '');

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Rekap_Approve_StockOpname_" . date('Y-m-d_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rekap Approve Stock Opname</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; padding: 6px 8px; }
        th { background-color: #1a56db; color: white; font-weight: bold; text-align: center; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .header { text-align: center; margin-bottom: 15px; }
        .plus { color: green; font-weight: bold; }
        .minus { color: red; font-weight: bold; }
        .zero { color: #666; }
        tfoot td { background-color: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>REKAP HASIL APPROVE STOCK OPNAME</h2>
        <?php if(!empty($filterPrinciple)): ?>
        <p>Principle: <strong><?php echo htmlspecialchars($filterPrinciple); ?></strong></p>
        <?php endif; ?>
        <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Principle</th>
                <th>Nama Produk</th>
                <th>No. Batch</th>
                <th>QTY Sebelum SO</th>
                <th>QTY Sesudah SO</th>
                <th>Selisih</th>
                <th>QTY Inventory Sekarang</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT A.id, A.id_psd, A.id_pro, A.nama_pro, A.qty, A.qty_so, A.no_bcode, A.bcode_so, A.selisih, B.sisa_psd, C.nama_p, D.nama_principle
                FROM stock AS A
                LEFT JOIN produk_stokdetail AS B ON A.id_psd=B.id_psd
                LEFT JOIN produk AS C ON A.id_pro=C.id_pro
                LEFT JOIN master_principle AS D ON C.nama_p=D.id_mp
                WHERE A.qty_so > 0";
        if(!empty($filterPrinciple)) $sql .= " AND D.nama_principle = :fp";
        $sql .= " ORDER BY D.nama_principle ASC, A.nama_pro ASC";
        $master = $conn->prepare($sql);
        if(!empty($filterPrinciple)) $master->bindParam(':fp', $filterPrinciple, PDO::PARAM_STR);
        $master->execute();

        $nomor = 1;
        $totalBefore = 0;
        $totalAfter = 0;
        $rows = $master->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $hasil):
            $selisih = (int)$hasil['qty_so'] - (int)$hasil['qty'];
            $totalBefore += (int)$hasil['qty'];
            $totalAfter  += (int)$hasil['qty_so'];
            if($selisih > 0) $cls = 'plus';
            elseif($selisih < 0) $cls = 'minus';
            else $cls = 'zero';
        ?>
            <tr>
                <td class="text-center"><?php echo $nomor; ?></td>
                <td class="text-center"><?php echo htmlspecialchars($hasil['nama_principle'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($hasil['nama_pro']); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($hasil['bcode_so']); ?></td>
                <td class="text-right"><?php echo number_format($hasil['qty']); ?></td>
                <td class="text-right"><?php echo number_format($hasil['qty_so']); ?></td>
                <td class="text-center <?php echo $cls; ?>">
                    <?php echo ($selisih > 0 ? '+' : '') . number_format($selisih); ?>
                </td>
                <td class="text-right"><?php echo number_format($hasil['sisa_psd']); ?></td>
            </tr>
        <?php $nomor++; endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-center"><strong>TOTAL</strong></td>
                <td class="text-right"><strong><?php echo number_format($totalBefore); ?></strong></td>
                <td class="text-right"><strong><?php echo number_format($totalAfter); ?></strong></td>
                <td class="text-center"><strong><?php
                    $diff = $totalAfter - $totalBefore;
                    echo ($diff > 0 ? '+' : '') . number_format($diff);
                ?></strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top:20px; font-size:10px; color:#666;">
        Total <?php echo count($rows); ?> produk &mdash; Dicetak pada <?php echo date('d/m/Y H:i:s'); ?>
    </p>
</body>
</html>
