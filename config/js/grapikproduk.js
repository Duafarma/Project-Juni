/**
 * Feri.js - Product chart visualization
 * This file contains JavaScript functions for product chart visualization
 * Created by feri and ruli
 */
$(document).ready(function () {
  // Sembunyikan container all-produk pada awalnya
  $("#all-produk").hide();

  // Format Rupiah
  function formatRupiah(angka) {
    if (angka === 0 || !angka) return "Rp 0";
    if (angka >= 1000000000) return "Rp " + (angka / 1000000000).toFixed(1) + " M";
    if (angka >= 1000000) return "Rp " + (angka / 1000000).toFixed(1) + " Jt";
    if (angka >= 1000) return "Rp " + (angka / 1000).toFixed(1) + " Rb";
    return "Rp " + Math.round(angka);
  }

  // Format Unit
  function formatUnit(val) {
    if (val >= 1000) return (val / 1000).toFixed(1) + "k";
    return Math.round(val);
  }

  // ===================== ALL PRODUCTS CHART =====================
  function loadAllProductsChart(id_out = "", id_apl = "", id_mg = "") {
    let url = `./ajax/produkgrafik/produkg2.php?all_produk=1`;
    if (id_out) url += `&id_out=${id_out}`;
    if (id_apl) url += `&id_apl=${id_apl}`;
    if (id_mg) url += `&id_mg=${id_mg}`;

    $("#loadingMessage").show();
    $("#transaksiChart").hide();
    $("#all-produk").show();

    let customizationApplied = false;

    fetch(url)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            // Check content type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // If not JSON, get text for debugging
                return response.text().then(text => {
                    console.error('Response is not JSON:', text.substring(0, 500)); 
                    throw new Error('Response is not in JSON format');
                });
            }
            
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Raw response:', text.substring(0, 500)); 
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then((jsonData) => {
            console.log("Parsed API Response:", jsonData);
            $("#loadingMessage").hide();

            // Info sumber data
            if (jsonData.source) {
              const sourceType = jsonData.source.type === "api" ? "API" : "Database Lokal";
              $("#judulCabang").text(`Cabang: ${jsonData.source.name} (${sourceType})`);
              $("#dataSourceInfo").html(`<div class="badge badge-info">Data dari ${sourceType}</div>`).show();
            } else {
              $("#judulCabang").text("Cabang: -");
              $("#dataSourceInfo").hide();
            }

            const allProdukContainer = document.getElementById("all-produk");
            allProdukContainer.innerHTML = "";

            if (!jsonData.series || jsonData.series.length === 0) {
              allProdukContainer.innerHTML = `<div style="text-align: center; padding: 20px; color: #d9534f;">
                    <strong>Tidak ada data:</strong> Tidak ditemukan data penjualan<br>
                    <small>Silahkan pilih periode waktu yang berbeda</small>
                </div>`;
              return;
            }

            try {
              let bulan_labels = jsonData.bulan_labels;
              let series = jsonData.series;
              const monthlyOrders = jsonData.monthlyOrders || {};

              const yearColors = {
                [jsonData.tahun1]: "rgba(255, 99, 132, 0.7)",
                [jsonData.tahun2]: "rgba(255, 205, 86, 0.7)",
                [jsonData.tahun3]: "rgba(54, 162, 235, 0.7)",
              };
              const yearBorderColors = {
                [jsonData.tahun1]: "rgb(255, 99, 132)",
                [jsonData.tahun2]: "rgb(255, 205, 86)",
                [jsonData.tahun3]: "rgb(54, 162, 235)",
              };
              const yearMarkerColors = {
                [jsonData.tahun1]: "rgb(210, 84, 110)",     // lebih gelap dari merah
                [jsonData.tahun2]: "rgb(215, 172, 72)",    // lebih gelap dari kuning
                [jsonData.tahun3]: "rgb(46, 138, 200)",     // lebih gelap dari biru
              };

              // Gabungkan data berdasarkan tahun
              const combinedSeries = {};
              for (let bulanIndex = 0; bulanIndex < bulan_labels.length; bulanIndex++) {
                const bulanNomor = bulanIndex + 1;
                let tahunOrder;
                if (monthlyOrders && monthlyOrders[bulanNomor]) {
                  tahunOrder = monthlyOrders[bulanNomor];
                } else {
                  const bulanData = series.map((s) => ({
                    name: s.name,
                    value: s.data[bulanIndex],
                    tahun: parseInt(s.name.replace("Tahun ", "")),
                  }));
                  bulanData.sort((a, b) => a.value - b.value);
                  tahunOrder = bulanData.map((item) => item.tahun);
                }
                tahunOrder.forEach((tahun) => {
                  const seriData = series.find((s) => parseInt(s.name.replace("Tahun ", "")) === tahun);
                  if (!seriData) return;
                  if (!combinedSeries[tahun]) {
                    combinedSeries[tahun] = {
                      name: `Tahun ${tahun}`,
                      data: Array(bulan_labels.length).fill(0),
                      color: yearColors[tahun],
                      _year: tahun,
                    };
                  }
                  combinedSeries[tahun].data[bulanIndex] += seriData.data[bulanIndex];
                });
              }
              const allSeries = Object.values(combinedSeries).sort((a, b) => a._year - b._year);

              // Tambahkan series data bulan sebelumnya (tersembunyi)
              if (jsonData.transaksiRDBulanSebelumnya) {
                allSeries.push({
                  name: "BulanSebelumnya",
                  data: jsonData.transaksiRDBulanSebelumnya,
                  // Hidden series (tidak terlihat di chart)
                  type: 'line',
                  enabled: false,
                  showInLegend: false,
                  enabledOnSeries: undefined
                });
              }

              // Total keseluruhan
              let totalKeseluruhan = 0;
              Object.values(jsonData.totalPerTahun).forEach((total) => { totalKeseluruhan += total; });

              // Chart
              const allProdukChart = new ApexCharts(allProdukContainer, {
                series: allSeries,
                chart: {
                  type: "line",
                  height: 500,
                  stacked: false,
                  stackType: "normal",
                  toolbar: { show: true },
                  events: {
                    mounted: function (chartContext, config) {
                      if (!customizationApplied) {
                        setTimeout(() => {
                          customizeYAxis(allProdukContainer, chartContext);
                          customizationApplied = true;
                        }, 300);
                      }
                    }
                  },
                },
                stroke: {
                  width: 4,
                  curve: "straight",
                  colors: allSeries.map((series) => yearBorderColors[series._year]),
                },
                markers: {
                  size: 5,
                  shape: "circle",
                  colors: allSeries.map((series) => yearMarkerColors[series._year] || "#444"), // gunakan warna lebih tua
                  hover: { size: 6 },
                },
                plotOptions: {
                  bar: {
                    horizontal: false,
                    columnWidth: "50%",
                    borderRadius: 0,
                    borderWidth: 0,
                    borderColor: "transparent",
                    distributed: false,
                    endingShape: "flat",
                  },
                },
                dataLabels: {
                  enabled: true,
                  formatter: function (val) {
                    if (val === 0) return "";
                    return formatRupiah(val);
                  },
                  style: {
                    fontSize: "11px",
                    colors: ["#333"],
                    fontWeight: "bold",
                  },
                  offsetY: 0,
                  background: {
                    enabled: true,
                    foreColor: "#fff",
                    padding: 4,
                    borderRadius: 2,
                    borderWidth: 1,
                    borderColor: "rgba(0,0,0,0.05)",
                    opacity: 0.7,
                  },
                },
                xaxis: {
                  categories: bulan_labels,
                  labels: {
                    style: { fontSize: "12px", fontWeight: "bold" },
                  },
                  axisBorder: { show: true },
                  axisTicks: { show: true },
                },
                yaxis: {
                  show: true,
                  labels: {
                    show: true,
                    formatter: function (val) {
                      if (val >= 1000000000) return "Rp " + (val / 1000000000).toFixed(1) + " M";
                      if (val >= 1000000) return "Rp " + (val / 1000000).toFixed(1) + " Jt";
                      if (val >= 1000) return "Rp " + Math.round(val / 1000) + " Rb";
                      return "Rp " + Math.round(val);
                    },
                    style: { fontSize: "12px" },
                  },
                  axisBorder: { show: true, color: "#e0e0e0" },
                  axisTicks: { show: true, color: "#e0e0e0" },
                },
                grid: {
                  show: true,
                  borderColor: "#e0e0e0",
                  strokeDashArray: 4,
                  position: "back",
                },
                tooltip: {
                  shared: true,
                  intersect: false,
                  position: 'top', // Tooltip selalu di atas
                  custom: function({ series, seriesIndex, dataPointIndex, w }) {
                    const bulanNames = [
                      "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
                      "Jul", "Agu", "Sep", "Okt", "Nov", "Des"
                    ];
                    let barItems = [];
                    let stokItems = [];
                    
                    // Tambahkan informasi bulan sebelumnya
                    let bulanSebelumnyaInfo = "";
                    
                    // Cek apakah data bulan sebelumnya tersedia dalam series atau initialSeries
                    const bulanSebelumnyaValue = w.globals.series.find(s => s.name === "BulanSebelumnya")?.data[dataPointIndex];
                    
                    // Jika data tersedia
                    if (jsonData && jsonData.transaksiRDBulanSebelumnya && jsonData.transaksiRDBulanSebelumnya[dataPointIndex] > 0) {
                      // Ambil nama bulan sebelumnya
                      const bulanSekarang = dataPointIndex;
                      const bulanSebelumnya = bulanSekarang === 0 ? 11 : bulanSekarang - 1;
                      
                      bulanSebelumnyaInfo = `
                        <div style="font-weight:bold;margin-top:8px;margin-bottom:6px;border-top:1px solid #ddd;padding-top:6px;">
                          Penerimaan ${bulanNames[bulanSebelumnya]}: <span style="color:#2196F3">${formatUnit(jsonData.transaksiRDBulanSebelumnya[dataPointIndex])}</span>
                        </div>
                      `;
                    }
                    
                    w.config.series.forEach((s, idx) => {
                      const val = s.data[dataPointIndex];
                      const tahun = s.name.replace("Tahun ", "");
                      if (val && val !== 0) {
                        // Cek apakah stokSo atau stokSisa
                        if (s.name === "Stok Awal Bulan" || s.name === "Stok Berjalan") {
                          stokItems.push({
                            name: s.name,
                            value: formatRupiah(val), // gunakan formatRupiah untuk stok di all produk
                            color: typeof s.color === "function" ? s.color({ dataPointIndex }) : (s.color || "#888"),
                          });
                        } else {
                          barItems.push({
                            name: `${tahun}`,
                            value: formatRupiah(val), // gunakan formatRupiah untuk all produk
                            color: typeof s.color === "function" ? s.color({ dataPointIndex }) : (s.color || "#888"),
                          });
                        }
                      }
                    });
                    barItems.sort((a, b) => {
                      const aVal = parseFloat(a.value.replace(/[^\d.-]/g, ""));
                      const bVal = parseFloat(b.value.replace(/[^\d.-]/g, ""));
                      return bVal - aVal;
                    });
                    let html = `<div class="apexcharts-tooltip-title" style="font-weight:bold;">${bulanNames[dataPointIndex]}</div>`;
                    html += '<div style="font-size:14px;margin-bottom:10px;font-weight:bold;">Total Penjualan</div>';
                    barItems.forEach(item => {
                      html += `
                        <div style="display:flex;align-items:center;margin-bottom:6px;">
                          <span style="display:inline-block;width:10px;height:10px;background:${item.color};margin-left:12px;margin-right:6px;border-radius:50%;border:none;"></span>
                          <span style="font-weight:bold;">${item.name} :</span>
                          <b class="tooltip-value" style="margin-left:8px;">${item.value}</b>
                        </div>
                      `;
                    });
                    if (stokItems.length > 0) {
                      html += `<hr style="border:0;border-top:1px solid #ccc;margin:6px 0;">`;
                      stokItems.forEach(item => {
                        html += `
                          <div style="font-weight:bold;display:flex;align-items:center;margin-left:12px;margin-bottom:2px;">
                            ${item.name}
                          </div>
                          <div style="display:flex;align-items:center;margin-left:12px;margin-bottom:6px;">
                            <span style="display:inline-block;width:10px;height:10px;background:${item.color};margin-right:6px;border-radius:50%;border:none;"></span>
                            <b class="tooltip-value">${item.value}</b>
                          </div>
                        `;
                      });
                    }
                    html += bulanSebelumnyaInfo;
                    return html;
                  }
                },
                title: {
                  text: "Total Penjualan per Bulan (3 Tahun Terakhir)",
                  align: "center",
                  style: { fontSize: "16px", fontWeight: "bold" },
                },
                subtitle: {
                  text: `Total penjualan: Rp ${totalKeseluruhan.toLocaleString("id-ID")}`,
                  align: "center",
                  margin: 10,
                  style: { fontSize: "12px", color: "#666" },
                },
                legend: {
                  show: false,
                  position: "bottom",
                  horizontalAlign: "center",
                  fontSize: "13px",
                  markers: { width: 12, height: 12, radius: 3 },
                  itemMargin: { horizontal: 10, vertical: 5 },
                },
                fill: { opacity: 0.8 },
                states: {
                  hover: { filter: { type: "lighten", value: 0.05 } },
                  active: { filter: { type: "darken", value: 0.1 } },
                },
              });

              allProdukChart.render();

            } catch (err) {
              console.error("Error rendering all products chart:", err);
              allProdukContainer.innerHTML = `<div style="text-align: center; padding: 20px; color: #d9534f;">
                    <strong>Error:</strong> ${err.message}<br>
                    <small>Silahkan coba lagi atau hubungi administrator</small>
                </div>`;
            }
          })
          .catch((error) => {
            $("#loadingMessage").hide();
            console.error("Error fetching API:", error);
            
            // Add user-friendly error display
            const container = document.getElementById("all-produk");
            if (container) {
                container.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: #d9534f; 
                         border: 1px solid #d9534f; border-radius: 5px; background-color: #f9f2f4;">
                        <h4>Data Tidak Dapat Dimuat</h4>
                        <p>${error.message}</p>
                        <button class="btn btn-sm btn-outline-danger" onclick="location.reload()">Coba Lagi</button>
                        <p class="mt-3 small">Jika masalah berlanjut, hubungi administrator sistem.</p>
                    </div>
                `;
            }
          });
  }

  // ===================== INDIVIDUAL PRODUCT CHART =====================
  function loadChart(id_pro = "", id_out = "", id_apl = "", id_mg = "") {
    let url = `./ajax/produkgrafik/produkg2.php`;
    if (id_pro) url += `?id_pro=${id_pro}`;
    if (id_out) url += `&id_out=${id_out}`;
    if (id_apl) url += `&id_apl=${id_apl}`;
    if (id_mg) url += `&id_mg=${id_mg}`;

    $("#loadingMessage").show();
    $("#transaksiChart").show();
    $("#all-produk").hide();

    document.getElementById("transaksiChart").innerHTML = "";

    fetch(url)
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            // Check content type
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // If not JSON, get text for debugging
                return response.text().then(text => {
                    console.error('Response is not JSON:', text.substring(0, 500)); // Log first 500 chars
                    throw new Error('Response is not in JSON format');
                });
            }
            
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    console.error('Raw response:', text.substring(0, 500)); 
                    throw new Error('Invalid JSON response');
                }
            });
        })
        .then((jsonData) => {
            console.log("Parsed API Response:", jsonData);
            $("#loadingMessage").hide();
            
            // Info sumber data
            if (jsonData.source) {
                const sourceType = jsonData.source.type === "api" ? "API" : "Database Lokal";
                $("#judulCabang").text(`Cabang: ${jsonData.source.name} (${sourceType})`);
                $("#dataSourceInfo").html(`<div class="badge badge-info">Data dari ${sourceType}</div>`).show();
            } else {
                $("#judulCabang").text("Cabang: -");
                $("#dataSourceInfo").hide();
            }

        const yearColors = {
          [jsonData.tahun1_label]: "rgba(255, 99, 132, 0.7)",
          [jsonData.tahun2_label]: "rgba(255, 205, 86, 0.7)",
          [jsonData.tahun3_label]: "rgba(54, 162, 235, 0.7)",
        };
        const yearBorderColors = {
          [jsonData.tahun1_label]: "rgb(255, 99, 132)",
          [jsonData.tahun2_label]: "rgb(255, 205, 86)",
          [jsonData.tahun3_label]: "rgb(54, 162, 235)",
        };
        const yearMarkerColors = {
          [jsonData.tahun1_label]: "rgb(210, 84, 110)",     // lebih gelap dari merah
          [jsonData.tahun2_label]: "rgb(215, 172, 72)",    // lebih gelap dari kuning
          [jsonData.tahun3_label]: "rgb(46, 138, 200)",     // lebih gelap dari biru
        };

        const chartContainer = document.getElementById("transaksiChart");
        chartContainer.innerHTML = "";

        // Extract data
        const seriesData = {};
        const years = [jsonData.tahun1_label, jsonData.tahun2_label, jsonData.tahun3_label];
        years.forEach((year) => {
          seriesData[year] = {};
          const foundSeries = jsonData.series.find((s) => s.name && s.name.includes(year));
          if (foundSeries && Array.isArray(foundSeries.data)) {
            foundSeries.data.forEach((value, monthIndex) => {
              seriesData[year][monthIndex + 1] = value;
            });
          }
        });

        const allSeries = [];
        if (jsonData.monthlyOrder) {
          for (let month = 1; month <= 12; month++) {
            const yearOrder = jsonData.monthlyOrder[month];
            yearOrder.forEach((year) => {
              let existingSeries = allSeries.find((s) => s.name === `Tahun ${year}`);
              if (!existingSeries) {
                existingSeries = {
                  name: `Tahun ${year}`,
                  data: Array(12).fill(0),
                  color: yearColors[year],
                  _year: year,
                };
                allSeries.push(existingSeries);
              }
              const monthIndex = month - 1;
              existingSeries.data[monthIndex] = seriesData[year][month];
            });
          }
          allSeries.sort((a, b) => a._year - b._year);
        } else {
          jsonData.series.forEach((series) => {
            const yearMatch = series.name.match(/Tahun (\d+)/);
            if (yearMatch) {
              const year = yearMatch[1];
              allSeries.push({
                name: `Tahun ${year}`,
                data: series.data,
                color: yearColors[year],
                _year: year,
              });
            }
          });
        }

        // Bulan sekarang (0-based)
        const now = new Date();
        const bulanSekarang = now.getMonth();

        // Data stok
        const stokSo = Array.isArray(jsonData.stokSo) ? jsonData.stokSo : Array(12).fill(0);
        const stokSisa = Array.isArray(jsonData.stokSisa) ? jsonData.stokSisa : Array(12).fill(0);

        // Series stokSo (abu-abu)
        const stokSoSeries = {
          name: "Stok Awal Bulan",
          type: "bar",
          stack: "stok",
          data: stokSo.map((val, idx) => {
            if (idx < bulanSekarang && (stokSisa[idx] === 0 || stokSisa[idx] === null)) {
              return val === null ? 0 : val;
            }
            return null;
          }),
          color: function({ dataPointIndex, w }) {
            // Hover effect (opsional, ApexCharts tidak selalu support per-bar hover)
            return "rgb(181, 181, 181)";
          }
        };

        // Series stokSisa (hijau)
        const stokSisaSeries = {
          name: "Stok Berjalan",
          type: "bar",
          stack: "stok",
          data: stokSisa.map((val, idx) => {
            return idx === bulanSekarang ? (val !== null ? val : 0) : null;
          }),
          color: "rgb(108, 255, 106)"
        };

        // Tambahkan ke allSeries
        if (id_pro && id_pro !== "All") {
          if (!id_out || id_out === "" || id_out === "All") {
            // Tampilkan stok awal bulan & stok berjalan jika outlet tidak dipilih
            allSeries.splice(1, 0, stokSoSeries, stokSisaSeries);
          } else {
            // Jika outlet dipilih, hanya tampilkan stok berjalan
            allSeries.splice(1, 0, stokSisaSeries);
          }
        }

        // Chart
        const chart = new ApexCharts(chartContainer, {
          series: allSeries,
          chart: {
            type: "line",
            height: 500,
            stacked: true,
            stackType: "normal",
            toolbar: {
              show: true,
              tools: {
                download: true,
                selection: true,
                zoom: true,
                zoomin: true,
                zoomout: true,
                pan: true,
                reset: true,
                customIcons: [
                  {
                    icon: '<i class="fa fa-file-excel"></i>', // Ikon kustom (gunakan ikon font-awesome atau lainnya)
                    title: 'Analisis Penjualan XLS', // Tooltip untuk ikon
                    class: 'custom-link-icon', // Kelas CSS untuk ikon
                    click: function (chartContext, options) {
                      // Ambil id_apl dari dropdown cabang
                      const idApl = document.getElementById('cabang').value;

                      // Validasi jika id_apl kosong
                      if (!idApl) {
                        Swal.fire({
                          icon: 'warning', // Ikon peringatan
                          title: 'Cabang Belum Dipilih!',
                          text: 'Silakan pilih cabang terlebih dahulu sebelum melanjutkan.',
                          confirmButtonText: 'OK',
                        });
                        return; // Hentikan eksekusi jika id_apl kosong
                      }

                      // Bangun URL berdasarkan id_apl
                      let url = './laporan/xls/stokpenjualan/stokpenjualan.php';
                      url += `?id_apl=${idApl}`;

                      // Arahkan ke URL yang dibangun
                      window.location.href = url;
                    },
                  },
                ],
              },
            
            },
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif',
            background: "#ffffff",
            events: {
              mounted: function (chartContext, config) {
                // Tidak perlu customizeYAxisTransaksiChart
              },
            },
          },
          plotOptions: {
            bar: {
              horizontal: false,
              columnWidth: "50%",
              borderRadius: 0,
              borderWidth: 0,
              borderColor: "transparent",
              dataLabels: { position: "center" },
              distributed: false,
              endingShape: "flat",
            },
          },
          stroke: {
            width: 4,
            colors: allSeries.map((series) => {
              if (series.type === "line") return series.color;
              return yearBorderColors[series._year];
            }),
            curve: "straight",
          },
          markers: {
            size: 5,
            shape: "circle",
            colors: allSeries.map((series) => yearMarkerColors[series._year] || "#444"), // gunakan warna lebih tua
            hover: { size: 6 },
          },
          dataLabels: { enabled: false },
          xaxis: {
            categories: jsonData.labels,
            labels: {
              style: { fontSize: "12px", colors: "#000000", fontWeight: "bold" },
              rotateAlways: false,
            },
            axisTicks: { show: true },
            axisBorder: { show: true },
          },
          grid: {
            show: true,
            borderColor: "#e0e0e0",
            strokeDashArray: 4,
            position: "back",
          },
          legend: {
            show: false,
            position: "bottom",
            horizontalAlign: "center",
            fontSize: "14px",
            markers: { width: 12, height: 12, radius: 3 },
            itemMargin: { horizontal: 10, vertical: 5 },
          },
          tooltip: {
            shared: true,
            intersect: false,
            custom: function({ series, seriesIndex, dataPointIndex, w }) {
              const bulanNames = [
                "Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
                "Jul", "Agu", "Sep", "Okt", "Nov", "Des"
              ];
              let barItems = [];
              let stokItems = [];
              
              // Tambahkan informasi bulan sebelumnya
              let bulanSebelumnyaInfo = "";
              
              // Cek apakah data bulan sebelumnya tersedia dalam series atau initialSeries
              const bulanSebelumnyaValue = w.globals.series.find(s => s.name === "BulanSebelumnya")?.data[dataPointIndex];
              
              // Jika data tersedia
              if (jsonData && jsonData.transaksiRDBulanSebelumnya && jsonData.transaksiRDBulanSebelumnya[dataPointIndex] > 0) {
                // Ambil nama bulan sebelumnya
                const bulanSekarang = dataPointIndex;
                const bulanSebelumnya = bulanSekarang === 0 ? 11 : bulanSekarang - 1;
                
                bulanSebelumnyaInfo = `
                  <div style="font-weight:bold;margin-top:8px;margin-bottom:6px;border-top:1px solid #ddd;padding-top:6px;">
                    Penerimaan ${bulanNames[bulanSebelumnya]}: <span style="color:#2196F3">${formatUnit(jsonData.transaksiRDBulanSebelumnya[dataPointIndex])}</span>
                  </div>
                `;
              }
              
              w.config.series.forEach((s, idx) => {
                const val = s.data[dataPointIndex];
                const tahun = s.name.replace("Tahun ", "");
                if (val && val !== 0) {
                  // Cek apakah stokSo atau stokSisa
                  if (s.name === "Stok Awal Bulan" || s.name === "Stok Berjalan") {
                    stokItems.push({
                      name: s.name,
                      value: (val || 0).toLocaleString("id-ID"),
                      color: typeof s.color === "function" ? s.color({ dataPointIndex }) : (s.color || "#888"),
                    });
                  } else {
                    barItems.push({
                      name: `${tahun}`,
                      value: (val || 0).toLocaleString("id-ID"),
                      color: typeof s.color === "function" ? s.color({ dataPointIndex }) : (s.color || "#888"),
                    });
                  }
                }
              });
              barItems.sort((a, b) => {
                const aVal = parseFloat(a.value.replace(/[^\d.-]/g, ""));
                const bVal = parseFloat(b.value.replace(/[^\d.-]/g, ""));
                return bVal - aVal;
              });
              let html = `<div class="apexcharts-tooltip-title" style="font-weight:bold;">${bulanNames[dataPointIndex]}</div>`;
                html += '<div style="font-size:14px;margin-bottom:10px;font-weight:bold;">Penjualan : Obat</div>';
              barItems.forEach(item => {
                html += `
                  <div style="display:flex;align-items:center;margin-bottom:6px;">
                    <span style="display:inline-block;width:10px;height:10px;margin-left:12px;background:${item.color};margin-right:6px;border-radius:50%;border:none;"></span>
                    <span style="font-weight:bold;">${item.name} :</span>
                    <b class="tooltip-value" style="margin-left:8px;">${item.value}</b>
                  </div>
                `;
              });
              if (stokItems.length > 0) {
                html += `<hr style="border:0;border-top:1px solid #ccc;margin:6px 0;">`;
                stokItems.forEach(item => {
                  html += `
                    <div style="font-weight:bold;display:flex;align-items:center;margin-left:12px;margin-bottom:2px;">
                      ${item.name}
                    </div>
                    <div style="display:flex;align-items:center;margin-left:12px;margin-bottom:6px;">
                      <span style="display:inline-block;width:10px;height:10px;background:${item.color};margin-right:6px;border-radius:50%;border:none;"></span>
                      <b class="tooltip-value">${item.value}</b>
                    </div>
                  `;
                });
              }
              html += bulanSebelumnyaInfo;
              return html;
            }
          },
          fill: { opacity: 0.8 },
          states: {
            hover: { filter: { type: "none" } },
            active: { filter: { type: "darken", value: 0.1 } },
          },
          yaxis: {
            show: true,
            labels: {
              formatter: function (val) { return Math.round(val).toLocaleString(); },
              style: { fontSize: "12px" },
            },
            axisBorder: { show: true, color: "#e0e0e0" },
            axisTicks: { show: true, color: "#e0e0e0" },
            title: {},
          },
        });

        chart.render();
      })
      .catch((error) => {
        console.error("Error fetching API:", error);
      });
  }

  // ===================== EVENT HANDLERS =====================
  $("#produk").change(function () {
    var id_pro = $(this).val();
    var nama_pro = $("#produk option:selected").text();
    var id_out = $("#outlet").val();
    var id_apl = $("#cabang").val();
    var id_mg = $("#grup").val();
    if (id_out === "All") id_out = "";
    if (id_pro === "All") {
      $("#flag-stok-awal").hide();
      $("#flag-stok-real").hide();
      $("#judulGrafik").text("Semua Produk");
      loadAllProductsChart(id_out, id_apl, id_mg);
    } else if (id_pro) {
      $("#flag-stok-awal").show();
      $("#flag-stok-real").show();
      $("#judulGrafik").text("Produk : " + nama_pro);
      loadChart(id_pro, id_out, id_apl, id_mg);
    } else {
      $("#flag-stok-awal").show();
      $("#flag-stok-real").show();
      $("#judulGrafik").text("Silakan pilih produk");
      $("#transaksiChart").html("");
      $("#all-produk").html("");
      $("#transaksiChart").hide();
      $("#all-produk").hide();
      $("#dataSourceInfo").hide();
    }
  });

  $("#produk").trigger("change");

  $("#outlet").change(function () {
    let id_pro = $("#produk").val();
    let id_out = $(this).val();
    let nama_out = $("#outlet option:selected").text();
    let id_apl = $("#cabang").val();
    let id_mg = $("#grup").val();
    if (id_out === "All") {
      id_out = "";
      $("#judulOutlet").text("Outlet : All Grup");
      $("#flag-stok-awal").show();
      $("#flag-stok-real").show();
    } else if (id_out) {
      $("#judulOutlet").text("Outlet : " + nama_out);
      $("#flag-stok-awal").hide();      // Sembunyikan stok awal bulan
      $("#flag-stok-real").show();      // Tetap tampilkan stok berjalan
    } else {
      $("#judulOutlet").text("Outlet : -");
      $("#flag-stok-awal").show();
      $("#flag-stok-real").show();
    }
    if (id_pro === "All") {
      loadAllProductsChart(id_out, id_apl, id_mg);
    } else if (id_pro) {
      loadChart(id_pro, id_out, id_apl, id_mg);
    }
  });

  $("#cabang").change(function () {
    let id_pro = $("#produk").val();
    let id_out = $("#outlet").val();
    let id_apl = $(this).val();
    let id_mg = $("#grup").val(); // Pastikan id_mg tetap diambil dari dropdown grup

    if (id_out === "All") id_out = "";

    // Tambahan label khusus untuk a+b
    if (id_apl === "a_b") {
        $("#judulCabang").text("Cabang: Gabungan Puri + Puri B");
    }

    if (id_pro === "All") {
        loadAllProductsChart(id_out, id_apl, id_mg); // Pastikan id_mg diteruskan
    } else if (id_pro) {
        loadChart(id_pro, id_out, id_apl, id_mg); // Pastikan id_mg diteruskan
    }
  });

  $("#grup").change(function () {
    let id_mg = $(this).val(); // Ambil ID grup yang dipilih
    let nama_mg = $("#grup option:selected").text(); // Ambil nama grup berdasarkan opsi yang dipilih

    if (id_mg) {
        $("#judulGrup").text("Grup : " + nama_mg);
        loadOutletsByGrup(); // Panggil fungsi untuk memuat outlet berdasarkan grup
    } else {
        $("#judulGrup").text("Grup : -");
        $("#outlet").html('<option value="">-- Pilih --</option>'); // Reset dropdown outlet
    }

    // Tambahkan parameter id_mg ke API
    let id_pro = $("#produk").val();
    let id_out = $("#outlet").val();
    let id_apl = $("#cabang").val();

    if (id_out === "All") id_out = "";
    if (id_pro === "All") {
        loadAllProductsChart(id_out, id_apl, id_mg); // Tambahkan id_mg
    } else if (id_pro) {
        loadChart(id_pro, id_out, id_apl, id_mg); // Tambahkan id_mg
    }
  });

  $("#tampilkanAll").click(function () {
    $("#judulGrafik").text("Semua Produk");
    $("#judulOutlet").text("Outlet : -");
    $("#produk").val("All").trigger("change");
  });

  // Default produk saat halaman dimuat
  let defaultProduct = $("#produk option:eq(1)").val();
  let defaultProductName = $("#produk option:eq(1)").text();
  if (defaultProduct) {
    $("#judulGrafik").text("Produk : " + defaultProductName);
    loadChart(defaultProduct);
  }

  // ===================== Y-AXIS CUSTOMIZATION =====================
  function customizeYAxis(chartContainer, chartInstance) {
    const oldIndicators = chartContainer.querySelectorAll(".custom-y-label, .custom-y-line");
    oldIndicators.forEach((el) => el.remove());
    setTimeout(() => {
      try {
        const series = chartInstance.w.globals.series;
        const maxValues = series.map((data) =>
          Math.max(...data.filter((value) => value !== null && value !== undefined))
        );
        if (maxValues.length === 0) return;
        chartInstance.updateOptions({
          yaxis: {
            labels: {
              formatter: function (value) {
                if (value >= 1000000000) return "Rp " + (value / 1000000000).toFixed(1) + " M";
                if (value >= 1000000) return "Rp " + (value / 1000000).toFixed(1) + " Jt";
                if (value >= 1000) return "Rp " + (value / 1000).toFixed(0) + " Rb";
                return "Rp " + value.toFixed(0);
              },
            },
          },
        });
      } catch (error) {
        console.error("Error customizing Y-axis:", error);
      }
    }, 500);
  }

  // ===================== LOAD OUTLETS BY GROUP =====================
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
        const apiUrl = `/192.268.908.09/ajax/get_outlets/get_outlets.php?id_mg=${encodeURIComponent(selectedGrup)}`;

        fetch(apiUrl)
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
});


