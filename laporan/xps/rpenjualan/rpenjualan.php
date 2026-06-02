<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	require_once('../../../config/connection/connection.php');
	require_once('../../../config/connection/security.php');
	require_once('../../../config/function/data.php');
	require_once('../../../config/function/date.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$date	= new Date;
	$tanggal= date('Y-m-d');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$outlet	= empty($pecah[0]) ? "" : "AND B.id_out='$pecah[0]'"; 
	$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
	$tgl1	= empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'"; 
	$tgl2	= empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Penjualan</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
    	<h4>REPORT PENJUALAN</h4>
    	<table class="tabel">
        	<thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Tgl. PO</center></th>
                    <th>Nomor PO</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th>Nomor Faktur</th>
                    <th>Outlet</th>
                    <th>Officer Code</th>
                    <th>Nama Barang</th>
                    <th>Kode Barang</th>
                    <th>No. Batch</th>
                    <th><center>Exp. Date</center></th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Diskon</th>
                    <th>Total</th>
                    <th><center>Jatuh Tempo</center></th>
                    <th><center>Remaining</center></th>
                    <th><center>Status</center></th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, B.kode_tfk, B.tgl_tfk, B.po_tfk, B.tglpo_tfk, B.tgl_limit, B.status_tfk, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, C.nama_pro, C.kode_pro, D.nama_out, D.ofcode_out, E.no_bcode, E.tgl_expired FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN outlet AS D ON B.id_out=D.id_out LEFT JOIN produk_stokdetail AS E ON A.id_psd=E.id_psd WHERE A.id_tfd!='' $outlet $produk $tgl1 $tgl2 ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC");
				$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['tglpo_tfk']); ?></center></td>
                    <td><?php echo($hasil['po_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><?php echo($hasil['nama_out']); ?></td>
                    <td><?php echo($hasil['ofcode_out']); ?></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['kode_pro']); ?></td>
                    <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['tgl_expired']); ?></td>
                    <td><?php echo($data->angka($hasil['jumlah_tfd'])); ?></td>
                    <td><?php echo($data->angka($hasil['harga_tfd'])); ?></td>
                    <td><?php echo($hasil['diskon_tfd']); ?></td>
                    <td><?php echo($data->angka($hasil['total_tfd'])); ?></td>
                    <td><center><?php echo($hasil['tgl_limit']); ?></center></td>
                    <td><center><?php echo($hasil['jarak']); ?></center></td>
                    <td><center><?php echo($status); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
