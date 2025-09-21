<?php
/**
 * Fired during plugin activation
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Add custom capabilities
        self::add_capabilities();
        
        // Schedule cron jobs
        self::schedule_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        if (defined('SPB_DEBUG') && SPB_DEBUG) {
            error_log('Smart Page Builder activated successfully');
        }
    }

    /**
     * Create custom database tables
     *
     * @since    1.0.0
     * @access   private
     */
    private static function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // AI Insights table
        $table_name = $wpdb->prefix . 'spb_ai_insights';
        $sql = "CREATE TABLE $table_name (
            insight_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(50) NOT NULL,
            query_term TEXT NOT NULL,
            related_post_ids JSON,
            confidence_score DECIMAL(5,4) NOT NULL DEFAULT 0.0000,
            search_volume INT(11) DEFAULT 0,
            trend_direction ENUM('up', 'down', 'stable') DEFAULT 'stable',
            seasonal_pattern JSON,
            last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (insight_id),
            INDEX idx_type (type),
            INDEX idx_query_term (query_term(100)),
            INDEX idx_confidence (confidence_score)
        ) $charset_collate;";

        // Dynamic Rules table
        $table_name = $wpdb->prefix . 'spb_dynamic_rules';
        $sql .= "CREATE TABLE $table_name (
            rule_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            rule_name VARCHAR(255) NOT NULL,
            trigger_query TEXT NOT NULL,
            trigger_type ENUM('exact', 'contains', 'regex') DEFAULT 'contains',
            template_id VARCHAR(100) NOT NULL,
            content_sources JSON,
            seo_settings JSON,
            status ENUM('active', 'inactive', 'testing') DEFAULT 'active',
            priority INT(11) DEFAULT 10,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (rule_id),
            INDEX idx_status (status),
            INDEX idx_priority (priority)
        ) $charset_collate;";

        // A/B Tests table
        $table_name = $wpdb->prefix . 'spb_ab_tests';
        $sql .= "CREATE TABLE $table_name (
            test_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_name VARCHAR(255) NOT NULL,
            rule_id BIGINT(20) UNSIGNED NOT NULL,
            variant_a_template VARCHAR(100) NOT NULL,
            variant_b_template VARCHAR(100) NOT NULL,
            traffic_split DECIMAL(3,2) DEFAULT 0.50,
            start_date DATETIME NOT NULL,
            end_date DATETIME,
            status ENUM('draft', 'running', 'paused', 'completed') DEFAULT 'draft',
            winner ENUM('a', 'b', 'inconclusive') NULL,
            confidence_level DECIMAL(5,4) DEFAULT 0.0000,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (test_id),
            INDEX idx_status (status),
            FOREIGN KEY (rule_id) REFERENCES {$wpdb->prefix}spb_dynamic_rules(rule_id)
        ) $charset_collate;";

        // Metrics table
        $table_name = $wpdb->prefix . 'spb_metrics';
        $sql .= "CREATE TABLE $table_name (
            metric_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            page_url VARCHAR(500) NOT NULL,
            rule_id BIGINT(20) UNSIGNED,
            test_id BIGINT(20) UNSIGNED,
            variant ENUM('control', 'a', 'b') DEFAULT 'control',
            metric_type VARCHAR(50) NOT NULL,
            metric_value DECIMAL(10,4) NOT NULL,
            user_session VARCHAR(100),
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (metric_id),
            INDEX idx_page_url (page_url(100)),
            INDEX idx_metric_type (metric_type),
            INDEX idx_timestamp (timestamp)
        ) $charset_collate;";

        // Generated Content table
        $table_name = $wpdb->prefix . 'spb_generated_content';
        $sql .= "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key VARCHAR(255) NOT NULL,
            content_type VARCHAR(100) NOT NULL,
            content_html LONGTEXT,
            content_text LONGTEXT,
            context_data LONGTEXT,
            generation_time DECIMAL(10,3),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME,
            user_id BIGINT(20) UNSIGNED,
            ip_address VARCHAR(45),
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key),
            KEY content_type (content_type),
            KEY expires_at (expires_at),
            KEY user_id (user_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default plugin options
     *
     * @since    1.0.0
     * @access   private
     */
    private static function set_default_options() {
        $default_options = array(
            'spb_cache_enabled' => true,
            'spb_cache_duration' => 3600,
            'spb_min_user_role' => 'author',
            'spb_content_moderation' => false,
            'spb_auto_generate' => true,
            'spb_confidence_threshold' => 0.6,
            'spb_max_content_length' => 2000,
            'spb_debug_mode' => false,
            'spb_data_retention_days' => 30,
            'spb_rate_limit_requests' => 100,
            'spb_rate_limit_window' => 3600
        );

        foreach ($default_options as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
            }
        }
    }

    /**
     * Add custom capabilities to user roles
     *
     * @since    1.0.0
     * @access   private
     */
    private static function add_capabilities() {
        $admin_role = get_role('administrator');
        $editor_role = get_role('editor');

        if ($admin_role) {
            $admin_role->add_cap('spb_manage_settings');
            $admin_role->add_cap('spb_generate_content');
            $admin_role->add_cap('spb_view_analytics');
            $admin_role->add_cap('spb_approve_content');
        }

        if ($editor_role) {
            $editor_role->add_cap('spb_generate_content');
            $editor_role->add_cap('spb_approve_content');
        }
    }

    /**
     * Schedule cron jobs
     *
     * @since    1.0.0
     * @access   private
     */
    private static function schedule_cron_jobs() {
        // Schedule daily cleanup
        if (!wp_next_scheduled('spb_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'spb_daily_cleanup');
        }

        // Schedule hourly cache cleanup
        if (!wp_next_scheduled('spb_hourly_cache_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'spb_hourly_cache_cleanup');
        }
    }
}
