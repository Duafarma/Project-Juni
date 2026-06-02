<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_STOCKOPNAME.xls");
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
	 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Stockopname</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="10">REPORT STOCKOPNAME</th>
            </tr>
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                        <th><center>#</center></th>
                        <th><center>Tgl SO</center></th>
                        <th>Nama Produk</th>
                        <th>Kategori Produk</th>
                        <th>Kode Produk Jadi</th>

                        <th><center>Nomor Batch Inventory</center></th>
                        <th>Qty Inventory</th>
                        <th>Expired Date Inventory</th>
                        <th>Nomor Batch SO</th>
                        <th>Qty SO</th>
                        <th><center>Harga</center></th>
                        <th>Principle</th>
                        <th><center>Total</center></th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$grand_total = 0;
                $master	= $conn->prepare("SELECT A.id,A.nama_pro,A.no_bcode,C.kategori_obat,C.kode_produk_jadi,A.qty,A.bcode_so,A.qty_so, A.created_at, B.tgl_expired, D.harga_phg, E.nama_principle FROM so AS A
					LEFT JOIN produk_stokdetail AS B ON A.id_psd = B.id_psd
					LEFT JOIN produk AS C ON A.id_pro = C.id_pro
					LEFT JOIN produk_harga AS D ON C.id_pro = D.id_pro AND D.status_phg = 'Active'
					LEFT JOIN master_principle AS E ON C.nama_p = E.id_mp
					WHERE A.id ORDER BY A.created_at DESC, A.nama_pro DESC ");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['created_at']); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['kategori_obat']); ?></td>
                    <td><?php echo($hasil['kode_produk_jadi']); ?></td>
                    <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['qty']); ?></td>
                      <td><?php echo($hasil['tgl_expired']); ?></td>
                     <td><?php echo($hasil['bcode_so']); ?></td>
                     <td><?php echo($hasil['qty_so']); ?></td>
                     <td><?php echo($hasil['harga_phg']); ?></td>
                     <td><?php echo($hasil['nama_principle']); ?></td>
                     <td><?php $subtotal = $hasil['qty_so'] * $hasil['harga_phg']; $grand_total += $subtotal; echo(number_format($subtotal, 0, ',', '.')); ?></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="9"><strong>Grand Total</strong></td>
                    <td colspan="2"><strong><?php echo(number_format($grand_total, 0, ',', '.')); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
