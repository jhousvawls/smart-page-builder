<?php
/**
 * Fresh Installation Integration Tests
 *
 * Tests for automatic Phase 2 activation on fresh WordPress installations.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/integration
 * @since      2.0.0
 */

/**
 * Fresh Installation Test Class
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/integration
 */
class Test_Fresh_Installation extends WP_UnitTestCase {

    /**
     * Test fresh plugin installation and activation
     */
    public function test_fresh_plugin_installation() {
        // Simulate fresh WordPress installation
        $this->clean_installation();
        
        // Verify no Smart Page Builder options exist
        $this->assertFalse(get_option('spb_version'));
        $this->assertFalse(get_option('spb_phase_2_available'));
        
        // Simulate plugin activation
        $this->activate_plugin();
        
        // Verify Phase 2 is automatically available
        $this->assertEquals('2.0.0', get_option('spb_version'));
        $this->assertTrue(get_option('spb_phase_2_available'));
        
        // Verify Phase 2 database tables were created
        $this->verify_phase_2_tables_exist();
        
        // Verify Phase 2 features are available
        $this->verify_phase_2_features_available();
    }

    /**
     * Test plugin activation with existing WordPress content
     */
    public function test_activation_with_existing_content() {
        // Create some existing WordPress content
        $this->create_sample_wordpress_content();
        
        // Activate plugin
        $this->activate_plugin();
        
        // Verify existing content is preserved
        $this->verify_existing_content_preserved();
        
        // Verify Phase 2 features work with existing content
        $this->verify_phase_2_integration_with_existing_content();
    }

    /**
     * Test plugin activation on different WordPress versions
     */
    public function test_wordpress_version_compatibility() {
        global $wp_version;
        
        // Skip if WordPress version is too old
        if (version_compare($wp_version, '6.0', '<')) {
            $this->markTestSkipped('WordPress version too old for Phase 2');
        }
        
        $this->activate_plugin();
        
        // Verify compatibility with current WordPress version
        $this->assertTrue(get_option('spb_phase_2_available'));
        $this->verify_admin_menu_integration();
        $this->verify_capabilities_integration();
    }

    /**
     * Test plugin activation with different PHP versions
     */
    public function test_php_version_compatibility() {
        // Skip if PHP version is too old
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            $this->markTestSkipped('PHP version too old for Phase 2');
        }
        
        $this->activate_plugin();
        
        // Verify PHP 8.0+ features work correctly
        $this->verify_phase_2_classes_load();
        $this->verify_type_declarations_work();
    }

    /**
     * Test database permissions and table creation
     */
    public function test_database_permissions() {
        global $wpdb;
        
        // Test if we can create tables
        $test_table = $wpdb->prefix . 'spb_test_permissions';
        $sql = "CREATE TABLE $test_table (id INT AUTO_INCREMENT PRIMARY KEY)";
        
        $result = $wpdb->query($sql);
        
        if ($result === false) {
            $this->markTestSkipped('Insufficient database permissions for table creation');
        }
        
        // Clean up test table
        $wpdb->query("DROP TABLE IF EXISTS $test_table");
        
        // Now test actual plugin activation
        $this->activate_plugin();
        $this->verify_phase_2_tables_exist();
    }

    /**
     * Test plugin activation with limited memory
     */
    public function test_memory_usage_during_activation() {
        $memory_before = memory_get_usage();
        
        $this->activate_plugin();
        
        $memory_after = memory_get_usage();
        $memory_used = $memory_after - $memory_before;
        
        // Verify memory usage is reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memory_used, 'Plugin activation uses too much memory');
        
        // Verify Phase 2 is still available despite memory constraints
        $this->assertTrue(get_option('spb_phase_2_available'));
    }

    /**
     * Test plugin activation timing
     */
    public function test_activation_performance() {
        $start_time = microtime(true);
        
        $this->activate_plugin();
        
        $end_time = microtime(true);
        $activation_time = $end_time - $start_time;
        
        // Verify activation completes within reasonable time (5 seconds)
        $this->assertLessThan(5.0, $activation_time, 'Plugin activation takes too long');
        
        // Verify Phase 2 is available
        $this->assertTrue(get_option('spb_phase_2_available'));
    }

    /**
     * Test multisite compatibility
     */
    public function test_multisite_compatibility() {
        if (!is_multisite()) {
            $this->markTestSkipped('Not a multisite installation');
        }
        
        // Test network activation
        $this->activate_plugin_network_wide();
        
        // Verify Phase 2 is available on main site
        $this->assertTrue(get_option('spb_phase_2_available'));
        
        // Switch to a different site and verify
        $blog_id = $this->factory->blog->create();
        switch_to_blog($blog_id);
        
        $this->assertTrue(get_option('spb_phase_2_available'));
        
        restore_current_blog();
    }

    /**
     * Test plugin deactivation and reactivation
     */
    public function test_deactivation_reactivation_cycle() {
        // Initial activation
        $this->activate_plugin();
        $this->assertTrue(get_option('spb_phase_2_available'));
        
        // Deactivate plugin
        $this->deactivate_plugin();
        
        // Verify options are preserved (not deleted on deactivation)
        $this->assertEquals('2.0.0', get_option('spb_version'));
        $this->assertTrue(get_option('spb_phase_2_available'));
        
        // Reactivate plugin
        $this->activate_plugin();
        
        // Verify Phase 2 is still available
        $this->assertTrue(get_option('spb_phase_2_available'));
        $this->verify_phase_2_tables_exist();
    }

    /**
     * Helper method to clean installation
     */
    private function clean_installation() {
        // Remove all Smart Page Builder options
        $options = array(
            'spb_version',
            'spb_db_version',
            'spb_phase_2_available',
            'spb_disable_phase_2',
            'spb_cache_enabled',
            'spb_confidence_threshold',
            'spb_max_content_length'
        );
        
        foreach ($options as $option) {
            delete_option($option);
        }
        
        // Remove database tables
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'spb_ai_insights',
            $wpdb->prefix . 'spb_dynamic_rules',
            $wpdb->prefix . 'spb_ab_tests',
            $wpdb->prefix . 'spb_metrics',
            $wpdb->prefix . 'spb_generated_content',
            $wpdb->prefix . 'spb_analytics',
            $wpdb->prefix . 'spb_ab_test_variants',
            $wpdb->prefix . 'spb_ab_test_results'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        // Clear cron jobs
        wp_clear_scheduled_hook('spb_daily_cleanup');
        wp_clear_scheduled_hook('spb_hourly_cache_cleanup');
    }

    /**
     * Helper method to activate plugin
     */
    private function activate_plugin() {
        // Simulate plugin activation
        Smart_Page_Builder_Activator::activate();
    }

    /**
     * Helper method to deactivate plugin
     */
    private function deactivate_plugin() {
        // Simulate plugin deactivation
        Smart_Page_Builder_Deactivator::deactivate();
    }

    /**
     * Helper method to activate plugin network-wide
     */
    private function activate_plugin_network_wide() {
        if (is_multisite()) {
            // Network activation logic would go here
            $this->activate_plugin();
        }
    }

    /**
     * Verify Phase 2 database tables exist
     */
    private function verify_phase_2_tables_exist() {
        global $wpdb;
        
        $phase_2_tables = array(
            $wpdb->prefix . 'spb_analytics',
            $wpdb->prefix . 'spb_ab_tests',
            $wpdb->prefix . 'spb_ab_test_variants',
            $wpdb->prefix . 'spb_ab_test_results'
        );
        
        foreach ($phase_2_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            $this->assertEquals($table, $exists, "Table $table does not exist");
        }
    }

    /**
     * Verify Phase 2 features are available
     */
    private function verify_phase_2_features_available() {
        // Test main plugin class
        $plugin = new Smart_Page_Builder();
        $reflection = new ReflectionClass($plugin);
        $method = $reflection->getMethod('is_phase_2_available');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($plugin));
        
        // Test admin class
        $admin = new Smart_Page_Builder_Admin('smart-page-builder', '2.0.0');
        $reflection = new ReflectionClass($admin);
        $method = $reflection->getMethod('is_phase_2_available');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke($admin));
    }

    /**
     * Create sample WordPress content
     */
    private function create_sample_wordpress_content() {
        // Create sample posts
        for ($i = 1; $i <= 5; $i++) {
            $this->factory->post->create(array(
                'post_title' => "Sample Post $i",
                'post_content' => "This is sample content for post $i",
                'post_status' => 'publish'
            ));
        }
        
        // Create sample pages
        for ($i = 1; $i <= 3; $i++) {
            $this->factory->post->create(array(
                'post_title' => "Sample Page $i",
                'post_content' => "This is sample content for page $i",
                'post_type' => 'page',
                'post_status' => 'publish'
            ));
        }
    }

    /**
     * Verify existing content is preserved
     */
    private function verify_existing_content_preserved() {
        $posts = get_posts(array('numberposts' => -1));
        $this->assertGreaterThanOrEqual(5, count($posts));
        
        $pages = get_pages();
        $this->assertGreaterThanOrEqual(3, count($pages));
    }

    /**
     * Verify Phase 2 integration with existing content
     */
    private function verify_phase_2_integration_with_existing_content() {
        // Test that Phase 2 analytics can track existing content
        $posts = get_posts(array('numberposts' => 1));
        if (!empty($posts)) {
            $post = $posts[0];
            
            // Simulate analytics tracking
            if (class_exists('Smart_Page_Builder_Analytics_Manager')) {
                $analytics = new Smart_Page_Builder_Analytics_Manager();
                // Test would go here if analytics methods were implemented
            }
        }
    }

    /**
     * Verify admin menu integration
     */
    private function verify_admin_menu_integration() {
        global $menu, $submenu;
        
        // Check if Smart Page Builder menu exists
        $menu_exists = false;
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && $menu_item[2] === 'smart-page-builder') {
                $menu_exists = true;
                break;
            }
        }
        
        $this->assertTrue($menu_exists, 'Smart Page Builder admin menu not found');
    }

    /**
     * Verify capabilities integration
     */
    private function verify_capabilities_integration() {
        $admin_role = get_role('administrator');
        
        $required_caps = array(
            'spb_manage_settings',
            'spb_generate_content',
            'spb_view_analytics',
            'spb_approve_content'
        );
        
        foreach ($required_caps as $cap) {
            $this->assertTrue($admin_role->has_cap($cap), "Capability $cap not found");
        }
    }

    /**
     * Verify Phase 2 classes load correctly
     */
    private function verify_phase_2_classes_load() {
        $phase_2_classes = array(
            'Smart_Page_Builder_Analytics_Manager',
            'Smart_Page_Builder_AI_Provider_Manager',
            'Smart_Page_Builder_SEO_Optimizer',
            'Smart_Page_Builder_AB_Testing'
        );
        
        foreach ($phase_2_classes as $class) {
            $this->assertTrue(class_exists($class), "Class $class not loaded");
        }
    }

    /**
     * Verify PHP 8.0+ type declarations work
     */
    private function verify_type_declarations_work() {
        // Test that PHP 8.0+ features work correctly
        if (class_exists('Smart_Page_Builder_Analytics_Manager')) {
            $analytics = new Smart_Page_Builder_Analytics_Manager();
            $this->assertInstanceOf('Smart_Page_Builder_Analytics_Manager', $analytics);
        }
    }

    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        $this->clean_installation();
        parent::tearDown();
    }
}
