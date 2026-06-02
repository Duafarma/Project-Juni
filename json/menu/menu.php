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
		$tabel	= '<tr><td colspan="6">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_menu) AS total FROM menu AS A LEFT JOIN kategori_menu AS B ON A.id_kmu=B.id_kmu LEFT JOIN icon AS C ON A.id_icon=C.id_icon WHERE A.nama_menu LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_menu, A.nama_menu, A.urutan_menu, B.nama_kmu, C.nama_icon FROM menu AS A LEFT JOIN kategori_menu AS B ON A.id_kmu=B.id_kmu LEFT JOIN icon AS C ON A.id_icon=C.id_icon WHERE A.nama_menu LIKE '%$cari%' ORDER BY B.urutan_kmu ASC, A.urutan_menu ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="#modal1" onclick="crud(\'menu\', \'update\', \''.$hasil['id_menu'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'menu\', \'delete\', \''.$hasil['id_menu'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['nama_kmu'].'</td><td>'.$hasil['nama_menu'].'</td><td>'.$hasil['nama_icon'].'</td><td><center>'.$hasil['urutan_menu'].'</center></td><td><center>'.$edit.$delete.'</center></td></tr>';
		}
		$navi	= $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>