<?php 
include('../../config/koneksi.php');

// Validate parameters and set defaults
$nomor = isset($_GET['nomor']) ? intval($_GET['nomor']) : 0;
$mitra = isset($_GET['mitra']) ? htmlspecialchars($_GET['mitra']) : '';
$cart = isset($_GET['cart']) ? htmlspecialchars($_GET['cart']) : '';
$notin = isset($_GET['notin']) ? htmlspecialchars($_GET['notin']) : '';

// Check if this is AJAX request to load products
if (isset($_GET['principle']) && isset($_GET['action']) && $_GET['action'] == 'load_products') {
	// Set content type and start output buffering for clean output
	header('Content-Type: text/html; charset=utf-8');
	ob_start();
	ob_clean();
	
	$principle = $_GET['principle'];
	$search = isset($_GET['search']) ? trim($_GET['search']) : '';
	
	try {
		// Build query
		$sql = "SELECT id_p, kode_p, nama_p, nama_p2, code_p, harga_agen, berat, kat_produk, 
				satuan_qty, satuan, barcode, tglexpired, gudang, stok, diskon 
				FROM produk 
				WHERE nama_p LIKE :principle";
		
		$params = array(':principle' => "%$principle%");
		
		if (!empty($search)) {
			$sql .= " AND (nama_p2 LIKE :search OR kode_p LIKE :search OR barcode LIKE :search)";
			$params[':search'] = "%$search%";
		}
		
		$sql .= " ORDER BY nama_p2 ASC LIMIT 100";
		
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);
		$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		if (empty($products)) {
			echo '<tr><td colspan="6" class="text-center text-muted py-4">No products found</td></tr>';
		} else {
			foreach ($products as $row) {
				$onclick_values = array(
					$nomor,
					"'" . addslashes($row['kode_p']) . "'",
					"'" . addslashes($row['id_p']) . "'",
					"'" . addslashes($row['nama_p2']) . "'",
					"'" . addslashes($row['code_p']) . "'",
					$row['harga_agen'],
					$row['berat'],
					"'" . addslashes($row['kat_produk']) . "'",
					$row['satuan_qty'],
					"'" . addslashes($row['satuan']) . "'",
					"'" . addslashes($row['barcode']) . "'",
					"'" . addslashes($row['tglexpired']) . "'",
					"'" . addslashes($row['gudang']) . "'",
					$row['stok'],
					$row['diskon']
				);
				
				$onclick = 'getproductsales(' . implode(',', $onclick_values) . ')';
				
				echo '<tr class="product-row" onclick="' . htmlspecialchars($onclick) . '" style="cursor: pointer;">';
				echo '<td>' . htmlspecialchars($row['kode_p']) . '</td>';
				echo '<td>' . htmlspecialchars($row['nama_p2']) . '</td>';
				echo '<td>Rp ' . number_format($row['harga_agen'], 0, ',', '.') . '</td>';
				echo '<td>' . htmlspecialchars($row['satuan']) . '</td>';
				echo '<td>' . htmlspecialchars($row['stok']) . '</td>';
				echo '<td>' . htmlspecialchars($row['gudang']) . '</td>';
				echo '</tr>';
			}
		}
		
	} catch (Exception $e) {
		error_log("Database error in product loading: " . $e->getMessage());
		echo '<tr><td colspan="6" class="text-center text-danger">Database error occurred</td></tr>';
	}
	
	// Clean output and exit
	$output = ob_get_clean();
	echo $output;
	exit;
}

// Get principles for tabs
try {
	$stmt = $pdo->prepare("SELECT DISTINCT nama_p FROM master_principle ORDER BY nama_p ASC");
	$stmt->execute();
	$principles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
	$principles = array();
	error_log("Error fetching principles: " . $e->getMessage());
}
?>

<div class="modal fade" id="modalproduct" tabindex="-1" role="dialog" aria-labelledby="modalproductLabel" aria-hidden="true">
	<div class="modal-dialog modal-xl" role="document">
		<div class="modal-content">
			<div class="modal-header bg-primary text-white">
				<h5 class="modal-title" id="modalproductLabel">
					<i class="fas fa-search"></i> Product Search by Principle
				</h5>
				<button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			
			<div class="modal-body">
				<!-- Principle Selection Tabs -->
				<div class="card mb-3">
					<div class="card-header">
						<h6 class="mb-0"><i class="fas fa-tags"></i> Select Product Principle</h6>
					</div>
					<div class="card-body p-2">
						<div class="row">
							<?php foreach ($principles as $principle): ?>
							<div class="col-md-2 col-sm-4 col-6 mb-2">
								<button type="button" 
									class="btn btn-outline-primary btn-sm w-100 principle-btn" 
									onclick="loadPrincipleProducts('<?php echo htmlspecialchars($principle['nama_p']); ?>', '<?php echo htmlspecialchars($principle['nama_p']); ?>')">
									<?php echo htmlspecialchars($principle['nama_p']); ?>
								</button>
							</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
				
				<!-- Search Section -->
				<div class="card mb-3" id="searchSection" style="display: none;">
					<div class="card-header d-flex justify-content-between align-items-center">
						<h6 class="mb-0">
							<i class="fas fa-list"></i> Products for: 
							<span class="text-primary font-weight-bold" id="selectedPrincipleName">-</span>
						</h6>
						<div class="input-group" style="max-width: 300px;">
							<input type="text" 
								id="productSearch" 
								class="form-control form-control-sm" 
								placeholder="Search products...">
							<div class="input-group-append">
								<span class="input-group-text">
									<i class="fas fa-search"></i>
								</span>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Products Table -->
				<div class="card">
					<div class="card-body p-0">
						<div id="loadingSpinner" class="text-center p-4" style="display: none;">
							<div class="spinner-border text-primary" role="status">
								<span class="sr-only">Loading...</span>
							</div>
							<p class="mt-2 mb-0 text-muted">Loading products...</p>
						</div>
						
						<div class="table-responsive">
							<table class="table table-hover mb-0" id="principleProductTable">
								<thead class="bg-light">
									<tr>
										<th style="width: 15%;">Code</th>
										<th style="width: 35%;">Product Name</th>
										<th style="width: 15%;">Price</th>
										<th style="width: 10%;">Unit</th>
										<th style="width: 10%;">Stock</th>
										<th style="width: 15%;">Warehouse</th>
									</tr>
								</thead>
								<tbody id="productTableBody">
									<tr>
										<td colspan="6" class="text-center text-muted py-5">
											<i class="fas fa-hand-point-up fa-2x mb-2"></i>
											<p class="mb-0">Please select a principle above to view products</p>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">
					<i class="fas fa-times"></i> Close
				</button>
			</div>
		</div>
	</div>
</div>

<script>
$(document).ready(function() {
	// Initialize variables - updated to be dynamic
	var currentNomor = parseInt($('#modalproduct').data('current-nomor')) || <?php echo $nomor; ?>;
	var currentMitra = $('#modalproduct').data('current-mitra') || '<?php echo $mitra; ?>';
	var currentCart = $('#modalproduct').data('current-cart') || '<?php echo $cart; ?>';
	var currentNotin = $('#modalproduct').data('current-notin') || '<?php echo addslashes($notin); ?>';
	var currentPrinciple = '';
	var productTable = null;
	var searchTimeout = null;
	
	// Function to load products by principle
	window.loadPrincipleProducts = function(principleId, principleName) {
		currentPrinciple = principleName;
		
		// Update UI
		$('#selectedPrincipleName').text(principleName);
		$('#searchSection').show();
		$('#productSearch').val('');
		
		// Highlight selected principle button
		$('.principle-btn').removeClass('btn-primary').addClass('btn-outline-primary');
		$(event.target).removeClass('btn-outline-primary').addClass('btn-primary');
		
		// Load products
		loadProducts('', principleName);
	};
	
	function loadProducts(searchTerm, principleParam) {
		var principleToUse = principleParam || currentPrinciple;
		
		if (!principleToUse) {
			$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> Select principle first</td></tr>');
			return;
		}
		
		// Show loading
		$('#loadingSpinner').show();
		$('#productTableBody').html('<tr><td colspan="6" class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>');
		
		// Cleanup existing DataTable
		if ($.fn.DataTable && $.fn.DataTable.isDataTable('#principleProductTable')) {
			$('#principleProductTable').DataTable().clear().destroy();
		}
		
		$.ajax({
			url: window.location.href,
			type: 'GET',
			data: {
				action: 'load_products',
				principle: principleToUse,
				mitra: currentMitra,
				cart: currentCart,
				nomor: currentNomor,
				search: searchTerm || ''
			},
			success: function(response) {
				$('#loadingSpinner').hide();
				
				if (!response || response.trim() === '') {
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-info-circle"></i> No products found</td></tr>');
					return;
				}
				
				try {
					$('#productTableBody').html(response);
					
					// Initialize DataTable after brief delay
					setTimeout(function() {
						initProductTable();
					}, 100);
					
				} catch(e) {
					console.error('Error displaying products:', e);
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Error displaying products</td></tr>');
				}
			},
			error: function(xhr, status, error) {
				console.error('AJAX Error:', error);
				$('#loadingSpinner').hide();
				$('#productTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Failed to load products</td></tr>');
			}
		});
	}
	
	// Initialize DataTable
	function initProductTable() {
		try {
			if ($.fn.DataTable && $.fn.DataTable.isDataTable('#principleProductTable')) {
				$('#principleProductTable').DataTable().clear().destroy();
			}
			
			productTable = $('#principleProductTable').DataTable({
				"pageLength": 10,
				"lengthMenu": [[10, 25, 50], [10, 25, 50]],
				"searching": false,
				"ordering": true,
				"order": [[1, 'asc']],
				"info": true,
				"lengthChange": false,
				"autoWidth": false,
				"language": {
					"info": "Showing _START_ to _END_ of _TOTAL_ products",
					"paginate": {
						"next": "Next ›",
						"previous": "‹ Prev"
					},
					"emptyTable": "No products available"
				}
			});
			
		} catch(e) {
			console.error('DataTable init error:', e);
		}
	}
	
	// Live search with debouncing
	$('#productSearch').on('input', function() {
		clearTimeout(searchTimeout);
		var searchTerm = $(this).val().trim();
		
		searchTimeout = setTimeout(function() {
			if (currentPrinciple) {
				loadProducts(searchTerm, currentPrinciple);
			}
		}, 300);
	});
	
	// Handle product selection via event delegation
	$(document).on('click', '.product-row', function(e) {
		e.preventDefault();
		
		var onclick = $(this).attr('onclick');
		if (onclick) {
			try {
				// Execute the onclick function
				eval(onclick);
				// Close modal after successful selection
				$('#modalproduct').modal('hide');
			} catch(error) {
				console.error('Error executing product selection:', error);
				alert('Error selecting product. Please try again.');
			}
		}
	});
	
	// Reset modal when closed
	$('#modalproduct').on('hidden.bs.modal', function() {
		$('#searchSection').hide();
		$('#productSearch').val('');
		currentPrinciple = '';
		
		$('.principle-btn').removeClass('btn-primary').addClass('btn-outline-primary');
		
		if ($.fn.DataTable && $.fn.DataTable.isDataTable('#principleProductTable')) {
			$('#principleProductTable').DataTable().clear().destroy();
		}
		
		$('#productTableBody').html(`
			<tr>
				<td colspan="6" class="text-center text-muted py-5">
					<i class="fas fa-hand-point-up fa-2x mb-2"></i>
					<p class="mb-0">Please select a principle above to view products</p>
				</td>
			</tr>
		`);
	});
});
</script>