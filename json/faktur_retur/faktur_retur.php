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
	$sistem	= $data->sistem('url_sis');
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
	//$cari	= $data->cekcari($cari, '-', ' ');
	//$cari	= $data->cekcari($cari, '_', '/');
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="4">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$active	= 'Active';
		$no		= $mulai;
		$qJumlah = "SELECT
						COUNT(*) AS total
					FROM
						faktur_retur AS A
					LEFT JOIN supplier AS B ON
						A.id_sup = B.id_sup
					LEFT JOIN supplier_alamat AS C ON
						A.id_sup = C.id_sup 
					LEFT JOIN regional_kabupaten AS D ON
						C.id_sup = D.id_rpo 
					WHERE
						A.kode_fkr LIKE '%$cari%' 
						OR A.sj_fkr LIKE '%$cari%'
						OR A.po_fkr LIKE '%$cari%'
						OR B.nama_sup LIKE '%$cari%'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_fkr,
						A.sj_fkr,
						A.tglsj_fkr,
						A.po_fkr,
						A.tglpo_fkr,
						A.kode_fkr,
						A.tgl_tfk,
						A.total_fkr,
						A.status_fkr,
						B.nama_sup
					FROM
						faktur_retur AS A
					LEFT JOIN supplier AS B ON
						A.id_sup = B.id_sup
					LEFT JOIN supplier_alamat AS C ON
						A.id_sup = C.id_sup 
					WHERE
						A.kode_fkr LIKE '%$cari%'
						OR A.sj_fkr LIKE '%$cari%'
						OR A.po_fkr LIKE '%$cari%'
						OR B.nama_sup LIKE '%$cari%'
					ORDER BY
						A.tglsj_fkr DESC,
					    CAST(A.sj_fkr AS UNSIGNED) DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_fkr']);
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/sjsalesretur/sjsalesretur.php?key='.$hasil['id_fkr'].'" title="Cetak SJ"><span class="badge badge-warning"><i class="fa fa-truck"></i></span></a> <a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursalesretur.php?key='.$hasil['id_fkr'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			$item	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && $hasil['status_fkr']==='Faktur') ? '<a href="'.$sistem.'/itemretur/'.$uniq.'" title="Item Faktur"><span class="badge badge-primary"><i class="fa fa-check"></i></span></a> ' : '';
		    $suhu	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/suhuretur.php?key='.$hasil['id_fkr'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
            $viewe	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursalesreturexc.php?key='.$hasil['id_fkr'].'" title="Cetak Faktur Excel"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_fkr'],array('Faktur','Tagihan'))) ? '<a href="#modal1" onclick="crud(\'faktur_retur\', \'update\', \''.$hasil['id_fkr'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			// $tf		= ($data->akses($admin, $menu, 'A.tf_status')==='Active') ? ' <a href="#modal1" onclick="crud()" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-tags"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && in_array($hasil['status_fkr'],array('Faktur','Tagihan'))) ? ' <a href="#modal1" onclick="crud(\'faktur_retur\', \'delete\', \''.$hasil['id_fkr'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$status	= ($hasil['status_fkr']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_fkr']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$sph	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/sphretur.php?key='.$hasil['id_fkr'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			
			$resi	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/resiretur.php?key='.$hasil['id_fkr'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			$pajak	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/pajakretur.php?key='.$hasil['id_fkr'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			
			$tabel	.= '<tr>
			                <td><center>'.$no.'</center></td>
			                <td>'.$hasil['kode_fkr'].'</td>
			                <td>'.$hasil['nama_sup'].'</td>
			                <td>'.$hasil['tgl_tfk'].'</td>
			                <td>'.$hasil['po_fkr'].'</td>
			                <td><div align="right">'.$data->angka($hasil['total_fkr']).'</div></td>
			                <td><center>'.$view.'</center></td>
			                <td><center>'.$item.$edit.$delete.'</center></td>
			            
			          </tr>';
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
