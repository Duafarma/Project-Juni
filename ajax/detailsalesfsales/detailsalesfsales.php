<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    
    $secu    = new Security;
    $base    = new DB;
    $data    = new Data;
    
    $sistem  = $data->sistem('url_sis');
    $admin   = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci   = $secu->injection(@$_COOKIE['kuncikuy']);
    $kode    = $secu->injection(@$_POST['x']);
    
    // Debug logging
    error_log("LOAD ITEMS DEBUG - Received code: " . $kode);
    error_log("LOAD ITEMS DEBUG - POST data: " . json_encode($_POST));
    
    // Validasi admin (optional untuk AJAX endpoint)
    if (empty($kode)) {
        error_log("LOAD ITEMS ERROR - No code received");
        echo json_encode(array("error" => "Kode faktur tidak ditemukan"));
        exit;
    }
    
    $conn    = $base->open();
    $nomor   = 1;
    $tabel   = '';
    $total   = 0;
    
    // Membaca data faktur detail berdasarkan kode faktur
    $master = $conn->prepare("SELECT A.id_tfd, A.id_psd, A.id_pro, A.jumlah_tfd, A.harga_tfd, A.diskon_tfd, A.total_tfd, 
                              B.nama_pro, B.berat_pro, B.kode_pro, 
                              C.nama_kpr, 
                              D.nama_spr,
                              E.no_bcode, E.tgl_expired, E.gudang, E.sisa_psd,
                              F.persen_pds
                              FROM transaksi_fakturdetail AS A 
                              LEFT JOIN produk AS B ON A.id_pro=B.id_pro 
                              LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr 
                              LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr 
                              LEFT JOIN produk_stokdetail AS E ON A.id_psd=E.id_psd
                              LEFT JOIN produk_diskon AS F ON B.id_pro=F.id_pro AND F.id_out = (SELECT id_out FROM transaksi_faktur WHERE id_tfk=:kode)
                              WHERE A.id_tfk=:kode 
                              ORDER BY A.id_tfd ASC");
    
    if (!$master) {
        error_log("LOAD ITEMS ERROR - Failed to prepare query: " . implode(", ", $conn->errorInfo()));
        echo json_encode(array("error" => "Database error"));
        exit;
    }
    
    $master->bindParam(':kode', $kode, PDO::PARAM_STR);
    
    try {
        $master->execute();
        
        // Debug logging
        $rowCount = $master->rowCount();
        error_log("LOAD ITEMS DEBUG - Query executed. Row count: " . $rowCount);
    } catch (Exception $e) {
        error_log("LOAD ITEMS ERROR - Query execution failed: " . $e->getMessage());
        echo json_encode(array("error" => "Query failed: " . $e->getMessage()));
        exit;
    }
    
    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        $total += $hasil['total_tfd'];
        $stok_tersedia = $hasil['sisa_psd']; // Langsung dari sisa_psd, tanpa ditambah jumlah_tfd
        $sisa_psd_original = $hasil['sisa_psd'] + $hasil['jumlah_tfd']; // Total stok yang tersedia di awal
        
        $tabel .= '<tr id="traddeditfsales'.$nomor.'">
                    <td>
                        <a onclick="editfsalesitem('.$nomor.', '.$hasil['id_tfd'].')"><i class="fa fa-edit"></i></a> '.$hasil['nama_pro'].' 
                        <input type="hidden" name="id_tfd[]" value="'.$hasil['id_tfd'].'" readonly="readonly" />
                        <input type="hidden" name="kodestok[]" value="'.$hasil['id_psd'].'" readonly="readonly" />
                        <input type="hidden" name="product[]" value="'.$hasil['id_pro'].'" readonly="readonly" />
                        <input type="hidden" name="stok_awal[]" id="stok_awal'.$nomor.'" value="'.$stok_tersedia.'" readonly="readonly" />
                        <input type="hidden" name="jumlah_awal[]" id="jumlah_awal'.$nomor.'" value="'.$hasil['jumlah_tfd'].'" readonly="readonly" />
                        <input type="hidden" name="sisa_psd_original[]" id="sisa_psd_original'.$nomor.'" value="'.$sisa_psd_original.'" readonly="readonly" />
                    </td>
                    <td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td>
                    <td>'.$hasil['no_bcode'].'</td>
                    <td>'.$hasil['gudang'].'</td>
                    <td>'.$hasil['tgl_expired'].'</td>
                    <td><input type="text" name="harga[]" id="eharga'.$nomor.'" class="inputangka" onkeyup="angka(this)" value="'.number_format($hasil['harga_tfd'], 0, ',', '.').'" placeholder="0" /></td>
                    <td><input type="text" name="jumlah[]" id="ejumlah'.$nomor.'" class="inputangka" value="'.$hasil['jumlah_tfd'].'" oninput="hitungsalesedit('.$nomor.')" placeholder="0" /></td>
                    <td>
                        <span id="stok_tersedia'.$nomor.'">'.$stok_tersedia.'</span>
                        <br><small class="text-muted">Max: '.$sisa_psd_original.'</small>
                    </td>
                    <td><input type="text" name="diskon[]" id="ediskon'.$nomor.'" class="inputangka" oninput="hitungsalesedit('.$nomor.')" value="'.$hasil['diskon_tfd'].'" placeholder="0" /></td>
                    <td><input type="text" name="total[]" id="etotal'.$nomor.'" class="inputtotal" onkeyup="angka(this)" value="'.number_format($hasil['total_tfd'], 0, ',', '.').'" placeholder="0" readonly="readonly" /></td>
                    <td><center><a onclick="deletefsalesitem('.$nomor.', '.$hasil['id_tfd'].')"><i class="fa fa-trash text-danger"></i></a></center></td>
                </tr>';
        $nomor++;
    }
    
    $conn = $base->close();
    
    // Debug logging
    error_log("LOAD ITEMS DEBUG - Final result: " . json_encode(array(
        "itemCount" => ($nomor - 1),
        "total" => $total,
        "hasData" => !empty($tabel)
    )));
    
    $json = array(
        "tabel" => $tabel,
        "jumlahitem" => ($nomor - 1),
        "subtotal" => number_format($total, 0, ',', '.')
    );
    
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    
    echo(json_encode($json));
?>
