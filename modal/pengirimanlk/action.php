<?php
    error_reporting(0);
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $catat = date('Y-m-d H:i:s');
    $act = $secu->injection(@$_GET['act']);
    
    // Validate admin login
    if ($secu->validadmin($admin, $kunci) == false) {
        header('location:'.$data->sistem('url_sis').'/signout');
        exit();
    }
    
    $conn = $base->open();
    
    switch($act) {
        case "input":
            // Basic variables
            $id_p_l_k           = 'TFKPLK'.time();  // Generate unique ID
            $id_tfk             = $secu->injection($_POST['id_tfk']);
            $id_out             = $secu->injection($_POST['id_out']);
            $id_pengiriman      = $secu->injection($_POST['id_pengiriman']);
            $id_vendor          = $secu->injection($_POST['id_vendor']);
            $cabang          = $secu->injection($_POST['cabang']);
            $nomor_resi         = $secu->injection($_POST['nomor_resi']);
            $tanggal_faktur = $secu->injection($_POST['tanggal_faktur']);
            $tanggal_pengiriman = $secu->injection($_POST['tanggal_pengiriman']);

            $created_by = $admin;
            $updated_by = $admin;
    
        
            $save = $conn->prepare("
                INSERT INTO transaksi_p_luar_kota (
                    id_p_l_k, 
                    id_tfk, 
                    id_out, 
                    id_pengiriman, 
                    id_vendor, 
                    nomor_resi,
                    cabang,
                    tanggal_faktur, 
                    tanggal_pengiriman, 
                    created_at, 
                    created_by, 
                    updated_at, 
                    updated_by, 
                    updated_by_diambil, 
                    updated_at_diambil, 
                    updated_by_pengiriman, 
                    updated_at_pengiriman, 
                    updated_by_terkirim, 
                    updated_at_terkirim)
                VALUES (
                    :id_p_l_k, 
                    :id_tfk, 
                    :id_out, 
                    :id_pengiriman, 
                    :id_vendor, 
                    :nomor_resi,
                    :cabang,
                    :tanggal_faktur,
                    :tanggal_pengiriman, 
                    :created_at, 
                    :created_by, 
                    :updated_at, 
                    :updated_by, 
                    :updated_by_diambil, 
                    :updated_at_diambil, 
                    :updated_by_pengiriman, 
                    :updated_at_pengiriman, 
                    :updated_by_terkirim, 
                    :updated_at_terkirim
                )
            ");
    
            $save->bindParam(':id_p_l_k', $id_p_l_k, PDO::PARAM_STR);
            $save->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
            $save->bindParam(':id_out', $id_out, PDO::PARAM_STR);
            $save->bindParam(':id_pengiriman', $id_pengiriman, PDO::PARAM_STR);
            $save->bindParam(':id_vendor', $id_vendor, PDO::PARAM_STR);
            $save->bindParam(':nomor_resi', $nomor_resi, PDO::PARAM_STR);
            $save->bindParam(':cabang', $cabang, PDO::PARAM_STR);
            $save->bindParam(':tanggal_faktur', $tanggal_faktur, PDO::PARAM_STR);
            $save->bindParam(':tanggal_pengiriman', $tanggal_pengiriman, PDO::PARAM_STR);
            $save->bindParam(':created_at', $catat, PDO::PARAM_STR);
            $save->bindParam(':created_by', $created_by, PDO::PARAM_STR);
            $save->bindParam(':updated_at', $catat, PDO::PARAM_STR);
            $save->bindParam(':updated_by', $updated_by, PDO::PARAM_STR);
            $save->bindParam(':updated_by_diambil', $updated_by, PDO::PARAM_STR);
            $save->bindParam(':updated_at_diambil', $catat, PDO::PARAM_STR);
           
            $null_value = null; 
            $save->bindParam(':updated_by_pengiriman', $null_value, PDO::PARAM_STR);
            $save->bindParam(':updated_at_pengiriman', $null_value, PDO::PARAM_STR);
            $save->bindParam(':updated_by_terkirim', $null_value, PDO::PARAM_STR);
            $save->bindParam(':updated_at_terkirim', $null_value, PDO::PARAM_STR);
    
 
            try {
                $save->execute();  
                $riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id_p_l_k', 'Input Data Pengiriman Luar Kota', 'Create', '', '$catat', '$admin')");
                echo "success";  
            } catch (PDOException $e) {
                echo "error: ".$e->getMessage();  
            }
            //Update
            break;
            case "update":
                $kode	= $secu->injection($_POST['keycode']);
				$id_tfk	= $secu->injection($_POST['id_tfk']);
				$id_out	= $secu->injection($_POST['id_out']);
				$id_pengiriman	= $secu->injection($_POST['id_pengiriman']);
				$id_vendor	= $secu->injection($_POST['id_vendor']);
				$nomor_resi	= $secu->injection($_POST['nomor_resi']);
				$cabang	= $secu->injection($_POST['cabang']);
    
                $edit	= $conn->prepare("UPDATE transaksi_p_luar_kota SET id_tfk=:id_tfk, id_out=:id_out, id_pengiriman=:id_pengiriman, id_vendor=:id_vendor, nomor_resi=:nomor_resi, cabang=:cabang, updated_at=:catat, updated_by=:admin WHERE id_p_l_k=:kode");
                $edit->bindParam(":kode", $kode, PDO::PARAM_STR);
				$edit->bindParam(":id_tfk", $id_tfk, PDO::PARAM_STR);
				$edit->bindParam(":id_out", $id_out, PDO::PARAM_STR);
				$edit->bindParam(":id_pengiriman", $id_pengiriman, PDO::PARAM_STR);
				$edit->bindParam(":id_vendor", $id_vendor, PDO::PARAM_STR);
				$edit->bindParam(":nomor_resi", $nomor_resi, PDO::PARAM_STR);
				$edit->bindParam(":cabang", $cabang, PDO::PARAM_STR);
    
                $edit->bindParam(":catat", $catat, PDO::PARAM_STR);
                $edit->bindParam(":admin", $admin, PDO::PARAM_STR);
                $edit->execute();
                //RIWAYAT
                $riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'Pengiriman Luar Kota', 'Update', '', '$catat', '$admin')");
                $hasil	= ($edit==true) ? "success" : "error";
                echo($hasil);
            break;
            //delete
            case "delete":
                $kode	= $secu->injection($_POST['keycode']);
                $dele	= $conn->prepare("DELETE FROM transaksi_p_luar_kota WHERE id_p_l_k=:kode");
                $dele->bindParam(":kode", $kode, PDO::PARAM_STR);
                $dele->execute();
                //RIWAYAT
                $riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'pengiriman Luar Kota', 'Delete', '', '$catat', '$admin')");
                $hasil	= ($dele==true) ? "success" : "error";
                echo($hasil);
			break;
    }
    
    $conn = $base->close();
?>
