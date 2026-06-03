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
		echo json_encode(['data'=>[],'recordsTotal'=>0,'recordsFiltered'=>0]);
		exit;
	}

	$cari   = $secu->injection(@$_POST['cari'] ?? '');
	$hal    = max(1, (int)(@$_POST['halaman'] ?? 1));
	$limit  = (int)(@$_POST['maximal'] ?? 15);
	$offset = ($hal - 1) * $limit;

	$where = '';
	$params = [];
	if($cari !== '') {
		$where = "WHERE T.no_tir LIKE :cari OR T.keterangan LIKE :cari OR T.created_by LIKE :cari";
		$params[':cari'] = '%'.$cari.'%';
	}

	$cntQ = $conn->prepare("SELECT COUNT(*) FROM transfer_ir T $where");
	foreach($params as $k => $v) $cntQ->bindValue($k, $v);
	$cntQ->execute();
	$total = (int)$cntQ->fetchColumn();

	$q = $conn->prepare("
		SELECT T.id_tir, T.no_tir, T.keterangan, T.status_tir, T.notes_tir,
		       T.created_at, T.created_by, T.approved_at, T.approved_by,
		       (SELECT COUNT(*) FROM transfer_ir_detail D WHERE D.id_tir=T.id_tir) AS jml_item,
		       (SELECT SUM(D.jumlah) FROM transfer_ir_detail D WHERE D.id_tir=T.id_tir) AS tot_jumlah
		FROM transfer_ir T
		$where
		ORDER BY T.created_at DESC
		LIMIT :limit OFFSET :offset
	");
	foreach($params as $k => $v) $q->bindValue($k, $v);
	$q->bindValue(':limit',  $limit,  PDO::PARAM_INT);
	$q->bindValue(':offset', $offset, PDO::PARAM_INT);
	$q->execute();
	$rows = $q->fetchAll(PDO::FETCH_ASSOC);

	$url = $data->sistem('url_sis');

	$statusBadge = [
		'draft'    => '<span class="badge badge-secondary">Draft</span>',
		'pending'  => '<span class="badge badge-warning text-dark">Menunggu Approve</span>',
		'approved' => '<span class="badge badge-success">Approved</span>',
		'rejected' => '<span class="badge badge-danger">Ditolak</span>',
	];

	$out = [];
	$no = $offset + 1;
	foreach($rows as $r) {
		$badge   = $statusBadge[$r['status_tir']] ?? $r['status_tir'];
		$aksi    = '<a href="'.$url.'/transferir/v/'.$r['id_tir'].'"><button class="btn btn-primary btn-xs btn-pill"><i class="fa fa-eye"></i> Detail</button></a>';
		$out[] = [
			'no'       => $no++,
			'no_tir'   => '<strong>'.htmlspecialchars($r['no_tir']).'</strong>',
			'keterangan'=> htmlspecialchars($r['keterangan']),
			'jml_item' => (int)$r['jml_item'].' item / '.number_format((int)$r['tot_jumlah'],0,',','.').' pcs',
			'status'   => $badge,
			'operator' => htmlspecialchars($r['created_by']),
			'tgl'      => $r['created_at'] ? date('d/m/Y H:i', strtotime($r['created_at'])) : '-',
			'aksi'     => $aksi,
		];
	}

	echo json_encode(['data'=>$out,'recordsTotal'=>$total,'recordsFiltered'=>$total]);
?>
