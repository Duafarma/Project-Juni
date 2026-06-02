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
		$tabel	= '<tr><td colspan="2">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$pecah	= explode('_', $cari);
		$outlet	= empty(@$pecah[0]) ? "id_out!=''" : "id_out IN('".str_replace("-", "', '", "$pecah[0]")."')"; 
		if(empty(@$pecah[1]) or empty(@$pecah[2])){
			$tabel	= '<tr><td colspan="2">Pilih outlet dan tanggal...</td></tr>';
			$navi	= '';
		} else {
			$tabel	= '';
			$no		= $mulai;
			$jumlah	= $conn->query("SELECT COUNT(id_out) AS total FROM outlet WHERE $outlet")->fetch(PDO::FETCH_ASSOC);
			$master		= $conn->prepare("SELECT id_out, nama_out FROM outlet WHERE $outlet ORDER BY nama_out ASC LIMIT :mulai, :maxi");
			$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
			$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
			$master->execute();
			while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
				$no++;
				$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['nama_out'].'</td>';
				$awal	= empty($pecah[1]) ? "" : strtotime($pecah[1]); 
				$akhir	= empty($pecah[2]) ? "" : strtotime($pecah[2]); 
				while($awal<=$akhir){
					$bulan	= date('Y-m', $awal);
					$total	= $data->outletjual($hasil['id_out'], $bulan);
					$tabel	.= '<td><div align="right">'.$data->angka($total).'</div></td>';
					$awal	= strtotime("+1 month", $awal);
				}
			$tabel	.= '</tr>';
			}
			$navi	= ''; 
		}
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>