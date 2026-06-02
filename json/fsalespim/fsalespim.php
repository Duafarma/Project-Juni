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
						transaksi_faktur_pim AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					LEFT JOIN outlet_alamat AS C ON
						A.id_out = C.id_out 
					LEFT JOIN regional_kabupaten AS D ON
						C.id_out = D.id_rpo 
					WHERE
						A.kode_tfk LIKE '%$cari%'
						OR A.sj_tfk LIKE '%$cari%'
						OR A.po_tfk LIKE '%$cari%'
						OR B.nama_out LIKE '%$cari%'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_tfk,
						A.sj_tfk,
						A.tglsj_tfk,
						A.po_tfk,
						A.ccp,
						A.tglpo_tfk,
						A.kode_tfk,
						A.tgl_tfk,
						A.total_tfk,
						A.status_tfk,
						A.status_limit,
						A.hargapim,
						B.nama_out,
						D.nama_rkb
					FROM
						transaksi_faktur_pim AS A
					LEFT JOIN outlet AS B ON
						A.id_out = B.id_out
					LEFT JOIN outlet_alamat AS C ON
						A.id_out = C.id_out 
					LEFT JOIN regional_kabupaten AS D ON
						C.id_rkb = D.id_rkb 
					WHERE
						A.kode_tfk LIKE '%$cari%'
						OR A.sj_tfk LIKE '%$cari%'
						OR A.po_tfk LIKE '%$cari%'
						OR B.nama_out LIKE '%$cari%'
					ORDER BY
						A.tglsj_tfk DESC,
					    CAST(A.sj_tfk AS UNSIGNED) DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_tfk']);
// 			$view	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/sjsales/sjsalespim.php?key='.$hasil['id_tfk'].'" title="Cetak SJ"><span class="badge badge-warning"><i class="fa fa-truck"></i></span></a> <a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursalespim.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			// Jika status_limit = limit, hide tombol view (cetak) dan ganti icon warning berkedip
			if (
				(isset($hasil['status_limit']) && $hasil['status_limit'] === 'limit')
				|| (isset($hasil['status_limit']) && strtolower($hasil['status_limit']) === 'merah')
				|| (isset($hasil['status_limit']) && strtolower($hasil['status_limit']) === 'orange')
				|| (isset($hasil['status_limit']) && strtolower($hasil['status_limit']) === 'kuning')
			) {
				// Pilih warna dan tooltip berdasarkan penyebab
				if (isset($hasil['status_limit']) && strtolower($hasil['status_limit']) === 'merah') {
					$title = 'Status = Merah';
					$fill  = '#fe3f3f';
					$stroke = '#bb2a2a';
				} elseif (isset($hasil['status_limit']) && strtolower($hasil['status_limit']) === 'orange') {
					$title = 'Status = Orange';
					$fill  = '#FF8C00';
					$stroke = '#cc6e00';
				} elseif (isset($hasil['status_limit']) && strtolower($hasil['status_limit'] )=== 'kuning') {
					$title = 'Status = Kuning';
					$fill  = '#FFFF00';
					$stroke = '#CCCC00';
				} else {
					$title = 'Status = Limit';
					$fill  = '#FFD54A';
					$stroke = '#FFC107';
				}
				$view = '<span class="icon-badge" data-toggle="tooltip" data-placement="top" title="'.$title.'" style="display:inline-block;width:34px;height:34px;">
				<svg viewBox="0 0 32 32" width="32" height="32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
					<circle cx="16" cy="16" r="10" fill="'.$fill.'"/>
					<circle cx="16" cy="16" r="12" fill="none" stroke="'.$stroke.'" stroke-width="2" opacity="0.8">
						<animate attributeName="r" from="12" to="18" dur="1.5s" repeatCount="indefinite" />
						<animate attributeName="opacity" values="0.8;0;0.8" dur="1.5s" repeatCount="indefinite" />
					</circle>
					<text x="16" y="21" font-size="14" text-anchor="middle" fill="#000" font-family="Arial, sans-serif" font-weight="bold">!</text>
				</svg>
				</span>';
			} else {
				$view = ($data->akses($admin, $menu, 'A.read_status')==='Active')
					? '<a target="_blank" href="'.$sistem.'/laporan/xps/sjsales/sjsalespim.php?key='.$hasil['id_tfk'].'" title="Cetak SJ"><span class="badge badge-warning icon-badge"><i class="fa fa-truck"></i></span></a> 
					   <a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursalespim.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success icon-badge"><i class="fa fa-print"></i></span></a>'
					: '';
			}
			$item	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && $hasil['status_tfk']==='Faktur') ? '<a href="'.$sistem.'/itemsalespim/'.$uniq.'" title="Item Faktur"><span class="badge badge-primary"><i class="fa fa-check"></i></span></a> ' : '';
			$edititem = ($data->akses($admin, $menu, 'A.update_status')==='Active' 
				&& in_array($hasil['status_tfk'], array('Tagihan','Faktur','Revisi'))
				&& isset($hasil['hargapim']) && in_array(trim($hasil['hargapim']), array('A','B','C')) ) 
				? '<a href="#modalKeteranganRevisi" onclick="openKeteranganRevisi(\''.$uniq.'\', \''.$hasil['kode_tfk'].'\')" data-toggle="modal" title="Edit Item Faktur"><span class="badge badge-warning"><i class="fa fa-edit"></i></span></a> ' 
				: '';
		    $suhu	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/suhupim.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
            $viewe	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/faktursalesexcpim.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur Excel"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			$ccp	= (in_array($hasil['ccp'],array('ada')))  ? '<span class="badge badge-primary"><i class="fa-solid fa-flag"></i></span>' : '';
			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_tfk'],array('Faktur','Tagihan','Revisi'))) ? '<a href="#modal1" onclick="crud(\'fsalespim\', \'update\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			// $tf		= ($data->akses($admin, $menu, 'A.tf_status')==='Active') ? ' <a href="#modal1" onclick="crud()" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-tags"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && in_array($hasil['status_tfk'],array('Faktur','Tagihan','Revisi'))) ? ' <a href="#modal1" onclick="crud(\'fsalespim\', \'delete\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$sph	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/sphpim.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			
			$resi	= ($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<a target="_blank" href="'.$sistem.'/laporan/xps/faktursales/resipim.php?key='.$hasil['id_tfk'].'" title="Cetak Faktur"><span class="badge badge-success"><i class="fa fa-print"></i></span></a>' : '';
			// Format nomor faktur dengan indikator revisi
			$nomor_faktur = $hasil['kode_tfk'];
			if ($hasil['status_tfk'] === 'Revisi') {
				$nomor_faktur = '<span style="color: #007bff; font-weight: bold;">' . $hasil['kode_tfk'] . ' <span style="background-color: #007bff; color: white; padding: 2px 5px; border-radius: 3px; font-size: 11px;">R</span></span>';
			}
			
			$tabel	.= '<tr>
			                <td><center>'.$no.'</center></td>
			                <td>'.$nomor_faktur.'</td>
			                <td>'.$hasil['nama_out'].'</td>
			                <td>'.$ccp.'</td>
			                <td><center>'.$hasil['nama_rkb'].'</center></td>
			                <td>'.$hasil['tgl_tfk'].'</td>
			                <td>'.$hasil['po_tfk'].'</td>
			                <td><center>'.$hasil['tglpo_tfk'].'</center></td>
			                <td><div align="right">'.$data->angka($hasil['total_tfk']).'</div></td>
			                <td><center>'.$view.'</center></td>
			                <td><center>'.$edititem.$item.$edit.$delete.'</center></td>
			                <td><center>'.$viewe.'</center></td>
			                <td><center>'.$suhu.'</center></td>
			                <td><center>'.$sph.'</center></td>
			                <td><center>'.$resi.'</center></td>
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
