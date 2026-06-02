<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=inventoryretur.xls");
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
	$search	= $data->cekcari($cari, '-', ' ');
	$active	= 'Active';
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Inventory</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="10">INVENTORY RETUR</th>
            </tr>
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>#</center></th>
                    <th>NAMA PRODUK</th>
                    <th>KATEGORI PRODUK</th>
                    <th>KATEGORI OBAT</th>
                    <th>KODE PRODUK JADI</th>
                    <th>UKURAN</th>
                    <th>NO. BATCH</th>
                    <th>ED</th>
                    <th>jumlah</th>
					<th><div align="right">GUDANG</div></th>
                    <th><div align="right">HARGA</div></th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.no_bcode, A.ed,A.gudang, A.sisa,B.kategori_obat,B.kode_produk_jadi, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, D.nama_kpr, E.nama_spr FROM inventory_retur AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.sisa>0 AND B.nama_pro LIKE '%$search%' AND C.status_phg=:active ORDER BY B.nama_pro ASC");
				$master->bindParam(':active', $active, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['nama_kpr']); ?></td>
                    <td><?php echo($hasil['kategori_obat']); ?></td>
                    <td><?php echo($hasil['kode_produk_jadi']); ?></td>
                    <td><?php echo($hasil['berat_pro']); ?> <?php echo($hasil['nama_spr']); ?></td>
                    <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['ed']); ?></td>
					<td><?php echo($hasil['gudang']); ?></td>
                    <td><div align="right"><?php echo($hasil['harga_phg']); ?></div></td>
                    <td><center><?php echo($hasil['sisa']); ?></center></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
    </body>
<?php } ?>
</html>
