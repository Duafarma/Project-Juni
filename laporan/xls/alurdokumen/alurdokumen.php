<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Alur Dokumen Faktur .xls");
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
    $cari	= $secu->injection(@$_GET['caridata']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
// 	$cari	= $secu->injection(@$_GET['key']);
// 	$search	= $data->cekcari($cari, '-', ' ');
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Outlet</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="26">DATA Alur Dokumen</th>
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
                    <th rowspan="2"><center>#</center></th>
                    <th rowspan="2">Tanggal Faktur</th>
                    <th rowspan="2"><center> Faktur Terbentuk</center></th>
                    <!--<th colspan="2"><center>Faktur Terkirim</center></th>-->

                    <th colspan="2"><center>Faktur Kembali</center></th>
                    <th colspan="2"><center>Filing Faktur</center></th>
                    <th colspan="2"><center>Buat PPN</center></th>
                    <th colspan="2"><center>Upload PPN</center></th>
                    <!--<th colspan="2"><center>Pemberkasan</center></th>-->
                    <!--<th colspan="2"><center> TF</center></th>-->
                    <!--<th colspan="3"><center>Pembayaran</center></th>-->

                </tr>
                <tr>

                    <th><center>Sudah</center></th>
                    <th><center>Belum</center></th>

                    <th><center>Sudah</center></th>
                    <th><center>Belum</center></th>

                    <th><center>Sudah</center></th>
                    <th><center>Belum</center></th>

                    <th><center>Sudah</center></th>
                    <th><center>Belum</center></th>

                    <!--<th><center>Sudah</center></th>-->
                    <!--<th><center>Belum</center></th>-->
                    

                    <!--<th><center>Sudah</center></th>-->
                    <!--<th><center>Belum</center></th>-->

                    <!--<th><center>Lunas</center></th>-->
                    <!--<th><center>Sebagian</center></th>-->
                    <!--<th><center>Belum</center></th>-->
                   
                   
                   
                </tr>
            </thead>
            <tbody>
			<?php
				$nomor	= 1;
				$pecah	= explode('_', $cari);
        		$cari	= $data->cekcari($pecah[0], '-', ' ');
        		$tgl1	= empty($pecah[1]) ? "" : "AND tgl_tfk>='$pecah[1]'"; 
        		$tgl2	= empty($pecah[2]) ? "" : "AND tgl_tfk<='$pecah[2]'"; 
            	$qMaster = "SELECT
                                COUNT(*) AS jumlah,  
            						id_tfk,
            						tgl_tfk,
                                    status_balik
            					FROM
            						transaksi_faktur
            					WHERE
            						tgl_tfk LIKE '%$cari%' $tgl1 $tgl2
            					GROUP BY
            						tgl_tfk DESC";				
	        	$master	= $conn->prepare($qMaster);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				    
        
                    $belumbalik		      		= $data->belumbalik($hasil['tgl_tfk']);
        			$sudahbalik		      		= $data->sudahbalik($hasil['tgl_tfk']);
        
                    $belumfiling		    	= $data->belumfiling($hasil['tgl_tfk']);
        			$sudahfiling		    	= $data->sudahfiling($hasil['tgl_tfk']);
        
                    $beluminputpajak		    = $data->beluminputpajak($hasil['tgl_tfk']);
        			$sudahnputpajak		  		= $data->sudahinputpajak($hasil['tgl_tfk']);
        			
        			$uploadpajak				= $data->uploadpajak($hasil['tgl_tfk']);
        			$sudahuploadpajak			= $data->sudahuploadpajak($hasil['tgl_tfk']);
        
        // 			$belumpemberkasan		    = $data->belumpemberkasan($hasil['tgl_tfk']);
        // 			$siappemberkasan			= $data->siappemberkasan($hasil['tgl_tfk']);
        
        // 			$selesaitf					= $data->selesaitf($hasil['tgl_tfk']);
        // 			$belumtf					= $data->belumtf($hasil['tgl_tfk']);
        
        // 			$sudahpembayaran			= $data->sudahpembayaran($hasil['tgl_tfk']);
        // 			$belumpembayaran			= $data->belumpembayaran($hasil['tgl_tfk']);
        // 			$bayarsebagian				= $data->bayarsebagian($hasil['tgl_tfk']);
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo ($hasil['tgl_tfk']) ?></center></td>
                	<td><?php echo($hasil['jumlah']); ?></td>
                	<td><?php echo($sudahbalik); ?></td>
                	<td><?php echo($belumbalik); ?></td>
                 	<td><?php echo($sudahfiling); ?></td>
                 	<td><?php echo($belumfiling); ?></td>
                 	<td><?php echo($sudahnputpajak); ?></td>
                 	<td><?php echo($beluminputpajak); ?></td>
                 	<td><?php echo($sudahuploadpajak); ?></td>
                 	<td><?php echo($uploadpajak); ?></td>

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
