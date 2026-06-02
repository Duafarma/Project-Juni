<?php
	require_once('../config/connection/connection.php');
	require_once('../config/connection/security.php');
	require_once('../config/function/data.php');
	
	$secu = new Security;
	$base = new DB;
	$data = new Data;
	
	// Check encryption key
	$encrypt = $secu->injection(@$_GET['encrypt']);
	$expectedEncrypt = md5(date('Y-m-d') . "#YbcJSFjkdsfb"); // Sesuaikan dengan key_apl
	
	if($encrypt !== $expectedEncrypt) {
		echo json_encode(array("result" => "error", "message" => "Invalid encryption key"));
		exit();
	}
	
	// Get JSON data
	$json = file_get_contents('php://input');
	$postData = json_decode($json, true);
	
	if(!$postData) {
		echo json_encode(array("result" => "error", "message" => "Invalid JSON data"));
		exit();
	}
	
	$conn = $base->open();
	$catat = date('Y-m-d H:i:s');
	$admin = 'API_SYNC';
	
	try {
		$nama_out = $secu->injection($postData['nama_out']);
		$status_pembayaran = $secu->injection($postData['status_pembayaran']);
		
		// Update outlet dengan nama yang sama
		$edit = $conn->prepare("UPDATE outlet SET status_pembayaran=:status_pembayaran, updated_at=:catat, updated_by=:admin WHERE nama_out=:nama_out");
		$edit->bindParam(":nama_out", $nama_out, PDO::PARAM_STR);
		$edit->bindParam(":status_pembayaran", $status_pembayaran, PDO::PARAM_STR);
		$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
		$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
		$edit->execute();
		
		$affectedRows = $edit->rowCount();
		
		// Catat riwayat
		$riwayat = $conn->query("INSERT INTO riwayat VALUES('', '', 'Outlet Warning API', 'Update via API - nama_out: $nama_out, affected: $affectedRows rows', '', '$catat', '$admin')");
		
		echo json_encode(array(
			"result" => "success", 
			"message" => "Updated $affectedRows outlets with nama_out: $nama_out",
			"affected_rows" => $affectedRows
		));
		
	} catch (Exception $e) {
		echo json_encode(array("result" => "error", "message" => $e->getMessage()));
	}
	
	$base->close();
?>
