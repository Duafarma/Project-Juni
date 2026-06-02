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
		$no		= $mulai;
		$master	= $conn->prepare("SELECT A.id_psd, A.no_bcode, A.status_barang,A.awal, A.tgl_expired,A.status, A.gudang, A.tgl_psd, A.qty_so, A.sisa_psd,B.id_pro, B.nama_pro, B.berat_pro,B.minstok_pro, C.harga_phg, C.hargap_phg, E.nama_spr FROM produk_stokdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON B.id_pro=C.id_pro LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr WHERE A.status_barang=:active AND B.nama_pro LIKE '%$cari%' AND C.status_phg=:active GROUP BY B.nama_pro ASC");
		$master->bindParam(':active', $active, PDO::PARAM_STR);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
		
            $cancel	= (in_array($hasil['status'],array('belum so'))) ? '<a href="#modal1" onclick="crud(\'stockopnamein\', \'updateStatusCancel\', \''.$hasil['id_psd'].'\')" data-toggle="modal"><span class="badge badge-danger" title="Revisi Selisih"> <i class="fa fa-times"></i></span></a>':'';

		
			
			$badgeStatus = ($hasil['status']==='belum so') ? 'success' : (($hasil['status']==='sudah so') ? 'secondary' : 'warning');


			
			 $so	= $data->so($hasil['id_pro']);
			 $sisa	= $data->sisapsd($hasil['id_pro']);
		     $selisih = ($so - $sisa);
		
			$tabel	.= '<tr>
							
                			 <td>'.$no.'</td>						
		                	 <td>'.$hasil['nama_pro'].'</td>
			                 <td>'. $sisa.'</td>
                        
		                	<td>'.$so.'</td>
                			<td>'.$selisih.'</td>
                			 
		                </tr>';
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