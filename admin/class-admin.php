<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Admin Class
 *
 * Defines the plugin name, version, and hooks for the admin area.
 */
class Smart_Page_Builder_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    3.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    3.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    3.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    3.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            SPB_PLUGIN_URL . 'admin/css/smart-page-builder-admin.css',
            array(),
            $this->version,
            'all'
        );

        // Enqueue analytics dashboard styles if on analytics page
        if ($this->is_plugin_page('analytics')) {
            wp_enqueue_style(
                $this->plugin_name . '-analytics',
                SPB_PLUGIN_URL . 'admin/css/analytics-dashboard.css',
                array($this->plugin_name),
                $this->version,
                'all'
            );
        }

        // Enqueue support documentation styles if on support page
        if ($this->is_plugin_page('support')) {
            wp_enqueue_style(
                $this->plugin_name . '-support',
                SPB_PLUGIN_URL . 'admin/css/support-documentation.css',
                array($this->plugin_name),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    3.0.0
     */
    public function enqueue_scripts() {
        // Check if we're on a plugin page
        $current_page = $this->get_current_admin_page();
        $is_plugin_page = strpos($current_page, $this->plugin_name) === 0;
        
        // Debug: Add console logging to help troubleshoot
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SPB Debug - Current page: " . $current_page);
            error_log("SPB Debug - Plugin name: " . $this->plugin_name);
            error_log("SPB Debug - Is plugin page: " . ($is_plugin_page ? 'YES' : 'NO'));
        }
        
        // Only enqueue on plugin pages
        if (!$is_plugin_page) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            SPB_PLUGIN_URL . 'admin/js/smart-page-builder-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script with admin data - this ensures spb_admin is available on all plugin pages
        wp_localize_script(
            $this->plugin_name,
            'spb_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spb_admin_nonce'),
                'plugin_url' => SPB_PLUGIN_URL,
                'version' => $this->version,
                'current_page' => $current_page,
                'personalization_enabled' => defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION,
                'search_generation_enabled' => defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION
            )
        );

        // Enqueue analytics dashboard scripts if on analytics page
        if ($this->is_plugin_page('analytics')) {
            wp_enqueue_script(
                $this->plugin_name . '-analytics',
                SPB_PLUGIN_URL . 'admin/js/analytics-dashboard.js',
                array($this->plugin_name, 'jquery', 'wp-api'),
                $this->version,
                false
            );
        }

        // Enqueue support documentation scripts if on support page
        if ($this->is_plugin_page('support')) {
            wp_enqueue_script(
                $this->plugin_name . '-support',
                SPB_PLUGIN_URL . 'admin/js/support-documentation.js',
                array($this->plugin_name),
                $this->version,
                false
            );
        }
    }

    /**
     * Add plugin admin menu
     *
     * @since    3.0.0
     * @updated  3.2.0 Enhanced navigation structure with workflow-based grouping
     */
    public function add_plugin_admin_menu() {
        // Main menu page with brain icon
        add_menu_page(
            __('Smart Page Builder', 'smart-page-builder'),
            __('Smart Page Builder', 'smart-page-builder'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page'),
            'dashicons-brain',
            30
        );

        // Dashboard submenu (same as main page)
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'smart-page-builder'),
            __('Dashboard', 'smart-page-builder'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_plugin_admin_page')
        );

        // Content Management section
        add_submenu_page(
            $this->plugin_name,
            __('Content Management', 'smart-page-builder'),
            __('Content Management', 'smart-page-builder'),
            'manage_options',
            $this->plugin_name . '-content-management',
            array($this, 'display_plugin_content_management_page')
        );

        // Content Approval submenu (v3.1 feature) - now under Content Management
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            add_submenu_page(
                $this->plugin_name,
                __('Content Approval', 'smart-page-builder'),
                __('— Content Approval', 'smart-page-builder'), // Indented to show hierarchy
                'manage_options',
                $this->plugin_name . '-approval',
                array($this, 'display_plugin_approval_page')
            );
        }

        // Analytics & Reports submenu (renamed for clarity)
        add_submenu_page(
            $this->plugin_name,
            __('Analytics & Reports', 'smart-page-builder'),
            __('Analytics & Reports', 'smart-page-builder'),
            'manage_options',
            $this->plugin_name . '-analytics',
            array($this, 'display_plugin_analytics_page')
        );

        // Personalization submenu (v3.0 feature)
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            add_submenu_page(
                $this->plugin_name,
                __('Personalization', 'smart-page-builder'),
                __('Personalization', 'smart-page-builder'),
                'manage_options',
                $this->plugin_name . '-personalization',
                array($this, 'display_plugin_personalization_page')
            );
        }

        // Configuration section (groups settings and integrations)
        add_submenu_page(
            $this->plugin_name,
            __('Configuration', 'smart-page-builder'),
            __('Configuration', 'smart-page-builder'),
            'manage_options',
            $this->plugin_name . '-settings',
            array($this, 'display_plugin_settings_page')
        );

        // WP Engine Integration submenu - now under Configuration
        add_submenu_page(
            $this->plugin_name,
            __('WP Engine AI Setup', 'smart-page-builder'),
            __('— WP Engine AI', 'smart-page-builder'), // Indented to show hierarchy
            'manage_options',
            $this->plugin_name . '-wpengine',
            array($this, 'display_plugin_wpengine_page')
        );

        // Help & Support submenu (renamed for clarity)
        add_submenu_page(
            $this->plugin_name,
            __('Help & Support', 'smart-page-builder'),
            __('Help & Support', 'smart-page-builder'),
            'manage_options',
            $this->plugin_name . '-support',
            array($this, 'display_plugin_support_page')
        );
    }

    /**
     * Initialize admin functionality
     *
     * @since    3.0.0
     */
    public function admin_init() {
        // Register settings
        $this->register_settings();

        // Initialize AJAX handlers
        $this->init_ajax_handlers();

        // Add admin notices
        add_action('admin_notices', array($this, 'display_admin_notices'));

        // Add plugin action links
        add_filter('plugin_action_links_' . SPB_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * Register plugin settings
     *
     * @since    3.0.0
     */
    private function register_settings() {
        // General settings
        register_setting('spb_general_settings', 'spb_general_options');
        
        // Personalization settings (v3.0)
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            register_setting('spb_personalization_settings', 'spb_personalization_options');
        }

        // WP Engine settings - register individual options
        register_setting('spb_wpengine_settings', 'spb_wpengine_api_url');
        register_setting('spb_wpengine_settings', 'spb_wpengine_access_token');
        register_setting('spb_wpengine_settings', 'spb_wpengine_site_id');
        register_setting('spb_wpengine_settings', 'spb_enable_search_interception');
        register_setting('spb_wpengine_settings', 'spb_auto_approve_threshold');
        register_setting('spb_wpengine_settings', 'spb_enable_seo_urls');
        register_setting('spb_wpengine_settings', 'spb_min_query_length');
        register_setting('spb_wpengine_settings', 'spb_max_query_length');

        // Search generation settings (v3.1)
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            register_setting('spb_search_generation_settings', 'spb_search_generation_options');
        }
    }

    /**
     * Initialize AJAX handlers
     *
     * @since    3.0.0
     * @updated  3.2.0 Added Phase 2 real-time dashboard handlers
     */
    private function init_ajax_handlers() {
        // General AJAX handlers
        add_action('wp_ajax_spb_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_spb_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_spb_get_analytics_data', array($this, 'ajax_get_analytics_data'));

        // Phase 2: Real-time dashboard AJAX handlers
        add_action('wp_ajax_spb_get_dashboard_stats', array($this, 'ajax_get_dashboard_stats'));
        add_action('wp_ajax_spb_get_recent_activity', array($this, 'ajax_get_recent_activity'));
        add_action('wp_ajax_spb_get_system_health', array($this, 'ajax_get_system_health'));
        add_action('wp_ajax_spb_get_performance_metrics', array($this, 'ajax_get_performance_metrics'));
        add_action('wp_ajax_spb_get_notifications', array($this, 'ajax_get_notifications'));
        add_action('wp_ajax_spb_dismiss_notification', array($this, 'ajax_dismiss_notification'));
        add_action('wp_ajax_spb_run_system_diagnostics', array($this, 'ajax_run_system_diagnostics'));

        // Personalization AJAX handlers (v3.0)
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            add_action('wp_ajax_spb_get_user_interests', array($this, 'ajax_get_user_interests'));
            add_action('wp_ajax_spb_update_personalization_rules', array($this, 'ajax_update_personalization_rules'));
        }

        // Search generation AJAX handlers (v3.1)
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            add_action('wp_ajax_spb_approve_content', array($this, 'ajax_approve_content'));
            add_action('wp_ajax_spb_reject_content', array($this, 'ajax_reject_content'));
            add_action('wp_ajax_spb_bulk_approve', array($this, 'ajax_bulk_approve'));
        }
        
        // Content Management AJAX handlers
        add_action('wp_ajax_spb_get_content_list', array($this, 'ajax_get_content_list'));
        add_action('wp_ajax_spb_delete_content', array($this, 'ajax_delete_content'));
        add_action('wp_ajax_spb_bulk_content_action', array($this, 'ajax_bulk_content_action'));
        add_action('wp_ajax_spb_content_action', array($this, 'ajax_content_action'));
        add_action('wp_ajax_spb_preview_content', array($this, 'ajax_preview_content'));
        add_action('wp_ajax_spb_edit_content', array($this, 'ajax_edit_content'));
        
        // Cache management AJAX handlers
        add_action('wp_ajax_spb_clear_cache', array($this, 'ajax_clear_cache'));
        
        // WP Engine AJAX handlers
        add_action('wp_ajax_spb_test_wpengine_connection', array($this, 'ajax_test_wpengine_connection'));
        add_action('wp_ajax_spb_test_wpengine_integration', array($this, 'ajax_test_wpengine_integration'));
    }

    /**
     * Display main admin page
     *
     * @since    3.0.0
     */
    public function display_plugin_admin_page() {
        include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-display.php';
    }

    /**
     * Display settings page
     *
     * @since    3.0.0
     */
    public function display_plugin_settings_page() {
        include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-settings.php';
    }

    /**
     * Display analytics page
     *
     * @since    3.0.0
     */
    public function display_plugin_analytics_page() {
        include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-analytics.php';
    }

    /**
     * Display personalization page
     *
     * @since    3.0.0
     */
    public function display_plugin_personalization_page() {
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-personalization.php';
        } else {
            wp_die(__('Personalization features are not available.', 'smart-page-builder'));
        }
    }

    /**
     * Display WP Engine page
     *
     * @since    3.0.0
     */
    public function display_plugin_wpengine_page() {
        include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-wpengine.php';
    }

    /**
     * Display content management page
     *
     * @since    3.2.0
     */
    public function display_plugin_content_management_page() {
        include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-content-management.php';
    }

    /**
     * Display content approval page
     *
     * @since    3.1.0
     */
    public function display_plugin_approval_page() {
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-approval.php';
        } else {
            wp_die(__('Content approval features are not available.', 'smart-page-builder'));
        }
    }

    /**
     * Display support page
     *
     * @since    3.0.0
     */
    public function display_plugin_support_page() {
        include_once SPB_PLUGIN_DIR . 'admin/partials/smart-page-builder-admin-support.php';
    }

    /**
     * Display admin notices
     *
     * @since    3.0.0
     */
    public function display_admin_notices() {
        // Check for plugin activation notice
        if (get_transient('spb_activation_notice')) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Smart Page Builder has been activated successfully!', 'smart-page-builder') . '</p>';
            echo '</div>';
            delete_transient('spb_activation_notice');
        }

        // Check for WP Engine connection status
        if ($this->is_plugin_page()) {
            $wpengine_status = get_option('spb_wpengine_connection_status', 'not_configured');
            if ($wpengine_status === 'error') {
                echo '<div class="notice notice-warning">';
                echo '<p>' . __('WP Engine AI Toolkit connection error. Please check your settings.', 'smart-page-builder') . '</p>';
                echo '</div>';
            }
        }

        // Check for v3.0 personalization availability
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION && $this->is_plugin_page()) {
            $personalization_status = get_option('spb_personalization_status', 'not_configured');
            if ($personalization_status === 'tables_missing') {
                echo '<div class="notice notice-info">';
                echo '<p>' . __('Personalization tables need to be created. Please run the database setup.', 'smart-page-builder') . '</p>';
                echo '</div>';
            }
        }
    }

    /**
     * Add plugin action links
     *
     * @since    3.0.0
     * @param    array    $links    Existing action links.
     * @return   array              Modified action links.
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=' . $this->plugin_name . '-settings') . '">' . __('Settings', 'smart-page-builder') . '</a>';
        array_unshift($links, $settings_link);

        $dashboard_link = '<a href="' . admin_url('admin.php?page=' . $this->plugin_name) . '">' . __('Dashboard', 'smart-page-builder') . '</a>';
        array_unshift($links, $dashboard_link);

        return $links;
    }

    /**
     * Check if current page is a plugin admin page
     *
     * @since    3.0.0
     * @param    string    $page    Specific page to check for.
     * @return   bool               True if on plugin page.
     */
    private function is_plugin_page($page = null) {
        $current_page = $this->get_current_admin_page();
        
        if ($page === null) {
            return strpos($current_page, $this->plugin_name) === 0;
        }
        
        return $current_page === $this->plugin_name . '-' . $page;
    }

    /**
     * Get current admin page
     *
     * @since    3.0.0
     * @return   string    Current admin page.
     */
    private function get_current_admin_page() {
        return isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    }

    /**
     * AJAX handler for saving settings
     *
     * @since    3.0.0
     */
    public function ajax_save_settings() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'smart-page-builder'));
        }

        $settings_type = sanitize_text_field($_POST['settings_type']);
        $settings_data = $_POST['settings_data'];

        // Validate and save settings based on type
        switch ($settings_type) {
            case 'general':
                update_option('spb_general_options', $settings_data);
                break;
            case 'personalization':
                if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
                    update_option('spb_personalization_options', $settings_data);
                }
                break;
            case 'wpengine':
                update_option('spb_wpengine_options', $settings_data);
                break;
            case 'search_generation':
                if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
                    update_option('spb_search_generation_options', $settings_data);
                }
                break;
        }

        wp_send_json_success(array('message' => __('Settings saved successfully.', 'smart-page-builder')));
    }

    /**
     * AJAX handler for testing connections
     *
     * @since    3.0.0
     */
    public function ajax_test_connection() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'smart-page-builder'));
        }

        $connection_type = sanitize_text_field($_POST['connection_type']);
        
        switch ($connection_type) {
            case 'wpengine':
                $result = $this->test_wpengine_connection();
                break;
            default:
                $result = array('success' => false, 'message' => __('Unknown connection type.', 'smart-page-builder'));
        }

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Test WP Engine connection
     *
     * @since    3.0.0
     * @return   array    Connection test result.
     */
    private function test_wpengine_connection() {
        if (class_exists('SPB_WPEngine_API_Client')) {
            $client = new SPB_WPEngine_API_Client();
            return $client->test_connection();
        }
        
        return array(
            'success' => false,
            'message' => __('WP Engine API client not available.', 'smart-page-builder')
        );
    }

    /**
     * AJAX handler for getting analytics data
     *
     * @since    3.0.0
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'smart-page-builder'));
        }

        if (class_exists('SPB_Analytics_Manager')) {
            $analytics = new SPB_Analytics_Manager();
            $data = $analytics->get_dashboard_data();
            wp_send_json_success($data);
        } else {
            wp_send_json_error(array('message' => __('Analytics not available.', 'smart-page-builder')));
        }
    }

    /**
     * AJAX handler for clearing cache
     *
     * @since    3.1.7
     */
    public function ajax_clear_cache() {
        check_ajax_referer('spb_clear_cache', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            // Clear WordPress transients
            $transients_cleared = 0;
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
                if (delete_transient($transient)) {
                    $transients_cleared++;
                }
                delete_site_transient($transient);
            }
            
            // Clear object cache if available
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
            
            // Clear file-based cache
            $files_cleared = $this->clear_file_cache();
            
            wp_send_json_success(array(
                'message' => __('Cache cleared successfully.', 'smart-page-builder'),
                'details' => array(
                    'transients_cleared' => $transients_cleared,
                    'files_cleared' => $files_cleared
                )
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error clearing cache: ', 'smart-page-builder') . $e->getMessage()
            ));
        }
    }

    /**
     * Clear file-based cache
     *
     * @since    3.1.7
     * @return   int    Number of files cleared.
     */
    private function clear_file_cache() {
        $files_cleared = 0;
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/smart-page-builder/cache';
        
        if (is_dir($cache_dir)) {
            $files = glob($cache_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (unlink($file)) {
                        $files_cleared++;
                    }
                }
            }
        }
        
        return $files_cleared;
    }

    /**
     * Get plugin name
     *
     * @since    3.0.0
     * @return   string    Plugin name.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Get plugin version
     *
     * @since    3.0.0
     * @return   string    Plugin version.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * AJAX handler for testing WP Engine connection
     *
     * @since    3.1.7
     */
    public function ajax_test_wpengine_connection() {
        check_ajax_referer('spb_test_connection', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        // Get current settings
        $api_url = get_option('spb_wpengine_api_url', '');
        $access_token = get_option('spb_wpengine_access_token', '');
        $site_id = get_option('spb_wpengine_site_id', '');

        // Validate required settings
        if (empty($api_url) || empty($access_token) || empty($site_id)) {
            wp_send_json_error(array(
                'message' => __('Missing required credentials. Please configure API URL, Access Token, and Site ID.', 'smart-page-builder')
            ));
            return;
        }

        // Test the connection
        $result = $this->perform_wpengine_connection_test($api_url, $access_token, $site_id);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX handler for testing WP Engine full integration
     *
     * @since    3.1.7
     */
    public function ajax_test_wpengine_integration() {
        check_ajax_referer('spb_test_integration', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        // Get current settings
        $api_url = get_option('spb_wpengine_api_url', '');
        $access_token = get_option('spb_wpengine_access_token', '');
        $site_id = get_option('spb_wpengine_site_id', '');

        // Validate required settings
        if (empty($api_url) || empty($access_token) || empty($site_id)) {
            wp_send_json_error(array(
                'message' => __('Missing required credentials. Please configure API URL, Access Token, and Site ID.', 'smart-page-builder')
            ));
            return;
        }

        // Perform comprehensive integration test
        $result = $this->perform_wpengine_integration_test($api_url, $access_token, $site_id);
        
        wp_send_json_success($result);
    }

    /**
     * Perform WP Engine connection test with detailed error reporting
     *
     * @since    3.1.7
     * @param    string    $api_url       API endpoint URL
     * @param    string    $access_token  Access token
     * @param    string    $site_id       Site ID
     * @return   array                    Test result with detailed information
     */
    private function perform_wpengine_connection_test($api_url, $access_token, $site_id) {
        try {
            // Validate URL format
            if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
                return array(
                    'success' => false,
                    'message' => __('Invalid API URL format. Please enter a valid URL (e.g., https://api.wpengine.com/v1)', 'smart-page-builder'),
                    'error_code' => 'INVALID_URL'
                );
            }

            // Test basic connectivity
            $test_url = rtrim($api_url, '/') . '/health';
            $response = wp_remote_get($test_url, array(
                'timeout' => 10,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Smart-Page-Builder/' . $this->version
                )
            ));

            // Check for connection errors
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                return array(
                    'success' => false,
                    'message' => sprintf(__('Connection failed: %s', 'smart-page-builder'), $error_message),
                    'error_code' => 'CONNECTION_ERROR',
                    'details' => array(
                        'error_type' => $response->get_error_code(),
                        'error_message' => $error_message,
                        'url_tested' => $test_url
                    )
                );
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            // Check response code
            if ($response_code === 401) {
                return array(
                    'success' => false,
                    'message' => __('Authentication failed. Please check your access token.', 'smart-page-builder'),
                    'error_code' => 'AUTH_ERROR',
                    'details' => array(
                        'response_code' => $response_code,
                        'suggestion' => 'Verify your access token is correct and has not expired'
                    )
                );
            }

            if ($response_code === 403) {
                return array(
                    'success' => false,
                    'message' => __('Access forbidden. Your token may not have sufficient permissions.', 'smart-page-builder'),
                    'error_code' => 'PERMISSION_ERROR',
                    'details' => array(
                        'response_code' => $response_code,
                        'suggestion' => 'Check that your access token has the required permissions for AI Toolkit access'
                    )
                );
            }

            if ($response_code === 404) {
                return array(
                    'success' => false,
                    'message' => __('API endpoint not found. Please verify your API URL.', 'smart-page-builder'),
                    'error_code' => 'ENDPOINT_NOT_FOUND',
                    'details' => array(
                        'response_code' => $response_code,
                        'url_tested' => $test_url,
                        'suggestion' => 'Verify the API URL is correct (e.g., https://api.wpengine.com/v1)'
                    )
                );
            }

            if ($response_code >= 500) {
                return array(
                    'success' => false,
                    'message' => __('Server error. The WP Engine API may be temporarily unavailable.', 'smart-page-builder'),
                    'error_code' => 'SERVER_ERROR',
                    'details' => array(
                        'response_code' => $response_code,
                        'suggestion' => 'Try again in a few minutes. If the problem persists, contact WP Engine support.'
                    )
                );
            }

            if ($response_code !== 200) {
                return array(
                    'success' => false,
                    'message' => sprintf(__('Unexpected response code: %d', 'smart-page-builder'), $response_code),
                    'error_code' => 'UNEXPECTED_RESPONSE',
                    'details' => array(
                        'response_code' => $response_code,
                        'response_body' => substr($response_body, 0, 200)
                    )
                );
            }

            // Connection successful
            return array(
                'success' => true,
                'message' => __('Connection successful! WP Engine AI Toolkit is accessible.', 'smart-page-builder'),
                'details' => array(
                    'response_code' => $response_code,
                    'api_url' => $api_url,
                    'site_id' => $site_id,
                    'connection_time' => date('Y-m-d H:i:s')
                )
            );

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => sprintf(__('Connection test failed: %s', 'smart-page-builder'), $e->getMessage()),
                'error_code' => 'EXCEPTION_ERROR',
                'details' => array(
                    'exception_message' => $e->getMessage(),
                    'exception_code' => $e->getCode()
                )
            );
        }
    }

    /**
     * Perform comprehensive WP Engine integration test
     *
     * @since    3.1.7
     * @param    string    $api_url       API endpoint URL
     * @param    string    $access_token  Access token
     * @param    string    $site_id       Site ID
     * @return   array                    Comprehensive test results
     */
    private function perform_wpengine_integration_test($api_url, $access_token, $site_id) {
        $results = array(
            'api_connection' => false,
            'smart_search' => false,
            'vector_search' => false,
            'recommendations' => false,
            'query_enhancement' => false,
            'overall_status' => 'failed',
            'errors' => array()
        );

        try {
            // Test 1: API Connection
            $connection_test = $this->perform_wpengine_connection_test($api_url, $access_token, $site_id);
            $results['api_connection'] = $connection_test['success'];
            
            if (!$connection_test['success']) {
                $results['errors'][] = 'API Connection: ' . $connection_test['message'];
                return $results;
            }

            // Test 2: Smart Search
            $search_result = $this->test_wpengine_smart_search($api_url, $access_token, $site_id);
            $results['smart_search'] = $search_result['success'];
            if (!$search_result['success']) {
                $results['errors'][] = 'Smart Search: ' . $search_result['message'];
            }

            // Test 3: Vector Search
            $vector_result = $this->test_wpengine_vector_search($api_url, $access_token, $site_id);
            $results['vector_search'] = $vector_result['success'];
            if (!$vector_result['success']) {
                $results['errors'][] = 'Vector Search: ' . $vector_result['message'];
            }

            // Test 4: Recommendations
            $recommendations_result = $this->test_wpengine_recommendations($api_url, $access_token, $site_id);
            $results['recommendations'] = $recommendations_result['success'];
            if (!$recommendations_result['success']) {
                $results['errors'][] = 'Recommendations: ' . $recommendations_result['message'];
            }

            // Test 5: Query Enhancement
            $enhancement_result = $this->test_wpengine_query_enhancement($api_url, $access_token, $site_id);
            $results['query_enhancement'] = $enhancement_result['success'];
            if (!$enhancement_result['success']) {
                $results['errors'][] = 'Query Enhancement: ' . $enhancement_result['message'];
            }

            // Determine overall status
            $successful_tests = array_sum(array_filter($results, 'is_bool'));
            if ($successful_tests >= 4) {
                $results['overall_status'] = 'passed';
            } elseif ($successful_tests >= 2) {
                $results['overall_status'] = 'partial';
            } else {
                $results['overall_status'] = 'failed';
            }

        } catch (Exception $e) {
            $results['errors'][] = 'Integration Test Exception: ' . $e->getMessage();
        }

        return $results;
    }

    /**
     * Test WP Engine Smart Search functionality
     */
    private function test_wpengine_smart_search($api_url, $access_token, $site_id) {
        try {
            $search_url = rtrim($api_url, '/') . '/search';
            $response = wp_remote_post($search_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'query' => 'test search',
                    'site_id' => $site_id,
                    'limit' => 5
                ))
            ));

            if (is_wp_error($response)) {
                return array('success' => false, 'message' => $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            return array('success' => $response_code === 200, 'message' => 'Response code: ' . $response_code);

        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Test WP Engine Vector Search functionality
     */
    private function test_wpengine_vector_search($api_url, $access_token, $site_id) {
        try {
            $vector_url = rtrim($api_url, '/') . '/vector-search';
            $response = wp_remote_post($vector_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'query' => 'semantic search test',
                    'site_id' => $site_id,
                    'limit' => 5
                ))
            ));

            if (is_wp_error($response)) {
                return array('success' => false, 'message' => $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            return array('success' => $response_code === 200, 'message' => 'Response code: ' . $response_code);

        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Test WP Engine Recommendations functionality
     */
    private function test_wpengine_recommendations($api_url, $access_token, $site_id) {
        try {
            $recommendations_url = rtrim($api_url, '/') . '/recommendations';
            $response = wp_remote_post($recommendations_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'site_id' => $site_id,
                    'context' => 'test',
                    'limit' => 3
                ))
            ));

            if (is_wp_error($response)) {
                return array('success' => false, 'message' => $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            return array('success' => $response_code === 200, 'message' => 'Response code: ' . $response_code);

        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * Test WP Engine Query Enhancement functionality
     */
    private function test_wpengine_query_enhancement($api_url, $access_token, $site_id) {
        try {
            $enhancement_url = rtrim($api_url, '/') . '/enhance-query';
            $response = wp_remote_post($enhancement_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'query' => 'test query enhancement',
                    'site_id' => $site_id
                ))
            ));

            if (is_wp_error($response)) {
                return array('success' => false, 'message' => $response->get_error_message());
            }

            $response_code = wp_remote_retrieve_response_code($response);
            return array('success' => $response_code === 200, 'message' => 'Response code: ' . $response_code);

        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * AJAX handler for getting real-time dashboard stats
     *
     * @since    3.2.0
     */
    public function ajax_get_dashboard_stats() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            // Get real-time statistics
            $stats = array(
                'total_pages' => wp_count_posts('page')->publish ?? 0,
                'total_posts' => wp_count_posts('post')->publish ?? 0,
                'active_users' => count_users()['total_users'] ?? 0,
                'ai_generated_pages' => get_option('spb_ai_generated_count', 0),
                'pending_approvals' => get_option('spb_pending_approvals_count', 0),
                'personalization_active' => get_option('spb_personalization_active_users', 0),
                'search_queries_today' => get_option('spb_search_queries_today', 0),
                'content_quality_score' => get_option('spb_avg_content_quality', 85),
                'last_updated' => current_time('timestamp')
            );

            // Add trend indicators
            $stats['trends'] = array(
                'pages_trend' => $this->calculate_trend('pages', $stats['total_pages']),
                'posts_trend' => $this->calculate_trend('posts', $stats['total_posts']),
                'users_trend' => $this->calculate_trend('users', $stats['active_users']),
                'ai_pages_trend' => $this->calculate_trend('ai_pages', $stats['ai_generated_pages'])
            );

            wp_send_json_success($stats);

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error fetching dashboard stats: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for getting recent activity feed
     *
     * @since    3.2.0
     */
    public function ajax_get_recent_activity() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
            
            // Get recent activity from database
            $activity = $this->get_recent_activity_data($limit);
            
            wp_send_json_success(array(
                'activity' => $activity,
                'last_updated' => current_time('timestamp')
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error fetching activity: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for getting system health status
     *
     * @since    3.2.0
     */
    public function ajax_get_system_health() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $health = array(
                'overall_status' => 'good',
                'checks' => array(
                    'php_version' => array(
                        'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'good' : 'warning',
                        'value' => PHP_VERSION,
                        'message' => version_compare(PHP_VERSION, '7.4', '>=') ? 'PHP version is compatible' : 'PHP version should be 7.4 or higher'
                    ),
                    'wp_version' => array(
                        'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'good' : 'warning',
                        'value' => get_bloginfo('version'),
                        'message' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'WordPress version is compatible' : 'WordPress version should be 5.0 or higher'
                    ),
                    'memory_limit' => array(
                        'status' => $this->check_memory_limit(),
                        'value' => ini_get('memory_limit'),
                        'message' => $this->get_memory_status_message()
                    ),
                    'wpengine_connection' => array(
                        'status' => get_option('spb_wpengine_connection_status', 'not_configured') === 'connected' ? 'good' : 'warning',
                        'value' => get_option('spb_wpengine_connection_status', 'not_configured'),
                        'message' => $this->get_wpengine_status_message()
                    ),
                    'database_status' => array(
                        'status' => $this->check_database_status(),
                        'value' => 'Connected',
                        'message' => 'Database connection is healthy'
                    )
                ),
                'last_checked' => current_time('timestamp')
            );

            // Determine overall status
            $warning_count = 0;
            $error_count = 0;
            foreach ($health['checks'] as $check) {
                if ($check['status'] === 'warning') $warning_count++;
                if ($check['status'] === 'error') $error_count++;
            }

            if ($error_count > 0) {
                $health['overall_status'] = 'error';
            } elseif ($warning_count > 0) {
                $health['overall_status'] = 'warning';
            }

            wp_send_json_success($health);

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error checking system health: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for getting performance metrics
     *
     * @since    3.2.0
     */
    public function ajax_get_performance_metrics() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $metrics = array(
                'page_load_time' => get_option('spb_avg_page_load_time', 1.2),
                'ai_generation_time' => get_option('spb_avg_ai_generation_time', 3.5),
                'cache_hit_rate' => get_option('spb_cache_hit_rate', 78),
                'search_response_time' => get_option('spb_avg_search_response_time', 0.8),
                'personalization_accuracy' => get_option('spb_personalization_accuracy', 92),
                'content_approval_rate' => get_option('spb_content_approval_rate', 85),
                'user_engagement_score' => get_option('spb_user_engagement_score', 76),
                'last_updated' => current_time('timestamp')
            );

            // Add performance trends
            $metrics['trends'] = array(
                'page_load_trend' => $this->calculate_performance_trend('page_load_time'),
                'ai_generation_trend' => $this->calculate_performance_trend('ai_generation_time'),
                'cache_trend' => $this->calculate_performance_trend('cache_hit_rate'),
                'engagement_trend' => $this->calculate_performance_trend('user_engagement_score')
            );

            wp_send_json_success($metrics);

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error fetching performance metrics: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for getting notifications
     *
     * @since    3.2.0
     */
    public function ajax_get_notifications() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $notifications = get_option('spb_admin_notifications', array());
            
            // Add system-generated notifications
            $notifications = array_merge($notifications, $this->generate_system_notifications());
            
            // Sort by priority and timestamp
            usort($notifications, function($a, $b) {
                if ($a['priority'] === $b['priority']) {
                    return $b['timestamp'] - $a['timestamp'];
                }
                return $a['priority'] === 'high' ? -1 : 1;
            });

            wp_send_json_success(array(
                'notifications' => array_slice($notifications, 0, 10), // Limit to 10 most recent
                'unread_count' => count(array_filter($notifications, function($n) { return !$n['read']; }))
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error fetching notifications: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for dismissing notifications
     *
     * @since    3.2.0
     */
    public function ajax_dismiss_notification() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $notification_id = sanitize_text_field($_POST['notification_id']);
            $notifications = get_option('spb_admin_notifications', array());
            
            // Mark notification as read
            foreach ($notifications as &$notification) {
                if ($notification['id'] === $notification_id) {
                    $notification['read'] = true;
                    break;
                }
            }
            
            update_option('spb_admin_notifications', $notifications);
            
            wp_send_json_success(array('message' => __('Notification dismissed.', 'smart-page-builder')));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error dismissing notification: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for running system diagnostics
     *
     * @since    3.2.0
     */
    public function ajax_run_system_diagnostics() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $diagnostics = array(
                'started_at' => current_time('timestamp'),
                'tests' => array()
            );

            // Test 1: Database connectivity
            $diagnostics['tests']['database'] = $this->test_database_connectivity();
            
            // Test 2: File permissions
            $diagnostics['tests']['file_permissions'] = $this->test_file_permissions();
            
            // Test 3: WP Engine connection
            $diagnostics['tests']['wpengine'] = $this->test_wpengine_connectivity();
            
            // Test 4: Cache functionality
            $diagnostics['tests']['cache'] = $this->test_cache_functionality();
            
            // Test 5: Plugin dependencies
            $diagnostics['tests']['dependencies'] = $this->test_plugin_dependencies();

            $diagnostics['completed_at'] = current_time('timestamp');
            $diagnostics['duration'] = $diagnostics['completed_at'] - $diagnostics['started_at'];
            
            // Determine overall result
            $failed_tests = array_filter($diagnostics['tests'], function($test) {
                return !$test['passed'];
            });
            
            $diagnostics['overall_result'] = empty($failed_tests) ? 'passed' : 'failed';
            $diagnostics['failed_count'] = count($failed_tests);
            $diagnostics['total_count'] = count($diagnostics['tests']);

            wp_send_json_success($diagnostics);

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error running diagnostics: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * Calculate trend for a metric
     *
     * @since    3.2.0
     * @param    string    $metric    Metric name
     * @param    mixed     $current   Current value
     * @return   array               Trend data
     */
    private function calculate_trend($metric, $current) {
        $previous = get_option("spb_previous_{$metric}", $current);
        $change = $current - $previous;
        $percentage = $previous > 0 ? round(($change / $previous) * 100, 1) : 0;
        
        // Update previous value for next calculation
        update_option("spb_previous_{$metric}", $current);
        
        return array(
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
            'change' => $change,
            'percentage' => $percentage
        );
    }

    /**
     * Get recent activity data
     *
     * @since    3.2.0
     * @param    int    $limit    Number of activities to return
     * @return   array           Activity data
     */
    private function get_recent_activity_data($limit = 10) {
        global $wpdb;
        $activity = array();
        
        // Get recent AI-generated pages if available
        $ai_pages_table = $wpdb->prefix . 'spb_generated_pages';
        if ($wpdb->get_var("SHOW TABLES LIKE '$ai_pages_table'") == $ai_pages_table) {
            $recent_pages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $ai_pages_table 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                     ORDER BY created_at DESC 
                     LIMIT %d",
                    $limit
                )
            );
            
            foreach ($recent_pages as $page) {
                $activity[] = array(
                    'type' => 'ai_page_generated',
                    'title' => 'AI Page Generated',
                    'description' => 'Page created for query: ' . esc_html($page->search_query),
                    'time' => strtotime($page->created_at),
                    'icon' => '🤖'
                );
            }
        }
        
        // Get recent content approvals if available
        $approvals_table = $wpdb->prefix . 'spb_content_approvals';
        if ($wpdb->get_var("SHOW TABLES LIKE '$approvals_table'") == $approvals_table) {
            $recent_approvals = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $approvals_table 
                     WHERE approved_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                     AND status = 'approved'
                     ORDER BY approved_at DESC 
                     LIMIT %d",
                    3
                )
            );
            
            foreach ($recent_approvals as $approval) {
                $activity[] = array(
                    'type' => 'content_approved',
                    'title' => 'Content Approved',
                    'description' => 'AI-generated content approved',
                    'time' => strtotime($approval->approved_at),
                    'icon' => '✅'
                );
            }
        }
        
        // Get recent personalization updates if available
        $personalization_table = $wpdb->prefix . 'spb_user_interests';
        if ($wpdb->get_var("SHOW TABLES LIKE '$personalization_table'") == $personalization_table) {
            $recent_updates = $wpdb->get_var(
                "SELECT COUNT(*) FROM $personalization_table 
                 WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
            );
            
            if ($recent_updates > 0) {
                $activity[] = array(
                    'type' => 'personalization_updated',
                    'title' => 'Personalization Updated',
                    'description' => sprintf('Interest vectors updated for %d users', $recent_updates),
                    'time' => current_time('timestamp') - 3600,
                    'icon' => '🎯'
                );
            }
        }
        
        // Get recent posts as fallback
        if (empty($activity)) {
            $recent_posts = get_posts(array(
                'numberposts' => 3,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            foreach ($recent_posts as $post) {
                $activity[] = array(
                    'type' => 'post_published',
                    'title' => 'Post Published',
                    'description' => 'New post: ' . $post->post_title,
                    'time' => strtotime($post->post_date),
                    'icon' => '📝'
                );
            }
        }
        
        // Sort by time and limit
        if (!empty($activity)) {
            usort($activity, function($a, $b) {
                return $b['time'] - $a['time'];
            });
            $activity = array_slice($activity, 0, $limit);
        }
        
        return $activity;
    }

    /**
     * Helper methods for system health checks
     */
    private function check_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        $memory_in_bytes = $this->convert_to_bytes($memory_limit);
        return $memory_in_bytes >= 134217728 ? 'good' : 'warning'; // 128MB minimum
    }

    private function get_memory_status_message() {
        $memory_limit = ini_get('memory_limit');
        $memory_in_bytes = $this->convert_to_bytes($memory_limit);
        return $memory_in_bytes >= 134217728 ? 
            'Memory limit is sufficient' : 
            'Consider increasing memory limit to 128MB or higher';
    }

    private function get_wpengine_status_message() {
        $status = get_option('spb_wpengine_connection_status', 'not_configured');
        switch ($status) {
            case 'connected':
                return 'WP Engine AI Toolkit is connected and working';
            case 'error':
                return 'WP Engine connection has errors - check settings';
            default:
                return 'WP Engine AI Toolkit not configured';
        }
    }

    private function check_database_status() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT 1");
        return $result === '1' ? 'good' : 'error';
    }

    private function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value)-1]);
        $value = (int) $value;
        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }
        return $value;
    }

    private function calculate_performance_trend($metric) {
        // Get historical data for trend calculation
        $previous_value = get_option("spb_previous_{$metric}", null);
        $current_value = get_option("spb_{$metric}", 0);
        
        if ($previous_value === null) {
            // No historical data available
            update_option("spb_previous_{$metric}", $current_value);
            return 'stable';
        }
        
        $change = $current_value - $previous_value;
        $percentage_change = $previous_value > 0 ? ($change / $previous_value) * 100 : 0;
        
        // Update previous value for next calculation
        update_option("spb_previous_{$metric}", $current_value);
        
        // Determine trend direction based on percentage change
        if (abs($percentage_change) < 2) {
            return 'stable';
        } elseif ($percentage_change > 0) {
            return 'up';
        } else {
            return 'down';
        }
    }

    private function generate_system_notifications() {
        $notifications = array();
        
        // Check for pending approvals
        $pending_count = get_option('spb_pending_approvals_count', 0);
        if ($pending_count > 0) {
            $notifications[] = array(
                'id' => 'pending_approvals_' . date('Y-m-d'),
                'type' => 'info',
                'priority' => 'medium',
                'title' => 'Pending Content Approvals',
                'message' => sprintf('%d pieces of AI-generated content are waiting for approval.', $pending_count),
                'timestamp' => current_time('timestamp'),
                'read' => false,
                'action_url' => admin_url('admin.php?page=smart-page-builder-approval')
            );
        }
        
        // Check WP Engine connection
        if (get_option('spb_wpengine_connection_status') === 'error') {
            $notifications[] = array(
                'id' => 'wpengine_error_' . date('Y-m-d'),
                'type' => 'warning',
                'priority' => 'high',
                'title' => 'WP Engine Connection Issue',
                'message' => 'There is an issue with your WP Engine AI Toolkit connection.',
                'timestamp' => current_time('timestamp'),
                'read' => false,
                'action_url' => admin_url('admin.php?page=smart-page-builder-wpengine')
            );
        }
        
        return $notifications;
    }

    private function test_database_connectivity() {
        global $wpdb;
        try {
            $result = $wpdb->get_var("SELECT 1");
            return array(
                'passed' => $result === '1',
                'message' => $result === '1' ? 'Database connection successful' : 'Database connection failed',
                'details' => array('query_result' => $result)
            );
        } catch (Exception $e) {
            return array(
                'passed' => false,
                'message' => 'Database connection error: ' . $e->getMessage(),
                'details' => array('error' => $e->getMessage())
            );
        }
    }

    private function test_file_permissions() {
        $upload_dir = wp_upload_dir();
        $test_file = $upload_dir['basedir'] . '/spb_test_' . time() . '.txt';
        
        try {
            $result = file_put_contents($test_file, 'test');
            if ($result !== false) {
                unlink($test_file);
                return array(
                    'passed' => true,
                    'message' => 'File permissions are correct',
                    'details' => array('upload_dir' => $upload_dir['basedir'])
                );
            } else {
                return array(
                    'passed' => false,
                    'message' => 'Cannot write to upload directory',
                    'details' => array('upload_dir' => $upload_dir['basedir'])
                );
            }
        } catch (Exception $e) {
            return array(
                'passed' => false,
                'message' => 'File permission test failed: ' . $e->getMessage(),
                'details' => array('error' => $e->getMessage())
            );
        }
    }

    private function test_wpengine_connectivity() {
        $api_url = get_option('spb_wpengine_api_url', '');
        $access_token = get_option('spb_wpengine_access_token', '');
        
        if (empty($api_url) || empty($access_token)) {
            return array(
                'passed' => false,
                'message' => 'WP Engine credentials not configured',
                'details' => array('configured' => false)
            );
        }
        
        $test_result = $this->perform_wpengine_connection_test($api_url, $access_token, get_option('spb_wpengine_site_id', ''));
        
        return array(
            'passed' => $test_result['success'],
            'message' => $test_result['message'],
            'details' => $test_result['details'] ?? array()
        );
    }

    private function test_cache_functionality() {
        $test_key = 'spb_cache_test_' . time();
        $test_value = 'test_value_' . rand(1000, 9999);
        
        try {
            set_transient($test_key, $test_value, 60);
            $retrieved = get_transient($test_key);
            delete_transient($test_key);
            
            return array(
                'passed' => $retrieved === $test_value,
                'message' => $retrieved === $test_value ? 'Cache functionality working' : 'Cache functionality failed',
                'details' => array(
                    'set_value' => $test_value,
                    'retrieved_value' => $retrieved
                )
            );
        } catch (Exception $e) {
            return array(
                'passed' => false,
                'message' => 'Cache test failed: ' . $e->getMessage(),
                'details' => array('error' => $e->getMessage())
            );
        }
    }

    private function test_plugin_dependencies() {
        $required_functions = array('wp_remote_get', 'wp_remote_post', 'wp_cache_flush', 'add_action');
        $missing_functions = array();
        
        foreach ($required_functions as $function) {
            if (!function_exists($function)) {
                $missing_functions[] = $function;
            }
        }
        
        return array(
            'passed' => empty($missing_functions),
            'message' => empty($missing_functions) ? 'All required functions available' : 'Missing required functions',
            'details' => array(
                'required_functions' => $required_functions,
                'missing_functions' => $missing_functions
            )
        );
    }

    /**
     * AJAX handler for getting content list
     *
     * @since    3.2.0
     */
    public function ajax_get_content_list() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            global $wpdb;
            
            // Get filter parameters
            $status_filter = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
            $type_filter = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'all';
            $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

            // Check if content tables exist
            $content_table = $wpdb->prefix . 'spb_generated_content';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'") == $content_table;
            
            if (!$table_exists) {
                // Return empty result if table doesn't exist
                wp_send_json_success(array(
                    'content' => array(),
                    'total' => 0,
                    'message' => 'No AI-generated content found. Content will appear here after AI page generation.'
                ));
                return;
            }

            // Build query
            $where_conditions = array('1=1');
            $query_params = array();

            if ($status_filter !== 'all') {
                $where_conditions[] = 'status = %s';
                $query_params[] = $status_filter;
            }

            if ($type_filter !== 'all') {
                $where_conditions[] = 'content_type = %s';
                $query_params[] = $type_filter;
            }

            if (!empty($search_term)) {
                $where_conditions[] = '(title LIKE %s OR search_query LIKE %s)';
                $query_params[] = '%' . $wpdb->esc_like($search_term) . '%';
                $query_params[] = '%' . $wpdb->esc_like($search_term) . '%';
            }

            $where_clause = implode(' AND ', $where_conditions);

            // Get total count
            $count_query = "SELECT COUNT(*) FROM $content_table WHERE $where_clause";
            if (!empty($query_params)) {
                $count_query = $wpdb->prepare($count_query, $query_params);
            }
            $total = $wpdb->get_var($count_query);

            // Get content
            $content_query = "SELECT * FROM $content_table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
            $final_params = array_merge($query_params, array($limit, $offset));
            $content_query = $wpdb->prepare($content_query, $final_params);
            $content = $wpdb->get_results($content_query);

            wp_send_json_success(array(
                'content' => $content,
                'total' => intval($total),
                'message' => sprintf('Found %d content items', $total)
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error fetching content: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for deleting content
     *
     * @since    3.2.0
     */
    public function ajax_delete_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_admin_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        $content_id = sanitize_text_field($_POST['content_id']);
        
        if (empty($content_id)) {
            wp_send_json_error(array('message' => 'Invalid content ID'));
            return;
        }
        
        global $wpdb;
        $content_table = $wpdb->prefix . 'spb_generated_content';
        
        // Check if this is a WordPress post (prefixed with 'post_')
        if (strpos($content_id, 'post_') === 0) {
            // Extract the actual post ID
            $post_id = intval(str_replace('post_', '', $content_id));
            
            if ($post_id <= 0) {
                wp_send_json_error(array('message' => 'Invalid post ID'));
                return;
            }
            
            // Check if post exists
            $post = get_post($post_id);
            if (!$post) {
                wp_send_json_error(array('message' => 'Post not found'));
                return;
            }
            
            // Delete the WordPress post
            $result = wp_delete_post($post_id, true); // true = force delete (bypass trash)
            
            if (!$result) {
                wp_send_json_error(array('message' => 'Failed to delete post'));
                return;
            }
            
            wp_send_json_success(array('message' => 'Content deleted successfully'));
            return;
        }
        
        // Handle custom table content
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'") == $content_table;
        
        if (!$table_exists) {
            wp_send_json_error(array('message' => 'Content table does not exist'));
            return;
        }
        
        // Delete the content from custom table
        $result = $wpdb->delete(
            $content_table,
            array('id' => $content_id),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Failed to delete content from database'));
            return;
        }
        
        if ($result === 0) {
            wp_send_json_error(array('message' => 'Content not found'));
            return;
        }
        
        wp_send_json_success(array('message' => 'Content deleted successfully'));
    }

    /**
     * AJAX handler for bulk content actions
     *
     * @since    3.2.0
     */
    public function ajax_bulk_content_action() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
            $content_ids = isset($_POST['content_ids']) ? array_map('intval', $_POST['content_ids']) : array();
            
            if (empty($action) || empty($content_ids)) {
                wp_send_json_error(array('message' => __('Invalid action or content IDs.', 'smart-page-builder')));
                return;
            }

            global $wpdb;
            $content_table = $wpdb->prefix . 'spb_generated_content';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'") == $content_table;
            
            if (!$table_exists) {
                wp_send_json_error(array('message' => __('Content table not found.', 'smart-page-builder')));
                return;
            }

            $success_count = 0;
            $error_count = 0;
            $errors = array();

            foreach ($content_ids as $content_id) {
                try {
                    switch ($action) {
                        case 'delete':
                            $result = $wpdb->delete(
                                $content_table,
                                array('id' => $content_id),
                                array('%d')
                            );
                            break;
                            
                        case 'approve':
                            $result = $wpdb->update(
                                $content_table,
                                array(
                                    'status' => 'approved',
                                    'approved_at' => current_time('mysql'),
                                    'approved_by' => get_current_user_id()
                                ),
                                array('id' => $content_id),
                                array('%s', '%s', '%d'),
                                array('%d')
                            );
                            break;
                            
                        case 'reject':
                            $result = $wpdb->update(
                                $content_table,
                                array(
                                    'status' => 'rejected',
                                    'rejected_at' => current_time('mysql'),
                                    'rejected_by' => get_current_user_id()
                                ),
                                array('id' => $content_id),
                                array('%s', '%s', '%d'),
                                array('%d')
                            );
                            break;
                            
                        default:
                            $errors[] = sprintf('Unknown action: %s for content ID: %d', $action, $content_id);
                            $error_count++;
                            continue 2;
                    }

                    if ($result !== false) {
                        $success_count++;
                    } else {
                        $errors[] = sprintf('Failed to %s content ID: %d', $action, $content_id);
                        $error_count++;
                    }

                } catch (Exception $e) {
                    $errors[] = sprintf('Error processing content ID %d: %s', $content_id, $e->getMessage());
                    $error_count++;
                }
            }

            // Log the bulk action
            error_log(sprintf('SPB: Bulk %s action - Success: %d, Errors: %d, User: %d', 
                $action, 
                $success_count, 
                $error_count, 
                get_current_user_id()
            ));

            $response = array(
                'success_count' => $success_count,
                'error_count' => $error_count,
                'total_processed' => count($content_ids),
                'action' => $action
            );

            if ($error_count > 0) {
                $response['errors'] = $errors;
                $response['message'] = sprintf(
                    __('Bulk %s completed with %d successes and %d errors.', 'smart-page-builder'),
                    $action,
                    $success_count,
                    $error_count
                );
            } else {
                $response['message'] = sprintf(
                    __('Bulk %s completed successfully for %d items.', 'smart-page-builder'),
                    $action,
                    $success_count
                );
            }

            wp_send_json_success($response);

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error processing bulk action: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for individual content actions (approve/reject)
     *
     * @since    3.2.0
     */
    public function ajax_content_action() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $action = isset($_POST['content_action']) ? sanitize_text_field($_POST['content_action']) : '';
            $content_id = isset($_POST['content_id']) ? sanitize_text_field($_POST['content_id']) : '';
            
            if (empty($action) || empty($content_id)) {
                wp_send_json_error(array('message' => __('Invalid action or content ID.', 'smart-page-builder')));
                return;
            }

            global $wpdb;
            $content_table = $wpdb->prefix . 'spb_generated_content';
            
            // Check if this is a WordPress post (prefixed with 'post_')
            if (strpos($content_id, 'post_') === 0) {
                // Extract the actual post ID
                $post_id = intval(str_replace('post_', '', $content_id));
                
                if ($post_id <= 0) {
                    wp_send_json_error(array('message' => __('Invalid post ID.', 'smart-page-builder')));
                    return;
                }
                
                // Check if post exists
                $post = get_post($post_id);
                if (!$post) {
                    wp_send_json_error(array('message' => __('Post not found.', 'smart-page-builder')));
                    return;
                }
                
                // Update post status based on action
                $new_status = '';
                switch ($action) {
                    case 'approve':
                        $new_status = 'publish';
                        break;
                    case 'reject':
                        $new_status = 'draft';
                        break;
                    default:
                        wp_send_json_error(array('message' => __('Unknown action.', 'smart-page-builder')));
                        return;
                }
                
                // Update the post status
                $result = wp_update_post(array(
                    'ID' => $post_id,
                    'post_status' => $new_status
                ));
                
                if (is_wp_error($result)) {
                    wp_send_json_error(array('message' => __('Failed to update post status.', 'smart-page-builder')));
                    return;
                }
                
                wp_send_json_success(array('message' => sprintf(__('Content %s successfully.', 'smart-page-builder'), $action . 'd')));
                return;
            }
            
            // Handle custom table content
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'") == $content_table;
            
            if (!$table_exists) {
                wp_send_json_error(array('message' => __('Content table not found.', 'smart-page-builder')));
                return;
            }

            // Prepare update data based on action
            $update_data = array();
            switch ($action) {
                case 'approve':
                    $update_data = array(
                        'status' => 'approved',
                        'approved_at' => current_time('mysql'),
                        'approved_by' => get_current_user_id()
                    );
                    break;
                    
                case 'reject':
                    $update_data = array(
                        'status' => 'rejected',
                        'rejected_at' => current_time('mysql'),
                        'rejected_by' => get_current_user_id()
                    );
                    break;
                    
                default:
                    wp_send_json_error(array('message' => __('Unknown action.', 'smart-page-builder')));
                    return;
            }

            // Update the content
            $result = $wpdb->update(
                $content_table,
                $update_data,
                array('id' => $content_id),
                array('%s', '%s', '%d'),
                array('%d')
            );

            if ($result === false) {
                wp_send_json_error(array('message' => __('Failed to update content.', 'smart-page-builder')));
                return;
            }

            if ($result === 0) {
                wp_send_json_error(array('message' => __('Content not found.', 'smart-page-builder')));
                return;
            }

            // Log the action
            error_log(sprintf('SPB: Content %s - ID: %s, User: %d', 
                $action, 
                $content_id, 
                get_current_user_id()
            ));

            wp_send_json_success(array(
                'message' => sprintf(__('Content %s successfully.', 'smart-page-builder'), $action . 'd'),
                'content_id' => $content_id,
                'action' => $action
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error processing content action: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for previewing content
     *
     * @since    3.2.0
     */
    public function ajax_preview_content() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
            
            if ($content_id <= 0) {
                wp_send_json_error(array('message' => __('Invalid content ID.', 'smart-page-builder')));
                return;
            }

            global $wpdb;
            $content_table = $wpdb->prefix . 'spb_generated_content';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'") == $content_table;
            
            if (!$table_exists) {
                wp_send_json_error(array('message' => __('Content table not found.', 'smart-page-builder')));
                return;
            }

            // Get content
            $content = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $content_table WHERE id = %d",
                $content_id
            ));

            if (!$content) {
                wp_send_json_error(array('message' => __('Content not found.', 'smart-page-builder')));
                return;
            }

            // Decode content data if it's JSON
            $content_data = $content->content_data;
            if (is_string($content_data)) {
                $decoded = json_decode($content_data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $content_data = $decoded;
                }
            }

            wp_send_json_success(array(
                'content' => $content,
                'content_data' => $content_data,
                'preview_html' => $this->generate_content_preview_html($content, $content_data)
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error previewing content: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * AJAX handler for editing content
     *
     * @since    3.2.0
     */
    public function ajax_edit_content() {
        check_ajax_referer('spb_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'smart-page-builder')));
            return;
        }

        try {
            $content_id = isset($_POST['content_id']) ? intval($_POST['content_id']) : 0;
            $updated_data = isset($_POST['content_data']) ? $_POST['content_data'] : array();
            
            if ($content_id <= 0) {
                wp_send_json_error(array('message' => __('Invalid content ID.', 'smart-page-builder')));
                return;
            }

            global $wpdb;
            $content_table = $wpdb->prefix . 'spb_generated_content';
            
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$content_table'") == $content_table;
            
            if (!$table_exists) {
                wp_send_json_error(array('message' => __('Content table not found.', 'smart-page-builder')));
                return;
            }

            // Sanitize and prepare update data
            $update_data = array(
                'updated_at' => current_time('mysql'),
                'updated_by' => get_current_user_id()
            );

            if (isset($updated_data['title'])) {
                $update_data['title'] = sanitize_text_field($updated_data['title']);
            }

            if (isset($updated_data['content_data'])) {
                $update_data['content_data'] = wp_json_encode($updated_data['content_data']);
            }

            if (isset($updated_data['status'])) {
                $update_data['status'] = sanitize_text_field($updated_data['status']);
            }

            // Update the content
            $result = $wpdb->update(
                $content_table,
                $update_data,
                array('id' => $content_id),
                null,
                array('%d')
            );

            if ($result === false) {
                wp_send_json_error(array('message' => __('Failed to update content.', 'smart-page-builder')));
                return;
            }

            // Log the update
            error_log(sprintf('SPB: Content updated - ID: %d, User: %d', 
                $content_id, 
                get_current_user_id()
            ));

            wp_send_json_success(array(
                'message' => __('Content updated successfully.', 'smart-page-builder'),
                'updated_id' => $content_id
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Error updating content: ', 'smart-page-builder') . $e->getMessage()));
        }
    }

    /**
     * Generate HTML preview for content
     *
     * @since    3.2.0
     * @param    object    $content       Content object
     * @param    mixed     $content_data  Content data
     * @return   string                   Preview HTML
     */
    private function generate_content_preview_html($content, $content_data) {
        $html = '<div class="spb-content-preview">';
        
        // Title
        $html .= '<h2>' . esc_html($content->title ?? 'Untitled') . '</h2>';
        
        // Meta information
        $html .= '<div class="spb-content-meta">';
        $html .= '<p><strong>Search Query:</strong> ' . esc_html($content->search_query ?? 'N/A') . '</p>';
        $html .= '<p><strong>Type:</strong> ' . esc_html($content->content_type ?? 'N/A') . '</p>';
        $html .= '<p><strong>Status:</strong> ' . esc_html($content->status ?? 'N/A') . '</p>';
        $html .= '<p><strong>Quality Score:</strong> ' . esc_html($content->quality_score ?? 'N/A') . '%</p>';
        $html .= '<p><strong>Created:</strong> ' . esc_html($content->created_at ?? 'N/A') . '</p>';
        $html .= '</div>';
        
        // Content preview
        if (is_array($content_data)) {
            $html .= '<div class="spb-content-sections">';
            
            foreach ($content_data as $section => $data) {
                $html .= '<div class="spb-content-section">';
                $html .= '<h3>' . esc_html(ucfirst($section)) . '</h3>';
                
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        if (is_string($value)) {
                            $html .= '<p><strong>' . esc_html(ucfirst($key)) . ':</strong> ' . esc_html(wp_trim_words($value, 20)) . '</p>';
                        }
                    }
                } else if (is_string($data)) {
                    $html .= '<p>' . esc_html(wp_trim_words($data, 30)) . '</p>';
                }
                
                $html .= '</div>';
            }
            
            $html .= '</div>';
        } else if (is_string($content_data)) {
            $html .= '<div class="spb-content-text">';
            $html .= '<p>' . esc_html(wp_trim_words($content_data, 50)) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
