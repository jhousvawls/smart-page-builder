<?php
/**
 * Integration Tests for Search-Triggered AI Page Generation
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

class SPB_Search_Integration_Test extends WP_UnitTestCase {
    
    /**
     * WP Engine API Client instance
     */
    private $api_client;
    
    /**
     * Query Enhancement Engine instance
     */
    private $query_enhancer;
    
    /**
     * WP Engine Integration Hub instance
     */
    private $integration_hub;
    
    /**
     * Search Integration Manager instance
     */
    private $search_manager;
    
    /**
     * Search Database Manager instance
     */
    private $db_manager;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize components
        $this->api_client = new SPB_WPEngine_API_Client();
        $this->query_enhancer = new SPB_Query_Enhancement_Engine();
        $this->integration_hub = new SPB_WPEngine_Integration_Hub();
        $this->search_manager = new SPB_Search_Integration_Manager();
        $this->db_manager = new SPB_Search_Database_Manager();
        
        // Create test database tables
        $this->db_manager->create_search_tables();
        
        // Set up test WP Engine credentials (mock)
        update_option('spb_wpengine_api_url', 'https://api.wpengine.test/v1');
        update_option('spb_wpengine_access_token', 'test_token_123');
        update_option('spb_wpengine_site_id', 'test_site_id');
        
        // Enable search interception
        update_option('spb_enable_search_interception', true);
        update_option('spb_auto_approve_threshold', 0.8);
        update_option('spb_enable_seo_urls', true);
    }
    
    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up database tables
        $this->db_manager->drop_search_tables();
        
        // Clean up options
        delete_option('spb_wpengine_api_url');
        delete_option('spb_wpengine_access_token');
        delete_option('spb_wpengine_site_id');
        delete_option('spb_enable_search_interception');
        delete_option('spb_auto_approve_threshold');
        delete_option('spb_enable_seo_urls');
        
        parent::tearDown();
    }
    
    /**
     * Test database table creation
     */
    public function test_database_tables_creation() {
        $this->assertTrue($this->db_manager->search_tables_exist(), 'Search tables should be created');
        
        global $wpdb;
        
        // Test search pages table
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $this->assertTrue($table_exists, 'Search pages table should exist');
        
        // Test query enhancements table
        $table_name = $wpdb->prefix . 'spb_query_enhancements';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $this->assertTrue($table_exists, 'Query enhancements table should exist');
        
        // Test generated components table
        $table_name = $wpdb->prefix . 'spb_generated_components';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        $this->assertTrue($table_exists, 'Generated components table should exist');
    }
    
    /**
     * Test WP Engine API client configuration
     */
    public function test_api_client_configuration() {
        $config = $this->api_client->get_configuration();
        
        $this->assertEquals('https://api.wpengine.test/v1', $config['api_url']);
        $this->assertEquals('test_site_id', $config['site_id']);
        $this->assertTrue($config['has_token']);
        $this->assertEquals(30, $config['timeout']);
    }
    
    /**
     * Test query enhancement functionality
     */
    public function test_query_enhancement() {
        $test_query = 'web design tutorials';
        
        $enhancement_result = $this->query_enhancer->enhance_query($test_query);
        
        $this->assertIsArray($enhancement_result);
        $this->assertEquals($test_query, $enhancement_result['original_query']);
        $this->assertNotEmpty($enhancement_result['enhanced_query']);
        $this->assertIsArray($enhancement_result['synonyms']);
        $this->assertContains($enhancement_result['intent'], ['educational', 'commercial', 'informational', 'navigational']);
        $this->assertIsFloat($enhancement_result['confidence']);
        $this->assertGreaterThanOrEqual(0, $enhancement_result['confidence']);
        $this->assertLessThanOrEqual(1, $enhancement_result['confidence']);
    }
    
    /**
     * Test intent detection
     */
    public function test_intent_detection() {
        $test_cases = [
            'how to build a website' => 'educational',
            'buy web hosting' => 'commercial',
            'contact us' => 'navigational',
            'about our company' => 'informational'
        ];
        
        foreach ($test_cases as $query => $expected_intent) {
            $detected_intent = $this->query_enhancer->detect_search_intent($query);
            $this->assertEquals($expected_intent, $detected_intent, "Intent detection failed for query: $query");
        }
    }
    
    /**
     * Test search page generation workflow
     */
    public function test_search_page_generation() {
        $test_query = 'wordpress development';
        $user_context = [
            'user_id' => 1,
            'is_logged_in' => true,
            'timestamp' => time()
        ];
        
        // Mock successful content discovery
        $this->mock_content_discovery_success();
        
        $generation_result = $this->search_manager->generate_search_page($test_query, $user_context);
        
        $this->assertTrue($generation_result['success'], 'Search page generation should succeed');
        $this->assertIsInt($generation_result['search_page_id']);
        $this->assertNotEmpty($generation_result['page_url']);
        $this->assertNotEmpty($generation_result['query_hash']);
        $this->assertGreaterThan(0, $generation_result['total_results']);
        $this->assertIsFloat($generation_result['processing_time']);
    }
    
    /**
     * Test search page URL generation
     */
    public function test_search_page_url_generation() {
        $test_query = 'test query';
        $query_hash = substr(md5(strtolower(trim($test_query))), 0, 16);
        
        // Test SEO-friendly URLs
        update_option('spb_enable_seo_urls', true);
        $this->search_manager->update_options(['enable_seo_urls' => true]);
        
        $expected_url = home_url("/smart-page/{$query_hash}/");
        $this->assertStringContains($query_hash, $expected_url);
        
        // Test query parameter URLs
        update_option('spb_enable_seo_urls', false);
        $this->search_manager->update_options(['enable_seo_urls' => false]);
        
        $expected_url = home_url("/?spb_search_page={$query_hash}");
        $this->assertStringContains($query_hash, $expected_url);
    }
    
    /**
     * Test search query validation
     */
    public function test_search_query_validation() {
        $valid_queries = [
            'web design',
            'how to build a website',
            'wordpress development tutorials'
        ];
        
        $invalid_queries = [
            'ab', // too short
            str_repeat('a', 201), // too long
            'test<script>', // contains HTML
            'test"quote' // contains quotes
        ];
        
        foreach ($valid_queries as $query) {
            $reflection = new ReflectionClass($this->search_manager);
            $method = $reflection->getMethod('is_valid_search_query');
            $method->setAccessible(true);
            
            $this->assertTrue($method->invoke($this->search_manager, $query), "Query should be valid: $query");
        }
        
        foreach ($invalid_queries as $query) {
            $reflection = new ReflectionClass($this->search_manager);
            $method = $reflection->getMethod('is_valid_search_query');
            $method->setAccessible(true);
            
            $this->assertFalse($method->invoke($this->search_manager, $query), "Query should be invalid: $query");
        }
    }
    
    /**
     * Test database operations
     */
    public function test_database_operations() {
        $test_query = 'test database query';
        $query_hash = substr(md5(strtolower(trim($test_query))), 0, 16);
        $page_url = home_url("/smart-page/{$query_hash}/");
        
        $discovery_result = [
            'merged_results' => [
                ['title' => 'Test Result 1', 'content' => 'Test content 1'],
                ['title' => 'Test Result 2', 'content' => 'Test content 2']
            ],
            'total_results' => 2,
            'processing_time' => 1500
        ];
        
        $user_context = ['user_session_id' => 'test_session_123'];
        
        // Store search page data
        $reflection = new ReflectionClass($this->search_manager);
        $method = $reflection->getMethod('store_search_page_data');
        $method->setAccessible(true);
        
        $page_id = $method->invoke($this->search_manager, $test_query, $query_hash, $page_url, $discovery_result, $user_context);
        
        $this->assertIsInt($page_id);
        $this->assertGreaterThan(0, $page_id);
        
        // Retrieve search page
        $stored_page = $this->db_manager->get_search_page($page_id);
        
        $this->assertIsArray($stored_page);
        $this->assertEquals($test_query, $stored_page['search_query']);
        $this->assertEquals($query_hash, $stored_page['query_hash']);
        $this->assertEquals($page_url, $stored_page['page_url']);
        $this->assertNotEmpty($stored_page['generated_content']);
        
        // Test approval status update
        $update_result = $this->db_manager->update_approval_status($page_id, 'approved', 1);
        $this->assertTrue($update_result);
        
        $updated_page = $this->db_manager->get_search_page($page_id);
        $this->assertEquals('approved', $updated_page['approval_status']);
        $this->assertNotNull($updated_page['approved_at']);
        $this->assertEquals(1, $updated_page['approved_by']);
    }
    
    /**
     * Test search statistics
     */
    public function test_search_statistics() {
        // Create test data
        $this->create_test_search_pages();
        
        $stats = $this->db_manager->get_search_stats(30);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_searches', $stats);
        $this->assertArrayHasKey('approved_searches', $stats);
        $this->assertArrayHasKey('pending_searches', $stats);
        $this->assertArrayHasKey('rejected_searches', $stats);
        $this->assertArrayHasKey('total_views', $stats);
        $this->assertArrayHasKey('avg_confidence', $stats);
        
        $this->assertGreaterThan(0, $stats['total_searches']);
    }
    
    /**
     * Test popular queries
     */
    public function test_popular_queries() {
        // Create test data
        $this->create_test_search_pages();
        
        $popular_queries = $this->db_manager->get_popular_queries(5, 30);
        
        $this->assertIsArray($popular_queries);
        $this->assertNotEmpty($popular_queries);
        
        foreach ($popular_queries as $query_data) {
            $this->assertArrayHasKey('search_query', $query_data);
            $this->assertArrayHasKey('search_count', $query_data);
            $this->assertArrayHasKey('total_views', $query_data);
            $this->assertArrayHasKey('avg_confidence', $query_data);
        }
    }
    
    /**
     * Test integration hub content discovery
     */
    public function test_integration_hub_discovery() {
        $test_query = 'wordpress plugins';
        $user_context = ['user_id' => 1];
        
        // Mock API responses
        $this->mock_content_discovery_success();
        
        $discovery_result = $this->integration_hub->discover_content($test_query, $user_context);
        
        $this->assertIsArray($discovery_result);
        $this->assertEquals($test_query, $discovery_result['query']);
        $this->assertArrayHasKey('enhanced_query', $discovery_result);
        $this->assertArrayHasKey('sources', $discovery_result);
        $this->assertArrayHasKey('merged_results', $discovery_result);
        $this->assertArrayHasKey('total_results', $discovery_result);
        $this->assertArrayHasKey('processing_time', $discovery_result);
    }
    
    /**
     * Test error handling and graceful degradation
     */
    public function test_error_handling() {
        // Test with invalid API credentials
        update_option('spb_wpengine_api_url', '');
        update_option('spb_wpengine_access_token', '');
        
        $api_client = new SPB_WPEngine_API_Client();
        $connection_test = $api_client->test_connection();
        
        $this->assertFalse($connection_test['success']);
        $this->assertEquals('MISSING_CREDENTIALS', $connection_test['error_code']);
        
        // Test query enhancement without AI provider
        $enhancement_result = $this->query_enhancer->enhance_query('test query');
        
        $this->assertIsArray($enhancement_result);
        $this->assertEquals('test query', $enhancement_result['original_query']);
        // Should still work with basic functionality
        $this->assertNotEmpty($enhancement_result['intent']);
    }
    
    /**
     * Test cleanup functionality
     */
    public function test_cleanup_functionality() {
        // Create old test pages
        $this->create_old_test_pages();
        
        $deleted_count = $this->db_manager->cleanup_old_pages(30);
        
        $this->assertIsInt($deleted_count);
        $this->assertGreaterThanOrEqual(0, $deleted_count);
    }
    
    /**
     * Test cache functionality
     */
    public function test_cache_functionality() {
        if (!class_exists('SPB_Cache_Manager')) {
            $this->markTestSkipped('Cache manager not available');
        }
        
        $test_query = 'cached query test';
        
        // First call should generate and cache
        $result1 = $this->query_enhancer->enhance_query($test_query);
        
        // Second call should use cache
        $result2 = $this->query_enhancer->enhance_query($test_query);
        
        $this->assertEquals($result1['enhanced_query'], $result2['enhanced_query']);
        $this->assertEquals($result1['intent'], $result2['intent']);
    }
    
    /**
     * Helper method to mock successful content discovery
     */
    private function mock_content_discovery_success() {
        // Mock WP Engine API responses
        add_filter('pre_http_request', function($preempt, $args, $url) {
            if (strpos($url, 'api.wpengine.test') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'data' => [
                            'smartSearch' => [
                                'results' => [
                                    ['id' => '1', 'title' => 'Test Result 1', 'content' => 'Test content 1', 'score' => 0.9],
                                    ['id' => '2', 'title' => 'Test Result 2', 'content' => 'Test content 2', 'score' => 0.8]
                                ],
                                'totalResults' => 2,
                                'searchTime' => 0.1
                            ],
                            'vectorSearch' => [
                                'results' => [
                                    ['id' => '3', 'title' => 'Vector Result 1', 'content' => 'Vector content 1', 'similarity' => 0.85]
                                ],
                                'totalResults' => 1,
                                'queryTime' => 0.05
                            ],
                            'recommendations' => [
                                'results' => [
                                    ['id' => '4', 'title' => 'Recommended Result 1', 'content' => 'Recommended content 1', 'score' => 0.7]
                                ],
                                'totalResults' => 1,
                                'recommendationTime' => 0.03
                            ]
                        ]
                    ])
                ];
            }
            return $preempt;
        }, 10, 3);
    }
    
    /**
     * Helper method to create test search pages
     */
    private function create_test_search_pages() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $test_pages = [
            ['wordpress development', 'approved', 0.9, 5],
            ['web design', 'approved', 0.8, 3],
            ['seo tips', 'pending', 0.7, 0],
            ['marketing strategies', 'rejected', 0.4, 0]
        ];
        
        foreach ($test_pages as $page_data) {
            $wpdb->insert(
                $table_name,
                [
                    'search_query' => $page_data[0],
                    'query_hash' => substr(md5($page_data[0]), 0, 16),
                    'page_url' => home_url('/smart-page/' . substr(md5($page_data[0]), 0, 16) . '/'),
                    'generated_content' => json_encode(['test' => 'data']),
                    'approval_status' => $page_data[1],
                    'confidence_score' => $page_data[2],
                    'views_count' => $page_data[3],
                    'created_at' => current_time('mysql')
                ]
            );
        }
    }
    
    /**
     * Helper method to create old test pages for cleanup testing
     */
    private function create_old_test_pages() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $old_date = date('Y-m-d H:i:s', strtotime('-100 days'));
        
        $old_pages = [
            ['old rejected query', 'rejected', 0.3],
            ['old low confidence query', 'pending', 0.4]
        ];
        
        foreach ($old_pages as $page_data) {
            $wpdb->insert(
                $table_name,
                [
                    'search_query' => $page_data[0],
                    'query_hash' => substr(md5($page_data[0]), 0, 16),
                    'page_url' => home_url('/smart-page/' . substr(md5($page_data[0]), 0, 16) . '/'),
                    'generated_content' => json_encode(['test' => 'data']),
                    'approval_status' => $page_data[1],
                    'confidence_score' => $page_data[2],
                    'created_at' => $old_date
                ]
            );
        }
    }
}
