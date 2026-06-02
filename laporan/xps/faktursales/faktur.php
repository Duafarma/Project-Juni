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
	$read	= $conn->prepare("SELECT A.id_tfk,
                                    -- A.sj_tfk,
                                    -- A.tglsj_tfk,
                                    A.po_tfk,
                                    A.kode_tfkk,
                                    A.tgl_tfk,
                                    A.total_tfk,
                                    A.status_tfk,
                                    B.nama_out,
                                    C.kode_tfk,
									D.pengiriman_ola
                                FROM
                                    transaksi_faktur_penggantian_barang AS A
                                LEFT JOIN outlet AS B ON
                                    A.id_out = B.id_out
                                LEFT JOIN transaksi_faktur AS C ON
                                    A.no_tfk_penjualan = C.id_tfk
								LEFT JOIN outlet_alamat AS D ON
									B.id_out = D.id_out
								 WHERE A.id_tfk=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Faktur Penggantian Barang</title>
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
	<div style="float:left; font-size:60px;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
            <div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        	<div align="right" style="font-size:10px;"><?php echo($data->sistem('alamat_sis')); ?></div>
        	<div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        	<div align="right" style="font-size:10px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
        	<div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
        	 <div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-0989/02/2020</div>
        </div>
		<hr class="garis" />
        <div style="text-align:center; font-weight:bold; margin:10px 0px 10px 0px;">Surat Penggantian Barang</div>
        <div style="text-align:center; font-weight:bold;">NO. SPB : <?php echo($view['kode_tfkk']); ?> </div>
        <div style="text-align:center; font-weight:bold; margin-bottom:20px;">Tanggal : <?php echo($view['tgl_tfk']); ?> </div>

		<table>
        	<tr>
            	<td>Kepada Yth,</td>
            	<td></td>
            	<td></td>
            </tr>
        	<tr>
            	<td>NAMA OUTLET</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['nama_out']); ?></td>
            </tr>
        	<tr>
            	<td>ALAMAT</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['pengiriman_ola']); ?></td>
            </tr>
        </table>
        <!-- <div style="margin:10px 0px 10px 0px;">Mohon dikirimkan segera pesanan kami di bawah ini sesuai kebutuhan kami</div> -->
    	<table class="tabel">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th><div align="center">NAMA PRODUK</div></th>
                    <th><div align="center">SEDIAAN</div></th>
					<th><div align="center">NO. BATCH</div></th>
					<th><div align="center">EXP. DATE</div></th>
                    <th><div align="center">JUMLAH</div></th>

					
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT 
												A.jumlah_tfd,
												A.id_tfd, 
												B.nama_pro, 
												C.nama_kpr, 
												C.satuan_kpr,
												D.no_bcode,
												D.tgl_expired 
										FROM   
										        transaksi_faktur_penggantian_barang_detail AS A 
												LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
												LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr  
												LEFT JOIN produk_stokdetail AS D ON A.id_psd = D.id_psd
												WHERE A.id_tfk=:kode");
				$master->bindParam(':kode', $kode, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['nama_pro']); ?></center></td>
                	<td><center><?php echo($hasil['nama_kpr']); ?></center></td>
					<td><center><?php echo($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo($hasil['tgl_expired']); ?></center></td>

                	<td><center><?php echo($hasil['jumlah_tfd']); ?></center></td>
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
