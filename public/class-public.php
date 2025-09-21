<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/public
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/public
 * @since      1.0.0
 */
class Smart_Page_Builder_Public {

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
            plugin_dir_url(__FILE__) . 'css/smart-page-builder-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
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
            plugin_dir_url(__FILE__) . 'js/smart-page-builder-public.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize script for AJAX
        wp_localize_script(
            $this->plugin_name,
            'spb_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spb_ajax_nonce')
            )
        );
    }

    /**
     * Add rewrite rules for smart pages
     *
     * @since    1.0.0
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^smart-page/([^/]+)/?$',
            'index.php?spb_dynamic_page=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     *
     * @since    1.0.0
     * @param    array    $vars    The query vars
     * @return   array             The modified query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'spb_dynamic_page';
        return $vars;
    }

    /**
     * Handle smart page requests
     *
     * @since    1.0.0
     */
    public function handle_smart_page_requests() {
        $page_slug = get_query_var('spb_dynamic_page');
        
        if ($page_slug) {
            $this->serve_dynamic_page($page_slug);
        }
    }

    /**
     * Serve dynamic page
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $page_slug    The page slug
     */
    private function serve_dynamic_page($page_slug) {
        // Look for approved dynamic page
        $pages = get_posts(array(
            'post_type'   => 'spb_dynamic_page',
            'post_status' => 'publish',
            'name'        => $page_slug,
            'numberposts' => 1
        ));

        if (!empty($pages)) {
            $page = $pages[0];
            $this->load_dynamic_page_template($page);
        } else {
            // Page not found or not approved - redirect to search
            wp_redirect(home_url('/?s=' . urlencode(str_replace('-', ' ', $page_slug))));
            exit;
        }
    }

    /**
     * Load dynamic page template
     *
     * @since    1.0.0
     * @access   private
     * @param    WP_Post    $page    The page object
     */
    private function load_dynamic_page_template($page) {
        // Set up global post data
        global $post;
        $post = $page;
        setup_postdata($post);

        // Load template
        $template = locate_template('smart-page-builder/dynamic-page.php');
        if (!$template) {
            $template = SPB_PLUGIN_DIR . 'templates/dynamic-page.php';
        }

        include $template;
        exit;
    }
}
