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
				$kode	    = $data->basecode('FNCKWTTND', 10, 'id_finance', 'finance');	
                $nomorf     = $secu->injection($_POST['nomor']);
                $nama	    = $secu->injection($_POST['nama_outlet']);
                $tgl        = $secu->injection($_POST['tanggal_faktur']);
			
				$save	= $conn->prepare("INSERT INTO finance VALUES(:kode, :nomorf, :nama, :tgl, :id, :catat, :admin, :catat, :admin)");
				$save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
                $save->bindParam(":nomorf", $nomorf, PDO::PARAM_STR);
                // $save->bindParam(":nomorfak", $nomorfak, PDO::PARAM_STR);
                // $save->bindParam(":nama", $nama, PDO::PARAM_STR);
				$save->bindParam(":nama", $nama, PDO::PARAM_STR);
                $save->bindParam(":tgl", $tgl, PDO::PARAM_STR);
				$save->bindParam(":nokwi", $nokwi, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();

			$no = 0;
                $jumlah = count(@$_POST['nokwi']);
                while ($no < $jumlah) {
                    // Pisahkan ID dan sumber
                    list($nokwi_raw, $sumber) = explode('|', $secu->injection(@$_POST['nokwi'][$no]));
                    $ket = $secu->injection(@$_POST['ket'][$no]);
                
                    // Simpan ke finance_detail
                    $save = $conn->prepare("INSERT INTO finance_detail VALUES(:id, :kode, :nokwi, :ket, :catat, :admin, :catat, :admin)");
                    $save->bindParam(":id", $id, PDO::PARAM_STR);
                    $save->bindParam(":kode", $kode, PDO::PARAM_STR);
                    $save->bindParam(":nokwi", $nokwi_raw, PDO::PARAM_STR);
                    $save->bindParam(":ket", $ket, PDO::PARAM_STR);
                    $save->bindParam(":catat", $catat, PDO::PARAM_STR);
                    $save->bindParam(":admin", $admin, PDO::PARAM_STR);
                    $save->execute();
                
                    // Update status tergantung sumber
                    if ($sumber == 'cendo') {
                        $update = $conn->prepare("UPDATE transaksi_faktur SET status_dokumentasi='sudah siap' WHERE id_tfk = :nokwi");
                    } else if ($sumber == 'pim') {
                        $update = $conn->prepare("UPDATE transaksi_faktur_pim SET status_dokumentasi='sudah siap' WHERE id_tfk = :nokwi");
                    }
                    $update->bindParam(':nokwi', $nokwi_raw, PDO::PARAM_STR);
                    $update->execute();
                
                    $no++;
                }

			
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'finance', 'Create', 'Input Tuker Faktur', '$catat', '$admin')");
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