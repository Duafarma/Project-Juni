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
		$pecah	= explode('_', $cari);
		$search	= @$pecah[0];
		$tgl1	= empty($pecah[1]) ? "" : "AND DATE(A.created_at)>='$pecah[1]'";
		$tgl2	= empty($pecah[2]) ? "" : "AND DATE(A.created_at)<='$pecah[2]'";
		$conn	= $base->open();
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_riwayat) AS total FROM riwayat AS A LEFT JOIN adminz AS B ON A.created_by=B.id_adm WHERE A.id_riwayat!='' $tgl1 $tgl2 AND (A.menu_riwayat LIKE '%$search%' OR A.status_riwayat LIKE '%$search%' OR A.ket_riwayat LIKE '%$search%' OR B.nama_adm LIKE '%$search%')")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_riwayat, A.menu_riwayat, A.kode_riwayat, A.status_riwayat, A.ket_riwayat, A.created_at, B.nama_adm FROM riwayat AS A LEFT JOIN adminz AS B ON A.created_by=B.id_adm WHERE A.id_riwayat!='' $tgl1 $tgl2 AND (A.menu_riwayat LIKE '%$search%' OR A.status_riwayat LIKE '%$search%' OR A.ket_riwayat LIKE '%$search%' OR B.nama_adm LIKE '%$search%') ORDER BY A.created_at DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['menu_riwayat'].'</td><td>'.$hasil['kode_riwayat'].'</td><td>'.$hasil['status_riwayat'].'</td><td>'.$hasil['ket_riwayat'].'</td><td>'.$hasil['nama_adm'].'</td><td><center>'.$hasil['created_at'].'</center></td></tr>';
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