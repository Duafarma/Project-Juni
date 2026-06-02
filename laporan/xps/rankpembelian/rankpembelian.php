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
	$rank	= empty($pecah[0]) ? 10 : $pecah[0];
	$tgl1	= empty($pecah[1]) ? "" : "AND C.tgl_tre>='$pecah[1]'";
	$tgl2	= empty($pecah[2]) ? "" : "AND C.tgl_tre<='$pecah[2]'";
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Rank Pembelian Produk</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>


    <body>
    	<h4>RANK PEMBELIAN PRODUK</h4>
		<table style="font-size:12px;">
        	<tr>
            	<td>Max. Rank</td>
            	<td><center>:</center></td>
            	<td><?php echo($rank); ?></td>
            </tr>
        	<tr>
            	<td>Periode 1</td>
            	<td><center>:</center></td>
            	<td><?php echo(@$pecah[1]); ?></td>
            </tr>
        	<tr>
            	<td>Periode 2</td>
            	<td><center>:</center></td>
            	<td><?php echo(@$pecah[2]); ?></td>
            </tr>
        </table>
        <p></p>
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th><center>RANK</center></th>
                    <th><div align="left">OUTLET</div></th>
                    <th><div align="right">TOTAL</div></th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT D.nama_pro, D.total FROM(SELECT A.nama_pro, (SELECT SUM(B.jumlah_trd) FROM transaksi_receivedetail AS B LEFT JOIN transaksi_receive AS C ON B.id_tre=C.id_tre WHERE B.id_pro=A.id_pro $tgl1 $tgl2) AS total FROM produk AS A GROUP BY A.id_pro) AS D ORDER BY D.total DESC LIMIT :rank");
				$master->bindParam(':rank', $rank, PDO::PARAM_INT);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['nama_pro']); ?></td>
                	<td><div align="right"><?php echo($data->angka($hasil['total'])); ?></div></td>
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
