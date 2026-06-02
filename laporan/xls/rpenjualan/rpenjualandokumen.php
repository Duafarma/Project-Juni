<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_dokumen.xls");
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
	$cari	= $data->cekcari($pecah[0], '-', ' ');
	$tgl1	= empty($pecah[1]) ? "" : "AND A.tgl_tfk>='$pecah[1]'"; 
	$tgl2	= empty($pecah[2]) ? "" : "AND A.tgl_tfk<='$pecah[2]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Dokumen</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="18">REPORT DOKUMEN FAKTUR </th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            <tr>
                    <th><center>#</center></th>
                    <th><center>Nama Outlet</center></th>
                    <th>Nomor Faktur</th>
                    <th>Tanggal Faktur</th>
                    <th>Total Faktur</th>
                    <th><center>Kode Outlet</center></th>
                    <th>No. Po</th>
                    <th>Tgl. Po</th>
                    <th>Status Pengirman Barang</th>
                    <th>Status Dokumen Balik</th>
                    <th>Status Dokumen Failing</th>
                    <th>Status Faktur Pajak</th>
                    <th>Status Tukar Faktur</th>
                    <th>Tanggal Tanda Terima TF</th>
                    <th><center>Status Pembayaran</center></th>
                    <th><center>Jatuh Tempo Pembayaran</center></th>
                    <th><center>Remaining</center></th>
                </tr>
    		</thead>
            <tbody>
           <?php
                $nomor = 1;
                $qmaster = "SELECT A.id_tfk, 
                                    A.kode_tfk, 
                                A.tgl_tfk,
                                A.tglpo_tfk,
                                A.status_tfkkb,
                                A.po_tfk,
                                A.status_tfkkf, 
                                A.status_dokumen,
                                A.status_f_pajak, 
                                A.status_failing, 
                                A.total_tfk,
                                A.tgl_limit, 
                                A.status_tfk, 
                                B.nama_out, 
                                B.kode_rs,
                                F.tanggal_tf, 
                                D.nama_adm 
                        FROM transaksi_faktur AS A 
                        LEFT JOIN outlet AS B ON A.id_out = B.id_out 
                        LEFT JOIN transaksi_faktur_kirim_f AS C ON A.id_tfk = C.id_tfk 
                        LEFT JOIN adminz AS D ON C.id_adm = D.id_adm 
                        LEFT JOIN dokumen_tf_balik_detail AS E ON A.id_tfk = E.no_faktur
                        LEFT JOIN dokumen_tf_balik AS F ON E.id_tfb = F.id_tfb
                        WHERE (A.kode_tfk LIKE '%$cari%' OR B.nama_out LIKE '%$cari%') 
                          AND A.tgl_tfk >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR) 
                          $tgl1 $tgl2 
                        ORDER BY A.tgl_tfk DESC, A.kode_tfk DESC";

                $master	= $conn->prepare($qmaster);
				// $master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo(strtoupper($hasil['nama_out'])); ?></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['total_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['kode_rs']); ?></center></td>
                    <td><center><?php echo($hasil['po_tfk']); ?></center></td>
                                        <td><center><?php echo($hasil['tglpo_tfk']); ?></center></td>

                    <td><?php echo($hasil['status_tfkkb']); ?></td>
                     <td><?php echo($hasil['status_dokumen']); ?></td>
                     <td><?php echo($hasil['status_failing']); ?></td>
                    <td><?php echo($hasil['status_f_pajak']); ?></td>
                    <td><?php echo($hasil['nama_adm']); ?></td>
                    <td><?php echo($hasil['tanggal_tf']); ?></td>
                    <td><center><?php echo($status); ?></center></td>
                    <td><center><?php echo($hasil['tgl_limit']); ?></center></td>
                    <td><?php echo($hasil['jarak']); ?></td>
                    <td><?php echo($hasil['updated_at']); ?></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
