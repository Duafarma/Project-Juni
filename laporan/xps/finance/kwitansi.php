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
	$read	= $conn->prepare("SELECT tfkk.*,
										o.nama_out,
                                        o.resmi_out,
										-- c.kode_tfk,
										-- c.total_tfk,
										-- a.nomor_faktur,
										-- c.kode_tfk,
										tfkk.nomor, 
										tfkk.tanggal_faktur
									FROM
										finance tfkk
									LEFT JOIN outlet o ON
										tfkk.nama_outlet = o.id_out
									-- LEFT JOIN transaksi_faktur c ON 
									-- 	tfkk.nomor_faktur = c.id_tfk
									-- LEFT JOIN adminz a ON
									-- 	tfkk.created_by = a.id_adm
										WHERE tfkk.id_finance=:kode");
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
			<div align="right" style="font-size:10px;"><?php echo($data->sistem('pt_sis')); ?></div>
        	<div style="float:left;  font-size:16px; marging-left:px; border:solid 2px #666666; padding-right:90px; padding-left:90px; padding-top:20px; padding-bottom:20px;" >KWITANSI</div>
        	<div align="right" style="font-size:10px;"><?php echo($data->sistem('alamat_sis')); ?></div>
        	<div align="right" style="font-size:10px;">PHONE <?php echo($data->sistem('telp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">NPWP : <?php echo($data->sistem('npwp_sis')); ?></div>
        	<div align="right" style="font-size:10px;">IZIN PBF : <?php echo($data->sistem('pbf_sis')); ?></div>
        </div>
    	<br />
        <br />
    

		<div style="text-align:center; font-weight:bold; margin-bottom:20px;"></div>

		<?php

			$nomor	= 1;
			$subtot = 0; // inisialisasi subtotal
			$master	= $conn->prepare("SELECT tfkk.*,
						o.nama_out,
						d.kode_tfk,
						d.total_tfk,
						d.po_tfk,
						d.tgl_tfk,
						-- a.nomor_faktur,
						d.kode_tfk,
						tfkk.nomor,
						tfkk.tanggal_faktur
					FROM
						finance tfkk
					LEFT JOIN outlet o ON
						tfkk.nama_outlet = o.id_out
					LEFT JOIN finance_detail c ON 
						tfkk.id_finance = c.id_finance
					LEFT JOIN transaksi_faktur d ON 
						d.id_tfk = c.no_kwitansi
					-- LEFT JOIN adminz a ON
					-- 	tfkk.created_by = a.id_adm
						WHERE tfkk.id_finance=:kode
					UNION ALL
					SELECT tfkk.*,
						o.nama_out,
						p.kode_tfk,
						p.total_tfk,
						p.po_tfk,
						p.tgl_tfk,
						p.kode_tfk,
						tfkk.nomor,
						tfkk.tanggal_faktur
					FROM
						finance tfkk
					LEFT JOIN outlet o ON
						tfkk.nama_outlet = o.id_out
					LEFT JOIN finance_detail c ON 
						tfkk.id_finance = c.id_finance
					LEFT JOIN transaksi_faktur_pim p ON 
						p.id_tfk = c.no_kwitansi
						WHERE tfkk.id_finance=:kode2 AND p.kode_tfk IS NOT NULL");
			$master->bindParam(':kode', $kode, PDO::PARAM_STR);
			$master->bindParam(':kode2', $kode, PDO::PARAM_STR);
			$master->execute();
			while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				$subtot	+= $hasil['total_tfk'];
				
				// $stotal	+= $subtot;
		?>
		<?php
				$nomor++;
             	}
		?>
        

        <table rules="rows" style="margin-right:10px; margin-bottom:10px; ">
            <tr>
                <td style="width:150px;">Nomor</td>
                <td style="width:150px;"></td>
                <td>: <?php echo($view['nomor']); ?></td>
            </tr>
        </table>
        
        <table rules="rows" style="margin-right:10px; margin-bottom:10px; ">
            <tr>
                <td style="width:150px;">Telah Terima Dari</td>
                <td style="width:150px;"></td>
                <td rowspan="2">: <?php echo($view['resmi_out']); ?></td>
            </tr>
            <tr>
                <td><i>Received Form</i></td>
                <td></td>
            </tr>
        </table>
    
        <table rules="rows" style="margin-right:10px; margin-bottom:10px; ">
            <tr>
                <td style="width:150px;">Sejumlah Uang</td>
                <td style="width:150px;"></td>
                <td rowspan="2">: Rp. <?php echo($data->angka($subtot)); ?></td>
            </tr>
            <tr>
                <td><i>Amount Received</i></td>
                <td></td>
            </tr>
        </table>
        
        <table rules="rows" style="margin-right:10px; margin-bottom:10px; ">
            <tr>
                <td style="width:150px;">Terbilang</td>
                <td style="width:150px;"></td>
                <td rowspan="2"><b><i>: #<?php echo($data->terbilang($subtot)); ?> Rupiah#</i></b></td>
            </tr>
            <tr>
                <td><i>Amount In Words</i></td>
                <td></td>
            </tr>
        </table>
        
        <table rules="rows" style="margin-right:10px; margin-bottom:10px; ">
            <tr>
                <td style="width:150px;">Tujuan Pembayaran</td>
                <td style="width:150px;"></td>
                <td rowspan="2">: </td>
            </tr>
            <tr>
                <td><i>Payment For</i></td>
                <td></td>
            </tr>
        </table>
    
        <table rules="rows" style="margin-right:10px; margin-bottom:10px; ">
            <tr>
                <td style="width:150px;">Keterangan</td>
                <td style="width:150px;"></td>
                <td>:</td>
            </tr>
        </table>
        
        <table rules="rows" style="margin-right:10px; ">
            <tr>
                <td style="width:150px;"></td>
                <td style="width:150px;"></td>
                <td>
                    <table class="tabel" style="border:solid 1px #666666; width:400px; font-weight:bold; font-size:12px; margin-left:20px;" >
                        <thead>
                            <td><center><b>Nomor Faktur</b></center></td>
                            <td><center><b>Nominal</b></center></td>
                        </thead>
                        <tbody>
                            <?php
                            // query ulang utk detail tanpa memodifikasi subtotal
                            $detail = $conn->prepare("SELECT d.kode_tfk,d.total_tfk FROM finance tfkk
                                LEFT JOIN finance_detail c ON tfkk.id_finance=c.id_finance
                                LEFT JOIN transaksi_faktur d ON d.id_tfk=c.no_kwitansi
                                WHERE tfkk.id_finance=:kode
                                UNION ALL
                                SELECT p.kode_tfk,p.total_tfk FROM finance tfkk
                                LEFT JOIN finance_detail c ON tfkk.id_finance=c.id_finance
                                LEFT JOIN transaksi_faktur_pim p ON p.id_tfk=c.no_kwitansi
                                WHERE tfkk.id_finance=:kode2 AND p.kode_tfk IS NOT NULL");
                            $detail->bindParam(':kode',$kode,PDO::PARAM_STR);
                            $detail->bindParam(':kode2',$kode,PDO::PARAM_STR);
                            $detail->execute();
                            while($det = $detail->fetch(PDO::FETCH_ASSOC)) { ?>
                                <tr>
                                    <td><center><?php echo $det['kode_tfk']; ?></center></td>
                                    <td><center style="float:right;">&nbsp;<?php echo $data->angka($det['total_tfk']); ?></center></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                        <thead>
                            <tr>
                                <th>TOTAL</th>
                                <td><center style="float:right;"><?php echo $data->angka($subtot); ?></center></td>	
                            </tr>
                        </thead>
                    </table>
                </td>
            </tr>
        </table>
       
        <!-- Menggunakan struktur tabel yang sama untuk alignment -->
        <table style="margin-right:10px; margin-top:30px;">
            <tr>
                <td style="width: 175px;"></td>
                <td style="width: 500px;">
                    <div style="text-align: center; margin-left: 250px;">
                        Jakarta, <?php echo($date->tgl_indo($view['tanggal_faktur'])); ?>
                        <br><br><br><br><br><br>
                        <u><b>Rizki Ardi Bachtiar</b></u>
                        <br>
                        <b>PJ. Finance & Accounting Manager</b>
                    </div>
                </td>
            </tr>
        </table>            
        </table>
		<br />
            <script type="text/javascript">window.print();</script>
    </body>
    
    
    
<?php } ?>
</html></html>