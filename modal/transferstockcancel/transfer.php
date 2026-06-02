<?php
/**
 * Modal Transfer Stok Cancel - Per Faktur (Batch)
 * Transfer semua atau sebagian item dari faktur yang sama ke inventory tujuan
 */
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu   = new Security;
$base   = new DB;
$data   = new Data;
$conn   = $base->open();
$act    = $secu->injection(@$_GET['act']);

switch($act){
    case "form":
        // Form transfer stok per faktur
        $kode_faktur = $secu->injection($_GET['kode_faktur']);
        
        // Get all items from this faktur that are still 'cancel' status
        $qItems = "SELECT 
                        A.*,
                        B.nama_pro,
                        B.kode_produk_jadi
                    FROM produk_stockdetail_cancel AS A
                    LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                    WHERE A.kode_faktur = :kode_faktur AND A.status = 'cancel'
                    ORDER BY B.nama_pro ASC";
        $items = $conn->prepare($qItems);
        $items->bindParam(':kode_faktur', $kode_faktur, PDO::PARAM_STR);
        $items->execute();
        $itemList = $items->fetchAll(PDO::FETCH_ASSOC);
        
        if(empty($itemList)){
            echo '<div class="modal-header bg-warning">
                    <h6 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Tidak Ada Item</h6>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  </div>
                  <div class="modal-body">
                    <div class="alert alert-warning">Tidak ada item yang bisa ditransfer dari faktur ini. Semua item mungkin sudah ditransfer.</div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Tutup</button>
                  </div>';
            exit;
        }
        
        // Get first item for faktur info
        $firstItem = $itemList[0];
        $tglFaktur = !empty($firstItem['tgl_faktur']) ? date('d-m-Y', strtotime($firstItem['tgl_faktur'])) : '-';
        
        // Calculate totals
        $totalItems = count($itemList);
        $totalQty = array_sum(array_column($itemList, 'jumlah_cancel'));
        
        // Generate Nomor Transfer: TRF/STK/001/XX/XXXX (XX = bulan romawi)
        $bulanAngka = date('n');
        $tahun = date('Y');
        $romawiMap = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $bulanRomawi = $romawiMap[$bulanAngka];
        $prefix = "TRF/STK/";
        $suffix = "/$bulanRomawi/$tahun";
        
        $qLastNo = "SELECT nomor_transfer FROM transfer_stockcancel 
                    WHERE nomor_transfer LIKE :pattern 
                    ORDER BY id_tsc DESC LIMIT 1";
        $stmtLastNo = $conn->prepare($qLastNo);
        $pattern = $prefix . '%' . $suffix;
        $stmtLastNo->bindParam(':pattern', $pattern, PDO::PARAM_STR);
        $stmtLastNo->execute();
        $lastNo = $stmtLastNo->fetch(PDO::FETCH_ASSOC);
        
        if($lastNo){
            preg_match('/TRF\/STK\/(\d+)\//', $lastNo['nomor_transfer'], $matches);
            $nextNum = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $nextNum = 1;
        }
        $nomor_transfer = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT) . $suffix;
?>
<div class="modal-header bg-primary text-white">
    <h6 class="modal-title"><i class="fa fa-exchange-alt"></i> Transfer Stok Cancel - <?php echo($kode_faktur); ?></h6>
    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<form id="formtransaksi" action="#" method="post" autocomplete="off">
    <input type="hidden" name="nmenu" id="nmenu" value="transferstockcancel" readonly="readonly" />
    <input type="hidden" name="nact" id="nact" value="transfer_batch" readonly="readonly" />
    <input type="hidden" name="kode_faktur" value="<?php echo($kode_faktur); ?>" readonly="readonly" />
    <input type="hidden" name="tgl_faktur" value="<?php echo($firstItem['tgl_faktur']); ?>" readonly="readonly" />
    <div class="modal-body">
        <!-- Info Faktur -->
        <div class="card mb-3">
            <div class="card-header bg-light py-2">
                <strong><i class="fa fa-file-invoice"></i> Info Faktur</strong>
            </div>
            <div class="card-body py-2">
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-muted">Kode Faktur</small><br>
                        <strong><?php echo($kode_faktur); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Tanggal Faktur</small><br>
                        <strong><?php echo($tglFaktur); ?></strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted">Total Item / Qty</small><br>
                        <strong><?php echo($totalItems); ?> item / <?php echo($data->angka($totalQty)); ?> pcs</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info py-2 mb-3">
            <i class="fa fa-info-circle"></i> <strong>Info:</strong> Stok akan otomatis ditransfer kembali ke <strong>gudang asal</strong> sesuai data pada tabel cancel.
        </div>
        
        <div class="row mb-3">
            <div class="col-md-6">
                <label><strong>Nomor Transfer</strong></label>
                <input type="text" name="nomor_transfer" class="form-control bg-light" value="<?php echo($nomor_transfer); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label><strong>Keterangan Transfer</strong></label>
                <input type="text" name="keterangan_transfer" class="form-control" placeholder="Keterangan (opsional)..." />
            </div>
        </div>
        
        <!-- Daftar Item -->
        <div class="card">
            <div class="card-header bg-light py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <strong><i class="fa fa-boxes"></i> Pilih Item untuk Transfer</strong>
                    <div>
                        <button type="button" class="btn btn-xs btn-outline-primary" onclick="selectAllItems()"><i class="fa fa-check-square"></i> Pilih Semua</button>
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="deselectAllItems()"><i class="fa fa-square"></i> Batal Pilih</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="bg-light" style="position: sticky; top: 0;">
                            <tr>
                                <th width="40"><center><input type="checkbox" id="checkAll" onchange="toggleAllItems(this)" checked></center></th>
                                <th>Produk</th>
                                <th>Batch/Barcode</th>
                                <th><center>Expired</center></th>
                                <th><center>Qty Cancel</center></th>
                                <th><center>Qty Transfer</center></th>
                                <th>Gudang Asal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 0;
                            foreach($itemList as $item): 
                                $no++;
                                $tglExp = !empty($item['tgl_expired']) ? date('d-m-Y', strtotime($item['tgl_expired'])) : '-';
                            ?>
                            <tr>
                                <td>
                                    <center>
                                        <input type="checkbox" name="items[<?php echo($no); ?>][selected]" value="1" class="item-checkbox" checked>
                                        <input type="hidden" name="items[<?php echo($no); ?>][id_psc]" value="<?php echo($item['id_psc']); ?>">
                                        <input type="hidden" name="items[<?php echo($no); ?>][id_pro]" value="<?php echo($item['id_pro']); ?>">
                                        <input type="hidden" name="items[<?php echo($no); ?>][id_trd]" value="<?php echo($item['id_trd']); ?>">
                                        <input type="hidden" name="items[<?php echo($no); ?>][no_bcode]" value="<?php echo($item['no_bcode']); ?>">
                                        <input type="hidden" name="items[<?php echo($no); ?>][tgl_expired]" value="<?php echo($item['tgl_expired']); ?>">
                                        <input type="hidden" name="items[<?php echo($no); ?>][jumlah_cancel]" value="<?php echo($item['jumlah_cancel']); ?>">
                                        <input type="hidden" name="items[<?php echo($no); ?>][gudang]" value="<?php echo($item['gudang']); ?>">
                                    </center>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo($item['kode_produk_jadi']); ?></small><br>
                                    <?php echo($item['nama_pro']); ?>
                                </td>
                                <td><?php echo($item['no_bcode']); ?></td>
                                <td><center><?php echo($tglExp); ?></center></td>
                                <td><center><span class="badge badge-danger"><?php echo($data->angka($item['jumlah_cancel'])); ?></span></center></td>
                                <td>
                                    <center>
                                        <input type="number" name="items[<?php echo($no); ?>][jumlah_transfer]" 
                                               class="form-control form-control-sm text-center qty-transfer" 
                                               value="<?php echo($item['jumlah_cancel']); ?>" 
                                               min="1" max="<?php echo($item['jumlah_cancel']); ?>" 
                                               style="width: 80px;"
                                               onchange="updateTotal()">
                                    </center>
                                </td>
                                <td><small><?php echo($item['gudang']); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <th colspan="4" class="text-right">Total Transfer:</th>
                                <th><center><span id="totalItemSelected"><?php echo($totalItems); ?></span> item</center></th>
                                <th><center><span id="totalQtyTransfer" class="badge badge-primary"><?php echo($data->angka($totalQty)); ?></span> pcs</center></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal"><i class="fa fa-times"></i> Batal</button>
        <button type="submit" id="bsave" class="btn btn-primary btn-xs"><i class="fa fa-check"></i> Transfer Stok Terpilih</button>
    </div>
</form>

<script>
function toggleAllItems(checkbox) {
    $('.item-checkbox').prop('checked', checkbox.checked);
    updateTotal();
}

function selectAllItems() {
    $('#checkAll').prop('checked', true);
    $('.item-checkbox').prop('checked', true);
    updateTotal();
}

function deselectAllItems() {
    $('#checkAll').prop('checked', false);
    $('.item-checkbox').prop('checked', false);
    updateTotal();
}

function updateTotal() {
    var totalItems = 0;
    var totalQty = 0;
    
    $('.item-checkbox:checked').each(function() {
        totalItems++;
        var row = $(this).closest('tr');
        var qtyTransfer = parseInt(row.find('.qty-transfer').val()) || 0;
        totalQty += qtyTransfer;
    });
    
    $('#totalItemSelected').text(totalItems);
    $('#totalQtyTransfer').text(totalQty.toLocaleString('id-ID'));
    
    // Update checkAll state
    var allChecked = $('.item-checkbox:checked').length === $('.item-checkbox').length;
    $('#checkAll').prop('checked', allChecked);
}

// Initialize
$(document).ready(function() {
    updateTotal();
    
    $('.item-checkbox').on('change', updateTotal);
});
</script>
<?php
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
