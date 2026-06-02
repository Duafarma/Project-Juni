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
		$tabel	= '<tr><td colspan="3">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$pecah	= explode('_', $cari);
		$rank	= empty($pecah[0]) ? 10 : $pecah[0];
		$tgl1	= empty($pecah[1]) ? "" : "AND C.tgl_tre>='$pecah[1]'";
		$tgl2	= empty($pecah[2]) ? "" : "AND C.tgl_tre<='$pecah[2]'";
		$conn	= $base->open();
		$tabel	= '';
		$no		= 1;
		$master	= $conn->prepare("SELECT D.nama_pro, D.total FROM(SELECT A.nama_pro, (SELECT SUM(B.jumlah_trd) FROM transaksi_receivedetail AS B LEFT JOIN transaksi_receive AS C ON B.id_tre=C.id_tre WHERE B.id_pro=A.id_pro $tgl1 $tgl2) AS total FROM produk AS A GROUP BY A.id_pro) AS D ORDER BY D.total DESC LIMIT :rank");
		$master->bindParam(':rank', $rank, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['nama_pro'].'</td><td>'.$data->angka($hasil['total']).'</td></tr>';
			$no++;
		}
		$navi	= '';
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>