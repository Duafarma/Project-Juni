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
	$sistem	= $data->sistem('url_sis');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header("location:$sistem/signout"); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$search	= @$pecah[0];
	$tgl1	= empty(@$pecah[1]) ? "" : "AND B.tgl_tfk>='$pecah[1]'";
	$tgl2	= empty(@$pecah[2]) ? "" : "AND B.tgl_tfk<='$pecah[2]'";
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Produk</title>
        <link href="<?php echo("$sistem/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
    	<h4>REPORT PRODUK</h4>
        <div>Search : <?php echo(@$pecah[0]); ?></div>
        <div>Periode : <?php echo(@$pecah[1].' s/d '.@$pecah[2]); ?></div>
    	<br />
        <table class="tabel">
        	<thead>
                <tr>
                    <th><center>#</center></th>
                    <th><div align="left">Nama</div></th>
                    <th><div align="left">Kode</div></th>
                    <th><div align="left">Kategori</div></th>
                    <th><div align="left">Berat/Satuan</div></th>
                    <th><div align="right">Jumlah</div></th>
                </tr>
    		</thead>
            <tbody>
			<?php
				$active	= 'Active';
				$nomor	= 1;
				$master		= $conn->prepare("SELECT A.id_pro, C.nama_pro, C.kode_pro, SUM(A.jumlah_tfd) AS total, D.nama_spr, E.nama_kpr FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN satuan_produk AS D ON C.id_spr=D.id_spr LEFT JOIN kategori_produk AS E ON C.id_kpr=E.id_kpr WHERE (C.nama_pro LIKE '%$search%' OR C.kode_pro LIKE '%$search%') $tgl1 $tgl2 GROUP BY C.id_pro ORDER BY SUM(A.jumlah_tfd) DESC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
                <tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['kode_pro']); ?></td>
                    <td><?php echo($hasil['nama_kpr']); ?></td>
                    <td><?php echo($hasil['nama_spr']); ?></td>
                    <td><div align="right"><?php echo($data->angka($hasil['total'])); ?></div></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
