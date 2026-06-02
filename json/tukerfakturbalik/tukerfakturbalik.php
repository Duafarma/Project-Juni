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
		$tgl1	= empty($pecah[1]) ? "" : "AND A.tgl_tfk>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND A.tgl_tfk<='$pecah[2]'"; 
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
		$qJumlah = "SELECT
						COUNT(*) AS total
					FROM
						(
							SELECT id_tfk, kode_tfk, tgl_tfk, status_balik, total_tfk, status_tfk, id_out, tglsj_tfk, sj_tfk FROM transaksi_faktur
							UNION ALL
							SELECT id_tfk, kode_tfk, tgl_tfk, status_balik, total_tfk, status_tfk, id_out, tglsj_tfk, sj_tfk FROM transaksi_faktur_pim
						) AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					WHERE
					(A.kode_tfk LIKE '%$cari%' OR B.nama_out LIKE '%$cari%') $tgl1 $tgl2";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_tfk,
						A.kode_tfk,
						A.tgl_tfk,
						A.status_balik,
						A.total_tfk,
						A.status_tfk,
						B.nama_out,
                        C.created_at,
                        D.tanggal_tf
					FROM
						(
							SELECT id_tfk, kode_tfk, tgl_tfk, status_balik, total_tfk, status_tfk, id_out, tglsj_tfk, sj_tfk FROM transaksi_faktur
							UNION ALL
							SELECT id_tfk, kode_tfk, tgl_tfk, status_balik, total_tfk, status_tfk, id_out, tglsj_tfk, sj_tfk FROM transaksi_faktur_pim
						) AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					LEFT JOIN dokumen_tf_balik_detail AS C ON
                        A.id_tfk = C.no_faktur
                    LEFT JOIN dokumen_tf_balik AS D ON
                        C.id_tfb = D.id_tfb
					WHERE
						(A.kode_tfk LIKE '%$cari%' OR B.nama_out LIKE '%$cari%') $tgl1 $tgl2
					ORDER BY
						A.tglsj_tfk DESC,
						A.sj_tfk DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tfk']);
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['kode_tfk'].'</td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['tgl_tfk'].'</td><td>'.$hasil['status_balik'].'</td><td>'.$hasil['tanggal_tf'].'</td></tr>';
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
