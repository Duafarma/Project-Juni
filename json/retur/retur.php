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
		$tabel	= '<tr><td colspan="6">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_r) AS total FROM retur AS A INNER JOIN outlet AS B ON A.id_out=B.id_out WHERE A.no_retur LIKE '%$cari%' OR B.nama_out LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_r, A.id_tfk, A.no_retur,A.keterangan, A.tanggal, B.nama_out FROM retur AS A INNER JOIN outlet AS B ON A.id_out=B.id_out WHERE A.no_retur LIKE '%$cari%' OR B.nama_out LIKE '%$cari%' ORDER BY A.tanggal DESC, A.no_retur DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a href="'.$sistem.'/retur/v/'.$hasil['id_r'].'"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a> <a target="_blank" href="'.$sistem.'/laporan/xps/retur/retur.php?key='.$hasil['id_r'].'"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
 			//$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a href="'.$sistem.'/retur/v/'.$hasil['id_r'].'"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a> <a target="_blank" href="'.$sistem.'/laporan/xps/order/order.php?key='.$hasil['id_r'].'"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			//$views	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/order/prekusor.php?key='.$hasil['id_r'].'"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			
 			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="#modal1" onclick="crud(\'retur\', \'update\', \''.$hasil['id_r'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
 			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'retur\', \'delete\', \''.$hasil['id_r'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr>
			                <td><center>'.$no.'</center></td>
			               	<td><center>'.$hasil['no_retur'].'</center></td>
			               	<td><center>'.$hasil['nama_out'].'</center></td>
			               	<td><center>'.$hasil['tanggal'].'</center></td>
			               	<td><center>'.$hasil['keterangan'].'</center></td>
			               	<td><center>'.$edit.$view.$delete.'</center></td>
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