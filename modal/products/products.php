<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$sistem = $data->sistem('url_sis');
$conn = $base->open();

// Get parameters
$nomor = isset($_GET['nomor']) ? intval($_GET['nomor']) : 1;
$mitra = isset($_GET['mitra']) ? htmlspecialchars($_GET['mitra']) : '';
$cart = isset($_GET['cart']) ? htmlspecialchars($_GET['cart']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Build query using proper table structure
    $sql = "SELECT A.id_psd, A.no_bcode, A.tgl_expired, A.gudang, A.sisa_psd, 
                   B.id_pro, B.kode_pro, B.nama_pro, B.berat_pro, B.nama_p, 
                   C.harga_phg, D.nama_kpr, D.satuan_kpr, E.nama_spr, F.persen_pds
            FROM produk_stokdetail AS A 
            LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
            LEFT JOIN produk_harga_detail AS C ON B.id_pro=C.id_pro 
            LEFT JOIN kategori_produk AS D ON B.id_kpr=D.id_kpr 
            LEFT JOIN satuan_produk AS E ON B.id_spr=E.id_spr 
            LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro 
            WHERE A.sisa_psd>0";
    
    $conditions = array();
    
    if (!empty($search)) {
        $conditions[] = "(B.nama_pro LIKE '%$search%' OR B.kode_pro LIKE '%$search%')";
    }
    
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY B.nama_pro ASC LIMIT 50";
    
    $master = $conn->prepare($sql);
    $master->execute();
    $products = array();
    
    while($row = $master->fetch(PDO::FETCH_ASSOC)) {
        $products[] = $row;
    }
    
} catch (Exception $e) {
    $products = array();
    error_log("Error fetching products: " . $e->getMessage());
}
?>

<div class="row mb-3">
    <div class="col-12">
        <input type="text" id="productSearch" class="form-control" placeholder="Search products..." onkeyup="searchProducts()">
    </div>
</div>

<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
    <table class="table table-hover table-sm">
        <thead class="bg-light sticky-top">
            <tr>
                <th>Code</th>
                <th>Product Name</th>
                <th>Price</th>
                <th>Unit</th>
                <th>Stock</th>
                <th>Warehouse</th>
            </tr>
        </thead>
        <tbody id="productTableBody">
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-3">No products found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $row): ?>
                    <?php
                    $onclick_values = array(
                        $nomor,
                        "'" . addslashes($row['kode_pro']) . "'",
                        "'" . addslashes($row['id_pro']) . "'",
                        "'" . addslashes($row['nama_pro']) . "'",
                        "'" . addslashes($row['kode_pro']) . "'",
                        $row['harga_phg'] ?: 0,
                        $row['berat_pro'] ?: 0,
                        "'" . addslashes($row['nama_kpr']) . "'",
                        1, // satuan_qty default
                        "'" . addslashes($row['satuan_kpr']) . "'",
                        "'" . addslashes($row['no_bcode']) . "'",
                        "'" . addslashes($row['tgl_expired']) . "'",
                        "'" . addslashes($row['gudang']) . "'",
                        $row['sisa_psd'] ?: 0,
                        $row['persen_pds'] ?: 0
                    );
                    
                    $onclick = 'getproductsales(' . implode(',', $onclick_values) . ')';
                    ?>
                    <tr onclick="<?php echo htmlspecialchars($onclick); ?>" style="cursor: pointer;">
                        <td><?php echo htmlspecialchars($row['kode_pro']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_pro']); ?></td>
                        <td>Rp <?php echo number_format($row['harga_phg'] ?: 0, 0, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($row['satuan_kpr']); ?></td>
                        <td><?php echo htmlspecialchars($row['sisa_psd']); ?></td>
                        <td><?php echo htmlspecialchars($row['gudang']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function searchProducts() {
    var searchTerm = document.getElementById('productSearch').value;
    
    // Simple client-side search for now
    var rows = document.querySelectorAll('#productTableBody tr');
    
    rows.forEach(function(row) {
        var text = row.textContent.toLowerCase();
        var searchLower = searchTerm.toLowerCase();
        
        if (text.includes(searchLower) || searchTerm === '') {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Handle product selection
$(document).on('click', '#productTableBody tr[onclick]', function() {
    var onclick = $(this).attr('onclick');
    if (onclick) {
        try {
            eval(onclick);
        } catch(e) {
            console.error('Error selecting product:', e);
            alert('Error selecting product. Please try again.');
        }
    }
});
</script>

<?php
$conn = $base->close();
?>