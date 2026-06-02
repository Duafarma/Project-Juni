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
				$kode	    = $data->basecode('UPJ', 5, 'id_tf', 'jadwal_tf');	
                $tgl        = $secu->injection($_POST['tanggal']);
				$tujuan        = $secu->injection($_POST['tujuan']);

				$save	= $conn->prepare("INSERT INTO jadwal_tf VALUES(:kode, :tgl, :tujuan, :catat, :admin, :catat, :admin)");
				// $save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":tujuan", $tujuan, PDO::PARAM_STR);

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
					$no_faktur	= $secu->injection(@$_POST['no_faktur'][$no]);
					$ket	= $secu->injection(@$_POST['ket'][$no]);
					// Save
					$save	= $conn->prepare("INSERT INTO jadwal_tf_detail VALUES(:id, :kode, :no_faktur, :ket,:catat, :admin, :catat, :admin)");
					$save->bindParam(":id", $id, PDO::PARAM_STR);
					$save->bindParam(":kode", $kode, PDO::PARAM_STR);
					$save->bindParam(":no_faktur", $no_faktur, PDO::PARAM_STR);
					$save->bindParam(":ket", $ket, PDO::PARAM_STR);
					$save->bindParam(":catat", $catat, PDO::PARAM_STR);
					$save->bindParam(":admin", $admin, PDO::PARAM_STR);
					$save->execute();
					
				$no++;
				}
			
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'upload faktur pajak', 'Create', 'Serah Terima Faailing', '$catat', '$admin')");
				$hasil	= ($save==true) ? "success" : "error";
			
			    echo($hasil);
			break;

		}
	}
	$conn	= $base->close();
	// echo($url);
?>