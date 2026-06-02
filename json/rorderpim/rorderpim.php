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
		$tabel	= '<tr><td colspan="9">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		// Modify this query to filter by the specific id_sup 'SUP1594639748'
		$jumlah	= $conn->query("SELECT COUNT(TABEL.id_tre) AS total FROM(SELECT A.id_tre FROM transaksi_receive AS A INNER JOIN supplier AS B ON A.id_sup=B.id_sup INNER JOIN transaksi_order AS C ON A.id_tor=C.id_tor LEFT JOIN transaksi_receivedetail AS D ON A.id_tre=D.id_tre LEFT JOIN produk AS E ON D.id_pro=E.id_pro WHERE (A.fak_tre LIKE '%$cari%' OR C.kode_tor LIKE '%$cari%' OR B.nama_sup LIKE '%$cari%' OR E.nama_pro LIKE '%$cari%') AND B.id_sup = 'SUP1738590257' GROUP BY A.id_tre) AS TABEL")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_tre, A.fak_tre, A.tglfak_tre, A.tgl_tre, A.total_tre, A.tgl_limit, A.status_tre, B.nama_sup, C.kode_tor FROM transaksi_receive AS A INNER JOIN supplier AS B ON A.id_sup=B.id_sup INNER JOIN transaksi_order AS C ON A.id_tor=C.id_tor LEFT JOIN transaksi_receivedetail AS D ON A.id_tre=D.id_tre LEFT JOIN produk AS E ON D.id_pro=E.id_pro WHERE (A.fak_tre LIKE '%$cari%' OR C.kode_tor LIKE '%$cari%' OR B.nama_sup LIKE '%$cari%' OR E.nama_pro LIKE '%$cari%') AND B.id_sup = 'SUP1738590257' GROUP BY A.id_tre ORDER BY A.tgl_tre DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tre']);
			$view	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="'.$sistem.'/rorderpim/v/'.$uniq.'"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'rorderpim\', \'delete\', \''.$uniq.'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$print	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/rorder/rorder.php?key='.$hasil['id_tre'].'" title="Cetak Penerimaan Barang"><span class="badge badge-warning"><i class="fa fa-truck"></i></span></a>' : '';
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['kode_tor'].'</td>
							<td><a href="#modal1" onclick="crud(\'rorder\', \'faktur\', \''.$uniq.'\')" data-toggle="modal">'.$hasil['fak_tre'].'</a></td>
							<td>'.$hasil['nama_sup'].'</td>
							<td>'.$hasil['tglfak_tre'].'</td>
							<td>'.$hasil['tgl_limit'].'</td>
							<td>'.$data->angka($hasil['total_tre']).'</td>
							<td>'.$hasil['tgl_tre'].'</td>
							<td><center>'.$view.$delete.$print.'</center></td>
						</tr>';
		}
		$navi	= $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($json));
?>
