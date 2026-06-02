<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Report</a></li>
                <li class="breadcrumb-item active" aria-current="page"> DPE</li>
            </ol>
        </nav>
        <h4 class="content-title"> DPE</h4>
    </div>
</div>
<?php
	$cari	= $secu->injection(@$_GET['cari']);
	$pecah	= explode('_', $cari);
?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />
<div class="content-body">
	<div class="row mg-b-10">
        <div class="col-sm-6">
			<a href="<?php echo("$sistem/rpenjualandpe"); ?>" title="Refresh"><button class="btn btn-info btn-pill btn-xs"><i class="fa fa-spinner"></i> Refresh</button></a>
        	<?php echo(($data->akses($admin, $menu, 'A.read_status')==='Active') ? '<button class="btn btn-success btn-pill btn-xs" onclick="downloadExcel()" title="XLS"><i class="fa fa-print"></i> XLS</button>' : ''); ?>
        </div>
        
    </div>
   
    
    <!-- Baris Pertama: Vision Blu Products -->
    <div class="row mg-b-15">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header pd-10 bg-primary text-white">
                    <h6 class="tx-uppercase tx-10 tx-spacing-1 tx-semibold mg-b-0">PENJUALAN VISION BLU</h6>
                </div>
                <div class="card-body pd-10">
                    <div id="topSalesVisionBlu">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin"></i> Loading Vision Blu...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header pd-10 bg-success text-white">
                    <h6 class="tx-uppercase tx-10 tx-spacing-1 tx-semibold mg-b-0"> PENJUALAN VISION BLU EXTRA</h6>
                </div>
                <div class="card-body pd-10">
                    <div id="topSalesVisionBluExtra">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin"></i> Loading Vision Blu Extra...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Baris Kedua: Filter dan Produk -->
    <div class="row mg-b-15">
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header pd-10 bg-warning text-dark">
                    <h6 class="tx-uppercase tx-10 tx-spacing-1 tx-semibold mg-b-0">Penjualan DPE</h6>
                </div>
                <div class="card-body pd-10">
                    <div id="topSalesData">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin"></i> Loading Data...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card">
                <div class="card-header pd-10 bg-info text-white">
                    <h6 class="tx-uppercase tx-10 tx-spacing-1 tx-semibold mg-b-0">Penjualan DPE</h6>
                </div>
                <div class="card-body pd-10">
                    <div id="topSalesDPE">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin"></i> Loading DPE...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mg-b-15">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header pd-10">
                    <h6 class="tx-uppercase tx-10 tx-spacing-1 tx-color-02 tx-semibold mg-b-0">Filter Produk & Outlet</h6>
                </div>
                <div class="card-body pd-10">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="selectProduk" class="form-label tx-semibold">Pilih Produk:</label>
                                <input list="productList" class="form-control form-control-sm" id="selectProduk" placeholder="Ketik nama produk..." autocomplete="off">
                                <datalist id="productList">
                                    <option value="">Semua Produk</option>
                                </datalist>
                                <small class="text-muted">Ketik untuk mencari produk...</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="selectOutlet" class="form-label tx-semibold">Pilih Outlet:</label>
                                <input list="outletList" class="form-control form-control-sm" id="selectOutlet" placeholder="Ketik nama outlet..." autocomplete="off">
                                <datalist id="outletList">
                                    <option value="">Semua Outlet</option>
                                </datalist>
                                <small class="text-muted">Ketik untuk mencari outlet...</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="btn-group btn-group-sm" style="width: 100%;">
                                <button class="btn btn-primary" type="button" id="btnFilterProduk" style="width: 48%; margin-right: 4%;">
                                    <i class="fa fa-filter"></i> Cari Produk
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="btnClearProduk" style="width: 48%;">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="btn-group btn-group-sm" style="width: 100%;">
                                <button class="btn btn-success" type="button" id="btnFilterOutlet" style="width: 48%; margin-right: 4%;">
                                    <i class="fa fa-filter"></i> Cari Outlet
                                </button>
                                <button class="btn btn-outline-secondary" type="button" id="btnClearOutlet" style="width: 48%;">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover mg-b-0">
            <thead>
                <tr>
                    <th><center>#</center></th>
                    <th><center>Tgl. Faktur</center></th>
                    <th>Nomor Faktur</th>
                    <th>Nama Outlet</th>
                    <th>Kategori Produk</th>
                    <th>Nama Produk</th>
                    <th>Qty</th>
                    <th>Harga</th>
                    <th>Diskon</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody id="isitabel"></tbody>
        </table>
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
		</div>
    </div>
</div>
<script>
$(document).ready(function(){
    viewdata('rpenjualandpe', 15, 1);
    loadTopSalesVisionBlu();
    loadTopSalesVisionBluExtra();
    loadTopSalesData();
    loadTopSalesDPE();
    loadProductList();
    loadOutletList();
    
    // Initialize button text
    $('#btnFilterProduk').html('<i class="fa fa-filter"></i> Cari');
    $('#btnClearProduk').html('<i class="fa fa-times"></i> Clear');
    $('#btnFilterOutlet').html('<i class="fa fa-filter"></i> Cari');
    $('#btnClearOutlet').html('<i class="fa fa-times"></i> Clear');
    
    // Filter functionality
    $('#btnFilterProduk').click(function() {
        filterByProduct();
    });
    
    $('#btnFilterOutlet').click(function() {
        filterByOutlet();
    });
    
    $('#btnClearProduk').click(function() {
        $('#selectProduk').val('');
        clearProductFilter();
    });
    
    $('#btnClearOutlet').click(function() {
        $('#selectOutlet').val('');
        clearOutletFilter();
    });
    
    // Handle input changes
    $('#selectProduk').on('input', function() {
        var inputValue = $(this).val();
        // Optional: Auto-suggest or validate against datalist
    });
    
    $('#selectOutlet').on('input', function() {
        var inputValue = $(this).val();
        // Optional: Auto-suggest or validate against datalist
    });
});

function loadProductList() {
    $.ajax({
        url: 'json/rpenjualandpe/getproducts.php',
        type: 'GET',
        data: {
            type: 'produk'
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success' && response.data.length > 0) {
                // Clear existing options
                $('#productList').empty();
                
                // Add default option
                $('#productList').append('<option value="">Semua Produk</option>');
                
                // Add product options only
                $.each(response.data, function(index, item) {
                    if(item.type === 'produk') {
                        $('#productList').append('<option value="' + item.name + '">');
                    }
                });
            } else {
                $('#productList').empty().append('<option value="">Tidak ada produk</option>');
            }
        },
        error: function() {
            console.log('Error loading product list');
            $('#productList').empty().append('<option value="">Error loading products</option>');
        }
    });
}

function loadOutletList() {
    $.ajax({
        url: 'json/rpenjualandpe/getproducts.php',
        type: 'GET',
        data: {
            type: 'outlet'
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success' && response.data.length > 0) {
                // Clear existing options
                $('#outletList').empty();
                
                // Add default option
                $('#outletList').append('<option value="">Semua Outlet</option>');
                
                // Add outlet options only
                $.each(response.data, function(index, item) {
                    if(item.type === 'outlet') {
                        $('#outletList').append('<option value="' + item.name + '">');
                    }
                });
            } else {
                $('#outletList').empty().append('<option value="">Tidak ada outlet</option>');
            }
        },
        error: function() {
            console.log('Error loading outlet list');
            $('#outletList').empty().append('<option value="">Error loading outlets</option>');
        }
    });
}

function filterByProduct() {
    var selectedInput = $('#selectProduk').val();
    
    if(selectedInput === '') {
        // Jika tidak ada input dipilih, load semua data
        clearProductFilter();
        return;
    }
    
    // Show loading indicator
    $('#btnFilterProduk').html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    $('#btnFilterProduk').prop('disabled', true);
    
    // Cari ID berdasarkan input yang dipilih (produk atau outlet)
    $.ajax({
        url: 'json/rpenjualandpe/getproductoroutletid.php',
        type: 'GET',
        data: {
            search_input: selectedInput
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                // Update caridata berdasarkan tipe (produk atau outlet)
                var currentCaridata = $('#caridata').val();
                var pecah = currentCaridata.split('_');
                var newCaridata = '';
                
                if(response.type === 'produk') {
                    // Format: outlet_produk_tgl1_tgl2
                    newCaridata = (pecah[0] || '') + '_' + response.id_produk + '_' + (pecah[2] || '') + '_' + (pecah[3] || '');
                } else if(response.type === 'outlet') {
                    // Format: outlet_produk_tgl1_tgl2
                    newCaridata = response.id_outlet + '_' + (pecah[1] || '') + '_' + (pecah[2] || '') + '_' + (pecah[3] || '');
                }
                
                $('#caridata').val(newCaridata);
                $('#halaman').val(1);
                
                // Reload data dengan filter
                viewdata('rpenjualandpe', $('#maximal').val(), $('#halaman').val());
                
                // Reload all top sales widgets dengan filter yang sama
                loadTopSalesVisionBlu();
                loadTopSalesVisionBluExtra();
                loadTopSalesData();
                loadTopSalesDPE();
                loadTopSalesDPE();
                
                // Update XLS button to show filter is active
                var displayName = response.type === 'produk' ? response.nama_produk : response.nama_outlet;
                updateXLSButton(displayName);
            } else {
                alert('Data tidak ditemukan: ' + response.message);
            }
        },
        error: function() {
            alert('Error mencari data');
        },
        complete: function() {
            // Reset button
            $('#btnFilterProduk').html('<i class="fa fa-filter"></i> Filter');
            $('#btnFilterProduk').prop('disabled', false);
        }
    });
}

function clearProductFilter() {
    // Show loading indicator
    $('#btnClearProduk').html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    $('#btnClearProduk').prop('disabled', true);
    
    // Reset input
    $('#selectProduk').val('');
    
    // Reload full product list
    loadProductList();
    
    // Reset caridata ke tanpa filter produk
    var currentCaridata = $('#caridata').val();
    var pecah = currentCaridata.split('_');
    
    // Format: outlet__tgl1_tgl2 (produk kosong)
    var newCaridata = (pecah[0] || '') + '__' + (pecah[2] || '') + '_' + (pecah[3] || '');
    
    $('#caridata').val(newCaridata);
    $('#halaman').val(1);
    
    // Reload semua data
    viewdata('rpenjualandpe', $('#maximal').val(), $('#halaman').val());
    
    // Reload all top sales widgets tanpa filter
    loadTopSalesVisionBlu();
    loadTopSalesVisionBluExtra();
    loadTopSalesData();
    loadTopSalesDPE();
    
    // Reset XLS button
    updateXLSButton('');
    
    // Reset button after a short delay
    setTimeout(function() {
        $('#btnClearProduk').html('<i class="fa fa-times"></i> Clear');
        $('#btnClearProduk').prop('disabled', false);
    }, 1000);
}

function searchByProduct() {
    var searchTerm = $('#searchProduk').val().toLowerCase();
    if(searchTerm.length < 2) {
        alert('Minimal 2 karakter untuk pencarian');
        return;
    }
    
    $('#isitabel tr').each(function() {
        var productName = $(this).find('td:eq(5)').text().toLowerCase(); // kolom nama produk
        if(productName.indexOf(searchTerm) === -1) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
    
    // Filter both top sales widgets
    $('#topSalesThisMonth tbody tr, #topSalesLastMonth tbody tr').each(function() {
        var productName = $(this).find('td:eq(2)').text().toLowerCase(); // kolom nama produk di top sales
        if(productName.indexOf(searchTerm) === -1) {
            $(this).hide();
        } else {
            $(this).show();
        }
    });
}

function clearProductSearch() {
    $('#isitabel tr').show();
    $('#topSalesThisMonth tbody tr, #topSalesLastMonth tbody tr').show();
}

function filterByOutlet() {
    var selectedOutlet = $('#selectOutlet').val();
    
    if (!selectedOutlet) {
        alert('Pilih outlet terlebih dahulu');
        return;
    }
    
    // Show loading indicator
    $('#btnFilterOutlet').html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    $('#btnFilterOutlet').prop('disabled', true);
    
    // Use the same endpoint as product filtering
    $.ajax({
        url: 'json/rpenjualandpe/getproductoroutletid.php',
        type: 'GET',
        data: {
            search_input: selectedOutlet
        },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                // Update caridata with outlet
                var currentCaridata = $('#caridata').val();
                var pecah = currentCaridata.split('_');
                var newCaridata = '';
                
                if(response.type === 'outlet') {
                    // Format: outlet_produk_tgl1_tgl2
                    newCaridata = response.id_outlet + '_' + (pecah[1] || '') + '_' + (pecah[2] || '') + '_' + (pecah[3] || '');
                } else {
                    // Fallback for product type
                    newCaridata = (pecah[0] || '') + '_' + response.id_produk + '_' + (pecah[2] || '') + '_' + (pecah[3] || '');
                }
                
                $('#caridata').val(newCaridata);
                $('#halaman').val(1);
                
                // Reload data dengan filter outlet
                viewdata('rpenjualandpe', $('#maximal').val(), $('#halaman').val());
                
                // Reload all top sales widgets dengan filter outlet
                loadTopSalesVisionBlu();
                loadTopSalesVisionBluExtra();
                loadTopSalesData();
                loadTopSalesDPE();
                
                // Update XLS button to show filter is active
                var displayName = response.type === 'outlet' ? response.nama_outlet : response.nama_produk;
                updateXLSButton(displayName);
            } else {
                alert('Data tidak ditemukan: ' + response.message);
            }
        },
        error: function() {
            alert('Error mencari data');
        },
        complete: function() {
            // Reset button
            $('#btnFilterOutlet').html('<i class="fa fa-filter"></i> Filter');
            $('#btnFilterOutlet').prop('disabled', false);
        }
    });
}

function clearOutletFilter() {
    // Show loading indicator
    $('#btnClearOutlet').html('<i class="fa fa-spinner fa-spin"></i> Loading...');
    $('#btnClearOutlet').prop('disabled', true);
    
    // Reset input
    $('#selectOutlet').val('');
    
    // Reload full outlet list
    loadOutletList();
    
    // Reset caridata ke tanpa filter outlet
    var currentCaridata = $('#caridata').val();
    var pecah = currentCaridata.split('_');
    
    // Format: _produk_tgl1_tgl2 (outlet kosong)
    var newCaridata = '_' + (pecah[1] || '') + '_' + (pecah[2] || '') + '_' + (pecah[3] || '');
    
    $('#caridata').val(newCaridata);
    $('#halaman').val(1);
    
    // Reload semua data
    viewdata('rpenjualandpe', $('#maximal').val(), $('#halaman').val());
    
    // Reload all top sales widgets tanpa filter
    loadTopSalesVisionBlu();
    loadTopSalesVisionBluExtra();
    loadTopSalesData();
    loadTopSalesDPE();
    
    // Reset XLS button
    updateXLSButton('');
    
    // Reset button after a short delay
    setTimeout(function() {
        $('#btnClearOutlet').html('<i class="fa fa-times"></i> Clear');
        $('#btnClearOutlet').prop('disabled', false);
    }, 1000);
}

function loadTopSalesVisionBlu() {
    var caridata = $('#caridata').val();
    
    $.ajax({
        url: 'json/rpenjualandpe/topsales.php',
        type: 'GET',
        data: {
            caridata: caridata,
            category: 'vision_blu'
        },
        dataType: 'json',
        success: function(response) {
            var html = generateCategoryTopSalesHTML(response, 'Vision Blu', 'outlet');
            $('#topSalesVisionBlu').html(html);
        },
        error: function() {
            $('#topSalesVisionBlu').html('<div class="alert alert-danger mg-b-10">Error loading Vision Blu data.</div>');
        }
    });
}

function loadTopSalesVisionBluExtra() {
    var caridata = $('#caridata').val();
    
    $.ajax({
        url: 'json/rpenjualandpe/topsales.php',
        type: 'GET',
        data: {
            caridata: caridata,
            category: 'vision_blu_extra'
        },
        dataType: 'json',
        success: function(response) {
            var html = generateCategoryTopSalesHTML(response, 'Vision Blu Extra', 'outlet');
            $('#topSalesVisionBluExtra').html(html);
        },
        error: function() {
            $('#topSalesVisionBluExtra').html('<div class="alert alert-danger mg-b-10">Error loading Vision Blu Extra data.</div>');
        }
    });
}

function loadTopSalesDPE() {
    var caridata = $('#caridata').val();
    var currentDate = new Date();
    var currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
    var currentYear = currentDate.getFullYear();
    var firstDay = currentYear + '-' + currentMonth + '-01';
    var lastDay = new Date(currentYear, currentDate.getMonth() + 1, 0).toISOString().split('T')[0];
    
    $.ajax({
        url: 'json/rpenjualandpe/topsales.php',
        type: 'GET',
        data: {
            caridata: caridata,
            tgl1: firstDay,
            tgl2: lastDay,
            category: 'dpe_general'
        },
        dataType: 'json',
        success: function(response) {
            var html = generateCategoryTopSalesHTML(response, 'DPE Products', 'item');
            $('#topSalesDPE').html(html);
        },
        error: function() {
            $('#topSalesDPE').html('<div class="alert alert-danger mg-b-10">Error loading DPE data.</div>');
        }
    });
}

function loadTopSalesData() {
    var caridata = $('#caridata').val();
    var currentDate = new Date();
    var currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
    var currentYear = currentDate.getFullYear();
    var firstDay = currentYear + '-' + currentMonth + '-01';
    var lastDay = new Date(currentYear, currentDate.getMonth() + 1, 0).toISOString().split('T')[0];
    
    $.ajax({
        url: 'json/rpenjualandpe/topsales.php',
        type: 'GET',
        data: {
            caridata: caridata,
            tgl1: firstDay,
            tgl2: lastDay,
            category: 'penjualan_data'
        },
        dataType: 'json',
        success: function(response) {
            var html = generateCategoryTopSalesHTML(response, 'Penjualan Data', 'outlet');
            $('#topSalesData').html(html);
        },
        error: function() {
            $('#topSalesData').html('<div class="alert alert-danger mg-b-10">Error loading outlet data.</div>');
        }
    });
}

function generateCategoryTopSalesHTML(response, category, type) {
    if(response.status === 'success') {
        var html = '<div class="table-responsive">';
        html += '<table class="table table-striped table-hover table-sm">';
        html += '<thead class="thead-dark">';
        html += '<tr>';
        
        if(type === 'outlet' && category === 'Penjualan Data') {
            html += '<th width="70%">Outlet</th>';
            html += '<th width="30%"><center>Total Rp</center></th>';
        } else if(type === 'outlet') {
            html += '<th width="60%">Outlet</th>';
            html += '<th width="40%"><center>QTY</center></th>';
        } else {
            html += '<th width="60%">Item</th>';
            html += '<th width="40%"><center>QTY</center></th>';
        }
        
        html += '</tr>';
        html += '</thead>';
        html += '<tbody>';
        
        if(response.data && response.data.length > 0) {
            $.each(response.data, function(index, item) {
                if(index < 5) { // Limit to top 5
                    var badgeClass = index === 0 ? 'badge-warning' : (index === 1 ? 'badge-info' : 'badge-secondary');
                    var icon = index === 0 ? 'fa-trophy' : (index === 1 ? 'fa-medal' : 'fa-star');
                    
                    html += '<tr>';
                    
                    if(type === 'outlet') {
                        html += '<td><span class="badge ' + badgeClass + ' mr-2"><i class="fa ' + icon + '"></i></span><strong>' + item.nama_out + '</strong></td>';
                        
                        if(category === 'Penjualan Data') {
                            var totalRp = item.total_amount ? 'Rp ' + number_format(Math.round(item.total_amount)) : 'Rp 0';
                            html += '<td class="text-center"><strong>' + totalRp + '</strong></td>';
                        } else {
                            html += '<td class="text-center"><strong>' + number_format(item.total_qty) + '</strong></td>';
                        }
                    } else {
                        html += '<td><span class="badge ' + badgeClass + ' mr-2"><i class="fa ' + icon + '"></i></span>' + item.nama_pro + '</td>';
                        html += '<td class="text-center"><strong>' + number_format(item.total_qty) + '</strong></td>';
                    }
                    
                    html += '</tr>';
                }
            });
        } else {
            var colspan = (type === 'outlet' && category === 'Penjualan Data') ? '2' : '2';
            html += '<tr><td colspan="' + colspan + '" class="text-center">Tidak ada data ' + category + '</td></tr>';
        }
        
        html += '</tbody>';
        html += '</table>';
        html += '</div>';
        return html;
    } else {
        return '<div class="alert alert-warning mg-b-10">Tidak ada data ' + category + '.</div>';
    }
}

function number_format(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function downloadExcel() {
    // Get current search parameters
    var currentCaridata = $('#caridata').val();
    var selectedProduct = $('#selectProduk').val();
    
    // Show notification if product filter is active
    if(selectedProduct !== '') {
        if(!confirm('Download Excel untuk produk "' + selectedProduct + '" saja?')) {
            return;
        }
    }
    
    // Build download URL with current parameters
    var downloadUrl = '<?php echo($sistem); ?>/laporan/xls/rpenjualandpe/rpenjualandpe.php?key=' + currentCaridata;
    
    // Open in new tab for download
    window.open(downloadUrl, '_blank');
}

function updateXLSButton(productName) {
    var xlsButton = $('button[onclick="downloadExcel()"]');
    if(xlsButton.length > 0) {
        if(productName !== '') {
            // Show that filter is active
            xlsButton.removeClass('btn-success').addClass('btn-warning');
            xlsButton.html('<i class="fa fa-print"></i> XLS (' + productName.substring(0, 15) + (productName.length > 15 ? '...' : '') + ')');
            xlsButton.attr('title', 'Download Excel untuk produk: ' + productName);
        } else {
            // Reset to normal
            xlsButton.removeClass('btn-warning').addClass('btn-success');
            xlsButton.html('<i class="fa fa-print"></i> XLS');
            xlsButton.attr('title', 'Download Excel semua data');
        }
    }
}
</script>

<script>
$(document).ready(function(){
    viewdata('rpenjualandpe', $('#maximal').val(), $('#halaman').val());
    
    // Load both product and outlet lists for the dual search system
    loadProductList();
    loadOutletList();
    
    // Load all top sales widgets on page load
    loadTopSalesVisionBlu();
    loadTopSalesVisionBluExtra(); 
    loadTopSalesData();
    loadTopSalesDPE();
});
</script>