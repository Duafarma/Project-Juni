<div class="content-header">
    <div>
        <!-- <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Finance</a></li>
                <li class="breadcrumb-item active" aria-current="page">Siap TF</li>
            </ol>
        </nav> -->
        <h4 class="content-title">Input Data - Siap TF</h4>
    </div>
</div>
<input type="hidden" name="jumlegal" id="jumlegal" value="0" readonly="readonly" />
<input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
<div class="content-body">
    <div class="component-section no-code">
        <form id="formtransaksi" action="#" method="post"  enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="siaptf" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
        <div class="form-row">
            <div class="form-group col-sm-12">
                <label>Tgl. Siap TF <span class="tx-danger">*</span></label>
                <input type="text" name="tanggal" class="form-control datepicker"  value="<?php echo(date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-group col-sm-12">
                    <label>Tujuan <span class="tx-danger">*</span></label>
                    <select name="tujuan"  id="id_po" class="form-control select2" required="required">
                        <option value="">-- Pilih --</option>
                        <option value="Dokumen"> Dokumen </option>
                        <option value="Tagihan"> Tagihan </option>
                        <option value="Tukar Faktur"> Tukar Faktur </option>
                    </select>
            </div>
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
                        <a onclick="additem('tbllegal', 'jumlegal', 'siaptf')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Single</span></a>
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
                additem('tbllegal', 'jumlegal', 'siaptf'); // server sekarang cache opsi
                completed++;
                if (completed === jumlahRow) {
                    setTimeout(function() {
                        // Tidak perlu destroy/re-init semua select2 lagi
                        alert('Berhasil menambahkan ' + jumlahRow + ' row');
                        if (jumlahRowEl) jumlahRowEl.value = '1';
                    }, 100);
                }
            // Perkecil jeda agar lebih cepat (tetap ada sedikit throttling)
            }, delayIndex * 10);
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
            placeholder: '-- Pilih --',
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