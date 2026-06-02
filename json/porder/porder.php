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
		$tabel	= '<tr><td colspan="9">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		$jumlah	= $conn->query("SELECT COUNT(A.id_tre) AS total FROM transaksi_receive AS A LEFT JOIN supplier AS C ON A.id_sup=C.id_sup WHERE A.fak_tre LIKE '%$cari%' OR C.nama_sup LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_tre, A.total_tre, A.fak_tre, A.tglfak_tre, A.tgl_limit, A.status_tre, SUM(B.jumlah_pre) AS bayar, C.nama_sup FROM transaksi_receive AS A LEFT JOIN pembayaran_receive AS B ON A.id_tre=B.id_tre LEFT JOIN supplier AS C ON A.id_sup=C.id_sup WHERE A.fak_tre LIKE '%$cari%' OR C.nama_sup LIKE '%$cari%' GROUP BY A.id_tre ORDER BY A.created_at DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$status	= ($hasil['status_tre']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tre']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$no++; 
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a href="#modal1" onclick="crud(\'porder\', \'detail\', \''.$hasil['id_tre'].'\')" data-toggle="modal"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a>' : '';
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['fak_tre'].'</td>
							<td>'.$hasil['nama_sup'].'</td>
							<td>'.$hasil['tglfak_tre'].'</td>
							<td>'.$hasil['tgl_limit'].'</td>
							<td>'.$data->angka($hasil['total_tre']).'</td>
							<td>'.$data->angka($hasil['total_tre'] - $hasil['bayar']).'</td>
							<td>'.$status.'</td>
							<td><center>'.$view.'</center></td>
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