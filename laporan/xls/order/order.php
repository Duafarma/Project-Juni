<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Order_A.xls");
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
	$outlet	= empty($pecah[0]) ? "" : "AND B.id_out='$pecah[0]'"; 
	$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
	$tgl1	= empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'"; 
	$tgl2	= empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Penjualan</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="18">Order</th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Tgl. PO</center></th>
                    <th>Nomor PO</th>
                    <th><center>Supplier</center></th>
                    <th>Nama Barang</th>
                    <th>Satuan Barang</th>
                    <th>Qty</th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor = 1;
                $master = $conn->prepare("SELECT A.id_tod, E.id_tor, A.id_tor, E.tgl_tor, E.kode_tor, A.jumlah_tod, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr 
                    FROM transaksi_orderdetail AS A 
                    LEFT JOIN produk AS B ON A.id_pro = B.id_pro 
                    LEFT JOIN kategori_produk AS C ON B.id_kpr = C.id_kpr 
                    LEFT JOIN satuan_produk AS D ON B.id_spr = D.id_spr 
                    LEFT JOIN transaksi_order AS E ON A.id_tor = E.id_tor 
                    WHERE E.id_tor IS NOT NULL 
                    ORDER BY E.kode_tor DESC");
                $master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				   
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['tgl_tor']); ?></center></td>
                    <td><?php echo($hasil['kode_tor']); ?></td>
                    <td><center><?php echo($hasil['nama_spr']); ?></center></td>
                    <td><center><?php echo($hasil['nama_pro']); ?></center></td>
                    <td><?php echo($hasil['satuan_kpr']); ?></td>
                     <td><?php echo($hasil['jumlah_tod']); ?></td>
    
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
