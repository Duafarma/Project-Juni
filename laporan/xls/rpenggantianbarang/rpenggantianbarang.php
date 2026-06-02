<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_penggantian_barang_pusat.xls");
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
            	<th colspan="18">REPORT PENGGANTIAN BARANG</th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                <th><center>#</center></th>
                    <th>Nomor Penggantian Barang</th>
                    <th>Keterangan </th>
                    <th><center>Tgl. Faktur</center></th>
                    <th>Outlet</th>
                    <th>No Fkt Penjualan</th>
                    <th>Nama Barang</th>
                                        <th>Kategori Obat</th>

                                        <th>Kode Produk Jadi</th>

                    <th>No. Batch</th>
                    <th><center>Exp. Date</center></th>
                    <th>Qty</th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT  
                                            A.jumlah_tfd, 
                                            A.harga_tfd, 
                                            A.diskon_tfd, 
                                            A.total_tfd, 
                                            B.kode_tfkk,
                                            B.tgl_tfk,
                                            B.po_tfk, 
                                            C.nama_pro, 
                                            C.kode_pro, 
                                            C.kode_produk_jadi,
                                            C.kategori_obat,
                                            D.nama_out, 
                                            D.ofcode_out, 
                                            E.no_bcode, 
                                            E.tgl_expired,
                                            F.kode_tfk 
                                            FROM transaksi_faktur_penggantian_barang_detail AS A LEFT JOIN transaksi_faktur_penggantian_barang AS B ON A.id_tfk=B.id_tfk 
                                            LEFT JOIN produk AS C ON A.id_pro=C.id_pro 
                                            LEFT JOIN outlet AS D ON B.id_out=D.id_out 
                                            LEFT JOIN produk_stokdetail AS E ON A.id_psd=E.id_psd 
                                            LEFT JOIN transaksi_faktur AS F ON B.no_tfk_penjualan=F.id_tfk
                                            WHERE A.id_tfd!='' $outlet $produk $tgl1 $tgl2 ORDER BY B.tgl_tfk DESC,
                                            B.kode_tfkk DESC");
				// $master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['kode_tfkk']); ?></center></td>
                    <td><?php echo($hasil['po_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><?php echo($hasil['nama_out']); ?></td>
                     <td><?php echo($hasil['nama_pro']); ?></td>
                                       <td><?php echo($hasil['kategori_obat']); ?></td>
                     <td><?php echo($hasil['kode_produk_jadi']); ?></td>

                    <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['tgl_expired']); ?></td>
                    <td><?php echo($hasil['jumlah_tfd']); ?></td>
                    
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
