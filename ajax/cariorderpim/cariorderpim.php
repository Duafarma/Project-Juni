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
	//READ DATA
	$read	= $conn->prepare("SELECT A.tgl_tor, A.ket_tor, C.nama_sup, COUNT(B.id_tor) AS jitem, D.parameter_sdi, D.diskon1_sdi, D.diskon2_sdi, D.top_sdi FROM transaksi_order AS A INNER JOIN transaksi_orderdetail AS B ON A.id_tor=B.id_tor INNER JOIN supplier AS C ON A.id_sup=C.id_sup LEFT JOIN supplier_diskon AS D ON C.id_sup=D.id_sup WHERE A.id_tor=:kode");
	$read->bindParam(':kode', $kode, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	$limit	= date("Y-m-d", strtotime("+$view[top_sdi] Days", strtotime($catat)));
	$subtot	= 0;
	$total	= 0;
	$active	= 'Active';
	$master	= $conn->prepare("SELECT A.id_tod, A.jumlah_tod, B.id_pro, B.nama_pro, B.berat_pro, C.nama_kpr, D.nama_spr, E.harga_phg FROM transaksi_orderdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr LEFT JOIN produk_harga AS E ON B.id_pro=E.id_pro WHERE A.id_tor=:kode AND E.status_phg=:active GROUP BY A.id_tod ORDER BY A.id_tod ASC");
	$master->bindParam(':kode', $kode, PDO::PARAM_STR);
	$master->bindParam(':active', $active, PDO::PARAM_STR);
	$master->execute();
	while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
		$subtot	= $hasil['harga_phg'] * $hasil['jumlah_tod'];
		$diskon	= ($hasil['jumlah_tod']<=$view['parameter_sdi']) ? $view['diskon1_sdi'] : $view['diskon2_sdi'];
		$stotal	= $subtot - (($subtot * $diskon) / 100);
		$total	+= $stotal;
		$tabel	.= '<tr id="traddorder'.$nomor.'">
					<td>
					<a onclick="addrorderpim('.$nomor.', '.$hasil['id_tod'].')"><i class="fa fa-plus-circle"></i></a> '.$hasil['nama_pro'].'
					<input type="hidden" name="product[]" value="'.$hasil['id_pro'].'" readonly="readonly" />
					</td>
					<td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td>
					<td><input type="text" name="gudang[]" class="inputrans" placeholder="" /></td>
					<td><input type="text" name="batchcode[]" class="inputrans" value="" placeholder="-" required="required" /></td>
					<td><input type="text" name="tbatchcode[]" class="inputrans fortgl" value="" placeholder="9999-99-99" required="required" /></td>
					<td><input type="text" name="harga[]" id="pharga'.$nomor.'" class="inputangka" onkeyup="angka(this)" value="'.$data->angka($hasil['harga_phg']).'" placeholder="0" readonly="readonly" /></td>
					<td>'.$hasil['jumlah_tod'].'</td>
					<td><input type="text" name="jumlah[]" id="pjumlah'.$nomor.'" class="inputangka" value="'.$hasil['jumlah_tod'].'" onchange="jumlahorderpim('.$nomor.')" onkeyup="angka(this)" placeholder="0" /></td>
					<td><input type="text" name="diskon[]" id="pdiskon'.$nomor.'" class="inputangka" onchange="hitungorderpim('.$nomor.')" value="'.$diskon.'" placeholder="0" /></td>
					<td><input type="text" name="total[]" id="ptotal'.$nomor.'" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($stotal).'" placeholder="0" readonly="readonly" /></td>
					<td><center>-</center></td>
				</tr><script type="text/javascript">$(".fortgl").mask("9999-99-99");</script>';
	$nomor++;
	}
    $ppn    = ($total * 11) / 100;
    $pph    = ($total * 0.3) / 100;
    $gtotal = $total + $ppn + $pph;
	$footer	= 
	'<tr>
					<td></td>
					<td colspan="8"><div align="right"><b>SUBTOTAL</b></div></td>
					<td>
					<input type="text" name="pstotal" id="pstotal" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($total).'" placeholder="0" readonly="readonly" />
					<input type="hidden" name="minorder" id="minorder" value="'.$view['parameter_sdi'].'" readonly="readonly" />
					<input type="hidden" name="diskon1" id="diskon1" value="'.$view['diskon1_sdi'].'" readonly="readonly" />
					<input type="hidden" name="diskon2" id="diskon2" value="'.$view['diskon2_sdi'].'" readonly="readonly" />
					</td>
					<td></td>
					<tr></tr>

				</tr>
				<tr>
				<td></td>
					<td colspan="8"><div align="right"><b>PPN (11%)</b></div></td>
					<td><input type="text" name="pppn" id="pppn" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($ppn).'" placeholder="0" readonly="readonly" /></td><td></td>
				</tr>
				 <tr>
                    <td></td>
                    <td colspan="8"><div align="right"><b>PPH (0.3%)</b></div></td>
                    <td><input type="text" name="pph" id="pph" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($pph).'" placeholder="0" readonly="readonly" /></td><td></td>
                </tr>
				<tr>
				<td></td>
					<td colspan="8"><div align="right"><b>TOTAL</b></div></td>
					<td><input type="text" name="pgtotal" id="pgtotal" class="inputtotal" onkeyup="angka(this)" value="'.$data->angka($gtotal).'" placeholder="0" readonly="readonly" /></td><td></td>
				</tr>';
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "supplier" => $view['nama_sup'], "tglorder" => $view['tgl_tor'], "ketorder" => $view['ket_tor'], "jatuhtempo" => $limit, "jumaddorder" => $view['jitem'], "footer" => $footer);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>