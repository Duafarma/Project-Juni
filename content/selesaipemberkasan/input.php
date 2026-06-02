<div class="content-header">
    <div>
        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Pengiriman</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pengiriman Kurir</li>
            </ol>
        </nav> -->
        <h4 class="content-title">Input Data - Selesai Selesai Pemberkasan - Serah Terima Faktur</h4>
    </div>
</div>
<input type="hidden" name="jumlegal" id="jumlegal" value="0" readonly="readonly" />
<input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <form id="formtransaksi" action="#" method="post"  enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="selesaipemberkasan" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="form-row">
            <div class="form-group col-sm-12">
                <label>Tgl. Serah Terima Faktur  <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal" class="form-control datepicker"  value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
            </div>
           

            <!-- <div class="row"> -->
  
		<!-- </div> -->
            <!-- <div class="form-group col-sm-6">
                <label>Nomor Faktur <span class="tx-danger">*</span></label>
                <select name="nomor_faktur" id="nomor_faktur" class="form-control select2" onchange="" required="required">
                <option value="">-- Pilih --</option>
                <?php
                 $master = $conn->prepare("SELECT id_tfk, kode_tfk, DATE_FORMAT(created_at, '%Y-%m-%d') FROM transaksi_faktur ORDER BY kode_tfk ASC");
                 $master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                <option value="<?php echo($hasil['id_tfk']); ?>"><?php echo($hasil['kode_tfk']); ?></option>              
                 <?php } ?>
                </select>
            </div>

            <div class="form-group col-sm-6">
                <label>No Faktur <span class="tx-danger">*</span></label>
                <select name="nomor_faktur_lagi" id="nomor_faktur_lagi" class="form-control select2" onchange="" required="required">
                <option value="">-- Pilih --</option>
                <?php
                 $master = $conn->prepare("SELECT id_tfk, kode_tfk, DATE_FORMAT(created_at, '%Y-%m-%d') FROM transaksi_faktur ORDER BY kode_tfk ASC");
                 $master->execute();
                  while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
                ?>
                <option value="<?php echo($hasil['id_tfk']); ?>"><?php echo($hasil['kode_tfk']); ?></option>              
                 <?php } ?>
                </select>
            </div> -->
            <!-- <div class="form-group col-sm-6"></div> -->
            
            <!-- <div class="form-group col-sm-6">
                <label>Status Pengiriman <span class="tx-danger">*</span></label>
                <select name="status_tfkk" id="status_tfkk" class="form-control select2" required="required">
                    <option value="">-- Pilih --</option>
                    <option value="Diterima">Diterima</option>
                    <option value="Dikembalikan Sebagian">Dikembalikan Sebagian</option>
                    <option value="Dikembalikan Seluruhnya">Dikembalikan Seluruhnya</option>
                </select>
            </div> -->
            
        </div>
        
         <div class="row">
            <div class="form-group col-sm-12">
                <label>Nomor Faktur <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th>Nomor Faktur</th>
							<th>Ket.</th>
							<!-- <th>Expired Date</th> -->
							<th><center>Hapus</center></th>
						</tr>
					</thead>
					<tbody id="tbllegal"></tbody>
				</table>
                <div class="row mt-2">
                    <div class="col-sm-4">
                        <div class="input-group">
                            <input type="number" id="jumlah_row" class="form-control" placeholder="Jumlah row" min="1" max="500" value="1">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info btn-sm" onclick="addMultipleRows()">
                                    <i class="fa fa-plus"></i> Add Multiple
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">Masukkan jumlah row yang diinginkan (max 500)</small>
                    </div>
                    <div class="col-sm-4">
                        <a onclick="additem('tbllegal', 'jumlegal', 'selesaipemberkasan')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Single</span></a>
                    </div>
                </div>
            </div>
		</div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/selesaifakturpajak"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
        <!-- <div class="row">
            <div class="textarea col-sm-12">
                 <a href="<?php echo("$sistem/finance"); ?>" title="Batal"><button type="button" class="btn btn-secondary btn-xs">Batal</button></a>

                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div> -->
		</form>
    </div>
</div>

<!-- <div>
    json_encode($_REQUEST_api)
</div> -->

<script type="text/javascript">
function addMultipleRows() {
    var jumlahRowEl = document.getElementById('jumlah_row');
    var jumlahRow = parseInt(jumlahRowEl ? jumlahRowEl.value : 1, 10);

    if (isNaN(jumlahRow) || jumlahRow < 1) {
        alert('Masukkan jumlah row yang valid (minimal 1)');
        return;
    }
    if (jumlahRow > 500) {
        alert('Maksimal 500 row yang bisa ditambahkan sekaligus');
        return;
    }
    if (jumlahRow > 50 && !confirm('Anda akan menambahkan ' + jumlahRow + ' row sekaligus. Lanjutkan?')) {
        return;
    }

    var completed = 0;
    for (var i = 0; i < jumlahRow; i++) {
        (function(delayIndex) {
            setTimeout(function() {
                // panggil fungsi additem yang sudah ada di project
                additem('tbllegal', 'jumlegal', 'selesaipemberkasan');
                completed++;
                if (completed === jumlahRow) {
                    setTimeout(function() {
                        initializeAllSelect2();
                        alert('Berhasil menambahkan ' + jumlahRow + ' row');
                        if (jumlahRowEl) jumlahRowEl.value = '1';
                    }, 200);
                }
            }, delayIndex * 100);
        })(i);
    }
}

function initializeAllSelect2() {
    setTimeout(function() {
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
        $('.select2').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                try { $(this).select2('destroy'); } catch(e) {}
            }
        });
        $('.select2').select2({
            placeholder: '-- Pilih Nomor Faktur --',
            width: '100%',
            allowClear: true
        });
    }, 100);
}

if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        initializeAllSelect2();
    });
}
</script>