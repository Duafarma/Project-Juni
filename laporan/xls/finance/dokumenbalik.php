<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    header("Content-Type: application/vnd.ms-excel");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Filing.xls");
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
	$cari	= $data->cekcari($pecah[0], '-', ' ');
	$tgl1	= empty($pecah[1]) ? "" : "AND C.created_at>='$pecah[1]'"; 
	$tgl2	= empty($pecah[2]) ? "" : "AND C.created_at<='$pecah[2]'"; 

?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Filing</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="26">Data Filing</th>
            </tr>
        
            <tr>
            	<td colspan="26"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>NO</center></th>
                    <th>Nomor Faktur</th>
					<th>Nama Outlet</th>
                    <th>Status</th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
			    $qMaster = "SELECT
    						A.id_tfk,
    						A.kode_tfk,
    						C.created_at,
    						A.tgl_tfk,
    						A.status_dokumen,
    						A.status_failing,
    						B.nama_out
                          
    					FROM
    						transaksi_faktur AS A
    					LEFT JOIN outlet AS B ON
    						A.id_out = B.id_out
    					INNER JOIN dokumen_failing_detail AS C ON
    					    A.id_tfk = C.no_faktur
    					WHERE
    					    C.created_at LIKE '%$cari%' $tgl1 $tgl2";
				$master	= $conn->prepare($qMaster);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['kode_tfk']); ?></td>
					<td><?php echo($hasil['nama_out']); ?></td>
                	<td><?php echo($hasil['status_failing']); ?></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
		<?php $conn	= $base->close(); ?>

    </body>
<?php } ?>
</html>
