<?php
$nomorHariIni = 1;
try {
    $qNo = $conn->prepare("SELECT COUNT(*) FROM transfer_ir WHERE DATE(created_at)=CURDATE()");
    $qNo->execute();
    $nomorHariIni = ((int)$qNo->fetchColumn()) + 1;
} catch (Exception $e) {
    $nomorHariIni = 1;
}
$kodePreview = 'TIR' . date('Ymd') . str_pad($nomorHariIni, 4, '0', STR_PAD_LEFT);
?>

<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/transferir">Transfer Retur ke Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Baru</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Inventory Retur ke Penjualan - Baru</h4>
    </div>
</div>

<div class="content-body">
    <div class="component-section no-code">
        <h5 class="tx-semibold"><?php echo $data->sistem('pt_sis'); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo $data->sistem('pbf_sis'); ?></div>
            <div>NPWP No : <?php echo $data->sistem('npwp_sis'); ?></div>
            <div>Alamat : <?php echo $data->sistem('alamat_sis'); ?></div>
        </div>

        <form id="formTransferIR" action="#" method="post" autocomplete="off" onsubmit="return false;">
            <div class="row row-sm">
                <div class="col-sm-3">
                    <label>Kode <span class="tx-danger">*</span></label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($kodePreview); ?>" readonly>
                </div>
                <div class="col-sm-3">
                    <label>Tanggal <span class="tx-danger">*</span></label>
                    <input type="text" class="form-control" value="<?php echo date('Y-m-d'); ?>" readonly>
                </div>
                <div class="col-sm-6">
                    <label>Keterangan</label>
                    <input type="text" id="ketTransfer" class="form-control" placeholder="Ketik keterangan di sini..." maxlength="255">
                </div>
            </div>

            <div class="clearfix mg-t-25 mg-b-10"></div>
            <h5 class="tx-semibold">Pilih Produk dari Inventory Retur</h5>
            <p class="mg-b-25">Pilih produk dari inventory retur yang akan ditransfer ke inventory penjualan.</p>

            <div class="row row-sm mg-b-15">
                <div class="col-sm-4">
                    <label>Sumber Inventory Retur <span class="tx-danger">*</span></label>
                    <select id="pilihAplikasi" class="form-control" onchange="gantiSumber()">
                        <option value="lokal">-- Lokal (Aplikasi Ini) --</option>
                        <?php
                        $qApl = $conn->query("SELECT id_apl, nama_apl, base_url_apl FROM aplikasi WHERE active_apl=1 AND self_apl=0 ORDER BY nama_apl ASC");
                        while($apl = $qApl->fetch(PDO::FETCH_ASSOC)) {
                            echo '<option value="'.htmlspecialchars($apl['id_apl']).'" data-url="'.htmlspecialchars($apl['base_url_apl']).'">'.htmlspecialchars($apl['nama_apl']).'</option>';
                        }
                        ?>
                    </select>
                    <small class="text-muted" id="infoSumber">Menampilkan inventory retur dari aplikasi ini</small>
                </div>
            </div>

            <div class="row row-sm">
                <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="tabeltransaksi" id="tbItems">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Detail</th>
                                    <th>Batchcode</th>
                                    <th>Tgl. Expired</th>
                                    <th>Gudang</th>
                                    <th>Stok</th>
                                    <th>Jumlah Transfer</th>
                                    <th>Satuan</th>
                                    <th><center>Hapus</center></th>
                                </tr>
                            </thead>
                            <tbody id="tbItemBody">
                                <tr id="trEmpty"><td colspan="9" class="text-center text-muted"><small>Belum ada item ditambahkan</small></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <a onclick="addProductTRP()"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Tambah Produk</span></a>
                </div>
            </div>

            <div class="clearfix mg-t-25 mg-b-25"></div>
            <div class="row row-sm">
                <div class="col-sm-12">
                    <a href="<?php echo $sistem; ?>/transferir" title="Batal">
                        <button type="button" class="btn btn-secondary">Batal</button>
                    </a>
                    <button type="button" id="btnSaveTransfer" class="btn btn-dark" onclick="submitTransfer()">Simpan</button>
                    <button type="button" id="btnSaveDraftTransfer" class="btn btn-outline-secondary" onclick="saveDraft()">Simpan Draft</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalPilihProdukTIR" tabindex="-1" role="dialog" aria-labelledby="modalPilihProdukTIRLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPilihProdukTIRLabel">Pilih Produk dari Inventory</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-2">
                    <input type="text" id="cariProduk" class="form-control form-control-sm" placeholder="Ketik nama produk / batch code...">
                </div>
                <div class="table-responsive" style="max-height:400px;overflow-y:auto;">
                    <table class="table table-sm table-hover" id="tbCariProduk">
                        <thead class="thead-light">
                            <tr>
                                <th>Produk</th>
                                <th>Batch</th>
                                <th>ED</th>
                                <th>Gudang</th>
                                <th width="70px"><center>Stok</center></th>
                                <th width="40px"></th>
                            </tr>
                        </thead>
                        <tbody id="tbBodyCari">
                            <tr><td colspan="6" class="text-center text-muted"><small>Ketik untuk mencari produk...</small></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
var itemList = [];
var cariTimer;
var sumberApl = 'lokal'; // default lokal

function gantiSumber() {
    var sel = document.getElementById('pilihAplikasi');
    sumberApl = sel.value;
    var nama = sel.options[sel.selectedIndex].text;
    document.getElementById('infoSumber').textContent = 'Menampilkan inventory retur dari: ' + nama;
    // Kosongkan tabel item saat ganti sumber (produk beda app)
    if(itemList.length > 0) {
        swal({title:'Perhatian', text:'Mengganti sumber akan mengosongkan daftar produk yang sudah dipilih.', type:'warning',
            showCancelButton:true, confirmButtonText:'Lanjutkan', cancelButtonText:'Batal'},
            function(ok) {
                if(ok) { itemList = []; renderItems(); }
                else { document.getElementById('pilihAplikasi').value = sumberApl; }
            });
        return;
    }
}

function getSearchUrl() {
    if(sumberApl === 'lokal') {
        return '<?php echo $data->sistem('url_sis'); ?>/ajax/transferir/search_inventory.php';
    }
    return '<?php echo $data->sistem('url_sis'); ?>/ajax/transferir/search_inventory_remote.php';
}

function getSearchData(cari) {
    if(sumberApl === 'lokal') {
        return { cari: cari };
    }
    return { cari: cari, id_apl: sumberApl };
}

function addProductTRP() {
    $('#modalPilihProdukTIR').modal('show');
    setTimeout(function() { $('#cariProduk').focus(); }, 200);
    muatSemuaProduk();
}

function muatSemuaProduk() {
    $('#tbBodyCari').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>');
    $.ajax({
        url: getSearchUrl(),
        type: 'POST',
        dataType: 'json',
        data: getSearchData(''),
        success: function(res) {
            if(res.error) {
                $('#tbBodyCari').html('<tr><td colspan="6" class="text-center text-danger"><small>' + res.error + '</small></td></tr>');
                return;
            }
            renderHasilCari(res);
        },
        error: function() {
            $('#tbBodyCari').html('<tr><td colspan="6" class="text-center text-danger"><small>Gagal memuat.</small></td></tr>');
        }
    });
}

$('#cariProduk').on('input', function() {
    clearTimeout(cariTimer);
    var q = $(this).val().trim();
    if (q.length === 0) {
        muatSemuaProduk();
        return;
    }
    cariTimer = setTimeout(function() { cariProduk(q); }, 400);
});

function renderHasilCari(res) {
    if (!res.data || res.data.length === 0) {
        $('#tbBodyCari').html('<tr><td colspan="6" class="text-center text-muted"><small>Tidak ada stok ditemukan.</small></td></tr>');
        return;
    }
    var tb = '';
    $.each(res.data, function(i, r) {
        tb += '<tr>' +
            '<td><strong>' + r.nama_pro + '</strong></td>' +
            '<td>' + r.no_bcode + '</td>' +
            '<td>' + r.tgl_expired + '</td>' +
            '<td>' + r.gudang + '</td>' +
            '<td><center>' + r.sisa + '</center></td>' +
            '<td><center><button type="button" class="btn btn-success btn-xs" onclick="langkahTambah(' +
                r.id_psd + ',\'' + escJs(r.id_pro) + '\',\'' + escJs(r.nama_pro) + '\',\'' + escJs(r.no_bcode) + '\',\'' + escJs(r.tgl_expired) + '\',\'' + escJs(r.gudang) + '\',' + r.sisa +
            ')"><i class="fa fa-plus"></i></button></center></td>' +
            '</tr>';
    });
    $('#tbBodyCari').html(tb);
}

function cariProduk(q) {
    $('#tbBodyCari').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i></td></tr>');
    $.ajax({
        url: getSearchUrl(),
        type: 'POST',
        dataType: 'json',
        data: getSearchData(q),
        success: function(res) {
            if(res.error) {
                $('#tbBodyCari').html('<tr><td colspan="6" class="text-center text-danger"><small>' + res.error + '</small></td></tr>');
                return;
            }
            renderHasilCari(res);
        },
        error: function() {
            $('#tbBodyCari').html('<tr><td colspan="6" class="text-center text-danger"><small>Gagal memuat.</small></td></tr>');
        }
    });
}

function escJs(s) {
    return String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function langkahTambah(id_psd, id_pro, nama_pro, bcode, expired, gudang, sisa) {
    var existing = itemList.findIndex(function(x) { return x.id_psd === id_psd; });
    if (existing >= 0) {
        // sudah ada, tambah 1
        if (itemList[existing].jumlah >= sisa) {
            swal('Perhatian', 'Jumlah transfer melebihi stok! Stok tersedia: ' + sisa + '.', 'warning');
            return;
        }
        itemList[existing].jumlah += 1;
    } else {
        itemList.push({
            id_psd: id_psd,
            id_pro: id_pro,
            nama_pro: nama_pro,
            no_bcode: bcode,
            tgl_expired: expired,
            gudang: gudang,
            sisa: sisa,
            jumlah: 1
        });
    }
    renderItems();
    $('#modalPilihProdukTIR').modal('hide');
}

function renderItems() {
    if (itemList.length === 0) {
        $('#tbItemBody').html('<tr id="trEmpty"><td colspan="9" class="text-center text-muted"><small>Belum ada item ditambahkan</small></td></tr>');
        return;
    }

    var tb = '';
    $.each(itemList, function(i, r) {
        tb += '<tr>' +
            '<td><strong>' + r.nama_pro + '</strong></td>' +
            '<td><small class="text-muted">Produk inventory</small></td>' +
            '<td>' + r.no_bcode + '</td>' +
            '<td>' + r.tgl_expired + '</td>' +
            '<td>' + r.gudang + '</td>' +
            '<td><center>' + r.sisa + '</center></td>' +
            '<td><input type="number" class="form-control form-control-sm text-right" min="1" max="' + r.sisa + '" value="' + r.jumlah + '" onchange="ubahJumlah(' + i + ', this.value)"></td>' +
            '<td>PCS</td>' +
            '<td><center><button type="button" class="btn btn-danger btn-xs" onclick="hapusItem(' + i + ')"><i class="fa fa-times"></i></button></center></td>' +
            '</tr>';
    });
    $('#tbItemBody').html(tb);
}

function ubahJumlah(idx, val) {
    var j = parseInt(val, 10);
    if (isNaN(j) || j < 1) { j = 1; }
    if (j > itemList[idx].sisa) {
        swal('Perhatian', 'Jumlah transfer melebihi stok! Stok tersedia: ' + itemList[idx].sisa + '.', 'warning');
        j = itemList[idx].sisa;
    }
    itemList[idx].jumlah = j;
    renderItems();
}

function hapusItem(idx) {
    itemList.splice(idx, 1);
    renderItems();
}

function getPayload(status) {
    return {
        namamenu:   'save_transfer',
        keterangan: $('#ketTransfer').val(),
        status:     status,
        sumber_apl: sumberApl,  // 'lokal' atau id_apl app sumber
        items:      JSON.stringify(itemList)
    };
}

function saveDraft() {
    if (itemList.length === 0) {
        swal('Perhatian', 'Tambahkan item terlebih dahulu.', 'warning');
        return;
    }
    kirimTransfer('draft');
}

function submitTransfer() {
    if (itemList.length === 0) {
        swal('Perhatian', 'Tambahkan item terlebih dahulu.', 'warning');
        return;
    }

    swal({
        title: 'Simpan Transfer?',
        text: 'Transfer akan diajukan untuk proses approval.',
        type: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal'
    }, function(isConfirmed) {
        if (isConfirmed) {
            kirimTransfer('pending');
        }
    });
}

function kirimTransfer(status) {
    var payload = getPayload(status);
    $('#btnSaveTransfer').prop('disabled', true);
    $('#btnSaveDraftTransfer').prop('disabled', true);

    swal({ title: 'Menyimpan...', text: 'Mohon tunggu...', showConfirmButton: false });

    $.ajax({
        url: '<?php echo $data->sistem('url_sis'); ?>/modal/transferir/action.php',
        type: 'POST',
        data: payload,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'ok') {
                swal('Berhasil', res.message, 'success');
                setTimeout(function() {
                    window.location.href = '<?php echo $data->sistem('url_sis'); ?>/transferir/v/' + res.id_tir;
                }, 1500);
            } else {
                swal('Gagal', res.message, 'error');
            }
        },
        error: function() {
            swal('Error', 'Terjadi kesalahan jaringan.', 'error');
        },
        complete: function() {
            $('#btnSaveTransfer').prop('disabled', false);
            $('#btnSaveDraftTransfer').prop('disabled', false);
        }
    });
}

$('#modalPilihProdukTIR').on('hidden.bs.modal', function() {
    $('#cariProduk').val('');
    $('#tbBodyCari').html('<tr><td colspan="6" class="text-center text-muted"><small>Memuat...</small></td></tr>');
});
</script>
