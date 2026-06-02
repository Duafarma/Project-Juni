<?php
	error_reporting(0);
    require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
    // $conn	= $base->open();
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$catat	= date('Y-m-d H:i:s');
	$act	= $secu->injection(@$_GET['act']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){
		header('location:'.$data->sistem('url_sis').'/signout');
		
	} else {
		$conn	= $base->open();
		switch($act){
			case "input":
				$id		= '';
				$kode	    = $data->basecode('TANDA', 5, 'id_db', 'dokumen_balik');	
                $tgl        = $secu->injection($_POST['tanggal']);
			
				$save	= $conn->prepare("INSERT INTO dokumen_balik VALUES(:kode, :tgl, :catat, :admin, :catat, :admin)");
				// $save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
                // $save->bindParam(":nomorfak", $nomorfak, PDO::PARAM_STR);
                // $save->bindParam(":nama", $nama, PDO::PARAM_STR);
                $save->bindParam(":tgl", $tgl, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();

				$no		= 0;
				$jumlah	= count(@$_POST['no_faktur']);
				while($no<$jumlah)
				{
					$no_faktur_full	= $secu->injection(@$_POST['no_faktur'][$no]);
					$ket	= $secu->injection(@$_POST['ket'][$no]);
					
					// Parse sumber|id_tfk format
					$parts = explode('|', $no_faktur_full);
					$sumber = $parts[0]; // Cendo atau PIM
					$id_tfk = $parts[1]; // actual id_tfk
					
					// Save only the id_tfk to dokumen_balik_detail
					$save	= $conn->prepare("INSERT INTO dokumen_balik_detail VALUES(:id, :kode, :no_faktur, :ket, 'sudah balik',:catat, :admin, :catat, :admin)");
					$save->bindParam(":id", $id, PDO::PARAM_STR);
					$save->bindParam(":kode", $kode, PDO::PARAM_STR);
					$save->bindParam(":no_faktur", $id_tfk, PDO::PARAM_STR);
					$save->bindParam(":ket", $ket, PDO::PARAM_STR);
					$save->bindParam(":catat", $catat, PDO::PARAM_STR);
					$save->bindParam(":admin", $admin, PDO::PARAM_STR);
					$save->execute();
            
                    // Update status based on source
                    if($sumber == 'Cendo') {
                        $update_tfk = $conn->prepare("UPDATE transaksi_faktur SET status_dokumen='sudah balik' WHERE id_tfk= :no_faktur");
                        $update_tfk->bindParam(':no_faktur', $id_tfk, PDO::PARAM_STR);
                        $update_tfk->execute();
                    } else if($sumber == 'PIM') {
                        $update_tfk = $conn->prepare("UPDATE transaksi_faktur_pim SET status_dokumen='sudah balik' WHERE id_tfk= :no_faktur");
                        $update_tfk->bindParam(':no_faktur', $id_tfk, PDO::PARAM_STR);
                        $update_tfk->execute();
                    }
					
				$no++;
				}
			
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'finance Dokumen Balik', 'Create', 'Input Tuker Faktur', '$catat', '$admin')");
				$hasil	= ($save==true) ? "success" : "error";
			
			    echo($hasil);
			break;

			case "delete":
				$kode	= $secu->injection($_POST['keycode']);
				$dele	= $conn->prepare("DELETE A, B FROM finance AS A LEFT JOIN finance_detail AS B ON A.id_finance=B.id_finance WHERE A.id_finance=:kode");
				$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
				$dele->execute();
				/*
				$dele	= $conn->prepare("DELETE FROM outlet_alamat WHERE id_out=:kode");
				$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
				$dele->execute();
				$dele	= $conn->prepare("DELETE FROM outlet_diskon WHERE id_out=:kode");
				$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
				$dele->execute();
				*/
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Finance', 'Delete', '', '$catat', '$admin')");
				$hasil	= ($dele==true) ? "success" : "error";
				echo($hasil);
			break;
		
		}
	}
	$conn	= $base->close();
	// echo($url);
?>