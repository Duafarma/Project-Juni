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
				$id_mg = $secu->injection($_POST['id_mg']);
				$kode	= $data->bcode($koka, 'kode_out', 'outlet');
				$kode_rs	= $secu->injection($_POST['kode_rs']);
				$namao	= $secu->injection($_POST['namaoutlet']);
				$namar	= $secu->injection($_POST['namaresmi']);
				$npwp	= $secu->injection($_POST['npwp']);
				$limit	= $secu->injection($_POST['limit']);
				$ofcode	= $secu->injection($_POST['ofcode']);

					// Auto-fill ofcode_out from logged-in admin's kode_sales when available
					$admin_kode_sales = $data->myadmin($admin, 'kode_sales');
					if (!empty($admin_kode_sales)) {
						$ofcode = $admin_kode_sales;
					}
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
				$syarat	= $secu->injection($_POST['syarat']);
				$altor	= $secu->injection($_POST['alamatkantor']);
				$alkir	= $secu->injection($_POST['alamatkirim']);
				$altuk	= $secu->injection($_POST['alamattukar']);
				$picp	= $secu->injection($_POST['picp']);
				$picpk	= $secu->injection($_POST['picpk']);
				$picf	= $secu->injection($_POST['picf']);
				$picfk	= $secu->injection($_POST['picfk']);
				$npwpa	= $secu->injection($_POST['npwpa']);
				$status_faktur	= 'A';
				$profit	= '0';
				$status	= 'Inactive';
				$status_pembayaran = 'Hijau';
				$status_urgent = 'tidak';
				$platform   = '0';
				$status_manual   = 'Non Manual';


				
				// Diskon & Kondisi
				// Ambil diskon Cendo sebagai default
				$diskon_cendo = 0; // default 0 jika tidak ada
				if(isset($_POST['diskon_principle']) && is_array($_POST['diskon_principle'])){
					// Cari ID principle Cendo (MP0000000001)
					$cendo_query = $conn->prepare("SELECT id_mp FROM master_principle WHERE nama_principle = 'Cendo' LIMIT 1");
					$cendo_query->execute();
					$cendo_data = $cendo_query->fetch(PDO::FETCH_ASSOC);
					
					if($cendo_data && isset($_POST['diskon_principle'][$cendo_data['id_mp']])){
						$diskon_cendo = $secu->injection($_POST['diskon_principle'][$cendo_data['id_mp']]);
						if(empty($diskon_cendo) || !is_numeric($diskon_cendo)){
							$diskon_cendo = 0;
						}
					}
				}
				$diskon = $diskon_cendo; // Set diskon default ke nilai Cendo
				// Save Outlet
				$save	= $conn->prepare("INSERT INTO outlet VALUES(:code, :kate, :kode, :kode_rs, :namao, :namar, :npwp, :id, :ofcode, :kete, :id_mg, :status_faktur,:profit, :status,:status_pembayaran,:status_urgent, :platform, :limit, :status_manual, :catat, :admin, :catat, :admin)");
				$save->bindParam(":id", $id, PDO::PARAM_STR);
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":kate", $kate, PDO::PARAM_STR);
				$save->bindParam(":kode", $kode, PDO::PARAM_STR);
				$save->bindParam(":kode_rs", $kode_rs, PDO::PARAM_STR);
				$save->bindParam(":namao", $namao, PDO::PARAM_STR);
				$save->bindParam(":namar", $namar, PDO::PARAM_STR);
				$save->bindParam(":npwp", $npwp, PDO::PARAM_STR);
				$save->bindParam(":legal", $legal, PDO::PARAM_STR);
				$save->bindParam(":ofcode", $ofcode, PDO::PARAM_STR);
				$save->bindParam(":kete", $kete, PDO::PARAM_STR);
				$save->bindParam(":id_mg", $id_mg, PDO::PARAM_STR);
				$save->bindParam(":status_faktur", $status_faktur, PDO::PARAM_STR);
				$save->bindParam(":profit", $profit, PDO::PARAM_STR);
				$save->bindParam(":status", $status, PDO::PARAM_STR);
				$save->bindParam(":status_pembayaran", $status_pembayaran, PDO::PARAM_STR);
				$save->bindParam(":status_urgent", $status_urgent, PDO::PARAM_STR);
				$save->bindParam(":platform", $platform, PDO::PARAM_STR);
				$save->bindParam(":limit", $limit, PDO::PARAM_STR);
				$save->bindParam(":status_manual", $status_manual, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				// Save Alamat
				$save	= $conn->prepare("INSERT INTO outlet_alamat VALUES(:id, :code, :telp, :hape, :fax, :email, :web, :prov, :kab, :kopos, :picp, :picpk, :picf, :picfk, :jadwal, :syarat, :altor, :alkir, :altuk, :npwpa, :catat, :admin, :catat, :admin)");
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
				$save->bindParam(":syarat", $syarat, PDO::PARAM_STR);
				$save->bindParam(":altor", $altor, PDO::PARAM_STR);
				$save->bindParam(":alkir", $alkir, PDO::PARAM_STR);
				$save->bindParam(":altuk", $altuk, PDO::PARAM_STR);
				$save->bindParam(":npwpa", $npwpa, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				// Save Diskon Produk berdasarkan Principle
				if(isset($_POST['diskon_principle']) && is_array($_POST['diskon_principle'])){
					// Loop setiap principle
					foreach($_POST['diskon_principle'] as $id_mp => $diskon_principle){
						$id_mp = $secu->injection($id_mp);
						$diskon_principle = $secu->injection($diskon_principle);
						
						// Gunakan nilai inputan form apa adanya, set 0 jika kosong
						if(empty($diskon_principle) || !is_numeric($diskon_principle)){
							$diskon_principle = 0;
						}
						
						// Insert produk_diskon untuk produk dengan principle tertentu
						$save = $conn->prepare("INSERT INTO produk_diskon(id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by) 
												SELECT :code, id_pro, :diskon_principle, :catat, :admin, :catat, :admin 
												FROM produk WHERE nama_p = :id_mp");
						$save->bindParam(":code", $code, PDO::PARAM_STR);
						$save->bindParam(":diskon_principle", $diskon_principle, PDO::PARAM_STR);
						$save->bindParam(":id_mp", $id_mp, PDO::PARAM_STR);
						$save->bindParam(":catat", $catat, PDO::PARAM_STR);
						$save->bindParam(":admin", $admin, PDO::PARAM_STR);
						$save->execute();
					}
					
					// Insert diskon Cendo untuk produk yang tidak memiliki principle atau principle tidak diset
					$principle_list = implode("','", array_keys($_POST['diskon_principle']));
					$save = $conn->prepare("INSERT INTO produk_diskon(id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by) 
											SELECT :code, id_pro, :diskon, :catat, :admin, :catat, :admin 
											FROM produk WHERE nama_p NOT IN ('$principle_list') OR nama_p IS NULL OR nama_p = ''");
					$save->bindParam(":code", $code, PDO::PARAM_STR);
					$save->bindParam(":diskon", $diskon, PDO::PARAM_STR);
					$save->bindParam(":catat", $catat, PDO::PARAM_STR);
					$save->bindParam(":admin", $admin, PDO::PARAM_STR);
					$save->execute();
				} else {
					// Jika tidak ada diskon principle, gunakan diskon default untuk semua produk
					$save	= $conn->prepare("INSERT INTO produk_diskon(id_out, id_pro, persen_pds, created_at, created_by, updated_at, updated_by) SELECT :code, id_pro, :diskon, :catat, :admin, :catat, :admin FROM produk");
					$save->bindParam(":code", $code, PDO::PARAM_STR);
					$save->bindParam(":diskon", $diskon, PDO::PARAM_STR);
					$save->bindParam(":catat", $catat, PDO::PARAM_STR);
					$save->bindParam(":admin", $admin, PDO::PARAM_STR);
					$save->execute();
				}
				// Save Legal
				$no		= 0;
				$jumlah	= count(@$_POST['legal']);
				while($no<$jumlah){
					$legal	= $secu->injection(@$_POST['legal'][$no]);
					$klegal	= $secu->injection(@$_POST['ketlegal'][$no]);
					$tlegal	= $secu->injection(@$_POST['tgllegal'][$no]);
					$dokumen = '';
					// Save
					$save	= $conn->prepare("INSERT INTO outlet_legal VALUES(:id, :code, :legal, :klegal, :tlegal, :dokumen, :catat, :admin, :catat, :admin)");
					$save->bindParam(":id", $id, PDO::PARAM_STR);
					$save->bindParam(":code", $code, PDO::PARAM_STR);
					$save->bindParam(":legal", $legal, PDO::PARAM_STR);
					$save->bindParam(":klegal", $klegal, PDO::PARAM_STR);
					$save->bindParam(":tlegal", $tlegal, PDO::PARAM_STR);
					$save->bindParam(":dokumen", $dokumen, PDO::PARAM_STR);
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
				
				// harga produk
			$save	= $conn->prepare("INSERT INTO produk_harga_detail(id_out, id_pro, harga_phg,hargap_phg, created_at, created_by, updated_at, updated_by) SELECT :code, id_pro, harga_phg,hargap_phg, :catat, :admin, :catat, :admin FROM produk_harga WHERE status_phg='Active'");
			$save->bindParam(":id", $id, PDO::PARAM_STR);
			$save->bindParam(":code", $code, PDO::PARAM_STR);
			$save->bindParam(":catat", $catat, PDO::PARAM_STR);
			$save->bindParam(":admin", $admin, PDO::PARAM_STR);
			$save->execute();

			// harga produk
			$jumlah	= count(@$_POST['product']);
			$no		= 0;
			while($no<=$jumlah){
				$product= $secu->injection(@$_POST['product'][$no]);
				$harpoout	= $secu->injection(@$_POST['harpoout'][$no]);
				$harpooutp	= $secu->injection(@$_POST['harpooutp'][$no]);

				// Save
				$save	= $conn->prepare("UPDATE produk_harga_detail SET harga_phg=:harpoout, hargap_phg=:harpooutp, updated_at=:catat, updated_by=:admin WHERE id_out=:code AND id_pro=:product");
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":product", $product, PDO::PARAM_STR);
				$save->bindParam(":harpoout", $harpoout, PDO::PARAM_STR);
				$save->bindParam(":harpooutp", $harpooutp, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
			$no++;
			}
				
				// Save Diskon & Kondisi outlet_diskon
				$parameter = 1; // parameter default
				$diskon1 = $diskon; // diskon default untuk pembelian kecil
				$diskon2 = $diskon; // diskon default untuk pembelian besar
				$save	= $conn->prepare("INSERT INTO outlet_diskon(id_out, top_odi, parameter_odi, diskon1_odi, diskon2_odi, diskon_odi, created_at, created_by, updated_at, updated_by) VALUES(:code, :top_odi, :parameter, :diskon1, :diskon2, :diskon, :catat, :admin, :catat, :admin)");
				$save->bindParam(":code", $code, PDO::PARAM_STR);
				$save->bindParam(":top_odi", $limit, PDO::PARAM_STR);
				$save->bindParam(":parameter", $parameter, PDO::PARAM_STR);
				$save->bindParam(":diskon1", $diskon1, PDO::PARAM_STR);
				$save->bindParam(":diskon2", $diskon2, PDO::PARAM_STR);
				$save->bindParam(":diskon", $diskon, PDO::PARAM_STR);
				$save->bindParam(":catat", $catat, PDO::PARAM_STR);
				$save->bindParam(":admin", $admin, PDO::PARAM_STR);
				$save->execute();
				// Save Program associations (if any)
				if(isset($_POST['program_ids']) && is_array($_POST['program_ids'])){
					foreach($_POST['program_ids'] as $idpp){
						$idpp = $secu->injection($idpp);
						$savep = $conn->prepare("INSERT INTO master_program_produk_outlet (id_pp, id_out, created_at, created_by) VALUES(:idpp, :code, :catat, :admin)");
						$savep->bindParam(":idpp", $idpp, PDO::PARAM_INT);
						$savep->bindParam(":code", $code, PDO::PARAM_STR);
						$savep->bindParam(":catat", $catat, PDO::PARAM_STR);
						$savep->bindParam(":admin", $admin, PDO::PARAM_STR);
						$savep->execute();
					}
				}
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Create', '', '$catat', '$admin')");
				$hasil	= ($save==true) ? "success" : "error";
				echo($hasil);
				break;
			case "update":
				// Basic
				$code	= $secu->injection($_POST['keycode']);
				$kate	= $secu->injection($_POST['kategori']);
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
				$syarat	= $secu->injection($_POST['syarat']);
				$altor	= $secu->injection($_POST['alamatkantor']);
				$alkir	= $secu->injection($_POST['alamatkirim']);
				$altuk	= $secu->injection($_POST['alamattukar']);
				$picp	= $secu->injection($_POST['picp']);
				$picpk	= $secu->injection($_POST['picpk']);
				$picf	= $secu->injection($_POST['picf']);
				$picfk	= $secu->injection($_POST['picfk']);
				$npwpa	= $secu->injection($_POST['npwpa']);
				
				// Diskon & Kondisi
				// Ambil diskon Cendo sebagai default
				$diskon_cendo = 0; // default 0 jika tidak ada
				if(isset($_POST['diskon_principle']) && is_array($_POST['diskon_principle'])){
					// Cari ID principle Cendo (MP0000000001)
					$cendo_query = $conn->prepare("SELECT id_mp FROM master_principle WHERE nama_principle = 'Cendo' LIMIT 1");
					$cendo_query->execute();
					$cendo_data = $cendo_query->fetch(PDO::FETCH_ASSOC);
					
					if($cendo_data && isset($_POST['diskon_principle'][$cendo_data['id_mp']])){
						$diskon_cendo = $secu->injection($_POST['diskon_principle'][$cendo_data['id_mp']]);
						if(empty($diskon_cendo) || !is_numeric($diskon_cendo)){
							$diskon_cendo = 0;
						}
					}
				}
				$diskon = $diskon_cendo; // Set diskon default ke nilai Cendo
				
				// Update Outlet
				$edit	= $conn->prepare("UPDATE outlet SET id_kot=:kate, nama_out=:namao, resmi_out=:namar, npwp_out=:npwp, ofcode_out=:ofcode, ket_out=:kete, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->bindParam(":kate", $kate, PDO::PARAM_STR);
				$edit->bindParam(":namao", $namao, PDO::PARAM_STR);
				$edit->bindParam(":namar", $namar, PDO::PARAM_STR);
				$edit->bindParam(":npwp", $npwp, PDO::PARAM_STR);
				$edit->bindParam(":ofcode", $ofcode, PDO::PARAM_STR);
				$edit->bindParam(":kete", $kete, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				
				// Update Alamat
				$edit	= $conn->prepare("UPDATE outlet_alamat SET telp_ola=:telp, hp_ola=:hape, fax_ola=:fax, email_ola=:email, web_ola=:web, id_rpo=:prov, id_rkb=:kab, kopos_ola=:kopos, picp_ola=:picp, picpk_ola=:picpk, picf_ola=:picf, picfk_ola=:picfk, jatuk_ola=:jadwal, syatuk_ola=:syarat, kantor_ola=:altor, pengiriman_ola=:alkir, atuk_ola=:altuk, npwp_ola=:npwpa, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
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
				$edit->bindParam(":syarat", $syarat, PDO::PARAM_STR);
				$edit->bindParam(":altor", $altor, PDO::PARAM_STR);
				$edit->bindParam(":alkir", $alkir, PDO::PARAM_STR);
				$edit->bindParam(":altuk", $altuk, PDO::PARAM_STR);
				$edit->bindParam(":npwpa", $npwpa, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				
				// Update Diskon & Kondisi outlet_diskon
				$parameter = 1; // parameter default
				$diskon1 = $diskon; // diskon default untuk pembelian kecil
				$diskon2 = $diskon; // diskon default untuk pembelian besar
				$edit	= $conn->prepare("UPDATE outlet_diskon SET top_odi=:top_odi, parameter_odi=:parameter, diskon1_odi=:diskon1, diskon2_odi=:diskon2, diskon_odi=:diskon, updated_at=:catat, updated_by=:admin WHERE id_out=:code");
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->bindParam(":top_odi", $limit, PDO::PARAM_STR);
				$edit->bindParam(":parameter", $parameter, PDO::PARAM_STR);
				$edit->bindParam(":diskon1", $diskon1, PDO::PARAM_STR);
				$edit->bindParam(":diskon2", $diskon2, PDO::PARAM_STR);
				$edit->bindParam(":diskon", $diskon, PDO::PARAM_STR);
				$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
				$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
				$edit->execute();
				
				// Update Diskon Produk berdasarkan Principle
				if(isset($_POST['diskon_principle']) && is_array($_POST['diskon_principle'])){
					// Loop setiap principle
					foreach($_POST['diskon_principle'] as $id_mp => $diskon_principle){
						$id_mp = $secu->injection($id_mp);
						$diskon_principle = $secu->injection($diskon_principle);
						
						// Gunakan nilai inputan form apa adanya, set 0 jika kosong
						if(empty($diskon_principle) || !is_numeric($diskon_principle)){
							$diskon_principle = 0;
						}
						
						// Update produk_diskon untuk produk dengan principle tertentu
						$edit = $conn->prepare("UPDATE produk_diskon SET persen_pds = :diskon_principle, updated_at = :catat, updated_by = :admin 
												WHERE id_out = :code AND id_pro IN (SELECT id_pro FROM produk WHERE nama_p = :id_mp)");
						$edit->bindParam(":code", $code, PDO::PARAM_STR);
						$edit->bindParam(":diskon_principle", $diskon_principle, PDO::PARAM_STR);
						$edit->bindParam(":id_mp", $id_mp, PDO::PARAM_STR);
						$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
						$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
						$edit->execute();
					}
					
					// Update diskon Cendo untuk produk yang tidak memiliki principle atau principle tidak diset
					$principle_list = implode("','", array_keys($_POST['diskon_principle']));
					$edit = $conn->prepare("UPDATE produk_diskon SET persen_pds = :diskon, updated_at = :catat, updated_by = :admin 
											WHERE id_out = :code AND id_pro IN (SELECT id_pro FROM produk WHERE nama_p NOT IN ('$principle_list') OR nama_p IS NULL OR nama_p = '')");
					$edit->bindParam(":code", $code, PDO::PARAM_STR);
					$edit->bindParam(":diskon", $diskon, PDO::PARAM_STR);
					$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
					$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
					$edit->execute();
				} else {
					// Jika tidak ada diskon principle, gunakan diskon default untuk semua produk
					$edit = $conn->prepare("UPDATE produk_diskon SET persen_pds = :diskon, updated_at = :catat, updated_by = :admin WHERE id_out = :code");
					$edit->bindParam(":code", $code, PDO::PARAM_STR);
					$edit->bindParam(":diskon", $diskon, PDO::PARAM_STR);
					$edit->bindParam(":catat", $catat, PDO::PARAM_STR);
					$edit->bindParam(":admin", $admin, PDO::PARAM_STR);
					$edit->execute();
				}
				
				// Update Program associations (replace existing)
				$delp = $conn->prepare("DELETE FROM master_program_produk_outlet WHERE id_out=:code");
				$delp->bindParam(":code", $code, PDO::PARAM_STR);
				$delp->execute();
				if(isset($_POST['program_ids']) && is_array($_POST['program_ids'])){
					foreach($_POST['program_ids'] as $idpp){
						$idpp = $secu->injection($idpp);
						$savep = $conn->prepare("INSERT INTO master_program_produk_outlet (id_pp, id_out, created_at, created_by) VALUES(:idpp, :code, :catat, :admin)");
						$savep->bindParam(":idpp", $idpp, PDO::PARAM_INT);
						$savep->bindParam(":code", $code, PDO::PARAM_STR);
						$savep->bindParam(":catat", $catat, PDO::PARAM_STR);
						$savep->bindParam(":admin", $admin, PDO::PARAM_STR);
						$savep->execute();
					}
				}
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Update', '', '$catat', '$admin')");
				$hasil	= ($edit==true) ? "success" : "error";
				echo($hasil);
				break;
			case "updateStatusRevised":
				$code	= $secu->injection($_POST['keycode']);
				$edit	= $conn->prepare("UPDATE outlet SET status_out='Revised' WHERE id_out=:code");
				$edit->bindParam(":code", $code, PDO::PARAM_STR);
				$edit->execute();
				//RIWAYAT
				$riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$code', 'Outlet', 'Update', '', '$catat', '$admin')");
				$hasil	= ($edit==true) ? "success" : "error";
				echo($hasil);
				break;
		}
	}
	$conn	= $base->close();
?>