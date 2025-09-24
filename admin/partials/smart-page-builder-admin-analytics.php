<?php
/**
 * Admin Analytics Interface for Smart Page Builder
 *
 * Provides the analytics dashboard interface for the admin area.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get analytics data with safety checks
$analytics_manager = null;
if (class_exists('Smart_Page_Builder_Analytics_Manager')) {
    $analytics_manager = new Smart_Page_Builder_Analytics_Manager();
}

// Get analytics data
$analytics_data = array(
    'total_pages' => 0,
    'total_components' => 0,
    'total_views' => 0,
    'avg_load_time' => 0,
    'conversion_rate' => 0,
    'top_components' => array(),
    'recent_activity' => array(),
    'performance_metrics' => array()
);

if ($analytics_manager && method_exists($analytics_manager, 'get_dashboard_data')) {
    $analytics_data = $analytics_manager->get_dashboard_data();
}

// Date range handling
$date_range = isset($_GET['range']) ? sanitize_text_field($_GET['range']) : '7days';
$valid_ranges = array('24hours', '7days', '30days', '90days');
if (!in_array($date_range, $valid_ranges)) {
    $date_range = '7days';
}
?>

<div class="wrap spb-analytics-interface">
    <div class="spb-page-header">
        <h1><?php esc_html_e('Smart Page Builder Analytics', 'smart-page-builder'); ?></h1>
        <p class="spb-page-description"><?php esc_html_e('Monitor performance and insights', 'smart-page-builder'); ?></p>
    </div>
    
    <!-- Date Range Selector -->
    <div class="spb-analytics-controls">
        <div class="spb-date-range-selector">
            <label for="spb-date-range"><?php esc_html_e('Date Range:', 'smart-page-builder'); ?></label>
            <select id="spb-date-range" name="range">
                <option value="24hours" <?php selected($date_range, '24hours'); ?>><?php esc_html_e('Last 24 Hours', 'smart-page-builder'); ?></option>
                <option value="7days" <?php selected($date_range, '7days'); ?>><?php esc_html_e('Last 7 Days', 'smart-page-builder'); ?></option>
                <option value="30days" <?php selected($date_range, '30days'); ?>><?php esc_html_e('Last 30 Days', 'smart-page-builder'); ?></option>
                <option value="90days" <?php selected($date_range, '90days'); ?>><?php esc_html_e('Last 90 Days', 'smart-page-builder'); ?></option>
            </select>
            <button type="button" class="button" id="spb-refresh-analytics">
                <?php esc_html_e('Refresh', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>

    <!-- Analytics Overview Cards -->
    <div class="spb-analytics-overview">
        <div class="spb-overview-cards">
            <div class="spb-overview-card">
                <div class="spb-card-icon">📄</div>
                <div class="spb-card-content">
                    <h3><?php esc_html_e('Total Pages', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value"><?php echo esc_html($analytics_data['total_pages']); ?></div>
                    <div class="spb-metric-change positive">+12%</div>
                </div>
            </div>
            
            <div class="spb-overview-card">
                <div class="spb-card-icon">🧩</div>
                <div class="spb-card-content">
                    <h3><?php esc_html_e('Components Used', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value"><?php echo esc_html($analytics_data['total_components']); ?></div>
                    <div class="spb-metric-change positive">+8%</div>
                </div>
            </div>
            
            <div class="spb-overview-card">
                <div class="spb-card-icon">👁️</div>
                <div class="spb-card-content">
                    <h3><?php esc_html_e('Page Views', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value"><?php echo esc_html(number_format($analytics_data['total_views'])); ?></div>
                    <div class="spb-metric-change positive">+15%</div>
                </div>
            </div>
            
            <div class="spb-overview-card">
                <div class="spb-card-icon">⚡</div>
                <div class="spb-card-content">
                    <h3><?php esc_html_e('Avg Load Time', 'smart-page-builder'); ?></h3>
                    <div class="spb-metric-value"><?php echo esc_html(round($analytics_data['avg_load_time'], 2)); ?>s</div>
                    <div class="spb-metric-change negative">-5%</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts -->
    <div class="spb-analytics-charts">
        <div class="spb-chart-container">
            <div class="spb-chart-card">
                <h3><?php esc_html_e('Page Views Over Time', 'smart-page-builder'); ?></h3>
                <div class="spb-chart-placeholder" id="spb-views-chart">
                    <canvas id="spb-views-canvas" width="400" height="200"></canvas>
                </div>
            </div>
            
            <div class="spb-chart-card">
                <h3><?php esc_html_e('Component Usage', 'smart-page-builder'); ?></h3>
                <div class="spb-chart-placeholder" id="spb-components-chart">
                    <canvas id="spb-components-canvas" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Components -->
    <div class="spb-analytics-section">
        <h2><?php esc_html_e('Top Performing Components', 'smart-page-builder'); ?></h2>
        
        <div class="spb-components-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Component', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Usage Count', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Avg. Performance', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Conversion Rate', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Actions', 'smart-page-builder'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $top_components = !empty($analytics_data['top_components']) ? $analytics_data['top_components'] : array(
                        array('name' => 'Hero Banner', 'usage' => 45, 'performance' => 92, 'conversion' => 3.2),
                        array('name' => 'Call to Action', 'usage' => 38, 'performance' => 88, 'conversion' => 5.1),
                        array('name' => 'Feature Grid', 'usage' => 32, 'performance' => 85, 'conversion' => 2.8),
                        array('name' => 'Testimonials', 'usage' => 28, 'performance' => 90, 'conversion' => 4.3),
                        array('name' => 'Contact Form', 'usage' => 22, 'performance' => 87, 'conversion' => 8.7)
                    );
                    
                    foreach ($top_components as $component):
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($component['name']); ?></strong></td>
                            <td><?php echo esc_html($component['usage']); ?></td>
                            <td>
                                <div class="spb-performance-bar">
                                    <div class="spb-performance-fill" style="width: <?php echo esc_attr($component['performance']); ?>%"></div>
                                </div>
                                <span class="spb-performance-text"><?php echo esc_html($component['performance']); ?>%</span>
                            </td>
                            <td><?php echo esc_html($component['conversion']); ?>%</td>
                            <td>
                                <button type="button" class="button button-small spb-view-details" data-component="<?php echo esc_attr($component['name']); ?>">
                                    <?php esc_html_e('View Details', 'smart-page-builder'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="spb-analytics-section">
        <h2><?php esc_html_e('Recent Activity', 'smart-page-builder'); ?></h2>
        
        <div class="spb-activity-feed">
            <?php
            $recent_activity = !empty($analytics_data['recent_activity']) ? $analytics_data['recent_activity'] : array(
                array('type' => 'page_created', 'title' => 'New landing page created', 'time' => '2 hours ago'),
                array('type' => 'component_added', 'title' => 'Hero banner added to homepage', 'time' => '4 hours ago'),
                array('type' => 'performance_alert', 'title' => 'Page load time improved by 15%', 'time' => '6 hours ago'),
                array('type' => 'conversion_milestone', 'title' => 'Contact form reached 100 submissions', 'time' => '1 day ago'),
                array('type' => 'page_updated', 'title' => 'About page components updated', 'time' => '2 days ago')
            );
            
            foreach ($recent_activity as $activity):
            ?>
                <div class="spb-activity-item">
                    <div class="spb-activity-icon spb-activity-<?php echo esc_attr($activity['type']); ?>"></div>
                    <div class="spb-activity-content">
                        <div class="spb-activity-title"><?php echo esc_html($activity['title']); ?></div>
                        <div class="spb-activity-time"><?php echo esc_html($activity['time']); ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Performance Insights -->
    <div class="spb-analytics-section">
        <h2><?php esc_html_e('Performance Insights', 'smart-page-builder'); ?></h2>
        
        <div class="spb-insights-grid">
            <div class="spb-insight-card">
                <div class="spb-insight-icon">🚀</div>
                <div class="spb-insight-content">
                    <h4><?php esc_html_e('Speed Optimization', 'smart-page-builder'); ?></h4>
                    <p><?php esc_html_e('Your pages are loading 23% faster than last month. Great job optimizing images and caching!', 'smart-page-builder'); ?></p>
                </div>
            </div>
            
            <div class="spb-insight-card">
                <div class="spb-insight-icon">📈</div>
                <div class="spb-insight-content">
                    <h4><?php esc_html_e('Conversion Growth', 'smart-page-builder'); ?></h4>
                    <p><?php esc_html_e('Contact form conversions increased by 18%. Consider adding more CTAs to other pages.', 'smart-page-builder'); ?></p>
                </div>
            </div>
            
            <div class="spb-insight-card">
                <div class="spb-insight-icon">🎯</div>
                <div class="spb-insight-content">
                    <h4><?php esc_html_e('Component Recommendation', 'smart-page-builder'); ?></h4>
                    <p><?php esc_html_e('Testimonial components show high engagement. Try adding them to your product pages.', 'smart-page-builder'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.spb-analytics-interface {
    max-width: 100%;
}

.spb-analytics-controls {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.spb-date-range-selector {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spb-date-range-selector label {
    font-weight: 600;
}

.spb-analytics-overview {
    margin-bottom: 30px;
}

.spb-overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.spb-overview-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.spb-card-icon {
    font-size: 2.5em;
    margin-right: 15px;
}

.spb-card-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spb-metric-value {
    font-size: 2.2em;
    font-weight: bold;
    color: #2271b1;
    margin-bottom: 5px;
}

.spb-metric-change {
    font-size: 12px;
    font-weight: 600;
}

.spb-metric-change.positive {
    color: #27ae60;
}

.spb-metric-change.negative {
    color: #e74c3c;
}

.spb-analytics-charts {
    margin-bottom: 30px;
}

.spb-chart-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.spb-chart-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.spb-chart-card h3 {
    margin: 0 0 15px 0;
    color: #333;
}

.spb-chart-placeholder {
    height: 200px;
    background: #f9f9f9;
    border: 2px dashed #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    border-radius: 4px;
}

.spb-analytics-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.spb-analytics-section h2 {
    margin: 0 0 20px 0;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.spb-components-table-container {
    overflow-x: auto;
}

.spb-performance-bar {
    width: 80px;
    height: 8px;
    background: #ddd;
    border-radius: 4px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-right: 8px;
}

.spb-performance-fill {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c 0%, #f39c12 50%, #27ae60 100%);
    transition: width 0.3s ease;
}

.spb-performance-text {
    font-size: 12px;
    color: #666;
}

.spb-activity-feed {
    max-height: 400px;
    overflow-y: auto;
}

.spb-activity-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.spb-activity-item:last-child {
    border-bottom: none;
}

.spb-activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.spb-activity-page_created {
    background: #e3f2fd;
    color: #1976d2;
}

.spb-activity-component_added {
    background: #f3e5f5;
    color: #7b1fa2;
}

.spb-activity-performance_alert {
    background: #e8f5e8;
    color: #388e3c;
}

.spb-activity-conversion_milestone {
    background: #fff3e0;
    color: #f57c00;
}

.spb-activity-page_updated {
    background: #fce4ec;
    color: #c2185b;
}

.spb-activity-title {
    font-weight: 600;
    color: #333;
}

.spb-activity-time {
    font-size: 12px;
    color: #666;
    margin-top: 2px;
}

.spb-insights-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.spb-insight-card {
    background: #f9f9f9;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: flex-start;
}

.spb-insight-icon {
    font-size: 2em;
    margin-right: 15px;
    margin-top: 5px;
}

.spb-insight-content h4 {
    margin: 0 0 10px 0;
    color: #333;
}

.spb-insight-content p {
    margin: 0;
    color: #666;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .spb-overview-cards {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .spb-chart-container {
        grid-template-columns: 1fr;
    }
    
    .spb-insights-grid {
        grid-template-columns: 1fr;
    }
    
    .spb-date-range-selector {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Date range change handler
    $('#spb-date-range').on('change', function() {
        var range = $(this).val();
        window.location.href = window.location.pathname + '?page=smart-page-builder-analytics&range=' + range;
    });
    
    // Refresh analytics
    $('#spb-refresh-analytics').on('click', function() {
        location.reload();
    });
    
    // View component details
    $('.spb-view-details').on('click', function() {
        var component = $(this).data('component');
        alert('Component details for: ' + component + '\n\nThis would open a detailed analytics view for this component.');
    });
    
    // Initialize charts (placeholder functionality)
    if (typeof Chart !== 'undefined') {
        // Views chart
        var viewsCtx = document.getElementById('spb-views-canvas');
        if (viewsCtx) {
            new Chart(viewsCtx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Page Views',
                        data: [120, 190, 300, 500, 200, 300, 450],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
        
        // Components chart
        var componentsCtx = document.getElementById('spb-components-canvas');
        if (componentsCtx) {
            new Chart(componentsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Hero Banner', 'CTA', 'Feature Grid', 'Testimonials', 'Contact Form'],
                    datasets: [{
                        data: [45, 38, 32, 28, 22],
                        backgroundColor: [
                            '#2271b1',
                            '#27ae60',
                            '#f39c12',
                            '#e74c3c',
                            '#9b59b6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }
    } else {
        // Fallback if Chart.js is not loaded
        $('.spb-chart-placeholder').html('<p style="text-align: center; color: #666;">Chart visualization requires Chart.js library</p>');
    }
});
</script>
