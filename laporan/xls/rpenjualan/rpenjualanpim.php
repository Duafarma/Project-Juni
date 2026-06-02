<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_penjualan_PIM.xls");
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
            	<th colspan="18">REPORT PENJUALAN PIM</th>
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
                    <th><center>Tgl. Faktur</center></th>
                    <th>Kode RS</th>
                    <th>Nomor Faktur</th>
                    <th>Outlet</th>
                    <th>Alamat Outlet</th>
                    <th>Kabupaten Outlet</th>
                    <th>Kode Outlet</th>
                    <th>Kategori Outlet</th>
                    <th>Officer Code</th>
                    <th>Nama Barang</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Diskon</th>
                    <th>Total</th>
                    <th><center>Tipe Faktur Penjualan</center></th>
                    <th><center>Kategori Faktur</center></th>
                      <th><center>Gudang</center></th>
                    <th><center>Cabang</center></th>
                                        <th><center>Principle</center></th>

                   
                   
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$status	= 'Active';
				$qmaster = "SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, B.pajak_tfk,B.pajak_tfkt, B.kode_tfk, B.tgl_tfk, B.po_tfk, B.tglpo_tfk, B.tgl_limit, B.status_tfk,B.status_tfkkb, B.status_tfkkf, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak,  C.nama_pro, C.kategori_obat, C.kode_pro,C.kode_produk_jadi, D.nama_out,D.kode_out,D.kode_rs, D.id_kot, D.ofcode_out, E.no_bcode, E.tgl_expired, E.gudang,F.pengiriman_ola, G.kode_kot, H.nama_rkb FROM transaksi_fakturdetail_pim AS A LEFT JOIN transaksi_faktur_pim AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN outlet AS D ON B.id_out=D.id_out  LEFT JOIN produk_stokdetail AS E ON A.id_psd=E.id_psd LEFT JOIN outlet_alamat AS F ON D.id_out=F.id_out LEFT JOIN kategori_outlet AS G ON D.id_kot=G.id_kot LEFT JOIN regional_kabupaten AS H ON F.id_rkb=H.id_rkb WHERE A.id_tfd!='' $outlet $produk $tgl1 $tgl2 AND D.status_out=:status ORDER BY B.tgl_tfk DESC, CAST(B.kode_tfk AS UNSIGNED) DESC";
				$master	= $conn->prepare($qmaster);
				$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->bindParam(':status', $status, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['tglpo_tfk']); ?></center></td>
                    <td><?php echo($hasil['po_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['kode_rs']); ?></center></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                	<td><?php echo(strtoupper($hasil['nama_out'])); ?></td>
                     <td><?php echo($hasil['pengiriman_ola']); ?></td>
                     <td><?php echo($hasil['nama_rkb']); ?></td>
                    <td><?php echo($hasil['kode_out']); ?></td>
                    <td><?php echo($hasil['kode_kot']); ?></td>
                    <td><?php echo($hasil['ofcode_out']); ?></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                   
                    <td><?php echo($hasil['jumlah_tfd']); ?></td>
                    <td><?php echo($hasil['harga_tfd']); ?></td>
                    <td><?php echo($hasil['diskon_tfd']); ?>%</td>
                    <td><?php echo($hasil['total_tfd']); ?></td>
                    <td><center>A</center></td>
                    <td><center><?php echo($hasil['pajak_tfkt']); ?></center></td>
                     <td><center>Puri 1</center></td>
                    <td><center>Puri </center></td>
                                        <td><center>PIM</center></td>

				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
