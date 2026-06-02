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
		$tabel	= '<tr><td colspan="11">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		// $active	= 'Active';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_tkf_t) AS total FROM tuker_faktur AS A WHERE (A.id_tfk LIKE '%$cari%' OR A.id_tfk LIKE '%$cari%')")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_tkf_t, A.tanggal_tkf, A.status, A.note, B.kode_tfk, B.tgl_tfk, C.nama_out FROM tuker_faktur AS A INNER JOIN transaksi_faktur AS B ON A.id_tfk=B.id_tfk INNER JOIN outlet AS C ON A.id_kot=C.id_out WHERE (C.nama_out LIKE '%$cari%' OR C.nama_out LIKE '%$cari%') ORDER BY C.nama_out DESC LIMIT :mulai, :maxi");
		// $master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'tfaktur\', \'update\', \''.$hasil['id_tkf_t'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'tfaktur\', \'delete\', \''.$hasil['id_tkf_t'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['kode_tfk'].'</td>
							<td>'.$hasil['nama_out'].'</td>
							<td>'.$hasil['tgl_tfk'].'</td>
							<td>'.$hasil['tanggal_tkf'].'</td>
							<td>'.$hasil['status'].'</td>
							<td>'.$hasil['note'].'</td>
							<td><center>'.$edit.$delete.'</center></td>
						</tr>';
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