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
	//GET DATA
	$cari	= (isset($_GET['caridata'])) ? $secu->injection(@$_GET['caridata']) : "";
	$page	= (isset($_GET['halaman'])) ? $secu->injection(@$_GET['halaman']) : "0";
	$maxi	= (isset($_GET['maximal'])) ? $secu->injection(@$_GET['maximal']) : "15";
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="6">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		// get count datas
		$qJumlah = "SELECT
						COUNT(tt.id_ttr) AS total
					FROM
						transaksi_transferstock tt
					LEFT JOIN aplikasi a2 ON
						tt.id_app_from = a2.id_apl
					LEFT JOIN aplikasi a3 ON
						tt.id_app_to = a3.id_apl
					WHERE
						tt.kode_ttr LIKE '%$cari%'
						OR a2.nama_apl LIKE '%$cari%'
						OR a3.nama_apl LIKE '%$cari%'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		// get record datas
		$qMaster = "SELECT
						tt.id_ttr,
						tt.kode_ttr,
						tt.tgl_ttr,
						tt.tipe_ttr,
						tt.status_ttr,
						a2.nama_apl AS dari,
						a3.nama_apl AS ke
					FROM
						transaksi_transferstock tt
					LEFT JOIN aplikasi a2 ON
						tt.id_app_from = a2.id_apl
					LEFT JOIN aplikasi a3 ON
						tt.id_app_to = a3.id_apl
					WHERE
						tt.kode_ttr LIKE '%$cari%'
						OR a2.nama_apl LIKE '%$cari%'
						OR a3.nama_apl LIKE '%$cari%'
					ORDER BY
						tt.tgl_ttr DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a href="'.$sistem.'/transferstok/v/'.$hasil['id_ttr'].'"><span class="badge badge-warning"><i class="fa fa-search"></i></span></a> <a target="_blank" href="'.$sistem.'/laporan/xps/transfer/transfer.php?key='.$hasil['id_ttr'].'"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && $hasil['status_ttr'] == 'Process') ? ' <a href="#modal1" onclick="crud(\'transferstok\', \'delete\', \''.$hasil['id_ttr'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
				$sph	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/transfer/suhu.php?key='.$hasil['id_ttr'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
				$penerimaan	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/transfer/penerimaan.php?key='.$hasil['id_ttr'].'" title="Cetak Penerimaan Barang"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			
			$tabel	.= '
				<tr>
					<td><center>'.$no.'</center></td>
					<td><a href="#modal1" onclick="crud(\'transferstok\', \'tutup\', \''.$hasil['id_ttr'].'\')" data-toggle="modal">'.$hasil['kode_ttr'].'</a></td>
					<td>'.$hasil['tgl_ttr'].'</td>
					<td>'.$hasil['tipe_ttr'].'</td>
					<td>'.$hasil['dari'].'</td>
					<td>'.$hasil['ke'].'</td>
					<td>'.$hasil['status_ttr'].'</td>
										<td><center>'.$sph.'</center></td>

					<td><center>'.$view.$delete.$penerimaan.'</center></td>
					</td>
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
