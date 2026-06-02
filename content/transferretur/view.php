

<div class="content-header">
	<div>
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				<li class="breadcrumb-item"><a href="#">Home</a></li>
				<li class="breadcrumb-item"><a href="#">Inventory</a></li>
				<li class="breadcrumb-item active" aria-current="page">Transfer Retur</li>
			</ol>
		</nav>
		<h4 class="content-title">Inventory - Transfer Retur</h4>
	</div>
</div>
<?php
// Get transfer ID from URL
$kode = $secu->injection($_GET['keycode'] ?? '');

// Check if keycode is provided
if (empty($kode)) {
	echo '<div class="alert alert-danger">Invalid transfer retur ID</div>';
	echo '<div class="row row-sm">
            <div class="col-sm-12">
                <a href="' . $sistem . '/transferretur" class="btn btn-secondary"><i class="fa fa-chevron-circle-left"></i> Kembali</a>
            </div>
          </div>';
	exit;
}

// Fetch transfer retur data with related information
$q = "SELECT
        tt.id_ttr,
        tt.kode_ttr,
        tt.tipe_ttr,
        tt.id_app_from,
        tt.id_app_to,
        a2.nama_apl AS apl_from,
        a3.nama_apl AS apl_to,
        tt.ket_ttr,
        tt.tgl_ttr,
        tt.status_ttr
    FROM
        transaksi_transferretur tt
    LEFT JOIN aplikasi a2 ON
        tt.id_app_from = a2.id_apl
    LEFT JOIN aplikasi a3 ON
        tt.id_app_to = a3.id_apl
    WHERE
        tt.id_ttr = :kode
    LIMIT 1";

// Execute the query
$read = $conn->prepare($q);
$read->bindParam(':kode', $kode, PDO::PARAM_STR);
$read->execute();
$view = $read->fetch(PDO::FETCH_ASSOC);

// Check if data exists before proceeding
if (!$view) {
	// Display error and exit
	echo '<div class="alert alert-danger">Data transfer retur tidak ditemukan</div>';
	echo '<div class="row row-sm">
            <div class="col-sm-12">
                <a href="' . $sistem . '/transferretur" class="btn btn-secondary"><i class="fa fa-chevron-circle-left"></i> Kembali</a>
            </div>
          </div>';
	exit; // Stop execution
}

// Set badge color based on status
$badgeColors = [
	'Waiting' => 'warning',
	'Process' => 'primary',
	'Approved' => 'success',
	'Rejected' => 'danger',
	'Canceled' => 'secondary'
];

$badgeColor = $badgeColors[$view['status_ttr']] ?? 'secondary';

// Process approval/reject/cancel if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$id = $_POST['id'] ?? '';
	$result = ['success' => false, 'message' => '', 'errors' => []];

	if ($action === 'approve' || $action === 'reject') {
		// Handle approval or rejection
		$approval = ($action === 'approve') ? 1 : 0;
		$statusText = ($action === 'approve') ? 'Approved' : 'Rejected';

		try {
			// Begin transaction
			$conn->beginTransaction();

			// Update transfer retur status
			$updateStatus = $conn->prepare("UPDATE transaksi_transferretur SET status_ttr = :status WHERE id_ttr = :id");
			$updateStatus->execute([
				':status' => $statusText,
				':id' => $id
			]);

			// If approved and type is IN, update inventory
			if ($action === 'approve' && $view['tipe_ttr'] === 'IN') {
				// Get items to process
				$items = $conn->prepare("SELECT 
                    a.id_i_r, a.jumlah_ttd, b.id_pro, b.no_bcode, b.ed
                    FROM transaksi_transferreturdetail a
                    LEFT JOIN inventory_retur b ON a.id_i_r = b.id_i_r
                    WHERE a.id_ttr = :id");
				$items->execute([':id' => $id]);

				while ($item = $items->fetch(PDO::FETCH_ASSOC)) {
					// Process inventory update logic here
					// This is a simplified example - actual implementation may vary
					$updateInventory = $conn->prepare("UPDATE inventory 
                        SET stok = stok + :qty 
                        WHERE id_pro = :id_pro AND no_bcode = :bcode AND id_app = :app_to");
					$updateInventory->execute([
						':qty' => $item['jumlah_ttd'],
						':id_pro' => $item['id_pro'],
						':bcode' => $item['no_bcode'],
						':app_to' => $view['id_app_to']
					]);
				}
			}

			// Commit transaction
			$conn->commit();
			$result['success'] = true;
			$result['message'] = "Transfer retur berhasil " . ($action === 'approve' ? 'disetujui' : 'ditolak');
		} catch (Exception $e) {
			$conn->rollBack();
			$result['message'] = "Error: " . $e->getMessage();
			$result['errors'][] = $e->getMessage();
		}
	} else if ($action === 'cancel') {
		// Handle cancellation
		try {
			$updateStatus = $conn->prepare("UPDATE transaksi_transferretur SET status_ttr = 'Canceled' WHERE id_ttr = :id");
			$updateStatus->execute([':id' => $id]);

			$result['success'] = true;
			$result['message'] = "Transfer retur berhasil dibatalkan";
		} catch (Exception $e) {
			$result['message'] = "Error: " . $e->getMessage();
			$result['errors'][] = $e->getMessage();
		}
	}

	// Output JSON response if AJAX request
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
		header('Content-Type: application/json');
		echo json_encode($result);
		exit;
	}

	// Redirect or reload if needed for non-AJAX requests
	if ($result['success']) {
		header("Location: {$sistem}/transferretur/view?keycode={$kode}");
		exit;
	}
}
?>

<div class="content-body">
	<div class="component-section no-code">
		<div style="margin-top:10px; margin-bottom:25px;">
			<div class="row row-sm">
				<div class="col-sm-8">
					<div id="response-message"></div>
				</div>
			</div>
		</div>
		<div class="row row-sm">
			<div class="col-sm-3">
				<label>Kode <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?php echo htmlspecialchars($view['kode_ttr']); ?>" placeholder="-" required="required" disabled />
			</div>
			<div class="col-sm-3">
				<label>Tipe Transfer <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?= ($view['tipe_ttr'] == 'IN') ? 'Masuk' : 'Keluar' ?>" placeholder="-" required="required" disabled />
			</div>
			<div class="col-sm-3">
				<label>Tanggal <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?php echo ($view['tgl_ttr']); ?>" placeholder="9999-99-99" required="required" readonly />
			</div>
			<div class="col-sm-3">
				<label>Status</label>
				<h6><span class="badge badge-<?= $badgeColor ?>"><?= $view['status_ttr'] ?></span></h6>
			</div>
		</div>
		<div class="row row-sm">
			<div class="col-sm-3">
				<label>Asal <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?php echo ($view['apl_from']); ?>" placeholder="-" required="required" disabled />
			</div>
			<div class="col-sm-3">
				<label>Tujuan <span class="tx-danger">*</span></label>
				<input type="text" class="form-control" value="<?php echo ($view['apl_to']); ?>" placeholder="-" required="required" disabled />
			</div>
			<div class="col-sm-6">
				<label>Keterangan</label>
				<input type="text" class="form-control" placeholder="Ketik keterangan di sini..." value="<?php echo ($view['ket_ttr']); ?>" disabled />
			</div>
		</div><!-- row -->
		<div class="row row-sm">
			<div class="col-sm-12">
				<div class="clearfix mg-t-15 mg-b-15"></div>
				<div class="table-responsive">
					<table class="table table-bordered">
						<thead>
							<tr>
								<th>Product</th>
								<th>Detail</th>
								<th>Batchcode</th>
								<th>Expired Date</th>
								<th>Jumlah <?= ($view['tipe_ttr'] == 'IN') ? 'Masuk' : 'Keluar' ?></th>
								<th>Satuan</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$id_ttr = $view['id_ttr'];
							// Updated query to use transaksi_transferreturdetail and inventory_retur tables
							$qMaster = "SELECT
                                            c.id_pro,
                                            c.kode_pro,
                                            c.nama_pro,
                                            d.nama_kpr,
                                            c.berat_pro,
                                            d.satuan_kpr,
                                            b.ed AS tgl_expired,
                                            b.no_bcode,
                                            a.jumlah_ttd,
                                            e.nama_spr
                                        FROM
                                            transaksi_transferreturdetail a
                                        LEFT JOIN inventory_retur b ON
                                            a.id_i_r = b.id_i_r
                                        LEFT JOIN produk c ON
                                            b.id_pro = c.id_pro
                                        LEFT JOIN kategori_produk d ON
                                            c.id_kpr = d.id_kpr
                                        LEFT JOIN satuan_produk AS e ON
                                            c.id_spr = e.id_spr
                                        WHERE
                                            a.id_ttr = :id_ttr";

							$master = $conn->prepare($qMaster);
							$master->bindParam(':id_ttr', $id_ttr, PDO::PARAM_STR);
							$master->execute();

							if ($master->rowCount() > 0) {
								while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
									$proddetail = $hasil['nama_kpr'] . " (" . $hasil['berat_pro'] . " " . $hasil['satuan_kpr'] . ")";
							?>
									<tr>
										<td><?php echo ("(" . $hasil['kode_pro'] . ") " . $hasil['nama_pro']); ?></td>
										<td><?php echo ($proddetail); ?></td>
										<td><?php echo ($hasil['no_bcode']); ?></td>
										<td><?php echo ($hasil['tgl_expired']); ?></td>
										<td><?php echo ($hasil['jumlah_ttd']); ?></td>
										<td><?php echo ($hasil['nama_spr']); ?></td>
									</tr>
								<?php
								}
							} else {
								?>
								<tr>
									<td colspan="6" class="text-center">Tidak ada data produk</td>
								</tr>
							<?php
							}
							?>
						</tbody>
					</table>
					<br>
				</div>
			</div>
		</div>
		<?php require_once('config/frame/alert.php'); ?>
		<div class="row row-sm mt-3">
			<div class="col-sm-12">
				<a href="<?php echo ("$sistem/transferretur"); ?>" title="Kembali" class="btn btn-secondary btn-xs">
					<i class="fa fa-chevron-circle-left"></i> Kembali
				</a>
				
				<?php if ($view['status_ttr'] == 'Waiting') { ?>
				<button type="button" id="btnApprove" class="btn btn-success btn-xs" 
					onclick="approvalTransferReturModal('transferretur','<?php echo $view['id_ttr']; ?>','1')">
					<i class="fa fa-check"></i> APPROVE
				</button>
				<button type="button" id="btnReject" class="btn btn-danger btn-xs" 
					onclick="approvalTransferReturModal('transferretur','<?php echo $view['id_ttr']; ?>','0')">
					<i class="fa fa-times"></i> REJECT
				</button>
				<?php } ?>
				
				<div id="imgloading"></div>
			</div>
		</div>
	</div>
</div>