<?php
require_once('config/connection/connection.php');
$base = new DB;
$conn = $base->open();
$q = $conn->query('SHOW CREATE TABLE nomor_faktur_booking');
print_r($q->fetch(PDO::FETCH_ASSOC));
