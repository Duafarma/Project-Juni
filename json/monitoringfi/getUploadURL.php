<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    $conn = $base->open();
    
    // ACCESS DATA
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $level = $secu->injection(@$_COOKIE['jeniskuy']);
    $valid = $secu->validadmin($admin, $kunci);
    
    // GET DATA
    $id_tfk = $secu->injection(@$_GET['id_tfk']);
    $cabang = $secu->injection(@$_GET['cabang']);
    
    $response = [
        "success" => false,
        "message" => "Dokumen tidak ditemukan",
        "url" => null
    ];
    
    if($valid) {
        try {
            // Query to get the latest document URL for this invoice
            $stmt = $conn->prepare("SELECT url_upload FROM upload_f_pajak_detail 
                                    WHERE no_faktur = :no_faktur
                                    ORDER BY created_at DESC LIMIT 1");
            $stmt->bindParam(':no_faktur', $no_faktur);
            $stmt->execute();
            
            if($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $response["success"] = true;
                $response["message"] = "URL dokumen ditemukan";
                $response["url"] = $result['url_upload'];
            }
        } catch(PDOException $e) {
            $response["message"] = "Database error: " . $e->getMessage();
        }
    } else {
        $response["message"] = "Unauthorized access";
    }
    
    $conn = $base->close();
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
?>