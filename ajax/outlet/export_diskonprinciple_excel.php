<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');

$secu = new Security;
$base = new DB;
$conn = $base->open();

$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);

// Validasi login
if ($secu->validadmin($admin, $kunci) == false) {
    http_response_code(401);
    echo 'Session login anda habis';
    exit;
}

$outlet_id = $secu->injection(@$_GET['outlet_id']);
$principle_id = $secu->injection(@$_GET['principle_id']);

if (empty($outlet_id) || empty($principle_id)) {
    http_response_code(400);
    echo 'Parameter tidak lengkap';
    exit;
}

try {
    // Ambil info outlet
    $stmtOutlet = $conn->prepare("SELECT o.resmi_out, o.nama_out FROM outlet o WHERE o.id_out = :id_out");
    $stmtOutlet->bindParam(':id_out', $outlet_id, PDO::PARAM_STR);
    $stmtOutlet->execute();
    $outlet = $stmtOutlet->fetch(PDO::FETCH_ASSOC);

    if (!$outlet) {
        http_response_code(404);
        echo 'Outlet tidak ditemukan';
        exit;
    }

    // Ambil info principle
    $stmtPrinciple = $conn->prepare("SELECT nama_principle FROM master_principle WHERE id_mp = :id_mp");
    $stmtPrinciple->bindParam(':id_mp', $principle_id, PDO::PARAM_STR);
    $stmtPrinciple->execute();
    $principle = $stmtPrinciple->fetch(PDO::FETCH_ASSOC);

    if (!$principle) {
        http_response_code(404);
        echo 'Principle tidak ditemukan';
        exit;
    }

    // Query produk + diskon saat ini
    $query = "SELECT p.id_pro, p.nama_pro, p.berat_pro,
                     COALESCE(pd.persen_pds, 0) AS diskon_current
              FROM produk p
              LEFT JOIN produk_diskon pd ON p.id_pro = pd.id_pro AND pd.id_out = :outlet_id
              WHERE p.nama_p = :principle_id
              AND LOWER(p.status_pro) = 'active'
              ORDER BY p.nama_pro";

    $stmt = $conn->prepare($query);
    $stmt->bindParam(':outlet_id', $outlet_id, PDO::PARAM_STR);
    $stmt->bindParam(':principle_id', $principle_id, PDO::PARAM_STR);
    $stmt->execute();

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load PhpSpreadsheet bila tersedia
    $vendorPath = '../../vendor/autoload.php';
    if (file_exists($vendorPath)) {
        require_once($vendorPath);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Diskon Principle');

        // Header info
        $sheet->setCellValue('A1', 'Outlet');
        $sheet->setCellValue('B1', $outlet['resmi_out']);
        $sheet->setCellValue('A2', 'Principle');
        $sheet->setCellValue('B2', $principle['nama_principle']);
        $sheet->setCellValue('A3', 'Generated At');
        $sheet->setCellValue('B3', date('Y-m-d H:i:s'));

        // Table header
        $startRow = 5;
        $sheet->setCellValue('A' . $startRow, 'No');
        $sheet->setCellValue('B' . $startRow, 'Nama Produk');
        $sheet->setCellValue('C' . $startRow, 'Diskon (%)');

        $row = $startRow + 1;
        $no = 1;
        foreach ($products as $product) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $product['nama_pro']);
            $sheet->setCellValue('C' . $row, (float)$product['diskon_current']);
            $row++;
        }

        // Styling basic
        $sheet->getStyle('A' . $startRow . ':C' . $startRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $startRow . ':C' . ($row - 1))
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);

        $safeOutlet = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outlet['resmi_out']);
        $safePrinciple = preg_replace('/[^A-Za-z0-9_\-]/', '_', $principle['nama_principle']);
        $filename = 'Diskon_Principle_' . $safeOutlet . '_' . $safePrinciple . '_' . date('Ymd_His') . '.xlsx';

        // Output headers
        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // Fallback: HTML table (xls)
    $safeOutlet = preg_replace('/[^A-Za-z0-9_\-]/', '_', $outlet['resmi_out']);
    $safePrinciple = preg_replace('/[^A-Za-z0-9_\-]/', '_', $principle['nama_principle']);
    $filename = 'Diskon_Principle_' . $safeOutlet . '_' . $safePrinciple . '_' . date('Ymd_His') . '.xls';

    if (ob_get_length()) {
        ob_end_clean();
    }
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<title>Diskon Principle</title>';
    echo '<style>table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #000; padding: 5px; } th { background-color: #f2f2f2; }</style>';
    echo '</head>';
    echo '<body>';
    echo '<h3>Diskon by Principle</h3>';
    echo '<p>Outlet: ' . htmlspecialchars($outlet['resmi_out']) . '</p>';
    echo '<p>Principle: ' . htmlspecialchars($principle['nama_principle']) . '</p>';
    echo '<p>Generated At: ' . date('Y-m-d H:i:s') . '</p>';

    echo '<table>';
    echo '<tr><th>No</th><th>Nama Produk</th><th>Diskon (%)</th></tr>';
    $no = 1;
    foreach ($products as $product) {
        echo '<tr>';
        echo '<td>' . $no++ . '</td>';
        echo '<td>' . htmlspecialchars($product['nama_pro']) . '</td>';
        echo '<td>' . htmlspecialchars($product['diskon_current']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</body></html>';
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Terjadi kesalahan: ' . $e->getMessage();
    exit;
}

$conn = $base->close();
?>
