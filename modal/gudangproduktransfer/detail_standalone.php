<?php
// filepath: c:\Development\laragon\www\192.268.908.09\modal\gudangproduktransfer\detail_standalone.php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();
$sistem = $data->sistem('url_sis');

// Ambil parameter
$kode = $secu->injection($_GET['keycode']);

// Query data transfer
$read = $conn->prepare("SELECT 
    t.*, 
    mi_asal.nama_inventory as gudang_asal_nama,
    mi_tujuan.nama_inventory as gudang_tujuan_nama,
    a_created.nama_adm as created_by_name,
    a_updated.nama_adm as updated_by_name
FROM transfer_gudang t 
LEFT JOIN master_inventory mi_asal ON t.id_inventory = mi_asal.id_inventory
LEFT JOIN master_inventory mi_tujuan ON t.id_inventory_tujuan = mi_tujuan.id_inventory
LEFT JOIN adminz a_created ON t.created_by = a_created.id_adm
LEFT JOIN adminz a_updated ON t.updated_by = a_updated.id_adm
WHERE t.id_ttg=:kode");
$read->bindParam(':kode', $kode, PDO::PARAM_STR);
$read->execute();
$view = $read->fetch(PDO::FETCH_ASSOC);

// Get details
$detail = $conn->prepare("SELECT 
    td.*, 
    p.nama_pro,
    p.kode_pro,
    ps.tgl_expired,
    ps.no_bcode
FROM transfer_gudangdetail td
LEFT JOIN produk p ON td.id_pro = p.id_pro
LEFT JOIN produk_stokdetail ps ON td.id_psd = ps.id_psd
WHERE td.id_ttg=:kode
ORDER BY p.nama_pro ASC");
$detail->bindParam(':kode', $kode, PDO::PARAM_STR);
$detail->execute();
$details = $detail->fetchAll(PDO::FETCH_ASSOC);

// Fungsi untuk warna status
function getStatusColor($status)
{
    switch ($status) {
        case 'Draft':
            return 'secondary';
        case 'Process':
            return 'primary';
        case 'Delivered':
            return 'info';
        case 'Completed':
            return 'success';
        case 'Canceled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transfer Gudang - <?= $view['kode_ttg'] ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            padding: 20px;
        }

        .header-title {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .badge {
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="header-title d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Transfer Gudang - <?= $view['kode_ttg'] ?></h5>
            <span class="badge badge-<?= getStatusColor($view['status_ttg']) ?>"><?= $view['status_ttg'] ?></span>
        </div>

        <!-- Transfer info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="40%" class="font-weight-medium text-muted">Tanggal</td>
                        <td width="5%">:</td>
                        <td class="font-weight-medium"><?= date('d M Y', strtotime($view['tgl_ttg'])) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-medium text-muted">Gudang Asal</td>
                        <td>:</td>
                        <td class="font-weight-medium"><?= $view['gudang_asal_nama'] ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-medium text-muted">Gudang Tujuan</td>
                        <td>:</td>
                        <td class="font-weight-medium"><?= $view['gudang_tujuan_nama'] ?: '-' ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td width="40%" class="font-weight-medium text-muted">Dibuat Oleh</td>
                        <td width="5%">:</td>
                        <td class="font-weight-medium"><?= $view['created_by_name'] ?: '-' ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-medium text-muted">Tanggal Dibuat</td>
                        <td>:</td>
                        <td class="font-weight-medium"><?= date('d M Y H:i', strtotime($view['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td class="font-weight-medium text-muted">Terakhir Diupdate</td>
                        <td>:</td>
                        <td class="font-weight-medium">
                            <?= !empty($view['updated_at']) ? date('d M Y H:i', strtotime($view['updated_at'])) . ' oleh ' . $view['updated_by_name'] : '-' ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Keterangan -->
        <?php if (!empty($view['ket_ttg'])): ?>
            <div class="mb-4">
                <h6 class="mb-2 font-weight-bold">Keterangan:</h6>
                <p class="bg-light p-3 rounded mb-0"><?= nl2br(htmlspecialchars($view['ket_ttg'])) ?></p>
            </div>
        <?php endif; ?>

        <!-- Detail produk dengan styling yang lebih baik -->
        <div class="mb-3">
            <h6 class="mb-3 font-weight-bold">Daftar Produk</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th class="text-center" width="5%">No</th>
                            <th>Kode Produk</th>
                            <th>Nama Produk</th>
                            <th width="15%">Batch</th>
                            <th width="15%">Kadaluarsa</th>
                            <th class="text-right" width="12%">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($details) > 0): ?>
                            <?php foreach ($details as $i => $item): ?>
                                <tr>
                                    <td class="text-center"><?= $i + 1 ?></td>
                                    <td><?= $item['kode_pro'] ?: '-' ?></td>
                                    <td><?= $item['nama_pro'] ?></td>
                                    <td><?= $item['no_batch'] ?: '-' ?></td>
                                    <td><?= !empty($item['tgl_expired']) ? date('d M Y', strtotime($item['tgl_expired'])) : '-' ?></td>
                                    <td class="text-right"><?= number_format($item['jumlah_ttd']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Total -->
                            <tr class="bg-light font-weight-bold">
                                <td colspan="5" class="text-right">Total Item:</td>
                                <td class="text-right">
                                    <?= number_format(array_sum(array_column($details, 'jumlah_ttd'))) ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data produk</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tombol aksi -->
        <div class="text-center mt-4">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fa fa-print mr-1"></i> Cetak
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.close()">
                <i class="fa fa-times mr-1"></i> Tutup
            </button>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php $conn = $base->close(); ?>