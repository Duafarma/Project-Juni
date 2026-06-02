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
	$read	= $conn->prepare("SELECT A.sj_fkr, A.po_fkr, A.tglsj_fkr, B.nama_sup, B.npwp_sup, C.alamat_sal FROM faktur_retur AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_alamat AS C ON B.id_sup=C.id_sup WHERE A.id_fkr=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);

?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Surat Jalan</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>


    <body>
		<div>
        	<div style="float:left; font-size:60px; font-family:'Times New Roman', Times, serif;"><img src="<?php echo("../../../berkas/sistem/".$data->sistem('logo_sis')); ?>" height="70" width="100" /></div>
            <div align="right" style="font-size:10px; font-family:Arial, Helvetica, sans-serif;"><?php echo($data->sistem('pt_sis')); ?></div>
<div align="right" style="font-size:10px; font-family:Arial, Helvetica, sans-serif;"><?php echo substr($data->sistem('alamat_sis'),0,69); ?></div>
        	        	<div align="right" style="font-size:10px; font-family:Arial, Helvetica, sans-serif;"><?php echo substr($data->sistem('alamat_sis'),70,120); ?></div>        	
        	        	<div align="right" style="font-size:10px; font-family:Arial, Helvetica, sans-serif;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        	<div align="right" style="font-size:10px; font-family:Arial, Helvetica, sans-serif;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        	<div align="right" style="font-size:10px; font-family:Arial, Helvetica, sans-serif;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        </div>
    	<br />
		<table width="100%" style="font-family:Arial, Helvetica, sans-serif; font-size:10px;">
        	<tr>
        		<td width="50%"></td>
        		<td width="15%">Tanggal</td>
        		<td width="3%"><center>:</center></td>
        		<td width="32%"><?php echo($date->tgl_indo($view['tglsj_fkr'])); ?></td>
        	</tr>
        	<tr>
        		<td><div align="left">Kepada Yth,</div></td>
        		<td>Surat Jalan No.</td>
        		<td><center>:</center></td>
        		<td><?php echo($view['sj_fkr']); ?></td>
        	</tr>
        </table>
    	<table class="tabelinfo">
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
                	<td><center><?php echo($hasil['ed']); ?></center></td>
                	<td><div align="right"><?php echo($data->angka($hasil['jumlah_fkrd'])); ?></div></td>
                
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
            <div style="width:100%; display:inline-block;" align="left">
			<div style="margin-bottom:50px;"></div>
			<table class="tabel" style="font-size:10px;">
            	<thead>
                	<tr>
                	    <th width="20%"><div>PT. Dua Farma Mahakarsa</div></th>
                    	<th width="20%"><div>PT. Dua Farma Mahakarsa</div></th>
                    	<th width="20%"><div>PT. Dua Farma Mahakarsa</div></th>
                    	<th width="20%"><div>PT. Dua Farma Mahakarsa</div></th>
						<th width="20%"><div><center><?php echo($view['nama_sup']); ?></center></div></th>
                    </tr>
                </thead>
                <tbody>
                	<tr>
                	    <td height="120" style="vertical-align:bottom;">
                        <div style="font-size:15px;"><center>(..............................)</center></div>
                        <div style="font-size:15px;"><center>Staff Gudang</center></div>
						
                        </td>
                    	<td height="120" style="vertical-align:bottom;">
                        <div style="font-size:15px;"><center>(..............................)</center></div>
                        <div style="font-size:15px;"><center>Koordinator Gudang</center></div>
						
                        </td>
                        <td height="120" style="vertical-align:bottom;">
						<div style="font-size:15px;"><center>(..........................)</center></div>
                        <div style="font-size:15px;"><center> Koordinator Kurir</center></div>
						</td>
                    	<td height="120" style="vertical-align:bottom;">
						<div style="font-size:15px;"><center>(..........................)</center></div>
                        <div style="font-size:15px;"><center>  Kurir</center></div>
						</td>

						<td  height="120" style="vertical-align:bottom;">
						<div style="font-size:15px;">Nama :</div>
                        <div style="font-size:15px;">(Ttd dan Stempel)</div>
						</td>
                    </tr>
                </tbody>
				
            </table>
			<br />
			<br />
			<br />
			<br />
			<table style="font-size:100px;">
            	<thead>
                	<tr >
                	    <th width="20%"><div></div></th>
                    	<th width="20%"><div></div></th>
                    	<th width="20%"><div></div></th>
						<th width="20%"><div></div></th>
                    </tr>
                </thead>
                <tbody>
                	<tr>
                	    <td height="0" style="vertical-align:bottom;">
                        <div style="font-size:15px;"><center></center></div>
                        <div style="font-size:15px;"><center></center></div>
						
                        </td>
                    	<td height="0" style="vertical-align:bottom;">
                        <div style="font-size:15px;"><center></center></div>
                        <div style="font-size:15px;"><center></center></div>
						
                        </td>
                        <td height="0" style="vertical-align:bottom;">
                        <div style="font-size:15px;"><center> </center></div>
						</td>
                    	<td>
						<td height="0" style="vertical-align:bottom;">
						<div style="font-size:15px;"><center></center></div>
                        <div style="font-size:15px;"><center>  </center></div>
						</td>

						<td  height="0" style="vertical-align:bottom;">
						<div style="font-size:15px;"></div>
                        <div style="font-size:15px;"></div>
						</td>
                    </tr>
                </tbody>
				
            </table>
			
			
            </div>
            
		</div>
		
		
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
