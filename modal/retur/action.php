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
				$kode	    = $data->basecode('RTUR', 5, 'id_r', 'retur');	
                $id_out      = $secu->injection($_POST['id_out']);
                $id_tfk      = $secu->injection($_POST['id_tfk']);
                $retur      = $secu->injection($_POST['no_retur']);
                $keterangan        = $secu->injection($_POST['keterangan']);
                $tanggal        = $secu->injection($_POST['tanggal']);

				$save	= $conn->prepare("INSERT INTO retur VALUES(:kode,:id_out, :id_tfk, :retur, :keterangan, :tanggal, :catat, :admin, :catat, :admin)");
				// $save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":id_out", $id_out, PDO::PARAM_STR);
				$save->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
                $save->bindParam(":retur", $retur, PDO::PARAM_STR);
                $save->bindParam(":keterangan", $keterangan, PDO::PARAM_STR);
				$save->bindParam(":tanggal", $tanggal, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();

				$no		= 0;
				$jumlah	= count(@$_POST['id_pro']);
				while($no<$jumlah)
				{
					$id_pro 	= $secu->injection(@$_POST['id_pro'][$no]);
					$no_bcode	= $secu->injection(@$_POST['no_bcode'][$no]);
                    $ed         = $secu->injection($_POST['ed'][$no]);
                    $ed         = (DateTime::createFromFormat('Y-m-d', $ed)) ? $ed : null;
					$qty     	= $secu->injection(@$_POST['qty'][$no]);
					$gudang 	= $secu->injection(@$_POST['gudang'][$no]);

					// Save
					$save	= $conn->prepare("INSERT INTO retur_detail VALUES(:id, :kode, :id_pro, :no_bcode, :ed, :qty,:gudang,:catat, :admin, :catat, :admin)");
					$save->bindParam(":id", $id, PDO::PARAM_STR);
					$save->bindParam(":kode", $kode, PDO::PARAM_STR);
					$save->bindParam(":id_pro", $id_pro, PDO::PARAM_STR);
					$save->bindParam(":no_bcode", $no_bcode, PDO::PARAM_STR);
					$save->bindParam(":ed", $ed, PDO::PARAM_STR);
					$save->bindParam(":qty", $qty, PDO::PARAM_STR);
					$save->bindParam(":gudang", $gudang, PDO::PARAM_STR);
					$save->bindParam(":catat", $catat, PDO::PARAM_STR);
					$save->bindParam(":admin", $admin, PDO::PARAM_STR);
					$save->execute();
				$no++;
				}
					//STOK
					$save	= $conn->prepare("INSERT INTO inventory_retur SELECT '', id_r_d, id_pro, no_bcode, ed, :tanggal, jumlah, '0', jumlah ,gudang, created_at, created_by, updated_at, updated_by FROM retur_detail WHERE id_r=:kode");
					$save->bindParam(':kode', $kode, PDO::PARAM_STR);
					$save->bindParam(':tanggal', $tanggal, PDO::PARAM_STR);
					$save->execute();
				
			
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Retur Barang', 'Create', 'Serah Terima Faailing', '$catat', '$admin')");
				$hasil	= ($save==true) ? "success" : "error";
			
			    echo($hasil);
			break;

            case "update":
				$kode	= $secu->injection($_POST['keycode']);
				$id_out	= $secu->injection($_POST['id_out']);
				$id_tfk	= $secu->injection($_POST['id_tfk']);
				$no_retur	= $secu->injection($_POST['no_retur']);
				$ket	= $secu->injection($_POST['ket']);
				$tanggal	= $secu->injection($_POST['tanggal']);

	
				$edit	= $conn->prepare("UPDATE retur SET id_out=:id_out, id_tfk=:id_tfk, no_retur=:no_retur, keterangan=:ket, tanggal=:tanggal, updated_at=:catat, updated_by=:admin WHERE id_r=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":id_out", $id_out, PDO::PARAM_STR);
				$edit->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
				$edit->bindParam(":no_retur", $no_retur, PDO::PARAM_STR);
				$edit->bindParam(":ket", $ket, PDO::PARAM_STR);
				$edit->bindParam(":tanggal", $tanggal, PDO::PARAM_STR);
	
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Barang Retur', 'Update', '', '$catat', '$admin')");
				$hasil	= ($edit==true) ? "success" : "error";
				echo($hasil);
			break;

			case "delete":
				$kode	= $secu->injection($_POST['keycode']);
				$dele	= $conn->prepare("DELETE A, B, C FROM retur AS A LEFT JOIN retur_detail AS B ON A.id_r=B.id_r LEFT JOIN inventory_retur AS C ON B.id_r_d=C.id_r_d WHERE A.id_r=:kode");
				$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
				$dele->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Retur Barang', 'Delete', '', '$catat', '$admin')");
				$hasil	= ($dele==true) ? "success" : "error";
				echo($hasil);
			break;
		
		}
	}
	$conn	= $base->close();
	// echo($url);
?>