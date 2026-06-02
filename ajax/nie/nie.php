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
	// $kode	= 'KLG02';
    $limit	= $data->sistem('limit_expired');
    $jumlah	= $conn->query("SELECT COUNT(id_pro) AS total FROM produk WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_nie)<='$limit' OR  TIMESTAMPDIFF(DAY, tgl_nie, '$tanggal')>0")->fetch(PDO::FETCH_ASSOC);

	// $jumlah	= $conn->query("SELECT COUNT(id_pro) AS total FROM produk AS A INNER JOIN outlet_legal AS B ON A.id_out=B.id_out INNER JOIN kategori_legal AS C ON B.id_klg=C.id_klg WHERE C.id_klg='$kode' AND TIMESTAMPDIFF(MONTH, '$tanggal', B.expired_ole)<=C.parameter_klg")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada Nomer Nie Expired</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Nomer Nie Produk</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>Nama produk</th><th>Nomor Nie</th><th>Tanggal Nie</th></tr></thead>';
		$master	= $conn->prepare("SELECT nama_pro, no_nie, tgl_nie, TIMESTAMPDIFF(DAY, :tanggal, tgl_nie) FROM produk WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_nie)<=:limit OR TIMESTAMPDIFF(MONTH, tgl_nie, '$tanggal')>0");
		// $master->bindParam(':kode', $kode, PDO::PARAM_STR);
        $master->bindParam(':limit', $limit, PDO::PARAM_INT);

		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['no_nie'].'</td><td>'.$hasil['tgl_nie'].'</td></tr>';
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