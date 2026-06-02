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
		$no		= $mulai;
		$rows = array();
        $local = "APL_01";
		$master	= $conn->prepare("SELECT 'A' as source, A.id_psd, A.no_bcode, A.tgl_expired, A.gudang, A.tgl_psd,SUM(A.sisa_psd) AS jumlah, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.sisa_psd>0 AND B.nama_pro LIKE '%$cari%' AND C.status_phg=:active GROUP BY B.nama_pro  ORDER BY B.nama_pro ASC");
		$master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->execute();
		$rows = $master->fetchAll(PDO::FETCH_ASSOC);
		$froms = array("APL_01", "APL03", "APL05", "APL07", "APL08");
                foreach ($froms as $key => $from) {
                    if ($from != $local) {
                        $target 	= $data->get_apl($from);
                        $targetUrl 	= $target[0]['base_url_apl'];
                        $targetKey  = $target[0]['key_apl'];
                        $encrypt    = md5($tanggal . "#" . $targetKey);
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/getInventory.php?encrypt=" . $encrypt . "&key=" . $cari);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $res = curl_exec($ch);
                        // curl handling error
                        if (curl_errno($ch)) {
                            $error_msg = curl_error($ch);
                        }
                        curl_close ($ch);
                        if (isset($error_msg)) {
                            $curl_result = $error_msg;
                        } else {
                            $curl_result = json_decode($res, true)['result'];                            
                            $rows = array_merge($rows, $curl_result);
                        }
                    }
                }
                // resorting all
            usort($rows, function($a, $b) {
                    return $a['nama_pro'] <=> $b['nama_pro'];
                });
            foreach($rows as $row => $hasil) {
			$no++;
			$awal  = date_create($hasil['tgl_psd']);
			$akhir = date_create();
			$diff  = date_diff( $awal, $akhir );
            
			$status	= empty($hasil['sisa_psd']) ? 'Kosong' : (($hasil['sisa_psd']<50) ? 'Order Ulang' : 'Cukup');
			$nama	= '<a href="#modal1" onclick="crud(\'inventory\', \'update\', \''.$hasil['id_psd'].'\')" data-toggle="modal">'.$hasil['nama_pro'].'</a>';
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$nama.'</td><td><div>'.$hasil['source'].'</div></td><td><div>'.$data->angka($hasil['jumlah']).'</div></td></tr>';
		}
		$navi	= '';
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	//header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>