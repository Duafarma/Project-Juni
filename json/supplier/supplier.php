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
		$jumlah	= $conn->query("SELECT COUNT(A.id_sup) AS total FROM supplier AS A INNER JOIN kategori_supplier AS B ON A.id_ksp=B.id_ksp INNER JOIN supplier_alamat AS C ON A.id_sup=C.id_sup WHERE A.nama_sup LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_sup, A.kode_sup, A.nama_sup,A.status_sup, A.cp_sup, A.npwp_sup, B.nama_ksp, C.telp_sal, C.email_sal FROM supplier AS A INNER JOIN kategori_supplier AS B ON A.id_ksp=B.id_ksp INNER JOIN supplier_alamat AS C ON A.id_sup=C.id_sup WHERE A.nama_sup LIKE '%$cari%' AND A.status_sup=:active  ORDER BY A.nama_sup ASC LIMIT :mulai, :maxi");
				$master->bindParam(':active', $active, PDO::PARAM_STR);

		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++; 
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="'.$data->sistem('url_sis').'/supplier/e/'.$hasil['id_sup'].'"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'supplier\', \'delete\', \''.$hasil['id_sup'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td><a href="supplier/v/'.$hasil['id_sup'].'">'.$hasil['nama_sup'].'</a></td><td>'.$hasil['nama_ksp'].'</td><td>'.$hasil['npwp_sup'].'</td><td>'.$hasil['telp_sal'].'</td><td>'.$hasil['email_sal'].'</td><td><center>'.$edit.$delete.'</center></td></tr>';
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