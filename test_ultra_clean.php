<!DOCTYPE html>
<html>
<head>
    <title>Ultra-Clean Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">
    <h2>🚀 Ultra-Clean AJAX Test</h2>
    
    <div class="alert alert-info">
        <strong>Goal:</strong> Zero syntax errors, clean HTML output, working product selection.
    </div>
    
    <button onclick="testUltraClean()" class="btn btn-success btn-lg btn-block mb-3">
        ⚡ Test Ultra-Clean AJAX
    </button>
    
    <div id="status" class="alert alert-secondary">Ready...</div>
    
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Warehouse</th>
                    <th>Expiry</th>
                    <th>Stock</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody id="results">
                <tr><td colspan="6" class="text-center">Click test button</td></tr>
            </tbody>
        </table>
    </div>

    <script>
    function testUltraClean() {
        $('#status').removeClass().addClass('alert alert-info').html('🔄 Testing ultra-clean AJAX...');
        $('#results').html('<tr><td colspan="6" class="text-center"><div class="spinner-border"></div></td></tr>');
        
        $.ajax({
            url: 'ajax/loadProducts_ultra.php',
            type: 'POST',
            data: {
                principle: 'Cendo',
                mitra: 'TEST001',
                cart: '',
                nomor: '1',
                search: ''
            },
            success: function(response) {
                try {
                    // Test if response is clean
                    if (!response || response.trim() === '') {
                        throw new Error('Empty response');
                    }
                    
                    // Test DOM insertion
                    $('#results').html(response);
                    
                    var rows = $('#results tr').length;
                    $('#status').removeClass().addClass('alert alert-success')
                        .html('✅ SUCCESS! ' + rows + ' rows loaded without errors');
                    
                    // Test click handling
                    $(document).off('click', '.product-row').on('click', '.product-row', function() {
                        var name = $(this).data('nama-pro');
                        $('#status').removeClass().addClass('alert alert-info')
                            .html('🖱️ Product clicked: ' + name);
                    });
                    
                } catch(e) {
                    $('#status').removeClass().addClass('alert alert-danger')
                        .html('❌ ERROR: ' + e.message);
                    console.error('Error:', e);
                }
            },
            error: function(xhr, status, error) {
                $('#status').removeClass().addClass('alert alert-danger')
                    .html('❌ AJAX ERROR: ' + error);
                console.error('AJAX Error:', error);
            }
        });
    }
    
    // Auto-test on load
    $(document).ready(function() {
        setTimeout(testUltraClean, 1000);
    });
    </script>
</body>
</html>