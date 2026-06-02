/**
 * Warehouse Product Transfer Management
 *
 * This script handles the warehouse transfer UI functionality including:
 * - Form initialization and validation
 * - Product selection and management
 * - Custom notifications
 * - Modal interactions
 */

// Global variables
let baseUrl = "";

$(document).ready(function () {
  // Get base URL from meta tag
  baseUrl = $('meta[name="sistem-url"]').attr("content") || "";

  // Log environmental information for debugging
  logEnvironmentInfo();

  // Initialize UI components
  initializeComponents();

  // Set up event handlers
  setupEventHandlers();

  // Create notification container
  createNotificationContainer();
});

/**
 * Log environment information for debugging purposes
 */
function logEnvironmentInfo() {
  console.log("jQuery version:", $.fn.jquery);

  if (typeof $.fn.modal === "undefined") {
    console.error(
      "Bootstrap modal function not found! Bootstrap may not be loaded correctly."
    );
    alert(
      "System Error: Bootstrap modal not available. Please check the console for details."
    );
  } else {
    console.log("Bootstrap modal function detected");
  }

  // Check for multiple jQuery instances
  if (window.jQuery && $ !== window.jQuery) {
    console.warn(
      "Multiple jQuery instances detected - this can cause conflicts!"
    );
  }

  // Set up global error handler
  window.onerror = function (message, source, lineno, colno, error) {
    console.error("Global error caught:", message, "at", source, ":", lineno);
    return false;
  };
}

/**
 * Initialize UI components
 */
function initializeComponents() {
  // Initialize select2 dropdowns
  $(".select2").select2();
}

/**
 * Set up all event handlers
 */
function setupEventHandlers() {
  // Add Product button handlers
  $("#btnAddProduct, #btnAddProductEmpty")
    .off("click")
    .on("click", function (e) {
      e.preventDefault();
      showAddProductModal();
    });

  // Form submission handler
  $("#btnSubmitTransfer")
    .off("click")
    .on("click", function () {
      validateAndSubmitForm();
    });

  // Warehouse selection validation handlers
  setupWarehouseValidation();

  $("#gudang_tujuan").on("change", function () {
    const selectedInventoryId = $(this).val();
    const selectedInventoryName = $(this).find("option:selected").text().trim();

    if (selectedInventoryId) {
      // Update the hidden field for inventory_id
      $("#inventory_id").val(selectedInventoryId);

      // If you need to update the code format based on inventory name
      const currentCode = $("#kode_ttg").val();
      if (currentCode) {
        // Extract parts of the code
        const parts = currentCode.split("/");
        if (parts.length >= 3) {
          // Generate initials from the inventory name (take first letter of each word)
          const initials = selectedInventoryName
            .split(" ")
            .map((word) => word.charAt(0).toUpperCase())
            .join("");

          // Replace the inventory name part (3rd segment) with the initials
          parts[2] = initials;

          // Join back and update
          $("#kode_ttg").val(parts.join("/"));
        }
      }
    }
  });
}

/**
 * Set up validation for warehouse selection fields
 */
function setupWarehouseValidation() {
  // Validate gudang tujuan on change
  $("#gudang_tujuan").on("change", function () {
    const gudangAsal = $("#gudang_asal").val();
    const gudangTujuan = $(this).val();

    validateGudangSelection(gudangAsal, gudangTujuan);
  });

  // Validate gudang asal on change
  $("#gudang_asal").on("change", function () {
    const gudangAsal = $(this).val();
    const gudangTujuan = $("#gudang_tujuan").val();

    validateGudangSelection(gudangAsal, gudangTujuan);
  });
}

/**
 * Validate that source and destination warehouses are different
 *
 * @param {string} gudangAsal - Source warehouse ID
 * @param {string} gudangTujuan - Destination warehouse ID
 */
function validateGudangSelection(gudangAsal, gudangTujuan) {
  // Only validate if both fields have values
  if (gudangAsal && gudangTujuan && gudangAsal === gudangTujuan) {
    showNotification({
      type: "warning",
      title: "Gudang Sama",
      message:
        "Gudang asal dan gudang tujuan tidak boleh sama. Silakan pilih gudang yang berbeda.",
      actions: [
        {
          text: "Pilih Ulang",
          type: "primary",
          onClick: function () {
            $("#gudang_tujuan").val("").trigger("change.select2");
            closeNotification();
          },
        },
        {
          text: "Refresh Halaman",
          type: "secondary",
          onClick: function () {
            location.reload();
          },
        },
      ],
      autoClose: false,
      shake: true,
    });
  }
}

/**
 * Create notification container in DOM
 */
function createNotificationContainer() {
  if (!$("#alertNotification").length) {
    $("body").append(`
            <div id="alertNotification" class="alert-notification">
                <div class="alert-header">
                    <div id="alertIcon" class="alert-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <h5 id="alertTitle" class="alert-title">Notification Title</h5>
                    <button type="button" class="alert-close" onclick="closeNotification()">×</button>
                </div>
                <div id="alertBody" class="alert-body">
                    Notification message goes here
                </div>
                <div id="alertActions" class="alert-actions">
                    <!-- Buttons will be added dynamically -->
                </div>
                <div class="alert-progress">
                    <div class="alert-progress-bar"></div>
                </div>
            </div>
        `);
  }
}

/**
 * Validate form and submit via AJAX if all validations pass
 */
function validateAndSubmitForm() {
  // Validate source warehouse
  if (!$("#gudang_asal").val()) {
    showNotification({
      type: "error",
      title: "Validasi Gagal",
      message: "Silakan pilih gudang asal terlebih dahulu",
      autoClose: true,
    });
    $("#gudang_asal").focus();
    return;
  }

  // Validate destination warehouse
  if (!$("#gudang_tujuan").val()) {
    showNotification({
      type: "error",
      title: "Validasi Gagal",
      message: "Silakan pilih gudang tujuan terlebih dahulu",
      autoClose: true,
    });
    $("#gudang_tujuan").focus();
    return;
  }

  // Check if warehouses are the same
  if ($("#gudang_asal").val() === $("#gudang_tujuan").val()) {
    showNotification({
      type: "error",
      title: "Validasi Gagal",
      message:
        "Gudang asal dan gudang tujuan tidak boleh sama. Silakan pilih gudang tujuan yang berbeda.",
      actions: [
        {
          text: "Pilih Ulang",
          type: "primary",
          onClick: function () {
            $("#gudang_tujuan").focus();
            closeNotification();
          },
        },
      ],
      autoClose: false,
      shake: true,
    });
    return;
  }

  // Set the hidden inventory_id for legacy compatibility
  $("#inventory_id").val($("#gudang_asal").val());

  // Check if products have been added
  const hasProducts = !$("#emptyProductState").is(":visible");
  if (!hasProducts) {
    showNotification({
      type: "error",
      title: "Validasi Gagal",
      message: "Mohon tambahkan produk yang akan ditransfer",
      autoClose: true,
    });
    return;
  }

  // Create a JSON summary of the transfer items
  const transferItems = [];
  $("#tableTransferProduct tr").each(function () {
    const rowIndex = $(this).attr("id").replace("product-row-", "");

    transferItems.push({
      product_id: $(`input[name="product_id_${rowIndex}"]`).val(),
      psd_id: $(`input[name="psd_id_${rowIndex}"]`).val(),
      product_name: $(`input[name="product_name_${rowIndex}"]`).val(),
      batch: $(`input[name="batch_${rowIndex}"]`).val(),
      expire: $(`input[name="expire_${rowIndex}"]`).val(),
      qty: $(`input[name="qty_${rowIndex}"]`).val(),
    });
  });

  // Debug logging
  console.log("Transfer items before JSON:", transferItems);

  // Convert to JSON string
  const jsonData = JSON.stringify(transferItems);
  console.log("JSON data:", jsonData);

  // Store transfer items JSON in hidden field
  $("#cartaddProductTransfer").val(jsonData);

  // More debug info
  console.log(
    "Form data to be submitted:",
    $("#formTransferGudang").serialize()
  );

  // Show loading notification
  showNotification({
    type: "info",
    title: "Memproses Transfer",
    message:
      '<div class="text-center"><div class="spinner-border spinner-border-sm text-primary mr-2" role="status"></div>Sedang memproses data transfer...</div>',
    autoClose: false,
  });

  // Submit via AJAX instead of traditional form submission
  $.ajax({
    url: window.baseUrl + "/ajax/gudangproduktransfer/post_produk.php",
    type: "POST",
    data: {
      kode_ttg: $("#kode_ttg").val(),
      tanggal_ttg: $("#tanggal_ttg").val(),
      gudang_asal: $("#gudang_asal").val(),
      gudang_tujuan: $("#gudang_tujuan").val(),
      catatan_ttg: $("#catatan_ttg").val(),
      cartaddProductTransfer: $("#cartaddProductTransfer").val(), // Send the JSON string directly
    },
    dataType: "json",
    success: function (response) {
      closeNotification();

      if (response.success) {
        // Show success notification
        showNotification({
          type: "success",
          title: "Transfer Berhasil",
          message: response.message || "Data transfer gudang berhasil disimpan",
          actions: [
            {
              text: "Lihat Daftar Transfer",
              type: "primary",
              onClick: function () {
                window.location.href = window.baseUrl + "/gudangproduktransfer";
              },
            },
            {
              text: "Buat Transfer Baru",
              type: "secondary",
              onClick: function () {
                resetTransferForm();
                closeNotification();
              },
            },
          ],
          autoClose: false,
        });
      } else {
        // Show error notification
        showNotification({
          type: "error",
          title: "Transfer Gagal",
          message: response.message || "Terjadi kesalahan saat menyimpan data",
          autoClose: true,
        });
      }
    },
    error: function (xhr, status, error) {
      closeNotification();

      // Try to parse response if it's JSON
      let errorMessage = "Terjadi kesalahan saat memproses data transfer";
      try {
        const response = JSON.parse(xhr.responseText);
        if (response && response.message) {
          errorMessage = response.message;
        }
      } catch (e) {
        console.error("Error parsing JSON response:", e);
      }

      // Show error notification
      showNotification({
        type: "error",
        title: "Error",
        message: errorMessage,
        autoClose: true,
      });

      // Log error details for debugging
      console.error("Form submission error:", error);
      console.error("Status:", status);
      console.error("Response:", xhr.responseText);
    },
  });

  // Prevent default form submission
  return false;
}

/**
 * Reset the transfer form after successful submission
 */
function resetTransferForm() {
  // Generate new transfer code
  const date = new Date();
  const year = date.getFullYear().toString().substr(-2);
  const month = ("0" + (date.getMonth() + 1)).slice(-2);
  const day = ("0" + date.getDate()).slice(-2);
  const random = Math.floor(1000 + Math.random() * 9000);
  const newCode = "TG" + year + month + day + random;

  // Reset form fields but preserve warehouse selections if needed
  const gudangAsal = $("#gudang_asal").val();
  const gudangTujuan = $("#gudang_tujuan").val();

  // Reset form
  $("#formTransferGudang")[0].reset();

  // Restore values that we want to keep
  $("#kode_ttg").val(newCode);
  $("#tanggal_ttg").val(new Date().toISOString().split("T")[0]); // Current date in YYYY-MM-DD
  $("#tipe_transfer").val("OUT");

  // Optional: if you want to keep the same warehouses
  if (gudangAsal && gudangTujuan) {
    $("#gudang_asal").val(gudangAsal).trigger("change.select2");
    $("#gudang_tujuan").val(gudangTujuan).trigger("change.select2");
  }

  // Clear product list
  $("#tableTransferProduct").empty();
  $("#countaddProductTransfer").val(0);

  // Show empty state, hide product table
  $("#emptyProductState").removeClass("d-none");
  $("#productTableContainer").addClass("d-none");

  // Reset summary
  updateSummary();
}

/**
 * Display notification with customizable options
 *
 * @param {Object} options - Notification configuration options
 * @param {string} options.type - Type of notification (info|success|warning|error)
 * @param {string} options.title - Notification title
 * @param {string} options.message - Notification message
 * @param {Array} options.actions - Action buttons for the notification
 * @param {boolean} options.autoClose - Whether to automatically close the notification
 * @param {number} options.duration - Auto-close duration in milliseconds
 * @param {boolean} options.shake - Whether to apply shake animation
 */
function showNotification(options) {
  try {
    // Default options
    const defaults = {
      type: "info",
      title: "Notification",
      message: "",
      actions: [],
      autoClose: true,
      duration: 5000,
      shake: false,
    };

    // Merge passed options with defaults
    const settings = {
      ...defaults,
      ...options,
    };

    // Ensure container exists
    if (!$("#alertNotification").length) {
      createNotificationContainer();
    }

    // Get notification elements
    const notification = $("#alertNotification");
    const icon = $("#alertIcon");
    const title = $("#alertTitle");
    const body = $("#alertBody");
    const actions = $("#alertActions");

    // Reset previous notification state
    icon.removeClass("alert-icon-success alert-icon-warning alert-icon-error");
    actions.empty();

    // Configure icon based on notification type
    let iconClass = "fa-info-circle";
    let iconType = "";

    switch (settings.type) {
      case "success":
        iconClass = "fa-check-circle";
        iconType = "alert-icon-success";
        break;
      case "warning":
        iconClass = "fa-exclamation-triangle";
        iconType = "alert-icon-warning";
        break;
      case "error":
        iconClass = "fa-times-circle";
        iconType = "alert-icon-error";
        break;
    }

    // Update notification content
    icon.addClass(iconType).html(`<i class="fa ${iconClass}"></i>`);
    title.text(settings.title);
    body.html(settings.message);

    // Add action buttons if provided
    if (settings.actions && settings.actions.length) {
      settings.actions.forEach((action) => {
        const btnClass =
          action.type === "primary"
            ? "alert-btn-primary"
            : "alert-btn-secondary";
        const button = $(
          `<button class="alert-btn ${btnClass}">${action.text}</button>`
        );
        button.on("click", action.onClick);
        actions.append(button);
      });
    }

    // Show notification
    notification.addClass("show");

    // Apply shake animation if enabled
    if (settings.shake) {
      setTimeout(() => {
        notification.addClass("alert-shake");

        // Remove shake class after animation completes
        setTimeout(() => {
          notification.removeClass("alert-shake");
        }, 1000);
      }, 100);
    }

    // Auto-close if enabled
    if (settings.autoClose) {
      setTimeout(() => {
        closeNotification();
      }, settings.duration);
    }
  } catch (err) {
    console.error("Error showing notification:", err);
    alert(options.title + ": " + options.message);
  }
}

/**
 * Close the notification
 */
function closeNotification() {
  const notification = $("#alertNotification");
  notification.removeClass("show");
}

/**
 * Display modal for product selection
 */
function showAddProductModal() {
  // Get warehouse selections for validation
  const gudangAsal = $("#gudang_asal").val();
  const gudangTujuan = $("#gudang_tujuan").val();

  // Validate source warehouse selection
  if (!gudangAsal) {
    showNotification({
      type: "warning",
      title: "Pilih Gudang Asal",
      message: "Silakan pilih gudang asal terlebih dahulu",
      autoClose: true,
    });
    $("#gudang_asal").focus();
    return;
  }

  // Validate destination warehouse selection
  if (!gudangTujuan) {
    showNotification({
      type: "warning",
      title: "Pilih Gudang Tujuan",
      message: "Silakan pilih gudang tujuan terlebih dahulu",
      autoClose: true,
    });
    $("#gudang_tujuan").focus();
    return;
  }

  // Validate warehouses are different
  if (gudangAsal === gudangTujuan) {
    showNotification({
      type: "warning",
      title: "Gudang Sama",
      message: "Gudang asal dan gudang tujuan tidak boleh sama",
      autoClose: true,
    });
    return;
  }

  // Set the hidden inventory_id field for backward compatibility
  $("#inventory_id").val(gudangAsal);

  // Create modal UI
  createProductSelectionModal();

  // Set up modal event handlers
  setupModalEvents();

  // Load real product data
  loadRealProductData(gudangAsal);
}

/**
 * Create the product selection modal UI
 */
function createProductSelectionModal() {
  // First remove existing modal if any
  $("#simpleModal").remove();

  // Get warehouse name for display
  const warehouseName = $("#gudang_asal option:selected").text();

  // Create simple custom modal HTML
  $("body").append(`
    <div id="simpleModal" class="simple-modal-overlay">
        <div class="simple-modal-container">
            <div class="simple-modal-header">
                <h5><i class="fa fa-boxes mr-2"></i> Pilih Produk dari ${warehouseName}</h5>
                <button type="button" class="simple-modal-close">&times;</button>
            </div>
            <div class="simple-modal-body">
                <!-- Search and Filter Controls -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" id="simpleProductSearch" class="form-control" placeholder="Cari produk...">
                            <div class="input-group-append">
                                <button class="btn btn-outline-primary" type="button" id="simpleSearchBtn">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="simpleProductCategory" class="form-control">
                            <!-- Categories will be loaded dynamically via AJAX -->
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="simpleProductSort" class="form-control">
                            <option value="name">Nama</option>
                            <option value="stock">Stok (Tertinggi)</option>
                            <option value="expire">Kadaluarsa (Terdekat)</option>
                        </select>
                    </div>
                </div>
                
                <!-- Loading State -->
                <div id="simpleLoadingState" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Memuat data produk...</p>
                </div>
                
                <!-- Product Table -->
                <div id="simpleProductTable" style="display:none">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Batch</th>
                                    <th>Kadaluarsa</th>
                                    <th class="text-right">Stok</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="simpleProductRows">
                                <!-- Product rows will be added here -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="simpleEmptyState" class="text-center py-4" style="display:none">
                    <i class="fa fa-box-open fa-3x text-muted mb-3"></i>
                    <h5>Tidak ada produk ditemukan</h5>
                    <p class="text-muted">Tidak ada produk yang tersedia di gudang asal ini.</p>
                </div>
                
                <!-- Pagination Controls -->
                <div id="simplePagination" class="d-flex justify-content-between align-items-center mt-3" style="display:none">
                    <div class="pagination-info">
                        <span id="simplePaginationInfo">Menampilkan 1-10 dari 0 produk</span>
                    </div>
                    <div class="pagination-controls">
                        <button id="simplePrevPage" class="btn btn-sm btn-outline-secondary" disabled>
                            <i class="fa fa-chevron-left"></i> Sebelumnya
                        </button>
                        <span class="mx-2">
                            Halaman <span id="simpleCurrentPage">1</span> dari <span id="simpleTotalPages">1</span>
                        </span>
                        <button id="simpleNextPage" class="btn btn-sm btn-outline-secondary" disabled>
                            Selanjutnya <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="simple-modal-footer">
                <button type="button" class="btn btn-outline-secondary simple-modal-close">Tutup</button>
            </div>
        </div>
    </div>
    `);

  // Load categories via AJAX
  loadProductCategories();
}

/**
 * Load product categories via AJAX
 */
function loadProductCategories() {
  $.ajax({
    url: window.baseUrl + "/ajax/gudangproduktransfer/get_categories.php",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response && response.success && response.categories) {
        const categorySelect = $("#simpleProductCategory");

        // Add default "All Categories" option
        categorySelect.append('<option value="">Semua Kategori</option>');

        // Add each category from response
        response.categories.forEach((category) => {
          categorySelect.append(`
                        <option value="${category.name}">${category.name}</option>
                    `);
        });

        // Log success for debugging
        console.log(`Loaded ${response.count} categories successfully`);
      } else {
        console.warn("Invalid category response format:", response);
      }
    },
    error: function (xhr, status, error) {
      console.error("Error loading categories:", error);
      console.error("Status:", status);
      console.error("Response:", xhr.responseText);

      showNotification({
        type: "error",
        title: "Error",
        message: "Gagal memuat data kategori produk",
        autoClose: true,
      });
    },
  });
}

/**
 * Set up event handlers for the product selection modal
 */
function setupModalEvents() {
  // Close button event handler
  $(".simple-modal-close").on("click", function () {
    $("#simpleModal").remove();
  });

  // Initialize pagination state
  window.simplePaginationState = {
    currentPage: 1,
    itemsPerPage: 10,
    totalItems: 0,
    totalPages: 0,
    allProducts: [],
  };

  // Pagination button handlers
  $("#simplePrevPage").on("click", function () {
    if ($(this).prop("disabled")) return;

    window.simplePaginationState.currentPage--;
    renderProductPage();
  });

  $("#simpleNextPage").on("click", function () {
    if ($(this).prop("disabled")) return;

    window.simplePaginationState.currentPage++;
    renderProductPage();
  });

  // Search functionality with debounce for performance
  let searchTimeout;
  $("#simpleProductSearch, #simpleSearchBtn").on("keyup click", function () {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function () {
      window.simplePaginationState.currentPage = 1; // Reset to first page on search
      renderProductPage();
    }, 300);
  });

  // Category filter handler
  $("#simpleProductCategory").on("change", function () {
    window.simplePaginationState.currentPage = 1; // Reset to first page on filter change
    renderProductPage();
  });

  // Sort handler
  $("#simpleProductSort").on("change", function () {
    renderProductPage();
  });
}

/**
 * Load real product data from the database for the modal
 *
 * @param {string} gudangAsal - ID of the source warehouse
 */
function loadRealProductData(gudangAsal) {
  // Show loading state
  $("#simpleLoadingState").show();
  $("#simpleProductTable").hide();
  $("#simpleEmptyState").hide();

  console.log("Loading products from warehouse:", gudangAsal);

  // AJAX request to fetch products from the selected warehouse
  $.ajax({
    url: window.baseUrl + "/ajax/gudangproduktransfer/get_produk.php",
    type: "POST",
    dataType: "json",
    data: {
      warehouse_id: gudangAsal,
    },
    success: function (response) {
      console.log("API Response received:", response);

      // First convert the response to a standardized format
      const normalizedProducts = normalizeProductResponse(response);

      if (normalizedProducts && normalizedProducts.length > 0) {
        console.log("Normalized Products:", normalizedProducts);

        // Store products in pagination state
        window.simplePaginationState.allProducts = normalizedProducts;
        window.simplePaginationState.totalItems = normalizedProducts.length;
        window.simplePaginationState.totalPages = Math.ceil(
          normalizedProducts.length / window.simplePaginationState.itemsPerPage
        );

        // Render products
        renderProductPage();

        // Show success message
        showNotification({
          type: "success",
          title: "Data Berhasil Dimuat",
          message: `Berhasil memuat ${normalizedProducts.length} produk dari gudang`,
          autoClose: true,
          duration: 3000,
        });
      } else {
        // No products found
        $("#simpleLoadingState").hide();
        $("#simpleProductTable").hide();
        $("#simpleEmptyState").show();

        // Update pagination
        window.simplePaginationState.totalItems = 0;
        window.simplePaginationState.totalPages = 1;
        updatePaginationDisplay(0, 0);
      }
    },
    error: function (xhr, status, error) {
      console.error("Error fetching products:", error);
      console.error("Status:", status);
      console.error("Response text:", xhr.responseText);

      // Show error state
      $("#simpleLoadingState").hide();
      $("#simpleProductTable").hide();
      $("#simpleEmptyState").show().html(`
                <i class="fa fa-exclamation-circle fa-3x text-danger mb-3"></i>
                <h5>Terjadi Kesalahan</h5>
                <p class="text-muted">Tidak dapat memuat data produk. Error: ${error}</p>
                <button type="button" class="btn btn-outline-primary" onclick="loadRealProductData('${gudangAsal}')">
                    <i class="fa fa-refresh"></i> Coba Lagi
                </button>
            `);
    },
  });
}

/**
 * Normalize product response data structure to a consistent format
 *
 * @param {Object|Array} response - API response
 * @return {Array} Normalized array of product objects
 */
function normalizeProductResponse(response) {
  // Initialize products array
  let products = [];

  // Handle different response structures
  if (Array.isArray(response)) {
    // Simple array response
    products = response;
  } else if (
    response &&
    response.products &&
    Array.isArray(response.products)
  ) {
    // Response with products array
    products = response.products;
  } else if (typeof response === "object" && response !== null) {
    // Try to find products array in the response
    for (const key in response) {
      if (Array.isArray(response[key])) {
        products = response[key];
        break;
      }
    }
  }

  // Map each product to a normalized structure
  return products.map((product) => {
    // Handle nested structure as in the example response
    if (product.stock_detail && product.info) {
      return {
        id: product.id || "",
        kategori_obat: product.kategori || product.nama_produk_jadi || "Umum",
        id_pro: product.id || "",
        psd_id: product.stock_detail.id || "",
        id_psd: product.stock_detail.id || "",
        nama_pro: product.nama || "",
        kode_pro: product.kode || "",
        kategori_obat: product.kategori || "",
        no_bcode: product.stock_detail.batch || "",
        tgl_expired: product.stock_detail.expired?.raw || "",
        masuk_psd: product.stock_detail.stock?.masuk || 0,
        keluar_psd: product.stock_detail.stock?.keluar || 0,
        sisa_psd: product.stock_detail.stock?.sisa || 0,
        gudang: product.stock_detail.gudang || "",
        harga_phg: product.info?.harga || 0,
        berat_pro: product.info?.berat || "",
        status_pro: product.info?.status || "",
        satuan_kpr: product.info?.satuan?.name || "",
      };
    }

    // Return product with direct property access (original format)
    return product;
  });
}

/**
 * Render product rows in the table with real data
 *
 * @param {Array} products - Array of product objects to display
 */
function renderProductRows(products) {
  const tableBody = $("#simpleProductRows");
  tableBody.empty();

  // Hide loading state
  $("#simpleLoadingState").hide();

  // Handle empty products case
  if (!products || products.length === 0) {
    $("#simpleProductTable").hide();
    $("#simpleEmptyState").show();
    return;
  }

  // Show product table and hide empty state
  $("#simpleProductTable").show();
  $("#simpleEmptyState").hide();

  // Add each product row
  products.forEach((product) => {
    try {
      // Format expiry date with validation
      let expireDate = null;
      let formattedDate = "N/A";
      let expireClass = "";

      if (product.tgl_expired) {
        try {
          expireDate = new Date(product.tgl_expired);

          // Check if date is valid
          if (!isNaN(expireDate.getTime())) {
            formattedDate = expireDate.toLocaleDateString("id-ID", {
              year: "numeric",
              month: "short",
              day: "numeric",
            });

            // Check if product is expiring soon (within 3 months)
            const today = new Date();
            const monthsDiff =
              (expireDate.getFullYear() - today.getFullYear()) * 12 +
              (expireDate.getMonth() - today.getMonth());
            expireClass = monthsDiff <= 3 ? "text-danger" : "";
          }
        } catch (err) {
          console.error("Error formatting date:", err);
        }
      }

      // Get stock quantity safely
      const stockQty = parseInt(product.sisa_psd) || 0;

      // Create row HTML
      const row = `
                <tr>
                    <td>
                        <div><strong>${escapeHtml(
                          product.nama_pro
                        )}</strong></div>
                        <small class="text-muted">${escapeHtml(
                          product.kategori_obat || "Umum"
                        )}</small>
                    </td>
                    <td>${escapeHtml(product.no_bcode)}</td>
                    <td class="${expireClass}">${formattedDate}</td>
                    <td class="text-right">${stockQty.toLocaleString(
                      "id-ID"
                    )}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-primary simple-add-product" 
                            data-id="${escapeHtml(product.id_pro)}"
                            data-psd="${escapeHtml(product.id_psd)}"
                            data-name="${escapeHtml(product.nama_pro)}"
                            data-batch="${escapeHtml(product.no_bcode)}"
                            data-expire="${product.tgl_expired}"
                            data-stock="${stockQty}">
                            <i class="fa fa-plus-circle"></i> Tambah
                        </button>
                    </td>
                </tr>
            `;

      tableBody.append(row);
    } catch (err) {
      console.error("Error rendering product row:", err, product);
    }
  });

  // Bind click events to add buttons
  $(".simple-add-product")
    .off("click")
    .on("click", function () {
      try {
        const productData = {
          id: $(this).data("id"),
          psd_id: $(this).data("psd"),
          name: $(this).data("name"),
          batch: $(this).data("batch"),
          expire: $(this).data("expire"),
          stock: parseFloat($(this).data("stock")),
        };

        // Validate product data before proceeding
        if (!productData.id || !productData.name || isNaN(productData.stock)) {
          showNotification({
            type: "error",
            title: "Data Produk Tidak Lengkap",
            message:
              "Terdapat informasi produk yang tidak lengkap. Silakan coba produk lain.",
            autoClose: true,
          });
          return;
        }

        promptQuantity(productData);
      } catch (err) {
        console.error("Error handling product selection:", err);
        showNotification({
          type: "error",
          title: "Error",
          message: "Terjadi kesalahan saat memilih produk.",
          autoClose: true,
        });
      }
    });
}

/**
 * Escape HTML special characters to prevent XSS
 *
 * @param {string} unsafe - String that may contain HTML special chars
 * @return {string} Escaped safe string
 */
function escapeHtml(unsafe) {
  if (unsafe === null || unsafe === undefined) return "";

  return String(unsafe)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/**
 * Update pagination display elements
 *
 * @param {number} startIndex - Index of first displayed item
 * @param {number} endIndex - Index of last displayed item
 */
function updatePaginationDisplay(startIndex, endIndex) {
  const state = window.simplePaginationState;

  // Update pagination info text
  $("#simplePaginationInfo").text(
    `Menampilkan ${
      state.totalItems > 0 ? startIndex + 1 : 0
    }-${endIndex} dari ${state.totalItems} produk`
  );

  // Update page numbers
  $("#simpleCurrentPage").text(state.currentPage);
  $("#simpleTotalPages").text(state.totalPages);

  // Update button states
  $("#simplePrevPage").prop("disabled", state.currentPage === 1);
  $("#simpleNextPage").prop(
    "disabled",
    state.currentPage === state.totalPages || state.totalItems === 0
  );

  // Show/hide pagination
  $("#simplePagination").toggle(state.totalItems > 0);
}

/**
 * Render the current page of products
 */
function renderProductPage() {
  const state = window.simplePaginationState;
  let filteredProducts = [];

  // Ensure allProducts is an array before processing
  if (Array.isArray(state.allProducts)) {
    filteredProducts = state.allProducts.slice();
  } else {
    console.error(
      "Expected allProducts to be an array, got:",
      typeof state.allProducts
    );
    filteredProducts = [];
  }

  // Apply search filter if search term exists
  const searchTerm = $("#simpleProductSearch").val().toLowerCase();
  if (searchTerm && searchTerm.length > 0) {
    filteredProducts = filteredProducts.filter((product) => {
      if (!product) return false;

      // Search across multiple fields
      return (
        (product.nama_pro &&
          product.nama_pro.toLowerCase().includes(searchTerm)) ||
        (product.no_bcode &&
          product.no_bcode.toLowerCase().includes(searchTerm)) ||
        (product.kategori_obat &&
          product.kategori_obat.toLowerCase().includes(searchTerm))
      );
    });
  }

  // Apply category filter if selected
  const categoryFilter = $("#simpleProductCategory").val();
  if (categoryFilter && categoryFilter.length > 0) {
    filteredProducts = filteredProducts.filter((product) => {
      if (!product) return false;
      return product.kategori_obat === categoryFilter;
    });
  }

  // Apply sorting based on selected sort option with proper error handling
  try {
    const sortType = $("#simpleProductSort").val();
    switch (sortType) {
      case "name":
        filteredProducts.sort((a, b) => {
          const nameA = (a.nama_pro || "").toString().toLowerCase();
          const nameB = (b.nama_pro || "").toString().toLowerCase();
          return nameA.localeCompare(nameB);
        });
        break;

      case "stock":
        filteredProducts.sort((a, b) => {
          const stockA = parseInt(a.sisa_psd || 0);
          const stockB = parseInt(b.sisa_psd || 0);
          return stockB - stockA; // Descending
        });
        break;

      case "expire":
        filteredProducts.sort((a, b) => {
          // Handle null dates
          if (!a.tgl_expired && !b.tgl_expired) return 0;
          if (!a.tgl_expired) return 1;
          if (!b.tgl_expired) return -1;

          // Compare dates
          return new Date(a.tgl_expired) - new Date(b.tgl_expired);
        });
        break;
    }
  } catch (err) {
    console.error("Error sorting products:", err);
  }

  // Update pagination state with filtered products
  state.totalItems = filteredProducts.length;
  state.totalPages = Math.max(
    1,
    Math.ceil(state.totalItems / state.itemsPerPage)
  );

  // Ensure current page is valid after filtering
  if (state.currentPage > state.totalPages) {
    state.currentPage = state.totalPages;
  }

  // Get items for current page
  const startIndex = (state.currentPage - 1) * state.itemsPerPage;
  const endIndex = Math.min(startIndex + state.itemsPerPage, state.totalItems);
  const currentPageItems = filteredProducts.slice(startIndex, endIndex);

  // Update pagination display
  updatePaginationDisplay(startIndex, endIndex);

  // Render product rows
  renderProductRows(currentPageItems);
}

/**
 * Display quantity selection prompt for a product
 *
 * @param {Object} product - Product data object
 */
function promptQuantity(product) {
  // Format date for display with validation
  let formattedExpireDate = "Tidak Ada Tanggal";

  if (product.expire) {
    try {
      const expireDate = new Date(product.expire);
      if (!isNaN(expireDate.getTime())) {
        formattedExpireDate = expireDate.toLocaleDateString("id-ID");
      }
    } catch (err) {
      console.error("Error formatting expiry date:", err);
    }
  }

  // Ensure stock is a valid number
  const stock = isNaN(parseFloat(product.stock))
    ? 0
    : parseFloat(product.stock);

  // Create quantity prompt modal
  $("body").append(`
        <div id="quantityPrompt" class="simple-modal-overlay" style="z-index:1051;">
            <div class="simple-modal-container" style="max-width:450px;">
                <!-- Prompt Header -->
                <div class="simple-modal-header">
                    <h5>Jumlah Transfer</h5>
                    <button type="button" class="simple-modal-close" id="closeQuantityPrompt">&times;</button>
                </div>
                
                <!-- Prompt Body -->
                <div class="simple-modal-body">
                    <!-- Product Info Card -->
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <h6>${escapeHtml(product.name)}</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Batch</small>
                                    <p><strong>${escapeHtml(
                                      product.batch
                                    )}</strong></p>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Kadaluarsa</small>
                                    <p><strong>${formattedExpireDate}</strong></p>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted">Stok Tersedia</small>
                                <p><strong>${stock.toLocaleString(
                                  "id-ID"
                                )}</strong></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quantity Input -->
                    <div class="form-group">
                        <label for="quantity">Jumlah Transfer <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <button class="btn btn-outline-secondary" type="button" id="decreaseQty">-</button>
                            </div>
                            <input type="number" class="form-control text-center" id="quantity" min="1" max="${stock}" value="${Math.min(
    1,
    stock
  )}">
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" id="increaseQty">+</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Maksimal: ${stock.toLocaleString(
                          "id-ID"
                        )}</small>
                    </div>
                </div>
                
                <!-- Prompt Footer -->
                <div class="simple-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="cancelQuantity">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirmQuantity">Tambahkan</button>
                </div>
            </div>
        </div>
    `);

  // Setup quantity adjustment controls
  setupQuantityControls(product);
}

/**
 * Set up controls for quantity adjustment in the prompt
 *
 * @param {Object} product - Product data object
 */
function setupQuantityControls(product) {
  // Get elements
  const quantityInput = $("#quantity");
  const decreaseBtn = $("#decreaseQty");
  const increaseBtn = $("#increaseQty");
  const confirmBtn = $("#confirmQuantity");
  const cancelBtn = $("#cancelQuantity, #closeQuantityPrompt");

  // Set max value based on available stock
  const maxQty = isNaN(parseFloat(product.stock))
    ? 0
    : parseFloat(product.stock);
  quantityInput.attr("max", maxQty);

  // Decrease button
  decreaseBtn.on("click", function () {
    let currentQty = parseInt(quantityInput.val()) || 0;
    if (currentQty > 1) {
      quantityInput.val(currentQty - 1);
    }
  });

  // Increase button
  increaseBtn.on("click", function () {
    let currentQty = parseInt(quantityInput.val()) || 0;
    if (currentQty < maxQty) {
      quantityInput.val(currentQty + 1);
    }
  });

  // Ensure valid input on change
  quantityInput.on("change", function () {
    let value = parseInt($(this).val()) || 0;
    if (value < 1) value = 1;
    if (value > maxQty) value = maxQty;
    $(this).val(value);
  });

  // Cancel button
  cancelBtn.on("click", function () {
    $("#quantityPrompt").remove();
  });

  // Confirm button
  confirmBtn.on("click", function () {
    const quantity = parseInt(quantityInput.val()) || 0;

    if (quantity < 1 || quantity > maxQty) {
      showNotification({
        type: "warning",
        title: "Jumlah Tidak Valid",
        message: `Jumlah harus antara 1 sampai ${maxQty}`,
        autoClose: true,
      });
      return;
    }

    // Add product to transfer list
    addProductToTransferList(product, quantity);

    // Close prompt
    $("#quantityPrompt").remove();

    // Close product selection modal
    $("#simpleModal").remove();
  });
}

/**
 * Add product to the transfer list table
 *
 * @param {Object} product - Product data object
 * @param {number} quantity - Quantity to add
 */
function addProductToTransferList(product, quantity) {
  // Get current count
  const count = parseInt($("#countaddProductTransfer").val() || "0");

  // Format expire date
  let formattedExpire = "N/A";
  try {
    if (product.expire) {
      const expireDate = new Date(product.expire);
      if (!isNaN(expireDate.getTime())) {
        formattedExpire = expireDate.toLocaleDateString("id-ID");
      }
    }
  } catch (err) {
    console.error("Error formatting date:", err);
  }

  // Create new row HTML
  const newRow = `
        <tr id="product-row-${count}">
            <td class="text-center">${count + 1}</td>
            <td>${escapeHtml(product.name)}</td>
            <td>${escapeHtml(product.batch)}</td>
            <td>${formattedExpire}</td>
            <td>${parseFloat(product.stock).toLocaleString("id-ID")}</td>
            <td>
                <input type="number" name="qty_${count}" class="form-control input-sm qty-input" 
                    value="${quantity}" min="1" max="${product.stock}" 
                    onchange="validateQty(this, ${
                      product.stock
                    }); updateSummary()">
                <input type="hidden" name="product_id_${count}" value="${
    product.id
  }">
                <input type="hidden" name="psd_id_${count}" value="${
    product.psd_id
  }">
                <input type="hidden" name="product_name_${count}" value="${
    product.name
  }">
                <input type="hidden" name="batch_${count}" value="${
    product.batch
  }">
                <input type="hidden" name="expire_${count}" value="${
    product.expire
  }">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeProduct(${count})">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

  // Append to table and update display
  $("#tableTransferProduct").append(newRow);
  $("#countaddProductTransfer").val(count + 1);

  // Show product table and hide empty state
  $("#emptyProductState").addClass("d-none");
  $("#productTableContainer").removeClass("d-none");

  // Update summary
  updateSummary();

  // Show success notification
  showNotification({
    type: "success",
    title: "Produk Ditambahkan",
    message: `${quantity} ${product.name} berhasil ditambahkan ke daftar transfer`,
    autoClose: true,
  });
}

/**
 * Validate quantity input within min/max limits
 *
 * @param {HTMLElement} input - Quantity input element
 * @param {number} maxStock - Maximum stock allowed
 */
function validateQty(input, maxStock) {
  let value = parseInt($(input).val()) || 0;
  if (value < 1) {
    $(input).val(1);
    showNotification({
      type: "warning",
      title: "Jumlah Minimum",
      message: "Jumlah minimum transfer adalah 1",
      autoClose: true,
    });
  } else if (value > maxStock) {
    $(input).val(maxStock);
    showNotification({
      type: "warning",
      title: "Jumlah Maksimum",
      message: `Jumlah maksimum transfer adalah ${maxStock}`,
      autoClose: true,
    });
  }
}

/**
 * Remove product from the transfer list
 *
 * @param {number} index - Product row index to remove
 */
function removeProduct(index) {
  // Remove the row
  $(`#product-row-${index}`).remove();

  // Check if table is now empty
  if ($("#tableTransferProduct tr").length === 0) {
    $("#emptyProductState").removeClass("d-none");
    $("#productTableContainer").addClass("d-none");
  }

  // Update summary
  updateSummary();
}

/**
 * Update transfer summary information
 */
function updateSummary() {
  // Count items
  const itemCount = $("#tableTransferProduct tr").length;

  // Sum quantities
  let totalQty = 0;
  $(".qty-input").each(function () {
    totalQty += parseInt($(this).val()) || 0;
  });

  // Update summary display
  $("#totalItems").text(itemCount);
  $("#totalQty").text(totalQty.toLocaleString("id-ID"));
}

/**
 * Initialize the warehouse transfer page
 * This function is called from input.php
 *
 * @param {string} baseUrl - Base URL for AJAX requests
 */
function initTransferPage(baseUrl) {
  // Set global baseUrl variable
  window.baseUrl = baseUrl;
  console.log("Transfer page initialized with baseUrl:", baseUrl);

  // Log environmental information for debugging
  logEnvironmentInfo();

  // Initialize UI components
  initializeComponents();

  // Set up event handlers
  setupEventHandlers();

  // Create notification container
  createNotificationContainer();
}
