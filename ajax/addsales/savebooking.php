<?php
/**
 * savebooking.php
 * Menyimpan/menghapus booking sementara (draft) saat user memilih produk
 * di halaman input item faktur SEBELUM klik Simpan.
 * 
 * Digunakan agar faktur lain bisa melihat stok yang sudah "dibooking"
 * meskipun faktur sumber belum disimpan finalnya.
 */

header('Content-Type: application/json');

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$conn = $base->open();

$action = isset($_POST['action']) ? $secu->injection($_POST['action']) : '';
$id_tfk = isset($_POST['id_tfk']) ? $secu->injection($_POST['id_tfk']) : '';
$id_psd = isset($_POST['id_psd']) ? $secu->injection($_POST['id_psd']) : '';
$jumlah = isset($_POST['jumlah']) ? (int)$_POST['jumlah'] : 1;

if (empty($id_tfk) || empty($action)) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter tidak lengkap']);
    exit;
}

try {
    switch ($action) {
        case 'save':
            // INSERT atau UPDATE booking untuk batch ini
            if (empty($id_psd)) {
                echo json_encode(['status' => 'error', 'message' => 'id_psd kosong']);
                exit;
            }
            $jumlah = max(1, $jumlah);
            $stmt = $conn->prepare("
                INSERT INTO transaksi_booking_draft (id_tfk, id_psd, jumlah, created_at, updated_at)
                VALUES (:id_tfk, :id_psd, :jumlah, NOW(), NOW())
                ON DUPLICATE KEY UPDATE jumlah = :jumlah, updated_at = NOW()
            ");
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->bindParam(':id_psd', $id_psd);
            $stmt->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Booking tersimpan']);
            break;

        case 'update':
            // Update jumlah booking (dipanggil saat user mengubah qty)
            if (empty($id_psd)) {
                echo json_encode(['status' => 'error', 'message' => 'id_psd kosong']);
                exit;
            }
            $jumlah = max(1, $jumlah);
            $stmt = $conn->prepare("
                UPDATE transaksi_booking_draft 
                SET jumlah = :jumlah, updated_at = NOW()
                WHERE id_tfk = :id_tfk AND id_psd = :id_psd
            ");
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->bindParam(':id_psd', $id_psd);
            $stmt->bindParam(':jumlah', $jumlah, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Booking diupdate']);
            break;

        case 'delete':
            // Hapus booking untuk batch tertentu dari faktur ini
            if (empty($id_psd)) {
                echo json_encode(['status' => 'error', 'message' => 'id_psd kosong']);
                exit;
            }
            $stmt = $conn->prepare("
                DELETE FROM transaksi_booking_draft 
                WHERE id_tfk = :id_tfk AND id_psd = :id_psd
            ");
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->bindParam(':id_psd', $id_psd);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Booking dihapus']);
            break;

        case 'clear':
            // Hapus SEMUA booking dari faktur ini (dipanggil setelah Simpan berhasil)
            $stmt = $conn->prepare("
                DELETE FROM transaksi_booking_draft 
                WHERE id_tfk = :id_tfk
            ");
            $stmt->bindParam(':id_tfk', $id_tfk);
            $stmt->execute();
            echo json_encode(['status' => 'success', 'message' => 'Semua booking dihapus']);
            break;

        case 'cleanup':
            // Hapus booking lama (lebih dari 24 jam) - housekeeping
            $stmt = $conn->prepare("
                DELETE FROM transaksi_booking_draft 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $deleted = $stmt->rowCount();
            echo json_encode(['status' => 'success', 'message' => "Hapus $deleted booking kadaluarsa"]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenal']);
            break;
    }
} catch (PDOException $e) {
    // Jika tabel belum ada, tidak perlu error fatal - hanya log saja
    error_log('savebooking error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
}

$base->close();
?>
