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
	$read	= $conn->prepare("
		SELECT A.id_out,
		       A.kode_tfk,
		       A.tgl_tfk,
		       A.total_tfk,
		       A.ppn_tfk,
		       A.subtot_tfk,
		       A.tgl_limit,
		       A.po_tfk,
		       TIMESTAMPDIFF(DAY, A.tgl_tfk, A.tgl_limit) AS jarak,
		       B.resmi_out,
		       B.nama_out,
		       B.npwp_out,
		       C.pengiriman_ola
		FROM transaksi_faktur AS A
		LEFT JOIN outlet AS B ON BINARY A.id_out = BINARY B.id_out
		LEFT JOIN outlet_alamat AS C ON BINARY B.id_out = BINARY C.id_out
		WHERE A.id_tfk = :kode
     ");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $view	= $read->fetch(PDO::FETCH_ASSOC);
    // ambil mapping rekening untuk outlet jika ada
    $bank_mapping = null;
    if(!empty($view['id_out'])){
        // lakukan dua langkah untuk menghindari perbandingan kolom dengan collation berbeda
        $stmt = $conn->prepare("SELECT id_rek FROM outlet_rekening WHERE id_out = :id_out LIMIT 1");
        $stmt->bindParam(':id_out', $view['id_out'], PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row && !empty($row['id_rek'])){
            $stmt2 = $conn->prepare("SELECT nama_rekening, nomor_rekening, atas_nama FROM master_rekening WHERE id_rek = :id_rek LIMIT 1");
            $stmt2->bindParam(':id_rek', $row['id_rek'], PDO::PARAM_STR);
            $stmt2->execute();
            $bank_mapping = $stmt2->fetch(PDO::FETCH_ASSOC);
        }
    }
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Untitled Document</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>


    <body style="font-family: Calibri, sans-serif; font-size: 12px;">
		<div style="margin-bottom: 10px;">
        	<div style="float:left; font-size: 48px; font-weight: bold; color: #FFA500;">Dua.</div>
            <div style="text-align:right; float:right; padding-right:20px;">
                <div style="font-size:9px;"><?php echo($data->sistem('pt_sis')); ?></div>
                <div style="font-size:9px;"><?php echo substr($data->sistem('alamat_sis'),0,88); ?></div>
                <!-- <div style="font-size:9px;"><?php echo substr($data->sistem('alamat_sis'),60,80); ?></div> -->
                <div style="font-size:9px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
                <!-- <div style="font-size:9px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div> -->
                <div style="font-size:9px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
                <div style="font-size:9px;">SIPA APJ : <?php echo($data->sistem('sipa_sis')); ?></div>
                <div style="font-size:9px;">CDOB : <?php echo($data->sistem('cdob_sis')); ?></div>
                <div style="font-size:9px;">CDOB CCP : CDOB2777/S/1-1844/01/2024</div>
            </div>
            <div style="clear:both;"></div>
        </div>
    	<br style="clear:both;"/>
		
		<table style="border-collapse: collapse; margin-bottom: 10px; font-size: 12px;">
            <tr>
                <td style="width: 80px; padding: 2px 0;">Tanggal</td>
                <td style="width: 15px; padding: 2px 5px;">:</td>
                <td style="padding: 2px 0;"><?php echo($date->tgl_indo($view['tgl_tfk'])); ?></td>
            </tr>
            <tr>
                <td style="width: 80px; padding: 2px 0;">Faktur No.</td>
                <td style="width: 15px; padding: 2px 5px;">:</td>
                <td style="padding: 2px 0;"><?php echo($view['kode_tfk']); ?></td>
            </tr>
        </table>
        <div style="margin-top: 5px;">Kepada Yth,</div>

    		
    	<table class="tabelinfo " style="font-family: Calibri, sans-serif; font-size: 12px;">
        	<tr>
            	<td width="35%">Nama Pelanggan</td>
                <td width="3%"><center>:</center></td>
           		<td width="62%"><?php echo($view['nama_out']); ?></td>
            </tr>
            <tr>
            	<td width="35%">Nama Outlet</td>
                <td width="3%"><center>:</center></td>
           		<td width="62%"><?php echo($view['resmi_out']); ?></td>
            </tr>
        	<tr>
            	<td>Alamat Kirim</td>
                <td><center>:</center></td>
           		<td><?php echo($view['pengiriman_ola']); ?></td>
            </tr>
        	<tr>
            	<td>NPWP</td>
                <td><center>:</center></td>
           		<td><?php echo($view['npwp_out']); ?></td>
            </tr>
        	<tr>
            	<td>NO. PO</td>
                <td><center>:</center></td>
           		<td><?php echo($view['po_tfk']); ?></td>
            </tr>
        </table>
</br>
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
				$master	= $conn->prepare("SELECT A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, B.no_bcode, B.tgl_expired, C.kode_pro, C.nama_pro, C.berat_pro, D.nama_kpr, E.nama_spr FROM transaksi_fakturdetail AS A LEFT JOIN produk_stokdetail AS B ON A.id_psd=B.id_psd LEFT JOIN produk AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON C.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON C.id_spr=E.id_spr WHERE A.id_tfk=:kode");
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
                	<td><?php echo($hasil['nama_kpr']); ?></td>
                	<td><?php echo("$hasil[berat_pro] $hasil[nama_spr]"); ?></td>
                	<td><center><?php echo($hasil['no_bcode']); ?></center></td>
                	<td><center><?php echo(substr($hasil['tgl_expired'], 0, 7)); ?></center></td>
                	<td><div align="right"><?php echo($data->angka($hasil['jumlah_tfd'])); ?></div></td>
                	<td><div align="right"><?php echo($data->angka($hasil['harga_tfd'])); ?></div></td>
                	<td><center><?php echo($hasil['diskon_tfd']); ?>%</center></td>
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
        <!-- SECTION TOTAL -->
        <table style="width: 50%; border-collapse: collapse; margin-bottom: 15px; font-size: 12px;">
            <tr>
                <td style="width: 40%; text-align: left; padding: 2px 5px;">Total 1</td>
                <td style="width: 15%; text-align: left; padding: 2px 5px;">Rp.</td>
                <td style="width: 45%; text-align: right; padding: 2px 5px;"><?php echo($data->angka($view['subtot_tfk'])); ?></td>
            </tr>
            <tr>
                <td style="text-align: left; padding: 2px 5px;">Potongan</td>
                <td style="text-align: left; padding: 2px 5px;">Rp.</td>
                <td style="text-align: right; padding: 2px 5px;"><?php echo($data->angka(0)); ?></td>
            </tr>
            <tr>
                <td style="text-align: left; padding: 2px 5px;">PPN &nbsp;&nbsp; 11%</td>
                <td style="text-align: left; padding: 2px 5px;">Rp.</td>
                <td style="text-align: right; padding: 2px 5px;"><?php echo($data->angka($view['ppn_tfk'])); ?></td>
            </tr>
            <tr>
                <td colspan="3" style="height: 1px; background-color: #000; padding: 0;"></td>
            </tr>
            <tr>
                <td style="text-align: left; padding: 2px 5px; font-weight: bold;">Total Faktur</td>
                <td style="text-align: left; padding: 2px 5px; font-weight: bold;">Rp.</td>
                <td style="text-align: right; padding: 2px 5px; font-weight: bold;"><?php echo($data->angka($view['total_tfk'])); ?></td>
            </tr>
        </table>

        <!-- SECTION TERBILANG -->
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #333; margin-bottom: 15px;">
            <tr>
                <td style="padding: 6px; font-weight: bold;">Terbilang :</td>
            </tr>
            <tr>
                <td style="padding: 6px; padding-left: 50px; font-weight: bold;">
                    # <?php echo($data->terbilang($view['total_tfk'])); ?> Rupiah #
                </td>
            </tr>
        </table>
		<br/>
		<br/>
		<br/>
        <!-- SECTION TTD -->
        <div style="width: 100%; display: table;">
            <!-- KIRI: PT. DUA FARMA -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-right: 5px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #333; margin-bottom: 8px;">
                    <tr>
                        <td style="border: 1px solid #333; padding: 6px; font-weight: bold; width: 60%;">PT. DUA FARMA MAHAKARSA</td>
                        <td style="border: 1px solid #333; padding: 6px; font-weight: bold; width: 40%;">TTD DAN CAP</td>
                    </tr>
                    <tr style="height: 80px;">
                        <td style="border: 1px solid #333; padding: 6px; vertical-align: bottom;">
                            <div>Nama : <?php echo($data->sistem('apoteker_sis')); ?></div>
                            <div>Jabatan : Apoteker</div>
                        </td>
                        <td style="border: 1px solid #333; padding: 6px;"></td>
                    </tr>
                </table>
                <div style="font-size: 12px;">
                    <div style="margin-bottom: 3px;">Pembayaran dapat dilakukan dengan cara melakukan transfer ke :</div>
                    <div>Nama Bank : <?php 
                        if($bank_mapping) { echo htmlspecialchars($bank_mapping['nama_rekening']); }
                        else { echo htmlspecialchars($data->sistem('bank_sis')); }
                    ?> &nbsp;&nbsp;&nbsp; No. Rekening : <?php 
                        if($bank_mapping) { echo htmlspecialchars($bank_mapping['nomor_rekening']); }
                        else { echo htmlspecialchars($data->sistem('norek_sis')); }
                    ?></div>
                    <div>An. <?php 
                        if($bank_mapping) { echo htmlspecialchars($bank_mapping['atas_nama']); }
                        else { echo htmlspecialchars($data->sistem('anam_sis')); }
                    ?></div>
                </div>
            </div>

            <!-- KANAN: PENERIMA -->
            <div style="display: table-cell; width: 50%; vertical-align: top; padding-left: 5px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #333; margin-bottom: 8px;">
                    <tr>
                        <td style="border: 1px solid #333; padding: 6px; font-weight: bold; width: 60%;">PENERIMA</td>
                        <td style="border: 1px solid #333; padding: 6px; font-weight: bold; width: 40%;">TTD DAN CAP</td>
                    </tr>
                    <tr style="height: 80px;">
                        <td style="border: 1px solid #333; padding: 6px; vertical-align: top;">
                            <div style="font-weight: bold; margin-bottom: 10px;"><?php echo($view['nama_out']); ?></div>
                            <div style="margin-top: auto; vertical-align: bottom;">
                                <div>Nama :</div>
                                <div>Jabatan :</div>
                            </div>
                        </td>
                        <td style="border: 1px solid #333; padding: 6px;"></td>
                    </tr>
                </table>
                <div style="font-size: 12px;">
                    <div style="font-weight: bold; margin-bottom: 3px;">Jatuh Tempo Pembayaran :</div>
                    <div style="font-weight: bold;"><?php echo($date->tgl_indo($view['tgl_limit']).' &nbsp;&nbsp;&nbsp; ('.$view['jarak'].' Hari Dari Obat Diterima)'); ?></div>
                </div>
            </div>
        </div>

		<script type="text/javascript">window.print(); </script>
    </body>
<?php } ?>
</html>
