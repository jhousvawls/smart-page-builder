<?php
/**
 * Phase 2 Automatic Activation Tests
 *
 * Tests for the automatic Phase 2 activation functionality.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 * @since      2.0.0
 */

/**
 * Phase 2 Activation Test Class
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 */
class Test_Phase_2_Activation extends WP_UnitTestCase {

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Clean up any existing options
        delete_option('spb_version');
        delete_option('spb_db_version');
        delete_option('spb_phase_2_available');
        delete_option('spb_disable_phase_2');
    }

    /**
     * Test plugin activation creates Phase 2 options
     */
    public function test_activation_creates_phase_2_options() {
        // Simulate plugin activation
        Smart_Page_Builder_Activator::activate();
        
        // Verify Phase 2 options are set
        $this->assertEquals('2.0.0', get_option('spb_version'));
        $this->assertEquals('2.0.0', get_option('spb_db_version'));
        $this->assertTrue(get_option('spb_phase_2_available'));
    }

    /**
     * Test Phase 2 database tables are created
     */
    public function test_activation_creates_phase_2_tables() {
        global $wpdb;
        
        // Simulate plugin activation
        Smart_Page_Builder_Activator::activate();
        
        // Check Phase 2 tables exist
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        $ab_tests_table = $wpdb->prefix . 'spb_ab_tests';
        $ab_variants_table = $wpdb->prefix . 'spb_ab_test_variants';
        $ab_results_table = $wpdb->prefix . 'spb_ab_test_results';
        
        $this->assertEquals($analytics_table, $wpdb->get_var("SHOW TABLES LIKE '{$analytics_table}'"));
        $this->assertEquals($ab_tests_table, $wpdb->get_var("SHOW TABLES LIKE '{$ab_tests_table}'"));
        $this->assertEquals($ab_variants_table, $wpdb->get_var("SHOW TABLES LIKE '{$ab_variants_table}'"));
        $this->assertEquals($ab_results_table, $wpdb->get_var("SHOW TABLES LIKE '{$ab_results_table}'"));
    }

    /**
     * Test Phase 2 availability detection
     */
    public function test_phase_2_availability_detection() {
        // Initially Phase 2 should not be available
        $plugin = new Smart_Page_Builder();
        $this->assertFalse($this->call_private_method($plugin, 'is_phase_2_available'));
        
        // After activation, Phase 2 should be available
        Smart_Page_Builder_Activator::activate();
        $this->assertTrue($this->call_private_method($plugin, 'is_phase_2_available'));
    }

    /**
     * Test Phase 2 can be disabled via admin setting
     */
    public function test_phase_2_can_be_disabled() {
        // Activate plugin first
        Smart_Page_Builder_Activator::activate();
        
        $plugin = new Smart_Page_Builder();
        $this->assertTrue($this->call_private_method($plugin, 'is_phase_2_available'));
        
        // Disable Phase 2 via admin setting
        update_option('spb_disable_phase_2', true);
        $this->assertFalse($this->call_private_method($plugin, 'is_phase_2_available'));
    }

    /**
     * Test graceful fallback when Phase 2 tables don't exist
     */
    public function test_graceful_fallback_without_tables() {
        global $wpdb;
        
        // Set Phase 2 as available but don't create tables
        update_option('spb_phase_2_available', true);
        
        $plugin = new Smart_Page_Builder();
        
        // Should return false because tables don't exist
        $this->assertFalse($this->call_private_method($plugin, 'is_phase_2_available'));
    }

    /**
     * Test upgrade from Phase 1 to Phase 2
     */
    public function test_upgrade_from_phase_1() {
        // Simulate Phase 1 installation
        update_option('spb_version', '1.0.0');
        update_option('spb_db_version', '1.0.0');
        
        // Simulate upgrade activation
        Smart_Page_Builder_Activator::activate();
        
        // Verify upgrade to Phase 2
        $this->assertEquals('2.0.0', get_option('spb_version'));
        $this->assertEquals('2.0.0', get_option('spb_db_version'));
        $this->assertTrue(get_option('spb_phase_2_available'));
    }

    /**
     * Test admin class Phase 2 detection
     */
    public function test_admin_phase_2_detection() {
        $admin = new Smart_Page_Builder_Admin('smart-page-builder', '2.0.0');
        
        // Initially should return false
        $this->assertFalse($this->call_private_method($admin, 'is_phase_2_available'));
        
        // After activation should return true
        Smart_Page_Builder_Activator::activate();
        $this->assertTrue($this->call_private_method($admin, 'is_phase_2_available'));
    }

    /**
     * Test default options are set during activation
     */
    public function test_default_options_set() {
        Smart_Page_Builder_Activator::activate();
        
        // Check some default options exist
        $this->assertNotFalse(get_option('spb_cache_enabled'));
        $this->assertNotFalse(get_option('spb_confidence_threshold'));
        $this->assertNotFalse(get_option('spb_max_content_length'));
    }

    /**
     * Test capabilities are added during activation
     */
    public function test_capabilities_added() {
        Smart_Page_Builder_Activator::activate();
        
        $admin_role = get_role('administrator');
        
        $this->assertTrue($admin_role->has_cap('spb_manage_settings'));
        $this->assertTrue($admin_role->has_cap('spb_generate_content'));
        $this->assertTrue($admin_role->has_cap('spb_view_analytics'));
        $this->assertTrue($admin_role->has_cap('spb_approve_content'));
    }

    /**
     * Test cron jobs are scheduled during activation
     */
    public function test_cron_jobs_scheduled() {
        Smart_Page_Builder_Activator::activate();
        
        $this->assertNotFalse(wp_next_scheduled('spb_daily_cleanup'));
        $this->assertNotFalse(wp_next_scheduled('spb_hourly_cache_cleanup'));
    }

    /**
     * Helper method to call private methods for testing
     */
    private function call_private_method($object, $method_name, $args = array()) {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }

    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        global $wpdb;
        
        // Clean up test options
        delete_option('spb_version');
        delete_option('spb_db_version');
        delete_option('spb_phase_2_available');
        delete_option('spb_disable_phase_2');
        
        // Clean up test tables
        $tables = array(
            $wpdb->prefix . 'spb_analytics',
            $wpdb->prefix . 'spb_ab_tests',
            $wpdb->prefix . 'spb_ab_test_variants',
            $wpdb->prefix . 'spb_ab_test_results'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        // Clean up cron jobs
        wp_clear_scheduled_hook('spb_daily_cleanup');
        wp_clear_scheduled_hook('spb_hourly_cache_cleanup');
        
        parent::tearDown();
    }
}
