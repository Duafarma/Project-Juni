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
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$obat	= empty($pecah[0]) ? "" : "AND C.id_pro='$pecah[0]'";
	$tgl1	= empty($pecah[1]) ? "" : "AND A.tgl_tfk>='$pecah[1]'"; 
	$tgl2	= empty($pecah[2]) ? "" : "AND A.tgl_tfk<='$pecah[2]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Penjualan Obat</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
    	<h4>REPORT PENJUALAN OBAT</h4>
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th><div align="left">NO. FAKTUR</div></th>
                    <th><div align="center">TGL. FAKTUR</div></th>
                    <th><div align="left">ID PRODUK</div></th>
                    <th><div align="left">NAMA PRODUK</div></th>
                    <th><div align="left">SEDIAAN</div></th>
                    <th><div align="left">NO. BATCH</div></th>
                    <th><div align="left">TGL. EXPIRED</div></th>
                    <th><div align="right">TOTAL</div></th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.jumlah_tfd, B.kode_tfk, B.tgl_tfk, C.no_bcode, C.tgl_expired, D.kode_pro, D.nama_pro, D.berat_pro, E.nama_kpr, F.nama_spr FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk_stokdetail AS C ON A.id_psd=C.id_psd LEFT JOIN produk AS D ON C.id_pro=D.id_pro LEFT JOIN kategori_produk AS E ON D.id_kpr=E.id_kpr LEFT JOIN satuan_produk AS F ON D.id_spr=F.id_spr WHERE A.id_tfd!='' $obat $tgl1 $tgl2 ORDER BY B.tgl_tfk DESC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['kode_tfk']); ?></td>
                	<td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                	<td><?php echo($hasil['kode_pro']); ?></td>
                	<td><?php echo($hasil['nama_pro']); ?></td>
                	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                	<td><?php echo($hasil['no_bcode']); ?></td>
                	<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
                	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
