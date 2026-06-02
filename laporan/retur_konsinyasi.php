<?php
require_once "../config/connection.php";

if (!isset($_GET['id'])) {
    die('Parameter tidak valid');
}

$id_trk = $secu->injection($_GET['id']);

// Get header
$qHeader = "SELECT 
                trk.*,
                tfk.kode_tfk,
                tfk.tgl_tfk,
                outl.nama_out,
                outl.kode_out
            FROM transaksi_retur_konsinyasi trk
            LEFT JOIN transaksi_faktur_konsinyasi tfk ON trk.id_tfk = tfk.id_tfk
            LEFT JOIN outlet outl ON trk.id_out = outl.id_out
            WHERE trk.id_trk = :id_trk";

$header = $conn->prepare($qHeader);
$header->bindParam(':id_trk', $id_trk, PDO::PARAM_STR);
$header->execute();
$data = $header->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die('Data tidak ditemukan');
}

// Get detail items
$qDetail = "SELECT 
                trkd.*,
                pro.nama_pro,
                pro.kode_pro
            FROM transaksi_retur_konsinyasi_detail trkd
            LEFT JOIN produk pro ON trkd.id_pro = pro.id_pro
            WHERE trkd.id_trk = :id_trk
            ORDER BY trkd.id_trkd";

$detail = $conn->prepare($qDetail);
$detail->bindParam(':id_trk', $id_trk, PDO::PARAM_STR);
$detail->execute();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cetak Retur Konsinyasi - <?php echo $data['no_retur']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 5px 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 5px;
            vertical-align: top;
        }
        .detail-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .detail-table th, .detail-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        .detail-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .summary-box {
            margin-top: 20px;
            padding: 10px;
            border: 2px solid #333;
            display: inline-block;
        }
        .signature {
            margin-top: 50px;
        }
        .signature-box {
            display: inline-block;
            width: 30%;
            text-align: center;
            margin: 0 5%;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="header">
        <h2>BUKTI RETUR KONSINYASI</h2>
        <h3><?php echo $data['no_retur']; ?></h3>
    </div>

    <table class="info-table">
        <tr>
            <td width="50%">
                <table>
                    <tr>
                        <td width="150"><strong>No. Retur</strong></td>
                        <td width="10">:</td>
                        <td><?php echo $data['no_retur']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Tanggal Retur</strong></td>
                        <td>:</td>
                        <td><?php echo date('d F Y', strtotime($data['tgl_retur'])); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Status</strong></td>
                        <td>:</td>
                        <td><?php echo $data['status_trk']; ?></td>
                    </tr>
                </table>
            </td>
            <td width="50%">
                <table>
                    <tr>
                        <td width="150"><strong>No. Faktur Konsinyasi</strong></td>
                        <td width="10">:</td>
                        <td><?php echo $data['kode_tfk']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Outlet</strong></td>
                        <td>:</td>
                        <td><?php echo $data['kode_out']; ?> - <?php echo $data['nama_out']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Keterangan</strong></td>
                        <td>:</td>
                        <td><?php echo !empty($data['keterangan']) ? $data['keterangan'] : '-'; ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <h4>Detail Barang Diretur:</h4>
    <table class="detail-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode Produk</th>
                <th width="40%">Nama Produk</th>
                <th width="13%" class="text-center">Qty Retur</th>
                <th width="13%" class="text-center">Sisa Sebelum</th>
                <th width="14%" class="text-center">Sisa Sesudah</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($detail->rowCount() > 0) {
            $no = 1;
            while ($item = $detail->fetch(PDO::FETCH_ASSOC)) {
        ?>
            <tr>
                <td class="text-center"><?php echo $no++; ?></td>
                <td><?php echo $item['kode_pro']; ?></td>
                <td><?php echo $item['nama_pro']; ?></td>
                <td class="text-center"><?php echo number_format($item['qty_retur'], 0, ',', '.'); ?></td>
                <td class="text-center"><?php echo number_format($item['qty_sisa_sebelum'], 0, ',', '.'); ?></td>
                <td class="text-center"><strong><?php echo number_format($item['qty_sisa_sesudah'], 0, ',', '.'); ?></strong></td>
            </tr>
        <?php
            }
        }
        ?>
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background-color: #f0f0f0;">
                <td colspan="3" class="text-right">TOTAL:</td>
                <td class="text-center"><?php echo number_format($data['total_qty'], 0, ',', '.'); ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-box">
        <strong>Ringkasan:</strong><br>
        Total Item: <?php echo $data['total_item']; ?> item<br>
        Total Quantity: <?php echo number_format($data['total_qty'], 0, ',', '.'); ?> pcs
    </div>

    <div class="signature">
        <table width="100%">
            <tr>
                <td class="text-center signature-box">
                    <p>Diserahkan Oleh,</p>
                    <br><br><br>
                    <p>_________________</p>
                    <p>(Outlet)</p>
                </td>
                <td class="text-center signature-box">
                    <p>Diterima Oleh,</p>
                    <br><br><br>
                    <p>_________________</p>
                    <p>(Gudang)</p>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 30px; font-size: 10px; color: #666;">
        <p>Dicetak pada: <?php echo date('d F Y H:i:s'); ?></p>
    </div>
</body>
</html>
