<?php
ob_start();
error_reporting(0);

require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

header('Content-Type: application/json');

$produk = isset($_POST['produk']) ? $secu->injection(trim($_POST['produk'])) : '';
$bulan  = isset($_POST['bulan'])  ? $secu->injection(trim($_POST['bulan']))  : '';
$tahun  = isset($_POST['tahun'])  ? $secu->injection(trim($_POST['tahun']))  : '';

// Validate numerics
if($bulan !== '' && (!is_numeric($bulan) || $bulan < 1 || $bulan > 12)) $bulan = '';
if($tahun !== '' && (!is_numeric($tahun) || strlen($tahun) !== 4)) $tahun = '';

try {
    // Jika ada produk, jalankan stored procedure update report_produk dulu
    if($produk !== '') {
        $stmt_sp = $conn->query("CALL reportproduk('".addslashes($produk)."')");
        // Consume semua result sets agar PDO bisa lanjut query berikutnya
        if($stmt_sp) {
            do { $stmt_sp->fetchAll(); } while($stmt_sp->nextRowset());
            $stmt_sp->closeCursor();
        }
    }

    // Build query
    $where  = [];
    $params = [];

    if($produk !== '') {
        $where[]  = 'id_pro = :produk';
        $params[':produk'] = $produk;
    }
    if($bulan !== '') {
        $where[]  = 'MONTH(tgl_rpo) = :bulan';
        $params[':bulan'] = (int)$bulan;
    }
    if($tahun !== '') {
        $where[]  = 'YEAR(tgl_rpo) = :tahun';
        $params[':tahun'] = (int)$tahun;
    }

    // Jika tidak ada filter produk dan tidak ada filter waktu, kembalikan pesan
    if(empty($where)) {
        // Tampilkan semua data dengan limit untuk mencegah overload
        $sql = "SELECT jenis_rpo, bcode_rpo, mitra_rpo, kode_rpo, gudang, faktur_rpo, tgl_rpo, jumlah_rpo, id_pro
                FROM report_produk
                ORDER BY tgl_rpo ASC
                LIMIT 500";
        $stmt = $conn->query($sql);
    } else {
        $sql = "SELECT jenis_rpo, bcode_rpo, mitra_rpo, kode_rpo, gudang, faktur_rpo, tgl_rpo, jumlah_rpo, id_pro
                FROM report_produk
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tgl_rpo ASC";
        $stmt = $conn->prepare($sql);
        foreach($params as $key => $val) {
            $type = is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $val, $type);
        }
        $stmt->execute();
    }

    $rows      = [];
    $total_in  = 0;
    $total_out = 0;

    $jenis_in  = ['Order','TF-IN','IN-Konsinyasi','Transfer-StockCancel'];
    $jenis_out = ['Sales','TF-OUT','Donasi','Pinjaman','Retur','Lain-Lain','Retur-P','Konsinyasi'];

    // ---- Stok Awal dari tabel so (H-1 bulan filter) ----
    $tgl_h1 = null;
    if($bulan !== '' && $tahun !== '') {
        $tgl_h1 = date('Y-m-d', strtotime($tahun.'-'.str_pad($bulan, 2, '0', STR_PAD_LEFT).'-01 -1 day'));
    } elseif($tahun !== '') {
        $tgl_h1 = ($tahun - 1).'-12-31';
    }

    if($tgl_h1 !== null) {
        $so_produk_cond = '';
        $so_params = [':tgl_h1' => $tgl_h1];
        if($produk !== '') {
            $so_produk_cond      = 'AND TRIM(id_pro) = TRIM(:so_produk)';
            $so_params[':so_produk'] = $produk;
        }
        $so_sql = "SELECT COALESCE(SUM(qty_so), 0) as stok_awal
                   FROM so
                   WHERE DATE(created_at) = :tgl_h1
                   $so_produk_cond";
        $so_stmt = $conn->prepare($so_sql);
        foreach($so_params as $k => $v) $so_stmt->bindValue($k, $v, PDO::PARAM_STR);
        $so_stmt->execute();
        $so_row    = $so_stmt->fetch(PDO::FETCH_ASSOC);
        $stok_awal = (int)$so_row['stok_awal'];
        $total_in += $stok_awal;
        $rows[] = [
            'jenis'     => 'Stock Awal',
            'mitra'     => '-',
            'kode'      => '-',
            'faktur'    => '-',
            'tanggal'   => $tgl_h1,
            'batchcode' => '-',
            'gudang'    => '-',
            'in_qty'    => number_format($stok_awal, 0, ',', '.'),
            'out_qty'   => '0',
        ];
    }
    // ---- End Stok Awal ----

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $in  = in_array($row['jenis_rpo'], $jenis_in)  ? (int)$row['jumlah_rpo'] : 0;
        $out = in_array($row['jenis_rpo'], $jenis_out) ? (int)$row['jumlah_rpo'] : 0;

        if($in === 0 && $out === 0) continue;

        $total_in  += $in;
        $total_out += $out;

        $label_map = ['Order' => 'Pembelian', 'Sales' => 'Penjualan'];
        $jenis_display = isset($label_map[$row['jenis_rpo']]) ? $label_map[$row['jenis_rpo']] : $row['jenis_rpo'];
        $rows[] = [
            'jenis'     => htmlspecialchars($jenis_display),
            'mitra'     => htmlspecialchars($row['mitra_rpo']),
            'kode'      => htmlspecialchars($row['kode_rpo']),
            'faktur'    => htmlspecialchars($row['faktur_rpo']),
            'tanggal'   => htmlspecialchars($row['tgl_rpo']),
            'batchcode' => htmlspecialchars($row['bcode_rpo']),
            'gudang'    => htmlspecialchars($row['gudang']),
            'in_qty'    => number_format($in, 0, ',', '.'),
            'out_qty'   => number_format($out, 0, ',', '.'),
        ];
    }

    ob_clean();
    echo json_encode([
        'status'     => 'ok',
        'total_rows' => count($rows),
        'total_in'   => number_format($total_in,  0, ',', '.'),
        'total_out'  => number_format($total_out, 0, ',', '.'),
        'balance'    => number_format($total_in - $total_out, 0, ',', '.'),
        'rows'       => $rows,
    ]);

} catch(Exception $e){
    ob_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage(),
    ]);
}
?>
