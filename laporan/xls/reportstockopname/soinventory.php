<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=reportstockopname.xls");
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
	$cari	= $secu->injection(@$_GET['key']);
	$search	= $data->cekcari($cari, '-', ' ');
	$active	= 'Active';
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Stock Opname</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="10"><center>Data STOCK OBAT Inventory</center></th>
            </tr>
            	<tr>
            	<th colspan="11"><center>PT. DUA FARMA MAHAKARSA</center></th>
            </tr>
           
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th colspan="4">STOK INVENTORY</th>
                    <th  colspan="2">STOK OPNAME</th>
                    <th colspan="2">STATUS</th
				</tr>
				
				  <tr>
                     <th>#</th>
                    <th><center>Nama Produk</center></th>
                    <th><center>No. Batch</center></th>
                    <th><center>QTY</center></th>
                    
                    <th><center>No. Batch</center></th>
                    <th><center>QTY</center></th>
                    <th><center>Selisih</center></th>
                    <th><center>Status</center></th>
                </tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
				$tabel	= '';
            	$active	= 'Active';
        		$no		= $mulai;
        		$master	= $conn->prepare("SELECT A.id_psd, A.no_bcode, A.status_barang, A.tgl_expired,A.status, A.gudang, A.tgl_psd, A.qty_so, A.sisa_psd, B.id_pro,B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.status_barang=:active AND B.nama_pro LIKE '%$cari%' AND C.status_phg=:active ORDER BY B.nama_pro ASC");
        		$master->bindParam(':active', $active, PDO::PARAM_STR);
        		$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				                
				                // $so	= $data->so($hasil['id_pro']);
				               

                			
                			 $a= $hasil['sisa_psd'];
                            $b= $hasil['qty_so'];
                            $selisih = ($b - $a);
                				
                			
			?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                     <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['sisa_psd']); ?></td>
                     <td><?php echo($hasil['no_bcode']); ?></td>
                    <td><?php echo($hasil['qty_so']); ?></td>
                    <td><?php echo($selisih); ?></td>
                    <td><?php echo($hasil['status']); ?></td>
             
                    


                </tr>
			<?php
				$nomor++;
                }
			?>
            </tbody>
        </table>
    </body>
<?php } ?>
</html>
