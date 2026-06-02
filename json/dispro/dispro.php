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
		$tabel	= '<tr><td colspan="4">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$pecah	= explode('outlet', $cari);
		$kode	= base64_decode(@$pecah[0]);
		$search	= @$pecah[1];
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_pds) AS total FROM produk_diskon AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro WHERE A.id_out='$kode' AND (B.nama_pro LIKE '%$search%' OR B.kode_pro LIKE '%$search%')")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_pds, A.persen_pds, B.nama_pro FROM produk_diskon AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro WHERE A.id_out=:kode AND (B.nama_pro LIKE '%$search%' OR B.kode_pro LIKE '%$search%') ORDER BY B.nama_pro ASC LIMIT :mulai, :maxi");
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_pds']);
// 			$edit	= ($data->akses($admin, 'outlet', 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'dispro\', \'update\', \''.$uniq.'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['persen_pds'].'%</td><td><center></center></td></tr>';
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