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
	$sistem	= $data->sistem('url_sis');
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
		$tabel	= '<tr><td colspan="5">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$jenis	= ($level=='Super') ? "" : "AND A.id_adm='$admin'";
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_adm) AS total FROM adminz AS A LEFT JOIN cabang AS B ON A.id_cabang=B.id_cabang WHERE (nama_adm LIKE '%$cari%' OR email_adm LIKE '%$cari%' OR B.nama_cabang LIKE '%$cari%') $jenis")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_adm, A.jenis_adm, A.nama_adm, A.email_adm, B.nama_cabang FROM adminz AS A LEFT JOIN cabang AS B ON A.id_cabang=B.id_cabang WHERE (A.nama_adm LIKE '%$cari%' OR A.email_adm LIKE '%$cari%' OR B.nama_cabang LIKE '%$cari%') $jenis ORDER BY B.nama_cabang ASC, A.nama_adm ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_adm']);
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="'.$sistem.'/administrator/e/'.$uniq.'" title="Edit Data"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'administrator\', \'delete\', \''.$hasil['id_adm'].'\')" data-toggle="modal" title="Delete Data"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['nama_cabang'].'</td><td>'.$hasil['nama_adm'].'</td><td>'.$hasil['jenis_adm'].'</td><td>'.$hasil['email_adm'].'</td><td><center>'.$edit.$delete.'</center></td></tr>';
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