<?php
// Test script untuk debugging principle dan product selection
require_once 'config/config.php';

echo "<h2>Debugging Test - Principle dan Products</h2>\n";

try {
    // Test koneksi
    echo "<h3>1. Test Database Connection</h3>\n";
    $pdo = $conn;
    echo "<p>✅ Database connection successful</p>\n";
    
    // Test principles
    echo "<h3>2. Test Master Principles</h3>\n";
    $stmt = $pdo->prepare("SELECT * FROM master_principle ORDER BY nama_principle");
    $stmt->execute();
    $principles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>\n";
    echo "<tr><th>ID</th><th>Nama Principle</th><th>Created At</th></tr>\n";
    
    foreach($principles as $principle) {
        echo "<tr>\n";
        echo "<td>".$principle['id_mp']."</td>\n";
        echo "<td>".$principle['nama_principle']."</td>\n";
        echo "<td>".($principle['created_at'] ?? 'N/A')."</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    // Test products per principle
    echo "<h3>3. Test Products per Principle (First 5 products each)</h3>\n";
    
    foreach($principles as $principle) {
        echo "<h4>Principle: ".$principle['nama_principle']."</h4>\n";
        
        // Query products for this principle
        $query = "SELECT B.nama_pro, B.kode_pro, B.nama_p 
                 FROM produk_stokdetail AS A 
                 LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                 WHERE A.sisa_psd>0 AND B.nama_p = :principle 
                 LIMIT 5";
        
        $stmt2 = $pdo->prepare($query);
        $stmt2->bindParam(':principle', $principle['nama_principle']);
        $stmt2->execute();
        $products = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        if(count($products) > 0) {
            echo "<ul>\n";
            foreach($products as $product) {
                echo "<li>".$product['nama_pro']." (Code: ".$product['kode_pro'].", Principle: ".$product['nama_p'].")</li>\n";
            }
            echo "</ul>\n";
        } else {
            echo "<p><em>Tidak ada produk ditemukan untuk principle ini.</em></p>\n";
        }
        echo "<hr>\n";
    }
    
    // Test search functionality
    echo "<h3>4. Test Search Functionality</h3>\n";
    $searchTerm = "vitamin"; // Test search term
    
    $query = "SELECT B.nama_pro, B.kode_pro, B.nama_p 
             FROM produk_stokdetail AS A 
             LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
             WHERE A.sisa_psd>0 AND (B.nama_pro LIKE :search OR B.kode_pro LIKE :search) 
             LIMIT 10";
    
    $stmt3 = $pdo->prepare($query);
    $searchParam = "%".$searchTerm."%";
    $stmt3->bindParam(':search', $searchParam);
    $stmt3->execute();
    $searchResults = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Search term: <strong>$searchTerm</strong></p>\n";
    if(count($searchResults) > 0) {
        echo "<ul>\n";
        foreach($searchResults as $product) {
            echo "<li>".$product['nama_pro']." (Code: ".$product['kode_pro'].", Principle: ".$product['nama_p'].")</li>\n";
        }
        echo "</ul>\n";
    } else {
        echo "<p><em>Tidak ada hasil pencarian.</em></p>\n";
    }
    
} catch(PDOException $e) {
    echo "<p>❌ Error: ".$e->getMessage()."</p>\n";
}

echo "<br><a href='javascript:history.back()'>← Kembali</a>\n";
?>