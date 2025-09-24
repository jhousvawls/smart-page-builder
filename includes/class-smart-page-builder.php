<?php
/**
 * The core plugin class
 *
 * @package Smart_Page_Builder
 * @since   3.0.11
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Smart Page Builder class
 */
class Smart_Page_Builder {
    
    /**
     * Plugin version
     */
    const VERSION = '3.4.9';
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin loader
     */
    protected $loader;
    
    /**
     * Plugin name
     */
    protected $plugin_name;
    
    /**
     * Plugin version
     */
    protected $version;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->version = self::VERSION;
        $this->plugin_name = 'smart-page-builder';
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        // Load the plugin loader
        require_once SPB_PLUGIN_DIR . 'includes/class-loader.php';
        $this->loader = new Smart_Page_Builder_Loader();
        
        // Load internationalization
        require_once SPB_PLUGIN_DIR . 'includes/class-i18n.php';
        
        // Load admin functionality
        if (is_admin()) {
            require_once SPB_PLUGIN_DIR . 'admin/class-admin.php';
        }
        
        // Load public functionality
        require_once SPB_PLUGIN_DIR . 'public/class-public.php';
        
        // Load v3.0 personalization features if enabled
        if (defined('SPB_V3_PERSONALIZATION') && SPB_V3_PERSONALIZATION) {
            $this->load_v3_features();
        }
    }
    
    /**
     * Load v3.0 personalization features
     */
    private function load_v3_features() {
        // Check if v3.0 tables exist
        if (!$this->is_v3_personalization_available()) {
            return;
        }
        
        // Load v3.0 core classes
        $v3_classes = [
            'includes/class-interest-vector-calculator.php',
            'includes/class-signal-collector.php',
            'includes/class-component-personalizer.php',
            'includes/class-privacy-manager.php',
            'includes/class-session-manager.php'
        ];
        
        foreach ($v3_classes as $class_file) {
            $file_path = SPB_PLUGIN_DIR . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Load optional v3.0 classes
        $optional_classes = [
            'includes/class-collaborative-filter.php',
            'includes/class-redis-manager.php',
            'includes/class-api-manager.php',
            'includes/class-webhook-manager.php'
        ];
        
        foreach ($optional_classes as $class_file) {
            $file_path = SPB_PLUGIN_DIR . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Load v3.1+ search generation features if enabled
        if (defined('SPB_V3_SEARCH_GENERATION') && SPB_V3_SEARCH_GENERATION) {
            $this->load_search_generation_features();
        }
        
        // Load v3.1+ AI content generation features if enabled
        if (defined('SPB_V3_AI_CONTENT_GENERATION') && SPB_V3_AI_CONTENT_GENERATION) {
            $this->load_ai_content_generation_features();
        }
    }
    
    /**
     * Load search generation features
     */
    private function load_search_generation_features() {
        // Load search-related classes (with graceful degradation)
        $search_classes = [
            'includes/class-wpengine-api-client.php',
            'includes/class-query-enhancement-engine.php',
            'includes/class-wpengine-integration-hub.php',
            'includes/class-search-database-manager.php',
            'includes/class-search-integration-manager.php',
            'includes/class-search-integration-manager-debug.php'  // DEBUG VERSION
        ];
        
        foreach ($search_classes as $class_file) {
            $file_path = SPB_PLUGIN_DIR . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Initialize search integration manager (production version)
        if (class_exists('SPB_Search_Integration_Manager')) {
            new SPB_Search_Integration_Manager();
            error_log('SPB DEBUG: Using production search integration manager');
        }
        
        // Create missing tables if needed
        if (!$this->is_search_generation_available()) {
            $this->create_missing_search_tables();
        }
    }
    
    /**
     * Load AI content generation features
     */
    private function load_ai_content_generation_features() {
        // Load AI generation classes
        $ai_classes = [
            'includes/class-ai-page-generation-engine.php',
            'includes/class-template-engine.php',
            'includes/class-content-approval-system.php',
            'includes/class-quality-assessment-engine.php'
        ];
        
        foreach ($ai_classes as $class_file) {
            $file_path = SPB_PLUGIN_DIR . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Load component generators
        $component_generators = [
            'includes/component-generators/abstract-component-generator.php',
            'includes/component-generators/class-hero-generator.php',
            'includes/component-generators/class-article-generator.php',
            'includes/component-generators/class-cta-generator.php'
        ];
        
        foreach ($component_generators as $class_file) {
            $file_path = SPB_PLUGIN_DIR . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Check if search generation is available
     */
    public function is_search_generation_available() {
        global $wpdb;
        
        $required_tables = [
            $wpdb->prefix . 'spb_search_pages',
            $wpdb->prefix . 'spb_query_enhancements',
            $wpdb->prefix . 'spb_generated_components'
        ];
        
        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if v3.0 personalization is available
     */
    public function is_v3_personalization_available() {
        global $wpdb;
        
        $required_tables = [
            $wpdb->prefix . 'spb_user_interest_vectors',
            $wpdb->prefix . 'spb_user_signals',
            $wpdb->prefix . 'spb_personalization_rules',
            $wpdb->prefix . 'spb_component_variants',
            $wpdb->prefix . 'spb_user_consent'
        ];
        
        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Set the plugin locale
     */
    private function set_locale() {
        $plugin_i18n = new Smart_Page_Builder_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }
    
    /**
     * Define admin hooks
     */
    private function define_admin_hooks() {
        if (!is_admin()) {
            return;
        }
        
        $plugin_admin = new Smart_Page_Builder_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin enqueue scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Admin init
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
    }
    
    /**
     * Define public hooks
     */
    private function define_public_hooks() {
        $plugin_public = new Smart_Page_Builder_Public($this->get_plugin_name(), $this->get_version());
        
        // Public enqueue scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Public init
        $this->loader->add_action('init', $plugin_public, 'init');
    }
    
    /**
     * Run the plugin
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Get plugin name
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }
    
    /**
     * Get plugin version
     */
    public function get_version() {
        return $this->version;
    }
    
    /**
     * Get the loader
     */
    public function get_loader() {
        return $this->loader;
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Activation logic handled in class-activator.php
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Deactivation logic handled in class-deactivator.php
    }
    
    /**
     * Create missing search tables on demand
     */
    private function create_missing_search_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Search pages table
        $search_pages_table = $wpdb->prefix . 'spb_search_pages';
        if ($wpdb->get_var("SHOW TABLES LIKE '$search_pages_table'") !== $search_pages_table) {
            $sql = "CREATE TABLE $search_pages_table (
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
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Query enhancements table
        $query_enhancements_table = $wpdb->prefix . 'spb_query_enhancements';
        if ($wpdb->get_var("SHOW TABLES LIKE '$query_enhancements_table'") !== $query_enhancements_table) {
            $sql = "CREATE TABLE $query_enhancements_table (
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
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Generated components table
        $generated_components_table = $wpdb->prefix . 'spb_generated_components';
        if ($wpdb->get_var("SHOW TABLES LIKE '$generated_components_table'") !== $generated_components_table) {
            $sql = "CREATE TABLE $generated_components_table (
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
                KEY quality_score (quality_score)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        error_log('SPB: Created missing search tables on demand');
    }
}
