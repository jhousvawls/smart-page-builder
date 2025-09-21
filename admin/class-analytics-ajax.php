<?php
/**
 * Analytics AJAX Handler Class
 *
 * Handles AJAX requests for the analytics dashboard including
 * real-time data updates, exports, and A/B test management.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Analytics AJAX Handler Class
 *
 * Provides AJAX endpoints for the analytics dashboard to enable
 * real-time data updates, export functionality, and interactive features.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin
 */
class Smart_Page_Builder_Analytics_Ajax {

    /**
     * Analytics manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Analytics_Manager    $analytics_manager
     */
    private $analytics_manager;

    /**
     * A/B testing manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_AB_Testing    $ab_testing
     */
    private $ab_testing;

    /**
     * Initialize the AJAX handler
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->analytics_manager = new Smart_Page_Builder_Analytics_Manager();
        $this->ab_testing = new Smart_Page_Builder_AB_Testing();
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since    2.0.0
     * @access   private
     */
    private function init_hooks() {
        // AJAX endpoints for logged-in users
        add_action('wp_ajax_spb_get_analytics_data', array($this, 'get_analytics_data'));
        add_action('wp_ajax_spb_refresh_metrics', array($this, 'refresh_metrics'));
        add_action('wp_ajax_spb_export_analytics', array($this, 'export_analytics'));
        add_action('wp_ajax_spb_create_ab_test', array($this, 'create_ab_test'));
        add_action('wp_ajax_spb_get_provider_stats', array($this, 'get_provider_stats'));
        add_action('wp_ajax_spb_test_provider_connection', array($this, 'test_provider_connection'));
        add_action('wp_ajax_spb_generate_content_from_gap', array($this, 'generate_content_from_gap'));
        add_action('wp_ajax_spb_get_content_gaps', array($this, 'get_content_gaps'));
        add_action('wp_ajax_spb_get_top_content', array($this, 'get_top_content'));
    }

    /**
     * Get analytics data for dashboard
     *
     * @since    2.0.0
     */
    public function get_analytics_data() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_view_analytics')) {
            wp_send_json_error('Insufficient permissions');
        }

        $period = sanitize_text_field($_POST['period'] ?? 'week');
        $data = $this->analytics_manager->get_dashboard_analytics($period);

        // Add real-time provider statistics
        $data['provider_stats'] = $this->get_provider_usage_stats();
        
        // Add current timestamp
        $data['last_updated'] = current_time('mysql');
        $data['timestamp'] = time();

        wp_send_json_success($data);
    }

    /**
     * Refresh metrics data
     *
     * @since    2.0.0
     */
    public function refresh_metrics() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_view_analytics')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Clear analytics cache to force fresh data
        wp_cache_delete('spb_analytics_dashboard', 'spb_analytics');
        wp_cache_delete('spb_content_gaps', 'spb_analytics');
        wp_cache_delete('spb_top_content', 'spb_analytics');

        // Get fresh data
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        $data = $this->analytics_manager->get_dashboard_analytics($period);

        // Add provider statistics
        $data['provider_stats'] = $this->get_provider_usage_stats();
        $data['last_updated'] = current_time('mysql');

        wp_send_json_success($data);
    }

    /**
     * Export analytics data
     *
     * @since    2.0.0
     */
    public function export_analytics() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'spb_export_analytics')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_export_analytics')) {
            wp_die('Insufficient permissions');
        }

        $export_type = sanitize_text_field($_GET['export'] ?? 'csv');
        $period = sanitize_text_field($_GET['period'] ?? 'week');

        $data = $this->analytics_manager->get_dashboard_analytics($period);
        
        switch ($export_type) {
            case 'csv':
                $this->export_csv($data, $period);
                break;
            case 'json':
                $this->export_json($data, $period);
                break;
            default:
                wp_die('Invalid export type');
        }
    }

    /**
     * Create A/B test
     *
     * @since    2.0.0
     */
    public function create_ab_test() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_manage_ab_tests')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_name = sanitize_text_field($_POST['test_name'] ?? '');
        $test_type = sanitize_text_field($_POST['test_type'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (empty($test_name) || empty($test_type)) {
            wp_send_json_error('Test name and type are required');
        }

        $test_data = array(
            'name' => $test_name,
            'type' => $test_type,
            'description' => $description,
            'status' => 'active',
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );

        $test_id = $this->ab_testing->create_test($test_data);

        if ($test_id) {
            wp_send_json_success(array(
                'test_id' => $test_id,
                'message' => 'A/B test created successfully'
            ));
        } else {
            wp_send_json_error('Failed to create A/B test');
        }
    }

    /**
     * Get provider usage statistics
     *
     * @since    2.0.0
     */
    public function get_provider_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_view_analytics')) {
            wp_send_json_error('Insufficient permissions');
        }

        $stats = $this->get_provider_usage_stats();
        wp_send_json_success($stats);
    }

    /**
     * Test provider connection
     *
     * @since    2.0.0
     */
    public function test_provider_connection() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_manage_providers')) {
            wp_send_json_error('Insufficient permissions');
        }

        $provider_id = sanitize_text_field($_POST['provider_id'] ?? '');
        
        if (empty($provider_id)) {
            wp_send_json_error('Provider ID is required');
        }

        // Get provider manager
        $provider_manager = new Smart_Page_Builder_AI_Provider_Manager();
        $result = $provider_manager->test_provider_connection($provider_id);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Generate content from content gap
     *
     * @since    2.0.0
     */
    public function generate_content_from_gap() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_generate_content')) {
            wp_send_json_error('Insufficient permissions');
        }

        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $content_type = sanitize_text_field($_POST['content_type'] ?? 'default');

        if (empty($search_term)) {
            wp_send_json_error('Search term is required');
        }

        // Trigger content generation (this would typically be queued)
        $generation_data = array(
            'search_term' => $search_term,
            'content_type' => $content_type,
            'source' => 'content_gap',
            'requested_by' => get_current_user_id(),
            'requested_at' => current_time('mysql')
        );

        // Add to generation queue
        $queue_id = $this->queue_content_generation($generation_data);

        if ($queue_id) {
            wp_send_json_success(array(
                'queue_id' => $queue_id,
                'message' => 'Content generation queued successfully'
            ));
        } else {
            wp_send_json_error('Failed to queue content generation');
        }
    }

    /**
     * Get content gaps data
     *
     * @since    2.0.0
     */
    public function get_content_gaps() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_view_analytics')) {
            wp_send_json_error('Insufficient permissions');
        }

        $limit = intval($_POST['limit'] ?? 10);
        $gaps = $this->analytics_manager->get_content_gaps($limit);

        wp_send_json_success($gaps);
    }

    /**
     * Get top performing content
     *
     * @since    2.0.0
     */
    public function get_top_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_analytics_nonce')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_view_analytics')) {
            wp_send_json_error('Insufficient permissions');
        }

        $limit = intval($_POST['limit'] ?? 10);
        $period = sanitize_text_field($_POST['period'] ?? 'week');
        
        $top_content = $this->analytics_manager->get_top_performing_content($limit, $period);

        wp_send_json_success($top_content);
    }

    /**
     * Get provider usage statistics
     *
     * @since    2.0.0
     * @access   private
     * @return   array    Provider usage statistics
     */
    private function get_provider_usage_stats() {
        global $wpdb;

        $analytics_table = $wpdb->prefix . 'spb_analytics';
        
        // Get provider usage for the last 30 days
        $stats = $wpdb->get_results(
            "SELECT 
                provider_id,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as successful_requests,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as failed_requests,
                AVG(response_time) as avg_response_time,
                SUM(cost) as total_cost
             FROM {$analytics_table} 
             WHERE event_type = 'ai_provider_usage'
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY provider_id
             ORDER BY total_requests DESC"
        );

        $formatted_stats = array();
        foreach ($stats as $stat) {
            $success_rate = $stat->total_requests > 0 
                ? ($stat->successful_requests / $stat->total_requests) * 100 
                : 0;

            $formatted_stats[$stat->provider_id] = array(
                'total_requests' => intval($stat->total_requests),
                'successful_requests' => intval($stat->successful_requests),
                'failed_requests' => intval($stat->failed_requests),
                'success_rate' => round($success_rate, 2),
                'avg_response_time' => round($stat->avg_response_time, 2),
                'total_cost' => round($stat->total_cost, 4)
            );
        }

        return $formatted_stats;
    }

    /**
     * Export data as CSV
     *
     * @since    2.0.0
     * @access   private
     * @param    array    $data      Analytics data
     * @param    string   $period    Time period
     */
    private function export_csv($data, $period) {
        $filename = 'spb-analytics-' . $period . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // Write headers
        fputcsv($output, array(
            'Metric',
            'Value',
            'Period',
            'Generated At'
        ));

        // Write basic metrics
        $metrics = array(
            'Page Views' => $data[$period]['page_views'] ?? 0,
            'Content Generated' => $data[$period]['content_generated'] ?? 0,
            'Search Queries' => $data[$period]['search_queries'] ?? 0,
            'Average Confidence' => $data[$period]['avg_confidence'] ?? 0,
            'Approval Rate' => $data[$period]['approval_rate'] ?? 0
        );

        foreach ($metrics as $metric => $value) {
            fputcsv($output, array(
                $metric,
                $value,
                $period,
                current_time('mysql')
            ));
        }

        // Write content gaps
        if (!empty($data['content_gaps'])) {
            fputcsv($output, array('', '', '', '')); // Empty row
            fputcsv($output, array('Content Gaps', '', '', ''));
            fputcsv($output, array('Search Term', 'Search Count', 'Opportunity Score', ''));
            
            foreach ($data['content_gaps'] as $gap) {
                fputcsv($output, array(
                    $gap['search_term'],
                    $gap['search_count'],
                    $gap['opportunity_score'],
                    ''
                ));
            }
        }

        fclose($output);
        exit;
    }

    /**
     * Export data as JSON
     *
     * @since    2.0.0
     * @access   private
     * @param    array    $data      Analytics data
     * @param    string   $period    Time period
     */
    private function export_json($data, $period) {
        $filename = 'spb-analytics-' . $period . '-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $export_data = array(
            'export_info' => array(
                'period' => $period,
                'generated_at' => current_time('mysql'),
                'plugin_version' => SPB_VERSION
            ),
            'analytics_data' => $data
        );

        echo wp_json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Queue content generation
     *
     * @since    2.0.0
     * @access   private
     * @param    array    $generation_data    Generation request data
     * @return   int|false    Queue ID or false on failure
     */
    private function queue_content_generation($generation_data) {
        global $wpdb;

        $queue_table = $wpdb->prefix . 'spb_generation_queue';
        
        $result = $wpdb->insert(
            $queue_table,
            array(
                'search_term' => $generation_data['search_term'],
                'content_type' => $generation_data['content_type'],
                'source' => $generation_data['source'],
                'status' => 'queued',
                'requested_by' => $generation_data['requested_by'],
                'requested_at' => $generation_data['requested_at'],
                'priority' => 5 // Default priority
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Enqueue scripts and localize AJAX data
     *
     * @since    2.0.0
     */
    public static function enqueue_scripts() {
        if (get_current_screen()->id !== 'smart-page-builder_page_spb-analytics') {
            return;
        }

        wp_localize_script('spb-analytics-dashboard', 'spbAnalytics', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spb_analytics_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'smart-page-builder'),
                'error' => __('An error occurred', 'smart-page-builder'),
                'success' => __('Success', 'smart-page-builder'),
                'confirm_test_creation' => __('Are you sure you want to create this A/B test?', 'smart-page-builder'),
                'confirm_content_generation' => __('Generate content for this search term?', 'smart-page-builder')
            )
        ));
    }
}

// Initialize AJAX handler
if (is_admin()) {
    new Smart_Page_Builder_Analytics_Ajax();
    
    // Enqueue scripts on analytics page
    add_action('admin_enqueue_scripts', array('Smart_Page_Builder_Analytics_Ajax', 'enqueue_scripts'));
}
