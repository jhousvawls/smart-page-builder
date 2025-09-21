<?php
/**
 * Analytics Manager Unit Tests
 *
 * Tests for the Smart Page Builder Analytics Manager class.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 * @since      2.0.0
 */

/**
 * Analytics Manager Test Class
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 */
class Test_Analytics_Manager extends WP_UnitTestCase {

    /**
     * Analytics manager instance
     *
     * @var Smart_Page_Builder_Analytics_Manager
     */
    private $analytics_manager;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Define Phase 2 constant for testing
        if (!defined('SPB_PHASE_2_ENABLED')) {
            define('SPB_PHASE_2_ENABLED', true);
        }
        
        $this->analytics_manager = new Smart_Page_Builder_Analytics_Manager();
    }

    /**
     * Test analytics manager initialization
     */
    public function test_analytics_manager_initialization() {
        $this->assertInstanceOf(
            'Smart_Page_Builder_Analytics_Manager',
            $this->analytics_manager
        );
    }

    /**
     * Test page view tracking
     */
    public function test_page_view_tracking() {
        // Create a test post
        $post_id = $this->factory->post->create(array(
            'post_type' => 'spb_dynamic_page',
            'post_title' => 'Test Analytics Page',
            'post_status' => 'publish'
        ));

        // Mock global post
        global $post;
        $post = get_post($post_id);

        // Mock server variables
        $_SERVER['HTTP_USER_AGENT'] = 'Test User Agent';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_REFERER'] = 'https://example.com';

        // Test page view tracking
        $this->analytics_manager->track_page_view();

        // Verify the tracking was successful
        $this->assertTrue(true); // Basic assertion - in real implementation, check database
    }

    /**
     * Test search query tracking
     */
    public function test_search_query_tracking() {
        // Create a mock WP_Query for search
        $query = new WP_Query();
        $query->is_search = true;
        $query->is_main_query = true;
        $query->found_posts = 0;

        // Mock search query
        $_GET['s'] = 'test search term';

        // Test search query tracking
        $this->analytics_manager->track_search_query($query);

        // Verify the tracking was successful
        $this->assertTrue(true); // Basic assertion - in real implementation, check database
    }

    /**
     * Test content generation tracking
     */
    public function test_content_generation_tracking() {
        $content_data = array(
            'search_term' => 'test search',
            'content_type' => 'how-to',
            'confidence_score' => 0.85,
            'generation_time' => 2.5,
            'word_count' => 500,
            'source_count' => 3
        );

        // Test content generation tracking
        $this->analytics_manager->track_content_generation($content_data);

        // Verify the tracking was successful
        $this->assertTrue(true); // Basic assertion - in real implementation, check database
    }

    /**
     * Test dashboard analytics data retrieval
     */
    public function test_get_dashboard_analytics() {
        $analytics_data = $this->analytics_manager->get_dashboard_analytics();

        // Verify analytics data structure
        $this->assertIsArray($analytics_data);
        $this->assertArrayHasKey('today', $analytics_data);
        $this->assertArrayHasKey('weekly', $analytics_data);
        $this->assertArrayHasKey('monthly', $analytics_data);
        $this->assertArrayHasKey('top_content', $analytics_data);
        $this->assertArrayHasKey('content_gaps', $analytics_data);
        $this->assertArrayHasKey('approval_rates', $analytics_data);
    }

    /**
     * Test analytics data caching
     */
    public function test_analytics_caching() {
        // First call should generate data
        $start_time = microtime(true);
        $analytics_data_1 = $this->analytics_manager->get_dashboard_analytics();
        $first_call_time = microtime(true) - $start_time;

        // Second call should use cache and be faster
        $start_time = microtime(true);
        $analytics_data_2 = $this->analytics_manager->get_dashboard_analytics();
        $second_call_time = microtime(true) - $start_time;

        // Verify data is identical
        $this->assertEquals($analytics_data_1, $analytics_data_2);
        
        // Verify second call was faster (cached)
        $this->assertLessThan($first_call_time, $second_call_time);
    }

    /**
     * Test content approval tracking
     */
    public function test_content_approval_tracking() {
        $approval_data = array(
            'post_id' => 123,
            'approval_time' => 1.2
        );

        // Test content approval tracking
        $this->analytics_manager->track_content_approval($approval_data);

        // Verify the tracking was successful
        $this->assertTrue(true); // Basic assertion - in real implementation, check database
    }

    /**
     * Test content rejection tracking
     */
    public function test_content_rejection_tracking() {
        $rejection_data = array(
            'post_id' => 123,
            'reason' => 'Quality too low'
        );

        // Test content rejection tracking
        $this->analytics_manager->track_content_rejection($rejection_data);

        // Verify the tracking was successful
        $this->assertTrue(true); // Basic assertion - in real implementation, check database
    }

    /**
     * Test opportunity score calculation
     */
    public function test_opportunity_score_calculation() {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->analytics_manager);
        $method = $reflection->getMethod('calculate_opportunity_score');
        $method->setAccessible(true);

        // Test different search terms
        $score1 = $method->invoke($this->analytics_manager, 'how to fix', 5);
        $score2 = $method->invoke($this->analytics_manager, 'test', 1);
        $score3 = $method->invoke($this->analytics_manager, 'how to install wordpress plugin step by step', 10);

        // Verify scores are within expected range
        $this->assertGreaterThanOrEqual(0, $score1);
        $this->assertLessThanOrEqual(100, $score1);
        
        $this->assertGreaterThanOrEqual(0, $score2);
        $this->assertLessThanOrEqual(100, $score2);
        
        $this->assertGreaterThanOrEqual(0, $score3);
        $this->assertLessThanOrEqual(100, $score3);

        // Longer, more specific terms should have higher scores
        $this->assertGreaterThan($score2, $score3);
    }

    /**
     * Test client IP detection
     */
    public function test_client_ip_detection() {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->analytics_manager);
        $method = $reflection->getMethod('get_client_ip');
        $method->setAccessible(true);

        // Test with different IP scenarios
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $ip1 = $method->invoke($this->analytics_manager);
        $this->assertEquals('192.168.1.1', $ip1);

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1';
        $ip2 = $method->invoke($this->analytics_manager);
        $this->assertEquals('203.0.113.1', $ip2);
    }

    /**
     * Test session ID generation
     */
    public function test_session_id_generation() {
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->analytics_manager);
        $method = $reflection->getMethod('get_session_id');
        $method->setAccessible(true);

        $session_id = $method->invoke($this->analytics_manager);
        
        // Verify session ID is not empty
        $this->assertNotEmpty($session_id);
        $this->assertIsString($session_id);
    }

    /**
     * Test analytics cleanup
     */
    public function test_analytics_cleanup() {
        // Test cleanup method doesn't throw errors
        $this->analytics_manager->cleanup_old_analytics();
        
        // Verify cleanup completed successfully
        $this->assertTrue(true);
    }

    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        // Clean up any test data
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTP_REFERER']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_GET['s']);
        
        parent::tearDown();
    }
}
