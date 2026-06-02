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
		$active	= 'Active';
		$no		= $mulai;
// 		$nama_p	= 'MP0000000001';
		$jumlah	= $conn->query("SELECT COUNT(A.id_pro) AS total FROM produk AS A WHERE (A.nama_pro LIKE '%$cari%' OR A.kode_pro LIKE '%$cari%') AND A.status_pro='$active'")->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_pro,
						A.kode_pro,
						E.nama_principle,
						A.kategori_obat,
						A.nama_pro,
						A.berat_pro,
						A.rak_pro,
						A.section_pro,
						A.minstok_pro,
						B.harga_phg,
						B.hargap_phg,
						C.nama_kpr,
						D.kode_spr
					FROM
						produk AS A
					INNER JOIN produk_harga AS B ON
						A.id_pro = B.id_pro
					INNER JOIN kategori_produk AS C ON
						A.id_kpr = C.id_kpr
					INNER JOIN satuan_produk AS D ON
						A.id_spr = D.id_spr
					LEFT JOIN master_principle AS E ON
					    A.nama_p = E.id_mp
					WHERE
						(A.nama_pro LIKE '%$cari%'
						OR A.kode_pro LIKE '%$cari%')
						AND A.status_pro =:active
						AND B.status_phg =:active
					
					GROUP BY
						A.id_pro,
						A.kode_pro,
						A.kategori_obat,
						A.nama_pro,
						A.berat_pro,
						A.rak_pro,
						A.section_pro,
						A.minstok_pro,
						B.harga_phg,
						B.hargap_phg,
						C.nama_kpr,
						D.kode_spr
					ORDER BY
						A.nama_pro ASC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':active', $active, PDO::PARAM_STR);
// 		$master->bindParam(':nama_p', $nama_p, PDO::PARAM_STR); // Static value
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a href="'.$data->sistem('url_sis').'/produk/v/'.$hasil['id_pro'].'" title="Historis"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a>' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'produk\', \'update\', \''.$hasil['id_pro'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'produk\', \'delete\', \''.$hasil['id_pro'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['nama_pro'].'</td>
							<td>'.$hasil['nama_principle'].'</td>
							<td>'.$hasil['kategori_obat'].'</td>
							<td>'.$hasil['nama_kpr'].'</td>
							<td>'.$hasil['berat_pro'].' '.$hasil['kode_spr'].'</td>
							<td><center>'.$hasil['rak_pro'].'</center></td>
							<td><div align="right">'.$hasil['minstok_pro'].'</div></td>
							<td><div align="right"><a href="hproduk/'.$hasil['id_pro'].'">'.$data->angka($hasil['harga_phg']).'</a></div></td>
							<td><div align="right">'.$data->angka($hasil['hargap_phg']).'</div></td>
							<td><center>'.$view.$edit.$delete.'</center></td>
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