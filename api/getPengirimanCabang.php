<?php
require_once('../config/connection/connection.php');
require_once('../config/connection/security.php');
require_once('../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;
$conn = $base->open();

$tgl = date('Y-m-d');
$cari = $secu->injection(@$_GET['caridata']);
$page = (int)($secu->injection(@$_GET['halaman']) ?: 1);
$maxi = (int)($secu->injection(@$_GET['maximal']) ?: 1000);
$mulai = ($page > 1) ? (($page * $maxi) - $maxi) : 0;
$encrypt = $secu->injection(@$_GET['encrypt']);

$source = $data->self_apl();
$sourceKey = $source['key_apl'];
$id_apl = $source['id_apl'];
$nama_apl = $source['nama_apl'];

if (md5($tgl . "#" . $sourceKey) == $encrypt) {
    $searchTerm = '%' . $cari . '%';
    $qMaster = "
        SELECT
            '$id_apl' AS id_apl,
            '$nama_apl' AS nama_apl,
            A.created_at,
            A.updated_at,
            A.id_tfkkb,
            A.status_tfkkb,
            A.ket_tfkkb,
            A.tgl_tfkkb,
            B.id_tfk,
            B.kode_tfk,
            B.sj_tfk,
            B.tgl_tfk,
            B.sediaan,
            B.jml_sediaan,
            B.ccp,
            C.nama_out,
            D.nama_adm,
            'transaksi_faktur' AS source_table
        FROM transaksi_faktur_kirim_b AS A
        LEFT JOIN transaksi_faktur AS B ON B.id_tfk = A.id_tfk
        LEFT JOIN outlet AS C ON C.id_out = B.id_out
        LEFT JOIN adminz AS D ON A.id_adm = D.id_adm
        WHERE (A.id_tfkkb LIKE :cari OR B.kode_tfk LIKE :cari OR C.nama_out LIKE :cari OR D.nama_adm LIKE :cari)

        UNION ALL

        SELECT
            '$id_apl' AS id_apl,
            '$nama_apl' AS nama_apl,
            A.created_at,
            A.updated_at,
            A.id_tfkkb,
            A.status_tfkkb,
            A.ket_tfkkb,
            A.tgl_tfkkb,
            P.id_tfk,
            P.kode_tfk,
            P.sj_tfk,
            P.tgl_tfk,
            P.sediaan,
            P.jml_sediaan,
            P.ccp,
            C.nama_out,
            D.nama_adm,
            'transaksi_faktur_pim' AS source_table
        FROM transaksi_faktur_kirim_b AS A
        LEFT JOIN transaksi_faktur_pim AS P ON P.id_tfk = A.id_tfk
        LEFT JOIN outlet AS C ON C.id_out = P.id_out
        LEFT JOIN adminz AS D ON A.id_adm = D.id_adm
        WHERE (A.id_tfkkb LIKE :cari OR P.kode_tfk LIKE :cari OR C.nama_out LIKE :cari OR D.nama_adm LIKE :cari)

        UNION ALL

        SELECT
            '$id_apl' AS id_apl,
            '$nama_apl' AS nama_apl,
            A.created_at,
            A.updated_at,
            A.id_tfkkb,
            A.status_tfkkb,
            A.ket_tfkkb,
            A.tgl_tfkkb,
            CTF.id_tfk,
            CTF.kode_tfk,
            CTF.sj_tfk,
            CTF.tgl_tfk,
            CTF.sediaan,
            CTF.jml_sediaan,
            CTF.ccp,
            OC.nama_out,
            AD.nama_adm,
            'transaksi_faktur_c' AS source_table
        FROM transaksi_faktur_kirim_b AS A
        LEFT JOIN transaksi_faktur_c AS CTF ON CTF.id_tfk = A.id_tfk
        LEFT JOIN outlet AS OC ON OC.id_out = CTF.id_out
        LEFT JOIN adminz AS AD ON A.id_adm = AD.id_adm
        WHERE (A.id_tfkkb LIKE :cari OR CTF.kode_tfk LIKE :cari OR OC.nama_out LIKE :cari OR AD.nama_adm LIKE :cari)

        ORDER BY created_at DESC
        LIMIT :mulai, :maxi
    ";
    $master = $conn->prepare($qMaster);
    $master->bindParam(':cari', $searchTerm, PDO::PARAM_STR);
    $master->bindParam(':mulai', $mulai, PDO::PARAM_INT);
    $master->bindParam(':maxi', $maxi, PDO::PARAM_INT);
    $master->execute();
    $result = $master->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    echo json_encode([
        "status" => "success",
        "result" => $result,
        "total_records" => count($result),
        "id_apl" => $id_apl,
        "nama_apl" => $nama_apl
    ]);
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
}
$conn = $base->close();
?>