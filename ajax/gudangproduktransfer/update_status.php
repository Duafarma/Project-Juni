<?php
// filepath: c:\Development\laragon\www\192.268.908.09\ajax\gudangproduktransfer\update_status.php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$id_ttg = $secu->injection($_POST['id']);
$new_status = $secu->injection($_POST['status']);
$note = $secu->injection($_POST['catatan']);
$admin = $secu->injection($_COOKIE['adminkuy']);
$today = date('Y-m-d H:i:s');

// Dapatkan status saat ini dan kode_ttg
$get_current = $conn->prepare("SELECT status_ttg, id_inventory, id_inventory_tujuan, kode_ttg FROM transfer_gudang WHERE id_ttg = :id");
$get_current->bindParam(':id', $id_ttg);
$get_current->execute();
$current = $get_current->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    $result = [
        'status' => 'error',
        'message' => 'Transfer tidak ditemukan'
    ];
    echo json_encode($result);
    exit;
}

$old_status = $current['status_ttg'];
$kode = $current['kode_ttg']; // Get the kode_ttg from database


// Validasi perubahan status
$valid_transitions = [
    'Draft' => ['Completed'],
    'Completed' => [] // Tidak bisa diubah lagi
];

if (!in_array($new_status, $valid_transitions[$old_status] ?? [])) {
    $result = [
        'status' => 'error',
        'message' => "Perubahan status dari {$old_status} ke {$new_status} tidak diperbolehkan"
    ];
    echo json_encode($result);
    exit;
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
    $log_query = $conn->prepare("
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

    $log_query->bindParam(':id_ttg', $id_ttg);
    $log_query->bindParam(':status_lama', $old_status);
    $log_query->bindParam(':status_baru', $new_status);
    $log_query->bindParam(':catatan', $note);
    $log_query->bindParam(':created_at', $today);
    $log_query->bindParam(':created_by', $admin);
    $log_query->execute();

    // Catat ke riwayat umum
    $log_desc = "Mengubah status transfer gudang dari {$old_status} menjadi {$new_status}";
    // No need to generate kode here as we already have it from the query above

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

    // Jika status baru adalah 'Completed', update stok di gudang tujuan
    if ($new_status === 'Completed') {
        // Ambil semua produk yang ditransfer
        $get_products = $conn->prepare("
            SELECT 
                td.id_pro, 
                td.id_psd, 
                td.jumlah_ttd,
                ps.no_bcode, 
                ps.tgl_expired
            FROM transfer_gudangdetail td
            JOIN produk_stokdetail ps ON td.id_psd = ps.id_psd
            WHERE td.id_ttg = :id
        ");
        $get_products->bindParam(':id', $id_ttg);
        $get_products->execute();
        $products = $get_products->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            // Cek jika produk dengan batch yang sama sudah ada di gudang tujuan
            $check_target = $conn->prepare("
                SELECT id_psd FROM produk_stokdetail 
                WHERE 
                    id_pro = :pro_id AND 
                    gudang = :gudang AND 
                    no_bcode = :batch
            ");
            $check_target->bindParam(':pro_id', $product['id_pro']);
            $check_target->bindParam(':gudang', $current['id_inventory_tujuan']);
            $check_target->bindParam(':batch', $product['no_bcode']);
            $check_target->execute();
            $target = $check_target->fetch(PDO::FETCH_ASSOC);

            if ($target) {
                // Update stok yang ada
                $update_stock = $conn->prepare("
                    UPDATE produk_stokdetail 
                    SET 
                        masuk_psd = masuk_psd + :qty,
                        sisa_psd = sisa_psd + :qty,
                        updated_at = :time,
                        updated_by = :admin
                    WHERE id_psd = :id
                ");
                $update_stock->bindParam(':qty', $product['jumlah_ttd']);
                $update_stock->bindParam(':time', $today);
                $update_stock->bindParam(':admin', $admin);
                $update_stock->bindParam(':id', $target['id_psd']);
                $update_stock->execute();
            } else {
                // Buat stok baru
                $insert_stock = $conn->prepare("
                    INSERT INTO produk_stokdetail (
                        id_pro, id_trd, no_bcode, tgl_expired, tgl_psd,
                        qty_so, status_barang, awal, masuk_psd, keluar_psd, sisa_psd, gudang,
                        created_at, created_by, updated_at, updated_by
                    ) VALUES (
                        :pro_id, :trd_id, :batch, :expired, :tgl_psd,
                        0, 'active', :qty, :qty, 0, :qty, :gudang,
                        :time, :admin, :time, :admin
                    )
                ");
                $insert_stock->bindParam(':pro_id', $product['id_pro']);
                $insert_stock->bindParam(':trd_id', $kode);
                $insert_stock->bindParam(':batch', $product['no_bcode']);
                $insert_stock->bindParam(':expired', $product['tgl_expired']);
                $insert_stock->bindParam(':tgl_psd', date('Y-m-d'));
                $insert_stock->bindParam(':qty', $product['jumlah_ttd']);
                $insert_stock->bindParam(':gudang', $current['id_inventory_tujuan']);
                $insert_stock->bindParam(':time', $today);
                $insert_stock->bindParam(':admin', $admin);
                $insert_stock->execute();
            }


            // Log history stok
            // Log history stok
            $log_stok = $conn->prepare("
                INSERT INTO produk_stokdetail_log (
                    id_psd, 
                    id_trd,
                    masuk_psdl, 
                    keluar_psdl, 
                    sisa_psdl,
                    tanggal_psdl,
                    jenis_psdl,
                    ket_psdl,
                    created_at,
                    created_by
                ) VALUES (
                    :id_psd,
                    :id_trd,
                    :masuk,
                    0,
                    :sisa,
                    :tanggal,
                    'Transfer In',
                    :catatan,
                    :created_at,
                    :created_by
                )
            ");

            // Jika target ada, gunakan id target, jika tidak, ambil last insert ID
            $target_psd_id = $target ? $target['id_psd'] : $conn->lastInsertId();

            // Hitung sisa stok terbaru untuk gudang tujuan
            $get_sisa = $conn->prepare("SELECT sisa_psd FROM produk_stokdetail WHERE id_psd = :id");
            $get_sisa->bindParam(':id', $target_psd_id);
            $get_sisa->execute();
            $sisa_stok = $get_sisa->fetchColumn();

            // Format tanggal terlebih dahulu
            $current_date = date('Y-m-d');
            $catatan = "Transfer masuk dari gudang " . $current['id_inventory'] . " (ID Transfer: " . $id_ttg . ")";

            $log_stok->bindParam(':id_psd', $target_psd_id);
            $log_stok->bindParam(':id_trd', $kode); // Added this parameter to fix the missing field
            $log_stok->bindParam(':masuk', $product['jumlah_ttd']);
            $log_stok->bindParam(':sisa', $sisa_stok);
            $log_stok->bindParam(':tanggal', $current_date); // Using variable instead of function call
            $log_stok->bindParam(':catatan', $catatan); // Using variable instead of function call
            $log_stok->bindParam(':created_at', $today);
            $log_stok->bindParam(':created_by', $admin);
            $log_stok->execute();
        }
    }

    // Commit transaksi
    $conn->commit();

    // Respon sukses
    $result = [
        'status' => 'success',
        'message' => "Status transfer berhasil diubah menjadi {$new_status}"
    ];
} catch (Exception $e) {
    // Rollback jika terjadi error
    $conn->rollBack();

    // Respon error
    $result = [
        'status' => 'error',
        'message' => "Error: " . $e->getMessage()
    ];
}

// Output respon dalam format JSON
echo json_encode($result);

// Close connection
$conn = $base->close();
