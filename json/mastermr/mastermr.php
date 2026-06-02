<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/paging.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$paging	= new Paging;
	$conn	= $base->open();
	$conn->exec("CREATE TABLE IF NOT EXISTS `master_mr_baru` (
		`id_mr` int(11) NOT NULL AUTO_INCREMENT,
		`nama_mr` varchar(255) NOT NULL DEFAULT '',
		`area` varchar(255) NOT NULL DEFAULT '',
		`ket` text DEFAULT NULL,
		`created_at` datetime NOT NULL,
		`created_by` varchar(100) NOT NULL,
		`updated_at` datetime DEFAULT NULL,
		`updated_by` varchar(100) DEFAULT NULL,
		PRIMARY KEY (`id_mr`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
	
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	
	//POST DATA
	$cari	= $secu->injection(@$_GET['caridata']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="5">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		$jumlahStmt = $conn->prepare("SELECT COUNT(id_mr) AS total FROM master_mr_baru WHERE nama_mr LIKE :cari OR area LIKE :cari");
		$jumlahStmt->bindValue(':cari', '%'.$cari.'%', PDO::PARAM_STR);
		$jumlahStmt->execute();
		$jumlah	= $jumlahStmt->fetch(PDO::FETCH_ASSOC);
		
		$master	= $conn->prepare("SELECT id_mr, nama_mr, area, ket FROM master_mr_baru WHERE nama_mr LIKE :cari OR area LIKE :cari ORDER BY nama_mr ASC LIMIT :mulai, :maxi");
		$master->bindValue(':cari', '%'.$cari.'%', PDO::PARAM_STR);
		$master->bindValue(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindValue(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		
		while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="#modal1" onclick="crud(\'mastermr\', \'update\', \''.$hasil['id_mr'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'mastermr\', \'delete\', \''.$hasil['id_mr'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			
			$keterangan = empty($hasil['ket']) ? '-' : $hasil['ket'];
			
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['nama_mr'].'</td>
							<td>'.$hasil['area'].'</td>
							<td>'.$keterangan.'</td>
							<td><center>'.$edit.$delete.'</center></td>
						</tr>';
		}
		$navi	= $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($json));
?>
