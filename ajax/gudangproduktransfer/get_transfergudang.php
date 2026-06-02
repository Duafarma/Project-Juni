<?php
// filepath: c:\Development\laragon\www\192.268.908.09\ajax\gudangproduktransfer\get_transfergudang.php

/**
 * Get Transfer Gudang Data
 * 
 * This endpoint retrieves warehouse transfer data either for a specific transfer
 * or a list of transfers based on the provided parameters.
 * 
 * @uses $_POST['id_ttg'] - Optional. If provided, returns details for a specific transfer
 * @uses $_POST['start_date'] - Optional. Filter transfers by start date
 * @uses $_POST['end_date'] - Optional. Filter transfers by end date
 * @uses $_POST['status'] - Optional. Filter transfers by status
 */

// Add CORS headers for browser compatibility
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json');

// Include required files
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

try {
    // Initialize database connection
    $base = new DB;
    $data = new Data;
    $secu = new Security();
    $conn = $data->open();

    // Check authentication
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $valid = $secu->validadmin($admin, $kunci);

    if ($valid === false) {
        throw new Exception('Session expired, please login again');
    }

    // Check if a specific transfer ID is requested
    if (isset($_POST['id_ttg']) && !empty($_POST['id_ttg'])) {
        $id_ttg = $secu->injection($_POST['id_ttg']);

        // Get the main transfer data
        $query = $conn->prepare("
            SELECT 
                t.id_ttg, 
                t.kode_ttg, 
                t.kode_ext_ttg, 
                t.tgl_ttg, 
                t.id_inventory as id_gudang_asal,
                t.id_inventory_tujuan as id_gudang_tujuan,
                mi.nama_inventory as gudang_asal_nama,
                mi.nama_inventory as gudang_tujuan_nama,
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
                adminz a ON t.created_by = a.id_adm
            WHERE 
                t.id_ttg = :id_ttg
        ");

        $query->bindParam(':id_ttg', $id_ttg);
        $query->execute();
        $transfer = $query->fetch(PDO::FETCH_ASSOC);

        if (!$transfer) {
            throw new Exception('Transfer tidak ditemukan');
        }

        // Get the transfer details (products)
        $detail_query = $conn->prepare("
            SELECT 
                td.id_tgd, 
                td.id_ttg, 
                td.id_psd, 
                td.id_pro, 
                td.id_ext_pro, 
                td.jumlah_ttd, 
                td.keterangan, 
                td.no_batch, 
                p.nama_pro,
                ps.tgl_expired,
                ps.sisa_psd as stok_asal
            FROM 
                transfer_gudangdetail td
            LEFT JOIN 
                produk p ON td.id_pro = p.id_pro
            LEFT JOIN 
                produk_stokdetail ps ON td.id_psd = ps.id_psd
            WHERE 
                td.id_ttg = :id_ttg
            ORDER BY 
                td.id_tgd ASC
        ");

        $detail_query->bindParam(':id_ttg', $id_ttg);
        $detail_query->execute();
        $details = $detail_query->fetchAll(PDO::FETCH_ASSOC);

        // Get the transfer logs
        $log_query = $conn->prepare("
            SELECT 
                l.id_log, 
                l.status_lama, 
                l.status_baru, 
                l.catatan, 
                l.created_at, 
                l.created_by,
                a.nama_adm as created_by_name
            FROM 
                transfer_gudang_log l
            LEFT JOIN 
                adminz a ON l.created_by = a.id_adm
            WHERE 
                l.id_ttg = :id_ttg
            ORDER BY 
                l.created_at DESC
        ");

        $log_query->bindParam(':id_ttg', $id_ttg);
        $log_query->execute();
        $logs = $log_query->fetchAll(PDO::FETCH_ASSOC);

        // Format dates for better display
        if ($transfer) {
            $transfer['tgl_ttg_formatted'] = date('d M Y', strtotime($transfer['tgl_ttg']));
            $transfer['created_at_formatted'] = date('d M Y H:i', strtotime($transfer['created_at']));
        }

        foreach ($details as &$detail) {
            if (!empty($detail['tgl_expired'])) {
                $detail['tgl_expired_formatted'] = date('d M Y', strtotime($detail['tgl_expired']));
            } else {
                $detail['tgl_expired_formatted'] = '-';
            }
        }

        foreach ($logs as &$log) {
            $log['created_at_formatted'] = date('d M Y H:i', strtotime($log['created_at']));
        }

        // Return the complete transfer data
        echo json_encode([
            'success' => true,
            'data' => [
                'transfer' => $transfer,
                'details' => $details,
                'logs' => $logs
            ]
        ]);
    } else {
        // List of transfers with filtering options
        $where = "WHERE 1=1";
        $params = [];

        // Filter by date range if provided
        if (isset($_POST['start_date']) && !empty($_POST['start_date'])) {
            $start_date = $secu->injection($_POST['start_date']);
            $where .= " AND t.tgl_ttg >= :start_date";
            $params[':start_date'] = $start_date;
        }

        if (isset($_POST['end_date']) && !empty($_POST['end_date'])) {
            $end_date = $secu->injection($_POST['end_date']);
            $where .= " AND t.tgl_ttg <= :end_date";
            $params[':end_date'] = $end_date;
        }

        // Filter by status if provided
        if (isset($_POST['status']) && !empty($_POST['status'])) {
            $status = $secu->injection($_POST['status']);
            $where .= " AND t.status_ttg = :status";
            $params[':status'] = $status;
        }

        // Get total count for pagination
        $count_query = $conn->prepare("
            SELECT COUNT(*) as total
            FROM transfer_gudang t
            $where
        ");

        foreach ($params as $key => $value) {
            $count_query->bindValue($key, $value);
        }

        $count_query->execute();
        $total = $count_query->fetchColumn();

        // Get paginated data
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = ($page - 1) * $limit;

        $query = $conn->prepare("
                SELECT 
                t.id_ttg, 
                t.kode_ttg, 
                t.kode_ext_ttg,
                t.tgl_ttg, 
                t.id_inventory as id_gudang_asal,
                t.id_inventory_tujuan as id_gudang_tujuan,
                mi.nama_inventory as gudang_asal_nama,
                mi_tujuan.nama_inventory as gudang_tujuan_nama,
                t.ket_ttg, 
                t.status_ttg, 
                t.created_at, 
                t.created_by,
                a.nama_adm as created_by_name,
                (SELECT COUNT(*) FROM transfer_gudangdetail td WHERE td.id_ttg = t.id_ttg) as total_items
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
            LIMIT :offset, :limit
        ");

        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }

        $query->bindValue(':offset', $offset, PDO::PARAM_INT);
        $query->bindValue(':limit', $limit, PDO::PARAM_INT);
        $query->execute();

        $transfers = $query->fetchAll(PDO::FETCH_ASSOC);

        // Format dates for better display
        foreach ($transfers as &$transfer) {
            $transfer['tgl_ttg_formatted'] = date('d M Y', strtotime($transfer['tgl_ttg']));
            $transfer['created_at_formatted'] = date('d M Y H:i', strtotime($transfer['created_at']));

            // Get status class for UI styling
            switch ($transfer['status_ttg']) {
                case 'Draft':
                    $transfer['status_class'] = 'secondary';
                    break;
                case 'Process':
                    $transfer['status_class'] = 'primary';
                    break;
                case 'Delivered':
                    $transfer['status_class'] = 'info';
                    break;
                case 'Completed':
                    $transfer['status_class'] = 'success';
                    break;
                case 'Canceled':
                    $transfer['status_class'] = 'danger';
                    break;
                default:
                    $transfer['status_class'] = 'secondary';
            }
        }

        // Return the list of transfers with pagination info
        echo json_encode([
            'success' => true,
            'data' => [
                'transfers' => $transfers,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'total_pages' => ceil($total / $limit)
                ]
            ]
        ]);
    }
} catch (PDOException $e) {
    // Handle database errors
    error_log("Database Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle general errors
    error_log("General Error: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close database connection
    if (isset($data)) {
        $data->close();
    }
}
