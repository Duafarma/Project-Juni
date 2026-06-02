<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$json	= null;
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$catat	= date('Y-m-d H:i:s');
	$active	= "Active";
	$inact	= "Inactive";
	$email	= $secu->injection($_POST['emailz']);
	$pass	= $secu->injection($_POST['passz']);
	$unix	= md5($pass);
	$conn	= $base->open();
	// login
	$read	= $conn->prepare("SELECT B.id_adm, B.jenis_adm FROM adminz_account AS A INNER JOIN adminz AS B ON A.id_adm=B.id_adm WHERE B.email_adm=:email AND A.password_aac=:unix AND B.status_adm=:active");
	$read->bindParam(':email', $email, PDO::PARAM_STR);
	$read->bindParam(':unix', $unix, PDO::PARAM_STR);
	$read->bindParam(':active', $active, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);	
	if(empty($view['id_adm'])){
		$hasil	= "error";
	} else {
		$edit	= $conn->prepare("UPDATE adminz_login SET status_alo=:inact WHERE id_adm=:kode");
		$edit->bindParam(':inact', $inact, PDO::PARAM_STR);
		$edit->bindParam(':kode', $view['id_adm'], PDO::PARAM_STR);
		$edit->execute();
		//READ DATA
		$kunci	= time();
		$batas	= date("Y-m-d H:i:s", strtotime("+13 Hours", strtotime($catat)));
		$save	= $conn->prepare("INSERT INTO adminz_login(id_adm, kunci_alo, batas_alo, status_alo, created_at) VALUES(:kode, :kunci, :batas, :active, :catat)");
		$save->bindParam(':kode', $view['id_adm'], PDO::PARAM_STR);
		$save->bindParam(':kunci', $kunci, PDO::PARAM_STR);
		$save->bindParam(':batas', $batas, PDO::PARAM_STR);
		$save->bindParam(':active', $active, PDO::PARAM_STR);
		$save->bindParam(':catat', $catat, PDO::PARAM_STR);
		$save->execute();
		$hasil	= "success";
		//RIWAYAT
		$riwayat= $conn->query("INSERT INTO riwayat (kode_riwayat, menu_riwayat, status_riwayat, ket_riwayat, created_at, created_by) VALUES('$view[id_adm]', 'Login', 'Create', 'Login admin', '$catat', '$view[id_adm]')");
		setcookie('adminkuy', $view['id_adm'], time() + 46800, '/');
		setcookie('jeniskuy', $view['jenis_adm'], time() + 46800, '/');
		setcookie('kuncikuy', $kunci, time() + 46800, '/');
	}
	$conn	= $base->close();
	$json	= array("hasil" => $hasil);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>