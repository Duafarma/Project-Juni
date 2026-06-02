<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$sistem	= $data->sistem('url_sis');
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	//$act	= $secu->injection(@$_GET['act']);
	$conn	= $base->open();

	$jumlah	= $secu->injection($_POST['j']);
	$kode	= $secu->injection($_POST['k']);
	$diskon	= $secu->injection($_POST['l']);
	$active	= 'Active';
	$read	= $conn->prepare("SELECT A.jumlah_tod, B.id_pro, B.nama_pro, B.berat_pro, C.nama_kpr, D.nama_spr, E.harga_phg FROM transaksi_orderdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr LEFT JOIN produk_harga AS E ON B.id_pro=E.id_pro WHERE A.id_tod=:kode AND E.status_phg=:active");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->bindParam(':active', $active, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	$subtot	= $view['harga_phg'] * 1;
	$total	= $subtot - (($subtot * 0) / 100);
	//$jumlah	= $_GET['jumlah'];
	$nomor	= ($jumlah + 1);
	$tabel	= '<tr id="traddorder'.$nomor.'">
					<td>'.$view['nama_pro'].'<input type="hidden" name="product[]" value="'.$view['id_pro'].'" readonly="readonly" /></td>
					<td>'.$view['nama_kpr'].' ('.$view['berat_pro'].' '.$view['nama_spr'].')</td>
					<td><input type="text" name="gudang[]" class="inputrans" placeholder="" /></td>
					<td><input type="text" name="batchcode[]" class="inputrans" placeholder="-" /></td>
					<td><input type="text" name="tbatchcode[]" class="inputrans fortgl" placeholder="9999-99-99" /></td>
					<td><input type="text" name="harga[]" id="pharga'.$nomor.'" class="inputangka" onkeyup="angka(this)" value="'.$data->angka($view['harga_phg']).'" placeholder="0" readonly="readonly" /></td>
					<td>'.$view['jumlah_tod'].'</td>
					<td><input type="text" name="jumlah[]" id="pjumlah'.$nomor.'" class="inputangka" value="1" onchange="jumlahorderdpe('.$nomor.')" onkeyup="angka(this)" placeholder="0" /></td>
					<td><input type="text" name="diskon[]" id="pdiskon'.$nomor.'" class="inputangka" value="'.$diskon.'" onchange="hitungorderdpe('.$nomor.')" placeholder="0" /></td>
					<td><input type="text" name="total[]" id="ptotal'.$nomor.'" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($subtot).'" placeholder="0" readonly="readonly" /></td>
					<td>
					<center>
						<a onclick="remmaximaldpe(\'addorder\', '.$nomor.')"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
					</center>
					</td>
				</tr><script type="text/javascript">$(".fortgl").mask("9999-99-99");</script>';
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "total" => $total);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>