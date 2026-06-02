<?php
/**
 * Modal Detail Transfer Stock Cancel - View Transfer History
 * Menampilkan detail transfer dari tabel transfer_stockcancel
 */
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu   = new Security;
$base   = new DB;
$data   = new Data;
$conn   = $base->open();

$act    = $secu->injection(@$_GET['act']);
$id_tsc = $secu->injection(@$_GET['id_tsc']);

switch($act){
    case 'view':
        if(empty($id_tsc)){
            echo '<div class="modal-header bg-danger text-white">
                    <h6 class="modal-title">Error</h6>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <div class="alert alert-danger">ID Transfer tidak ditemukan</div>
                  </div>';
            exit;
        }
        
        // Query header transfer
        $qHeader = "SELECT 
                        A.*,
                        C.nama_adm AS transfer_by_name
                    FROM transfer_stockcancel AS A
                    LEFT JOIN adminz AS C ON A.transfer_by = C.id_adm
                    WHERE A.id_tsc = :id_tsc";
        $header = $conn->prepare($qHeader);
        $header->bindParam(':id_tsc', $id_tsc, PDO::PARAM_STR);
        $header->execute();
        $headerData = $header->fetch(PDO::FETCH_ASSOC);
        
        if($headerData){
            // Query detail items
            $qDetail = "SELECT 
                            A.*,
                            B.nama_pro,
                            B.kode_produk_jadi,
                            C.gudang AS gudang_tujuan
                        FROM transfer_stockcancel_detail AS A
                        LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                        LEFT JOIN produk_stokdetail AS C ON A.id_psd_baru = C.id_psd
                        WHERE A.id_tsc = :id_tsc
                        ORDER BY B.nama_pro ASC";
            $detail = $conn->prepare($qDetail);
            $detail->bindParam(':id_tsc', $id_tsc, PDO::PARAM_STR);
            $detail->execute();
            $detailList = $detail->fetchAll(PDO::FETCH_ASSOC);
            
            $tglFaktur = !empty($headerData['tgl_faktur']) ? date('d-m-Y', strtotime($headerData['tgl_faktur'])) : '-';
            $transferAt = !empty($headerData['transfer_at']) ? date('d-m-Y H:i:s', strtotime($headerData['transfer_at'])) : '-';
?>
<div class="modal-header bg-info text-white">
    <h6 class="modal-title"><i class="fa fa-exchange-alt"></i> Detail Transfer: <?php echo $headerData['nomor_transfer'] ?: $headerData['kode_transfer']; ?></h6>
    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
</div>
<div class="modal-body">
    <div class="row mb-3">
        <div class="col-md-6">
            <h6 class="text-primary mb-2"><i class="fa fa-info-circle"></i> Informasi Transfer</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td width="40%"><strong>Nomor Transfer</strong></td>
                    <td>: <?php echo $headerData['nomor_transfer'] ?: $headerData['kode_transfer']; ?></td>
                </tr>
                <tr>
                    <td><strong>Kode Faktur</strong></td>
                    <td>: <?php echo $headerData['kode_faktur']; ?></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Faktur</strong></td>
                    <td>: <?php echo $tglFaktur; ?></td>
                </tr>
                <tr>
                    <td><strong>Inventory Tujuan</strong></td>
                    <td>: <span class="badge badge-success"><?php 
                        if($headerData['id_inventory_tujuan'] == 'AUTO'){
                            echo '<i class="fa fa-warehouse"></i> Gudang Asal Masing-masing';
                        } else {
                            echo $headerData['id_inventory_tujuan'];
                        }
                    ?></span></td>
                </tr>
            </table>
        </div>
        <div class="col-md-6">
            <h6 class="text-info mb-2"><i class="fa fa-chart-bar"></i> Summary</h6>
            <table class="table table-sm table-borderless">
                <tr>
                    <td width="40%"><strong>Total Item</strong></td>
                    <td>: <span class="badge badge-primary"><?php echo $data->angka($headerData['total_item']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Total Qty</strong></td>
                    <td>: <span class="badge badge-success"><?php echo $data->angka($headerData['total_qty']); ?></span></td>
                </tr>
                <tr>
                    <td><strong>Tanggal Transfer</strong></td>
                    <td>: <?php echo $transferAt; ?></td>
                </tr>
                <tr>
                    <td><strong>Transfer By</strong></td>
                    <td>: <?php echo $headerData['transfer_by_name'] ?: $headerData['transfer_by']; ?></td>
                </tr>
                <tr>
                    <td><strong>Status</strong></td>
                    <td>: 
                        <?php if($headerData['status_transfer'] == 'completed'): ?>
                        <span class="badge badge-success"><i class="fa fa-check"></i> Completed</span>
                        <?php elseif($headerData['status_transfer'] == 'cancelled'): ?>
                        <span class="badge badge-danger"><i class="fa fa-times"></i> Cancelled</span>
                        <?php else: ?>
                        <span class="badge badge-secondary"><?php echo ucfirst($headerData['status_transfer']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php if(!empty($headerData['keterangan_transfer'])): ?>
    <div class="alert alert-light border mb-3">
        <strong><i class="fa fa-comment"></i> Keterangan:</strong><br>
        <?php echo nl2br(htmlspecialchars($headerData['keterangan_transfer'])); ?>
    </div>
    <?php endif; ?>
    
    <hr>
    
    <h6 class="text-success mb-2"><i class="fa fa-boxes"></i> Daftar Item Transfer</h6>
    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
        <table class="table table-sm table-hover table-bordered">
            <thead class="bg-light" style="position: sticky; top: 0;">
                <tr>
                    <th width="30">#</th>
                    <th>Produk</th>
                    <th>Batch/Barcode</th>
                    <th><center>Expired</center></th>
                    <th><center>Qty Transfer</center></th>
                    <?php if($headerData['id_inventory_tujuan'] == 'AUTO'): ?>
                    <th>Gudang Tujuan</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 0;
                foreach($detailList as $item): 
                    $no++;
                    $tglExp = !empty($item['tgl_expired']) ? date('d-m-Y', strtotime($item['tgl_expired'])) : '-';
                ?>
                <tr>
                    <td><?php echo $no; ?></td>
                    <td>
                        <small class="text-muted"><?php echo $item['kode_produk_jadi']; ?></small><br>
                        <?php echo $item['nama_pro']; ?>
                    </td>
                    <td><?php echo $item['no_bcode']; ?></td>
                    <td><center><?php echo $tglExp; ?></center></td>
                    <td><center><strong class="text-success"><?php echo $data->angka($item['jumlah_transfer']); ?></strong></center></td>
                    <?php if($headerData['id_inventory_tujuan'] == 'AUTO'): ?>
                    <td><small><?php echo $item['gudang_tujuan']; ?></small></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="4" class="text-right">Total:</th>
                    <th><center><strong><?php echo $data->angka($headerData['total_qty']); ?></strong></center></th>
                    <?php if($headerData['id_inventory_tujuan'] == 'AUTO'): ?>
                    <th></th>
                    <?php endif; ?>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Tutup</button>
</div>
<?php
        } else {
            echo '<div class="modal-header bg-warning">
                    <h6 class="modal-title">Data Tidak Ditemukan</h6>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Data transfer tidak ditemukan</div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Tutup</button>
                  </div>';
        }
        break;
        
    default:
        echo '<div class="modal-header bg-danger text-white">
                <h6 class="modal-title">Error</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
              </div>
              <div class="modal-body">
                <div class="alert alert-danger">Action tidak valid</div>
              </div>';
        break;
}
?>
