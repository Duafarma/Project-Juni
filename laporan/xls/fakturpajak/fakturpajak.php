<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=fakturpajak.xls");
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
            	<th colspan="26">DATA FAKTUR PAJAK</th>
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
            	    <th><center>No</center></th>
                   <th><center>Nomor Faktur</center></th>
                    <th><center>Tanggal Faktur </center></th>
                    <th><center>Nomor Seri Faktur Pajak</center></th>
                    <th><center>Status Faktur Pajak</center></th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
                $qMaster = "SELECT
						A.id_tfk,
						A.id_f_p,
                        A.status_f_pajak,
                        A.nomor_seri,
						A.tanggal,
						A.keterangan,
						B.id_tfk,
						B.kode_tfk,
						B.tgl_tfk
					
					FROM
						faktur_pajak AS A
					LEFT JOIN transaksi_faktur AS B ON
						A.id_tfk = B.id_tfk
					WHERE
						A.id_tfk LIKE '%$search%'
					
					ORDER BY
						A.tanggal DESC";
		        $master	= $conn->prepare($qMaster);				
		        $master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['kode_tfk']); ?></td>
                	<td><?php echo($hasil['tgl_tfk']); ?></td>
                	<td><?php echo($hasil['nomor_seri']); ?></td>
                	<td><?php echo($hasil['status_f_pajak']); ?></td>
                	
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
