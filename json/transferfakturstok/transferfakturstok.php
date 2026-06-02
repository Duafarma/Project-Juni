<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');
require_once('../../config/function/paging.php');
$base   = new DB;
$secu   = new Security;
$data   = new Data;
$paging = new Paging;
$conn   = $base->open();
$sistem = $data->sistem('url_sis');

$admin  = $secu->injection(@$_COOKIE['adminkuy']);
$kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
$level  = $secu->injection(@$_COOKIE['jeniskuy']);
$valid  = $secu->validadmin($admin, $kunci);

$cari   = $secu->injection(@$_GET['caridata']);
$page   = $secu->injection(@$_GET['halaman']);
$maxi   = $secu->injection(@$_GET['maximal']);
$menu   = $secu->injection(@$_GET['menudata']);
$mulai  = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

if($valid == false){
    $tabel = '<tr><td colspan="9">Session login anda habis...</td></tr>';
    $navi  = '';
} else {
    $tabel = '';
    $no    = $mulai;

    // Auto-create table jika belum ada
    $conn->exec("CREATE TABLE IF NOT EXISTS `transfer_faktur_stok` (
        `id_tfs` int(11) NOT NULL AUTO_INCREMENT,
        `nomor_transfer` varchar(100) NOT NULL,
        `id_tfk` varchar(50) NOT NULL,
        `kode_faktur` varchar(100) DEFAULT NULL,
        `tgl_faktur` date DEFAULT NULL,
        `nama_outlet` varchar(200) DEFAULT NULL,
        `total_item` int(11) NOT NULL DEFAULT 0,
        `total_qty` int(11) NOT NULL DEFAULT 0,
        `keterangan` text DEFAULT NULL,
        `status` varchar(50) DEFAULT 'selesai',
        `created_at` datetime NOT NULL,
        `created_by` varchar(100) NOT NULL,
        `updated_at` datetime NOT NULL,
        `updated_by` varchar(100) NOT NULL,
        PRIMARY KEY (`id_tfs`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");

    $qJumlah = "SELECT COUNT(*) AS total FROM transfer_faktur_stok
                WHERE nomor_transfer LIKE '%$cari%' OR kode_faktur LIKE '%$cari%' OR nama_outlet LIKE '%$cari%'";
    $jumlah  = $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);

    $qMaster = "SELECT * FROM transfer_faktur_stok
                WHERE nomor_transfer LIKE '%$cari%' OR kode_faktur LIKE '%$cari%' OR nama_outlet LIKE '%$cari%'
                ORDER BY created_at DESC
                LIMIT :mulai, :maxi";
    $master  = $conn->prepare($qMaster);
    $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();

    while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
        $no++;
        $tglFaktur  = !empty($hasil['tgl_faktur']) ? date('d-m-Y', strtotime($hasil['tgl_faktur'])) : '-';
        $createdAt  = !empty($hasil['created_at']) ? date('d-m-Y H:i', strtotime($hasil['created_at'])) : '-';
        $statusBadge = '<span class="badge badge-success">' . htmlspecialchars($hasil['status']) . '</span>';
        $view = '<a href="#" onclick="viewDetailTfs(' . $hasil['id_tfs'] . ')" title="Detail"><span class="badge badge-info"><i class="fa fa-eye"></i></span></a>';
        $tabel .= '<tr>
            <td><center>' . $no . '</center></td>
            <td><strong>' . htmlspecialchars($hasil['nomor_transfer']) . '</strong></td>
            <td>' . htmlspecialchars($hasil['kode_faktur'] ?? '-') . '</td>
            <td>' . htmlspecialchars($hasil['nama_outlet'] ?? '-') . '</td>
            <td><center>' . $tglFaktur . '</center></td>
            <td><center>' . $createdAt . '</center></td>
            <td><center><span class="badge badge-primary">' . number_format($hasil['total_item']) . '</span></center></td>
            <td><center><span class="badge badge-danger">' . number_format($hasil['total_qty']) . '</span></center></td>
            <td><center>' . $statusBadge . '</center></td>
            <td><center>' . $view . '</center></td>
        </tr>';
    }
    if($no == $mulai){
        $tabel = '<tr><td colspan="10" class="text-center text-muted">Belum ada data transfer.</td></tr>';
    }
    $navi = $paging->myPaging($menu, $jumlah['total'], $maxi, $page);
}
$conn = $base->close();
$json = ["tabel" => $tabel, "halaman" => $page, "paginasi" => $navi];
http_response_code(200);
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo json_encode($json);
