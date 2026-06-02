<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$base = new DB;
$secu = new Security;
$data = new Data;
$conn = $base->open();

// ACCESS
$admin = $secu->injection($_COOKIE['adminkuy'] ?? '');
$kunci = $secu->injection($_COOKIE['kuncikuy'] ?? '');
$valid = $secu->validadmin($admin, $kunci);

if (!$valid) {
    http_response_code(401);
    echo 'Session login anda habis...';
    exit;
}

$caridataRaw = $secu->injection($_GET['caridata'] ?? '');
$pecah = explode('_', $caridataRaw);
$cari = $data->cekcari($pecah[0] ?? '', '-', ' ');

$fakturUnion = "
    SELECT id_tfk, MAX(kode_tfk) AS kode_tfk, MAX(id_out) AS id_out
    FROM (
        SELECT id_tfk, kode_tfk, id_out FROM transaksi_faktur
        UNION ALL
        SELECT id_tfk, kode_tfk, id_out FROM transaksi_faktur_pim
    ) AS U
    GROUP BY id_tfk
";

$q = "
    SELECT
           COALESCE(F.kode_tfk, A.no_faktur) AS kode_tfk,
           O.nama_out,
           A.created_at AS tanggal_balik,
           A.status_dokumen
    FROM dokumen_balik_detail AS A
    LEFT JOIN ($fakturUnion) AS F ON F.id_tfk = A.no_faktur
    LEFT JOIN outlet AS O ON O.id_out = F.id_out
    WHERE (A.no_faktur LIKE :cari OR F.kode_tfk LIKE :cari OR O.nama_out LIKE :cari)
    ORDER BY A.created_at DESC, A.id_dbd DESC
";

$stmt = $conn->prepare($q);
$stmt->bindValue(':cari', "%$cari%", PDO::PARAM_STR);
$stmt->execute();

require_once('../../vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Dokumen Balik');

$sheet->setCellValue('A1', 'No');
$sheet->setCellValue('B1', 'Nomor Faktur');
$sheet->setCellValue('C1', 'Nama Outlet');
$sheet->setCellValue('D1', 'Tanggal Balik');
$sheet->setCellValue('E1', 'Status');

$rowNum = 2;
$no = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $no++;
    $sheet->setCellValue('A' . $rowNum, $no);
    $sheet->setCellValueExplicit('B' . $rowNum, $row['kode_tfk'] ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue('C' . $rowNum, $row['nama_out'] ?? '');
    $sheet->setCellValue('D' . $rowNum, $row['tanggal_balik'] ?? '');
    $sheet->setCellValue('E' . $rowNum, $row['status_dokumen'] ?? '');
    $rowNum++;
}

foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

$filename = 'report_dokumen_balik_' . date('Ymd_His') . '.xlsx';

// Clean output buffer to avoid corrupting xlsx
while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn = $base->close();
exit;
?>
