<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	$base	= new DB;
	$secu	= new Security;
	$conn	= $base->open();

	$id_pro		= $secu->injection(@$_POST['id_pro']);
	$minstok	= $secu->injection(@$_POST['minstok']);

	header("Access-Control-Allow-Origin: *");
	header("Content-type: application/json; charset=utf-8");

	if(empty($id_pro) || $minstok === ''){
		echo json_encode(["status" => "error", "message" => "Parameter tidak lengkap"]);
		exit;
	}

	$update = $conn->prepare("UPDATE produk SET minstok_pro = :minstok WHERE id_pro = :id_pro");
	$update->bindParam(':minstok', $minstok, PDO::PARAM_INT);
	$update->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
	$update->execute();

	$conn = $base->close();

	echo json_encode(["status" => "success", "message" => "Minimal stok berhasil diperbarui"]);
?>
