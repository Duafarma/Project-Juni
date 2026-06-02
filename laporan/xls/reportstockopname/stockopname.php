<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Report Stock Opname Puri A.xls");
	require_once('../../../config/connection/connection.php');
	require_once('../../../config/connection/security.php');
	require_once('../../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
    $periodeAwal = date('Y-m-01');
    $periodeAkhir = date('Y-m-t');



    // Query agregasi IN/OUT langsung dari tabel transaksi sumber.
	$master = $conn->prepare("
		SELECT
			B.id_pro,
			B.nama_pro,
			IFNULL((SELECT SUM(qty_so) FROM stock_awal WHERE id_pro = B.id_pro), 0) AS stok_awal,
			IFNULL((SELECT SUM(qty_so) FROM produk_stokdetail WHERE id_pro = B.id_pro), 0) AS qty_so,
            IFNULL(A.total_in, 0) AS total_in,
            IFNULL(C.total_out, 0) AS total_out,
            IFNULL(D.harga_phg, 0) AS harga_hna
		FROM produk AS B
        LEFT JOIN (
            SELECT data_in.id_pro, SUM(data_in.jumlah) AS total_in
            FROM (
                SELECT A.id_pro, A.jumlah_trd AS jumlah
                FROM transaksi_receivedetail AS A
                LEFT JOIN transaksi_receive AS B ON A.id_tre = B.id_tre
                WHERE B.tgl_tre BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_ttd AS jumlah
                FROM transaksi_transferstockdetail AS A
                LEFT JOIN transaksi_transferstock AS B ON A.id_ttr = B.id_ttr
                WHERE B.tipe_ttr = 'IN'
                    AND B.tgl_ttr BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.qty_retur AS jumlah
                FROM transaksi_retur_konsinyasi_detail AS A
                LEFT JOIN transaksi_retur_konsinyasi AS B ON A.id_trk = B.id_trk
                WHERE B.tgl_retur BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_transfer AS jumlah
                FROM transfer_stockcancel_detail AS A
                LEFT JOIN transfer_stockcancel AS B ON A.id_tsc = B.id_tsc
                WHERE B.transfer_at BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())
            ) AS data_in
            GROUP BY data_in.id_pro
        ) AS A ON A.id_pro = B.id_pro
        LEFT JOIN (
            SELECT data_out.id_pro, SUM(data_out.jumlah) AS total_out
            FROM (
                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_fakturdetail AS A
                LEFT JOIN transaksi_faktur AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_fakturdetail_d AS A
                LEFT JOIN transaksi_faktur_d AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_fakturdetail_p AS A
                LEFT JOIN transaksi_faktur_p AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_fakturdetail_r AS A
                LEFT JOIN transaksi_faktur_r AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_fakturdetail_l AS A
                LEFT JOIN transaksi_faktur_l AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_ttd AS jumlah
                FROM transaksi_transferstockdetail AS A
                LEFT JOIN transaksi_transferstock AS B ON A.id_ttr = B.id_ttr
                WHERE B.tipe_ttr = 'OUT'
                    AND B.status_ttr = 'Approved'
                    AND B.tgl_ttr BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_faktur_penggantian_barang_detail AS A
                LEFT JOIN transaksi_faktur_penggantian_barang AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.jumlah_tfd AS jumlah
                FROM transaksi_fakturdetail_pim AS A
                LEFT JOIN transaksi_faktur_pim AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())

                UNION ALL

                SELECT A.id_pro, A.sisa_tfd AS jumlah
                FROM transaksi_fakturdetail_konsinyasi AS A
                LEFT JOIN transaksi_faktur_konsinyasi AS B ON A.id_tfk = B.id_tfk
                WHERE B.tgl_tfk BETWEEN DATE_FORMAT(CURRENT_DATE(),'%Y-%m-01') AND LAST_DAY(CURRENT_DATE())
            ) AS data_out
            GROUP BY data_out.id_pro
        ) AS C ON C.id_pro = B.id_pro
        LEFT JOIN (
            SELECT id_pro, MAX(harga_phg) AS harga_phg
            FROM produk_harga
            WHERE status_phg = 'Active'
            GROUP BY id_pro
        ) AS D ON D.id_pro = B.id_pro
		WHERE B.status_pro = 'Active'
		ORDER BY B.nama_pro ASC
	");
	$master->execute();
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Rekap Kartu Stok </title>
    </head>
    <body>
        <table>
            <tr>
                <th colspan="8">REKAP FISIK - REKAP KARTU STOK</th>
            </tr>
            <tr>
                <td colspan="8">Periode IN/OUT: <?php echo $periodeAwal; ?> s.d. <?php echo $periodeAkhir; ?></td>
            </tr>
            <tr>
                <td colspan="8"></td>
            </tr>
        </table>
        <table border="1">
            <thead>
                <tr>
                    <th><center>NO</center></th>
                    <th><center>NAMA PRODUK</center></th>
                    <th><center>STOK AWAL</center></th>
                    <th><center>STOK MASUK</center></th>
                    <th><center>TERJUAL</center></th>
                    <th><center>STOK AKHIR</center></th>
                    <th><center>HNA</center></th>
                    <th><center>TOTAL</center></th>
                </tr>
            </thead>
            <tbody>
            <?php
				$nomor = 1;
				$grandTotal = 0;
				while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
					$awal		= $hasil['stok_awal'];
					$totalin	= $hasil['total_in'];
					$totalout	= $hasil['total_out'];
					$so			= $hasil['qty_so'];
					$akhir		= ($awal + $totalin) - $totalout;
					$hna		= $hasil['harga_hna'];
					$total		= $so * $hna;
					$grandTotal += $total;
            ?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo(htmlspecialchars($hasil['nama_pro'])); ?></td>
                    <td><center><?php echo($awal); ?></center></td>
                    <td><center><?php echo($totalin); ?></center></td>
                    <td><center><?php echo($totalout); ?></center></td>
                    <td><center><?php echo($so); ?></center></td>
                    <td><center><?php echo($hna); ?></center></td>
                    <td><center><?php echo($total); ?></center></td>
                </tr>
            <?php $nomor++; } ?>
                <tr>
                    <td colspan="7"><strong>GRAND TOTAL</strong></td>
                    <td><center><strong><?php echo($grandTotal); ?></strong></center></td>
                </tr>
            </tbody>
        </table>
        <?php $conn = $base->close(); ?>
    </body>
<?php } ?>
</html>
