<?php
/**
 * JSON Endpoint - Transfer Stock Cancel History
 * Menampilkan data transfer yang sudah dilakukan dari tabel transfer_stockcancel
 */
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

// ACCESS DATA
$admin  = $secu->injection(@$_COOKIE['adminkuy']);
$kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
$level  = $secu->injection(@$_COOKIE['jeniskuy']);
$valid  = $secu->validadmin($admin, $kunci);

// POST DATA
$cari   = $secu->injection(@$_GET['caridata']);
$page   = $secu->injection(@$_GET['halaman']);
$maxi   = $secu->injection(@$_GET['maximal']);
$menu   = $secu->injection(@$_GET['menudata']);
$mulai  = ($page>1) ? (($page * $maxi) - $maxi) : 0;

// READ DATA
if($valid==false){
    $tabel  = '<tr><td colspan="10">Session login anda habis...</td></tr>';
    $navi   = '';
} else {
    // Parse filter tanggal dari cari
    $pecah  = explode('_', $cari);
    $search = @$pecah[0];
    $tgl1   = empty($pecah[1]) ? "" : "AND DATE(A.transfer_at)>='$pecah[1]'";
    $tgl2   = empty($pecah[2]) ? "" : "AND DATE(A.transfer_at)<='$pecah[2]'";
    
    $tabel  = '';
    $no     = $mulai;
    
    // Query untuk menghitung total data
    $qJumlah = "SELECT COUNT(*) AS total 
                FROM transfer_stockcancel AS A
                WHERE 1=1 $tgl1 $tgl2
                AND (
                    A.nomor_transfer LIKE '%$search%'
                    OR A.kode_transfer LIKE '%$search%'
                    OR A.kode_faktur LIKE '%$search%'
                )";
    $jumlah = $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);
    
    // Query untuk mengambil data
    $qMaster = "SELECT 
                    A.id_tsc,
                    A.kode_transfer,
                    A.nomor_transfer,
                    A.kode_faktur,
                    A.tgl_faktur,
                    A.id_inventory_tujuan,
                    A.total_item,
                    A.total_qty,
                    A.keterangan_transfer,
                    A.status_transfer,
                    A.transfer_at,
                    A.transfer_by,
                    C.nama_adm AS transfer_by_name
                FROM transfer_stockcancel AS A
                LEFT JOIN adminz AS C ON A.transfer_by = C.id_adm
                WHERE 1=1 $tgl1 $tgl2
                AND (
                    A.nomor_transfer LIKE '%$search%'
                    OR A.kode_transfer LIKE '%$search%'
                    OR A.kode_faktur LIKE '%$search%'
                )
                ORDER BY A.transfer_at DESC
                LIMIT :mulai, :maxi";
    $master = $conn->prepare($qMaster);
    $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();
    
    while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
        $no++;
        
        // Format tanggal
        $tglFaktur = !empty($hasil['tgl_faktur']) ? date('d-m-Y', strtotime($hasil['tgl_faktur'])) : '-';
        $transferAt = !empty($hasil['transfer_at']) ? date('d-m-Y H:i', strtotime($hasil['transfer_at'])) : '-';
        
        // Status badge
        if($hasil['status_transfer'] == 'completed'){
            $statusBadge = '<span class="badge badge-success"><i class="fa fa-check"></i> Completed</span>';
        } else if($hasil['status_transfer'] == 'cancelled'){
            $statusBadge = '<span class="badge badge-danger"><i class="fa fa-times"></i> Cancelled</span>';
        } else {
            $statusBadge = '<span class="badge badge-secondary">' . ucfirst($hasil['status_transfer']) . '</span>';
        }
        
        // Nama inventory
        if($hasil['id_inventory_tujuan'] == 'AUTO'){
            $namaInventory = '<i class="fa fa-warehouse"></i> Gudang Asal Masing-masing';
        } else {
            $namaInventory = $hasil['id_inventory_tujuan'];
        }
        
        // Nomor transfer
        $nomorTransfer = !empty($hasil['nomor_transfer']) ? $hasil['nomor_transfer'] : $hasil['kode_transfer'];
        
        // Tombol detail
        $btnDetail = '<a href="#vmodal" onclick="viewDetail(\''.$hasil['id_tsc'].'\')" data-toggle="modal" title="Lihat Detail"><span class="badge badge-info"><i class="fa fa-eye"></i></span></a>';
        
        $tabel .= '<tr>
            <td><center>'.$no.'</center></td>
            <td><strong>'.$nomorTransfer.'</strong></td>
            <td>'.$hasil['kode_faktur'].'</td>
            <td><center>'.$tglFaktur.'</center></td>
            <td>'.$namaInventory.'</td>
            <td><center>'.$transferAt.'</center></td>
            <td><center><span class="badge badge-primary">'.$data->angka($hasil['total_item']).'</span></center></td>
            <td><center><span class="badge badge-success">'.$data->angka($hasil['total_qty']).'</span></center></td>
            <td><center>'.$statusBadge.'</center></td>
            <td><center>'.$btnDetail.'</center></td>
        </tr>';
    }
    
    // Jika tidak ada data
    if(empty($tabel)){
        $tabel = '<tr><td colspan="10" class="text-center text-muted py-4"><i class="fa fa-inbox fa-2x"></i><br>Tidak ada data transfer</td></tr>';
    }
    
    $navi = $paging->myPaging($menu, $jumlah['total'], $maxi, $page); 
}
$conn = $base->close();
$json = array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
http_response_code(200);
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo(json_encode($json));
?>
