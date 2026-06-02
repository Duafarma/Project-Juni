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
	//READ DATA
	if($valid==false){
		$tabel	= '<tr><td colspan="12">Session login anda habis...</td></tr>';
		$navi	= '';
	} else {
		// Parse filter tanggal dari cari
		$pecah	= explode('_', $cari);
		$search	= @$pecah[0];
		$tgl1	= empty($pecah[1]) ? "" : "AND DATE(A.cancel_at)>='$pecah[1]'";
		$tgl2	= empty($pecah[2]) ? "" : "AND DATE(A.cancel_at)<='$pecah[2]'";
		
		$tabel	= '';
		$no		= $mulai;
		
		// Query untuk menghitung total data
		$qJumlah = "SELECT COUNT(*) AS total 
					FROM produk_stockdetail_cancel AS A
					LEFT JOIN produk AS B ON A.id_pro = B.id_pro
					LEFT JOIN adminz AS C ON A.cancel_by = C.id_adm
					WHERE 1=1 $tgl1 $tgl2
					AND (
						A.kode_faktur LIKE '%$search%'
						OR B.nama_pro LIKE '%$search%'
						OR A.no_bcode LIKE '%$search%'
						OR A.keterangan_cancel LIKE '%$search%'
					)";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		
		// Query untuk mengambil data
		$qMaster = "SELECT 
						A.id_psc,
						A.id_psd,
						A.id_pro,
						A.id_tfk,
						A.kode_faktur,
						A.tgl_faktur,
						A.no_bcode,
						A.tgl_expired,
						A.tgl_psd,
						A.jumlah_cancel,
						A.gudang,
						A.keterangan_cancel,
						A.dari_konsinyasi,
						A.status,
						A.cancel_at,
						A.cancel_by,
						B.nama_pro,
						B.kode_produk_jadi,
						C.nama_adm AS cancel_by_name
					FROM produk_stockdetail_cancel AS A
					LEFT JOIN produk AS B ON A.id_pro = B.id_pro
					LEFT JOIN adminz AS C ON A.cancel_by = C.id_adm
					WHERE 1=1 $tgl1 $tgl2
					AND (
						A.kode_faktur LIKE '%$search%'
						OR B.nama_pro LIKE '%$search%'
						OR A.no_bcode LIKE '%$search%'
						OR A.keterangan_cancel LIKE '%$search%'
					)
					ORDER BY A.cancel_at DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		
		while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			
			// Format tanggal
			$tglFaktur = !empty($hasil['tgl_faktur']) ? date('d-m-Y', strtotime($hasil['tgl_faktur'])) : '-';
			$tglExpired = !empty($hasil['tgl_expired']) ? date('d-m-Y', strtotime($hasil['tgl_expired'])) : '-';
			$cancelAt = !empty($hasil['cancel_at']) ? date('d-m-Y H:i', strtotime($hasil['cancel_at'])) : '-';
			
			// Potong keterangan jika terlalu panjang
			$keterangan = strlen($hasil['keterangan_cancel']) > 30 
				? substr($hasil['keterangan_cancel'], 0, 30) . '...' 
				: $hasil['keterangan_cancel'];
			
			// Badge untuk konsinyasi
			$badgeKonsi = ($hasil['dari_konsinyasi'] === 'ya') 
				? '<span class="badge badge-warning" title="Dari Konsinyasi"><i class="fa fa-box"></i></span> ' 
				: '';
			
			// Badge status
			$statusBadge = ($hasil['status'] == 'transferred') 
				? '<span class="badge badge-success"><i class="fa fa-check"></i></span>'
				: '<span class="badge badge-danger"><i class="fa fa-times"></i></span>';
			
			// Tombol detail
			$btnDetail = '<a href="#modalDetailCancel" onclick="showDetailCancel(\''.$hasil['id_psc'].'\')" data-toggle="modal" title="Lihat Detail"><span class="badge badge-info"><i class="fa fa-eye"></i></span></a>';
			
		
			
			$tabel .= '<tr>
				<td><center>'.$no.'</center></td>
				<td>'.$badgeKonsi.$hasil['kode_faktur'].'</td>
				<td>'.$hasil['nama_pro'].'</td>
				<td>'.$hasil['no_bcode'].'</td>
				<td><center>'.$tglFaktur.'</center></td>
				<td><center>'.$tglExpired.'</center></td>
				<td><center><strong>'.$data->angka($hasil['jumlah_cancel']).'</strong></center></td>
				<td><center>'.$hasil['gudang'].'</center></td>
				<td title="'.$hasil['keterangan_cancel'].'">'.$keterangan.'</td>
				<td><center>'.$statusBadge.'</center></td>
				<td><center>'.$hasil['cancel_by_name'].'</center></td>
				<td><center>'.$cancelAt.'</center></td>
				<td><center>'.$btnDetail.'</center></td>
			</tr>';
		}
		
		// Jika tidak ada data
		if(empty($tabel)){
			$tabel = '<tr><td colspan="13" class="text-center text-muted py-4"><i class="fa fa-inbox fa-2x"></i><br>Tidak ada data stok cancel</td></tr>';
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
