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
	$cari	= $secu->injection(@$_GET['cari']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$whereClause = '';
	if ($cari != '') {
		$pecah	= explode('_', $cari);
		$cariTipe	= "('" . implode("','",explode('-', @$pecah[0])) . "')";
		$cariBarang	= @$pecah[2];
		$tgl1	= @$pecah[3] . "-01";
		$tgl2	= @$pecah[4] . "-01";
		$whereClause = "WHERE (b.tgl_ttr BETWEEN '$tgl1' AND LAST_DAY('$tgl2')) AND d.nama_pro LIKE '%$cariBarang%' OR b.tipe_ttr IN $cariTipe";
	}
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Transfer Stok</title>
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
		<table class="tabel">
        	<thead>
            	<tr>
                    <th><center>NO.</center></th>
                    <th>TANGGAL</th>
                    <th>TIPE</th>
                    <th>KODE TRANSFER</th>
                    <th>KODE BARANG</th>
                    <th>NAMA BARANG</th>
                    <th>NO. BATCH</th>
                    <th>EXP. DATE</th>
                    <th>JUMLAH TRANSFER</th>
                    <th>STATUS</th>
				</tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$qMaster = "SELECT
								b.tgl_ttr,
								b.tipe_ttr,
								b.kode_ttr,
								d.id_pro,
								d.kode_pro,
								d.nama_pro,
								c.no_bcode,
								c.tgl_expired,
								a.jumlah_ttd,
								b.status_ttr 
							FROM
								transaksi_transferstockdetail a
							LEFT JOIN transaksi_transferstock b ON
								a.id_ttr = b.id_ttr
							LEFT JOIN produk_stokdetail c ON
								a.id_psd = c.id_psd
							LEFT JOIN produk d ON
								c.id_pro = d.id_pro
							$whereClause
							ORDER BY
								b.tgl_ttr DESC, b.kode_ttr DESC";
				$master	= $conn->prepare($qMaster);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><center><?php echo($hasil['tgl_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['tipe_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['kode_ttr']); ?></center></td>
                	<td><center><?php echo($hasil['kode_pro']); ?></center></td>
					<td><center><?php echo($hasil['nama_pro']); ?></center></td>
					<td><center><?php echo($hasil['no_bcode']); ?></center></td>
					<td><center><?php echo($hasil['tgl_expired']); ?></center></td>
					<td><center><?php echo($hasil['jumlah_ttd']); ?></center></td>
					<td><center><?php echo($hasil['status_ttr']); ?></center></td>
                </tr>
			<?php
				$nomor++;
            	}
			?>
            </tbody>
        </table>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
