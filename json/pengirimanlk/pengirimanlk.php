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
		$jumlah	= $conn->query("SELECT COUNT(id_p_l_k) AS total FROM transaksi_p_luar_kota WHERE id_p_l_k")->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
                A.id_p_l_k,
                D.status_pengiriman,
                A.id_tfk,
                C.nama_vendor,
				A.cabang,
                A.tanggal_faktur,
                B.nama_out,
                A.tanggal_pengiriman
            FROM
                transaksi_p_luar_kota AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            LEFT JOIN vendor_pengiriman AS C ON A.id_vendor = C.id_vendor
            LEFT JOIN master_pengiriman AS D ON A.id_pengiriman = D.id_pengiriman
            WHERE
                A.nomor_resi LIKE '%$cari%' 
                OR B.nama_out LIKE '%$cari%'
            ORDER BY
                A.nomor_resi ASC
            LIMIT :mulai, :maxi";

                    // var_dump($qMaster);
                    // exit();
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a href="'.$data->sistem('url_sis').'/pengirimanlk/v/'.$hasil['id_p_l_k'].'" title="Historis"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a>' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'pengirimanlk\', \'update\', \''.$hasil['id_p_l_k'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'pengirimanlk\', \'delete\', \''.$hasil['id_p_l_k'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['status_pengiriman'].'</td>
							<td>'.$hasil['id_tfk'].'</td>
							<td>'.$hasil['nama_vendor'].'</td>
							<td>'.$hasil['cabang'].'</td>
							<td>'.$hasil['tanggal_faktur'].'</td>
							<td>'.$hasil['nama_out'].'</td>
							<td><center>'.$hasil['tanggal_pengiriman'].'</center></td>
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