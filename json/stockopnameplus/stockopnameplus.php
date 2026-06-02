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
	$carii	= $secu->injection(@$_GET['cariitem']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="10">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$master	= $conn->prepare("SELECT SUM(A.selisih) AS total, A.id, A.id_psd, A.id_pro, A.nama_pro, A.qty, A.qty_so, B.harga_phg FROM total_inventory AS A LEFT JOIN produk_harga AS B ON A.id_pro=B.id_pro WHERE selisih>0 GROUP BY id_pro ASC");
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			// if ($hasil != $hasil['nama_pro']) {
			// 	echo $hasil['nama_pro'];
			//  }
			// $hasil = $hasil['nama_pro'];
            // $submit	= '<a href="#modal1" onclick="crud(\'stockopname\', \'updateStatusDone\', \''.$hasil['id_psd'].'\')" data-toggle="modal"><span class="badge badge-success"  title="Berhasil"><i class="fa fa-check"></i></span></a>';
            // $cancel	= '<a href="#modal1" onclick="crud(\'stockopname\', \'updateStatusCancel\', \''.$hasil['id_psd'].'\')" data-toggle="modal"><span class="badge badge-danger" title="Revisi Selisih"> <i class="fa fa-times"></i></span></a>';

            // $nama   = '';
			$selisi	= $data->selisihplus($hasil['id_pro']);
			$b= $hasil['harga_phg'];
			$selisih = ($b * $selisi);	
			$tabel	.= '<tr>
							
		                	<td><center>'.$hasil['nama_pro'].'</center></td>
							<td>'.$selisi.'</td>						
			                <td><div align="right">'.$data->angka($hasil['harga_phg']).'</div></td>
							<td><div align="right">'.$data->angka($selisih).'</div></td>

		                </tr>';
						
		}
		$navi	= '';
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>