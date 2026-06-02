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
		$cari	= $secu->injection(@$_GET['caridata']);
    	$page	= $secu->injection(@$_GET['halaman']);
    	$maxi	= $secu->injection(@$_GET['maximal']);
    	$menu	= $secu->injection(@$_GET['menudata']);
    	$mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;
		
		// checking encrypt
		$encrypt= $secu->injection($_GET['encrypt']);
		$source = $data->self_apl();
		$sourceKey  = $source['key_apl'];
		if (md5($tgl. "#" . $sourceKey) == $encrypt) {
			// checking params
			$cari	= $secu->injection(@$_GET['caridata']);
			// get query
			$active		= 'Active';
			$A          = 'A';
			$qMaster    = "SELECT  	'A' as source,
			                    A.id_out,
			                    A.profit, 
			                    A.nama_out, 
			                    C.diskon_odi,
			                    A.status_faktur, 
			                    A.status_out 
			               FROM 
			               outlet AS A INNER JOIN outlet_diskon AS C ON A.id_out=C.id_out 
			               WHERE A.status_out=:active AND A.status_faktur=:A  GROUP BY A.nama_out
					         ORDER BY A.nama_out ASC";
			$master	= $conn->prepare($qMaster);
			$master->bindParam(':active', $active, PDO::PARAM_STR);
			$master->bindParam(':A', $A, PDO::PARAM_STR);

			$master->execute();
			if ($master) {
				$hasil= $master->fetchAll(PDO::FETCH_ASSOC);
					$januari	    = $data->januari($hasil['id_out']);
                $februari	    = $data->februari($hasil['id_out']);
                $maret		    = $data->maret($hasil['id_out']);
                $april		    = $data->april($hasil['id_out']);
                $mei		    = $data->mei($hasil['id_out']);
                $juni		    = $data->juni($hasil['id_out']);
                $juli		    = $data->juli($hasil['id_out']);
                $agustus		= $data->agustus($hasil['id_out']);
        
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
