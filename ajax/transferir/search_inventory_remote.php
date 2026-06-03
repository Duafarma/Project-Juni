<?php
	session_start();
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu = new Security;
	$base = new DB;
	$data = new Data;
	$conn = $base->open();
	$admin = $secu->injection(@$_COOKIE['adminkuy']);
	$kunci = $secu->injection(@$_COOKIE['kuncikuy']);

	header('Content-Type: application/json');

	if($secu->validadmin($admin, $kunci) == false) {
		echo json_encode(['data'=>[], 'error'=>'Session habis.']);
		exit;
	}

	$id_apl = $secu->injection(@$_POST['id_apl'] ?? '');
	$cari   = $secu->injection(@$_POST['cari'] ?? '');

	// Ambil data aplikasi target
	$qApl = $conn->prepare("SELECT base_url_apl, key_apl, nama_apl FROM aplikasi WHERE id_apl=:id AND active_apl=1 LIMIT 1");
	$qApl->bindParam(':id', $id_apl, PDO::PARAM_STR);
	$qApl->execute();
	$apl = $qApl->fetch(PDO::FETCH_ASSOC);

	if(!$apl || empty($apl['base_url_apl'])) {
		echo json_encode(['data'=>[], 'error'=>'Aplikasi tidak ditemukan atau URL tidak tersedia.']);
		exit;
	}

	$tgl     = date('Y-m-d');
	$encrypt = md5($tgl . '#' . $apl['key_apl']);
	$url     = rtrim($apl['base_url_apl'], '/') . '/api/getInventoryReturTransfer.php?encrypt=' . urlencode($encrypt);

	// Panggil API via cURL
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['cari' => $cari]));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlError = curl_error($ch);
	curl_close($ch);

	if($curlError || $httpCode !== 200) {
		echo json_encode(['data'=>[], 'error'=>'Gagal menghubungi '.$apl['nama_apl'].': '.($curlError ?: 'HTTP '.$httpCode)]);
		exit;
	}

	$remoteData = json_decode($response, true);
	if(!isset($remoteData['status']) || $remoteData['status'] !== 'ok') {
		$msg = $remoteData['message'] ?? 'Response tidak valid';
		echo json_encode(['data'=>[], 'error'=>$apl['nama_apl'].': '.$msg]);
		exit;
	}

	// Format sudah sesuai, teruskan langsung
	$out = [];
	foreach($remoteData['data'] as $r) {
		$out[] = [
			'id_psd'      => (int)$r['id_i_r'],
			'id_pro'      => $r['id_pro'],
			'nama_pro'    => $r['nama_pro'],
			'no_bcode'    => $r['no_bcode'],
			'tgl_expired' => $r['tgl_expired'],
			'gudang'      => $r['gudang'],
			'sisa'        => (int)$r['sisa'],
		];
	}

	echo json_encode(['data' => $out, 'sumber' => $apl['nama_apl']]);
?>
