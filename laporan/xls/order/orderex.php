<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Order_APJ Meinanda.xls");
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
	$read	= $conn->prepare("SELECT A.kode_tor, A.tgl_tor, B.nama_sup, C.alamat_sal FROM transaksi_order AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_alamat AS C ON B.id_sup=C.id_sup WHERE A.id_tor=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Stok</title>
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
		<hr class="garis" />
        <div style="text-align:center; font-weight:bold; margin:10px 0px 10px 0px;">Surat Pesanan Barang</div>
        <div style="text-align:center; font-weight:bold;">NO. SPB : <?php echo($view['kode_tor']); ?></div>
        <div style="text-align:center; font-weight:bold; margin-bottom:20px;">Tanggal : <?php echo($date->tgl_indo($view['tgl_tor'])); ?></div>

		<table>
        	<tr>
            	<td>Kepada Yth,</td>
            	<td></td>
            	<td></td>
            </tr>
        	<tr>
            	<td>NAMA PT.</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['nama_sup']); ?></td>
            </tr>
        	<tr>
            	<td>ALAMAT</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['alamat_sal']); ?></td>
            </tr>
        </table>
        <div style="margin:10px 0px 10px 0px;">Mohon dikirimkan segera pesanan kami di bawah ini sesuai kebutuhan kami</div>
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th><div align="center">NAMA PRODUK</div></th>
                    <th><div align="center">SEDIAAN</div></th>
                    <th><div align="center">JUMLAH</div></th>
                    <th><div align="center">SATUAN</div></th>
                    <th><div align="center">KETERANGAN</div></th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.jumlah_tod, B.nama_pro, C.nama_kpr, C.satuan_kpr FROM transaksi_orderdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr WHERE A.id_tor=:kode");
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['nama_pro']); ?></center></td>
                	<td><center><?php echo($hasil['nama_kpr']); ?></center></td>
                	<td><center><?php echo($hasil['jumlah_tod']); ?></center></td>
                	<td><center><?php echo($hasil['satuan_kpr']); ?></center></td>
                	<td><center></center></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
        <br />
        <table width="100%">
        	<tr>
            	<td width="30%">Penerimaan Pesanan</td>
            	<td width="40%"></td>
            	<td width="30%"><div style="text-transform:capitalize;"><?php echo(strtolower($data->sistem('pt_sis'))); ?></div></td>
            </tr>
        	<tr>
            	<td>Penanggung Jawab</td>
            	<td></td>
            	<td>Penanggung Jawab</td>
            </tr>
        	<tr>
            	<td height="60"></td>
				<td></td>
				<td><img src="<?php echo("../../../berkas/sistem/".$data->sistem('cap_apoteker')); ?>" width="65%" /></td>
            </tr>
        	<tr>
            	<td>(....................)</td>
            	<td></td>
            	<td>(....................)</td>
            </tr>
        	<tr>
            	<td>SIPA NO</td>
            	<td></td>
            	<td>SIPA NO</td>
            </tr>
        </table>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
