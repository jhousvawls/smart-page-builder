<?php
/**
 * Fired during plugin deactivation
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Deactivator Class
 *
 * Fired during plugin deactivation.
 */
class Smart_Page_Builder_Deactivator {

    /**
     * Plugin deactivation handler
     *
     * @since    3.0.0
     */
    public static function deactivate() {
        // Clear scheduled cron events
        self::clear_cron_events();
        
        // Clear transients and cache
        self::clear_cache();
        
        // Flush rewrite rules
        self::flush_rewrite_rules();
        
        // Clear temporary data
        self::clear_temporary_data();
        
        // Deactivate personalization features if enabled
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            self::deactivate_personalization_features();
        }
        
        // Deactivate search generation features if enabled
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            self::deactivate_search_generation_features();
        }
        
        // Log deactivation
        self::log_deactivation();
        
        // Set deactivation flag
        update_option('spb_deactivation_time', current_time('timestamp'));
        delete_option('spb_activation_notice');
    }

    /**
     * Clear scheduled cron events
     *
     * @since    3.0.0
     */
    private static function clear_cron_events() {
        // Clear core plugin cron events
        $cron_events = array(
            'spb_cleanup_analytics',
            'spb_cleanup_cache',
            'spb_update_interest_vectors',
            'spb_cleanup_search_pages',
            'spb_process_approval_queue',
            'spb_update_quality_scores',
            'spb_cleanup_expired_sessions'
        );
        
        foreach ($cron_events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
        
        // Clear all instances of recurring events
        foreach ($cron_events as $event) {
            wp_clear_scheduled_hook($event);
        }
    }

    /**
     * Clear cache and transients
     *
     * @since    3.0.0
     */
    private static function clear_cache() {
        // Clear plugin transients
        $transients = array(
            'spb_activation_notice',
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
        
        // Clear file-based cache
        self::clear_file_cache();
    }

    /**
     * Clear file-based cache
     *
     * @since    3.0.0
     */
    private static function clear_file_cache() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/smart-page-builder/cache';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }

    /**
     * Flush rewrite rules
     *
     * @since    3.0.0
     */
    private static function flush_rewrite_rules() {
        // Remove custom rewrite rules
        global $wp_rewrite;
        
        // Remove search page rules
        $wp_rewrite->non_wp_rules = array_filter(
            $wp_rewrite->non_wp_rules,
            function($rule) {
                return strpos($rule, 'spb_search_page') === false;
            }
        );
        
        // Flush rules
        flush_rewrite_rules();
    }

    /**
     * Clear temporary data
     *
     * @since    3.0.0
     */
    private static function clear_temporary_data() {
        global $wpdb;
        
        // Check if tables exist before trying to clean them
        $tables_to_check = array(
            'spb_analytics',
            'spb_user_signals',
            'spb_search_pages'
        );
        
        foreach ($tables_to_check as $table_name) {
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->prefix . $table_name
            ));
            
            if (!$table_exists) {
                continue;
            }
            
            // Check if created_at column exists
            $column_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW COLUMNS FROM {$wpdb->prefix}{$table_name} LIKE %s",
                'created_at'
            ));
            
            if (!$column_exists) {
                continue;
            }
            
            // Clear data based on table type
            if ($table_name === 'spb_analytics') {
                // Clear temporary analytics data older than 30 days
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}spb_analytics 
                     WHERE created_at < %s",
                    date('Y-m-d H:i:s', strtotime('-30 days'))
                ));
            } elseif ($table_name === 'spb_user_signals' && defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
                // Clear expired user sessions
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}spb_user_signals 
                     WHERE created_at < %s",
                    date('Y-m-d H:i:s', strtotime('-7 days'))
                ));
            } elseif ($table_name === 'spb_search_pages' && defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
                // Clear pending search pages older than 7 days
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->prefix}spb_search_pages 
                     WHERE page_status = 'pending' AND created_at < %s",
                    date('Y-m-d H:i:s', strtotime('-7 days'))
                ));
            }
        }
    }

    /**
     * Deactivate personalization features
     *
     * @since    3.0.0
     */
    private static function deactivate_personalization_features() {
        // Update personalization status
        update_option('spb_personalization_status', 'inactive');
        
        // Clear personalization cache
        delete_transient('spb_interest_vectors_cache');
        delete_transient('spb_personalization_rules_cache');
        
        // Clear user consent data if privacy mode is enabled
        $options = get_option('spb_personalization_options', array());
        if (isset($options['enable_privacy_mode']) && $options['enable_privacy_mode']) {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}spb_user_consent");
        }
    }

    /**
     * Deactivate search generation features
     *
     * @since    3.1.0
     */
    private static function deactivate_search_generation_features() {
        // Update search generation status
        update_option('spb_search_generation_status', 'inactive');
        
        // Clear search generation cache
        delete_transient('spb_ai_generation_cache');
        delete_transient('spb_quality_assessment_cache');
        
        // Clear approval queue cache
        delete_transient('spb_approval_queue_cache');
    }

    /**
     * Log deactivation
     *
     * @since    3.0.0
     */
    private static function log_deactivation() {
        // Log deactivation event
        if (class_exists('SPB_Analytics_Manager')) {
            $analytics = new SPB_Analytics_Manager();
            $analytics->track_event('plugin_deactivated', array(
                'version' => SPB_VERSION,
                'deactivation_time' => current_time('timestamp'),
                'user_id' => get_current_user_id(),
                'reason' => 'manual_deactivation'
            ));
        }
        
        // Write to log file if debug mode is enabled
        $options = get_option('spb_general_options', array());
        if (isset($options['debug_mode']) && $options['debug_mode']) {
            self::write_debug_log('Plugin deactivated at ' . current_time('mysql'));
        }
    }

    /**
     * Write to debug log
     *
     * @since    3.0.0
     * @param    string    $message    Log message.
     */
    private static function write_debug_log($message) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/smart-page-builder/logs';
        
        if (!is_dir($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        $log_file = $log_dir . '/deactivation.log';
        $timestamp = current_time('mysql');
        $log_entry = "[{$timestamp}] {$message}" . PHP_EOL;
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Clean uninstall (called from uninstall.php)
     *
     * @since    3.0.0
     */
    public static function uninstall() {
        // Check if user has permission to uninstall
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        // Check if this is a multisite uninstall
        if (is_multisite()) {
            self::uninstall_multisite();
        } else {
            self::uninstall_single_site();
        }
    }

    /**
     * Uninstall for single site
     *
     * @since    3.0.0
     */
    private static function uninstall_single_site() {
        // Remove database tables
        self::remove_database_tables();
        
        // Remove options
        self::remove_options();
        
        // Remove upload directories
        self::remove_upload_directories();
        
        // Clear all cron events
        self::clear_cron_events();
        
        // Clear all cache
        self::clear_cache();
        
        // Remove user meta
        self::remove_user_meta();
    }

    /**
     * Uninstall for multisite
     *
     * @since    3.0.0
     */
    private static function uninstall_multisite() {
        global $wpdb;
        
        // Get all blog IDs
        $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->blogs}");
        
        foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            self::uninstall_single_site();
            restore_current_blog();
        }
        
        // Remove network options
        delete_site_option('spb_network_options');
    }

    /**
     * Remove database tables
     *
     * @since    3.0.0
     */
    private static function remove_database_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'spb_components',
            $wpdb->prefix . 'spb_page_templates',
            $wpdb->prefix . 'spb_analytics',
            $wpdb->prefix . 'spb_search_pages',
            $wpdb->prefix . 'spb_query_enhancements',
            $wpdb->prefix . 'spb_generated_components',
            $wpdb->prefix . 'spb_content_approvals',
            $wpdb->prefix . 'spb_user_interest_vectors',
            $wpdb->prefix . 'spb_user_signals',
            $wpdb->prefix . 'spb_personalization_rules',
            $wpdb->prefix . 'spb_component_variants',
            $wpdb->prefix . 'spb_user_consent'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Remove plugin options
     *
     * @since    3.0.0
     */
    private static function remove_options() {
        $options = array(
            'spb_version',
            'spb_activation_time',
            'spb_deactivation_time',
            'spb_general_options',
            'spb_wpengine_options',
            'spb_personalization_options',
            'spb_search_generation_options',
            'spb_system_requirements',
            'spb_personalization_status',
            'spb_search_generation_status',
            'spb_wpengine_connection_status',
            'spb_default_interest_categories',
            'spb_ai_providers',
            'spb_quality_thresholds'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }

    /**
     * Remove upload directories
     *
     * @since    3.0.0
     */
    private static function remove_upload_directories() {
        $upload_dir = wp_upload_dir();
        $spb_dir = $upload_dir['basedir'] . '/smart-page-builder';
        
        if (is_dir($spb_dir)) {
            self::remove_directory_recursive($spb_dir);
        }
    }

    /**
     * Remove directory recursively
     *
     * @since    3.0.0
     * @param    string    $dir    Directory path.
     */
    private static function remove_directory_recursive($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::remove_directory_recursive($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }

    /**
     * Remove user meta
     *
     * @since    3.0.0
     */
    private static function remove_user_meta() {
        global $wpdb;
        
        $meta_keys = array(
            'spb_user_preferences',
            'spb_interest_profile',
            'spb_personalization_consent',
            'spb_last_activity'
        );
        
        foreach ($meta_keys as $meta_key) {
            $wpdb->delete(
                $wpdb->usermeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
    }

    /**
     * Get deactivation status
     *
     * @since    3.0.0
     * @return   array    Deactivation status information.
     */
    public static function get_deactivation_status() {
        return array(
            'deactivation_time' => get_option('spb_deactivation_time', 0),
            'cron_events_cleared' => !wp_next_scheduled('spb_cleanup_analytics'),
            'cache_cleared' => !get_transient('spb_activation_notice'),
            'personalization_deactivated' => get_option('spb_personalization_status', 'inactive') === 'inactive',
            'search_generation_deactivated' => get_option('spb_search_generation_status', 'inactive') === 'inactive'
        );
    }
}
