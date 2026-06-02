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
	$search	= $data->cekcari($pecah[0], '-', ' ');
	$tgl1	= empty($pecah[1]) ? "" : "AND A.tgl_expired>='$pecah[1]'"; 
	$tgl2	= empty($pecah[2]) ? "" : "AND A.tgl_expired<='$pecah[2]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report ED</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
    	<h4>REPORT ED</h4>
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th><div align="left">ID BARANG</div></th>
                    <th><div align="left">NAMA BARANG</div></th>
                    <th><div align="left">SEDIAAN</div></th>
                    <th><div align="left">NO. BCODE</div></th>
                    <th><div align="center">TGL. EXPIRED</div></th>
                    <th><div align="right">STOK</div></th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.no_bcode, A.tgl_expired, A.sisa_psd, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, D.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE (A.no_bcode LIKE '%$search%' OR B.nama_pro LIKE '%$search%' OR B.kode_pro LIKE '%$search%') $tgl1 $tgl2 ORDER BY B.nama_pro ASC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['kode_pro']); ?></td>
                	<td><?php echo($hasil['nama_pro']); ?></td>
                	<td><?php echo("$hasil[nama_kpr] ($hasil[berat_pro] $hasil[nama_spr])"); ?></td>
                	<td><?php echo($hasil['no_bcode']); ?></td>
                	<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
                	<td><div align="right"><?php echo($hasil['sisa_psd']); ?></div></td>
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
