<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
/* Wave button untuk status = limit (radius gelombang diperkecil) */
.wave-btn {
    position: relative;
    z-index: 1;
    overflow: visible;
    transition: transform .12s ease;
}
.wave-btn.pulse {
    animation: pulseShadow 1.2s ease-in-out infinite;
}
.wave-btn::after {
    content: "";
    position: absolute;
    left: 50%;
    top: 50%;
    /* radius gelombang dikurangi lagi */
    width: calc(100% + 0px);
    height: calc(100% + 0px);
    transform: translate(-50%, -50%) scale(0.4);
    border-radius: 50%;
    pointer-events: none;
    background: radial-gradient(circle at 50% 40%, rgba(255,255,255,0.12), rgba(255,255,255,0));
    opacity: 0;
    animation: wave 1.2s ease-out infinite;
}

/* Jika tombol merah (btn-danger), buat warna gelombang agak kemerahan */
.wave-btn.btn-danger::after {
    background: radial-gradient(circle at 50% 40%, rgba(248, 46, 46, 0.35), rgba(231,76,60,0));
}

/* Jika tombol kuning (btn-warning), buat warna gelombang kekuningan */
.wave-btn.btn-warning::after {
    background: radial-gradient(circle at 50% 40%, rgba(255, 193, 7, 0.35), rgba(224,168,0,0));
}

/* keyframes (skala maksimum dikurangi lebih lanjut) */
@keyframes wave {
    0% {
        transform: translate(-50%, -50%) scale(0.4);
        opacity: 0.9;
    }
    70% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0.12;
    }
    100% {
        transform: translate(-50%, -50%) scale(1.4);
        opacity: 0;
    }
}
@keyframes pulseShadow {
    0% { box-shadow: 0 1px 4px rgba(192,57,43,0.08); }
    50% { box-shadow: 0 6px 12px rgba(192,57,43,0.14); transform: translateY(-1px); }
    100% { box-shadow: 0 1px 4px rgba(192,57,43,0.08); transform: translateY(0); }
}
</style>

<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Transaction Menu</a></li>
                <li class="breadcrumb-item active" aria-current="page">Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Approve Limit & Kuning</h4>
    </div>
</div>
<?php
    $cari	= $secu->injection($_GET['cari'] ?? '');
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
    <div class="row mg-b-10">
        <div class="col-sm-12">
            <!-- FIX: pakai modal khusus agar pasti muncul -->
            <a href="javascript:void(0)"
               onclick="flimitOpenCariModal()">
               <button type="button" class="btn btn-warning btn-pill btn-xs">
                    <i class="fa fa-search"></i> Cari Data
               </button>
            </a>

            <a href="<?php echo("$sistem/flimit"); ?>" title="Refresh"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
            <a target="_blank" href="<?php echo($data->sistem('url_sis')."/laporan/xls/finance/flimit.php?key=$cari"); ?>" title=".XLS"><button class="btn btn-success btn-pill btn-xs"><i class="fa fa-print"></i> .XLS</button></a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Status Limit</center></th>
                    <th>Nomer Faktur</th>
                    <th>Nama Outlet</th>
                    <th>Tanggal Faktur</th>
                    <th class="text-right">Limit Outlet</th>
                    <th class="text-right">Nominal</th>
                    <th><center>Approval</center></th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
		</div>
    </div>
</div>

<!-- FIX: Modal Cari Data (content di-load via AJAX ke .modal-content) -->
<div class="modal fade" id="modalCariLimit" tabindex="-1" role="dialog" aria-labelledby="modalCariLimitLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="p-4 text-center text-muted">Loading...</div>
    </div>
  </div>
</div>

<!-- Modal Approve Limit -->
<div class="modal fade" id="modalApproveLimit" tabindex="-1" role="dialog" aria-labelledby="modalApproveLimitLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">

      <div class="modal-header">
        <h6 class="modal-title" id="modalApproveLimitLabel">Approve Limit & Kuning</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="approve_limit_val" value="">
        <input type="hidden" id="approve_type" value="">
        <div id="approve_limit_text" class="mb-2">-</div>
        <div class="text-muted">Apakah Anda yakin ingin approve status limit atau kuning untuk faktur ini?</div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-dark btn-xs" id="btnApproveLimitYes" onclick="flimitDoApproveLimit()">Approve</button>
        <div id="approve_limit_loading" class="ml-2"></div>
      </div>

    </div>
  </div>
</div>

<script type="text/javascript">
function flimitRenderApproveInfo(sumber, faktur, statusLabel) {
    var fakturText = (sumber ? (sumber + ' - ') : '') + (faktur || '-');

    var badgeClass = 'badge badge-warning';
    if (String(statusLabel).toLowerCase() === 'kuning') badgeClass = 'badge badge-warning';

    return '' +
        '<div style="text-align:left;">' +
            '<div><strong>Faktur:</strong> ' + fakturText + '</div>' +
            '<div class="mt-1"><small class="text-muted">Status: <span class="' + badgeClass + '">' + statusLabel + '</span></small></div>' +
        '</div>';
}

window.flimitApproveLimit = function(value) {
    $('#approve_type').val('limit');
    $('#approve_limit_val').val(value);

    var parts  = (value || '').split('|');
    var sumber = (parts.length > 1 ? parts[0] : '');
    var nofak  = (parts.length > 1 ? parts[1] : parts[0]); // ini sekarang berisi nomor faktur (kode_tfk) dari JSON

    // ✅ tampilkan faktur + status di bawahnya
    $('#approve_limit_text').html(flimitRenderApproveInfo(sumber, nofak, 'Limit'));

    $('#approve_limit_loading').html('');
    $('#btnApproveLimitYes').prop('disabled', false);
    $('#modalApproveLimit').modal('show');
};

// Fungsi khusus untuk handle approve kuning
window.flimitHandleKuning = function(value) {
    try {
        var parts  = (value || '').split('|');
        var sumber = (parts.length > 1 ? parts[0] : '');
        var nofak  = (parts.length > 1 ? parts[1] : parts[0]); // nomor faktur (kode_tfk)

        $('#approve_limit_val').val(value);
        $('#approve_type').val('kuning');

        // ✅ tampilkan faktur + status di bawahnya
        $('#approve_limit_text').html(
            flimitRenderApproveInfo(sumber, nofak, 'Kuning')
        );

        $('#approve_limit_loading').html('');
        $('#btnApproveLimitYes').prop('disabled', false);
        $('#modalApproveLimit').modal('show');
    } catch(e){
        console.error(e);
        Swal.fire({icon:'error', title:'Error', text: 'Gagal menampilkan informasi.'});
    }
};

/**
 * Aman untuk response yang:
 * - JSON: {"status":"success|error","message":"..."}
 * - atau text legacy: "success", "error: ...", dll
 */
function interpretApproveResponse(resp, xhr) {
    // resp bisa object (kalau dataType json) atau string
    try {
        if (typeof resp === 'object' && resp !== null) {
            const st = String(resp.status || '').toLowerCase();
            return {
                ok: (st === 'success' || st === 'ok' || resp.success === true),
                message: String(resp.message || (st === 'success' ? 'Berhasil.' : 'Gagal.'))
            };
        }

        const text = String(resp || '').trim();

        // coba parse JSON kalau ternyata string JSON
        if (text.startsWith('{') || text.startsWith('[')) {
            const obj = JSON.parse(text);
            return interpretApproveResponse(obj, xhr);
        }

        // fallback legacy text
        if (text.toLowerCase() === 'success') return { ok: true, message: 'Berhasil approve.' };
        if (text.toLowerCase().startsWith('error')) return { ok: false, message: text.replace(/^error\s*:\s*/i, '') || 'Gagal approve.' };

        // default
        return { ok: false, message: text || 'Gagal approve.' };
    } catch (e) {
        return { ok: false, message: 'Response tidak valid dari server.' };
    }
}

window.flimitDoApproveLimit = function() {
    var value = $('#approve_limit_val').val();
    var approveType = $('#approve_type').val() || '';

    $('#approve_limit_loading').html('<img src="<?php echo $sistem; ?>/asset/image/loading.gif" height="20">');
    $('#btnApproveLimitYes').prop('disabled', true);

    $.ajax({
        type: 'POST',
        url: '<?php echo $sistem; ?>/modal/flimit/action.php?act=approve',
        data: { no_faktur: value, approve_type: approveType },

        // FIX: minta JSON (kalau JSON rusak, akan masuk ke error handler)
        dataType: 'json',

        success: function(respObj, textStatus, xhr) {
            $('#modalApproveLimit').modal('hide');

            const r = interpretApproveResponse(respObj, xhr);

            Swal.fire({
                icon: r.ok ? 'success' : 'error',
                title: r.ok ? 'Berhasil Approve' : 'Gagal Approve',
                text: r.message,
                confirmButtonText: 'OK',
                timer: 2500,
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then(function() {
                if (r.ok) flimitRefreshTable();
            });
        },

        error: function(xhr) {
            $('#modalApproveLimit').modal('hide');

            // ambil responseText bila JSON parse gagal
            const r = interpretApproveResponse(xhr ? xhr.responseText : '', xhr);

            Swal.fire({
                icon: 'error',
                title: 'Gagal Approve',
                text: r.message,
                confirmButtonText: 'OK',
                timer: 3000,
                timerProgressBar: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                footer: '<small class="text-muted">HTTP ' + (xhr ? xhr.status : '') + '</small>'
            });
        },

        complete: function() {
            $('#approve_limit_loading').html('');
            $('#btnApproveLimitYes').prop('disabled', false);
        }
    });
};

/**
 * Refresh tabel flimit setelah approve sukses.
 * Prioritas:
 * 1) viewdata(menu,maximal,halaman) (ada di fazlurr.js)
 * 2) readdata(menu) kalau memang ada di project ini
 * 3) fallback reload halaman
 */
function flimitRefreshTable() {
    try {
        var menu = 'flimit';
        var maximal = parseInt($('#maximal').val() || '15', 10);
        var halaman = parseInt($('#halaman').val() || '1', 10);

        if (typeof viewdata === 'function') {
            viewdata(menu, maximal, halaman);
            return;
        }
        if (typeof readdata === 'function') {
            readdata(menu);
            return;
        }
        window.location.reload();
    } catch (e) {
        window.location.reload();
    }
};

/**
 * FIX: buka modal cari + load konten modal caridatalimit via AJAX.
 * Load response ke #modalCariLimit .modal-content (karena response sudah include modal-header/body/footer).
 */
window.flimitOpenCariModal = function() {
    try {
        var menu = 'flimit';
        var cari = $('#caridata').val() || '';

        // pastikan modal tampil dulu
        $('#modalCariLimit').modal('show');
        $('#modalCariLimit .modal-content').html('<div class="p-4 text-center text-muted">Loading...</div>');

        // pakai usuper dari fazlurr.js jika ada, fallback manual
        var baseUrl = (typeof usuper !== 'undefined' && usuper) 
            ? usuper 
            : (window.location.origin + '/' + window.location.pathname.split('/')[1]);

        $.ajax({
            url: baseUrl + '/modal/caridatalimit/caridatalimit.php',
            type: 'POST',
            dataType: 'html',
            cache: false,
            data: { menu: menu, cari: cari },
            success: function(html) {
                $('#modalCariLimit .modal-content').html(html);
            },
            error: function(xhr) {
                $('#modalCariLimit').modal('hide');
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: 'Modal pencarian gagal dimuat. (HTTP ' + (xhr ? xhr.status : '') + ')'
                });
            }
        });
    } catch (e) {
        console.error(e);
        alert('Gagal membuka modal pencarian.');
    }
};

// Optional: reset isi modal saat ditutup
$('#modalCariLimit').on('hidden.bs.modal', function() {
    $('#modalCariLimit .modal-content').html('<div class="p-4 text-center text-muted">Loading...</div>');
});

</script>