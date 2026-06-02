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
	$notin 	= empty($cart) ? "A.id_psd!=''" : "A.id_psd NOT IN('".str_replace("-", "', '", $cart)."')";
?>
	<link href="<?php echo("$sistem/DataTables/datatables.min.css"); ?>" rel="stylesheet" />
    <div class="modal-header">
        <h6 class="modal-title" id="exampleModalLabel">
            <i class="fas fa-search"></i> Pilih Produk - Penggantian Barang
        </h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true"><i class="fa fa-times-circle"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <!-- Search Bar -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" class="form-control" id="productSearchPenggantian" placeholder="Ketik nama produk, kode, atau batchcode...">
                </div>
            </div>
        </div>
        
        <!-- Product Table -->
        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
            <table class="table table-bordered table-hover table-sm" id="productTablePenggantian">
                <thead class="thead-dark" style="position: sticky; top: 0; z-index: 10;">
                    <tr>
                        <th>Nama Produk</th>
                        <th class="text-center">Batchcode</th>
                        <th class="text-center">Gudang</th>
                        <th class="text-center">Tgl. ED</th>
                        <th class="text-right">Stok</th>
                        <th class="text-right">Harga</th>
                    </tr>
                </thead>
                <tbody id="productBodyPenggantian">
                    <tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading products...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3" id="paginationControls" style="display: none !important;">
            <div>
                <small class="text-muted" id="paginationInfo">Showing 0 - 0 of 0 items</small>
            </div>
            <div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationList">
                        <!-- Pagination buttons will be inserted here -->
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Pagination Controls -->
        <div class="d-flex justify-content-between align-items-center mt-3" id="paginationControls" style="display: none;">
            <div>
                <small class="text-muted" id="paginationInfo">Showing 0 - 0 of 0 items</small>
            </div>
            <div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationList">
                        <!-- Pagination buttons will be inserted here -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
    </div>
<?php $conn	= $base->close(); ?>
	<script type="text/javascript" src="<?php echo("$sistem/DataTables/datatables.min.js"); ?>"></script>
	<script type="text/javascript">
	var currentNomor = <?php echo $nomor; ?>;
	var currentMitra = '<?php echo $mitra; ?>';
	var currentCart = '<?php echo $cart; ?>';
	var searchTimeout = null;
	var currentPage = 1;
	
	console.log("Modal Penggantian Barang loaded - Nomor:", currentNomor, "Outlet:", currentMitra);
	
	// Update pagination controls
	function updatePagination() {
		var paginationData = $('#paginationData td').first();
		if(paginationData.length === 0) {
			$('#paginationControls').hide();
			return;
		}
		
		var page = parseInt(paginationData.data('page')) || 1;
		var totalPages = parseInt(paginationData.data('total-pages')) || 1;
		var totalItems = parseInt(paginationData.data('total-items')) || 0;
		var itemsPerPage = parseInt(paginationData.data('items-per-page')) || 10;
		
		if(totalItems === 0) {
			$('#paginationControls').hide();
			return;
		}
		
		// Update info text
		var startItem = ((page - 1) * itemsPerPage) + 1;
		var endItem = Math.min(page * itemsPerPage, totalItems);
		$("#paginationInfo").text("Showing " + startItem + " - " + endItem + " of " + totalItems + " items");
		
		// Build pagination buttons
		var paginationHtml = "";
		
		// Previous button
		if(page > 1) {
			paginationHtml += "<li class=\"page-item\"><a class=\"page-link\" href=\"#\" data-page=\"" + (page - 1) + "\">‹</a></li>";
		} else {
			paginationHtml += "<li class=\"page-item disabled\"><span class=\"page-link\">‹</span></li>";
		}
		
		// Page numbers
		var startPage = Math.max(1, page - 2);
		var endPage = Math.min(totalPages, page + 2);
		
		for(var i = startPage; i <= endPage; i++) {
			if(i === page) {
				paginationHtml += "<li class=\"page-item active\"><span class=\"page-link\">" + i + "</span></li>";
			} else {
				paginationHtml += "<li class=\"page-item\"><a class=\"page-link\" href=\"#\" data-page=\"" + i + "\">" + i + "</a></li>";
			}
		}
		
		// Next button
		if(page < totalPages) {
			paginationHtml += "<li class=\"page-item\"><a class=\"page-link\" href=\"#\" data-page=\"" + (page + 1) + "\">›</a></li>";
		} else {
			paginationHtml += "<li class=\"page-item disabled\"><span class=\"page-link\">›</span></li>";
		}
		
		$("#paginationList").html(paginationHtml);
		$("#paginationControls").show();
	}
	
	// Load products
	function loadProductsPenggantian(searchTerm, page) {
		searchTerm = searchTerm || '';
		page = page || 1;
		currentPage = page;
		
		console.log("Loading products - Search:", searchTerm, "Page:", page);
		
		$("#productBodyPenggantian").html("<tr><td colspan=\"6\" class=\"text-center\"><div class=\"spinner-border spinner-border-sm\"></div> Loading...</td></tr>");
		
		$.ajax({
			url: usuper + '/ajax/loadProductsPenggantian.php',
			type: 'POST',
			data: {
				mitra: currentMitra,
				cart: currentCart,
				nomor: currentNomor,
				search: searchTerm,
				page: page
			},
			success: function(response) {
				console.log("Products loaded, length:", response.length);
				console.log("Response preview (first 200 chars):", response.substring(0, 200));
				console.log("Response type:", typeof response);
				
				// Trim whitespace and BOM
				response = response.trim();
				response = response.replace(/^\uFEFF/, ''); // Remove BOM
				
				// Check if response contains PHP error
				if(response.indexOf("<" + "?php") > -1 || response.indexOf("<br />") > -1 || response.indexOf("Fatal error") > -1) {
					console.error("Response contains PHP error!");
					$("#productBodyPenggantian").html("<tr><td colspan=\"6\" class=\"text-center text-danger\"><i class=\"fas fa-exclamation-triangle\"></i> Server error - check PHP logs</td></tr>");
					return;
				}
				
				if(!response || response === "") {
					$("#productBodyPenggantian").html("<tr><td colspan=\"6\" class=\"text-center text-muted py-4\"><i class=\"fas fa-box-open\"></i> Tidak ada produk</td></tr>");
					return;
				}
				
				// Check if response has table rows
				if(response.indexOf("<tr") === -1) {
					console.error("Invalid response - no TR tags found");
					console.error("Response:", response);
					$("#productBodyPenggantian").html("<tr><td colspan=\"6\" class=\"text-center text-danger\"><i class=\"fas fa-exclamation-triangle\"></i> Invalid response format</td></tr>");
					return;
				}
				
				// Render products
				$("#productBodyPenggantian").html(response);
				
				// Count rendered rows
				var rowCount = $("#productBodyPenggantian tr:not(#paginationData)").length;
				console.log("Products rendered successfully:", rowCount, "rows");
				
				// Update pagination
				updatePagination();
				
				if(rowCount === 0) {
					console.error("No rows rendered!");
					$("#productBodyPenggantian").html("<tr><td colspan=\"6\" class=\"text-center text-warning\"><i class=\"fas fa-exclamation-triangle\"></i> Gagal render produk</td></tr>");
				}
			},
			error: function(xhr, status, error) {
				console.error("Error loading products:", error);
				$("#productBodyPenggantian").html("<tr><td colspan=\"6\" class=\"text-center text-danger\"><i class=\"fas fa-exclamation-triangle\"></i> Error: " + error + "</td></tr>");
			}
		});
	}
	
	// Search with debounce
	$("#productSearchPenggantian").on("input keyup", function() {
		if(searchTimeout) clearTimeout(searchTimeout);
		
		searchTimeout = setTimeout(function() {
			var searchTerm = $("#productSearchPenggantian").val();
			currentPage = 1; // Reset to first page on new search
			loadProductsPenggantian(searchTerm, 1);
		}, 300);
	});
	
	// Load on ready
	$(document).ready(function() {
		loadProductsPenggantian("", 1);
		
		// Attach click handler using event delegation
		// This ensures clicks on dynamically added rows work properly
		$(document).on("click", "#productBodyPenggantian tr", function(e) {
			// The onclick attribute in the TR will fire automatically
			// This is just a fallback for debugging/tracking
			console.log("Product row clicked");
		});
		
		// Pagination click handler
		$(document).on("click", "#paginationList a.page-link", function(e) {
			e.preventDefault();
			var page = parseInt($(this).data("page"));
			var searchTerm = $("#productSearchPenggantian").val();
			loadProductsPenggantian(searchTerm, page);
		});
	});
	</script>
	
	<style>
	#productTablePenggantian tbody tr {
		cursor: pointer;
		transition: all 0.2s ease;
	}
	
	#productTablePenggantian tbody tr {
		/* Ensure onclick works */
		pointer-events: auto;
	}
	
	#productTablePenggantian tbody tr:hover {
		background-color: #e3f2fd !important;
		transform: scale(1.01);
	}
	
	.table thead th {
		background-color: #343a40;
		color: white;
		font-weight: 600;
		border-color: #454d55;
	}
	
	.input-group-text {
		background-color: #f8f9fa;
	}
	
	/* Pagination styling */
	#paginationControls .pagination {
		justify-content: center;
	}
	
	#paginationControls .page-link {
		color: #007bff;
		border-color: #dee2e6;
	}
	
	#paginationControls .page-item.active .page-link {
		background-color: #007bff;
		border-color: #007bff;
	}
	
	#paginationControls .page-item.disabled .page-link {
		color: #6c757d;
		background-color: #fff;
	}
	</style>
