<?php
/**
 * The public-facing functionality of the plugin
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Public Class
 *
 * Defines the plugin name, version, and hooks for the public-facing side of the site.
 */
class Smart_Page_Builder_Public {

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
     * @param    string    $plugin_name       The name of the plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    3.0.0
     */
    public function enqueue_styles() {
        // Only enqueue on pages where the plugin is active
        if (!$this->should_enqueue_assets()) {
            return;
        }

        wp_enqueue_style(
            $this->plugin_name,
            SPB_PLUGIN_URL . 'public/css/smart-page-builder-public.css',
            array(),
            $this->version,
            'all'
        );

        // Enqueue personalization styles if v3.0 features are enabled
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            wp_enqueue_style(
                $this->plugin_name . '-personalization',
                SPB_PLUGIN_URL . 'public/css/smart-page-builder-personalization.css',
                array($this->plugin_name),
                $this->version,
                'all'
            );
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    3.0.0
     */
    public function enqueue_scripts() {
        // Only enqueue on pages where the plugin is active
        if (!$this->should_enqueue_assets()) {
            return;
        }

        wp_enqueue_script(
            $this->plugin_name,
            SPB_PLUGIN_URL . 'public/js/smart-page-builder-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script with public data
        wp_localize_script(
            $this->plugin_name,
            'spb_public',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spb_public_nonce'),
                'plugin_url' => SPB_PLUGIN_URL,
                'version' => $this->version,
                'personalization_enabled' => defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION
            )
        );

        // Enqueue personalization scripts if v3.0 features are enabled
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            wp_enqueue_script(
                $this->plugin_name . '-personalization',
                SPB_PLUGIN_URL . 'public/js/smart-page-builder-personalization.js',
                array($this->plugin_name),
                $this->version,
                false
            );

            // Localize personalization script
            wp_localize_script(
                $this->plugin_name . '-personalization',
                'spb_personalization',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('spb_personalization_nonce'),
                    'session_id' => $this->get_session_id(),
                    'tracking_enabled' => $this->is_tracking_enabled()
                )
            );
        }
    }

    /**
     * Initialize public functionality
     *
     * @since    3.0.0
     */
    public function init() {
        // Register shortcodes
        $this->register_shortcodes();

        // Initialize v3.0 personalization if enabled
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            $this->init_personalization();
        }

        // Initialize search-triggered page generation if enabled
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            $this->init_search_generation();
        }
    }

    /**
     * Register plugin shortcodes
     *
     * @since    3.0.0
     */
    private function register_shortcodes() {
        add_shortcode('smart_page_builder', array($this, 'shortcode_smart_page_builder'));
        add_shortcode('spb_component', array($this, 'shortcode_spb_component'));
        
        // v3.0 personalization shortcodes
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            add_shortcode('spb_personalized', array($this, 'shortcode_personalized_content'));
            add_shortcode('spb_interest_based', array($this, 'shortcode_interest_based_content'));
        }
    }

    /**
     * Main plugin shortcode
     *
     * @since    3.0.0
     * @param    array     $atts    Shortcode attributes.
     * @param    string    $content Shortcode content.
     * @return   string             Shortcode output.
     */
    public function shortcode_smart_page_builder($atts, $content = '') {
        $atts = shortcode_atts(
            array(
                'type' => 'default',
                'id' => '',
                'class' => '',
                'personalize' => 'false'
            ),
            $atts,
            'smart_page_builder'
        );

        $output = '<div class="smart-page-builder-component ' . esc_attr($atts['class']) . '"';
        if (!empty($atts['id'])) {
            $output .= ' id="' . esc_attr($atts['id']) . '"';
        }
        $output .= '>';

        // Apply personalization if enabled and requested
        if ($atts['personalize'] === 'true' && defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            $content = $this->apply_personalization($content, $atts);
        }

        $output .= do_shortcode($content);
        $output .= '</div>';

        return $output;
    }

    /**
     * Component shortcode
     *
     * @since    3.0.0
     * @param    array     $atts    Shortcode attributes.
     * @param    string    $content Shortcode content.
     * @return   string             Shortcode output.
     */
    public function shortcode_spb_component($atts, $content = '') {
        $atts = shortcode_atts(
            array(
                'type' => 'text',
                'variant' => 'default',
                'personalize' => 'false'
            ),
            $atts,
            'spb_component'
        );

        $component_class = 'spb-component spb-component-' . esc_attr($atts['type']);
        if (!empty($atts['variant'])) {
            $component_class .= ' spb-variant-' . esc_attr($atts['variant']);
        }

        $output = '<div class="' . $component_class . '">';

        // Apply personalization if enabled
        if ($atts['personalize'] === 'true' && defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            $content = $this->apply_personalization($content, $atts);
        }

        $output .= do_shortcode($content);
        $output .= '</div>';

        return $output;
    }

    /**
     * Personalized content shortcode
     *
     * @since    3.0.0
     * @param    array     $atts    Shortcode attributes.
     * @param    string    $content Shortcode content.
     * @return   string             Shortcode output.
     */
    public function shortcode_personalized_content($atts, $content = '') {
        if (!defined('SPB_V3_PERSONALIZATION') || !SPB_V3_PERSONALIZATION) {
            return do_shortcode($content);
        }

        $atts = shortcode_atts(
            array(
                'interests' => '',
                'fallback' => '',
                'min_confidence' => '0.5'
            ),
            $atts,
            'spb_personalized'
        );

        // Apply personalization logic
        $personalized_content = $this->apply_personalization($content, $atts);
        
        return do_shortcode($personalized_content);
    }

    /**
     * Interest-based content shortcode
     *
     * @since    3.0.0
     * @param    array     $atts    Shortcode attributes.
     * @param    string    $content Shortcode content.
     * @return   string             Shortcode output.
     */
    public function shortcode_interest_based_content($atts, $content = '') {
        if (!defined('SPB_V3_PERSONALIZATION') || !SPB_V3_PERSONALIZATION) {
            return do_shortcode($content);
        }

        $atts = shortcode_atts(
            array(
                'interest' => '',
                'threshold' => '0.3',
                'fallback' => ''
            ),
            $atts,
            'spb_interest_based'
        );

        // Check user interest alignment
        if ($this->user_has_interest($atts['interest'], floatval($atts['threshold']))) {
            return do_shortcode($content);
        } elseif (!empty($atts['fallback'])) {
            return do_shortcode($atts['fallback']);
        }

        return '';
    }

    /**
     * Initialize personalization features
     *
     * @since    3.0.0
     */
    private function init_personalization() {
        // Initialize signal collection
        if (class_exists('SPB_Signal_Collector')) {
            $signal_collector = new SPB_Signal_Collector();
            add_action('wp_footer', array($signal_collector, 'output_tracking_script'));
        }

        // Initialize session management
        if (class_exists('SPB_Session_Manager')) {
            $session_manager = new SPB_Session_Manager();
            add_action('init', array($session_manager, 'start_session'));
        }
    }

    /**
     * Initialize search-triggered page generation
     *
     * @since    3.1.0
     */
    private function init_search_generation() {
        // Initialize search integration
        if (class_exists('SPB_Search_Integration_Manager')) {
            $search_manager = new SPB_Search_Integration_Manager();
            add_action('init', array($search_manager, 'init'));
        }
    }

    /**
     * Check if assets should be enqueued
     *
     * @since    3.0.0
     * @return   bool    True if assets should be enqueued.
     */
    private function should_enqueue_assets() {
        // Always enqueue on pages with Smart Page Builder content
        global $post;
        
        if (is_admin()) {
            return false;
        }

        // Check if current page has plugin shortcodes
        if ($post && (
            has_shortcode($post->post_content, 'smart_page_builder') ||
            has_shortcode($post->post_content, 'spb_component') ||
            has_shortcode($post->post_content, 'spb_personalized') ||
            has_shortcode($post->post_content, 'spb_interest_based')
        )) {
            return true;
        }

        // Check if this is a search-generated page
        if (get_query_var('spb_search_page')) {
            return true;
        }

        // Allow filtering
        return apply_filters('spb_should_enqueue_public_assets', false);
    }

    /**
     * Apply personalization to content
     *
     * @since    3.0.0
     * @param    string    $content Content to personalize.
     * @param    array     $atts    Personalization attributes.
     * @return   string             Personalized content.
     */
    private function apply_personalization($content, $atts) {
        if (!class_exists('SPB_Component_Personalizer')) {
            return $content;
        }

        $personalizer = new SPB_Component_Personalizer();
        return $personalizer->personalize_content($content, $atts);
    }

    /**
     * Check if user has specific interest
     *
     * @since    3.0.0
     * @param    string    $interest   Interest to check.
     * @param    float     $threshold  Minimum threshold.
     * @return   bool                  True if user has interest above threshold.
     */
    private function user_has_interest($interest, $threshold) {
        if (!class_exists('SPB_Interest_Vector_Calculator')) {
            return false;
        }

        $calculator = new SPB_Interest_Vector_Calculator();
        $user_interests = $calculator->get_user_interests($this->get_session_id());
        
        return isset($user_interests[$interest]) && $user_interests[$interest] >= $threshold;
    }

    /**
     * Get current session ID
     *
     * @since    3.0.0
     * @return   string    Session ID.
     */
    private function get_session_id() {
        if (class_exists('SPB_Session_Manager')) {
            $session_manager = new SPB_Session_Manager();
            return $session_manager->get_session_id();
        }
        
        return session_id() ?: wp_generate_uuid4();
    }

    /**
     * Check if tracking is enabled
     *
     * @since    3.0.0
     * @return   bool    True if tracking is enabled.
     */
    private function is_tracking_enabled() {
        if (class_exists('SPB_Privacy_Manager')) {
            $privacy_manager = new SPB_Privacy_Manager();
            return $privacy_manager->is_tracking_allowed();
        }
        
        return true; // Default to enabled if privacy manager not available
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
}
