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
		$tgl1	= empty($pecah[1]) ? "" : "AND DATE(A.tgl_limit)>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND DATE(A.tgl_limit)<='$pecah[2]'"; 
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_tre) AS total FROM transaksi_receive AS A LEFT JOIN transaksi_order AS B ON A.id_tor=B.id_tor LEFT JOIN supplier AS C ON A.id_sup=C.id_sup WHERE (B.kode_tor LIKE '%$search%' OR C.nama_sup LIKE '%$search%') $tgl1 $tgl2")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.total_tre, A.tgl_tre, A.tgl_limit, A.status_tre, B.kode_tor, C.nama_sup FROM transaksi_receive AS A LEFT JOIN transaksi_order AS B ON A.id_tor=B.id_tor LEFT JOIN supplier AS C ON A.id_sup=C.id_sup WHERE (B.kode_tor LIKE '%$search%' OR C.nama_sup LIKE '%$search%') $tgl1 $tgl2 ORDER BY A.tgl_limit ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$status	= ($hasil['status_tre']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tre']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['kode_tor'].'</td><td>'.$hasil['nama_sup'].'</td><td>'.$hasil['tgl_tre'].'</td><td>'.$data->angka($hasil['total_tre']).'</td><td>'.$status.'</td><td><span class="text text-danger">'.$hasil['tgl_limit'].'</span></td></tr>';
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