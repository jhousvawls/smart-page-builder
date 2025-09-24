<?php
/**
 * Comprehensive Test Suite for Intelligent Discovery
 * Tests the Content Recommendation and Search Enhancement System
 *
 * @package Smart_Page_Builder
 * @subpackage Tests
 */

class Test_Intelligent_Discovery extends WP_UnitTestCase {

    private $content_analyzer;
    private $search_enhancer;
    private $recommendation_engine;
    private $test_session_id;
    private $test_content_ids;

    public function setUp(): void {
        parent::setUp();
        
        $this->content_analyzer = new SPB_Content_Analyzer();
        $this->search_enhancer = new SPB_Search_Enhancement_Engine();
        $this->recommendation_engine = new SPB_Recommendation_Engine();
        $this->test_session_id = 'discovery_test_' . time();
        
        // Create test content
        $this->setup_test_content();
    }

    public function tearDown(): void {
        $this->cleanup_test_content();
        parent::tearDown();
    }

    /**
     * Test 1: Content Relevance Scoring
     * Validates that content is scored correctly based on interest vectors
     */
    public function test_content_relevance_scoring() {
        $start_time = microtime(true);
        
        // Create tech enthusiast interest vector
        $tech_interest_vector = [
            'technology' => 0.85,
            'smart-home' => 0.78,
            'automation' => 0.72,
            'business' => 0.15,
            'safety' => 0.25
        ];
        
        // Test content relevance calculation
        $tech_content = $this->get_test_content('smart-home-automation');
        $relevance_score = $this->content_analyzer->calculate_content_relevance(
            $tech_content, 
            $tech_interest_vector
        );
        
        $this->assertGreaterThan(0.7, $relevance_score, 'Tech content should have high relevance for tech user');
        
        // Test irrelevant content
        $safety_content = $this->get_test_content('safety-guidelines');
        $safety_relevance = $this->content_analyzer->calculate_content_relevance(
            $safety_content, 
            $tech_interest_vector
        );
        
        $this->assertLessThan($relevance_score, $safety_relevance, 'Safety content should have lower relevance for tech user');
        
        // Check performance
        $scoring_time = (microtime(true) - $start_time) * 1000;
        $this->assertLessThan(50, $scoring_time, 'Content relevance scoring should complete in <50ms');
    }

    /**
     * Test 2: Cosine Similarity Calculation
     * Tests the mathematical accuracy of similarity calculations
     */
    public function test_cosine_similarity_calculation() {
        $semantic_analyzer = new SPB_Semantic_Analyzer();
        
        // Test identical vectors (should be 1.0)
        $vector_a = ['tech' => 0.8, 'home' => 0.6];
        $vector_b = ['tech' => 0.8, 'home' => 0.6];
        $similarity = $semantic_analyzer->cosine_similarity($vector_a, $vector_b);
        $this->assertEquals(1.0, $similarity, 'Identical vectors should have similarity of 1.0', 0.001);
        
        // Test orthogonal vectors (should be 0.0)
        $vector_c = ['tech' => 1.0, 'home' => 0.0];
        $vector_d = ['tech' => 0.0, 'home' => 1.0];
        $orthogonal_similarity = $semantic_analyzer->cosine_similarity($vector_c, $vector_d);
        $this->assertEquals(0.0, $orthogonal_similarity, 'Orthogonal vectors should have similarity of 0.0', 0.001);
        
        // Test partial similarity
        $vector_e = ['tech' => 0.8, 'home' => 0.6, 'safety' => 0.2];
        $vector_f = ['tech' => 0.6, 'home' => 0.8, 'business' => 0.4];
        $partial_similarity = $semantic_analyzer->cosine_similarity($vector_e, $vector_f);
        $this->assertGreaterThan(0.0, $partial_similarity, 'Partially similar vectors should have positive similarity');
        $this->assertLessThan(1.0, $partial_similarity, 'Partially similar vectors should have similarity less than 1.0');
    }

    /**
     * Test 3: Search Result Personalization
     * Tests that search results are enhanced based on user interests
     */
    public function test_search_result_personalization() {
        $start_time = microtime(true);
        
        // Create user interest vector
        $user_interests = [
            'technology' => 0.9,
            'smart-home' => 0.8,
            'professional' => 0.3,
            'safety' => 0.4
        ];
        
        // Perform search
        $search_query = 'home automation';
        $base_results = $this->get_base_search_results($search_query);
        $personalized_results = $this->search_enhancer->enhance_search_results(
            $base_results,
            $user_interests,
            $this->test_session_id
        );
        
        // Verify personalization applied
        $this->assertNotEmpty($personalized_results, 'Personalized results should not be empty');
        $this->assertCount(count($base_results), $personalized_results, 'Result count should be preserved');
        
        // Check that tech content is prioritized
        $first_result = $personalized_results[0];
        $this->assertContains('technology', $first_result['categories'], 'First result should be tech-related');
        
        // Verify relevance scores are calculated
        foreach ($personalized_results as $result) {
            $this->assertArrayHasKey('relevance_score', $result, 'Each result should have relevance score');
            $this->assertGreaterThan(0, $result['relevance_score'], 'Relevance score should be positive');
        }
        
        // Check performance
        $enhancement_time = (microtime(true) - $start_time) * 1000;
        $this->assertLessThan(150, $enhancement_time, 'Search enhancement should complete in <150ms');
    }

    /**
     * Test 4: Diversity Algorithm
     * Tests that recommendations include diverse content to prevent filter bubbles
     */
    public function test_diversity_algorithm() {
        // Create strong tech interest vector
        $tech_interests = [
            'technology' => 0.95,
            'smart-home' => 0.90,
            'automation' => 0.85,
            'safety' => 0.10,
            'business' => 0.05
        ];
        
        // Get recommendations with diversity
        $recommendations = $this->recommendation_engine->get_personalized_recommendations(
            $tech_interests,
            10, // count
            0.3 // 30% diversity factor
        );
        
        $this->assertCount(10, $recommendations, 'Should return requested number of recommendations');
        
        // Check diversity - at least 30% should be non-primary interests
        $primary_categories = ['technology', 'smart-home', 'automation'];
        $diverse_count = 0;
        
        foreach ($recommendations as $rec) {
            $is_diverse = true;
            foreach ($primary_categories as $category) {
                if (in_array($category, $rec['categories'])) {
                    $is_diverse = false;
                    break;
                }
            }
            if ($is_diverse) {
                $diverse_count++;
            }
        }
        
        $diversity_percentage = $diverse_count / count($recommendations);
        $this->assertGreaterThanOrEqual(0.25, $diversity_percentage, 'At least 25% of recommendations should be diverse');
    }

    /**
     * Test 5: Real-time Content Discovery
     * Tests the system's ability to surface relevant content in real-time
     */
    public function test_realtime_content_discovery() {
        $start_time = microtime(true);
        
        // Simulate user behavior signals
        $this->simulate_user_signals();
        
        // Get real-time recommendations
        $realtime_recs = $this->recommendation_engine->get_realtime_recommendations(
            $this->test_session_id,
            5
        );
        
        $this->assertNotEmpty($realtime_recs, 'Real-time recommendations should not be empty');
        $this->assertLessThanOrEqual(5, count($realtime_recs), 'Should not exceed requested count');
        
        // Verify recommendations are relevant to recent signals
        foreach ($realtime_recs as $rec) {
            $this->assertArrayHasKey('relevance_score', $rec, 'Real-time rec should have relevance score');
            $this->assertGreaterThan(0.3, $rec['relevance_score'], 'Real-time rec should have decent relevance');
        }
        
        // Check real-time performance
        $realtime_time = (microtime(true) - $start_time) * 1000;
        $this->assertLessThan(200, $realtime_time, 'Real-time discovery should complete in <200ms');
    }

    /**
     * Test 6: Content Gap Identification
     * Tests the system's ability to identify missing content opportunities
     */
    public function test_content_gap_identification() {
        // Create search queries that should trigger content gaps
        $failed_searches = [
            'smart doorbell installation with existing chime',
            'professional contractor markup strategies 2025',
            'workshop ventilation requirements DIY'
        ];
        
        $gap_analyzer = new SPB_Content_Gap_Analyzer();
        
        foreach ($failed_searches as $query) {
            $gap_analysis = $gap_analyzer->analyze_content_gap($query, $this->test_session_id);
            
            $this->assertArrayHasKey('gap_identified', $gap_analysis, 'Gap analysis should identify if gap exists');
            $this->assertArrayHasKey('suggested_topics', $gap_analysis, 'Gap analysis should suggest topics');
            $this->assertArrayHasKey('priority_score', $gap_analysis, 'Gap analysis should have priority score');
            
            if ($gap_analysis['gap_identified']) {
                $this->assertNotEmpty($gap_analysis['suggested_topics'], 'Should suggest topics for identified gaps');
                $this->assertGreaterThan(0, $gap_analysis['priority_score'], 'Priority score should be positive');
            }
        }
    }

    /**
     * Test 7: API Response Performance
     * Tests that all discovery APIs meet performance requirements
     */
    public function test_api_response_performance() {
        $performance_metrics = [];
        
        // Test recommendation API performance
        $start_time = microtime(true);
        $this->recommendation_engine->get_personalized_recommendations(['technology' => 0.8], 5);
        $performance_metrics['recommendation_api'] = (microtime(true) - $start_time) * 1000;
        
        // Test search enhancement API performance
        $start_time = microtime(true);
        $base_results = $this->get_base_search_results('test query');
        $this->search_enhancer->enhance_search_results($base_results, ['tech' => 0.8], $this->test_session_id);
        $performance_metrics['search_enhancement_api'] = (microtime(true) - $start_time) * 1000;
        
        // Test content analysis API performance
        $start_time = microtime(true);
        $test_content = $this->get_test_content('smart-home-automation');
        $this->content_analyzer->calculate_content_relevance($test_content, ['tech' => 0.8]);
        $performance_metrics['content_analysis_api'] = (microtime(true) - $start_time) * 1000;
        
        // Validate performance requirements
        $this->assertLessThan(100, $performance_metrics['recommendation_api'], 'Recommendation API should be <100ms');
        $this->assertLessThan(150, $performance_metrics['search_enhancement_api'], 'Search enhancement API should be <150ms');
        $this->assertLessThan(50, $performance_metrics['content_analysis_api'], 'Content analysis API should be <50ms');
        
        // Log performance metrics
        error_log('SPB Discovery Performance Metrics: ' . json_encode($performance_metrics));
    }

    /**
     * Test 8: Cross-Platform Compatibility
     * Tests that discovery works across different platforms (web, mobile, API)
     */
    public function test_cross_platform_compatibility() {
        $platforms = ['web', 'mobile', 'api'];
        $test_interests = ['technology' => 0.8, 'smart-home' => 0.7];
        
        foreach ($platforms as $platform) {
            $platform_recs = $this->recommendation_engine->get_platform_recommendations(
                $test_interests,
                $platform,
                5
            );
            
            $this->assertNotEmpty($platform_recs, "Recommendations should work for {$platform} platform");
            $this->assertLessThanOrEqual(5, count($platform_recs), "Should respect count limit for {$platform}");
            
            // Verify platform-specific formatting
            foreach ($platform_recs as $rec) {
                $this->assertArrayHasKey('platform_data', $rec, "Should have platform data for {$platform}");
                $this->assertEquals($platform, $rec['platform_data']['target_platform'], "Should target correct platform");
            }
        }
    }

    // Helper Methods

    private function setup_test_content() {
        $this->test_content_ids = [];
        
        // Create test posts for different categories
        $test_posts = [
            [
                'title' => 'Smart Home Automation Guide',
                'content' => 'Complete guide to setting up smart home automation with IoT devices and hubs.',
                'categories' => ['technology', 'smart-home', 'automation'],
                'slug' => 'smart-home-automation'
            ],
            [
                'title' => 'Professional Contractor Tools',
                'content' => 'Essential tools and equipment for professional contractors and construction projects.',
                'categories' => ['professional', 'business', 'tools'],
                'slug' => 'contractor-tools'
            ],
            [
                'title' => 'Safety Guidelines for DIY Projects',
                'content' => 'Important safety guidelines and best practices for DIY home improvement projects.',
                'categories' => ['safety', 'beginner', 'diy'],
                'slug' => 'safety-guidelines'
            ]
        ];
        
        foreach ($test_posts as $post_data) {
            $post_id = wp_insert_post([
                'post_title' => $post_data['title'],
                'post_content' => $post_data['content'],
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_name' => $post_data['slug']
            ]);
            
            if ($post_id) {
                $this->test_content_ids[] = $post_id;
                
                // Add category metadata
                update_post_meta($post_id, '_spb_categories', $post_data['categories']);
            }
        }
    }

    private function get_test_content($slug) {
        $post = get_page_by_path($slug, OBJECT, 'post');
        if ($post) {
            return [
                'ID' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'categories' => get_post_meta($post->ID, '_spb_categories', true) ?: []
            ];
        }
        return null;
    }

    private function get_base_search_results($query) {
        // Simulate base search results
        return [
            [
                'ID' => $this->test_content_ids[0] ?? 1,
                'title' => 'Smart Home Automation Guide',
                'excerpt' => 'Complete guide to smart home setup...',
                'categories' => ['technology', 'smart-home'],
                'score' => 0.8
            ],
            [
                'ID' => $this->test_content_ids[1] ?? 2,
                'title' => 'Professional Contractor Tools',
                'excerpt' => 'Essential tools for contractors...',
                'categories' => ['professional', 'business'],
                'score' => 0.6
            ],
            [
                'ID' => $this->test_content_ids[2] ?? 3,
                'title' => 'Safety Guidelines for DIY',
                'excerpt' => 'Important safety practices...',
                'categories' => ['safety', 'beginner'],
                'score' => 0.4
            ]
        ];
    }

    private function simulate_user_signals() {
        $signal_collector = new SPB_Signal_Collector();
        
        // Simulate recent user activity
        $signals = [
            ['search_query', ['query' => 'smart home setup', 'category' => 'technology']],
            ['content_click', ['content_id' => 'post:smart-home', 'category' => 'technology']],
            ['time_spent', ['content_id' => 'post:smart-home', 'engagement_time' => 180]]
        ];
        
        foreach ($signals as $signal) {
            $signal_collector->collect_signal($this->test_session_id, $signal[0], $signal[1]);
        }
    }

    private function cleanup_test_content() {
        foreach ($this->test_content_ids as $post_id) {
            wp_delete_post($post_id, true);
        }
        
        // Clean up test signals
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'spb_user_signals',
            ['session_id' => $this->test_session_id]
        );
    }
}

/**
 * Mock Classes for Testing
 * These would be replaced by actual implementations
 */

class SPB_Content_Analyzer {
    public function calculate_content_relevance($content, $interest_vector) {
        // Mock implementation
        $content_categories = $content['categories'] ?? [];
        $relevance = 0;
        
        foreach ($content_categories as $category) {
            if (isset($interest_vector[$category])) {
                $relevance += $interest_vector[$category];
            }
        }
        
        return min(1.0, $relevance / count($content_categories));
    }
}

class SPB_Search_Enhancement_Engine {
    public function enhance_search_results($base_results, $user_interests, $session_id) {
        $enhanced_results = [];
        
        foreach ($base_results as $result) {
            $enhanced_result = $result;
            $enhanced_result['relevance_score'] = $this->calculate_relevance($result, $user_interests);
            $enhanced_results[] = $enhanced_result;
        }
        
        // Sort by relevance score
        usort($enhanced_results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return $enhanced_results;
    }
    
    private function calculate_relevance($result, $user_interests) {
        $relevance = 0;
        $categories = $result['categories'] ?? [];
        
        foreach ($categories as $category) {
            if (isset($user_interests[$category])) {
                $relevance += $user_interests[$category];
            }
        }
        
        return $relevance / max(1, count($categories));
    }
}

class SPB_Recommendation_Engine {
    public function get_personalized_recommendations($interest_vector, $count, $diversity_factor = 0.3) {
        // Mock implementation with diversity
        $all_content = $this->get_all_content();
        $scored_content = [];
        
        foreach ($all_content as $content) {
            $score = $this->calculate_content_score($content, $interest_vector);
            $scored_content[] = array_merge($content, ['relevance_score' => $score]);
        }
        
        // Sort by score
        usort($scored_content, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // Apply diversity algorithm
        return $this->apply_diversity($scored_content, $count, $diversity_factor);
    }
    
    public function get_realtime_recommendations($session_id, $count) {
        // Mock real-time recommendations
        return array_slice($this->get_all_content(), 0, $count);
    }
    
    public function get_platform_recommendations($interests, $platform, $count) {
        $recommendations = $this->get_personalized_recommendations($interests, $count);
        
        // Add platform-specific data
        foreach ($recommendations as &$rec) {
            $rec['platform_data'] = [
                'target_platform' => $platform,
                'format' => $platform === 'mobile' ? 'compact' : 'full'
            ];
        }
        
        return $recommendations;
    }
    
    private function get_all_content() {
        return [
            ['title' => 'Smart Home Guide', 'categories' => ['technology', 'smart-home']],
            ['title' => 'Contractor Tools', 'categories' => ['professional', 'business']],
            ['title' => 'Safety Guidelines', 'categories' => ['safety', 'beginner']],
            ['title' => 'DIY Projects', 'categories' => ['diy', 'beginner']],
            ['title' => 'Business Tips', 'categories' => ['business', 'professional']]
        ];
    }
    
    private function calculate_content_score($content, $interest_vector) {
        $score = 0;
        foreach ($content['categories'] as $category) {
            if (isset($interest_vector[$category])) {
                $score += $interest_vector[$category];
            }
        }
        return $score / count($content['categories']);
    }
    
    private function apply_diversity($scored_content, $count, $diversity_factor) {
        $selected = [];
        $primary_count = (int)($count * (1 - $diversity_factor));
        
        // Select top scoring content
        for ($i = 0; $i < $primary_count && $i < count($scored_content); $i++) {
            $selected[] = $scored_content[$i];
        }
        
        // Add diverse content
        $remaining = array_slice($scored_content, $primary_count);
        $diverse_count = $count - count($selected);
        
        for ($i = 0; $i < $diverse_count && $i < count($remaining); $i++) {
            $selected[] = $remaining[$i];
        }
        
        return $selected;
    }
}

class SPB_Semantic_Analyzer {
    public function cosine_similarity($vector_a, $vector_b) {
        $dot_product = 0;
        $magnitude_a = 0;
        $magnitude_b = 0;
        
        $all_keys = array_unique(array_merge(array_keys($vector_a), array_keys($vector_b)));
        
        foreach ($all_keys as $key) {
            $a_val = $vector_a[$key] ?? 0;
            $b_val = $vector_b[$key] ?? 0;
            
            $dot_product += $a_val * $b_val;
            $magnitude_a += $a_val * $a_val;
            $magnitude_b += $b_val * $b_val;
        }
        
        $magnitude_a = sqrt($magnitude_a);
        $magnitude_b = sqrt($magnitude_b);
        
        if ($magnitude_a == 0 || $magnitude_b == 0) {
            return 0;
        }
        
        return $dot_product / ($magnitude_a * $magnitude_b);
    }
}

class SPB_Content_Gap_Analyzer {
    public function analyze_content_gap($query, $session_id) {
        // Mock content gap analysis
        $existing_content = $this->search_existing_content($query);
        
        if (count($existing_content) < 2) {
            return [
                'gap_identified' => true,
                'suggested_topics' => $this->generate_topic_suggestions($query),
                'priority_score' => 0.8,
                'query' => $query
            ];
        }
        
        return [
            'gap_identified' => false,
            'suggested_topics' => [],
            'priority_score' => 0.0,
            'query' => $query
        ];
    }
    
    private function search_existing_content($query) {
        // Mock search for existing content
        return []; // Simulate no existing content
    }
    
    private function generate_topic_suggestions($query) {
        return [
            'Step-by-step installation guide',
            'Common troubleshooting issues',
            'Required tools and materials',
            'Safety considerations'
        ];
    }
}
