<?php
/**
 * Analytics Manager Class
 *
 * Handles real-time analytics, metrics collection, and performance tracking
 * for the Smart Page Builder plugin.
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
 * Analytics Manager Class
 *
 * Manages analytics data collection, processing, and reporting for generated content.
 * Provides real-time metrics, trend analysis, and performance insights.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_Analytics_Manager {

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
     * Initialize the analytics manager
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->cache_manager = new Smart_Page_Builder_Cache_Manager();
        $this->database = new Smart_Page_Builder_Database();
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since    2.0.0
     * @access   private
     */
    private function init_hooks() {
        // Track page views for generated content
        add_action('wp', array($this, 'track_page_view'));
        
        // Track search queries
        add_action('pre_get_posts', array($this, 'track_search_query'));
        
        // Track content generation events
        add_action('spb_content_generated', array($this, 'track_content_generation'));
        add_action('spb_content_approved', array($this, 'track_content_approval'));
        add_action('spb_content_rejected', array($this, 'track_content_rejection'));
        
        // Schedule analytics cleanup
        add_action('spb_analytics_cleanup', array($this, 'cleanup_old_analytics'));
        
        if (!wp_next_scheduled('spb_analytics_cleanup')) {
            wp_schedule_event(time(), 'daily', 'spb_analytics_cleanup');
        }
    }

    /**
     * Track page view for generated content
     *
     * @since    2.0.0
     */
    public function track_page_view() {
        if (!is_singular('spb_dynamic_page')) {
            return;
        }

        global $post;
        
        $analytics_data = array(
            'post_id' => $post->ID,
            'event_type' => 'page_view',
            'timestamp' => current_time('mysql'),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'ip_address' => $this->get_client_ip(),
            'referrer' => sanitize_url($_SERVER['HTTP_REFERER'] ?? ''),
            'session_id' => $this->get_session_id()
        );

        $this->record_analytics_event($analytics_data);
        $this->update_real_time_metrics($post->ID);
    }

    /**
     * Track search queries that don't return results
     *
     * @since    2.0.0
     * @param    WP_Query    $query    The WordPress query object
     */
    public function track_search_query($query) {
        if (!$query->is_search() || !$query->is_main_query()) {
            return;
        }

        $search_term = get_search_query();
        if (empty($search_term)) {
            return;
        }

        // Check if this search returns results
        $has_results = $query->found_posts > 0;

        $analytics_data = array(
            'event_type' => 'search_query',
            'search_term' => sanitize_text_field($search_term),
            'has_results' => $has_results,
            'result_count' => $query->found_posts,
            'timestamp' => current_time('mysql'),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'ip_address' => $this->get_client_ip(),
            'session_id' => $this->get_session_id()
        );

        $this->record_analytics_event($analytics_data);
        
        // Track content gaps (searches with no results)
        if (!$has_results) {
            $this->track_content_gap($search_term);
        }
    }

    /**
     * Track content generation events
     *
     * @since    2.0.0
     * @param    array    $content_data    Content generation data
     */
    public function track_content_generation($content_data) {
        $analytics_data = array(
            'event_type' => 'content_generated',
            'search_term' => sanitize_text_field($content_data['search_term'] ?? ''),
            'content_type' => sanitize_text_field($content_data['content_type'] ?? ''),
            'confidence_score' => floatval($content_data['confidence_score'] ?? 0),
            'generation_time' => floatval($content_data['generation_time'] ?? 0),
            'word_count' => intval($content_data['word_count'] ?? 0),
            'source_count' => intval($content_data['source_count'] ?? 0),
            'timestamp' => current_time('mysql')
        );

        $this->record_analytics_event($analytics_data);
        $this->update_generation_metrics($content_data);
    }

    /**
     * Track content approval events
     *
     * @since    2.0.0
     * @param    array    $approval_data    Content approval data
     */
    public function track_content_approval($approval_data) {
        $analytics_data = array(
            'post_id' => intval($approval_data['post_id'] ?? 0),
            'event_type' => 'content_approved',
            'approval_time' => floatval($approval_data['approval_time'] ?? 0),
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        );

        $this->record_analytics_event($analytics_data);
        $this->update_approval_metrics($approval_data);
    }

    /**
     * Track content rejection events
     *
     * @since    2.0.0
     * @param    array    $rejection_data    Content rejection data
     */
    public function track_content_rejection($rejection_data) {
        $analytics_data = array(
            'post_id' => intval($rejection_data['post_id'] ?? 0),
            'event_type' => 'content_rejected',
            'rejection_reason' => sanitize_text_field($rejection_data['reason'] ?? ''),
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        );

        $this->record_analytics_event($analytics_data);
        $this->update_rejection_metrics($rejection_data);
    }

    /**
     * Get real-time analytics dashboard data
     *
     * @since    2.0.0
     * @return   array    Dashboard analytics data
     */
    public function get_dashboard_analytics() {
        $cache_key = 'spb_dashboard_analytics';
        $cached_data = $this->cache_manager->get($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        $today = current_time('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        $month_ago = date('Y-m-d', strtotime('-30 days'));

        // Get today's metrics
        $today_metrics = $this->get_period_metrics($today, $today);
        
        // Get weekly metrics
        $weekly_metrics = $this->get_period_metrics($week_ago, $today);
        
        // Get monthly metrics
        $monthly_metrics = $this->get_period_metrics($month_ago, $today);

        // Get top performing content
        $top_content = $this->get_top_performing_content(10);
        
        // Get content gaps
        $content_gaps = $this->get_content_gaps(10);
        
        // Get approval rates
        $approval_rates = $this->get_approval_rates();

        $dashboard_data = array(
            'today' => $today_metrics,
            'weekly' => $weekly_metrics,
            'monthly' => $monthly_metrics,
            'top_content' => $top_content,
            'content_gaps' => $content_gaps,
            'approval_rates' => $approval_rates,
            'last_updated' => current_time('mysql')
        );

        // Cache for 5 minutes
        $this->cache_manager->set($cache_key, $dashboard_data, 300);
        
        return $dashboard_data;
    }

    /**
     * Get metrics for a specific time period
     *
     * @since    2.0.0
     * @param    string    $start_date    Start date (Y-m-d format)
     * @param    string    $end_date      End date (Y-m-d format)
     * @return   array     Period metrics
     */
    private function get_period_metrics($start_date, $end_date) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        
        // Page views
        $page_views = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$analytics_table} 
             WHERE event_type = 'page_view' 
             AND DATE(timestamp) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Search queries
        $search_queries = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$analytics_table} 
             WHERE event_type = 'search_query' 
             AND DATE(timestamp) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Content generated
        $content_generated = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$analytics_table} 
             WHERE event_type = 'content_generated' 
             AND DATE(timestamp) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Content approved
        $content_approved = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$analytics_table} 
             WHERE event_type = 'content_approved' 
             AND DATE(timestamp) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        // Average confidence score
        $avg_confidence = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(confidence_score) FROM {$analytics_table} 
             WHERE event_type = 'content_generated' 
             AND confidence_score > 0
             AND DATE(timestamp) BETWEEN %s AND %s",
            $start_date,
            $end_date
        ));

        return array(
            'page_views' => intval($page_views),
            'search_queries' => intval($search_queries),
            'content_generated' => intval($content_generated),
            'content_approved' => intval($content_approved),
            'approval_rate' => $content_generated > 0 ? round(($content_approved / $content_generated) * 100, 2) : 0,
            'avg_confidence' => round(floatval($avg_confidence), 2)
        );
    }

    /**
     * Get top performing content
     *
     * @since    2.0.0
     * @param    int      $limit    Number of results to return
     * @return   array    Top performing content
     */
    private function get_top_performing_content($limit = 10) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, COUNT(*) as view_count
             FROM {$analytics_table} 
             WHERE event_type = 'page_view' 
             AND post_id > 0
             AND DATE(timestamp) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY post_id 
             ORDER BY view_count DESC 
             LIMIT %d",
            $limit
        ));

        $top_content = array();
        foreach ($results as $result) {
            $post = get_post($result->post_id);
            if ($post) {
                $top_content[] = array(
                    'post_id' => $result->post_id,
                    'title' => $post->post_title,
                    'view_count' => intval($result->view_count),
                    'url' => get_permalink($result->post_id)
                );
            }
        }

        return $top_content;
    }

    /**
     * Get content gaps (searches with no results)
     *
     * @since    2.0.0
     * @param    int      $limit    Number of results to return
     * @return   array    Content gaps
     */
    private function get_content_gaps($limit = 10) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT search_term, COUNT(*) as search_count
             FROM {$analytics_table} 
             WHERE event_type = 'search_query' 
             AND has_results = 0
             AND DATE(timestamp) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY search_term 
             ORDER BY search_count DESC 
             LIMIT %d",
            $limit
        ));

        $content_gaps = array();
        foreach ($results as $result) {
            $content_gaps[] = array(
                'search_term' => $result->search_term,
                'search_count' => intval($result->search_count),
                'opportunity_score' => $this->calculate_opportunity_score($result->search_term, $result->search_count)
            );
        }

        return $content_gaps;
    }

    /**
     * Get approval rates by content type
     *
     * @since    2.0.0
     * @return   array    Approval rates
     */
    private function get_approval_rates() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        
        $results = $wpdb->get_results(
            "SELECT 
                content_type,
                COUNT(CASE WHEN event_type = 'content_generated' THEN 1 END) as generated,
                COUNT(CASE WHEN event_type = 'content_approved' THEN 1 END) as approved
             FROM {$analytics_table} 
             WHERE content_type IS NOT NULL
             AND DATE(timestamp) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY content_type"
        );

        $approval_rates = array();
        foreach ($results as $result) {
            $approval_rate = $result->generated > 0 ? round(($result->approved / $result->generated) * 100, 2) : 0;
            $approval_rates[] = array(
                'content_type' => $result->content_type,
                'generated' => intval($result->generated),
                'approved' => intval($result->approved),
                'approval_rate' => $approval_rate
            );
        }

        return $approval_rates;
    }

    /**
     * Record an analytics event
     *
     * @since    2.0.0
     * @param    array    $analytics_data    Analytics event data
     * @return   bool     Success status
     */
    private function record_analytics_event($analytics_data) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        
        $result = $wpdb->insert(
            $analytics_table,
            $analytics_data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%d', '%d', '%d', '%s')
        );

        return $result !== false;
    }

    /**
     * Update real-time metrics cache
     *
     * @since    2.0.0
     * @param    int      $post_id    Post ID
     */
    private function update_real_time_metrics($post_id) {
        $cache_key = "spb_realtime_metrics_{$post_id}";
        $current_views = $this->cache_manager->get($cache_key);
        
        if ($current_views === false) {
            $current_views = 0;
        }
        
        $current_views++;
        $this->cache_manager->set($cache_key, $current_views, 3600); // Cache for 1 hour
    }

    /**
     * Track content gap
     *
     * @since    2.0.0
     * @param    string   $search_term    Search term with no results
     */
    private function track_content_gap($search_term) {
        $cache_key = "spb_content_gap_" . md5($search_term);
        $gap_count = $this->cache_manager->get($cache_key);
        
        if ($gap_count === false) {
            $gap_count = 0;
        }
        
        $gap_count++;
        $this->cache_manager->set($cache_key, $gap_count, 86400); // Cache for 24 hours
        
        // If this gap has been hit multiple times, consider it for content generation
        if ($gap_count >= 3) {
            do_action('spb_content_gap_identified', $search_term, $gap_count);
        }
    }

    /**
     * Update generation metrics
     *
     * @since    2.0.0
     * @param    array    $content_data    Content generation data
     */
    private function update_generation_metrics($content_data) {
        // Update average generation time
        $cache_key = 'spb_avg_generation_time';
        $current_avg = $this->cache_manager->get($cache_key);
        
        if ($current_avg === false) {
            $current_avg = array('total_time' => 0, 'count' => 0);
        }
        
        $current_avg['total_time'] += floatval($content_data['generation_time'] ?? 0);
        $current_avg['count']++;
        
        $this->cache_manager->set($cache_key, $current_avg, 3600);
    }

    /**
     * Update approval metrics
     *
     * @since    2.0.0
     * @param    array    $approval_data    Content approval data
     */
    private function update_approval_metrics($approval_data) {
        // Update approval rate cache
        $cache_key = 'spb_approval_rate';
        $current_rate = $this->cache_manager->get($cache_key);
        
        if ($current_rate === false) {
            $current_rate = array('approved' => 0, 'total' => 0);
        }
        
        $current_rate['approved']++;
        $current_rate['total']++;
        
        $this->cache_manager->set($cache_key, $current_rate, 3600);
    }

    /**
     * Update rejection metrics
     *
     * @since    2.0.0
     * @param    array    $rejection_data    Content rejection data
     */
    private function update_rejection_metrics($rejection_data) {
        // Update rejection rate cache
        $cache_key = 'spb_approval_rate';
        $current_rate = $this->cache_manager->get($cache_key);
        
        if ($current_rate === false) {
            $current_rate = array('approved' => 0, 'total' => 0);
        }
        
        $current_rate['total']++;
        
        $this->cache_manager->set($cache_key, $current_rate, 3600);
    }

    /**
     * Calculate opportunity score for content gaps
     *
     * @since    2.0.0
     * @param    string   $search_term     Search term
     * @param    int      $search_count    Number of searches
     * @return   float    Opportunity score
     */
    private function calculate_opportunity_score($search_term, $search_count) {
        // Base score from search frequency
        $frequency_score = min($search_count * 10, 100);
        
        // Bonus for longer, more specific terms
        $term_length = str_word_count($search_term);
        $specificity_bonus = min($term_length * 5, 25);
        
        // Bonus for question-like terms
        $question_bonus = 0;
        $question_words = array('how', 'what', 'why', 'when', 'where', 'which');
        foreach ($question_words as $word) {
            if (stripos($search_term, $word) !== false) {
                $question_bonus = 15;
                break;
            }
        }
        
        return min($frequency_score + $specificity_bonus + $question_bonus, 100);
    }

    /**
     * Get client IP address
     *
     * @since    2.0.0
     * @return   string   Client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? '');
    }

    /**
     * Get or create session ID
     *
     * @since    2.0.0
     * @return   string   Session ID
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        
        return session_id();
    }

    /**
     * Clean up old analytics data
     *
     * @since    2.0.0
     */
    public function cleanup_old_analytics() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        $retention_days = apply_filters('spb_analytics_retention_days', 90);
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$analytics_table} 
             WHERE timestamp < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $retention_days
        ));
    }
}
