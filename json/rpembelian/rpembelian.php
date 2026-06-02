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
	$tanggal= date('Y-m-d');
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
		$supp	= empty($pecah[0]) ? "" : "AND B.id_sup='$pecah[0]'"; 
		$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'"; 
		$tgl1	= empty($pecah[2]) ? "" : "AND C.tgl_tor>='$pecah[2]'"; 
		$tgl2	= empty($pecah[3]) ? "" : "AND C.tgl_tor<='$pecah[3]'"; 
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_trd) AS total FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre INNER JOIN transaksi_order AS C ON B.id_tor=C.id_tor WHERE A.id_trd!='' $supp $produk $tgl1 $tgl2")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.bcode_trd, A.tbcode_trd, A.jumlah_trd, A.harga_trd, A.diskon_trd, A.total_trd, B.fak_tre, B.tglfak_tre, B.tgl_limit, B.status_tre, TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak, C.tgl_tor, C.kode_tor, D.nama_pro, D.kode_pro, F.nama_sup FROM transaksi_receivedetail AS A INNER JOIN transaksi_receive AS B ON A.id_tre=B.id_tre LEFT JOIN transaksi_order AS C ON B.id_tor=C.id_tor LEFT JOIN produk AS D ON A.id_pro=D.id_pro LEFT JOIN supplier AS F ON B.id_sup=F.id_sup WHERE A.id_trd!='' $supp $produk $tgl1 $tgl2 ORDER BY B.tgl_tre DESC, A.created_at DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$status	= ($hasil['status_tre']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tre']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td><center>'.$hasil['tgl_tor'].'</center></td><td>'.$hasil['kode_tor'].'</td><td>'.$hasil['nama_sup'].'</td><td>'.$hasil['fak_tre'].'</td><td><center>'.$hasil['tglfak_tre'].'</center></td><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['kode_pro'].'</td><td>'.$hasil['bcode_trd'].'</td><td>'.$hasil['tbcode_trd'].'</td><td>'.$data->angka($hasil['jumlah_trd']).'</td><td>'.$data->angka($hasil['harga_trd']).'</td><td>'.$hasil['diskon_trd'].'</td><td>'.$data->angka($hasil['total_trd']).'</td><td><center>'.$hasil['tgl_limit'].'</center></td><td><center>'.$hasil['jarak'].'</center></td><td><center>'.$status.'</center></td></tr>';
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