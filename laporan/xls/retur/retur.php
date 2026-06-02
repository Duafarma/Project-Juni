<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_retur.xls");
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
// 	$supp	= empty($pecah[0]) ? "" : "AND B.id_sup='$pecah[0]'"; 
// 	$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
// 	$tgl1	= empty($pecah[2]) ? "" : "AND C.tgl_tor>='$pecah[2]'"; 
// 	$tgl2	= empty($pecah[3]) ? "" : "AND C.tgl_tor<='$pecah[3]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Retur</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="17">REPORT RETUR</th>
            </tr>
            <tr>
            	<td colspan="17"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
               <th><center>#</center></th>
                    <th><center>Tgl. Retur</center></th>
                    <th>Keterangan Retur</th>
                    <th>Outlet</th>
                    <th>Nomor Retur</th>
                    <th>Nama Barang</th>
                    <th>Gudang</th>
                    <th>No. Batch</th>
                    <th><center>Exp. Date</center></th>
                    <th>Qty</th>
                    <th>Kode Produk Jadi</th>
                    <th>Kategori Produk</th>

    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.id_r_d, A.id_r, A.no_bcode, A.ed, A.created_at, A.gudang, A.jumlah, B.no_retur, B.keterangan, B.tanggal, C.nama_out,D.kode_produk_jadi,D.kategori_obat, D.nama_pro FROM retur_detail AS A INNER JOIN retur AS B ON A.id_r=B.id_r LEFT JOIN outlet AS C ON B.id_out=C.id_out LEFT JOIN produk AS D ON A.id_pro = D.id_pro WHERE A.id_r_d!='' ORDER BY B.tanggal DESC, A.created_at DESC");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){

			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['tanggal']); ?></center></td>
                    <td><?php echo($hasil['keterangan']); ?></td>
                    <td><?php echo($hasil['nama_out']); ?></td>
                    <td><?php echo($hasil['no_retur']); ?></td>
                    <td><center><?php echo($hasil['nama_pro']); ?></center></td>
                     <td><center><?php echo($hasil['gudang']); ?></center></td>
                    <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['ed']); ?></td>
                    <td><?php echo($hasil['jumlah']); ?></td>
                    <td><?php echo($hasil['kode_produk_jadi']); ?></td>
                    <td><?php echo($hasil['kategori_obat']); ?></td>

				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
		<?php $conn	= $base->open(); ?>
    </body>
<?php } ?>
</html>
 