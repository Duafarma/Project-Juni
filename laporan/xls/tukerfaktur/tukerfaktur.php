<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=tukerfaktur.xls");
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
        <title>Tuker Faktur</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="10">LAPORAN TUKER FAKTUR</th>
            </tr>
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>#</center></th>
                    <th>NOMOR FAKTUR</th>
                    <th>NAMA OUTLET</th>
                    <th>TANGGAL FAKTUR PENJUALAN</th>
                    <th>TANGGAL TUKER FAKTUR</th>
                    <th>STATUS</th>
                    <th>KETERANGAN</th>
		</tr>
    		</thead>
            <tbody>
			<?php
		$nomor		= $mulai;
// 		$jumlah	= $conn->query("SELECT COUNT(A.id_tkf_t) AS total FROM tuker_faktur AS A WHERE (A.id_tfk LIKE '%$cari%' OR A.id_tfk LIKE '%$cari%')")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_tkf_t, A.tanggal_tkf, A.status,A.note, B.kode_tfk, B.tgl_tfk, C.nama_out FROM tuker_faktur AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk INNER JOIN outlet AS C ON A.id_kot=C.id_out ");
		// $master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){

				
			?>
                <tr>
                <td><center><?php echo($nomor); ?></center></td>
                <td><?php echo($hasil['kode_tfk']); ?></td>
                <td><?php echo($hasil['nama_out']); ?></td>
                <td><?php echo($hasil['tgl_tfk']); ?></td>
                <td><?php echo($hasil['tanggal_tkf']); ?></td>
                <td><?php echo($hasil['status']); ?></td>
                <td><?php echo($hasil['note']); ?></td>
                
                  				  
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
