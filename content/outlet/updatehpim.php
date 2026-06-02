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
    $kode = $secu->injection($_GET['keycode']); // Pastikan ini benar-benar sudah divalidasi
    $read = $conn->prepare("SELECT A.id_pro, B.nama_pro, A.harga_a, A.harga_b, A.harga_c
                            FROM produk_hargapim AS A
                            LEFT JOIN produk AS B ON A.id_pro = B.id_pro
                            LEFT JOIN outlet AS C ON A.id_out = C.id_out
                            WHERE C.id_out = :kode 
                            AND (A.harga_a IS NOT NULL AND A.harga_a > 0
                            OR A.harga_b IS NOT NULL AND A.harga_b > 0
                            OR A.harga_c IS NOT NULL AND A.harga_c > 0)");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $results = $read->fetchAll(PDO::FETCH_ASSOC);

?>
<div class="content-body">
    <div class="component-section no-code">
        <input type="hidden" name="jumitem" id="jumitem" value="0" readonly="readonly" />
        <form id="formtransaksi" action="#" method="post" enctype="multipart/form-data" autocomplete="off">
        <input type="hidden" name="nmenu" id="nmenu" value="outlet" readonly="readonly" />
        <input type="hidden" name="nact" id="nact" value="updatehpim" readonly="readonly" />
        <input type="hidden" name="keycode" id="keycode" value="<?php echo($kode); ?>" readonly="readonly" />
		<div class="row">
            <div class="form-group col-sm-12">
                <label>Diskon Produk <span class="tx-danger">*</span></label>
				<table class="table table-hover mg-b-0">
					<thead>
						<tr>
							<th>Produk</th>
							<th>Harga A</th>
							<th>Harga B</th>
							<th>Harga C</th>
							<th><center>#</center></th>
						</tr>
					</thead>
					<tbody id="tbldiskon">
                    <?php foreach ($results as $key => $row) { ?>
                    <tr id="<?php echo "item$key"; ?>">
                        <td>
                            <a href="#modal1" onclick="<?php echo "mproduct($key, 'showproduct')"; ?>" data-toggle="modal">
                                <div id="<?php echo "noproduct$key"; ?>"><?php echo htmlspecialchars($row['nama_pro']); ?></div>
                            </a>
                            <input type="hidden" name="product[]" id="<?php echo "product$key"; ?>" value="<?php echo $row['id_pro']; ?>" class="itemproduct" readonly="readonly" />
                        </td>
                        <td><input type="text" name="harga_a[]" class="inputangka" placeholder="0" required value="<?php echo htmlspecialchars($row['harga_a']); ?>" /></td>
                        <td><input type="text" name="harga_b[]" class="inputangka" placeholder="0" required value="<?php echo htmlspecialchars($row['harga_b']); ?>" /></td>
                        <td><input type="text" name="harga_c[]" class="inputangka" placeholder="0" required value="<?php echo htmlspecialchars($row['harga_c']); ?>" /></td>
                        <td>
                            <center>
                                <a onclick="<?php echo "delprohpim($key)"; ?>"><span class="badge badge-danger"><i class="fa fa-times-circle"></i></span></a>
                            </center>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
				</table>
                <a onclick="hpimoutlet()"><span class="badge badge-success"><i class="fa fa-plus-circle"></i> Add Data</span></a>
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