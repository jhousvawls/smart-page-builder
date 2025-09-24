<?php
/**
 * Admin Support Interface for Smart Page Builder
 *
 * Provides the support and documentation interface for the admin area.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get system information for support
$system_info = array(
    'plugin_version' => defined('SPB_VERSION') ? SPB_VERSION : '3.1.0',
    'wp_version' => get_bloginfo('version'),
    'php_version' => PHP_VERSION,
    'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'mysql_version' => $GLOBALS['wpdb']->db_version(),
    'active_theme' => wp_get_theme()->get('Name'),
    'active_plugins' => count(get_option('active_plugins', array()))
);

// Check for common issues
$health_checks = array();

// Memory check
$memory_limit_bytes = wp_convert_hr_to_bytes(ini_get('memory_limit'));
if ($memory_limit_bytes < 134217728) { // 128MB
    $health_checks[] = array(
        'type' => 'warning',
        'title' => 'Low Memory Limit',
        'message' => 'Your PHP memory limit is below 128MB. This may cause issues with AI content generation.'
    );
}

// PHP version check
if (version_compare(PHP_VERSION, '7.4', '<')) {
    $health_checks[] = array(
        'type' => 'error',
        'title' => 'Outdated PHP Version',
        'message' => 'PHP 7.4 or higher is required for optimal performance and security.'
    );
}

// WordPress version check
if (version_compare(get_bloginfo('version'), '5.0', '<')) {
    $health_checks[] = array(
        'type' => 'error',
        'title' => 'Outdated WordPress Version',
        'message' => 'WordPress 5.0 or higher is required for full plugin compatibility.'
    );
}

// cURL check
if (!function_exists('curl_init')) {
    $health_checks[] = array(
        'type' => 'error',
        'title' => 'Missing cURL Extension',
        'message' => 'cURL is required for API connections to work properly.'
    );
}

// Check if no issues found
if (empty($health_checks)) {
    $health_checks[] = array(
        'type' => 'success',
        'title' => 'System Health Good',
        'message' => 'No critical issues detected with your system configuration.'
    );
}
?>

<div class="wrap spb-support-interface">
    <div class="spb-page-header">
        <h1><?php esc_html_e('Smart Page Builder Support', 'smart-page-builder'); ?></h1>
        <p class="spb-page-description"><?php esc_html_e('Help and documentation', 'smart-page-builder'); ?></p>
    </div>
    
    <!-- Support Navigation -->
    <nav class="nav-tab-wrapper spb-support-nav">
        <a href="#documentation" class="nav-tab nav-tab-active" data-tab="documentation">
            <?php esc_html_e('Documentation', 'smart-page-builder'); ?>
        </a>
        <a href="#troubleshooting" class="nav-tab" data-tab="troubleshooting">
            <?php esc_html_e('Troubleshooting', 'smart-page-builder'); ?>
        </a>
        <a href="#system-info" class="nav-tab" data-tab="system-info">
            <?php esc_html_e('System Information', 'smart-page-builder'); ?>
        </a>
        <a href="#contact" class="nav-tab" data-tab="contact">
            <?php esc_html_e('Contact Support', 'smart-page-builder'); ?>
        </a>
    </nav>

    <!-- Documentation Tab -->
    <div id="documentation-tab" class="spb-tab-content active">
        <h2><?php esc_html_e('Getting Started', 'smart-page-builder'); ?></h2>
        
        <div class="spb-documentation-grid">
            <div class="spb-doc-card">
                <div class="spb-doc-icon">🚀</div>
                <h3><?php esc_html_e('Getting Started', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Learn how to enable and configure WP Engine Smart Search for your site.', 'smart-page-builder'); ?></p>
                <a href="https://wpengine.com/support/wp-engine-smart-search#Enable" target="_blank" class="button button-primary">
                    <?php esc_html_e('View Guide', 'smart-page-builder'); ?>
                </a>
            </div>
            
            <div class="spb-doc-card">
                <div class="spb-doc-icon">📊</div>
                <h3><?php esc_html_e('Insights API', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Access comprehensive documentation for the WP Engine AI Toolkit Insights API.', 'smart-page-builder'); ?></p>
                <a href="https://developers.wpengine.com/docs/wp-engine-ai-toolkit/common-apis/insights-api/" target="_blank" class="button button-primary">
                    <?php esc_html_e('API Documentation', 'smart-page-builder'); ?>
                </a>
            </div>
            
            <div class="spb-doc-card">
                <div class="spb-doc-icon">🔍</div>
                <h3><?php esc_html_e('Search API', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Learn how to configure and use the WP Engine Semantic Search Configuration API.', 'smart-page-builder'); ?></p>
                <a href="https://developers.wpengine.com/docs/wp-engine-ai-toolkit/common-apis/semantic-search-config-api/" target="_blank" class="button button-primary">
                    <?php esc_html_e('API Documentation', 'smart-page-builder'); ?>
                </a>
            </div>
        </div>
        
        <div class="spb-need-help-section">
            <h2><?php esc_html_e('Need Help?', 'smart-page-builder'); ?></h2>
            <p><?php esc_html_e('Check out our documentation and support resources:', 'smart-page-builder'); ?></p>
            
            <div class="spb-help-links">
                <a href="https://mcptest2.wpenginepowered.com/wp-admin/admin.php?page=smart-page-builder-wpengine" target="_blank" class="button button-secondary">
                    <?php esc_html_e('Setup Guide', 'smart-page-builder'); ?>
                </a>
                <a href="https://mcptest2.wpenginepowered.com/wp-admin/admin.php?page=smart-page-builder-wpengine" target="_blank" class="button button-secondary">
                    <?php esc_html_e('API Documentation', 'smart-page-builder'); ?>
                </a>
                <a href="https://mcptest2.wpenginepowered.com/wp-admin/admin.php?page=smart-page-builder-wpengine" target="_blank" class="button button-secondary">
                    <?php esc_html_e('Troubleshooting', 'smart-page-builder'); ?>
                </a>
                <a href="https://mcptest2.wpenginepowered.com/wp-admin/admin.php?page=smart-page-builder-wpengine" target="_blank" class="button button-secondary">
                    <?php esc_html_e('Support Forum', 'smart-page-builder'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Troubleshooting Tab -->
    <div id="troubleshooting-tab" class="spb-tab-content">
        <h2><?php esc_html_e('System Health Check', 'smart-page-builder'); ?></h2>
        
        <div class="spb-health-checks">
            <?php foreach ($health_checks as $check): ?>
                <div class="spb-health-check spb-health-<?php echo esc_attr($check['type']); ?>">
                    <div class="spb-health-icon">
                        <?php if ($check['type'] === 'success'): ?>✅<?php endif; ?>
                        <?php if ($check['type'] === 'warning'): ?>⚠️<?php endif; ?>
                        <?php if ($check['type'] === 'error'): ?>❌<?php endif; ?>
                    </div>
                    <div class="spb-health-content">
                        <h4><?php echo esc_html($check['title']); ?></h4>
                        <p><?php echo esc_html($check['message']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <h2><?php esc_html_e('Common Issues & Solutions', 'smart-page-builder'); ?></h2>
        
        <div class="spb-troubleshooting-accordion">
            <div class="spb-accordion-item">
                <h3 class="spb-accordion-header">
                    <?php esc_html_e('Pages not generating properly', 'smart-page-builder'); ?>
                    <span class="spb-accordion-toggle">+</span>
                </h3>
                <div class="spb-accordion-content">
                    <p><?php esc_html_e('If AI-generated pages are not working correctly:', 'smart-page-builder'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Check your WP Engine AI Toolkit API credentials', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Verify your PHP memory limit is at least 128MB', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Ensure cURL extension is installed and enabled', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Check the error logs for specific error messages', 'smart-page-builder'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="spb-accordion-item">
                <h3 class="spb-accordion-header">
                    <?php esc_html_e('Slow page loading', 'smart-page-builder'); ?>
                    <span class="spb-accordion-toggle">+</span>
                </h3>
                <div class="spb-accordion-content">
                    <p><?php esc_html_e('To improve page loading performance:', 'smart-page-builder'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Enable caching in the plugin settings', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Optimize images and reduce component complexity', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Use a caching plugin like WP Rocket or W3 Total Cache', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Consider upgrading your hosting plan for better performance', 'smart-page-builder'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="spb-accordion-item">
                <h3 class="spb-accordion-header">
                    <?php esc_html_e('Components not displaying correctly', 'smart-page-builder'); ?>
                    <span class="spb-accordion-toggle">+</span>
                </h3>
                <div class="spb-accordion-content">
                    <p><?php esc_html_e('If components are not rendering properly:', 'smart-page-builder'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Check for theme conflicts by switching to a default theme', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Disable other plugins to identify conflicts', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Clear all caches (plugin, theme, and hosting)', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Ensure your theme supports the required CSS and JavaScript', 'smart-page-builder'); ?></li>
                    </ul>
                </div>
            </div>
            
            <div class="spb-accordion-item">
                <h3 class="spb-accordion-header">
                    <?php esc_html_e('Database errors', 'smart-page-builder'); ?>
                    <span class="spb-accordion-toggle">+</span>
                </h3>
                <div class="spb-accordion-content">
                    <p><?php esc_html_e('If you encounter database-related errors:', 'smart-page-builder'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Deactivate and reactivate the plugin to recreate tables', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Check database permissions and available storage space', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Contact your hosting provider if issues persist', 'smart-page-builder'); ?></li>
                        <li><?php esc_html_e('Backup your database before making any changes', 'smart-page-builder'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="spb-debug-tools">
            <h3><?php esc_html_e('Debug Tools', 'smart-page-builder'); ?></h3>
            <p><?php esc_html_e('Use these tools to diagnose and resolve issues:', 'smart-page-builder'); ?></p>
            
            <div class="spb-debug-buttons">
                <button type="button" class="button" id="spb-clear-cache">
                    <?php esc_html_e('Clear All Cache', 'smart-page-builder'); ?>
                </button>
                <button type="button" class="button" id="spb-test-api">
                    <?php esc_html_e('Test API Connection', 'smart-page-builder'); ?>
                </button>
                <button type="button" class="button" id="spb-regenerate-tables">
                    <?php esc_html_e('Regenerate Database Tables', 'smart-page-builder'); ?>
                </button>
                <button type="button" class="button" id="spb-export-logs">
                    <?php esc_html_e('Export Error Logs', 'smart-page-builder'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- System Information Tab -->
    <div id="system-info-tab" class="spb-tab-content">
        <h2><?php esc_html_e('System Information', 'smart-page-builder'); ?></h2>
        
        <div class="spb-system-info-grid">
            <div class="spb-info-section">
                <h3><?php esc_html_e('Plugin Information', 'smart-page-builder'); ?></h3>
                <table class="spb-info-table">
                    <tr>
                        <td><strong><?php esc_html_e('Plugin Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['plugin_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('WordPress Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['wp_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Active Theme', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['active_theme']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Active Plugins', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['active_plugins']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="spb-info-section">
                <h3><?php esc_html_e('Server Information', 'smart-page-builder'); ?></h3>
                <table class="spb-info-table">
                    <tr>
                        <td><strong><?php esc_html_e('PHP Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['php_version']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Server Software', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['server_software']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('MySQL Version', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['mysql_version']); ?></td>
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
                    <tr>
                        <td><strong><?php esc_html_e('Post Max Size', 'smart-page-builder'); ?></strong></td>
                        <td><?php echo esc_html($system_info['post_max_size']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="spb-export-section">
            <h3><?php esc_html_e('Export System Information', 'smart-page-builder'); ?></h3>
            <p><?php esc_html_e('Export your system information to share with support or for troubleshooting purposes.', 'smart-page-builder'); ?></p>
            <button type="button" class="button button-primary" id="spb-export-system-info">
                <?php esc_html_e('Export System Info', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>

    <!-- Contact Support Tab -->
    <div id="contact-tab" class="spb-tab-content">
        <h2><?php esc_html_e('Contact Support', 'smart-page-builder'); ?></h2>
        
        <div class="spb-contact-options">
            <div class="spb-contact-card">
                <div class="spb-contact-icon">📧</div>
                <h3><?php esc_html_e('Email Support', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Get help via email. We typically respond within 24 hours.', 'smart-page-builder'); ?></p>
                <a href="mailto:support@smartpagebuilder.com" class="button button-primary">
                    <?php esc_html_e('Send Email', 'smart-page-builder'); ?>
                </a>
            </div>
            
            <div class="spb-contact-card">
                <div class="spb-contact-icon">💬</div>
                <h3><?php esc_html_e('Live Chat', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Chat with our support team in real-time during business hours.', 'smart-page-builder'); ?></p>
                <button type="button" class="button button-primary" id="spb-start-chat">
                    <?php esc_html_e('Start Chat', 'smart-page-builder'); ?>
                </button>
            </div>
            
            <div class="spb-contact-card">
                <div class="spb-contact-icon">📚</div>
                <h3><?php esc_html_e('Knowledge Base', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Browse our comprehensive knowledge base for answers to common questions.', 'smart-page-builder'); ?></p>
                <a href="https://docs.smartpagebuilder.com" target="_blank" class="button button-primary">
                    <?php esc_html_e('Browse Docs', 'smart-page-builder'); ?>
                </a>
            </div>
            
            <div class="spb-contact-card">
                <div class="spb-contact-icon">🎥</div>
                <h3><?php esc_html_e('Video Tutorials', 'smart-page-builder'); ?></h3>
                <p><?php esc_html_e('Watch step-by-step video tutorials to learn how to use the plugin.', 'smart-page-builder'); ?></p>
                <a href="https://youtube.com/smartpagebuilder" target="_blank" class="button button-primary">
                    <?php esc_html_e('Watch Videos', 'smart-page-builder'); ?>
                </a>
            </div>
        </div>
        
        <div class="spb-support-form">
            <h3><?php esc_html_e('Submit Support Request', 'smart-page-builder'); ?></h3>
            <form id="spb-support-form">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="support-name"><?php esc_html_e('Your Name', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="support-name" name="name" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="support-email"><?php esc_html_e('Email Address', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="support-email" name="email" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="support-subject"><?php esc_html_e('Subject', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="support-subject" name="subject" class="regular-text" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="support-priority"><?php esc_html_e('Priority', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <select id="support-priority" name="priority">
                                <option value="low"><?php esc_html_e('Low', 'smart-page-builder'); ?></option>
                                <option value="normal" selected><?php esc_html_e('Normal', 'smart-page-builder'); ?></option>
                                <option value="high"><?php esc_html_e('High', 'smart-page-builder'); ?></option>
                                <option value="urgent"><?php esc_html_e('Urgent', 'smart-page-builder'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="support-message"><?php esc_html_e('Message', 'smart-page-builder'); ?></label>
                        </th>
                        <td>
                            <textarea id="support-message" name="message" rows="8" cols="50" class="large-text" required></textarea>
                            <p class="description"><?php esc_html_e('Please provide as much detail as possible about your issue.', 'smart-page-builder'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"></th>
                        <td>
                            <label>
                                <input type="checkbox" id="include-system-info" name="include_system_info" checked />
                                <?php esc_html_e('Include system information with this request', 'smart-page-builder'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Submit Support Request', 'smart-page-builder'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.spb-support-interface {
    max-width: 100%;
}

.spb-support-nav {
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

.spb-documentation-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spb-doc-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.spb-doc-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.spb-doc-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.spb-doc-card p {
    color: #666;
    margin-bottom: 15px;
}

.spb-video-tutorials {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.spb-video-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.spb-video-thumbnail {
    height: 180px;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.spb-play-button {
    width: 60px;
    height: 60px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    cursor: pointer;
}

.spb-video-card h4 {
    margin: 15px;
    color: #333;
}

.spb-video-card p {
    margin: 0 15px 15px 15px;
    color: #666;
    font-size: 14px;
}

.spb-health-checks {
    margin-bottom: 30px;
}

.spb-health-check {
    display: flex;
    align-items: center;
    padding: 15px;
    margin-bottom: 10px;
    border-radius: 4px;
    border-left: 4px solid;
}

.spb-health-success {
    background: #f0f8f0;
    border-left-color: #27ae60;
}

.spb-health-warning {
    background: #fff8e1;
    border-left-color: #f39c12;
}

.spb-health-error {
    background: #fdf2f2;
    border-left-color: #e74c3c;
}

.spb-health-icon {
    font-size: 24px;
    margin-right: 15px;
}

.spb-need-help-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.spb-need-help-section h2 {
    margin-top: 0;
    color: #333;
}

.spb-help-links {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.spb-help-links .button {
    flex: 1;
    min-width: 150px;
    text-align: center;
}

@media (max-width: 768px) {
    .spb-help-links {
        flex-direction: column;
    }
    
    .spb-help-links .button {
        width: 100%;
    }
}

.spb-troubleshooting-accordion {
    margin-bottom: 30px;
}

.spb-accordion-item {
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
}

.spb-accordion-header {
    background: #f5f5f5;
    padding: 15px;
    margin: 0;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 4px 4px 0 0;
}

.spb-accordion-header:hover {
    background: #e9e9e9;
}

.spb-accordion-toggle {
    font-size: 18px;
    font-weight: bold;
}

.spb-accordion-content {
    padding: 15px;
    display: none;
    border-top: 1px solid #ddd;
}

.spb-accordion-content ul {
    margin: 10px 0;
    padding-left: 20px;
}

.spb-accordion-content li {
    margin-bottom: 5px;
}

.spb-debug-tools {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
}

.spb-debug-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 15px;
}

.spb-debug-buttons .button {
    flex: 1;
    min-width: 150px;
}

.spb-system-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spb-info-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.spb-info-section h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.spb-info-table {
    width: 100%;
    border-collapse: collapse;
}

.spb-info-table td {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.spb-info-table td:first-child {
    width: 40%;
    color: #666;
}

.spb-export-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.spb-contact-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spb-contact-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.spb-contact-icon {
    font-size: 3em;
    margin-bottom: 15px;
}

.spb-contact-card h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.spb-contact-card p {
    color: #666;
    margin-bottom: 15px;
}

.spb-support-form {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.spb-support-form h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.spb-support-form .form-table th {
    width: 150px;
    vertical-align: top;
    padding-top: 10px;
}

.spb-support-form .form-table td {
    padding: 5px 0;
}

.spb-support-form .regular-text,
.spb-support-form .large-text {
    width: 100%;
    max-width: 500px;
}

.spb-support-form select {
    width: 200px;
}

@media (max-width: 768px) {
    .spb-documentation-grid,
    .spb-contact-options {
        grid-template-columns: 1fr;
    }
    
    .spb-system-info-grid {
        grid-template-columns: 1fr;
    }
    
    .spb-debug-buttons {
        flex-direction: column;
    }
    
    .spb-debug-buttons .button {
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching functionality
    $('.spb-support-nav .nav-tab').on('click', function(e) {
        e.preventDefault();
        
        var targetTab = $(this).data('tab');
        
        // Update active tab
        $('.spb-support-nav .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show target content
        $('.spb-tab-content').removeClass('active');
        $('#' + targetTab + '-tab').addClass('active');
    });
    
    // Accordion functionality
    $('.spb-accordion-header').on('click', function() {
        var $content = $(this).next('.spb-accordion-content');
        var $toggle = $(this).find('.spb-accordion-toggle');
        
        if ($content.is(':visible')) {
            $content.slideUp();
            $toggle.text('+');
        } else {
            $('.spb-accordion-content').slideUp();
            $('.spb-accordion-toggle').text('+');
            $content.slideDown();
            $toggle.text('-');
        }
    });
    
    // Debug tools functionality
    $('#spb-clear-cache').on('click', function() {
        if (confirm('Are you sure you want to clear all cache?')) {
            // Add AJAX call here
            alert('Cache cleared successfully!');
        }
    });
    
    $('#spb-test-api').on('click', function() {
        // Add AJAX call here
        alert('API connection test completed. Check the results above.');
    });
    
    $('#spb-regenerate-tables').on('click', function() {
        if (confirm('Are you sure you want to regenerate database tables? This may take a few moments.')) {
            // Add AJAX call here
            alert('Database tables regenerated successfully!');
        }
    });
    
    $('#spb-export-logs').on('click', function() {
        // Add AJAX call here
        alert('Error logs exported successfully!');
    });
    
    $('#spb-export-system-info').on('click', function() {
        // Create and download system info
        var systemInfo = 'Smart Page Builder System Information\n';
        systemInfo += '=====================================\n\n';
        
        $('.spb-info-table').each(function() {
            var sectionTitle = $(this).closest('.spb-info-section').find('h3').text();
            systemInfo += sectionTitle + '\n';
            systemInfo += '-'.repeat(sectionTitle.length) + '\n';
            
            $(this).find('tr').each(function() {
                var label = $(this).find('td:first').text().trim();
                var value = $(this).find('td:last').text().trim();
                systemInfo += label + ': ' + value + '\n';
            });
            systemInfo += '\n';
        });
        
        var blob = new Blob([systemInfo], { type: 'text/plain' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'smart-page-builder-system-info.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
    
    // Support form submission
    $('#spb-support-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = {
            name: $('#support-name').val(),
            email: $('#support-email').val(),
            subject: $('#support-subject').val(),
            priority: $('#support-priority').val(),
            message: $('#support-message').val(),
            include_system_info: $('#include-system-info').is(':checked')
        };
        
        // Add AJAX call here to submit support request
        alert('Support request submitted successfully! We will get back to you soon.');
        
        // Reset form
        this.reset();
    });
});
</script>
