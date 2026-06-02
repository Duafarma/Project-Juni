<?php
	// must add request validation
	if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
		http_response_code(405);
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo "Method Not Allowed";
	} else {
		require_once('../config/connection/connection.php');
		require_once('../config/connection/security.php');
		require_once('../config/function/data.php');
		$secu	= new Security;
		$base	= new DB;
		$data	= new Data;
		$tgl	= date('Y-m-d');
		$conn	= $base->open();
		$hasil 	= "Error";
		
		// checking encrypt
		$encrypt= $secu->injection($_GET['encrypt']);
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		if (md5($tgl. "#" . $sourceKey) == $encrypt) {
			// checking params
			$cari	= $secu->injection(@$_GET['key']);
			$pecah	= explode('_', $cari);
			$outlet	= empty($pecah[0]) ? "" : "AND B.id_out='$pecah[0]'";
			$produk	= empty($pecah[1]) ? "" : "AND A.id_pro='$pecah[1]'";
			$tgl1	= empty($pecah[2]) ? "" : "AND B.tgl_tfk>='$pecah[2]'";
			$tgl2	= empty($pecah[3]) ? "" : "AND B.tgl_tfk<='$pecah[3]'";
			// get query
			$status	= 'Active';
			$qMaster = "SELECT
					'Puri' as source,
					A.jumlah_tfd,
					A.harga_tfd,
					A.diskon_tfd,
					A.total_tfd,
					A.created_at,
					B.pajak_tfk,
					B.pajak_tfkt,
					B.kode_tfk,
					B.tgl_tfk,
					B.po_tfk,
					B.tglpo_tfk,
					B.tgl_limit,
					B.status_tfk,
					TIMESTAMPDIFF(DAY, B.tgl_limit, :tanggal) AS jarak,
					C.nama_pro,
					C.kategori_obat,
					C.kode_pro,
					C.kode_produk_jadi,
					D.nama_out,
					D.kode_out,
					D.id_kot,
					D.ofcode_out,
					E.no_bcode,
					E.tgl_expired,
					F.pengiriman_ola,
					H.nama_rkb,
					G.kode_kot,
					I.nama_mg
				FROM
					transaksi_fakturdetail AS A
				LEFT JOIN transaksi_faktur AS B ON
					A.id_tfk = B.id_tfk
				LEFT JOIN produk AS C ON
					A.id_pro = C.id_pro
				LEFT JOIN outlet AS D ON
					B.id_out = D.id_out
				LEFT JOIN produk_stokdetail AS E ON
					A.id_psd = E.id_psd
				LEFT JOIN outlet_alamat AS F ON
					D.id_out = F.id_out
				LEFT JOIN kategori_outlet AS G ON
					D.id_kot = G.id_kot
		    	LEFT JOIN regional_kabupaten AS H ON
				    F.id_rkb=H.id_rkb
				LEFT JOIN master_grup AS I ON 
				    D.id_mg = I.id_mg
				WHERE
					A.id_tfd != '' $outlet $produk $tgl1 $tgl2
				ORDER BY
					B.tgl_tfk DESC,
					B.kode_tfk DESC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':tanggal', $tgl, PDO::PARAM_STR);
			$master->execute();
			if ($master) {
				$hasil= $master->fetchAll(PDO::FETCH_ASSOC);
				http_response_code(200);
			} else {
				$hasil = $save->error;
				http_response_code(500);
			}
		} else {
			$hasil = "Unauthorized";
			http_response_code(401);
		}
		$conn	= $base->close();
		header('Access-Control-Allow-Origin: *');
		header("Content-type: application/json; charset=utf-8");
		echo json_encode(array("result" => $hasil));
	}
?>
