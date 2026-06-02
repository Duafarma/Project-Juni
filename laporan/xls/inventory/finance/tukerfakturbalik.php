<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_tanda_terima_b.xls");
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
	$tgl1	= empty($pecah[1]) ? "" : "AND C.tgl_tfk>='$pecah[1]'"; 
	$tgl2	= empty($pecah[2]) ? "" : "AND C.tgl_tfk<='$pecah[2]'"; 


?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>REPORT TANDA TERIMA</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="18">REPORT TANDA TERIMA</th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                    <th><center>#</center></th>
                    <th>Nama Outlet</th>
                    <th>Nomer Faktur</th>
                    <th>Tanggal Tukar Faktur</th>
                    <th>Tanggal Terima</th>
                    <!--<th>Tanggal Dokumen Tuker Faktur</th>-->
                   
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT
                            					A.id_tfb,
                            					A.tanggal,
                            					A.tanggal_tf,
                            					C.kode_tfk,
                            					D.nama_out
                            					FROM
                            					     dokumen_tf_balik AS A
                            					LEFT JOIN dokumen_tf_balik_detail AS B ON
                            						A.id_tfb = B.id_tfb
                            					LEFT JOIN transaksi_faktur AS C ON
                                                    B.no_faktur = C.id_tfk 
                                                LEFT JOIN outlet AS D ON 
                                                    C.id_out = D.id_out
                            					WHERE
                            						(C.kode_tfk LIKE '%$cari%' OR D.nama_out LIKE '%$cari%') $tgl1 $tgl2
                            					ORDER BY
                            						A.tanggal ASC");
                            				
				// $master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['nama_out']); ?></center></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><?php echo($hasil['tanggal_tf']); ?></td>
                    <td><center><?php echo($hasil['tanggal']); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
