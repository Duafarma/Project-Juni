<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_penyiapan_barang.xls");
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
        <title>Report Dokumen</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="7">Report Penyiapan Barang</th>
            </tr>
            <tr>
            	<td colspan="7"></td>
            </tr>
        </table>
        <tr>
            Tanggal : <?php echo($date->getHari(date('Y-m-d')).', '.$date->tgl_indo(date('Y-m-d'))); ?>
        </tr>
    	<table border="1">
        	<thead>
            <tr>
                    <th><center>#</center></th>
                    <th><center>Nama Outlet</center></th>
                    <th>Nomor Faktur</th>
                    <th>Tanggal Faktur</th>
                    <th>Jam</th>
                    <th>Jumlah Packing</th>
                    <th>Ceklist</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>TTD Penerima</th>

                   
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
                $master	= $conn->prepare("SELECT * FROM (
					SELECT B.created_at, B.kode_tfk, B.tgl_tfk, B.status_tfkkb, B.status_tfkkf, B.po_tfk, B.status_dokumen, B.status_f_pajak, B.status_failing, B.tglpo_tfk, B.tgl_limit, B.status_tfk, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, D.nama_out, D.ofcode_out
					FROM transaksi_faktur AS B LEFT JOIN outlet AS D ON B.id_out=D.id_out
					WHERE B.tgl_tfk >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND B.kode_tfk LIKE '%$cari%'
					UNION ALL
					SELECT B.created_at, B.kode_tfk, B.tgl_tfk, B.status_tfkkb, B.status_tfkkf, B.po_tfk, B.status_dokumen, B.status_f_pajak, B.status_failing, B.tglpo_tfk, B.tgl_limit, B.status_tfk, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal2) AS jarak, D.nama_out, D.ofcode_out
					FROM transaksi_faktur_pim AS B LEFT JOIN outlet AS D ON B.id_out=D.id_out
					WHERE B.tgl_tfk >= DATE_SUB(NOW(), INTERVAL 1 DAY) AND B.kode_tfk LIKE '%$cari%'
				) AS combined ORDER BY tgl_tfk DESC, CAST(kode_tfk AS UNSIGNED) DESC");
				$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->bindParam(':tanggal2', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo(strtoupper($hasil['nama_out'])); ?></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['created_at']); ?></center></td>
                    <td></td>
                    <td></td>
                    <td> Kirim Barang</td>
                    <td></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
