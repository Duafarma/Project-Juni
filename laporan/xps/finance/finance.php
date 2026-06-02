<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	require_once('../../../config/connection/connection.php');
	require_once('../../../config/connection/security.php');
	require_once('../../../config/function/data.php');
	require_once('../../../config/function/date.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$date	= new Date;
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$kode	= $secu->injection(@$_GET['key']);
	$read	= $conn->prepare("SELECT tfkk.*,
										o.nama_out,
										-- c.kode_tfk,
										-- c.total_tfk,
										-- a.nomor_faktur,
										-- c.kode_tfk,
										tfkk.nomor, 
										tfkk.tanggal_faktur
									FROM
										finance tfkk
									LEFT JOIN outlet o ON
										tfkk.nama_outlet = o.id_out
									-- LEFT JOIN transaksi_faktur c ON 
									-- 	tfkk.nomor_faktur = c.id_tfk
									-- LEFT JOIN adminz a ON
									-- 	tfkk.created_by = a.id_adm
										WHERE tfkk.id_finance=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	//$limit	= $date->oprPeriode("Y-m-d", "+$view[top_odi] DAY", $view['tgl_tfk']);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Untitled Document</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>


    <body>
		<div>
        	<div style="float:left; font-size:60px;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
            <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        	<div align="right" style="font-size:10px;"><?php echo($data->sistem('alamat_sis')); ?></div>
        	<div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        </div>
    	<br />
		<hr class="garis" />
        <div style="text-align:center; font-weight:bold; margin:10px 0px 10px 0px;">TANDA TERIMA FAKTUR</div>
        <div style="text-align:center; font-weight:bold;"><?php echo($date->tgl_indo($view['tanggal_faktur'])); ?></div>
        <div style="text-align:center; font-weight:bold; margin:10px 0px 10px 0px;"><?php echo($view['nama_out']); ?></div>

		<div style="text-align:center; font-weight:bold; margin-bottom:20px;"></div>

		<!-- <table width="100%" style="font-family:Arial, Helvetica, sans-serif; font-size:10px;">
        	<tr>
        		<td width="50%"></td>
        		<td width="15%">Tanggal</td>
        		<td width="3%"><center>:</center></td>
        		<td width="32%"><?php echo($date->tgl_indo($view['tgl_tfk'])); ?></td>
        	</tr>
        	<tr>
        		<td><div align="left">Kepada Yth,</div></td>
        		<td>Faktur No.</td>
        		<td><center>:</center></td>
        		<td><?php echo($view['kode_tfk']); ?></td>
        	</tr>
        </table> -->
    	<!-- <table class="tabelinfo">
        	<tr>
            	<td width="35%">NAMA PELANGGAN</td>
                <td width="3%"><center>:</center></td>
           		<td width="62%"><?php echo($view['nama_out']); ?></td>
            </tr>
        	<tr>
            	<td>ALAMAT KIRIM</td>
                <td><center>:</center></td>
           		<td><?php echo($view['pengiriman_ola']); ?></td>
            </tr>
        	<tr>
            	<td>NPWP</td>
                <td><center>:</center></td>
           		<td><?php echo($view['npwp_out']); ?></td>
            </tr>
        	<tr>
            	<td>NO. PO</td>
                <td><center>:</center></td>
           		<td><?php echo($view['po_tfk']); ?></td>
            </tr>
        </table> -->
		<p></p>
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th style="font-size:14px;" rowspan="2">NO</th>
					<th style="font-size:14px;" rowspan="2">Nomor PO</th>
                    <th style="font-size:14px;" rowspan="2">Nomer Faktur</th>
                    <th style="font-size:14px;" colspan="1">Tanggal Faktur</th>
                    <th style="font-size:14px;" rowspan="2">Nominal Faktur</th>
                    <th style="font-size:14px;" rowspan="2">Penerima Tukar Faktur dan Stempel Outlet</th>
                    <!-- <th rowspan="2">Stempel Outlet</th> -->
                    <!-- <th rowspan="2">HARGA</th>
                    <th rowspan="2">DISKON</th>
                    <th rowspan="2">TOTAL</th> -->
				</tr>
            	
    		</thead>
            <tbody>
            <?php

				$nomor	= 1;
			$master	= $conn->prepare("
	SELECT tfkk.*,
		o.nama_out,
		d.kode_tfk,
		d.total_tfk,
		d.po_tfk,
		d.tgl_tfk,
		tfkk.nomor,
		tfkk.tanggal_faktur
	FROM finance tfkk
	LEFT JOIN outlet o ON tfkk.nama_outlet = o.id_out
	LEFT JOIN finance_detail c ON tfkk.id_finance = c.id_finance
	LEFT JOIN (
		SELECT id_tfk, kode_tfk, total_tfk, po_tfk, tgl_tfk FROM transaksi_faktur
		UNION ALL
		SELECT id_tfk, kode_tfk, total_tfk, po_tfk, tgl_tfk FROM transaksi_faktur_pim
	) d ON d.id_tfk = c.no_kwitansi
	WHERE tfkk.id_finance = :kode
");

				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					// $subtot	= $hasil['jumlah_tfd'] * $hasil['harga_tfd'];
					// $diskon	= ($subtot * $hasil['diskon_tfd']) / 100;
					// $total	= $subtot - $diskon;
					// $stotal	+= $total;
			?>
            	<tr>
                	<td style="font-size:14px;">
						<center><?php echo($nomor); ?></center>
					</td>
					<td style="font-size:14px;">
						<center><?php echo($hasil['po_tfk']); ?></center>
					</td>
                	<td style="font-size:14px;">
						<center><?php echo($hasil['kode_tfk']); ?></center>
					</td>
                	<td style="font-size:14px;">
						<center><?php echo($hasil['tgl_tfk']); ?></center>
					</td>
					<td style="font-size:14px;">
						<div align="center">Rp. <?php echo($data->angka($hasil['total_tfk'])); ?></div>
					</td>
					<td>

					</td>
					
					<!-- <td>

					</td> -->
					<!-- <td>

					</td> -->
                	<!-- <td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                	<td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                	<td><center><?php echo($hasil['diskon_tfd']); ?>%</center></td>
                	<td><div align="right"><?php echo($data->angka($total)); ?></div></td> -->
                </tr>
				<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
		<br />
        
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
