<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin
 * @since      1.0.0
 */
class Smart_Page_Builder_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
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
     * @since    1.0.0
     */
    public function enqueue_styles() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Smart_Page_Builder_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Smart_Page_Builder_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'css/smart-page-builder-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Smart_Page_Builder_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Smart_Page_Builder_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url(__FILE__) . 'js/smart-page-builder-admin.js',
            array('jquery'),
            $this->version,
            false
        );
    }

    /**
     * Add admin menu items
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Smart Page Builder', 'smart-page-builder'),
            __('Smart Page Builder', 'smart-page-builder'),
            'spb_manage_settings',
            'smart-page-builder',
            array($this, 'display_plugin_admin_page'),
            'dashicons-admin-page',
            30
        );

        add_submenu_page(
            'smart-page-builder',
            __('Settings', 'smart-page-builder'),
            __('Settings', 'smart-page-builder'),
            'spb_manage_settings',
            'smart-page-builder-settings',
            array($this, 'display_settings_page')
        );

        add_submenu_page(
            'smart-page-builder',
            __('Analytics', 'smart-page-builder'),
            __('Analytics', 'smart-page-builder'),
            'spb_view_analytics',
            'smart-page-builder-analytics',
            array($this, 'display_analytics_page')
        );
    }

    /**
     * Initialize admin settings
     *
     * @since    1.0.0
     */
    public function admin_init() {
        register_setting('spb_settings', 'spb_settings', array($this, 'validate_settings'));
        
        // Add AJAX handlers
        add_action('wp_ajax_spb_load_content_preview', array($this, 'handle_content_preview_ajax'));
    }

    /**
     * Display the main admin page
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page() {
        include_once 'partials/smart-page-builder-admin-display.php';
    }

    /**
     * Display the settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        include_once 'partials/smart-page-builder-admin-settings.php';
    }

    /**
     * Display the analytics page
     *
     * @since    1.0.0
     */
    public function display_analytics_page() {
        include_once 'partials/smart-page-builder-admin-analytics.php';
    }

    /**
     * Validate settings
     *
     * @since    1.0.0
     * @param    array    $input    The settings to validate
     * @return   array              The validated settings
     */
    public function validate_settings($input) {
        $valid = array();

        // Validate API key
        if (isset($input['api_key'])) {
            $valid['api_key'] = sanitize_text_field($input['api_key']);
        }

        // Validate confidence threshold
        if (isset($input['confidence_threshold'])) {
            $threshold = floatval($input['confidence_threshold']);
            $valid['confidence_threshold'] = ($threshold >= 0.1 && $threshold <= 1.0) ? $threshold : 0.6;
        }

        // Validate cache duration
        if (isset($input['cache_duration'])) {
            $duration = absint($input['cache_duration']);
            $valid['cache_duration'] = ($duration >= 300 && $duration <= 86400) ? $duration : 3600;
        }

        return $valid;
    }

    /**
     * Handle AJAX request for content preview
     *
     * @since    1.0.0
     */
    public function handle_content_preview_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_load_preview')) {
            wp_die('Security check failed');
        }

        // Check user capabilities
        if (!current_user_can('spb_approve_content')) {
            wp_die('Insufficient permissions');
        }

        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);

        if (!$post || $post->post_type !== 'spb_dynamic_page') {
            wp_send_json_error('Invalid post');
        }

        // Get post meta data
        $sources = maybe_unserialize(get_post_meta($post_id, '_spb_sources', true));
        $search_term = get_post_meta($post_id, '_spb_search_term', true);
        $confidence = get_post_meta($post_id, '_spb_confidence', true);
        $content_type = get_post_meta($post_id, '_spb_content_type', true);

        // Format sources for display
        $sources_html = '';
        if (!empty($sources) && is_array($sources)) {
            $sources_html = '<ul>';
            foreach ($sources as $source) {
                $sources_html .= '<li>';
                $sources_html .= '<strong><a href="' . esc_url($source['url']) . '" target="_blank">' . esc_html($source['title']) . '</a></strong>';
                $sources_html .= '<br><small>Relevance: ' . number_format($source['relevance_score'], 2) . '</small>';
                $sources_html .= '<br>' . esc_html($source['excerpt']);
                $sources_html .= '</li>';
            }
            $sources_html .= '</ul>';
        } else {
            $sources_html = '<p>No sources available.</p>';
        }

        // Send response
        wp_send_json_success(array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'sources' => $sources_html,
            'meta' => array(
                'search_term' => $search_term,
                'confidence' => $confidence,
                'content_type' => $content_type
            )
        ));
    }
}
