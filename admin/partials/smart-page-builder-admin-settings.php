<?php
/**
 * System Settings Interface for Smart Page Builder
 *
 * Provides the main system settings interface for the admin area.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Settings validation function
function spb_validate_system_settings($settings) {
    $errors = array();
    
    // Validate max components per page
    if ($settings['max_components_per_page'] < 10 || $settings['max_components_per_page'] > 200) {
        $errors[] = __('Max components per page must be between 10 and 200.', 'smart-page-builder');
    }
    
    // Validate auto-save interval
    if ($settings['auto_save_interval'] < 10 || $settings['auto_save_interval'] > 300) {
        $errors[] = __('Auto-save interval must be between 10 and 300 seconds.', 'smart-page-builder');
    }
    
    // Validate cache duration
    if ($settings['cache_duration'] < 300 || $settings['cache_duration'] > 86400) {
        $errors[] = __('Cache duration must be between 300 and 86400 seconds.', 'smart-page-builder');
    }
    
    return $errors;
}

// Handle form submissions
if (isset($_POST['spb_save_system_settings']) && wp_verify_nonce($_POST['spb_system_nonce'], 'spb_save_system_settings')) {
    $system_settings = array(
        'max_components_per_page' => intval($_POST['max_components_per_page']),
        'auto_save_interval' => intval($_POST['auto_save_interval']),
        'enable_caching' => isset($_POST['enable_caching']) ? 1 : 0,
        'cache_duration' => intval($_POST['cache_duration']),
        'enable_compression' => isset($_POST['enable_compression']) ? 1 : 0,
        'debug_mode' => isset($_POST['debug_mode']) ? 1 : 0
    );
    
    // Validate settings
    $validation_errors = spb_validate_system_settings($system_settings);
    
    if (empty($validation_errors)) {
        update_option('spb_system_settings', $system_settings);
        $success_message = __('System settings saved successfully.', 'smart-page-builder');
    } else {
        $error_message = implode('<br>', $validation_errors);
    }
}

// Get current settings with defaults
$system_settings = get_option('spb_system_settings', array(
    'max_components_per_page' => 50,
    'auto_save_interval' => 30,
    'enable_caching' => 1,
    'cache_duration' => 3600,
    'enable_compression' => 1,
    'debug_mode' => 0
));

// Get system information
$system_info = array(
    'php_version' => PHP_VERSION,
    'wp_version' => get_bloginfo('version'),
    'plugin_version' => defined('SPB_VERSION') ? SPB_VERSION : '3.5.0',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
);
?>

<div class="wrap spb-system-settings">
    <div class="spb-page-header">
        <h1><?php esc_html_e('System Settings', 'smart-page-builder'); ?></h1>
        <p class="spb-page-description"><?php esc_html_e('Core plugin configuration and system management', 'smart-page-builder'); ?></p>
    </div>
    
    <!-- Display messages -->
    <?php if (isset($success_message)): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($success_message); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo wp_kses_post($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Settings Tabs -->
    <nav class="nav-tab-wrapper spb-nav-tabs">
        <a href="#core" class="nav-tab nav-tab-active" data-tab="core">
            <span class="dashicons dashicons-admin-settings"></span>
            <?php esc_html_e('Core Settings', 'smart-page-builder'); ?>
        </a>
        <a href="#system-info" class="nav-tab" data-tab="system-info">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e('System Information', 'smart-page-builder'); ?>
        </a>
    </nav>

    <!-- Core Settings Tab -->
    <div id="core-tab" class="spb-tab-content active">
        <form method="post" action="" class="spb-settings-form">
            <?php wp_nonce_field('spb_save_system_settings', 'spb_system_nonce'); ?>
            
            <div class="spb-settings-section">
                <h2><?php esc_html_e('Plugin Behavior', 'smart-page-builder'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="max_components_per_page"><?php esc_html_e('Max Components Per Page', 'smart-page-builder'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="max_components_per_page" 
                                       name="max_components_per_page" 
                                       value="<?php echo esc_attr($system_settings['max_components_per_page']); ?>" 
                                       min="10" 
                                       max="200" 
                                       class="small-text" />
                                <p class="description"><?php esc_html_e('Maximum number of components allowed per page (10-200).', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="auto_save_interval"><?php esc_html_e('Auto-save Interval', 'smart-page-builder'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="auto_save_interval" 
                                       name="auto_save_interval" 
                                       value="<?php echo esc_attr($system_settings['auto_save_interval']); ?>" 
                                       min="10" 
                                       max="300" 
                                       class="small-text" />
                                <span><?php esc_html_e('seconds', 'smart-page-builder'); ?></span>
                                <p class="description"><?php esc_html_e('How often to auto-save changes (10-300 seconds).', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="spb-settings-section">
                <h2><?php esc_html_e('Performance', 'smart-page-builder'); ?></h2>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="enable_caching"><?php esc_html_e('Enable Caching', 'smart-page-builder'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="enable_caching" 
                                           name="enable_caching" 
                                           value="1" 
                                           <?php checked($system_settings['enable_caching'], 1); ?> />
                                    <?php esc_html_e('Enable caching to improve page load times', 'smart-page-builder'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Recommended for production sites to improve performance.', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cache_duration"><?php esc_html_e('Cache Duration', 'smart-page-builder'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="cache_duration" 
                                       name="cache_duration" 
                                       value="<?php echo esc_attr($system_settings['cache_duration']); ?>" 
                                       min="300" 
                                       max="86400" 
                                       class="small-text" />
                                <span><?php esc_html_e('seconds', 'smart-page-builder'); ?></span>
                                <p class="description"><?php esc_html_e('How long to cache generated content (300-86400 seconds).', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="enable_compression"><?php esc_html_e('Enable Compression', 'smart-page-builder'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="enable_compression" 
                                           name="enable_compression" 
                                           value="1" 
                                           <?php checked($system_settings['enable_compression'], 1); ?> />
                                    <?php esc_html_e('Enable GZIP compression for better performance', 'smart-page-builder'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Reduces bandwidth usage and improves load times.', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="debug_mode"><?php esc_html_e('Debug Mode', 'smart-page-builder'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           id="debug_mode" 
                                           name="debug_mode" 
                                           value="1" 
                                           <?php checked($system_settings['debug_mode'], 1); ?> />
                                    <?php esc_html_e('Enable debug mode for troubleshooting', 'smart-page-builder'); ?>
                                </label>
                                <p class="description"><?php esc_html_e('Not recommended for production sites. Enables detailed logging.', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <h3><?php esc_html_e('Cache Management', 'smart-page-builder'); ?></h3>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Clear Cache', 'smart-page-builder'); ?></th>
                            <td>
                                <button type="button" class="button button-secondary" id="spb-clear-cache">
                                    <span class="dashicons dashicons-trash"></span>
                                    <?php esc_html_e('Clear All Cache', 'smart-page-builder'); ?>
                                </button>
                                <p class="description"><?php esc_html_e('Clear all cached data to force regeneration.', 'smart-page-builder'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <?php submit_button(__('Save System Settings', 'smart-page-builder'), 'primary', 'spb_save_system_settings'); ?>
        </form>
    </div>

    <!-- System Information Tab -->
    <div id="system-info-tab" class="spb-tab-content">
        <div class="spb-settings-section">
            <h2><?php esc_html_e('Environment Details', 'smart-page-builder'); ?></h2>
            
            <table class="widefat spb-system-info">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['plugin_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['wp_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['php_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Memory Limit', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['memory_limit']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Max Execution Time', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['max_execution_time']); ?> <?php esc_html_e('seconds', 'smart-page-builder'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Upload Max Filesize', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['upload_max_filesize']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="spb-settings-section">
            <h2><?php esc_html_e('Active Features', 'smart-page-builder'); ?></h2>
            <div class="spb-status-grid">
                <div class="spb-status-card">
                    <h4><?php esc_html_e('Core Features', 'smart-page-builder'); ?></h4>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator active"></span>
                        <?php esc_html_e('Page Builder', 'smart-page-builder'); ?>
                    </div>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator active"></span>
                        <?php esc_html_e('Component System', 'smart-page-builder'); ?>
                    </div>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator active"></span>
                        <?php esc_html_e('Template Engine', 'smart-page-builder'); ?>
                    </div>
                </div>
                
                <div class="spb-status-card">
                    <h4><?php esc_html_e('AI Features', 'smart-page-builder'); ?></h4>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator <?php echo defined('SPB_V3_AI_CONTENT_GENERATION') && SPB_V3_AI_CONTENT_GENERATION ? 'active' : 'inactive'; ?>"></span>
                        <?php esc_html_e('AI Content Generation', 'smart-page-builder'); ?>
                    </div>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator <?php echo defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION ? 'active' : 'inactive'; ?>"></span>
                        <?php esc_html_e('Search Generation', 'smart-page-builder'); ?>
                    </div>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator <?php echo defined('SPB_V3_QUALITY_ASSESSMENT') && SPB_V3_QUALITY_ASSESSMENT ? 'active' : 'inactive'; ?>"></span>
                        <?php esc_html_e('Quality Assessment', 'smart-page-builder'); ?>
                    </div>
                </div>
                
                <div class="spb-status-card">
                    <h4><?php esc_html_e('Personalization', 'smart-page-builder'); ?></h4>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator <?php echo defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION ? 'active' : 'inactive'; ?>"></span>
                        <?php esc_html_e('Interest Vectors', 'smart-page-builder'); ?>
                    </div>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator <?php echo defined('SPB_V3_SIGNAL_COLLECTION') && SPB_V3_SIGNAL_COLLECTION ? 'active' : 'inactive'; ?>"></span>
                        <?php esc_html_e('Signal Collection', 'smart-page-builder'); ?>
                    </div>
                    <div class="spb-status-item">
                        <span class="spb-status-indicator <?php echo defined('SPB_V3_COMPONENT_PERSONALIZATION') && SPB_V3_COMPONENT_PERSONALIZATION ? 'active' : 'inactive'; ?>"></span>
                        <?php esc_html_e('Component Personalization', 'smart-page-builder'); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="spb-settings-section">
            <h2><?php esc_html_e('Diagnostics', 'smart-page-builder'); ?></h2>
            <p><?php esc_html_e('Export system information for support purposes.', 'smart-page-builder'); ?></p>
            <button type="button" class="button button-secondary" id="spb-export-system-info">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export System Info', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.spb-system-settings {
    max-width: 100%;
}

.spb-page-header {
    margin-bottom: 20px;
}

.spb-page-header h1 {
    margin-bottom: 5px;
}

.spb-page-description {
    color: #666;
    font-size: 14px;
    margin: 0;
}

.spb-nav-tabs {
    margin-bottom: 20px;
}

.spb-nav-tabs .nav-tab {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.spb-tab-content {
    display: none;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.spb-tab-content.active {
    display: block;
}

.spb-settings-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.spb-settings-section:last-child {
    border-bottom: none;
}

.spb-settings-section h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-size: 18px;
    color: #333;
}

.spb-settings-section h3 {
    margin-top: 25px;
    margin-bottom: 15px;
    font-size: 16px;
    color: #333;
}

.spb-settings-form {
    max-width: 800px;
}

.spb-system-info {
    max-width: 600px;
    margin-bottom: 30px;
}

.spb-system-info td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.spb-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.spb-status-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
}

.spb-status-card h4 {
    margin: 0 0 15px 0;
    color: #333;
    border-bottom: 1px solid #ddd;
    padding-bottom: 10px;
    font-size: 14px;
}

.spb-status-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    font-size: 13px;
}

.spb-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 10px;
    display: inline-block;
}

.spb-status-indicator.active {
    background-color: #27ae60;
}

.spb-status-indicator.inactive {
    background-color: #e74c3c;
}

.button .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

@media (max-width: 768px) {
    .spb-status-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.spb-nav-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        if (!tab) return;
        
        // Update tab appearance
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show/hide content
        $('.spb-tab-content').removeClass('active').hide();
        $('#' + tab + '-tab').addClass('active').fadeIn(200);
    });
    
    // Clear cache
    $('#spb-clear-cache').on('click', function() {
        var $button = $(this);
        if (confirm('<?php echo esc_js(__('Are you sure you want to clear all cache?', 'smart-page-builder')); ?>')) {
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Clearing...', 'smart-page-builder')); ?>');
            
            $.post(ajaxurl, {
                action: 'spb_clear_cache',
                nonce: '<?php echo wp_create_nonce('spb_clear_cache'); ?>'
            })
            .done(function(response) {
                if (response && response.success) {
                    alert('<?php echo esc_js(__('Cache cleared successfully.', 'smart-page-builder')); ?>');
                } else {
                    alert('<?php echo esc_js(__('Error clearing cache:', 'smart-page-builder')); ?> ' + (response.data || '<?php echo esc_js(__('Unknown error', 'smart-page-builder')); ?>'));
                }
            })
            .fail(function() {
                alert('<?php echo esc_js(__('Network error while clearing cache.', 'smart-page-builder')); ?>');
            })
            .always(function() {
                $button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php echo esc_js(__('Clear All Cache', 'smart-page-builder')); ?>');
            });
        }
    });
    
    // Export system info
    $('#spb-export-system-info').on('click', function() {
        try {
            var systemInfo = {
                plugin_version: '<?php echo esc_js($system_info['plugin_version']); ?>',
                wp_version: '<?php echo esc_js($system_info['wp_version']); ?>',
                php_version: '<?php echo esc_js($system_info['php_version']); ?>',
                memory_limit: '<?php echo esc_js($system_info['memory_limit']); ?>',
                max_execution_time: '<?php echo esc_js($system_info['max_execution_time']); ?>',
                upload_max_filesize: '<?php echo esc_js($system_info['upload_max_filesize']); ?>',
                export_date: new Date().toISOString()
            };
            
            var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(systemInfo, null, 2));
            var downloadAnchorNode = document.createElement('a');
            downloadAnchorNode.setAttribute("href", dataStr);
            downloadAnchorNode.setAttribute("download", "smart-page-builder-system-info.json");
            document.body.appendChild(downloadAnchorNode);
            downloadAnchorNode.click();
            downloadAnchorNode.remove();
        } catch (error) {
            alert('<?php echo esc_js(__('Error exporting system information:', 'smart-page-builder')); ?> ' + error.message);
        }
    });
    
    // Add spinning animation for loading states
    $('<style>.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>').appendTo('head');
});
</script>
