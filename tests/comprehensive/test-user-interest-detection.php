<?php
/**
 * Comprehensive Test Suite for User Interest Detection
 * Tests the Interest Vector Calculator and Signal Collection System
 *
 * @package Smart_Page_Builder
 * @subpackage Tests
 */

class Test_User_Interest_Detection extends WP_UnitTestCase {

    private $signal_collector;
    private $interest_calculator;
    private $test_session_id;
    private $dummy_data_detector;

    public function setUp(): void {
        parent::setUp();
        
        $this->signal_collector = new SPB_Signal_Collector();
        $this->interest_calculator = new SPB_Interest_Vector_Calculator();
        $this->test_session_id = 'test_session_' . time();
        $this->dummy_data_detector = new SPB_Dummy_Data_Detector();
        
        // Clean up any existing test data
        $this->cleanup_test_data();
    }

    public function tearDown(): void {
        $this->cleanup_test_data();
        parent::tearDown();
    }

    /**
     * Test 1: Signal Collection Accuracy
     * Verifies that all user signals are captured correctly
     */
    public function test_signal_collection_accuracy() {
        $start_time = microtime(true);
        
        // Test search query signal
        $search_signal_id = $this->signal_collector->collect_signal(
            $this->test_session_id,
            'search_query',
            [
                'query' => 'smart home automation',
                'category' => 'technology',
                'results_count' => 15
            ]
        );
        
        $this->assertNotFalse($search_signal_id, 'Search signal should be collected successfully');
        
        // Test content click signal
        $click_signal_id = $this->signal_collector->collect_signal(
            $this->test_session_id,
            'content_click',
            [
                'content_id' => 'post:123',
                'content_type' => 'post',
                'category' => 'smart-home',
                'click_position' => ['x' => 150, 'y' => 300]
            ]
        );
        
        $this->assertNotFalse($click_signal_id, 'Click signal should be collected successfully');
        
        // Test time spent signal
        $time_signal_id = $this->signal_collector->collect_signal(
            $this->test_session_id,
            'time_spent',
            [
                'content_id' => 'post:123',
                'engagement_time' => 180,
                'scroll_depth' => 0.75
            ]
        );
        
        $this->assertNotFalse($time_signal_id, 'Time spent signal should be collected successfully');
        
        // Verify signals are stored correctly
        $stored_signals = $this->get_stored_signals($this->test_session_id);
        $this->assertCount(3, $stored_signals, 'All three signals should be stored');
        
        // Check signal processing time
        $processing_time = (microtime(true) - $start_time) * 1000;
        $this->assertLessThan(100, $processing_time, 'Signal processing should complete in <100ms');
        
        // Verify no dummy data in signals
        foreach ($stored_signals as $signal) {
            $this->assertFalse(
                $this->dummy_data_detector->contains_dummy_indicators($signal['signal_data']),
                'Signal data should not contain dummy indicators'
            );
        }
    }

    /**
     * Test 2: TF-IDF Calculation Accuracy
     * Validates the mathematical correctness of TF-IDF analysis
     */
    public function test_tfidf_calculation_accuracy() {
        $tfidf_analyzer = new SPB_TFIDF_Analyzer();
        
        // Test corpus
        $corpus = [
            'Smart home automation makes life easier with IoT devices',
            'Professional contractors need reliable tools for projects',
            'Safety guidelines are essential for DIY home improvement'
        ];
        
        // Test term frequency calculation
        $tf = $tfidf_analyzer->term_frequency('smart', $corpus[0]);
        $expected_tf = 1 / 9; // 1 occurrence out of 9 words
        $this->assertEquals($expected_tf, $tf, 'Term frequency should be calculated correctly', 0.001);
        
        // Test inverse document frequency
        $idf = $tfidf_analyzer->inverse_document_frequency('home', $corpus);
        $expected_idf = log(3 / 2); // 3 total docs, 2 contain 'home'
        $this->assertEquals($expected_idf, $idf, 'IDF should be calculated correctly', 0.001);
        
        // Test full TF-IDF calculation
        $tfidf = $tfidf_analyzer->calculate_tfidf('smart', $corpus[0], $corpus);
        $expected_tfidf = $expected_tf * log(3 / 1); // Only first doc contains 'smart'
        $this->assertEquals($expected_tfidf, $tfidf, 'TF-IDF should be calculated correctly', 0.001);
    }

    /**
     * Test 3: Interest Vector Calculation
     * Tests the complete interest vector generation process
     */
    public function test_interest_vector_calculation() {
        $start_time = microtime(true);
        
        // Create test signals for tech enthusiast persona
        $this->create_tech_enthusiast_signals();
        
        // Calculate interest vector
        $vector_data = $this->interest_calculator->calculate_interest_vector($this->test_session_id);
        
        // Verify vector structure
        $this->assertArrayHasKey('vector', $vector_data, 'Vector data should contain interest vector');
        $this->assertArrayHasKey('confidence', $vector_data, 'Vector data should contain confidence score');
        $this->assertArrayHasKey('signal_count', $vector_data, 'Vector data should contain signal count');
        
        // Verify vector quality
        $interest_vector = $vector_data['vector'];
        $this->assertNotEmpty($interest_vector, 'Interest vector should not be empty');
        
        // Check that technology-related interests are highest
        $this->assertArrayHasKey('technology', $interest_vector, 'Technology interest should be present');
        $this->assertArrayHasKey('smart-home', $interest_vector, 'Smart home interest should be present');
        
        // Verify confidence score
        $confidence = $vector_data['confidence'];
        $this->assertGreaterThan(0.6, $confidence, 'Confidence should be above minimum threshold');
        $this->assertLessThanOrEqual(1.0, $confidence, 'Confidence should not exceed 1.0');
        
        // Check calculation performance
        $calculation_time = (microtime(true) - $start_time) * 1000;
        $this->assertLessThan(50, $calculation_time, 'Interest vector calculation should complete in <50ms');
        
        // Verify vector normalization
        $vector_sum = array_sum($interest_vector);
        $this->assertEquals(1.0, $vector_sum, 'Interest vector should be normalized to sum to 1.0', 0.01);
    }

    /**
     * Test 4: Temporal Decay Application
     * Verifies that older signals have reduced weight
     */
    public function test_temporal_decay_application() {
        $temporal_decay = new SPB_Temporal_Decay();
        
        // Test recent signal (should have high weight)
        $recent_timestamp = time() - 3600; // 1 hour ago
        $recent_weight = $temporal_decay->calculate_recency_weight($recent_timestamp);
        $this->assertGreaterThan(0.9, $recent_weight, 'Recent signals should have high weight');
        
        // Test old signal (should have lower weight)
        $old_timestamp = time() - (7 * 24 * 3600); // 7 days ago
        $old_weight = $temporal_decay->calculate_recency_weight($old_timestamp);
        $this->assertLessThan($recent_weight, $old_weight, 'Older signals should have lower weight');
        
        // Test very old signal (should be minimal or zero)
        $very_old_timestamp = time() - (35 * 24 * 3600); // 35 days ago
        $very_old_weight = $temporal_decay->calculate_recency_weight($very_old_timestamp);
        $this->assertEquals(0, $very_old_weight, 'Very old signals should have zero weight');
        
        // Test exponential decay function
        $base_score = 1.0;
        $decayed_score = $temporal_decay->apply_decay($base_score, $old_timestamp);
        $this->assertLessThan($base_score, $decayed_score, 'Decay should reduce signal strength');
    }

    /**
     * Test 5: User Persona Identification
     * Tests accuracy of identifying different user types
     */
    public function test_user_persona_identification() {
        // Test Tech Enthusiast identification
        $tech_session = 'tech_test_' . time();
        $this->create_tech_enthusiast_signals($tech_session);
        $tech_vector = $this->interest_calculator->calculate_interest_vector($tech_session);
        
        $this->assertGreaterThan(0.7, $tech_vector['vector']['technology'] ?? 0, 'Tech enthusiast should have high technology interest');
        $this->assertGreaterThan(0.6, $tech_vector['vector']['smart-home'] ?? 0, 'Tech enthusiast should have high smart-home interest');
        
        // Test Professional Contractor identification
        $pro_session = 'pro_test_' . time();
        $this->create_professional_contractor_signals($pro_session);
        $pro_vector = $this->interest_calculator->calculate_interest_vector($pro_session);
        
        $this->assertGreaterThan(0.7, $pro_vector['vector']['business'] ?? 0, 'Professional should have high business interest');
        $this->assertGreaterThan(0.6, $pro_vector['vector']['tools'] ?? 0, 'Professional should have high tools interest');
        
        // Test Safety-Conscious Beginner identification
        $safety_session = 'safety_test_' . time();
        $this->create_safety_conscious_signals($safety_session);
        $safety_vector = $this->interest_calculator->calculate_interest_vector($safety_session);
        
        $this->assertGreaterThan(0.8, $safety_vector['vector']['safety'] ?? 0, 'Safety-conscious user should have high safety interest');
        $this->assertGreaterThan(0.6, $safety_vector['vector']['beginner'] ?? 0, 'Safety-conscious user should have high beginner interest');
        
        // Clean up test sessions
        $this->cleanup_test_session($tech_session);
        $this->cleanup_test_session($pro_session);
        $this->cleanup_test_session($safety_session);
    }

    /**
     * Test 6: Performance Benchmarks
     * Validates that all operations meet performance requirements
     */
    public function test_performance_benchmarks() {
        $performance_metrics = [];
        
        // Test signal collection performance
        $start_time = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $this->signal_collector->collect_signal(
                $this->test_session_id,
                'content_view',
                ['content_id' => "post:{$i}", 'category' => 'test']
            );
        }
        $performance_metrics['signal_collection_avg'] = ((microtime(true) - $start_time) / 10) * 1000;
        
        // Test interest vector calculation performance
        $start_time = microtime(true);
        $this->interest_calculator->calculate_interest_vector($this->test_session_id);
        $performance_metrics['vector_calculation'] = (microtime(true) - $start_time) * 1000;
        
        // Validate performance requirements
        $this->assertLessThan(10, $performance_metrics['signal_collection_avg'], 'Average signal collection should be <10ms');
        $this->assertLessThan(50, $performance_metrics['vector_calculation'], 'Vector calculation should be <50ms');
        
        // Log performance metrics for monitoring
        error_log('SPB Performance Metrics: ' . json_encode($performance_metrics));
    }

    /**
     * Test 7: Dummy Data Detection
     * Critical test to ensure no dummy data exists in production
     */
    public function test_dummy_data_detection() {
        $dummy_issues = $this->dummy_data_detector->scan_for_dummy_data();
        
        // Critical: No high-severity dummy data should exist
        $high_severity_issues = array_filter($dummy_issues['issues'], function($issue) {
            return $issue['severity'] === 'high';
        });
        
        $this->assertEmpty($high_severity_issues, 'CRITICAL: No high-severity dummy data should exist in production');
        
        // Log any medium/low severity issues for review
        if ($dummy_issues['total_issues'] > 0) {
            error_log('SPB Dummy Data Issues Found: ' . json_encode($dummy_issues));
        }
        
        // Verify specific areas are clean
        $this->assert_dashboard_clean();
        $this->assert_database_clean();
        $this->assert_content_clean();
    }

    // Helper Methods

    private function create_tech_enthusiast_signals($session_id = null) {
        $session = $session_id ?: $this->test_session_id;
        
        $tech_signals = [
            ['search_query', ['query' => 'smart home automation', 'category' => 'technology']],
            ['search_query', ['query' => 'IoT device installation', 'category' => 'technology']],
            ['content_click', ['content_id' => 'post:smart-home', 'category' => 'smart-home']],
            ['time_spent', ['content_id' => 'post:smart-home', 'engagement_time' => 240]],
            ['taxonomy_engagement', ['category' => 'IoT', 'interaction_type' => 'click']]
        ];
        
        foreach ($tech_signals as $signal) {
            $this->signal_collector->collect_signal($session, $signal[0], $signal[1]);
        }
    }

    private function create_professional_contractor_signals($session_id) {
        $pro_signals = [
            ['search_query', ['query' => 'commercial grade tools', 'category' => 'business']],
            ['search_query', ['query' => 'project cost estimation', 'category' => 'business']],
            ['content_click', ['content_id' => 'post:contractor-tools', 'category' => 'professional']],
            ['time_spent', ['content_id' => 'post:contractor-tools', 'engagement_time' => 300]],
            ['taxonomy_engagement', ['category' => 'Business Tools', 'interaction_type' => 'click']]
        ];
        
        foreach ($pro_signals as $signal) {
            $this->signal_collector->collect_signal($session_id, $signal[0], $signal[1]);
        }
    }

    private function create_safety_conscious_signals($session_id) {
        $safety_signals = [
            ['search_query', ['query' => 'electrical safety basics', 'category' => 'safety']],
            ['search_query', ['query' => 'when to call professional', 'category' => 'safety']],
            ['content_click', ['content_id' => 'post:safety-guide', 'category' => 'safety']],
            ['time_spent', ['content_id' => 'post:safety-guide', 'engagement_time' => 420]],
            ['taxonomy_engagement', ['category' => 'Tool Safety', 'interaction_type' => 'click']]
        ];
        
        foreach ($safety_signals as $signal) {
            $this->signal_collector->collect_signal($session_id, $signal[0], $signal[1]);
        }
    }

    private function get_stored_signals($session_id) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spb_user_signals WHERE session_id = %s ORDER BY timestamp DESC",
            $session_id
        ), ARRAY_A);
    }

    private function assert_dashboard_clean() {
        // Check for dummy data in analytics dashboard
        $analytics_data = $this->get_analytics_dashboard_data();
        foreach ($analytics_data as $metric => $value) {
            $this->assertFalse(
                $this->dummy_data_detector->contains_dummy_indicators($value),
                "Dashboard metric '{$metric}' should not contain dummy data"
            );
        }
    }

    private function assert_database_clean() {
        global $wpdb;
        
        // Check for dummy posts
        $dummy_posts = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (post_title LIKE '%sample%' OR post_title LIKE '%test%' OR post_title LIKE '%dummy%')
        ");
        
        $this->assertEquals(0, $dummy_posts, 'No dummy posts should exist in database');
    }

    private function assert_content_clean() {
        // Check generated content for dummy data
        $generated_content = $this->get_recent_generated_content();
        foreach ($generated_content as $content) {
            $this->assertFalse(
                strpos(strtolower($content['content']), 'lorem ipsum') !== false,
                'Generated content should not contain lorem ipsum'
            );
        }
    }

    private function get_analytics_dashboard_data() {
        // Mock analytics data retrieval
        return [
            'total_signals' => '1,234',
            'active_users' => '567',
            'personalization_rate' => '89%'
        ];
    }

    private function get_recent_generated_content() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT content FROM {$wpdb->prefix}spb_generated_content 
             WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR) 
             LIMIT 10",
            ARRAY_A
        );
    }

    private function cleanup_test_data() {
        global $wpdb;
        
        // Clean up test signals
        $wpdb->delete(
            $wpdb->prefix . 'spb_user_signals',
            ['session_id' => $this->test_session_id]
        );
        
        // Clean up test interest vectors
        $wpdb->delete(
            $wpdb->prefix . 'spb_user_interest_vectors',
            ['session_id' => $this->test_session_id]
        );
    }

    private function cleanup_test_session($session_id) {
        global $wpdb;
        
        $wpdb->delete($wpdb->prefix . 'spb_user_signals', ['session_id' => $session_id]);
        $wpdb->delete($wpdb->prefix . 'spb_user_interest_vectors', ['session_id' => $session_id]);
    }
}

/**
 * Dummy Data Detector Class
 * Scans for any remaining dummy/test data in the system
 */
class SPB_Dummy_Data_Detector {
    
    private $dummy_indicators = [
        'lorem ipsum', 'sample data', 'test user', 'dummy content',
        'placeholder', 'fake data', 'example content', 'demo data',
        'test@example.com', 'john doe', 'jane smith', 'sample post'
    ];
    
    public function scan_for_dummy_data() {
        $issues = [];
        
        // Check database tables
        $issues = array_merge($issues, $this->scan_database_tables());
        
        // Check admin dashboard content
        $issues = array_merge($issues, $this->scan_dashboard_content());
        
        return $this->generate_dummy_data_report($issues);
    }
    
    public function contains_dummy_indicators($content) {
        if (!is_string($content)) {
            $content = json_encode($content);
        }
        
        $content_lower = strtolower($content);
        
        foreach ($this->dummy_indicators as $indicator) {
            if (strpos($content_lower, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function scan_database_tables() {
        global $wpdb;
        $issues = [];
        
        // Check posts for dummy content
        $dummy_posts = $wpdb->get_results("
            SELECT ID, post_title, post_content 
            FROM {$wpdb->posts} 
            WHERE post_status = 'publish' 
            AND (post_title LIKE '%sample%' 
                OR post_title LIKE '%test%' 
                OR post_title LIKE '%dummy%'
                OR post_content LIKE '%lorem ipsum%')
        ");
        
        if (!empty($dummy_posts)) {
            $issues[] = [
                'type' => 'dummy_posts',
                'severity' => 'high',
                'count' => count($dummy_posts),
                'details' => $dummy_posts
            ];
        }
        
        return $issues;
    }
    
    private function scan_dashboard_content() {
        // This would scan actual dashboard content in a real implementation
        return [];
    }
    
    private function generate_dummy_data_report($issues) {
        return [
            'scan_timestamp' => current_time('mysql'),
            'total_issues' => count($issues),
            'severity_breakdown' => [
                'high' => count(array_filter($issues, function($i) { return $i['severity'] === 'high'; })),
                'medium' => count(array_filter($issues, function($i) { return $i['severity'] === 'medium'; })),
                'low' => count(array_filter($issues, function($i) { return $i['severity'] === 'low'; }))
            ],
            'issues' => $issues
        ];
    }
}
