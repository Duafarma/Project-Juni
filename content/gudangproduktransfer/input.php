<?php

/**
 * Product Transfer Management - Input Page
 * 
 * This file handles creating new product transfers between warehouses,
 * including UI for form input, product selection, and validation.
 */

// Generate unique transfer code with timestamp and random number
// Get the current date components
$today = date('Y-m-d');
$year = date('y');
$month = date('m');
$day = date('d');

// Get current auto increment number from database
$conn = $data->open();
try {
    // Get the latest transfer number for today
    $query = $conn->query("SELECT MAX(SUBSTRING_INDEX(SUBSTRING_INDEX(kode_ttg, '/', 2), '/', -1)) as last_num 
                          FROM transfer_gudang 
                          WHERE DATE(tgl_ttg) = CURRENT_DATE()");
    $result = $query->fetch(PDO::FETCH_ASSOC);

    // Generate next number
    $last_num = $result['last_num'];
    $next_num = is_numeric($last_num) ? (int)$last_num + 1 : 1; // If no record found or not numeric, start with 1
    $formatted_num = str_pad($next_num, 4, '0', STR_PAD_LEFT);

    // Get the current inventory selection if the form was submitted
    $selected_inventory_id = isset($_POST['gudang_asal']) ? $secu->injection($_POST['gudang_asal']) : null;

    // Query to get inventory information
    if ($selected_inventory_id) {
        // If we have a selected inventory ID, query for that specific inventory
        $inventory_query = $conn->prepare("SELECT id_inventory, nama_inventory FROM master_inventory WHERE id_inventory = :id_inventory LIMIT 1");
        $inventory_query->bindParam(':id_inventory', $selected_inventory_id);
        $inventory_query->execute();
        $inventory_result = $inventory_query->fetch(PDO::FETCH_ASSOC);
    } else {
        // If no selection, get the first inventory as default
        $inventory_query = $conn->query("SELECT id_inventory, nama_inventory FROM master_inventory ORDER BY nama_inventory ASC LIMIT 1");
        $inventory_result = $inventory_query->fetch(PDO::FETCH_ASSOC);
    }

    // Use the inventory result
    if ($inventory_result) {
        $inventory_id = $inventory_result['id_inventory'];
        $inventory_name = $inventory_result['nama_inventory'];
    } else {
        // Fallback defaults
        $inventory_id = '001';
        $inventory_name = 'Default Inventory';
    }

    // Generate initials from the inventory name (take first letter of each word)
    $initials = '';
    $words = explode(' ', trim($inventory_name));
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
    }

    // Replace slashes for safety and trim whitespace
    $inventory_name = str_replace('/', '-', $inventory_name);
    $inventory_name = trim($inventory_name);

    // Get month in roman numerals using the romawi() function
    $roman_month = $data->romawi((int)$month);

    // Generate the final code with initials instead of full name
    $kode = "TRF/$formatted_num/$initials/$roman_month/$year$month$day";
    $kode = strtoupper($kode); // Ensure the code is in uppercase

} catch (Exception $e) {
    error_log("Error generating transfer code: " . $e->getMessage());
    // Fallback code generation if database query fails
    $kode = "TRF/" . rand(1000, 9999) . "/INV001/I/" . $year . $month . $day;
}

// Retrieve inventory data for warehouse selection dropdowns
$conn = $data->open();
$inventory_list = [];
try {
    $inventory_query = $conn->query("SELECT id_inventory, nama_inventory FROM master_inventory ORDER BY nama_inventory ASC");
    $inventory_list = $inventory_query->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching inventory: " . $e->getMessage());
}

// Maintain backward compatibility with existing variable names
$inventory = $inventory_list;
?>

<!-- Include External CSS File -->
<link rel="stylesheet" href="<?= $sistem; ?>/config/css/gudangproduktransfer.css">

<!-- Page Header with Breadcrumb Navigation -->
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= $sistem; ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item"><a href="<?= $sistem; ?>/gudangproduktransfer">Transfer Gudang</a></li>
                <li class="breadcrumb-item active" aria-current="page">Input Transfer</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Gudang Keluar (OUT)</h4>
    </div>
</div>

<!-- Main Content Area -->
<div class="content-body">
    <div class="component-section no-code">
        <!-- Company Information Section -->
        <h5 id="section1" class="tx-semibold"><?php echo htmlspecialchars($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo htmlspecialchars($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo htmlspecialchars($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo htmlspecialchars($data->sistem('alamat_sis')); ?></div>
        </div>

        <!-- Main Transfer Form -->
        <form id="formTransferGudang" method="POST" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="gudangproduktransfer" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />

            <!-- Transfer Details Section -->
            <div class="row row-sm">
                <div class="col-sm-6">
                    <!-- Transfer Code Field (Auto-generated) -->
                    <div class="form-group">
                        <label class="form-label">Kode Transfer</label>
                        <input type="text" name="kode_ttg" id="kode_ttg" class="form-control" value="<?= htmlspecialchars($kode); ?>" readonly>
                    </div>

                    <!-- Transfer Date Field -->
                    <div class="form-group">
                        <label class="form-label">Tanggal Transfer <span class="tx-danger">*</span></label>
                        <input type="date" name="tanggal_ttg" id="tanggal_ttg" class="form-control" value="<?= htmlspecialchars($today); ?>" required>
                    </div>
                </div>

                <div class="col-sm-6">
                    <!-- Transfer Type Field (Fixed to OUT) -->
                    <div class="form-group">
                        <label class="form-label">Tipe Transfer <span class="tx-danger">*</span></label>
                        <select name="tipe_transfer" id="tipe_transfer" class="form-control" required readonly>
                            <option value="OUT" selected>OUT (Keluar)</option>
                        </select>
                    </div>

                    <!-- Source Warehouse Selection -->
                    <div class="form-group">
                        <label class="form-label">Gudang Asal <span class="tx-danger">*</span></label>
                        <select name="gudang_asal" id="gudang_asal" class="form-control select2" required>
                            <option value="">-- Pilih Gudang Asal --</option>
                            <?php foreach ($inventory_list as $inv): ?>
                                <option value="<?= htmlspecialchars($inv['id_inventory']); ?>">
                                    <?= htmlspecialchars($inv['nama_inventory']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row row-sm">
                <div class="col-sm-6">
                    <!-- Destination Warehouse Selection -->
                    <div class="form-group">
                        <label class="form-label">Gudang Tujuan <span class="tx-danger">*</span></label>
                        <select name="gudang_tujuan" id="gudang_tujuan" class="form-control select2" required>
                            <option value="">-- Pilih Gudang Tujuan --</option>
                            <?php foreach ($inventory_list as $inv): ?>
                                <option value="<?= htmlspecialchars($inv['id_inventory']); ?>">
                                    <?= htmlspecialchars($inv['nama_inventory']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-sm-6">
                    <!-- Additional Notes Field -->
                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan_ttg" id="catatan_ttg" class="form-control" rows="3"
                            placeholder="Tambahkan catatan atau keterangan (opsional)"></textarea>
                    </div>
                </div>
            </div>

            <!-- Product Selection Section -->
            <div class="clearfix mg-t-25 mg-b-25"></div>
            <h5 id="section1" class="tx-semibold">Transfer Produk</h5>
            <p class="mg-b-25">Pilih produk yang akan ditransfer keluar.</p>

            <div class="row row-sm">
                <div class="col-sm-12">
                    <!-- Add Product Button -->
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" id="btnAddProduct" class="btn btn-primary">
                            <i class="fa fa-plus-circle"></i> Tambah Produk
                        </button>
                    </div>

                    <!-- Empty State - Shown when no products added -->
                    <div id="emptyProductState" class="text-center py-5">
                        <i class="fa fa-box-open fa-4x text-muted mb-3"></i>
                        <h5>Belum Ada Produk</h5>
                        <p class="text-muted mb-3">Tambahkan produk yang ingin ditransfer keluar dengan mengklik tombol "Tambah Produk"</p>
                        <button type="button" id="btnAddProductEmpty" class="btn btn-outline-primary">
                            <i class="fa fa-plus-circle"></i> Tambah Produk
                        </button>
                    </div>

                    <!-- Product List Table - Initially Hidden -->
                    <div id="productTableContainer" class="d-none">
                        <div class="table-responsive">
                            <table id="productTable" class="table table-bordered table-hover mg-b-0">
                                <thead class="thead-primary">
                                    <tr>
                                        <th class="text-center" width="5%">No</th>
                                        <th width="25%">Produk</th>
                                        <th width="10%">Batch</th>
                                        <th width="15%">Kadaluarsa</th>
                                        <th width="15%">Stok Tersedia</th>
                                        <th width="15%">Jumlah Transfer</th>
                                        <th class="text-center" width="15%">Tindakan</th>
                                    </tr>
                                </thead>
                                <tbody id="tableTransferProduct">
                                    <!-- Products will be added here dynamically -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right">Total Item:</td>
                                        <td colspan="2" id="totalItems">0</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="text-right">Total Qty:</td>
                                        <td colspan="2" id="totalQty">0</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Hidden fields for product tracking -->
                    <input type="hidden" name="countaddProductTransfer" id="countaddProductTransfer" value="1">
                    <input type="hidden" name="cartaddProductTransfer" id="cartaddProductTransfer" value="">
                    <input type="hidden" name="type" id="type" value="OUT">
                    <input type="hidden" name="inventory_id" id="inventory_id" value="">
                </div>
            </div>

            <!-- Form Action Buttons -->
            <div class="form-layout-footer mg-t-30">
                <div class="row">
                    <div class="col-sm-6">
                        <a href="<?= $sistem; ?>/gudangproduktransfer" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button type="button" id="btnSubmitTransfer" class="btn btn-primary">
                            <i class="fa fa-save"></i> Simpan Transfer
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Product Selection Modal -->
<div class="modal fade" id="modal2" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle"><i class="fa fa-boxes mr-2"></i> Pilih Produk dari Gudang</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Include External JavaScript -->
<script src="<?= $sistem; ?>/config/js/gudangproduktransfer.js"></script>

<!-- Pass sistem URL to JS -->
<script>
    // Pass sistem URL to JavaScript for AJAX requests
    var sistemUrl = "<?= $sistem; ?>";
    
    $(document).ready(function() {
        // Initialize the warehouse transfer functionality
        if (typeof initTransferPage === 'function') {
            initTransferPage(sistemUrl);
        } else {
            console.error("Error: gudangproduktransfer.js was not loaded correctly.");
        }
    });
</script>