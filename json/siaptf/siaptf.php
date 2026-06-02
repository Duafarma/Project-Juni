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
	$cari = isset($_GET['caridata']) ? $secu->injection($_GET['caridata']) : '';
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
		$tgl1	= empty($pecah[1]) ? "" : "AND tgl_tfk>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND tgl_tfk<='$pecah[2]'"; 
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
        // $tgl ='';
		$qJumlah = "SELECT
						COUNT(*) AS total
					FROM
					    jadwal_tf AS A
                    LEFT JOIN jadwal_tf_detail AS D ON
                        A.id_tf = D.id_tf
					LEFT JOIN transaksi_faktur AS B ON
						D.no_faktur = B.id_tfk
					LEFT JOIN outlet AS C ON
						B.id_out = C.id_out 
					WHERE
						 A.id_tf LIKE '%$cari%'  AND B.tgl_tfk LIKE '%$cari%' $tgl1 $tgl2 ";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
                        A.id_tf,
                        A.tanggal,
                        B.tgl_tfk,
						B.kode_tfk,
                        B.tgl_tfk,
						C.nama_out,
                        B.total_tfk
					FROM
					     jadwal_tf AS A
                    LEFT JOIN jadwal_tf_detail AS D ON
                        A.id_tf = D.id_tf
					LEFT JOIN transaksi_faktur AS B ON
						D.no_faktur = B.id_tfk
					LEFT JOIN outlet AS C ON
						B.id_out = C.id_out
					WHERE
						 A.id_tf LIKE '%$cari%'  AND B.tgl_tfk LIKE '%$cari%' $tgl1 $tgl2 
					ORDER BY
						A.tanggal DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tf']);
            // $tanggal = ($tgl !== $hasil['tanggal']) ? $hasil['tanggal'] : '';

			$tabel	.= '<tr>
                            <td>'.$no.'</td>
                            <td>'.$hasil['nama_out'].'</td>       
                            <td>'.$hasil['kode_tfk'].'</td>
                            <td>'.$data->angka($hasil['total_tfk']).'</td>
                            <td>'.$hasil['tgl_tfk'].'</td>
                            <td>'.$hasil['tanggal'].'</td>
                    </tr>';
                    // $tgl = $hasil['tanggal'];

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
