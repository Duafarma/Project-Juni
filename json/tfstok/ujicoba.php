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
		$jumlah	= $conn->query("SELECT COUNT(id_transfer) AS total FROM transfer_in AS A LEFT JOIN transfer_indetail AS D ON A.id_transfer=D.id_trid LEFT JOIN produk AS E ON D.id_pro=E.id_pro WHERE A.transfer_tre LIKE '%$cari%' GROUP BY A.id_transfer) AS TABEL")->fetch(PDO::FETCH_ASSOC);
		$master	= $conn->prepare("SELECT A.id_transfer, A.transfer_tre, A.status, A.created_at FROM transfer_in AS A LEFT JOIN transfer_indetail AS D ON A.id_transfer=D.id_trid LEFT JOIN produk AS E ON D.id_pro=E.id_pro DESC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_transfer']);
			$view	= ($data->akses($admin, $menu, 'A.update_status')==='Active') ? '<a href="'.$sistem.'/tfstok/v/'.$uniq.'"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? ' <a href="#modal1" onclick="crud(\'tfstok\', \'delete\', \''.$uniq.'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			//$status	= ($hasil['status_tre']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tre']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['transfer_tre'].'</td>
							<td>'.$hasil['status'].'</td>
                            // <td>'.$hasil['status'].'</td>
							// <td>'.$hasil['gudang'].'</td>
							<td>'.$hasil['created_at'].'</td>
							<td><center>'.$view.$delete.'</center></td>
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