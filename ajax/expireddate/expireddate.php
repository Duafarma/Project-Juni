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
	$limit	= $data->sistem('limit_expired');
	$jumlah	= $conn->query("SELECT COUNT(id_psd) AS total FROM produk_stokdetail WHERE TIMESTAMPDIFF(DAY, '$tanggal', tgl_expired)<='$limit' OR TIMESTAMPDIFF(DAY, tgl_expired, '$tanggal')>0")->fetch(PDO::FETCH_ASSOC);
	if(empty($jumlah['total'])){
		$tabel	= '<h6 class="text text-danger"><i>Tidak Ada Expired Date</i></h6>';	
	} else {
		$no		= 1;
		$tabel	= '<h6>Expired Date</h6><table class="table table-bordered"><thead><tr><th><center>No.</center></th><th>Produk</th><th>Sediaan</th><th>Batchcode</th><th>Tgl. Expired</th><th>Gudang</th><th><div align="right">Stok</div></th><th>Satuan Qty.</th></tr></thead>';
		$qMaster = "SELECT
						A.no_bcode,
						A.tgl_expired,
						A.sisa_psd,
						A.gudang,
						B.nama_pro,
						B.berat_pro,
						C.nama_kpr,
						C.satuan_kpr,
						D.nama_spr
					FROM
						produk_stokdetail AS A
					LEFT JOIN produk AS B ON
						A.id_pro = B.id_pro
					LEFT JOIN kategori_produk AS C ON
						B.id_kpr = C.id_kpr
					LEFT JOIN satuan_produk AS D ON
						B.id_spr = D.id_spr
					WHERE
						A.sisa_psd <> 0 AND
						(TIMESTAMPDIFF(DAY, :tanggal, tgl_expired)<=:limit OR
						TIMESTAMPDIFF(DAY, tgl_expired, :tanggal)>0)";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
		$master->bindParam(':limit', $limit, PDO::PARAM_INT);
		$master->execute();
		while($hasil	= $master->fetch(PDO::FETCH_ASSOC)){
				$tabel	.= '<tr><td><center>'.$no.'</td></center><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td><td>'.$hasil['no_bcode'].'</td><td>'.$hasil['tgl_expired'].'</td><td>'.$hasil['gudang'].'</td><td><div align="right">'.$data->angka($hasil['sisa_psd']).'</div></td><td>'.$hasil['satuan_kpr'].'</td></tr>';
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