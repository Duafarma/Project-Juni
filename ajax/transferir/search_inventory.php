<?php
	session_start();
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);

	header('Content-Type: application/json');

	if($secu->validadmin($admin, $kunci) == false) {
		echo json_encode(['data'=>[]]);
		exit;
	}

	$cari = $secu->injection(@$_POST['cari'] ?? '');

	if($cari !== '') {
		$like = '%'.$cari.'%';
		$whereExtra = 'AND (P.nama_pro LIKE :cari OR IR.no_bcode LIKE :cari)';
	} else {
		$like = null;
		$whereExtra = '';
	}

	$sql = "
		SELECT IR.id_i_r AS id_psd, IR.id_pro, IR.no_bcode, IR.ed AS tgl_expired, IR.gudang, IR.sisa,
		       P.nama_pro, P.berat_pro, S.nama_spr
		FROM inventory_retur IR
		LEFT JOIN produk P ON IR.id_pro = P.id_pro
		LEFT JOIN satuan_produk S ON P.id_spr = S.id_spr
		WHERE IR.sisa > 0
		  AND P.id_pro IS NOT NULL
		  $whereExtra
		ORDER BY P.nama_pro, IR.ed
		LIMIT 100
	";
	$q = $conn->prepare($sql);
	if($like !== null) $q->bindValue(':cari', $like);
	$q->execute();
	$rows = $q->fetchAll(PDO::FETCH_ASSOC);

	$out = [];
	foreach($rows as $r) {
		$satuan = trim($r['berat_pro'].' '.($r['nama_spr'] ?? ''));
		$out[] = [
			'id_psd'     => (int)$r['id_psd'],   // sebenarnya id_i_r
			'id_pro'     => $r['id_pro'],
			'nama_pro'   => $r['nama_pro'].($satuan ? ' '.$satuan : ''),
			'no_bcode'   => $r['no_bcode'],
			'tgl_expired'=> $r['tgl_expired'],
			'gudang'     => $r['gudang'],
			'sisa'       => (int)$r['sisa'],
		];
	}

	echo json_encode(['data'=>$out]);
?>
