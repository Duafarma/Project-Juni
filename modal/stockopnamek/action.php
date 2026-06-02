<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$menu	= $secu->injection(@$_POST['namamenu']);
	$act	= $secu->injection(@$_GET['act']);
	$secu->validadmin($admin, $kunci);
	if ($secu->validadmin($admin, $kunci)==false) {
		header('location:'.$data->sistem('url_sis').'/signout');
	} else {
		$conn	= $base->open();
		switch($menu){	
			case 'updateStatusCancel':
				$kode	= $secu->injection(@$_POST['keycode']);
			
				if ($kode) {
					$update_tfkkb = $conn->prepare("UPDATE produk_stokdetail SET status='proses revisi' WHERE id_psd=:kode");
					$update_tfkkb->bindParam(':kode', $kode, PDO::PARAM_STR);
					// $update_tfkkb->bindParam(':qty_so', $qty_so, PDO::PARAM_STR);
					$update_tfkkb->execute();
					$hasil	= ($update_tfkkb) ? "success" : "error";
				} else {
					$hasil= "error";
				}
				
				$conn	= $base->close();
				echo($hasil);
			break;

			case 'updateStatusRevisi':
				$kode	= $secu->injection(@$_POST['keycode']);
			
				if ($kode) {
					$update_tfkkb = $conn->prepare("UPDATE produk_stokdetail SET status='selesai so' WHERE id_psd=:kode");
					$update_tfkkb->bindParam(':kode', $kode, PDO::PARAM_STR);
					// $update_tfkkb->bindParam(':qty_so', $qty_so, PDO::PARAM_STR);
					$update_tfkkb->execute();
					$hasil	= ($update_tfkkb) ? "success" : "error";
				} else {
					$hasil= "error";
				}
				
				$conn	= $base->close();
				echo($hasil);
			break;
			
			case 'updateStatusPersetujuan':
				$kode	= $secu->injection(@$_POST['keycode']);
			
				if ($kode) {
					$update_tfkkb = $conn->prepare("UPDATE produk_stokdetail SET status='persetujuan' WHERE id_psd=:kode");
					$update_tfkkb->bindParam(':kode', $kode, PDO::PARAM_STR);
					// $update_tfkkb->bindParam(':qty_so', $qty_so, PDO::PARAM_STR);
					$update_tfkkb->execute();
					$hasil	= ($update_tfkkb) ? "success" : "error";
				} else {
					$hasil= "error";
				}
				
				$conn	= $base->close();
				echo($hasil);
			break;
		}
	}
	
	if ($secu->validadmin($admin, $kunci)==false) {
		header('location:'.$data->sistem('url_sis').'/signout');
	} else {
		$conn	= $base->open();
		switch($act){
			case "input":
				$id		= '';
				$jumlah	= count($_POST['id_pro']);
				$nomor	= 0;
				while($nomor<$jumlah){
				// $jumlah	= count($_POST['id_pro']);			

						$id_psd			= $secu->injection(@$_POST['id_psd'][$nomor]);
						$id_pro			= $secu->injection(@$_POST['id_pro'][$nomor]);
						$nama_pro		= $secu->injection(@$_POST['nama_pro'][$nomor]);
						$no_bcode		= $secu->injection(@$_POST['no_bcode'][$nomor]);
						$qty			= $secu->injection(@$_POST['qty'][$nomor]);
						$bcode_so		= $secu->injection(@$_POST['bcode_so'][$nomor]);
						$qty_so			= $secu->injection(@$_POST['qty_so'][$nomor]);
						$selisih		= $secu->injection(@$_POST['selisih'][$nomor]);
						$status			= $secu->injection(@$_POST['status'][$nomor]);

						$save	= $conn->prepare("INSERT INTO so VALUES(:id,:id_psd, :id_pro, :nama_pro, :no_bcode, :qty, :bcode_so, :qty_so, :selisih, 'selesai so', :catat, :admin, :catat, :admin)");
						$save->bindParam(":id", $id, PDO::PARAM_STR);
						$save->bindParam(":id_psd", $id_psd, PDO::PARAM_STR);
						$save->bindParam(":id_pro", $id_pro, PDO::PARAM_STR);
						$save->bindParam(":nama_pro", $nama_pro, PDO::PARAM_STR);
						$save->bindParam(":no_bcode", $no_bcode, PDO::PARAM_STR);
						$save->bindParam(":qty", $qty, PDO::PARAM_STR);
						$save->bindParam(":bcode_so", $bcode_so, PDO::PARAM_STR);
						$save->bindParam(":qty_so", $qty_so, PDO::PARAM_STR);
						$save->bindParam(":selisih", $selisih, PDO::PARAM_STR);
						$save->bindParam(":status", $status, PDO::PARAM_STR);
						$save->bindParam(':catat', $catat, PDO::PARAM_STR);
						$save->bindParam(':admin', $admin, PDO::PARAM_STR);
						$save->execute();


						$id_psd				= $secu->injection(@$_POST['id_psd'][$nomor]);
						$update_tfk			= $conn->prepare("UPDATE produk_stokdetail SET status='selesai so' WHERE id_psd= :id_psd");
						$update_tfk->bindParam(':id_psd', $id_psd, PDO::PARAM_STR);

						$update_tfk->execute();
				
						$nomor++;
					}
				
					
					//RIWAYAT
					$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$id', 'Administrator', 'Create', '', '$catat', '$admin')");
					$hasil	= ($save==true) ? "success" : "error";
			

				// $conn	= $base->close();
				echo($hasil);
			break;
	
		}
	}
?>