<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_transfer_retur.xls");
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
		$whereClause = "WHERE tt.kode_ttr LIKE '%$cari%' OR a2.nama_apl LIKE '%$cari%' OR a3.nama_apl LIKE '%$cari%'";
	}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Transfer Retur</title>
    </head>

    <body>
		<table>
        	<tr>
            	<th colspan="10">REPORT TRANSFER RETUR</th>
            </tr>
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                    <th><center>NO.</center></th>
                    <th>KODE TRANSFER</th>
                    <th>TANGGAL</th>
                    <th>TIPE</th>
                    <th>DARI</th>
                    <th>KE</th>
                    <th>STATUS</th>
                    <th>KODE PRODUK</th>
                    <th>NAMA PRODUK</th>
                    <th>JUMLAH</th>
                    <th>NO. BATCH</th>
                    <th>EXP. DATE</th>
                    <th>Kode Produk Jadi</th>
                    <th>Kategori Obat</th>

                    <th>ALASAN RETUR</th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$qMaster = "SELECT
								tt.id_ttr,
								tt.kode_ttr,
								tt.tgl_ttr,
								tt.tipe_ttr,
								tt.status_ttr,
								tt.ket_ttr,
								a2.nama_apl AS dari,
								a3.nama_apl AS ke,
								d.kode_pro,
								d.nama_pro,
								d.kode_produk_jadi,
								d.kategori_obat,
								ttd.jumlah_ttd,
								psd.no_bcode,
								psd.ed
							FROM
								transaksi_transferretur tt
							LEFT JOIN aplikasi a2 ON
								tt.id_app_from = a2.id_apl
							LEFT JOIN aplikasi a3 ON
								tt.id_app_to = a3.id_apl
							LEFT JOIN transaksi_transferreturdetail ttd ON
								tt.id_ttr = ttd.id_ttr
							LEFT JOIN inventory_retur psd ON
								ttd.id_i_r = psd.id_i_r
							LEFT JOIN produk d ON
								psd.id_pro = d.id_pro
							$whereClause
							ORDER BY
								tt.created_at DESC, tt.kode_ttr DESC";
				$master	= $conn->prepare($qMaster);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['kode_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['tgl_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['tipe_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['dari']); ?></center></td>
                	<td><center><?php echo($hasil['ke']); ?></center></td>
                	<td><center><?php echo($hasil['status_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['kode_pro']); ?></center></td>
					<td><center><?php echo($hasil['nama_pro']); ?></center></td>
					<td><center><?php echo($hasil['jumlah_ttd']); ?></center></td>
					<td><center><?php echo($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo($hasil['ed']); ?></center></td>
					<td><center><?php echo($hasil['kode_produk_jadi']); ?></center></td>
					<td><center><?php echo($hasil['kategori_obat']); ?></center></td>
					<td><center><?php echo($hasil['ket_ttr']); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
