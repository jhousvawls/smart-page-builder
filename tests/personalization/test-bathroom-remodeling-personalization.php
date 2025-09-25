<?php
/**
 * Personalization Testing Framework for "Remodeling a Bathroom" Scenario
 *
 * Tests the complete personalization workflow for bathroom remodeling searches
 * to validate current implementation and identify missing functionality.
 *
 * @package Smart_Page_Builder
 * @subpackage Tests
 * @since 3.6.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bathroom Remodeling Personalization Test Class
 */
class SPB_Test_Bathroom_Remodeling_Personalization extends WP_UnitTestCase {

    /**
     * Search Integration Manager instance
     */
    private $search_manager;

    /**
     * AI Page Generation Engine instance
     */
    private $ai_engine;

    /**
     * Test session ID
     */
    private $test_session_id;

    /**
     * Test search query
     */
    private $test_query = 'remodeling a bathroom';

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize components
        $this->search_manager = new SPB_Search_Integration_Manager();
        $this->ai_engine = new SPB_AI_Page_Generation_Engine();
        
        // Create test session
        $this->test_session_id = 'test_session_' . uniqid();
        
        // Set up test database tables
        $this->create_test_tables();
        
        // Mock user context
        $this->setup_test_user_context();
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up test data
        $this->cleanup_test_data();
        parent::tearDown();
    }

    /**
     * Test 1: Basic Search Interception
     * 
     * Validates that bathroom remodeling searches are properly intercepted
     */
    public function test_bathroom_search_interception() {
        // Simulate search query
        $_GET['s'] = $this->test_query;
        
        // Create mock WP_Query
        $query = new WP_Query([
            'is_search' => true,
            's' => $this->test_query
        ]);
        
        // Test search validation
        $is_valid = $this->invoke_private_method(
            $this->search_manager,
            'is_valid_search_query',
            [$this->test_query]
        );
        
        $this->assertTrue($is_valid, 'Bathroom remodeling query should be valid');
        
        // Test intent detection
        $intent = $this->invoke_private_method(
            $this->search_manager,
            'determine_user_intent',
            [$this->test_query]
        );
        
        $this->assertEquals('commercial', $intent, 'Should detect commercial intent for remodeling query');
    }

    /**
     * Test 2: Interest Vector Calculation (Expected to Fail - Not Implemented)
     * 
     * Tests interest vector calculation for bathroom remodeling searches
     */
    public function test_interest_vector_calculation() {
        // This test will fail until Interest Vector Calculator is implemented
        if (!class_exists('SPB_Interest_Vector_Calculator')) {
            $this->markTestSkipped('Interest Vector Calculator not implemented');
            return;
        }
        
        $calculator = new SPB_Interest_Vector_Calculator();
        
        // Test search interest extraction
        $interests = $calculator->extract_search_interests($this->test_query);
        
        $this->assertIsArray($interests, 'Should return array of interests');
        $this->assertArrayHasKey('home_improvement', $interests, 'Should detect home improvement interest');
        $this->assertArrayHasKey('renovation', $interests, 'Should detect renovation interest');
        $this->assertGreaterThan(0.5, $interests['home_improvement'], 'Home improvement score should be significant');
        
        // Test TF-IDF calculation
        $tfidf_scores = $calculator->calculate_tfidf_scores($this->test_query);
        $this->assertArrayHasKey('bathroom', $tfidf_scores, 'Should calculate TF-IDF for bathroom');
        $this->assertArrayHasKey('remodeling', $tfidf_scores, 'Should calculate TF-IDF for remodeling');
    }

    /**
     * Test 3: Signal Collection (Expected to Fail - Not Implemented)
     * 
     * Tests behavioral signal collection for search queries
     */
    public function test_signal_collection() {
        if (!class_exists('SPB_Signal_Collector')) {
            $this->markTestSkipped('Signal Collector not implemented');
            return;
        }
        
        $collector = new SPB_Signal_Collector();
        
        // Test search signal collection
        $signal_data = [
            'query' => $this->test_query,
            'intent' => 'commercial',
            'category' => 'home_improvement',
            'timestamp' => current_time('mysql')
        ];
        
        $signal_id = $collector->collect_signal(
            $this->test_session_id,
            'search_query',
            $signal_data,
            1.0
        );
        
        $this->assertIsInt($signal_id, 'Should return signal ID');
        $this->assertGreaterThan(0, $signal_id, 'Signal ID should be positive');
        
        // Test signal validation
        $is_valid = $collector->validate_signal('search_query', $signal_data);
        $this->assertTrue($is_valid, 'Search signal should be valid');
    }

    /**
     * Test 4: Component Personalization (Expected to Fail - Not Implemented)
     * 
     * Tests personalized component generation for bathroom remodeling
     */
    public function test_component_personalization() {
        if (!class_exists('SPB_Component_Personalizer')) {
            $this->markTestSkipped('Component Personalizer not implemented');
            return;
        }
        
        $personalizer = new SPB_Component_Personalizer();
        
        // Mock user interests
        $interests = [
            'home_improvement' => 0.85,
            'renovation' => 0.78,
            'interior_design' => 0.65,
            'diy_projects' => 0.45
        ];
        
        $context = [
            'search_query' => $this->test_query,
            'intent' => 'commercial',
            'user_session_id' => $this->test_session_id
        ];
        
        // Test hero personalization
        $hero = $personalizer->personalize_hero($interests, $context);
        
        $this->assertIsArray($hero, 'Should return hero array');
        $this->assertArrayHasKey('headline', $hero, 'Should have headline');
        $this->assertArrayHasKey('subheadline', $hero, 'Should have subheadline');
        $this->assertArrayHasKey('cta_primary', $hero, 'Should have primary CTA');
        
        // Validate bathroom-specific content
        $headline_lower = strtolower($hero['headline']);
        $this->assertStringContainsString('bathroom', $headline_lower, 'Headline should mention bathroom');
        
        // Test CTA personalization
        $cta = $personalizer->personalize_cta($interests, $context);
        
        $this->assertIsArray($cta, 'Should return CTA array');
        $this->assertArrayHasKey('headline', $cta, 'Should have CTA headline');
        $this->assertArrayHasKey('primary_button', $cta, 'Should have primary button');
        
        // Validate home improvement focused CTAs
        $cta_text = strtolower($cta['primary_button']['text']);
        $this->assertThat(
            $cta_text,
            $this->logicalOr(
                $this->stringContains('consultation'),
                $this->stringContains('estimate'),
                $this->stringContains('quote')
            ),
            'CTA should be consultation/estimate focused for remodeling'
        );
    }

    /**
     * Test 5: Current AI Content Generation
     * 
     * Tests the current AI content generation for bathroom remodeling
     */
    public function test_current_ai_content_generation() {
        // Mock discovery results
        $discovery_results = [
            [
                'title' => 'Bathroom Renovation Guide',
                'excerpt' => 'Complete guide to bathroom remodeling',
                'url' => 'https://example.com/bathroom-guide',
                'category' => 'home_improvement'
            ],
            [
                'title' => 'Modern Bathroom Design Ideas',
                'excerpt' => 'Latest trends in bathroom design',
                'url' => 'https://example.com/bathroom-design',
                'category' => 'interior_design'
            ]
        ];
        
        $user_context = [
            'session_id' => $this->test_session_id,
            'user_agent' => 'Test User Agent',
            'referrer' => 'https://google.com'
        ];
        
        // Test page content generation
        $result = $this->ai_engine->generate_page_content(
            $this->test_query,
            $discovery_results,
            $this->test_session_id,
            $user_context
        );
        
        $this->assertIsArray($result, 'Should return result array');
        $this->assertArrayHasKey('success', $result, 'Should have success flag');
        
        if ($result['success']) {
            $this->assertArrayHasKey('content', $result, 'Should have content');
            $this->assertArrayHasKey('components', $result, 'Should have components');
            $this->assertArrayHasKey('quality_metrics', $result, 'Should have quality metrics');
            
            // Validate content structure
            $content = $result['content'];
            $this->assertArrayHasKey('template', $content, 'Should have template');
            $this->assertArrayHasKey('sections', $content, 'Should have sections');
            $this->assertArrayHasKey('metadata', $content, 'Should have metadata');
            
            // Validate commercial template selection
            $this->assertEquals('commercial', $content['template'], 'Should use commercial template for remodeling');
            
            // Validate metadata
            $metadata = $content['metadata'];
            $this->assertArrayHasKey('title', $metadata, 'Should have page title');
            $this->assertArrayHasKey('description', $metadata, 'Should have page description');
            
            $title_lower = strtolower($metadata['title']);
            $this->assertStringContainsString('bathroom', $title_lower, 'Title should mention bathroom');
        }
    }

    /**
     * Test 6: Template Selection Logic
     * 
     * Tests template selection for bathroom remodeling searches
     */
    public function test_template_selection() {
        // Test intent analysis
        $page_intent = $this->invoke_private_method(
            $this->ai_engine,
            'analyze_page_intent',
            [$this->test_query, []]
        );
        
        $this->assertIsArray($page_intent, 'Should return intent array');
        $this->assertArrayHasKey('primary_intent', $page_intent, 'Should have primary intent');
        $this->assertArrayHasKey('confidence', $page_intent, 'Should have confidence score');
        $this->assertArrayHasKey('suggested_components', $page_intent, 'Should have suggested components');
        
        // Validate commercial intent detection
        $this->assertEquals('commercial', $page_intent['primary_intent'], 'Should detect commercial intent');
        $this->assertGreaterThan(0.5, $page_intent['confidence'], 'Should have reasonable confidence');
        
        // Validate suggested components for commercial intent
        $suggested = $page_intent['suggested_components'];
        $this->assertContains('hero', $suggested, 'Should suggest hero component');
        $this->assertContains('cta', $suggested, 'Should suggest CTA component');
    }

    /**
     * Test 7: Quality Assessment
     * 
     * Tests content quality assessment for bathroom remodeling pages
     */
    public function test_quality_assessment() {
        // Mock page content
        $page_content = [
            'template' => 'commercial',
            'sections' => [
                'hero' => [
                    'components' => [
                        [
                            'type' => 'hero',
                            'content' => [
                                'headline' => 'Transform Your Bathroom Today',
                                'subheadline' => 'Professional remodeling services'
                            ],
                            'confidence' => 0.8
                        ]
                    ]
                ],
                'main' => [
                    'components' => [
                        [
                            'type' => 'article',
                            'content' => [
                                'title' => 'Bathroom Remodeling Services',
                                'content' => 'Complete bathroom renovation solutions...'
                            ],
                            'confidence' => 0.7
                        ]
                    ]
                ]
            ]
        ];
        
        $personalization_context = [
            'search_query' => $this->test_query,
            'user_interests' => [
                'home_improvement' => 0.8,
                'renovation' => 0.7
            ]
        ];
        
        // Test quality assessment
        $quality_metrics = $this->invoke_private_method(
            $this->ai_engine,
            'assess_content_quality',
            [$page_content, $personalization_context]
        );
        
        $this->assertIsArray($quality_metrics, 'Should return quality metrics');
        $this->assertArrayHasKey('overall_confidence', $quality_metrics, 'Should have overall confidence');
        $this->assertArrayHasKey('content_relevance', $quality_metrics, 'Should have content relevance');
        $this->assertArrayHasKey('component_scores', $quality_metrics, 'Should have component scores');
        
        // Validate confidence scores
        $this->assertGreaterThan(0, $quality_metrics['overall_confidence'], 'Overall confidence should be positive');
        $this->assertLessThanOrEqual(1, $quality_metrics['overall_confidence'], 'Overall confidence should not exceed 1');
    }

    /**
     * Test 8: End-to-End Search Flow
     * 
     * Tests the complete search flow for bathroom remodeling
     */
    public function test_end_to_end_search_flow() {
        // Mock WordPress search environment
        global $wp_query;
        $wp_query = new WP_Query([
            'is_search' => true,
            's' => $this->test_query
        ]);
        
        $_GET['s'] = $this->test_query;
        
        // Test search page generation
        $user_context = [
            'session_id' => $this->test_session_id,
            'user_agent' => 'Test Browser',
            'referrer' => 'https://google.com/search?q=' . urlencode($this->test_query)
        ];
        
        $result = $this->search_manager->generate_search_page($this->test_query, $user_context);
        
        $this->assertIsArray($result, 'Should return result array');
        $this->assertArrayHasKey('success', $result, 'Should have success flag');
        
        if ($result['success']) {
            $this->assertArrayHasKey('page_url', $result, 'Should have page URL');
            $this->assertArrayHasKey('query_hash', $result, 'Should have query hash');
            $this->assertArrayHasKey('search_page_id', $result, 'Should have search page ID');
            
            // Validate page URL format
            $page_url = $result['page_url'];
            $this->assertStringContainsString('smart-page', $page_url, 'URL should contain smart-page');
            
            // Test database storage
            global $wpdb;
            $table_name = $wpdb->prefix . 'spb_search_pages';
            $stored_page = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d",
                $result['search_page_id']
            ), ARRAY_A);
            
            $this->assertNotNull($stored_page, 'Page should be stored in database');
            $this->assertEquals($this->test_query, $stored_page['search_query'], 'Stored query should match');
        }
    }

    /**
     * Test 9: Personalization Performance Benchmarks
     * 
     * Tests performance of personalization components
     */
    public function test_personalization_performance() {
        $start_time = microtime(true);
        
        // Test search processing time
        $user_context = ['session_id' => $this->test_session_id];
        $result = $this->search_manager->generate_search_page($this->test_query, $user_context);
        
        $processing_time = microtime(true) - $start_time;
        
        // Performance assertions
        $this->assertLessThan(5.0, $processing_time, 'Search page generation should complete within 5 seconds');
        
        if ($result['success'] && isset($result['processing_time'])) {
            $this->assertLessThan(3000, $result['processing_time'], 'Processing time should be under 3000ms');
        }
    }

    /**
     * Test 10: Privacy Compliance (Expected to Fail - Not Implemented)
     * 
     * Tests privacy compliance for personalization features
     */
    public function test_privacy_compliance() {
        if (!class_exists('SPB_Privacy_Manager')) {
            $this->markTestSkipped('Privacy Manager not implemented');
            return;
        }
        
        $privacy_manager = new SPB_Privacy_Manager();
        
        // Test consent checking
        $has_consent = $privacy_manager->check_consent($this->test_session_id);
        $this->assertIsBool($has_consent, 'Should return boolean for consent check');
        
        // Test data anonymization
        $test_data = [
            'session_id' => $this->test_session_id,
            'search_query' => $this->test_query,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser'
        ];
        
        $anonymized = $privacy_manager->anonymize_data($test_data);
        $this->assertIsArray($anonymized, 'Should return anonymized data array');
        $this->assertNotEquals($test_data['ip_address'], $anonymized['ip_address'], 'IP should be anonymized');
        
        // Test opt-out handling
        $opt_out_result = $privacy_manager->handle_opt_out($this->test_session_id);
        $this->assertTrue($opt_out_result, 'Should successfully handle opt-out');
    }

    /**
     * Helper Methods
     */

    /**
     * Create test database tables
     */
    private function create_test_tables() {
        global $wpdb;
        
        // Create search pages table if it doesn't exist
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_query varchar(255) NOT NULL,
            page_title varchar(255) NOT NULL,
            page_content longtext NOT NULL,
            page_slug varchar(200) NOT NULL,
            page_status varchar(20) DEFAULT 'pending',
            quality_score decimal(3,2) DEFAULT NULL,
            approval_status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY search_query (search_query)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set up test user context
     */
    private function setup_test_user_context() {
        // Mock session data
        if (class_exists('SPB_Session_Manager')) {
            $session_manager = new SPB_Session_Manager();
            $session_manager->create_session($this->test_session_id);
        }
        
        // Mock user interests (if Interest Vector Calculator exists)
        if (class_exists('SPB_Interest_Vector_Calculator')) {
            $calculator = new SPB_Interest_Vector_Calculator();
            $calculator->set_user_interests($this->test_session_id, [
                'home_improvement' => 0.8,
                'renovation' => 0.7,
                'interior_design' => 0.6
            ]);
        }
    }

    /**
     * Clean up test data
     */
    private function cleanup_test_data() {
        global $wpdb;
        
        // Clean up search pages
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $wpdb->delete($table_name, ['search_query' => $this->test_query]);
        
        // Clean up session data
        if (class_exists('SPB_Session_Manager')) {
            $session_manager = new SPB_Session_Manager();
            $session_manager->destroy_session($this->test_session_id);
        }
    }

    /**
     * Invoke private method for testing
     */
    private function invoke_private_method($object, $method_name, $parameters = []) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($method_name);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Data Providers
     */

    /**
     * Provide test search queries related to bathroom remodeling
     */
    public function bathroom_remodeling_queries() {
        return [
            ['remodeling a bathroom'],
            ['bathroom renovation'],
            ['bathroom remodel cost'],
            ['modern bathroom design'],
            ['bathroom renovation ideas'],
            ['small bathroom remodel'],
            ['bathroom renovation contractor'],
            ['bathroom remodeling services']
        ];
    }

    /**
     * Provide expected interest categories for bathroom remodeling
     */
    public function expected_interest_categories() {
        return [
            ['home_improvement', 0.8],
            ['renovation', 0.7],
            ['interior_design', 0.6],
            ['diy_projects', 0.4],
            ['construction', 0.5]
        ];
    }

    /**
     * Provide expected component types for commercial intent
     */
    public function commercial_component_types() {
        return [
            ['hero'],
            ['product_showcase'],
            ['cta'],
            ['testimonials'],
            ['features']
        ];
    }
}

/**
 * Test Suite Runner
 * 
 * Runs all personalization tests and generates a comprehensive report
 */
class SPB_Personalization_Test_Runner {
    
    /**
     * Run all personalization tests
     */
    public static function run_all_tests() {
        $test_suite = new SPB_Test_Bathroom_Remodeling_Personalization();
        $results = [];
        
        $test_methods = [
            'test_bathroom_search_interception',
            'test_interest_vector_calculation',
            'test_signal_collection',
            'test_component_personalization',
            'test_current_ai_content_generation',
            'test_template_selection',
            'test_quality_assessment',
            'test_end_to_end_search_flow',
            'test_personalization_performance',
            'test_privacy_compliance'
        ];
        
        foreach ($test_methods as $method) {
            try {
                $test_suite->setUp();
                $test_suite->$method();
                $results[$method] = ['status' => 'PASS', 'message' => 'Test passed'];
                $test_suite->tearDown();
            } catch (Exception $e) {
                $results[$method] = ['status' => 'FAIL', 'message' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Generate test report
     */
    public static function generate_report($results) {
        $total_tests = count($results);
        $passed_tests = count(array_filter($results, function($result) {
            return $result['status'] === 'PASS';
        }));
        $failed_tests = $total_tests - $passed_tests;
        
        $report = "# Smart Page Builder Personalization Test Report\n\n";
        $report .= "**Test Summary:**\n";
        $report .= "- Total Tests: {$total_tests}\n";
        $report .= "- Passed: {$passed_tests}\n";
        $report .= "- Failed: {$failed_tests}\n";
        $report .= "- Success Rate: " . round(($passed_tests / $total_tests) * 100, 2) . "%\n\n";
        
        $report .= "**Detailed Results:**\n\n";
        
        foreach ($results as $test => $result) {
            $status_icon = $result['status'] === 'PASS' ? '✅' : '❌';
            $report .= "- {$status_icon} **{$test}**: {$result['status']}\n";
            if ($result['status'] === 'FAIL') {
                $report .= "  - Error: {$result['message']}\n";
            }
            $report .= "\n";
        }
        
        return $report;
    }
}
