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
	$limit	= $data->sistem('limit_outlet');
	$status	= 'Lunas';
	$jumlah	= $conn->query("SELECT COUNT(A.kode_tfk) AS total FROM transaksi_faktur AS A LEFT JOIN pembayaran_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN outlet AS C ON A.id_out=C.id_out WHERE A.status_tfk!='$status' AND (TIMESTAMPDIFF(DAY, '$tanggal', tgl_limit)<='$limit' OR TIMESTAMPDIFF(DAY, tgl_limit, '$tanggal')>0)")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada Tagihan Outlet</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Tagihan Outlet</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>No. Faktur</th><th>Outlet</th><th>Tgl. Faktur</th><th>Jatuh Tempo</th><th><div align="right">Sisa Tagihan</div></th></tr></thead>';
		$master	= $conn->prepare("SELECT A.kode_tfk, A.tgl_tfk, A.total_tfk, A.tgl_limit, SUM(B.jumlah_pfk) AS bayar, C.nama_out FROM transaksi_faktur AS A LEFT JOIN pembayaran_faktur AS B ON A.id_tfk=B.id_tfk LEFT JOIN outlet AS C ON A.id_out=C.id_out WHERE A.status_tfk!=:status AND (TIMESTAMPDIFF(DAY, :tanggal, tgl_limit)<=:limit OR TIMESTAMPDIFF(DAY, tgl_limit, :tanggal)>0) GROUP BY A.id_tfk ORDER BY A.created_at ASC");
		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->bindParam(':status', $status, PDO::PARAM_STR);
		$master->bindParam(':limit', $limit, PDO::PARAM_INT);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['kode_tfk'].'</td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['tgl_tfk'].'</td><td>'.$hasil['tgl_limit'].'</td><td><div align="right">'.$data->angka($hasil['total_tfk'] - $hasil['bayar']).'</div></td></tr>';
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