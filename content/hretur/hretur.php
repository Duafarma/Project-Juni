<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Konsinyasi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Histori Retur</li>
            </ol>
        </nav>
        <h4 class="content-title">Histori Retur Konsinyasi</h4>
        <p class="mg-b-0 tx-color-03">Daftar riwayat pengembalian barang konsinyasi</p>
    </div>
</div>

<div class="content-body">
    <div class="component-section">
        <div class="row row-sm mg-b-20">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" id="searchInput" placeholder="Cari No. Retur, Outlet, Faktur...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="fa fa-search"></i> Cari
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <tr>
                        <th width="5%">No</th>
                        <th width="15%">No. Retur</th>
                        <th width="12%">Tanggal</th>
                        <th width="15%">No. Faktur Konsinyasi</th>
                        <th width="20%">Outlet</th>
                        <th width="8%" class="text-center">Total Item</th>
                        <th width="10%" class="text-center">Total Qty</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="5%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $search = isset($_GET['cari']) ? $secu->injection($_GET['cari']) : '';
                $where = "WHERE 1=1";
                if (!empty($search)) {
                    $where .= " AND (trk.no_retur LIKE :search OR tfk.kode_tfk LIKE :search OR outl.nama_out LIKE :search)";
                }
                
                $qMaster = "SELECT 
                                trk.*,
                                tfk.kode_tfk,
                                outl.nama_out
                            FROM transaksi_retur_konsinyasi trk
                            LEFT JOIN transaksi_faktur_konsinyasi tfk ON trk.id_tfk = tfk.id_tfk
                            LEFT JOIN outlet outl ON trk.id_out = outl.id_out
                            $where
                            ORDER BY trk.tgl_retur DESC, trk.created_at DESC";
                
                try {
                    $master = $conn->prepare($qMaster);
                    if (!empty($search)) {
                        $searchParam = "%$search%";
                        $master->bindParam(':search', $searchParam, PDO::PARAM_STR);
                    }
                    $master->execute();
                    
                    if ($master->rowCount() > 0) {
                        $no = 1;
                        while($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td>
                            <strong><?php echo $hasil['no_retur']; ?></strong>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($hasil['tgl_retur'])); ?></td>
                        <td>
                            <a href="<?php echo "$sistem/fsalesk/v/".base64_encode($hasil['id_tfk']); ?>" target="_blank">
                                <?php echo $hasil['kode_tfk']; ?>
                            </a>
                        </td>
                        <td><?php echo $hasil['nama_out']; ?></td>
                        <td class="text-center">
                            <span class="badge badge-info"><?php echo $hasil['total_item']; ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-primary"><?php echo number_format($hasil['total_qty'], 0, ',', '.'); ?></span>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-success"><?php echo $hasil['status_trk']; ?></span>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-info" onclick="viewDetail('<?php echo $hasil['id_trk']; ?>')">
                                <i class="fa fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                <?php
                        }
                    } else {
                ?>
                    <tr>
                        <td colspan="9" class="text-center tx-color-03">Tidak ada data histori retur</td>
                    </tr>
                <?php
                    }
                } catch (PDOException $e) {
                    echo '<tr><td colspan="9" class="text-center text-danger">Error: '.$e->getMessage().'</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" id="modalContent">
            <!-- Content akan diload via AJAX -->
        </div>
    </div>
</div>

<script>
function viewDetail(id_trk) {
    $('#modalDetail').modal('show');
    $('#modalContent').html('<div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p class="mg-t-20">Loading...</p></div>');
    
    $.ajax({
        url: '<?php echo $sistem; ?>/modal/returkonsinyasi/detail.php',
        type: 'POST',
        data: { id_trk: id_trk },
        success: function(response) {
            $('#modalContent').html(response);
        },
        error: function() {
            $('#modalContent').html('<div class="modal-body text-center text-danger"><i class="fa fa-times-circle fa-3x"></i><p class="mg-t-20">Error loading data</p></div>');
        }
    });
}

$(document).ready(function() {
    $('#searchInput').on('keyup', function(e) {
        if (e.keyCode === 13) {
            var search = $(this).val();
            if (search !== '') {
                window.location.href = '<?php echo $sistem; ?>/hretur/cari=' + encodeURIComponent(search);
            } else {
                window.location.href = '<?php echo $sistem; ?>/hretur';
            }
        }
    });
});
</script>
