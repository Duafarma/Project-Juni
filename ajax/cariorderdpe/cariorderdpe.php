<?php
    // Memasukkan file konfigurasi dan fungsi yang diperlukan
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    
    // Membuat objek untuk keamanan dan koneksi database
    $secu    = new Security;
    $base    = new DB;
    $data    = new Data;
    
    // Mendapatkan sistem dan tanggal saat ini
    $sistem  = $data->sistem('url_sis');
    $catat   = date('Y-m-d H:i:s');
    $admin   = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci   = $secu->injection(@$_COOKIE['kuncikuy']);
    $kode    = $secu->injection(@$_POST['x']);
    $tgl     = date('Y-m-d');
    
    // Membuka koneksi ke database
    $conn    = $base->open();
    $nomor   = 1;
    $tabel   = '';
    
    // Membaca data transaksi berdasarkan kode
  	$read	= $conn->prepare("SELECT A.tgl_tor, A.ket_tor, C.nama_sup, COUNT(B.id_tor) AS jitem, D.parameter_sdi, D.diskon1_sdi, D.diskon2_sdi, D.top_sdi FROM transaksi_order AS A INNER JOIN transaksi_orderdetail AS B ON A.id_tor=B.id_tor INNER JOIN supplier AS C ON A.id_sup=C.id_sup LEFT JOIN supplier_diskon AS D ON C.id_sup=D.id_sup WHERE A.id_tor=:kode");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $view    = $read->fetch(PDO::FETCH_ASSOC);
    
    // Menghitung tanggal jatuh tempo berdasarkan top_sdi
    $limit   = date("Y-m-d", strtotime("+$view[top_sdi] Days", strtotime($catat)));
    $subtot  = 0;
    $total   = 0;
    $active  = 'Active';
    
    // Menyiapkan query untuk mengambil detail produk dengan diskon dari tabel produk_diskonsup
  	$master	= $conn->prepare("SELECT A.id_tod, A.jumlah_tod, B.id_pro, B.nama_pro, B.berat_pro, C.nama_kpr, D.nama_spr, E.harga_phg, F.persen_pds, G.id_sup FROM transaksi_orderdetail AS A LEFT JOIN produk AS B ON A.id_pro=B.id_pro LEFT JOIN kategori_produk AS C ON B.id_kpr=C.id_kpr LEFT JOIN satuan_produk AS D ON B.id_spr=D.id_spr LEFT JOIN produk_harga AS E ON B.id_pro=E.id_pro LEFT JOIN transaksi_order AS G ON A.id_tor=G.id_tor LEFT JOIN produk_diskonsup AS F ON (B.id_pro=F.id_pro AND G.id_sup=F.id_sup) WHERE A.id_tor=:kode AND E.status_phg=:active GROUP BY A.id_tod ORDER BY A.id_tod ASC");
    $master->bindParam(':kode', $kode, PDO::PARAM_STR);
    $master->bindParam(':active', $active, PDO::PARAM_STR);
    $master->execute();
    
    // Menyusun tabel produk dan menghitung subtotal, diskon, dan total
    while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
        $subtot  = $hasil['harga_phg'] * $hasil['jumlah_tod'];
        
        // Menggunakan diskon dari tabel produk_diskonsup jika ada, jika tidak gunakan diskon supplier
        if (!empty($hasil['persen_pds'])) {
            $diskon = $hasil['persen_pds'];
        } else {
            $diskon = ($hasil['jumlah_tod'] <= $view['parameter_sdi']) ? $view['diskon1_sdi'] : $view['diskon2_sdi'];
        }
        
        $stotal  = $subtot - (($subtot * $diskon) / 100);
        $total   += $stotal;

        // Menyusun baris tabel
        $tabel  .= '<tr id="traddorder'.$nomor.'">
                    <td>
                        <a onclick="addrorderdpe('.$nomor.', '.$hasil['id_tod'].')"><i class="fa fa-plus-circle"></i></a> '.$hasil['nama_pro'].' 
                        <input type="hidden" name="product[]" value="'.$hasil['id_pro'].'" readonly="readonly" />
                    </td>
                    <td>'.$hasil['nama_kpr'].' ('.$hasil['berat_pro'].' '.$hasil['nama_spr'].')</td>
                    	<td><input type="text" name="gudang[]" class="inputrans" placeholder="" /></td>
                    <td><input type="text" name="batchcode[]" class="inputrans" value="" placeholder="-" required="required" /></td>
                    <td><input type="text" name="tbatchcode[]" class="inputrans fortgl" value="" placeholder="9999-99-99" required="required" /></td>
                    <td><input type="text" name="harga[]" id="pharga'.$nomor.'" class="inputangka" onkeyup="angka(this)" value="'.number_format($hasil['harga_phg'], 0, ',', '.').'" placeholder="0" readonly="readonly" /></td>
                    <td>'.$hasil['jumlah_tod'].'</td>
                    <td><input type="text" name="jumlah[]" id="pjumlah'.$nomor.'" class="inputangka" value="'.$hasil['jumlah_tod'].'" onchange="jumlahorderdpe('.$nomor.')" onkeyup="angka(this)" placeholder="0" /></td>
                    <td><input type="text" name="diskon[]" id="pdiskon'.$nomor.'" class="inputangka" onchange="hitungorderdpe('.$nomor.')" value="'.$diskon.'" placeholder="0" /></td>
                    <td><input type="text" name="total[]" id="ptotal'.$nomor.'" class="inputtotal" onkeyup="angka(this)" value="'.number_format($stotal, 0, ',', '.').'" placeholder="0" readonly="readonly" /></td>
                    <td><center>-</center></td>
                </tr>
                <script type="text/javascript">$(".fortgl").mask("9999-99-99");</script>';
        $nomor++;
    }
    
    // Menghitung PPN dan total akhir
    // $ppn    = ($total * 11) / 100;
    // $pph    = ($total * 0.3) / 100;
    $gtotal = $total ;

    // Menyusun footer untuk menampilkan subtotal, PPN, dan total
    $footer = 
    '<tr>
        <td></td>
        <td colspan="8"><div align="right"><b>SUBTOTAL</b></div></td>
        <td>
            <input type="text" name="pstotal" id="pstotal" class="inputtotal" onkeyup="angka(this)" value="'.number_format($total, 0, ',', '.').'" placeholder="0" readonly="readonly" />
            <input type="hidden" name="minorder" id="minorder" value="'.$view['parameter_sdi'].'" readonly="readonly" />
            <input type="hidden" name="diskon1" id="diskon1" value="'.$view['diskon1_sdi'].'" readonly="readonly" />
            <input type="hidden" name="diskon2" id="diskon2" value="'.$view['diskon2_sdi'].'" readonly="readonly" />
        </td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td colspan="8"><div align="right"><b>TOTAL</b></div></td>
        <td><input type="text" name="pgtotal" id="pgtotal" class="inputtotal" onkeyup="angka(this)" value="'.number_format($gtotal, 0, ',', '.').'" placeholder="0" readonly="readonly" /></td><td></td>
    </tr>';

    // Menutup koneksi database
    $conn = $base->close();

    // Menyusun output JSON yang akan dikirimkan ke frontend
    $json = array(
        "tabel" => $tabel, 
        "supplier" => $view['nama_sup'], 
        "tglorder" => $view['tgl_tor'], 
        "ketorder" => $view['ket_tor'], 
        "jatuhtempo" => $limit, 
        "jumaddorder" => $view['jitem'], 
        "footer" => $footer
    );
    
    // Mengirim response HTTP
    http_response_code(200);
    header('Access-Control-Allow-Origin: *');
    header("Content-type: application/json; charset=utf-8");
    
    // Mengirimkan data dalam format JSON
    echo(json_encode($json));
?>
