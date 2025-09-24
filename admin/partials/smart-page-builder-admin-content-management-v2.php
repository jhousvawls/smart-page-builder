<?php
/**
 * Content Management Interface for Smart Page Builder - Cache Bypass Version
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// FORCE CACHE BYPASS - New file name
$cache_buster = time() . '_' . rand(1000, 9999);
?>

<div class="wrap spb-content-management">
    <div class="spb-content-header">
        <h1 class="spb-page-title">
            Content Management <span class="spb-version">v3.2.2-CACHE-BYPASS</span>
        </h1>
        <p class="spb-page-subtitle">
            Cache bypass test - if you see this, the latest code is loading!
        </p>
    </div>

    <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; margin: 20px 0; border-radius: 5px;">
        <h3 style="color: #155724; margin: 0 0 10px 0;">🎯 Cache Bypass Test Active</h3>
        <p style="color: #155724; margin: 0;">
            <strong>Cache Buster ID:</strong> <?php echo $cache_buster; ?><br>
            <strong>File:</strong> smart-page-builder-admin-content-management-v2.php<br>
            <strong>Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>

    <!-- Sample Content for Testing -->
    <div class="spb-content-table-container">
        <table class="spb-content-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" id="spb-select-all">
                    </td>
                    <th>Content Title</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" class="spb-content-checkbox" value="test_1">
                    </th>
                    <td>
                        <strong>Test Content Item 1</strong>
                    </td>
                    <td>
                        <button type="button" class="spb-delete-btn button" data-content-id="test_1">
                            Delete Test
                        </button>
                    </td>
                </tr>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" class="spb-content-checkbox" value="test_2">
                    </th>
                    <td>
                        <strong>Test Content Item 2</strong>
                    </td>
                    <td>
                        <button type="button" class="spb-delete-btn button" data-content-id="test_2">
                            Delete Test
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.spb-content-management {
    max-width: 100%;
}

.spb-content-header {
    margin-bottom: 30px;
    padding: 20px 0;
    border-bottom: 1px solid #ddd;
}

.spb-page-title {
    font-size: 2em;
    margin: 0;
    color: #333;
}

.spb-version {
    font-size: 0.6em;
    color: #666;
    font-weight: normal;
}

.spb-page-subtitle {
    margin: 10px 0 0 0;
    color: #666;
    font-size: 1.1em;
}

.spb-content-table-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 20px;
}

.spb-content-table {
    margin: 0;
    border: none;
}

.spb-content-table th,
.spb-content-table td {
    padding: 12px;
    vertical-align: middle;
}
</style>

<script>
// ULTIMATE CACHE BYPASS TEST
console.log('=== CACHE BYPASS TEST ===');
console.log('File: smart-page-builder-admin-content-management-v2.php');
console.log('Cache Buster:', '<?php echo $cache_buster; ?>');
console.log('Timestamp:', new Date().toISOString());
console.log('=== END CACHE BYPASS TEST ===');

// Test AJAX variables
var spb_admin = spb_admin || {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('spb_admin_nonce'); ?>'
};

console.log('SPB Admin Variables (Cache Bypass):', spb_admin);

jQuery(document).ready(function($) {
    console.log('Cache Bypass JavaScript loaded successfully!');
    
    // Test delete functionality
    $('.spb-delete-btn').on('click', function() {
        var contentId = $(this).data('content-id');
        console.log('Delete button clicked for:', contentId);
        
        if (confirm('Test delete for: ' + contentId + '?')) {
            // Test AJAX call
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_delete_content',
                    content_id: contentId,
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    console.log('AJAX Success:', response);
                    alert('AJAX call successful! Response: ' + JSON.stringify(response));
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error:', error);
                    alert('AJAX Error: ' + error);
                }
            });
        }
    });
});
</script>
