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
	
	// ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$level	= $secu->injection(@$_COOKIE['jeniskuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	
	// POST DATA
	$cari	= $secu->injection(@$_GET['caridata']);
	$page	= $secu->injection(@$_GET['halaman']);
	$maxi	= $secu->injection(@$_GET['maximal']);
	$menu	= $secu->injection(@$_GET['menudata']);
	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
	
	// READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="8">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		$tabel	= '';
		$no		= $mulai;
		
		$jumlah	= $conn->query("SELECT COUNT(id_program) AS total FROM program_promo WHERE nama_program LIKE '%$cari%' OR kode_program LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
		
		$master	= $conn->prepare("SELECT * FROM program_promo WHERE nama_program LIKE '%$cari%' OR kode_program LIKE '%$cari%' ORDER BY urutan ASC, nama_program ASC LIMIT :mulai, :maxi");
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		
		while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			
			// Status badge
			$statusBadge = ($hasil['status_program'] === 'Active') 
				? '<span class="badge badge-success"><i class="fas fa-check"></i> Active</span>' 
				: '<span class="badge badge-danger"><i class="fas fa-times"></i> Inactive</span>';
			
			// Icon preview
			$iconPreview = '<i class="'.$hasil['icon_class'].' icon-preview"></i>';
			
			// Deskripsi (potong jika terlalu panjang)
			$deskripsi = (strlen($hasil['deskripsi']) > 50) 
				? substr($hasil['deskripsi'], 0, 50).'...' 
				: $hasil['deskripsi'];
			
			// Action buttons
			$edit = ($data->akses($admin, $menu, 'A.update_status')==='Active') 
				? '<a href="#modal1" onclick="crud(\'programpromo\', \'update\', \''.$hasil['id_program'].'\')" data-toggle="modal"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' 
				: '';
			$delete = ($data->akses($admin, $menu, 'A.delete_status')==='Active') 
				? ' <a href="#modal1" onclick="crud(\'programpromo\', \'delete\', \''.$hasil['id_program'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' 
				: '';
			
			$tabel .= '<tr>';
			$tabel .= '<td><center>'.$no.'</center></td>';
			$tabel .= '<td><code>'.$hasil['kode_program'].'</code></td>';
			$tabel .= '<td><strong>'.$hasil['nama_program'].'</strong></td>';
			$tabel .= '<td>'.$deskripsi.'</td>';
			$tabel .= '<td><center>'.$iconPreview.'</center></td>';
			$tabel .= '<td><center>'.$statusBadge.'</center></td>';
			$tabel .= '<td><center>'.$hasil['urutan'].'</center></td>';
			$tabel .= '<td><center>'.$edit.$delete.'</center></td>';
			$tabel .= '</tr>';
		}
		
		if($no == $mulai) {
			$tabel = '<tr><td colspan="8" class="text-center text-muted"><i class="fas fa-inbox"></i> Tidak ada data</td></tr>';
		}
		
		$navi = $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
	}
	
	$conn = $base->close();
	$json = array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo(json_encode($json));
?>
