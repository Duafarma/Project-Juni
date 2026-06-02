<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$kode	= $secu->injection(@$_POST['x']);
	$conn	= $base->open();
	//READ DATA
	$read	= $conn->prepare("SELECT A.status_pembayaran, A.`platform` AS limit_outlet, B.kode_kot, C.top_odi, C.parameter_odi, C.diskon1_odi, C.diskon2_odi FROM outlet AS A LEFT JOIN kategori_outlet AS B ON A.id_kot=B.id_kot LEFT JOIN outlet_diskon AS C ON A.id_out=C.id_out WHERE A.id_out=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	// $gabung	= '.01.01/SJ/'.$view['kode_kot'].'/'.$data->romawi(date('m')).'/'.date('y');
	$keycode_raw = $secu->injection(@$_POST['keycode']);
	$keycode = base64_decode($keycode_raw);
	
	if (empty($kode)) {
	    // Delete booking if outlet is cleared
	    $del_draft = $conn->prepare("DELETE FROM nomor_faktur_booking WHERE id_tfk=:keycode");
	    $del_draft->bindParam(':keycode', $keycode, PDO::PARAM_STR);
	    $del_draft->execute();
	    
	    $json = array(
	        "koout" => "",
	        "fkout" => "",
	        "minorder" => "",
	        "diskon1" => "",
	        "diskon2" => "",
	        "jatuhtempo" => "",
	        "status_pembayaran" => "",
	        "limit_outlet" => 0
	    );
	    $conn = $base->close();
	    http_response_code(200);
	    header('Access-Control-Allow-Origin: *');
	    header("Content-type: application/json; charset=utf-8");
	    echo(json_encode($json));
	    exit;
	}
	
	$cek_draft = $conn->prepare("SELECT id_out, sj_tfk, kode_tfk FROM nomor_faktur_booking WHERE id_tfk=:keycode");
	$cek_draft->bindParam(':keycode', $keycode, PDO::PARAM_STR);
	$cek_draft->execute();
	$draft = $cek_draft->fetch(PDO::FETCH_ASSOC);
	
	if ($draft && $draft['id_out'] == $kode) {
	    // Outlet sama, pakai nomor yang sudah di-booking
	    $inv = $draft['sj_tfk'];
	    $fak = $draft['kode_tfk'];
	} else {
	    // Outlet berbeda atau belum ada booking:
	    // Hapus booking lama dulu (kalau ada) agar nomornya tidak ikut dihitung di MAX
	    if ($draft) {
	        $del_old = $conn->prepare("DELETE FROM nomor_faktur_booking WHERE id_tfk=:keycode");
	        $del_old->bindParam(':keycode', $keycode, PDO::PARAM_STR);
	        $del_old->execute();
	    }

	    // Cek nomor urut dari transaksi_faktur dan nomor_faktur_booking (tanpa booking lama)
	    $bulanRomawi = $data->romawi(date('m'));
	    $tahun = date('y');
	    $filter = "%/$bulanRomawi/$tahun";
	    $prefixSJ = '.01.01/SJ/'.$view['kode_kot'].'/'.$bulanRomawi.'/'.$tahun;
	    $prefixFaktur = '.01.01/FKT/'.$view['kode_kot'].'/'.$bulanRomawi.'/'.$tahun;
	    
	    $q1 = $conn->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(sj_tfk, '.', 1) AS UNSIGNED)) AS max1 FROM transaksi_faktur WHERE sj_tfk LIKE :filter");
	    $q1->execute([':filter' => $filter]);
	    $max1 = (int)$q1->fetchColumn();
	    
	    $q2 = $conn->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(sj_tfk, '.', 1) AS UNSIGNED)) AS max2 FROM nomor_faktur_booking WHERE sj_tfk LIKE :filter");
	    $q2->execute([':filter' => $filter]);
	    $max2 = (int)$q2->fetchColumn();
	    
	    $nourut = max($max1, $max2) + 1;
	    $inv = sprintf("%04d", $nourut) . $prefixSJ;
	    $fak = sprintf("%04d", $nourut) . $prefixFaktur;

	    // Insert booking baru
	    $ins_draft = $conn->prepare("INSERT INTO nomor_faktur_booking (id_tfk, id_out, sj_tfk, kode_tfk) VALUES (:keycode, :out, :sj, :fak)");
	    $ins_draft->bindParam(':keycode', $keycode, PDO::PARAM_STR);
	    $ins_draft->bindParam(':out', $kode, PDO::PARAM_STR);
	    $ins_draft->bindParam(':sj', $inv, PDO::PARAM_STR);
	    $ins_draft->bindParam(':fak', $fak, PDO::PARAM_STR);
	    $ins_draft->execute();
	}
	$limit	= date("Y-m-d", strtotime("+$view[top_odi] Days", strtotime($catat)));
	$conn	= $base->close();
	$json	= array(
	                "koout" => $inv,
	                "fkout" => $fak, 
	                "minorder" => $view['parameter_odi'], 
	                "diskon1" => $view['diskon1_odi'], 
	                "diskon2" => $view['diskon2_odi'], 
	                "jatuhtempo" => $limit,
	                "status_pembayaran" => isset($view['status_pembayaran']) ? $view['status_pembayaran'] : '',
	                "limit_outlet" => isset($view['limit_outlet']) ? (int)$view['limit_outlet'] : 0

	       );
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>