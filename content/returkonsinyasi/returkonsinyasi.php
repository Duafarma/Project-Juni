<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Konsinyasi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Retur Konsinyasi</li>
            </ol>
        </nav>
        <h4 class="content-title">Retur Barang Konsinyasi</h4>
        <p class="mg-b-0 tx-color-03">Kembalikan barang konsinyasi yang tidak terjual ke stok gudang</p>
    </div>
</div>
<div class="content-body">
    <div class="component-section">
        <div class="alert alert-info" role="alert">
            <div class="d-flex align-items-center">
                <i class="fa fa-info-circle mg-r-10" style="font-size: 24px;"></i>
                <div>
                    <strong>Informasi:</strong><br>
                    Pilih faktur konsinyasi yang masih memiliki sisa barang untuk dikembalikan ke gudang. 
                    Hanya faktur dengan status <span class="badge badge-warning">Konsinyasi</span> atau 
                    <span class="badge badge-info">Sebagian</span> yang dapat diretur.
                </div>
            </div>
        </div>

        <div class="row row-sm mg-b-20">
            <div class="col-md-12">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari berdasarkan Nomor Faktur, Outlet...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="fa fa-search"></i> Cari
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-sm">
            <?php
            $search = isset($_GET['cari']) ? $secu->injection($_GET['cari']) : '';
            $where = "WHERE (tfk.status_tfk = 'Konsinyasi' OR tfk.status_tfk = 'Sebagian')";
            if (!empty($search)) {
                $where .= " AND (tfk.kode_tfk LIKE :search OR outl.nama_out LIKE :search)";
            }
            
            $qMaster = "SELECT 
                            tfk.id_tfk,
                            tfk.kode_tfk,
                            tfk.tgl_tfk,
                            tfk.status_tfk,
                            tfk.total_tfk,
                            outl.nama_out,
                            (SELECT SUM(tfd.sisa_tfd) FROM transaksi_fakturdetail_konsinyasi tfd WHERE tfd.id_tfk = tfk.id_tfk) as total_sisa
                        FROM transaksi_faktur_konsinyasi tfk
                        LEFT JOIN outlet outl ON tfk.id_out = outl.id_out
                        $where
                        ORDER BY tfk.tgl_tfk DESC";
            
            try {
                $master = $conn->prepare($qMaster);
                if (!empty($search)) {
                    $searchParam = "%$search%";
                    $master->bindParam(':search', $searchParam, PDO::PARAM_STR);
                }
                $master->execute();
                
                if ($master->rowCount() > 0) {
                    while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                        // Hitung rentang waktu
                        $tglKonsinyasi = new DateTime($hasil['tgl_tfk']);
                        $tglSekarang = new DateTime();
                        $selisih = $tglSekarang->diff($tglKonsinyasi);
                        $hariKonsinyasi = $selisih->days;
                        
                        // Badge color untuk rentang waktu
                        if ($hariKonsinyasi < 30) {
                            $badgeWaktu = 'success';
                        } elseif ($hariKonsinyasi <= 60) {
                            $badgeWaktu = 'warning';
                        } else {
                            $badgeWaktu = 'danger';
                        }
                        
                        // Badge status
                        $badgeStatus = $hasil['status_tfk'] == 'Konsinyasi' ? 'warning' : 'info';
            ?>
            <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mg-t-10">
                <div class="card" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <div class="card-body" style="padding: 20px;">
                        <div class="d-flex justify-content-between align-items-start mg-b-15">
                            <div>
                                <h5 class="mg-b-5" style="font-weight: 600;">
                                    <?php echo $hasil['kode_tfk']; ?>
                                </h5>
                                <p class="tx-color-03 mg-b-0">
                                    <i class="fa fa-store mg-r-5"></i><?php echo $hasil['nama_out']; ?>
                                </p>
                            </div>
                            <span class="badge badge-<?php echo $badgeStatus; ?>" style="font-size: 11px;">
                                <?php echo $hasil['status_tfk']; ?>
                            </span>
                        </div>
                        
                        <div class="mg-b-15">
                            <div class="d-flex justify-content-between tx-13 mg-b-5">
                                <span class="tx-color-03">Tanggal Konsinyasi:</span>
                                <span class="tx-medium"><?php echo date('d/m/Y', strtotime($hasil['tgl_tfk'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between tx-13 mg-b-5">
                                <span class="tx-color-03">Rentang Waktu:</span>
                                <span class="badge badge-<?php echo $badgeWaktu; ?>"><?php echo $hariKonsinyasi; ?> hari</span>
                            </div>
                            <div class="d-flex justify-content-between tx-13 mg-b-5">
                                <span class="tx-color-03">Total Nilai:</span>
                                <span class="tx-medium">Rp <?php echo number_format($hasil['total_tfk'], 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between tx-13">
                                <span class="tx-color-03">Sisa Barang:</span>
                                <span class="tx-medium tx-primary"><?php echo number_format($hasil['total_sisa'], 0, ',', '.'); ?> pcs</span>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="<?php echo "$sistem/fsalesk/v/".base64_encode($hasil['id_tfk']); ?>" 
                               class="btn btn-sm btn-outline-secondary"
                               title="Lihat Detail">
                                <i class="fa fa-eye"></i> Lihat
                            </a>
                            <a href="<?php echo "$sistem/returkonsinyasi/i/".base64_encode($hasil['id_tfk']); ?>" 
                               class="btn btn-sm btn-primary"
                               style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <i class="fa fa-undo"></i> Proses Retur
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
                    }
                } else {
            ?>
            <div class="col-12">
                <div class="alert alert-warning" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-exclamation-triangle mg-r-10" style="font-size: 24px;"></i>
                        <div>
                            <strong>Tidak ada data</strong><br>
                            Tidak ada faktur konsinyasi yang dapat diretur saat ini. 
                            Pastikan ada faktur dengan status Konsinyasi atau Sebagian yang masih memiliki sisa barang.
                        </div>
                    </div>
                </div>
            </div>
            <?php
                }
            } catch (PDOException $e) {
                echo '<div class="col-12"><div class="alert alert-danger">Error: '.$e->getMessage().'</div></div>';
            }
            ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').on('keyup', function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            if (search !== '') {
                window.location.href = '<?php echo $sistem; ?>/returkonsinyasi/cari=' + encodeURIComponent(search);
            } else {
                window.location.href = '<?php echo $sistem; ?>/returkonsinyasi';
            }
        }
    });
});
</script>

<style>
.card:hover {
    transform: translateY(-5px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2) !important;
}
</style>
