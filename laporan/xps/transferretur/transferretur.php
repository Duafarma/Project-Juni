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
	$read	= $conn->prepare("SELECT
						tt.id_ttr,
						tt.kode_ttr,
						tt.tgl_ttr,
						tt.tipe_ttr,
						tt.status_ttr,
						tt.ket_ttr,
						a2.nama_apl AS dari,
						a3.nama_apl AS ke
					FROM
						transaksi_transferretur tt
					LEFT JOIN aplikasi a2 ON
						tt.id_app_from = a2.id_apl
					LEFT JOIN aplikasi a3 ON
						tt.id_app_to = a3.id_apl
					WHERE
					     tt.id_ttr=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Retur</title>
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

</BR>

    <body>
	
        <div style="text-align:center; font-weight:bold; margin:10px 0px 10px 0px;">SURAT PENGANTAR BARANG</div>
       
</BR>
		<table>
        
        	<tr>
            	<td>NOMOR REQUEST</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['kode_ttr']); ?></td>
            </tr>
        	<tr>
            	<td>TANGGAL</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['tgl_ttr']); ?></td>
            </tr>
            <tr>
            	<td>KETERANGAN</td>
            	<td><center>:</center></td>
            	<td><?php echo($view['ket_ttr']); ?></td>
            </tr>
        </table>
        </br>
    	<table class="tabel">
            <thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th><div align="center">NAMA OBAT</div></th>
                    <th><div align="center">KEMASAN</div></th>
                    <th><div align="center">JUMLAH</div></th>
                    <th><div align="center">NO BATCH</div></th>
                    <th><div align="center">ED</div></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $nomor	= 1;
                $id_ttr = $view['id_ttr'];
                $master	= $conn->prepare("SELECT
                                        p.id_pro,
                                        p.kode_pro,
                                        p.nama_pro,
                                        kp.nama_kpr,
                                        p.berat_pro,
                                        kp.satuan_kpr,
                                        ir.ed AS tgl_expired,
                                        ir.no_bcode,
                                        ttd.jumlah_ttd,
                                        sp.nama_spr
                                    FROM
                                        transaksi_transferreturdetail ttd
                                    LEFT JOIN inventory_retur ir ON
                                        ttd.id_i_r = ir.id_i_r
                                    LEFT JOIN produk p ON
                                        ttd.id_pro = p.id_pro
                                    LEFT JOIN kategori_produk kp ON
                                        p.id_kpr = kp.id_kpr
                                    LEFT JOIN satuan_produk sp ON
                                        p.id_spr = sp.id_spr
                                    WHERE
                                        ttd.id_ttr = :id_ttr");
                $master->bindParam(':id_ttr', $id_ttr, PDO::PARAM_STR);
                $master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$proddetail	= $hasil['nama_kpr']." (".$hasil['berat_pro']." ".$hasil['satuan_kpr'].")";
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['nama_pro']); ?></center></td>
                	<td><?php echo($proddetail); ?></td>
                	<td><center><?php echo($hasil['jumlah_ttd']); ?></center></td>
                	<td><center><?php echo($hasil['no_bcode']); ?></center></td>
                	<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
        <br />
         </BR>
                 <table width="100%">
                	<tr>
                    	<td width="30%">Penerimaan Pesanan</td>
                    	<td width="40%"></td>
                    	<td width="30%"><div style="text-transform:capitalize;">Pengirim</div></td>
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
