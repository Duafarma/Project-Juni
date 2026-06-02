<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
} else {
    require_once('../config/connection/connection.php');
    require_once('../config/connection/security.php');
    require_once('../config/function/data.php');
    require_once('../config/function/paging.php');
    $secu = new Security;
    $base = new DB;
    $data = new Data;
    $paging = new Paging;
    $conn = $base->open();

    // Validate encryption key
    $encrypt = $secu->injection($_GET['encrypt'] ?? '');
    $tgl = date('Y-m-d');
    $source = $data->self_apl();
    $sourceKey = $source['key_apl'];
    
    if (md5($tgl . "#" . $sourceKey) != $encrypt) {
        http_response_code(401);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Unauthorized access",
            "data" => [],
            "total" => 0
        ]);
        exit;
    }

    // Validasi parameter
    $cari = $secu->injection($_GET['caridata'] ?? '');
    $cari_cabang = $secu->injection($_GET['cari_cabang'] ?? '');
    $id_out = $secu->injection($_GET['id_out'] ?? ''); 
    $page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
    $maxi = isset($_GET['maximal']) ? (int)$_GET['maximal'] : 15;
    $menu = $secu->injection($_GET['menudata'] ?? '');
    $mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;
    
    // Ambil parameter periode
    $periode_dari = $secu->injection($_GET['periode_dari'] ?? '');
    $periode_sampai = $secu->injection($_GET['periode_sampai'] ?? '');

    try {
        // Modified approach: Use UNION to combine data from both tables
        $whereCendo = "(
            A.kode_tfk LIKE :cari OR
            A.sj_tfk LIKE :cari OR
            A.po_tfk LIKE :cari OR
            B.nama_out LIKE :cari
        ) AND A.status_tfk NOT IN ('Cancel','Draft')"; // <<< exclude Draft
        
        $wherePIM = "(
            P.kode_tfk LIKE :cari OR
            P.sj_tfk LIKE :cari OR
            P.po_tfk LIKE :cari OR
            B_P.nama_out LIKE :cari
        ) AND P.status_tfk NOT IN ('Cancel','Draft')"; // <<< exclude Draft
        
        // Add filters for id_out if provided
        if (!empty($id_out) && $id_out !== 'All') {
            $whereCendo .= " AND A.id_out = :id_out";
            $wherePIM .= " AND P.id_out = :id_out";
        }

        // Add cabang-specific search if parameter is provided
        if (!empty($cari_cabang)) {
            $whereCendo .= " AND (B.nama_out LIKE :cari_cabang OR A.kode_tfk LIKE :cari_cabang)";
            $wherePIM .= " AND (B_P.nama_out LIKE :cari_cabang OR P.kode_tfk LIKE :cari_cabang)";
        }
        
        // Add period filters
        if (!empty($periode_dari)) {
            $whereCendo .= " AND A.tgl_tfk >= :periode_dari";
            $wherePIM .= " AND P.tgl_tfk >= :periode_dari";
        }
        if (!empty($periode_sampai)) {
            $whereCendo .= " AND A.tgl_tfk <= :periode_sampai";
            $wherePIM .= " AND P.tgl_tfk <= :periode_sampai";
        }
        
        // Count total records (Cendo & DPE + PIM)
        $qJumlah = "
            SELECT COUNT(*) as total FROM (
                SELECT A.id_tfk FROM transaksi_faktur AS A
                LEFT JOIN outlet AS B ON A.id_out = B.id_out
                LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
                LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb
                WHERE $whereCendo
                
                UNION ALL
                
                SELECT P.id_tfk FROM transaksi_faktur_pim AS P
                LEFT JOIN outlet AS B_P ON P.id_out = B_P.id_out
                LEFT JOIN outlet_alamat AS C_P ON P.id_out = C_P.id_out
                LEFT JOIN regional_kabupaten AS D_P ON C_P.id_rkb = D_P.id_rkb
                WHERE $wherePIM
            ) AS combined";
        
        $stmtJumlah = $conn->prepare($qJumlah);
        $stmtJumlah->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
        
        if (!empty($id_out) && $id_out !== 'All') {
            $stmtJumlah->bindValue(':id_out', $id_out, PDO::PARAM_STR);
        }
        
        if (!empty($cari_cabang)) {
            $stmtJumlah->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
        }
        
        if (!empty($periode_dari)) {
            $stmtJumlah->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
        }
        if (!empty($periode_sampai)) {
            $stmtJumlah->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
        }
        
        $stmtJumlah->execute();
        $jumlah = $stmtJumlah->fetch(PDO::FETCH_ASSOC);
        $total = $jumlah['total'] ?? 0;
        
        // Fetch data with UNION query
        $qMaster = "
            (SELECT
                A.id_tfk,
                A.sj_tfk,
                A.tglsj_tfk,
                A.po_tfk,
                A.tglpo_tfk,
                A.kode_tfk,
                A.status_dokumen,
                A.status_failing,
                A.status_tfk,
                A.status_tfkkf,
                A.tgl_tfk,
                A.ppn_tfk,
                A.total_tfk,
                A.subtot_tfk,
                A.status_f_pajak,
                A.upload_f_pajak,
                B.nama_out,
                B.kode_rs,
                B.status_urgent,
                D.nama_rkb,
                od.top_odi,
                tb.tgl_tfkkb,
                df.dokumen_failing_created_at,
                db.dokumen_balik_created_at,
                up.fp_created_at,
                fin.pemberkasan_created_at,
                fin.tanggal_faktur_finance AS tanggal_faktur_finance,
                tf.tf_created_at,
                sr.serah_terima_failing_created_at,
                sfp.serah_terima_faktur_pajak_created_at,
                sfb.serah_terima_faktur_pemberkasan_created_at,
                jd.jadwal_tf_tanggal,
                ad.nama_adm,
                dtfb.dokumen_tf_balik_tanggal,
                dtfb_det.dokumen_tf_balik_detail_created_at,
                'Cendo & DPE' AS jenis_faktur
            FROM transaksi_faktur AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            LEFT JOIN outlet_alamat AS C ON A.id_out = C.id_out
            LEFT JOIN regional_kabupaten AS D ON C.id_rkb = D.id_rkb

            /* derived: TOP ODI per outlet */
            LEFT JOIN (
                SELECT id_out, MAX(top_odi) AS top_odi
                FROM outlet_diskon
                GROUP BY id_out
            ) od ON od.id_out = A.id_out

            LEFT JOIN (
                SELECT id_tfk, DATE(MAX(created_at)) AS tgl_tfkkb
                FROM transaksi_faktur_kirim_b
                WHERE created_at IS NOT NULL
                GROUP BY id_tfk
            ) tb ON tb.id_tfk = A.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_failing_created_at
                FROM dokumen_failing_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) df ON df.no_faktur = A.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_balik_created_at
                FROM dokumen_balik_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) db ON db.no_faktur = A.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS fp_created_at
                FROM upload_f_pajak_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) up ON up.no_faktur = A.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_failing_created_at
                FROM serah_terima_failing_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) sr ON sr.no_faktur = A.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pajak_created_at
                FROM serah_terima_faktur_pajak_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) sfp ON sfp.no_faktur = A.id_tfk

            /* derived: terakhir created_at untuk serah_terima_faktur_pemberkasan_detail */
            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pemberkasan_created_at
                FROM serah_terima_faktur_pemberkasan_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) sfb ON sfb.no_faktur = A.id_tfk

            LEFT JOIN (
                /* ambil pemberkasan_created_at dari finance_detail
                   dan ambil tanggal_faktur dari tabel finance via id_finance */
                SELECT
                    fd.no_kwitansi,
                    DATE(MAX(fd.created_at)) AS pemberkasan_created_at,
                    COALESCE(
                        MAX(f.tanggal_faktur),
                        DATE(MAX(fd.created_at))
                    ) AS tanggal_faktur_finance
                FROM finance_detail fd
                LEFT JOIN finance f ON f.id_finance = fd.id_finance
                WHERE fd.created_at IS NOT NULL
                GROUP BY fd.no_kwitansi
            ) fin ON fin.no_kwitansi = A.id_tfk

            /* derived: ambil baris transaksi_faktur_kirim_f terakhir per id_tfk -> tf_created_at + id_adm */
            LEFT JOIN (
                SELECT t.id_tfk, DATE(t.created_at) AS tf_created_at, t.id_adm
                FROM transaksi_faktur_kirim_f t
                JOIN (
                    SELECT id_tfk AS itk, MAX(created_at) AS max_created_at
                    FROM transaksi_faktur_kirim_f
                    WHERE created_at IS NOT NULL
                    GROUP BY id_tfk
                ) tx ON t.id_tfk = tx.itk AND t.created_at = tx.max_created_at
            ) tf ON tf.id_tfk = A.id_tfk

            /* ambil nama admin dari tabel adminz berdasarkan id_adm (bisa NULL) */
            LEFT JOIN adminz ad ON ad.id_adm = tf.id_adm

            LEFT JOIN (
                SELECT jtd.no_faktur, DATE(MAX(jt.tanggal)) AS jadwal_tf_tanggal
                FROM jadwal_tf_detail jtd
                JOIN jadwal_tf jt ON jtd.id_tf = jt.id_tf
                WHERE jt.tanggal IS NOT NULL
                GROUP BY jtd.no_faktur
            ) jd ON jd.no_faktur = A.id_tfk

            /* derived: terakhir tanggal pada dokumen_tf_balik via dokumen_tf_balik_detail */
            LEFT JOIN (
                SELECT dtd.no_faktur, DATE(MAX(dt.tanggal)) AS dokumen_tf_balik_tanggal
                FROM dokumen_tf_balik_detail dtd
                JOIN dokumen_tf_balik dt ON dtd.id_tfb = dt.id_tfb
                WHERE dt.tanggal IS NOT NULL
                GROUP BY dtd.no_faktur
            ) dtfb ON dtfb.no_faktur = A.id_tfk

            /* derived: terakhir created_at untuk dokumen_tf_balik_detail (created_at) - NEW */
            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_tf_balik_detail_created_at
                FROM dokumen_tf_balik_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) dtfb_det ON dtfb_det.no_faktur = A.id_tfk

            WHERE $whereCendo)
            
            UNION ALL
            
            (SELECT
                P.id_tfk,
                P.sj_tfk,
                P.tglsj_tfk,
                P.po_tfk,
                P.tglpo_tfk,
                P.kode_tfk,
                P.status_dokumen,
                P.status_failing,
                P.status_tfk,
                P.status_tfkkf,
                P.tgl_tfk,
                P.ppn_tfk,
                P.total_tfk,
                P.subtot_tfk,
                P.status_f_pajak,
                P.upload_f_pajak,
                B_P.nama_out,
                B_P.kode_rs,
                B_P.status_urgent,
                D_P.nama_rkb,
                od.top_odi,
                tb.tgl_tfkkb,
                df.dokumen_failing_created_at,
                db.dokumen_balik_created_at,
                up.fp_created_at,
                fin.pemberkasan_created_at,
                fin.tanggal_faktur_finance AS tanggal_faktur_finance,
                tf.tf_created_at,
                sr.serah_terima_failing_created_at,
                sfp.serah_terima_faktur_pajak_created_at,
                sfb.serah_terima_faktur_pemberkasan_created_at,
                jd.jadwal_tf_tanggal,
                ad.nama_adm,
                dtfb_p.dokumen_tf_balik_tanggal,
                dtfb_det_p.dokumen_tf_balik_detail_created_at,
                'PIM' AS jenis_faktur
            FROM transaksi_faktur_pim AS P
            LEFT JOIN outlet AS B_P ON P.id_out = B_P.id_out
            LEFT JOIN outlet_alamat AS C_P ON P.id_out = C_P.id_out
            LEFT JOIN regional_kabupaten AS D_P ON C_P.id_rkb = D_P.id_rkb

            /* derived: TOP ODI per outlet */
            LEFT JOIN (
                SELECT id_out, MAX(top_odi) AS top_odi
                FROM outlet_diskon
                GROUP BY id_out
            ) od ON od.id_out = P.id_out

            LEFT JOIN (
                SELECT id_tfk, DATE(MAX(created_at)) AS tgl_tfkkb
                FROM transaksi_faktur_kirim_b
                WHERE created_at IS NOT NULL
                GROUP BY id_tfk
            ) tb ON tb.id_tfk = P.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_failing_created_at
                FROM dokumen_failing_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) df ON df.no_faktur = P.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_balik_created_at
                FROM dokumen_balik_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) db ON db.no_faktur = P.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS fp_created_at
                FROM upload_f_pajak_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) up ON up.no_faktur = P.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_failing_created_at
                FROM serah_terima_failing_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) sr ON sr.no_faktur = P.id_tfk

            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pajak_created_at
                FROM serah_terima_faktur_pajak_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) sfp ON sfp.no_faktur = P.id_tfk

            /* derived: terakhir created_at untuk serah_terima_faktur_pemberkasan_detail (PIM) */
            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pemberkasan_created_at
                FROM serah_terima_faktur_pemberkasan_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) sfb ON sfb.no_faktur = P.id_tfk

            LEFT JOIN (
                /* ambil pemberkasan_created_at dari finance_detail
                   dan ambil tanggal_faktur dari tabel finance via id_finance */
                SELECT
                    fd.no_kwitansi,
                    DATE(MAX(fd.created_at)) AS pemberkasan_created_at,
                    COALESCE(
                        MAX(f.tanggal_faktur),
                        DATE(MAX(fd.created_at))
                    ) AS tanggal_faktur_finance
                FROM finance_detail fd
                LEFT JOIN finance f ON f.id_finance = fd.id_finance
                WHERE fd.created_at IS NOT NULL
                GROUP BY fd.no_kwitansi
            ) fin ON fin.no_kwitansi = P.id_tfk

            /* derived: ambil baris transaksi_faktur_kirim_f terakhir per id_tfk -> tf_created_at + id_adm */
            LEFT JOIN (
                SELECT t.id_tfk, DATE(t.created_at) AS tf_created_at, t.id_adm
                FROM transaksi_faktur_kirim_f t
                JOIN (
                    SELECT id_tfk AS itk, MAX(created_at) AS max_created_at
                    FROM transaksi_faktur_kirim_f
                    WHERE created_at IS NOT NULL
                    GROUP BY id_tfk
                ) tx ON t.id_tfk = tx.itk AND t.created_at = tx.max_created_at
            ) tf ON tf.id_tfk = P.id_tfk

            /* ambil nama admin dari tabel adminz berdasarkan id_adm (bisa NULL) */
            LEFT JOIN adminz ad ON ad.id_adm = tf.id_adm

            LEFT JOIN (
                SELECT jtd.no_faktur, DATE(MAX(jt.tanggal)) AS jadwal_tf_tanggal
                FROM jadwal_tf_detail jtd
                JOIN jadwal_tf jt ON jtd.id_tf = jt.id_tf
                WHERE jt.tanggal IS NOT NULL
                GROUP BY jtd.no_faktur
            ) jd ON jd.no_faktur = P.id_tfk

            /* derived: terakhir tanggal pada dokumen_tf_balik via dokumen_tf_balik_detail (PIM) */
            LEFT JOIN (
                SELECT dtd.no_faktur, DATE(MAX(dt.tanggal)) AS dokumen_tf_balik_tanggal
                FROM dokumen_tf_balik_detail dtd
                JOIN dokumen_tf_balik dt ON dtd.id_tfb = dt.id_tfb
                WHERE dt.tanggal IS NOT NULL
                GROUP BY dtd.no_faktur
            ) dtfb_p ON dtfb_p.no_faktur = P.id_tfk

            /* derived: terakhir created_at untuk dokumen_tf_balik_detail (created_at) - NEW for PIM */
            LEFT JOIN (
                SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_tf_balik_detail_created_at
                FROM dokumen_tf_balik_detail
                WHERE created_at IS NOT NULL
                GROUP BY no_faktur
            ) dtfb_det_p ON dtfb_det_p.no_faktur = P.id_tfk

            WHERE $wherePIM)
            
            ORDER BY
                CASE WHEN LOWER(status_urgent) = 'urgent' THEN 0 ELSE 1 END,
                YEAR(tgl_tfk) DESC,
                MONTH(tgl_tfk) DESC,
                DAY(tgl_tfk) ASC
            LIMIT :mulai, :maxi";
        
        $master = $conn->prepare($qMaster);
        $master->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
        
        if (!empty($id_out) && $id_out !== 'All') {
            $master->bindValue(':id_out', $id_out, PDO::PARAM_STR);
        }
        
        if (!empty($cari_cabang)) {
            $master->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
        }
        
        // Bind parameter periode
        if (!empty($periode_dari)) {
            $master->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
        }
        if (!empty($periode_sampai)) {
            $master->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
        }
        
        $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
        $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
        $master->execute();

        $dataRows = [];
        $no = $mulai;
        
        // Get the branch name from the system
        $source = $data->self_apl();
        $nama_cabang = $source['nama_apl'];
        
        // Replace the existing while loop with this improved version
        while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
            $no++;
            $sistem = $data->sistem('url_sis');
            
            // Check if item is urgent - use strict comparison for safety
            $isUrgent = false;
            if (isset($hasil['status_urgent']) && strtolower(trim($hasil['status_urgent'])) === 'urgent') {
                $isUrgent = true;
            }
            
            $dataRows[] = [
                "no" => $no,
                "kode_tfk" => $hasil['kode_tfk'],
                "nama_out" => $hasil['nama_out'],
                "tgl_tfk" => $hasil['tgl_tfk'],
                "tanggal_faktur_finance" => $hasil['tanggal_faktur_finance'] ?? '-',
                "top_odi" => $hasil['top_odi'] ?? '-',
                "total_tfk" => $data->angka($hasil['subtot_tfk']),
                "kode_rs" => $hasil['kode_rs'],
                "po_tfk" => $hasil['po_tfk'],
                "tglpo_tfk" => $hasil['tglpo_tfk'],

                "nama_cabang" => $nama_cabang,
                "status_dokumen" => $hasil['status_dokumen'],
                "status_failing" => $hasil['status_failing'],
                "status_tfkkf" => $hasil['status_tfkkf'],
                "status_tfk" => $hasil['status_tfk'],
                "subtot_tfk" => $data->angka($hasil['subtot_tfk']),
                "id_tfk" => $hasil['id_tfk'],
                "urgent_flag" => $isUrgent,
                "status_urgent" => $isUrgent ? 'urgent' : 'normal',
                "upload_f_pajak" => $hasil['upload_f_pajak'] ?? 'belum',
                "status_f_pajak" => $hasil['status_f_pajak'] ?? 'belum terbit',
                "jenis_faktur" => $hasil['jenis_faktur'],
                "adminz" => $hasil['adminz'] ?? ($hasil['nama_adm'] ?? '-'),
                // new fields from derived joins
                "tgl_tfkkb" => $hasil['tgl_tfkkb'] ?? '-',
                "dokumen_failing_created_at" => $hasil['dokumen_failing_created_at'] ?? '-',
                "dokumen_balik_created_at" => $hasil['dokumen_balik_created_at'] ?? '-',
                "fp_created_at" => $hasil['fp_created_at'] ?? '-',
                "dokumen_tf_balik_tanggal" => $hasil['dokumen_tf_balik_tanggal'] ?? '-',
                "dokumen_tf_balik_detail_created_at" => $hasil['dokumen_tf_balik_detail_created_at'] ?? $hasil['dokumen_tf_balik_detail_created_at'] ?? '-', /* NEW */
                /* serah terima failing (baru) */
                "serah_terima_failing_created_at" => $hasil['serah_terima_failing_created_at'] ?? '-',
                /* serah terima faktur pajak (baru) */
                "serah_terima_faktur_pajak_created_at" => $hasil['serah_terima_faktur_pajak_created_at'] ?? '-',
                /* serah terima faktur pemberkasan (baru) */
                "serah_terima_faktur_pemberkasan_created_at" => $hasil['serah_terima_faktur_pemberkasan_created_at'] ?? '-',
                "pemberkasan_created_at" => $hasil['pemberkasan_created_at'] ?? '-',
                //"jadwal_tf_tanggal" => $hasil['jadwal_tf_tanggal'] ?? '-',
                "tf_created_at" => $hasil['tf_created_at'] ?? '-',
                "action" => [
                    "faktur_url" => $sistem . '/laporan/xps/faktursales/faktursales.php?key=' . $hasil['id_tfk'],
                    "print_enabled" => ($hasil['upload_f_pajak'] ?? '') === 'sudah',
                    "print_url" => $sistem . '/laporan/xps/monitoringfi/monitoringfi.php?id_tfk=' . $hasil['id_tfk'] . '&jenis_faktur=' . $hasil['jenis_faktur']
                ]
            ];
        }

        $navi = $paging->myPaging($menu, $total, $maxi, $page);

        $response = [
            "success" => true,
            "message" => "",
            "data" => $dataRows,
            "total" => $total,
            "halaman" => $page,
            "paginasi" => $navi
        ];
        
        http_response_code(200);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode($response);
    } catch (PDOException $e) {
        error_log("Database Error in getFakturPajak.php: " . $e->getMessage());
        http_response_code(500);
        header('Access-Control-Allow-Origin: *');
        header("Content-type: application/json; charset=utf-8");
        echo json_encode([
            "success" => false,
            "message" => "Database Error: " . $e->getMessage(),
            "data" => [],
            "total" => 0
        ]);
    } finally {
        $conn = $base->close();
    }
}
?>