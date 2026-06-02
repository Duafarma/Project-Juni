<?php
	error_reporting(0);
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
			$code	= 'OUT'.time();
			$kate	= $secu->injection($_POST['kategori']);
			$koka	= $data->koutlet($kate, 'kode_kot');
			$kode	= $data->bcode($koka, 'kode_out', 'outlet');
			$namao	= $secu->injection($_POST['namaoutlet']);
			$namar	= $secu->injection($_POST['namaresmi']);
			$npwp	= $secu->injection($_POST['npwp']);
			$limit	= $secu->injection($_POST['limit']);
			$ofcode	= $secu->injection($_POST['ofcode']);
			$kete	= $secu->injection($_POST['kete']);
			// Alamat
			$telp	= $secu->injection($_POST['telp']);
			$hape	= $secu->injection($_POST['hape']);
			$fax	= $secu->injection($_POST['fax']);
			$email	= $secu->injection($_POST['email']);
			$web	= $secu->injection($_POST['website']);
			$prov	= $secu->injection($_POST['provinsi']);
			$kab	= $secu->injection($_POST['kabupaten']);
			$kopos	= $secu->injection($_POST['kopos']);
			$jadwal	= $secu->injection($_POST['jadwal']);
			$altor	= $secu->injection($_POST['alamatkantor']);
			$alkir	= $secu->injection($_POST['alamatkirim']);
			$altuk	= $secu->injection($_POST['alamattukar']);
			$picp	= $secu->injection($_POST['picp']);
			$picpk	= $secu->injection($_POST['picpk']);
			$picf	= $secu->injection($_POST['picf']);
			$picfk	= $secu->injection($_POST['picfk']);
			$status	= 'Active';
			// Diskon & Kondisi
			$diskon	= $secu->injection($_POST['diskon']);
			// Save Outlet
			$save	= $conn->prepare("INSERT INTO outlet VALUES(:code, :kate, :kode, :namao, :namar, :npwp, :id, :ofcode, :kete, :status, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":kate", $kate, PDO::PARAM_STR);
			$save->bindParam(":kode", $kode, PDO::PARAM_STR);
			$save->bindParam(":namao", $namao, PDO::PARAM_STR);
			$save->bindParam(":namar", $namar, PDO::PARAM_STR);
			$save->bindParam(":npwp", $npwp, PDO::PARAM_STR);
			$save->bindParam(":legal", $legal, PDO::PARAM_STR);
			$save->bindParam(":ofcode", $ofcode, PDO::PARAM_STR);
			$save->bindParam(":kete", $kete, PDO::PARAM_STR);
			$save->bindParam(":status", $status, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			// Save Alamat
			$save	= $conn->prepare("INSERT INTO outlet_alamat VALUES(:id, :code, :telp, :hape, :fax, :email, :web, :prov, :kab, :kopos, :picp, :picpk, :picf, :picfk, :jadwal, :altor, :alkir, :altuk, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":telp", $telp, PDO::PARAM_STR);
			$save->bindParam(":hape", $hape, PDO::PARAM_STR);
			$save->bindParam(":fax", $fax, PDO::PARAM_STR);
			$save->bindParam(":email", $email, PDO::PARAM_STR);
			$save->bindParam(":web", $web, PDO::PARAM_STR);
			$save->bindParam(":prov", $prov, PDO::PARAM_STR);
			$save->bindParam(":kab", $kab, PDO::PARAM_STR);
			$save->bindParam(":kopos", $kopos, PDO::PARAM_STR);
			$save->bindParam(":picp", $picp, PDO::PARAM_STR);
			$save->bindParam(":picpk", $picpk, PDO::PARAM_STR);
			$save->bindParam(":picf", $picf, PDO::PARAM_STR);
			$save->bindParam(":picfk", $picfk, PDO::PARAM_STR);
			$save->bindParam(":jadwal", $jadwal, PDO::PARAM_STR);
			$save->bindParam(":altor", $altor, PDO::PARAM_STR);
			$save->bindParam(":alkir", $alkir, PDO::PARAM_STR);
			$save->bindParam(":altuk", $altuk, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			// Save Diskon Produk
			$save	= $conn->prepare("INSERT INTO produk_diskon(id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by) SELECT :code, id_pro, :diskon, :catat, :admin, :catat, :admin FROM produk");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":diskon", $diskon, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			// Save Legal
			$no		= 0;
			$jumlah	= count(@$_POST['legal']);
			while($no<$jumlah){
				$legal	= $secu->injection(@$_POST['legal'][$no]);
				$klegal	= $secu->injection(@$_POST['ketlegal'][$no]);
				$tlegal	= $secu->injection(@$_POST['tgllegal'][$no]);
				// Save
				$save	= $conn->prepare("INSERT INTO outlet_legal VALUES(:id, :code, :legal, :klegal, :tlegal, :catat, :admin, :catat, :admin)");
				$save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":legal", $legal, PDO::PARAM_STR);
				$save->bindParam(":klegal", $klegal, PDO::PARAM_STR);
				$save->bindParam(":tlegal", $tlegal, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
			$no++;
			}
			// Update Kondisi Diskon
			$no		= 0;
			$jumlah	= count(@$_POST['product']);
			while($no<$jumlah){
				$product= $secu->injection(@$_POST['product'][$no]);
				$dispro	= $secu->injection(@$_POST['dispro'][$no]);
				// Save
				$edit	= $conn->prepare("UPDATE produk_diskon SET persen_pds=:dispro, updated_at=:catat, updated_by=:admin WHERE id_out=:code AND id_pro=:product");
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->bindParam(":product", $product, PDO::PARAM_STR);
				$edit->bindParam(":dispro", $dispro, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
			$no++;
			}
			// Save Diskon & Kondisi
			$save	= $conn->prepare("INSERT INTO outlet_diskon(id_out, top_odi, diskon_odi, created_at, created_by, updated_at, updated_by) VALUES(:code, :limit, :diskon, :catat, :admin, :catat, :admin)");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":limit", $limit, PDO::PARAM_STR);
			$save->bindParam(":diskon", $diskon, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Create', '', '$catat', '$admin')");
			$hasil	= ($save==true) ? "success" : "error";
			echo($hasil);
		break;
	case "updatehpim":
			// Sanitize the input keycode
			$code = $secu->injection($_POST['keycode']);
			// Delete existing prices for this outlet
			$remove = $conn->prepare("DELETE FROM produk_hargapim WHERE id_out=:code");
			$remove->bindParam(":code", $code, PDO::PARAM_STR);
			$remove->execute();
		
			// Check if the 'product' data exists
			if (isset($_POST['product']) && !empty($_POST['product'])) {
				// Prepare to insert new prices (after deleting old data)
				$save = $conn->prepare("INSERT INTO produk_hargapim (id_out, id_pro, harga_a, harga_b, harga_c, created_at, created_by, updated_at, updated_by)
					SELECT :code, id_pro, :harga_a, :harga_b, :harga_c, :catat, :admin, :catat, :admin FROM produk_harga WHERE status_phg='Active'");
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":harga_a", $harga_a, PDO::PARAM_STR);
				$save->bindParam(":harga_b", $harga_b, PDO::PARAM_STR);
				$save->bindParam(":harga_c", $harga_c, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
		
		
				// Loop through each product and update its price
				$jumlah = count($_POST['product']);
				for ($no = 0; $no <= $jumlah; $no++) {
					// Sanitize product data
					$product = $secu->injection($_POST['product'][$no]);
					$harga_a = $secu->injection($_POST['harga_a'][$no]);
					$harga_b = $secu->injection($_POST['harga_b'][$no]);
					$harga_c = $secu->injection($_POST['harga_c'][$no]);
		
					// Prepare to update product prices for this outlet
					$update = $conn->prepare("UPDATE produk_hargapim
						SET harga_a=:harga_a, harga_b=:harga_b, harga_c=:harga_c, updated_at=:catat, updated_by=:admin
						WHERE id_out=:code AND id_pro=:product");
					$update->bindParam(":code", $code, PDO::PARAM_STR);
					$update->bindParam(":product", $product, PDO::PARAM_STR);
					$update->bindParam(":harga_a", $harga_a, PDO::PARAM_STR);
					$update->bindParam(":harga_b", $harga_b, PDO::PARAM_STR);
					$update->bindParam(":harga_c", $harga_c, PDO::PARAM_STR);
					$update->bindParam(":catat", $catat, PDO::PARAM_STR);
					$update->bindParam(":admin", $admin, PDO::PARAM_STR);
					$update->execute();
				}
		
				// Record the history of the update
				$conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet Harga PIM', 'Update', '', '$catat', '$admin')");
		
				// Provide feedback on success
				echo "success";
			} else {
				// If no products were sent in the request
				echo "No products specified.";
			}
			break;
			
			case "updateitem":
				$code = $secu->injection($_POST['keycode']);
			
				// Hapus diskon lama
				$remove = $conn->prepare("DELETE FROM produk_diskon WHERE id_out = :code");
				$remove->bindParam(":code", $code, PDO::PARAM_STR);
				$remove->execute();
			
				// Simpan diskon baru jika ada
				if (isset($_POST['product']) && !empty($_POST['product'])) {
					$jumlah = count($_POST['product']);
			
					for ($no = 0; $no < $jumlah; $no++) {
						$product     = $secu->injection($_POST['product'][$no]);
						$persen_pds  = $secu->injection($_POST['persen_pds'][$no]);
			
						if (!empty($product)) {
							$save = $conn->prepare("INSERT INTO produk_diskon (id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by)
													VALUES (:code, :product, :persen_pds, :catat, :admin, :catat, :admin)");
							$save->bindParam(":code", $code, PDO::PARAM_STR);
							$save->bindParam(":product", $product, PDO::PARAM_STR);
							$save->bindParam(":persen_pds", $persen_pds, PDO::PARAM_STR);
							$save->bindParam(":catat", $catat, PDO::PARAM_STR);
							$save->bindParam(":admin", $admin, PDO::PARAM_STR);
							$save->execute();
						}
					}
			
					// Catat riwayat
					$conn->query("INSERT INTO riwayat VALUES('', '$code', 'Update Harga Item', 'Update', '', '$catat', '$admin')");
			
					echo "success";
				} else {
					echo "No products specified.";
				}
				break;
			
		case "update":
			// Basic
			$code	= $secu->injection($_POST['keycode']);
			$kate	= $secu->injection($_POST['kategori']);
			//$koka	= $data->koutlet($kate, 'kode_kot');
			//$kode	= $data->bcode($koka, 'kode_out', 'outlet');
			$namao	= $secu->injection($_POST['namaoutlet']);
			$namar	= $secu->injection($_POST['namaresmi']);
			$npwp	= $secu->injection($_POST['npwp']);
			$limit	= $secu->injection($_POST['limit']);
			$legal	= implode("_", $_POST['legal']);
			$ofcode	= $secu->injection($_POST['ofcode']);
			$kete	= $secu->injection($_POST['kete']);
			$kode_rs = $secu->injection($_POST['kode_rs']);
			// Alamat
			$telp	= $secu->injection($_POST['telp']);
			$hape	= $secu->injection($_POST['hape']);
			$fax	= $secu->injection($_POST['fax']);
			$email	= $secu->injection($_POST['email']);
			$web	= $secu->injection($_POST['website']);
			$prov	= $secu->injection($_POST['provinsi']);
			$kab	= $secu->injection($_POST['kabupaten']);
			$kopos	= $secu->injection($_POST['kopos']);
			$jadwal	= $secu->injection($_POST['jadwal']);
			$altor	= $secu->injection($_POST['alamatkantor']);
			$alkir	= $secu->injection($_POST['alamatkirim']);
			$altuk	= $secu->injection($_POST['alamattukar']);
			$picp	= $secu->injection($_POST['picp']);
			$picpk	= $secu->injection($_POST['picpk']);
			$picf	= $secu->injection($_POST['picf']);
			$picfk	= $secu->injection($_POST['picfk']);
			$status	= 'Active';
			// Diskon & Kondisi
			$diskon	= $secu->injection($_POST['diskon']);
			// Update Outlet
			$edit	= $conn->prepare("UPDATE outlet SET nama_out=:namao, resmi_out=:namar, npwp_out=:npwp, legal_out=:legal, ofcode_out=:ofcode, kode_rs=:kode_rs, ket_out=:kete, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":namao", $namao, PDO::PARAM_STR);
			$edit->bindParam(":namar", $namar, PDO::PARAM_STR);
			$edit->bindParam(":npwp", $npwp, PDO::PARAM_STR);
			$edit->bindParam(":legal", $legal, PDO::PARAM_STR);
			$edit->bindParam(":ofcode", $ofcode, PDO::PARAM_STR);
			$edit->bindParam(":kete", $kete, PDO::PARAM_STR);
			$edit->bindParam(":kode_rs", $kode_rs, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			// Update Alamat
			$edit	= $conn->prepare("UPDATE outlet_alamat SET telp_ola=:telp, hp_ola=:hape, fax_ola=:fax, email_ola=:email, web_ola=:web, id_rpo=:prov, id_rkb=:kab, kopos_ola=:kopos, picp_ola=:picp, picpk_ola=:picpk, picf_ola=:picf, picfk_ola=:picfk, jatuk_ola=:jadwal, kantor_ola=:altor, pengiriman_ola=:alkir, atuk_ola=:altuk, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":telp", $telp, PDO::PARAM_STR);
			$edit->bindParam(":hape", $hape, PDO::PARAM_STR);
			$edit->bindParam(":fax", $fax, PDO::PARAM_STR);
			$edit->bindParam(":email", $email, PDO::PARAM_STR);
			$edit->bindParam(":web", $web, PDO::PARAM_STR);
			$edit->bindParam(":prov", $prov, PDO::PARAM_STR);
			$edit->bindParam(":kab", $kab, PDO::PARAM_STR);
			$edit->bindParam(":kopos", $kopos, PDO::PARAM_STR);
			$edit->bindParam(":picp", $picp, PDO::PARAM_STR);
			$edit->bindParam(":picpk", $picpk, PDO::PARAM_STR);
			$edit->bindParam(":picf", $picf, PDO::PARAM_STR);
			$edit->bindParam(":picfk", $picfk, PDO::PARAM_STR);
			$edit->bindParam(":jadwal", $jadwal, PDO::PARAM_STR);
			$edit->bindParam(":altor", $altor, PDO::PARAM_STR);
			$edit->bindParam(":alkir", $alkir, PDO::PARAM_STR);
			$edit->bindParam(":altuk", $altuk, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			// Hapus Legal
			$remove	= $conn->prepare("DELETE FROM outlet_legal WHERE id_out=:code");
			$remove->bindParam(":code", $code, PDO::PARAM_STR);
			$remove->execute();
			
// // 			// Hapus harga produk
// 			$remove	= $conn->prepare("DELETE FROM produk_harga_detail WHERE id_out=:code");
// 			$remove->bindParam(":code", $code, PDO::PARAM_STR);
// 			$remove->execute();
			
// 			Hapus Diskon
			$remove	= $conn->prepare("DELETE FROM produk_diskon WHERE id_out=:code");
			$remove->bindParam(":code", $code, PDO::PARAM_STR);
			$remove->execute();
			// Update Legal
			$no		= 0;
			$jumlah	= count(@$_POST['legal']);
			while($no<$jumlah){
				$legal	= $secu->injection(@$_POST['legal'][$no]);
				$klegal	= $secu->injection(@$_POST['ketlegal'][$no]);
				$tlegal	= $secu->injection(@$_POST['tgllegal'][$no]);
				// Save
				$save	= $conn->prepare("INSERT INTO outlet_legal VALUES(:id, :code, :legal, :klegal, :tlegal, :catat, :admin, :catat, :admin)");
				$save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":legal", $legal, PDO::PARAM_STR);
				$save->bindParam(":klegal", $klegal, PDO::PARAM_STR);
				$save->bindParam(":tlegal", $tlegal, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
			$no++;
			}
		
			$save	= $conn->prepare("INSERT INTO produk_diskon(id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by) SELECT :code, id_pro, :diskon, :catat, :admin, :catat, :admin FROM produk");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":diskon", $diskon, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();
			// Update Kondisi Diskon
			$jumlah	= count(@$_POST['product']);
			$no		= 0;
			while($no<=$jumlah){
				$product= $secu->injection(@$_POST['product'][$no]);
				$dispro	= $secu->injection(@$_POST['dispro'][$no]);
				// Save
				$save	= $conn->prepare("UPDATE produk_diskon SET persen_pds=:dispro, updated_at=:catat, updated_by=:admin WHERE id_out=:code AND id_pro=:product");
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":product", $product, PDO::PARAM_STR);
				$save->bindParam(":dispro", $dispro, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
			$no++;
			}

// // 			// harga produk
// 			$save	= $conn->prepare("INSERT INTO produk_harga_detail(id_out, id_pro, harga_phg,hargap_phg, created_at, created_by, updated_at, updated_by) SELECT :code, id_pro, harga_phg,hargap_phg, :catat, :admin, :catat, :admin FROM produk_harga WHERE status_phg='Active'");
// 			$save->bindParam(":id", $id, PDO::PARAM_STR);
// 			$save->bindParam(":code", $code, PDO::PARAM_STR);
// 			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
// 			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
// 			$save->execute();

// 			// harga produk
// 			$jumlah	= count(@$_POST['product']);
// 			$no		= 0;
// 			while($no<=$jumlah){
// 				$product= $secu->injection(@$_POST['product'][$no]);
// 				$harpoout	= $secu->injection(@$_POST['harpoout'][$no]);
// 				$harpooutp	= $secu->injection(@$_POST['harpooutp'][$no]);

// 				// Save
// 				$save	= $conn->prepare("UPDATE produk_harga_detail SET harga_phg=:harpoout, hargap_phg=:harpooutp, updated_at=:catat, updated_by=:admin WHERE id_out=:code AND id_pro=:product");
// 				$save->bindParam(":code", $code, PDO::PARAM_STR);
// 				$save->bindParam(":product", $product, PDO::PARAM_STR);
// 				$save->bindParam(":harpoout", $harpoout, PDO::PARAM_STR);
// 				$save->bindParam(":harpooutp", $harpooutp, PDO::PARAM_STR);
// 				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
// 				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
// 				$save->execute();
// 			$no++;
// 			}

			// Update Diskon & Kondisi
			$edit	= $conn->prepare("UPDATE outlet_diskon SET top_odi=:limit, diskon_odi=:diskon, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":limit", $limit, PDO::PARAM_STR);
			$edit->bindParam(":diskon", $diskon, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "delete":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE A, B, C, D, E FROM outlet AS A LEFT JOIN outlet_alamat AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON A.id_out=C.id_out LEFT JOIN outlet_legal AS D ON A.id_out=D.id_out LEFT JOIN produk_diskon AS E ON A.id_out=E.id_out WHERE A.id_out=:kode");
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
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Outlet', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
		case "updateStatusApprove":
			$code	= $secu->injection($_POST['keycode']);
			$edit	= $conn->prepare("UPDATE outlet SET status_out='Active' WHERE id_out=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		case "updateStatusReject":
			$kode	= $secu->injection($_POST['keycode']);
			$dele	= $conn->prepare("DELETE A, B, C, D, E FROM outlet AS A LEFT JOIN outlet_alamat AS B ON A.id_out=B.id_out LEFT JOIN outlet_diskon AS C ON A.id_out=C.id_out LEFT JOIN outlet_legal AS D ON A.id_out=D.id_out LEFT JOIN produk_diskon AS E ON A.id_out=E.id_out WHERE A.id_out=:kode");
			$dele->bindParam(":kode", $kode, PDO::PARAM_STR);
			$dele->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Delete', '', '$catat', '$admin')");
			$hasil	= ($dele==true) ? "success" : "error";
			echo($hasil);
		break;
		case "updateStatusRevision":
			$code	= $secu->injection($_POST['keycode']);
			$edit	= $conn->prepare("UPDATE outlet SET status_out='Need Revision' WHERE id_out=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Update', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		
		case "updateStatusApproveSPV":
			$code	= $secu->injection($_POST['keycode']);
			$edit	= $conn->prepare("UPDATE outlet SET status_out='Active', updated_at=:catat, updated_by=:admin WHERE id_out=:code");
			$edit->bindParam(":code", $code, PDO::PARAM_STR);
			$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
			$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
			$edit->execute();
			//RIWAYAT
			$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Approve SPV to Active', '', '$catat', '$admin')");
			$hasil	= ($edit==true) ? "success" : "error";
			echo($hasil);
		break;
		
	case "updateDiskonByPrinciple":
			$code = $secu->injection($_POST['keycode']);
			$principle = $secu->injection($_POST['principle']);
			$diskon = $secu->injection($_POST['diskon']);
			
			// Cek dulu berapa produk yang akan diupdate
			$check = $conn->prepare("SELECT COUNT(*) as total, 
									GROUP_CONCAT(p.nama_pro LIMIT 5) as sample_products
									FROM produk_diskon AS pd 
									INNER JOIN produk AS p ON pd.id_pro = p.id_pro 
									WHERE pd.id_out = :code AND p.nama_p = :principle");
			$check->bindParam(":code", $code, PDO::PARAM_STR);
			$check->bindParam(":principle", $principle, PDO::PARAM_STR);
			$check->execute();
			$count = $check->fetch(PDO::FETCH_ASSOC);
			
			if($count['total'] > 0) {
				// Update diskon untuk produk berdasarkan principle
				$edit = $conn->prepare("UPDATE produk_diskon AS pd 
									   INNER JOIN produk AS p ON pd.id_pro = p.id_pro 
									   SET pd.persen_pds = :diskon, 
									       pd.updated_at = :catat, 
									       pd.updated_by = :admin 
									   WHERE pd.id_out = :code AND p.nama_p = :principle");
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->bindParam(":principle", $principle, PDO::PARAM_STR);
				$edit->bindParam(":diskon", $diskon, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				
				//RIWAYAT
				$riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Update Diskon by Principle', 'Principle: $principle - Diskon: $diskon% - Updated: $count[total] products', '$catat', '$admin')");
				$hasil = "success";
			} else {
				// Debug: cek apakah ada produk dengan principle tersebut
				$debug = $conn->prepare("SELECT COUNT(*) as total_products FROM produk WHERE nama_p = :principle");
				$debug->bindParam(":principle", $principle, PDO::PARAM_STR);
				$debug->execute();
				$debug_result = $debug->fetch(PDO::FETCH_ASSOC);
				
				// Log untuk debugging
				$riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Debug Diskon by Principle', 'Principle: $principle - No products found in outlet, Total products in system: $debug_result[total_products]', '$catat', '$admin')");
				$hasil = "no_data";
			}
			echo($hasil);
		break;
		
case "updatelegal":
				$id		= '';
				$code	= $secu->injection($_POST['keycode']);

				// Fetch existing documents before delete
				$existDok = array();
				$fetchDok = $conn->prepare("SELECT id_klg, dokumen_ole FROM outlet_legal WHERE id_out=:code");
				$fetchDok->bindParam(":code", $code, PDO::PARAM_STR);
				$fetchDok->execute();
				while($rowDok = $fetchDok->fetch(PDO::FETCH_ASSOC)){
					if(!empty($rowDok['dokumen_ole'])){
						$existDok[$rowDok['id_klg']] = $rowDok['dokumen_ole'];
					}
				}

				$remove	= $conn->prepare("DELETE FROM outlet_legal WHERE id_out=:code");
				$remove->bindParam(":code", $code, PDO::PARAM_STR);
				$remove->execute();

				$legalPost = (isset($_POST['legal']) && is_array($_POST['legal'])) ? $_POST['legal'] : array();
				$ketPost = (isset($_POST['ketlegal']) && is_array($_POST['ketlegal'])) ? $_POST['ketlegal'] : array();
				$tglPost = (isset($_POST['tgllegal']) && is_array($_POST['tgllegal'])) ? $_POST['tgllegal'] : array();
				$oldDokPost = (isset($_POST['olddoklegal']) && is_array($_POST['olddoklegal'])) ? $_POST['olddoklegal'] : array();

				// Upload directory for legal documents
				$uploadDir = '../../berkas/legal/';
				if(!is_dir($uploadDir)){
					mkdir($uploadDir, 0755, true);
				}
				$allowedExt = array('pdf','jpg','jpeg','png','gif','doc','docx');

				$legalIds = array();
				foreach($legalPost as $key => $legalValue){
					$legalValue = $secu->injection($legalValue);
					if(empty($legalValue)){
						continue;
					}
					$klegal = isset($ketPost[$key]) ? $secu->injection($ketPost[$key]) : '';
					$tlegal = isset($tglPost[$key]) ? $secu->injection($tglPost[$key]) : '';

					// Handle file upload - keep old doc if no new upload
					$dokumen = isset($oldDokPost[$key]) ? $secu->injection($oldDokPost[$key]) : '';
					if(empty($dokumen) && isset($existDok[$legalValue])){
						$dokumen = $existDok[$legalValue];
					}
					if(isset($_FILES['doklegal']['name'][$key]) && $_FILES['doklegal']['error'][$key] === UPLOAD_ERR_OK){
						$origName = $_FILES['doklegal']['name'][$key];
						$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
						if(in_array($ext, $allowedExt)){
							$fileName = $code.'_'.$key.'_'.time().'.'.$ext;
							$destination = $uploadDir.$fileName;
							if(move_uploaded_file($_FILES['doklegal']['tmp_name'][$key], $destination)){
								$dokumen = $fileName;
							}
						}
					}

					$save	= $conn->prepare("INSERT INTO outlet_legal VALUES(:id, :code, :legal, :klegal, :tlegal, :dokumen, :catat, :admin, :catat, :admin)");
					$save->bindParam(":id", $id, PDO::PARAM_STR);
					$save->bindParam(":code", $code, PDO::PARAM_STR);
					$save->bindParam(":legal", $legalValue, PDO::PARAM_STR);
					$save->bindParam(":klegal", $klegal, PDO::PARAM_STR);
					$save->bindParam(":tlegal", $tlegal, PDO::PARAM_STR);
					$save->bindParam(":dokumen", $dokumen, PDO::PARAM_STR);
					$save->bindParam(":catat", $catat, PDO::PARAM_STR);
					$save->bindParam(":admin", $admin, PDO::PARAM_STR);
					$save->execute();
					$legalIds[] = $legalValue;
				}

				$legal_out = implode("_", $legalIds);
				$edit = $conn->prepare("UPDATE outlet SET legal_out=:legal_out, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->bindParam(":legal_out", $legal_out, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();

				$conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet Legal', 'Update', '', '$catat', '$admin')");
				$hasil	= ($edit==true) ? "success" : "error";
				echo($hasil);
			break;
			
	
		
	case "updateDiskonBulk":
		$keycode = $secu->injection($_POST['keycode']);
		$principle = $secu->injection($_POST['principle']);
		$product_ids = $_POST['product_id'];
		$diskons = $_POST['diskon'];
		
		if (empty($keycode) || empty($product_ids) || empty($diskons)) {
			echo "gagal - data tidak lengkap";
			break;
		}
		
		try {
			$conn->beginTransaction();
			$success_count = 0;
			
			for ($i = 0; $i < count($product_ids); $i++) {
				$product_id = $secu->injection($product_ids[$i]);
				$diskon = $secu->injection($diskons[$i]);
				
				// Validasi diskon (0-100)
				if ($diskon < 0 || $diskon > 100) {
					continue;
				}
				
				// Cek apakah sudah ada di produk_diskon
				$cek = $conn->prepare("SELECT id_pro FROM produk_diskon WHERE id_out = :keycode AND id_pro = :product_id");
				$cek->bindParam(':keycode', $keycode, PDO::PARAM_STR);
				$cek->bindParam(':product_id', $product_id, PDO::PARAM_STR);
				$cek->execute();
				
				if ($cek->rowCount() > 0) {
					// Sudah ada => UPDATE
					$stmt = $conn->prepare("UPDATE produk_diskon SET 
												persen_pds = :diskon,
												updated_at = :catat,
												updated_by = :admin
											WHERE id_out = :keycode 
											AND id_pro = :product_id");
				} else {
					// Belum ada => INSERT
					$stmt = $conn->prepare("INSERT INTO produk_diskon (id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by)
											VALUES (:keycode, :product_id, :diskon, :catat, :admin, :catat, :admin)");
				}
				
				$stmt->bindParam(':diskon', $diskon, PDO::PARAM_STR);
				$stmt->bindParam(':catat', $catat, PDO::PARAM_STR);
				$stmt->bindParam(':admin', $admin, PDO::PARAM_STR);
				$stmt->bindParam(':keycode', $keycode, PDO::PARAM_STR);
				$stmt->bindParam(':product_id', $product_id, PDO::PARAM_STR);
				
				if ($stmt->execute()) {
					$success_count++;
				}
			}
			
			// Log ke riwayat
			$riwayat = $conn->prepare("INSERT INTO riwayat VALUES('', :keycode, 'Outlet', 'Update Bulk Diskon', :ket, :catat, :admin)");
			$ket_riwayat = "Update bulk diskon untuk outlet $keycode, principle $principle - $success_count produk berhasil diupdate";
			$riwayat->bindParam(':keycode', $keycode, PDO::PARAM_STR);
			$riwayat->bindParam(':ket', $ket_riwayat, PDO::PARAM_STR);
			$riwayat->bindParam(':catat', $catat, PDO::PARAM_STR);
			$riwayat->bindParam(':admin', $admin, PDO::PARAM_STR);
			$riwayat->execute();
			
			$conn->commit();
			echo "success";
			
		} catch (Exception $e) {
			$conn->rollBack();
			echo "gagal - " . $e->getMessage();
		}
		break;
	}
	$conn	= $base->close();
	}
?>