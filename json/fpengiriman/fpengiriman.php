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
						transaksi_faktur_kirim_f AS A
					INNER JOIN transaksi_faktur AS B ON
						B.id_tfk = A.id_tfk
					LEFT JOIN outlet AS C ON
						C.id_out = B.id_out
					LEFT JOIN adminz AS D ON
						A.id_adm = D.id_adm
					INNER JOIN transaksi_faktur_kirim_b AS E ON
						E.id_tfk = A.id_tfk AND
						E.status_tfkkb = 'Sudah Dikirim'
					WHERE
						A.id_tfkkf LIKE '%$cari%'
						OR A.id_adm LIKE '%$cari%'
						OR A.id_tfk LIKE '%$cari%'
						OR A.id_out LIKE '%$cari%'
						OR C.nama_out LIKE '%$cari%'
						OR D.nama_adm LIKE '%$cari%'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.*,
						A.created_at,
						B.kode_tfk,
						B.sj_tfk,
						B.tgl_tfk,
						B.tgl_tfk,
						C.nama_out,
						D.nama_adm
					FROM
						transaksi_faktur_kirim_f AS A
					INNER JOIN transaksi_faktur AS B ON
						B.id_tfk = A.id_tfk
					LEFT JOIN outlet AS C ON
						C.id_out = B.id_out
					LEFT JOIN adminz AS D ON
						A.id_adm = D.id_adm
					INNER JOIN transaksi_faktur_kirim_b AS E ON
						E.id_tfk = A.id_tfk AND
						E.status_tfkkb = 'Sudah Dikirim'
					WHERE
						A.id_tfkkf LIKE '%$cari%'
						OR A.id_adm LIKE '%$cari%'
						OR A.id_tfk LIKE '%$cari%'
						OR A.id_out LIKE '%$cari%'
						OR C.nama_out LIKE '%$cari%'
						OR D.nama_adm LIKE '%$cari%'
					ORDER BY
						A.created_at DESC,
						B.tglpo_tfk DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tfkkf']);
			// $view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/sjsales/sjsales.php?key='.$hasil['id_tfk'].'" title="Cetak SJ"><span class="badge badge-warning"><i class="fa fa-truck"></i></span></a> <a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursales.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			// $item	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && $hasil['status_tfk']==='Faktur') ? '<a href="'.$sistem.'/itemsales/'.$uniq.'" title="Item Faktur"><span class="badge badge-primary"><i class="fa fa-check"></i></span></a> ' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_tfkkf'],array('Belum Dikirim'))) ? '<a href="#modal1" onclick="crud(\'fpengiriman\', \'update\', \''.$hasil['id_tfkkf'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			// $tf		= ($data->akses($admin, $menu, 'A.tf_status')==='Active') ? ' <a href="#modal1" onclick="crud()" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-tags"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && in_array($hasil['status_tfkkf'],array('Belum Dikirim'))) ? ' <a href="#modal1" onclick="crud(\'fpengiriman\', \'delete\', \''.$hasil['id_tfkkf'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$status	= ($hasil['status_tfkkf']=='Belum Dikirim') ? '<span class="badge badge-warning">' .strtoupper($hasil['status_tfkkf']). '</span>' : '<span class="badge badge-success">' .strtoupper($hasil['status_tfkkf']). '</span>';
			$ket_tfkkf = (strlen($hasil['ket_tfkkf'])) ? substr($hasil['ket_tfkkf'], 0, 12) . '...' : '';
			$tabel	.= '
				<tr><td><center>'.$no.'</center></td>
				<td>'.$status.'</td>
				<td>'.$hasil['kode_tfk'].'</td>
				<td>'.$hasil['nama_adm'].'</td>
				<td>'.$hasil['tgl_tfk'].'</td>
				
				<td>'.$hasil['nama_out'].'</td>
				<td>'.$hasil['created_at'].'</td>
				<td>'.$ket_tfkkf.'</td>
				<td><center>'.$edit.$delete.'</center></td>
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
