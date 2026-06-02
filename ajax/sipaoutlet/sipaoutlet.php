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
	$kode	= 'KLG02';
	$jumlah	= $conn->query("SELECT COUNT(A.id_out) AS total FROM outlet AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg='$kode' AND TIMESTAMPDIFF(MONTH, '$tanggal', B.expired_ole)<=C.parameter_klg")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada SIPA Outlet</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Surat Izin Outlet</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>Kode Outlet</th><th>Nama Outlet</th><th>Tgl. Expired</th><th>Selisih</th></tr></thead>';
		$master	= $conn->prepare("SELECT A.kode_out, A.nama_out, B.expired_ole, TIMESTAMPDIFF(MONTH, :tanggal, B.expired_ole) AS selisih FROM outlet AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg=:kode AND TIMESTAMPDIFF(MONTH, :tanggal, B.expired_ole)<=C.parameter_klg");
		$master->bindParam(':kode', $kode, PDO::PARAM_STR);
		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['kode_out'].'</td><td>'.$hasil['nama_out'].'</td><td>'.$hasil['expired_ole'].'</td><td>'.$hasil['selisih'].' bulan</td></tr>';
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