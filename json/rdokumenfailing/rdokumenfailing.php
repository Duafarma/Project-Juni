<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
require_once('../../config/function/paging.php');

$base = new DB;
$secu = new Security;
$data = new Data;
$paging = new Paging;
$conn = $base->open();

// ACCESS DATA
$admin = $secu->injection($_COOKIE['adminkuy'] ?? '');
$kunci = $secu->injection($_COOKIE['kuncikuy'] ?? '');
$valid = $secu->validadmin($admin, $kunci);

// GET DATA
$cariRaw = $secu->injection($_GET['caridata'] ?? '');
$page = (int)($secu->injection($_GET['halaman'] ?? 1));
$maxi = (int)($secu->injection($_GET['maximal'] ?? 10));
$menu = $secu->injection($_GET['menudata'] ?? '');
$mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

if (!$valid) {
    $tabel = '<tr><td colspan="5">Session login anda habis...</td></tr>';
    $navi = '';
} else {
    $pecah = explode('_', $cariRaw);
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

    // Count total records
    $qjumlah = "
        SELECT COUNT(A.id_dfd) AS total
        FROM dokumen_failing_detail AS A
        LEFT JOIN ($fakturUnion) AS F ON F.id_tfk = A.no_faktur
        LEFT JOIN outlet AS O ON O.id_out = F.id_out
        WHERE (A.no_faktur LIKE :cari OR F.kode_tfk LIKE :cari OR O.nama_out LIKE :cari)
    ";
    $stmt = $conn->prepare($qjumlah);
    $stmt->bindValue(':cari', "%$cari%", PDO::PARAM_STR);
    $stmt->execute();
    $jumlah = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch records
    $qmaster = "
        SELECT
            COALESCE(F.kode_tfk, A.no_faktur) AS kode_tfk,
            A.status_failing,
            A.created_at,
            O.nama_out
        FROM dokumen_failing_detail AS A
        LEFT JOIN ($fakturUnion) AS F ON F.id_tfk = A.no_faktur
        LEFT JOIN outlet AS O ON O.id_out = F.id_out
        WHERE (A.no_faktur LIKE :cari OR F.kode_tfk LIKE :cari OR O.nama_out LIKE :cari)
        ORDER BY A.created_at DESC, A.id_dfd DESC
        LIMIT :mulai, :maxi
    ";

    $master = $conn->prepare($qmaster);
    $master->bindValue(':cari', "%$cari%", PDO::PARAM_STR);
    $master->bindValue(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindValue(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();

    $tabel = '';
    $no = $mulai;
    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        $no++;
        $namaOutlet = $hasil['nama_out'] ?? '';
        $namaOutlet = ($namaOutlet === '') ? '-' : $namaOutlet;

        $tabel .= '<tr>' .
            '<td><center>' . $no . '</center></td>' .
            '<td>' . htmlspecialchars($hasil['kode_tfk'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>' .
            '<td>' . htmlspecialchars($namaOutlet, ENT_QUOTES, 'UTF-8') . '</td>' .
            '<td><center>' . htmlspecialchars($hasil['created_at'] ?? '', ENT_QUOTES, 'UTF-8') . '</center></td>' .
            '<td>' . htmlspecialchars($hasil['status_failing'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>' .
        '</tr>';
    }

    if ($tabel === '') {
        $tabel = '<tr><td colspan="5">Data tidak ditemukan...</td></tr>';
    }

    $navi = $paging->myPaging($menu, (int)($jumlah['total'] ?? 0), $maxi, $page);
}

$conn = $base->close();

$json = array(
    'tabel' => $tabel,
    'halaman' => $page,
    'paginasi' => $navi
);

http_response_code(200);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
echo json_encode($json);
exit;
?>
