<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
ini_set('memory_limit', '512M'); // Increase memory limit for large datasets
ini_set('max_execution_time', 600); // Allow script to run for 10 minutes
set_time_limit(600); // Same as above, for redundancy

try {
    require_once('../../../config/connection/connection.php');
    require_once('../../../config/connection/security.php');
    require_once('../../../config/function/data.php');

    $base = new DB();
    $secu = new Security;
    $data = new Data;

    // Ambil filter periode dari GET (jika ada)
    $periode_dari = isset($_GET['periode_dari']) ? $_GET['periode_dari'] : '';
    $periode_sampai = isset($_GET['periode_sampai']) ? $_GET['periode_sampai'] : '';
    
    if (!headers_sent()) {
        header("Content-Type: application/force-download");
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Laporan"); 
        
        // Create dynamic filename with date range
        $filename = "FakturPajak";
        if ($periode_dari && $periode_sampai) {
            $filename .= "" . date('(d-m-Y)', strtotime($periode_dari)) . "-" . date('(d-m-Y)', strtotime($periode_sampai));
        } elseif ($periode_dari) {
            $filename .= "" . date('(d-m-Y)', strtotime($periode_dari)) . "-";
        } elseif ($periode_sampai) {
            $filename .= "-" . date('(d-m-Y)', strtotime($periode_sampai));
        }
        $filename .= ".xls";
        
        header("content-disposition:attachment; filename=$filename");
    }

    // Bangun URL API dengan filter periode dan setting maksimal data yang lebih besar
    $apiUrl = $data->sistem('url_sis') . "/api/getfakturpajakall.php?halaman=1&maximal=50000&status_dokumen=" . urlencode('sudah balik');
    if ($periode_dari) $apiUrl .= "&periode_dari=" . urlencode($periode_dari);
    if ($periode_sampai) $apiUrl .= "&periode_sampai=" . urlencode($periode_sampai);
    
    // Debug logging
    error_log("Requesting data from API: " . $apiUrl);
    
    // Tambahkan timeout lebih lama untuk mengakomodasi data yang banyak
    $context = stream_context_create([
        'http' => [
            'timeout' => 600, // Increased timeout to 10 minutes
            'ignore_errors' => true // Don't fail on HTTP errors
        ]
    ]);
    
    // Use try-catch for API fetch to handle potential connection issues
    try {
        // Additional debug info
        error_log("Starting API request to: $apiUrl");

        // Use CURL instead of file_get_contents for better error handling
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600); // 10 minutes timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            error_log("cURL error: $curlError");
            throw new Exception("Koneksi API gagal: $curlError");
        }

        if ($httpCode != 200) {
            error_log("HTTP error $httpCode. Response: " . substr($response, 0, 1000));
            throw new Exception("API mengembalikan HTTP code: $httpCode");
        }

        // Log response size
        error_log("API response received, size: " . strlen($response) . " bytes");

        // Check for empty response
        if (empty($response)) {
            throw new Exception("API mengembalikan response kosong");
        }

        // Try to decode with additional error handling
        $apiData = null;
        try {
            $apiData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $jsonEx) {
            error_log("JSON decode error: " . $jsonEx->getMessage());
            error_log("Response excerpt: " . substr($response, 0, 1000));
            throw new Exception("JSON decode error: " . $jsonEx->getMessage());
        }
        
        // Check for API errors
        if (isset($apiData['success']) && $apiData['success'] === false) {
            throw new Exception("API error: " . ($apiData['message'] ?? 'Unknown error'));
        }
        
        // Validate API response structure
        if (!isset($apiData['data'])) {
            error_log("API response missing 'data' key");
            error_log("API response keys: " . implode(', ', array_keys($apiData)));
            throw new Exception("Data dari API tidak valid: tidak ada key 'data'");
        }
        
        if (!is_array($apiData['data'])) {
            error_log("API 'data' is not an array, type: " . gettype($apiData['data']));
            throw new Exception("Data dari API tidak valid: 'data' bukan array");
        }
        
        if (empty($apiData['data'])) {
            throw new Exception("Tidak ada data faktur pajak yang ditemukan untuk periode ini.");
        }
        
        $rows = $apiData['data'];
        $namaCabang = "Semua Cabang";
    } catch (Exception $e) {
        // Re-throw with more context
        throw new Exception("Gagal menghubungi API: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Show friendly error message instead of crashing
    ?>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Error - Monitoring Faktur Pajak</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .error { color: #721c24; background-color: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
            h2 { color: #0c5460; }
        </style>
    </head>
    <body>
        <h2>Error Laporan Monitoring Faktur Pajak</h2>
        <div class="error">
            <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
        </div>
        <div>
            <p>Kemungkinan penyebab:</p>
            <ul>
                <li>Server API tidak dapat dijangkau</li>
                <li>Terlalu banyak data yang diminta untuk periode yang dipilih</li> 
                <li>Salah satu cabang mengalami masalah koneksi</li>
                <li>Tidak ada data untuk filter yang dipilih</li>
            </ul>
            <p>Saran:</p>
            <ul>
                <li>Coba dengan rentang tanggal yang lebih pendek</li>
                <li>Periksa koneksi jaringan antar cabang</li>
                <li>Coba lagi nanti</li>
            </ul>
        </div>
    </body>
    <?php
    exit; // Stop processing
}
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Monitoring Faktur Pajak</title>
    <style>
        /* Highlight urgent rows */
        tr.urgent-row { background-color: #fff3cd; } /* light orange */

        /* 'nanti' => WARNA ORANGE pada Kode Faktur (HANYA kode) */
        .nanti-kode { color: #ff8c00 !important; font-weight:700; }

        /* jika ingin nomor juga berwarna ketika kode kosong, gunakan class ini */
        .nanti-number { color: #ff8c00 !important; font-weight:700; }

        /* 'Revisi' kode warna biru */
        .revisi-number { color: #007bff !important; font-weight:700; }

        /* Pastikan tidak ada rule .nanti-number lain yang menimpa */
    </style>
</head>
<body>
    <table>
        <tr>
            <th colspan="11" style="font-size:18px; font-family:Arial, Helvetica, sans-serif; text-align:center;">MONITORING FAKTUR PAJAK</th>
        </tr>
        <tr>
            <th colspan="11" style="font-size:18px; font-family:Arial, Helvetica, sans-serif; text-align:center;">
                <?php
                    echo $data->sistem('pt_sis');
                    // Tampilkan periode di bawah nama perusahaan jika ada
                    if ($periode_dari || $periode_sampai) {
                        echo "<br><span style='font-size:17px;'>";
                        echo "Periode: ";
                        if ($periode_dari && $periode_sampai) {
                            echo date('d-m-Y', strtotime($periode_dari)) . " s/d " . date('d-m-Y', strtotime($periode_sampai));
                        } elseif ($periode_dari) {
                            echo "Mulai " . date('d-m-Y', strtotime($periode_dari));
                        } elseif ($periode_sampai) {
                            echo "Sampai " . date('d-m-Y', strtotime($periode_sampai));
                        }
                        echo "</span>";
                    }
                ?>
            </th>
        </tr>
        <tr>
            <td colspan="11"></td>
        </tr>
    </table>
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Faktur</th>
                <th>Tanggal Faktur</th>
                <th>Cabang</th>
                <th>Outlet</th>
                <th>Subtotal</th>
                <th>PPN</th>
                <th>Total</th>
                <th>Status Faktur Pajak</th>
                <th>Upload Faktur Pajak</th>
                <th>Jenis Faktur</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Urutkan baris: prioritas urgent dulu, lalu bulan termuda di atas,
            // dan dalam bulan yang sama tanggal tertua dulu
            usort($rows, function($a, $b) {
                $statusA = isset($a['status_urgent']) ? strtolower(trim($a['status_urgent'])) : '';
                $statusB = isset($b['status_urgent']) ? strtolower(trim($b['status_urgent'])) : '';

                $isUrgentA = ($statusA === 'urgent') ? 1 : 0;
                $isUrgentB = ($statusB === 'urgent') ? 1 : 0;

                // Jika salah satu urgent, urgent harus di atas
                if ($isUrgentA !== $isUrgentB) {
                    return ($isUrgentA > $isUrgentB) ? -1 : 1; // urgent first
                }

                // Lanjutkan dengan perbandingan tanggal apabila status sama (keduanya urgent atau bukan)
                $dateA = strtotime($a['tgl_tfk'] ?? '0');
                $dateB = strtotime($b['tgl_tfk'] ?? '0');

                // Bandingkan tahun-bulan (bulan termuda di atas)
                $yearMonthA = date('Y-m', $dateA);
                $yearMonthB = date('Y-m', $dateB);
                if ($yearMonthA != $yearMonthB) {
                    return strtotime($yearMonthB) - strtotime($yearMonthA); // Bulan termuda dulu
                }

                // Jika bulan sama, bandingkan hari (hari tertua dulu)
                return $dateA - $dateB;
            });
            
            $no = 1;
            foreach ($rows as $row) {
                // Pastikan jenis faktur selalu terisi dengan benar
                $jenis = '';
                if (isset($row['jenis']) && !empty($row['jenis'])) {
                    $jenis = $row['jenis'];
                } elseif (isset($row['kode_tfk'])) {
                    if (strpos(strtoupper($row['kode_tfk']), 'PIM') !== false) {
                        $jenis = 'PIM';
                    } else {
                        $jenis = 'Cendo & DPE';
                    }
                } else {
                    $jenis = 'Cendo & DPE';
                }

                // Deteksi status urgent / nanti / revisi (case-insensitive)
                $statusUrgentRaw = isset($row['status_urgent']) ? trim($row['status_urgent']) : (isset($row['status_urgent']) ? $row['status_urgent'] : '');
                $statusTfkRaw = isset($row['status_tfk']) ? trim($row['status_tfk']) : (isset($row['status_tfk']) ? $row['status_tfk'] : '');

                $isUrgent = $statusUrgentRaw !== '' && strtolower($statusUrgentRaw) === 'urgent';
                $isNanti  = $statusUrgentRaw !== '' && strtolower($statusUrgentRaw) === 'nanti';
                $isRevisi = $statusTfkRaw !== '' && strtolower($statusTfkRaw) === 'revisi';

                // Build row start with class for urgent
                $trClass = $isUrgent ? ' class="urgent-row"' : '';

                echo "<tr{$trClass}>";

                // No (tampil normal, tapi beri warna orange jika 'nanti' dan kode kosong)
                $noDisplay = $no;
                // Jika status = 'nanti' dan kode kosong maka warnakan nomor juga
                $kodeIsEmpty = empty($row['kode_tfk']) || trim($row['kode_tfk']) === '';
                if ($isNanti && $kodeIsEmpty) {
                    echo "<td style='text-align:center;'><span class='nanti-number'>{$noDisplay}</span></td>";
                } else {
                    echo "<td style='text-align:center;'>{$noDisplay}</td>";
                }

                // Kode Faktur: jika status_tfk = Revisi => .revisi-number, jika status_urgent = 'nanti' => .nanti-kode (ORANGE)
                $kodeDisplay = htmlspecialchars($row['kode_tfk'] ?? '-');
                if ($isRevisi) {
                    echo "<td><span class='revisi-number'>{$kodeDisplay}</span></td>";
                } else if ($isNanti) {
                    echo "<td><span class='nanti-kode'>{$kodeDisplay}</span></td>";
                } else {
                    echo "<td>{$kodeDisplay}</td>";
                }

                // Tanggal, Cabang, Outlet, Subtotal, PPN, Total, Status, Upload, Jenis
                echo "<td>" . htmlspecialchars($row['tgl_tfk'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_cabang'] ?? '-') . "</td>";
                echo "<td>" . htmlspecialchars($row['nama_out'] ?? '-') . "</td>";
                echo "<td style='text-align:right;'>Rp. " . htmlspecialchars($row['subtot_tfk'] ?? '-') . "</td>";
                echo "<td style='text-align:right;'>Rp. " . htmlspecialchars($row['ppn_tfk'] ?? '-') . "</td>";
                echo "<td style='text-align:right;'>Rp. " . htmlspecialchars($row['total_tfk'] ?? '-') . "</td>";
                echo "<td style='text-align:center;'>" . htmlspecialchars($row['status_f_pajak'] ?? '-') . "</td>";
                echo "<td style='text-align:center;'>" . htmlspecialchars($row['upload_f_pajak'] ?? '-') . "</td>";
                echo "<td style='text-align:center;'>" . htmlspecialchars($jenis) . "</td>";
                echo "</tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>
</body>
</html>
