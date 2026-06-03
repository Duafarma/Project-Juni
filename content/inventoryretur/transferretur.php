<?php
	$page   = isset($_GET['page']) ? (int)$_GET['page'] : 1;
	$limit  = 20;
	$offset = ($page - 1) * $limit;

	$list = $conn->prepare("
		SELECT T.id_tir, T.no_tir, T.no_bcode, T.tgl_expired, T.gudang, T.jumlah, T.keterangan, T.created_at, T.created_by,
		       P.nama_pro, P.berat_pro, S.nama_spr
		FROM transfer_inventory_retur T
		LEFT JOIN produk P ON T.id_pro = P.id_pro
		LEFT JOIN satuan_produk S ON P.id_spr = S.id_spr
		ORDER BY T.created_at DESC
		LIMIT :limit OFFSET :offset
	");
	$list->bindParam(':limit', $limit, PDO::PARAM_INT);
	$list->bindParam(':offset', $offset, PDO::PARAM_INT);
	$list->execute();
	$rows = $list->fetchAll(PDO::FETCH_ASSOC);

	$cntQ = $conn->query("SELECT COUNT(*) FROM transfer_inventory_retur");
	$total = (int)$cntQ->fetchColumn();
	$totalPage = ceil($total / $limit);
?>
<div class="content-header">
	<div>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">Menu</a></li>
				<li class="breadcrumb-item"><a href="<?php echo $sistem; ?>/inventoryretur">Inventory Retur</a></li>
				<li class="breadcrumb-item active" aria-current="page">Riwayat Transfer</li>
			</ol>
		</nav>
		<h4 class="content-title"><i class="fa fa-exchange-alt text-warning"></i> Riwayat Transfer ke Inventory Retur</h4>
	</div>
</div>
<div class="content-body">
	<div class="component-section no-code">
		<div class="row mg-b-15">
			<div class="col-sm-12">
				<a href="<?php echo $sistem; ?>/inventory" class="btn btn-info btn-sm"><i class="fa fa-arrow-left"></i> Kembali ke Inventory</a>
				<a href="<?php echo $sistem; ?>/inventoryretur" class="btn btn-secondary btn-sm"><i class="fa fa-list"></i> Inventory Retur</a>
			</div>
		</div>
		<div class="table-responsive">
			<table class="table table-bordered table-striped table-sm">
				<thead class="bg-warning">
					<tr>
						<th width="4%"><center>#</center></th>
						<th width="14%"><center>No. Bukti</center></th>
						<th width="22%">Nama Produk</th>
						<th width="10%"><center>No. Batch</center></th>
						<th width="8%"><center>ED</center></th>
						<th width="8%"><center>Gudang</center></th>
						<th width="7%"><center>Jumlah</center></th>
						<th width="15%">Keterangan</th>
						<th width="8%"><center>Operator</center></th>
						<th width="10%"><center>Tanggal</center></th>
					</tr>
				</thead>
				<tbody>
				<?php if(empty($rows)): ?>
					<tr><td colspan="10" class="text-center text-muted">Belum ada data transfer.</td></tr>
				<?php else: ?>
				<?php $no = $offset + 1; foreach($rows as $r): ?>
					<tr>
						<td><center><?php echo $no++; ?></center></td>
						<td><center><span class="badge badge-warning"><?php echo htmlspecialchars($r['no_tir']); ?></span></center></td>
						<td><?php echo htmlspecialchars($r['nama_pro']); ?> <small class="text-muted"><?php echo htmlspecialchars($r['berat_pro'].' '.$r['nama_spr']); ?></small></td>
						<td><center><?php echo htmlspecialchars($r['no_bcode']); ?></center></td>
						<td><center><?php echo $r['tgl_expired']; ?></center></td>
						<td><center><?php echo htmlspecialchars($r['gudang']); ?></center></td>
						<td><center><strong><?php echo number_format($r['jumlah'], 0, ',', '.'); ?></strong></center></td>
						<td><small><?php echo htmlspecialchars($r['keterangan']); ?></small></td>
						<td><center><small><?php echo htmlspecialchars($r['created_by']); ?></small></center></td>
						<td><center><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></center></td>
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
					<a class="page-link" href="<?php echo $sistem; ?>/transferretur?page=<?php echo $p; ?>"><?php echo $p; ?></a>
				</li>
				<?php endfor; ?>
			</ul>
		</nav>
		<?php endif; ?>
	</div>
</div>
