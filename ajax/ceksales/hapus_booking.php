<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	$secu	= new Security;
	$base	= new DB;
	$keycode_raw = $secu->injection(@$_POST['keycode']);
	$keycode = base64_decode($keycode_raw);
	
	if (!empty($keycode)) {
		$conn = $base->open();
		try {
			$del = $conn->prepare("DELETE FROM nomor_faktur_booking WHERE id_tfk=:keycode");
			$del->bindParam(':keycode', $keycode, PDO::PARAM_STR);
			$del->execute();
			echo json_encode(["status" => "ok"]);
		} catch (PDOException $e) {
			echo json_encode(["status" => "error", "msg" => $e->getMessage()]);
		}
		$base->close();
	} else {
		echo json_encode(["status" => "empty"]);
	}
?>
