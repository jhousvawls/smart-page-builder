<?php
/**
 * A/B Testing Framework
 *
 * Handles A/B testing for content templates, algorithms, and performance optimization
 * in the Smart Page Builder plugin.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A/B Testing Framework Class
 *
 * Manages A/B tests for different aspects of content generation including
 * templates, algorithms, confidence thresholds, and user experience variations.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_AB_Testing {

    /**
     * The cache manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Cache_Manager    $cache_manager
     */
    private $cache_manager;

    /**
     * The database instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Database    $database
     */
    private $database;

    /**
     * The analytics manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Analytics_Manager    $analytics_manager
     */
    private $analytics_manager;

    /**
     * Active tests cache
     *
     * @since    2.0.0
     * @access   private
     * @var      array    $active_tests
     */
    private $active_tests = null;

    /**
     * Initialize the A/B testing framework
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->cache_manager = new Smart_Page_Builder_Cache_Manager();
        $this->database = new Smart_Page_Builder_Database();
        $this->analytics_manager = new Smart_Page_Builder_Analytics_Manager();
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since    2.0.0
     * @access   private
     */
    private function init_hooks() {
        // Hook into content generation process
        add_filter('spb_content_generation_template', array($this, 'apply_template_test'), 10, 2);
        add_filter('spb_content_generation_algorithm', array($this, 'apply_algorithm_test'), 10, 2);
        add_filter('spb_confidence_threshold', array($this, 'apply_confidence_test'), 10, 2);
        
        // Track test results
        add_action('spb_content_generated', array($this, 'track_test_result'), 10, 2);
        add_action('spb_content_approved', array($this, 'track_test_conversion'), 10, 2);
        add_action('spb_page_view', array($this, 'track_test_engagement'), 10, 2);
        
        // AJAX handlers for admin interface
        add_action('wp_ajax_spb_create_ab_test', array($this, 'handle_create_test_ajax'));
        add_action('wp_ajax_spb_stop_ab_test', array($this, 'handle_stop_test_ajax'));
        add_action('wp_ajax_spb_get_test_results', array($this, 'handle_get_results_ajax'));
        
        // Cleanup completed tests
        add_action('spb_ab_test_cleanup', array($this, 'cleanup_completed_tests'));
        
        if (!wp_next_scheduled('spb_ab_test_cleanup')) {
            wp_schedule_event(time(), 'daily', 'spb_ab_test_cleanup');
        }
    }

    /**
     * Create a new A/B test
     *
     * @since    2.0.0
     * @param    array    $test_config    Test configuration
     * @return   int|WP_Error            Test ID or error
     */
    public function create_test($test_config) {
        // Validate test configuration
        $validation_result = $this->validate_test_config($test_config);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }

        global $wpdb;
        $ab_tests_table = $wpdb->prefix . 'spb_ab_tests';

        // Prepare test data
        $test_data = array(
            'name' => sanitize_text_field($test_config['name']),
            'description' => sanitize_textarea_field($test_config['description'] ?? ''),
            'test_type' => sanitize_text_field($test_config['test_type']),
            'status' => 'active',
            'config' => wp_json_encode($test_config),
            'start_date' => current_time('mysql'),
            'target_sample_size' => intval($test_config['sample_size'] ?? 100),
            'confidence_level' => floatval($test_config['confidence_level'] ?? 95),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        );

        // Insert test record
        $result = $wpdb->insert($ab_tests_table, $test_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Failed to create A/B test');
        }

        $test_id = $wpdb->insert_id;

        // Create test variants
        $this->create_test_variants($test_id, $test_config['variants']);

        // Clear active tests cache
        $this->active_tests = null;
        $this->cache_manager->delete('spb_active_ab_tests');

        // Log test creation
        do_action('spb_ab_test_created', $test_id, $test_config);

        return $test_id;
    }

    /**
     * Get active A/B tests
     *
     * @since    2.0.0
     * @return   array    Active tests
     */
    public function get_active_tests() {
        if ($this->active_tests !== null) {
            return $this->active_tests;
        }

        $cache_key = 'spb_active_ab_tests';
        $cached_tests = $this->cache_manager->get($cache_key);
        
        if ($cached_tests !== false) {
            $this->active_tests = $cached_tests;
            return $this->active_tests;
        }

        global $wpdb;
        $ab_tests_table = $wpdb->prefix . 'spb_ab_tests';
        
        $tests = $wpdb->get_results(
            "SELECT * FROM {$ab_tests_table} 
             WHERE status = 'active' 
             ORDER BY created_at DESC"
        );

        $active_tests = array();
        foreach ($tests as $test) {
            $test->config = json_decode($test->config, true);
            $test->variants = $this->get_test_variants($test->id);
            $active_tests[] = $test;
        }

        $this->active_tests = $active_tests;
        $this->cache_manager->set($cache_key, $active_tests, 300); // Cache for 5 minutes

        return $this->active_tests;
    }

    /**
     * Apply template test variation
     *
     * @since    2.0.0
     * @param    string   $template       Current template
     * @param    array    $context        Generation context
     * @return   string   Modified template
     */
    public function apply_template_test($template, $context) {
        $active_tests = $this->get_active_tests();
        
        foreach ($active_tests as $test) {
            if ($test->test_type === 'template' && $this->should_include_in_test($test, $context)) {
                $variant = $this->assign_test_variant($test, $context);
                if ($variant && isset($variant->config['template'])) {
                    return $variant->config['template'];
                }
            }
        }

        return $template;
    }

    /**
     * Apply algorithm test variation
     *
     * @since    2.0.0
     * @param    string   $algorithm      Current algorithm
     * @param    array    $context        Generation context
     * @return   string   Modified algorithm
     */
    public function apply_algorithm_test($algorithm, $context) {
        $active_tests = $this->get_active_tests();
        
        foreach ($active_tests as $test) {
            if ($test->test_type === 'algorithm' && $this->should_include_in_test($test, $context)) {
                $variant = $this->assign_test_variant($test, $context);
                if ($variant && isset($variant->config['algorithm'])) {
                    return $variant->config['algorithm'];
                }
            }
        }

        return $algorithm;
    }

    /**
     * Apply confidence threshold test variation
     *
     * @since    2.0.0
     * @param    float    $threshold      Current threshold
     * @param    array    $context        Generation context
     * @return   float    Modified threshold
     */
    public function apply_confidence_test($threshold, $context) {
        $active_tests = $this->get_active_tests();
        
        foreach ($active_tests as $test) {
            if ($test->test_type === 'confidence' && $this->should_include_in_test($test, $context)) {
                $variant = $this->assign_test_variant($test, $context);
                if ($variant && isset($variant->config['threshold'])) {
                    return floatval($variant->config['threshold']);
                }
            }
        }

        return $threshold;
    }

    /**
     * Track test result
     *
     * @since    2.0.0
     * @param    array    $content_data   Content generation data
     * @param    array    $context        Generation context
     */
    public function track_test_result($content_data, $context = array()) {
        $active_tests = $this->get_active_tests();
        
        foreach ($active_tests as $test) {
            if ($this->should_include_in_test($test, $context)) {
                $variant = $this->get_assigned_variant($test, $context);
                if ($variant) {
                    $this->record_test_event($test->id, $variant->id, 'generation', $content_data);
                }
            }
        }
    }

    /**
     * Track test conversion (approval)
     *
     * @since    2.0.0
     * @param    array    $approval_data  Content approval data
     * @param    array    $context        Generation context
     */
    public function track_test_conversion($approval_data, $context = array()) {
        $active_tests = $this->get_active_tests();
        
        foreach ($active_tests as $test) {
            if ($this->should_include_in_test($test, $context)) {
                $variant = $this->get_assigned_variant($test, $context);
                if ($variant) {
                    $this->record_test_event($test->id, $variant->id, 'conversion', $approval_data);
                }
            }
        }
    }

    /**
     * Track test engagement (page view)
     *
     * @since    2.0.0
     * @param    int      $post_id        Post ID
     * @param    array    $context        View context
     */
    public function track_test_engagement($post_id, $context = array()) {
        // Get post generation context to determine which tests it was part of
        $post_tests = get_post_meta($post_id, '_spb_ab_tests', true);
        
        if (!empty($post_tests) && is_array($post_tests)) {
            foreach ($post_tests as $test_assignment) {
                $this->record_test_event(
                    $test_assignment['test_id'], 
                    $test_assignment['variant_id'], 
                    'engagement', 
                    array('post_id' => $post_id)
                );
            }
        }
    }

    /**
     * Get test results and statistics
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @return   array    Test results
     */
    public function get_test_results($test_id) {
        global $wpdb;
        
        $test = $this->get_test_by_id($test_id);
        if (!$test) {
            return array();
        }

        $variants = $this->get_test_variants($test_id);
        $results = array(
            'test' => $test,
            'variants' => array(),
            'summary' => array(),
            'statistical_significance' => array()
        );

        $ab_results_table = $wpdb->prefix . 'spb_ab_test_results';
        
        foreach ($variants as $variant) {
            // Get variant statistics
            $variant_stats = $wpdb->get_row($wpdb->prepare(
                "SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN event_type = 'generation' THEN 1 END) as generations,
                    COUNT(CASE WHEN event_type = 'conversion' THEN 1 END) as conversions,
                    COUNT(CASE WHEN event_type = 'engagement' THEN 1 END) as engagements,
                    AVG(CASE WHEN event_type = 'generation' AND event_data LIKE '%confidence_score%' 
                        THEN JSON_EXTRACT(event_data, '$.confidence_score') END) as avg_confidence
                 FROM {$ab_results_table} 
                 WHERE test_id = %d AND variant_id = %d",
                $test_id,
                $variant->id
            ));

            $conversion_rate = $variant_stats->generations > 0 
                ? ($variant_stats->conversions / $variant_stats->generations) * 100 
                : 0;

            $engagement_rate = $variant_stats->conversions > 0 
                ? ($variant_stats->engagements / $variant_stats->conversions) * 100 
                : 0;

            $results['variants'][$variant->id] = array(
                'variant' => $variant,
                'stats' => $variant_stats,
                'conversion_rate' => round($conversion_rate, 2),
                'engagement_rate' => round($engagement_rate, 2),
                'avg_confidence' => round(floatval($variant_stats->avg_confidence), 2)
            );
        }

        // Calculate statistical significance
        $results['statistical_significance'] = $this->calculate_statistical_significance($results['variants']);
        
        // Generate summary
        $results['summary'] = $this->generate_test_summary($results);

        return $results;
    }

    /**
     * Stop an A/B test
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @param    string   $reason         Reason for stopping
     * @return   bool     Success status
     */
    public function stop_test($test_id, $reason = '') {
        global $wpdb;
        
        $ab_tests_table = $wpdb->prefix . 'spb_ab_tests';
        
        $result = $wpdb->update(
            $ab_tests_table,
            array(
                'status' => 'stopped',
                'end_date' => current_time('mysql'),
                'stop_reason' => sanitize_text_field($reason)
            ),
            array('id' => $test_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            // Clear cache
            $this->active_tests = null;
            $this->cache_manager->delete('spb_active_ab_tests');
            
            // Log test stop
            do_action('spb_ab_test_stopped', $test_id, $reason);
            
            return true;
        }

        return false;
    }

    /**
     * Validate test configuration
     *
     * @since    2.0.0
     * @param    array    $config         Test configuration
     * @return   bool|WP_Error           Validation result
     */
    private function validate_test_config($config) {
        $required_fields = array('name', 'test_type', 'variants');
        
        foreach ($required_fields as $field) {
            if (empty($config[$field])) {
                return new WP_Error('missing_field', "Required field '{$field}' is missing");
            }
        }

        $valid_types = array('template', 'algorithm', 'confidence');
        if (!in_array($config['test_type'], $valid_types)) {
            return new WP_Error('invalid_type', 'Invalid test type');
        }

        if (!is_array($config['variants']) || count($config['variants']) < 2) {
            return new WP_Error('invalid_variants', 'At least 2 variants are required');
        }

        return true;
    }

    /**
     * Create test variants
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @param    array    $variants       Variant configurations
     */
    private function create_test_variants($test_id, $variants) {
        global $wpdb;
        
        $ab_variants_table = $wpdb->prefix . 'spb_ab_test_variants';
        
        foreach ($variants as $index => $variant) {
            $variant_data = array(
                'test_id' => $test_id,
                'name' => sanitize_text_field($variant['name']),
                'description' => sanitize_textarea_field($variant['description'] ?? ''),
                'config' => wp_json_encode($variant['config']),
                'traffic_allocation' => floatval($variant['traffic_allocation'] ?? (100 / count($variants))),
                'is_control' => $index === 0 ? 1 : 0,
                'created_at' => current_time('mysql')
            );

            $wpdb->insert($ab_variants_table, $variant_data);
        }
    }

    /**
     * Get test variants
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @return   array    Test variants
     */
    private function get_test_variants($test_id) {
        global $wpdb;
        
        $ab_variants_table = $wpdb->prefix . 'spb_ab_test_variants';
        
        $variants = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$ab_variants_table} WHERE test_id = %d ORDER BY is_control DESC, id ASC",
            $test_id
        ));

        foreach ($variants as $variant) {
            $variant->config = json_decode($variant->config, true);
        }

        return $variants;
    }

    /**
     * Check if context should be included in test
     *
     * @since    2.0.0
     * @param    object   $test           Test object
     * @param    array    $context        Generation context
     * @return   bool     Should include
     */
    private function should_include_in_test($test, $context) {
        // Check if test has reached sample size
        if ($this->has_reached_sample_size($test)) {
            return false;
        }

        // Check targeting criteria if configured
        if (!empty($test->config['targeting'])) {
            return $this->matches_targeting_criteria($test->config['targeting'], $context);
        }

        return true;
    }

    /**
     * Assign test variant to context
     *
     * @since    2.0.0
     * @param    object   $test           Test object
     * @param    array    $context        Generation context
     * @return   object|null              Assigned variant
     */
    private function assign_test_variant($test, $context) {
        // Check if already assigned
        $existing_assignment = $this->get_assigned_variant($test, $context);
        if ($existing_assignment) {
            return $existing_assignment;
        }

        // Assign based on traffic allocation
        $variants = $test->variants;
        $random = mt_rand(1, 100);
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->traffic_allocation;
            if ($random <= $cumulative) {
                $this->store_variant_assignment($test->id, $variant->id, $context);
                return $variant;
            }
        }

        // Fallback to control variant
        return $variants[0] ?? null;
    }

    /**
     * Get assigned variant for context
     *
     * @since    2.0.0
     * @param    object   $test           Test object
     * @param    array    $context        Generation context
     * @return   object|null              Assigned variant
     */
    private function get_assigned_variant($test, $context) {
        $assignment_key = $this->get_assignment_key($test->id, $context);
        $cached_assignment = $this->cache_manager->get($assignment_key);
        
        if ($cached_assignment !== false) {
            foreach ($test->variants as $variant) {
                if ($variant->id == $cached_assignment) {
                    return $variant;
                }
            }
        }

        return null;
    }

    /**
     * Store variant assignment
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @param    int      $variant_id     Variant ID
     * @param    array    $context        Generation context
     */
    private function store_variant_assignment($test_id, $variant_id, $context) {
        $assignment_key = $this->get_assignment_key($test_id, $context);
        $this->cache_manager->set($assignment_key, $variant_id, 86400); // Cache for 24 hours
    }

    /**
     * Get assignment cache key
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @param    array    $context        Generation context
     * @return   string   Cache key
     */
    private function get_assignment_key($test_id, $context) {
        $identifier = $context['user_id'] ?? $context['session_id'] ?? $context['ip_address'] ?? 'anonymous';
        return "spb_ab_assignment_{$test_id}_" . md5($identifier);
    }

    /**
     * Record test event
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @param    int      $variant_id     Variant ID
     * @param    string   $event_type     Event type
     * @param    array    $event_data     Event data
     */
    private function record_test_event($test_id, $variant_id, $event_type, $event_data) {
        global $wpdb;
        
        $ab_results_table = $wpdb->prefix . 'spb_ab_test_results';
        
        $wpdb->insert(
            $ab_results_table,
            array(
                'test_id' => $test_id,
                'variant_id' => $variant_id,
                'event_type' => $event_type,
                'event_data' => wp_json_encode($event_data),
                'timestamp' => current_time('mysql')
            )
        );
    }

    /**
     * Calculate statistical significance
     *
     * @since    2.0.0
     * @param    array    $variants       Variant results
     * @return   array    Significance data
     */
    private function calculate_statistical_significance($variants) {
        if (count($variants) < 2) {
            return array();
        }

        $control = null;
        $variations = array();

        foreach ($variants as $variant_id => $variant_data) {
            if ($variant_data['variant']->is_control) {
                $control = $variant_data;
            } else {
                $variations[] = $variant_data;
            }
        }

        if (!$control) {
            return array();
        }

        $significance_results = array();

        foreach ($variations as $variation) {
            $significance = $this->calculate_z_test(
                $control['stats']->conversions,
                $control['stats']->generations,
                $variation['stats']->conversions,
                $variation['stats']->generations
            );

            $significance_results[$variation['variant']->id] = array(
                'z_score' => $significance['z_score'],
                'p_value' => $significance['p_value'],
                'is_significant' => $significance['p_value'] < 0.05,
                'confidence_level' => (1 - $significance['p_value']) * 100,
                'improvement' => $significance['improvement']
            );
        }

        return $significance_results;
    }

    /**
     * Calculate Z-test for conversion rates
     *
     * @since    2.0.0
     * @param    int      $control_conversions    Control conversions
     * @param    int      $control_total          Control total
     * @param    int      $variant_conversions    Variant conversions
     * @param    int      $variant_total          Variant total
     * @return   array    Z-test results
     */
    private function calculate_z_test($control_conversions, $control_total, $variant_conversions, $variant_total) {
        if ($control_total == 0 || $variant_total == 0) {
            return array('z_score' => 0, 'p_value' => 1, 'improvement' => 0);
        }

        $p1 = $control_conversions / $control_total;
        $p2 = $variant_conversions / $variant_total;
        
        $p_pooled = ($control_conversions + $variant_conversions) / ($control_total + $variant_total);
        $se = sqrt($p_pooled * (1 - $p_pooled) * (1/$control_total + 1/$variant_total));
        
        if ($se == 0) {
            return array('z_score' => 0, 'p_value' => 1, 'improvement' => 0);
        }

        $z_score = ($p2 - $p1) / $se;
        $p_value = 2 * (1 - $this->standard_normal_cdf(abs($z_score)));
        $improvement = $p1 > 0 ? (($p2 - $p1) / $p1) * 100 : 0;

        return array(
            'z_score' => round($z_score, 4),
            'p_value' => round($p_value, 4),
            'improvement' => round($improvement, 2)
        );
    }

    /**
     * Standard normal cumulative distribution function
     *
     * @since    2.0.0
     * @param    float    $x              Input value
     * @return   float    CDF value
     */
    private function standard_normal_cdf($x) {
        return 0.5 * (1 + $this->error_function($x / sqrt(2)));
    }

    /**
     * Error function approximation
     *
     * @since    2.0.0
     * @param    float    $x              Input value
     * @return   float    Error function value
     */
    private function error_function($x) {
        $a1 =  0.254829592;
        $a2 = -0.284496736;
        $a3 =  1.421413741;
        $a4 = -1.453152027;
        $a5 =  1.061405429;
        $p  =  0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return $sign * $y;
    }

    /**
     * Generate test summary
     *
     * @since    2.0.0
     * @param    array    $results        Test results
     * @return   array    Test summary
     */
    private function generate_test_summary($results) {
        $summary = array(
            'total_participants' => 0,
            'best_variant' => null,
            'winner_confidence' => 0,
            'recommendation' => 'continue'
        );

        $best_conversion_rate = 0;
        $best_variant_id = null;

        foreach ($results['variants'] as $variant_id => $variant_data) {
            $summary['total_participants'] += $variant_data['stats']->generations;
            
            if ($variant_data['conversion_rate'] > $best_conversion_rate) {
                $best_conversion_rate = $variant_data['conversion_rate'];
                $best_variant_id = $variant_id;
            }
        }

        if ($best_variant_id && isset($results['statistical_significance'][$best_variant_id])) {
            $summary['best_variant'] = $best_variant_id;
            $summary['winner_confidence'] = $results['statistical_significance'][$best_variant_id]['confidence_level'];
            
            if ($results['statistical_significance'][$best_variant_id]['is_significant']) {
                $summary['recommendation'] = 'implement_winner';
            }
        }

        return $summary;
    }

    /**
     * Check if test has reached sample size
     *
     * @since    2.0.0
     * @param    object   $test           Test object
     * @return   bool     Has reached sample size
     */
    private function has_reached_sample_size($test) {
        global $wpdb;
        
        $ab_results_table = $wpdb->prefix . 'spb_ab_test_results';
        
        $total_events = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$ab_results_table} 
             WHERE test_id = %d AND event_type = 'generation'",
            $test->id
        ));

        return intval($total_events) >= $test->target_sample_size;
    }

    /**
     * Check if context matches targeting criteria
     *
     * @since    2.0.0
     * @param    array    $targeting      Targeting criteria
     * @param    array    $context        Generation context
     * @return   bool     Matches criteria
     */
    private function matches_targeting_criteria($targeting, $context) {
        // Implement targeting logic based on criteria
        // This could include user segments, content types, time periods, etc.
        return true; // Simplified for now
    }

    /**
     * Get test by ID
     *
     * @since    2.0.0
     * @param    int      $test_id        Test ID
     * @return   object|null              Test object
     */
    private function get_test_by_id($test_id) {
        global $wpdb;
        
        $ab_tests_table = $wpdb->prefix . 'spb_ab_tests';
        
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$ab_tests_table} WHERE id = %d",
            $test_id
        ));

        if ($test) {
            $test->config = json_decode($test->config, true);
        }

        return $test;
    }

    /**
     * Cleanup completed tests
     *
     * @since    2.0.0
     */
    public function cleanup_completed_tests() {
        global $wpdb;
        
        $ab_tests_table = $wpdb->prefix . 'spb_ab_tests';
        $retention_days = apply_filters('spb_ab_test_retention_days', 90);
        
        // Archive old completed tests
        $wpdb->query($wpdb->prepare(
            "UPDATE {$ab_tests_table} 
             SET status = 'archived' 
             WHERE status IN ('completed', 'stopped') 
             AND end_date < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
    }

    /**
     * Handle AJAX request to create A/B test
     *
     * @since    2.0.0
     */
    public function handle_create_test_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_create_ab_test')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_manage_settings')) {
            wp_send_json_error('Insufficient permissions');
        }

        // Validate and sanitize input
        $test_config = array(
            'name' => sanitize_text_field($_POST['test_name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'test_type' => sanitize_text_field($_POST['test_type']),
            'sample_size' => intval($_POST['sample_size'] ?? 100),
            'confidence_level' => floatval($_POST['confidence_level'] ?? 95),
            'variants' => array()
        );

        // Create default variants based on test type
        switch ($test_config['test_type']) {
            case 'template':
                $test_config['variants'] = array(
                    array(
                        'name' => 'Control',
                        'description' => 'Current template',
                        'config' => array('template' => 'current'),
                        'traffic_allocation' => 50
                    ),
                    array(
                        'name' => 'Variation A',
                        'description' => 'New template',
                        'config' => array('template' => 'new'),
                        'traffic_allocation' => 50
                    )
                );
                break;
            case 'algorithm':
                $test_config['variants'] = array(
                    array(
                        'name' => 'TF-IDF',
                        'description' => 'Current TF-IDF algorithm',
                        'config' => array('algorithm' => 'tfidf'),
                        'traffic_allocation' => 50
                    ),
                    array(
                        'name' => 'Enhanced',
                        'description' => 'Enhanced algorithm',
                        'config' => array('algorithm' => 'enhanced'),
                        'traffic_allocation' => 50
                    )
                );
                break;
            case 'confidence':
                $test_config['variants'] = array(
                    array(
                        'name' => 'Standard (60%)',
                        'description' => 'Current confidence threshold',
                        'config' => array('threshold' => 0.6),
                        'traffic_allocation' => 50
                    ),
                    array(
                        'name' => 'Higher (70%)',
                        'description' => 'Higher confidence threshold',
                        'config' => array('threshold' => 0.7),
                        'traffic_allocation' => 50
                    )
                );
                break;
        }

        $test_id = $this->create_test($test_config);

        if (is_wp_error($test_id)) {
            wp_send_json_error($test_id->get_error_message());
        }

        wp_send_json_success(array(
            'test_id' => $test_id,
            'message' => 'A/B test created successfully'
        ));
    }

    /**
     * Handle AJAX request to stop A/B test
     *
     * @since    2.0.0
     */
    public function handle_stop_test_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_stop_ab_test')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_manage_settings')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_id = intval($_POST['test_id']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');

        $success = $this->stop_test($test_id, $reason);

        if ($success) {
            wp_send_json_success(array(
                'message' => 'A/B test stopped successfully'
            ));
        } else {
            wp_send_json_error('Failed to stop A/B test');
        }
    }

    /**
     * Handle AJAX request to get test results
     *
     * @since    2.0.0
     */
    public function handle_get_results_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_get_test_results')) {
            wp_send_json_error('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_view_analytics')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_id = intval($_POST['test_id']);
        $results = $this->get_test_results($test_id);

        if (empty($results)) {
            wp_send_json_error('Test not found or no results available');
        }

        wp_send_json_success($results);
    }
}
