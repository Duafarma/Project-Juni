<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Report Pembayaran A.xls");
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
            	<th colspan="26">DATA Pembayaran</th>
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
                     <th><center>#</center></th>
                    <th>No. Faktur</th>
                    <th>Outlet</th>
                    <th>Tgl. Faktur</th>
                    <th>Jatuh Tempo</th>
                    <th>Tgl. Pembayaran</th>
                    <th>Total Tagihan</th>
                    
                    <th>Pemabayaran</th>
                    <th>Status</th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, A.tgl_limit, A.total_tfk, A.status_tfk, SUM(B.jumlah_pfk) AS bayar, B.tgl_pfk, C.nama_out FROM transaksi_faktur AS A LEFT JOIN pembayaran_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN outlet AS C ON A.id_out=C.id_out WHERE A.kode_tfk LIKE '%$cari%' OR C.nama_out LIKE '%$cari%' GROUP BY A.id_tfk ORDER BY A.tgl_tfk");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
				$a = $hasil ['total_tfk'];
				$b = $hasil['jumlah_pfk'];
				$c = $a - $b ;
				$d = $c;

			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['kode_tfk']); ?></td>
                	<td><?php echo($hasil['nama_out']); ?></td>
                	<td><?php echo($hasil['tgl_tfk']); ?></td>
                	<td><?php echo($hasil['tgl_limit']); ?></td>
                	 <td><?php echo($hasil['tgl_pfk']); ?></td>
                	<td><?php echo($hasil['total_tfk']); ?></td>
                	<td><?php echo($hasil['bayar']); ?></td>
                	<td><center><?php echo($status); ?></center></td>
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
