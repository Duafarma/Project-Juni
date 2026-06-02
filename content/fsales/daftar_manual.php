<?php
	// Ambil daftar faktur manual
	$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
	$limit = 20;
	$offset = ($page - 1) * $limit;

	$list = $conn->prepare("SELECT id_tfm, id_tfk, kode_tfk, ket_mr, total_tfm, status_tfm, created_at 
							FROM transaksi_faktur_manual 
							ORDER BY created_at DESC 
							LIMIT :limit OFFSET :offset");
	$list->bindParam(':limit', $limit, PDO::PARAM_INT);
	$list->bindParam(':offset', $offset, PDO::PARAM_INT);
	$list->execute();
	$rows = $list->fetchAll(PDO::FETCH_ASSOC);

	$countQ = $conn->query("SELECT COUNT(*) FROM transaksi_faktur_manual");
	$total  = (int)$countQ->fetchColumn();
	$totalPage = ceil($total / $limit);
?>
<div class="content-header">
	<div>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">Home</a></li>
				<li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/fsales">Faktur Penjualan</a></li>
				<li class="breadcrumb-item active" aria-current="page">Daftar Faktur Manual</li>
			</ol>
		</nav>
		<h4 class="content-title"><i class="fa fa-check text-danger"></i> Daftar Faktur Manual</h4>
	</div>
</div>
<div class="content-body">
	<div class="component-section no-code">
		<div class="row mg-b-15">
			<div class="col-sm-12">
				<a href="<?php echo $sistem; ?>/fsales" class="btn btn-secondary btn-sm"><i class="fa fa-arrow-left"></i> Kembali ke Faktur</a>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-sm">
				<thead class="bg-danger text-white">
					<tr>
						<th width="5%"><center>#</center></th>
						<th width="20%">No. Faktur Manual</th>
						<th width="25%">Pelanggan (MR)</th>
						<th width="15%">Faktur Asli</th>
						<th width="15%"><div align="right">Total</div></th>
						<th width="10%"><center>Tgl Buat</center></th>
						<th width="10%"><center>Aksi</center></th>
					</tr>
				</thead>
				<tbody>
				<?php if(empty($rows)): ?>
					<tr><td colspan="7" class="text-center text-muted">Belum ada faktur manual.</td></tr>
				<?php else: ?>
				<?php $no = $offset + 1; foreach($rows as $r): ?>
					<tr>
						<td><center><?php echo $no++; ?></center></td>
						<td><?php echo htmlspecialchars($r['kode_tfk']); ?></td>
						<td><?php echo htmlspecialchars($r['ket_mr']); ?></td>
						<td><small class="text-muted"><?php echo htmlspecialchars($r['id_tfk']); ?></small></td>
						<td><div align="right">Rp <?php echo number_format($r['total_tfm'], 0, ',', '.'); ?></div></td>
						<td><center><?php echo date('d/m/Y', strtotime($r['created_at'])); ?></center></td>
						<td><center>
							<a href="<?php echo $data->sistem('url_sis'); ?>/laporan/xps/faktursales/faktursales_manual.php?key=<?php echo $r['id_tfm']; ?>" 
							   target="_blank" class="btn btn-danger btn-sm" title="Print">
								<i class="fa fa-print"></i>
							</a>
						</center></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
		</div>
		<?php if($totalPage > 1): ?>
		<nav>
			<ul class="pagination pagination-sm">
				<?php for($p = 1; $p <= $totalPage; $p++): ?>
				<li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
					<a class="page-link" href="<?php echo $sistem; ?>/fsales/daftarmanual?page=<?php echo $p; ?>"><?php echo $p; ?></a>
				</li>
				<?php endfor; ?>
			</ul>
		</nav>
		<?php endif; ?>
	</div>
</div>
