<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=kwitansi dan tanda terima.xls");
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
	$search	= $data->cekcari($cari, '-', ' ');
	$pecah	= explode('_', $cari);
	// $outlet	= empty($pecah[0]) ? "" : "AND B.id_rute='$pecah[0]'"; 
	// $produk	= empty($pecah[1]) ? "" : "AND A.id_rute='$pecah[1]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Kwitansi</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="26">Data Kwitansi</th>
            </tr>
        
            <tr>
            	<td colspan="26"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>NO</center></th>
                    <th>Nomor Kwitansi</th>
                    <th>Nomor Faktur</th>
					<th>Nama Outlet</th>
                    <th>Tanggal</th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
				$master = $conn->prepare("
                                        SELECT 
                                        	A.id_finance, A.nomor, A.nama_outlet, A.tanggal_faktur,  
                                        	B.id_f_detail, B.no_kwitansi, B.id_finance, 
                                        	C.kode_tfk, D.nama_out 
                                        FROM finance AS A 
                                        LEFT JOIN finance_detail AS B ON A.id_finance = B.id_finance 
                                        
                                        LEFT JOIN (
                                        	SELECT id_tfk, kode_tfk, 'cendo' AS sumber FROM transaksi_faktur
                                        	UNION ALL
                                        	SELECT id_tfk, kode_tfk, 'pim' AS sumber FROM transaksi_faktur_pim
                                        ) AS C ON B.no_kwitansi = C.id_tfk
                                        
                                        LEFT JOIN outlet AS D ON A.nama_outlet = D.id_out 
                                        
                                        WHERE A.id_finance != '' 
                                        ORDER BY A.nomor DESC, A.tanggal_faktur DESC
                                        ");

				// $master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['nomor']); ?></td>
                	<td><?php echo($hasil['kode_tfk']); ?></td>
					<td><?php echo($hasil['nama_out']); ?></td>
                	<td><?php echo($hasil['tanggal_faktur']); ?></td>
                	        <td><?php echo(strtoupper($hasil['sumber'])); ?></td>

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
