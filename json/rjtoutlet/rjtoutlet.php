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
		$tabel	= '<tr><td colspan="8">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$pecah	= explode('_', $cari);
		$cari	= $data->cekcari($pecah[0], '-', ' ');
		$tgl1	= empty($pecah[1]) ? "" : "AND DATE(A.tgl_limit)>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND DATE(A.tgl_limit)<='$pecah[2]'"; 
		//READ DATA
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.kode_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE (A.kode_tfk LIKE '%$cari%' OR B.nama_out LIKE '%$cari%') $tgl1 $tgl2")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.kode_tfk, A.total_tfk, A.tgl_tfk, A.tgl_limit, A.status_tfk, A.po_tfk, B.nama_out FROM transaksi_faktur AS A LEFT JOIN outlet AS B ON A.id_out=B.id_out WHERE (A.kode_tfk LIKE '%$cari%' OR B.nama_out LIKE '%$cari%') $tgl1 $tgl2 ORDER BY A.tgl_limit ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['kode_tfk'].'</td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['tgl_tfk'].'</td><td>'.$hasil['po_tfk'].'</td><td>'.$data->angka($hasil['total_tfk']).'</td><td>'.$status.'</td><td><span class="text text-danger">'.$hasil['tgl_limit'].'</span></td></tr>';
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