<?php
// Simple debug untuk test loadProducts.php langsung
require_once('config/connection/connection.php');
require_once('config/connection/security.php');
require_once('config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

echo "<h2>Debug LoadProducts</h2>";
echo "<h3>1. Test Database Connection</h3>";

try {
    $testQuery = $conn->prepare("SELECT COUNT(*) as count FROM produk WHERE nama_p IS NOT NULL");
    $testQuery->execute();
    $result = $testQuery->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Database OK - Products with principle: " . $result['count'] . "</p>";
} catch(Exception $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>";
}

echo "<h3>2. Test Principles</h3>";
try {
    $principles = $conn->prepare("SELECT DISTINCT nama_p FROM produk WHERE nama_p IS NOT NULL ORDER BY nama_p");
    $principles->execute();
    $principleList = $principles->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<ul>";
    foreach($principleList as $p) {
        echo "<li>" . $p['nama_p'] . "</li>";
    }
    echo "</ul>";
} catch(Exception $e) {
    echo "<p>❌ Principles Error: " . $e->getMessage() . "</p>";
}

echo "<h3>3. Test LoadProducts AJAX</h3>";
?>

<form method="POST" action="ajax/loadProducts.php" target="ajaxFrame" onsubmit="return testAjax()">
    <label>Principle:</label>
    <select name="principle" id="testPrinciple">
        <option value="">-- Select --</option>
        <?php
        foreach($principleList as $p) {
            echo '<option value="'.$p['nama_p'].'">'.$p['nama_p'].'</option>';
        }
        ?>
    </select><br><br>
    
    <input type="hidden" name="mitra" value="TEST001">
    <input type="hidden" name="cart" value="">
    <input type="hidden" name="nomor" value="1">
    <input type="hidden" name="search" value="">
    
    <button type="submit">Test AJAX Call</button>
</form>

<iframe name="ajaxFrame" style="width: 100%; height: 400px; border: 1px solid #ccc; margin-top: 10px;"></iframe>

<h3>4. Check Error Log</h3>
<iframe src="error_log" style="width: 100%; height: 200px; border: 1px solid #ccc;"></iframe>

<script>
function testAjax() {
    var principle = document.getElementById('testPrinciple').value;
    if (!principle) {
        alert('Please select a principle');
        return false;
    }
    console.log('Testing with principle:', principle);
    return true;
}
</script>