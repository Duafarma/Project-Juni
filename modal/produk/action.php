<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$catat	= date('Y-m-d H:i:s');
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$menu	= $secu->injection(@$_POST['namamenu']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){
		//header('location:'.$data->sistem('url_sis').'/signout');
		$url	= 'signout';
	} else {
		switch($menu){
			case "input":
				$id		= '';
				$kode	= $data->basecode('PRO', 5, 'id_pro', 'produk');
				$kate	= $secu->injection($_POST['kategori']);
				$satuan	= $secu->injection($_POST['satuan']);
				$nama	= $secu->injection($_POST['nama']);
				$nama_p	= $secu->injection($_POST['nama_p']);
				$nomor	= $secu->injection($_POST['nomor']);
				$berat	= $secu->injection($_POST['berat']);
				$rak	= $secu->injection($_POST['rak']);
				$sek	= $secu->injection($_POST['sek']);
				$harga	= str_replace('.', '', $_POST['harga']);
				$hargap	= str_replace('.', '', $_POST['hargappn']);
				$mini	= str_replace('.', '', $_POST['minstok']);
				$active	= 'Active';
				$obat	= $secu->injection($_POST['kategori_obat']);
				$kodep	= $secu->injection($_POST['kode_produk_jadi']);
				$nie	= $secu->injection($_POST['no_nie']);
				$tgl	= $secu->injection($_POST['tgl_nie']);
				$code	= $secu->injection($_POST['new_code']);
				$zat_pre	= $secu->injection($_POST['zat_pre']);
				$kekuatan	= $secu->injection($_POST['kekuatan']);
				$ccp	= $secu->injection($_POST['ccp']);
				
				$save	= $conn->prepare("INSERT INTO produk VALUES(:kode, :kate,  :satuan, :nomor, :nama,:nama_p, :berat, :rak, :sek, :mini, :active, :obat, :kodep, :nie, :tgl, :code, :zat_pre, :kekuatan, :ccp, :catat, :admin, :catat, :admin)");
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":kate", $kate, PDO::PARAM_STR);
				$save->bindParam(":satuan", $satuan, PDO::PARAM_STR);
				$save->bindParam(":nomor", $nomor, PDO::PARAM_STR);
				$save->bindParam(":nama", $nama, PDO::PARAM_STR);
				$save->bindParam(":nama_p", $nama_p, PDO::PARAM_STR);
				$save->bindParam(":berat", $berat, PDO::PARAM_STR);
				$save->bindParam(":rak", $rak, PDO::PARAM_STR);
				$save->bindParam(":sek", $sek, PDO::PARAM_STR);
				$save->bindParam(":mini", $mini, PDO::PARAM_INT);
				$save->bindParam(":active", $active, PDO::PARAM_STR);
				$save->bindParam(":obat", $obat, PDO::PARAM_STR);
				$save->bindParam(":kodep", $kodep, PDO::PARAM_STR);
				$save->bindParam(":nie", $nie, PDO::PARAM_STR);
				$save->bindParam(":tgl", $tgl, PDO::PARAM_STR);
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":zat_pre", $zat_pre, PDO::PARAM_STR);
				$save->bindParam(":kekuatan", $kekuatan, PDO::PARAM_STR);
				$save->bindParam(":ccp", $ccp, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				/*
				$save	= $conn->prepare("INSERT INTO produk_stok(id_pro, created_at, created_by, updated_at, updated_by) VALUES(:kode, :catat, :admin, :catat, :admin)");
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				*/
				$save	= $conn->prepare("INSERT INTO produk_harga VALUES(:id, :kode, :harga, :hargap, :active, :catat, :admin, :catat, :admin)");
				$save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":harga", $harga, PDO::PARAM_INT);
				$save->bindParam(":hargap", $hargap, PDO::PARAM_INT);
				$save->bindParam(":active", $active, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Produk', 'Create', '', '$catat', '$admin')");
				if($save==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data produk berhasil diinput.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data produk gagal diinput.', time() + 5, '/');
				}
			break;
			case "update":
				$kode	= $secu->injection($_POST['keycode']);
				$kate	= $secu->injection($_POST['kategori']);
				$satuan	= $secu->injection($_POST['satuan']);
				$nama	= $secu->injection($_POST['nama']);
				$nama_p	= $secu->injection($_POST['nama_p']);
				$obat	= $secu->injection($_POST['kategori_obat']);
				$kodep	= $secu->injection($_POST['kode_produk_jadi']);
				$nie	= $secu->injection($_POST['no_nie']);
				$tgl	= $secu->injection($_POST['tgl_nie']);
				$code	= $secu->injection($_POST['new_code']);
				$zat_pre	= $secu->injection($_POST['zat_pre']);
				$kekuatan	= $secu->injection($_POST['kekuatan']);
				$ccp	= $secu->injection($_POST['ccp']);
				$nomor	= $secu->injection($_POST['nomor']);
				$berat	= $secu->injection($_POST['berat']);
				$rak	= $secu->injection($_POST['rak']);
				$sek	= $secu->injection($_POST['sek']);
				$mini	= str_replace('.', '', $_POST['minstok']);
				$edit	= $conn->prepare("UPDATE produk SET id_kpr=:kate, id_spr=:satuan,nama_p=:nama_p, kategori_obat=:obat, kode_produk_jadi=:kodep, no_nie=:nie, tgl_nie=:tgl, new_code=:code, zat_pre=:zat_pre, kekuatan=:kekuatan, ccp=:ccp, kode_pro=:nomor, nama_pro=:nama, berat_pro=:berat, rak_pro=:rak, section_pro=:sek, minstok_pro=:mini, updated_at=:catat, updated_by=:admin WHERE id_pro=:kode");
				$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":kate", $kate, PDO::PARAM_STR);
				$edit->bindParam(":satuan", $satuan, PDO::PARAM_STR);
				$edit->bindParam(":nama_p", $nama_p, PDO::PARAM_STR);
				$edit->bindParam(":nomor", $nomor, PDO::PARAM_STR);
				$edit->bindParam(":obat", $obat, PDO::PARAM_STR);
				$edit->bindParam(":kodep", $kodep, PDO::PARAM_STR);
				$edit->bindParam(":nie", $nie, PDO::PARAM_STR);
				$edit->bindParam(":tgl", $tgl, PDO::PARAM_STR);
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->bindParam(":zat_pre", $zat_pre, PDO::PARAM_STR);
				$edit->bindParam(":kekuatan", $kekuatan, PDO::PARAM_STR);
				$edit->bindParam(":ccp", $ccp, PDO::PARAM_STR);
				$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
				$edit->bindParam(":berat", $berat, PDO::PARAM_STR);
				$edit->bindParam(":rak", $rak, PDO::PARAM_STR);
				$edit->bindParam(":sek", $sek, PDO::PARAM_STR);
				$edit->bindParam(":mini", $mini, PDO::PARAM_INT);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Produk', 'Update', '', '$catat', '$admin')");
				if($edit==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data produk berhasil diupdate.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data produk gagal diupdate.', time() + 5, '/');
				}
			break;
			case "delete":
				$kode	= $secu->injection($_POST['keycode']);
				$remove	= $conn->prepare("DELETE A, B FROM produk AS A LEFT JOIN produk_stok AS B ON A.id_pro=B.id_pro LEFT JOIN produk_harga AS C ON A.id_pro=C.id_pro WHERE A.id_pro=:kode");
				$remove->bindParam(":kode", $kode, PDO::PARAM_STR);
				$remove->execute();
				/*
				$dele	= $conn->prepare("DELETE FROM produk_stok WHERE id_pro=:kode");
				$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
				$dele->execute();
				*/
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Produk', 'Delete', '', '$catat', '$admin')");
				if($remove==true){
					setcookie('info', 'success', time() + 5, '/');
					setcookie('pesan', 'Data produk berhasil dihapus.', time() + 5, '/');
				} else {
					setcookie('info', 'danger', time() + 5, '/');
					setcookie('pesan', 'Data produk gagal dihapus.', time() + 5, '/');
				}
			break;
		}
		$url	= 'produk';
	}
	$conn	= $base->close();
	echo($url);
?>