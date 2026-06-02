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
		$active	= 'Active';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_phg) AS total FROM produk_harga AS A INNER JOIN produk AS B ON A.id_pro=B.id_pro WHERE B.status_pro='$active' AND B.id_pro='$cari'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.harga_phg, A.hargap_phg, A.status_phg, A.created_at, B.kode_pro, B.nama_pro FROM produk_harga AS A INNER JOIN produk AS B ON A.id_pro=B.id_pro WHERE B.status_pro=:active AND B.id_pro=:produk ORDER BY B.created_at DESC LIMIT :mulai, :maxi");
		$master->bindParam(':produk', $cari, PDO::PARAM_STR);
		$master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td><center>'.$hasil['created_at'].'</center></td><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['kode_pro'].'</td><td><div align="right">'.$data->angka($hasil['harga_phg']).'</div></td><td><div align="right">'.$data->angka($hasil['hargap_phg']).'</div></td><td>'.$hasil['status_phg'].'</td></tr>';
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