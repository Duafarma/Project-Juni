<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    require_once('../../config/function/paging.php');
    $base	= new DB;
    $secu	= new Security;
    $data	= new Data;
    $paging	= new Paging;
    $conn	= $base->open();
    $sistem	= $data->sistem('url_sis');
    //ACCESS DATA
    $admin	= $secu->injection(@$_COOKIE['adminkuy']);
    $kunci	= $secu->injection(@$_COOKIE['kuncikuy']);
    $level	= $secu->injection(@$_COOKIE['jeniskuy']);
    $valid	= $secu->validadmin($admin, $kunci);
    //POST DATA
    $cari	= $secu->injection(@$_GET['caridata']);
    $page	= $secu->injection(@$_GET['halaman']);
    $maxi	= $secu->injection(@$_GET['maximal']);
    $menu	= $secu->injection(@$_GET['menudata']);
    $mulai	= ($page>1) ? (($page * $maxi) - $maxi) : 0;

    //READ DATA
    if($valid==false){
        $tabel	= '<tr><td colspan="8">Session login anda habis...</td></tr>';
        $navi	= '';
    } else {
        $pecah	= explode('_', $cari);
        $cari	= $data->cekcari($pecah[0], '-', ' ');

        $tgl1	= empty($pecah[1]) ? "" : "AND A.created_at>='$pecah[1]'";
        $tgl2	= empty($pecah[2]) ? "" : "AND A.created_at<='$pecah[2]'";

        $tabel	= '';
        $no		= $mulai;

        $statusFilterSql = "LOWER(TRIM(COALESCE(A.status_limit,''))) IN ('limit','approve','kuning')";
        $approveHistoryFilterSql = "(
            LOWER(TRIM(COALESCE(A.status_limit,''))) <> 'approve'
            OR EXISTS (
                SELECT 1
                FROM `limit` AS LF
                WHERE LF.id_tfk = A.id_tfk
            )
            OR EXISTS (
                SELECT 1
                FROM limit_kuning AS LK
                WHERE LK.id_tfk = A.id_tfk
            )
        )";

        $qJumlah = "SELECT COUNT(*) AS total
                    FROM (
                        SELECT A.id_tfk
                        FROM transaksi_faktur AS A
                        LEFT JOIN outlet AS B ON A.id_out = B.id_out
                        WHERE (
                                A.created_at LIKE '%$cari%'
                             OR A.kode_tfk   LIKE '%$cari%'
                             OR A.id_tfk     LIKE '%$cari%'
                             OR B.nama_out   LIKE '%$cari%'
                                                ) $tgl1 $tgl2
                                                    AND $statusFilterSql
                                                    AND $approveHistoryFilterSql

                        UNION ALL

                        SELECT A.id_tfk
                        FROM transaksi_faktur_pim AS A
                        LEFT JOIN outlet AS B ON A.id_out = B.id_out
                        WHERE (
                                A.created_at LIKE '%$cari%'
                             OR A.kode_tfk   LIKE '%$cari%'
                             OR A.id_tfk     LIKE '%$cari%'
                             OR B.nama_out   LIKE '%$cari%'
                                                ) $tgl1 $tgl2
                                                    AND $statusFilterSql
                                                    AND $approveHistoryFilterSql
                    ) AS combined_data";
        $jumlah	= $conn->query($qJumlah)->fetch(PDO::FETCH_ASSOC);

        /**
         * Ambil LIMIT TERBARU per faktur (berdasarkan id_limit terbesar),
         * sekalian created_by & tgl_limit untuk ditampilkan sebagai Approval.
         */
        $limitLatestSql = "
            SELECT
                ld.no_faktur,
                SUBSTRING_INDEX(GROUP_CONCAT(l.id_limit    ORDER BY l.id_limit DESC), ',', 1) AS id_limit_last,
                SUBSTRING_INDEX(GROUP_CONCAT(l.tgl_limit   ORDER BY l.id_limit DESC), ',', 1) AS tgl_limit,
                SUBSTRING_INDEX(GROUP_CONCAT(l.created_by  ORDER BY l.id_limit DESC), ',', 1) AS created_by_limit
            FROM limit_detail ld
            INNER JOIN `limit` l ON l.id_limit = ld.id_limit
            GROUP BY ld.no_faktur
        ";

        // Ambil LIMIT_KUNING TERBARU per faktur (jika ada)
        $limitKuningSql = "
            SELECT
                lmd.no_faktur,
                SUBSTRING_INDEX(GROUP_CONCAT(lm.id_kuning ORDER BY lm.id_kuning DESC), ',', 1) AS id_kuning_last,
                SUBSTRING_INDEX(GROUP_CONCAT(lm.tgl_kuning       ORDER BY lm.id_kuning DESC), ',', 1) AS tgl_kuning,
                SUBSTRING_INDEX(GROUP_CONCAT(lm.created_by     ORDER BY lm.id_kuning DESC), ',', 1) AS created_by_kuning
            FROM limit_kuning_detail lmd
            INNER JOIN limit_kuning lm ON lm.id_kuning = lmd.id_kuning
            GROUP BY lmd.no_faktur
        ";

        $qMaster = "SELECT
                X.id_tfk,
                X.kode_tfk,
                X.created_at,
                X.tgl_tfk,
                X.total_tfk,
                X.status_limit,
                X.nama_out,
                X.limit_outlet,
                X.status_pembayaran,
                X.sumber,
                X.approval_nama,
                X.tgl_approve,
                X.jenis_approve
            FROM (
                SELECT
                    CONVERT(A.id_tfk USING utf8mb4) AS id_tfk,
                    CONVERT(A.kode_tfk USING utf8mb4) AS kode_tfk,
                    A.created_at,
                    A.tgl_tfk,
                    A.total_tfk,
                    CONVERT(A.status_limit USING utf8mb4) AS status_limit,
                    CONVERT(B.nama_out USING utf8mb4) AS nama_out,
                    CONVERT(B.status_pembayaran USING utf8mb4) AS status_pembayaran,
                    CAST(REPLACE(REPLACE(COALESCE(B.`platform`, '0'), '.', ''), ',', '') AS UNSIGNED) AS limit_outlet,
                    CONVERT('Cendo' USING utf8mb4) AS sumber,
                    -- jika ada record limit_kuning → ambil nama dari adminz via id_adm kuning, fallback ke id limit biasa
                    CONVERT(CASE WHEN M.tgl_kuning IS NOT NULL
                         THEN COALESCE(ADM_M.nama_adm, M.created_by_kuning)
                         ELSE COALESCE(AD1.nama_adm, L.created_by_limit)
                    END USING utf8mb4) AS approval_nama,
                    COALESCE(M.tgl_kuning, L.tgl_limit) AS tgl_approve,
                    CONVERT(CASE WHEN M.tgl_kuning IS NOT NULL THEN 'kuning' ELSE 'limit' END USING utf8mb4) AS jenis_approve
                FROM transaksi_faktur AS A
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                LEFT JOIN ($limitLatestSql) AS L ON L.no_faktur = A.id_tfk
                LEFT JOIN adminz AS AD1 ON AD1.id_adm = L.created_by_limit
                LEFT JOIN ($limitKuningSql) AS M ON M.no_faktur = A.id_tfk
                LEFT JOIN adminz AS ADM_M ON ADM_M.id_adm = M.created_by_kuning
                WHERE (
                        A.created_at LIKE '%$cari%'
                     OR A.kode_tfk   LIKE '%$cari%'
                     OR A.id_tfk     LIKE '%$cari%'
                     OR B.nama_out   LIKE '%$cari%'
                                ) $tgl1 $tgl2
                                    AND $statusFilterSql
                                    AND $approveHistoryFilterSql

                UNION ALL

                SELECT
                    CONVERT(A.id_tfk USING utf8mb4) AS id_tfk,
                    CONVERT(A.kode_tfk USING utf8mb4) AS kode_tfk,
                    A.created_at,
                    A.tgl_tfk,
                    A.total_tfk,
                    CONVERT(A.status_limit USING utf8mb4) AS status_limit,
                    CONVERT(B.nama_out USING utf8mb4) AS nama_out,
                    CONVERT(B.status_pembayaran USING utf8mb4) AS status_pembayaran,
                    CAST(REPLACE(REPLACE(COALESCE(B.`platform`, '0'), '.', ''), ',', '') AS UNSIGNED) AS limit_outlet,
                    CONVERT('PIM' USING utf8mb4) AS sumber,
                    CONVERT(CASE WHEN M2.tgl_kuning IS NOT NULL
                         THEN COALESCE(ADM_M2.nama_adm, M2.created_by_kuning)
                         ELSE COALESCE(AD2.nama_adm, L2.created_by_limit)
                    END USING utf8mb4) AS approval_nama,
                    COALESCE(M2.tgl_kuning, L2.tgl_limit) AS tgl_approve,
                    CONVERT(CASE WHEN M2.tgl_kuning IS NOT NULL THEN 'kuning' ELSE 'limit' END USING utf8mb4) AS jenis_approve
                FROM transaksi_faktur_pim AS A
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                LEFT JOIN ($limitLatestSql) AS L2 ON L2.no_faktur = A.id_tfk
                LEFT JOIN adminz AS AD2 ON AD2.id_adm = L2.created_by_limit
                LEFT JOIN ($limitKuningSql) AS M2 ON M2.no_faktur = A.id_tfk
                LEFT JOIN adminz AS ADM_M2 ON ADM_M2.id_adm = M2.created_by_kuning
                WHERE (
                        A.created_at LIKE '%$cari%'
                     OR A.kode_tfk   LIKE '%$cari%'
                     OR A.id_tfk     LIKE '%$cari%'
                     OR B.nama_out   LIKE '%$cari%'
                                ) $tgl1 $tgl2
                                    AND $statusFilterSql
                                    AND $approveHistoryFilterSql
            ) AS X
            ORDER BY
                CASE
                    WHEN LOWER(TRIM(COALESCE(X.status_limit, ''))) = 'limit'  THEN 0
                    WHEN LOWER(TRIM(COALESCE(X.status_limit, ''))) = 'kuning' THEN 1
                    ELSE 2
                END ASC,
                X.created_at DESC
            LIMIT :mulai, :maxi";

        $master	= $conn->prepare($qMaster);
        $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
        $master->execute();

        while($hasil= $master->fetch(PDO::FETCH_ASSOC)){
            $no++;

            $statusLower = strtolower(trim((string)($hasil['status_limit'] ?? '')));

            // KIRIM NOMOR FAKTUR (kode_tfk) ke frontend, bukan id_tfk
            // Backend approve sudah handle pencarian by id_tfk OR kode_tfk
            $kodeForApprove = trim((string)($hasil['kode_tfk'] ?? ''));
            if ($kodeForApprove === '') {
                $kodeForApprove = trim((string)($hasil['id_tfk'] ?? ''));
            }

            $val = addslashes($hasil['sumber'].'|'.$kodeForApprove);

            // Tambahkan badge status_limit setelah nomor faktur
            $outletStatus = strtolower(trim((string)($hasil['status_pembayaran'] ?? '')));
            $status_text = trim((string)($hasil['status_limit'] ?? ''));
            $jenisApprove = strtolower(trim((string)($hasil['jenis_approve'] ?? 'limit')));

            // Tentukan status tampilan: 'kuning' atau 'limit' (meskipun status sebenarnya sudah 'approve')
            $displayStatus = 'approve';
            if ($statusLower === 'kuning' || ($statusLower === 'approve' && $jenisApprove === 'kuning')) {
                $displayStatus = 'kuning';
            } elseif ($statusLower === 'limit' || ($statusLower === 'approve' && $jenisApprove === 'limit')) {
                $displayStatus = 'limit';
            }

            $statusBadge = '';
            if ($displayStatus === 'kuning') {
                $statusBadge = ' <span class="badge badge-warning">Kuning</span>';
            } elseif ($displayStatus === 'limit') {
                $statusBadge = ' <span class="badge badge-info">Limit</span>';
            } else {
                $statusBadge = ' <span class="badge badge-success">Approved</span>';
            }

            $statusView = '<div class="text-center">-</div>';
            if($statusLower === 'approve'){
                $statusView = '<div class="text-center"><button type="button" class="btn btn-success btn-sm rounded-circle" disabled title="Status = Approved" aria-label="Status = Approved" style="width:32px;height:32px;line-height:30px;padding:0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;"><i class="fa fa-check" aria-hidden="true" style="font-size:12px;color:#fff;"></i><span class="sr-only">Approved</span></button></div>';
            } elseif($statusLower === 'limit'){
                $statusView = '<div class="text-center">' .
                    '<button type="button" class="btn btn-danger btn-sm rounded-circle wave-btn pulse" title="Limit" onclick="flimitApproveLimit(\''.$val.'\')" aria-label="Limit" style="width:34px;height:34px;line-height:32px;padding:0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background-image: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.06), transparent), linear-gradient(135deg,#e74c3c,#c0392b);box-shadow: 0 5px 10px rgba(192,57,43,0.16), 0 0 0 2px rgba(224,83,70,0.06) inset;">' .
                    '<i class="fa fa-times" aria-hidden="true" style="color:#fff;font-size:13px;"></i>' .
                    '<span class="sr-only">Limit</span></button></div>';
            } elseif($statusLower === 'kuning'){
                // Tombol 'kuning' dengan styling kuning
                $statusView = '<div class="text-center">' .
                    '<button type="button" class="btn btn-warning btn-sm rounded-circle wave-btn pulse" title="Kuning" onclick="flimitHandleKuning(\''.$val.'\')" aria-label="Limit" style="width:34px;height:34px;line-height:32px;padding:0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background-image: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.06), transparent), linear-gradient(135deg,#e74c3c,#c0392b);box-shadow: 0 5px 10px rgba(192,57,43,0.16), 0 0 0 2px rgba(224,83,70,0.06) inset;">' .
                    '<i class="fa fa-times" aria-hidden="true" style="color:#fff;font-size:13px;"></i>' .
                    '<span class="sr-only">Kuning</span></button></div>';
            }

            $totalView = number_format((float)($hasil['total_tfk'] ?? 0), 0, ',', '.');
            // Tampilkan limit atau status kuning berdasarkan status tampilan (tetap menampilkan Limit/Kuning meskipun status sebenarnya 'approve')
            if ($displayStatus === 'kuning') {
                $limitView = '<div>Status Kuning</div>';
            } else {
                $limitView = 'Rp. ' . number_format((float)($hasil['limit_outlet'] ?? 0), 0, ',', '.');
            }

            $approvalNama = trim((string)($hasil['approval_nama'] ?? ''));
            $approvalNamaView = ($approvalNama !== '' ? htmlspecialchars($approvalNama, ENT_QUOTES) : '-');

            // ACTION cell: buka laporan XPS detail untuk baris ini (dari sebelumnya PDF)
            $xpsUrl = $sistem . '/laporan/xps/flimit/flimit.php?id=' . urlencode($hasil['id_tfk']) . '&sumber=' . urlencode($hasil['sumber']);
            $actionCell = '<div class="text-center"><a target="_blank" href="'.$xpsUrl.'"><button type="button" class="btn btn-success btn-xs" title="Detail / XPS" style="padding:4px 8px;font-size:11px;height:28px;line-height:16px;"><i class="fa fa-print"></i> Detail</button></a></div>';

            // NEW: bikin kolom approval bisa diklik (khusus status=limit)
            $approvalCellView = '<span class="text-muted">'.$approvalNamaView.'</span>';
            if ($statusLower === 'limit') {
                $approvalCellView =
                    '<button type="button" class="btn btn-link p-0" '.
                    'onclick="flimitApproveLimit(\''.$val.'\')" '.
                    'title="Klik untuk approve" style="text-decoration:none;">'.
                        $approvalNamaView.
                    '</button>';
            }

            $tabel	.= '<tr>
                            <td><center>'.$no.'</center></td>
                            <td class="text-center">'.$statusView.'</td>
                            <td>'.htmlspecialchars($hasil['kode_tfk'], ENT_QUOTES).' ('.htmlspecialchars($hasil['sumber'], ENT_QUOTES).')'.$statusBadge.'</td>
                            <td>'.$hasil['nama_out'].'</td>
                            <td>'.$hasil['tgl_tfk'].'</td>
                            <td class="text-right">'.$limitView.'</td>
                            <td class="text-right">Rp. '.$totalView.'</td>
                            <td class="text-center">'.$approvalCellView.'</td>
							<td class="text-center">'.$actionCell.'</td>
                     </tr>';
        }

        $navi	= $paging->myPaging($menu, (int)$jumlah['total'], (int)$maxi, (int)$page);
    }

$conn	= $base->close();
$json	= array("tabel" => $tabel, "halaman" => $page, "paginasi" => $navi);
http_response_code(200);
header('Access-Control-Allow-Origin: *');
header("Content-type: application/json; charset=utf-8");
echo(json_encode($json));
?>
