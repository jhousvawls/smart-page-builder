<?php
/**
 * Fired during plugin activation
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Activator Class
 *
 * Fired during plugin activation.
 */
class Smart_Page_Builder_Activator {

    /**
     * Plugin activation handler
     *
     * @since    3.0.0
     */
    public static function activate() {
        // Set activation flag
        set_transient('spb_activation_notice', true, 30);
        
        // Create database tables
        self::create_database_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create upload directories
        self::create_upload_directories();
        
        // Schedule cron events
        self::schedule_cron_events();
        
        // Flush rewrite rules
        self::flush_rewrite_rules();
        
        // Check system requirements
        self::check_system_requirements();
        
        // Initialize v3.0 personalization if available
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            self::activate_personalization_features();
        }
        
        // Initialize v3.1 search generation if available
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            self::activate_search_generation_features();
        }
        
        // Update version
        update_option('spb_version', SPB_VERSION);
        update_option('spb_activation_time', current_time('timestamp'));
    }

    /**
     * Create database tables
     *
     * @since    3.0.0
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Core plugin tables
        $tables = array();
        
        // Component storage table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_components (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            component_type varchar(50) NOT NULL,
            component_data longtext NOT NULL,
            component_hash varchar(64) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY component_type (component_type),
            KEY component_hash (component_hash)
        ) $charset_collate;";
        
        // Page templates table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_page_templates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            template_name varchar(100) NOT NULL,
            template_type varchar(50) NOT NULL,
            template_data longtext NOT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_type (template_type),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Analytics table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_analytics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Search pages table (Phase 1)
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_search_pages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_query varchar(255) NOT NULL,
            page_title varchar(255) NOT NULL,
            page_content longtext NOT NULL,
            page_slug varchar(200) NOT NULL,
            page_status varchar(20) DEFAULT 'pending',
            quality_score decimal(3,2) DEFAULT NULL,
            approval_status varchar(20) DEFAULT 'pending',
            approved_by bigint(20) unsigned DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY page_slug (page_slug),
            KEY search_query (search_query),
            KEY page_status (page_status),
            KEY approval_status (approval_status),
            KEY quality_score (quality_score)
        ) $charset_collate;";
        
        // Query enhancements table (Phase 1)
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_query_enhancements (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            original_query varchar(255) NOT NULL,
            enhanced_query varchar(500) NOT NULL,
            enhancement_type varchar(50) NOT NULL,
            confidence_score decimal(3,2) DEFAULT NULL,
            used_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY original_query (original_query),
            KEY enhancement_type (enhancement_type),
            KEY confidence_score (confidence_score)
        ) $charset_collate;";
        
        // Generated components table (Phase 2)
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_generated_components (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_page_id bigint(20) unsigned NOT NULL,
            component_type varchar(50) NOT NULL,
            component_content longtext NOT NULL,
            ai_provider varchar(50) NOT NULL,
            generation_time decimal(4,2) DEFAULT NULL,
            quality_score decimal(3,2) DEFAULT NULL,
            personalization_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY search_page_id (search_page_id),
            KEY component_type (component_type),
            KEY ai_provider (ai_provider),
            KEY quality_score (quality_score),
            FOREIGN KEY (search_page_id) REFERENCES {$wpdb->prefix}spb_search_pages(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Content approvals table (Phase 2) - removed foreign key constraint for compatibility
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_content_approvals (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_query varchar(255) NOT NULL,
            generated_content longtext NOT NULL,
            content_type varchar(50) NOT NULL DEFAULT 'page',
            quality_score decimal(3,2) DEFAULT NULL,
            confidence_score decimal(3,2) DEFAULT NULL,
            status varchar(20) DEFAULT 'pending_review',
            priority varchar(20) DEFAULT 'normal',
            assigned_to bigint(20) unsigned DEFAULT NULL,
            reviewed_by bigint(20) unsigned DEFAULT NULL,
            reviewed_at datetime DEFAULT NULL,
            approval_notes text DEFAULT NULL,
            rejection_reason varchar(100) DEFAULT NULL,
            ai_provider varchar(50) DEFAULT NULL,
            generation_metadata longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY search_query (search_query),
            KEY status (status),
            KEY priority (priority),
            KEY quality_score (quality_score),
            KEY confidence_score (confidence_score),
            KEY assigned_to (assigned_to),
            KEY reviewed_by (reviewed_by),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $table) {
            dbDelta($table);
        }
        
        // Manually ensure content approvals table exists (fallback)
        self::ensure_content_approvals_table();
    }

    /**
     * Create v3.0 personalization tables
     *
     * @since    3.0.0
     */
    private static function create_personalization_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array();
        
        // User interest vectors table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_user_interest_vectors (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            interest_category varchar(100) NOT NULL,
            interest_score decimal(5,4) NOT NULL DEFAULT 0.0000,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_interest (user_id, session_id, interest_category),
            KEY interest_category (interest_category),
            KEY interest_score (interest_score)
        ) $charset_collate;";
        
        // User signals table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_user_signals (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            signal_type varchar(50) NOT NULL,
            signal_data longtext NOT NULL,
            signal_weight decimal(3,2) DEFAULT 1.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY signal_type (signal_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Personalization rules table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_personalization_rules (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            rule_name varchar(100) NOT NULL,
            rule_type varchar(50) NOT NULL,
            conditions longtext NOT NULL,
            actions longtext NOT NULL,
            priority int(11) DEFAULT 10,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY rule_type (rule_type),
            KEY priority (priority),
            KEY is_active (is_active)
        ) $charset_collate;";
        
        // Component variants table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_component_variants (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            component_id bigint(20) unsigned NOT NULL,
            variant_name varchar(100) NOT NULL,
            variant_data longtext NOT NULL,
            target_interests longtext DEFAULT NULL,
            performance_score decimal(5,4) DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY component_id (component_id),
            KEY is_active (is_active),
            KEY performance_score (performance_score)
        ) $charset_collate;";
        
        // User consent table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}spb_user_consent (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT NULL,
            session_id varchar(100) DEFAULT NULL,
            consent_type varchar(50) NOT NULL,
            consent_given tinyint(1) NOT NULL DEFAULT 0,
            consent_data longtext DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_consent (user_id, session_id, consent_type),
            KEY consent_type (consent_type),
            KEY consent_given (consent_given)
        ) $charset_collate;";
        
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($tables as $table) {
            dbDelta($table);
        }
    }

    /**
     * Set default plugin options
     *
     * @since    3.0.0
     */
    private static function set_default_options() {
        // General settings
        $general_defaults = array(
            'enable_analytics' => true,
            'enable_caching' => true,
            'cache_duration' => 3600,
            'enable_compression' => true,
            'debug_mode' => false
        );
        add_option('spb_general_options', $general_defaults);
        
        // WP Engine settings
        $wpengine_defaults = array(
            'api_key' => '',
            'api_secret' => '',
            'environment' => 'production',
            'enable_smart_search' => true,
            'enable_vector_database' => true,
            'enable_recommendations' => true
        );
        add_option('spb_wpengine_options', $wpengine_defaults);
        
        // Personalization settings (v3.0)
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            $personalization_defaults = array(
                'enable_tracking' => true,
                'enable_personalization' => true,
                'interest_decay_rate' => 0.1,
                'min_confidence_threshold' => 0.3,
                'max_variants_per_component' => 5,
                'enable_privacy_mode' => false
            );
            add_option('spb_personalization_options', $personalization_defaults);
        }
        
        // Search generation settings (v3.1)
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            $search_generation_defaults = array(
                'enable_auto_generation' => true,
                'auto_approval_threshold' => 0.8,
                'max_generation_time' => 30,
                'enable_quality_checks' => true,
                'require_manual_approval' => false
            );
            add_option('spb_search_generation_options', $search_generation_defaults);
        }
    }

    /**
     * Create upload directories
     *
     * @since    3.0.0
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $spb_dir = $upload_dir['basedir'] . '/smart-page-builder';
        
        $directories = array(
            $spb_dir,
            $spb_dir . '/cache',
            $spb_dir . '/templates',
            $spb_dir . '/exports',
            $spb_dir . '/logs'
        );
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Create .htaccess for security
                $htaccess_content = "Order deny,allow\nDeny from all\n";
                file_put_contents($dir . '/.htaccess', $htaccess_content);
                
                // Create index.php for security
                $index_content = "<?php\n// Silence is golden.\n";
                file_put_contents($dir . '/index.php', $index_content);
            }
        }
    }

    /**
     * Schedule cron events
     *
     * @since    3.0.0
     */
    private static function schedule_cron_events() {
        // Schedule analytics cleanup
        if (!wp_next_scheduled('spb_cleanup_analytics')) {
            wp_schedule_event(time(), 'daily', 'spb_cleanup_analytics');
        }
        
        // Schedule cache cleanup
        if (!wp_next_scheduled('spb_cleanup_cache')) {
            wp_schedule_event(time(), 'hourly', 'spb_cleanup_cache');
        }
        
        // Schedule personalization updates (v3.0)
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            if (!wp_next_scheduled('spb_update_interest_vectors')) {
                wp_schedule_event(time(), 'hourly', 'spb_update_interest_vectors');
            }
        }
        
        // Schedule search generation cleanup (v3.1)
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            if (!wp_next_scheduled('spb_cleanup_search_pages')) {
                wp_schedule_event(time(), 'daily', 'spb_cleanup_search_pages');
            }
        }
    }

    /**
     * Flush rewrite rules
     *
     * @since    3.0.0
     */
    private static function flush_rewrite_rules() {
        // Add custom rewrite rules for search pages
        add_rewrite_rule(
            '^spb-search/([^/]+)/?$',
            'index.php?spb_search_page=$matches[1]',
            'top'
        );
        
        // Flush rules
        flush_rewrite_rules();
    }

    /**
     * Check system requirements
     *
     * @since    3.0.0
     */
    private static function check_system_requirements() {
        $requirements = array();
        
        // PHP version check
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $requirements[] = 'PHP 7.4 or higher is required.';
        }
        
        // WordPress version check
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            $requirements[] = 'WordPress 5.0 or higher is required.';
        }
        
        // Memory limit check
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit && intval($memory_limit) < 128) {
            $requirements[] = 'PHP memory limit of 128MB or higher is recommended.';
        }
        
        // cURL check
        if (!function_exists('curl_init')) {
            $requirements[] = 'cURL extension is required for API connections.';
        }
        
        // JSON check
        if (!function_exists('json_encode')) {
            $requirements[] = 'JSON extension is required.';
        }
        
        // Store requirements check results
        if (!empty($requirements)) {
            update_option('spb_system_requirements', $requirements);
        } else {
            delete_option('spb_system_requirements');
        }
    }

    /**
     * Activate personalization features
     *
     * @since    3.0.0
     */
    private static function activate_personalization_features() {
        // Create personalization tables
        self::create_personalization_tables();
        
        // Set personalization status
        update_option('spb_personalization_status', 'active');
        
        // Initialize default interest categories
        $default_interests = array(
            'technology', 'business', 'health', 'education', 'entertainment',
            'sports', 'travel', 'food', 'fashion', 'science', 'politics',
            'finance', 'automotive', 'real-estate', 'lifestyle'
        );
        update_option('spb_default_interest_categories', $default_interests);
    }

    /**
     * Activate search generation features
     *
     * @since    3.1.0
     */
    private static function activate_search_generation_features() {
        // Set search generation status
        update_option('spb_search_generation_status', 'active');
        
        // Initialize AI provider settings
        $ai_providers = array(
            'openai' => array('enabled' => true, 'priority' => 1),
            'anthropic' => array('enabled' => true, 'priority' => 2),
            'google' => array('enabled' => true, 'priority' => 3)
        );
        update_option('spb_ai_providers', $ai_providers);
        
        // Initialize quality thresholds
        $quality_thresholds = array(
            'auto_approve' => 0.8,
            'manual_review' => 0.6,
            'auto_reject' => 0.3
        );
        update_option('spb_quality_thresholds', $quality_thresholds);
    }

    /**
     * Plugin upgrade handler
     *
     * @since    3.0.0
     * @param    string    $old_version    Previous version.
     * @param    string    $new_version    New version.
     */
    public static function upgrade($old_version, $new_version) {
        // Log upgrade attempt
        error_log("Smart Page Builder upgrading from {$old_version} to {$new_version}");
        
        // Run version-specific upgrades
        if (version_compare($old_version, '3.0.0', '<')) {
            self::upgrade_to_3_0_0();
        }
        
        if (version_compare($old_version, '3.1.0', '<')) {
            self::upgrade_to_3_1_0();
        }
        
        // Update database tables if needed
        self::create_database_tables();
        
        // Update personalization features if available
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            self::activate_personalization_features();
        }
        
        // Update search generation features if available
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            self::activate_search_generation_features();
        }
        
        // Clear any cached data
        self::clear_upgrade_cache();
        
        // Set upgrade completion flag
        update_option('spb_last_upgrade', current_time('timestamp'));
        
        error_log("Smart Page Builder upgrade completed successfully");
    }

    /**
     * Upgrade to version 3.0.0
     *
     * @since    3.0.0
     */
    private static function upgrade_to_3_0_0() {
        // Migrate old settings if they exist
        $old_options = get_option('spb_options', array());
        if (!empty($old_options)) {
            // Convert old options to new structure
            $general_options = array(
                'enable_analytics' => isset($old_options['analytics']) ? $old_options['analytics'] : true,
                'enable_caching' => isset($old_options['caching']) ? $old_options['caching'] : true,
                'cache_duration' => isset($old_options['cache_time']) ? $old_options['cache_time'] : 3600,
                'debug_mode' => isset($old_options['debug']) ? $old_options['debug'] : false
            );
            update_option('spb_general_options', $general_options);
            
            // Remove old options
            delete_option('spb_options');
        }
        
        // Create v3.0 personalization tables
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            self::create_personalization_tables();
        }
    }

    /**
     * Upgrade to version 3.1.0
     *
     * @since    3.1.0
     */
    private static function upgrade_to_3_1_0() {
        // Add search generation features
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            // Create search generation tables (already handled in create_database_tables)
            
            // Set default search generation options
            $search_defaults = array(
                'enable_auto_generation' => true,
                'auto_approval_threshold' => 0.8,
                'max_generation_time' => 30,
                'enable_quality_checks' => true,
                'require_manual_approval' => false
            );
            
            $existing_options = get_option('spb_search_generation_options', array());
            $merged_options = array_merge($search_defaults, $existing_options);
            update_option('spb_search_generation_options', $merged_options);
        }
        
        // Update AI provider settings
        $ai_providers = get_option('spb_ai_providers', array());
        if (empty($ai_providers)) {
            $default_providers = array(
                'openai' => array('enabled' => true, 'priority' => 1),
                'anthropic' => array('enabled' => true, 'priority' => 2),
                'google' => array('enabled' => true, 'priority' => 3)
            );
            update_option('spb_ai_providers', $default_providers);
        }
    }

    /**
     * Clear upgrade cache
     *
     * @since    3.0.0
     */
    private static function clear_upgrade_cache() {
        // Clear all plugin transients
        $transients = array(
            'spb_system_check',
            'spb_wpengine_connection',
            'spb_analytics_cache',
            'spb_personalization_cache',
            'spb_search_generation_cache',
            'spb_component_cache',
            'spb_template_cache'
        );
        
        foreach ($transients as $transient) {
            delete_transient($transient);
            delete_site_transient($transient);
        }
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Ensure content approvals table exists (fallback method)
     *
     * @since    3.1.7
     */
    private static function ensure_content_approvals_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            // Create the table manually
            $sql = "CREATE TABLE {$table_name} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                search_query varchar(255) NOT NULL,
                generated_content longtext NOT NULL,
                content_type varchar(50) NOT NULL DEFAULT 'page',
                quality_score decimal(3,2) DEFAULT NULL,
                confidence_score decimal(3,2) DEFAULT NULL,
                status varchar(20) DEFAULT 'pending_review',
                priority varchar(20) DEFAULT 'normal',
                assigned_to bigint(20) unsigned DEFAULT NULL,
                reviewed_by bigint(20) unsigned DEFAULT NULL,
                reviewed_at datetime DEFAULT NULL,
                approval_notes text DEFAULT NULL,
                rejection_reason varchar(100) DEFAULT NULL,
                ai_provider varchar(50) DEFAULT NULL,
                generation_metadata longtext DEFAULT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY search_query (search_query),
                KEY status (status),
                KEY priority (priority),
                KEY quality_score (quality_score),
                KEY confidence_score (confidence_score),
                KEY assigned_to (assigned_to),
                KEY reviewed_by (reviewed_by),
                KEY created_at (created_at)
            ) {$charset_collate};";
            
            $wpdb->query($sql);
        }
    }

    /**
     * Get activation status
     *
     * @since    3.0.0
     * @return   array    Activation status information.
     */
    public static function get_activation_status() {
        return array(
            'version' => get_option('spb_version', '0.0.0'),
            'activation_time' => get_option('spb_activation_time', 0),
            'last_upgrade' => get_option('spb_last_upgrade', 0),
            'system_requirements' => get_option('spb_system_requirements', array()),
            'personalization_active' => get_option('spb_personalization_status', 'inactive') === 'active',
            'search_generation_active' => get_option('spb_search_generation_status', 'inactive') === 'active'
        );
    }
}
