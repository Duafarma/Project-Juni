<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_transfer_stok.xls");
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
	$cari	= $secu->injection(@$_GET['key']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$whereClause = '';
	if ($cari != '') {
		$pecah	= explode('_', $cari);
		$cariTipe	= "('" . implode("','",explode('-', @$pecah[0])) . "')";
		$cariBarang	= @$pecah[2];
		$tgl1	= @$pecah[3] . "-01";
		$tgl2	= @$pecah[4] . "-01";
		$whereClause = "WHERE (b.tgl_ttr BETWEEN '$tgl1' AND LAST_DAY('$tgl2')) AND d.nama_pro LIKE '%$cariBarang%' OR b.tipe_ttr IN $cariTipe";
	}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Transfer Stok</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="18">REPORT TRANSFER STOK</th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                    <th><center>NO.</center></th>
                    <th>TANGGAL</th>
                    <th>TIPE</th>
                    <th>KODE TRANSFER</th>
					<th>KATEGORI PRODUK</th>
					<th>KODE PRODUK JADI</th>
                    <th>KODE BARANG</th>
                    <th>NAMA BARANG</th>
                    <th>NO. BATCH</th>
                    <th>EXP. DATE</th>
                    <th>GUDANG</th>
                    <th>JUMLAH TRANSFER</th>
                    <th>KETERANGAN TRANSFER</th>
                    <th>TUJUAN TRANSFER</th>
                    <th>STATUS</th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$qMaster = "SELECT
								b.tgl_ttr,
								b.tipe_ttr,
								b.ket_ttr,
								b.kode_ttr,
								b.ket_ttr,
								d.id_pro,
								d.kode_pro,
								d.nama_pro,
								d.kategori_obat,
								d.kode_produk_jadi,
								c.no_bcode,
								c.tgl_expired,
								c.gudang,
								a.jumlah_ttd,
								b.status_ttr ,
								e.nama_apl
							FROM
								transaksi_transferstockdetail a
							LEFT JOIN transaksi_transferstock b ON
								a.id_ttr = b.id_ttr
							LEFT JOIN produk_stokdetail c ON
								a.id_psd = c.id_psd
							LEFT JOIN produk d ON
								c.id_pro = d.id_pro
							LEFT JOIN aplikasi e ON 
							    b.id_app_to = e.id_apl 
							$whereClause
							ORDER BY
                            b.tgl_ttr DESC, b.kode_ttr DESC";
				$master	= $conn->prepare($qMaster);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['tgl_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['tipe_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['kode_ttr']); ?></center></td>
					<td><center><?php echo($hasil['kategori_obat']); ?></center></td>
					<td><center><?php echo($hasil['kode_produk_jadi']); ?></center></td>
                	<td><center><?php echo($hasil['kode_pro']); ?></center></td>
					<td><center><?php echo($hasil['nama_pro']); ?></center></td>
					<td><center><?php echo($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
					<td><center><?php echo($hasil['gudang']); ?></center></td>
					<td><center><?php echo($hasil['jumlah_ttd']); ?></center></td>
					<td><center><?php echo($hasil['ket_ttr']); ?></center></td>
						<td><center><?php echo($hasil['nama_apl']); ?></center></td>
					<td><center><?php echo($hasil['status_ttr']); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
