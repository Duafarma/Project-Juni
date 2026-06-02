<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_backup_faktur_sales.xls");
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
	// Tampilkan semua backup yang berkaitan dengan sebelum revisi & delete
	$whereClause = 'WHERE tfj.status_tfk IN ("Tagihan","Delete")';
	if ($cari != '') {
		$whereClause .= " AND (tfj.kode_tfk LIKE '%$cari%' OR tfj.sj_tfk LIKE '%$cari%' OR o.nama_out LIKE '%$cari%' OR a.nama_adm LIKE '%$cari%' OR tfj.keterangan_revisi LIKE '%$cari%' OR tfj.backup_reason LIKE '%$cari%')";
	}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Backup Faktur Sales</title>
    </head>

    <body>
		<table>
        	<tr>
            	<th colspan="15">REPORT BACKUP FAKTUR SALES</th>
            </tr>
            <tr>
            	<td colspan="15"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                    <th><center>NO.</center></th>
                    <th>NO. FAKTUR</th>
                    <th>TANGGAL FAKTUR</th>
                    <th>OUTLET</th>
                    <th>NO. SJ</th>
					<th>STATUS</th>
					<th>BACKUP REASON</th>
                    <th>TOTAL FAKTUR</th>
                    <th>BACKUP BY</th>
                    <th>BACKUP AT</th>
                    <th>KETERANGAN REVISI</th>
                    <th>KODE PRODUK</th>
                    <th>NAMA PRODUK</th>
                    <th>NO. BATCH</th>
                    <th>EXP. DATE</th>
                    <th>JUMLAH</th>
                    <th>HARGA</th>
                    <th>DISKON (%)</th>
                    <th>TOTAL ITEM</th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$qMaster = "SELECT
								tfj.id_tfk_junk,
								tfj.kode_tfk,
								tfj.tgl_tfk,
								tfj.sj_tfk,
								tfj.status_tfk,
								tfj.backup_reason,
								tfj.total_tfk,
								tfj.backup_at,
								tfj.keterangan_revisi,
								o.nama_out,
								a.nama_adm,
								tfdj.jumlah_tfd,
								tfdj.harga_tfd,
								tfdj.diskon_tfd,
								p.kode_pro,
								p.nama_pro,
								psd.no_bcode,
								psd.tgl_expired
							FROM
								transaksi_faktur_junk tfj
							LEFT JOIN outlet o ON
								tfj.id_out = o.id_out
							LEFT JOIN adminz a ON
								tfj.backup_by = a.id_adm
							LEFT JOIN transaksi_fakturdetail_junk tfdj ON
								tfj.id_tfk_junk = tfdj.id_tfk_junk
							LEFT JOIN produk_stokdetail psd ON
								tfdj.id_psd = psd.id_psd
							LEFT JOIN produk p ON
								psd.id_pro = p.id_pro
							$whereClause
							ORDER BY
								tfj.backup_at DESC, tfj.kode_tfk DESC";
				$master	= $conn->prepare($qMaster);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$subtotal = $hasil['jumlah_tfd'] * $hasil['harga_tfd'];
					$diskon_amount = ($subtotal * $hasil['diskon_tfd']) / 100;
					$total_item = $subtotal - $diskon_amount;
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['kode_tfk']); ?></center></td>
                	<td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                	<td><center><?php echo($hasil['nama_out']); ?></center></td>
                	<td><center><?php echo($hasil['sj_tfk']); ?></center></td>
					<td><center><?php echo($hasil['status_tfk']); ?></center></td>
					<td><center><?php echo($hasil['backup_reason']); ?></center></td>
                	<td><center><?php echo($hasil['total_tfk']); ?></center></td>
                	<td><center><?php echo($hasil['nama_adm']); ?></center></td>
                	<td><center><?php echo($hasil['backup_at']); ?></center></td>
                	<td><center><?php echo($hasil['keterangan_revisi']); ?></center></td>
                	<td><center><?php echo($hasil['kode_pro']); ?></center></td>
					<td><center><?php echo($hasil['nama_pro']); ?></center></td>
					<td><center><?php echo($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
					<td><center><?php echo($hasil['jumlah_tfd']); ?></center></td>
					<td><center><?php echo($hasil['harga_tfd']); ?></center></td>
					<td><center><?php echo($hasil['diskon_tfd']); ?></center></td>
					<td><center><?php echo($total_item); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
