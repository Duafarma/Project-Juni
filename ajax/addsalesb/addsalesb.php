<?php
    require_once('../../config/connection/connection.php');
    require_once('../../config/connection/security.php');
    require_once('../../config/function/data.php');
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $sistem = $data->sistem('url_sis');
    $catat  = date('Y-m-d H:i:s');
    $admin  = $secu->injection(@$_COOKIE['adminkuy']);
    $kunci  = $secu->injection(@$_COOKIE['kuncikuy']);
    $conn   = $base->open();

    $jumlah = intval($secu->injection(@$_GET['jumlah']));
    $diskon = $secu->injection(@$_GET['diskon']);
    $nomor  = $jumlah + 1;
    
    // Create the HTML for the new row
    $html = '<tr id="traddsales'.$nomor.'">
        <td>
        <a href="#modal1" onclick="addsalesb('.$nomor.', \'outlet\')" data-toggle="modal">
        <div id="noproduct'.$nomor.'">Pilih</div>
        </a>
        <input type="hidden" name="kodestok[]" id="kodestok'.$nomor.'" readonly="readonly" />
        <input type="hidden" name="product[]" id="product'.$nomor.'" class="itemproduct" readonly="readonly" />
        <input type="hidden" name="prostok[]" id="prostok'.$nomor.'" readonly="readonly" />
        </td>
        <td><div id="prodetail'.$nomor.'">-</div></td>
        <td><div id="nobcode'.$nomor.'">-</div></td>
        <td><div id="gudang'.$nomor.'">-</div></td>
        <td><div id="tgled'.$nomor.'">-</div></td>
        <td><input type="text" name="harga[]" id="pharga'.$nomor.'" class="inputangka" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
        <td><input type="text" name="jumlah[]" id="pjumlah'.$nomor.'" class="inputangka" value="1" onchange="jumlahsalesb('.$nomor.')" onkeyup="angka(this)" placeholder="0" /></td>
        <td><div id="satuanqty'.$nomor.'">-</div></td>
        <td><input type="text" name="diskon[]" id="pdiskon'.$nomor.'" class="inputangka" value="'.$diskon.'" onchange="hitungsalesb('.$nomor.')" onkeyup="angka(this)" placeholder="0" /></td>
        <td><input type="text" name="total[]" id="ptotal'.$nomor.'" class="inputtotal" onkeyup="angka(this)" placeholder="0" readonly="readonly" /></td>
        <td>
        <center>
            <a onclick="delsalesb('.$nomor.')"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
        </center>
        </td>
    </tr>';
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'item' => $html,
        'total' => $nomor,
        'cart' => 'addsalesb'
    ]);
    
    $conn = $base->close();
    exit();
?>