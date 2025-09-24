<?php
/**
 * WP Engine Integration Admin Interface
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$wpengine_settings = [
    'api_url' => get_option('spb_wpengine_api_url', ''),
    'access_token' => get_option('spb_wpengine_access_token', ''),
    'site_id' => get_option('spb_wpengine_site_id', ''),
    'enable_search_interception' => get_option('spb_enable_search_interception', true),
    'auto_approve_threshold' => get_option('spb_auto_approve_threshold', 0.8),
    'enable_seo_urls' => get_option('spb_enable_seo_urls', true),
    'min_query_length' => get_option('spb_min_query_length', 3),
    'max_query_length' => get_option('spb_max_query_length', 200)
];

// Test connection if credentials are provided
$connection_status = null;
if (!empty($wpengine_settings['api_url']) && !empty($wpengine_settings['access_token']) && !empty($wpengine_settings['site_id'])) {
    if (class_exists('SPB_WPEngine_API_Client')) {
        $api_client = new SPB_WPEngine_API_Client();
        $connection_status = $api_client->test_connection();
    }
}
?>

<div class="wrap">
    <div class="spb-page-header">
        <h1><?php echo esc_html(get_admin_page_title()); ?> - WP Engine Integration</h1>
        <p class="spb-page-description">AI Toolkit configuration and testing</p>
    </div>
    
    <?php if (isset($_GET['settings-updated'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><strong>Settings saved successfully!</strong></p>
        </div>
    <?php endif; ?>
    
    <div class="spb-admin-container">
        <div class="spb-admin-main">
            
            <!-- Connection Status -->
            <?php if ($connection_status): ?>
                <div class="spb-connection-status">
                    <?php if ($connection_status['success']): ?>
                        <div class="notice notice-success inline">
                            <p><span class="dashicons dashicons-yes-alt"></span> <strong>Connected to WP Engine AI Toolkit</strong></p>
                            <p><?php echo esc_html($connection_status['message']); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-error inline">
                            <p><span class="dashicons dashicons-warning"></span> <strong>Connection Failed</strong></p>
                            <p><?php echo esc_html($connection_status['error']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="options.php" class="spb-wpengine-form">
                <?php
                settings_fields('spb_wpengine_settings');
                do_settings_sections('spb_wpengine_settings');
                ?>
                
                <!-- API Configuration -->
                <div class="spb-settings-section">
                    <h2>WP Engine AI Toolkit Configuration</h2>
                    <p class="description">Configure your WP Engine AI Toolkit API credentials to enable search-triggered page generation.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="spb_wpengine_api_url">API Endpoint URL</label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="spb_wpengine_api_url" 
                                       name="spb_wpengine_api_url" 
                                       value="<?php echo esc_attr($wpengine_settings['api_url']); ?>" 
                                       class="regular-text" 
                                       placeholder="https://api.wpengine.com/v1" />
                                <p class="description">The base URL for your WP Engine AI Toolkit API endpoint.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="spb_wpengine_access_token">Access Token</label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="spb_wpengine_access_token" 
                                       name="spb_wpengine_access_token" 
                                       value="<?php echo esc_attr($wpengine_settings['access_token']); ?>" 
                                       class="regular-text" 
                                       placeholder="Enter your API access token" />
                                <p class="description">Your WP Engine AI Toolkit access token. This will be stored securely.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="spb_wpengine_site_id">Site ID</label>
                            </th>
                            <td>
                                <input type="text" 
                                       id="spb_wpengine_site_id" 
                                       name="spb_wpengine_site_id" 
                                       value="<?php echo esc_attr($wpengine_settings['site_id']); ?>" 
                                       class="regular-text" 
                                       placeholder="your-site-id" />
                                <p class="description">Your WP Engine site identifier for API requests.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Search Integration Settings -->
                <div class="spb-settings-section">
                    <h2>Search Integration Settings</h2>
                    <p class="description">Configure how Smart Page Builder intercepts and processes search queries.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Enable Search Interception</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" 
                                               name="spb_enable_search_interception" 
                                               value="1" 
                                               <?php checked($wpengine_settings['enable_search_interception']); ?> />
                                        Intercept search queries and generate smart pages
                                    </label>
                                    <p class="description">When enabled, search queries will trigger AI-powered page generation instead of showing standard search results.</p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="spb_min_query_length">Minimum Query Length</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="spb_min_query_length" 
                                       name="spb_min_query_length" 
                                       value="<?php echo esc_attr($wpengine_settings['min_query_length']); ?>" 
                                       min="1" 
                                       max="50" 
                                       class="small-text" />
                                <p class="description">Minimum number of characters required for search query processing.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="spb_max_query_length">Maximum Query Length</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="spb_max_query_length" 
                                       name="spb_max_query_length" 
                                       value="<?php echo esc_attr($wpengine_settings['max_query_length']); ?>" 
                                       min="50" 
                                       max="500" 
                                       class="small-text" />
                                <p class="description">Maximum number of characters allowed for search query processing.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Page Generation Settings -->
                <div class="spb-settings-section">
                    <h2>Page Generation Settings</h2>
                    <p class="description">Configure how generated pages are approved and displayed.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="spb_auto_approve_threshold">Auto-Approval Threshold</label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="spb_auto_approve_threshold" 
                                       name="spb_auto_approve_threshold" 
                                       value="<?php echo esc_attr($wpengine_settings['auto_approve_threshold']); ?>" 
                                       min="0" 
                                       max="1" 
                                       step="0.1" 
                                       class="small-text" />
                                <p class="description">Confidence score threshold (0-1) for automatically approving generated pages. Pages below this threshold require manual approval.</p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">Enable SEO-Friendly URLs</th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" 
                                               name="spb_enable_seo_urls" 
                                               value="1" 
                                               <?php checked($wpengine_settings['enable_seo_urls']); ?> />
                                        Use SEO-friendly URLs for generated pages
                                    </label>
                                    <p class="description">When enabled, generated pages will use URLs like <code>/smart-page/query-hash/</code> instead of query parameters.</p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button('Save WP Engine Settings'); ?>
            </form>
            
            <!-- Test Connection Button -->
            <div class="spb-settings-section">
                <h2>Connection Testing</h2>
                <p class="description">Test your WP Engine AI Toolkit integration to ensure everything is working correctly.</p>
                
                <button type="button" id="spb-test-connection" class="button button-secondary">
                    <span class="dashicons dashicons-admin-tools"></span> Test Connection
                </button>
                
                <button type="button" id="spb-test-integration" class="button button-secondary" style="margin-left: 10px;">
                    <span class="dashicons dashicons-search"></span> Test Full Integration
                </button>
                
                <div id="spb-test-results" class="spb-test-results" style="margin-top: 15px;"></div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="spb-admin-sidebar">
            <div class="spb-sidebar-widget">
                <h3>WP Engine AI Toolkit</h3>
                <p>The WP Engine AI Toolkit provides powerful search and content discovery capabilities:</p>
                <ul>
                    <li><strong>Smart Search:</strong> Enhanced search with semantic understanding</li>
                    <li><strong>Vector Database:</strong> Semantic content matching and discovery</li>
                    <li><strong>Recommendations:</strong> Personalized content suggestions</li>
                </ul>
                <p><a href="https://wpengine.com/ai-toolkit/" target="_blank" class="button button-primary">Learn More</a></p>
            </div>
            
            <div class="spb-sidebar-widget">
                <h3>Search Page Generation</h3>
                <p>When users search your site, Smart Page Builder will:</p>
                <ol>
                    <li>Enhance the search query with AI</li>
                    <li>Discover relevant content from multiple sources</li>
                    <li>Generate a personalized page in real-time</li>
                    <li>Present a custom experience instead of search results</li>
                </ol>
            </div>
            
            <div class="spb-sidebar-widget">
                <h3>Need Help?</h3>
                <p>Check out our documentation and support resources:</p>
                <ul>
                    <li><a href="#" target="_blank">Setup Guide</a></li>
                    <li><a href="#" target="_blank">API Documentation</a></li>
                    <li><a href="#" target="_blank">Troubleshooting</a></li>
                    <li><a href="#" target="_blank">Support Forum</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.spb-admin-container {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.spb-admin-main {
    flex: 1;
    max-width: 800px;
}

.spb-admin-sidebar {
    width: 300px;
    flex-shrink: 0;
}

.spb-settings-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.spb-settings-section h2 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 18px;
}

.spb-connection-status {
    margin-bottom: 20px;
}

.spb-connection-status .notice {
    margin: 0;
}

.spb-connection-status .dashicons {
    margin-right: 5px;
}

.spb-sidebar-widget {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.spb-sidebar-widget h3 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 16px;
}

.spb-sidebar-widget ul,
.spb-sidebar-widget ol {
    margin-left: 20px;
}

.spb-test-results {
    display: none;
}

.spb-test-results.show {
    display: block;
}

.spb-test-result-item {
    padding: 10px;
    margin: 5px 0;
    border-radius: 4px;
    border-left: 4px solid #ddd;
}

.spb-test-result-item.success {
    background: #f0f8f0;
    border-left-color: #46b450;
}

.spb-test-result-item.error {
    background: #fef7f7;
    border-left-color: #dc3232;
}

.spb-test-result-item.warning {
    background: #fff8e5;
    border-left-color: #ffb900;
}

.spb-test-result-item .dashicons {
    margin-right: 5px;
}

.spb-wpengine-form .form-table th {
    width: 200px;
}

@media (max-width: 1200px) {
    .spb-admin-container {
        flex-direction: column;
    }
    
    .spb-admin-sidebar {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Test Connection
    $('#spb-test-connection').on('click', function() {
        var $button = $(this);
        var $results = $('#spb-test-results');
        
        $button.prop('disabled', true).text('Testing...');
        $results.html('<div class="spb-test-result-item"><span class="dashicons dashicons-update spin"></span> Testing connection...</div>').addClass('show');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spb_test_wpengine_connection',
                nonce: '<?php echo wp_create_nonce('spb_test_connection'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $results.html('<div class="spb-test-result-item success"><span class="dashicons dashicons-yes-alt"></span> Connection successful!</div>');
                } else {
                    $results.html('<div class="spb-test-result-item error"><span class="dashicons dashicons-warning"></span> Connection failed: ' + response.data.message + '</div>');
                }
            },
            error: function() {
                $results.html('<div class="spb-test-result-item error"><span class="dashicons dashicons-warning"></span> Connection test failed due to an error.</div>');
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-admin-tools"></span> Test Connection');
            }
        });
    });
    
    // Test Full Integration
    $('#spb-test-integration').on('click', function() {
        var $button = $(this);
        var $results = $('#spb-test-results');
        
        $button.prop('disabled', true).text('Testing...');
        $results.html('<div class="spb-test-result-item"><span class="dashicons dashicons-update spin"></span> Testing full integration...</div>').addClass('show');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'spb_test_wpengine_integration',
                nonce: '<?php echo wp_create_nonce('spb_test_integration'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    var results = response.data;
                    var html = '';
                    
                    // API Connection
                    html += '<div class="spb-test-result-item ' + (results.api_connection ? 'success' : 'error') + '">';
                    html += '<span class="dashicons dashicons-' + (results.api_connection ? 'yes-alt' : 'warning') + '"></span>';
                    html += 'API Connection: ' + (results.api_connection ? 'Success' : 'Failed');
                    html += '</div>';
                    
                    // Smart Search
                    html += '<div class="spb-test-result-item ' + (results.smart_search ? 'success' : 'error') + '">';
                    html += '<span class="dashicons dashicons-' + (results.smart_search ? 'yes-alt' : 'warning') + '"></span>';
                    html += 'Smart Search: ' + (results.smart_search ? 'Working' : 'Failed');
                    html += '</div>';
                    
                    // Vector Search
                    html += '<div class="spb-test-result-item ' + (results.vector_search ? 'success' : 'error') + '">';
                    html += '<span class="dashicons dashicons-' + (results.vector_search ? 'yes-alt' : 'warning') + '"></span>';
                    html += 'Vector Search: ' + (results.vector_search ? 'Working' : 'Failed');
                    html += '</div>';
                    
                    // Recommendations
                    html += '<div class="spb-test-result-item ' + (results.recommendations ? 'success' : 'error') + '">';
                    html += '<span class="dashicons dashicons-' + (results.recommendations ? 'yes-alt' : 'warning') + '"></span>';
                    html += 'Recommendations: ' + (results.recommendations ? 'Working' : 'Failed');
                    html += '</div>';
                    
                    // Query Enhancement
                    html += '<div class="spb-test-result-item ' + (results.query_enhancement ? 'success' : 'warning') + '">';
                    html += '<span class="dashicons dashicons-' + (results.query_enhancement ? 'yes-alt' : 'warning') + '"></span>';
                    html += 'Query Enhancement: ' + (results.query_enhancement ? 'Working' : 'Limited');
                    html += '</div>';
                    
                    // Overall Status
                    var statusClass = results.overall_status === 'passed' ? 'success' : (results.overall_status === 'partial' ? 'warning' : 'error');
                    html += '<div class="spb-test-result-item ' + statusClass + '">';
                    html += '<span class="dashicons dashicons-' + (results.overall_status === 'passed' ? 'yes-alt' : 'warning') + '"></span>';
                    html += '<strong>Overall Status: ' + results.overall_status.toUpperCase() + '</strong>';
                    html += '</div>';
                    
                    // Errors
                    if (results.errors && results.errors.length > 0) {
                        html += '<div class="spb-test-result-item error">';
                        html += '<span class="dashicons dashicons-warning"></span>';
                        html += '<strong>Errors:</strong><br>' + results.errors.join('<br>');
                        html += '</div>';
                    }
                    
                    $results.html(html);
                } else {
                    $results.html('<div class="spb-test-result-item error"><span class="dashicons dashicons-warning"></span> Integration test failed: ' + response.data.message + '</div>');
                }
            },
            error: function() {
                $results.html('<div class="spb-test-result-item error"><span class="dashicons dashicons-warning"></span> Integration test failed due to an error.</div>');
            },
            complete: function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-search"></span> Test Full Integration');
            }
        });
    });
    
    // Add spinning animation for loading states
    $('<style>.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
});
</script>
