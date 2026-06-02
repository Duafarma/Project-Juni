<?php
/**
 * AJAX Get Items by Kode Faktur
 */
session_start();
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

header('Content-Type: application/json');

try {
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $conn   = $base->open();

    $kode_faktur = $secu->injection(@$_GET['kode_faktur']);

    if(empty($kode_faktur)){
        echo json_encode(['status' => 'error', 'message' => 'Kode faktur tidak ditemukan']);
        exit;
    }

    $qItems = "SELECT 
                    A.*,
                    B.nama_pro,
                    B.kode_produk_jadi
                FROM produk_stockdetail_cancel AS A
                LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                WHERE A.kode_faktur = :kode_faktur AND A.status = 'cancel'
                ORDER BY B.nama_pro ASC";
    $items = $conn->prepare($qItems);
    $items->bindParam(':kode_faktur', $kode_faktur, PDO::PARAM_STR);
    $items->execute();
    $itemList = $items->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($itemList)){
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada item pending untuk faktur ini']);
        exit;
    }
    
    // Format data
    $result = [];
    foreach($itemList as $item){
        $item['tgl_expired_formatted'] = !empty($item['tgl_expired']) ? date('d-m-Y', strtotime($item['tgl_expired'])) : '-';
        $item['jumlah_cancel_formatted'] = $data->angka($item['jumlah_cancel']);
        $result[] = $item;
    }
    
    echo json_encode(['status' => 'success', 'data' => $result]);
    
} catch(Exception $e){
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
