<?php
/**
 * Smart Page Builder
 *
 * @package           SmartPageBuilder
 * @author            Smart Page Builder Team
 * @copyright         2024 Smart Page Builder
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Smart Page Builder
 * Plugin URI:        https://smartpagebuilder.com
 * Description:       Revolutionary dual AI platform combining content generation with real-time personalization. Transform search queries into valuable content while delivering personalized user experiences through advanced Interest Vector analysis.
 * Version:           3.6.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Smart Page Builder Team
 * Author URI:        https://smartpagebuilder.com
 * Text Domain:       smart-page-builder
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://smartpagebuilder.com
 * Network:           false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin version
 */
define('SPB_VERSION', '3.6.1');

/**
 * Plugin directory path
 */
define('SPB_PLUGIN_DIR', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL
 */
define('SPB_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename
 */
define('SPB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin file path
 */
define('SPB_PLUGIN_FILE', __FILE__);

/**
 * Feature flags for v3.0 functionality
 */
define('SPB_V3_PERSONALIZATION', true);
define('SPB_V3_INTEREST_VECTORS', true);
define('SPB_V3_SIGNAL_COLLECTION', true);
define('SPB_V3_COMPONENT_PERSONALIZATION', true);

/**
 * Feature flags for v3.1 functionality
 */
define('SPB_V3_SEARCH_GENERATION', true);
define('SPB_V3_AI_CONTENT_GENERATION', true);
define('SPB_V3_QUALITY_ASSESSMENT', true);
define('SPB_V3_CONTENT_APPROVAL', true);

/**
 * Database version for migrations
 */
define('SPB_DB_VERSION', '3.0.11');

/**
 * Minimum WordPress version required
 */
define('SPB_MIN_WP_VERSION', '6.0');

/**
 * Minimum PHP version required
 */
define('SPB_MIN_PHP_VERSION', '8.0');

/**
 * Check system requirements
 */
function spb_check_requirements() {
    $errors = [];
    
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), SPB_MIN_WP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Smart Page Builder requires WordPress %s or higher. You are running version %s.', 'smart-page-builder'),
            SPB_MIN_WP_VERSION,
            get_bloginfo('version')
        );
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, SPB_MIN_PHP_VERSION, '<')) {
        $errors[] = sprintf(
            __('Smart Page Builder requires PHP %s or higher. You are running version %s.', 'smart-page-builder'),
            SPB_MIN_PHP_VERSION,
            PHP_VERSION
        );
    }
    
    // Check required PHP extensions
    $required_extensions = ['json', 'mbstring', 'curl'];
    foreach ($required_extensions as $extension) {
        if (!extension_loaded($extension)) {
            $errors[] = sprintf(
                __('Smart Page Builder requires the PHP %s extension.', 'smart-page-builder'),
                $extension
            );
        }
    }
    
    return $errors;
}

/**
 * Display admin notice for requirement errors
 */
function spb_requirements_notice() {
    $errors = spb_check_requirements();
    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . __('Smart Page Builder activation failed:', 'smart-page-builder') . '</strong><br>';
        echo implode('<br>', $errors);
        echo '</p></div>';
        
        // Deactivate the plugin
        deactivate_plugins(SPB_PLUGIN_BASENAME);
        return false;
    }
    return true;
}

/**
 * Plugin activation hook
 */
function spb_activate() {
    // Check requirements
    if (!spb_check_requirements()) {
        add_action('admin_notices', 'spb_requirements_notice');
        return;
    }
    
    // Load the activator class
    require_once SPB_PLUGIN_DIR . 'includes/class-activator.php';
    Smart_Page_Builder_Activator::activate();
}

/**
 * Plugin deactivation hook
 */
function spb_deactivate() {
    require_once SPB_PLUGIN_DIR . 'includes/class-deactivator.php';
    Smart_Page_Builder_Deactivator::deactivate();
}

/**
 * Plugin uninstall hook
 */
function spb_uninstall() {
    // This is handled in uninstall.php
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'spb_activate');
register_deactivation_hook(__FILE__, 'spb_deactivate');

/**
 * Initialize the plugin
 */
function spb_init() {
    // Check requirements on every load
    $errors = spb_check_requirements();
    if (!empty($errors)) {
        add_action('admin_notices', 'spb_requirements_notice');
        return;
    }
    
    // Load the main plugin class
    require_once SPB_PLUGIN_DIR . 'includes/class-smart-page-builder.php';
    
    // Initialize the plugin
    $plugin = new Smart_Page_Builder();
    $plugin->run();
}

// Initialize the plugin after WordPress is fully loaded
add_action('plugins_loaded', 'spb_init');

/**
 * Add plugin action links
 */
function spb_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=smart-page-builder') . '">' . __('Settings', 'smart-page-builder') . '</a>';
    array_unshift($links, $settings_link);
    
    if (SPB_V3_PERSONALIZATION) {
        $personalization_link = '<a href="' . admin_url('admin.php?page=smart-page-builder-personalization') . '">' . __('Personalization', 'smart-page-builder') . '</a>';
        array_unshift($links, $personalization_link);
    }
    
    return $links;
}
add_filter('plugin_action_links_' . SPB_PLUGIN_BASENAME, 'spb_plugin_action_links');

/**
 * Add plugin meta links
 */
function spb_plugin_meta_links($links, $file) {
    if ($file === SPB_PLUGIN_BASENAME) {
        $links[] = '<a href="https://smartpagebuilder.com/docs/" target="_blank">' . __('Documentation', 'smart-page-builder') . '</a>';
        $links[] = '<a href="https://smartpagebuilder.com/support/" target="_blank">' . __('Support', 'smart-page-builder') . '</a>';
        $links[] = '<a href="https://smartpagebuilder.com/pro/" target="_blank">' . __('Pro Version', 'smart-page-builder') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'spb_plugin_meta_links', 10, 2);

/**
 * Load plugin textdomain for translations
 */
function spb_load_textdomain() {
    load_plugin_textdomain(
        'smart-page-builder',
        false,
        dirname(SPB_PLUGIN_BASENAME) . '/languages/'
    );
}
add_action('plugins_loaded', 'spb_load_textdomain');

/**
 * Add admin body class for plugin pages
 */
function spb_admin_body_class($classes) {
    $screen = get_current_screen();
    if ($screen && strpos($screen->id, 'smart-page-builder') !== false) {
        $classes .= ' smart-page-builder-admin';
    }
    return $classes;
}
add_filter('admin_body_class', 'spb_admin_body_class');

/**
 * Plugin upgrade check
 */
function spb_upgrade_check() {
    $installed_version = get_option('spb_version', '0.0.0');
    
    if (version_compare($installed_version, SPB_VERSION, '<')) {
        // Run upgrade routine
        require_once SPB_PLUGIN_DIR . 'includes/class-activator.php';
        Smart_Page_Builder_Activator::upgrade($installed_version, SPB_VERSION);
        
        // Update version
        update_option('spb_version', SPB_VERSION);
        update_option('spb_db_version', SPB_DB_VERSION);
    }
}
add_action('plugins_loaded', 'spb_upgrade_check');

/**
 * Emergency deactivation function
 */
function spb_emergency_deactivate() {
    if (isset($_GET['spb_emergency_deactivate']) && current_user_can('activate_plugins')) {
        deactivate_plugins(SPB_PLUGIN_BASENAME);
        wp_redirect(admin_url('plugins.php?deactivate=true'));
        exit;
    }
}
add_action('admin_init', 'spb_emergency_deactivate');

/**
 * Plugin compatibility check
 */
function spb_compatibility_check() {
    // Check for conflicting plugins
    $conflicting_plugins = [
        'wp-page-builder/wp-page-builder.php',
        'elementor/elementor.php',
        'beaver-builder-lite-version/fl-builder.php'
    ];
    
    $active_conflicts = [];
    foreach ($conflicting_plugins as $plugin) {
        if (is_plugin_active($plugin)) {
            $active_conflicts[] = $plugin;
        }
    }
    
    if (!empty($active_conflicts)) {
        add_action('admin_notices', function() use ($active_conflicts) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>' . __('Smart Page Builder Warning:', 'smart-page-builder') . '</strong><br>';
            echo __('The following plugins may conflict with Smart Page Builder:', 'smart-page-builder') . '<br>';
            echo implode('<br>', $active_conflicts);
            echo '</p></div>';
        });
    }
}
add_action('admin_init', 'spb_compatibility_check');

/**
 * Debug information for support
 */
function spb_debug_info() {
    if (!current_user_can('manage_options') || !isset($_GET['spb_debug'])) {
        return;
    }
    
    $debug_info = [
        'Plugin Version' => SPB_VERSION,
        'WordPress Version' => get_bloginfo('version'),
        'PHP Version' => PHP_VERSION,
        'Active Theme' => get_template(),
        'Multisite' => is_multisite() ? 'Yes' : 'No',
        'Memory Limit' => ini_get('memory_limit'),
        'Max Execution Time' => ini_get('max_execution_time'),
        'V3 Personalization' => SPB_V3_PERSONALIZATION ? 'Enabled' : 'Disabled',
        'Database Version' => get_option('spb_db_version', 'Not Set')
    ];
    
    header('Content-Type: application/json');
    echo json_encode($debug_info, JSON_PRETTY_PRINT);
    exit;
}
add_action('init', 'spb_debug_info');

/**
 * Performance monitoring
 */
function spb_performance_monitor() {
    if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) {
        $start_time = microtime(true);
        
        add_action('wp_footer', function() use ($start_time) {
            $end_time = microtime(true);
            $execution_time = ($end_time - $start_time) * 1000;
            
            if ($execution_time > 300) { // Alert if over 300ms
                echo '<!-- Smart Page Builder Performance Alert: ' . round($execution_time, 2) . 'ms -->';
            }
        });
    }
}
add_action('init', 'spb_performance_monitor');
