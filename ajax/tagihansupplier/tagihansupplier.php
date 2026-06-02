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
	$tanggal= date('Y-m-d');
	$limit	= $data->sistem('limit_supplier');
	$status	= 'Lunas';
	$jumlah	= $conn->query("SELECT COUNT(A.fak_tre) AS total FROM transaksi_receive AS A LEFT JOIN pembayaran_receive AS B ON A.id_tre=B.id_tre LEFT JOIN supplier AS C ON A.id_sup=C.id_sup WHERE A.status_tre!='$status' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<='$limit' OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada Tagihan Supplier</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Tagihan Supplier</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>No. Faktur</th><th>Supplier</th><th>Tgl. Faktur</th><th>Jatuh Tempo</th><th><div align="right">Sisa Tagihan</div></th></tr></thead>';
		$master	= $conn->prepare("SELECT A.fak_tre, A.tglfak_tre, A.total_tre, A.tgl_limit, SUM(B.jumlah_pre) AS bayar, C.nama_sup FROM transaksi_receive AS A LEFT JOIN pembayaran_receive AS B ON A.id_tre=B.id_tre LEFT JOIN supplier AS C ON A.id_sup=C.id_sup WHERE A.status_tre!=:status AND (TIMESTAMPDIFF(DAY, :tanggal, tgl_limit)<=:limit OR TIMESTAMPDIFF(DAY, tgl_limit, :tanggal)>0) GROUP BY A.id_tre ORDER BY A.created_at ASC");
		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->bindParam(':status', $status, PDO::PARAM_STR);
		$master->bindParam(':limit', $limit, PDO::PARAM_INT);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['fak_tre'].'</td><td>'.$hasil['nama_sup'].'</td><td>'.$hasil['tglfak_tre'].'</td><td>'.$hasil['tgl_limit'].'</td><td><div align="right">'.$data->angka($hasil['total_tre'] - $hasil['bayar']).'</div></td></tr>';
		$no++;
		}
		$tabel	.= '</table>';
	}
	$conn	= $base->close();

	$json	= array("tabel" => $tabel);
	http_response_code(200);
	header("Access-Control-Allow-Origin: *");
	header("Content-type: application/json; charset=utf-8");
	//header('content-type: application/json');
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>