<?php

// Ambil nama produk dari database
$namaProduk = null;
if (!empty($kode)) {
    $read = $conn->prepare("SELECT nama_pro FROM produk WHERE id_pro = :kode");
    $read->bindParam(':kode', $kode, PDO::PARAM_STR);
    $read->execute();
    $result = $read->fetch(PDO::FETCH_ASSOC);
    $namaProduk = $result ? $result['nama_pro'] : null;
}

// Generate opsi tahun
$tahunSekarang = date('Y');
$tahunOptions = "";
for ($i = $tahunSekarang; $i >= $tahunSekarang - 5; $i--) {
    $selected = ($i == $tahunSekarang) ? 'selected' : '';
    $tahunOptions .= "<option value='$i' $selected>$i</option>";
}

?>

<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Grafik Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Analisis</h4>
    </div>
</div>

<div class="content-body">
    <div class="component-section no-code">
        <form id="formsalespnp" action="#" method="post" autocomplete="off">
        <div class="row row-sm">
            <div class="col-sm-3">
            <label for="produkSelect">Produk<span class="tx-danger">*</span></label>
            <select id="produkSelect" class="form-control select2" required="required">
                    <option value="">Pilih Produk</option>
                    <?php
                    $produkQuery = $conn->query("SELECT id_pro, nama_pro FROM produk ORDER BY nama_pro ASC");
                    while ($row = $produkQuery->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($kode == $row['id_pro']) ? 'selected' : '';
                        echo "<option value='{$row['id_pro']}' $selected>{$row['nama_pro']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-sm-3">
            <label for="tahunSelect">Tahun<span class="tx-danger">*</span></label>
            <select id="tahunSelect" class="form-control select2" required="required">
                    <?= $tahunOptions; ?>
                </select>
            </div>
            <div class="col-sm-3">
            <label for="cabangSelect">Cabang<span class="tx-danger">*</span></label>
            <select id="cabangSelect" class="form-control select2" required="required">
                    <option value="">Pilih Cabang</option>

                </select>
            </div>
            <div class="col-sm-3">
            <label for="outletSelect">Outlet<span class="tx-danger">*</span></label>
                <select id="outletSelect" class="form-control select2" required="required">
                	<option value="">-- Pilih --</option>
				<?php
                    $outletQuery = $conn->query("SELECT id_out, nama_out FROM outlet ORDER BY nama_out ASC");
                    while ($row = $outletQuery->fetch(PDO::FETCH_ASSOC)) {
                        $selected = ($kode == $row['id_out']) ? 'selected' : '';
                        echo "<option value='{$row['id_out']}' $selected>{$row['nama_out']}</option>";
                    }
				?>
                </select>
            </div>
            <div class="col-sm-3 mg-t-10">
                <label> N/NP</label> <span class="tx-danger">*</span></label> <br>
                <input type="checkbox" name="" value="N"> N<br>
                <input type="checkbox" name="" value="NP"> NP<br>
            </div>
        </div><!-- row -->
        <div class="clearfix mg-t-25 mg-b-25"></div>
		</form>
    </div>
</div>

<!-- Grafik -->
<div class="row d-flex justify-content-center">
    <div class="col-11 text-center">
        <h5 id="judulGrafik" class="text-center">
            <?= htmlspecialchars($namaProduk ?? 'Silakan pilih produk'); ?>
        </h5>
        <div id="loadingMessage" style="display:none;">🔄 Memuat data...</div>
        <canvas id="grafik1" width="800" height="400"></canvas>
    </div>
</div>

<!-- Import library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {
    let ctx = document.getElementById('grafik1').getContext('2d');
    let grafik = null;

    function fetchData(id_pro, tahun, id_out) {
        if (!id_pro) {
            alert("Pilih produk terlebih dahulu!");
            $("#judulGrafik").text("Silakan pilih produk");
            return;
        }

        $("#loadingMessage").show();
        $('#grafik1').css('opacity', '0.5');

        $.ajax({
            url: './ajax/produkgrafik/produkg.php',
            type: 'GET',
            data: { id_pro: id_pro, tahun: tahun, id_out: id_out },
            dataType: 'json',
            success: function (data) {
                $("#loadingMessage").hide();
                $('#grafik1').css('opacity', '1');

                if (data.error) {
                    alert(data.error);
                    return;
                }

                $('#judulGrafik').text(data.nama_produk || 'Produk Tidak Ditemukan');

                if (grafik) {
                    grafik.destroy();
                }

                // ✅ Menentukan nilai tertinggi
                let maxVal = Math.max(...data.outData);

                // ✅ Gunakan warna berbeda untuk nilai tertinggi
                let backgroundColors = data.outData.map(value => 
                    value === maxVal ? 'rgba(0, 255, 0, 0.89)' : 'rgba(164, 3, 3, 0.89)'
                );

                grafik = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Stok - ' + tahun,
                                type: 'line',
                                data: data.stokData,
                                borderColor: 'green',
                                backgroundColor: 'rgba(0, 255, 30, 0.88)',
                                fill: false,
                                tension: 0.1
                            },
                            {
                                label: 'Penjualan - ' + tahun,
                                type: 'bar',
                                data: data.outData,
                                borderColor: 'red',
                                backgroundColor: backgroundColors, // ✅ Warna dinamis
                                borderWidth: 1,
                                fill: false,
                                tension: 0.1
                            },
                            {
                                label: 'Penjualan Tertinggi - ' + tahun,
                                type: 'bar',
                                data: data.outallData,
                                borderColor: 'blue',
                                backgroundColor: 'rgb(0, 3, 165)',
                                borderWidth: 1,
                                fill: false,
                                tension: 0.1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true }
                        },
                        plugins: {
                            legend: { position: 'top' }
                        }
                    }
                });
            },
            error: function (xhr, status, error) {
                console.error("Error fetching data:", error);
                $("#loadingMessage").hide();
                $('#grafik1').css('opacity', '1');
                alert("Gagal mengambil data! Periksa koneksi atau hubungi IT.");
            }
        });
    }

    // Event saat dropdown produk, tahun, outlet berubah
    $('#produkSelect, #tahunSelect, #outletSelect').change(function () {
        let selectedId = $('#produkSelect').val();
        let selectedYear = $('#tahunSelect').val();
        let selectedOutlet = $('#outletSelect').val();
        fetchData(selectedId, selectedYear, selectedOutlet);
    });

    // Load pertama kali jika ada produk default
    let defaultId = $('#produkSelect').val();
    let defaultYear = $('#tahunSelect').val();
    let defaultOutlet = $('#outletSelect').val();
    if (defaultId) {
        fetchData(defaultId, defaultYear, defaultOutlet);
    }
});
</script>

<style>
canvas {
    max-height: 400px; /* Batasi tinggi grafik */
    max-width: 600;
}
</style>