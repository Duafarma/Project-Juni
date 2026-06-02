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
						transaksi_faktur_l AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					WHERE
						A.kode_tfk LIKE '%$cari%'
						OR A.sj_tfk LIKE '%$cari%'
						OR A.po_tfk LIKE '%$cari%'
						OR B.nama_out LIKE '%$cari%'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_tfk,
						-- A.sj_tfk,
						-- A.tglsj_tfk,
						A.po_tfk,
						A.kode_tfk,
						A.tgl_tfk,
						A.total_tfk,
						A.status_tfk,
						B.nama_out
					FROM
						transaksi_faktur_l AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					WHERE
						A.kode_tfk LIKE '%$cari%'
						OR A.sj_tfk LIKE '%$cari%'
						OR A.po_tfk LIKE '%$cari%'
						OR B.nama_out LIKE '%$cari%'
					ORDER BY
						A.tglsj_tfk DESC,
						A.sj_tfk DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tfk']);
			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursalesl.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			$item	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && $hasil['status_tfk']==='Faktur') ? '<a href="'.$sistem.'/itemsalesl/'.$uniq.'" title="Item Faktur Lain - Lain"><span class="badge badge-primary"><i class="fa fa-check"></i></span></a> ' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_tfk'],array('Faktur','Tagihan'))) ? '<a href="#modal1" onclick="crud(\'fsalesl\', \'update\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			// $tf		= ($data->akses($admin, $menu, 'A.tf_status')==='Active') ? ' <a href="#modal1" onclick="crud()" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-tags"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && in_array($hasil['status_tfk'],array('Faktur','Tagihan'))) ? ' <a href="#modal1" onclick="crud(\'fsalesl\', \'delete\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td>'.$hasil['kode_tfk'].'</td><td>'.$hasil['nama_out'].'</td><td><center>'.$hasil['tgl_tfk'].'</center></td><td>'.$hasil['po_tfk'].'</td><td><div align="right">'.$data->angka($hasil['total_tfk']).'</div></td><td><center>'.$view.'</center></td><td><center>'.$item.$edit.$delete.'</center></td></tr>';
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
