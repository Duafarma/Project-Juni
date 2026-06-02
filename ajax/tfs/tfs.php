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
	$jumlah	= $conn->query("SELECT COUNT(*) AS jumlah FROM transaksi_faktur AS A LEFT JOIN outlet AS C ON A.id_out=C.id_out WHERE A.status_tfkkf='Sudah Dikirim'")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['jumlah'])){
		$tabel	= '<h6 class="text text-danger"><i>.....</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Belum Tukar Faktur</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>No. Faktur</th><th>Outlet</th><th>Tgl. Faktur</th></tr></thead>';
		$master	= $conn->prepare("SELECT A.kode_tfk, A.tgl_tfk, A.total_tfk, A.tgl_limit,A.status_tfkkf,C.nama_out FROM transaksi_faktur AS A LEFT JOIN outlet AS C ON A.id_out=C.id_out WHERE A.status_tfkkf='Sudah Dikirim' ORDER BY A.created_at ASC");
		// $master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		// $master->bindParam(':status', $status, PDO::PARAM_STR);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['kode_tfk'].'</td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['tgl_tfk'].'</td></tr>';
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