<style>
    .modern-header {
        background: #5a67d8;
        padding: 25px 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(90, 103, 216, 0.3);
        border: 2px solid #4c51bf;
        animation: slideDown 0.5s ease-out;
    }
    
    .modern-header h4 {
        color: white;
        font-weight: 600;
        margin: 0;
        font-size: 24px;
        text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .breadcrumb-modern {
        background: transparent;
        padding: 0;
        margin: 0 0 10px 0;
    }
    
    .breadcrumb-modern .breadcrumb-item {
        color: rgba(255,255,255,0.8);
        font-size: 13px;
    }
    
    .breadcrumb-modern .breadcrumb-item a {
        color: white;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .breadcrumb-modern .breadcrumb-item a:hover {
        color: #ffd700;
    }
    
    .breadcrumb-modern .breadcrumb-item.active {
        color: white;
        font-weight: 500;
    }
    
    .module-card {
        background: white;
        border-radius: 8px;
        padding: 14px;
        margin-bottom: 12px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border: 2px solid #e2e8f0;
        animation: fadeIn 0.6s ease-out;
        animation-fill-mode: both;
    }
    
    .module-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        border-color: #5a67d8;
    }
    
    .module-icon {
        width: 42px;
        height: 42px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 10px;
        transition: all 0.3s;
    }
    
    .module-card:hover .module-icon {
        transform: scale(1.1) rotate(5deg);
    }
    
    .module-title {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 5px;
        color: #2d3748;
    }
    
    .module-desc {
        font-size: 11px;
        color: #718096;
        margin-bottom: 10px;
        line-height: 1.3;
    }
    
    .module-btn {
        width: 100%;
        padding: 8px 14px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s;
        border: 2px solid transparent;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .module-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }
    
    .btn-solid-blue {
        background: #5a67d8;
        color: white;
        border-color: #4c51bf;
    }
    
    .btn-solid-blue:hover {
        background: #4c51bf;
        color: white;
    }
    
    .btn-solid-green {
        background: #48bb78;
        color: white;
        border-color: #38a169;
    }
    
    .btn-solid-green:hover {
        background: #38a169;
        color: white;
    }
    
    .btn-solid-orange {
        background: #ed8936;
        color: white;
        border-color: #dd6b20;
    }
    
    .btn-solid-orange:hover {
        background: #dd6b20;
        color: white;
    }
    
    .btn-solid-cyan {
        background: #4299e1;
        color: white;
        border-color: #3182ce;
    }
    
    .btn-solid-cyan:hover {
        background: #3182ce;
        color: white;
    }
    
    .refresh-btn {
        background: #5a67d8;
        color: white;
        border: 2px solid #4c51bf;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        transition: all 0.3s;
        box-shadow: 0 2px 10px rgba(90, 103, 216, 0.3);
    }
    
    .refresh-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(90, 103, 216, 0.4);
        background: #4c51bf;
        color: white;
    }
    
    .stats-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 5px;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .module-card:nth-child(1) { animation-delay: 0.1s; }
    .module-card:nth-child(2) { animation-delay: 0.2s; }
    .module-card:nth-child(3) { animation-delay: 0.3s; }
    .module-card:nth-child(4) { animation-delay: 0.4s; }
    
    @media (max-width: 768px) {
        .modern-header {
            padding: 20px;
        }
        .modern-header h4 {
            font-size: 20px;
        }
        .module-card {
            margin-bottom: 15px;
        }
    }
</style>

<div class="modern-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb breadcrumb-modern">
            <li class="breadcrumb-item"><a href="#"><i class="fa fa-home"></i> Home</a></li>
            <li class="breadcrumb-item"><a href="#">Faktur Pengeluaran Barang</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
        </ol>
    </nav>
    <h4><i class="fa fa-file-invoice"></i> Faktur Pengeluaran Barang</h4>
</div>

<?php $cari = $secu->injection(@$_GET['cari']); ?>
<input type="hidden" name="caridata" id="caridata" value="<?php echo($cari); ?>" readonly="readonly" />
<input type="hidden" name="halaman" id="halaman" value="1" readonly="readonly" />
<input type="hidden" name="maximal" id="maximal" value="15" readonly="readonly" />

<div class="content-body">
    <div class="row mg-b-20">
        <div class="col-sm-12">
            <a href="<?php echo($data->sistem('url_sis').'/fsalespe'); ?>">
                <button class="refresh-btn">
                    <i class="fa fa-sync-alt"></i> Refresh Data
                </button>
            </a>
        </div>
    </div>

    <?php require_once('config/frame/alert.php'); ?>

    <div class="row">
        <!-- Faktur Penjualan Cendo & DPE -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="module-card">
                <div class="module-icon" style="background: #5a67d8; border: 2px solid #4c51bf;">
                    <i class="fa fa-file-invoice" style="color: white;"></i>
                </div>
                <div class="module-title">
                    Faktur Penjualan 
                    <span class="stats-badge" style="background: #e3f2fd; color: #1976d2; border: 1px solid #90caf9;">AKTIF</span>
                </div>
                <div class="module-desc">
                    Kelola Faktur Penjualan .
                </div>
                <a href="<?php echo($data->sistem('url_sis').'/fsales'); ?>" style="text-decoration: none;">
                    <button class="module-btn btn-solid-blue">
                        <i class="fa fa-arrow-right"></i> Buka Modul
                    </button>
                </a>
            </div>
        </div>

        <!-- Faktur Penjualan PIM Harga Tayang -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="module-card">
                <div class="module-icon" style="background: #48bb78; border: 2px solid #38a169;">
                    <i class="fa fa-file-alt" style="color: white;"></i>
                </div>
                <div class="module-title">
                    Faktur Penjualan PIM Harga Tayang
                    <span class="stats-badge" style="background: #e8f5e9; color: #2e7d32; border: 1px solid #81c784;">PIM</span>
                </div>
                <div class="module-desc">
                    Faktur penjualan PIM dengan harga yang sudah ditampilkan. Cocok untuk penjualan dengan harga fix.
                </div>
                <a href="<?php echo($data->sistem('url_sis').'/fsalespim'); ?>" style="text-decoration: none;">
                    <button class="module-btn btn-solid-green">
                        <i class="fa fa-arrow-right"></i> Buka Modul
                    </button>
                </a>
            </div>
        </div>

        <!-- Faktur Penjualan PIM Nego E-Katalog -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="module-card">
                <div class="module-icon" style="background: #ed8936; border: 2px solid #dd6b20;">
                    <i class="fa fa-handshake" style="color: white;"></i>
                </div>
                <div class="module-title">
                    Faktur Penjualan PIM Nego E-Katalog
                    <span class="stats-badge" style="background: #fff3e0; color: #e65100; border: 1px solid #ffb74d;">NEGO</span>
                </div>
                <div class="module-desc">
                    Faktur penjualan PIM dengan sistem negosiasi harga melalui E-Katalog. Fleksibel dan transparan.
                </div>
                <a href="<?php echo($data->sistem('url_sis').'/fsalespim2'); ?>" style="text-decoration: none;">
                    <button class="module-btn btn-solid-orange">
                        <i class="fa fa-arrow-right"></i> Buka Modul
                    </button>
                </a>
            </div>
        </div>

        <!-- Faktur Konsinyasi -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="module-card">
                <div class="module-icon" style="background: #4299e1; border: 2px solid #3182ce;">
                    <i class="fa fa-exchange-alt" style="color: white;"></i>
                </div>
                <div class="module-title">
                    Faktur Konsinyasi
                    <span class="stats-badge" style="background: #e1f5fe; color: #0277bd; border: 1px solid #4fc3f7;">NEW</span>
                </div>
                <div class="module-desc">
                    Kelola faktur konsinyasi dengan tracking stok barang di outlet. Sistem monitoring real-time.
                </div>
                <a href="<?php echo($data->sistem('url_sis').'/fsalesk'); ?>" style="text-decoration: none;">
                    <button class="module-btn btn-solid-cyan">
                        <i class="fa fa-arrow-right"></i> Buka Modul
                    </button>
                </a>
            </div>
        </div>

        <!-- Faktur Manual -->
        <div class="col-lg-6 col-md-6 col-sm-12">
            <div class="module-card">
                <div class="module-icon" style="background: #dc3545; border: 2px solid #b02a37;">
                    <i class="fa fa-check" style="color: white;"></i>
                </div>
                <div class="module-title">
                    Faktur Manual
                    <span class="stats-badge" style="background: #fdecea; color: #b71c1c; border: 1px solid #ef9a9a;">MR</span>
                </div>
                <div class="module-desc">
                    Daftar faktur manual yang dibuat dari faktur penjualan dengan data MR. Cetak Surat Pengantar Barang.
                </div>
                <a href="<?php echo($data->sistem('url_sis').'/fsales/daftarmanual'); ?>" style="text-decoration: none;">
                    <button class="module-btn" style="background: #dc3545; border-color: #dc3545; color: white;">
                        <i class="fa fa-arrow-right"></i> Buka Modul
                    </button>
                </a>
            </div>
        </div>
    </div>

    <div class="table-responsive" style="margin-top: 30px;">
        <table class="table table-hover mg-b-0">
            <tbody id="isitabel"></tbody>
        </table>
        <div class="mg-t-10">
            <nav aria-label="Page navigation example">
                <ul class="pagination pagination-circle mg-b-0" id="paginasi"></ul>
            </nav>
        </div>
    </div>
</div>