<?php
	$uniq	= $secu->injection(@$_GET['keycode']);
	$code	= base64_decode($uniq);
	$read	= $conn->prepare("SELECT sj_tfk, id_out, kode_tfk, program, dari_konsinyasi, id_tfk_konsinyasi FROM transaksi_faktur WHERE id_tfk=:code");
	$read->bindParam(':code', $code, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);
	
	// Cek program - sekarang bisa berisi: "tidak", "program_vb", "program_diskon", atau "program_vb,program_diskon"
	$programValue = $view['program'];
	$isProgramYa = ($programValue !== 'tidak' && !empty($programValue)) ? 'true' : 'false';
	
	// Parse program yang dipilih
	$selectedPrograms = ($programValue !== 'tidak' && !empty($programValue)) ? explode(',', $programValue) : array();
	$isProgramVB = in_array('program_vb', $selectedPrograms) ? 'true' : 'false';
	$isProgramDiskon = in_array('program_diskon', $selectedPrograms) ? 'true' : 'false';
	
	// Cek konsinyasi
	$dariKonsinyasi = $view['dari_konsinyasi'];
	$idFakturKonsinyasi = $view['id_tfk_konsinyasi'];
	$isKonsinyasi = ($dariKonsinyasi === 'ya') ? 'true' : 'false';

	// PROGRAM OTOMATIS BERDASARKAN OUTLET DAN PRODUK
	$id_out = $view['id_out'];
	$queryAutoProgram = "
		SELECT p.id_pp, p.nama_program, p.jenis_program, p.min_qty, p.diskon_persen, pd.id_pro, pd.harga_program
		FROM master_program_produk p
		JOIN master_program_produk_outlet po ON p.id_pp = po.id_pp
		JOIN master_program_produk_detail pd ON p.id_pp = pd.id_pp
		WHERE p.status_program = 'Active' AND po.id_out = :id_out
	";
	$stmtAutoProgram = $conn->prepare($queryAutoProgram);
	$stmtAutoProgram->bindParam(':id_out', $id_out, PDO::PARAM_STR);
	$stmtAutoProgram->execute();
	$autoProgramsList = $stmtAutoProgram->fetchAll(PDO::FETCH_ASSOC);
	$autoProgramsJson = json_encode($autoProgramsList);
?>
<form id="formsalespnp" action="#" method="post" autocomplete="off">
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page">Faktur Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Input Item - Faktur Penjualan</h4>
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
        <input type="hidden" name="namamodal" id="namamodal" value="fsales" readonly="readonly" />
        <input type="hidden" name="namamenu" value="items" readonly="readonly" />
        <input type="hidden" name="keycode" value="<?php echo($uniq); ?>" readonly="readonly" />
        <input type="hidden" name="nomorfaktur" value="<?php echo($view['kode_tfk']); ?>" readonly="readonly" />
        <input type="hidden" name="program" id="program_status" value="<?php echo($view['program']); ?>" readonly="readonly" />
        <input type="hidden" id="program_vb_status" value="<?php echo($isProgramVB); ?>" readonly="readonly" />
        <input type="hidden" id="program_diskon_status" value="<?php echo($isProgramDiskon); ?>" readonly="readonly" />
        <input type="hidden" name="dari_konsinyasi" id="dari_konsinyasi" value="<?php echo($dariKonsinyasi); ?>" readonly="readonly" />
        <input type="hidden" name="id_tfk_konsinyasi" id="id_tfk_konsinyasi" value="<?php echo($idFakturKonsinyasi); ?>" readonly="readonly" />
        <!-- SweetAlert2 (wajib ada supaya Swal.fire jalan) -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            
        // limit
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
        
        // Set global variable untuk status program
        var FAKTUR_PROGRAM_STATUS = <?php echo($isProgramYa); ?>;
        var FAKTUR_PROGRAM_VB = <?php echo($isProgramVB); ?>;
        var FAKTUR_PROGRAM_DISKON = <?php echo($isProgramDiskon); ?>;
        var FAKTUR_DARI_KONSINYASI = <?php echo($isKonsinyasi); ?>;
        var FAKTUR_ID_KONSINYASI = '<?php echo($idFakturKonsinyasi); ?>';
        console.log('Status Program Faktur:', FAKTUR_PROGRAM_STATUS);
        console.log('Program VB:', FAKTUR_PROGRAM_VB, '| Program Diskon:', FAKTUR_PROGRAM_DISKON);
        console.log('Dari Konsinyasi:', FAKTUR_DARI_KONSINYASI, '| ID Konsinyasi:', FAKTUR_ID_KONSINYASI);
        console.log('jQuery loaded:', typeof jQuery !== 'undefined');
        
        // Function to open product modal - this is called by the "Pilih" button
        function openProductModal(nomor, outlet) {
            // Ambil nilai mitra dan cart dari parameter/hidden field
            var mitra = $("#" + outlet).val();
            var cart = $("#cartaddsales").val(); // Ambil cart yang berisi semua ID batch yang sudah dipilih
            var dariKonsi = $("#dari_konsinyasi").val();
            var idKonsi = $("#id_tfk_konsinyasi").val();
            var idTfk = $("#id_tfk_faktur").val(); // ID faktur saat ini untuk exclude dari booking
            
            console.log('Outlet param:', outlet, 'Mitra:', mitra, 'Cart:', cart, 'Konsinyasi:', dariKonsi, 'ID Konsi:', idKonsi);
            
            if (!mitra || mitra == '') {
                swal("Maaf!", "Pilih outlet dulu...", "error");
                return false;
            }
            
            // Load modal content with principle tabs
            $.ajax({
                url: usuper + "/modal/addsales/addsales.php",
                type: "POST",
                async: true,
                dataType: "text",
                cache: false,
                data: { 
                    "m": mitra, 
                    "x": nomor, 
                    "y": cart, 
                    "dari_konsinyasi": dariKonsi, 
                    "id_tfk_konsinyasi": idKonsi,
                    "id_tfk": idTfk
                },
                beforeSend: function() {
                    console.log('Loading modal content...');
                },
                success: function(data) {
                    console.log('Modal content loaded successfully');
                    $("#modal1 .modal-content").html(data);
                    $("#modal1").modal('show');
                },
                error: function(xhr, status, error) {
                    console.error('Error loading modal:', error);
                    swal("Error!", "Gagal memuat produk", "error");
                }
            });
            
            return false;
        }
        
        // Simple addsales function for backward compatibility
        function addsales(nomor, outlet) {
            return openProductModal(nomor, outlet);
        }
        
        console.log('openProductModal defined:', typeof openProductModal !== 'undefined');
        
        // Load existing items saat halaman pertama kali dibuka
        $(document).ready(function() {
            console.log('Document ready - loading existing items...');
            
            var idTfkFaktur = '<?php echo($code); ?>';

            // Bersihkan booking draft lama (sisa dari sesi sebelumnya yang tidak di-Simpan)
            // Item yang sudah tersimpan di DB tidak perlu booking, stok sudah terpotong
            $.ajax({
                url: usuper + '/ajax/addsales/savebooking.php',
                type: 'POST',
                data: { action: 'clear', id_tfk: idTfkFaktur },
                dataType: 'json',
                complete: function() {
                    // Baru load existing items setelah booking bersih
                    loadExistingItems();
                }
            });

            // Saat tab ditutup / refresh / navigasi pergi, bersihkan booking draft
            // Gunakan navigator.sendBeacon agar request tetap terkirim meski halaman menutup
            window.addEventListener('beforeunload', function() {
                var url = usuper + '/ajax/addsales/savebooking.php';
                var body = new URLSearchParams({ action: 'clear', id_tfk: idTfkFaktur });
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(url, body);
                }
            });
        });
        
        function loadExistingItems() {
            var fakturId = '<?php echo($code); ?>';
            
            $.ajax({
                url: usuper + "/ajax/loadExistingItems.php",
                type: "POST",
                data: { 
                    id_tfk: fakturId 
                },
                success: function(response) {
                    console.log('Existing items loaded:', response);
                    
                    if (response && response.trim() !== '' && response !== '0') {
                        // Parse response jika berupa JSON
                        try {
                            var items = JSON.parse(response);
                            
                            if (items.length > 0) {
                                $("#pilihoutlet").remove();
                                
                                // Set cart value dengan semua id_psd yang sudah ada
                                var cartIds = [];
                                var itemCount = 0;
                                
                                // Render each existing item
                                items.forEach(function(item, index) {
                                    var nomor = index + 1;
                                    cartIds.push(item.id_psd);
                                    
                                    // Create row HTML
                                    var rowHtml = createItemRow(nomor, item);
                                    $("#dataaddsales").append(rowHtml);
                                    
                                    // Populate data to row
                                    populateItemData(nomor, item);
                                    
                                    itemCount++;
                                });
                                
                                // Update cart and counter
                                $("#cartaddsales").val(cartIds.join('-'));
                                $("#jumaddsales").val(itemCount);
                                
                                // Calculate totals
                                calculateTotals();
                                
                                console.log('Loaded ' + itemCount + ' existing items');
                                console.log('Cart IDs:', cartIds.join('-'));
                            }
                        } catch(e) {
                            console.error('Error parsing items:', e);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading existing items:', error);
                }
            });
        }
        
        function createItemRow(nomor, item) {
            return `
            <tr id="traddsales${nomor}" class="itemproduct">
                <td>
                    <a href="javascript:void(0);" onclick="openProductModal(${nomor}, 'outlet'); return false;">
                        <div id="noproduct${nomor}">(${item.kode_pro}) ${item.nama_pro}</div>
                    </a>
                    <input type="hidden" name="kodestok[]" id="kodestok${nomor}" value="${item.id_psd}" readonly="readonly" />
                    <input type="hidden" name="product[]" id="product${nomor}" class="itemproduct" value="${item.id_pro}" readonly="readonly" />
                    <input type="hidden" name="prostok[]" id="prostok${nomor}" value="${item.sisa_psd}" readonly="readonly" />
                    <input type="hidden" name="namaproduk[]" id="pnamaproduk${nomor}" value="${item.nama_pro}" readonly="readonly" />
                </td>
                <td><div id="prodetail${nomor}">${item.nama_kpr} (${item.berat_pro} ${item.satuan_kpr})</div></td>
                <td><div id="nobcode${nomor}">${item.no_bcode}</div></td>
                <td><div id="gudang${nomor}">${item.gudang}</div></td>
                <td><div id="tgled${nomor}">${item.tgl_expired}</div></td>
                <td><input type="text" name="harga[]" id="pharga${nomor}" class="inputangka" value="${titik(item.harga_tfd)}" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                <td><input type="text" name="jumlah[]" id="pjumlah${nomor}" class="inputangka" value="${item.jumlah_tfd}" onchange="jumlahsales(${nomor})" onkeyup="angka(this)" placeholder="0" /></td>
                <td><div id="stoktampil${nomor}">${item.sisa_psd}</div></td>
                <td><input type="text" name="diskon[]" id="pdiskon${nomor}" class="inputangka" value="${item.diskon_tfd}" onchange="hitungsales(${nomor})" placeholder="0" readonly="readonly" /></td>
                <td><input type="text" name="total[]" id="ptotal${nomor}" class="inputtotal" value="${titik(item.total_tfd)}" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                <td><center><a onclick="delsales(${nomor})"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a></center></td>
            </tr>
            `;
        }
        
        function populateItemData(nomor, item) {
            $("#noproduct"+nomor).html('('+item.kode_pro+') '+item.nama_pro);
            $("#kodestok"+nomor).val(item.id_psd);
            $("#product"+nomor).val(item.id_pro);
            $("#prostok"+nomor).val(item.sisa_psd);
            $("#pnamaproduk"+nomor).val(item.nama_pro);
            $("#prodetail"+nomor).html(item.nama_kpr+' ('+item.berat_pro+' '+item.satuan_kpr+')');
            $("#nobcode"+nomor).html(item.no_bcode);
            $("#gudang"+nomor).html(item.gudang);
            $("#tgled"+nomor).html(item.tgl_expired);
            $("#stoktampil"+nomor).html(item.sisa_psd);
            $("#pharga"+nomor).val(titik(item.harga_tfd));
            $("#pjumlah"+nomor).val(item.jumlah_tfd);
            $("#pdiskon"+nomor).val(item.diskon_tfd);
            $("#ptotal"+nomor).val(titik(item.total_tfd));
        }
        
        function calculateTotals() {
            var subtotal = 0;
            
            $('input[name="total[]"]').each(function() {
                var val = $(this).val();
                if (val && val !== '') {
                    subtotal += parseInt(bersih(val));
                }
            });
            
            var ppn = Math.round((subtotal * 11) / 100);
            var grandTotal = subtotal + ppn;
            
            $("#pstotal").val(titik(subtotal));
            $("#pppn").val(titik(ppn));
            $("#pgtotal").val(titik(grandTotal));
        }
        
        </script>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <h5 id="section1" class="tx-semibold">Order Produk</h5>
        <p class="mg-b-25">Pilih produk yang akan dipesan kepada supplier.</p>
        <div class="row row-sm">
            <div class="col-sm-12">
                <div class="table-responsive">
                <table class="tabeltransaksi">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Detail</th>
                            <th>Batchcode</th>
                            <th>Gudang</th>
                            <th>Tgl. ED</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Stok</th>
                            <th>Diskon</th>
                            <th>Total</th>
                            <th><center>Act</center></th>
                        </tr>
                    </thead>
                    <tbody id="dataaddsales">
                    	<tr id="pilihoutlet"><td colspan="10">Pilih outlet dulu...</td></tr>
					</tbody>
                    <tfoot>
                    	<tr>
                            <td></td>
                        	<td colspan="8"><div align="right"><b>SUBTOTAL</b></div></td>
                            <td><input type="text" name="pstotal" id="pstotal" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                        	<td></td>
                        </tr>
                    	<tr>
                            <td></td>
                        	<td colspan="8"><div align="right"><b><span id="taxLabel">PPN (11%)</span></b></div></td>
                            <td><input type="text" name="pppn" id="pppn" class="inputtotal" onkeyup="angka(this)" placeholder="0"  /></td>
                        	<td></td>
                        </tr>
                    	<tr>
                            <td></td>
                        	<td colspan="8"><div align="right"><b>TOTAL</b></div></td>
                            <td><input type="text" name="pgtotal" id="pgtotal" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
                        	<td></td>
                        </tr>
                    </tfoot>
                </table>
                </div>
                <input type="hidden" name="minorder" id="minorder" value="" readonly="readonly" />
                <input type="hidden" name="diskon1" id="diskon1" value="" readonly="readonly" />
                <input type="hidden" name="diskon2" id="diskon2" value="" readonly="readonly" />
                <input type="hidden" name="cartaddsales" id="cartaddsales" value="" readonly="readonly" />
                <input type="hidden" name="jumaddsales" id="jumaddsales" value="0" readonly="readonly" />
                <input type="hidden" id="limit_outlet" name="limit_outlet" value="" readonly="readonly" />
                <input type="hidden" id="id_tfk_faktur" value="<?php echo($code); ?>" readonly="readonly" />
                <a onclick="addmaximal('addsales', 'outlet', 200)" style="cursor: pointer;"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
            </div>
        </div>
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo("$sistem/fsales"); ?>" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                <div id="imgloading"></div>
            </div>
		</div>
    </div>
</div>
</form>

<!-- Principle Selection Modal -->
<div class="modal fade" id="principleModal" tabindex="-1" role="dialog" aria-labelledby="principleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="principleModalLabel">
                    <i class="fas fa-building"></i> Pilih Principle
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-4 text-muted">Silakan pilih principle untuk menampilkan produk yang sesuai:</p>
                <div class="row" id="principleList">
                    <?php
                    try {
                        $principle_query = $conn->prepare("SELECT * FROM master_principle ORDER BY nama_principle ASC");
                        $principle_query->execute();
                        $principles = $principle_query->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach($principles as $principle) {
                            echo '<div class="col-md-6 mb-3">';
                            echo '<div class="principle-card card h-100" onclick="selectPrinciple(\''.$principle['id_mp'].'\', \''.$principle['nama_principle'].'\')" style="cursor: pointer; transition: all 0.3s ease;">';
                            echo '<div class="card-body text-center">';
                            echo '<div class="principle-icon mb-3">';
                            echo '<i class="fas fa-building fa-3x text-primary"></i>';
                            echo '</div>';
                            echo '<h5 class="card-title text-primary">'.$principle['nama_principle'].'</h5>';
                            echo '<p class="card-text text-muted small">ID: '.$principle['id_mp'].'</p>';
                            echo '<div class="principle-hover-effect">';
                            echo '<i class="fas fa-arrow-right text-success"></i> Pilih Principle';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } catch(PDOException $e) {
                        echo '<div class="col-12"><div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Tidak dapat memuat data principle.</div></div>';
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.principle-card {
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.principle-card:hover {
    border-color: #007bff;
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,123,255,0.15);
}

.principle-hover-effect {
    opacity: 0;
    transition: opacity 0.3s ease;
    color: #28a745;
    font-weight: 600;
}

.principle-card:hover .principle-hover-effect {
    opacity: 1;
}

.principle-icon {
    transition: transform 0.3s ease;
}

.principle-card:hover .principle-icon {
    transform: scale(1.1);
}

#principleModal .modal-dialog {
    animation: slideInDown 0.3s ease;
}

@keyframes slideInDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>

<script>
function showPrincipleModal() {
    $('#principleModal').modal('show');
}

function selectPrinciple(principleId, principleName) {
    // Simpan principle yang dipilih
    sessionStorage.setItem('selectedPrinciple', principleId);
    sessionStorage.setItem('selectedPrincipleName', principleName);
    
    // Tutup modal principle
    $('#principleModal').modal('hide');
    
    // Show loading message
    swal({
        title: 'Loading...',
        text: 'Menambahkan produk untuk ' + principleName,
        type: 'info',
        showConfirmButton: false,
        timer: 1500
    });
    
    // Setelah modal tertutup, panggil addmaximal
    $('#principleModal').on('hidden.bs.modal', function () {
        // Remove event listener setelah digunakan
        $(this).off('hidden.bs.modal');
        
        // Panggil addmaximal dengan principle yang dipilih
        addmaximalWithPrinciple('addsales', 'outlet', 200, principleId);
    });
}

function addmaximalWithPrinciple(menu, outlet, maksimal, principleId) {
    var total = $('.itemproduct').length;
    var jumlah = $("#jum"+menu).val();
    var diskon = $("#diskon1").val();
    var mitra = $("#"+outlet).val();
    
    if(mitra==''){
        swal("Maaf!", "Pilih "+outlet+" dulu...", "error");
    } else {
        if(parseInt(total)>=parseInt(maksimal)){
            swal("Maaf!", "Jumlah melebihi maksimal...", "error");
        } else {
            $.ajax({
                type: "GET",
                url: usuper+"/ajax/"+menu+"/"+menu+".php",
                data: { "jumlah" : jumlah, "diskon" : diskon, "principle" : principleId },
                success: function(data){
                    $("#pilihoutlet").remove();
                    $("#data"+menu).append(data);
                    var total = parseInt(jumlah) + 1;
                    $("#jum"+menu).val(total);
                }
            });
        }
    }
}

// Update addmaximal untuk langsung menambah row tanpa popup principle
function addmaximalDirect(menu, outlet, maksimal) {
    var total = $('.itemproduct').length;
    var jumlah = $("#jum"+menu).val();
    var diskon = $("#diskon1").val();
    var mitra = $("#"+outlet).val();
    
    if(mitra==''){
        swal("Maaf!", "Pilih "+outlet+" dulu...", "error");
    } else {
        if(parseInt(total)>=parseInt(maksimal)){
            swal("Maaf!", "Jumlah melebihi maksimal...", "error");
        } else {
            $.ajax({
                type: "GET",
                url: usuper+"/ajax/"+menu+"/"+menu+".php",
                data: { "jumlah" : jumlah, "diskon" : diskon },
                success: function(data){
                    $("#pilihoutlet").remove();
                    $("#data"+menu).append(data);
                    var total = parseInt(jumlah) + 1;
                    $("#jum"+menu).val(total);
                }
            });
        }
    }
}
</script>

<!-- Basic Product Selection Modal -->
<div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal1Label">Select Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="modal1content">
                    Loading products...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// AUTO PROGRAM SCRIPT - Ditambahkan sesuai permintaan: otomatis aktif berdasarkan outlet dan item
$(document).ready(function() {
    var autoPrograms = <?php echo $autoProgramsJson; ?>;
    var programByProduct = {};
    if (autoPrograms && autoPrograms.length > 0) {
        autoPrograms.forEach(function(prog) {
            programByProduct[prog.id_pro] = prog;
        });
    }

    function applyAutoProgram() {
        if (Object.keys(programByProduct).length === 0) return;

        // Hitung total qty per program yang ada di cart
        var qtyPerProgram = {};
        $('tr[id^="traddsales"]').each(function() {
            var nomor = $(this).attr('id').replace('traddsales', '');
            var idProInput = $('#product' + nomor);
            if (idProInput.length > 0) {
                var idPro = idProInput.val();
                if (programByProduct[idPro]) {
                    var qty = parseInt($('#pjumlah' + nomor).val()) || 0;
                    var idPp = programByProduct[idPro].id_pp;
                    qtyPerProgram[idPp] = (qtyPerProgram[idPp] || 0) + qty;
                }
            }
        });

        // Terapkan harga program jika memenuhi min_qty
        $('tr[id^="traddsales"]').each(function() {
            var nomor = $(this).attr('id').replace('traddsales', '');
            var idProInput = $('#product' + nomor);
            
            if (idProInput.length > 0) {
                var idPro = idProInput.val();
                if (programByProduct[idPro]) {
                    var prog = programByProduct[idPro];
                    var hrgProg = parseInt(prog.harga_program);
                    var diskonPersen = parseInt(prog.diskon_persen) || 0;
                    
                    var currentHarga = bersih($('#pharga' + nomor).val());
                    var currentDiskonRaw = $('#pdiskon' + nomor).val();
                    var totalQtyProgram = qtyPerProgram[prog.id_pp] || 0;
                    
                    if (totalQtyProgram >= parseInt(prog.min_qty)) {
                        // Simpan harga & diskon asli di data attribute agar bisa direvert
                        if (typeof $('#pharga' + nomor).attr('data-harga-asli') === 'undefined') {
                            $('#pharga' + nomor).attr('data-harga-asli', currentHarga);
                        }
                        if (typeof $('#pdiskon' + nomor).attr('data-diskon-asli') === 'undefined') {
                            $('#pdiskon' + nomor).attr('data-diskon-asli', currentDiskonRaw || "");
                        }
                        
                        var needsUpdate = false;
                        if (hrgProg > 0 && parseInt(currentHarga) !== hrgProg) {
                            $('#pharga' + nomor).val(titik(hrgProg));
                            needsUpdate = true;
                        }
                        
                        if (diskonPersen > 0) {
                            var stringDiskon = diskonPersen.toString();
                            if (currentDiskonRaw !== stringDiskon) {
                                $('#pdiskon' + nomor).val(stringDiskon);
                                needsUpdate = true;
                            }
                        }

                        if (needsUpdate) {
                            if (typeof hitungsales === 'function') hitungsales(nomor);
                            else if (typeof jumlahsales === 'function') jumlahsales(nomor);
                        }
                    } else {
                        // Revert ke harga/diskon asli jika sempat diubah
                        var hargaAsli = $('#pharga' + nomor).attr('data-harga-asli');
                        var diskonAsli = $('#pdiskon' + nomor).attr('data-diskon-asli');
                        var needsUpdate = false;
                        
                        if (typeof hargaAsli !== 'undefined' && parseInt(currentHarga) !== parseInt(hargaAsli)) {
                            $('#pharga' + nomor).val(titik(hargaAsli));
                            needsUpdate = true;
                        }
                        if (typeof diskonAsli !== 'undefined' && currentDiskonRaw !== diskonAsli) {
                            $('#pdiskon' + nomor).val(diskonAsli);
                            needsUpdate = true;
                        }
                        
                        if (needsUpdate) {
                            if (typeof hitungsales === 'function') hitungsales(nomor);
                            else if (typeof jumlahsales === 'function') jumlahsales(nomor);
                        }
                    }
                }
            }
        });
    }

    // Eksekusi tiap detik supaya mencover barang yang baru di add via AJAX atau update jumlah qty
    setInterval(applyAutoProgram, 1000);
});
</script>
