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
	$read	= $conn->prepare("SELECT tfj.kode_tfk, tfj.tgl_tfk, tfj.total_tfk, tfj.po_tfk, tfj.backup_at, tfj.backup_by, tfj.keterangan_revisi, tfj.status_tfk, tfj.backup_reason, o.resmi_out, o.nama_out, o.npwp_out, oa.pengiriman_ola, a.nama_adm FROM transaksi_faktur_junk AS tfj LEFT JOIN outlet AS o ON tfj.id_out=o.id_out LEFT JOIN outlet_alamat AS oa ON o.id_out=oa.id_out LEFT JOIN adminz AS a ON tfj.backup_by=a.id_adm WHERE tfj.id_tfk_junk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	//$limit	= $date->oprPeriode("Y-m-d", "+$view[top_odi] DAY", $view['tgl_tfk']);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Backup Faktur Sales</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>

    <body>
		<div>
        	<div style="float:left; font-size:60px;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
            <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        	<div align="right" style="font-size:10px;"><?php echo substr($data->sistem('alamat_sis'),0,69); ?></div>
        	<div align="right" style="font-size:10px;"><?php echo substr($data->sistem('alamat_sis'),70,120); ?></div>
        	<div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        	<div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
        	<div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
        	 <div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
        </div>
    	<br />
		
		<!-- Header untuk Backup -->
		<?php
			$headerTitle = 'BACKUP FAKTUR PENJUALAN';
			if (isset($view['backup_reason']) && stripos($view['backup_reason'],'delete') !== false) {
				$headerTitle = 'BACKUP FAKTUR PENJUALAN DI DELETE';
			} elseif (isset($view['backup_reason']) && stripos($view['backup_reason'],'before edit') !== false) {
				$headerTitle = 'BACKUP FAKTUR PENJUALAN SEBELUM DI REVISI';
			} elseif (isset($view['status_tfk']) && $view['status_tfk'] === 'Delete') {
				$headerTitle = 'BACKUP FAKTUR PENJUALAN DI DELETE';
			}
		?>
		<div style="background-color: #f8f9fa; border: 2px solid #dc3545; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
			<h3 style="color: #dc3545; margin: 0; text-align: center;"><?php echo htmlspecialchars($headerTitle); ?></h3>
			<p style="margin: 5px 0; font-size: 12px; text-align: center;">
				<strong>Dibuat Oleh:</strong> <?php echo($view['nama_adm']); ?> | 
				<strong>Dibuat Pada:</strong> <?php echo($date->tgl_indo(substr($view['backup_at'], 0, 10)) . ' ' . substr($view['backup_at'], 11, 8)); ?>
			</p>
			<?php if (!empty($view['keterangan_revisi'])): ?>
			<p style="margin: 5px 0; font-size: 12px; text-align: center;">
				<strong>Keterangan Revisi:</strong> <?php echo($view['keterangan_revisi']); ?>
			</p>
			<?php endif; ?>
		</div>

		<table width="100%" style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
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
        </table>
    	<table class="tabelinfo " style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
        	<tr>
            	<td width="35%">NAMA PELANGGAN</td>
                <td width="3%"><center>:</center></td>
           		<td width="62%"><?php echo($view['nama_out']); ?></td>
            </tr>
            <tr>
            	<td width="35%">NAMA OUTLET</td>
                <td width="3%"><center>:</center></td>
           		<td width="62%"><?php echo($view['resmi_out']); ?></td>
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
        </table>
		<p></p>
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th rowspan="2">NO</th>
                    <th rowspan="2">NAMA BARANG</th>
                    <th colspan="2">SEDIAAN</th>
                    <th rowspan="2">NO. BATCH</th>
                    <th rowspan="2">EXP. DATE</th>
                    <th rowspan="2">KUANTITAS</th>
                    <th rowspan="2">HARGA</th>
                    <th rowspan="2">DISKON</th>
                    <th rowspan="2">TOTAL</th>
				</tr>
            	<tr>
                    <th>SEDIAAN</th>
                    <th>UKURAN</th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$subtot	= 0;
				$diskon	= 0;
				$total	= 0;
				$stotal	= 0;
				$nomor	= 1;
				$master	= $conn->prepare("SELECT tfdj.jumlah_tfd, tfdj.harga_tfd, tfdj.diskon_tfd, psd.no_bcode, psd.tgl_expired, p.kode_pro, p.nama_pro, p.berat_pro, kp.nama_kpr, sp.nama_spr FROM transaksi_fakturdetail_junk AS tfdj LEFT JOIN produk_stokdetail AS psd ON tfdj.id_psd=psd.id_psd LEFT JOIN produk AS p ON psd.id_pro=p.id_pro LEFT JOIN kategori_produk AS kp ON p.id_kpr=kp.id_kpr LEFT JOIN satuan_produk AS sp ON p.id_spr=sp.id_spr WHERE tfdj.id_tfk_junk=:kode");
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$subtot	= $hasil['jumlah_tfd'] * $hasil['harga_tfd'];
					$diskon	= ($subtot * $hasil['diskon_tfd']) / 100;
					$total	= $subtot - $diskon;
					$stotal	+= $total;
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['nama_pro']); ?></td>
                	<td><?php echo($hasil['nama_kpr']); ?></td>
                	<td><?php echo("$hasil[berat_pro] $hasil[nama_spr]"); ?></td>
                	<td><center><?php echo($hasil['no_bcode']); ?></center></td>
                	<td><center><?php echo(substr($hasil['tgl_expired'], 0, 7)); ?></center></td>
                	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                	<td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                	<td><center><?php echo($hasil['diskon_tfd']); ?>%</center></td>
                	<td><div align="right"><?php echo($data->angka($total)); ?></div></td>
                </tr>
			<?php
			    $nomor++;
            	}
				$ppn	= ($stotal * 11) / 100;
				$gtotal	= round(($stotal + $ppn), 0);
			?>
            </tbody>
        </table>
		<br />
        <div style="width:100%;">
            <div style="width:45%; display:inline-block;" align="left">
			<div style="margin-bottom:50px;"></div>
		
			<br />
	
            </div>
            <div style="width:45%; display:inline-block; float:right;">
				<div align="right">
                <table width="80%" style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
                    <tr>
                        <td><div align="left">Total 1</div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka($stotal)); ?></div></td>
                    </tr>
                    <tr>
                        <td><div align="left">Potongan</div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka(0)); ?></div></td>
                    </tr>
                    <tr>
                        <td><div align="left">PPN <span style="float:right;">11%</span></div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka($ppn)); ?></div></td>
                    </tr>
                    <tr>
                        <td colspan="3"><div style="background:#666666; width:100%; height:1px;"></div></td>
                    </tr>
                    <tr>
                        <td><div align="left"><b>Total Faktur</b></div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left; font-weight:bold;">Rp.</span><b><?php echo($data->angka($view['total_tfk'])); ?></b></div></td>
                    </tr>
                </table>
                </div>
            	<!--<div style="min-height:30px; height:auto; border:solid 1px #666666; text-align:center; font-weight:bold; margin-top:10px; line-height:25px; font-size:12px;">Terbilang : # <?php //echo($data->terbilang($gtotal)); ?> Rupiah #</div>-->
            	
            </div>
		</div>

		<script type="text/javascript">window.print(); </script>
    </body>
<?php } ?>
</html>
