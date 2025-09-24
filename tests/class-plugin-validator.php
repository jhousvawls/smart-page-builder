<?php
/**
 * Plugin Validator - Comprehensive testing framework to prevent fatal errors
 *
 * This class validates all plugin files and dependencies before deployment
 * to prevent the fatal errors we've been experiencing.
 *
 * @package Smart_Page_Builder
 * @since   3.1.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Plugin Validator
 *
 * Comprehensive validation and testing framework
 */
class SPB_Plugin_Validator {

    /**
     * Required core files that must exist
     *
     * @var array
     */
    private static $required_files = array(
        'smart-page-builder.php',
        'includes/class-smart-page-builder.php',
        'includes/class-loader.php',
        'includes/class-i18n.php',
        'includes/class-activator.php',
        'includes/class-deactivator.php',
        'admin/class-admin.php',
        'public/class-public.php'
    );

    /**
     * Required classes that must be defined
     *
     * @var array
     */
    private static $required_classes = array(
        'Smart_Page_Builder',
        'Smart_Page_Builder_Loader',
        'Smart_Page_Builder_i18n',
        'Smart_Page_Builder_Activator',
        'Smart_Page_Builder_Deactivator',
        'Smart_Page_Builder_Admin',
        'Smart_Page_Builder_Public'
    );

    /**
     * Required methods that must exist in classes
     *
     * @var array
     */
    private static $required_methods = array(
        'Smart_Page_Builder_Activator' => array('activate', 'upgrade'),
        'Smart_Page_Builder_Deactivator' => array('deactivate'),
        'Smart_Page_Builder_Admin' => array('enqueue_styles', 'enqueue_scripts'),
        'Smart_Page_Builder_Public' => array('enqueue_styles', 'enqueue_scripts')
    );

    /**
     * Required constants that must be defined
     *
     * @var array
     */
    private static $required_constants = array(
        'SPB_VERSION',
        'SPB_PLUGIN_DIR',
        'SPB_PLUGIN_URL',
        'SPB_PLUGIN_BASENAME',
        'SPB_V3_PERSONALIZATION',
        'SPB_V3_SEARCH_GENERATION'
    );

    /**
     * Validation results
     *
     * @var array
     */
    private static $validation_results = array();

    /**
     * Run comprehensive plugin validation
     *
     * @return array Validation results
     */
    public static function validate_plugin() {
        self::$validation_results = array(
            'success' => true,
            'errors' => array(),
            'warnings' => array(),
            'tests_run' => 0,
            'tests_passed' => 0,
            'timestamp' => current_time('mysql')
        );

        // Run all validation tests
        self::validate_file_structure();
        self::validate_class_definitions();
        self::validate_method_existence();
        self::validate_constants();
        self::validate_syntax();
        self::validate_dependencies();
        self::validate_database_schema();
        self::validate_hooks_and_filters();

        // Calculate final results
        self::$validation_results['tests_passed'] = self::$validation_results['tests_run'] - count(self::$validation_results['errors']);
        self::$validation_results['success'] = empty(self::$validation_results['errors']);

        return self::$validation_results;
    }

    /**
     * Validate file structure
     */
    private static function validate_file_structure() {
        self::$validation_results['tests_run']++;

        $plugin_dir = SPB_PLUGIN_DIR;
        $missing_files = array();

        foreach (self::$required_files as $file) {
            $file_path = $plugin_dir . $file;
            if (!file_exists($file_path)) {
                $missing_files[] = $file;
            }
        }

        if (!empty($missing_files)) {
            self::$validation_results['errors'][] = 'Missing required files: ' . implode(', ', $missing_files);
        }

        // Check for additional important files
        $important_files = array(
            'readme.txt',
            'CHANGELOG.md',
            'uninstall.php'
        );

        foreach ($important_files as $file) {
            if (!file_exists($plugin_dir . $file)) {
                self::$validation_results['warnings'][] = "Missing recommended file: {$file}";
            }
        }
    }

    /**
     * Validate class definitions
     */
    private static function validate_class_definitions() {
        self::$validation_results['tests_run']++;

        // Load all required files first
        foreach (self::$required_files as $file) {
            $file_path = SPB_PLUGIN_DIR . $file;
            if (file_exists($file_path) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                require_once $file_path;
            }
        }

        $missing_classes = array();
        foreach (self::$required_classes as $class) {
            if (!class_exists($class)) {
                $missing_classes[] = $class;
            }
        }

        if (!empty($missing_classes)) {
            self::$validation_results['errors'][] = 'Missing required classes: ' . implode(', ', $missing_classes);
        }
    }

    /**
     * Validate method existence
     */
    private static function validate_method_existence() {
        self::$validation_results['tests_run']++;

        foreach (self::$required_methods as $class => $methods) {
            if (class_exists($class)) {
                foreach ($methods as $method) {
                    if (!method_exists($class, $method)) {
                        self::$validation_results['errors'][] = "Missing method {$method} in class {$class}";
                    }
                }
            }
        }
    }

    /**
     * Validate constants
     */
    private static function validate_constants() {
        self::$validation_results['tests_run']++;

        $missing_constants = array();
        foreach (self::$required_constants as $constant) {
            if (!defined($constant)) {
                $missing_constants[] = $constant;
            }
        }

        if (!empty($missing_constants)) {
            self::$validation_results['errors'][] = 'Missing required constants: ' . implode(', ', $missing_constants);
        }
    }

    /**
     * Validate PHP syntax
     */
    private static function validate_syntax() {
        self::$validation_results['tests_run']++;

        $php_files = self::get_php_files();
        $syntax_errors = array();

        foreach ($php_files as $file) {
            $output = array();
            $return_var = 0;
            exec("php -l " . escapeshellarg($file), $output, $return_var);
            
            if ($return_var !== 0) {
                $syntax_errors[] = basename($file) . ': ' . implode(' ', $output);
            }
        }

        if (!empty($syntax_errors)) {
            self::$validation_results['errors'][] = 'PHP syntax errors found: ' . implode('; ', $syntax_errors);
        }
    }

    /**
     * Validate dependencies
     */
    private static function validate_dependencies() {
        self::$validation_results['tests_run']++;

        // Check WordPress version
        if (function_exists('get_bloginfo')) {
            $wp_version = get_bloginfo('version');
            if (version_compare($wp_version, SPB_MIN_WP_VERSION, '<')) {
                self::$validation_results['errors'][] = "WordPress version {$wp_version} is below minimum required " . SPB_MIN_WP_VERSION;
            }
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, SPB_MIN_PHP_VERSION, '<')) {
            self::$validation_results['errors'][] = "PHP version " . PHP_VERSION . " is below minimum required " . SPB_MIN_PHP_VERSION;
        }

        // Check required PHP extensions
        $required_extensions = array('json', 'mbstring', 'curl');
        $missing_extensions = array();

        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing_extensions[] = $extension;
            }
        }

        if (!empty($missing_extensions)) {
            self::$validation_results['errors'][] = 'Missing PHP extensions: ' . implode(', ', $missing_extensions);
        }
    }

    /**
     * Validate database schema
     */
    private static function validate_database_schema() {
        self::$validation_results['tests_run']++;

        if (!function_exists('get_option')) {
            self::$validation_results['warnings'][] = 'Cannot validate database schema - WordPress not loaded';
            return;
        }

        global $wpdb;

        $required_tables = array(
            $wpdb->prefix . 'spb_components',
            $wpdb->prefix . 'spb_page_templates',
            $wpdb->prefix . 'spb_analytics',
            $wpdb->prefix . 'spb_search_pages',
            $wpdb->prefix . 'spb_query_enhancements',
            $wpdb->prefix . 'spb_generated_components'
        );

        $missing_tables = array();
        foreach ($required_tables as $table) {
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
            if ($result !== $table) {
                $missing_tables[] = $table;
            }
        }

        if (!empty($missing_tables)) {
            self::$validation_results['warnings'][] = 'Missing database tables (will be created on activation): ' . implode(', ', $missing_tables);
        }
    }

    /**
     * Validate hooks and filters
     */
    private static function validate_hooks_and_filters() {
        self::$validation_results['tests_run']++;

        // Check if main plugin hooks are registered
        $required_hooks = array(
            'plugins_loaded' => 'spb_init',
            'admin_init' => 'spb_emergency_deactivate'
        );

        foreach ($required_hooks as $hook => $function) {
            if (function_exists('has_action') && !has_action($hook, $function)) {
                self::$validation_results['warnings'][] = "Hook {$hook} -> {$function} not registered";
            }
        }
    }

    /**
     * Get all PHP files in the plugin
     *
     * @return array
     */
    private static function get_php_files() {
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(SPB_PLUGIN_DIR)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Generate validation report
     *
     * @param array $results Validation results
     * @return string HTML report
     */
    public static function generate_report($results = null) {
        if ($results === null) {
            $results = self::validate_plugin();
        }

        $html = '<div class="spb-validation-report">';
        $html .= '<h2>Smart Page Builder Validation Report</h2>';
        $html .= '<p><strong>Timestamp:</strong> ' . $results['timestamp'] . '</p>';
        $html .= '<p><strong>Tests Run:</strong> ' . $results['tests_run'] . '</p>';
        $html .= '<p><strong>Tests Passed:</strong> ' . $results['tests_passed'] . '</p>';

        if ($results['success']) {
            $html .= '<div class="notice notice-success"><p><strong>✅ All validation tests passed!</strong></p></div>';
        } else {
            $html .= '<div class="notice notice-error"><p><strong>❌ Validation failed with ' . count($results['errors']) . ' error(s)</strong></p></div>';
        }

        if (!empty($results['errors'])) {
            $html .= '<h3>Errors</h3><ul>';
            foreach ($results['errors'] as $error) {
                $html .= '<li style="color: red;">❌ ' . esc_html($error) . '</li>';
            }
            $html .= '</ul>';
        }

        if (!empty($results['warnings'])) {
            $html .= '<h3>Warnings</h3><ul>';
            foreach ($results['warnings'] as $warning) {
                $html .= '<li style="color: orange;">⚠️ ' . esc_html($warning) . '</li>';
            }
            $html .= '</ul>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Run validation and display results in admin
     */
    public static function admin_validation_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'smart-page-builder'));
        }

        $results = self::validate_plugin();
        echo self::generate_report($results);
    }

    /**
     * Quick validation check (for use in main plugin)
     *
     * @return bool True if basic validation passes
     */
    public static function quick_check() {
        // Check if all required files exist
        foreach (self::$required_files as $file) {
            if (!file_exists(SPB_PLUGIN_DIR . $file)) {
                return false;
            }
        }

        // Check if all required constants are defined
        foreach (self::$required_constants as $constant) {
            if (!defined($constant)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log validation results
     *
     * @param array $results Validation results
     */
    public static function log_results($results) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'SPB Validation: ' . ($results['success'] ? 'PASSED' : 'FAILED');
            $log_message .= ' (' . $results['tests_passed'] . '/' . $results['tests_run'] . ' tests passed)';
            
            if (!empty($results['errors'])) {
                $log_message .= ' Errors: ' . implode('; ', $results['errors']);
            }
            
            error_log($log_message);
        }
    }
}

// Auto-run validation if in debug mode
if (defined('WP_DEBUG') && WP_DEBUG && defined('SPB_PLUGIN_DIR')) {
    add_action('admin_init', function() {
        if (current_user_can('manage_options') && isset($_GET['spb_validate'])) {
            $results = SPB_Plugin_Validator::validate_plugin();
            SPB_Plugin_Validator::log_results($results);
            
            if (!$results['success']) {
                add_action('admin_notices', function() use ($results) {
                    echo '<div class="notice notice-error">';
                    echo '<p><strong>Smart Page Builder Validation Failed!</strong></p>';
                    echo '<p>Errors found: ' . count($results['errors']) . '</p>';
                    echo '<p><a href="' . admin_url('admin.php?page=smart-page-builder-validation') . '">View Full Report</a></p>';
                    echo '</div>';
                });
            }
        }
    });
}
