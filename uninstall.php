<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Smart Page Builder Uninstaller
 *
 * Handles complete cleanup when plugin is uninstalled.
 *
 * @since 1.0.0
 */
class Smart_Page_Builder_Uninstaller {

    /**
     * Uninstall the plugin
     *
     * @since 1.0.0
     */
    public static function uninstall() {
        // Check if we should delete data on uninstall
        $delete_data = get_option('spb_delete_data_on_uninstall', false);
        
        if ($delete_data) {
            self::delete_database_tables();
            self::delete_options();
            self::delete_user_capabilities();
            self::delete_transients();
            self::clear_scheduled_events();
        }
        
        // Always clear rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Delete custom database tables
     *
     * @since 1.0.0
     * @access private
     */
    private static function delete_database_tables() {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'spb_ai_insights',
            $wpdb->prefix . 'spb_dynamic_rules',
            $wpdb->prefix . 'spb_ab_tests',
            $wpdb->prefix . 'spb_metrics',
            $wpdb->prefix . 'spb_generated_content'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Delete plugin options
     *
     * @since 1.0.0
     * @access private
     */
    private static function delete_options() {
        $options = [
            'spb_cache_enabled',
            'spb_cache_duration',
            'spb_min_user_role',
            'spb_content_moderation',
            'spb_auto_generate',
            'spb_confidence_threshold',
            'spb_max_content_length',
            'spb_debug_mode',
            'spb_data_retention_days',
            'spb_rate_limit_requests',
            'spb_rate_limit_window',
            'spb_openai_api_key',
            'spb_anthropic_api_key',
            'spb_delete_data_on_uninstall',
            'spb_version',
            'spb_db_version'
        ];

        foreach ($options as $option) {
            delete_option($option);
        }

        // Delete multisite options if applicable
        if (is_multisite()) {
            foreach ($options as $option) {
                delete_site_option($option);
            }
        }
    }

    /**
     * Remove custom user capabilities
     *
     * @since 1.0.0
     * @access private
     */
    private static function delete_user_capabilities() {
        $capabilities = [
            'spb_manage_settings',
            'spb_generate_content',
            'spb_view_analytics',
            'spb_approve_content'
        ];

        $roles = ['administrator', 'editor', 'author'];

        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }

    /**
     * Delete plugin transients
     *
     * @since 1.0.0
     * @access private
     */
    private static function delete_transients() {
        global $wpdb;

        // Delete transients with our prefix
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_spb_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_spb_%'");

        // Delete site transients for multisite
        if (is_multisite()) {
            $wpdb->query("DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '_site_transient_spb_%'");
            $wpdb->query("DELETE FROM $wpdb->sitemeta WHERE meta_key LIKE '_site_transient_timeout_spb_%'");
        }
    }

    /**
     * Clear scheduled events
     *
     * @since 1.0.0
     * @access private
     */
    private static function clear_scheduled_events() {
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

        // Clear all scheduled events with our hooks
        wp_clear_scheduled_hook('spb_daily_cleanup');
        wp_clear_scheduled_hook('spb_hourly_cache_cleanup');
    }
}

// Run the uninstaller
Smart_Page_Builder_Uninstaller::uninstall();
