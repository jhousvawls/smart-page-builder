<?php
/**
 * Admin Settings Interface for Smart Page Builder
 *
 * Provides the main settings interface for the admin area.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if (isset($_POST['spb_save_settings']) && wp_verify_nonce($_POST['spb_settings_nonce'], 'spb_save_settings')) {
    // Save general settings
    $general_settings = array(
        'enable_analytics' => isset($_POST['enable_analytics']) ? 1 : 0,
        'enable_caching' => isset($_POST['enable_caching']) ? 1 : 0,
        'cache_duration' => intval($_POST['cache_duration']),
        'enable_compression' => isset($_POST['enable_compression']) ? 1 : 0,
        'debug_mode' => isset($_POST['debug_mode']) ? 1 : 0,
        'max_components_per_page' => intval($_POST['max_components_per_page']),
        'auto_save_interval' => intval($_POST['auto_save_interval'])
    );
    
    update_option('spb_general_options', $general_settings);
    echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved successfully.', 'smart-page-builder') . '</p></div>';
}

// Get current settings
$general_settings = get_option('spb_general_options', array(
    'enable_analytics' => 1,
    'enable_caching' => 1,
    'cache_duration' => 3600,
    'enable_compression' => 1,
    'debug_mode' => 0,
    'max_components_per_page' => 50,
    'auto_save_interval' => 30
));

// Get system information
$system_info = array(
    'php_version' => PHP_VERSION,
    'wp_version' => get_bloginfo('version'),
    'plugin_version' => defined('SPB_VERSION') ? SPB_VERSION : '3.1.0',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
);
?>

<div class="wrap spb-settings-interface">
    <div class="spb-page-header">
        <h1><?php esc_html_e('Smart Page Builder Settings', 'smart-page-builder'); ?></h1>
        <p class="spb-page-description"><?php esc_html_e('General plugin configuration', 'smart-page-builder'); ?></p>
    </div>
    
    <!-- Settings Tabs -->
    <nav class="nav-tab-wrapper spb-nav-tabs">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general">
            <?php esc_html_e('General', 'smart-page-builder'); ?>
        </a>
        <a href="#performance" class="nav-tab" data-tab="performance">
            <?php esc_html_e('Performance', 'smart-page-builder'); ?>
        </a>
        <a href="#system" class="nav-tab" data-tab="system">
            <?php esc_html_e('System Info', 'smart-page-builder'); ?>
        </a>
    </nav>

    <!-- General Settings Tab -->
    <div id="general-tab" class="spb-tab-content active">
        <form method="post" action="" class="spb-settings-form">
            <?php wp_nonce_field('spb_save_settings', 'spb_settings_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="enable_analytics"><?php esc_html_e('Enable Analytics', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_analytics" name="enable_analytics" value="1" <?php checked($general_settings['enable_analytics'], 1); ?> />
                            <p class="description"><?php esc_html_e('Enable analytics tracking for component performance and user interactions.', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_caching"><?php esc_html_e('Enable Caching', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_caching" name="enable_caching" value="1" <?php checked($general_settings['enable_caching'], 1); ?> />
                            <p class="description"><?php esc_html_e('Enable caching to improve page load times.', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cache_duration"><?php esc_html_e('Cache Duration (seconds)', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" value="<?php echo esc_attr($general_settings['cache_duration']); ?>" min="300" max="86400" />
                            <p class="description"><?php esc_html_e('How long to cache generated content (300-86400 seconds).', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="max_components_per_page"><?php esc_html_e('Max Components Per Page', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_components_per_page" name="max_components_per_page" value="<?php echo esc_attr($general_settings['max_components_per_page']); ?>" min="10" max="200" />
                            <p class="description"><?php esc_html_e('Maximum number of components allowed per page.', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="auto_save_interval"><?php esc_html_e('Auto-save Interval (seconds)', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="auto_save_interval" name="auto_save_interval" value="<?php echo esc_attr($general_settings['auto_save_interval']); ?>" min="10" max="300" />
                            <p class="description"><?php esc_html_e('How often to auto-save changes (10-300 seconds).', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php submit_button(__('Save General Settings', 'smart-page-builder'), 'primary', 'spb_save_settings'); ?>
        </form>
    </div>

    <!-- Performance Settings Tab -->
    <div id="performance-tab" class="spb-tab-content">
        <form method="post" action="" class="spb-settings-form">
            <?php wp_nonce_field('spb_save_settings', 'spb_settings_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="enable_compression"><?php esc_html_e('Enable Compression', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="enable_compression" name="enable_compression" value="1" <?php checked($general_settings['enable_compression'], 1); ?> />
                            <p class="description"><?php esc_html_e('Enable GZIP compression for better performance.', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="debug_mode"><?php esc_html_e('Debug Mode', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="debug_mode" name="debug_mode" value="1" <?php checked($general_settings['debug_mode'], 1); ?> />
                            <p class="description"><?php esc_html_e('Enable debug mode for troubleshooting (not recommended for production).', 'smart-page-builder'); ?></p>
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
                                <?php esc_html_e('Clear All Cache', 'smart-page-builder'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Clear all cached data to force regeneration.', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php submit_button(__('Save Performance Settings', 'smart-page-builder'), 'primary', 'spb_save_settings'); ?>
        </form>
    </div>

    <!-- System Information Tab -->
    <div id="system-tab" class="spb-tab-content">
        <h3><?php esc_html_e('System Information', 'smart-page-builder'); ?></h3>
        
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
        
        <h3><?php esc_html_e('Plugin Status', 'smart-page-builder'); ?></h3>
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
                <div class="spb_status-item">
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
        
        <h3><?php esc_html_e('Export System Information', 'smart-page-builder'); ?></h3>
        <p><?php esc_html_e('Export system information for support purposes.', 'smart-page-builder'); ?></p>
        <button type="button" class="button button-secondary" id="spb-export-system-info">
            <?php esc_html_e('Export System Info', 'smart-page-builder'); ?>
        </button>
    </div>
</div>

<style>
.spb-settings-interface {
    max-width: 100%;
}

.spb-nav-tabs {
    margin-bottom: 20px;
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

/* Ensure General tab is visible by default */
#general-tab {
    display: block;
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
}

.spb-status-item {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
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

@media (max-width: 768px) {
    .spb-status-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Ensure General tab is active by default
    $('.spb-tab-content').removeClass('active');
    $('#general-tab').addClass('active');
    $('.nav-tab').removeClass('nav-tab-active');
    $('.nav-tab[data-tab="general"]').addClass('nav-tab-active');
    
    // Tab switching with improved error handling
    $('.spb-nav-tabs .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var tab = $(this).data('tab');
        if (!tab) return;
        
        // Update tab appearance
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show/hide content with animation
        $('.spb-tab-content').removeClass('active').hide();
        $('#' + tab + '-tab').addClass('active').fadeIn(200);
    });
    
    // Clear cache with better error handling
    $('#spb-clear-cache').on('click', function() {
        var $button = $(this);
        if (confirm('Are you sure you want to clear all cache?')) {
            $button.prop('disabled', true).text('Clearing...');
            
            $.post(ajaxurl, {
                action: 'spb_clear_cache',
                nonce: '<?php echo wp_create_nonce('spb_clear_cache'); ?>'
            })
            .done(function(response) {
                if (response && response.success) {
                    alert('Cache cleared successfully.');
                } else {
                    alert('Error clearing cache: ' + (response.data || 'Unknown error'));
                }
            })
            .fail(function() {
                alert('Network error while clearing cache.');
            })
            .always(function() {
                $button.prop('disabled', false).text('Clear All Cache');
            });
        }
    });
    
    // Export system info with error handling
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
            alert('Error exporting system information: ' + error.message);
        }
    });
    
    // Form validation
    $('.spb-settings-form').on('submit', function() {
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"]');
        
        $submitButton.prop('disabled', true);
        
        // Re-enable after a delay to prevent double submission
        setTimeout(function() {
            $submitButton.prop('disabled', false);
        }, 2000);
    });
});
</script>
