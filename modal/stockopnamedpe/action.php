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
			case 'updateStatusDone':
				$kode	= $secu->injection(@$_POST['keycode']);
				$qty_so	= $secu->injection($_POST['qty_so']);
			
				if ($kode) {
					$update_tfkkb = $conn->prepare("UPDATE produk_stokdetail SET qty_so=:qty_so, status='sudah so' WHERE id_psd=:kode");
					$update_tfkkb->bindParam(':kode', $kode, PDO::PARAM_STR);
					$update_tfkkb->bindParam(':qty_so', $qty_so, PDO::PARAM_STR);
					$update_tfkkb->execute();
					$hasil	= ($update_tfkkb) ? "success" : "error";
				} else {
					$hasil= "error";
				}
				
				$conn	= $base->close();
				echo($hasil);
			break;
		}
		$conn	= $base->open();
		switch($act){
		case "input":
			$id_pro	= $secu->injection($_POST['id_pro']);
			
			$no_bcode	= $secu->injection($_POST['no_bcode']);
			$tgl_expired	= $secu->injection($_POST['tgl_expired']);
			$qty_so 	=$secu->injection($_POST['qty_so']);
			// /SAVE
			$save	= $conn->prepare("INSERT INTO produk_stokdetail VALUES('','', :id_pro, :no_bcode, :tgl_expired,:catat,0,0,0,'Meruya', :qty_so,'sudah so','','active', :catat, :admin, :catat, :admin)");
			$save->bindParam(':id_pro', $id_pro, PDO::PARAM_STR);
			$save->bindParam(':no_bcode', $no_bcode, PDO::PARAM_STR);
			$save->bindParam(':tgl_expired', $tgl_expired, PDO::PARAM_STR);
			$save->bindParam(':qty_so', $qty_so, PDO::PARAM_STR);
			$save->bindParam(':catat', $catat, PDO::PARAM_STR);
			$save->bindParam(':admin', $admin, PDO::PARAM_STR);
			$save->execute();			
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '', 'Nambah Item SO', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		}
	}
?>