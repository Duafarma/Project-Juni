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
	$read	= $conn->prepare("SELECT A.id_tre, A.fak_tre, A.tglfak_tre, A.total_tre, A.tgl_tre, A.status_tre, B.ket_tor, B.kode_tor, B.tgl_tor, C.nama_sup FROM transaksi_receive AS A LEFT JOIN transaksi_order AS B ON A.id_tor=B.id_tor LEFT JOIN supplier AS C ON B.id_sup=C.id_sup WHERE A.id_tre=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	//$limit	= $date->oprPeriode("Y-m-d", "+$view[top_odi] DAY", $view['tgl_tfk']);
?>
  <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Print Penerimaan Barang</title>
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
        	<!--<div align="right" style="font-size:10px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>-->
        	 <div align="right" style="font-size:10px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
        </div>
        <!-- <hr class="garis" /> -->
    	<br />
                <div style="text-align:center; font-size:22px; font-weight:bold; margin:10px 0px 10px 0px;">FORMULIR PENERIMAAN BARANG</div>
     
        <table style="font-weight:bold;">
        	
        	<tr>
            	<td>No. FAKTUR</td>
            	<td><center>:</center></td>
            	<td width="65%"><?php echo($view['fak_tre']); ?></td>
            </tr>
            <br />
            <br />
        	<tr>
            	<td>TANGGAL</td>
            	<td><center>:</center></td>
            	<td width="65%"><?php echo($view['tglfak_tre']); ?></td>
            </tr>
            <br />
            <br />
            <tr>
            	<td>NOMOR SURAT PESANAN</td>
            	<td><center>:</center></td>
            	<td width="65%"><?php echo($view['kode_tor']); ?></td>
            </tr>
        </table>
    	
		<p></p>
    	<div class="table-responsive">
				<table class="tabel">
                	<thead>
                    	<tr>
                            <th>NO</th>
                            <th>NAMA BARANG</th>
                            <th>BATCH</th>
                            <th>ED</th>
                            <th>JUMLAH</th>
                            <th>NIE</th>  
                        </tr>
                    </thead>
                    <tbody>
                    <?php
						$total	= 0;
                        $nomor	= 1;
						$master	= $conn->prepare("SELECT A.id_trd, A.bcode_trd, A.gudang, A.tbcode_trd, A.jumlah_trd, A.harga_trd, A.diskon_trd, A.total_trd, B.nama_pro, B.no_nie,   B.kode_pro, B.berat_pro, C.nama_kpr, D.nama_spr FROM transaksi_receivedetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.id_tre=:kode");
						$master->bindParam(':kode', $kode, PDO::PARAM_STR);
						$master->execute();
						while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
								$total	+= $hasil['total_trd'];
					?>
                    	<tr>
                            <td><center><?php echo($nomor); ?></center></td>
                            <td><center><?php echo($hasil['nama_pro']); ?></center></td>
                        	<td><center><?php echo($hasil['bcode_trd']); ?></center></td>
                        	<td><center><?php echo($hasil['tbcode_trd']); ?></center></td>
                        	<td></center><div align="right"><?php echo($data->angka($hasil['jumlah_trd'])); ?></div></center></td>
                        	<td><center><?php echo($hasil['no_nie']); ?></center></td>
                        </tr>
                  	<?php
                    $nomor++;
                    	}
						
					?>
                    	
                    </tbody>
                </table>
                </div>
                    <table style="font-weight:bold;">
                        
                      <tr>
                            <td>KETERANGAN</td>
                            <td><center>:</center></td>
                            <td><center></center></td>
                            <td width="65%"></td>
                        </tr>
                        <tr>
                            <td>Untuk Produk CCP Suhu </td>
                            <td><center>:</center></td>
                            <td><center>...................</center></td>
                            <td width="65%"></td>
                        </tr>
                        <br />
                        <br />
                        <tr>
                            <td>Produk</td>
                            <td><center>:</center></td>
                            <td><center> Rusak</center></td>
                            <td width="65%">:  <input type="checkbox" id="vehicle1" name="vehicle1" value="Bike"></td>
                        </tr>
                        <br />
                        <br />
                        <tr>
                            <td></td>
                            <td><center></center></td>
                            <td><center> Bocor</center></td>
                            <td width="65%"> :  <input type="checkbox" id="vehicle1" name="vehicle1" value="Bike"></td>
                        </tr>
                    </table>
                
        <br />
        <br />
        <table width="125%">
        	<tr>
            	<td width="33%"></td>
            	<td width="20%"></td>
            	<td width="33%"><div style="text-transform:capitalize;"></div></td>
            </tr>
        	<tr>
            	<td style="font-size:15px;">Petugas Gudang</td>
                <td style="font-size:15px;"></td>
            	<td style="font-size:15px;">Mengetahui, </td>
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
            	<td style="font-size:15px;">(Petugas  Penerimaan)</td>
                <td style="font-size:15px;"></td>
            	<td style="font-size:15px;">(Apoteker Penanggung Jawab)</td>
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
