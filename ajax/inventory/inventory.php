<?php
	require_once('../../config/connection/connection.php');
	require_once('../../config/connection/security.php');
	require_once('../../config/function/data.php');
	$base	= new DB;
	$secu	= new Security;
	$data	= new Data;
	$conn	= $base->open();
	$menu	= $secu->injection($_GET['menu']);
	switch($menu){
		case 'tracking':
			$tabel	= '
			<div class="clearfix mg-t-25 mg-b-25"></div>
			<div class="row row-sm">
				<div class="col-sm-3">
					<label>Inventory Account <span class="tx-danger">*</span></label>
					<select name="phonekontak" class="form-control" required="required">
						<option value="">-- Pilih --</option>
					</select>
				</div>
			</div>
			<div class="clearfix mg-t-25 mg-b-25"></div>
			<h5 id="section1" class="tx-semibold">I purchase this item</h5>
			<div class="row row-sm">
				<div class="col-sm-3">
					<label>Buy Unit Price <span class="tx-danger">*</span></label>
					<input type="text" name="pricepurchase" class="form-control" required="required"  />
				</div>
				<div class="col-sm-3">
					<label>Purchase Account <span class="tx-danger">*</span></label>
					<select name="accpurchase" class="form-control" required="required">
						<option value="">-- Select Purchase Account --</option>
			';
					$master	= $conn->prepare("SELECT id_act, nama_act FROM account_type ORDER BY nama_act ASC");
					$master->execute();
					while($hasil	= $master->fetchObject()){
						$tabel	.= '<optgroup label="'.$hasil->nama_act.'">';
						$master1	= $conn->prepare("SELECT id_acc, kode_acc, nama_acc FROM account_chart WHERE id_act=:kode ORDER BY nama_acc ASC");
						$master1->bindParam(":kode", $hasil->id_act, PDO::PARAM_STR);
						$master1->execute();
						while($hasil1	= $master1->fetchObject()){
							$tabel	.= '<option value="'.$hasil1->id_acc.'">'.$hasil1->nama_acc.'</option>';
						}
						$tabel	.= '</optgroup>';
					}
			$tabel	.= '
					</select>
				</div>
				<div class="col-sm-2">
					<label>Tax Rate <span class="tx-danger">*</span></label>
					<select name="taxpurchase" class="form-control" required="required">
						<option value="">-- Pilih --</option>
				';
				$master	= $conn->prepare("SELECT * FROM tax ORDER BY id_tax ASC");
				$master->execute();
				while($hasil	= $master->fetchObject()){
					$tabel	.= '<option value="'.$hasil->id_tax.'">'.$hasil->nama_tax.'</option>';
				}
				$tabel	.= '
					</select>
				</div>
				<div class="col-sm-4">
					<label>Purchase Description (for suppliers) <span class="tx-danger">*</span></label>
					<textarea name="deskpurchase" class="form-control" required="required"></textarea>
				</div>
			</div>
			<div class="clearfix mg-t-25 mg-b-25"></div>
			<h5 id="section1" class="tx-semibold">I sell this item</h5>
			<div class="row row-sm">
				<div class="col-sm-3">
					<label>Sales Unit Price <span class="tx-danger">*</span></label>
					<input type="text" name="pricesell" class="form-control" required="required"  />
				</div>
				<div class="col-sm-3">
					<label>Sales Account <span class="tx-danger">*</span></label>
					<select name="accsell" class="form-control" required="required">
						<option value="">-- Select Sales Account --</option>
			';
					$master	= $conn->prepare("SELECT id_act, nama_act FROM account_type ORDER BY nama_act ASC");
					$master->execute();
					while($hasil	= $master->fetchObject()){
						$tabel	.= '<optgroup label="'.$hasil->nama_act.'">';
						$master1	= $conn->prepare("SELECT id_acc, kode_acc, nama_acc FROM account_chart WHERE id_act=:kode ORDER BY nama_acc ASC");
						$master1->bindParam(":kode", $hasil->id_act, PDO::PARAM_STR);
						$master1->execute();
						while($hasil1	= $master1->fetchObject()){
							$tabel	.= '<option value="'.$hasil1->id_acc.'">'.$hasil1->nama_acc.'</option>';
						}
						$tabel	.= '</optgroup>';
					}
			$tabel	.= '
					</select>
				</div>
				<div class="col-sm-2">
					<label>Tax Rate <span class="tx-danger">*</span></label>
					<select name="taxsell" class="form-control" required="required">
						<option value="">-- Pilih --</option>
				';
				$master	= $conn->prepare("SELECT * FROM tax ORDER BY id_tax ASC");
				$master->execute();
				while($hasil	= $master->fetchObject()){
					$tabel	.= '<option value="'.$hasil->id_tax.'">'.$hasil->nama_tax.'</option>';
				}
				$tabel	.= '
					</select>
				</div>
				<div class="col-sm-4">
					<label>Sales Description (for customers) <span class="tx-danger">*</span></label>
					<textarea name="desksell" class="form-control" required="required"></textarea>
				</div>
			</div>';
			echo($tabel);
		break;
		case 'purchase':
			$tabel	= '
			<div class="clearfix mg-t-25 mg-b-25"></div>
			<h5 id="section1" class="tx-semibold">I purchase this item</h5>
			<div class="row row-sm">
				<div class="col-sm-3">
					<label>Buy Unit Price <span class="tx-danger">*</span></label>
					<input type="text" name="pricepurchase" class="form-control" required="required"  />
				</div>
				<div class="col-sm-3">
					<label>Purchase Account <span class="tx-danger">*</span></label>
					<select name="accpurchase" class="form-control" required="required">
						<option value="">-- Select Purchase Account --</option>
			';
					$master	= $conn->prepare("SELECT id_act, nama_act FROM account_type ORDER BY nama_act ASC");
					$master->execute();
					while($hasil	= $master->fetchObject()){
						$tabel	.= '<optgroup label="'.$hasil->nama_act.'">';
						$master1	= $conn->prepare("SELECT id_acc, kode_acc, nama_acc FROM account_chart WHERE id_act=:kode ORDER BY nama_acc ASC");
						$master1->bindParam(":kode", $hasil->id_act, PDO::PARAM_STR);
						$master1->execute();
						while($hasil1	= $master1->fetchObject()){
							$tabel	.= '<option value="'.$hasil1->id_acc.'">'.$hasil1->nama_acc.'</option>';
						}
						$tabel	.= '</optgroup>';
					}
			$tabel	.= '
					</select>
				</div>
				<div class="col-sm-2">
					<label>Tax Rate <span class="tx-danger">*</span></label>
					<select name="taxpurchase" class="form-control" required="required">
						<option value="">-- Pilih --</option>
				';
				$master	= $conn->prepare("SELECT * FROM tax ORDER BY id_tax ASC");
				$master->execute();
				while($hasil	= $master->fetchObject()){
					$tabel	.= '<option value="'.$hasil->id_tax.'">'.$hasil->nama_tax.'</option>';
				}
			$tabel	.= '
					</select>
				</div>
				<div class="col-sm-4">
					<label>Purchase Description (for suppliers) <span class="tx-danger">*</span></label>
					<textarea name="deskpurchase" class="form-control" required="required"></textarea>
				</div>
			</div>';
			echo($tabel);
		break;
		case 'sell':
			$tabel	= '
			<div class="clearfix mg-t-25 mg-b-25"></div>
			<h5 id="section1" class="tx-semibold">I sell this item</h5>
			<div class="row row-sm">
				<div class="col-sm-3">
					<label>Sales Unit Price <span class="tx-danger">*</span></label>
					<input type="text" name="pricesell" class="form-control" required="required"  />
				</div>
				<div class="col-sm-3">
					<label>Sales Account <span class="tx-danger">*</span></label>
					<select name="accsell" class="form-control" required="required">
						<option value="">-- Select Sales Account --</option>
			';
					$master	= $conn->prepare("SELECT id_act, nama_act FROM account_type ORDER BY nama_act ASC");
					$master->execute();
					while($hasil	= $master->fetchObject()){
						$tabel	.= '<optgroup label="'.$hasil->nama_act.'">';
						$master1	= $conn->prepare("SELECT id_acc, kode_acc, nama_acc FROM account_chart WHERE id_act=:kode ORDER BY nama_acc ASC");
						$master1->bindParam(":kode", $hasil->id_act, PDO::PARAM_STR);
						$master1->execute();
						while($hasil1	= $master1->fetchObject()){
							$tabel	.= '<option value="'.$hasil1->id_acc.'">'.$hasil1->nama_acc.'</option>';
						}
						$tabel	.= '</optgroup>';
					}
			$tabel	.= '
					</select>
				</div>
				<div class="col-sm-2">
					<label>Tax Rate <span class="tx-danger">*</span></label>
					<select name="taxsell" class="form-control" required="required">
						<option value="">-- Pilih --</option>
				';
				$master	= $conn->prepare("SELECT * FROM tax ORDER BY id_tax ASC");
				$master->execute();
				while($hasil	= $master->fetchObject()){
					$tabel	.= '<option value="'.$hasil->id_tax.'">'.$hasil->nama_tax.'</option>';
				}
				$tabel	.= '
					</select>
				</div>
				<div class="col-sm-4">
					<label>Sales Description (for customers) <span class="tx-danger">*</span></label>
					<textarea name="desksell" class="form-control" required="required"></textarea>
				</div>
			</div>';
			echo($tabel);
		break;
	}
	$conn	= $base->close();
?>