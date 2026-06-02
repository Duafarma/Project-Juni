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
    $tanggal = date('Y-m-d');

    // ACCESS DATA
    $admin = $secu->injection($_COOKIE['adminkuy'] ?? '');
    $kunci = $secu->injection($_COOKIE['kuncikuy'] ?? '');
    $level = $secu->injection($_COOKIE['jeniskuy'] ?? '');
    $valid = $secu->validadmin($admin, $kunci);

    // POST DATA
    $cari = $secu->injection($_GET['caridata'] ?? '');
    $page = (int)($secu->injection($_GET['halaman'] ?? 1));
    $maxi = (int)($secu->injection($_GET['maximal'] ?? 10));
    $menu = $secu->injection($_GET['menudata'] ?? '');
    $mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;

    // READ DATA
    if (!$valid) {
        $tabel = '<tr><td colspan="11">Session login anda habis...</td></tr>';
        $navi = '';
    } else {
        $pecah = explode('_', $cari);
        $cari = $data->cekcari($pecah[0], '-', ' ');
        $tgl1 = !empty($pecah[1]) ? "AND A.tgl_tfk >= :tgl1" : "";
        $tgl2 = !empty($pecah[2]) ? "AND A.tgl_tfk <= :tgl2" : "";

        // Count total records
        $qjumlah = "SELECT COUNT(A.kode_tfk) AS total 
                    FROM transaksi_faktur AS A 
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out 
                    WHERE (A.kode_tfk LIKE :cari OR B.nama_out LIKE :cari) $tgl1 $tgl2";
        $stmt = $conn->prepare($qjumlah);
        $stmt->bindValue(':cari', "%$cari%", PDO::PARAM_STR);
        if ($tgl1) $stmt->bindValue(':tgl1', $pecah[1], PDO::PARAM_STR);
        if ($tgl2) $stmt->bindValue(':tgl2', $pecah[2], PDO::PARAM_STR);
        $stmt->execute();
        $jumlah = $stmt->fetch(PDO::FETCH_ASSOC);

        // Fetch records
        $qmaster = "SELECT A.id_tfk, A.kode_tfk, A.tgl_tfk, A.status_tfkkb, A.status_tfkkf, 
                           A.status_dokumen, A.status_f_pajak, A.status_failing, A.tgl_limit, 
                           A.status_tfk, B.nama_out 
                    FROM transaksi_faktur AS A 
                    LEFT JOIN outlet AS B ON A.id_out = B.id_out 
                    WHERE (A.kode_tfk LIKE :cari OR B.nama_out LIKE :cari) $tgl1 $tgl2 
                    ORDER BY A.tgl_tfk DESC, CAST(A.sj_tfk AS UNSIGNED) DESC
                    LIMIT :mulai, :maxi";
        $master = $conn->prepare($qmaster);
        $master->bindValue(':cari', "%$cari%", PDO::PARAM_STR);
        if ($tgl1) $master->bindValue(':tgl1', $pecah[1], PDO::PARAM_STR);
        if ($tgl2) $master->bindValue(':tgl2', $pecah[2], PDO::PARAM_STR);
        $master->bindValue(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindValue(':maxi', $maxi, PDO::PARAM_INT);
        $master->execute();

        $tabel = '';
        $no = $mulai;
        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            $no++;
            $status = ($hasil['status_tfk'] === 'Tagihan') ? 'Belum Bayar' : 
                      (($hasil['status_tfk'] === 'Bayar') ? 'Pembayaran Sebagian' : 'Lunas');
            $tabel .= '<tr><td><center>' . $no . '</center></td>
                      <td><center>' . htmlspecialchars($hasil['nama_out']) . '</center></td>
                      <td>' . htmlspecialchars($hasil['kode_tfk']) . '</td>
                      <td><center>' . htmlspecialchars($hasil['tgl_tfk']) . '</center></td>
                      <td>' . htmlspecialchars($hasil['status_tfkkb']) . '</td>
                      <td>' . htmlspecialchars($hasil['status_dokumen']) . '</td>
                      <td>' . htmlspecialchars($hasil['status_failing']) . '</td>
                      <td>' . htmlspecialchars($hasil['status_f_pajak']) . '</td>
                      <td>' . htmlspecialchars($hasil['status_tfkkf']) . '</td>
                      <td>' . htmlspecialchars($status) . '</td>
                      <td>' . htmlspecialchars($hasil['tgl_limit']) . '</td></tr>';
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
