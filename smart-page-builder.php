<?php
/**
 * Smart Page Builder
 *
 * @package           SmartPageBuilder
 * @author            John Housholder
 * @copyright         2025 Smart Page Builder
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Smart Page Builder
 * Plugin URI:        https://github.com/jhousvawls/smart-page-builder
 * Description:       AI-powered WordPress plugin that transforms user search queries into valuable, SEO-optimized content pages through intelligent content assembly and draft-first approval workflow.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            John Housholder
 * Author URI:        https://github.com/jhousvawls
 * Text Domain:       smart-page-builder
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Network:           false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPB_VERSION', '1.0.0');
define('SPB_PLUGIN_FILE', __FILE__);
define('SPB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPB_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load the main plugin class
require_once SPB_PLUGIN_DIR . 'includes/class-smart-page-builder.php';

/**
 * Initialize the plugin
 *
 * @since 1.0.0
 */
function smart_page_builder_init() {
    $plugin = new Smart_Page_Builder();
    $plugin->run();
}
add_action('plugins_loaded', 'smart_page_builder_init');

/**
 * Plugin activation hook
 *
 * @since 1.0.0
 */
function smart_page_builder_activate() {
    require_once SPB_PLUGIN_DIR . 'includes/class-activator.php';
    Smart_Page_Builder_Activator::activate();
}
register_activation_hook(__FILE__, 'smart_page_builder_activate');

/**
 * Plugin deactivation hook
 *
 * @since 1.0.0
 */
function smart_page_builder_deactivate() {
    require_once SPB_PLUGIN_DIR . 'includes/class-deactivator.php';
    Smart_Page_Builder_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'smart_page_builder_deactivate');

// Uninstall hook is handled by uninstall.php file as per WordPress Codex
