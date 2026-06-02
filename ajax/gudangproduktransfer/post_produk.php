<?php

/**
 * Post Processing for Warehouse Transfer (JSON Response version)
 */

// Prevent any output before headers
ob_start();

// Include required files - ADDED THESE LINES
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

try {
    // Initialize necessary objects - FIXED INITIALIZATION
    $base = new DB;
    $data = new Data;
    $secu = new Security();
    $conn = $data->open();
    $today = date('Y-m-d H:i:s');

    // Set proper headers for JSON response
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');

    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Authentication check
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $valid = $secu->validadmin($admin, $kunci);

    if ($valid === false) {
        throw new Exception('Session expired, please login again');
    }

    // Extract and sanitize form data
    $kode = $secu->injection($_POST['kode_ttg'] ?? '');
    $tanggal = $secu->injection($_POST['tanggal_ttg'] ?? '');
    $gudang_asal = $secu->injection($_POST['gudang_asal'] ?? '');
    $gudang_tujuan = $secu->injection($_POST['gudang_tujuan'] ?? '');
    $catatan = $secu->injection($_POST['catatan_ttg'] ?? '');

    // Special handling for JSON data - DON'T run it through injection filter
    $products_json = $_POST['cartaddProductTransfer'] ?? '';

    // Debug - log the raw JSON to help diagnose issues
    error_log("Raw cart data: " . $products_json);

    // Parse and validate product data
    $products = json_decode($products_json, true);

    // Debug - check if parsing was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON parse error: " . json_last_error_msg());
        throw new Exception('Error parsing product data: ' . json_last_error_msg());
    }

    // More detailed validation
    if (empty($products)) {
        throw new Exception('Tidak ada produk yang dipilih untuk transfer');
    }

    if (!is_array($products)) {
        error_log("Products data is not an array: " . gettype($products));
        throw new Exception('Format data produk tidak valid');
    }

    // Log the decoded data structure
    error_log("Decoded products: " . print_r($products, true));

    // Start transaction
    $conn->beginTransaction();

    // 1. Insert into transfer_gudang table
    $query_main = $conn->prepare("
        INSERT INTO transfer_gudang (
            kode_ttg, 
            tgl_ttg, 
            id_inventory,
            id_inventory_tujuan,
            ket_ttg,
            status_ttg,
            created_at,
            created_by
        ) VALUES (
            :kode,
            :tanggal,
            :gudang_asal,
            :gudang_tujuan,
            :catatan,
            'Draft',
            :created_at,
            :created_by
        )
    ");

    $query_main->bindParam(':kode', $kode);
    $query_main->bindParam(':tanggal', $tanggal);
    $query_main->bindParam(':gudang_asal', $gudang_asal);
    $query_main->bindParam(':gudang_tujuan', $gudang_tujuan);
    $query_main->bindParam(':catatan', $catatan);
    $query_main->bindParam(':created_at', $today);
    $query_main->bindParam(':created_by', $admin);
    $query_main->execute();

    // Get the inserted transfer ID
    $id_ttg = $conn->lastInsertId();

    // Insert into notifications
    $query_log = $conn->prepare("
        INSERT INTO transfer_gudang_log (
            id_ttg,
            status_lama,
            status_baru,
            catatan,
            created_at,
            created_by
        ) VALUES (
            :id_ttg,
            NULL,  /* Empty status_lama for new entries */
            'Draft',
            'Transfer Gudang Baru',
            :created_at,
            :created_by
        )
    ");

    $query_log->bindParam(':id_ttg', $id_ttg);
    $query_log->bindParam(':created_at', $today);
    $query_log->bindParam(':created_by', $admin);
    $query_log->execute();

    // 3. Process each product
    foreach ($products as $index => $product) {
        // Validate required product data
        if (empty($product['product_id']) || empty($product['psd_id']) || empty($product['qty'])) {
            throw new Exception("Data produk tidak lengkap pada index $index");
        }

        $product_id = $product['product_id'];
        $psd_id = $product['psd_id'];
        $qty = (int)$product['qty'];
        $batch = $product['batch'] ?? '';
        $product_name = $product['product_name'] ?? 'Unknown Product';

        // Check if stock is available
        $check_stock = $conn->prepare("
            SELECT sisa_psd 
            FROM produk_stokdetail 
            WHERE id_psd = :psd_id AND gudang = :gudang_asal
        ");

        $check_stock->bindParam(':psd_id', $psd_id);
        $check_stock->bindParam(':gudang_asal', $gudang_asal);
        $check_stock->execute();
        $stock_data = $check_stock->fetch(PDO::FETCH_ASSOC);

        if (!$stock_data || $stock_data['sisa_psd'] < $qty) {
            throw new Exception("Stok tidak mencukupi untuk produk: " . $product_name);
        }

        // Insert into transfer_gudangdetail
        $query_detail = $conn->prepare("
            INSERT INTO transfer_gudangdetail (
                id_ttg,
                id_psd,
                id_pro,
                jumlah_ttd,
                no_batch,
                created_at,
                created_by
            ) VALUES (
                :id_ttg,
                :id_psd,
                :id_pro,
                :jumlah,
                :batch,
                :created_at,
                :created_by
            )
        ");

        $query_detail->bindParam(':id_ttg', $id_ttg);
        $query_detail->bindParam(':id_psd', $psd_id);
        $query_detail->bindParam(':id_pro', $product_id);
        $query_detail->bindParam(':jumlah', $qty);
        $query_detail->bindParam(':batch', $batch);
        $query_detail->bindParam(':created_at', $today);
        $query_detail->bindParam(':created_by', $admin);
        $query_detail->execute();

        // Update stock in produk_stokdetail (source warehouse)
        $query_update_stock = $conn->prepare("
            UPDATE produk_stokdetail 
            SET 
                keluar_psd = keluar_psd + :qty,
                sisa_psd = sisa_psd - :qty,
                updated_at = :updated_at,
                updated_by = :updated_by
            WHERE 
                id_psd = :id_psd AND gudang = :gudang_asal
        ");

        $query_update_stock->bindParam(':qty', $qty);
        $query_update_stock->bindParam(':updated_at', $today);
        $query_update_stock->bindParam(':updated_by', $admin);
        $query_update_stock->bindParam(':id_psd', $psd_id);
        $query_update_stock->bindParam(':gudang_asal', $gudang_asal);
        $query_update_stock->execute();
    }

    // Check if activity_log table exists and has the required columns
    // Write activity to riwayat table
    try {
        $log_desc = "Membuat transfer gudang dengan kode $kode";
        $query_activity = $conn->prepare("
            INSERT INTO riwayat (
                kode_riwayat,
                status_riwayat,
                ket_riwayat,
                menu_riwayat,
                created_at,
                created_by
            ) VALUES (
                :kode,
                'Create',
                :description,
                'Transfer Gudang',
                :created_at,
                :user_id
            )
        ");

        $query_activity->bindParam(':kode', $kode); // Perbaiki dari :id_ttg menjadi :kode
        $query_activity->bindParam(':description', $log_desc);
        $query_activity->bindParam(':created_at', $today);
        $query_activity->bindParam(':user_id', $admin);
        $query_activity->execute();
    } catch (PDOException $e) {
        // Log the error but continue with the transaction
        error_log("Failed to insert into riwayat: " . $e->getMessage());
    }

    // Commit transaction
    $conn->commit();

    // Clear any output buffer to avoid contamination
    ob_clean();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => "Data transfer gudang berhasil disimpan dengan kode $kode",
        'data' => [
            'kode' => $kode,
            'id' => $id_ttg
        ]
    ]);
} catch (PDOException $e) {
    // Handle database errors
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("Transfer Error: " . $e->getMessage());

    // Clear any output buffer
    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada database: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    // Handle general errors
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log("General Error: " . $e->getMessage());

    // Clear any output buffer
    ob_clean();

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    // Close database connection if it exists
    if (isset($data)) {
        $data->close();
    }

    // End output buffering and flush
    if (ob_get_level()) ob_end_flush();
}

/**
 * Tambahkan fungsi di file terpisah untuk menyelesaikan transfer
 */
function completeTransfer($id_ttg, $admin) {
    global $conn, $today;
    
    // 1. Ambil detail transfer
    $get_transfer = $conn->prepare("SELECT * FROM transfer_gudang WHERE id_ttg = :id");
    $get_transfer->bindParam(':id', $id_ttg);
    $get_transfer->execute();
    $transfer = $get_transfer->fetch(PDO::FETCH_ASSOC);
    
    // 2. Ambil semua produk yang ditransfer
    $get_products = $conn->prepare("SELECT * FROM transfer_gudangdetail WHERE id_ttg = :id");
    $get_products->bindParam(':id', $id_ttg);
    $get_products->execute();
    $products = $get_products->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Untuk setiap produk, tambahkan stok di gudang tujuan
    foreach ($products as $product) {
        // Ambil detail produk
        $get_product_detail = $conn->prepare("
            SELECT ps.*, p.nama_pro 
            FROM produk_stokdetail ps
            JOIN produk p ON ps.id_pro = p.id_pro
            WHERE ps.id_psd = :id
        ");
        $get_product_detail->bindParam(':id', $product['id_psd']);
        $get_product_detail->execute();
        $product_detail = $get_product_detail->fetch(PDO::FETCH_ASSOC);
        
        // Cek jika produk dengan batch yang sama sudah ada di gudang tujuan
        $check_target = $conn->prepare("
            SELECT id_psd FROM produk_stokdetail 
            WHERE 
                id_pro = :pro_id AND 
                gudang = :gudang AND 
                no_batch = :batch
        ");
        $check_target->bindParam(':pro_id', $product['id_pro']);
        $check_target->bindParam(':gudang', $transfer['id_inventory_tujuan']);
        $check_target->bindParam(':batch', $product['no_batch']);
        $check_target->execute();
        $target = $check_target->fetch(PDO::FETCH_ASSOC);
        
        if ($target) {
            // Update stok yang ada
            $update = $conn->prepare("
                UPDATE produk_stokdetail 
                SET 
                    masuk_psd = masuk_psd + :qty,
                    sisa_psd = sisa_psd + :qty,
                    updated_at = :time,
                    updated_by = :admin
                WHERE id_psd = :id
            ");
            $update->bindParam(':qty', $product['jumlah_ttd']);
            $update->bindParam(':time', $today);
            $update->bindParam(':admin', $admin);
            $update->bindParam(':id', $target['id_psd']);
            $update->execute();
        } else {
            // Buat stok baru
            $insert = $conn->prepare("
                INSERT INTO produk_stokdetail (
                    id_pro, no_batch, tgl_expired, 
                    masuk_psd, keluar_psd, sisa_psd, gudang,
                    created_at, created_by
                ) VALUES (
                    :pro_id, :batch, :expired,
                    :qty, 0, :qty, :gudang,
                    :time, :admin
                )
            ");
            $insert->bindParam(':pro_id', $product['id_pro']);
            $insert->bindParam(':batch', $product['no_batch']);
            $insert->bindParam(':expired', $product_detail['tgl_expired']);
            $insert->bindParam(':qty', $product['jumlah_ttd']);
            $insert->bindParam(':gudang', $transfer['id_inventory_tujuan']);
            $insert->bindParam(':time', $today);
            $insert->bindParam(':admin', $admin);
            $insert->execute();
        }
    }
    
    // 4. Update status transfer menjadi 'Completed'
    $update_transfer = $conn->prepare("
        UPDATE transfer_gudang 
        SET 
            status_ttg = 'Completed',
            updated_at = :time,
            updated_by = :admin
        WHERE id_ttg = :id
    ");
    $update_transfer->bindParam(':time', $today);
    $update_transfer->bindParam(':admin', $admin);
    $update_transfer->bindParam(':id', $id_ttg);
    $update_transfer->execute();
    
    // 5. Catat ke log
    $log = $conn->prepare("
        INSERT INTO transfer_gudang_log (
            id_ttg, status_lama, status_baru,
            catatan, created_at, created_by
        ) VALUES (
            :id, 'Process', 'Completed',
            'Transfer selesai dan diterima', :time, :admin
        )
    ");
    $log->bindParam(':id', $id_ttg);
    $log->bindParam(':time', $today);
    $log->bindParam(':admin', $admin);
    $log->execute();
    
    return true;
}

/**
 * Fungsi untuk mencatat perubahan status transfer
 */
function logTransferStatusChange($id_ttg, $old_status, $new_status, $note, $admin) {
    global $conn, $today;
    
    // Catat ke tabel transfer_gudang_log
    $query = $conn->prepare("
        INSERT INTO transfer_gudang_log (
            id_ttg,
            status_lama,
            status_baru,
            catatan,
            created_at,
            created_by
        ) VALUES (
            :id_ttg,
            :status_lama,
            :status_baru,
            :catatan,
            :created_at,
            :created_by
        )
    ");
    
    $query->bindParam(':id_ttg', $id_ttg);
    $query->bindParam(':status_lama', $old_status);
    $query->bindParam(':status_baru', $new_status);
    $query->bindParam(':catatan', $note);
    $query->bindParam(':created_at', $today);
    $query->bindParam(':created_by', $admin);
    
    return $query->execute();
}

/**
 * Update Status Transfer Gudang dengan History Tracking
 */
function updateTransferStatus($id_ttg, $new_status, $note, $admin) {
    global $conn, $today;
    
    // Dapatkan status saat ini
    $get_current = $conn->prepare("SELECT status_ttg FROM transfer_gudang WHERE id_ttg = :id");
    $get_current->bindParam(':id', $id_ttg);
    $get_current->execute();
    $current = $get_current->fetch(PDO::FETCH_ASSOC);
    
    if (!$current) {
        return ['success' => false, 'message' => 'Transfer tidak ditemukan'];
    }
    
    $old_status = $current['status_ttg'];
    
    // Validasi perubahan status
    $valid_transitions = [
        'Draft' => ['Process', 'Canceled'],
        'Process' => ['Delivered', 'Canceled'],
        'Delivered' => ['Completed', 'Canceled'],
        'Completed' => [], // Tidak bisa diubah lagi
        'Canceled' => []   // Tidak bisa diubah lagi
    ];
    
    if (!in_array($new_status, $valid_transitions[$old_status])) {
        return [
            'success' => false, 
            'message' => "Perubahan status dari {$old_status} ke {$new_status} tidak diperbolehkan"
        ];
    }
    
    // Mulai transaksi
    $conn->beginTransaction();
    
    try {
        // Update status transfer
        $update = $conn->prepare("
            UPDATE transfer_gudang 
            SET 
                status_ttg = :status,
                updated_at = :time,
                updated_by = :admin
            WHERE id_ttg = :id
        ");
        $update->bindParam(':status', $new_status);
        $update->bindParam(':time', $today);
        $update->bindParam(':admin', $admin);
        $update->bindParam(':id', $id_ttg);
        $update->execute();
        
        // Catat ke history log
        logTransferStatusChange($id_ttg, $old_status, $new_status, $note, $admin);
        
        // Catat ke riwayat umum
        $log_desc = "Mengubah status transfer gudang dari {$old_status} menjadi {$new_status}";
        $kode = "TG-" . $id_ttg;
        
        $query_activity = $conn->prepare("
            INSERT INTO riwayat (
                kode_riwayat,
                status_riwayat,
                ket_riwayat,
                menu_riwayat,
                created_at,
                created_by
            ) VALUES (
                :kode,
                'Update',
                :description,
                'Transfer Gudang',
                :created_at,
                :user_id
            )
        ");

        $query_activity->bindParam(':kode', $kode);
        $query_activity->bindParam(':description', $log_desc);
        $query_activity->bindParam(':created_at', $today);
        $query_activity->bindParam(':user_id', $admin);
        $query_activity->execute();
        
        // Jika status baru adalah 'Completed', tambahkan stok di gudang tujuan
        if ($new_status === 'Completed') {
            // Panggil fungsi untuk menyelesaikan transfer (menambah stok tujuan)
            // Ini bisa dipanggil dari file terpisah jika diimplementasikan
            completeTransfer($id_ttg, $admin);
        }
        
        // Commit transaksi
        $conn->commit();
        
        return [
            'success' => true,
            'message' => "Status transfer berhasil diubah menjadi {$new_status}"
        ];
    } 
    catch (Exception $e) {
        // Rollback jika terjadi error
        $conn->rollBack();
        
        return [
            'success' => false,
            'message' => "Gagal mengubah status: " . $e->getMessage()
        ];
    }
}
