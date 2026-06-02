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
	$carii	= $secu->injection(@$_GET['cariitem']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="10">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$active	= 'Active';
		$nama_p	= 'MP0000000002';

		$no		= $mulai;
		$previousName = ''; // Variabel untuk menyimpan nama produk sebelumnya
		$Ministok ='';
		$previousProductId = '';
		$master	= $conn->prepare("SELECT A.id_psd, A.no_bcode, A.tgl_expired,B.id_pro, A.gudang, A.tgl_psd, A.sisa_psd, B.nama_p, B.nama_pro, B.berat_pro, B.minstok_pro, C.harga_phg, C.hargap_phg, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.sisa_psd>0 AND B.nama_pro LIKE '%$cari%' AND C.status_phg=:active AND B.nama_p = :nama_p ORDER BY B.nama_pro ASC");
		$master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->bindParam(':nama_p', $nama_p, PDO::PARAM_STR); // Static value

		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$awal  = date_create($hasil['tgl_psd']);
			$akhir = date_create();
			$diff  = date_diff($awal, $akhir);
			// $status	= empty($hasil['sisa_psd']) ? 'Kosong' : (($hasil['sisa_psd']<50) ? 'Order Ulang' : 'Cukup');
            $status = empty($hasil['sisa_psd']) ? 'Kosong' : (($hasil['sisa_psd'] < $hasil['minstok_pro']) ? 'Order Ulang' : 'Cukup');

			// Periksa apakah nama produk sudah ditampilkan sebelumnya
			$nameColumn = ($previousName !== $hasil['nama_pro']) ? $hasil['nama_pro'] : '';
			$stok = ($Ministok !== $hasil['minstok_pro']) ? $hasil['minstok_pro'] : '';

			$bcode	= '<a href="#modal1" onclick="crud(\'inventorydpe\', \'update\', \''.$hasil['id_psd'].'\')" data-toggle="modal">'.$hasil['no_bcode'].'</a>';
			$currentProductId = $hasil['id_pro'];

			$sisa = ($previousProductId !== $currentProductId) ? $data->sisapsd($currentProductId) : '';

			$tabel	.= '<tr>
							<td><center>'.$no.'</center></td>
							<td>'.$nameColumn.'</td>
							<td>'.$hasil['gudang'].'</td>
							<td>'.$hasil['berat_pro'].' '.$hasil['nama_spr'].'</td>
							<td>'.$bcode.'</td>
							<td>'.$hasil['tgl_expired'].'</td>
							<td><center>'.$diff->days. " Hari ".'</center></td>
							<td><center>'.$data->angka($hasil['harga_phg']).'</center></td>
							<td><center>'.$data->angka($hasil['hargap_phg']).'</center></td>
							<td><center>'.$data->angka($hasil['sisa_psd']).'</center></td>
						     <td><center>'. $sisa.'</center></td>
						</tr>';
						
			// Update nama produk sebelumnya
			$previousName = $hasil['nama_pro'];
			$Ministok = $hasil['minstok_pro'];
			$previousProductId = $currentProductId;


		}
		$navi	= '';
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($json));
?>
