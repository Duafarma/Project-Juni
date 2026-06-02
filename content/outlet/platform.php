<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Home</a></li>
                <li class="breadcrumb-item">Mitra</li>
                <li class="breadcrumb-item active" aria-current="page">Outlet</li>
            </ol>
        </nav>
        <h4 class="content-title">Update Platfond & Outstanding - Outlet</h4>
    </div>
</div>

<?php
    $kode = $secu->injection($_GET['keycode']);

    // Handle update (POST)
    $alert = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (@$_POST['nact'] === 'updatePlatformLimit')) {
        $platform = $secu->injection(@$_POST['platform']); // outlet.platform
        $limit    = (int) (@$_POST['limit']);              // outlet.limit
        if ($limit < 0) { $limit = 0; }

        $catat = date('Y-m-d H:i:s');
        $adminUser = isset($admin) ? $admin : '';

        try {
            $conn->beginTransaction();

            // UPDATE hanya ke tabel outlet (platform & limit ada di outlet)
            $u1 = $conn->prepare("UPDATE outlet 
                SET platform=:platform, `limit`=:limit, updated_at=:catat, updated_by=:admin
                WHERE id_out=:code");
            $u1->bindParam(':platform', $platform, PDO::PARAM_STR);
            $u1->bindParam(':limit', $limit, PDO::PARAM_INT);
            $u1->bindParam(':catat', $catat, PDO::PARAM_STR);
            $u1->bindParam(':admin', $adminUser, PDO::PARAM_STR);
            $u1->bindParam(':code', $kode, PDO::PARAM_STR);
            $u1->execute();

            // Riwayat (pakai prepared statement biar aman)
            $rh = $conn->prepare("INSERT INTO riwayat VALUES('', :kode, 'Outlet Platform/Limit', 'Update', '', :catat, :admin)");
            $rh->bindParam(':kode', $kode, PDO::PARAM_STR);
            $rh->bindParam(':catat', $catat, PDO::PARAM_STR);
            $rh->bindParam(':admin', $adminUser, PDO::PARAM_STR);
            $rh->execute();

            $conn->commit();
            $alert = ['type' => 'success', 'msg' => 'Berhasil Update Platfond.'];
        } catch (Exception $e) {
            if ($conn->inTransaction()) { $conn->rollBack(); }
            $alert = ['type' => 'danger', 'msg' => 'Gagal update: '.$e->getMessage()];
        }
    }

    // Read current data (tabel outlet saja)
    $read = $conn->prepare("
        SELECT 
            nama_out, resmi_out, platform, `limit`
        FROM outlet
        WHERE id_out=:kode
        LIMIT 1
    ");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $view = $read->fetch(PDO::FETCH_ASSOC);
?>

<div class="content-body">
    <div class="component-section no-code">

        <?php if (!empty($alert)) { ?>
            <div class="alert alert-<?php echo($alert['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo($alert['msg']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php } ?>

        <form method="post" autocomplete="off">
            <input type="hidden" name="nact" value="updatePlatformLimit" />
            <input type="hidden" name="keycode" value="<?php echo htmlspecialchars($kode); ?>" />

            <div class="row">
                <div class="form-group col-sm-5">
                    <label>Platfond <span class="tx-danger">*</span></label>
                    <!-- hidden field yang dikirim ke server (tanpa titik) -->
                    <input type="hidden" name="platform" id="platform" value="<?php echo htmlspecialchars((int)($view['platform'] ?? 0)); ?>" />

                    <!-- field tampilan yang menggunakan titik sebagai pemisah ribuan -->
                    <input type="text" id="platform_display" class="form-control"
                        value="<?php echo ((int)($view['platform'] ?? 0)) > 0 ? htmlspecialchars(number_format((int)$view['platform'], 0, ',', '.')) : ''; ?>"
                        placeholder="0" inputmode="numeric" autocomplete="off" required />
                </div>
                <div class="form-group col-sm-4" style="display:none;">
                    <label>Limit<span class="tx-danger">*</span></label>
                    <!-- hidden field yang dikirim ke server (tanpa titik) -->
                    <input type="hidden" name="limit" id="limit" value="<?php echo htmlspecialchars((int)($view['limit'] ?? 0)); ?>" />

                    <!-- field tampilan yang menggunakan titik sebagai pemisah ribuan (bisa dihapus) -->
                    <input type="text" id="limit_display" class="form-control"
                        value="<?php echo ((int)($view['limit'] ?? 0)) > 0 ? htmlspecialchars(number_format((int)$view['limit'], 0, ',', '.')) : ''; ?>"
                        placeholder="0" inputmode="numeric" autocomplete="off" />

                    <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        var display = document.getElementById('limit_display');
                        var hidden  = document.getElementById('limit');
                        if(!display || !hidden) return;

                        // replace node to remove any previously attached listeners (clean slate)
                        var clone = display.cloneNode(true);
                        display.parentNode.replaceChild(clone, display);
                        display = clone;

                        function onlyDigits(str){
                            var d = (str || '').toString().replace(/\D+/g, '');
                            return d === '' ? '' : d;
                        }
                        function formatWithDots(value){
                            if(!value) return '';
                            return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        }

                        display.addEventListener('input', function(){
                            var digits = onlyDigits(this.value);
                            if(digits === ''){
                                this.value = '';
                                hidden.value = '';
                            } else {
                                this.value = formatWithDots(digits);
                                hidden.value = digits;
                            }
                        });

                        display.addEventListener('blur', function(){
                            var digits = onlyDigits(this.value);
                            if(digits === ''){
                                this.value = '0';
                                hidden.value = '0';
                            } else {
                                this.value = formatWithDots(digits);
                                hidden.value = digits;
                            }
                        });

                        // initialize: if hidden is 0 or empty, show blank (user can delete), otherwise show formatted
                        var hv = (hidden.value || '').toString().replace(/\D+/g, '');
                        hidden.value = hv === '' ? '' : hv;
                        display.value = hv === '' || hv === '0' ? (display.value || '') : formatWithDots(hv);
                    });
                    </script>
                </div>

                <div class="form-group col-sm-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark btn-xs">Update</button>
                    <a class="btn btn-secondary btn-xs ml-2" href="<?php echo($data->sistem('url_sis').'/outlet'); ?>">Kembali</a>
                </div>
            </div>
        </form>

        <hr>

        <h5 class="tx-semibold">Data Saat Ini</h5>
        <div class="table-responsive">
            <table class="table table-hover mg-b-0">
                <tbody>
                    <tr>
                        <th style="width:220px;">Nama Outlet</th>
                        <td><?php echo htmlspecialchars($view['nama_out'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Nama Resmi</th>
                        <td><?php echo htmlspecialchars($view['resmi_out'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <th>Platfond</th>
                        <td>
                            <?php
                                $p = $view['platform'] ?? null;
                                if ($p === null || $p === '') {
                                    echo '-';
                                } elseif (is_numeric($p)) {
                                    $num = (int)$p;
                                    echo htmlspecialchars('Rp '.number_format($num, 0, ',', '.'));
                                } else {
                                    echo htmlspecialchars($list_platform[$p] ?? $p);
                                }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Outstanding</th>
                        <td><?php
                            $lim = isset($view['limit']) ? (int)$view['limit'] : null;
                            echo $lim === null ? '-' : htmlspecialchars('Rp '.number_format($lim, 0, ',', '.'));
                        ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- tambahkan skrip ini di bawah form atau sebelum penutup </body> -->
<script>
(function(){
    const display = document.getElementById('limit_display');
    const hidden  = document.getElementById('limit');

    function formatWithDots(value){
        if(!value) return '0';
        return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    function onlyDigits(str){
        return (str || '').toString().replace(/\D+/g, '') || '0';
    }

    // Sync display -> hidden (on input)
    display.addEventListener('input', function(e){
        const digits = onlyDigits(this.value);
        this.value = formatWithDots(digits);
        hidden.value = digits;
    });

    // Ensure correct values on blur
    display.addEventListener('blur', function(){
        const digits = onlyDigits(this.value);
        this.value = formatWithDots(digits);
        hidden.value = digits;
    });

    // Initialize hidden (in case)
    hidden.value = onlyDigits(hidden.value);
    display.value = formatWithDots(hidden.value);
})();
</script>

<!-- tambahan untuk platform (samakan format dengan limit) -->
<script>
(function(){
    const pDisplay = document.getElementById('platform_display');
    const pHidden  = document.getElementById('platform');
    if(!pDisplay || !pHidden) return;

    function formatWithDots(value){
        if(!value) return '';
        return value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    function onlyDigits(str){
        return (str || '').toString().replace(/\D+/g, '');
    }

    pDisplay.addEventListener('input', function(){
        const digits = onlyDigits(this.value);
        if(digits === ''){
            this.value = '';
            pHidden.value = '';
        } else {
            this.value = formatWithDots(digits);
            pHidden.value = digits;
        }
    });

    pDisplay.addEventListener('blur', function(){
        const digits = onlyDigits(this.value);
        if(digits === ''){
            this.value = '0';
            pHidden.value = '0';
        } else {
            this.value = formatWithDots(digits);
            pHidden.value = digits;
        }
    });

    // initialize
    const hv = (pHidden.value || '').toString().replace(/\D+/g, '');
    pHidden.value = hv === '' ? '' : hv;
    pDisplay.value = hv === '' || hv === '0' ? (pDisplay.value || '') : formatWithDots(hv);
})();
</script>