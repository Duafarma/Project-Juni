<?php
// Test direct AJAX call ke loadProducts.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Forward to loadProducts.php
    $_POST['principle'] = $_POST['principle'] ?? 'Cendo';
    $_POST['mitra'] = $_POST['mitra'] ?? 'TEST001';
    $_POST['cart'] = $_POST['cart'] ?? '';
    $_POST['nomor'] = $_POST['nomor'] ?? '1';
    $_POST['search'] = $_POST['search'] ?? '';
    
    echo "<h3>AJAX Response from loadProducts.php:</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
    
    ob_start();
    include 'ajax/loadProducts.php';
    $response = ob_get_clean();
    
    echo htmlspecialchars($response);
    echo "</div>";
    
    echo "<h3>Raw Response (for debugging):</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    echo "<h3>Response Length:</h3>";
    echo "<p>" . strlen($response) . " characters</p>";
    
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Direct AJAX Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <h2><i class="fas fa-bug"></i> Direct AJAX Test</h2>
    
    <div class="row">
        <div class="col-md-6">
            <form method="POST">
                <div class="form-group">
                    <label>Principle:</label>
                    <select name="principle" class="form-control">
                        <option value="Cendo">Cendo</option>
                        <option value="DPE">DPE</option> 
                        <option value="PIM">PIM</option>
                        <option value="RHEA">RHEA</option>
                        <option value="Shirudo">Shirudo</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Mitra:</label>
                    <input type="text" name="mitra" class="form-control" value="TEST001">
                </div>
                
                <div class="form-group">
                    <label>Search:</label>
                    <input type="text" name="search" class="form-control" placeholder="Optional search term">
                </div>
                
                <button type="submit" class="btn btn-primary">Test Direct Call</button>
            </form>
        </div>
        
        <div class="col-md-6">
            <h5>Instructions:</h5>
            <ol>
                <li>Select a principle (Cendo, DPE, etc.)</li>
                <li>Click "Test Direct Call"</li>
                <li>Check if HTML response is generated</li>
                <li>Look for any PHP errors</li>
            </ol>
            
            <h5>What to Look For:</h5>
            <ul>
                <li>✅ HTML table rows in response</li>
                <li>✅ Product data visible</li>
                <li>❌ PHP errors or warnings</li>
                <li>❌ Empty response</li>
            </ul>
        </div>
    </div>
</body>
</html>