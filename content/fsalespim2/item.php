<?php
	$uniq = $secu->injection(@$_GET['keycode']); // Melakukan sanitasi input untuk mencegah injection
    $code = base64_decode($uniq); // Mendekodekan nilai yang diterima, asumsi encoded base64
    
    // Menyiapkan query untuk mengambil data
    $read = $conn->prepare("SELECT A.sj_tfk, A.id_out, COALESCE(B.hargapim, 'Harga Tidak Tersedia') AS hargapim, A.kode_tfk
                            FROM transaksi_faktur_pim AS A
                            LEFT JOIN jenis_hargapim AS B ON A.id_out = B.id_out
                            WHERE A.id_tfk = :code");
    
    // Bind parameter untuk menghindari SQL injection
    $read->bindParam(':code', $code, PDO::PARAM_STR);
    
    // Menjalankan query
    $read->execute();
    $view = $read->fetch(PDO::FETCH_ASSOC);
    $p = $np = '';
    $p = $np = '';
    if (!empty($view)) {
        if ($view['hargapim'] == 'A') {
            $p = 'checked';
            $view['hargapim'] == 'P';
        }
        if ($view['hargapim'] == 'B') {
            $np = 'checked';
            $view['hargapim'] == 'NP';
        }
        if ($view['hargapim'] == 'C') {
            $np = 'checked';
            $view['hargapim'] == 'NP';
        }
    }
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<form id="formsalespnp" action="#" method="post" autocomplete="off">
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan PIM2</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Item - Faktur Penjualan PIM2</h4>
        <h5>SJ : <?php echo($view['sj_tfk']); ?></h5>
    </div>
</div>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold"><?php echo($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo($data->sistem('alamat_sis')); ?></div>
        </div>
        <input type="hidden" name="outlet" id="outlet" value="<?php echo($view['id_out']); ?>" readonly="readonly" />
        <input type="hidden" name="namamodal" id="namamodal" value="fsalespim2" readonly="readonly" />
        <input type="hidden" name="namamenu" value="items" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfk']); ?>" readonly="readonly" />
        <!-- SweetAlert2 (wajib ada supaya Swal.fire jalan) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        
        <script>
        // Base URL untuk AJAX (dipakai juga untuk tombol Batal di bawah) limit
        window.SISTEM_URL = <?php echo json_encode($sistem ?? ''); ?>;

        var FAKTUR_PROGRAM_STATUS = <?php echo($isProgramYa); ?>;
        console.log('Status Program Faktur:', FAKTUR_PROGRAM_STATUS);

        function parseAngkaID(val) {
            if (val == null) return 0;
            var s = String(val).replace(/\./g, '').replace(/[^0-9\-]/g, '');
            var n = parseInt(s, 10);
            return isNaN(n) ? 0 : n;
        }

        // state untuk mencegah popup muncul terus-terusan
        window.__LIMIT_STATE__ = window.__LIMIT_STATE__ || { over: false, near80: false };

        function getSwalInstance() {
            // SweetAlert2 expose global "Swal"
            return window.Swal || null;
        }

        function waitForSwal(timeoutMs) {
            timeoutMs = typeof timeoutMs === 'number' ? timeoutMs : 2000;

            return new Promise(function(resolve, reject){
                var start = Date.now();
                (function tick(){
                    var swal = getSwalInstance();
                    if (swal && typeof swal.fire === 'function') return resolve(swal);
                    if (Date.now() - start >= timeoutMs) return reject(new Error('SweetAlert2 tidak ter-load'));
                    setTimeout(tick, 50);
                })();
            });
        }
        function showLimitPopup() {
            var msg = 'Nominal melebihi limit.';

            var closeSearchModalIfOpen = function(cb) {
                if (!window.jQuery) return cb();

                var $search = jQuery('#modal1');
                var searchOpen =
                    $search.length &&
                    ($search.hasClass('show') || $search.hasClass('in') || $search.is(':visible'));

                if (searchOpen && jQuery.fn && typeof jQuery.fn.modal === 'function') {
                    $search.one('hidden.bs.modal', function () { cb(); });
                    $search.modal('hide');
                    return;
                }
                cb();
            };

            closeSearchModalIfOpen(function(){
                waitForSwal(2000).then(function(Swal){
                    if (typeof Swal.isVisible === 'function' && Swal.isVisible()) return;

                    Swal.fire({
                        icon: 'warning',
                        title: 'Peringatan',
                        text: msg,
                        confirmButtonText: 'OK',
                        allowOutsideClick: false,
                        allowEscapeKey: true,
                        target: document.body
                    });
                }).catch(function(err){
                    console.warn('Popup limit gagal ditampilkan karena SweetAlert2 belum siap:', err);
                });
            });
        }

        // Notifikasi ketika subtotal > 80% dari limit (tapi <= limit)
        function showNearLimitPopup() {
            var msg = 'Nominal mendekati limit (lebih dari 80%).';

            var closeSearchModalIfOpen = function(cb) {
                if (!window.jQuery) return cb();

                var $search = jQuery('#modal1');
                var searchOpen =
                    $search.length &&
                    ($search.hasClass('show') || $search.hasClass('in') || $search.is(':visible'));

                if (searchOpen && jQuery.fn && typeof jQuery.fn.modal === 'function') {
                    $search.one('hidden.bs.modal', function () { cb(); });
                    $search.modal('hide');
                    return;
                }
                cb();
            };

            closeSearchModalIfOpen(function(){
                waitForSwal(2000).then(function(Swal){
                    if (typeof Swal.isVisible === 'function' && Swal.isVisible()) return;

                    Swal.fire({
                        icon: 'info',
                        title: 'Perhatian',
                        text: msg,
                        confirmButtonText: 'OK',
                        allowOutsideClick: true,
                        allowEscapeKey: true,
                        target: document.body
                    });
                }).catch(function(err){
                    console.warn('Popup near-limit gagal ditampilkan karena SweetAlert2 belum siap:', err);
                });
            });
        }

        function hideLimitPopup() {
            var Swal = getSwalInstance();
            if (Swal && typeof Swal.close === 'function') Swal.close();
        }

        function renderLimitWarning() {
            var subtotalEl = document.getElementById('pstotal');
            var limitEl = document.getElementById('limit_outlet');

            var subtotal = subtotalEl ? parseAngkaID(subtotalEl.value) : 0;
            var limit = limitEl ? parseAngkaID(limitEl.value) : 0;

            // Kalau limit = 0 anggap tidak dibatasi
            var over = (limit > 0 && subtotal > limit);

            // Baru: near ketika > 80% dan masih <= limit
            var near = (limit > 0 && subtotal > Math.floor(limit * 0.8) && subtotal <= limit);

            // JANGAN disable tombol simpan (tetap boleh simpan)

            // Prioritas: over > near > none
            if (over && !window.__LIMIT_STATE__.over) {
                showLimitPopup();
            } else if (!over && window.__LIMIT_STATE__.over) {
                // kembali aman dari over -> tutup popup
                hideLimitPopup();
            }

            if (!over) {
                if (near && !window.__LIMIT_STATE__.near80) {
                    showNearLimitPopup();
                } else if (!near && window.__LIMIT_STATE__.near80) {
                    hideLimitPopup();
                }
            }

            // update state
            window.__LIMIT_STATE__.over = over;
            window.__LIMIT_STATE__.near80 = (!over && near);
        }

        function fetchOutletLimit(outletId) {
            if (!outletId) return;

            var base = (window.SISTEM_URL && window.SISTEM_URL.length) ? window.SISTEM_URL : '';
            var url = base.replace(/\/$/, '') + '/ajax/ceksales/ceksales.php';
            var body = 'x=' + encodeURIComponent(outletId);

            fetch(url, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body,
                credentials: 'same-origin'
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                var limit = (res && typeof res.limit_outlet !== 'undefined') ? res.limit_outlet : 0;
                var limitEl = document.getElementById('limit_outlet');
                if (limitEl) limitEl.value = limit;

                renderLimitWarning();
            })
            .catch(function(err){
                console.warn('Gagal ambil limit outlet:', err);
            });
        }

        (function(){
            var outletId = document.getElementById('outlet') ? document.getElementById('outlet').value : '';
            if (outletId) fetchOutletLimit(outletId);
        })();

        (function watchSubtotal(){
            var last = null;
            setInterval(function(){
                var el = document.getElementById('pstotal');
                if (!el) return;
                if (el.value !== last) {
                    last = el.value;
                    renderLimitWarning();
                }
            }, 250);
        })
        ();
        //end limit
        </script>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Order Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan dipesan kepada outlet.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
                <div class="table-responsive">
                <table class="tabeltransaksi">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Detail</th>
                            <th>Satuan</th>
                            <th>Tgl. ED</th>
                            <th>Batchcode</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <!-- <th>St. Qty.</th> -->
                            <th>Total</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddsalespim">
                    	<tr id="pilihoutlet"><td colspan="10">Pilih outlet dulu...</td></tr>
					</tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td colspan="6"><div align="right"><b>SUBTOTAL</b></div></td>
                            <td><input type="text" name="pstotal" id="pstotal" class="inputtotal" placeholder="0" readonly="readonly" /></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="6"><div align="right"><b>DPP</b></div></td>
                            <td><input type="text" name="pdpp" id="pdpp" class="inputtotal" placeholder="0" readonly="readonly" /></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="6"><div align="right"><b>PPN (11%)</b></div></td>
                            <td><input type="text" name="pppn" id="pppn" class="inputtotal" placeholder="0" readonly="readonly" /></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="6"><div align="right"><b>TOTAL</b></div></td>
                            <td><input type="text" name="pgtotal" id="pgtotal" class="inputtotal" placeholder="0" readonly="readonly" /></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                <input type="hidden" name="minorder" id="minorder" value="" readonly="readonly" />
                <input type="hidden" name="cartaddsalespim" id="cartaddsalespim" value="" readonly="readonly" />
                <input type="hidden" name="jumaddsalespim2" id="jumaddsalespim2" value="0" readonly="readonly" />
                <input type="hidden" id="limit_outlet" name="limit_outlet" value="" readonly="readonly" />
                <a onclick="addmaximalpim2('addsalespim2', 'outlet', 50)"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsalespim2"); ?>" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
    </div>
</div>
</form>
