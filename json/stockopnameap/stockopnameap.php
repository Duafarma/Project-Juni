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
        $footer = '';
	} else {
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
		$master	= $conn->prepare("SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.gudang, A.status, A.tgl_psd, A.qty_so, A.sisa_psd, B.id_pro, B.nama_pro, B.berat_pro, B.minstok_pro, B.nama_p, C.harga_phg, C.hargap_phg, E.nama_spr, F.id_mp, F.nama_principle FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr LEFT JOIN master_principle AS F ON B.nama_p=F.id_mp WHERE (A.sisa_psd > 0 OR (A.sisa_psd = 0 AND A.qty_so > 0)) AND A.qty_so != A.sisa_psd AND B.nama_pro LIKE '%$cari%' AND C.status_phg=:active ORDER BY F.nama_principle ASC, B.nama_pro ASC");
		$master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$selisih = (int)$hasil['qty_so'] - (int)$hasil['sisa_psd'];
			$selisihLabel = ($selisih > 0)
				? '<span class="badge badge-success">+' . $data->angka($selisih) . '</span>'
				: '<span class="badge badge-danger">' . $data->angka($selisih) . '</span>';
			$principleLabel = htmlspecialchars($hasil['nama_principle'] ?? '-');
			$submit = (in_array($hasil['status'], array('selesai so'))) ? '<a href="#modal1" onclick="crud(\'stockopnameap\', \'updateStatusRevisi\', \''.$hasil['id_psd'].'\')" data-toggle="modal"><span class="badge badge-success"><i class="fa fa-check"></i></span></a>' : '';
			$tabel .= '<tr>
                    <td><center><span class="badge badge-primary">'.$principleLabel.'</span></center></td>
                    <td>'.$hasil['nama_pro'].'</td>
                    <td><center>'.$hasil['no_bcode'].'</center></td>
                    <td><center>'.$data->angka($hasil['sisa_psd']).'</center></td>
                    <td><center>'.$data->angka($hasil['qty_so']).'</center></td>
                    <td><center>'.$selisihLabel.'</center></td>
                    <td><center>'.$submit.'</center></td>
                </tr>';
		}

		$navi = '  ';
	}

	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>