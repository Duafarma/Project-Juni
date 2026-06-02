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
		$tabel	= '<tr><td colspan="9">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$pecah	= explode('_', $cari);
		$obat	= empty($pecah[0]) ? "" : "AND C.id_pro='$pecah[0]'";
		$tgl1	= empty($pecah[1]) ? "" : "AND B.tgl_tfk>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND B.tgl_tfk<='$pecah[2]'"; 
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_tfd) AS total FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk_stokdetail AS C ON A.id_psd=C.id_psd LEFT JOIN produk AS D ON C.id_pro=D.id_pro LEFT JOIN kategori_produk AS E ON D.id_kpr=E.id_kpr LEFT JOIN satuan_produk AS F ON D.id_spr=F.id_spr WHERE A.id_tfd!='' $obat $tgl1 $tgl2")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.jumlah_tfd, B.kode_tfk, B.tgl_tfk, C.no_bcode, C.tgl_expired, D.kode_pro, D.nama_pro, D.berat_pro, E.nama_kpr, F.nama_spr FROM transaksi_fakturdetail AS A LEFT JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN produk_stokdetail AS C ON A.id_psd=C.id_psd LEFT JOIN produk AS D ON C.id_pro=D.id_pro LEFT JOIN kategori_produk AS E ON D.id_kpr=E.id_kpr LEFT JOIN satuan_produk AS F ON D.id_spr=F.id_spr WHERE A.id_tfd!='' $obat $tgl1 $tgl2 ORDER BY B.tgl_tfk DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++; 
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['kode_tfk'].'</td><td>'.$hasil['tgl_tfk'].'</td><td>'.$hasil['kode_pro'].'</td><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td><td>'.$hasil['no_bcode'].'</td><td>'.$hasil['tgl_expired'].'</td><td>'.$data->angka($hasil['jumlah_tfd']).'</td></tr>';
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