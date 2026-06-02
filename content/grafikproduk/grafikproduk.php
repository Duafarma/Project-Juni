<?php
// Ambil daftar produk untuk dropdown
$produkQuery = $conn->query("SELECT id_pro, nama_pro FROM produk ORDER BY nama_pro ASC");

// Ambil tahun sekarang dan buat opsi tahun (5 tahun terakhir)
$tahunSekarang = date('Y');
$tahunOptions = "";
for ($i = $tahunSekarang; $i >= $tahunSekarang - 5; $i--) {
    $selected = ($i == $tahunSekarang) ? 'selected' : '';
    $tahunOptions .= "<option value='$i' $selected>$i</option>";
}
$cari	= $secu->injection(@$_GET['cari']);

// Jangan tutup koneksi di sini, karena akan digunakan untuk query outlet
?>
<!-- Ganti Chart.js dengan ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

<!-- Load Javascript apex chart stye on a config -->
<script src="./config/js/grapikproduk.js"></script>
<!-- Load css stye on a config -->
<link rel="stylesheet" href="./config/css/grapikproduk.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>




<!-- Breadcrumb dan Judul -->
<div class="content-header">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="#">Menu</a></li>
                <li class="breadcrumb-item"><a href="#">Master Data</a></li>
                <li class="breadcrumb-item active" aria-current="page">Grafik Penjualan</li>
            </ol>
        </nav>
        <h4 class="content-title">Analisis Penjualan</h4>
    </div>
</div>

<div class="content-body">
    <!-- Filter Container -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form id="formsalespnp" autocomplete="off">
                <div class="row">
                    <!-- Select Option Produk -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <label for="produk" class="form-label fw-medium">Pilih Produk:</label>
                        <select id="produk" class="form-control select2" required>
                            <option value="">Pilih Produk</option>
                            <option value="All" class="all-produk">All Produk</option>
                            <?php while ($row = $produkQuery->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $row['id_pro'] ?>"><?= $row['nama_pro'] ?></option>
                            <?php endwhile; ?>
                            <option value="a+b" class="a+b">System A+B</option>
                        </select>
                    </div>

                    <!-- Select Option Grup -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <label for="grup" class="form-label fw-medium">Pilih Grup:</label>
                        <select id="grup" name="grup" class="form-control select2" required>
                            <option value="">-- Pilih --</option>
                            <?php
                            $grupQuery = $conn->query("SELECT id_mg, nama_mg FROM master_grup ORDER BY nama_mg ASC");
                            while ($row = $grupQuery->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $row['id_mg'] ?>"><?= $row['nama_mg'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Select Option Outlet -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <label for="outlet" class="form-label fw-medium">Pilih Outlet:</label>
                        <select id="outlet" class="form-control select2">
                            <option value="">-- Pilih --</option>
                            <option value="All" class="all-grup">All Grup</option>
                        </select>
                    </div>

                    <!-- Select Option Cabang -->
                    <div class="col-md-3 col-sm-6 mb-3">
                        <label for="cabang" class="form-label fw-medium">Pilih Cabang:</label>
                        <select id="cabang" class="form-control select2">
                            <option value="">-- Pilih --</option>
                            <option value="all">All Cabang</option>
                            <option value="a_b">Puri + Puri B</option> <!-- Tambahkan baris ini -->
                            <?php
                            $cabangQuery = $conn->query("SELECT * FROM aplikasi WHERE active_apl = 1 ORDER BY nama_apl ASC");
                            while ($row = $cabangQuery->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?= $row['id_apl'] ?>"><?= $row['nama_apl'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Grafik Container -->
    <div class="card shadow">
        <div class="card-body">
            <div class="row d-flex justify-content-center">
                <div class="col-12 text-center">
                    <div class="mb-4">
                        <h5 id="judulGrafik" class="text-center fw-bold mb-2">Silakan pilih produk</h5>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <span id="judulGrup" class="badge bg-light text-dark border">Grup: -</span>
                            <span id="judulOutlet" class="badge bg-light text-dark border">Outlet: -</span>
                            <span id="judulCabang" class="badge bg-light text-dark border">Cabang: -</span>
                        </div>
                        <!-- Flag Informasi dengan Design Modern -->
                        <div class="flag-info mb-4 mt-4">
                            <div class="d-flex flex-wrap align-items-center justify-content-center gap-2">
                                <div class="flag-item d-flex align-items-center">
                                    <span class="color-dot"
                                        style="background-color: rgba(255, 99, 132, 0.7); border: 1px solid rgb(255, 99, 132);"></span>
                                    <span class="flag-text">Tahun <span id="tahun-1"></span></span>
                                </div>
                                <div class="flag-item d-flex align-items-center">
                                    <span class="color-dot"
                                        style="background-color: rgba(255, 205, 86, 0.7); border: 1px solid rgb(255, 205, 86);"></span>
                                    <span class="flag-text">Tahun <span id="tahun-2"></span></span>
                                </div>
                                <div class="flag-item d-flex align-items-center">
                                    <span class="color-dot"
                                        style="background-color: rgba(54, 162, 235, 0.7); border: 1px solid rgb(54, 162, 235);"></span>
                                    <span class="flag-text">Tahun <span id="tahun-3"></span></span>
                                </div>
                                <div class="flag-item d-flex align-items-center" id="flag-stok-awal">
                                    <span class="color-dot"
                                        style="background-color: rgb(180, 180, 180); border: 1px solid rgb(98, 98, 98);"></span>
                                    <span class="flag-text">Stok awal bulan</span>
                                </div>
                                <div class="flag-item d-flex align-items-center" id="flag-stok-real">
                                    <span class="color-dot"
                                        style="background-color: rgb(103, 251, 100); border: 1px solid rgb(83, 211, 80);"></span>
                                    <span class="flag-text">Stok Berjalan</span>
                                </div>
                                <!-- <div class="info-icon-container position-relative ml-2">
                                    <i class="fas fa-info-circle text-primary"
                                        style="font-size: 16px; cursor: help;"></i>
                                    <div class="info-tooltip">
                                        Grafik menampilkan penjualan untuk 3 tahun terakhir dengan urutan tumpukan
                                        sesuai nilai penjualan.<br>
                                        Garis hijau menunjukkan stok tersedia saat ini.<br>
                                        Posisi tahun di setiap bulan dapat berbeda tergantung pada nilai penjualan.
                                    </div>
                                </div> -->
                            </div>
                        </div>
                        <div id="dataSourceInfo" class="text-center my-2" style="display: none;"></div>
                    </div>

                    <div id="loadingMessage" class="py-3 text-center">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        </div>
                    </div>

                    <div class="chart-container">
                        <!-- pWE -->
                        <div id="transaksiChart"></div>
                        <div id="all-produk"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Set tahun pada flag secara dinamis
    document.addEventListener('DOMContentLoaded', function () {
        const tahunSekarang = new Date().getFullYear();
        document.getElementById('tahun-1').textContent = tahunSekarang - 2;
        document.getElementById('tahun-2').textContent = tahunSekarang - 1;
        document.getElementById('tahun-3').textContent = tahunSekarang;
    });

    function loadOutletsByGrup() {
        const grupDropdown = document.getElementById('grup');
        const outletDropdown = document.getElementById('outlet');
        const selectedGrup = grupDropdown.value;

        // Reset dropdown outlet
        outletDropdown.innerHTML = '<option value="">-- Pilih --</option>';
        outletDropdown.disabled = true;

        if (selectedGrup) {
            console.log('Memuat outlet untuk grup ID:', selectedGrup);

            // Kirim permintaan ke server
            fetch(`/192.268.908.09/ajax/get_outlets/get_outlets.php?id_mg=${encodeURIComponent(selectedGrup)}`)
                .then(response => {
                    console.log('Status respons:', response.status);
                    if (!response.ok) {
                        throw new Error('Gagal memuat data outlet');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Respons dari server:', data);
                    if (data.success) {
                        // Tambahkan opsi "All Grup"
                        const allOption = document.createElement('option');
                        allOption.value = 'All';
                        allOption.textContent = 'All Grup';
                        allOption.classList.add('all-grup');
                        outletDropdown.appendChild(allOption);

                        // Tambahkan opsi ke dropdown outlet
                        data.outlets.forEach(outlet => {
                            const option = document.createElement('option');
                            option.value = outlet.id_out;
                            option.textContent = outlet.nama_out;
                            outletDropdown.appendChild(option);
                        });
                        outletDropdown.disabled = false; // Aktifkan dropdown
                    } else {
                        alert(data.message || 'Gagal memuat outlet.');
                    }
                })
                .catch(error => {
                    console.error('Terjadi kesalahan:', error);
                    alert('Terjadi kesalahan saat memuat outlet.');
                });
        }
    }

</script>

<?php

// Tutup koneksi database setelah seluruh pemrosesan selesai
$conn = $base->close();
?>