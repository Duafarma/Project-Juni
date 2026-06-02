<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Mitra</li>
                <li class="breadcrumb-item active" aria-current="page">Outlet Harga PIM</li>
            </ol>
        </nav>
        <h4 class="content-title">Tambah Data - Outlet Harga PIM</h4>
    </div>
</div>
<?php
    $kode = $secu->injection($_GET['keycode']); // Validasi input
    $read = $conn->prepare("
        SELECT B.id_pro, B.nama_pro, A.persen_pds, A.id_out
        FROM produk AS B
        LEFT JOIN produk_diskon AS A ON A.id_pro = B.id_pro AND A.id_out = :kode
        ORDER BY B.nama_pro ASC
    ");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $results = $read->fetchAll(PDO::FETCH_ASSOC);
 

?>
<div class="content-body">
    <div class="component-section no-code">
        <!-- <input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" /> -->
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="outlet" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="updateitem" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
		<div class="row">
            <div class="form-group col-sm-12">
                <label>Diskon Produk <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
                            <th>No</th>
							<th>Produk</th>
							<th>Diskon</th>
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbldiskon">
                    <?php foreach ($results as $key => $row) { ?>
                    <tr id="<?php echo "item$key"; ?>">
                    <td><?php echo $key + 1; ?></td>
                        <td>
                            <a href="#modal1" onclick="<?php echo "mproduct($key, 'showproduct')"; ?>" data-toggle="modal">
                                <div id="<?php echo "noproduct$key"; ?>"><?php echo htmlspecialchars($row['nama_pro']); ?></div>
                            </a>
                            <input type="hidden" name="product[]" id="<?php echo "product$key"; ?>" value="<?php echo $row['id_pro']; ?>" class="itemproduct" readonly="readonly" />
                        </td>
                        <td style="position: relative;">
                            <input type="text" name="persen_pds[]" class="inputangka" placeholder="0" required 
                                value="<?php echo htmlspecialchars(!empty($row['persen_pds']) ? $row['persen_pds'] : '0'); ?>" 
                                style="padding-right: 20px;" />
                            <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">%</span>
                        </td>

                        <td>
                            <!-- <center>
                                <a onclick="<?php echo "delprohpim($key)"; ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                            </center> -->
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
				</table>
                <!-- <a onclick="hpimoutlet()"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a> -->
            </div>
		</div>
		
        <!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
        <div class="row">
            <div class="col-sm-12">
                <a href="<?php echo($data->sistem('url_sis').'/outlet'); ?>" title="Batal">
                <button type="button" class="btn btn-secondary btn-xs">Batal</button>
				</a>
                <button type="submit" id="bsave" class="btn btn-dark btn-xs">Update</button>
            </div>
		</div>
		</form>
    </div>
</div>