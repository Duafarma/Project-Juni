<?php
    require_once(__DIR__.'/../../config/connection/connection.php');
    require_once(__DIR__.'/../../config/connection/security.php');
    require_once(__DIR__.'/../../config/function/data.php');
    $secu   = new Security;
    $base   = new DB;
    $data   = new Data;
    $conn   = $base->open();

    $jumlah = intval($secu->injection(@$_GET['jumlah']));
    $nomor  = ($jumlah + 1);
?>
<tr id="traddorder<?php echo($nomor); ?>">
    <td>
        <a href="#modal1" onclick="mproductmp(<?php echo($nomor); ?>)" data-toggle="modal">
            <div id="noproduct<?php echo($nomor); ?>">Pilih Produk</div>
        </a>
        <input type="hidden" name="product[]" id="product<?php echo($nomor); ?>" class="itemproduct" readonly="readonly" />
    </td>
    <td><div id="detailproduct<?php echo($nomor); ?>">-</div></td>
    <td>
        <input type="text" name="harga[]" id="pharga<?php echo($nomor); ?>"
               class="inputangka form-control form-control-sm"
               onkeyup="angka(this)" placeholder="0" required="required" />
    </td>
    <td>
        <center>
            <a onclick="<?php echo("deleteorder($nomor)"); ?>">
                <span class="badge badge-danger"><i class="fa fa-times-circle"></i></span>
            </a>
        </center>
    </td>
</tr>
