<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    $secu	= new Security;
    $base	= new DB;
    $data	= new Data;
    $conn	= $base->open();
    $catat	= date('Y-m-d H:i:s');
    $admin	= $secu->injection(@$_COOKIE['adminkuy']);
    $kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
    $act	= $secu->injection(@$_GET['act']);
    
    switch($act){
        case "input":
            $kode	        = $data->bcode('MREK', 'id_rek', 'master_rekening');
            $nama_rekening	= $secu->injection($_POST['nama_rekening']);
            $nomor_rekening	= $secu->injection($_POST['nomor_rekening']);
            $atas_nama	= $secu->injection($_POST['atas_nama']);
            $save	= $conn->prepare("INSERT INTO master_rekening (id_rek, nama_rekening, nomor_rekening, atas_nama, created_at, created_by, updated_at, updated_by) VALUES(:kode, :nama_rekening, :nomor_rekening, :atas_nama, :catat, :admin, :catat, :admin)");
            $save->bindParam(":kode", $kode, PDO::PARAM_STR);
            $save->bindParam(":nama_rekening", $nama_rekening, PDO::PARAM_STR);
            $save->bindParam(":nomor_rekening", $nomor_rekening, PDO::PARAM_STR);
            $save->bindParam(":atas_nama", $atas_nama, PDO::PARAM_STR);
            $save->bindParam(":catat", $catat, PDO::PARAM_STR);
            $save->bindParam(":admin", $admin, PDO::PARAM_STR);
            $save->execute();
            //RIWAYAT
            $riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'master_rekening', 'Create', '', '$catat', '$admin')");
            $hasil	= ($save==true) ? "success" : "error";
            echo($hasil);
        break;
        
        case "update":
            $kode	        = $secu->injection($_POST['keycode']);
            $nama_rekening	= $secu->injection($_POST['nama_rekening']);
            $nomor_rekening	= $secu->injection($_POST['nomor_rekening']);
            $atas_nama	= $secu->injection($_POST['atas_nama']);
            $edit	= $conn->prepare("UPDATE master_rekening SET nama_rekening=:nama_rekening, nomor_rekening=:nomor_rekening, atas_nama=:atas_nama, updated_at=:catat, updated_by=:admin WHERE id_rek=:kode");
            $edit->bindParam(":kode", $kode, PDO::PARAM_STR);
            $edit->bindParam(":nama_rekening", $nama_rekening, PDO::PARAM_STR);
            $edit->bindParam(":nomor_rekening", $nomor_rekening, PDO::PARAM_STR);
            $edit->bindParam(":atas_nama", $atas_nama, PDO::PARAM_STR);
            $edit->bindParam(":catat", $catat, PDO::PARAM_STR);
            $edit->bindParam(":admin", $admin, PDO::PARAM_STR);
            $edit->execute();
            //RIWAYAT
            $riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'master_rekening', 'Update', '', '$catat', '$admin')");
            $hasil	= ($edit==true) ? "success" : "error";
            echo($hasil);
        break;
        
        case "delete":
            $kode	= $secu->injection($_POST['keycode']);
            $dele	= $conn->prepare("DELETE FROM master_rekening WHERE id_rek=:kode");
            $dele->bindParam(":kode", $kode, PDO::PARAM_STR);
            $dele->execute();
            //RIWAYAT
            $riwayat= $conn->query("INSERT INTO riwayat VALUES('', '$kode', 'master_rekening', 'Delete', '', '$catat', '$admin')");
            $hasil	= ($dele==true) ? "success" : "error";
            echo($hasil);
        break;

        // MODIFIED: proses assign/update dengan mode outlet atau grup
        case "outlet":
            $id_rek = $secu->injection($_POST['id_rek']);
            $assignment_mode = $secu->injection($_POST['assignment_mode']);
            
            try {
                $conn->beginTransaction();
                
                if($assignment_mode === 'grup'){
                    // Mode grup: update semua outlet berdasarkan id_mg
                    $id_mg = $secu->injection($_POST['id_mg']);
                    
                    // ambil semua id_out berdasarkan id_mg
                    $getOutlets = $conn->prepare("SELECT id_out FROM outlet WHERE id_mg = :id_mg");
                    $getOutlets->bindParam(":id_mg", $id_mg, PDO::PARAM_STR);
                    $getOutlets->execute();
                    
                    $affectedOutlets = [];
                    while($outlet = $getOutlets->fetch(PDO::FETCH_ASSOC)){
                        $id_out = $outlet['id_out'];
                        $affectedOutlets[] = $id_out;
                        
                        // cek apakah mapping sudah ada untuk outlet ini
                        $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM outlet_rekening WHERE id_out = :id_out");
                        $check->bindParam(":id_out", $id_out, PDO::PARAM_STR);
                        $check->execute();
                        $cnt = $check->fetch(PDO::FETCH_ASSOC);
                        
                        if($cnt && $cnt['cnt'] > 0){
                            // update
                            $upd = $conn->prepare("UPDATE outlet_rekening SET id_rek = :id_rek, updated_at = :catat, updated_by = :admin WHERE id_out = :id_out");
                            $upd->bindParam(":id_rek", $id_rek, PDO::PARAM_STR);
                            $upd->bindParam(":catat", $catat, PDO::PARAM_STR);
                            $upd->bindParam(":admin", $admin, PDO::PARAM_STR);
                            $upd->bindParam(":id_out", $id_out, PDO::PARAM_STR);
                            $upd->execute();
                        } else {
                            // insert
                            $ins = $conn->prepare("INSERT INTO outlet_rekening (id_out, id_rek, created_at, created_by) VALUES(:id_out, :id_rek, :catat, :admin)");
                            $ins->bindParam(":id_out", $id_out, PDO::PARAM_STR);
                            $ins->bindParam(":id_rek", $id_rek, PDO::PARAM_STR);
                            $ins->bindParam(":catat", $catat, PDO::PARAM_STR);
                            $ins->bindParam(":admin", $admin, PDO::PARAM_STR);
                            $ins->execute();
                        }
                        
                        // riwayat per outlet
                        $riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id_out', 'outlet_rekening', 'Create/Update via Grup', 'id_mg: $id_mg', '$catat', '$admin')");
                    }
                    
                    $conn->commit();
                    $hasil = "success";
                    
                } else {
                    // Mode outlet: single outlet
                    $id_out = $secu->injection($_POST['id_out']);
                    
                    // cek apakah mapping sudah ada
                    $check = $conn->prepare("SELECT COUNT(*) AS cnt FROM outlet_rekening WHERE id_out = :id_out");
                    $check->bindParam(":id_out", $id_out, PDO::PARAM_STR);
                    $check->execute();
                    $cnt = $check->fetch(PDO::FETCH_ASSOC);
                    
                    if($cnt && $cnt['cnt'] > 0){
                        $upd = $conn->prepare("UPDATE outlet_rekening SET id_rek = :id_rek, updated_at = :catat, updated_by = :admin WHERE id_out = :id_out");
                        $upd->bindParam(":id_rek", $id_rek, PDO::PARAM_STR);
                        $upd->bindParam(":catat", $catat, PDO::PARAM_STR);
                        $upd->bindParam(":admin", $admin, PDO::PARAM_STR);
                        $upd->bindParam(":id_out", $id_out, PDO::PARAM_STR);
                        $upd->execute();
                        $riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id_out', 'outlet_rekening', 'Update', '', '$catat', '$admin')");
                        $hasil = ($upd==true) ? "success" : "error";
                    } else {
                        $ins = $conn->prepare("INSERT INTO outlet_rekening (id_out, id_rek, created_at, created_by) VALUES(:id_out, :id_rek, :catat, :admin)");
                        $ins->bindParam(":id_out", $id_out, PDO::PARAM_STR);
                        $ins->bindParam(":id_rek", $id_rek, PDO::PARAM_STR);
                        $ins->bindParam(":catat", $catat, PDO::PARAM_STR);
                        $ins->bindParam(":admin", $admin, PDO::PARAM_STR);
                        $ins->execute();
                        $riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id_out', 'outlet_rekening', 'Create', '', '$catat', '$admin')");
                        $hasil = ($ins==true) ? "success" : "error";
                    }
                    
                    $conn->commit();
                }
                
            } catch(Exception $e) {
                $conn->rollback();
                $hasil = "error: " . $e->getMessage();
            }
            
            echo($hasil);
        break;

        // NEW: delete mapping outlet_rekening
        case "delete_outlet":
            $id_out = $secu->injection($_POST['id_out']);
            $dele = $conn->prepare("DELETE FROM outlet_rekening WHERE id_out = :id_out");
            $dele->bindParam(":id_out", $id_out, PDO::PARAM_STR);
            $dele->execute();
            $riwayat = $conn->query("INSERT INTO riwayat VALUES('', '$id_out', 'outlet_rekening', 'Delete', '', '$catat', '$admin')");
            $hasil = ($dele==true) ? "success" : "error";
            echo($hasil);
        break;

        // NEW: list outlet_rekening (return JSON: rows + pagination)
        case "list_outlet":
            $page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            if($page < 1) $page = 1;
            if($limit < 1) $limit = 10;
            if($limit > 100) $limit = 100;
            $offset = ($page - 1) * $limit;

            // ambil parameter pencarian tunggal (nama atau id)
            $q = isset($_GET['q']) ? $secu->injection($_GET['q']) : '';

            // build where dinamis: jika q ada, cari di nama_out atau id_out (partial)
            $where = " WHERE 1=1 ";
            $params = [];
            if($q !== ''){
                $where .= " AND (o.nama_out LIKE :q OR orr.id_out LIKE :q) ";
                $params[':q'] = "%$q%";
            }

            // total data dengan filter
            $countSql = "
                SELECT COUNT(*) AS total
                FROM outlet_rekening orr
                LEFT JOIN outlet o ON BINARY orr.id_out = BINARY o.id_out
                LEFT JOIN master_grup mg ON BINARY o.id_mg = BINARY mg.id_mg
            " . $where;
            $countStmt = $conn->prepare($countSql);
            foreach($params as $k=>$v){ $countStmt->bindValue($k, $v, PDO::PARAM_STR); }
            $countStmt->execute();
            $totalRow = $countStmt->fetch(PDO::FETCH_ASSOC);
            $total = (int)($totalRow['total'] ?? 0);
            $totalPages = ($total > 0) ? (int)ceil($total / $limit) : 1;

             // data halaman dengan filter - MODIFIED: tambah id_mg dan nama_mg
             $listSql = "
                 SELECT orr.id_out, orr.id_rek,
                        COALESCE(o.nama_out, orr.id_out) AS nama_out,
                        o.id_mg,
                        mg.nama_mg,
                        mr.nama_rekening,
                        mr.nomor_rekening,
                        mr.atas_nama
                 FROM outlet_rekening orr
                 LEFT JOIN outlet o ON BINARY orr.id_out = BINARY o.id_out
                 LEFT JOIN master_grup mg ON BINARY o.id_mg = BINARY mg.id_mg
                 LEFT JOIN master_rekening mr ON BINARY orr.id_rek = BINARY mr.id_rek
             " . $where . "
                 ORDER BY (o.nama_out IS NULL), o.nama_out ASC, orr.id_out ASC
                 LIMIT :offset, :limit
             ";
             $list = $conn->prepare($listSql);
            foreach($params as $k=>$v){ $list->bindValue($k, $v, PDO::PARAM_STR); }
             $list->bindParam(':offset', $offset, PDO::PARAM_INT);
             $list->bindParam(':limit', $limit, PDO::PARAM_INT);
             $list->execute();

            $rows = '';
            $no = $offset;
            while($l = $list->fetch(PDO::FETCH_ASSOC)){
                $no++;
                $id_out_h      = htmlspecialchars($l['id_out'] ?? '');
                $id_rek_h      = htmlspecialchars($l['id_rek'] ?? '');
                $nama_out_h    = htmlspecialchars($l['nama_out'] ?? '');
                $id_mg_h       = htmlspecialchars($l['id_mg'] ?? '');
                $nama_mg_h     = htmlspecialchars($l['nama_mg'] ?? '');
                $nama_bank_h   = htmlspecialchars($l['nama_rekening'] ?? '');
                $nomor_rek_h   = htmlspecialchars($l['nomor_rekening'] ?? '');
                $atas_nama_h   = htmlspecialchars($l['atas_nama'] ?? '');

                // Format tampilan grup
                $grup_display = '';
                if($id_mg_h && $nama_mg_h) {
                    $grup_display = " <small class='text-info'>[{$nama_mg_h}]</small>";
                }

                $rows .= "<tr>";
                $rows .= "<td><center>{$no}</center></td>";
                $rows .= "<td>{$nama_out_h} <small class='text-muted'>({$id_out_h})</small>{$grup_display}</td>";
                $rows .= "<td>{$nama_bank_h} <small class='text-muted'>({$id_rek_h})</small></td>";
                $rows .= "<td>{$nomor_rek_h}</td>";
                $rows .= "<td>{$atas_nama_h}</td>";
                // Action: Edit + Delete (sesuai style masterrekening)
                $rows .= "<td><center>
                            <a href=\"#\" onclick=\"editOutlet('{$id_out_h}','{$id_rek_h}')\">
                                <span class='badge badge-info'><i class='fa fa-edit'></i></span>
                            </a>
                            <a href=\"#\" onclick=\"deleteOutlet('{$id_out_h}')\">
                                <span class='badge badge-danger'><i class='fa fa-trash'></i></span>
                            </a>
                          </center></td>";
                $rows .= "</tr>";
            }

            // pagination markup (windowed)
            $pagination = '';
            if($total > 0){
                $pagination .= buildPagination($page, $totalPages);
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'rows' => $rows,
                'pagination' => $pagination,
                'page' => $page,
                'total' => $total,
                'limit' => $limit
            ]);
        break;
    }

    // helper untuk paginasi sederhana
    function buildPagination($page, $totalPages){
        $html = '';
        $prevDisabled = ($page <= 1) ? ' disabled' : '';
        $nextDisabled = ($page >= $totalPages) ? ' disabled' : '';

        $html .= '<li class="page-item'.$prevDisabled.'"><a class="page-link" href="#" data-page="'.max(1, $page-1).'">&laquo;</a></li>';

        // window 5 halaman
        $window = 5;
        $start = max(1, $page - floor($window/2));
        $end = min($totalPages, $start + $window - 1);
        if(($end - $start + 1) < $window){
            $start = max(1, $end - $window + 1);
        }

        if($start > 1){
            $html .= '<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>';
            if($start > 2){
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for($i=$start; $i<=$end; $i++){
            $active = ($i == $page) ? ' active' : '';
            $html .= '<li class="page-item'.$active.'"><a class="page-link" href="#" data-page="'.$i.'">'.$i.'</a></li>';
        }

        if($end < $totalPages){
            if($end < $totalPages - 1){
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="#" data-page="'.$totalPages.'">'.$totalPages.'</a></li>';
        }

        $html .= '<li class="page-item'.$nextDisabled.'"><a class="page-link" href="#" data-page="'.min($totalPages, $page+1).'">&raquo;</a></li>';
        return $html;
    }
?>
