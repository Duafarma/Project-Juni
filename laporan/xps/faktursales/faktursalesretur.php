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
	$read	= $conn->prepare("SELECT A.kode_fkr, A.tgl_tfk,A.total_fkr, A.tgl_limit, A.po_fkr, TIMESTAMPDIFF(DAY, A.tgl_tfk, A.tgl_limit) AS jarak,B.nama_sup, B.npwp_sup, C.alamat_sal FROM faktur_retur AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_alamat AS C ON B.id_sup=C.id_sup WHERE A.id_fkr=:kode");
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
        		<td><?php echo($view['kode_fkr']); ?></td>
        	</tr>
        </table>
    	<table class="tabelinfo " style="font-family:Calibri Light, Helvetica, sans-serif; font-size:12px;">
        	<tr>
            	<td width="35%">NAMA PELANGGAN</td>
                <td width="3%"><center>:</center></td>
           		<td width="62%"><?php echo($view['nama_sup']); ?></td>
            </tr>
        	<tr>
            	<td>ALAMAT KIRIM</td>
                <td><center>:</center></td>
           		<td><?php echo($view['alamat_sal']); ?></td>
            </tr>
        	<tr>
            	<td>NPWP</td>
                <td><center>:</center></td>
           		<td><?php echo($view['npwp_sup']); ?></td>
            </tr>
        	<tr>
            	<td>NO. PO</td>
                <td><center>:</center></td>
           		<td><?php echo($view['po_fkr']); ?></td>
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
				$master	= $conn->prepare("SELECT A.jumlah_fkrd, A.harga_fkrd, A.diskon_fkrd, B.no_bcode, B.ed, C.kode_pro, C.nama_pro, C.berat_pro, D.nama_kpr, E.nama_spr FROM faktur_returdetail AS A LEFT JOIN inventory_retur AS B ON A.id_i_r=B.id_i_r LEFT JOIN produk AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr WHERE A.id_fkr=:kode");
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$subtot	= $hasil['jumlah_fkrd'] * $hasil['harga_fkrd'];
					$diskon	= ($subtot * $hasil['diskon_fkrd']) / 100;
					$total	= $subtot - $diskon;
					$stotal	+= $total;
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['nama_pro']); ?></td>
                	<td><?php echo($hasil['nama_kpr']); ?></td>
                	<td><?php echo("$hasil[berat_pro] $hasil[nama_spr]"); ?></td>
                	<td><center><?php echo($hasil['no_bcode']); ?></center></td>
                	<td><center><?php echo(substr($hasil['ed'], 0, 7)); ?></center></td>
                	<td><div align="right"><?php echo($data->angka($hasil['jumlah_fkrd'])); ?></div></td>
                	<td><div align="right"><?php echo($data->angka($hasil['harga_fkrd'])); ?></div></td>
                	<td><center><?php echo($hasil['diskon_fkrd']); ?>%</center></td>
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
			<table class="tabel" style="font-size:10px;">
            	<thead>
                	<tr>
                    	<th width="60%"><div align="left"><?php echo($data->sistem('pt_sis')); ?></div></th>
                    	<th width="40%"><div align="left">TTD dan CAP</div></th>
                    </tr>
                </thead>
                <tbody>
                	<tr>
                    	<td height="60" style="vertical-align:bottom;">
                        <div align="left">Nama : <?php echo($data->sistem('apoteker_sis')); ?></div>
                        <div align="left">Jabatan : Apoteker</div>
                        </td>
                    	<td></td>
                    </tr>
                </tbody>
            </table>
			<br />
			<table class="tabel" style="font-size:10px;">
            	<thead>
                	<tr>
                    	<th width="60%"><div align="left">Penerima</div></th>
                    	<th width="40%"><div align="left">TTD dan CAP</div></th>
                    </tr>
                </thead>
                <tbody>
                	<tr>
                    	<td height="60">
						<div align="left" style="margin-bottom:30px; font-weight:bold;"><?php echo($view['nama_sup']); ?></div>
                        <div align="left">Nama :</div>
                        <div align="left">Jabatan :</div>
                        </td>
                    	<td></td>
                    </tr>
                </tbody>
            </table>
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
                        <td><div align="right"><span style="float:left; font-weight:bold;">Rp.</span><b><?php echo($data->angka($view['total_fkr'])); ?></b></div></td>
                    </tr>
                </table>
                </div>
            	<div style="min-height:30px; height:auto; border:solid 1px #666666; text-align:center; font-weight:bold; margin-top:10px; padding:5px; font-size:12px;">Terbilang : # <?php echo($data->terbilang($view['total_fkr'])); ?> Rupiah #</div>
            	<!--<div style="min-height:30px; height:auto; border:solid 1px #666666; text-align:center; font-weight:bold; margin-top:10px; line-height:25px; font-size:12px;">Terbilang : # <?php //echo($data->terbilang($gtotal)); ?> Rupiah #</div>-->
            	<div style="height:auto; border:solid 1px #666666; text-align:left; margin-top:10px; padding:5px; font-size:10px;">
                	<div style="font-weight:bold;">JATUH TEMPO PEMBAYARAN : <?php echo($date->tgl_indo($view['tgl_limit'])." ($view[jarak] Hari Dari Obat Diterima)"); ?></div>
					<div style="margin-top:5px;">Pembayaran dapat dilakukan dengan cara melakukan transfer ke :</div>
                	<div style="margin-left:15px; margin-top:5px;">BANK <?php echo($data->sistem('bank_sis')); ?></div>
                	<div style="margin-left:15px; margin-top:5px;"><?php echo($data->sistem('norek_sis')); ?></div>
                	<div style="margin-left:15px; margin-top:5px;">An. <?php echo($data->sistem('anam_sis')); ?></div>
                </div>
            </div>
		</div>

		<script type="text/javascript">window.print(); </script>
    </body>
<?php } ?>
</html>
