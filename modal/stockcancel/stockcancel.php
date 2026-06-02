<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	//ACCESS DATA
	$admin	= $secu->injection(@$_COOKIE['adminkuy']);
	$kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
	$valid	= $secu->validadmin($admin, $kunci);
	//GET DATA
	$act	= $secu->injection(@$_GET['act']);
	$kode	= $secu->injection(@$_GET['keycode']);
	
	if($valid == false){
		echo '<div class="alert alert-danger">Session login anda habis...</div>';
	} else {
		switch($act){
			case 'view':
				// Query untuk mengambil detail data
				$qDetail = "SELECT 
								A.*,
								B.nama_pro,
								B.kode_produk_jadi,
								C.nama_adm AS cancel_by_name,
								D.nama_adm AS created_by_name
							FROM produk_stockdetail_cancel AS A
							LEFT JOIN produk AS B ON A.id_pro = B.id_pro
							LEFT JOIN adminz AS C ON A.cancel_by = C.id_adm
							LEFT JOIN adminz AS D ON A.created_by = D.id_adm
							WHERE A.id_psc = :kode";
				$detail = $conn->prepare($qDetail);
				$detail->bindParam(':kode', $kode, PDO::PARAM_STR);
				$detail->execute();
				$hasil = $detail->fetch(PDO::FETCH_ASSOC);
				
				if($hasil){
					// Format tanggal
					$tglFaktur = !empty($hasil['tgl_faktur']) ? date('d-m-Y', strtotime($hasil['tgl_faktur'])) : '-';
					$tglExpired = !empty($hasil['tgl_expired']) ? date('d-m-Y', strtotime($hasil['tgl_expired'])) : '-';
					$tglPsd = !empty($hasil['tgl_psd']) ? date('d-m-Y', strtotime($hasil['tgl_psd'])) : '-';
					$cancelAt = !empty($hasil['cancel_at']) ? date('d-m-Y H:i:s', strtotime($hasil['cancel_at'])) : '-';
					$createdAt = !empty($hasil['created_at']) ? date('d-m-Y H:i:s', strtotime($hasil['created_at'])) : '-';
					
					// Badge konsinyasi
					$badgeKonsi = ($hasil['dari_konsinyasi'] === 'ya') 
						? '<span class="badge badge-warning">Ya</span>' 
						: '<span class="badge badge-secondary">Tidak</span>';
?>
<div class="row">
	<div class="col-md-6">
		<h6 class="text-primary mb-3"><i class="fa fa-file-invoice"></i> Informasi Faktur</h6>
		<table class="table table-sm table-borderless">
			<tr>
				<td width="40%"><strong>Kode Faktur</strong></td>
				<td>: <?php echo $hasil['kode_faktur']; ?></td>
			</tr>
			<tr>
				<td><strong>ID Faktur</strong></td>
				<td>: <small class="text-muted"><?php echo $hasil['id_tfk']; ?></small></td>
			</tr>
			<tr>
				<td><strong>Tanggal Faktur</strong></td>
				<td>: <?php echo $tglFaktur; ?></td>
			</tr>
			<tr>
				<td><strong>Dari Konsinyasi</strong></td>
				<td>: <?php echo $badgeKonsi; ?></td>
			</tr>
			<?php if($hasil['dari_konsinyasi'] === 'ya' && !empty($hasil['id_tfk_konsinyasi'])): ?>
			<tr>
				<td><strong>ID Faktur Konsinyasi</strong></td>
				<td>: <small class="text-muted"><?php echo $hasil['id_tfk_konsinyasi']; ?></small></td>
			</tr>
			<?php endif; ?>
		</table>
	</div>
	<div class="col-md-6">
		<h6 class="text-success mb-3"><i class="fa fa-box"></i> Informasi Produk</h6>
		<table class="table table-sm table-borderless">
			<tr>
				<td width="40%"><strong>Nama Produk</strong></td>
				<td>: <?php echo $hasil['nama_pro']; ?></td>
			</tr>
			<tr>
				<td><strong>Kode Produk</strong></td>
				<td>: <?php echo $hasil['kode_produk_jadi']; ?></td>
			</tr>
			<tr>
				<td><strong>Batch/Barcode</strong></td>
				<td>: <?php echo $hasil['no_bcode']; ?></td>
			</tr>
			<tr>
				<td><strong>Tanggal Expired</strong></td>
				<td>: <?php echo $tglExpired; ?></td>
			</tr>
			<tr>
				<td><strong>Tanggal Stok Masuk</strong></td>
				<td>: <?php echo $tglPsd; ?></td>
			</tr>
		</table>
	</div>
</div>

<hr>

<div class="row">
	<div class="col-md-6">
		<h6 class="text-danger mb-3"><i class="fa fa-times-circle"></i> Informasi Cancel</h6>
		<table class="table table-sm table-borderless">
			<tr>
				<td width="40%"><strong>Jumlah Cancel</strong></td>
				<td>: <span class="badge badge-danger" style="font-size: 14px;"><?php echo $data->angka($hasil['jumlah_cancel']); ?></span></td>
			</tr>
			<tr>
				<td><strong>Gudang</strong></td>
				<td>: <?php echo $hasil['gudang'] ?: '-'; ?></td>
			</tr>
			<tr>
				<td><strong>Status</strong></td>
				<td>: <span class="badge badge-secondary"><?php echo ucfirst($hasil['status']); ?></span></td>
			</tr>
		</table>
	</div>
	<div class="col-md-6">
		<h6 class="text-info mb-3"><i class="fa fa-user"></i> Informasi Audit</h6>
		<table class="table table-sm table-borderless">
			<tr>
				<td width="40%"><strong>Cancel By</strong></td>
				<td>: <?php echo $hasil['cancel_by_name'] ?: $hasil['cancel_by']; ?></td>
			</tr>
			<tr>
				<td><strong>Cancel At</strong></td>
				<td>: <?php echo $cancelAt; ?></td>
			</tr>
			<tr>
				<td><strong>Created At</strong></td>
				<td>: <?php echo $createdAt; ?></td>
			</tr>
		</table>
	</div>
</div>

<hr>

<div class="row">
	<div class="col-12">
		<h6 class="text-warning mb-3"><i class="fa fa-comment"></i> Keterangan Cancel</h6>
		<div class="alert alert-light border">
			<?php echo nl2br(htmlspecialchars($hasil['keterangan_cancel'])) ?: '<em class="text-muted">Tidak ada keterangan</em>'; ?>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-12">
		<h6 class="text-secondary mb-3"><i class="fa fa-database"></i> Referensi ID</h6>
		<table class="table table-sm table-bordered">
			<tr>
				<td width="25%"><strong>ID StockCancel (id_psc)</strong></td>
				<td><code><?php echo $hasil['id_psc']; ?></code></td>
			</tr>
			<tr>
				<td><strong>ID StokDetail (id_psd)</strong></td>
				<td><code><?php echo $hasil['id_psd']; ?></code></td>
			</tr>
			<tr>
				<td><strong>ID Transaksi Receive (id_trd)</strong></td>
				<td><code><?php echo $hasil['id_trd'] ?: '-'; ?></code></td>
			</tr>
			<tr>
				<td><strong>ID Produk (id_pro)</strong></td>
				<td><code><?php echo $hasil['id_pro']; ?></code></td>
			</tr>
		</table>
	</div>
</div>
<?php
				} else {
					echo '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> Data tidak ditemukan</div>';
				}
			break;
			
			default:
				echo '<div class="alert alert-danger">Action tidak valid</div>';
			break;
		}
	}
	$conn = $base->close();
?>
