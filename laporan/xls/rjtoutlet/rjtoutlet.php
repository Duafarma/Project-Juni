<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_jatuhtempo_outlet.xls");
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
	$search	= $data->cekcari($pecah[0], '-', ' ');
	$tgl1	= empty($pecah[1]) ? "" : "AND A.tgl_tfk>='$pecah[1]'"; 
	$tgl2	= empty($pecah[2]) ? "" : "AND A.tgl_tfk<='$pecah[2]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Jatuh Tempo Outlet</title>
    </head>
    <body>
		<table>
        	<tr>
            	<th colspan="8">REPORT JATUH TEMPO OUTLET</th>
            </tr>
            <tr>
            	<td colspan="8"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th><div align="left">NO. FAKTUR</div></th>
                    <th><div align="left">OUTLET</div></th>
                    <th><div align="center">TGL. FAKTUR</div></th>
                    <th><div align="left">NO. PO</div></th>
                    <th><div align="right">TOTAL</div></th>
                    <th><div align="left">STATUS</div></th>
                    <th><div align="left">JATUH TEMPO</div></th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.kode_tfk, A.total_tfk, A.tgl_tfk, A.tgl_limit, A.status_tfk, A.po_tfk, B.nama_out FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE (A.kode_tfk LIKE '%$search%' OR B.nama_out LIKE '%$search%') $tgl1 $tgl2 ORDER BY A.tgl_limit ASC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['kode_tfk']); ?></td>
                	<td><?php echo($hasil['nama_out']); ?></td>
                	<td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                	<td><?php echo($hasil['po_tfk']); ?></td>
                	<td><div align="right"><?php echo($hasil['total_tfk']); ?></div></td>
                	<td><?php echo($status); ?></td>
                	<td><?php echo($hasil['tgl_limit']); ?></td>
                </tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
