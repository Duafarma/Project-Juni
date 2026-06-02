<?php
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode(["success" => false, "message" => "Method Not Allowed"]);
    exit;
}

require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');
require_once('../config/function/paging.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$paging = new Paging;
$conn = $base->open();

// Ambil parameter pencarian/filter
$cari = $secu->injection($_GET['caridata'] ?? '');
$cari_cabang = $secu->injection($_GET['cari_cabang'] ?? '');
$id_out = $secu->injection($_GET['id_out'] ?? '');
$page = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$maxi = isset($_GET['maximal']) && $_GET['maximal'] !== '' ? (int)$_GET['maximal'] : 1000;
$menu = $secu->injection($_GET['menudata'] ?? '');
$mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;
$periode_dari = $secu->injection($_GET['periode_dari'] ?? '');
$periode_sampai = $secu->injection($_GET['periode_sampai'] ?? '');

// Default hanya ambil dari transaksi_faktur (untuk backward compatibility)
$includePim = (isset($_GET['include_pim']) && $_GET['include_pim'] == '1');

try {
    // Ambil semua cabang aktif
    $stmtApl = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, self_apl, nama_apl FROM aplikasi WHERE active_apl = 1");
    $stmtApl->execute();
    $aplikasiList = $stmtApl->fetchAll(PDO::FETCH_ASSOC);

    $allData = [];
    $total = 0;

    foreach ($aplikasiList as $apl) {
        $params = [
            'caridata' => $cari,
            'cari_cabang' => $cari_cabang,
            'id_out' => $id_out,
            'halaman' => 1,
            'maximal' => $maxi,
            'menudata' => $menu,
            'periode_dari' => $periode_dari,
            'periode_sampai' => $periode_sampai,
        ];

        if ($apl['self_apl'] == 1) {
            // Query langsung ke database lokal
            $where = "(
                A.kode_tfk LIKE :cari OR
                A.sj_tfk LIKE :cari OR
                A.po_tfk LIKE :cari OR
                B.nama_out LIKE :cari
            ) AND A.status_tfk NOT IN ('Cancel','Draft')"; // <<< exclude Draft

            if (!empty($id_out) && $id_out !== 'All') {
                $where .= " AND A.id_out = :id_out";
            }
            if (!empty($cari_cabang)) {
                $where .= " AND (B.nama_out LIKE :cari_cabang OR A.kode_tfk LIKE :cari_cabang)";
            }
            if (!empty($periode_dari)) {
                $where .= " AND A.tgl_tfk >= :periode_dari";
            }
            if (!empty($periode_sampai)) {
                $where .= " AND A.tgl_tfk <= :periode_sampai";
            }

            // Query untuk transaksi_faktur (Cendo & DPE) - gunakan LEFT JOIN ke derived subqueries
            $qMaster = "SELECT
                    A.id_tfk,
                    A.sj_tfk,
                    A.tglsj_tfk,
                    A.po_tfk,
                    A.tglpo_tfk,
                    A.kode_tfk,
                    A.tgl_tfk,
                    A.ppn_tfk,
                    A.total_tfk,
                    A.status_tfk,
                    A.subtot_tfk,
                    A.status_f_pajak,
                    A.upload_f_pajak,
                    A.status_failing,
                    A.status_tfkkf,
                    A.status_tfkkb,
                    A.status_dokumen,
                    B.kode_rs,
                    B.nama_out,
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

                /* derived: terakhir tgl_tfkkb dari transaksi_faktur_kirim_b */
                LEFT JOIN (
                    SELECT id_tfk, DATE(MAX(created_at)) AS tgl_tfkkb
                    FROM transaksi_faktur_kirim_b
                    WHERE created_at IS NOT NULL
                    GROUP BY id_tfk
                ) tb ON tb.id_tfk = A.id_tfk

                /* derived: terakhir created_at untuk dokumen_failing_detail */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_failing_created_at
                    FROM dokumen_failing_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) df ON df.no_faktur = A.id_tfk

                /* derived: terakhir created_at untuk dokumen_balik_detail */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_balik_created_at
                    FROM dokumen_balik_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) db ON db.no_faktur = A.id_tfk

                /* derived: terakhir created_at untuk upload_f_pajak_detail */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS fp_created_at
                    FROM upload_f_pajak_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) up ON up.no_faktur = A.id_tfk

                /* derived: terakhir created_at untuk serah_terima_failing_detail (baru) */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_failing_created_at
                    FROM serah_terima_failing_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) sr ON sr.no_faktur = A.id_tfk

                /* derived: terakhir created_at untuk serah_terima_faktur_pajak_detail (baru) */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pajak_created_at
                    FROM serah_terima_faktur_pajak_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) sfp ON sfp.no_faktur = A.id_tfk

                /* derived: terakhir created_at untuk finance_detail (no_kwitansi)
                   dan ambil tanggal_faktur dari tabel finance via id_finance */
                LEFT JOIN (
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

                LEFT JOIN adminz ad ON ad.id_adm = tf.id_adm

                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pemberkasan_created_at
                    FROM serah_terima_faktur_pemberkasan_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) sfb ON sfb.no_faktur = A.id_tfk

                /* derived: jadwal TF (ambil tanggal dari jadwal_tf via jadwal_tf_detail) */
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

                /* derived: terakhir created_at untuk dokumen_tf_balik_detail */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_tf_balik_detail_created_at
                    FROM dokumen_tf_balik_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) dtfb_det ON dtfb_det.no_faktur = A.id_tfk

                WHERE $where
                ORDER BY 
                    CASE WHEN LOWER(B.status_urgent) = 'urgent' THEN 0 ELSE 1 END,
                    A.tgl_tfk DESC";
            
            $master = $conn->prepare($qMaster);
            $master->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
            if (!empty($id_out) && $id_out !== 'All') {
                $master->bindValue(':id_out', $id_out, PDO::PARAM_STR);
            }
            if (!empty($cari_cabang)) {
                $master->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
            }
            if (!empty($periode_dari)) {
                $master->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
            }
            if (!empty($periode_sampai)) {
                $master->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
            }
            $master->execute();

            while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                $urgent_flag = strtolower($hasil['status_urgent'] ?? '') === 'urgent';

                $allData[] = [
                    "id_tfk" => $hasil['id_tfk'],
                    "kode_tfk" => $hasil['kode_tfk'],
                    "tgl_tfk" => $hasil['tgl_tfk'],
                    "tanggal_faktur_finance" => $hasil['tanggal_faktur_finance'] ?? '-',
                    "nama_cabang" => $apl['nama_apl'],
                    "nama_out" => $hasil['nama_out'],
                    "subtot_tfk" => $data->angka($hasil['subtot_tfk']),
                    "kode_rs" => $hasil['kode_rs'],
                    "ppn_tfk" => $data->angka($hasil['ppn_tfk']),
                    "po_tfk" => $hasil['po_tfk'],
                    "tglpo_tfk" => $hasil['tglpo_tfk'],
                    "top_odi" => $hasil['top_odi'] ?? '-',
                    /* map new field - pass raw timestamp (or NULL) */
                    "dokumen_failing_created_at" => $hasil['dokumen_failing_created_at'] ?? '-',
                    /* map new dokumen balik field */
                    "dokumen_balik_created_at" => $hasil['dokumen_balik_created_at'] ?? '-',
                    /* map new FP (upload f pajak) field */
                    "fp_created_at" => $hasil['fp_created_at'] ?? '-',
                    /* map new serah terima failing field (baru) */
                    "serah_terima_failing_created_at" => $hasil['serah_terima_failing_created_at'] ?? '-',
                    /* map new serah terima faktur pajak field (baru) */
                    "serah_terima_faktur_pajak_created_at" => $hasil['serah_terima_faktur_pajak_created_at'] ?? '-',
                    /* map new Pemberkasan field */
                    "pemberkasan_created_at" => $hasil['pemberkasan_created_at'] ?? '-',
                    /* map new serah terima faktur pemberkasan field for PIM */
                    "serah_terima_faktur_pemberkasan_created_at" => $hasil['serah_terima_faktur_pemberkasan_created_at'] ?? '-',
                    /* map new jadwal tf tanggal (derived) */
                    "jadwal_tf_tanggal" => $hasil['jadwal_tf_tanggal'] ?? '-',
                    /* dokumen tf balik (tanggal dari dokumen_tf_balik via detail) */
                    "dokumen_tf_balik_tanggal" => $hasil['dokumen_tf_balik_tanggal'] ?? '-',
                    /* NEW: dokumen tf balik detail created_at */
                    "dokumen_tf_balik_detail_created_at" => $hasil['dokumen_tf_balik_detail_created_at'] ?? '-',
                    /* map new TF field */
                    "adminz" => $hasil['adminz'] ?? ($hasil['nama_adm'] ?? '-'),
                    "tf_created_at" => $hasil['tf_created_at'] ?? '-',
                    "tgl_tfkkb" => $hasil['tgl_tfkkb'] ?? '-',
                    "total_tfk" => $data->angka($hasil['total_tfk']),
                    "status_f_pajak" => $hasil['status_f_pajak'] ?? '-',
                    "upload_f_pajak" => $hasil['upload_f_pajak'] ?? '-',
                    "status_failing" => $hasil['status_failing'] ?? '-',
                    "status_tfkkf" => $hasil['status_tfkkf'] ?? '-',
                    "status_tfkkb" => $hasil['status_tfkkb'] ?? '-',
                    "status_dokumen" => $hasil['status_dokumen'] ?? '-',
                    "status_tfk" => $hasil['status_tfk'],
                    "urgent_flag" => $urgent_flag,
                    "jenis_faktur" => "Cendo & DPE"
                ];
            }

            // Ambil data PIM jika diminta
            if ($includePim) {
                $wherePim = str_replace('A.', 'P.', $where);
                $wherePim = str_replace('B.', 'O.', $wherePim);
                
                $qPim = "SELECT
                        P.id_tfk AS id_tfk,
                        P.sj_tfk AS sj_tfk,
                        P.tglsj_tfk AS tglsj_tfk,
                        P.po_tfk AS po_tfk,
                        P.tglpo_tfk AS tglpo_tfk,
                        P.kode_tfk AS kode_tfk,
                        P.tgl_tfk AS tgl_tfk,
                        P.ppn_tfk AS ppn_tfk,
                        P.total_tfk AS total_tfk,
                        P.status_tfk AS status_tfk,
                        P.subtot_tfk AS subtot_tfk,
                        P.status_f_pajak,
                        P.upload_f_pajak,
                        P.status_failing,
                        P.status_tfkkf,
                        P.status_tfkkb,
                        P.status_dokumen,
                        O.kode_rs,
                        O.nama_out,
                        O.status_urgent,
                        R.nama_rkb,
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
                        'PIM' AS jenis_faktur
                    FROM transaksi_faktur_pim AS P
                    LEFT JOIN outlet AS O ON P.id_out = O.id_out
                    LEFT JOIN outlet_alamat AS OA ON P.id_out = OA.id_out
                    LEFT JOIN regional_kabupaten AS R ON OA.id_rkb = R.id_rkb

                    /* derived: TOP ODI per outlet */
                    LEFT JOIN (
                        SELECT id_out, MAX(top_odi) AS top_odi
                        FROM outlet_diskon
                        GROUP BY id_out
                    ) od ON od.id_out = P.id_out

                    LEFT JOIN (
                        SELECT id_tfk, MAX(tgl_tfkkb) AS tgl_tfkkb
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

                    LEFT JOIN (
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

                    LEFT JOIN adminz ad ON ad.id_adm = tf.id_adm
                    
                    LEFT JOIN (
                        SELECT no_faktur, DATE(MAX(created_at)) AS serah_terima_faktur_pemberkasan_created_at
                        FROM serah_terima_faktur_pemberkasan_detail
                        WHERE created_at IS NOT NULL
                        GROUP BY no_faktur
                    ) sfb ON sfb.no_faktur = P.id_tfk

                    /* derived: jadwal TF (PIM) */
                    LEFT JOIN (
                        SELECT jtd.no_faktur, DATE(MAX(jt.tanggal)) AS jadwal_tf_tanggal
                        FROM jadwal_tf_detail jtd
                        JOIN jadwal_tf jt ON jtd.id_tf = jt.id_tf
                        WHERE jt.tanggal IS NOT NULL
                        GROUP BY jtd.no_faktur
                    ) jd ON jd.no_faktur = P.id_tfk

                /* derived: terakhir tanggal pada dokumen_tf_balik via dokumen_tf_balik_detail */
                LEFT JOIN (
                    SELECT dtd.no_faktur, DATE(MAX(dt.tanggal)) AS dokumen_tf_balik_tanggal
                    FROM dokumen_tf_balik_detail dtd
                    JOIN dokumen_tf_balik dt ON dtd.id_tfb = dt.id_tfb
                    WHERE dt.tanggal IS NOT NULL
                    GROUP BY dtd.no_faktur
                ) dtfb ON dtfb.no_faktur = P.id_tfk

                /* derived: terakhir created_at untuk dokumen_tf_balik_detail */
                LEFT JOIN (
                    SELECT no_faktur, DATE(MAX(created_at)) AS dokumen_tf_balik_detail_created_at
                    FROM dokumen_tf_balik_detail
                    WHERE created_at IS NOT NULL
                    GROUP BY no_faktur
                ) dtfb_det ON dtfb_det.no_faktur = P.id_tfk

                    WHERE $wherePim
                    ORDER BY 
                        CASE WHEN LOWER(O.status_urgent) = 'urgent' THEN 0 ELSE 1 END,
                        P.tgl_tfk DESC";

                $masterPim = $conn->prepare($qPim);
                $masterPim->bindValue(':cari', '%' . $cari . '%', PDO::PARAM_STR);
                if (!empty($id_out) && $id_out !== 'All') {
                    $masterPim->bindValue(':id_out', $id_out, PDO::PARAM_STR);
                }
                if (!empty($cari_cabang)) {
                    $masterPim->bindValue(':cari_cabang', '%' . $cari_cabang . '%', PDO::PARAM_STR);
                }
                if (!empty($periode_dari)) {
                    $masterPim->bindValue(':periode_dari', $periode_dari, PDO::PARAM_STR);
                }
                if (!empty($periode_sampai)) {
                    $masterPim->bindValue(':periode_sampai', $periode_sampai, PDO::PARAM_STR);
                }
                $masterPim->execute();

                while ($hasil = $masterPim->fetch(PDO::FETCH_ASSOC)) {
                    $urgent_flag = strtolower($hasil['status_urgent'] ?? '') === 'urgent';

                    $allData[] = [
                        "id_tfk" => $hasil['id_tfk'],
                        "kode_tfk" => $hasil['kode_tfk'],
                        "tgl_tfk" => $hasil['tgl_tfk'],
                        "tanggal_faktur_finance" => $hasil['tanggal_faktur_finance'] ?? '-',
                        "nama_cabang" => $apl['nama_apl'],
                        "nama_out" => $hasil['nama_out'],
                        "subtot_tfk" => $data->angka($hasil['subtot_tfk']),
                        "kode_rs" => $hasil['kode_rs'],
                        "ppn_tfk" => $data->angka($hasil['ppn_tfk']),
                        "po_tfk" => $hasil['po_tfk'],
                        "tglpo_tfk" => $hasil['tglpo_tfk'],
                        "top_odi" => $hasil['top_odi'] ?? '-',
                        /* map new dokumen balik field for PIM */
                        "dokumen_balik_created_at" => $hasil['dokumen_balik_created_at'] ?? '-',
                        /* map new FP (upload f pajak) field for PIM */
                        "fp_created_at" => $hasil['fp_created_at'] ?? '-',
                        /* map new serah terima failing field for PIM (baru) */
                        "serah_terima_failing_created_at" => $hasil['serah_terima_failing_created_at'] ?? '-',
                        /* map new serah terima faktur pajak field for PIM (baru) */
                        "serah_terima_faktur_pajak_created_at" => $hasil['serah_terima_faktur_pajak_created_at'] ?? '-',
                        /* map new Pemberkasan field for PIM */
                        "pemberkasan_created_at" => $hasil['pemberkasan_created_at'] ?? '-',
                        /* map new serah terima faktur pemberkasan field for PIM */
                        "serah_terima_faktur_pemberkasan_created_at" => $hasil['serah_terima_faktur_pemberkasan_created_at'] ?? '-',
                        /* map new jadwal tf tanggal (derived) for PIM */
                        "jadwal_tf_tanggal" => $hasil['jadwal_tf_tanggal'] ?? '-',
                        /* dokumen tf balik (tanggal dari dokumen_tf_balik via detail) */
                        "dokumen_tf_balik_tanggal" => $hasil['dokumen_tf_balik_tanggal'] ?? '-',
                        /* NEW: dokumen tf balik detail created_at */
                        "dokumen_tf_balik_detail_created_at" => $hasil['dokumen_tf_balik_detail_created_at'] ?? '-',
                        /* map new TF field for PIM */
                        "adminz" => $hasil['adminz'] ?? ($hasil['nama_adm'] ?? '-'),
                        "tf_created_at" => $hasil['tf_created_at'] ?? '-',
                        "tgl_tfkkb" => $hasil['tgl_tfkkb'] ?? '-',
                        "total_tfk" => $data->angka($hasil['total_tfk']),
                        "status_f_pajak" => $hasil['status_f_pajak'] ?? '-',
                        "upload_f_pajak" => $hasil['upload_f_pajak'] ?? '-',
                        "status_failing" => $hasil['status_failing'] ?? '-',
                        "status_tfkkf" => $hasil['status_tfkkf'] ?? '-',
                        "status_tfkkb" => $hasil['status_tfkkb'] ?? '-',
                        "status_dokumen" => $hasil['status_dokumen'] ?? '-',
                        "status_tfk" => $hasil['status_tfk'],
                        "urgent_flag" => $urgent_flag,
                        "jenis_faktur" => "PIM"
                    ];
                }
            }
        } else {
            // Query ke API cabang lain
            $tgl = date('Y-m-d');
            $encrypt = md5($tgl . "#" . $apl['key_apl']);
            $params['encrypt'] = $encrypt;
            if ($includePim) {
                $params['include_pim'] = '1';
            }
            $apiUrl = rtrim($apl['base_url_apl'], '/') . '/api/getFakturArB.php?' . http_build_query($params);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 180); // 3 menit
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($curlError) {
                // Log error tapi tetap lanjut ke cabang berikutnya
                error_log("Error mengambil data dari cabang {$apl['nama_apl']}: $curlError");
                continue;
            }

            if ($httpCode != 200) {
                error_log("HTTP Error $httpCode dari cabang {$apl['nama_apl']}");
                continue;
            }

            $apiData = json_decode($response, true);
            if (isset($apiData['data']) && is_array($apiData['data'])) {
                foreach ($apiData['data'] as $row) {
                    $row['nama_cabang'] = $apl['nama_apl'];
                    // Pastikan jenis_faktur ada
                    if (!isset($row['jenis_faktur']) || empty($row['jenis_faktur'])) {
                        $row['jenis_faktur'] = 'Cendo & DPE'; // Default jika tidak diisi
                    }
                    $allData[] = $row;
                }
            } else {
                error_log("Format data tidak valid dari cabang {$apl['nama_apl']}");
            }
        }
    }

    // Pisahkan item urgent dan normal
    $urgentItems = [];
    $normalItems = [];
    
    foreach ($allData as $item) {
        if (isset($item['urgent_flag']) && $item['urgent_flag'] === true) {
            $urgentItems[] = $item;
        } else {
            $normalItems[] = $item;
        }
    }
    
    // Sort urgent items by year/month descending, day ascending
    usort($urgentItems, function($a, $b) {
        $dateA = new DateTime($a['tgl_tfk']);
        $dateB = new DateTime($b['tgl_tfk']);
        
        // Compare years (descending)
        $yearDiff = (int)$dateB->format('Y') - (int)$dateA->format('Y');
        if ($yearDiff !== 0) return $yearDiff;
        
        // Compare months (descending)
        $monthDiff = (int)$dateB->format('m') - (int)$dateA->format('m');
        if ($monthDiff !== 0) return $monthDiff;
        
        // Compare days (ascending)
        return (int)$dateA->format('d') - (int)$dateB->format('d');
    });
    
    // Use the same sorting for normal items
    usort($normalItems, function($a, $b) {
        $dateA = new DateTime($a['tgl_tfk']);
        $dateB = new DateTime($b['tgl_tfk']);
        
        // Compare years (descending)
        $yearDiff = (int)$dateB->format('Y') - (int)$dateA->format('Y');
        if ($yearDiff !== 0) return $yearDiff;
        
        // Compare months (descending)
        $monthDiff = (int)$dateB->format('m') - (int)$dateA->format('m');
        if ($monthDiff !== 0) return $monthDiff;
        
        // Compare days (ascending)
        return (int)$dateA->format('d') - (int)$dateB->format('d');
    });
    
    // Gabungkan kembali dengan urgent items di awal
    $allData = array_merge($urgentItems, $normalItems);

    // Pagination manual
    $total = count($allData);
    $start = $mulai;
    $end = min($start + $maxi, $total);
    $dataRows = array_slice($allData, $start, $maxi);

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
} catch (Exception $e) {
    http_response_code(500);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage(),
        "data" => [],
        "total" => 0
    ]);
} finally {
    $conn = $base->close();
}
?>