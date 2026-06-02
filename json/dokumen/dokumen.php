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
	//$cari	= $data->cekcari($cari, '-', ' ');
	//$cari	= $data->cekcari($cari, '_', '/');
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="4">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
		$qJumlah = "SELECT
						COUNT(*) AS total
					FROM
						transaksi_faktur AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					LEFT JOIN outlet_alamat AS C ON
						A.id_out = C.id_out 
					LEFT JOIN regional_kabupaten AS D ON
						C.id_out = D.id_rpo 
					WHERE
                    A.status_dokumen = 'belum balik'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_tfk,
						A.sj_tfk,
						A.tglsj_tfk,
						A.po_tfk,
						A.tglpo_tfk,
                        A.status_dokumen,
						A.status_failing,
						A.kode_tfk,
						A.tgl_tfk,
						A.total_tfk,
						A.status_tfk,
						B.nama_out,
						D.nama_rkb
					FROM
						transaksi_faktur AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					LEFT JOIN outlet_alamat AS C ON
						A.id_out = C.id_out 
					LEFT JOIN regional_kabupaten AS D ON
						C.id_rkb = D.id_rkb 
					WHERE
					
						A.status_failing = 'belum failing'
					ORDER BY
						A.tglsj_tfk DESC,
						A.sj_tfk DESC
					";
		$master	= $conn->prepare($qMaster);
	
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq		= base64_encode($hasil['id_tfk']);
			$submit		= (in_array($hasil['status_dokumen'],array('belum balik'))) ? '<a href="#modal1" onclick="crud(\'dokumen\', \'updateStatusBalik\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-success"><i class="fa fa-check"></i></span></a>' : '';
			$submitt	= (in_array($hasil['status_failing'],array('belum failing'))) ? '<a href="#modal1" onclick="crud(\'dokumen\', \'updateStatusFailing\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-success"><i class="fa fa-check"></i></span></a>' : '';

			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$hasil['kode_tfk'].'</td>
							<td>'.$hasil['nama_out'].'</td>
							<td>'.$submit.'</td>
							<td>'.$submitt.'</td>
						</tr>';
		}
		$navi	=''; 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>
