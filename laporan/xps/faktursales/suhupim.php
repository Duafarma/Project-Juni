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
	$read	= $conn->prepare("SELECT A.kode_tfk, A.tgl_tfk, A.tgl_limit, A.po_tfk, TIMESTAMPDIFF(DAY, A.tgl_tfk, A.tgl_limit) AS jarak, B.nama_out, B.npwp_out, C.pengiriman_ola FROM transaksi_faktur_pim AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out LEFT JOIN outlet_alamat AS C ON B.id_out=C.id_out WHERE A.id_tfk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	//$limit	= $date->oprPeriode("Y-m-d", "+$view[top_odi] DAY", $view['tgl_tfk']);
?>
  <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Print Suhu Dingin</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
			@media print {
				.garis{
					width:100%;
					border:solid 1px #333333;
					background-color:#333333;
					margin:20px 0px 20px 0px;
				}
			}
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
        	<div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
        	<div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
        	 <div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
        </div>
        <!-- <hr class="garis" /> -->
    	<br />
                <div style="text-align:center; font-size:20px; font-weight:bold; margin:10px 0px 10px 0px;">FORMULIR SERAH TERIMA BARANG</div>
                <div style="text-align:center; font-size:20px; font-weight:bold;"><u>BERSUHU 2-8 C </u></div>
                <!-- <div style="text-align:center; font-weight:bold; margin-bottom:20px;">Tanggal : <?php echo($date->tgl_indo($view['tgl_tor'])); ?></div> -->
                <br />
                <br />
        <table style="font-weight:bold;">
        	
        	<tr>
            	<td>Nama Outlet</td>
            	<td><center>:</center></td>
            	<td width="65%"><?php echo($view['nama_out']); ?></td>
            </tr>
            <br />
            <br />
        	<tr>
            	<td>Alamat Outlet</td>
            	<td><center>:</center></td>
            	<td width="65%"><?php echo($view['pengiriman_ola']); ?></td>
            </tr>
            <br />
            <br />
            <tr>
            	<td>Nomor Faktur</td>
            	<td><center>:</center></td>
            	<td width="65%"><?php echo($view['kode_tfk']); ?></td>
            </tr>
        </table>
    	
		<p></p>
    	<table class="tabel" width="600px" height="340px">
        	<!-- <thead> -->
            	<tr>
                    <th style="font-size:16px;">KETERANGAN</td>
                    <th style="font-size:16px;" >PENGEMAS</th>
                    <th style="font-size:16px;">PENERIMA</th>
                   
				</tr>
            	
    		<!-- </thead> -->
            <!-- <tbody> -->
           
            	<tr>
                	<td style="font-size:15px;">Tanggal</td>
                    <td></td>
                    <td></td>

                	
                </tr>
                <tr>
                	<td style="font-size:15px;">Jam </td>
                    <td></td>
                    <td></td>
                </tr>

                <tr>
                	<td style="font-size:15px;">Suhu (C) </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                	<td style="font-size:15px;">Jumlah Item</td>
                    <td></td>
                    <td></td>
                	
                </tr>
                <tr>
                	<td style="font-size:15px;">Jumlah Barang (Total) </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                	<td style="font-size:15px;">Jumlah Ice Pak</td>
                    <td></td>
                    <td></td>
                	
                </tr>
                <tr>
                	<td style="font-size:15px;">Kemasan </td>
                    <td style="font-size:15px;"> Box / CCP</td>
                    <td></td>
                </tr>
                <tr>
                	<td style="font-size:15px;">Kondisi Kemasan </td>
                    <td style="font-size:15px;"> Baik / Rusak</td>
                    <td style="font-size:15px;">Baik / Rusak</td>
                </tr>
                
		
            <!-- </tbody> -->
            
        </table>
		<br />
        <br />
        <br />
        <table width="125%">
        	<tr>
            	<td width="33%"></td>
            	<td width="33%"></td>
            	<td width="33%"><div style="text-transform:capitalize;"></div></td>
            </tr>
        	<tr>
            	<td style="font-size:15px;">Dikemas Oleh</td>
            	<td style="font-size:15px;">Dikirim Oleh</td>
            	<td style="font-size:15px;">Diterima Oleh</td>
            </tr>
        	<tr>
            	<td height="60"></td>
				<td height="60"></td>
				<td><img src="" width="60%" /></td>
            </tr>
            <tr>
            	<td height="60"></td>
				<td height="60"></td>
				<td><img src="" width="60%" /></td>
            </tr>
        	<tr>
            	<td style="font-size:15px;">(Staff Gudang)</td>
            	<td style="font-size:15px;">(Kurir)</td>
            	<td style="font-size:15px;">(Nama & Stempel)</td>
            </tr>
        	<!-- <tr>
            	<td>SIPA NO</td>
            	<td></td>
            	<td>SIPA NO</td>
            </tr> -->
        </table>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
