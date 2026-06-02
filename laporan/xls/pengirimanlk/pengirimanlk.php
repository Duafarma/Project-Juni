<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=outlet.xls");
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
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Outlet</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="26">DATA PENGIRIMAN LUAR KOTA</th>
            </tr>
        	<tr>
            	<th colspan="26"><?php echo($data->sistem('pt_sis')); ?></th>
            </tr>
            <tr>
            	<td colspan="26"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>NO</center></th>
                    <th>NO FAKTUR</th>
                    <th>NAMA OUTLET</th>
                    <th>STATUS PENGIRIMAN</th>
                    <th>EKPEDISI</th>
                    <th>CABANG</th>
                    <th>NO RESI</th>
                    <th>TANGGAL FAKTUR</th>
                    <th>TANGGAL PENGIRIMAN</th>
                   
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.id_p_l_k,A.id_tfk,B.nama_out,D.tahap_pengiriman,C.nama_vendor,A.cabang,A.nomor_resi,A.tanggal_faktur,A.tanggal_pengiriman FROM transaksi_p_luar_kota AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN vendor_pengiriman AS C ON A.id_vendor=C.id_vendor LEFT JOIN master_pengiriman AS D ON A.id_pengiriman=D.id_pengiriman WHERE A.id_p_l_k!='' ORDER BY A.id_p_l_k DESC, A.id_p_l_k DESC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['id_tfk']); ?></td>
                	<td><?php echo($hasil['nama_out']); ?></td>
                	<td><?php echo($hasil['tahap_pengiriman']); ?></td>
                	<td><?php echo($hasil['nama_vendor']); ?></td>
                	<td><?php echo($hasil['cabang']); ?></td>
                	<td><?php echo($hasil['nomor_resi']); ?></td>
                    <td><?php echo($hasil['tanggal_faktur']); ?></td>
                	<td><?php echo($hasil['tanggal_pengiriman']); ?></td>
                	
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
