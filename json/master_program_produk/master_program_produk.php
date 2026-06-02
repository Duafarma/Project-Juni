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
    $admin  = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
    $level  = $secu->injection(@$_COOKIE['jeniskuy']);
    $valid  = $secu->validadmin($admin, $kunci);
    $cari   = $secu->injection(@$_GET['caridata']);
    $page   = $secu->injection(@$_GET['halaman']);
    $maxi   = $secu->injection(@$_GET['maximal']);
    $menu   = $secu->injection(@$_GET['menudata']);
    $mulai  = ($page>1) ? (($page * $maxi) - $maxi) : 0;
    if($valid==false){
        $tabel = '<tr><td colspan="6">Session login anda habis...</td></tr>';
        $navi  = '';
    } else {
        $tabel = '';
        $no    = $mulai;
        $jumlah= $conn->query("SELECT COUNT(id_pp) AS total FROM master_program_produk WHERE nama_program LIKE '%$cari%'")->fetch(PDO::FETCH_ASSOC);
        $master= $conn->prepare("SELECT A.id_pp, A.nama_program, A.jenis_program, A.min_qty, A.diskon_persen, COUNT(DISTINCT B.id_ppd) AS jml_produk, COUNT(DISTINCT C.id_ppo) AS jml_outlet FROM master_program_produk AS A LEFT JOIN master_program_produk_detail AS B ON A.id_pp=B.id_pp LEFT JOIN master_program_produk_outlet AS C ON A.id_pp=C.id_pp WHERE A.nama_program LIKE :cari GROUP BY A.id_pp, A.nama_program, A.jenis_program, A.min_qty, A.diskon_persen ORDER BY A.nama_program ASC LIMIT :mulai, :maxi");
        $master->bindValue(':cari', '%'.$cari.'%', PDO::PARAM_STR);
        $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
        $master->execute();
        while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
            $no++;
            $detail = "<a href=\"#modal1\" onclick=\"crud('master_program_produk', 'detail', '".$hasil['id_pp']."')\" data-toggle=\"modal\"><span class=\"badge badge-info\"><i class=\"fa fa-list\"></i></span></a>";
            $edit   = ($data->akses($admin, $menu, 'A.update_status')==='Active') ? ' <a href="'.$data->sistem('url_sis').'/master_program_produk/e/'.$hasil['id_pp'].'"><span class="badge badge-warning"><i class="fa fa-edit"></i></span></a>' : '';
            $delete = ($data->akses($admin, $menu, 'A.delete_status')==='Active') ? "<a href=\"#modal1\" onclick=\"crud('master_program_produk', 'delete', '".$hasil['id_pp']."')\" data-toggle=\"modal\"><span class=\"badge badge-danger\"><i class=\"fa fa-trash\"></i></span></a>" : '';
            $tabel .= '<tr>
                        <td><center>'.$no.'</center></td>
                        <td>'.$hasil['nama_program'].'</td>
                        <td><center><span class="badge badge-pill badge-secondary">'.($hasil['jenis_program'] ?: '-').'</span></center></td>
                        <td><center><span class="badge badge-pill badge-primary">'.$hasil['jml_produk'].' Produk</span></center></td>
                        <td><center><span class="badge badge-pill badge-success">'.$hasil['jml_outlet'].' Outlet</span></center></td>
                        <td><center>'.$detail.$edit.$delete.'</center></td>
                    </tr>';
        }
        $navi   = $paging->myPaging($menu, $jumlah['total'], $maxi, $page);
    }
    $conn   = $base->close();
    $json   = array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo(json_encode($json));
?>
