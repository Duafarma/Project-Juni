<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=reportstockopname_A_tahunan.xls");
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
            	<th colspan="10"><center>Data STOCK OBAT</center></th>
            </tr>
            	<tr>
            	<th colspan="11"><center>PT. DUA FARMA MAHAKARSA</center></th>
            </tr>
            <tr>
                    <th colspan="12"><center><?php echo($date->getHari(date('Y-m-d')).', '.$date->tgl_indo(date('Y-m-d'))); ?></center></th>
            </tr>
            <tr>
            	<td colspan="10"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>#</center></th>
                    <th>NAMA PRODUK</th>
                    <th>STOK AWAL</th>
                    <th>STOK MASUK</th>
                    <th>TERJUAL</th>
                    <th>STOK AKHIR</th>
                    <TH>HNA</TH>
                    <th>TOTAL</th>
                   
				</tr>
    		</thead>
            <tbody>
			<?php
					$nomor	= 1;
					$tabel	= '';
            		$active	= 'Active';
            		$no		= $mulai;
	            	$master	= $conn->prepare("SELECT  B.id_pro, B.nama_pro, B.berat_pro,C.harga_phg FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro   WHERE  C.status_phg=:active GROUP BY B.nama_pro");
            		$master->bindParam(':active', $active, PDO::PARAM_STR);
            		$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				                
                                $so 	= $data->so($hasil['id_pro']);
				                $awal	= $data->stokawalltahun($hasil['id_pro']);
				                $in		= $data->stokitahun($hasil['id_pro']);
                                $int    = $data->stokintftahunan($hasil['id_pro']);
                				$outb	= $data->stokouttahunan($hasil['id_pro']);
                				$peng   = $data->testtahunan($hasil['id_pro']);
                			    
                				$outrr	= $data->stokoutrtahunan($hasil['id_pro']);
                				$outrrr	= $data->stokoutrrtahunan($hasil['id_pro']);

                                $totalin = ($in + $int);
                                $totalout = ($outb + $outrr + $outrrr + $peng);

                				$akhir	= ($awal + $totalin) - $totalout;
                                $selisih = ($so -  $akhir);

                				$harga=$hasil['harga_phg'];
                				$total = ($so * $harga);
                				
                				
			?>
                <tr>
                    <td><center><?php echo($nomor); ?></center></td>
                    <td><?php echo($hasil['nama_pro']); ?></td>
                    <td><?php echo($awal); ?></td>
                    <td><?php echo($totalin); ?></td>
                    <td><?php echo($totalout); ?></td>
                    <td><?php echo($so); ?></td>
                    <td><?php echo($hasil['harga_phg']); ?></td>
                    <td><?php echo($total); ?></td>

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
