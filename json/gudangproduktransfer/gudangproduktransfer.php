<?php

/**
 * JSON API for Transfer Gudang Data
 * 
 * This file serves as a bridge between the frontend and backend API,
 * providing transfer gudang data in JSON format.
 * Uses the AJAX get_transfergudang.php endpoint as the data source.
 */

// Include required dependencies
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
require_once('../../config/function/paging.php');

// Initialize classes
$base = new DB;
$secu = new Security;
$data = new Data;
$paging = new Paging;
$conn = $base->open();
$sistem = $data->sistem('url_sis');

// ACCESS DATA
$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$level = $secu->injection(@$_COOKIE['jeniskuy']);
$valid = $secu->validadmin($admin, $kunci);

// GET DATA
$cari = (isset($_GET['caridata'])) ? $secu->injection(@$_GET['caridata']) : "";
$page = (isset($_GET['halaman'])) ? max(1, intval($secu->injection(@$_GET['halaman']))) : 1;
$maxi = (isset($_GET['maximal'])) ? $secu->injection(@$_GET['maximal']) : "15";
$menu = $secu->injection(@$_GET['menudata']);
$mulai = ($page - 1) * $maxi; // Perhitungan offset yang konsisten

// READ DATA
if ($valid == false) {
    $tabel = '<tr><td colspan="7">Session login anda habis...</td></tr>';
    $navi = '';
} else {
    $tabel = '';
    $no = $mulai;

    // Build WHERE clause
    $where = "WHERE 1=1";
    if (!empty($cari)) {
        $where .= " AND (t.kode_ttg LIKE '%$cari%' OR mi.nama_inventory LIKE '%$cari%' OR mi_tujuan.nama_inventory LIKE '%$cari%')";
    }

    // Get count datas
    $qJumlah = "SELECT
                    COUNT(t.id_ttg) AS total
                FROM 
                    transfer_gudang t
                LEFT JOIN 
                    master_inventory mi ON t.id_inventory = mi.id_inventory
                LEFT JOIN 
                    master_inventory mi_tujuan ON t.id_inventory_tujuan = mi_tujuan.id_inventory
                $where";

    $jumlah = $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);

    // Get record datas
    $qMaster = "SELECT 
                    t.id_ttg, 
                    t.kode_ttg, 
                    t.tgl_ttg, 
                    t.id_inventory as id_gudang_asal,
                    t.id_inventory_tujuan as id_gudang_tujuan,
                    mi.nama_inventory as gudang_asal_nama,
                    mi_tujuan.nama_inventory as gudang_tujuan_nama,
                    t.ket_ttg, 
                    t.status_ttg, 
                    t.created_at, 
                    t.created_by,
                    a.nama_adm as created_by_name
                FROM 
                    transfer_gudang t
                LEFT JOIN 
                    master_inventory mi ON t.id_inventory = mi.id_inventory
                LEFT JOIN 
                    master_inventory mi_tujuan ON t.id_inventory_tujuan = mi_tujuan.id_inventory    
                LEFT JOIN 
                    adminz a ON t.created_by = a.id_adm
                $where
                ORDER BY 
                    t.created_at DESC
                LIMIT :mulai, :maxi";

    $master = $conn->prepare($qMaster);
    $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();

    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        $no++;

        // Format tanggal
        $tgl_ttg_formatted = date('d M Y', strtotime($hasil['tgl_ttg']));
        $created_at_formatted = date('d M Y H:i', strtotime($hasil['created_at']));

        // Status class
        $status_class = '';
        switch ($hasil['status_ttg']) {
            case 'Draft':
                $status_class = 'secondary';
                break;
            case 'Process':
                $status_class = 'primary';
                break;
            case 'Delivered':
                $status_class = 'info';
                break;
            case 'Completed':
                $status_class = 'success';
                break;
            case 'Canceled':
                $status_class = 'danger';
                break;
            default:
                $status_class = 'secondary';
        }

        // Hitung total items
        $detail_query = "SELECT COUNT(*) as total_items FROM transfer_gudangdetail WHERE id_ttg = :id_ttg";
        $detail_stmt = $conn->prepare($detail_query);
        $detail_stmt->bindParam(':id_ttg', $hasil['id_ttg']);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->fetch(PDO::FETCH_ASSOC);
        $total_items = $detail_result['total_items'];


        $view = '<button class="btn btn-outline-primary btn-xs" onclick="window.open(\'' . $sistem . '/modal/gudangproduktransfer/detail_standalone.php?keycode=' . $hasil['id_ttg'] . '\', \'detailWindow\', \'width=800,height=600,scrollbars=yes\')"><i class="fa fa-eye"></i></button>';
        $history = '<a href="' . $sistem . '/gudangproduktransfer/history/' . $hasil['id_ttg'] . '" title="History Transfer"><button class="btn btn-outline-info btn-xs"><i class="fa fa-history"></i></button></a>';

        $tabel .= '
                <tr>
                    <td align="center">' . $no . '</td>
                    <td>' . $hasil['kode_ttg'] . '</td>
                    <td>' . $tgl_ttg_formatted . '</td>
                    <td>' . $hasil['gudang_asal_nama'] . '</td>
                    <td>' . (!empty($hasil['gudang_tujuan_nama']) ? $hasil['gudang_tujuan_nama'] : '-') . '</td>
                    <td><span class="badge badge-' . $status_class . '">' . $hasil['status_ttg'] . '</span></td>
                    <td align="center">' . $view . ' ' . $history . '</td>
                </tr>';
    }

    // Generate pagination
    $page = intval($page);
    $navi = $paging->myPaging($menu, $jumlah['total'], $maxi, $page);
}

// Return JSON
$json = array(
    "success" => true,
    "data" => array(
        "transfers" => array(),
        "pagination" => array(
            "total" => $jumlah['total'],
            "page" => intval($page),
            "limit" => intval($maxi),
            "total_pages" => ceil($jumlah['total'] / $maxi)
        )
    ),
    "tabel" => $tabel,
    "halaman" => $page,
    "paginasi" => $navi
);

// Format data untuk penggunaan di frontend JS
if ($valid && $master->rowCount() > 0) {
    $master = $conn->prepare($qMaster);
    $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();

    $transfers = array();
    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        // Format tanggal
        $tgl_ttg_formatted = date('d M Y', strtotime($hasil['tgl_ttg']));
        $created_at_formatted = date('d M Y H:i', strtotime($hasil['created_at']));

        // Status class
        $status_class = '';
        switch ($hasil['status_ttg']) {
            case 'Draft':
                $status_class = 'secondary';
                break;
            case 'Process':
                $status_class = 'primary';
                break;
            case 'Delivered':
                $status_class = 'info';
                break;
            case 'Completed':
                $status_class = 'success';
                break;
            case 'Canceled':
                $status_class = 'danger';
                break;
            default:
                $status_class = 'secondary';
        }

        // Hitung total items
        $detail_query = "SELECT COUNT(*) as total_items FROM transfer_gudangdetail WHERE id_ttg = :id_ttg";
        $detail_stmt = $conn->prepare($detail_query);
        $detail_stmt->bindParam(':id_ttg', $hasil['id_ttg']);
        $detail_stmt->execute();
        $detail_result = $detail_stmt->fetch(PDO::FETCH_ASSOC);
        $total_items = $detail_result['total_items'];

        $transfers[] = array(
            "id_ttg" => $hasil['id_ttg'],
            "kode_ttg" => $hasil['kode_ttg'],
            "kode_ext_ttg" => null,
            "tgl_ttg" => $hasil['tgl_ttg'],
            "id_gudang_asal" => $hasil['id_gudang_asal'],
            "id_gudang_tujuan" => $hasil['id_gudang_tujuan'],
            "gudang_asal_nama" => $hasil['gudang_asal_nama'],
            "gudang_tujuan_nama" => $hasil['gudang_tujuan_nama'],
            "ket_ttg" => $hasil['ket_ttg'],
            "status_ttg" => $hasil['status_ttg'],
            "created_at" => $hasil['created_at'],
            "created_by" => $hasil['created_by'],
            "created_by_name" => $hasil['created_by_name'],
            "total_items" => $total_items,
            "tgl_ttg_formatted" => $tgl_ttg_formatted,
            "created_at_formatted" => $created_at_formatted,
            "status_class" => $status_class
        );
    }

    $json['data']['transfers'] = $transfers;
}

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
http_response_code(200);

// Output JSON response
echo json_encode($json);

// Close connection
$conn = $base->close();
