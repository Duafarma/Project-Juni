<!DOCTYPE html>
<html>
<head>
    <title>Test Modal Product Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="p-4">
    <h3><i class="fas fa-test-tube"></i> Test Modal Product Selection</h3>
    
    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalProductSelection">
        <i class="fas fa-box"></i> Test Product Selection Modal
    </button>
    
    <div class="mt-3">
        <h5>Debug Console:</h5>
        <div id="debugConsole" class="border p-3 bg-light" style="height: 300px; overflow-y: scroll; font-family: monospace; font-size: 12px;"></div>
        <button onclick="clearConsole()" class="btn btn-sm btn-secondary mt-2">Clear Console</button>
    </div>

    <!-- Load Modal -->
    <?php 
    // Set required variables for modal
    $nomor = 1;
    $mitra = 'TEST001';
    $cart = '';
    include 'modal/addsales/addsales.php'; 
    ?>

    <script>
    function log(message, type = 'info') {
        var timestamp = new Date().toLocaleTimeString();
        var color = type === 'error' ? 'red' : (type === 'success' ? 'green' : 'blue');
        $('#debugConsole').append('<div style="color: ' + color + ';">[' + timestamp + '] ' + message + '</div>');
        $('#debugConsole').scrollTop($('#debugConsole')[0].scrollHeight);
    }
    
    function clearConsole() {
        $('#debugConsole').empty();
    }
    
    // Override console.log to capture all logs
    var originalLog = console.log;
    var originalError = console.error; 
    var originalWarn = console.warn;
    
    console.log = function() {
        originalLog.apply(console, arguments);
        log(Array.prototype.slice.call(arguments).join(' '), 'info');
    };
    
    console.error = function() {
        originalError.apply(console, arguments);
        log(Array.prototype.slice.call(arguments).join(' '), 'error');
    };
    
    console.warn = function() {
        originalWarn.apply(console, arguments);
        log(Array.prototype.slice.call(arguments).join(' '), 'error');
    };
    
    // Test when modal opens
    $('#modalProductSelection').on('shown.bs.modal', function () {
        log('Modal opened successfully', 'success');
        log('Testing principle tabs...', 'info');
        
        // Check if tabs exist
        var tabs = $('.principle-tab').length;
        log('Principle tabs found: ' + tabs, tabs > 0 ? 'success' : 'error');
        
        // Check if first tab is active
        var activeTab = $('.principle-tab.active').length;
        log('Active tabs: ' + activeTab, activeTab > 0 ? 'success' : 'error');
    });
    
    $(document).ready(function() {
        log('Test page loaded', 'success');
    });
    </script>
</body>
</html>