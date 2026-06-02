<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$secu	= new Security;
	$base	= new DB;
	$data	= new Data;
	$conn	= $base->open();
	$sistem	= $data->sistem('url_sis');
	$tgl	= date('Y-m-d');
	$nomor	= $secu->injection($_POST['x']);
	$cart	= $secu->injection($_POST['y']);
	$mitra	= $secu->injection($_POST['m']);
	$principleId = isset($_POST['principle']) ? $secu->injection($_POST['principle']) : ''; // Add principle parameter with check
	$notin 	= empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";
	
	// Get principle name for display
	$principleName = 'Semua Produk';
	if(!empty($principleId)) {
		try {
			$principle_query = $conn->prepare("SELECT nama_principle FROM master_principle WHERE id_mp = :id");
			$principle_query->bindParam(':id', $principleId, PDO::PARAM_STR);
			$principle_query->execute();
			$principle_data = $principle_query->fetch(PDO::FETCH_ASSOC);
			if($principle_data) {
				$principleName = $principle_data['nama_principle'];
			}
		} catch(PDOException $e) {
			// Keep default name if query fails
		}
	}
?>
	<link href="<?php echo("$sistem/DataTables/datatables.min.css"); ?>" rel="stylesheet" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">
            <i class="fas fa-search"></i> Search Product
        </h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <!-- Tabs Navigation -->
        <ul class="nav nav-pills nav-fill mb-3" id="principeTabs" role="tablist">
            <?php
            try {
                $principle_query = $conn->prepare("SELECT * FROM master_principle ORDER BY nama_principle ASC");
                $principle_query->execute();
                $principles = $principle_query->fetchAll(PDO::FETCH_ASSOC);
                
                $first = true;
                foreach($principles as $principle) {
                    $active = $first ? 'active' : '';
                    $selected = $first ? 'true' : 'false';
                    echo '<li class="nav-item" role="presentation">';
                    echo '<button class="nav-link principle-tab '.$active.'" id="tab-'.$principle['id_mp'].'" data-bs-toggle="pill" data-bs-target="#content-'.$principle['id_mp'].'" type="button" role="tab" aria-controls="content-'.$principle['id_mp'].'" aria-selected="'.$selected.'" onclick="loadPrincipleProducts(\''.$principle['id_mp'].'\', \''.$principle['nama_principle'].'\')">';
                    echo '<i class="fas fa-building"></i> '.$principle['nama_principle'];
                    echo '</button>';
                    echo '</li>';
                    $first = false;
                }
            } catch(PDOException $e) {
                echo '<li class="nav-item"><button class="nav-link active" disabled>Error loading principles</button></li>';
            }
            ?>
        </ul>
        
        <!-- Search Bar -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" class="form-control" id="productSearch" placeholder="Ketik untuk mencari nama produk, batchcode, atau kode... (pencarian otomatis)">
                    <div class="input-group-append">
                        <span class="input-group-text text-muted"><small>Live Search</small></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab Content -->
        <div class="tab-content" id="principleTabContent">
            <div class="tab-pane fade show active" id="productContent" role="tabpanel">
                <div id="loadingSpinner" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading products...</p>
                </div>
                
                <div class="table-responsive" id="productTable">
                    <table class="table table-striped table-hover" id="principleProductTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>Nama Produk</th>
                                <th><center>Batchcode</center></th>
                                <th><center>Gudang</center></th>
                                <th><center>Tgl. ED</center></th>
                                <th class="text-right">Stok</th>
                                <th class="text-right">Harga</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody">
                            <tr><td colspan="6" class="text-center text-muted py-4"><i class="fas fa-info-circle"></i> Pilih principle untuk melihat produk</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Info -->
                <div class="row mt-3">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info" id="productInfo" role="status" aria-live="polite">Menampilkan 0 dari 0 produk</div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div class="dataTables_paginate" id="productPagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <div class="mr-auto text-muted">
            <small><i class="fas fa-info-circle"></i> Selected: <span id="selectedPrincipleName">None</span></small>
        </div>
        <button type="button" class="btn btn-secondary btn-xs" data-dismiss="modal">Close</button>
    </div>
<?php $conn	= $base->close(); ?>
	<script type="text/javascript" src="<?php echo("$sistem/DataTables/datatables.min.js"); ?>"></script>
	<script type="text/javascript">
	// Initialize variables
	var currentNomor = <?php echo $nomor; ?>;
	var currentMitra = '<?php echo $mitra; ?>';
	var currentCart = '<?php echo $cart; ?>';
	var currentNotin = '<?php echo addslashes($notin); ?>';
	var currentPrinciple = ''; // Store principle name
	var productTable = null;
	var searchTimeout = null;
	
	// Function to load products by principle - FIXED
	function loadPrincipleProducts(principleId, principleName) {
		currentPrinciple = principleName;
		
		// Update selected principle name
		$('#selectedPrincipleName').text(principleName);
		
		// Clear search box
		$('#productSearch').val('');
		
		// Show loading
		$('#loadingSpinner').show();
		$('#productTableBody').html('<tr><td colspan="6" class="text-center text-muted py-4">Loading products for ' + principleName + '...</td></tr>');
		
		// Load products using AJAX
		loadProducts('', principleName);
	}
	
	function loadProducts(searchTerm, principleParam) {
		var principleToUse = principleParam || currentPrinciple;
		
		if (!principleToUse) {
			$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> Pilih principle terlebih dahulu</td></tr>');
			return;
		}
		
		// Show loading
		$('#productTableBody').html('<tr><td colspan="6" class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>');
		
		$.ajax({
			url: '<?php echo $sistem; ?>/ajax/addsales/addsales.php',
			type: 'GET',
			data: {
				principle: principleToUse,
				mitra: currentMitra,
				cart: currentCart,
				nomor: currentNomor,
				search: searchTerm || ''
			},
			success: function(response) {
				$('#loadingSpinner').hide();
				
				if (!response || response.trim() === '') {
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> No data found</td></tr>');
					return;
				}
				
				try {
					$('#productTableBody').html(response);
					
					// Initialize DataTable
					setTimeout(function() {
						initProductTable();
					}, 200);
					
				} catch(e) {
					console.error('Error inserting response:', e);
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Error displaying products</td></tr>');
				}
			},
	
	// Initialize DataTable with proper cleanup
	function initProductTable() {
		// Check if table element exists
		if (!$('#principleProductTable').length) {
			console.error('Table #principleProductTable not found');
			return;
		}
		
		// Properly destroy existing DataTable instance
		try {
			if ($.fn.DataTable.isDataTable('#principleProductTable')) {
				$('#principleProductTable').DataTable().clear().destroy();
			}
			// Remove any DataTable classes that might remain
			$('#principleProductTable').removeClass('dataTable no-footer');
			$('#principleProductTable_wrapper').remove();
		} catch(e) {
			console.log('DataTable cleanup error:', e);
		}
		
		// Small delay to ensure DOM is ready
		setTimeout(function() {
			try {
				// Initialize new DataTable
				productTable = $('#principleProductTable').DataTable({
					"pageLength": 10,
					"lengthMenu": [[10, 25, 50], [10, 25, 50]],
					"searching": false,
					"ordering": true,
					"order": [[0, 'asc']],
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
				console.log('DataTable initialized successfully');
			} catch(e) {
				console.error('DataTable initialization error:', e);
			}
		}, 50);
	}
	}
	
	function loadPrincipleProducts(principleId, principleName) {
		currentPrinciple = principleName; // Store principle name, not ID
		
		// Update selected principle name
		$('#selectedPrincipleName').text(principleName);
		
		// Clear search box
		$('#productSearch').val('');
		
		// Properly destroy existing DataTable
		if ($.fn.DataTable.isDataTable('#principleProductTable')) {
			$('#principleProductTable').DataTable().clear().destroy();
			$('#principleProductTable').removeClass('dataTable no-footer');
		}
		
		// Show loading
		$('#loadingSpinner').show();
		$('#productTableBody').html('<tr><td colspan="6" class="text-center text-muted py-4">Loading products for ' + principleName + '...</td></tr>');
		
		// Load products using principle name
		loadProducts('', principleName);
	}
	
	function loadProducts(searchTerm, principleParam) {
		var principleToUse = principleParam || currentPrinciple;
		
		console.log('=== loadProducts Debug ===');
		console.log('searchTerm:', searchTerm);
		console.log('principleParam:', principleParam);
		console.log('currentPrinciple:', currentPrinciple);
		console.log('principleToUse:', principleToUse);
		console.log('currentMitra:', currentMitra);
		console.log('currentCart:', currentCart);
		console.log('currentNomor:', currentNomor);
		
		if (!principleToUse) {
			console.warn('No principle selected');
			$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> Pilih principle terlebih dahulu</td></tr>');
			return;
		}
		
		// Show loading
		$('#productTableBody').html('<tr><td colspan="6" class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm" role="status"></div> Loading products...</td></tr>');
		
		var ajaxData = {
			principle: principleToUse,
			mitra: currentMitra,
			cart: currentCart,
			nomor: currentNomor,
			search: searchTerm || ''
		};
		
		console.log('AJAX Data being sent:', ajaxData);
		
		var ajaxUrl = 'ajax/loadProducts_ultra.php'; // Ultra-clean version
		console.log('AJAX URL:', ajaxUrl);
		
		$.ajax({
			url: ajaxUrl,
			type: 'POST',
			data: ajaxData,
			beforeSend: function() {
				console.log('AJAX request started');
			},
			success: function(response) {
				console.log('AJAX Success!');
				console.log('Response type:', typeof response);
				console.log('Response length:', response ? response.length : 0);
				
				$('#loadingSpinner').hide();
				
				// Validate response
				if (!response || response.trim() === '') {
					console.warn('Empty response received');
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> No data received from server</td></tr>');
					return;
				}
				
				// Check for error responses
				if (response.indexOf('error.php') !== -1 || response.indexOf('Error:') !== -1) {
					console.error('Error response detected:', response.substring(0, 200));
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Server error detected</td></tr>');
					return;
				}
				
				// Check for valid HTML table rows
				if (response.indexOf('<tr') === -1) {
					console.warn('Invalid response format - no table rows found');
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> Invalid response format</td></tr>');
					return;
				}
				
				try {
					// Safely insert response
					$('#productTableBody').html(response);
					console.log('Response inserted successfully');
					
					// Count actual rows
					var rowCount = $('#productTableBody tr').length;
					console.log('Rows in table:', rowCount);
					
					// Initialize DataTable after content is loaded
					setTimeout(function() {
						initProductTable();
					}, 200);
				} catch(e) {
					console.error('Error inserting response:', e);
					console.error('Response content:', response.substring(0, 500));
					$('#productTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Error displaying products: ' + e.message + '</td></tr>');
				}
			},
			},
			error: function(xhr, status, error) {
				$('#loadingSpinner').hide();
				console.log('AJAX Error:', error);
				$('#productTableBody').html('<tr><td colspan="6" class="text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Error loading products</td></tr>');
			}
		});
	}
	
	// Live search function
	function doLiveSearch() {
		var searchTerm = $('#productSearch').val();
		
		if (currentPrinciple) {
			// Cleanup existing DataTable
			if ($.fn.DataTable.isDataTable('#principleProductTable')) {
				$('#principleProductTable').DataTable().clear().destroy();
				$('#principleProductTable').removeClass('dataTable no-footer');
			}
			
			$('#loadingSpinner').show();
			loadProducts(searchTerm, currentPrinciple);
		} else {
			$('#productTableBody').html('<tr><td colspan="6" class="text-center text-warning py-4"><i class="fas fa-exclamation-triangle"></i> Pilih principle terlebih dahulu</td></tr>');
		}
	}
	
	// Live search with debouncing
	$('#productSearch').on('input keyup', function() {
		// Clear previous timeout
		if (searchTimeout) {
			clearTimeout(searchTimeout);
		}
		
		// Set new timeout for 300ms delay
		searchTimeout = setTimeout(function() {
			doLiveSearch();
		}, 300);
	});
	
	// Load first principle automatically
	$(document).ready(function() {
		var firstTab = $('.principle-tab.active');
		if(firstTab.length > 0) {
			var principleId = firstTab.attr('id').replace('tab-', '');
			var principleName = firstTab.text().trim().replace(/.*? /, '');
			console.log('Auto loading first principle:', principleId, principleName);
			loadPrincipleProducts(principleId, principleName);
		}
		
		// Event delegation for product row clicks
		$(document).on('click', '.product-row', function() {
			var row = $(this);
			var params = [
				row.data('nomor'),
				row.data('id-psd'),
				row.data('id-pro'),
				row.data('nama-pro'),
				row.data('kode-pro'),
				row.data('harga'),
				row.data('berat'),
				row.data('nama-kpr'),
				row.data('satuan-kpr'),
				row.data('nama-spr'),
				row.data('bcode'),
				row.data('expired'),
				row.data('gudang'),
				row.data('stok'),
				row.data('diskon')
			];
			
			// Call the existing function with all parameters
			if (typeof getproductsales === 'function') {
				getproductsales.apply(null, params);
			} else {
				console.error('getproductsales function not found');
			}
		});
	});
	</script>
	
	<style>
	.principle-tab {
		border-radius: 25px !important;
		margin: 0 2px;
		transition: all 0.3s ease;
		border: 2px solid transparent;
	}
	
	.principle-tab:hover {
		transform: translateY(-2px);
		box-shadow: 0 4px 8px rgba(0,0,0,0.1);
	}
	
	.principle-tab.active {
		background: linear-gradient(45deg, #007bff, #0056b3) !important;
		border-color: #007bff !important;
		color: white !important;
		box-shadow: 0 4px 15px rgba(0,123,255,0.3);
	}
	
	.nav-pills .nav-link {
		border-radius: 25px;
	}
	
	.table-hover tbody tr:hover {
		background-color: #f8f9fa !important;
		cursor: pointer;
		transition: background-color 0.2s ease;
	}
	
	.product-row {
		transition: all 0.15s ease;
	}
	
	.product-row:hover {
		background-color: #e8f4fd !important;
		transform: translateY(-1px);
		box-shadow: 0 2px 8px rgba(0,123,255,0.15);
	}
	
	#loadingSpinner .spinner-border {
		width: 3rem;
		height: 3rem;
	}
	
	#productSearch {
		border-radius: 25px;
		padding-left: 15px;
	}
	
	.input-group-prepend .input-group-text {
		border-radius: 25px 0 0 25px;
		border-right: none;
		background-color: #f8f9fa;
	}
	
	.table thead th {
		border-top: none;
		font-weight: 600;
		background-color: #343a40;
		color: white;
	}
	
	.dataTables_wrapper .dataTables_paginate .paginate_button {
		padding: 0.375rem 0.75rem;
		margin: 0 2px;
		border-radius: 0.375rem;
	}
	
	.dataTables_wrapper .dataTables_paginate .paginate_button.current {
		background: #007bff !important;
		border-color: #007bff !important;
		color: white !important;
	}
	
	.modal-lg {
		max-width: 90%;
	}
	</style>