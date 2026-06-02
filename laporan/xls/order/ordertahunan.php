<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=Order Pajak tahunan.xls");
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
        <title>Report Penjualan</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="18">Order Dan Penerimaan Barang Pajak 2024</th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
                <tr>
                    <th>Nomor PO</th>
                    <th><center>Tgl. PO</center></th>
                    <th>Nama Barang</th>
                    <th>Qty Order</th>
                    
                    <th>No Penerimaan</th>
                     <th>Nama Barang</th>
                    <th><center>Tgl. Diterima</center></th>
                    <th>Qty Diterima</th>
                </tr>
    		</thead>
            <tbody>
<?php
$nomor = 1;
$lastKodeTor = ''; // Menyimpan kode_tor sebelumnya
$lastIdPro = '';   // Menyimpan id_pro sebelumnya

$master = $conn->prepare("
    SELECT E.kode_tor, E.tgl_tor, B.nama_pro, A.id_pro,
           A.jumlah_tod, 
           F.tgl_tre,
           F.fak_tre,
           G.jumlah_trd
    FROM transaksi_orderdetail AS A
    LEFT JOIN produk AS B ON A.id_pro = B.id_pro
    LEFT JOIN transaksi_order AS E ON A.id_tor = E.id_tor
    LEFT JOIN transaksi_receive AS F ON E.id_tor = F.id_tor
    LEFT JOIN transaksi_receivedetail AS G ON F.id_tre = G.id_tre AND A.id_pro = G.id_pro
    WHERE YEAR(E.tgl_tor) = 2024
      AND YEAR(F.tgl_tre) = 2024
    ORDER BY E.kode_tor DESC
");
$master->execute();
while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
?>
    <tr>
        <!--<td><center><?php echo($nomor); ?></center></td>-->
        <td><?php echo($hasil['kode_tor']); ?></td>
        <td><center><?php echo($hasil['tgl_tor']); ?></center></td>
        <td><center><?php echo($hasil['nama_pro']); ?></center></td>
        
        <?php
        // Menampilkan jumlah TOD hanya sekali per kode_tor dan id_pro
        if ($lastKodeTor !== $hasil['kode_tor'] || $lastIdPro !== $hasil['id_pro']) {
            echo '<td><center>' . $hasil['jumlah_tod'] . '</center></td>';
        } else {
            echo '<td><center>0</center></td>';
        }
        
        // Update nilai untuk kode_tor dan id_pro
        $lastKodeTor = $hasil['kode_tor'];
        $lastIdPro = $hasil['id_pro'];
        ?>
        
        <td><center><?php echo($hasil['fak_tre'] ?: 'Belum Diterima'); ?></center></td>
        <td><center><?php echo($hasil['nama_pro']); ?></center></td>
        <td><center><?php echo($hasil['tgl_tre'] ?: 'Belum Diterima'); ?></center></td>
        <td><center><?php echo($hasil['jumlah_trd'] ?: 0); ?></center></td>
    </tr>
<?php 
    $nomor++; 
}
?>

            </tbody>
        </table>
        <?php $conn	= $base->close(); ?>
    </body>
<?php } ?>
</html> 
