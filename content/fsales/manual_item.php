<?php
	$uniq	= $secu->injection(@$_GET['keycode']);
	$code	= base64_decode($uniq);
	
	// Ambil data faktur asli
	$read	= $conn->prepare("SELECT A.id_tfk, A.sj_tfk, A.kode_tfk, A.id_out, A.id_mr, A.ket_mr, B.nama_out 
								FROM transaksi_faktur A 
								LEFT JOIN outlet B ON A.id_out = B.id_out 
								WHERE A.id_tfk=:code");
	$read->bindParam(':code', $code, PDO::PARAM_STR);
	$read->execute();
	$view	= $read->fetch(PDO::FETCH_ASSOC);

	if(!$view || (int)$view['id_mr'] < 1) {
		echo '<div class="alert alert-danger">Faktur tidak ditemukan atau tidak memiliki data MR.</div>';
		return;
	}

	// Ambil nama MR
	$mrName = '';
	try {
		$mrq = $conn->prepare("SELECT nama_mr, area FROM master_mr_baru WHERE id_mr=:id");
		$mrq->bindParam(':id', $view['id_mr'], PDO::PARAM_INT);
		$mrq->execute();
		$mrData = $mrq->fetch(PDO::FETCH_ASSOC);
		if($mrData) $mrName = $mrData['nama_mr'] . ' (' . $mrData['area'] . ')';
	} catch(Exception $e) {}

	// Ambil detail item faktur asli
	$items = [];
	try {
		$qItems = $conn->prepare("SELECT D.id_tfd, D.id_psd, D.id_pro, D.jumlah_tfd, D.harga_tfd, D.diskon_tfd, D.total_tfd, 
								P.nama_pro, S.nama_spr,
								COALESCE(H.hargap_phg, D.harga_tfd) AS harga_display
							FROM transaksi_fakturdetail D
							LEFT JOIN produk P ON D.id_pro = P.id_pro
							LEFT JOIN satuan_produk S ON P.id_spr = S.id_spr
							LEFT JOIN produk_harga H ON D.id_pro = H.id_pro AND H.status_phg = 'Active'
								WHERE D.id_tfk = :code
								ORDER BY D.id_tfd ASC");
		$qItems->bindParam(':code', $code, PDO::PARAM_STR);
		$qItems->execute();
		$items = $qItems->fetchAll(PDO::FETCH_ASSOC);
	} catch(Exception $e) { $items = []; }

	// Cek apakah sudah pernah dibuat manual sebelumnya
	$existingManual = null;
	try {
		$cekManual = $conn->prepare("SELECT id_tfm, total_tfm, created_at FROM transaksi_faktur_manual WHERE id_tfk=:code ORDER BY id_tfm DESC LIMIT 1");
		$cekManual->bindParam(':code', $code, PDO::PARAM_STR);
		$cekManual->execute();
		$existingManual = $cekManual->fetch(PDO::FETCH_ASSOC);
	} catch(Exception $e) {}
?>
<form id="formManualFaktur" action="#" method="post" autocomplete="off">
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Penjualan</a></li>
                <li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/fsales">Faktur Penjualan</a></li>
                <li class="breadcrumb-item active" aria-current="page"> SPB</li>
            </ol>
        </nav>
        <h4 class="content-title"><i class="fa fa-check text-danger"></i> SPB</h4>
    </div>
</div>
<div class="content-body"
    <div class="component-section no-code">
        <?php if($existingManual): ?>
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i> SPB ini sudah pernah dibuat manual pada <strong><?php echo $existingManual['created_at']; ?></strong> 
            (Total: Rp <?php echo number_format($existingManual['total_tfm'], 0, ',', '.'); ?>). 
            Menyimpan lagi akan membuat record manual baru.
        </div>
        <?php endif; ?>

        <div class="row mg-b-20">
            <div class="col-sm-3">
                <label><strong>Nomor SPB</strong></label>
                <input type="text" name="kode_manual" class="form-control" placeholder="Masukkan No. SPB" required />
            </div>
            <div class="col-sm-3">
                <label><strong>Outlet</strong></label>
                <p><?php echo htmlspecialchars($view['nama_out']); ?></p>
            </div>
            <div class="col-sm-3">
                <label><strong>MR</strong></label>
                <p><span class="badge badge-danger"><?php echo htmlspecialchars($mrName); ?></span></p>
            </div>
        </div>

        <input type="hidden" name="namamodal" value="fsales_manual" />
        <input type="hidden" name="namamenu" value="save_manual" />
        <input type="hidden" name="keycode" value="<?php echo $uniq; ?>" />
        <input type="hidden" name="id_mr" value="<?php echo (int)$view['id_mr']; ?>" />
        <input type="hidden" name="ket_mr" value="<?php echo htmlspecialchars($view['ket_mr']); ?>" />

        <hr style="border: 2px solid #dc3545;">
        <h5 class="text-danger mg-b-15"><i class="fa fa-list"></i> Item Faktur (Harga & Diskon bisa diubah)</h5>

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="tblManualItems">
                <thead class="bg-danger text-white">
                    <tr>
                        <th width="5%"><center>#</center></th>
                        <th width="30%">Produk</th>
                        <th width="10%"><center>Qty</center></th>
                        <th width="15%">Harga</th>
                        <th width="15%">Diskon (%)</th>
                        <th width="20%"><div align="right">Total</div></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grandTotal = 0;
                    $no = 0;
                    foreach($items as $itm): 
                        $no++;
                    ?>
                    <tr>
                        <td><center><?php echo $no; ?></center></td>
                        <td>
                            <?php echo htmlspecialchars($itm['nama_pro']); ?> 
                            <small class="text-muted">(<?php echo htmlspecialchars($itm['nama_spr']); ?>)</small>
                            <input type="hidden" name="id_psd[]" value="<?php echo $itm['id_psd']; ?>" />
                            <input type="hidden" name="id_pro[]" value="<?php echo $itm['id_pro']; ?>" />
                            <input type="hidden" name="jumlah[]" value="<?php echo (int)$itm['jumlah_tfd']; ?>" />
                        </td>
                        <td><center><?php echo number_format($itm['jumlah_tfd'], 0, ',', '.'); ?></center></td>
                        <td>
                            <input type="text" name="harga[]" class="form-control text-right harga-manual" 
                                   value="<?php echo number_format($itm['harga_display'], 0, ',', '.'); ?>" 
                                   onkeyup="hitungTotalManual(this)" />
                        </td>
                        <td>
                            <input type="text" name="diskon[]" class="form-control text-center diskon-manual" 
                                   value="<?php echo $itm['diskon_tfd']; ?>" 
                                   onkeyup="hitungTotalManual(this)" />
                        </td>
                        <td>
                            <div align="right">
                                <input type="text" name="total[]" class="form-control text-right total-manual" 
                                       value="<?php echo number_format($itm['total_tfd'], 0, ',', '.'); ?>" readonly />
                            </div>
                        </td>
                    </tr>
                    <?php 
                        $grandTotal += (int)$itm['total_tfd'];
                    endforeach; 
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5"><div align="right"><strong>Grand Total</strong></div></td>
                        <td><div align="right"><strong id="manualGrandTotal" style="font-size:18px; color:#dc3545;"><?php echo number_format($grandTotal, 0, ',', '.'); ?></strong></div></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <input type="hidden" name="pstotal" id="pstotal" value="<?php echo $grandTotal; ?>" />
        <input type="hidden" name="pppn" id="pppn" value="0" />
        <input type="hidden" name="pgtotal" id="pgtotal" value="<?php echo $grandTotal; ?>" />

        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row row-sm">
            <div class="col-sm-12">
                <a href="<?php echo $sistem; ?>/fsales" title="Batal"><button type="button" class="btn btn-secondary">Batal</button></a>
                <button type="submit" id="bsave" class="btn btn-danger"><i class="fa fa-check"></i> Simpan SPB</button>
                <div id="imgloading"></div>
            </div>
        </div>
    </div>
</div>
</form>

<script>
function hitungTotalManual(el) {
    var row = el.closest('tr');
    var jumlah = parseInt(row.querySelector('input[name="jumlah[]"]').value) || 0;
    var hargaStr = row.querySelector('input[name="harga[]"]').value.replace(/\./g, '');
    var harga = parseInt(hargaStr) || 0;
    var diskon = parseFloat(row.querySelector('input[name="diskon[]"]').value) || 0;
    
    var subtotal = jumlah * harga;
    var diskonNominal = Math.round(subtotal * diskon / 100);
    var total = subtotal - diskonNominal;
    
    row.querySelector('input[name="total[]"]').value = formatRibuan(total);
    
    hitungGrandTotal();
}

function hitungGrandTotal() {
    var totals = document.querySelectorAll('.total-manual');
    var sub = 0;
    totals.forEach(function(el) {
        sub += parseInt(el.value.replace(/\./g, '')) || 0;
    });
    
    document.getElementById('manualGrandTotal').textContent = formatRibuan(sub);
    document.getElementById('pstotal').value = sub;
    document.getElementById('pppn').value = 0;
    document.getElementById('pgtotal').value = sub;
}

function formatRibuan(n) {
    return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Format harga saat ketik
document.querySelectorAll('.harga-manual').forEach(function(el) {
    el.addEventListener('input', function() {
        var val = this.value.replace(/\./g, '').replace(/[^0-9]/g, '');
        this.value = formatRibuan(parseInt(val) || 0);
    });
});

// Form submit via AJAX
document.getElementById('formManualFaktur').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('bsave');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';
    
    var formData = new FormData(form);
    
    fetch('<?php echo $sistem; ?>/modal/fsales/action_manual.php', {
        method: 'POST',
        body: formData
    })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if(res.status === 'Success') {
            if(typeof swal !== 'undefined') {
                swal({ title: "Berhasil!", text: res.message, icon: "success", timer: 2000, buttons: false });
            } else { alert(res.message); }
            setTimeout(function() { window.location.href = '<?php echo $sistem; ?>/fsales'; }, 2000);
        } else {
            if(typeof swal !== 'undefined') {
                swal({ title: "Error!", text: res.message, icon: "error" });
            } else { alert(res.message); }
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-check"></i> Simpan Faktur Manual';
        }
    })
    .catch(function(err) {
        alert('Terjadi kesalahan: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fa fa-check"></i> Simpan Faktur Manual';
    });
});
</script>
