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
	$kode	= $secu->injection(@$_POST['x']);
	$tgl	= date('Y-m-d');
	$conn	= $base->open();
	$nomor	= 1;
	$tabel	= '';

	$read	= $conn->prepare("SELECT A.po_tsl, A.tgl_tsl, A.subtot_tsl, A.ppn_tsl, A.total_tsl, B.nama_sup, B.npwp_sup, C.alamat_sal FROM transaksi_salesretur AS A LEFT JOIN supplier AS B ON A.id_sup=B.id_sup LEFT JOIN supplier_alamat AS C ON B.id_sup=C.id_sup WHERE A.id_tsl=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	$gabung	= '/FKT/'.$data->romawi(date('m')).'/'.date('y');
	$faktur	= $data->transcode($gabung, 'kode_fkr', 'faktur_retur');

	$subtot	= 0;
	$total	= 0;
	$active	= 'Active';
	$master	= $conn->prepare("SELECT A.id_i_r, A.jumlah_fkrd, A.harga_fkrd, A.diskon_fkrd, A.total_fkrd, B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, C.nama_kpr, C.satuan_kpr, D.nama_spr, E.gudang, E.no_bcode, E.ed FROM faktur_returdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr LEFT JOIN inventory_retur AS E ON A.id_i_r=E.id_i_r WHERE A.id_tsl=:kode");
	$master->bindParam(':kode', $kode, PDO::PARAM_STR);
	$master->execute();
	while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
		$tabel	.= '<tr id="traddfaktur'.$nomor.'">
						<td>
						<div id="noproduct'.$nomor.'">('.$hasil['kode_pro'].') '.$hasil['nama_pro'].'</div>
						<input type="hidden" name="kodestok[]" id="kodestok'.$nomor.'" value="'.$hasil['id_i_r'].'" readonly="readonly" />
						<input type="hidden" name="product[]" id="product'.$nomor.'" value="'.$hasil['id_pro'].'" readonly="readonly" />
						<input type="hidden" name="prostok[]" id="prostok'.$nomor.'" value="'.$hasil['jumlah_fkrd'].'" readonly="readonly" />
						</td>
						<td><div id="prodetail'.$nomor.'">'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</div></td>
						<td><div id="nobcode'.$nomor.'">'.$hasil['no_bcode'].'</div></td>
						<td><div id="gudang'.$nomor.'">'.$hasil['gudang'].'</div></td>
						<td><div id="tgled'.$nomor.'">'.$hasil['ed'].'</div></td>
						<td>'.$data->angka($hasil['harga_fkrd']).'<input type="hidden" name="harga[]" id="pharga'.$nomor.'" class="inputangka" onkeyup="angka(this)" value="'.$hasil['harga_fkrd'].'" placeholder="0" readonly="readonly" /></td>
						<td>
						'.$hasil['jumlah_fkrd'].'
						<input type="hidden" name="jumlah[]" id="pjumlah'.$nomor.'" class="inputangka" value="'.$hasil['jumlah_fkrd'].'" onchange="hitungsalesretur('.$nomor.')" onkeyup="angka(this)" placeholder="0" readonly="readonly" />
						</td>
						<td>'.$hasil['satuan_kpr'].'</td>
						<td><input type="text" name="diskon[]" id="pdiskon'.$nomor.'" class="inputangka" value="'.$hasil['diskon_fkrd'].'" onchange="hitungsalesretur('.$nomor.')" placeholder="0" readonly="readonly" /></td>
						<td><input type="text" name="total[]" id="ptotal'.$nomor.'" class="inputtotal" value="'.$data->angka($hasil['total_fkrd']).'" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
					</tr>';
		$nomor++;
	}
	$footer	= '<tr>
				<td colspan="8"><div align="right"><b>SUBTOTAL</b></div></td>
				<td><input type="text" name="pstotal" id="pstotal" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($view['subtot_tsl']).'" placeholder="0" readonly="readonly" /></td>
			</tr>
			<tr>
				<td colspan="8"><div align="right"><b>PPN (11%)</b></div></td>
				<td><input type="text" name="pppn" id="pppn" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($view['ppn_tsl']).'" placeholder="0" readonly="readonly" /></td>
			</tr>
			<tr>
				<td colspan="8"><div align="right"><b>TOTAL</b></div></td>
				<td><input type="text" name="pgtotal" id="pgtotal" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($view['total_tsl']).'" placeholder="0" readonly="readonly" /></td>
			</tr>';
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "footer" => $footer, "nofaktur" => $faktur, "tglsj" => $view['tgl_tsl'], "namaoutlet" => $view['nama_sup'], "alamatkirim" => $view['alamat_sal'], "npwp" => $view['npwp_sup'], "nomorpo" => $view['po_tsl']);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>