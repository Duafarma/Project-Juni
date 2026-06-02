<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $conn   = $base->open();
    $catat  = date('Y-m-d H:i:s');
    $admin  = $secu->injection(@$_COOKIE['adminkuy']);
    $act    = $secu->injection(@$_POST['namamenu']);
    if(empty($act)){
        $act = $secu->injection(@$_GET['act'] ?: $_POST['nact'] ?: $_GET['nact']);
    }

    $conn->exec("CREATE TABLE IF NOT EXISTS `master_program_produk` (
        `id_pp` int(11) NOT NULL AUTO_INCREMENT,
        `nama_program` varchar(255) NOT NULL DEFAULT '',
        `jenis_program` varchar(255) NOT NULL DEFAULT '',
        `min_qty` int(11) NOT NULL DEFAULT '0',
        `diskon_persen` int(11) NOT NULL DEFAULT '0',
        `status_program` varchar(50) NOT NULL DEFAULT 'Active',
        `created_at` datetime NOT NULL,
        `created_by` varchar(100) NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        `updated_by` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id_pp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $conn->exec("CREATE TABLE IF NOT EXISTS `master_program_produk_detail` (
        `id_ppd` int(11) NOT NULL AUTO_INCREMENT,
        `id_pp` int(11) NOT NULL,
        `id_pro` varchar(100) NOT NULL,
        `harga_program` int(11) NOT NULL DEFAULT '0',
        `created_at` datetime NOT NULL,
        `created_by` varchar(100) NOT NULL,
        PRIMARY KEY (`id_ppd`),
        UNIQUE KEY `master_program_produk_detail_uq` (`id_pp`,`id_pro`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    $conn->exec("CREATE TABLE IF NOT EXISTS `master_program_produk_outlet` (
        `id_ppo` int(11) NOT NULL AUTO_INCREMENT,
        `id_pp` int(11) NOT NULL,
        `id_out` int(11) NOT NULL,
        `created_at` datetime NOT NULL,
        `created_by` varchar(100) NOT NULL,
        PRIMARY KEY (`id_ppo`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    switch($act){
        case "input":
            $nama_program    = $secu->injection($_POST['nama_program']);
            $jenis_program   = $secu->injection($_POST['jenis_program']);
            $min_qty         = intval($_POST['min_qty']);
            $diskon_persen   = intval($_POST['diskon_persen']);
            $rawOutlets      = $_POST['outlet'] ?? [];
            if(is_array($rawOutlets)){
                $outlets = $rawOutlets;
            } else {
                $decoded = json_decode($rawOutlets, true);
                if(is_array($decoded)){
                    $outlets = $decoded;
                } else {
                    $outlets = preg_split('/[\s,;]+/', trim($rawOutlets), -1, PREG_SPLIT_NO_EMPTY);
                }
            }
            $outlets = array_values(array_filter(array_map('trim', (array)$outlets), function($value){ return $value !== ''; }));
            $products = isset($_POST['product']) ? (array)$_POST['product'] : [];
            $harga    = isset($_POST['harga']) ? (array)$_POST['harga'] : [];
            if(empty($nama_program)){
                echo("error|Nama program tidak boleh kosong");
                exit;
            }
            if(empty($products)){
                echo("error|Tambahkan minimal 1 produk");
                exit;
            }
            if(empty($outlets)){
                echo("error|Pilih minimal 1 outlet");
                exit;
            }
            $cek = $conn->prepare("SELECT id_pp FROM master_program_produk WHERE nama_program=:nama");
            $cek->bindParam(":nama", $nama_program, PDO::PARAM_STR);
            $cek->execute();
            if($cek->rowCount() > 0){
                echo("duplicate");
                exit;
            }
            $save = $conn->prepare("INSERT INTO master_program_produk (nama_program, jenis_program, min_qty, diskon_persen, status_program, created_at, created_by) VALUES(:nama, :jenis_program, :min_qty, :diskon_persen, 'Active', :catat, :admin)");
            $save->bindParam(":nama", $nama_program, PDO::PARAM_STR);
            $save->bindParam(":jenis_program", $jenis_program, PDO::PARAM_STR);
            $save->bindParam(":min_qty", $min_qty, PDO::PARAM_INT);
            $save->bindParam(":diskon_persen", $diskon_persen, PDO::PARAM_INT);
            $save->bindParam(":catat", $catat, PDO::PARAM_STR);
            $save->bindParam(":admin", $admin, PDO::PARAM_STR);
            $save->execute();
            $id_pp = $conn->lastInsertId();
            $saveDetail = $conn->prepare("INSERT INTO master_program_produk_detail (id_pp, id_pro, harga_program, created_at, created_by) VALUES(:id_pp, :id_pro, :harga, :catat, :admin)");
            foreach($products as $k => $id_pro){
                $id_pro = $secu->injection($id_pro);
                $hrg    = intval(str_replace('.', '', @$harga[$k]));
                if(empty($id_pro)) continue;
                $saveDetail->bindParam(":id_pp", $id_pp, PDO::PARAM_INT);
                $saveDetail->bindParam(":id_pro", $id_pro, PDO::PARAM_STR);
                $saveDetail->bindParam(":harga", $hrg, PDO::PARAM_INT);
                $saveDetail->bindParam(":catat", $catat, PDO::PARAM_STR);
                $saveDetail->bindParam(":admin", $admin, PDO::PARAM_STR);
                $saveDetail->execute();
            }
            if(!empty($outlets)){
                $saveOutlet = $conn->prepare("INSERT INTO master_program_produk_outlet (id_pp, id_out, created_at, created_by) VALUES(:id_pp, :id_out, :catat, :admin)");
                foreach($outlets as $id_out){
                    $id_out = trim($secu->injection($id_out));
                    if($id_out === '') continue;
                    $saveOutlet->execute([
                        ':id_pp' => $id_pp,
                        ':id_out' => $id_out,
                        ':catat' => $catat,
                        ':admin' => $admin
                    ]);
                }
            }
            $conn->query("INSERT INTO riwayat VALUES('', '$id_pp', 'Master Program Produk', 'Create', '', '$catat', '$admin')");
            echo("master_program_produk?s=1");
        break;
        case "update":
            $id             = intval($_POST['keycode']);
            $nama_program   = $secu->injection($_POST['nama_program']);
            $jenis_program  = $secu->injection($_POST['jenis_program']);
            $min_qty        = intval($_POST['min_qty']);
            $diskon_persen  = intval($_POST['diskon_persen']);
            $rawOutlets     = $_POST['outlet'] ?? [];
            if(is_array($rawOutlets)){
                $outlets = $rawOutlets;
            } else {
                $decoded = json_decode($rawOutlets, true);
                if(is_array($decoded)){
                    $outlets = $decoded;
                } else {
                    $outlets = preg_split('/[\s,;]+/', trim($rawOutlets), -1, PREG_SPLIT_NO_EMPTY);
                }
            }
            $outlets  = array_values(array_filter(array_map('trim', (array)$outlets), function($value){ return $value !== ''; }));
            $products  = isset($_POST['product']) ? (array)$_POST['product'] : [];
            $harga     = isset($_POST['harga']) ? (array)$_POST['harga'] : [];
            if(empty($nama_program)){
                echo("error|Nama program tidak boleh kosong");
                exit;
            }
            if(empty($outlets)){
                echo("error|Pilih minimal 1 outlet");
                exit;
            }
            $cekdup = $conn->prepare("SELECT id_pp FROM master_program_produk WHERE nama_program=:nama AND id_pp!=:id");
            $cekdup->bindParam(":nama", $nama_program, PDO::PARAM_STR);
            $cekdup->bindParam(":id", $id, PDO::PARAM_INT);
            $cekdup->execute();
            if($cekdup->rowCount() > 0){
                echo("duplicate");
                exit;
            }
            $upd = $conn->prepare("UPDATE master_program_produk SET nama_program=:nama, jenis_program=:jenis_program, min_qty=:min_qty, diskon_persen=:diskon_persen, updated_at=:catat, updated_by=:admin WHERE id_pp=:id");
            $upd->bindParam(":nama", $nama_program, PDO::PARAM_STR);
            $upd->bindParam(":jenis_program", $jenis_program, PDO::PARAM_STR);
            $upd->bindParam(":min_qty", $min_qty, PDO::PARAM_INT);
            $upd->bindParam(":diskon_persen", $diskon_persen, PDO::PARAM_INT);
            $upd->bindParam(":catat", $catat, PDO::PARAM_STR);
            $upd->bindParam(":admin", $admin, PDO::PARAM_STR);
            $upd->bindParam(":id", $id, PDO::PARAM_INT);
            $upd->execute();
            $conn->prepare("DELETE FROM master_program_produk_detail WHERE id_pp=:id")->execute([':id' => $id]);
            $conn->prepare("DELETE FROM master_program_produk_outlet WHERE id_pp=:id")->execute([':id' => $id]);
            if(!empty($products)){
                $saveDetail = $conn->prepare("INSERT INTO master_program_produk_detail (id_pp, id_pro, harga_program, created_at, created_by) VALUES(:id_pp, :id_pro, :harga, :catat, :admin)");
                foreach($products as $k => $id_pro){
                    $id_pro = $secu->injection($id_pro);
                    $hrg    = intval(str_replace('.', '', @$harga[$k]));
                    if(empty($id_pro)) continue;
                    $saveDetail->bindParam(":id_pp", $id, PDO::PARAM_INT);
                    $saveDetail->bindParam(":id_pro", $id_pro, PDO::PARAM_STR);
                    $saveDetail->bindParam(":harga", $hrg, PDO::PARAM_INT);
                    $saveDetail->bindParam(":catat", $catat, PDO::PARAM_STR);
                    $saveDetail->bindParam(":admin", $admin, PDO::PARAM_STR);
                    $saveDetail->execute();
                }
            }
            if(!empty($outlets)){
                $saveOutlet = $conn->prepare("INSERT INTO master_program_produk_outlet (id_pp, id_out, created_at, created_by) VALUES(:id_pp, :id_out, :catat, :admin)");
                foreach($outlets as $id_out){
                    $id_out = trim($secu->injection($id_out));
                    if($id_out === '') continue;
                    $saveOutlet->execute([
                        ':id_pp' => $id,
                        ':id_out' => $id_out,
                        ':catat' => $catat,
                        ':admin' => $admin
                    ]);
                }
            }
            $conn->query("INSERT INTO riwayat VALUES('', '$id', 'Master Program Produk', 'Update', '', '$catat', '$admin')");
            echo("master_program_produk?s=1");
        break;
        case "delete":
            $id = intval($_POST['keycode']);
            $conn->prepare("DELETE FROM master_program_produk_detail WHERE id_pp=:id")->execute([':id' => $id]);
            $conn->prepare("DELETE FROM master_program_produk_outlet WHERE id_pp=:id")->execute([':id' => $id]);
            $dele = $conn->prepare("DELETE FROM master_program_produk WHERE id_pp=:id");
            $dele->bindParam(":id", $id, PDO::PARAM_INT);
            $dele->execute();
            $conn->query("INSERT INTO riwayat VALUES('', '$id', 'Master Program Produk', 'Delete', '', '$catat', '$admin')");
            $hasil = ($dele==true) ? "success" : "error";
            echo($hasil);
        break;
    }
?>
