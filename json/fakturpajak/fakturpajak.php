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
						faktur_pajak AS A
					LEFT JOIN 
					    transaksi_faktur AS B ON
						A.id_tfk = B.id_tfk
					WHERE
						A.id_tfk LIKE '%$cari%'";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
						A.id_tfk,
						A.id_f_p,
                        A.status_f_pajak,
                        A.nomor_seri,
						A.tanggal_i_p,
						A.keterangan,
						B.id_tfk,
						B.kode_tfk,
						B.tgl_tfk
					
					FROM
						faktur_pajak AS A
					LEFT JOIN transaksi_faktur AS B ON
						A.id_tfk = B.id_tfk
					WHERE
						A.id_tfk LIKE '%$cari%'
					
					ORDER BY
						A.tanggal_i_p DESC
					LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	= base64_encode($hasil['id_f_p']);
			

			$edit	= ($data->akses($admin, $menu, 'A.update_status')==='Active' && in_array($hasil['status_f_pajak'],array('sudah terbit'))) ? '<a href="'.$data->sistem('url_sis').'/fakturpajak/e/'.$hasil['id_f_p'].'"><span class="badge badge-info"><i class="fa fa-edit"></i></span></a>' : '';
			// $tf		= ($data->akses($admin, $menu, 'A.tf_status')==='Active') ? ' <a href="#modal1" onclick="crud()" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-tags"></i></span></a>' : '';
			$delete	= ($data->akses($admin, $menu, 'A.delete_status')==='Active' && in_array($hasil['status_tfk'],array('Faktur','Tagihan'))) ? ' <a href="#modal1" onclick="crud(\'fsales\', \'delete\', \''.$hasil['id_tfk'].'\')" data-toggle="modal"><span class="badge badge-danger"><i class="fa fa-trash"></i></span></a>' : '';
			$status	= ($hasil['status_tfk']=='Tagihan') ? 'Belum Bayar' : (($hasil['status_tfk']=='Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
			$tabel	.= '<tr><td><center>'.$no.'</center></td><td><center>'.$hasil['kode_tfk'].'</td></center><td><center>'.$hasil['tgl_tfk'].'</center></td><td><center>'.$hasil['nomor_seri'].'</center></td><td><center>'.$hasil['status_f_pajak'].'</center></td><td><center>'.$edit.'</center></td></tr>';
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
