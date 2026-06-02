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
	//$limit	= $data->sistem('limit_stok');
	$jumlah	= $conn->query("SELECT COUNT(B.id_pro) AS total FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.jumlah")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada Produk Stok <50</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Produk Stok <50</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>Produk</th><th>Sediaan</th><th><div align="right">Total Stok</div></th><th><div align="right">Minimal Stok</div></th><th>Satuan Qty.</th></tr></thead>';
		$master	= $conn->prepare("SELECT A.jumlah, B.nama_pro, B.berat_pro, B.minstok_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr FROM(SELECT id_pro, SUM(sisa_psd) AS jumlah FROM produk_stokdetail GROUP BY id_pro) AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr WHERE A.jumlah");
		$master->bindParam(':limit', $limit, PDO::PARAM_INT);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td><td><div align="right">'.$data->angka($hasil['jumlah']).'</div></td><td><div align="right">'.$data->angka($hasil['minstok_pro']).'</div></td><td>'.$hasil['satuan_kpr'].'</td></tr>';
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