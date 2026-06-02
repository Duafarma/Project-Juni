<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Mitra</li>
                <li class="breadcrumb-item"><a href="#">Outlet</a></li>
                <li class="breadcrumb-item active" aria-current="page">Legal Outlet</li>
            </ol>
        </nav>
        <h4 class="content-title">Update Legal Outlet</h4>
    </div>
</div>

<?php
    $kode = $secu->injection(@$_GET['keycode']);

    $view = array();
    if(!empty($kode)){
        $read = $conn->prepare("SELECT id_out, nama_out, resmi_out FROM outlet WHERE id_out=:kode");
        $read->bindParam(':kode', $kode, PDO::PARAM_STR);
        $read->execute();
        $view = $read->fetch(PDO::FETCH_ASSOC);
    }

    $count = $conn->prepare("SELECT COUNT(*) AS jum FROM outlet_legal WHERE id_out=:kode");
    $count->bindParam(':kode', $kode, PDO::PARAM_STR);
    $count->execute();
    $hcount = $count->fetch(PDO::FETCH_ASSOC);
    $jumlegal = (!empty($hcount) && isset($hcount['jum'])) ? (int)$hcount['jum'] : 0;

    $optStmt = $conn->prepare("SELECT id_klg, nama_klg FROM kategori_legal ORDER BY nama_klg ASC");
    $optStmt->execute();
    $legalOptions = $optStmt->fetchAll(PDO::FETCH_ASSOC);

    $spesimenMap = array();
    foreach($legalOptions as $opt){
        $namaLower = strtolower(trim($opt['nama_klg']));
        $spesimenMap[$opt['id_klg']] = (
            (strpos($namaLower, 'spesimen') !== false || strpos($namaLower, 'specimen') !== false)
            && (strpos($namaLower, 'ttd') !== false || strpos($namaLower, 'tanda tangan') !== false)
        ) ? '1' : '0';
    }
?>

<input type="hidden" name="jumlegal" id="jumlegal" value="<?php echo($jumlegal); ?>" readonly="readonly" />

<div class="content-body">
    <div class="component-section no-code">
        <div class="row row-sm">
            <div class="col-sm-8">
                <h5 class="tx-semibold">Informasi Outlet</h5>
                <p class="mg-b-10">Update data legal outlet saja (tanpa mengubah data lain).</p>
            </div>
            <div class="col-sm-4 text-right">
                <a href="<?php echo($data->sistem('url_sis').'/outlet/e/'.$kode); ?>" class="btn btn-secondary btn-xs">Kembali ke Edit Outlet</a>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-sm-6">
                <label>Nama Resmi</label>
                <input type="text" class="form-control" value="<?php echo(!empty($view) ? htmlspecialchars($view['resmi_out']) : ''); ?>" readonly="readonly" />
            </div>
            <div class="form-group col-sm-6">
                <label>Nama Outlet</label>
                <input type="text" class="form-control" value="<?php echo(!empty($view) ? htmlspecialchars($view['nama_out']) : ''); ?>" readonly="readonly" />
            </div>
        </div>

        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="outlet" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="updatelegal" readonly="readonly" />
            <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />

            <div class="row">
                <div class="form-group col-sm-12">
                    <label>Legal Outlet <span class="tx-danger">*</span></label>
                    <table class="table table-hover mg-b-0">
                        <thead>
                            <tr>
                                <th>Legal</th>
                                <th>Ket.</th>
                                <th>Expired Date</th>
                                <th>Dokumen</th>
                                <th><center>#</center></th>
                            </tr>
                        </thead>
                        <tbody id="tbllegal">
                        <?php
                            $no = 1;
                            if(!empty($kode)){
                                $master = $conn->prepare("SELECT id_klg, ket_ole, expired_ole, dokumen_ole FROM outlet_legal WHERE id_out=:kode");
                                $master->bindParam(':kode', $kode, PDO::PARAM_STR);
                                $master->execute();
                                while($hasil = $master->fetch(PDO::FETCH_ASSOC)){
                        ?>
                            <tr id="<?php echo('ileg'.$no); ?>">
                                <?php
                                    $rowSpesimen = (isset($spesimenMap[$hasil['id_klg']]) && $spesimenMap[$hasil['id_klg']] === '1');
                                    $checkedSpesimen = (!empty($hasil['ket_ole']) && $hasil['ket_ole'] !== '0') ? 'checked="checked"' : '';
                                    $styleKet = $rowSpesimen ? 'style="display:none;"' : '';
                                    $styleExp = $rowSpesimen ? 'style="display:none;"' : '';
                                    $styleDoc = $rowSpesimen ? 'style="display:inline-block;"' : 'style="display:none;"';
                                ?>
                                <td>
                                    <select name="legal[<?php echo($no); ?>]" class="form-control legal-select" data-row="<?php echo($no); ?>" required="required">
                                        <option value="">-- Select Legal --</option>
                                        <?php
                                            foreach($legalOptions as $hlega){
                                                $namaLower = strtolower(trim($hlega['nama_klg']));
                                                   $isSpesimen = (
                                                   (strpos($namaLower, 'spesimen') !== false || strpos($namaLower, 'specimen') !== false)
                                                       && (strpos($namaLower, 'ttd') !== false || strpos($namaLower, 'tanda tangan') !== false)
                                                   ) ? '1' : '0';
                                                $pilih = ($hlega['id_klg'] === $hasil['id_klg']) ? 'selected="selected"' : '';
                                        ?>
                                            <option value="<?php echo($hlega['id_klg']); ?>" data-spesimen="<?php echo($isSpesimen); ?>" <?php echo($pilih); ?>><?php echo($hlega['nama_klg']); ?></option>
                                        <?php } ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="ketlegal[<?php echo($no); ?>]" id="<?php echo('ketlegal'.$no); ?>" class="form-control legal-ket" value="<?php echo(htmlspecialchars($hasil['ket_ole'])); ?>" placeholder="Type here..." <?php echo($styleKet); ?> />
                                    <label class="mg-b-0 legal-doc" id="<?php echo('docwrap'.$no); ?>" <?php echo($styleDoc); ?> >
                                        <input type="checkbox" class="legal-dok" <?php echo($checkedSpesimen); ?> /> Ada <small class="tx-gray-500">(uncheck = Tidak)</small>
                                    </label>
                                </td>
                                <td>
                                    <input type="text" name="tgllegal[<?php echo($no); ?>]" id="<?php echo('tgllegal'.$no); ?>" class="form-control fortgl legal-exp" value="<?php echo(htmlspecialchars($hasil['expired_ole'])); ?>" placeholder="9999-99-99" <?php echo($styleExp); ?> />
                                </td>
                                <td>
                                    <input type="file" name="doklegal[<?php echo($no); ?>]" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx" />
                                    <input type="hidden" name="olddoklegal[<?php echo($no); ?>]" value="<?php echo(htmlspecialchars($hasil['dokumen_ole'])); ?>" />
                                    <?php if(!empty($hasil['dokumen_ole'])){ ?>
                                        <small><a href="<?php echo($data->sistem('url_sis').'/berkas/legal/'.$hasil['dokumen_ole']); ?>" target="_blank" class="tx-primary"><?php echo($hasil['dokumen_ole']); ?></a></small>
                                    <?php } ?>
                                </td>
                                <td>
                                    <center>
                                        <a onclick="<?php echo('removeitem(\'jumlegal\', \'ileg\', '.$no.')'); ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                                    </center>
                                </td>
                            </tr>
                        <?php
                                    $no++;
                                }
                            }
                        ?>
                        </tbody>
                    </table>
                    <a onclick="additem('tbllegal', 'jumlegal', 'legaloutletupdate')"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
                </div>
            </div>

            <div class="clearfix mg-t-25 mg-b-25"></div>
            <div class="row">
                <div class="col-sm-12">
                    <a href="<?php echo($data->sistem('url_sis').'/outlet'); ?>" title="Batal">
                        <button type="button" class="btn btn-secondary btn-xs">Batal</button>
                    </a>
                    <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update Legal</button>
                    <span id="imgloading"></span>
                </div>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    (function(){
        function isSpesimenSelected($opt){
            var optText = ($opt.text() || '').toLowerCase();
            var hasSpesimenWord = (optText.indexOf('spesimen') !== -1) || (optText.indexOf('specimen') !== -1);
            var isSpesimenText = hasSpesimenWord && (optText.indexOf('ttd') !== -1 || optText.indexOf('tanda tangan') !== -1);
            return ($opt.data('spesimen') == 1) || isSpesimenText;
        }

        function applyRowState($row){
            var $sel = $row.find('select.legal-select');
            if(!$sel.length) return;

            var $opt = $sel.find('option:selected');
            var isSpesimen = isSpesimenSelected($opt);

            var $ket = $row.find('input.legal-ket');
            var $exp = $row.find('input.legal-exp');
            var $wrap = $row.find('.legal-doc');
            var $chk = $row.find('input.legal-dok');

            if(isSpesimen){
                $wrap.show();
                $ket.hide();
                $exp.hide();
                $exp.val('9999-99-99');

                if($ket.val() !== '1' && $ket.val() !== '0'){
                    var legacy = ($ket.val() || '').toString().trim();
                    $ket.val(legacy !== '' ? '1' : '0');
                }
                $chk.prop('checked', $ket.val() === '1');
            } else {
                $wrap.hide();
                $ket.show();
                $exp.show();

                if($ket.val() === '0' || $ket.val() === '1'){
                    $ket.val('');
                }
            }
        }

        $(function(){
            if ($.fn.mask) {
                $(".fortgl").mask("9999-99-99");
            }

            $('#tbllegal').find('tr').each(function(){
                applyRowState($(this));
            });

            $(document).on('change', 'select.legal-select', function(){
                applyRowState($(this).closest('tr'));
            });

            $(document).on('change', 'input.legal-dok', function(){
                var $row = $(this).closest('tr');
                $row.find('input.legal-ket').val(this.checked ? '1' : '0');
            });
        });
    })();
</script>


