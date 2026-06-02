<?php
require_once 'config/connection/connection.php';
$base = new DB;
$conn = $base->open();
$msgs = [];

// Tabel header faktur manual
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS transaksi_faktur_manual (
        id_tfm INT AUTO_INCREMENT PRIMARY KEY,
        id_tfk VARCHAR(100) NOT NULL COMMENT 'ID faktur asli dari transaksi_faktur',
        kode_tfk VARCHAR(100) DEFAULT '',
        sj_tfk VARCHAR(100) DEFAULT '',
        id_out VARCHAR(50) DEFAULT '',
        id_mr INT DEFAULT 0,
        ket_mr VARCHAR(255) DEFAULT '',
        subtot_tfm BIGINT DEFAULT 0,
        ppn_tfm BIGINT DEFAULT 0,
        total_tfm BIGINT DEFAULT 0,
        status_tfm VARCHAR(50) DEFAULT 'Manual',
        created_at DATETIME DEFAULT NULL,
        created_by VARCHAR(100) DEFAULT '',
        updated_at DATETIME DEFAULT NULL,
        updated_by VARCHAR(100) DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $msgs[] = "OK: tabel transaksi_faktur_manual dibuat";
} catch(PDOException $e) { $msgs[] = "transaksi_faktur_manual: ".$e->getMessage(); }

// Tabel detail faktur manual
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS transaksi_faktur_manual_detail (
        id_tfmd INT AUTO_INCREMENT PRIMARY KEY,
        id_tfm INT NOT NULL COMMENT 'FK ke transaksi_faktur_manual',
        id_tfk VARCHAR(100) DEFAULT '' COMMENT 'ID faktur asli',
        id_pro VARCHAR(50) DEFAULT '',
        id_psd VARCHAR(50) DEFAULT '',
        jumlah_tfmd INT DEFAULT 0,
        harga_tfmd BIGINT DEFAULT 0,
        diskon_tfmd VARCHAR(50) DEFAULT '0',
        total_tfmd BIGINT DEFAULT 0,
        created_at DATETIME DEFAULT NULL,
        created_by VARCHAR(100) DEFAULT ''
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $msgs[] = "OK: tabel transaksi_faktur_manual_detail dibuat";
} catch(PDOException $e) { $msgs[] = "transaksi_faktur_manual_detail: ".$e->getMessage(); }

echo implode("\n", $msgs)."\n";
$conn = null;
