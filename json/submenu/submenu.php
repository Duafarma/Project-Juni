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
		$tabel	= '<tr><td colspan="7">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_smu) AS total FROM sub_menu AS A LEFT JOIN menu AS B ON A.id_menu=B.id_menu LEFT JOIN kategori_menu AS C ON B.id_kmu=C.id_kmu WHERE A.nama_smu LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_smu, A.nama_smu, A.url_smu, A.urutan_smu, B.nama_menu, C.nama_kmu FROM sub_menu AS A LEFT JOIN menu AS B ON A.id_menu=B.id_menu LEFT JOIN kategori_menu AS C ON B.id_kmu=C.id_kmu WHERE A.nama_smu LIKE '%$cari%' ORDER BY C.urutan_kmu ASC, B.urutan_menu ASC, A.urutan_smu ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="#modal1" onclick="crud(\'submenu\', \'update\', \''.$hasil['id_smu'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'submenu\', \'delete\', \''.$hasil['id_smu'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['nama_kmu'].'</td><td>'.$hasil['nama_menu'].'</td><td>'.$hasil['nama_smu'].'</td><td>'.$hasil['url_smu'].'</td><td><center>'.$hasil['urutan_smu'].'</center></td><td><center>'.$edit.$delete.'</center></td></tr>';
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