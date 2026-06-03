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
	$kode	= (int)$secu->injection(@$_GET['key']);

	$read = $conn->prepare("SELECT id_tfm, id_tfk, kode_tfk, id_mr, ket_mr, subtot_tfm, ppn_tfm, total_tfm, created_at FROM transaksi_faktur_manual WHERE id_tfm=:id");
	$read->bindParam(':id', $kode, PDO::PARAM_INT);
	$read->execute();
	$view = $read->fetch(PDO::FETCH_ASSOC);
	if(!$view) { echo 'Data tidak ditemukan.'; exit; }
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Faktur Manual - <?php echo $view['kode_tfk']; ?></title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
        <style>
          table.tabel td, table.tabel th { border: none !important; }
          table.tabelinfo td { border: none !important; }
        </style>
    </head>
    <body style="font-family: Calibri, sans-serif; font-size: 12px;">
		<!-- <div style="margin-bottom: 10px;">
        	<div style="float:left; font-size: 48px; font-weight: bold; color: #FFA500;">Dua.</div>
            
            <div style="clear:both;"></div>
        </div> -->
    	<br style="clear:both;"/>
        </br>
        </br>
        </br>
        </br>
        </br>
        </br>

		<center><b style="font-size:14px;">SURAT PENGANTAR BARANG</b></center>
		<br/>

		<table style="width:100%; border-collapse:collapse; margin-bottom:10px; font-size:12px;">
			<tr>
				<td style="width:60%; vertical-align:top;">
					<div style="margin-bottom:3px;">Kepada Yth,</div>
					<table class="tabelinfo" style="font-family:Calibri,sans-serif; font-size:12px; border:none; border-collapse:collapse;">
						<tr>
							<td width="35%" style="border:none;">Nama Pelanggan</td>
							<td width="3%" style="border:none;"><center>:</center></td>
							<td width="62%" style="border:none;"><?php echo htmlspecialchars($view['ket_mr']); ?></td>
						</tr>
					</table>
				</td>
				<td style="width:40%; vertical-align:top; text-align:right; padding-right:20px;">
					<table style="border-collapse:collapse; font-size:12px; float:right;">
						<tr>
							<td style="width:80px; padding:2px 0;">Tanggal</td>
							<td style="width:15px; padding:2px 5px;">:</td>
							<td style="padding:2px 0;"><?php echo $date->tgl_indo(date('Y-m-d', strtotime($view['created_at']))); ?></td>
						</tr>
						<tr>
							<td style="padding:2px 0;">No SPB.</td>
							<td style="padding:2px 5px;">:</td>
							<td style="padding:2px 0;"><?php echo htmlspecialchars($view['kode_tfk']); ?></td>
						</tr>
					</table>
					<div style="clear:both;"></div>
				</td>
			</tr>
		</table>
		<br/>

		<table class="tabel" style="border-collapse:collapse; border:none;">
			<thead>
				<tr>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">NO</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">NAMA BARANG</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">NO. BATCH</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">EXP. DATE</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">KUANTITAS</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">HARGA</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">DISKON</th>
					<th style="border:none; border-bottom:1px solid #000; padding:4px 6px;">TOTAL</th>
				</tr>
			</thead>
			<tbody>
			<?php
				$stotal = 0;
				$nomor  = 1;
				$master = $conn->prepare("
					SELECT D.jumlah_tfmd, D.harga_tfmd, D.diskon_tfmd, D.total_tfmd,
					       P.nama_pro, P.berat_pro,
					       S.nama_spr,
					       B.no_bcode, B.tgl_expired
					FROM transaksi_faktur_manual_detail D
					LEFT JOIN produk P ON D.id_pro = P.id_pro
					LEFT JOIN satuan_produk S ON P.id_spr = S.id_spr
					LEFT JOIN produk_stokdetail B ON D.id_psd = B.id_psd
					WHERE D.id_tfm = :id
					ORDER BY D.id_tfmd ASC
				");
				$master->bindParam(':id', $kode, PDO::PARAM_INT);
				$master->execute();
				while($hasil = $master->fetch(PDO::FETCH_ASSOC)):
					$stotal += (int)$hasil['total_tfmd'];
			?>
				<tr>
					<td><center><?php echo $nomor++; ?></center></td>
					<td><?php echo htmlspecialchars($hasil['nama_pro']); ?> <small><?php echo htmlspecialchars($hasil['berat_pro'].' '.$hasil['nama_spr']); ?></small></td>
					<td><center><?php echo htmlspecialchars($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo substr($hasil['tgl_expired'], 0, 7); ?></center></td>
					<td><div align="right"><?php echo $data->angka($hasil['jumlah_tfmd']); ?></div></td>
					<td><div align="right"><?php echo $data->angka($hasil['harga_tfmd']); ?></div></td>
					<td><center><?php echo $hasil['diskon_tfmd']; ?>%</center></td>
					<td><div align="right"><?php echo $data->angka($hasil['total_tfmd']); ?></div></td>
				</tr>
			<?php endwhile; ?>
			</tbody>
		</table>
		<br/>

		<table style="width:50%; border-collapse:collapse; margin-bottom:15px; font-size:12px;">
			<tr>
				<td style="width:40%; text-align:left; padding:2px 5px;">Total 1</td>
				<td style="width:15%; text-align:left; padding:2px 5px;">Rp.</td>
				<td style="width:45%; text-align:right; padding:2px 5px;"><?php echo $data->angka($view['subtot_tfm']); ?></td>
			</tr>
			<tr>
				<td style="text-align:left; padding:2px 5px;">Potongan</td>
				<td style="text-align:left; padding:2px 5px;">Rp.</td>
				<td style="text-align:right; padding:2px 5px;"><?php echo $data->angka(0); ?></td>
			</tr>
			<tr>
				<td style="text-align:left; padding:2px 5px;"><b>Total</b></td>
				<td style="text-align:left; padding:2px 5px;"><b>Rp.</b></td>
				<td style="text-align:right; padding:2px 5px;"><b><?php echo $data->angka($view['total_tfm']); ?></b></td>
			</tr>
		</table>

		<br/>
		<table style="width:100%; border-collapse:collapse; font-size:12px; margin-top:20px;">
			<tr>
				<td style="width:30%; text-align:center;">Penerima</td>
				<td style="width:40%;"></td>
				<td style="width:30%; text-align:center;"></td>
			</tr>
			<tr style="height:60px;">
				<td></td>
				<td></td>
				<td></td>
			</tr>
			<tr>
				<td>
					<table style="font-size:12px;">
						<tr>
							<td>Nama</td>
							<td>&nbsp;:&nbsp;</td>
							<td style="border-bottom:1px solid #000; min-width:150px;">&nbsp;</td>
						</tr>
						
					</table>
				</td>
				<td></td>
				<td></td>
			</tr>
		</table>

		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
