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
        $filename = "MonitoringOperasional_Pending";
        if ($periode_dari && $periode_sampai) {
            $filename .= "_" . date('(d-m-Y)', strtotime($periode_dari)) . "-" . date('(d-m-Y)', strtotime($periode_sampai));
        } elseif ($periode_dari) {
            $filename .= "_" . date('(d-m-Y)', strtotime($periode_dari)) . "-";
        } elseif ($periode_sampai) {
            $filename .= "_-" . date('(d-m-Y)', strtotime($periode_sampai));
        }
        $filename .= ".xls";
        
        header("content-disposition:attachment; filename=$filename");
    }

    // Bangun URL API dengan filter periode
    $apiUrl = $data->sistem('url_sis') . "/api/getmonitoringop_pendingall.php?halaman=1&maximal=50000";
    if ($periode_dari) $apiUrl .= "&periode_dari=" . urlencode($periode_dari);
    if ($periode_sampai) $apiUrl .= "&periode_sampai=" . urlencode($periode_sampai);
    
    error_log("Requesting data from API: " . $apiUrl);
    
    try {
        error_log("Starting API request to: $apiUrl");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
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

        error_log("API response received, size: " . strlen($response) . " bytes");

        if (empty($response)) {
            throw new Exception("API mengembalikan response kosong");
        }

        $apiData = null;
        try {
            $apiData = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $jsonEx) {
            error_log("JSON decode error: " . $jsonEx->getMessage());
            error_log("Response excerpt: " . substr($response, 0, 1000));
            throw new Exception("JSON decode error: " . $jsonEx->getMessage());
        }
        
        if (isset($apiData['success']) && $apiData['success'] === false) {
            throw new Exception("API error: " . ($apiData['message'] ?? 'Unknown error'));
        }
        
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
            throw new Exception("Tidak ada data monitoring operasional pending untuk periode ini.");
        }
        
        $rows = $apiData['data'];
        
    } catch (Exception $e) {
        throw new Exception("Gagal menghubungi API: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Error - Monitoring Operasional Pending</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .error { color: #721c24; background-color: #f8d7da; padding: 10px; border: 1px solid #f5c6cb; border-radius: 4px; margin-bottom: 20px; }
            h2 { color: #0c5460; }
        </style>
    </head>
    <body>
        <h2>Error Laporan Monitoring Operasional Pending</h2>
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
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Monitoring Operasional Pending</title>
    <style>
        /* Highlight urgent rows */
        /* remove colored backgrounds, keep classes for semantics */
        tr.urgent-row { background-color: transparent; }
        tr.cito-row { background-color: transparent; }
        
        .status-pending { color: #dc3545 !important; font-weight: 700; }
        .status-success { color: #198754 !important; font-weight: 700; }
        .badge-cito { background-color: #dc3545; color: white; font-size: 8px; padding: 2px 4px; border-radius: 2px; }
    </style>
</head>
<body>
    <table>
        <tr>
            <th colspan="10" style="font-size:18px; font-family:Arial, Helvetica, sans-serif; text-align:center;">MONITORING OPERASIONAL - STATUS PENDING</th>
        </tr>
        <tr>
            <th colspan="10" style="font-size:18px; font-family:Arial, Helvetica, sans-serif; text-align:center;">
                <?php
                    echo $data->sistem('pt_sis');
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
            <td colspan="10"></td>
        </tr>
    </table>
    <table border="1">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Faktur</th>
                <th>Outlet</th>
                <th>Durasi</th>
                <th>Barang Terkirim</th>
                <th>Dokumen Kembali</th>
                <th>Failing Dokumen</th> <!-- jika diperlukan di laporan -->
                <th>FP</th> <!-- Upload F-Pajak (baru) -->
                <th>TF</th>
                <th>Cabang</th>
                <th>Jenis Faktur</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Urutkan berdasarkan tgl_tfk ascending (terlama di atas). Jika sama, gunakan created_at.
            // Parse tgl_tfk yang tersimpan dalam format d-m-Y (atau d/m/Y) untuk pengurutan
            $parseDateToTs = function($d) {
                if (empty($d)) return PHP_INT_MAX;
                $d = trim($d);
                $formats = ['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y/m/d'];
                foreach ($formats as $fmt) {
                    $dt = DateTime::createFromFormat($fmt, substr($d,0,10));
                    if ($dt && $dt->format($fmt) === substr($d,0,10)) {
                        return $dt->getTimestamp();
                    }
                }
                $ts = strtotime($d);
                return $ts !== false ? $ts : PHP_INT_MAX;
            };

            usort($rows, function($a, $b) use ($parseDateToTs) {
                $dateA = $parseDateToTs($a['tgl_tfk'] ?? '');
                $dateB = $parseDateToTs($b['tgl_tfk'] ?? '');
                if ($dateA === $dateB) {
                    $createdA = isset($a['created_at']) && !empty($a['created_at']) ? strtotime($a['created_at']) : PHP_INT_MAX;
                    $createdB = isset($b['created_at']) && !empty($b['created_at']) ? strtotime($b['created_at']) : PHP_INT_MAX;
                    return $createdA <=> $createdB;
                }
                return $dateA <=> $dateB;
            });
            
            $no = 1;
            foreach ($rows as $row) {
                // Determine jenis faktur
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

                // Check status
                $isCito = (isset($row['cito']) && strtolower($row['cito']) === 'cito');
                
                // Count pending
                $pendingCount = 0;
                if (isset($row['status_tfkkb']) && $row['status_tfkkb'] == 'Belum Dikirim') $pendingCount++;
                if (isset($row['status_dokumen']) && $row['status_dokumen'] == 'belum balik') $pendingCount++;
                if (isset($row['status_tfkkf']) && $row['status_tfkkf'] == 'Belum Dikirim') $pendingCount++;

                $isUrgent = $pendingCount >= 2;

                // Build row class
                $trClass = '';
                if ($isCito) {
                    $trClass = ' class="cito-row"';
                } elseif ($isUrgent) {
                    $trClass = ' class="urgent-row"';
                }

                echo "<tr{$trClass}>";
                echo "<td style='text-align:center;'>{$no}</td>";

                // Tanggal
                // Pastikan tampilkan sebagai d-m-Y
                $displayDate = '-';
                if (!empty($row['tgl_tfk'])) {
                    $raw = substr(trim($row['tgl_tfk']),0,10);
                    $dt = DateTime::createFromFormat('d-m-Y', $raw) ?: DateTime::createFromFormat('d/m/Y', $raw) ?: DateTime::createFromFormat('Y-m-d', $raw) ?: false;
                    if ($dt) $displayDate = $dt->format('d-m-Y');
                    else $displayDate = htmlspecialchars($row['tgl_tfk']);
                }
                echo "<td>" . $displayDate . "</td>";

                // No Faktur with cito badge
                $kodeFaktur = htmlspecialchars($row['kode_tfk'] ?? '-');
                if ($isCito) {
                    $kodeFaktur .= ' <span class="badge-cito">CITO</span>';
                }
                echo "<td>{$kodeFaktur}</td>";

                // Outlet
                echo "<td>" . htmlspecialchars($row['nama_out'] ?? '-') . "</td>";

                // Calculate days
                $waktuDays = 0;
                if (!empty($row['tgl_tfk'])) {
                    try {
                        $raw = substr(trim($row['tgl_tfk']),0,10);
                        $d1 = DateTime::createFromFormat('d-m-Y', $raw) ?: DateTime::createFromFormat('d/m/Y', $raw) ?: DateTime::createFromFormat('Y-m-d', $raw);
                        if ($d1 instanceof DateTime) {
                            $d2 = new DateTime();
                            $waktuDays = (int)$d1->diff($d2)->days;
                        }
                    } catch (Exception $ex) {
                        $waktuDays = 0;
                    }
                }
                echo "<td style='text-align:center;'>{$waktuDays} (Hari)</td>";

                // Status columns
                $statusTfkkb = (isset($row['status_tfkkb']) && $row['status_tfkkb'] == 'Sudah Dikirim') ? 'Selesai' : 'Pending';
                $statusDokumen = (isset($row['status_dokumen']) && $row['status_dokumen'] == 'sudah balik') ? 'Selesai' : 'Pending';
                $statusTfkkf = (isset($row['status_tfkkf']) && $row['status_tfkkf'] == 'Sudah Dikirim') ? 'Selesai' : 'Pending';
                
                // Failing Dokumen (jika ada) - hanya 'belum failing' dianggap Pending
                $sf = isset($row['status_failing']) ? strtolower(trim($row['status_failing'])) : '';
                $statusFailing = ($sf === 'belum failing') ? 'Pending' : 'Selesai';
                
                // Upload F-Pajak (FP)
                $fp_raw = isset($row['upload_f_pajak']) ? strtolower(trim($row['upload_f_pajak'])) : '';
                $fp_ok = ['1','true','sudah','sudah upload','uploaded','yes','ok'];
                $statusFP = in_array($fp_raw, $fp_ok, true) ? 'Selesai' : 'Pending';

                $statusClass = function($status) {
                    return $status == 'Selesai' ? 'status-success' : 'status-pending';
                };
 
                echo "<td style='text-align:center;'><span class='" . $statusClass($statusTfkkb) . "'>{$statusTfkkb}</span></td>";
                echo "<td style='text-align:center;'><span class='" . $statusClass($statusDokumen) . "'>{$statusDokumen}</span></td>";
                echo "<td style='text-align:center;'><span class='" . $statusClass($statusFailing) . "'>{$statusFailing}</span></td>";
                echo "<td style='text-align:center;'><span class='" . $statusClass($statusFP) . "'>{$statusFP}</span></td>"; // new FP column
                echo "<td style='text-align:center;'><span class='" . $statusClass($statusTfkkf) . "'>{$statusTfkkf}</span></td>";

                // Cabang
                echo "<td style='text-align:center;'>" . htmlspecialchars($row['nama_cabang'] ?? 'Puri') . "</td>";

                // Jenis Faktur
                echo "<td style='text-align:center;'>" . htmlspecialchars($jenis) . "</td>";

                // (Status Ceklis & Status Failing kolom dihapus)
                
                echo "</tr>";
                $no++;
            }
            ?>
        </tbody>
    </table>
</body>
</html>
