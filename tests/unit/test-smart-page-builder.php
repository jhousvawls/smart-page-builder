<?php
/**
 * Class Test_Smart_Page_Builder
 *
 * @package SmartPageBuilder
 */

/**
 * Sample test case for the Smart Page Builder plugin.
 */
class Test_Smart_Page_Builder extends WP_UnitTestCase {

    /**
     * Test plugin activation
     */
    public function test_plugin_activation() {
        // Test that the plugin activates without errors
        $this->assertTrue(class_exists('Smart_Page_Builder'));
    }

    /**
     * Test plugin constants are defined
     */
    public function test_plugin_constants() {
        $this->assertTrue(defined('SPB_VERSION'));
        $this->assertTrue(defined('SPB_PLUGIN_DIR'));
        $this->assertTrue(defined('SPB_PLUGIN_URL'));
        $this->assertTrue(defined('SPB_PLUGIN_BASENAME'));
    }

    /**
     * Test plugin version
     */
    public function test_plugin_version() {
        $this->assertEquals('1.0.0', SPB_VERSION);
    }

    /**
     * Test main plugin class instantiation
     */
    public function test_plugin_class_instantiation() {
        $plugin = new Smart_Page_Builder();
        $this->assertInstanceOf('Smart_Page_Builder', $plugin);
        $this->assertEquals('smart-page-builder', $plugin->get_plugin_name());
        $this->assertEquals('1.0.0', $plugin->get_version());
    }

    /**
     * Test loader class
     */
    public function test_loader_class() {
        $this->assertTrue(class_exists('Smart_Page_Builder_Loader'));
        $loader = new Smart_Page_Builder_Loader();
        $this->assertInstanceOf('Smart_Page_Builder_Loader', $loader);
    }

    /**
     * Test activator class
     */
    public function test_activator_class() {
        $this->assertTrue(class_exists('Smart_Page_Builder_Activator'));
        $this->assertTrue(method_exists('Smart_Page_Builder_Activator', 'activate'));
    }

    /**
     * Test deactivator class
     */
    public function test_deactivator_class() {
        $this->assertTrue(class_exists('Smart_Page_Builder_Deactivator'));
        $this->assertTrue(method_exists('Smart_Page_Builder_Deactivator', 'deactivate'));
    }

    /**
     * Test database tables creation
     */
    public function test_database_tables_exist() {
        global $wpdb;

        // Simulate activation
        Smart_Page_Builder_Activator::activate();

        // Check if custom tables exist
        $tables = [
            $wpdb->prefix . 'spb_ai_insights',
            $wpdb->prefix . 'spb_dynamic_rules',
            $wpdb->prefix . 'spb_ab_tests',
            $wpdb->prefix . 'spb_metrics',
            $wpdb->prefix . 'spb_generated_content'
        ];

        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $this->assertEquals($table, $table_exists, "Table $table should exist after activation");
        }
    }

    /**
     * Test default options are set
     */
    public function test_default_options() {
        // Simulate activation
        Smart_Page_Builder_Activator::activate();

        // Check default options
        $this->assertTrue(get_option('spb_cache_enabled'));
        $this->assertEquals(3600, get_option('spb_cache_duration'));
        $this->assertEquals('author', get_option('spb_min_user_role'));
        $this->assertEquals(0.6, get_option('spb_confidence_threshold'));
    }

    /**
     * Test custom capabilities are added
     */
    public function test_custom_capabilities() {
        // Simulate activation
        Smart_Page_Builder_Activator::activate();

        $admin_role = get_role('administrator');
        $this->assertTrue($admin_role->has_cap('spb_manage_settings'));
        $this->assertTrue($admin_role->has_cap('spb_generate_content'));
        $this->assertTrue($admin_role->has_cap('spb_view_analytics'));
        $this->assertTrue($admin_role->has_cap('spb_approve_content'));
    }

    /**
     * Test cron jobs are scheduled
     */
    public function test_cron_jobs_scheduled() {
        // Simulate activation
        Smart_Page_Builder_Activator::activate();

        $this->assertNotFalse(wp_next_scheduled('spb_daily_cleanup'));
        $this->assertNotFalse(wp_next_scheduled('spb_hourly_cache_cleanup'));
    }

    /**
     * Test plugin deactivation cleanup
     */
    public function test_plugin_deactivation() {
        // First activate
        Smart_Page_Builder_Activator::activate();

        // Then deactivate
        Smart_Page_Builder_Deactivator::deactivate();

        // Check that cron jobs are cleared
        $this->assertFalse(wp_next_scheduled('spb_daily_cleanup'));
        $this->assertFalse(wp_next_scheduled('spb_hourly_cache_cleanup'));
    }
}
