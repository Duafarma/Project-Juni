<!DOCTYPE html>
<html>
<head>
    <title>Quick Fix Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">
    <h2><i class="fas fa-wrench"></i> Quick AJAX Fix Test</h2>
    
    <div class="alert alert-info">
        <h5>Testing Ajax Response Without Syntax Errors:</h5>
        <p>This test calls the cleaned loadProducts_clean.php directly to verify no JavaScript syntax errors occur.</p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <h5>Test Parameters:</h5>
            <select id="testPrinciple" class="form-control mb-2">
                <option value="Cendo">Cendo</option>
                <option value="DPE">DPE</option>
                <option value="PIM">PIM</option>
                <option value="RHEA">RHEA</option>
                <option value="Shirudo">Shirudo</option>
            </select>
            <button onclick="testCleanAjax()" class="btn btn-success btn-block">
                <i class="fas fa-play"></i> Test Clean AJAX Call
            </button>
        </div>
        
        <div class="col-md-6">
            <h5>Status:</h5>
            <div id="status" class="alert alert-secondary">Ready to test...</div>
        </div>
    </div>
    
    <hr>
    
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Product Name</th>
                    <th>Batch Code</th>
                    <th>Warehouse</th>
                    <th>Expiry Date</th>
                    <th>Stock</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody id="testResults">
                <tr><td colspan="6" class="text-center text-muted">Click test button to load products</td></tr>
            </tbody>
        </table>
    </div>

    <script>
    function testCleanAjax() {
        var principle = $('#testPrinciple').val();
        
        $('#status').removeClass().addClass('alert alert-info').html('<i class="fas fa-spinner fa-spin"></i> Testing...');
        $('#testResults').html('<tr><td colspan="6" class="text-center"><div class="spinner-border"></div></td></tr>');
        
        $.ajax({
            url: 'ajax/loadProducts_clean.php',
            type: 'POST',
            data: {
                principle: principle,
                mitra: 'TEST001',
                cart: '',
                nomor: '1',
                search: ''
            },
            success: function(response) {
                console.log('Success! Response length:', response.length);
                
                if (response.trim() === '') {
                    $('#status').removeClass().addClass('alert alert-warning').html('<i class="fas fa-exclamation-triangle"></i> Empty response');
                    $('#testResults').html('<tr><td colspan="6" class="text-center text-warning">Empty response received</td></tr>');
                    return;
                }
                
                try {
                    // Test if response can be inserted without syntax errors
                    $('#testResults').html(response);
                    
                    var rowCount = $('#testResults tr').length;
                    $('#status').removeClass().addClass('alert alert-success').html('<i class="fas fa-check"></i> Success! ' + rowCount + ' rows loaded without syntax errors');
                    
                    // Test product row click
                    $(document).off('click', '.product-row').on('click', '.product-row', function() {
                        var productName = $(this).data('nama-pro');
                        $('#status').removeClass().addClass('alert alert-success').html('<i class="fas fa-mouse-pointer"></i> Product clicked: ' + productName);
                    });
                    
                } catch(e) {
                    $('#status').removeClass().addClass('alert alert-danger').html('<i class="fas fa-times"></i> JavaScript Error: ' + e.message);
                    console.error('Error:', e);
                }
            },
            error: function(xhr, status, error) {
                $('#status').removeClass().addClass('alert alert-danger').html('<i class="fas fa-times"></i> AJAX Error: ' + error);
                $('#testResults').html('<tr><td colspan="6" class="text-center text-danger">Error: ' + error + '</td></tr>');
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
            }
        });
    }
    
    $(document).ready(function() {
        console.log('Test page ready');
    });
    </script>
</body>
</html>