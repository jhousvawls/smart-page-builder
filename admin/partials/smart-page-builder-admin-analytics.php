<?php
/**
 * Analytics Dashboard Admin Page
 *
 * Provides comprehensive analytics and metrics for the Smart Page Builder plugin.
 * Displays real-time metrics, content performance, and content gap analysis.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin/partials
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('spb_view_analytics')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'smart-page-builder'));
}

// Initialize analytics manager if Phase 2 is enabled
$analytics_manager = null;
$analytics_data = array();
$phase_2_enabled = defined('SPB_PHASE_2_ENABLED') && SPB_PHASE_2_ENABLED;

if ($phase_2_enabled) {
    $analytics_manager = new Smart_Page_Builder_Analytics_Manager();
    $analytics_data = $analytics_manager->get_dashboard_analytics();
}

// Handle export requests
if (isset($_GET['export']) && wp_verify_nonce($_GET['_wpnonce'], 'spb_export_analytics')) {
    $export_type = sanitize_text_field($_GET['export']);
    $this->handle_analytics_export($export_type, $analytics_data);
}
?>

<div class="wrap spb-analytics-dashboard">
    <h1 class="wp-heading-inline">
        <?php echo esc_html(get_admin_page_title()); ?>
        <span class="spb-version-badge">Phase <?php echo $phase_2_enabled ? '2.0' : '1.0'; ?></span>
    </h1>

    <?php if (!$phase_2_enabled): ?>
        <div class="notice notice-info">
            <p>
                <strong><?php _e('Phase 2 Analytics Available!', 'smart-page-builder'); ?></strong>
                <?php _e('Enable Phase 2 features to access advanced analytics, real-time metrics, and content gap analysis.', 'smart-page-builder'); ?>
            </p>
            <p>
                <code>define('SPB_PHASE_2_ENABLED', true);</code>
            </p>
        </div>
    <?php endif; ?>

    <div class="spb-analytics-header">
        <div class="spb-analytics-actions">
            <button type="button" class="button" id="spb-refresh-analytics">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Refresh Data', 'smart-page-builder'); ?>
            </button>
            
            <?php if ($phase_2_enabled): ?>
                <div class="spb-export-dropdown">
                    <button type="button" class="button" id="spb-export-toggle">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export', 'smart-page-builder'); ?>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="spb-export-menu" id="spb-export-menu">
                        <a href="<?php echo wp_nonce_url(add_query_arg('export', 'csv'), 'spb_export_analytics'); ?>" class="spb-export-option">
                            <span class="dashicons dashicons-media-spreadsheet"></span>
                            <?php _e('Export as CSV', 'smart-page-builder'); ?>
                        </a>
                        <a href="<?php echo wp_nonce_url(add_query_arg('export', 'json'), 'spb_export_analytics'); ?>" class="spb-export-option">
                            <span class="dashicons dashicons-media-code"></span>
                            <?php _e('Export as JSON', 'smart-page-builder'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="spb-time-filter">
            <select id="spb-analytics-period">
                <option value="today"><?php _e('Today', 'smart-page-builder'); ?></option>
                <option value="week" selected><?php _e('Last 7 Days', 'smart-page-builder'); ?></option>
                <option value="month"><?php _e('Last 30 Days', 'smart-page-builder'); ?></option>
            </select>
        </div>
    </div>

    <?php if ($phase_2_enabled && !empty($analytics_data)): ?>
        <!-- Real-time Metrics Dashboard -->
        <div class="spb-analytics-grid">
            <!-- Key Metrics Cards -->
            <div class="spb-metrics-row">
                <div class="spb-metric-card spb-metric-primary">
                    <div class="spb-metric-icon">
                        <span class="dashicons dashicons-visibility"></span>
                    </div>
                    <div class="spb-metric-content">
                        <div class="spb-metric-value" id="spb-page-views">
                            <?php echo number_format($analytics_data['weekly']['page_views']); ?>
                        </div>
                        <div class="spb-metric-label"><?php _e('Page Views', 'smart-page-builder'); ?></div>
                        <div class="spb-metric-change spb-positive">
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                            <?php echo number_format((($analytics_data['weekly']['page_views'] - $analytics_data['today']['page_views']) / max($analytics_data['today']['page_views'], 1)) * 100, 1); ?>%
                        </div>
                    </div>
                </div>

                <div class="spb-metric-card spb-metric-success">
                    <div class="spb-metric-icon">
                        <span class="dashicons dashicons-admin-page"></span>
                    </div>
                    <div class="spb-metric-content">
                        <div class="spb-metric-value" id="spb-content-generated">
                            <?php echo number_format($analytics_data['weekly']['content_generated']); ?>
                        </div>
                        <div class="spb-metric-label"><?php _e('Content Generated', 'smart-page-builder'); ?></div>
                        <div class="spb-metric-change spb-positive">
                            <span class="dashicons dashicons-arrow-up-alt"></span>
                            <?php echo number_format($analytics_data['weekly']['approval_rate'], 1); ?>% <?php _e('approved', 'smart-page-builder'); ?>
                        </div>
                    </div>
                </div>

                <div class="spb-metric-card spb-metric-info">
                    <div class="spb-metric-icon">
                        <span class="dashicons dashicons-search"></span>
                    </div>
                    <div class="spb-metric-content">
                        <div class="spb-metric-value" id="spb-search-queries">
                            <?php echo number_format($analytics_data['weekly']['search_queries']); ?>
                        </div>
                        <div class="spb-metric-label"><?php _e('Search Queries', 'smart-page-builder'); ?></div>
                        <div class="spb-metric-change spb-neutral">
                            <?php echo count($analytics_data['content_gaps']); ?> <?php _e('gaps identified', 'smart-page-builder'); ?>
                        </div>
                    </div>
                </div>

                <div class="spb-metric-card spb-metric-warning">
                    <div class="spb-metric-icon">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="spb-metric-content">
                        <div class="spb-metric-value" id="spb-avg-confidence">
                            <?php echo number_format($analytics_data['weekly']['avg_confidence'], 1); ?>%
                        </div>
                        <div class="spb-metric-label"><?php _e('Avg. Confidence', 'smart-page-builder'); ?></div>
                        <div class="spb-metric-change <?php echo $analytics_data['weekly']['avg_confidence'] >= 70 ? 'spb-positive' : 'spb-negative'; ?>">
                            <span class="dashicons dashicons-<?php echo $analytics_data['weekly']['avg_confidence'] >= 70 ? 'arrow-up-alt' : 'arrow-down-alt'; ?>"></span>
                            <?php echo $analytics_data['weekly']['avg_confidence'] >= 70 ? __('Good', 'smart-page-builder') : __('Needs Improvement', 'smart-page-builder'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="spb-charts-row">
                <div class="spb-chart-container spb-chart-large">
                    <div class="spb-chart-header">
                        <h3><?php _e('Performance Trends', 'smart-page-builder'); ?></h3>
                        <div class="spb-chart-legend">
                            <span class="spb-legend-item spb-legend-views">
                                <span class="spb-legend-color"></span>
                                <?php _e('Page Views', 'smart-page-builder'); ?>
                            </span>
                            <span class="spb-legend-item spb-legend-content">
                                <span class="spb-legend-color"></span>
                                <?php _e('Content Generated', 'smart-page-builder'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="spb-chart-content">
                        <canvas id="spb-performance-chart" width="400" height="200"></canvas>
                    </div>
                </div>

                <div class="spb-chart-container spb-chart-small">
                    <div class="spb-chart-header">
                        <h3><?php _e('Approval Rates', 'smart-page-builder'); ?></h3>
                    </div>
                    <div class="spb-chart-content">
                        <canvas id="spb-approval-chart" width="200" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Content Analysis Row -->
            <div class="spb-analysis-row">
                <div class="spb-analysis-container spb-top-content">
                    <div class="spb-analysis-header">
                        <h3><?php _e('Top Performing Content', 'smart-page-builder'); ?></h3>
                        <span class="spb-analysis-count"><?php echo count($analytics_data['top_content']); ?> <?php _e('pages', 'smart-page-builder'); ?></span>
                    </div>
                    <div class="spb-analysis-content">
                        <?php if (!empty($analytics_data['top_content'])): ?>
                            <div class="spb-content-list">
                                <?php foreach (array_slice($analytics_data['top_content'], 0, 5) as $content): ?>
                                    <div class="spb-content-item">
                                        <div class="spb-content-title">
                                            <a href="<?php echo esc_url($content['url']); ?>" target="_blank">
                                                <?php echo esc_html($content['title']); ?>
                                            </a>
                                        </div>
                                        <div class="spb-content-stats">
                                            <span class="spb-stat-views">
                                                <span class="dashicons dashicons-visibility"></span>
                                                <?php echo number_format($content['view_count']); ?> <?php _e('views', 'smart-page-builder'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="spb-empty-state">
                                <span class="dashicons dashicons-admin-page"></span>
                                <p><?php _e('No content data available yet.', 'smart-page-builder'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="spb-analysis-container spb-content-gaps">
                    <div class="spb-analysis-header">
                        <h3><?php _e('Content Opportunities', 'smart-page-builder'); ?></h3>
                        <span class="spb-analysis-count"><?php echo count($analytics_data['content_gaps']); ?> <?php _e('gaps', 'smart-page-builder'); ?></span>
                    </div>
                    <div class="spb-analysis-content">
                        <?php if (!empty($analytics_data['content_gaps'])): ?>
                            <div class="spb-gaps-list">
                                <?php foreach (array_slice($analytics_data['content_gaps'], 0, 5) as $gap): ?>
                                    <div class="spb-gap-item">
                                        <div class="spb-gap-term">
                                            <strong><?php echo esc_html($gap['search_term']); ?></strong>
                                        </div>
                                        <div class="spb-gap-stats">
                                            <span class="spb-gap-searches">
                                                <?php echo number_format($gap['search_count']); ?> <?php _e('searches', 'smart-page-builder'); ?>
                                            </span>
                                            <span class="spb-gap-score spb-score-<?php echo $gap['opportunity_score'] >= 70 ? 'high' : ($gap['opportunity_score'] >= 40 ? 'medium' : 'low'); ?>">
                                                <?php echo number_format($gap['opportunity_score']); ?>% <?php _e('opportunity', 'smart-page-builder'); ?>
                                            </span>
                                        </div>
                                        <div class="spb-gap-actions">
                                            <button type="button" class="button button-small spb-generate-content" data-term="<?php echo esc_attr($gap['search_term']); ?>">
                                                <?php _e('Generate Content', 'smart-page-builder'); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="spb-empty-state">
                                <span class="dashicons dashicons-search"></span>
                                <p><?php _e('No content gaps identified yet.', 'smart-page-builder'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- A/B Testing Section -->
            <div class="spb-testing-row">
                <div class="spb-testing-container">
                    <div class="spb-testing-header">
                        <h3><?php _e('A/B Testing', 'smart-page-builder'); ?></h3>
                        <button type="button" class="button button-primary" id="spb-create-test">
                            <?php _e('Create New Test', 'smart-page-builder'); ?>
                        </button>
                    </div>
                    <div class="spb-testing-content">
                        <div class="spb-test-placeholder">
                            <span class="dashicons dashicons-chart-line"></span>
                            <p><?php _e('A/B testing framework ready. Create your first test to compare content templates and algorithms.', 'smart-page-builder'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Updates Indicator -->
        <div class="spb-realtime-indicator" id="spb-realtime-indicator">
            <span class="spb-indicator-dot"></span>
            <span class="spb-indicator-text"><?php _e('Live', 'smart-page-builder'); ?></span>
            <span class="spb-last-update"><?php _e('Last updated:', 'smart-page-builder'); ?> <span id="spb-last-update-time"><?php echo current_time('H:i:s'); ?></span></span>
        </div>

    <?php else: ?>
        <!-- Phase 1 Basic Analytics -->
        <div class="spb-basic-analytics">
            <div class="spb-basic-metrics">
                <div class="spb-basic-metric">
                    <h3><?php _e('Generated Pages', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value">
                        <?php
                        $generated_pages = wp_count_posts('spb_dynamic_page');
                        echo number_format($generated_pages->publish + $generated_pages->draft);
                        ?>
                    </div>
                </div>
                <div class="spb-basic-metric">
                    <h3><?php _e('Published Pages', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value">
                        <?php echo number_format($generated_pages->publish); ?>
                    </div>
                </div>
                <div class="spb-basic-metric">
                    <h3><?php _e('Pending Approval', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value">
                        <?php echo number_format($generated_pages->draft); ?>
                    </div>
                </div>
            </div>
            
            <div class="spb-upgrade-notice">
                <h3><?php _e('Upgrade to Phase 2 for Advanced Analytics', 'smart-page-builder'); ?></h3>
                <ul>
                    <li><?php _e('Real-time performance metrics', 'smart-page-builder'); ?></li>
                    <li><?php _e('Content gap analysis', 'smart-page-builder'); ?></li>
                    <li><?php _e('A/B testing framework', 'smart-page-builder'); ?></li>
                    <li><?php _e('Advanced reporting and exports', 'smart-page-builder'); ?></li>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- A/B Test Creation Modal -->
<div id="spb-test-modal" class="spb-modal" style="display: none;">
    <div class="spb-modal-content">
        <div class="spb-modal-header">
            <h2><?php _e('Create A/B Test', 'smart-page-builder'); ?></h2>
            <button type="button" class="spb-modal-close" id="spb-test-modal-close">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
        <div class="spb-modal-body">
            <form id="spb-create-test-form">
                <div class="spb-form-group">
                    <label for="spb-test-name"><?php _e('Test Name', 'smart-page-builder'); ?></label>
                    <input type="text" id="spb-test-name" name="test_name" required>
                </div>
                <div class="spb-form-group">
                    <label for="spb-test-type"><?php _e('Test Type', 'smart-page-builder'); ?></label>
                    <select id="spb-test-type" name="test_type">
                        <option value="template"><?php _e('Content Template', 'smart-page-builder'); ?></option>
                        <option value="algorithm"><?php _e('Algorithm Performance', 'smart-page-builder'); ?></option>
                        <option value="confidence"><?php _e('Confidence Threshold', 'smart-page-builder'); ?></option>
                    </select>
                </div>
                <div class="spb-form-group">
                    <label for="spb-test-description"><?php _e('Description', 'smart-page-builder'); ?></label>
                    <textarea id="spb-test-description" name="description" rows="3"></textarea>
                </div>
                <div class="spb-form-actions">
                    <button type="button" class="button" id="spb-test-cancel"><?php _e('Cancel', 'smart-page-builder'); ?></button>
                    <button type="submit" class="button button-primary"><?php _e('Create Test', 'smart-page-builder'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
// Pass analytics data to JavaScript
window.spbAnalyticsData = <?php echo json_encode($analytics_data); ?>;
window.spbPhase2Enabled = <?php echo $phase_2_enabled ? 'true' : 'false'; ?>;
</script>
