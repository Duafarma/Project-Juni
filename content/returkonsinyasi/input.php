<?php
$kode = base64_decode($secu->injection($_GET['keycode']));

// Get faktur konsinyasi data
$qFaktur = "SELECT 
                tfk.*,
                outl.nama_out,
                ola.kantor_ola as alamat_kantor,
                ola.pengiriman_ola as alamat_pengiriman
            FROM transaksi_faktur_konsinyasi tfk
            LEFT JOIN outlet outl ON tfk.id_out = outl.id_out
            LEFT JOIN outlet_alamat ola ON outl.id_out = ola.id_out
            WHERE tfk.id_tfk = :kode";
$readFaktur = $conn->prepare($qFaktur);
$readFaktur->bindParam(':kode', $kode, PDO::PARAM_STR);
$readFaktur->execute();
$faktur = $readFaktur->fetch(PDO::FETCH_ASSOC);

if (!$faktur) {
    echo '<script>window.location.href="'.$sistem.'/returkonsinyasi";</script>';
    exit;
}

// Get detail items yang masih ada sisa
$qDetail = "SELECT 
                tfd.*,
                pro.nama_pro,
                pro.kode_pro,
                kpr.nama_kpr,
                kpr.satuan_kpr,
                psd.no_bcode,
                psd.gudang,
                psdk.sisa_psd as stok_konsinyasi
            FROM transaksi_fakturdetail_konsinyasi tfd
            LEFT JOIN produk pro ON tfd.id_pro = pro.id_pro
            LEFT JOIN kategori_produk kpr ON pro.id_kpr = kpr.id_kpr
            LEFT JOIN produk_stokdetail psd ON tfd.id_psd = psd.id_psd
            LEFT JOIN produk_stokdetail_konsinyasi psdk ON tfd.id_psd = psdk.id_psd AND psdk.id_tfk = :kode
            WHERE tfd.id_tfk = :kode AND tfd.sisa_tfd > 0
            ORDER BY pro.nama_pro ASC";
$readDetail = $conn->prepare($qDetail);
$readDetail->bindParam(':kode', $kode, PDO::PARAM_STR);
$readDetail->execute();
?>

<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/returkonsinyasi">Retur Konsinyasi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Proses Retur</li>
            </ol>
        </nav>
        <h4 class="content-title">Proses Retur Barang Konsinyasi</h4>
        <p class="mg-b-0 tx-color-03">Pilih item dan jumlah yang akan dikembalikan ke gudang</p>
    </div>
</div>

<div class="content-body">
    <form id="formRetur" method="POST" action="<?php echo $sistem; ?>/modal/returkonsinyasi/action.php">
        <input type="hidden" name="menu" value="retur">
        <input type="hidden" name="id_tfk" value="<?php echo $faktur['id_tfk']; ?>">
        
        <!-- Info Faktur -->
        <div class="card mg-b-20" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 10px 10px 0 0;">
                <h6 class="mg-b-0" style="font-weight: 600;">
                    <i class="fa fa-file-invoice mg-r-10"></i>Informasi Faktur Konsinyasi
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless tx-13">
                            <tr>
                                <td width="40%" class="tx-color-03">No. Faktur</td>
                                <td width="5%">:</td>
                                <td class="tx-medium"><?php echo $faktur['kode_tfk']; ?></td>
                            </tr>
                            <tr>
                                <td class="tx-color-03">Tanggal</td>
                                <td>:</td>
                                <td class="tx-medium"><?php echo date('d/m/Y', strtotime($faktur['tgl_tfk'])); ?></td>
                            </tr>
                            <tr>
                                <td class="tx-color-03">Status</td>
                                <td>:</td>
                                <td>
                                    <span class="badge badge-<?php echo $faktur['status_tfk'] == 'Konsinyasi' ? 'warning' : 'info'; ?>">
                                        <?php echo $faktur['status_tfk']; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless tx-13">
                            <tr>
                                <td width="40%" class="tx-color-03">Outlet</td>
                                <td width="5%">:</td>
                                <td class="tx-medium"><?php echo $faktur['nama_out']; ?></td>
                            </tr>
                            <tr>
                                <td class="tx-color-03">Alamat</td>
                                <td>:</td>
                                <td class="tx-medium">
                                    <?php 
                                    $alamat = '';
                                    if (!empty($faktur['alamat_pengiriman'])) {
                                        $alamat = $faktur['alamat_pengiriman'];
                                    } elseif (!empty($faktur['alamat_kantor'])) {
                                        $alamat = $faktur['alamat_kantor'];
                                    } else {
                                        $alamat = '-';
                                    }
                                    echo $alamat;
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td class="tx-color-03">Total Nilai</td>
                                <td>:</td>
                                <td class="tx-medium">Rp <?php echo number_format($faktur['total_tfk'], 0, ',', '.'); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Item -->
        <div class="card" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-header bg-primary" style="border-radius: 10px 10px 0 0;">
                <h6 class="mg-b-0 text-white" style="font-weight: 600;">
                    <i class="fa fa-boxes mg-r-10"></i>Pilih Item untuk Diretur
                </h6>
            </div>
            <div class="card-body" style="padding: 0;">
                <div class="table-responsive">
                    <table class="table table-hover mg-b-0">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th width="5%" class="text-center">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th width="20%">Produk</th>
                                <th width="15%">Batchcode</th>
                                <th width="10%">Gudang</th>
                                <th width="10%" class="text-center">Stok Konsinyasi</th>
                                <th width="10%" class="text-center">Terjual</th>
                                <th width="10%" class="text-center">Sisa</th>
                                <th width="15%" class="text-center">Jumlah Retur</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        if ($readDetail->rowCount() > 0) {
                            while($item = $readDetail->fetch(PDO::FETCH_ASSOC)) {
                        ?>
                            <tr class="item-row">
                                <td class="text-center">
                                    <input type="checkbox" class="item-check" name="selected_items[]" value="<?php echo $item['id_tfd']; ?>">
                                    <input type="hidden" name="id_psd[<?php echo $item['id_tfd']; ?>]" value="<?php echo $item['id_psd']; ?>">
                                    <input type="hidden" name="max_qty[<?php echo $item['id_tfd']; ?>]" value="<?php echo $item['sisa_tfd']; ?>">
                                </td>
                                <td>
                                    <div class="tx-medium"><?php echo $item['nama_pro']; ?></div>
                                    <small class="tx-color-03"><?php echo $item['kode_pro']; ?> | <?php echo $item['nama_kpr']; ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-secondary"><?php echo $item['no_bcode']; ?></span>
                                </td>
                                <td><?php echo $item['gudang']; ?></td>
                                <td class="text-center">
                                    <span class="badge badge-info"><?php echo number_format($item['stok_konsinyasi'], 0, ',', '.'); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success"><?php echo number_format($item['terjual_tfd'], 0, ',', '.'); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-primary"><?php echo number_format($item['sisa_tfd'], 0, ',', '.'); ?></span>
                                </td>
                                <td>
                                    <input type="number" 
                                           class="form-control form-control-sm text-center qty-input" 
                                           name="qty_retur[<?php echo $item['id_tfd']; ?>]" 
                                           min="0" 
                                           max="<?php echo $item['sisa_tfd']; ?>" 
                                           value="0"
                                           disabled>
                                </td>
                            </tr>
                        <?php
                            }
                        } else {
                        ?>
                            <tr>
                                <td colspan="8" class="text-center tx-color-03">
                                    Tidak ada item yang dapat diretur
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Keterangan -->
        <div class="card mg-t-20" style="border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="card-body">
                <label class="tx-medium mg-b-10">Keterangan Retur <span class="tx-danger">*</span></label>
                <textarea class="form-control" name="keterangan" rows="3" required placeholder="Masukkan alasan retur konsinyasi..."></textarea>
                <small class="form-text tx-color-03">Contoh: Barang tidak laku, mendekati expired date, dll.</small>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mg-t-20 mg-b-20">
            <a href="<?php echo $sistem; ?>/returkonsinyasi" class="btn btn-secondary">
                <i class="fa fa-times mg-r-5"></i> Batal
            </a>
            <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                <i class="fa fa-check mg-r-5"></i> Proses Retur
            </button>
        </div>
    </form>
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Check all
    $('#checkAll').change(function() {
        $('.item-check').prop('checked', $(this).is(':checked')).trigger('change');
    });
    
    // Individual checkbox change
    $('.item-check').change(function() {
        var $row = $(this).closest('tr');
        var $qtyInput = $row.find('.qty-input');
        
        if ($(this).is(':checked')) {
            $qtyInput.prop('disabled', false).focus();
            var maxQty = parseInt($qtyInput.attr('max'));
            $qtyInput.val(maxQty); // Auto-fill dengan max qty
        } else {
            $qtyInput.prop('disabled', true).val(0);
        }
        
        validateForm();
    });
    
    // Qty input change
    $('.qty-input').on('input', function() {
        var max = parseInt($(this).attr('max'));
        var val = parseInt($(this).val());
        
        if (val > max) {
            $(this).val(max);
        }
        if (val < 0) {
            $(this).val(0);
        }
        
        validateForm();
    });
    
    // Form validation
    function validateForm() {
        var hasChecked = $('.item-check:checked').length > 0;
        var hasValidQty = false;
        
        $('.item-check:checked').each(function() {
            var $row = $(this).closest('tr');
            var qty = parseInt($row.find('.qty-input').val());
            if (qty > 0) {
                hasValidQty = true;
                return false;
            }
        });
        
        var keterangan = $('textarea[name="keterangan"]').val().trim();
        var isValid = hasChecked && hasValidQty && keterangan.length > 0;
        
        console.log('Validation:', {
            hasChecked: hasChecked,
            hasValidQty: hasValidQty,
            keterangan: keterangan.length,
            isValid: isValid
        });
        
        $('#btnSubmit').prop('disabled', !isValid);
    }
    
    // Keterangan change
    $('textarea[name="keterangan"]').on('input', function() {
        validateForm();
    });
    
    // Form submit
    $('#formRetur').submit(function(e) {
        e.preventDefault();
        
        var $form = $(this);
        
        var hasValidItem = false;
        $('.item-check:checked').each(function() {
            var $row = $(this).closest('tr');
            var qty = parseInt($row.find('.qty-input').val());
            if (qty > 0) {
                hasValidItem = true;
                return false;
            }
        });
        
        if (!hasValidItem) {
            Swal.fire({
                icon: 'warning',
                title: 'Peringatan',
                text: 'Pilih minimal 1 item dengan jumlah > 0'
            });
            return false;
        }
        
        Swal.fire({
            title: 'Konfirmasi Retur',
            text: 'Apakah Anda yakin akan memproses retur barang konsinyasi ini?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Proses!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses...',
                    html: 'Sedang memproses retur barang',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit form via AJAX
                $.ajax({
                    url: '<?php echo $sistem; ?>/modal/returkonsinyasi/action.php',
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    success: function(response) {
                        Swal.close();
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.message,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = '<?php echo $sistem; ?>/returkonsinyasi';
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.close();
                        console.log('Error details:', xhr.responseText);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan sistem. Cek console untuk detail.'
                        });
                    }
                });
            }
        });
        
        return false;
    });
});
</script>

<style>
.item-row:hover {
    background-color: #f8f9fa;
}
.qty-input:disabled {
    background-color: #e9ecef;
    cursor: not-allowed;
}
</style>
