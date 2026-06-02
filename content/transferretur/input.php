<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item"><a href="#">Inventory</a></li>
                <li class="breadcrumb-item"><a href="#">Transfer Retur</a></li>
                <li class="breadcrumb-item active" aria-current="page">Transfer Retur Baru</li>
            </ol>
        </nav>
        <h4 class="content-title">Transfer Retur Baru</h4>
    </div>
</div>
<?php
$unik = "/" . $data->romawi(date('m')) . '/' . date('Y');
$apls = $data->get_apl();
$self_apl = $data->self_apl(); // Get the current application array
$nama_apl = $self_apl['nomor']; // Get the name from the array



// Generate the code with proper prefix for transfer retur (TTR not TRF)
$kode = $data->transcodetfretur('TRF', $nama_apl, 'kode_ttr', 'transaksi_transferretur');

?>
<div class="content-body">
    <div class="component-section no-code">
        <h5 id="section1" class="tx-semibold"><?php echo ($data->sistem('pt_sis')); ?></h5>
        <div style="margin-top:10px; margin-bottom:25px;">
            <div>Izin PBF No : <?php echo ($data->sistem('pbf_sis')); ?></div>
            <div>NPWP No : <?php echo ($data->sistem('npwp_sis')); ?></div>
            <div>Alamat : <?php echo ($data->sistem('alamat_sis')); ?></div>
        </div>
        <form id="formProductReturTransfer" action="#" method="post" autocomplete="off">
            <input type="hidden" name="nmenu" id="nmenu" value="transferretur" readonly="readonly" />
            <input type="hidden" name="nact" id="nact" value="input" readonly="readonly" />
            <div class="row row-sm">
                <div class="col-sm-3">
                    <label>Kode <span class="tx-danger">*</span></label>
                    <input type="text" name="kode" class="form-control" value="<?php echo ($kode); ?>" placeholder="-" required="required" />
                </div>
                <div class="col-sm-3">
                    <label>Tipe Transfer <span class="tx-danger">*</span></label>
                    <select name="transfer_apl_type" id="transfer_apl_type" locked class="form-control select2" required="required">
                        <option value="">-- Pilih --</option>
                        <option value="IN" id="in">Masuk</option>
                        <option value="OUT">Keluar</option>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label>Tanggal <span class="tx-danger">*</span></label>
                    <input type="text" name="tanggal" class="form-control" value="<?php echo (date('Y-m-d')); ?>" placeholder="9999-99-99" required="required" readonly />
                </div>
            </div>
            <div class="row row-sm">
                <div class="col-sm-3">
                    <label>Asal <span class="tx-danger">*</span></label>
                    <select name="transfer_apl_from" id="transfer_apl_from" class="form-control select2" required="required">
                        <option value="">-- Pilih --</option>
                        <?php
                        foreach ($apls as $hasil) {
                        ?>
                            <option value="<?php echo ($hasil['id_apl']); ?>"><?php echo ($hasil['nama_apl']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-sm-3">
                    <label>Tujuan <span class="tx-danger">*</span></label>
                    <select name="transfer_apl_to" id="transfer_apl_to" class="form-control select2" required="required">
                        <option value="">-- Pilih --</option>
                        <?php
                        foreach ($apls as $hasil) {
                        ?>
                            <option value="<?php echo ($hasil['id_apl']); ?>"><?php echo ($hasil['nama_apl']); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Ketik keterangan di sini..." />
                </div>
            </div><!-- row -->
            <div class="clearfix mg-t-25 mg-b-25"></div>
            <h5 id="section1" class="tx-semibold">Transfer Produk</h5>
            <p class="mg-b-25">Pilih produk yang akan ditransfer.</p>
            <div class="row row-sm">
                <div class="col-sm-12">
                    <div class="table-responsive">
                        <table class="tabeltransaksi">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Detail</th>
                                    <th>Batchcode</th>
                                    <th>Tgl. Expired</th>
                                    <th>Stok</th>
                                    <th>Harga</th>
                                    <th id="labelTransferType">Jumlah</th>
                                    <th>Satuan Qty.</th>
                                    <th>
                                        <center>Act</center>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="dataaddProductReturTransfer">
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="cartaddProductReturTransfer" id="cartaddProductReturTransfer" value="" readonly="readonly" />
                    <input type="hidden" name="countaddProductReturTransfer" id="countaddProductReturTransfer" value="0" readonly="readonly" />
                    <a onclick="addProductReturTransfer('addProductReturTransfer', 'transfer_apl_type', 'transfer_apl_from', 'transfer_apl_to', 'tanggal', '<?php echo $self_apl['id_apl']; ?>', 50)"><span class="badge badge-success">
                            <i class="fa fa-plus-circle"></i> Add Data</span>
                    </a>
                </div>
            </div>
            <div class="clearfix mg-t-25 mg-b-25"></div>
            <div class="row row-sm">
                <div class="col-sm-12">
                    <a href="<?php echo ("$sistem/transferretur"); ?>" title="Batal">
                        <button type="button" class="btn btn-secondary">Batal</button>
                    </a>
                    <button type="submit" id="bsave" class="btn btn-dark">Simpan</button>
                    <div id="imgloading"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal for selecting a product -->
<div class="modal fade" id="modal2" tabindex="-1" role="dialog" aria-labelledby="modal2Label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal2Label">Select Product</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="example2" class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama</th>
                                <th>Batchcode</th>
                                <th>Tgl. ED</th>
                                <th>Gudang</th>
                                <th>Stok</th>
                                <th>Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Populate this table with products from the database -->
                            <?php
                            $status = 'Active';
                            $qMaster = "SELECT
                                A.id_i_r,
                                A.no_bcode,
                                A.tanggal,
                                A.sisa,
                                B.id_pro,
                                B.kode_pro,
                                B.nama_pro,
                                B.berat_pro,
                                C.harga_phg,
                                D.nama_kpr,
                                D.satuan_kpr,
                                E.nama_spr,
                                A.id_r_d,
                                A.tanggal,
                                A.gudang
                            FROM
                                inventory_retur AS A
                            LEFT JOIN produk AS B ON
                                A.id_pro = B.id_pro
                            LEFT JOIN produk_harga AS C ON
                                B.id_pro = C.id_pro
                            LEFT JOIN kategori_produk AS D ON
                                B.id_kpr = D.id_kpr
                            LEFT JOIN satuan_produk AS E ON
                                B.id_spr = E.id_spr
                            WHERE
                                A.sisa > 0 AND 
                                C.status_phg =:status
                            GROUP BY
                                A.id_i_r,
                                A.no_bcode,
                                A.ed,
                                A.sisa,
                                B.id_pro,
                                B.kode_pro,
                                B.nama_pro,
                                B.berat_pro,
                                C.harga_phg,
                                D.nama_kpr,
                                D.satuan_kpr,
                                E.nama_spr,
                                A.id_r_d,
                                A.tanggal,
                                A.gudang";
                            $master = $conn->prepare($qMaster);
                            $master->bindParam(':status', $status, PDO::PARAM_STR);
                            $master->execute();
                            while ($hasil = $master->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                                <tr onclick="showProductReturTransfer('<?php echo $nomor; ?>', '<?php echo $hasil['id_i_r']; ?>', '<?php echo $hasil['id_pro']; ?>', '<?php echo $hasil['nama_pro']; ?>', '<?php echo $hasil['kode_pro']; ?>', '<?php echo $hasil['harga_phg']; ?>', '<?php echo $hasil['berat_pro']; ?>', '<?php echo $hasil['nama_kpr']; ?>', '<?php echo $hasil['satuan_kpr']; ?>', '<?php echo $hasil['nama_spr']; ?>', '<?php echo $hasil['no_bcode']; ?>', '<?php echo $hasil['tanggal']; ?>', '<?php echo $hasil['sisa']; ?>', '<?php echo $hasil['id_r_d']; ?>', '<?php echo $hasil['tanggal']; ?>', '<?php echo $hasil['gudang']; ?>')">
                                    <td><?php echo $hasil['kode_pro']; ?></td>
                                    <td><?php echo $hasil['nama_pro']; ?></td>
                                    <td><?php echo $hasil['no_bcode']; ?></td>
                                    <td><?php echo $hasil['tanggal']; ?></td>
                                    <td><?php echo $hasil['gudang']; ?></td>
                                    <td><?php echo $hasil['sisa']; ?></td>
                                    <td><?php echo $hasil['harga_phg']; ?></td>
                                </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>