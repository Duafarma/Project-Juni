<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	require_once('../../config/function/thumb.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$file	= new File;
	$conn	= $base->open();
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$act	= $secu->injection(@$_GET['act']);
	switch($act){
		case "update":
			$pete	= $secu->injection($_POST['pete']);
			$npwp	= $secu->injection($_POST['npwp']);
			$telp	= $secu->injection($_POST['telp']);
			$email	= $secu->injection($_POST['email']);
			$pbf	= $secu->injection($_POST['pbf']);
			$tpbf	= $secu->injection($_POST['tpbf']);
			$cdob	= $secu->injection($_POST['cdob']);
			$tcdob	= $secu->injection($_POST['tcdob']);
			$sipa	= $secu->injection($_POST['sipa']);
			$tsipa	= $secu->injection($_POST['tsipa']);
			$apotek	= $secu->injection($_POST['apoteker']);
			$alamat	= $secu->injection($_POST['alamat']);
			// bank
			$bank	= $secu->injection($_POST['bank']);
			$norek	= $secu->injection($_POST['norek']);
			$anam	= $secu->injection($_POST['anam']);
			// limit
			$limed	= $secu->injection($_POST['limited']);
			//$limsto	= $secu->injection($_POST['limitstok']);
			$limsup	= $secu->injection($_POST['limitsup']);
			$limout	= $secu->injection($_POST['limitout']);
			// sistem
			$namasis= $secu->injection($_POST['namasis']);
			$namaap	= $secu->injection($_POST['namaap']);
			$tagline= $secu->injection($_POST['tagline']);
			$urlsis	= $secu->injection($_POST['urlsis']);
			// logo		
			$loga	= $_FILES["foto1"]['tmp_name'];
			$tiga	= $_FILES["foto1"]['type'];
			$naga	= $_FILES["foto1"]['name'];
			$fofe	= pathinfo($naga, PATHINFO_EXTENSION);
			$unik	= "logo".time().".".$fofe;
			// cap apoteker
			$loga2	= $_FILES["foto2"]['tmp_name'];
			$tiga2	= $_FILES["foto2"]['type'];
			$naga2	= $_FILES["foto2"]['name'];
			$fofe2	= pathinfo($naga2, PATHINFO_EXTENSION);
			$unik2	= "apoteker".time().".".$fofe2;
			
			if(!empty($loga)){
				unlink("../../berkas/sistem/".$data->sistem('logo_sis'));
				$file->uploadfoto('sistem', 'foto1', $unik, 150, 150);
				$edit	= $conn->prepare("UPDATE sistem SET logo_sis=:unik, updated_at=:catat, updated_by=:admin WHERE id_sis=1");
				$edit->bindParam(":unik", $unik, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
	
			if(!empty($loga2)){
				unlink("../../berkas/sistem/".$data->sistem('cap_apoteker'));
				$file->uploadfoto('sistem', 'foto2', $unik2, 300, 150);
				$edit	= $conn->prepare("UPDATE sistem SET cap_apoteker=:unik2, updated_at=:catat, updated_by=:admin WHERE id_sis=1");
				$edit->bindParam(":unik2", $unik2, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			}
	
			$edit	= $conn->prepare("UPDATE sistem SET pt_sis=:pete, npwp_sis=:npwp, telp_sis=:telp, email_sis=:email, pbf_sis=:pbf, tpbf_sis=:tpbf, cdob_sis=:cdob, tcdob_sis=:tcdob, sipa_sis=:sipa, tsipa_sis=:tsipa, apoteker_sis=:apotek, bank_sis=:bank, norek_sis=:norek, anam_sis=:anam, alamat_sis=:alamat, nama_sis=:namasis, app_sis=:namaap, tagline_sis=:tagline, url_sis=:urlsis, limit_expired=:limed, limit_supplier=:limsup, limit_outlet=:limout, updated_at=:catat, updated_by=:admin WHERE id_sis=1");
			$edit->bindParam(":pete", $pete, PDO::PARAM_STR);
			$edit->bindParam(":npwp", $npwp, PDO::PARAM_STR);
			$edit->bindParam(":telp", $telp, PDO::PARAM_STR);
			$edit->bindParam(":email", $email, PDO::PARAM_STR);
			$edit->bindParam(":pbf", $pbf, PDO::PARAM_STR);
			$edit->bindParam(":tpbf", $tpbf, PDO::PARAM_STR);
			$edit->bindParam(":cdob", $cdob, PDO::PARAM_STR);
			$edit->bindParam(":tcdob", $tcdob, PDO::PARAM_STR);
			$edit->bindParam(":sipa", $sipa, PDO::PARAM_STR);
			$edit->bindParam(":tsipa", $tsipa, PDO::PARAM_STR);
			$edit->bindParam(":apotek", $apotek, PDO::PARAM_STR);
			$edit->bindParam(":bank", $bank, PDO::PARAM_STR);
			$edit->bindParam(":norek", $norek, PDO::PARAM_STR);
			$edit->bindParam(":anam", $anam, PDO::PARAM_STR);
			$edit->bindParam(":alamat", $alamat, PDO::PARAM_STR);
			$edit->bindParam(":namasis", $namasis, PDO::PARAM_STR);
			$edit->bindParam(":namaap", $namaap, PDO::PARAM_STR);
			$edit->bindParam(":tagline", $tagline, PDO::PARAM_STR);
			$edit->bindParam(":urlsis", $urlsis, PDO::PARAM_STR);
			$edit->bindParam(":limed", $limed, PDO::PARAM_INT);
			//$edit->bindParam(":limsto", $limsto, PDO::PARAM_INT);
			$edit->bindParam(":limsup", $limsup, PDO::PARAM_INT);
			$edit->bindParam(":limout", $limout, PDO::PARAM_INT);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '1', 'Sistem', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
	}
?>