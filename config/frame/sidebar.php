<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">Sistem</label>
    </li>
    <li class="nav-item"><a href="<?php echo("$sistem/home"); ?>" class="nav-link"><i data-feather="home"></i> Dashboard</a></li>
</ul>
<!--
<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">Quick Launch</label>
    </li>
    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="file-plus"></i> Create New Invoice</a></li>
    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="file-plus"></i> Create New Billing</a></li>
    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="file-plus"></i> Create New Expense</a></li>
    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="file-plus"></i> Create Manual Journal</a></li>
</ul>
<div class="clearfix mg-t-25 mg-b-25"></div>
<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">List Menu</label>
    </li>
    <li class="nav-item"><a href="<?php //echo("$sistem/home"); ?>" class="nav-link"><i data-feather="home"></i> Dashboard</a></li>
    <li class="nav-item"><a href="<?php //echo("$sistem/cashandbank"); ?>" class="nav-link"><i data-feather="credit-card"></i> Cash & Bank</a></li>
    <li class="nav-item"><a href="#" class="nav-link"><i data-feather="trending-up"></i> Reports</a></li>
</ul>
<hr class="mg-t-5 mg-b-5">
-->
<?php
	$nomor		= 1;
	$jkate		= $conn->query("SELECT COUNT(D.id_kmu) AS total FROM role_menu AS A INNER JOIN sub_menu AS B ON A.id_smu=B.id_smu INNER JOIN menu AS C ON B.id_menu=C.id_menu INNER JOIN kategori_menu AS D ON C.id_kmu=D.id_kmu WHERE A.id_adm='$admin' GROUP BY D.id_kmu")->fetch(PDO::FETCH_ASSOC);
	$mkate		= $conn->prepare("SELECT D.id_kmu, D.nama_kmu FROM role_menu AS A INNER JOIN sub_menu AS B ON A.id_smu=B.id_smu INNER JOIN menu AS C ON B.id_menu=C.id_menu INNER JOIN kategori_menu AS D ON C.id_kmu=D.id_kmu WHERE A.id_adm=:admin GROUP BY D.id_kmu ORDER BY D.urutan_kmu ASC");
	$mkate->bindParam(':admin', $admin, PDO::PARAM_STR);
	$mkate->execute();
	while($hkate= $mkate->fetch(PDO::FETCH_ASSOC)){
?>
    <ul class="nav nav-sidebar">
        <li class="nav-label">
            <label class="content-label"><?php echo($hkate['nama_kmu']); ?></label>
        </li>
	<?php
        $mmenu		= $conn->prepare("SELECT C.id_menu, C.nama_menu, D.nama_icon FROM role_menu AS A INNER JOIN sub_menu AS B ON A.id_smu=B.id_smu INNER JOIN menu AS C ON B.id_menu=C.id_menu INNER JOIN icon AS D ON C.id_icon=D.id_icon WHERE A.id_adm=:admin AND C.id_kmu=:kode GROUP BY C.id_menu ORDER BY C.urutan_menu ASC");
        $mmenu->bindParam(':kode', $hkate['id_kmu'], PDO::PARAM_STR);
        $mmenu->bindParam(':admin', $admin, PDO::PARAM_STR);
        $mmenu->execute();
        while($hmenu= $mmenu->fetch(PDO::FETCH_ASSOC)){
    ?>
        <li class="nav-item">
            <a href="" class="nav-link with-sub">
                <!-- <i data-feather="<?php echo($hmenu['nama_icon']); ?>"></i> -->
                <?php echo($hmenu['nama_menu']); ?>
            </a>
            <nav class="nav nav-sub">
			<?php
                $msume		= $conn->prepare("SELECT B.nama_smu, B.url_smu FROM role_menu AS A INNER JOIN sub_menu AS B ON A.id_smu=B.id_smu WHERE A.id_adm=:admin AND B.id_menu=:kode ORDER BY B.urutan_smu ASC");
                $msume->bindParam(':kode', $hmenu['id_menu'], PDO::PARAM_STR);
                $msume->bindParam(':admin', $admin, PDO::PARAM_STR);
                $msume->execute();
                while($hsume= $msume->fetch(PDO::FETCH_ASSOC)){
                    // Count outlet dengan status Rekomendasi SPV untuk menu outlet
                    $notifBadge = '';
                    if($hsume['url_smu'] == 'outlet') {
                        $countSPV = $conn->query("SELECT COUNT(*) as total FROM outlet WHERE status_out='Rekomendasi SPV'")->fetch(PDO::FETCH_ASSOC);
                        if($countSPV['total'] > 0) {
                            $notifBadge = ' <span class="badge badge-danger" style="font-size:10px;">'.$countSPV['total'].'</span>';
                        }
                    }
            ?>
                <a href="<?php echo("$sistem/$hsume[url_smu]"); ?>" class="nav-sub-link"><?php echo($hsume['nama_smu'].$notifBadge); ?></a>
			<?php } ?>
            </nav>
        </li>
	<?php } ?>
    </ul>
<?php $nomor++; echo(($nomor===$jkate['total']) ? '<hr class="mg-t-5 mg-b-5">' : '<div class="clearfix mg-t-25 mg-b-25"></div>'); } /*?>

<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">Master Data</label>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="cloud-lightning"></i> Master Data</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/provinsi"); ?>" class="nav-sub-link">Provinsi</a>
            <a href="<?php echo("$sistem/kabupaten"); ?>" class="nav-sub-link">Kabupaten</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="users"></i> Mitra</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/koutlet"); ?>" class="nav-sub-link">Kategori Outlet</a>
            <a href="<?php echo("$sistem/ksupplier"); ?>" class="nav-sub-link">Kategori Supplier</a>
            <a href="<?php echo("$sistem/klegal"); ?>" class="nav-sub-link">Kategori Legal</a>
            <a href="<?php echo("$sistem/outlet"); ?>" class="nav-sub-link">Outlet</a>
            <a href="<?php echo("$sistem/supplier"); ?>" class="nav-sub-link">Supplier</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="codesandbox"></i> Produk</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/kproduk"); ?>" class="nav-sub-link">Kategori Produk</a>
            <a href="<?php echo("$sistem/sproduk"); ?>" class="nav-sub-link">Satuan Produk</a>
            <a href="<?php echo("$sistem/produk"); ?>" class="nav-sub-link">Produk</a>
        </nav>
    </li>
</ul>
<div class="clearfix mg-t-25 mg-b-25"></div>
<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">Transaction</label>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="truck"></i> Pembelian</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/order"); ?>" class="nav-sub-link">Order</a>
            <a href="<?php echo("$sistem/rorder"); ?>" class="nav-sub-link">Peneriamaan Barang</a>
            <a href="<?php echo("$sistem/porder"); ?>" class="nav-sub-link">Pembayaran Supplier</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="shopping-cart"></i> Penjualan</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/fsales"); ?>" class="nav-sub-link">Faktur Penjualan</a>
            <a href="<?php echo("$sistem/psales"); ?>" class="nav-sub-link">Pembayaran Outlet</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="database"></i> Inventory</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/inventory"); ?>" class="nav-sub-link">Inventory</a>
        </nav>
    </li>
</ul>
<hr class="mg-t-5 mg-b-5">
<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">Report</label>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="printer"></i> Report</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/rstok"); ?>" class="nav-sub-link">Stok</a>
            <a href="<?php echo("$sistem/rpembelian"); ?>" class="nav-sub-link">Pembelian</a>
            <a href="<?php echo("$sistem/rpenjualan"); ?>" class="nav-sub-link">Penjualan</a>
            <a href="<?php echo("$sistem/rspenjualan"); ?>" class="nav-sub-link">Summary Penjualan</a>
            <a href="<?php echo("$sistem/red"); ?>" class="nav-sub-link">Expired Date</a>
            <a href="<?php echo("$sistem/rjtoutlet"); ?>" class="nav-sub-link">Jatuh Tempo Outlet</a>
            <a href="<?php echo("$sistem/rjtsupplier"); ?>" class="nav-sub-link">Jatuh Tempo Supplier</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="pie-chart"></i> Grafik</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/gpembelian"); ?>" class="nav-sub-link">Pembelian</a>
            <a href="<?php echo("$sistem/rankpembelian"); ?>" class="nav-sub-link">Rank Pembelian</a>
            <a href="<?php echo("$sistem/gpenjualan"); ?>" class="nav-sub-link">Penjualan</a>
            <a href="<?php echo("$sistem/rankpenjualan"); ?>" class="nav-sub-link">Rank Penjualan</a>
        </nav>
    </li>
</ul>
<div class="clearfix mg-t-25 mg-b-25"></div>
<ul class="nav nav-sidebar">
    <li class="nav-label">
        <label class="content-label">Setting</label>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="user"></i> Pengguna</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/administrator"); ?>" class="nav-sub-link">Administrator</a>
            <a href="<?php echo("$sistem/account"); ?>" class="nav-sub-link">Account</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="key"></i> Role</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/kmenu"); ?>" class="nav-sub-link">Kategori Menu</a>
            <a href="<?php echo("$sistem/icon"); ?>" class="nav-sub-link">Icon</a>
            <a href="<?php echo("$sistem/menu"); ?>" class="nav-sub-link">Menu</a>
            <a href="<?php echo("$sistem/submenu"); ?>" class="nav-sub-link">Sub Menu</a>
        </nav>
    </li>
    <li class="nav-item">
        <a href="" class="nav-link with-sub"><i data-feather="shield"></i> Perusahaan</a>
        <nav class="nav nav-sub">
            <a href="<?php echo("$sistem/riwayat"); ?>" class="nav-sub-link">Riwayat</a>
            <a href="<?php echo("$sistem/sistem"); ?>" class="nav-sub-link">Perusahaan</a>
        </nav>
    </li>
</ul>
<hr class="mg-t-5 mg-b-5">
*/ ?>