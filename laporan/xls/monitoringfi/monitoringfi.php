<?php
// Set a higher execution time limit at the beginning of the script
set_time_limit(600); // 10 minutes

try {
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');
    require_once('../../../config/function/date.php');

    $base = new DB();
    $secu = new Security;
    $data = new Data;
    $date = new Date;
    $conn = $base->open();
    $sistem = $data->sistem('url_sis');

    // Access data
    $admin = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci = $secu->injection(@$_COOKIE['kuncikuy']);
    $valid = $secu->validadmin($admin, $kunci);

    // Redirect if not logged in
    if ($valid == false) {
        header("Location: " . $sistem . "/signout");
        exit;
    }

    // Get parameters from URL
    $periode_dari = $secu->injection(@$_GET['periode_dari']);
    $periode_sampai = $secu->injection(@$_GET['periode_sampai']);
    $id_apl = $secu->injection(@$_GET['id_apl']);
    $id_out = $secu->injection(@$_GET['id_out']);

    // Default to current year if no period specified
    if (empty($periode_dari) && empty($periode_sampai)) {
        $periode_dari = date('Y') . '-01-01';
        $periode_sampai = date('Y-m-d');
    }

    // Helper function to format dates to DD/MM/YY
    function formatDateDDMMYY($dateString) {
        if (empty($dateString) || $dateString === '-' || $dateString === null) {
            return '-';
        }
        try {
            $date = new DateTime($dateString);
            return $date->format('d/m/y');
        } catch (Exception $e) {
            return '-';
        }
    }

    // NEW: helper untuk output tanggal yang Excel akan kenali.
    // Mengembalikan format ISO (Y-m-d) sehingga Excel mem-parsing sebagai tanggal,
    // dan kita menggunakan style mso-number-format pada <td> untuk menampilkan dd/mm/yy.
    function formatDateForExcel($dateString) {
        if (empty($dateString) || $dateString === '-' || $dateString === null) {
            return '-';
        }
        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d'); // Excel-friendly input
        } catch (Exception $e) {
            return '-';
        }
    }

    // Set Excel download headers
    if (!headers_sent()) {
        header("Content-Type: application/force-download");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Laporan"); 
        
        // Create dynamic filename with date range
        $filename = "Laporan_Finance_AR";
        
        // Add branch name to filename if specific branch selected
        if ($id_apl && $id_apl != 'All' && $id_apl != 'all_cabang') {
            // Get branch name
            $stmtBranch = $conn->prepare("SELECT nama_apl FROM aplikasi WHERE id_apl = :id_apl");
            $stmtBranch->bindParam(':id_apl', $id_apl);
            $stmtBranch->execute();
            $branchInfo = $stmtBranch->fetch(PDO::FETCH_ASSOC);
            
            if ($branchInfo) {
                $branchName = preg_replace('/[^a-zA-Z0-9]/', '_', $branchInfo['nama_apl']);
                $filename .= "_" . $branchName;
            }
        }
        
        if ($periode_dari && $periode_sampai) {
            $filename .= "_" . date('d-m-y', strtotime($periode_dari)) . "_" . date('d-m-y', strtotime($periode_sampai));
        } elseif ($periode_dari) {
            $filename .= "_from_" . date('d-m-y', strtotime($periode_dari));
        } elseif ($periode_sampai) {
            $filename .= "_to_" . date('d-m-y', strtotime($periode_sampai));
        }
        $filename .= ".xls";
        
        header("content-disposition:attachment; filename=$filename");
    }

    // Build API URL with filter parameters and higher data limit
    $apiUrl = $data->sistem('url_sis') . "/api/getFakturArAll.php?halaman=1&maximal=50000&include_pim=1";
    if ($periode_dari) $apiUrl .= "&periode_dari=" . urlencode($periode_dari);
    if ($periode_sampai) $apiUrl .= "&periode_sampai=" . urlencode($periode_sampai);
    if ($id_out && $id_out != 'All') $apiUrl .= "&id_out=" . urlencode($id_out);

    // Always pass id_apl parameter
    if ($id_apl) $apiUrl .= "&id_apl=" . urlencode($id_apl);
    
    // Generate encryption token for API security
    $tgl = date('Y-m-d');
    $source = $data->self_apl();
    $encrypt = md5($tgl . "#" . $source['key_apl']);
    $apiUrl .= "&encrypt=" . $encrypt;
    
    // Add longer timeout for handling large data sets
    $context = stream_context_create([
        'http' => [
            'timeout' => 600 // 10-minute timeout
        ]
    ]);
    
    // Fetch data from API using cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10-minute timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("Error mengambil data: " . $curlError);
    }

    if ($httpCode != 200) {
        throw new Exception("HTTP Error: " . $httpCode . " - " . substr($response, 0, 200));
    }

    $apiData = json_decode($response, true);

    // Check for JSON decode errors
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decoding JSON response: " . json_last_error_msg());
    }

    if (!isset($apiData['data']) || !is_array($apiData['data'])) {
        if (isset($apiData['message'])) {
            throw new Exception("Error dari API: " . $apiData['message']);
        } else {
            throw new Exception("Data dari API tidak valid atau kosong. Response: " . substr($response, 0, 200));
        }
    }

    $rows = $apiData['data'];
    $namaCabang = $source['nama_apl']; // Default to system branch
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" 
      xmlns:o="urn:schemas-microsoft-com:office:office" 
      xmlns:x="urn:schemas-microsoft-com:office:excel">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Laporan Finance AR</title>
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Laporan Finance AR</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:FitToPage/>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    <style type="text/css">
        .urgent { background-color: #FFF8ED; font-weight: bold; }
        
        /* Use shorthand notation for column */
        td.no-col, th.no-col {
            mso-style-parent:style0;
            mso-number-format:General;
            mso-column-width:8pt; /* Very narrow */
            width:8pt;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <th colspan="26" style="font-size:18px; font-family:Arial, Helvetica, sans-serif; text-align:center;">LAPORAN FINANCE AR</th>
        </tr>
        <tr>
            <th colspan="26" style="font-size:18px; font-family:Arial, Helvetica, sans-serif; text-align:center;">
                <?php
                    echo $data->sistem('pt_sis');
                    echo "<br><span style='font-size:18px;'>";
                    echo "Periode: ";
                    if ($periode_dari && $periode_sampai) {
                        echo formatDateDDMMYY($periode_dari) . " s/d " . formatDateDDMMYY($periode_sampai);
                    } elseif ($periode_dari) {
                        echo "Dari " . formatDateDDMMYY($periode_dari);
                    } elseif ($periode_sampai) {
                        echo "Sampai " . formatDateDDMMYY($periode_sampai);
                    }
                    echo "</span>";
                ?>
            </th>
        </tr>
        <tr><td colspan="26"></td></tr>
    </table>

    <table border="1" style="border-collapse: collapse;">
        <colgroup>
            <!-- Define widths using explicit colgroup -->
            <col style="width:8pt; mso-width-source:userset; mso-width-alt:292;"/> <!-- NO: very narrow -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- CABANG -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- JENIS FAKTUR -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- NAMA OUTLET -->
            <col style="width:150pt; mso-width-source:userset; mso-width-alt:5485;"/> <!-- NOMOR FAKTUR / OUTLET -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TGL FAKTUR -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TOTAL FAKTUR -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- KODE OUTLET -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- NO PO -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TANGGAL PO -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- PENGIRIMAN BARANG -->
            <!-- swapped: place DOKUMEN BALIK before DOKUMEN FILING -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- DOKUMEN BALIK (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- DOKUMEN FILING (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- FP (upload f pajak) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- PEMBERKASAN (baru) -->
            <!-- TF column removed -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- SERAH TERIMA FAILING (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- SERAH TERIMA FAKTUR PAJEM (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TANGGAL FAKTUR FINANCE (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- SERAH TERIMA FAKTUR PEMBERKASAN (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- STATUS PENGIRIMAN BARANG -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- STATUS PEMBAYARAN -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TANGGAL SELESAI PEMBERKASAN (SUDAH TTD) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- JADWAL TF (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- ADMIN (baru) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- RUTE TUKAR FAKTUR -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- SELESAI TUKAR FAKTUR (baru, sama tf_created_at) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TANGGAL TUKAR FAKTUR (baru, sama tf_created_at) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TF BALIK (TANGGAL) -->
            <col style="width:120pt; mso-width-source:userset; mso-width-alt:4380;"/> <!-- TF BALIK (CREATED_AT) -->
            <col style="width:90pt; mso-width-source:userset; mso-width-alt:3291;"/> <!-- TOP ODI (baru) -->
        </colgroup>

        <thead>
            <tr style="background-color: #EDEDED; font-weight: bold;">
                <th class="no-col" style="text-align:center;">NO</th>
                <th style="text-align:center;">CABANG</th>
                <th style="text-align:center;">JENIS FAKTUR</th>
                <th style="text-align:center;">NAMA OUTLET</th>
                <th style="text-align:center;">NOMOR FAKTUR</th>
                <th style="text-align:center;">TGL FAKTUR</th>
                <th style="text-align:center;">TOTAL FAKTUR</th>
                <th style="text-align:center;">KODE OUTLET</th>
                <th style="text-align:center;">NO PO</th>
                <th style="text-align:center;">TANGGAL PO</th>
                <th style="text-align:center;">PENGIRIMAN BARANG</th> <!-- new column -->
                <!-- swapped: DOKUMEN BALIK before DOKUMEN FILING -->
                <th style="text-align:center;">DOKUMEN BALIK</th> <!-- new header -->
                <th style="text-align:center;">DOKUMEN FILING</th> <!-- new header -->
                <!-- MOVED: SERAH TERIMA FAKTUR FILLING now before FP -->
                <th style="text-align:center;">SERAH TERIMA FAKTUR FILLING</th> <!-- moved -->
                <th style="text-align:center;">FP</th> <!-- new header -->
                <!-- PEMBERKASAN moved after SERAH TERIMA FAKTUR & FP -->
                <th style="text-align:center;">SERAH TERIMA FAKTUR & FP</th> <!-- added header after filling -->
                <th style="text-align:center;">PEMBERKASAN</th> <!-- moved here -->
                <th style="text-align:center;">TANGGAL BUAT KWITANSI</th> <!-- NEW: tanggal_faktur_finance -->
                <th style="text-align:center;">TANGGAL SELESAI PEMBERKASAN (SUDAH TTD)</th> <!-- new header -->
                <th style="text-align:center;">INPUT JADWAL TUKAR FAKTUR</th> <!-- inserted after tanggal selesai pemberkasan -->
                <th style="text-align:center;">RUTE TUKAR FAKTUR</th> <!-- added admin column -->
                <th style="text-align:center;">SELESAI TUKAR FAKTUR</th>
                <th style="text-align:center;">TANGGAL TUKAR FAKTUR</th>
                <th style="text-align:center;">TANGGAL DOKUMEN BALIK</th>
                <th style="text-align:center;">TANGGAL DOK INPUT</th>
                <!-- removed: STATUS DOKUMEN BALIK, STATUS F. PAJAK, STATUS TF -->
                <!--<th style="text-align:center;">STATUS PENGIRIMAN BARANG</th>-->
                <th style="text-align:center;">STATUS PEMBAYARAN</th>
                <th style="text-align:center;">TOP ODI</th>
            </tr>
        </thead>

        <tbody>
            <?php
            $no = 1;
            
            // DEBUG: Pastikan semua baris memiliki data yang diperlukan
            foreach ($rows as &$row) {
                // Tambahkan nilai default jika field tidak ada atau kosong
                if (!isset($row['po_tfk']) || empty($row['po_tfk'])) $row['po_tfk'] = '-';
                if (!isset($row['tglpo_tfk']) || empty($row['tglpo_tfk'])) $row['tglpo_tfk'] = '-';
                if (!isset($row['tanggal_faktur_finance']) || empty($row['tanggal_faktur_finance'])) $row['tanggal_faktur_finance'] = '-'; // <-- default baru
                if (!isset($row['tgl_tfkkb']) || empty($row['tgl_tfkkb'])) $row['tgl_tfkkb'] = '-'; // <-- default for delivery date
                if (!isset($row['dokumen_failing_created_at']) || empty($row['dokumen_failing_created_at'])) $row['dokumen_failing_created_at'] = '-'; // <-- new default
                if (!isset($row['dokumen_balik_created_at']) || empty($row['dokumen_balik_created_at'])) $row['dokumen_balik_created_at'] = '-'; // <-- new default
                if (!isset($row['fp_created_at']) || empty($row['fp_created_at'])) $row['fp_created_at'] = '-'; // <-- new default for FP
                if (!isset($row['pemberkasan_created_at']) || empty($row['pemberkasan_created_at'])) $row['pemberkasan_created_at'] = '-'; // <-- new default for Pemberkasan
                if (!isset($row['tf_created_at']) || empty($row['tf_created_at'])) $row['tf_created_at'] = '-'; // <-- new default for TF
                if (!isset($row['jadwal_tf_tanggal']) || empty($row['jadwal_tf_tanggal'])) $row['jadwal_tf_tanggal'] = '-'; // <-- new default for Jadwal TF
                if (!isset($row['adminz']) || empty($row['adminz'])) $row['adminz'] = '-'; // <-- default for admin name
                if (!isset($row['dokumen_tf_balik_tanggal']) || empty($row['dokumen_tf_balik_tanggal'])) $row['dokumen_tf_balik_tanggal'] = '-';
                if (!isset($row['dokumen_tf_balik_detail_created_at']) || empty($row['dokumen_tf_balik_detail_created_at'])) $row['dokumen_tf_balik_detail_created_at'] = '-';
                if (!isset($row['serah_terima_failing_created_at']) || empty($row['serah_terima_failing_created_at'])) $row['serah_terima_failing_created_at'] = '-'; // <-- new default for Serah Terima Failing
                if (!isset($row['serah_terima_faktur_pajak_created_at']) || empty($row['serah_terima_faktur_pajak_created_at'])) $row['serah_terima_faktur_pajak_created_at'] = '-'; // <-- new default for Serah Terima Faktur Pajak
                if (!isset($row['serah_terima_faktur_pemberkasan_created_at']) || empty($row['serah_terima_faktur_pemberkasan_created_at'])) $row['serah_terima_faktur_pemberkasan_created_at'] = '-'; // <-- new default for Serah Terima Faktur Pemberkasan
                if (!isset($row['status_tfkkb']) || empty($row['status_tfkkb'])) $row['status_tfkkb'] = '-';
                if (!isset($row['status_tfk']) || empty($row['status_tfk'])) $row['status_tfk'] = '-';
                if (!isset($row['top_odi']) || $row['top_odi'] === '' || $row['top_odi'] === null) $row['top_odi'] = '-';
            }
            
            // Sort data by custom logic: urgent first, then year/month descending, day ascending
            usort($rows, function($a, $b) {
                // Keep urgent items at the top
                $isUrgentA = isset($a['urgent_flag']) && $a['urgent_flag'] === true;
                $isUrgentB = isset($b['urgent_flag']) && $b['urgent_flag'] === true;
                
                if ($isUrgentA && !$isUrgentB) return -1;
                if (!$isUrgentA && $isUrgentB) return 1;
                
                // Extract date components
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
            
            foreach ($rows as $row) {
                $isUrgent = isset($row['urgent_flag']) && $row['urgent_flag'] === true;
                $rowClass = $isUrgent ? ' class="urgent"' : '';
                
                // Get branch name from API data or use default system branch
                $branchName = isset($row['nama_cabang']) && !empty($row['nama_cabang']) 
                    ? $row['nama_cabang'] : $namaCabang;
                    
                // Default jenis_faktur to "Cendo & DPE" if not provided
                $jenisFaktur = isset($row['jenis_faktur']) && !empty($row['jenis_faktur'])
                    ? $row['jenis_faktur'] : 'Cendo & DPE';
            ?>
            <tr<?php echo $rowClass; ?>>
                <td class="no-col" align="center"><?php echo $no; ?></td>
                <td><?php echo $branchName; ?></td>
                <td><?php echo $jenisFaktur; ?></td>
                <td><?php echo $row['nama_out']; ?></td>
                <td>
                    <?php
                    $kode = isset($row['kode_tfk']) ? $row['kode_tfk'] : '-';
                    $status = isset($row['status_tfk']) ? strtolower(trim($row['status_tfk'])) : '';
                    if ($status === 'revisi') {
                        echo '<span style="color:#007bff;font-weight:700;">' . htmlspecialchars($kode) . '</span>';
                    } else {
                        echo htmlspecialchars($kode);
                    }
                    ?>
                </td>
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['tgl_tfk']); ?></td>
                <td align="right"><?php echo str_replace('.', '', $row['total_tfk']); ?></td>
                <td align="center"><?php echo $row['kode_rs']; ?></td>
                <td align="center"><?php echo $row['po_tfk']; ?></td>
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['tglpo_tfk']); ?></td>

                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['tgl_tfkkb']); ?></td>
                <!-- swapped: Dokumen Balik first, then Dokumen Filing -->
                <!-- new Dokumen Balik cell -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['dokumen_balik_created_at']); ?></td>
                <!-- new Dokumen Filing cell -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['dokumen_failing_created_at']); ?></td>
                <!-- MOVED: Serah Terima Faktur Filling (sebelum FP) -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['serah_terima_failing_created_at']); ?></td>
                <!-- FP cell -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['fp_created_at']); ?></td>
                <!-- SERAH TERIMA FAKTUR & FP -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['serah_terima_faktur_pajak_created_at']); ?></td>
                <!-- MOVED: PEMBERKASAN (now after SERAH TERIMA FAKTUR & FP) -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['pemberkasan_created_at']); ?></td>
                <!-- NEW: Tanggal Faktur dari Finance -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['tanggal_faktur_finance']); ?></td>
                <!-- new Tanggal Selesai Pemberkasan cell -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['serah_terima_faktur_pemberkasan_created_at']); ?></td>
                <!-- new Jadwal TF cell -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['jadwal_tf_tanggal']); ?></td>
                <!-- new Admin cell -->
                <td align="center">
                    <?php
                        if (isset($row['adminz']) && $row['adminz'] !== '-' && !empty($row['adminz'])) {
                            echo htmlspecialchars($row['adminz']);
                        } else {
                            echo '-';
                        }
                    ?>
                </td>
                <!-- NEW: SELESAI TUKAR FAKTUR (sama dengan tf_created_at) -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['tf_created_at']); ?></td>
                <!-- NEW: TANGGAL TUKAR FAKTUR (sama dengan tf_created_at) -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['tf_created_at']); ?></td>
                <!-- new Dokumen TF Balik - tanggal -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['dokumen_tf_balik_tanggal']); ?></td>
                <!-- new Dokumen TF Balik - created_at -->
                <td align="center" style="mso-number-format:'dd\\/mm\\/yy'"><?php echo formatDateForExcel($row['dokumen_tf_balik_detail_created_at']); ?></td>
                <td align="center"><?php echo $row['status_tfk']; ?></td>
                <td align="center"><?php echo htmlspecialchars((string)$row['top_odi']); ?></td>
             </tr>
            <?php
                $no++;
            }
            ?>
        </tbody>
    </table>
</body>
</html>