<?php
require_once('../../config/connection/connection.php');
require_once('../../config/connection/security.php');
require_once('../../config/function/data.php');

$secu = new Security;
$base = new DB;
$data = new Data;

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Validasi parameter
$caridata = isset($_GET['caridata']) ? $secu->injection($_GET['caridata']) : '';
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$maximal = isset($_GET['maximal']) ? (int)$_GET['maximal'] : 75;
$menudata = isset($_GET['menudata']) ? $secu->injection($_GET['menudata']) : '';
// filter berdasarkan nama aplikasi (dropdown akan berisi nama_apl dari tabel aplikasi)
$filter_nama_apl = isset($_GET['nama_apl']) ? trim($secu->injection($_GET['nama_apl'])) : '';

// Validasi tanggal
$tgl = isset($_GET['tgl']) ? trim($_GET['tgl']) : date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) {
    $tgl = date('Y-m-d');
}

// Validasi tanggal dengan DateTime
$date_obj = DateTime::createFromFormat('Y-m-d', $tgl);
if (!$date_obj || $date_obj->format('Y-m-d') !== $tgl) {
    $tgl = date('Y-m-d');
}

try {
    $conn = $base->open();
    
    // Hitung tanggal 3 bulan yang lalu dari tanggal yang dipilih
    $date_3_months_ago = date('Y-m-d', strtotime($tgl . ' -12 months'));
    
    // WHERE clause dengan filter 45 hari terakhir dan status pending
    $whereClause = "WHERE A.id_tfk IS NOT NULL 
                    AND A.tgl_tfk >= :tgl_3_months_ago 
                    AND A.tgl_tfk <= :tgl
                    AND NOT (
                        A.status_tfkkb = 'Sudah Dikirim'
                        AND A.status_dokumen = 'sudah balik'
                        AND COALESCE(A.status_tfkkf, 'Belum Dikirim') = 'Sudah Dikirim'
                    )";
    
    $params = [
        ':tgl_3_months_ago' => $date_3_months_ago,
        ':tgl' => $tgl
    ];
    
    if (!empty($caridata)) {
        $whereClause .= " AND (A.kode_tfk LIKE :cari OR B.nama_out LIKE :cari)";
        $params[':cari'] = '%' . $caridata . '%';
    }
    
    // Query data lokal dengan filter pending
    $query = "SELECT 'local' as source,
                A.id_tfk,
                A.kode_tfk,
                A.id_out,
                A.tgl_tfk,
                A.cito,
                A.status_ceklis,
                A.created_at,
                A.status_failing,
                A.upload_f_pajak,
                A.status_dokumen,
                COALESCE(A.status_tfkkb, 'Belum Dikirim') AS status_tfkkb,                        
                B.id_out,
                B.nama_out,
                COALESCE(A.status_tfkkf, 'Belum Dikirim') AS status_tfkkf,
                'Cendo & DPE' as jenis_faktur
            FROM transaksi_faktur AS A 
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            LEFT JOIN transaksi_faktur_kirim_b AS C ON A.id_tfk = C.id_tfk
            $whereClause
            
            UNION ALL
            
            SELECT 'local' as source,
                A.id_tfk,
                A.kode_tfk,
                A.id_out,
                A.tgl_tfk,
                A.cito,
                A.status_ceklis,
                A.created_at,
                A.status_failing,
                A.upload_f_pajak,
                A.status_dokumen,
                COALESCE(A.status_tfkkb, 'Belum Dikirim') AS status_tfkkb,
                B.id_out,
                B.nama_out,
                COALESCE(A.status_tfkkf, 'Belum Dikirim') AS status_tfkkf,
                'PIM' as jenis_faktur
            FROM transaksi_faktur_pim AS A
            LEFT JOIN outlet AS B ON A.id_out = B.id_out
            LEFT JOIN transaksi_faktur_kirim_b AS C ON A.id_tfk = C.id_tfk
            $whereClause
            
            ORDER BY tgl_tfk ASC, created_at ASC";
    
    $master = $conn->prepare($query);
    foreach ($params as $param => $value) {
        $master->bindValue($param, $value, PDO::PARAM_STR);
    }
    
    $master->execute();
    $rows = $master->fetchAll(PDO::FETCH_ASSOC);
    
    $localCount = count($rows);
    
    // Ambil valid_ids untuk status pengiriman (hanya untuk 3 bulan terakhir)
    $validIdsQuery = "SELECT DISTINCT B.id_tfk 
        FROM transaksi_faktur AS A
        INNER JOIN transaksi_faktur_kirim_b AS B ON A.id_tfk = B.id_tfk
        LEFT JOIN outlet AS O ON A.id_out = O.id_out
        WHERE A.tgl_tfk >= :tgl_3_months_ago AND A.tgl_tfk <= :tgl
        
        UNION
        
        SELECT DISTINCT B.id_tfk 
        FROM transaksi_faktur_pim AS A
        INNER JOIN transaksi_faktur_kirim_b AS B ON A.id_tfk = B.id_tfk
        LEFT JOIN outlet AS O ON A.id_out = O.id_out
        WHERE A.tgl_tfk >= :tgl_3_months_ago AND A.tgl_tfk <= :tgl";
    
    $validIdsStmt = $conn->prepare($validIdsQuery);
    $validIdsStmt->bindValue(':tgl_3_months_ago', $date_3_months_ago, PDO::PARAM_STR);
    $validIdsStmt->bindValue(':tgl', $tgl, PDO::PARAM_STR);
    $validIdsStmt->execute();
    $valid_ids = $validIdsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Ambil id_apl aplikasi lokal
    $local_id_apl = 'APL01';
    
    // Ambil daftar aplikasi lain
    $stmtApl = $conn->prepare("SELECT id_apl, base_url_apl, key_apl, nama_apl FROM aplikasi WHERE id_apl != :local_id_apl AND active_apl = 1");
    $stmtApl->bindParam(':local_id_apl', $local_id_apl, PDO::PARAM_STR);
    $stmtApl->execute();
    $aplikasi_list = $stmtApl->fetchAll(PDO::FETCH_ASSOC);
    // Siapkan daftar nama_apl untuk dropdown frontend
    $aplikasi_names = [];
    foreach ($aplikasi_list as $apl_item) {
        if (!empty($apl_item['nama_apl'])) $aplikasi_names[] = $apl_item['nama_apl'];
    }
    $aplikasi_names = array_values(array_unique($aplikasi_names));
    sort($aplikasi_names, SORT_STRING);

    // Tambahkan opsi lokal "Puri" di paling atas jika belum ada
    if (!in_array('Puri', $aplikasi_names)) {
        array_unshift($aplikasi_names, 'Puri');
    }
    
    // Ambil data dari API aplikasi lain
    $api_data = [];
    $api_count = 0;
    
    foreach ($aplikasi_list as $apl) {
        $targetUrl = $apl['base_url_apl'] ?? '';
        $targetKey = $apl['key_apl'] ?? '';
        $id_apl    = $apl['id_apl'] ?? '';
        
        if (empty($targetUrl) || empty($targetKey)) {
            continue;
        }
        
        // Buat encrypt berdasarkan tanggal yang sudah divalidasi
        $encrypt = md5($tgl . "#" . $targetKey);
        $timestamp = time();

        $postData = [
            'encrypt' => $encrypt,
            'tgl' => $tgl,
            'tgl_3_months_ago' => $date_3_months_ago,
            'key' => $caridata,
            'id_apl' => $id_apl,
            'timestamp' => $timestamp,
            'filter_pending' => 'true'
        ];
        
        $headers = [
            'Cache-Control: no-cache, no-store, must-revalidate',
            'Pragma: no-cache',
            'Expires: 0',
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $targetUrl . "/api/getFaktur_pending.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code == 200 && $res !== false && empty($curl_error)) {
            $api_response = json_decode($res, true);
            if ($api_response && isset($api_response['result']) && is_array($api_response['result'])) {
                $current_api_data = $api_response['result'];
                $api_data = array_merge($api_data, $current_api_data);
                $current_count = count($current_api_data);
                $api_count += $current_count;
                
                // Tambahkan ke valid_ids
                foreach ($current_api_data as $api_row) {
                    if (!in_array($api_row['id_tfk'], $valid_ids)) {
                        $valid_ids[] = $api_row['id_tfk'];
                    }
                }
            }
        }
    }
    
    // Gabungkan data dari lokal dan API
    $all_data = array_merge($rows, $api_data);
    // Jika ada filter nama_apl, sisakan hanya data dari aplikasi tersebut (API) 
    // (local/Puri tidak termasuk karena nama_apl berasal dari tabel aplikasi)
    if (!empty($filter_nama_apl)) {
        if ($filter_nama_apl === 'Puri') {
            // pilih hanya data lokal (source == 'local')
            $all_data = array_values(array_filter($all_data, function($r) {
                return (isset($r['source']) && $r['source'] === 'local');
            }));
        } else {
            $all_data = array_values(array_filter($all_data, function($r) use ($filter_nama_apl) {
                return (isset($r['nama_apl']) && $r['nama_apl'] === $filter_nama_apl);
            }));
        }
    }
    
    // Sortir data berdasarkan prioritas (yang paling banyak pending di atas)
    // Urutkan pertama berdasarkan tanggal (terlama/terdahulu di atas),
    // jika tanggal sama, urutkan berdasarkan jumlah pending (lebih banyak pending di atas),
    // fallback berdasarkan created_at.
    usort($all_data, function($a, $b) {
        $dateA = strtotime($a['tgl_tfk']);
        $dateB = strtotime($b['tgl_tfk']);

        if ($dateA !== $dateB) {
            return $dateA - $dateB; // tanggal lama (terdahulu) di atas
        }

        // Jika tanggal sama, hitung jumlah status pending (pakai status_tfkkb tunggal)
        $pending_count_a = 0;
        $pending_count_b = 0;
        if (isset($a['status_tfkkb']) && $a['status_tfkkb'] == 'Belum Dikirim') $pending_count_a++;
        if (isset($a['status_dokumen']) && $a['status_dokumen'] == 'belum balik') $pending_count_a++;
        if (isset($a['status_tfkkf']) && $a['status_tfkkf'] == 'Belum Dikirim') $pending_count_a++;

        if (isset($b['status_tfkkb']) && $b['status_tfkkb'] == 'Belum Dikirim') $pending_count_b++;
        if (isset($b['status_dokumen']) && $b['status_dokumen'] == 'belum balik') $pending_count_b++;
        if (isset($b['status_tfkkf']) && $b['status_tfkkf'] == 'Belum Dikirim') $pending_count_b++;

        if ($pending_count_a !== $pending_count_b) {
            return $pending_count_b - $pending_count_a; // lebih banyak pending di atas
        }

        // fallback: urutkan berdasarkan created_at (lebih lama di atas)
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    // Pagination: gunakan parameter $halaman dan $maximal (default 100)
    $maximal = (int)$maximal;
    if ($maximal <= 0) $maximal = 100;
    $total_records = count($all_data);
    $total_pages = max(1, (int)ceil($total_records / $maximal));
    $halaman = (int)$halaman;
    if ($halaman < 1) $halaman = 1;
    if ($halaman > $total_pages) $halaman = $total_pages;
    $offset = ($halaman - 1) * $maximal;

    // Ambil data untuk halaman saat ini
    $rows = array_slice($all_data, $offset, $maximal);
    $totalRows = count($rows); // jumlah dalam halaman saat ini

    // Split data halaman menjadi dua kolom.
    // Jika per-halaman 100, pakai 50 / 50. Untuk per-halaman lain, bagi rata.
    $per_column_fixed = 50;
    if ($maximal === 100) {
        $left_count = min($per_column_fixed, $totalRows);
    } else {
        $left_count = (int)ceil($totalRows / 2);
    }
    $first_half = array_slice($rows, 0, $left_count);
    $second_half = array_slice($rows, $left_count);
    
    // Generate tabel kiri
    $tabel_kiri = '';
    $nomor = 1;
    // nomor mulai dari offset+1 supaya nomor global konsisten
    $nomor = $offset + 1;
    
    foreach ($first_half as $row) {
        // Status pengiriman barang (Barang Terkirim) - sekarang gunakan satu field status_tfkkb dari tabel faktur
        $status_tfkkb_val = $row['status_tfkkb'] ?? 'Belum Dikirim';
        $statusk = ($status_tfkkb_val == 'Belum Dikirim') ?
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' :
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';
        
        $statusd = ($row['status_dokumen'] == 'belum balik') ? 
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' : 
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';
        $statustf = ($row['status_tfkkf'] == 'Belum Dikirim') ?
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' : 
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';

        // Status failing: hanya treat tepat 'Belum Failing' (case-insensitive) sebagai pending/failing
        $sf_raw = isset($row['status_failing']) ? trim((string)$row['status_failing']) : '';
        $statusFailing = (strtolower($sf_raw) === 'belum failing') ?
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' :
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';

        // FP (Upload F-Pajak) - tampilkan sebelum kolom TF
       $raw_fp = strtolower(trim((string)($row['upload_f_pajak'] ?? '')));
       $fp_ok_values = ['1','true','sudah','sudah upload','uploaded','yes','ok'];
       $statusFP = in_array($raw_fp, $fp_ok_values, true) ?
          '<span class="badge badge-success"><i class="fa fa-check"></i></span>' :
          '<span class="badge badge-danger"><i class="fa fa-times"></i></span>';

        $jenisFaktur = isset($row['jenis_faktur']) ? $row['jenis_faktur'] : 'Cendo & DPE';
        $jenisBadgeClass = ($jenisFaktur == 'PIM') ? 'badge-info' : 'badge-primary';
        
        // Determine cabang value based on data source
        $cabang = ($row['source'] === 'api') ? 
            htmlspecialchars($row['nama_apl'] ?? '-') : 
            'Puri';
        
        $citoBadge = '';
        if ($row['cito'] == 'cito') {
            $citoBadge = '<span style="font-size:6px; padding:0 3px; background-color:white; color:red; border:1px solid red; border-radius:2px; margin-left:5px;">
                <i class="fa fa-flag" style="font-size:6px;"></i>
            </span>';
        }

        // Hitung durasi hari dari tgl_tfk sampai sekarang
        $waktu_days = 0;
        if (!empty($row['tgl_tfk'])) {
            try {
                $d1 = new DateTime(substr($row['tgl_tfk'],0,10));
                $d2 = new DateTime(); // sekarang
                $waktu_days = (int)$d1->diff($d2)->days;
            } catch (Exception $ex) {
                $waktu_days = 0;
            }
        }

        // Warna teks berdasarkan ambang batas: >80 merah, >60 kuning, selainnya default (hitam)
        $waktu_color = '#393939ff';
        if ($waktu_days > 80) {
            $waktu_color = '#dc3545'; // merah
        } elseif ($waktu_days > 60) {
            $waktu_color = '#ffc107'; // kuning/coklat gelap
        }
        $waktu_badge = '<span style="color: '.$waktu_color.'; font-weight:600;">' . $waktu_days . ' (hari)</span>';
        
        // Batasi maksimal 15 karakter untuk keamanan tampilan
        $displayKodeTfk = substr($row['kode_tfk'], 0, 15);
        
        $tabel_kiri .= '<tr>
            <td>' . $nomor . '</td>
            <td>' . date('d/m/y', strtotime($row['tgl_tfk'])) . '</td>
            <td>
                <span style="display:inline-flex; align-items:center;">
                    ' . htmlspecialchars($displayKodeTfk) . '
                    ' . $citoBadge . '
                </span>
            </td>
            <td>' . htmlspecialchars($row['nama_out']) . '</td>
            <td style="text-align:center;">' . $cabang . '</td>
            <td style="text-align:center;">' . $waktu_badge . '</td>
            <td style="text-align:center;">' . $statusk . '</td> <!-- Barang Terkirim (baru) -->
            <td style="text-align:center;">' . $statusd . '</td>
            <td style="text-align:center;">' . $statusFailing . '</td> <!-- Failing Dokumen (baru) -->
            <td style="text-align:center;">' . $statusFP . '</td> <!-- FP (Upload F-Pajak) -->
            <td style="text-align:center;">' . $statustf . '</td>
            <td><span class="badge ' . $jenisBadgeClass . '">' . $jenisFaktur . '</span></td>
        </tr>';
        
        $nomor++;
    }
    
    // Generate tabel kanan
    $tabel_kanan = '';
    
    foreach ($second_half as $row) {
        // Status pengiriman barang (Barang Terkirim) - gunakan field status_tfkkb dari record (dari transaksi_faktur / transaksi_faktur_pim)
        $status_tfkkb_val = $row['status_tfkkb'] ?? 'Belum Dikirim';
        $statusk = ($status_tfkkb_val == 'Belum Dikirim') ?
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' :
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';
        
        $statusd = ($row['status_dokumen'] == 'belum balik') ? 
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' : 
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';

        $statustf = ($row['status_tfkkf'] == 'Belum Dikirim') ? 
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' : 
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';

        // Status failing: hanya treat tepat 'Belum Failing' (case-insensitive) sebagai pending/failing
        $sf_raw = isset($row['status_failing']) ? trim((string)$row['status_failing']) : '';
        $statusFailing = (strtolower($sf_raw) === 'belum failing') ?
            '<span class="badge badge-danger"><i class="fa fa-times"></i></span>' :
            '<span class="badge badge-success"><i class="fa fa-check"></i></span>';

        // FP (Upload F-Pajak) - tampilkan sebelum kolom TF
       $raw_fp = strtolower(trim((string)($row['upload_f_pajak'] ?? '')));
       $fp_ok_values = ['1','true','sudah','sudah upload','uploaded','yes','ok'];
       $statusFP = in_array($raw_fp, $fp_ok_values, true) ?
          '<span class="badge badge-success"><i class="fa fa-check"></i></span>' :
          '<span class="badge badge-danger"><i class="fa fa-times"></i></span>';

        $jenisFaktur = isset($row['jenis_faktur']) ? $row['jenis_faktur'] : 'Cendo & DPE';
        $jenisBadgeClass = ($jenisFaktur == 'PIM') ? 'badge-info' : 'badge-primary';
        
        // Determine cabang value based on data source
        $cabang = ($row['source'] === 'api') ? 
            htmlspecialchars($row['nama_apl'] ?? '-') : 
            'Puri';
        
        $citoBadge = '';
        if ($row['cito'] == 'cito') {
            $citoBadge = '<span style="font-size:6px; padding:0 3px; background-color:white; color:red; border:1px solid red; border-radius:2px; margin-left:5px;">
                <i class="fa fa-flag" style="font-size:6px;"></i>
            </span>';
        }

        // Hitung durasi hari dari tgl_tfk sampai sekarang untuk tabel kanan
        $waktu_days = 0;
        if (!empty($row['tgl_tfk'])) {
            try {
                $d1 = new DateTime(substr($row['tgl_tfk'],0,10));
                $d2 = new DateTime();
                $waktu_days = (int)$d1->diff($d2)->days;
            } catch (Exception $ex) {
                $waktu_days = 0;
            }
        }

        // Warna teks berdasarkan ambang batas: >80 merah, >60 kuning, selainnya default (hitam)
        $waktu_color = '#393939ff';
        if ($waktu_days > 80) {
            $waktu_color = '#dc3545'; // merah
        } elseif ($waktu_days > 60) {
            $waktu_color = '#ffc107'; // kuning/coklat gelap
        }
        $waktu_badge = '<span style="color: '.$waktu_color.'; font-weight:600;">' . $waktu_days . ' (hari)</span>';
        
        // Batasi maksimal 15 karakter untuk keamanan tampilan
        $displayKodeTfk = substr($row['kode_tfk'], 0, 15);
        
        $tabel_kanan .= '<tr>
            <td>' . $nomor . '</td>
            <td>' . date('d/m/y', strtotime($row['tgl_tfk'])) . '</td>
            <td>
                <span style="display:inline-flex; align-items:center;">
                    ' . htmlspecialchars($displayKodeTfk) . '
                    ' . $citoBadge . '
                </span>
            </td>
            <td>' . htmlspecialchars($row['nama_out']) . '</td>
            <td style="text-align:center;">' . $cabang . '</td>
            <td style="text-align:center;">' . $waktu_badge . '</td>
            <td style="text-align:center;">' . $statusk . '</td> <!-- Barang Terkirim (baru) -->
            <td style="text-align:center;">' . $statusd . '</td>
            <td style="text-align:center;">' . $statusFailing . '</td> <!-- Failing Dokumen (baru) -->
            <td style="text-align:center;">' . $statusFP . '</td> <!-- FP (Upload F-Pajak) -->
            <td style="text-align:center;">' . $statustf . '</td>
            <td><span class="badge ' . $jenisBadgeClass . '">' . $jenisFaktur . '</span></td>
        </tr>';
        
        $nomor++;
    }
    
    // Response
    $response = [
        'tabel_kiri' => $tabel_kiri,
        'tabel_kanan' => $tabel_kanan,
        'aplikasi' => $aplikasi_names, // daftar nama_apl untuk dropdown (API cabang)
        'total_data' => $total_records,      // total semua record dalam 3 bulan
        'local_count' => $localCount,
        'api_count' => $api_count,
        'tanggal' => $tgl,
        'range_tanggal' => $date_3_months_ago . ' s/d ' . $tgl
        ,
        'page' => $halaman,
        'per_page' => $maximal,
        'total_pages' => $total_pages,
        'total_records' => $total_records
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("JSON Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'tabel_kiri' => '<tr><td colspan="13" class="text-center text-danger">Error loading data</td></tr>', // colspan disesuaikan (13 kolom sekarang)
        'tabel_kanan' => '<tr><td colspan="13" class="text-center text-danger">Error loading data</td></tr>', // colspan disesuaikan (13 kolom sekarang)
        'total_data' => 0,
        'local_count' => 0,
        'api_count' => 0,
        'tanggal' => date('Y-m-d')
    ]);
} finally {
    if (isset($conn)) {
        $base->close();
    }
}
?>