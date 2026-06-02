<?php
// Set header untuk mengizinkan akses dari mana saja
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

// Ambil URL dari parameter
$url = isset($_GET['url']) ? $_GET['url'] : '';
if (empty($url)) {
    echo json_encode(['error' => 'URL tidak disediakan']);
    exit;
}

// Ambil parameter dari query string
$query = parse_url($url, PHP_URL_QUERY);
parse_str($query, $params);

// Ambil path dari URL untuk membangun URL baru
$path = parse_url($url, PHP_URL_PATH);
$base_url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
$final_url = $base_url . $path;

if (!empty($query)) {
    $final_url .= '?' . $query;
}

// Panggil URL menggunakan curl
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $final_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Kembalikan hasil
echo json_encode([
    'url' => $final_url,
    'http_code' => $http_code,
    'raw_response' => $response,
    'curl_error' => $curl_error,
    'is_valid_json' => json_decode($response) !== null,
    'json_error' => json_last_error_msg()
]);