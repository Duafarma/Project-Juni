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
		$pecah	= explode('_', $cari);
		$cari	= $data->cekcari($pecah[0], '-', ' ');
		$tgl1	= empty($pecah[1]) ? "" : "AND tgl_tfk>='$pecah[1]'"; 
		$tgl2	= empty($pecah[2]) ? "" : "AND tgl_tfk<='$pecah[2]'"; 
		$tabel	= '';
		$no		= $mulai;
		$qJumlah = "SELECT
						COUNT(*) AS total
					FROM
						transaksi_faktur 
					WHERE
						tgl_tfk LIKE '%$cari%' $tgl1 $tgl2 ";
		$jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
		$qMaster = "SELECT
                        COUNT(*) AS jumlah,  
						id_tfk,
						tgl_tfk,
                        status_balik
					FROM
						transaksi_faktur
					WHERE
						tgl_tfk LIKE '%$cari%' $tgl1 $tgl2
					GROUP BY
						tgl_tfk DESC  LIMIT :mulai, :maxi";
		$master	= $conn->prepare($qMaster);
		$master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
		$master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
		$master->execute();
		while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
			$no++;
			$uniq	              		= base64_encode($hasil['id_tfk']);

			$selesaipengiriman			= $data->selesaipengirimanbarang($hasil['tgl_tfk']);
			$belumpengiriman			= $data->belumpengirimanbarang($hasil['tgl_tfk']);

            $belumbalik		      		= $data->belumbalik($hasil['tgl_tfk']);
			$sudahbalik		      		= $data->sudahbalik($hasil['tgl_tfk']);

            $belumfiling		    	= $data->belumfiling($hasil['tgl_tfk']);
			$sudahfiling		    	= $data->sudahfiling($hasil['tgl_tfk']);

            $beluminputpajak		    = $data->beluminputpajak($hasil['tgl_tfk']);
			$sudahnputpajak		  		= $data->sudahinputpajak($hasil['tgl_tfk']);
			
			$uploadpajak				= $data->uploadpajak($hasil['tgl_tfk']);
			$sudahuploadpajak			= $data->sudahuploadpajak($hasil['tgl_tfk']);

			$belumpemberkasan		    = $data->belumpemberkasan($hasil['tgl_tfk']);
			$siappemberkasan			= $data->siappemberkasan($hasil['tgl_tfk']);

			$selesaitf					= $data->selesaitf($hasil['tgl_tfk']);
			$belumtf					= $data->belumtf($hasil['tgl_tfk']);

			$sudahpembayaran			= $data->sudahpembayaran($hasil['tgl_tfk']);
			$belumpembayaran			= $data->belumpembayaran($hasil['tgl_tfk']);
			$bayarsebagian				= $data->bayarsebagian($hasil['tgl_tfk']);

			$kosong						='';

			$tabel	.= '<tr>
								<td><center>'.$no.'</center></td>
								<td><center>'.$data->tgldd($hasil['tgl_tfk']).'</center></td>
								<td><center>'.$hasil['jumlah'].'</center></td>

								<td><center>'.$selesaipengiriman.'</center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/blt/'.$hasil['tgl_tfk'].'">'.$belumpengiriman.'</a></center></td>

								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/b/'.$hasil['tgl_tfk'].'">'.$sudahbalik.'</a></center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/v/'.$hasil['tgl_tfk'].'">'.$belumbalik.'</a></center></td>
								
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/fs/'.$hasil['tgl_tfk'].'">'.$sudahfiling.'</a></center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/fb/'.$hasil['tgl_tfk'].'">'.$belumfiling.'</a></center></td>

								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/ps/'.$hasil['tgl_tfk'].'">'.$sudahnputpajak.'</a></center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/pb/'.$hasil['tgl_tfk'].'">'.$beluminputpajak.'</a></center></td>

								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/us/'.$hasil['tgl_tfk'].'">'.$sudahuploadpajak.'</a></center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/ub/'.$hasil['tgl_tfk'].'">'.$uploadpajak.'</a></center></td>

								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/ts/'.$hasil['tgl_tfk'].'">'.$siappemberkasan.'</a></center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/tb/'.$hasil['tgl_tfk'].'">'.$belumpemberkasan.'</a></center></td>

								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/tfs/'.$hasil['tgl_tfk'].'">'.$selesaitf.'</a></center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/tfb/'.$hasil['tgl_tfk'].'">'.$belumtf.'</a></center></td>

								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/sp/'.$hasil['tgl_tfk'].'">'.$sudahpembayaran.'</center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/stp/'.$hasil['tgl_tfk'].'">'.$bayarsebagian.'</center></td>
								<td><center><a href="'.$data->sistem('url_sis').'/alurdokumen/bp/'.$hasil['tgl_tfk'].'">'.$belumpembayaran.'</center></td>
						</tr>';
		
		
		
		}
		$navi	= $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
	}
	$conn	= $base->close();
	$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
	http_response_code(200);
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	// header('Content-type: text/html; charset=UTF-8');
	echo(json_encode($json));
?>
