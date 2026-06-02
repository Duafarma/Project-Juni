<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	$secu	= new Security;
	$base	= new DB;
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	if(!$secu->validadmin($admin, $kunci)){
		http_response_code(403);
		echo json_encode(['pending' => 0]);
		exit;
	}
	$conn		= $base->open();
	$active		= 'Active';
	$principle	= $secu->injection(@$_GET['principle'] ?? '');

	if(!empty($principle)){
		$stmt = $conn->prepare("SELECT COUNT(*) FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro WHERE A.sisa_psd >= 0 AND (A.sisa_psd > 0 OR A.qty_so > 0) AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL) AND C.status_phg=:active AND B.nama_p=:principle");
		$stmt->bindParam(':active', $active, PDO::PARAM_STR);
		$stmt->bindParam(':principle', $principle, PDO::PARAM_STR);
	} else {
		$stmt = $conn->prepare("SELECT COUNT(*) FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro WHERE A.sisa_psd >= 0 AND (A.sisa_psd > 0 OR A.qty_so > 0) AND A.id_psd NOT IN (SELECT id_psd FROM stock WHERE id_psd IS NOT NULL) AND C.status_phg=:active");
		$stmt->bindParam(':active', $active, PDO::PARAM_STR);
	}
	$stmt->execute();
	$count = (int)$stmt->fetchColumn();
	$conn = $base->close();

	header('Content-Type: application/json');
	echo json_encode(['pending' => $count]);
?>
