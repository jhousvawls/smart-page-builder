<?php
/**
 * Provide a admin area view for the plugin dashboard
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$pending_drafts = get_posts(array(
    'post_type' => 'spb_dynamic_page',
    'post_status' => 'draft',
    'meta_key' => '_spb_status',
    'meta_value' => 'pending_approval',
    'numberposts' => -1
));

$published_content = get_posts(array(
    'post_type' => 'spb_dynamic_page',
    'post_status' => 'publish',
    'numberposts' => -1
));

$ai_usage_count = get_option('spb_ai_usage_count', 0);
$ai_enhancement_enabled = get_option('spb_ai_enhancement_enabled', false);

// Get recent activity
$recent_drafts = get_posts(array(
    'post_type' => 'spb_dynamic_page',
    'post_status' => 'draft',
    'meta_key' => '_spb_status',
    'meta_value' => 'pending_approval',
    'numberposts' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="spb-dashboard">
        <!-- Statistics Cards -->
        <div class="spb-stats-grid">
            <div class="spb-stat-card">
                <div class="spb-stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="spb-stat-content">
                    <h3><?php echo count($pending_drafts); ?></h3>
                    <p><?php _e('Pending Approval', 'smart-page-builder'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=smart-page-builder-approval'); ?>" class="spb-stat-link">
                        <?php _e('Review Queue', 'smart-page-builder'); ?>
                    </a>
                </div>
            </div>
            
            <div class="spb-stat-card">
                <div class="spb-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="spb-stat-content">
                    <h3><?php echo count($published_content); ?></h3>
                    <p><?php _e('Published Content', 'smart-page-builder'); ?></p>
                    <a href="<?php echo admin_url('edit.php?post_type=spb_dynamic_page'); ?>" class="spb-stat-link">
                        <?php _e('View All', 'smart-page-builder'); ?>
                    </a>
                </div>
            </div>
            
            <div class="spb-stat-card">
                <div class="spb-stat-icon">
                    <span class="dashicons dashicons-admin-generic"></span>
                </div>
                <div class="spb-stat-content">
                    <h3><?php echo $ai_usage_count; ?></h3>
                    <p><?php _e('AI Enhancements', 'smart-page-builder'); ?></p>
                    <span class="spb-stat-status <?php echo $ai_enhancement_enabled ? 'enabled' : 'disabled'; ?>">
                        <?php echo $ai_enhancement_enabled ? __('Enabled', 'smart-page-builder') : __('Disabled', 'smart-page-builder'); ?>
                    </span>
                </div>
            </div>
            
            <div class="spb-stat-card">
                <div class="spb-stat-icon">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <div class="spb-stat-content">
                    <h3><?php _e('Active', 'smart-page-builder'); ?></h3>
                    <p><?php _e('Search Monitoring', 'smart-page-builder'); ?></p>
                    <span class="spb-stat-status enabled">
                        <?php _e('Running', 'smart-page-builder'); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="spb-main-content">
            <!-- Recent Activity -->
            <div class="spb-section">
                <h2><?php _e('Recent Activity', 'smart-page-builder'); ?></h2>
                
                <?php if (!empty($recent_drafts)): ?>
                    <div class="spb-activity-list">
                        <?php foreach ($recent_drafts as $draft): 
                            $search_term = get_post_meta($draft->ID, '_spb_search_term', true);
                            $confidence = get_post_meta($draft->ID, '_spb_confidence', true);
                            $content_type = get_post_meta($draft->ID, '_spb_content_type', true);
                            $generated_date = get_post_meta($draft->ID, '_spb_generated_date', true);
                        ?>
                            <div class="spb-activity-item">
                                <div class="spb-activity-icon">
                                    <span class="dashicons dashicons-admin-page"></span>
                                </div>
                                <div class="spb-activity-content">
                                    <h4><?php echo esc_html($draft->post_title); ?></h4>
                                    <p class="spb-activity-meta">
                                        <?php printf(
                                            __('Generated %s ago for search: "%s"', 'smart-page-builder'),
                                            human_time_diff(strtotime($generated_date), current_time('timestamp')),
                                            esc_html($search_term)
                                        ); ?>
                                    </p>
                                    <div class="spb-activity-badges">
                                        <span class="spb-badge spb-badge-<?php echo esc_attr($content_type); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $content_type))); ?>
                                        </span>
                                        <span class="spb-confidence-badge">
                                            <?php echo number_format($confidence * 100, 1); ?>% confidence
                                        </span>
                                    </div>
                                </div>
                                <div class="spb-activity-actions">
                                    <a href="<?php echo admin_url('admin.php?page=smart-page-builder-approval'); ?>" class="button button-primary button-small">
                                        <?php _e('Review', 'smart-page-builder'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <p class="spb-view-all">
                        <a href="<?php echo admin_url('admin.php?page=smart-page-builder-approval'); ?>" class="button">
                            <?php _e('View All Pending Content', 'smart-page-builder'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <div class="spb-empty-state">
                        <span class="dashicons dashicons-admin-page"></span>
                        <h3><?php _e('No Recent Activity', 'smart-page-builder'); ?></h3>
                        <p><?php _e('Content will appear here when the system generates new drafts based on search queries.', 'smart-page-builder'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Actions -->
            <div class="spb-section">
                <h2><?php _e('Quick Actions', 'smart-page-builder'); ?></h2>
                
                <div class="spb-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=smart-page-builder-approval'); ?>" class="spb-action-card">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h3><?php _e('Review Content', 'smart-page-builder'); ?></h3>
                        <p><?php _e('Approve or reject generated content drafts', 'smart-page-builder'); ?></p>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=smart-page-builder-settings'); ?>" class="spb-action-card">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h3><?php _e('Configure Settings', 'smart-page-builder'); ?></h3>
                        <p><?php _e('Adjust confidence thresholds and AI settings', 'smart-page-builder'); ?></p>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=smart-page-builder-analytics'); ?>" class="spb-action-card">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h3><?php _e('View Analytics', 'smart-page-builder'); ?></h3>
                        <p><?php _e('Monitor performance and content gaps', 'smart-page-builder'); ?></p>
                    </a>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="spb-section">
                <h2><?php _e('System Information', 'smart-page-builder'); ?></h2>
                
                <div class="spb-system-info">
                    <div class="spb-info-item">
                        <strong><?php _e('Plugin Version:', 'smart-page-builder'); ?></strong>
                        <span>1.0.0</span>
                    </div>
                    <div class="spb-info-item">
                        <strong><?php _e('WordPress Version:', 'smart-page-builder'); ?></strong>
                        <span><?php echo get_bloginfo('version'); ?></span>
                    </div>
                    <div class="spb-info-item">
                        <strong><?php _e('PHP Version:', 'smart-page-builder'); ?></strong>
                        <span><?php echo PHP_VERSION; ?></span>
                    </div>
                    <div class="spb-info-item">
                        <strong><?php _e('Content Assembly:', 'smart-page-builder'); ?></strong>
                        <span class="spb-status-enabled"><?php _e('Active', 'smart-page-builder'); ?></span>
                    </div>
                    <div class="spb-info-item">
                        <strong><?php _e('AI Enhancement:', 'smart-page-builder'); ?></strong>
                        <span class="spb-status-<?php echo $ai_enhancement_enabled ? 'enabled' : 'disabled'; ?>">
                            <?php echo $ai_enhancement_enabled ? __('Enabled', 'smart-page-builder') : __('Disabled', 'smart-page-builder'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.spb-dashboard {
    max-width: 1200px;
}

.spb-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spb-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.spb-stat-icon {
    margin-right: 15px;
}

.spb-stat-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #0073aa;
}

.spb-stat-content h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: bold;
    color: #23282d;
}

.spb-stat-content p {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
}

.spb-stat-link {
    color: #0073aa;
    text-decoration: none;
    font-size: 13px;
}

.spb-stat-status {
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
}

.spb-stat-status.enabled {
    background: #d4edda;
    color: #155724;
}

.spb-stat-status.disabled {
    background: #f8d7da;
    color: #721c24;
}

.spb-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.spb-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    color: #23282d;
}

.spb-activity-list {
    space-y: 15px;
}

.spb-activity-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #eee;
    border-radius: 6px;
    margin-bottom: 10px;
}

.spb-activity-icon {
    margin-right: 15px;
}

.spb-activity-icon .dashicons {
    font-size: 20px;
    color: #666;
}

.spb-activity-content {
    flex: 1;
}

.spb-activity-content h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.spb-activity-meta {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 13px;
}

.spb-activity-badges {
    display: flex;
    gap: 8px;
}

.spb-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.spb-badge-how_to {
    background: #e3f2fd;
    color: #1565c0;
}

.spb-badge-tool_recommendation {
    background: #f3e5f5;
    color: #7b1fa2;
}

.spb-badge-safety_tips {
    background: #fff8e1;
    color: #f57f17;
}

.spb-badge-troubleshooting {
    background: #ffebee;
    color: #c62828;
}

.spb-badge-default {
    background: #f5f5f5;
    color: #616161;
}

.spb-confidence-badge {
    padding: 2px 6px;
    background: #e8f5e8;
    color: #2e7d32;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.spb-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.spb-empty-state .dashicons {
    font-size: 48px;
    color: #ddd;
    margin-bottom: 15px;
}

.spb-empty-state h3 {
    margin-bottom: 10px;
    color: #666;
}

.spb-quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.spb-action-card {
    display: block;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.spb-action-card:hover {
    border-color: #0073aa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    color: inherit;
    text-decoration: none;
}

.spb-action-card .dashicons {
    font-size: 24px;
    color: #0073aa;
    margin-bottom: 10px;
}

.spb-action-card h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.spb-action-card p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.spb-system-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.spb-info-item {
    display: flex;
    justify-content: space-between;
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.spb-status-enabled {
    color: #46b450;
    font-weight: bold;
}

.spb-status-disabled {
    color: #dc3232;
    font-weight: bold;
}
</style>
