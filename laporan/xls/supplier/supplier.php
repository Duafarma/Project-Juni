<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
	header("Content-Type: application/force-download");
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Laporan"); 
	header("content-disposition:attachment; filename=supplier.xls");
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
?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Data Supllier</title>
    </head>


    <body>
		<table>
        	<tr>
            	<th colspan="18">DATA SUPPLIER</th>
            </tr>
        	<tr>
            	<th colspan="18"><?php echo($data->sistem('pt_sis')); ?></th>
            </tr>
            <tr>
            	<td colspan="18"></td>
            </tr>
        </table>
    	<table border="1">
        	<thead>
            	<tr>
                    <th><center>NO</center></th>
                    <th>NAMA SUPPLIER</th>
                    <th>CONTACT PERSON</th>
                    <th>KATEGORI</th>
                    <th>NPWP</th>
                    <th>TELP.</th>
                    <th>HP</th>
                    <th>FAX</th>
                    <th>EMAIL</th>
                    <th>WEBSTIE</th>
                    <th>PROVINSI</th>
                    <th>KABUPATEN</th>
                    <th>KODE POS</th>
                    <th>ALAMAT</th>
                    <th>TOP</th>
                    <th>MINIMAL</th>
                    <th>DISKON <=MINIMAL</th>
                    <th>DISKON >MINIMAL</th>
				</tr>
    		</thead>
            <tbody>
			<?php
				$nomor	= 1;
				$master	= $conn->prepare("SELECT A.kode_sup, A.nama_sup, A.cp_sup, A.npwp_sup, B.telp_sal, B.hp_sal, B.fax_sal, B.email_sal, B.web_sal, B.kopos_sal, B.alamat_sal, E.nama_rkb, F.nama_rpo, D.nama_ksp, C.top_sdi, C.parameter_sdi, C.diskon1_sdi, C.diskon2_sdi FROM supplier AS A LEFT JOIN supplier_alamat AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_diskon AS C ON A.id_sup=C.id_sup INNER JOIN kategori_supplier AS D ON A.id_ksp=D.id_ksp LEFT JOIN regional_kabupaten AS E ON B.id_rkb=E.id_rkb LEFT JOIN regional_provinsi AS F ON E.id_rpo=F.id_rpo WHERE A.nama_sup LIKE '%$search%' ORDER BY A.nama_sup");
				$master->execute();
				while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			?>
            	<tr>
                	<td><center><?php echo($nomor); ?></center></td>
                	<td><?php echo($hasil['nama_sup']); ?></td>
                	<td><?php echo($hasil['cp_sup']); ?></td>
                	<td><?php echo($hasil['nama_ksp']); ?></td>
                	<td><?php echo($hasil['npwp_sup']); ?></td>
                    <td><?php echo($hasil['telp_sal']); ?></td>
                	<td><?php echo($hasil['hp_sal']); ?></td>
                	<td><?php echo($hasil['fax_sal']); ?></td>
                	<td><?php echo($hasil['email_sal']); ?></td>
                	<td><?php echo($hasil['web_sal']); ?></td>
                	<td><?php echo($hasil['nama_rpo']); ?></td>
                	<td><?php echo($hasil['nama_rkb']); ?></td>
                	<td><?php echo($hasil['kopos_sal']); ?></td>
                	<td><?php echo($hasil['alamat_sal']); ?></td>
                	<td><?php echo($hasil['top_sdi']); ?> Hari</td>
                	<td><?php echo($hasil['parameter_sdi']); ?></td>
                	<td><?php echo($hasil['diskon1_sdi']); ?>%</td>
                	<td><?php echo($hasil['diskon2_sdi']); ?>%</td>
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
