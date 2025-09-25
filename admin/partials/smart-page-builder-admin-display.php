<?php
/**
 * Main Admin Display Interface for Smart Page Builder
 *
 * Provides the main dashboard and overview interface for the admin area.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get plugin data
$plugin_data = get_plugin_data(SPB_PLUGIN_FILE);
$plugin_version = $plugin_data['Version'] ?? '3.1.6';

// Get system status
$system_status = array(
    'php_version' => PHP_VERSION,
    'wp_version' => get_bloginfo('version'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
);

// Get plugin statistics
$stats = array(
    'total_pages' => wp_count_posts('page')->publish ?? 0,
    'total_posts' => wp_count_posts('post')->publish ?? 0,
    'active_users' => count_users()['total_users'] ?? 0,
    'plugin_active_time' => get_option('spb_activation_time', time())
);

// Get recent activity from actual plugin operations
$recent_activity = array();

// Get recent AI-generated pages if available
global $wpdb;
$ai_pages_table = $wpdb->prefix . 'spb_generated_pages';
if ($wpdb->get_var("SHOW TABLES LIKE '$ai_pages_table'") == $ai_pages_table) {
    $recent_pages = $wpdb->get_results(
        "SELECT * FROM $ai_pages_table 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    
    foreach ($recent_pages as $page) {
        $recent_activity[] = array(
            'type' => 'page_generated',
            'title' => 'AI Page Generated',
            'description' => 'Page created for query: ' . esc_html($page->search_query),
            'time' => strtotime($page->created_at)
        );
    }
}

// Get recent content approvals if available
$approvals_table = $wpdb->prefix . 'spb_content_approvals';
if ($wpdb->get_var("SHOW TABLES LIKE '$approvals_table'") == $approvals_table) {
    $recent_approvals = $wpdb->get_results(
        "SELECT * FROM $approvals_table 
        WHERE reviewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        AND status = 'approved'
        ORDER BY reviewed_at DESC
         LIMIT 3"
    );
    
    foreach ($recent_approvals as $approval) {
        $recent_activity[] = array(
            'type' => 'content_approved',
            'title' => 'Content Approved',
            'description' => 'AI-generated content approved',
            'time' => strtotime($approval->reviewed_at)
        );
    }
}

// Sort by time if we have activities
if (!empty($recent_activity)) {
    usort($recent_activity, function($a, $b) {
        return $b['time'] - $a['time'];
    });
    $recent_activity = array_slice($recent_activity, 0, 5);
}
?>

<div class="wrap spb-main-dashboard">
    <div class="spb-dashboard-header">
        <h1 class="spb-dashboard-title">
            <?php esc_html_e('Smart Page Builder', 'smart-page-builder'); ?>
            <span class="spb-version">v<?php echo esc_html($plugin_version); ?></span>
        </h1>
        <p class="spb-dashboard-subtitle">
            <?php esc_html_e('AI-Powered Content Generation & Personalization Platform', 'smart-page-builder'); ?>
        </p>
    </div>

    <!-- Quick Stats Overview -->
    <div class="spb-stats-overview">
        <div class="spb-stats-header">
            <h2><?php esc_html_e('Dashboard Overview', 'smart-page-builder'); ?></h2>
            <div class="spb-stats-controls">
                <button type="button" class="spb-refresh-button spb-refresh-stats" title="<?php esc_attr_e('Refresh Statistics', 'smart-page-builder'); ?>">
                    🔄 <?php esc_html_e('Refresh', 'smart-page-builder'); ?>
                </button>
                <button type="button" class="spb-notifications-toggle" title="<?php esc_attr_e('View Notifications', 'smart-page-builder'); ?>">
                    🔔 <span class="spb-notifications-badge" style="display: none;">0</span>
                </button>
            </div>
        </div>
        <div class="spb-stats-grid">
            <div class="spb-stat-card" data-stat-type="total_pages">
                <div class="spb-stat-icon pages">📄</div>
                <div class="spb-stat-content">
                    <div class="spb-stat-number"><?php echo esc_html($stats['total_pages']); ?></div>
                    <div class="spb-stat-label"><?php esc_html_e('Total Pages', 'smart-page-builder'); ?></div>
                </div>
            </div>
            
            <div class="spb-stat-card" data-stat-type="total_posts">
                <div class="spb-stat-icon posts">📝</div>
                <div class="spb-stat-content">
                    <div class="spb-stat-number"><?php echo esc_html($stats['total_posts']); ?></div>
                    <div class="spb-stat-label"><?php esc_html_e('Total Posts', 'smart-page-builder'); ?></div>
                </div>
            </div>
            
            <div class="spb-stat-card" data-stat-type="active_users">
                <div class="spb-stat-icon users">👥</div>
                <div class="spb-stat-content">
                    <div class="spb-stat-number"><?php echo esc_html($stats['active_users']); ?></div>
                    <div class="spb-stat-label"><?php esc_html_e('Active Users', 'smart-page-builder'); ?></div>
                </div>
            </div>
            
            <div class="spb-stat-card" data-stat-type="ai_generated_pages">
                <div class="spb-stat-icon ai">🤖</div>
                <div class="spb-stat-content">
                    <div class="spb-stat-number"><?php echo esc_html(get_option('spb_ai_generated_count', 0)); ?></div>
                    <div class="spb-stat-label"><?php esc_html_e('AI Generated', 'smart-page-builder'); ?></div>
                </div>
            </div>
        </div>
        <div class="spb-last-updated"></div>
    </div>

    <!-- Main Content Grid -->
    <div class="spb-dashboard-grid">
        
        <!-- Quick Actions -->
        <div class="spb-dashboard-card spb-quick-actions">
            <h2><?php esc_html_e('Quick Actions', 'smart-page-builder'); ?></h2>
            <div class="spb-actions-grid">
                <a href="<?php echo esc_url(admin_url('admin.php?page=spb-personalization')); ?>" class="spb-action-button">
                    <span class="spb-action-icon">🎯</span>
                    <span class="spb-action-text"><?php esc_html_e('Personalization', 'smart-page-builder'); ?></span>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=spb-content-approval')); ?>" class="spb-action-button">
                    <span class="spb-action-icon">✅</span>
                    <span class="spb-action-text"><?php esc_html_e('Content Approval', 'smart-page-builder'); ?></span>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=spb-analytics')); ?>" class="spb-action-button">
                    <span class="spb-action-icon">📊</span>
                    <span class="spb-action-text"><?php esc_html_e('Analytics', 'smart-page-builder'); ?></span>
                </a>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=spb-wpengine')); ?>" class="spb-action-button">
                    <span class="spb-action-icon">🔧</span>
                    <span class="spb-action-text"><?php esc_html_e('WP Engine AI', 'smart-page-builder'); ?></span>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="spb-dashboard-card spb-system-status">
            <h2><?php esc_html_e('System Status', 'smart-page-builder'); ?></h2>
            <div class="spb-status-list">
                <div class="spb-status-item">
                    <span class="spb-status-label"><?php esc_html_e('PHP Version', 'smart-page-builder'); ?></span>
                    <span class="spb-status-value <?php echo version_compare($system_status['php_version'], '7.4', '>=') ? 'good' : 'warning'; ?>">
                        <?php echo esc_html($system_status['php_version']); ?>
                    </span>
                </div>
                
                <div class="spb-status-item">
                    <span class="spb-status-label"><?php esc_html_e('WordPress Version', 'smart-page-builder'); ?></span>
                    <span class="spb-status-value <?php echo version_compare($system_status['wp_version'], '5.0', '>=') ? 'good' : 'warning'; ?>">
                        <?php echo esc_html($system_status['wp_version']); ?>
                    </span>
                </div>
                
                <div class="spb-status-item">
                    <span class="spb-status-label"><?php esc_html_e('Memory Limit', 'smart-page-builder'); ?></span>
                    <span class="spb-status-value good">
                        <?php echo esc_html($system_status['memory_limit']); ?>
                    </span>
                </div>
                
                <div class="spb-status-item">
                    <span class="spb-status-label"><?php esc_html_e('Max Execution Time', 'smart-page-builder'); ?></span>
                    <span class="spb-status-value good">
                        <?php echo esc_html($system_status['max_execution_time']); ?>s
                    </span>
                </div>
            </div>
            
            <div class="spb-status-actions">
                <button type="button" class="button button-secondary" id="spb-run-diagnostics">
                    <?php esc_html_e('Run Diagnostics', 'smart-page-builder'); ?>
                </button>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="spb-dashboard-card spb-recent-activity">
            <h2><?php esc_html_e('Recent Activity', 'smart-page-builder'); ?></h2>
            <div class="spb-activity-list">
                <?php if (!empty($recent_activity)): ?>
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="spb-activity-item">
                            <div class="spb-activity-icon <?php echo esc_attr($activity['type']); ?>">
                                <?php
                                switch ($activity['type']) {
                                    case 'page_generated':
                                        echo '🤖';
                                        break;
                                    case 'personalization_updated':
                                        echo '🎯';
                                        break;
                                    case 'content_approved':
                                        echo '✅';
                                        break;
                                    default:
                                        echo '📝';
                                }
                                ?>
                            </div>
                            <div class="spb-activity-content">
                                <div class="spb-activity-title"><?php echo esc_html($activity['title']); ?></div>
                                <div class="spb-activity-description"><?php echo esc_html($activity['description']); ?></div>
                                <div class="spb-activity-time"><?php echo esc_html(human_time_diff($activity['time'], current_time('timestamp'))); ?> ago</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="spb-no-activity">
                        <p><?php esc_html_e('No recent activity to display.', 'smart-page-builder'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feature Status -->
        <div class="spb-dashboard-card spb-feature-status">
            <h2><?php esc_html_e('Feature Status', 'smart-page-builder'); ?></h2>
            <div class="spb-features-list">
                <?php
                // Check AI Content Generation status
                $ai_generation_active = (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) || 
                                       get_option('spb_ai_generation_enabled', false);
                ?>
                <div class="spb-feature-item">
                    <span class="spb-feature-name"><?php esc_html_e('AI Content Generation', 'smart-page-builder'); ?></span>
                    <span class="spb-feature-status <?php echo $ai_generation_active ? 'active' : 'inactive'; ?>">
                        <?php echo $ai_generation_active ? esc_html__('Active', 'smart-page-builder') : esc_html__('Inactive', 'smart-page-builder'); ?>
                    </span>
                </div>
                
                <?php
                // Check Search-Triggered Pages status
                $search_pages_active = (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) && 
                                      get_option('spb_enable_search_interception', false);
                ?>
                <div class="spb-feature-item">
                    <span class="spb-feature-name"><?php esc_html_e('Search-Triggered Pages', 'smart-page-builder'); ?></span>
                    <span class="spb-feature-status <?php echo $search_pages_active ? 'active' : 'inactive'; ?>">
                        <?php echo $search_pages_active ? esc_html__('Active', 'smart-page-builder') : esc_html__('Inactive', 'smart-page-builder'); ?>
                    </span>
                </div>
                
                <?php
                // Check Personalization Engine status
                $personalization_active = (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) && 
                                         get_option('spb_personalization_enabled', false);
                ?>
                <div class="spb-feature-item">
                    <span class="spb-feature-name"><?php esc_html_e('Personalization Engine', 'smart-page-builder'); ?></span>
                    <span class="spb-feature-status <?php echo $personalization_active ? 'active' : 'inactive'; ?>">
                        <?php echo $personalization_active ? esc_html__('Active', 'smart-page-builder') : esc_html__('Inactive', 'smart-page-builder'); ?>
                    </span>
                </div>
                
                <?php
                // Check Content Approval status
                $approval_active = (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) && 
                                  get_option('spb_content_approval_enabled', true);
                ?>
                <div class="spb-feature-item">
                    <span class="spb-feature-name"><?php esc_html_e('Content Approval', 'smart-page-builder'); ?></span>
                    <span class="spb-feature-status <?php echo $approval_active ? 'active' : 'inactive'; ?>">
                        <?php echo $approval_active ? esc_html__('Active', 'smart-page-builder') : esc_html__('Inactive', 'smart-page-builder'); ?>
                    </span>
                </div>
                
                <?php
                // Check WP Engine AI Integration status
                $wpengine_active = !empty(get_option('spb_wpengine_api_url', '')) && 
                                  !empty(get_option('spb_wpengine_access_token', '')) &&
                                  get_option('spb_wpengine_connection_status', 'not_configured') === 'connected';
                ?>
                <div class="spb-feature-item">
                    <span class="spb-feature-name"><?php esc_html_e('WP Engine AI Integration', 'smart-page-builder'); ?></span>
                    <span class="spb-feature-status <?php echo $wpengine_active ? 'active' : 'inactive'; ?>">
                        <?php echo $wpengine_active ? esc_html__('Active', 'smart-page-builder') : esc_html__('Not Configured', 'smart-page-builder'); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Getting Started -->
        <div class="spb-dashboard-card spb-getting-started">
            <h2><?php esc_html_e('Getting Started', 'smart-page-builder'); ?></h2>
            <div class="spb-getting-started-content">
                <p><?php esc_html_e('Welcome to Smart Page Builder! Here are some quick steps to get you started:', 'smart-page-builder'); ?></p>
                
                <ol class="spb-steps-list">
                    <li>
                        <strong><?php esc_html_e('Configure WP Engine AI', 'smart-page-builder'); ?></strong>
                        <p><?php esc_html_e('Set up your WP Engine AI credentials for content generation.', 'smart-page-builder'); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Enable Personalization', 'smart-page-builder'); ?></strong>
                        <p><?php esc_html_e('Turn on the personalization engine to start tracking user interests.', 'smart-page-builder'); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Test Search Generation', 'smart-page-builder'); ?></strong>
                        <p><?php esc_html_e('Try searching for content on your site to see AI page generation in action.', 'smart-page-builder'); ?></p>
                    </li>
                    <li>
                        <strong><?php esc_html_e('Review Analytics', 'smart-page-builder'); ?></strong>
                        <p><?php esc_html_e('Monitor performance and user engagement through the analytics dashboard.', 'smart-page-builder'); ?></p>
                    </li>
                </ol>
                
                <div class="spb-getting-started-actions">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=spb-wpengine')); ?>" class="button button-primary">
                        <?php esc_html_e('Configure WP Engine AI', 'smart-page-builder'); ?>
                    </a>
                    <a href="<?php echo esc_url('https://docs.example.com/smart-page-builder'); ?>" class="button button-secondary" target="_blank">
                        <?php esc_html_e('View Documentation', 'smart-page-builder'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.spb-main-dashboard {
    max-width: 100%;
}

.spb-dashboard-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 30px 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    margin: 0 0 30px 0;
}

.spb-dashboard-title {
    font-size: 2.5em;
    margin: 0;
    font-weight: 700;
}

.spb-version {
    font-size: 0.6em;
    opacity: 0.8;
    font-weight: normal;
}

.spb-dashboard-subtitle {
    font-size: 1.2em;
    margin: 10px 0 0 0;
    opacity: 0.9;
}

.spb-stats-overview {
    margin-bottom: 30px;
}

.spb-stats-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 0 5px;
}

.spb-stats-header h2 {
    margin: 0;
    color: #333;
    font-size: 1.4em;
}

.spb-stats-controls {
    display: flex;
    gap: 10px;
    align-items: center;
}

.spb-notifications-toggle {
    position: relative;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 0.9em;
    transition: all 0.2s ease;
}

.spb-notifications-toggle:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.spb-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.spb-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.spb-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.spb-stat-icon {
    font-size: 2.5em;
    margin-right: 15px;
}

.spb-stat-content {
    flex: 1;
}

.spb-stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #2271b1;
    line-height: 1;
}

.spb-stat-label {
    color: #666;
    font-size: 0.9em;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 5px;
}

.spb-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 20px;
}

.spb-dashboard-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.spb-dashboard-card h2 {
    margin: 0 0 20px 0;
    color: #333;
    font-size: 1.3em;
    border-bottom: 2px solid #f0f0f1;
    padding-bottom: 10px;
}

.spb-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
}

.spb-action-button {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px 15px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    transition: all 0.2s ease;
}

.spb-action-button:hover {
    background: #e9ecef;
    transform: translateY(-2px);
    color: #333;
    text-decoration: none;
}

.spb-action-icon {
    font-size: 2em;
    margin-bottom: 10px;
}

.spb-action-text {
    font-size: 0.9em;
    font-weight: 600;
    text-align: center;
}

.spb-status-list {
    margin-bottom: 20px;
}

.spb-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f1;
}

.spb-status-item:last-child {
    border-bottom: none;
}

.spb-status-label {
    font-weight: 500;
    color: #333;
}

.spb-status-value {
    font-weight: 600;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.9em;
}

.spb-status-value.good {
    background: #d4edda;
    color: #155724;
}

.spb-status-value.warning {
    background: #fff3cd;
    color: #856404;
}

.spb-status-value.error {
    background: #f8d7da;
    color: #721c24;
}

.spb-activity-list {
    max-height: 300px;
    overflow-y: auto;
}

.spb-activity-item {
    display: flex;
    align-items: flex-start;
    padding: 15px 0;
    border-bottom: 1px solid #f0f0f1;
}

.spb-activity-item:last-child {
    border-bottom: none;
}

.spb-activity-icon {
    font-size: 1.5em;
    margin-right: 15px;
    margin-top: 5px;
}

.spb-activity-content {
    flex: 1;
}

.spb-activity-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.spb-activity-description {
    color: #666;
    font-size: 0.9em;
    margin-bottom: 5px;
}

.spb-activity-time {
    color: #999;
    font-size: 0.8em;
}

.spb-features-list {
    margin-bottom: 20px;
}

.spb-feature-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f1;
}

.spb-feature-item:last-child {
    border-bottom: none;
}

.spb-feature-name {
    font-weight: 500;
    color: #333;
}

.spb-feature-status {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spb-feature-status.active {
    background: #d4edda;
    color: #155724;
}

.spb-feature-status.inactive {
    background: #f8d7da;
    color: #721c24;
}

.spb-getting-started-content p {
    margin-bottom: 20px;
    color: #666;
}

.spb-steps-list {
    margin: 20px 0;
    padding-left: 20px;
}

.spb-steps-list li {
    margin-bottom: 15px;
}

.spb-steps-list strong {
    color: #333;
}

.spb-steps-list p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 0.9em;
}

.spb-getting-started-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.spb-no-activity {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

@media (max-width: 768px) {
    .spb-dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .spb-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .spb-actions-grid {
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    }
    
    .spb-getting-started-actions {
        flex-direction: column;
    }
    
    .spb-getting-started-actions .button {
        text-align: center;
    }
}
</style>

<!-- Phase 2: Real-time Dashboard Enhancements -->
<div id="spb-notifications-container" class="spb-notifications-container" style="display: none;">
    <div class="spb-notifications-header">
        <h3><?php esc_html_e('Notifications', 'smart-page-builder'); ?></h3>
        <button type="button" class="spb-notifications-close">&times;</button>
    </div>
    <div class="spb-notifications-list"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Phase 2: Real-time Dashboard Implementation
    var SPB_Dashboard = {
        refreshInterval: 30000, // 30 seconds
        intervals: {},
        
        init: function() {
            this.setupEventHandlers();
            this.startRealTimeUpdates();
            this.loadNotifications();
        },
        
        setupEventHandlers: function() {
            var self = this;
            
            // Run diagnostics
            $('#spb-run-diagnostics').on('click', function() {
                self.runSystemDiagnostics($(this));
            });
            
            // Refresh buttons
            $('.spb-refresh-stats').on('click', function() {
                self.refreshDashboardStats();
            });
            
            $('.spb-refresh-activity').on('click', function() {
                self.refreshRecentActivity();
            });
            
            $('.spb-refresh-health').on('click', function() {
                self.refreshSystemHealth();
            });
            
            // Notifications
            $('.spb-notifications-toggle').on('click', function() {
                self.toggleNotifications();
            });
            
            $(document).on('click', '.spb-notification-dismiss', function() {
                self.dismissNotification($(this).data('notification-id'));
            });
        },
        
        startRealTimeUpdates: function() {
            var self = this;
            
            // Initial load
            this.refreshDashboardStats();
            this.refreshRecentActivity();
            this.refreshSystemHealth();
            this.refreshPerformanceMetrics();
            
            // Set up intervals
            this.intervals.stats = setInterval(function() {
                self.refreshDashboardStats();
            }, this.refreshInterval);
            
            this.intervals.activity = setInterval(function() {
                self.refreshRecentActivity();
            }, this.refreshInterval * 2); // Every minute
            
            this.intervals.health = setInterval(function() {
                self.refreshSystemHealth();
            }, this.refreshInterval * 4); // Every 2 minutes
            
            this.intervals.notifications = setInterval(function() {
                self.loadNotifications();
            }, this.refreshInterval * 6); // Every 3 minutes
        },
        
        refreshDashboardStats: function() {
            var self = this;
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_dashboard_stats',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateStatsDisplay(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to refresh dashboard stats');
                }
            });
        },
        
        updateStatsDisplay: function(stats) {
            // Update stat cards with animation
            $('.spb-stat-card').each(function() {
                var $card = $(this);
                var $number = $card.find('.spb-stat-number');
                var statType = $card.data('stat-type');
                
                if (stats[statType] !== undefined) {
                    var currentValue = parseInt($number.text()) || 0;
                    var newValue = stats[statType];
                    
                    if (currentValue !== newValue) {
                        $card.addClass('spb-stat-updating');
                        this.animateNumber($number, currentValue, newValue);
                        
                        // Add trend indicator
                        if (stats.trends && stats.trends[statType + '_trend']) {
                            this.updateTrendIndicator($card, stats.trends[statType + '_trend']);
                        }
                        
                        setTimeout(function() {
                            $card.removeClass('spb-stat-updating');
                        }, 1000);
                    }
                }
            }.bind(this));
            
            // Update last updated timestamp
            $('.spb-last-updated').text('Last updated: ' + new Date().toLocaleTimeString());
        },
        
        animateNumber: function($element, start, end) {
            var duration = 1000;
            var startTime = Date.now();
            
            function update() {
                var elapsed = Date.now() - startTime;
                var progress = Math.min(elapsed / duration, 1);
                var current = Math.round(start + (end - start) * progress);
                
                $element.text(current);
                
                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            }
            
            update();
        },
        
        updateTrendIndicator: function($card, trend) {
            var $existing = $card.find('.spb-trend-indicator');
            $existing.remove();
            
            var trendClass = 'spb-trend-' + trend.direction;
            var trendIcon = trend.direction === 'up' ? '↗' : (trend.direction === 'down' ? '↘' : '→');
            var trendText = trend.percentage !== 0 ? Math.abs(trend.percentage) + '%' : '';
            
            var $indicator = $('<div class="spb-trend-indicator ' + trendClass + '">' + 
                             trendIcon + ' ' + trendText + '</div>');
            
            $card.find('.spb-stat-content').append($indicator);
        },
        
        refreshRecentActivity: function() {
            var self = this;
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_recent_activity',
                    nonce: spb_admin.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success) {
                        self.updateActivityDisplay(response.data.activity);
                    }
                },
                error: function() {
                    console.log('Failed to refresh recent activity');
                }
            });
        },
        
        updateActivityDisplay: function(activities) {
            var $container = $('.spb-activity-list');
            $container.empty();
            
            if (activities.length === 0) {
                $container.html('<div class="spb-no-activity"><p>No recent activity to display.</p></div>');
                return;
            }
            
            activities.forEach(function(activity) {
                var timeAgo = this.timeAgo(activity.time);
                var $item = $('<div class="spb-activity-item spb-activity-new">' +
                    '<div class="spb-activity-icon ' + activity.type + '">' + activity.icon + '</div>' +
                    '<div class="spb-activity-content">' +
                        '<div class="spb-activity-title">' + activity.title + '</div>' +
                        '<div class="spb-activity-description">' + activity.description + '</div>' +
                        '<div class="spb-activity-time">' + timeAgo + '</div>' +
                    '</div>' +
                '</div>');
                
                $container.append($item);
                
                // Animate in
                setTimeout(function() {
                    $item.removeClass('spb-activity-new');
                }, 100);
            }.bind(this));
        },
        
        refreshSystemHealth: function() {
            var self = this;
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_system_health',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateHealthDisplay(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to refresh system health');
                }
            });
        },
        
        updateHealthDisplay: function(health) {
            // Update overall status
            var $statusCard = $('.spb-system-status');
            $statusCard.removeClass('status-good status-warning status-error')
                      .addClass('status-' + health.overall_status);
            
            // Update individual checks
            Object.keys(health.checks).forEach(function(checkName) {
                var check = health.checks[checkName];
                var $item = $('.spb-status-item[data-check="' + checkName + '"]');
                
                if ($item.length === 0) {
                    // Create new status item if it doesn't exist
                    $item = $('<div class="spb-status-item" data-check="' + checkName + '">' +
                        '<span class="spb-status-label">' + checkName.replace('_', ' ').toUpperCase() + '</span>' +
                        '<span class="spb-status-value"></span>' +
                    '</div>');
                    $('.spb-status-list').append($item);
                }
                
                var $value = $item.find('.spb-status-value');
                $value.removeClass('good warning error').addClass(check.status);
                $value.text(check.value);
                $value.attr('title', check.message);
            });
        },
        
        refreshPerformanceMetrics: function() {
            var self = this;
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_performance_metrics',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updatePerformanceDisplay(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to refresh performance metrics');
                }
            });
        },
        
        updatePerformanceDisplay: function(metrics) {
            // This would update performance charts and metrics
            // For now, we'll just log the data
            console.log('Performance metrics updated:', metrics);
        },
        
        runSystemDiagnostics: function($button) {
            var originalText = $button.text();
            $button.text('Running Diagnostics...').prop('disabled', true);
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_run_system_diagnostics',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var diagnostics = response.data;
                        var message = 'Diagnostics completed!\n\n';
                        message += 'Tests run: ' + diagnostics.total_count + '\n';
                        message += 'Failed: ' + diagnostics.failed_count + '\n';
                        message += 'Duration: ' + diagnostics.duration + ' seconds\n';
                        message += 'Overall result: ' + diagnostics.overall_result.toUpperCase();
                        
                        alert(message);
                    } else {
                        alert('Diagnostics failed: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('Failed to run diagnostics. Please try again.');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                }
            });
        },
        
        loadNotifications: function() {
            var self = this;
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_notifications',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateNotifications(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to load notifications');
                }
            });
        },
        
        updateNotifications: function(data) {
            var $container = $('.spb-notifications-list');
            var $badge = $('.spb-notifications-badge');
            
            // Update badge count
            if (data.unread_count > 0) {
                $badge.text(data.unread_count).show();
            } else {
                $badge.hide();
            }
            
            // Update notifications list
            $container.empty();
            
            if (data.notifications.length === 0) {
                $container.html('<div class="spb-no-notifications">No notifications</div>');
                return;
            }
            
            data.notifications.forEach(function(notification) {
                var $notification = $('<div class="spb-notification spb-notification-' + notification.type + '">' +
                    '<div class="spb-notification-header">' +
                        '<strong>' + notification.title + '</strong>' +
                        '<button type="button" class="spb-notification-dismiss" data-notification-id="' + notification.id + '">&times;</button>' +
                    '</div>' +
                    '<div class="spb-notification-message">' + notification.message + '</div>' +
                    '<div class="spb-notification-time">' + this.timeAgo(notification.timestamp) + '</div>' +
                '</div>');
                
                $container.append($notification);
            }.bind(this));
        },
        
        toggleNotifications: function() {
            var $container = $('#spb-notifications-container');
            $container.toggle();
        },
        
        dismissNotification: function(notificationId) {
            var self = this;
            
            $.ajax({
                url: spb_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_dismiss_notification',
                    nonce: spb_admin.nonce,
                    notification_id: notificationId
                },
                success: function(response) {
                    if (response.success) {
                        self.loadNotifications(); // Refresh notifications
                    }
                },
                error: function() {
                    console.log('Failed to dismiss notification');
                }
            });
        },
        
        timeAgo: function(timestamp) {
            var now = Math.floor(Date.now() / 1000);
            var diff = now - timestamp;
            
            if (diff < 60) return 'Just now';
            if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
            if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
            return Math.floor(diff / 86400) + ' days ago';
        },
        
        destroy: function() {
            // Clear all intervals
            Object.keys(this.intervals).forEach(function(key) {
                clearInterval(this.intervals[key]);
            }.bind(this));
        }
    };
    
    // Initialize dashboard
    SPB_Dashboard.init();
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        SPB_Dashboard.destroy();
    });
});
</script>

<style>
/* Phase 2: Real-time Dashboard Styles */
.spb-stat-updating {
    transform: scale(1.05);
    transition: transform 0.3s ease;
}

.spb-trend-indicator {
    font-size: 0.8em;
    margin-top: 5px;
    font-weight: 600;
}

.spb-trend-up {
    color: #28a745;
}

.spb-trend-down {
    color: #dc3545;
}

.spb-trend-stable {
    color: #6c757d;
}

.spb-activity-new {
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
}

.spb-activity-item {
    opacity: 1;
    transform: translateY(0);
}

.spb-last-updated {
    font-size: 0.8em;
    color: #666;
    text-align: center;
    margin-top: 10px;
}

.spb-refresh-button {
    background: none;
    border: none;
    color: #0073aa;
    cursor: pointer;
    font-size: 0.9em;
    padding: 5px 10px;
    border-radius: 3px;
    transition: background-color 0.2s ease;
}

.spb-refresh-button:hover {
    background-color: #f0f0f1;
}

.spb-notifications-container {
    position: fixed;
    top: 32px;
    right: 20px;
    width: 350px;
    max-height: 500px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    overflow: hidden;
}

.spb-notifications-header {
    padding: 15px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spb-notifications-header h3 {
    margin: 0;
    font-size: 1.1em;
}

.spb-notifications-close {
    background: none;
    border: none;
    font-size: 1.5em;
    cursor: pointer;
    color: #666;
}

.spb-notifications-list {
    max-height: 400px;
    overflow-y: auto;
    padding: 10px;
}

.spb-notification {
    padding: 12px;
    margin-bottom: 10px;
    border-radius: 6px;
    border-left: 4px solid #ddd;
}

.spb-notification-info {
    border-left-color: #17a2b8;
    background-color: #d1ecf1;
}

.spb-notification-warning {
    border-left-color: #ffc107;
    background-color: #fff3cd;
}

.spb-notification-error {
    border-left-color: #dc3545;
    background-color: #f8d7da;
}

.spb-notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 5px;
}

.spb-notification-dismiss {
    background: none;
    border: none;
    font-size: 1.2em;
    cursor: pointer;
    color: #666;
    padding: 0;
    margin-left: 10px;
}

.spb-notification-message {
    font-size: 0.9em;
    color: #333;
    margin-bottom: 5px;
}

.spb-notification-time {
    font-size: 0.8em;
    color: #666;
}

.spb-no-notifications {
    text-align: center;
    color: #666;
    padding: 20px;
}

.spb-notifications-badge {
    background: #dc3545;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 0.7em;
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    text-align: center;
}

.status-good .spb-dashboard-card {
    border-left: 4px solid #28a745;
}

.status-warning .spb-dashboard-card {
    border-left: 4px solid #ffc107;
}

.status-error .spb-dashboard-card {
    border-left: 4px solid #dc3545;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.spb-stat-updating .spb-stat-number {
    animation: pulse 0.5s ease-in-out;
}
</style>
