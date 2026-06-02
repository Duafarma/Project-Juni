<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php
require_once('../../../config/connection/connection.php');
require_once('../../../config/connection/security.php');
require_once('../../../config/function/data.php');
require_once('../../../config/function/date.php');
$secu = new Security;
$base = new DB;
$data = new Data;
$date = new Date;
$admin = $secu->injection(@$_COOKIE['adminkuy']);
$kunci = $secu->injection(@$_COOKIE['kuncikuy']);
$secu->validadmin($admin, $kunci);
if($secu->validadmin($admin, $kunci)==false){ 
    header('location:'.$data->sistem('url_sis').'/signout'); 
} else {
    $conn = $base->open();
    $id_tfk = $secu->injection(@$_GET['id_tfk']);
    $jenis_faktur = $secu->injection(@$_GET['jenis_faktur'] ?? 'Cendo & DPE'); // Default if not provided
    $url = $secu->injection(@$_GET['url']);

    // Choose the right table based on jenis_faktur
    $tableName = ($jenis_faktur === 'PIM') ? 'transaksi_faktur_pim' : 'transaksi_faktur';

    // Get document info from database
    $read = $conn->prepare("SELECT A.kode_tfk, A.tgl_tfk, B.nama_out 
                           FROM $tableName AS A 
                           LEFT JOIN outlet AS B ON A.id_out=B.id_out 
                           WHERE A.id_tfk=:id_tfk");
    $read->bindParam(':id_tfk', $id_tfk, PDO::PARAM_STR);
    $read->execute();
    $view = $read->fetch(PDO::FETCH_ASSOC);
    
    // Check file type and handle differently
    $fileType = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
    
    // For PDFs, redirect directly to the PDF URL
    if (strtolower($fileType) == 'pdf') {
        header('Location: ' . $url);
        exit;
    }

    // Only continue with HTML display for non-PDF files
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Print Faktur Pajak - <?php echo $view['kode_tfk']; ?></title>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color-adjust: exact;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .invoice-info {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .document-container {
            width: 100%;
            height: calc(100vh - 200px);
            min-height: 500px;
        }
        .document-frame {
            width: 100%;
            height: 100%;
            border: 1px solid #ddd;
        }
        .print-button {
            display: inline-block;
            margin: 10px 0;
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .print-button:hover {
            background-color: #0069d9;
        }
        /* Ensure images print in full RGB color */
        img {
            color-rendering: optimizeQuality;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            .document-container {
                height: auto;
            }
            /* Force color printing */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            /* Specifically target images for RGB color space */
            img {
                filter: none !important;
                color-rendering: optimizeQuality;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>Faktur Pajak</h2>
    </div>
    
    <div class="invoice-info">
        <table width="100%">
            <tr>
                <td width="20%"><strong>No. Faktur:</strong></td>
                <td><?php echo $view['kode_tfk']; ?></td>
            </tr>
            <tr>
                <td><strong>Tanggal:</strong></td>
                <td><?php echo $date->tgl_indo($view['tgl_tfk']); ?></td>
            </tr>
            <tr>
                <td><strong>Outlet:</strong></td>
                <td><?php echo $view['nama_out']; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="no-print" style="text-align: center; margin-bottom: 10px;">
        <button class="print-button" onclick="window.print()"><i class="fa fa-print"></i> Print Dokumen</button>
    </div>
    
    <div class="document-container">
        <?php 
        // Now only handling non-PDF files
        if (in_array(strtolower($fileType), ['jpg', 'jpeg', 'png', 'gif'])) {
            // For images, use img tag with RGB color space preserved
            echo '<img src="'.$url.'" style="max-width:100%; max-height:100%;" 
                  class="rgb-image" alt="Faktur Pajak Image" />';
        } else {
            // For other file types, provide direct link
            echo '<div style="text-align:center; padding:50px;">
                    <p>Dokumen tidak dapat ditampilkan secara langsung.</p>
                    <a href="'.$url.'" target="_blank" class="print-button">Buka Dokumen</a>
                  </div>';
        }
        ?>
    </div>
    
    <script type="text/javascript">
        // Auto-print when page loads (only for images, PDFs handled separately)
        window.onload = function() {
            // For images, ensure RGB color mode before printing
            var images = document.getElementsByTagName('img');
            for (var i = 0; i < images.length; i++) {
                images[i].style.colorRendering = 'optimizeQuality';
            }
            
            setTimeout(function() { 
                window.print(); 
            }, 1500);
        };
    </script>
</body>
</html>
<?php 
}
?>
</html>
