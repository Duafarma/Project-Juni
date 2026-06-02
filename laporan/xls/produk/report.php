<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=produk.xls");
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
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Outlet</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="9">HISTORIS PRODUK BERDASARKAN KARTU STOK</th>
            </tr>
        	<tr>
            	<th colspan="9"><?php echo($data->sistem('pt_sis')); ?></th>
            </tr>
            <tr>
            	<td colspan="9"></td>
            </tr>
        </table>
		<table>
        	<!--<tr>-->
         <!--   	<td>NamaProduk</td>-->
         <!--   	<td><center>:</center></td>-->
         <!--   	<td colspan="7"></td>-->
         <!--   </tr>-->
        </table>
    	<table border="1">
            <thead>
                <tr>
                    <th><center>No.</center></th>
                    <th>Jenis</th>
                    <th>Nama Produk</th>
                    <th>Supplier/Outlet</th>
                    <th>Kode</th>
                    <th>Faktur</th>
                    <th>Tanggal</th>
                    <th>Batchcode</th>
                    <th>Gudang</th>
                    <th><div align="right">In</div></th>
                    <th><div align="right">Out</div></th>
                </tr>
            </thead>
            <tbody>
            <?php
                $in		= 0;
                $out	= 0;
                $tin	= 0;
                $tout	= 0;
                $no		= 1;
                $proses	= $conn->query("CALL reportproduk('$kode')");
                $master	= $conn->prepare("SELECT A.jenis_rpo, A.bcode_rpo, A.mitra_rpo, A.kode_rpo, A.faktur_rpo, A.tgl_rpo, A.jumlah_rpo,A.gudang, B.nama_pro FROM report_produk AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro ORDER BY A.id_pro ASC");
                $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                $master->execute();
                while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                    $in		= ($hasil['jenis_rpo']=='Order' || $hasil['jenis_rpo']=='TF-IN') ? $hasil['jumlah_rpo'] : 0;
					$out	= ($hasil['jenis_rpo']=='Sales' || $hasil['jenis_rpo']=='TF-OUT'|| $hasil['jenis_rpo']=='Donasi' || $hasil['jenis_rpo']=='Pinjaman' || $hasil['jenis_rpo']== 'Retur' || $hasil['jenis_rpo']== 'Lain-Lain') ? $hasil['jumlah_rpo'] : 0;
                    $tin	+= $in;
                    $tout	+= $out;
            ?>
                <tr>
                    <td><center><?php echo($no); ?></center></td>
                    <td><?php echo($hasil['jenis_rpo']); ?></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($hasil['mitra_rpo']); ?></td>
                    <td><?php echo($hasil['kode_rpo']); ?></td>
                    <td><?php echo($hasil['faktur_rpo']); ?></td>
                    <td><?php echo($hasil['tgl_rpo']); ?></td>
                    <td><?php echo($hasil['bcode_rpo']); ?></td>
                    <td><?php echo($hasil['gudang']); ?></td>
                    <td><div align="right"><?php echo($data->angka($in)); ?></div></td>
                    <td><div align="right"><?php echo($data->angka($out)); ?></div></td>
                    
                </tr>
            <?php $no++; } ?>
                <tr>
                    <th colspan="7"><div align="right">TOTAL</div></th>
                    <th><div align="right"><?php echo($data->angka($tin)); ?></div></th>
                    <th><div align="right"><?php echo($data->angka($tout)); ?></div></th>
                </tr>
                
                <!--<tr>-->
                <!--    <th></th>-->
                <!--    <th colspan="7"><div align="right">BALANCE</div></th>-->
                <!--    <th colspan="2"><div align="center"><?php echo($data->angka($tin - $tout)); ?></div></th>-->
                <!--</tr>-->
            </tbody>
            
        </table>
    </body>
<?php } ?>
</html>
