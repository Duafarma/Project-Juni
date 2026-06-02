<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $sistem = $data->sistem('url_sis');
    $catat  = date('Y-m-d H:i:s');
    $admin  = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
    $valid  = $secu->validadmin($admin, $kunci);
    if($valid==false){ header("location:$sistem/signout"); } else {
    $conn   = $base->open();
    
    // Get parameters
    $supplier = $secu->injection(@$_POST['supplier']);
    $product = $secu->injection(@$_POST['product']);
    $result = "error";
    
    // Check if both parameters exist
    if(!empty($supplier) && !empty($product)) {
        // Delete record from database
        $delete = $conn->prepare("DELETE FROM produk_diskonsup WHERE id_sup=:supplier AND id_pro=:product");
        $delete->bindParam(':supplier', $supplier, PDO::PARAM_STR);
        $delete->bindParam(':product', $product, PDO::PARAM_STR);
        $delete->execute();
        
        // Log the action
        $riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$supplier', 'Supplier Diskon Produk', 'Delete', 'Produk: $product', '$catat', '$admin')");
        
        if($delete) {
            $result = "success";
        }
    }
    
    echo $result;
    $conn = $base->close();
    }
?>