<?php
	require_once('../config/connection/connection.php');
	require_once('../config/connection/security.php');
	require_once('../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$catat	= date('Y-m-d H:i:s');
    $encrypt= $secu->injection(@$_GET['encrypt']);
    $act	= $secu->injection(@$_GET['act']);
	$conn	= $base->open();
    $hasil 	= "Error";
    // checking encrypt
    $source = $data->self_apl();
    $sourceKey  = $source['key_apl'];
    if (md5(date('Y-m-d') . "#" . $sourceKey) == $encrypt) {
        // save transfer product detail
        $msgBugs = array();
        switch($act){
            case "updateStatusApprove":
                // insert outlet data
                $outletData = implode(",",array_slice(@$_POST,0,14));
                $outletDataSave = "INSERT INTO outlet VALUES(".$outletData.")";
                // insert outlet alamat
                $outletAlamatData = implode(",",array_slice(@$_POST,15,23));
                $outletAlamatDataSave = "INSERT INTO outlet_alamat VALUES('', ".$outletAlamatData.")";
                // insert outlet diskon
                $outletDiskonData = implode(",",array_slice(@$_POST,38,11));
                $outletDiskonDataSave = "INSERT INTO outlet_diskon VALUES('', ".$outletDiskonData.")";
                // insert outlet legal
                $outletLegalData = implode(",",array_slice(@$_POST,49,9));
                $outletLegalDataSave = "INSERT INTO outlet_legal VALUES('', ".$outletLegalData.")";
                // execute insert
                try {
                    $outletDataSave->execute();
                    $outletAlamatDataSave->execute();
                    $outletDiskonDataSave->execute();
                    $outletLegalDataSave->execute();
                } catch (PDOException $e) {
                    array_push($msgBugs, $e->getMessage());
                }
                break;
            default:
                $hasil = "Error";
                http_response_code(500);
                break;
        }
    } else {
        $hasil = "Unauthorized";
        http_response_code(401);
    }
    // check error
    if (empty($msgBugs)) {
        $hasil = "Success";
        http_response_code(200);
    } else {
        $hasil = implode(", ",$msgBugs);
        http_response_code(500);
    }
	$conn	= $base->close();
	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/json; charset=utf-8");
	echo json_encode(array("result" => $hasil));
?>
