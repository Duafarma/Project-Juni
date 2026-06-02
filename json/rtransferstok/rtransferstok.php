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
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	//POST DATA
	$cari	= $secu->injection(@$_GET['caridata']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="6">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$whereClause = '';
		$jumlah	= 0;
		$navi	= '';
		if ($cari != '') {
			$pecah	= explode('_', $cari);
			$cariTipe	= "('" . implode("','",explode('-', @$pecah[0])) . "')";
			$cariBarang	= @$pecah[2];
			$tgl1	= @$pecah[3] . "-01";
			$tgl2	= @$pecah[4] . "-01";
			$whereClause = "WHERE (b.tgl_ttr BETWEEN '$tgl1' AND LAST_DAY('$tgl2')) AND d.nama_pro LIKE '%$cariBarang%' OR b.tipe_ttr IN $cariTipe";
		}
		$tabel	= '';
		$no		= $mulai;
		$qJumlah = "SELECT
						COUNT(id_trd) AS total
					FROM
						transaksi_transferstockdetail a
					LEFT JOIN transaksi_transferstock b ON
						a.id_ttr = b.id_ttr
					LEFT JOIN produk_stokdetail c ON
						a.id_psd = c.id_psd
					LEFT JOIN produk d ON
						c.id_pro = d.id_pro
					$whereClause";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						b.tgl_ttr,
						b.tipe_ttr,
						b.kode_ttr,
						d.id_pro,
						d.kode_pro,
						d.nama_pro,
						c.no_bcode,
						c.tgl_expired,
						a.jumlah_ttd,
						b.status_ttr 
					FROM
						transaksi_transferstockdetail a
					LEFT JOIN transaksi_transferstock b ON
						a.id_ttr = b.id_ttr
					LEFT JOIN produk_stokdetail c ON
						a.id_psd = c.id_psd
					LEFT JOIN produk d ON
						c.id_pro = d.id_pro
					$whereClause
					ORDER BY
						b.tgl_ttr DESC
					LIMIT $mulai,$maxi";
		$master	= $conn->prepare($qMaster);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++; 
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['tgl_ttr'].'</td><td>'.$hasil['tipe_ttr'].'</td><td>'.$hasil['kode_ttr'].'</td><td>'.$hasil['kode_pro'].'</td><td>'.$hasil['nama_pro'].'</td><td>'.$hasil['no_bcode'].'</td><td>'.$hasil['tgl_expired'].'</td><td>'.$hasil['jumlah_ttd'].'</td><td>'.$hasil['status_ttr'].'</td></tr>';
		}
		$navi	= $paging->myPaging($menu, $jumlah['total'], $maxi, $page);
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>