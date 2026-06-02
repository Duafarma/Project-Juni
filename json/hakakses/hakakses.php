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
		$jumlah	= $conn->query("SELECT COUNT(id_aak) AS total FROM adminz_akses WHERE jenis_adm LIKE '%$cari%' OR nama_menu LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT id_aak, jenis_adm, nama_menu, tambah_data, edit_data, delete_data FROM adminz_akses WHERE jenis_adm LIKE '%$cari%' OR nama_menu LIKE '%$cari%' ORDER BY jenis_adm ASC, nama_menu ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$edit	= ($data->cekakses($level, 'Akses', 'edit_data')=='Ya') ? '<a href="#modal1" onclick="crud(\'hakakses\', \'update\', \''.$hasil['id_aak'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$no++; 
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['jenis_adm'].'</td><td>'.$hasil['nama_menu'].'</td><td>'.$hasil['tambah_data'].'</td><td>'.$hasil['edit_data'].'</td><td>'.$hasil['delete_data'].'</td><td><center>'.$edit.'</center></td></tr>';
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