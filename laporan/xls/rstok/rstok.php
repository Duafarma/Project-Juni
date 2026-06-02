<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_stok.xls");
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
	$search	= $data->cekcari(@$pecah[0], '-', ' ');
	$tgl1	= @$pecah[1];
	$tgl2	= @$pecah[2];
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Stok</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="5">REPORT STOK</th>
            </tr>
            <tr>
            	<td colspan="5"></td>
            </tr>
            <tr>
            	<td>Periode : <?php echo("$pecah[1] - $pecah[2]"); ?></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <!--<th><div align="left">ID BARANG</div></th>-->
                    <th><div align="left">NAMA BARANG</div></th>
                    <th><div align="right">STOK</div></th>
                    <th><div align="left">HARGA PRODUK</div></th>
				</tr>
    		</thead>
            <tbody>
            <?php if(empty($tgl1) || empty($tgl2)){ ?>
				<tr><td colspan="6">Pilih periode tanggal...</td></tr>
			<?php } else {
				$nomor	= 1;
				// $master	= $conn->prepare("SELECT id_pro, nama_pro FROM produk WHERE nama_pro LIKE '%$search%' ORDER BY nama_pro ASC");
				$master	= $conn->prepare("SELECT A.id_pro, A.nama_pro, B.harga_phg FROM produk AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro WHERE A.nama_pro LIKE '%$search%' ORDER BY A.nama_pro ASC");

				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$awal	= $data->stokawal($hasil['id_pro'], $tgl1);
					$in		= $data->stokin($hasil['id_pro'], $tgl1, $tgl2);
					$out	= $data->stokout($hasil['id_pro'], $tgl1, $tgl2);
					$akhir	= ($awal + $in) - $out;
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['nama_pro']); ?></td>
                	<td><div align="right"><?php echo($out); ?></div></td>
                	 <td><?php echo($hasil['harga_phg']); ?></td>

                	<!--<td><div align="right"><?php echo($awal); ?></div></td>-->
                	<!--<td><div align="right"><?php echo($in); ?></div></td>-->
                	<!--<td><div align="right"><?php echo($akhir); ?></div></td>-->
                </tr>
			<?php
				$nomor++;
            	}
				}
			?>
            </tbody>
        </table>
    </body>
<?php } ?>
</html>
