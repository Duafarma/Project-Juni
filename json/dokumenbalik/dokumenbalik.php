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
	    $pecah	= explode('_', $cari);
		$cari	= $data->cekcari($pecah[0], '-', ' ');
		$tgl1	= empty($pecah[1]) ? "" : "AND C.created_at>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND C.created_at<='$pecah[2]'"; 
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
		$qJumlah = "SELECT
						COUNT(*) AS total
					FROM (
					    SELECT A.id_tfk
					    FROM transaksi_faktur AS A
					    LEFT JOIN outlet AS B ON A.id_out = B.id_out
					    INNER JOIN dokumen_failing_detail AS C ON A.id_tfk = C.no_faktur
					    WHERE C.created_at LIKE '%$cari%' $tgl1 $tgl2
					    
					    UNION ALL
					    
					    SELECT A.id_tfk
					    FROM transaksi_faktur_pim AS A
					    LEFT JOIN outlet AS B ON A.id_out = B.id_out
					    INNER JOIN dokumen_failing_detail AS C ON A.id_tfk = C.no_faktur
					    WHERE C.created_at LIKE '%$cari%' $tgl1 $tgl2
					) AS combined_data";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_tfk,
						A.kode_tfk,
						C.created_at,
						A.tgl_tfk,
						A.status_dokumen,
						A.status_failing,
						B.nama_out,
						'Cendo' AS sumber
                      
					FROM
						transaksi_faktur AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					INNER JOIN dokumen_failing_detail AS C ON
					    A.id_tfk = C.no_faktur
					WHERE
					    C.created_at LIKE '%$cari%' $tgl1 $tgl2
					    
					UNION ALL
					
					SELECT
						A.id_tfk,
						A.kode_tfk,
						C.created_at,
						A.tgl_tfk,
						A.status_dokumen,
						A.status_failing,
						B.nama_out,
						'PIM' AS sumber
                      
					FROM
						transaksi_faktur_pim AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					INNER JOIN dokumen_failing_detail AS C ON
					    A.id_tfk = C.no_faktur
					WHERE
					    C.created_at LIKE '%$cari%' $tgl1 $tgl2
					    
					ORDER BY
						created_at DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tfk']);
			$tabel	.= '<tr>
			                <td><center>'.$no.'</center></td>
			                <td>'.$hasil['kode_tfk'].' ('.$hasil['sumber'].')</td>
			                <td>'.$hasil['nama_out'].'</td>
			                <td>'.$hasil['created_at'].'</td>
			                <td>'.$hasil['status_dokumen'].'</td>
			                <td>'.$hasil['status_failing'].'</td>
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
