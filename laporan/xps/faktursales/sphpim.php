<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=SPH.xls");
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
	$read	= $conn->prepare("SELECT A.kode_tfk, A.tgl_tfk, A.total_tfk, A.tgl_limit, A.po_tfk, A.hargapim, TIMESTAMPDIFF(DAY, A.tgl_tfk, A.tgl_limit) AS jarak, B.resmi_out, B.nama_out, B.npwp_out, C.pengiriman_ola FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_alamat AS C ON B.id_out=C.id_out WHERE A.id_tfk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Penawaran Harga Produk</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
		<div>
        	<div style="float:left; font-size:60px;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
            <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        	<div align="right" style="font-size:10px;"><?php echo substr($data->sistem('alamat_sis'), 0, 69); ?></div>
        	<div align="right" style="font-size:10px;"><?php echo substr($data->sistem('alamat_sis'), 70, 120); ?></div>
        	<div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        	<div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
        	<div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
        	<div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
        </div></br>
       <p style="margin-top: 20px;">Jakarta, <?php echo $date->tgl_indo(date('Y-m-d')); ?></p>
        
        <table style="width:100%; margin-bottom: 10px;">
            <tr>
                <td style="width:15%;">No</td>
                <td style="width:2%;">:</td>
                <td></td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>:</td>
                <td>Penawaran Harga Produk</td>
            </tr>
            <tr>
                <td>Lampiran</td>
                <td>:</td>
                <td>-</td>
            </tr>
        </table>

        <p>Kepada Yth,<br />
         <?php echo($view['nama_out']); ?><br />
         <?php echo($view['resmi_out']); ?><br />
         <?php echo($view['pengiriman_ola']); ?><br />

        <p>Dengan hormat,<br />
        Bersama surat ini kami PT. DUA FARMA MAHAKARSA memberikan penawaran harga produk CENDO, dengan rincian sebagai berikut :</p>

    	<table class="tabel">
        	<thead>
            	<tr>
                    <th >NO</th>
                    <th >NAMA OBAT</th>
                    <th >HNA</th>
                    <th >DISKON</th>
                    <th>JUMLAH</th>
                    <th>TOTAL</th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$subtot	= 0;
				$diskon	= 0;
				$total	= 0;
				$stotal	= 0;
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, B.no_bcode, B.tgl_expired, C.kode_pro, C.nama_pro, C.berat_pro, D.nama_kpr, E.nama_spr FROM transaksi_fakturdetail_pim AS A LEFT JOIN produk_stokdetail AS B ON A.id_psd=B.id_psd LEFT JOIN produk AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr WHERE A.id_tfk=:kode");
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
                	<td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                	<td><center><?php echo($hasil['diskon_tfd']); ?>%</center></td>
                	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
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
                        <td><div align="left">Total </div></td>
                        <td></td>
                        <td><div align="right"><span style="float:left;">Rp.</span><?php echo($data->angka($stotal)); ?></div></td>
                    </tr>
                    <tr>
						<?php if ($view['hargapim'] == '-') { ?>
							<td><div align="left">DPP</div></td>
						<?php } ?>
                        <td></td>
						<td>
						<div align="right">
							<?php
							if ($view['hargapim'] == '-') {
								echo '<span style="float:left;">Rp.</span>' . number_format($stotal / 1.11, 0, ',', '.');
							}
							?>
						</div>
						</td>
                    </tr>
					<tr>
						<td><div align="left">PPN <span style="float:right;">11%</span></div></td>
                        <td></td>
						<td>
						<div align="right">
							<span style="float:left;">Rp.</span>
							<?php
							if ($view['hargapim'] == '-') {
								echo number_format(($stotal / 1.11) * 0.11, 0, ',', '.');
							} else {
								echo number_format($ppn, 0, ',', '.');
							}
							?>
						</div>
						</td>
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
            </div>
		</div>
		<p>Demikian Surat Penawaran ini kami buat. Atas Kerjasamanya kami ucapkan banyak terima kasih<br /></p>
		</br>
			<table style="font-size:14px;">
            	<thead>
                	<tr>
                	    
                	    <th width="100%"><div>Hormat Kami, </div><div>PT. Dua Farma Mahakarsa</div></th>
                  
                    </tr>
                </thead>
                <tbody>
                	<tr>
                	    <td height="130" style="vertical-align:bottom;">
                        <div style="font-size:15px;"><center>Lucky Hafiansyah</center></div>
                        <div style="font-size:15px;"><center>Direktur Utama</center></div>
                        </td>
                    </tr>
                </tbody>
				
            </table>


		<script type="text/javascript">window.print(); </script>
    </body>
<?php } ?>
</html>
