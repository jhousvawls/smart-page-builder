<?php
/**
 * Fired during plugin deactivation
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
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Clear scheduled cron jobs
        self::clear_cron_jobs();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any temporary cache
        self::clear_temporary_cache();
        
        // Log deactivation
        if (defined('SPB_DEBUG') && SPB_DEBUG) {
            error_log('Smart Page Builder deactivated');
        }
    }

    /**
     * Clear scheduled cron jobs
     *
     * @since    1.0.0
     * @access   private
     */
    private static function clear_cron_jobs() {
        // Clear daily cleanup
        $timestamp = wp_next_scheduled('spb_daily_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'spb_daily_cleanup');
        }

        // Clear hourly cache cleanup
        $timestamp = wp_next_scheduled('spb_hourly_cache_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'spb_hourly_cache_cleanup');
        }
    }

    /**
     * Clear temporary cache files
     *
     * @since    1.0.0
     * @access   private
     */
    private static function clear_temporary_cache() {
        global $wpdb;

        // Clear expired cache entries
        $table_name = $wpdb->prefix . 'spb_generated_content';
        $wpdb->query("DELETE FROM $table_name WHERE expires_at < NOW()");
    }
}
