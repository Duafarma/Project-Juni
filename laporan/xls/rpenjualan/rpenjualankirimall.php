<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=report_penyiapan_barang_All.xls");
	require_once('../../../config/connection/connection.php');
	require_once('../../../config/connection/security.php');
	require_once('../../../config/function/data.php');
	require_once('../../../config/function/date.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$date	= new Date;
	$tanggal= date('Y-m-d');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$outlet	= empty($pecah[0]) ? "" : "AND B.id_out='$pecah[0]'"; 
	$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
	$tgl1	= empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'"; 
	$tgl2	= empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Dokumen</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="7">Report Penyiapan Barang</th>
            </tr>
            <tr>
            	<td colspan="7"></td>
            </tr>
        </table>
        <tr>
            Tanggal : <?php echo($date->getHari(date('Y-m-d')).', '.$date->tgl_indo(date('Y-m-d'))); ?>
        </tr>
    	<table border="1">
        	<thead>
            <tr>
                    <th><center>#</center></th>
                     <th><center>Gudang</center></th>
                    <th><center>Nama Outlet</center></th>
                    <th>Nomor Faktur</th>
                    <th>Tanggal Faktur</th>
                    <th>Jam</th>
                    <th>Jumlah Packing</th>
                    <th>Ceklist</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th>TTD Penerima</th>

                   
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
                $rows = array();
                $local = "APL_01";
				// $master	= $conn->prepare("SELECT 'local' as source, A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, B.pajak_tfk,B.pajak_tfkt, B.kode_tfk, B.tgl_tfk, B.po_tfk, B.tglpo_tfk, B.tgl_limit, B.status_tfk, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak,  C.nama_pro, C.kategori_obat, C.kode_pro,C.kode_produk_jadi, D.nama_out,D.kode_out, D.id_kot, D.ofcode_out, E.no_bcode, E.tgl_expired,F.pengiriman_ola,H.nama_rkb, G.kode_kot FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk AS C ON A.id_pro=C.id_pro LEFT JOIN outlet AS D ON B.id_out=D.id_out  LEFT JOIN produk_stokdetail AS E ON A.id_psd=E.id_psd LEFT JOIN outlet_alamat AS F ON D.id_out=F.id_out LEFT JOIN kategori_outlet AS G ON D.id_kot=G.id_kot LEFT JOIN regional_kabupaten AS H ON F.id_rkb=H.id_rkb WHERE A.created_at >= DATE_SUB(NOW(), INTERVAL 620 DAY) AND A.id_tfd!='' $outlet $produk $tgl1 $tgl2 ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC");
			    $master	= $conn->prepare("SELECT 'A' as source, B.created_at, B.kode_tfk, B.tgl_tfk,B.status_tfkkb,B.status_tfkkf, B.po_tfk,B.status_dokumen,B.status_f_pajak, B.status_failing, B.tglpo_tfk, B.tgl_limit, B.status_tfk, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, D.nama_out, D.ofcode_out FROM transaksi_faktur AS B LEFT JOIN outlet AS D ON B.id_out=D.id_out WHERE MONTH(B.tgl_tfk) = MONTH(CURRENT_DATE()) AND YEAR(B.tgl_tfk) = YEAR(CURRENT_DATE()) AND B.kode_tfk LIKE '%$cari%' ORDER BY B.tgl_tfk DESC, B.kode_tfk DESC");
				$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
                $rows = $master->fetchAll(PDO::FETCH_ASSOC);
				// get others all system
                $froms = array("APL_01", "APL02", "APL03","APL05");
                foreach ($froms as $key => $from) {
                    if ($from != $local) {
                        $target 	= $data->get_apl($from);
                        $targetUrl 	= $target[0]['base_url_apl'];
                        $targetKey  = $target[0]['key_apl'];
                        $encrypt    = md5($tanggal . "#" . $targetKey);
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/getPenjualankirimall.php?encrypt=" . $encrypt . "&key=" . $cari);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $res = curl_exec($ch);
                        // curl handling error
                        if (curl_errno($ch)) {
                            $error_msg = curl_error($ch);
                        }
                        curl_close ($ch);
                        if (isset($error_msg)) {
                            $curl_result = $error_msg;
                        } else {
                            $curl_result = json_decode($res, true)['result'];                            
                            $rows = array_merge($rows, $curl_result);
                        }
                    }
                }
                // resorting all
                usort($rows, function($a, $b) {
                    return $b['tgl_tfk'] <=> $a['tgl_tfk'];
                });
				foreach($rows as $row => $hasil) {
					$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	  <td><center><?php echo($hasil['source']); ?></center></td>
                    <td><center><?php echo($hasil['nama_out']); ?></center></td>
                    <td><?php echo($hasil['kode_tfk']); ?></td>
                    <td><center><?php echo($hasil['tgl_tfk']); ?></center></td>
                    <td><center><?php echo($hasil['created_at']); ?></center></td>
                    <td></td>
                    <td></td>
                    <td> Kirim Barang</td>
                    <td></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html>
