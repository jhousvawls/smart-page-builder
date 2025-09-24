<?php
/**
 * ULTIMATE CACHE BYPASS - Content Management Interface
 * 
 * This file uses a completely different name and approach to bypass ALL caching
 *
 * @package Smart_Page_Builder
 * @since 3.2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// ULTIMATE CACHE BYPASS
$ultimate_cache_buster = 'ULTIMATE_' . time() . '_' . rand(10000, 99999);
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .cache-bypass-alert {
            background: #ff6b6b !important;
            color: white !important;
            padding: 20px !important;
            margin: 20px 0 !important;
            border-radius: 8px !important;
            font-size: 18px !important;
            font-weight: bold !important;
            text-align: center !important;
            border: 3px solid #ff5252 !important;
        }
        .test-content {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .test-button {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        .test-button:hover {
            background: #005a87;
        }
    </style>
</head>
<body>

<div class="wrap">
    <div class="cache-bypass-alert">
        🚨 ULTIMATE CACHE BYPASS ACTIVE 🚨<br>
        Cache Buster: <?php echo $ultimate_cache_buster; ?><br>
        File: smart-page-builder-admin-content-management-v3.php<br>
        Time: <?php echo date('Y-m-d H:i:s'); ?>
    </div>

    <h1>Content Management - Ultimate Cache Bypass Test</h1>
    
    <div class="test-content">
        <h2>Performance Test</h2>
        <p>If you see this interface, the cache bypass worked!</p>
        
        <button class="test-button" id="performance-test">Test Performance</button>
        <button class="test-button" id="ajax-test">Test AJAX</button>
        
        <div id="test-results" style="margin-top: 20px; padding: 10px; background: #fff; border: 1px solid #ccc;"></div>
    </div>
</div>

<script>
// ULTIMATE CACHE BYPASS VERIFICATION
console.log('🚨 ULTIMATE CACHE BYPASS ACTIVE 🚨');
console.log('Cache Buster:', '<?php echo $ultimate_cache_buster; ?>');
console.log('File: smart-page-builder-admin-content-management-v3.php');
console.log('Timestamp:', new Date().toISOString());

// Performance monitoring
var performanceStart = performance.now();

// Test AJAX variables
var spb_admin = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('spb_admin_nonce'); ?>'
};

console.log('SPB Admin Variables (Ultimate Bypass):', spb_admin);

// Wait for jQuery
function waitForJQuery() {
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery loaded successfully');
        initializeTests();
    } else {
        console.log('Waiting for jQuery...');
        setTimeout(waitForJQuery, 100);
    }
}

function initializeTests() {
    jQuery(document).ready(function($) {
        var performanceEnd = performance.now();
        console.log('Page load performance:', (performanceEnd - performanceStart) + 'ms');
        
        $('#performance-test').on('click', function() {
            var start = performance.now();
            console.log('Performance test started');
            
            // Simulate the problematic operation
            var testData = [];
            for (var i = 0; i < 1000; i++) {
                testData.push('test_' + i);
            }
            
            var end = performance.now();
            var duration = end - start;
            
            console.log('Performance test completed in:', duration + 'ms');
            $('#test-results').html('<strong>Performance Test:</strong> ' + duration + 'ms');
            
            if (duration > 1000) {
                $('#test-results').append('<br><span style="color: red;">WARNING: Slow performance detected!</span>');
            }
        });
        
        $('#ajax-test').on('click', function() {
            console.log('AJAX test started');
            var start = performance.now();
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_delete_content',
                    content_id: 'test_ultimate_bypass',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    var end = performance.now();
                    console.log('AJAX test completed in:', (end - start) + 'ms');
                    console.log('AJAX Response:', response);
                    $('#test-results').html('<strong>AJAX Test:</strong> ' + (end - start) + 'ms<br><strong>Response:</strong> ' + JSON.stringify(response));
                },
                error: function(xhr, status, error) {
                    var end = performance.now();
                    console.log('AJAX test failed in:', (end - start) + 'ms');
                    console.log('AJAX Error:', error);
                    $('#test-results').html('<strong>AJAX Test Failed:</strong> ' + error + '<br><strong>Duration:</strong> ' + (end - start) + 'ms');
                }
            });
        });
        
        console.log('Ultimate cache bypass tests initialized successfully');
    });
}

// Start the process
waitForJQuery();

// Monitor for slow operations
var slowOperationThreshold = 1000; // 1 second
var originalSetTimeout = window.setTimeout;
window.setTimeout = function(callback, delay) {
    var start = performance.now();
    return originalSetTimeout(function() {
        var end = performance.now();
        if (end - start > slowOperationThreshold) {
            console.warn('Slow setTimeout detected:', (end - start) + 'ms');
        }
        callback();
    }, delay);
};

console.log('🚨 ULTIMATE CACHE BYPASS SCRIPT LOADED 🚨');
</script>

</body>
</html>
