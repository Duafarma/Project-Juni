<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo($data->sistem('url_sis')); ?>/home"><i class="fa fa-home"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="#">Konsinyasi</a></li>
                <li class="breadcrumb-item active" aria-current="page">Stok Detail Konsinyasi</li>
            </ol>
        </nav>
        <h4 class="content-title content-title-xs">Stok Detail Konsinyasi</h4>
    </div>
</div>
<style>
	.card-modern {
		border-radius: 10px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.08);
		border: none;
		margin-bottom: 20px;
	}
	.card-header-modern {
		background: #5a67d8;
		color: white;
		border-radius: 10px 10px 0 0 !important;
		padding: 20px;
		border: none;
	}
	.card-body-modern {
		padding: 25px;
		background: white;
		border-radius: 0 0 10px 10px;
	}
	.btn-modern {
		border-radius: 20px;
		padding: 8px 20px;
		font-weight: 500;
		transition: all 0.3s;
		box-shadow: 0 2px 5px rgba(0,0,0,0.1);
	}
	.btn-modern:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 10px rgba(0,0,0,0.15);
	}
	.table-modern {
		border-collapse: separate;
		border-spacing: 0;
		border-radius: 10px;
		overflow: hidden;
	}
	.table-modern thead th {
		background: #5a67d8;
		color: white;
		font-weight: 600;
		text-transform: uppercase;
		font-size: 12px;
		letter-spacing: 0.5px;
		border: none;
		padding: 15px 10px;
	}
	.table-modern tbody tr {
		transition: all 0.3s;
		border-bottom: 1px solid #f0f0f0;
	}
	.table-modern tbody tr:hover {
		background: #f8f9ff;
		transform: scale(1.01);
		box-shadow: 0 2px 8px rgba(90, 103, 216, 0.1);
	}
	.table-modern tbody td {
		padding: 15px 10px;
		vertical-align: middle;
		border: none;
	}
	.badge-modern {
		padding: 6px 15px;
		border-radius: 20px;
		font-weight: 500;
		font-size: 11px;
		letter-spacing: 0.5px;
	}
	.badge-active {
		background: #48bb78;
		color: white;
	}
	.badge-inactive {
		background: #f56565;
		color: white;
	}
	.stat-card {
		background: white;
		border-radius: 10px;
		padding: 20px;
		box-shadow: 0 2px 10px rgba(0,0,0,0.08);
		margin-bottom: 20px;
		border-left: 4px solid #5a67d8;
	}
	.stat-number {
		font-size: 28px;
		font-weight: 700;
		color: #5a67d8;
	}
	.stat-label {
		color: #666;
		font-size: 13px;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}
	.dataTables_wrapper .dataTables_filter input {
		border-radius: 20px;
		padding: 8px 20px;
		border: 2px solid #e0e0e0;
	}
	.dataTables_wrapper .dataTables_length select {
		border-radius: 10px;
		padding: 5px 15px;
		border: 2px solid #e0e0e0;
	}
</style>
<div class="content-body">
	<div class="row mg-b-20">
        <div class="col-sm-12">
			<a href="<?php echo($data->sistem('url_sis').'/stokkonsinyasi'); ?>"><button class="btn btn-info btn-modern btn-xs"><i class="fa fa-sync-alt"></i> Refresh Data</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/fsalesk'); ?>"><button class="btn btn-primary btn-modern btn-xs"><i class="fa fa-arrow-left"></i> Kembali ke Faktur</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/laporan/excel_stokkonsinyasi.php'); ?>" target="_blank"><button class="btn btn-success btn-modern btn-xs"><i class="fa fa-file-excel"></i> Download Excel</button></a>
			<a href="<?php echo($data->sistem('url_sis').'/laporan/pdf_stokkonsinyasi.php'); ?>" target="_blank"><button class="btn btn-danger btn-modern btn-xs"><i class="fa fa-file-pdf"></i> Print PDF</button></a>
        </div>
    </div>
    
    <?php require_once('config/frame/alert.php'); ?>
    <?php $filterSJ = isset($_GET['sj_filter']) ? trim($_GET['sj_filter']) : ''; ?>
    
    <!-- Filter Nomor SJ Konsinyasi -->
    <div class="card card-modern mb-3">
		<div class="card-body-modern" style="padding:15px 25px;">
			<div class="row align-items-center">
				<div class="col-sm-4">
					<label class="mb-1" style="font-weight:600;"><i class="fa fa-filter"></i> Filter Nomor SJ Konsinyasi</label>
					<select id="filterSJ" class="form-control">
						<option value="">-- Tampilkan Semua --</option>
						<?php
							$qSJ = "SELECT DISTINCT tfk.sj_tfk, outl.nama_out
									FROM transaksi_faktur_konsinyasi tfk
									LEFT JOIN outlet outl ON tfk.id_out = outl.id_out
									INNER JOIN produk_stokdetail_konsinyasi psk ON psk.id_tfk = tfk.id_tfk
									WHERE psk.sisa_psd > 0 AND tfk.sj_tfk IS NOT NULL AND tfk.sj_tfk != ''
									ORDER BY tfk.sj_tfk ASC";
							$rSJ = $conn->query($qSJ);
							while($rowSJ = $rSJ->fetch(PDO::FETCH_ASSOC)){
								$sel = ($filterSJ === $rowSJ['sj_tfk']) ? 'selected' : '';
								echo '<option value="'.htmlspecialchars($rowSJ['sj_tfk']).'" '.$sel.'>'
									.htmlspecialchars($rowSJ['sj_tfk'])
									.($rowSJ['nama_out'] ? ' - '.$rowSJ['nama_out'] : '')
									.'</option>';
							}
						?>
					</select>
				</div>
				<div class="col-sm-2" style="margin-top:22px;">
					<a href="<?php echo $data->sistem('url_sis'); ?>/stokkonsinyasi" class="btn btn-secondary btn-modern btn-sm"><i class="fa fa-times"></i> Reset</a>
				</div>
			</div>
		</div>
	</div>
	<script>
	document.getElementById('filterSJ').addEventListener('change', function(){
		var val = this.value;
		if(val){
			window.location.href = '<?php echo $data->sistem('url_sis'); ?>/stokkonsinyasi?sj_filter=' + encodeURIComponent(val);
		} else {
			window.location.href = '<?php echo $data->sistem('url_sis'); ?>/stokkonsinyasi';
		}
	});
	</script>
    
    <div class="card card-modern">
		<div class="card-header-modern">
			<h5 class="mb-0"><i class="fa fa-box-open"></i> Data Stok Detail Konsinyasi</h5>
			<small>Riwayat pengeluaran barang konsinyasi per item</small>
		</div>
		<div class="card-body-modern">
			<div class="table-responsive">
				<table class="table table-modern mg-b-0" id="tblstokkonsinyasi">
					<thead>
						<tr>
							<th><center>No</center></th>
							<th><center>Tanggal</center></th>
							<th>Nomor Faktur</th>
							<th>Outlet</th>
							<th><center>Rentang Waktu</center></th>
							<th>Nama Produk</th>
							<th>Barcode</th>
							<th><center>Expired</center></th>
							<th><div align="right">Stok</div></th>
							<th><center>Status</center></th>
						</tr>
					</thead>
					<tbody>
					<?php
						$no = 1;
						$total_keluar_all = 0;
						
						$qData = "SELECT 
									psk.id_psd,
									psk.tgl_psd,
									psk.no_bcode,
									psk.tgl_expired,
									psk.masuk_psd,
									psk.keluar_psd,
									psk.sisa_psd,
									psk.status_barang,
									psk.id_pro,
									psk.id_tfk,
									pro.kode_pro,
									pro.nama_pro,
									tfk.sj_tfk,
									tfk.tgl_tfk,
									outl.nama_out
								FROM produk_stokdetail_konsinyasi psk
								LEFT JOIN produk pro ON psk.id_pro = pro.id_pro
								LEFT JOIN transaksi_faktur_konsinyasi tfk ON psk.id_tfk = tfk.id_tfk
								LEFT JOIN outlet outl ON tfk.id_out = outl.id_out
								WHERE psk.sisa_psd > 0
							ORDER BY outl.nama_out ASC, tfk.tgl_tfk ASC, pro.kode_pro ASC";
					if(!empty($filterSJ)){
						$qData = str_replace("WHERE psk.sisa_psd > 0", "WHERE psk.sisa_psd > 0 AND tfk.sj_tfk = :sj_filter", $qData);
					}
					$records = $conn->prepare($qData);
					if(!empty($filterSJ)) $records->bindValue(':sj_filter', $filterSJ, PDO::PARAM_STR);
					$records->execute();
						$outlet_data = [];
						
						// Group data by outlet
						while($record = $records->fetch(PDO::FETCH_ASSOC)){
							$key = $record['nama_out'] ? $record['nama_out'] : 'Tanpa Outlet';
							if(!isset($outlet_data[$key])){
								$outlet_data[$key] = [
									'nama_out' => $record['nama_out'],
									'total' => 0,
									'items' => []
								];
							}
							$outlet_data[$key]['total'] += $record['sisa_psd'];
							$outlet_data[$key]['items'][] = $record;
							$total_keluar_all += $record['sisa_psd'];
						}
						
						// Display grouped data
						foreach($outlet_data as $key => $outlet){
					?>
					<?php
							foreach($outlet['items'] as $record){
								$tgl_psd = date('d/m/Y', strtotime($record['tgl_psd']));
								$tgl_expired = date('d/m/Y', strtotime($record['tgl_expired']));
								
								// Hitung rentang waktu konsinyasi
								$rentang_waktu = '-';
								if($record['tgl_tfk']){
									$tgl_mulai = new DateTime($record['tgl_tfk']);
									$tgl_sekarang = new DateTime();
									$selisih = $tgl_mulai->diff($tgl_sekarang);
									$hari = $selisih->days;
									$rentang_waktu = $hari . ' hari';
									
									// Warna badge berdasarkan durasi
									if($hari < 30){
										$badge_class = 'badge-success'; // Hijau < 30 hari
									} elseif($hari < 60){
										$badge_class = 'badge-warning'; // Kuning 30-60 hari
									} else {
										$badge_class = 'badge-danger'; // Merah > 60 hari
									}
									$rentang_waktu = '<span class="badge '.$badge_class.'">'.$rentang_waktu.'</span>';
								}
								
								$status_badge = '';
								if($record['status_barang'] == 'active'){
									$status_badge = '<span class="badge badge-modern badge-active">Active</span>';
								} else {
									$status_badge = '<span class="badge badge-modern badge-inactive">Inactive</span>';
								}
					?>
						<tr data-sj="<?php echo htmlspecialchars($record['sj_tfk'] ?? ''); ?>">
							<td><center><?php echo $no; ?></center></td>
							<td><center><?php echo $tgl_psd; ?></center></td>
							<td><?php echo $record['sj_tfk'] ? $record['sj_tfk'] : '-'; ?></td>
							<td><i class="fa fa-store"></i> <?php echo $record['nama_out'] ? $record['nama_out'] : '-'; ?></td>
							<td><center><?php echo $rentang_waktu; ?></center></td>
							<td><?php echo $record['nama_pro'] ? $record['nama_pro'] : '-'; ?></td>
							<td><code><?php echo $record['no_bcode'] ? $record['no_bcode'] : '-'; ?></code></td>
							<td><center><span class="badge badge-warning"><?php echo $tgl_expired; ?></span></center></td>
							<td><div align="right"><span class="text-danger"><strong><?php echo number_format($record['sisa_psd'], 0, ',', '.'); ?></strong></span></div></td>
							<td><center><?php echo $status_badge; ?></center></td>
						</tr>
					<?php
								$no++;
							}
						}
					?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
<script>
$(document).ready(function() {
    var table = $('#tblstokkonsinyasi').DataTable({
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Semua"]],
        "order": [[1, 'desc']],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ data",
            "zeroRecords": "Data tidak ditemukan",
            "info": "Halaman _PAGE_ dari _PAGES_ (_TOTAL_ data)",
            "infoEmpty": "Tidak ada data",
            "infoFiltered": "(Filter dari _MAX_ data)",
            "search": "Cari:",
            "paginate": {
                "first": "Awal",
                "last": "Akhir",
                "next": "›",
                "previous": "‹"
            }
        },
        "dom": '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
    });
});
</script>
