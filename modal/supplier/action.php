<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$catat	= date('Y-m-d H:i:s');
	$act	= $secu->injection(@$_GET['act']);
	$secu->validadmin($admin, $kunci);
	if($secu->validadmin($admin, $kunci)==false){ header('location:'.$data->sistem('url_sis').'/signout'); } else {
	$conn	= $base->open();
	switch($act){
		case "input":
			// Basic
			$id		= '';
			$code	= 'SUP'.time();
			$kate	= $secu->injection($_POST['kategori']);
			$kode	= $data->bcode("SP", 'kode_sup', 'supplier');
			$nama	= $secu->injection($_POST['nama']);
			$coper	= $secu->injection($_POST['coper']);
			$npwp	= $secu->injection($_POST['npwp']);
			$mini	= $secu->injection($_POST['mini']);
			$dismi	= $secu->injection($_POST['dismi']);
			$disma	= $secu->injection($_POST['disma']);
			$limit	= $secu->injection($_POST['limit']);
			// Alamat
			$telp	= $secu->injection($_POST['telp']);
			$hape	= $secu->injection($_POST['hape']);
			$fax	= $secu->injection($_POST['fax']);
			$email	= $secu->injection($_POST['email']);
			$web	= $secu->injection($_POST['website']);
			$prov	= $secu->injection($_POST['provinsi']);
			$kab	= $secu->injection($_POST['kabupaten']);
			$kopos	= $secu->injection($_POST['kopos']);
			$alamat	= $secu->injection($_POST['alamat']);
			$status	= 'Active';
			// Save Basic
			$save	= $conn->prepare("INSERT INTO supplier VALUES(:code, :kate, :kode, :nama, :coper, :npwp, :status, :catat, :admin, :catat, :admin)");
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":kate", $kate, PDO::PARAM_STR);
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":nama", $nama, PDO::PARAM_STR);
			$save->bindParam(":coper", $coper, PDO::PARAM_STR);
			$save->bindParam(":npwp", $npwp, PDO::PARAM_STR);
			$save->bindParam(":status", $status, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			// Save Alamat
			$save	= $conn->prepare("INSERT INTO supplier_alamat VALUES(:id, :code, :telp, :hape, :fax, :email, :web, :prov, :kab, :kopos, :alamat, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":telp", $telp, PDO::PARAM_STR);
			$save->bindParam(":hape", $hape, PDO::PARAM_STR);
			$save->bindParam(":fax", $fax, PDO::PARAM_STR);
			$save->bindParam(":email", $email, PDO::PARAM_STR);
			$save->bindParam(":prov", $prov, PDO::PARAM_STR);
			$save->bindParam(":web", $web, PDO::PARAM_STR);
			$save->bindParam(":kab", $kab, PDO::PARAM_STR);
			$save->bindParam(":kopos", $kopos, PDO::PARAM_STR);
			$save->bindParam(":alamat", $alamat, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			// Save Diskon
			$save	= $conn->prepare("INSERT INTO supplier_diskon VALUES(:id, :code, :limit, :mini, :dismi, :disma, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":limit", $limit, PDO::PARAM_STR);
			$save->bindParam(":mini", $mini, PDO::PARAM_STR);
			$save->bindParam(":dismi", $dismi, PDO::PARAM_STR);
			$save->bindParam(":disma", $disma, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Suppilier', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
		case "update":
			// Basic
			$code	= $secu->injection(@$_POST['keycode']);
			$kate	= $secu->injection(@$_POST['kategori']);
			$kode	= $data->bcode("SP", 'kode_sup', 'supplier');
			$nama	= $secu->injection(@$_POST['nama']);
			$coper	= $secu->injection(@$_POST['coper']);
			$npwp	= $secu->injection(@$_POST['npwp']);
			$mini	= $secu->injection(@$_POST['mini']);
			$dismi	= $secu->injection(@$_POST['dismi']);
			$disma	= $secu->injection(@$_POST['disma']);
			$limit	= $secu->injection(@$_POST['limit']);
			// Alamat
			$telp	= $secu->injection(@$_POST['telp']);
			$hape	= $secu->injection(@$_POST['hape']);
			$fax	= $secu->injection(@$_POST['fax']);
			$email	= $secu->injection(@$_POST['email']);
			$web	= $secu->injection(@$_POST['website']);
			$prov	= $secu->injection(@$_POST['provinsi']);
			$kab	= $secu->injection(@$_POST['kabupaten']);
			$kopos	= $secu->injection(@$_POST['kopos']);
			$alamat	= $secu->injection(@$_POST['alamat']);
			$status	= 'Active';
			// Save Basic
			$edit	= $conn->prepare("UPDATE supplier SET id_ksp=:kate, kode_sup=:kode, nama_sup=:nama, npwp_sup=:npwp, updated_at=:catat, updated_by=:admin WHERE id_sup=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":kate", $kate, PDO::PARAM_STR);
			$edit->bindParam(":kode", $kode, PDO::PARAM_STR);
			$edit->bindParam(":nama", $nama, PDO::PARAM_STR);
			$edit->bindParam(":npwp", $npwp, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			// Save Alamat
			$edit	= $conn->prepare("UPDATE supplier_alamat SET telp_sal=:telp, hp_sal=:hape, fax_sal=:fax, email_sal=:email, web_sal=:web, id_rpo=:prov, id_rkb=:kab, kopos_sal=:kopos, alamat_sal=:alamat, updated_at=:catat, updated_by=:admin WHERE id_sup=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":telp", $telp, PDO::PARAM_STR);
			$edit->bindParam(":hape", $hape, PDO::PARAM_STR);
			$edit->bindParam(":fax", $fax, PDO::PARAM_STR);
			$edit->bindParam(":email", $email, PDO::PARAM_STR);
			$edit->bindParam(":web", $web, PDO::PARAM_STR);
			$edit->bindParam(":prov", $prov, PDO::PARAM_STR);
			$edit->bindParam(":kab", $kab, PDO::PARAM_STR);
			$edit->bindParam(":kopos", $kopos, PDO::PARAM_STR);
			$edit->bindParam(":alamat", $alamat, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			// Update Diskon Produk
			$jumlah	= is_array(@$_POST['product']) ? count(@$_POST['product']) : 0;
			for($no=0; $no<$jumlah; $no++){
				$product= $secu->injection(@$_POST['product'][$no]);
				$dispro	= $secu->injection(@$_POST['dispro'][$no]);
				// Cek apakah sudah ada, jika belum insert, jika sudah update
				$cek = $conn->prepare("SELECT COUNT(*) as jml FROM produk_diskonsup WHERE id_sup=:code AND id_pro=:product");
				$cek->bindParam(":code", $code, PDO::PARAM_STR);
				$cek->bindParam(":product", $product, PDO::PARAM_STR);
				$cek->execute();
				$ada = $cek->fetch(PDO::FETCH_ASSOC);
				if($ada['jml']>0){
					// Update
					$save = $conn->prepare("UPDATE produk_diskonsup SET persen_pds=:dispro, updated_at=:catat, updated_by=:admin WHERE id_sup=:code AND id_pro=:product");
				} else {
					// Insert
					$save = $conn->prepare("INSERT INTO produk_diskonsup(id_sup, id_pro, persen_pds, created_at, created_by, updated_at, updated_by) VALUES(:code, :product, :dispro, :catat, :admin, :catat, :admin)");
				}
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":product", $product, PDO::PARAM_STR);
				$save->bindParam(":dispro", $dispro, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
			}
			// Save Diskon
			$edit	= $conn->prepare("UPDATE supplier_diskon SET top_sdi=:limit, parameter_sdi=:mini, diskon1_sdi=:dismi, diskon2_sdi=:disma, updated_at=:catat, updated_by=:admin WHERE id_sup=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":limit", $limit, PDO::PARAM_STR);
			$edit->bindParam(":mini", $mini, PDO::PARAM_STR);
			$edit->bindParam(":dismi", $dismi, PDO::PARAM_STR);
			$edit->bindParam(":disma", $disma, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Suppilier', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE A, B FROM supplier AS A LEFT JOIN supplier_alamat AS B ON A.id_sup=B.id_sup WHERE A.id_sup=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			/*
			$dele	= $conn->prepare("DELETE FROM supplier_alamat WHERE id_sup=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			*/
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Suppilier', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
	}
	$conn	= $base->close();
	}
?>