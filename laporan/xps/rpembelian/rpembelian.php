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
	$tanggal= date('Y-m-d');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	$cari	= $secu->injection(@$_GET['key']);
	$pecah	= explode('_', $cari);
	$supp	= empty($pecah[0]) ? "" : "AND B.id_sup='$pecah[0]'"; 
	$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
	$tgl1	= empty($pecah[2]) ? "" : "AND C.tgl_tor>='$pecah[2]'"; 
	$tgl2	= empty($pecah[3]) ? "" : "AND C.tgl_tor<='$pecah[3]'"; 
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Report Pembelian</title>
        <link href="<?php echo($data->sistem('url_sis')."/config/css/laporan.css"); ?>" rel="stylesheet">
		<style type="text/css" media="print">
          @page { size: portrait; }
        </style>
    </head>
    <body>
    	<h4>REPORT PEMBELIAN</h4>
    	<table class="tabel">
        	<thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Tgl. PO</center></th>
                    <th>Nomor PO</th>
                    <th>Supplier</th>
                    <th>Nomor Faktur</th>
                    <th><center>Tgl. Faktur</center></th>
                    <th>Nama Barang</th>
                    <th>Kode Barang</th>
                    <th>No. Batch</th>
                    <th><center>Exp. Date</center></th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Diskon</th>
                    <th>Total</th>
                    <th><center>Jatuh Tempo</center></th>
                    <th>Remaining</th>
                    <th>Status</th>
                </tr>
    		</thead>
            <tbody>
            <?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.bcode_trd, A.tbcode_trd, A.jumlah_trd, A.harga_trd, A.diskon_trd, A.total_trd, B.fak_tre, B.tglfak_tre, B.tgl_limit, B.status_tre, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, C.tgl_tor, C.kode_tor, D.nama_pro, D.kode_pro, F.nama_sup FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre LEFT JOIN transaksi_order AS C ON B.id_tor=C.id_tor LEFT JOIN produk AS D ON A.id_pro=D.id_pro LEFT JOIN supplier AS F ON B.id_sup=F.id_sup WHERE A.id_trd!='' $supp $produk $tgl1 $tgl2 ORDER BY B.tgl_tre DESC, A.created_at DESC");
				$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
					$status	= ($hasil['status_tre']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tre']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			?>
				<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                    <td><center><?php echo($hasil['tgl_tor']); ?></center></td>
                    <td><?php echo($hasil['kode_tor']); ?></td>
                    <td><?php echo($hasil['nama_sup']); ?></td>
                    <td><?php echo($hasil['fak_tre']); ?></td>
                    <td><center><?php echo($hasil['tglfak_tre']); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['kode_pro']); ?></td>
                    <td><?php echo($hasil['bcode_trd']); ?></td>
                    <td><?php echo($hasil['tbcode_trd']); ?></td>
                    <td><?php echo($data->angka($hasil['jumlah_trd'])); ?></td>
                    <td><?php echo($data->angka($hasil['harga_trd'])); ?></td>
                    <td><?php echo($hasil['diskon_trd']); ?></td>
                    <td><?php echo($data->angka($hasil['total_trd'])); ?></td>
                    <td><center><?php echo($hasil['tgl_limit']); ?></center></td>
                    <td><center><?php echo($hasil['jarak']); ?></center></td>
                    <td><center><?php echo($status); ?></center></td>
				</tr>
			<?php $nomor++; } ?>
            </tbody>
        </table>
		<?php $conn	= $base->open(); ?>
		<script type="text/javascript">window.print();</script>
    </body>
<?php } ?>
</html>
