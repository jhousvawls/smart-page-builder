<?php
/**
 * A/B Testing Framework Unit Tests
 *
 * Tests for the Smart Page Builder A/B Testing functionality.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 * @since      2.0.0
 */

/**
 * A/B Testing Test Class
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 */
class Test_AB_Testing extends WP_UnitTestCase {

    /**
     * A/B testing instance
     *
     * @var Smart_Page_Builder_AB_Testing
     */
    private $ab_testing;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Define Phase 2 constant for testing
        if (!defined('SPB_PHASE_2_ENABLED')) {
            define('SPB_PHASE_2_ENABLED', true);
        }
        
        $this->ab_testing = new Smart_Page_Builder_AB_Testing();
        
        // Create test tables
        $this->create_test_tables();
    }

    /**
     * Create test database tables
     */
    private function create_test_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // A/B Tests table
        $table_name = $wpdb->prefix . 'spb_ab_tests';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            test_type ENUM('template', 'algorithm', 'confidence') NOT NULL,
            status ENUM('active', 'stopped', 'completed', 'archived') DEFAULT 'active',
            config LONGTEXT,
            start_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            end_date DATETIME,
            target_sample_size INT(11) DEFAULT 100,
            confidence_level DECIMAL(5,2) DEFAULT 95.00,
            stop_reason TEXT,
            created_by BIGINT(20) UNSIGNED,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // A/B Test Variants table
        $table_name = $wpdb->prefix . 'spb_ab_test_variants';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id BIGINT(20) UNSIGNED NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            config LONGTEXT,
            traffic_allocation DECIMAL(5,2) DEFAULT 50.00,
            is_control TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // A/B Test Results table
        $table_name = $wpdb->prefix . 'spb_ab_test_results';
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            test_id BIGINT(20) UNSIGNED NOT NULL,
            variant_id BIGINT(20) UNSIGNED NOT NULL,
            event_type ENUM('generation', 'conversion', 'engagement') NOT NULL,
            event_data LONGTEXT,
            timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }

    /**
     * Test A/B testing initialization
     */
    public function test_ab_testing_initialization() {
        $this->assertInstanceOf(
            'Smart_Page_Builder_AB_Testing',
            $this->ab_testing
        );
    }

    /**
     * Test creating a new A/B test
     */
    public function test_create_ab_test() {
        $test_data = array(
            'name' => 'Template Test 1',
            'description' => 'Testing different content templates',
            'test_type' => 'template',
            'target_sample_size' => 200,
            'confidence_level' => 95.0
        );
        
        $test_id = $this->ab_testing->create_test($test_data);
        
        $this->assertIsInt($test_id);
        $this->assertGreaterThan(0, $test_id);
        
        // Verify test was created in database
        global $wpdb;
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spb_ab_tests WHERE id = %d",
            $test_id
        ));
        
        $this->assertNotNull($test);
        $this->assertEquals('Template Test 1', $test->name);
        $this->assertEquals('template', $test->test_type);
    }

    /**
     * Test creating test variants
     */
    public function test_create_test_variants() {
        // Create a test first
        $test_id = $this->ab_testing->create_test(array(
            'name' => 'Variant Test',
            'test_type' => 'template'
        ));
        
        // Create control variant
        $control_variant = array(
            'test_id' => $test_id,
            'name' => 'Control',
            'description' => 'Original template',
            'config' => json_encode(array('template' => 'original')),
            'traffic_allocation' => 50.0,
            'is_control' => true
        );
        
        $control_id = $this->ab_testing->create_variant($control_variant);
        $this->assertIsInt($control_id);
        
        // Create test variant
        $test_variant = array(
            'test_id' => $test_id,
            'name' => 'Variant A',
            'description' => 'New template design',
            'config' => json_encode(array('template' => 'new_design')),
            'traffic_allocation' => 50.0,
            'is_control' => false
        );
        
        $variant_id = $this->ab_testing->create_variant($test_variant);
        $this->assertIsInt($variant_id);
        
        // Verify variants were created
        $variants = $this->ab_testing->get_test_variants($test_id);
        $this->assertCount(2, $variants);
    }

    /**
     * Test traffic allocation
     */
    public function test_traffic_allocation() {
        // Create test with variants
        $test_id = $this->create_sample_test();
        
        // Test traffic allocation multiple times
        $allocations = array();
        for ($i = 0; $i < 100; $i++) {
            $variant = $this->ab_testing->allocate_traffic($test_id, 'user_' . $i);
            $allocations[] = $variant['id'];
        }
        
        // Should have both variants represented
        $unique_variants = array_unique($allocations);
        $this->assertGreaterThan(1, count($unique_variants));
        
        // Traffic should be roughly split (allowing for randomness)
        $variant_counts = array_count_values($allocations);
        foreach ($variant_counts as $count) {
            $this->assertGreaterThan(20, $count); // At least 20% traffic
            $this->assertLessThan(80, $count);    // At most 80% traffic
        }
    }

    /**
     * Test recording test results
     */
    public function test_record_test_results() {
        $test_id = $this->create_sample_test();
        $variants = $this->ab_testing->get_test_variants($test_id);
        $variant_id = $variants[0]['id'];
        
        $result_data = array(
            'test_id' => $test_id,
            'variant_id' => $variant_id,
            'event_type' => 'conversion',
            'event_data' => json_encode(array(
                'user_id' => 123,
                'conversion_value' => 1.0,
                'page_url' => '/test-page'
            ))
        );
        
        $result_id = $this->ab_testing->record_result($result_data);
        $this->assertIsInt($result_id);
        
        // Verify result was recorded
        global $wpdb;
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spb_ab_test_results WHERE id = %d",
            $result_id
        ));
        
        $this->assertNotNull($result);
        $this->assertEquals('conversion', $result->event_type);
    }

    /**
     * Test statistical significance calculation
     */
    public function test_statistical_significance() {
        $test_id = $this->create_sample_test();
        
        // Add sample data for statistical testing
        $this->add_sample_test_data($test_id);
        
        $significance = $this->ab_testing->calculate_statistical_significance($test_id);
        
        $this->assertIsArray($significance);
        $this->assertArrayHasKey('is_significant', $significance);
        $this->assertArrayHasKey('confidence_level', $significance);
        $this->assertArrayHasKey('p_value', $significance);
        $this->assertArrayHasKey('z_score', $significance);
    }

    /**
     * Test getting test results
     */
    public function test_get_test_results() {
        $test_id = $this->create_sample_test();
        $this->add_sample_test_data($test_id);
        
        $results = $this->ab_testing->get_test_results($test_id);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('variants', $results);
        $this->assertArrayHasKey('summary', $results);
        $this->assertArrayHasKey('statistical_significance', $results);
    }

    /**
     * Test stopping a test
     */
    public function test_stop_test() {
        $test_id = $this->create_sample_test();
        
        $result = $this->ab_testing->stop_test($test_id, 'Manual stop for testing');
        $this->assertTrue($result);
        
        // Verify test status was updated
        global $wpdb;
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spb_ab_tests WHERE id = %d",
            $test_id
        ));
        
        $this->assertEquals('stopped', $test->status);
        $this->assertEquals('Manual stop for testing', $test->stop_reason);
    }

    /**
     * Test archiving a test
     */
    public function test_archive_test() {
        $test_id = $this->create_sample_test();
        
        $result = $this->ab_testing->archive_test($test_id);
        $this->assertTrue($result);
        
        // Verify test status was updated
        global $wpdb;
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spb_ab_tests WHERE id = %d",
            $test_id
        ));
        
        $this->assertEquals('archived', $test->status);
    }

    /**
     * Test getting active tests
     */
    public function test_get_active_tests() {
        // Create multiple tests
        $this->create_sample_test();
        $this->create_sample_test();
        
        $active_tests = $this->ab_testing->get_active_tests();
        
        $this->assertIsArray($active_tests);
        $this->assertGreaterThanOrEqual(2, count($active_tests));
        
        // All returned tests should be active
        foreach ($active_tests as $test) {
            $this->assertEquals('active', $test['status']);
        }
    }

    /**
     * Test variant assignment consistency
     */
    public function test_variant_assignment_consistency() {
        $test_id = $this->create_sample_test();
        $user_id = 'consistent_user_123';
        
        // Get variant assignment multiple times for same user
        $variant1 = $this->ab_testing->allocate_traffic($test_id, $user_id);
        $variant2 = $this->ab_testing->allocate_traffic($test_id, $user_id);
        $variant3 = $this->ab_testing->allocate_traffic($test_id, $user_id);
        
        // Should always get the same variant for the same user
        $this->assertEquals($variant1['id'], $variant2['id']);
        $this->assertEquals($variant2['id'], $variant3['id']);
    }

    /**
     * Helper method to create a sample test with variants
     */
    private function create_sample_test() {
        $test_id = $this->ab_testing->create_test(array(
            'name' => 'Sample Test',
            'test_type' => 'template',
            'target_sample_size' => 100
        ));
        
        // Create control variant
        $this->ab_testing->create_variant(array(
            'test_id' => $test_id,
            'name' => 'Control',
            'traffic_allocation' => 50.0,
            'is_control' => true
        ));
        
        // Create test variant
        $this->ab_testing->create_variant(array(
            'test_id' => $test_id,
            'name' => 'Variant A',
            'traffic_allocation' => 50.0,
            'is_control' => false
        ));
        
        return $test_id;
    }

    /**
     * Helper method to add sample test data
     */
    private function add_sample_test_data($test_id) {
        $variants = $this->ab_testing->get_test_variants($test_id);
        
        // Add sample results for statistical testing
        foreach ($variants as $variant) {
            for ($i = 0; $i < 50; $i++) {
                $this->ab_testing->record_result(array(
                    'test_id' => $test_id,
                    'variant_id' => $variant['id'],
                    'event_type' => 'generation',
                    'event_data' => json_encode(array('success' => true))
                ));
                
                // Add some conversions (varying rates for different variants)
                if (($variant['is_control'] && $i < 10) || (!$variant['is_control'] && $i < 15)) {
                    $this->ab_testing->record_result(array(
                        'test_id' => $test_id,
                        'variant_id' => $variant['id'],
                        'event_type' => 'conversion',
                        'event_data' => json_encode(array('converted' => true))
                    ));
                }
            }
        }
    }

    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        global $wpdb;
        
        // Clean up test tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spb_ab_tests");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spb_ab_test_variants");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spb_ab_test_results");
        
        parent::tearDown();
    }
}
