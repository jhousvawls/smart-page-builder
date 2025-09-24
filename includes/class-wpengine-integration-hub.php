<?php
/**
 * WP Engine Integration Hub
 *
 * Orchestrates content discovery across Smart Search, Vector Database, and Recommendations
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP Engine Integration Hub class
 */
class SPB_WPEngine_Integration_Hub {
    
    /**
     * WP Engine API Client instance
     */
    private $api_client;
    
    /**
     * Query Enhancement Engine instance
     */
    private $query_enhancer;
    
    /**
     * Cache manager instance
     */
    private $cache_manager;
    
    /**
     * Default content discovery options
     */
    private $default_options = [
        'smart_search_weight' => 0.4,
        'vector_search_weight' => 0.4,
        'recommendations_weight' => 0.2,
        'max_results_per_source' => 10,
        'similarity_threshold' => 0.7,
        'cache_duration' => 1800,
        'enable_query_enhancement' => true,
        'merge_duplicate_content' => true
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_client = new SPB_WPEngine_API_Client();
        $this->query_enhancer = new SPB_Query_Enhancement_Engine();
        
        if (class_exists('SPB_Cache_Manager')) {
            $this->cache_manager = new SPB_Cache_Manager();
        }
    }
    
    /**
     * Discover content from multiple WP Engine sources
     *
     * @param string $query Search query
     * @param array $user_context User context data
     * @param array $options Discovery options
     * @return array Merged content discovery results
     */
    public function discover_content($query, $user_context = [], $options = []) {
        $options = wp_parse_args($options, $this->default_options);
        
        // Check cache first
        $cache_key = 'spb_content_discovery_' . md5($query . serialize($user_context) . serialize($options));
        if ($this->cache_manager && $options['cache_duration'] > 0) {
            $cached_result = $this->cache_manager->get($cache_key);
            if ($cached_result !== false) {
                return $cached_result;
            }
        }
        
        $discovery_result = [
            'query' => $query,
            'enhanced_query' => $query,
            'user_context' => $user_context,
            'sources' => [],
            'merged_results' => [],
            'total_results' => 0,
            'processing_time' => 0,
            'errors' => [],
            'fallback_used' => false
        ];
        
        $start_time = microtime(true);
        
        try {
            // Enhance query if enabled
            $enhanced_query_data = null;
            if ($options['enable_query_enhancement']) {
                $enhanced_query_data = $this->query_enhancer->enhance_query($query);
                $discovery_result['enhanced_query'] = $enhanced_query_data['enhanced_query'];
                $discovery_result['query_enhancement'] = $enhanced_query_data;
            }
            
            // Test API connection first
            $connection_test = $this->api_client->test_connection();
            
            if ($connection_test['success']) {
                // Discover content from multiple sources in parallel
                $source_results = $this->discover_from_all_sources(
                    $discovery_result['enhanced_query'],
                    $user_context,
                    $options
                );
                
                $discovery_result['sources'] = $source_results;
                
                // Merge and rank results
                $discovery_result['merged_results'] = $this->merge_and_rank_results(
                    $source_results,
                    $options
                );
            } else {
                // Use fallback content generation
                error_log('SPB: WP Engine API not available, using fallback content generation');
                $discovery_result['fallback_used'] = true;
                $discovery_result['errors'][] = 'WP Engine API not available: ' . $connection_test['error'];
                
                $fallback_results = $this->generate_fallback_content($query, $user_context, $options);
                $discovery_result['merged_results'] = $fallback_results;
            }
            
            $discovery_result['total_results'] = count($discovery_result['merged_results']);
            
        } catch (Exception $e) {
            error_log('SPB Content Discovery Error: ' . $e->getMessage());
            $discovery_result['errors'][] = $e->getMessage();
            
            // Use fallback content generation on any error
            if (empty($discovery_result['merged_results'])) {
                $discovery_result['fallback_used'] = true;
                $fallback_results = $this->generate_fallback_content($query, $user_context, $options);
                $discovery_result['merged_results'] = $fallback_results;
                $discovery_result['total_results'] = count($discovery_result['merged_results']);
            }
        }
        
        $discovery_result['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
        
        // Cache the result
        if ($this->cache_manager && $options['cache_duration'] > 0) {
            $this->cache_manager->set($cache_key, $discovery_result, $options['cache_duration']);
        }
        
        return $discovery_result;
    }
    
    /**
     * Discover content from all WP Engine sources
     *
     * @param string $enhanced_query Enhanced search query
     * @param array $user_context User context data
     * @param array $options Discovery options
     * @return array Results from all sources
     */
    private function discover_from_all_sources($enhanced_query, $user_context, $options) {
        $source_results = [
            'smart_search' => [],
            'vector_search' => [],
            'recommendations' => []
        ];
        
        // Smart Search
        try {
            $smart_search_options = [
                'limit' => $options['max_results_per_source'],
                'include_content' => true,
                'include_metadata' => true,
                'semantic_search' => true
            ];
            
            $smart_search_response = $this->api_client->smart_search($enhanced_query, $smart_search_options);
            
            if (!is_wp_error($smart_search_response) && isset($smart_search_response['data']['smartSearch']['results'])) {
                $source_results['smart_search'] = [
                    'results' => $smart_search_response['data']['smartSearch']['results'],
                    'total_results' => $smart_search_response['data']['smartSearch']['totalResults'],
                    'search_time' => $smart_search_response['data']['smartSearch']['searchTime'],
                    'weight' => $options['smart_search_weight']
                ];
            }
        } catch (Exception $e) {
            error_log('SPB Smart Search Error: ' . $e->getMessage());
            $source_results['smart_search']['error'] = $e->getMessage();
        }
        
        // Vector Search
        try {
            $vector_search_options = [
                'limit' => $options['max_results_per_source'],
                'similarity_threshold' => $options['similarity_threshold'],
                'content_types' => ['post', 'page']
            ];
            
            $vector_search_response = $this->api_client->vector_search($enhanced_query, $vector_search_options);
            
            if (!is_wp_error($vector_search_response) && isset($vector_search_response['data']['vectorSearch']['results'])) {
                $source_results['vector_search'] = [
                    'results' => $vector_search_response['data']['vectorSearch']['results'],
                    'total_results' => $vector_search_response['data']['vectorSearch']['totalResults'],
                    'query_time' => $vector_search_response['data']['vectorSearch']['queryTime'],
                    'weight' => $options['vector_search_weight']
                ];
            }
        } catch (Exception $e) {
            error_log('SPB Vector Search Error: ' . $e->getMessage());
            $source_results['vector_search']['error'] = $e->getMessage();
        }
        
        // Recommendations
        try {
            $recommendations_options = [
                'limit' => $options['max_results_per_source'],
                'content_types' => ['post', 'page'],
                'include_trending' => true
            ];
            
            $recommendations_response = $this->api_client->get_recommendations($user_context, $recommendations_options);
            
            if (!is_wp_error($recommendations_response) && isset($recommendations_response['data']['recommendations']['results'])) {
                $source_results['recommendations'] = [
                    'results' => $recommendations_response['data']['recommendations']['results'],
                    'total_results' => $recommendations_response['data']['recommendations']['totalResults'],
                    'recommendation_time' => $recommendations_response['data']['recommendations']['recommendationTime'],
                    'weight' => $options['recommendations_weight']
                ];
            }
        } catch (Exception $e) {
            error_log('SPB Recommendations Error: ' . $e->getMessage());
            $source_results['recommendations']['error'] = $e->getMessage();
        }
        
        return $source_results;
    }
    
    /**
     * Merge and rank results from multiple sources
     *
     * @param array $source_results Results from all sources
     * @param array $options Merging options
     * @return array Merged and ranked results
     */
    private function merge_and_rank_results($source_results, $options) {
        $all_results = [];
        $seen_urls = [];
        
        // Collect all results with source weighting
        foreach ($source_results as $source_name => $source_data) {
            if (empty($source_data['results'])) {
                continue;
            }
            
            $source_weight = isset($source_data['weight']) ? $source_data['weight'] : 0.33;
            
            foreach ($source_data['results'] as $result) {
                $url = isset($result['url']) ? $result['url'] : '';
                
                // Skip if we've seen this URL and merge_duplicate_content is enabled
                if ($options['merge_duplicate_content'] && !empty($url) && isset($seen_urls[$url])) {
                    // Boost the existing result's score
                    $existing_index = $seen_urls[$url];
                    $all_results[$existing_index]['composite_score'] += $this->calculate_result_score($result, $source_name) * $source_weight * 0.5;
                    $all_results[$existing_index]['sources'][] = $source_name;
                    continue;
                }
                
                // Calculate composite score
                $base_score = $this->calculate_result_score($result, $source_name);
                $composite_score = $base_score * $source_weight;
                
                $merged_result = [
                    'id' => $result['id'] ?? uniqid(),
                    'title' => $result['title'] ?? '',
                    'content' => $result['content'] ?? '',
                    'excerpt' => $result['excerpt'] ?? '',
                    'url' => $url,
                    'type' => $result['type'] ?? 'post',
                    'metadata' => $result['metadata'] ?? [],
                    'source' => $source_name,
                    'sources' => [$source_name],
                    'base_score' => $base_score,
                    'composite_score' => $composite_score,
                    'original_result' => $result
                ];
                
                $all_results[] = $merged_result;
                
                if (!empty($url)) {
                    $seen_urls[$url] = count($all_results) - 1;
                }
            }
        }
        
        // Sort by composite score (descending)
        usort($all_results, function($a, $b) {
            return $b['composite_score'] <=> $a['composite_score'];
        });
        
        return $all_results;
    }
    
    /**
     * Calculate result score based on source-specific metrics
     *
     * @param array $result Result data
     * @param string $source_name Source name
     * @return float Calculated score
     */
    private function calculate_result_score($result, $source_name) {
        $score = 0.5; // Base score
        
        switch ($source_name) {
            case 'smart_search':
                $score = isset($result['score']) ? floatval($result['score']) : 0.5;
                break;
                
            case 'vector_search':
                $score = isset($result['similarity']) ? floatval($result['similarity']) : 0.5;
                break;
                
            case 'recommendations':
                $score = isset($result['score']) ? floatval($result['score']) : 0.5;
                break;
        }
        
        // Boost score for recent content
        if (isset($result['metadata']['publishDate'])) {
            $publish_date = strtotime($result['metadata']['publishDate']);
            $days_old = (time() - $publish_date) / (24 * 60 * 60);
            
            if ($days_old < 30) {
                $score += 0.1; // Recent content boost
            }
        }
        
        // Boost score for longer content
        if (isset($result['content'])) {
            $content_length = strlen($result['content']);
            if ($content_length > 1000) {
                $score += 0.05; // Substantial content boost
            }
        }
        
        return min(1.0, max(0.0, $score));
    }
    
    /**
     * Generate fallback content when WP Engine APIs are not available
     *
     * @param string $query Search query
     * @param array $user_context User context data
     * @param array $options Discovery options
     * @return array Fallback content results
     */
    private function generate_fallback_content($query, $user_context, $options) {
        global $wpdb;
        
        $fallback_results = [];
        
        // Search existing WordPress content
        $search_terms = explode(' ', $query);
        $search_terms = array_filter($search_terms, function($term) {
            return strlen($term) > 2; // Filter out short words
        });
        
        if (!empty($search_terms)) {
            // Build search query for WordPress posts/pages
            $like_conditions = [];
            $search_values = [];
            
            foreach ($search_terms as $term) {
                $like_conditions[] = "(post_title LIKE %s OR post_content LIKE %s)";
                $search_values[] = '%' . $wpdb->esc_like($term) . '%';
                $search_values[] = '%' . $wpdb->esc_like($term) . '%';
            }
            
            $where_clause = implode(' OR ', $like_conditions);
            
            $sql = "SELECT ID, post_title, post_content, post_excerpt, post_type, post_date 
                    FROM {$wpdb->posts} 
                    WHERE post_status = 'publish' 
                    AND post_type IN ('post', 'page') 
                    AND ({$where_clause})
                    ORDER BY post_date DESC 
                    LIMIT %d";
            
            $search_values[] = $options['max_results_per_source'];
            
            $posts = $wpdb->get_results($wpdb->prepare($sql, $search_values));
            
            foreach ($posts as $post) {
                $excerpt = !empty($post->post_excerpt) ? $post->post_excerpt : wp_trim_words($post->post_content, 30);
                
                $fallback_results[] = [
                    'id' => 'fallback_' . $post->ID,
                    'title' => $post->post_title,
                    'content' => $post->post_content,
                    'excerpt' => $excerpt,
                    'url' => get_permalink($post->ID),
                    'type' => $post->post_type,
                    'metadata' => [
                        'author' => get_the_author_meta('display_name', get_post_field('post_author', $post->ID)),
                        'publishDate' => $post->post_date,
                        'categories' => wp_get_post_categories($post->ID, ['fields' => 'names']),
                        'tags' => wp_get_post_tags($post->ID, ['fields' => 'names'])
                    ],
                    'source' => 'fallback_search',
                    'sources' => ['fallback_search'],
                    'base_score' => 0.6, // Decent score for fallback content
                    'composite_score' => 0.6,
                    'original_result' => $post
                ];
            }
        }
        
        // If no WordPress content found, generate synthetic content
        if (empty($fallback_results)) {
            $fallback_results = $this->generate_synthetic_content($query, $options);
        }
        
        return $fallback_results;
    }
    
    /**
     * Generate synthetic content as last resort
     *
     * @param string $query Search query
     * @param array $options Discovery options
     * @return array Synthetic content results
     */
    private function generate_synthetic_content($query, $options) {
        $synthetic_results = [];
        
        // Generate basic content based on query
        $query_words = explode(' ', $query);
        $main_topic = ucfirst($query);
        
        // Create a few synthetic results
        $templates = [
            [
                'title' => "Complete Guide to {$main_topic}",
                'content' => "This comprehensive guide covers everything you need to know about {$query}. Learn the basics, advanced techniques, and best practices.",
                'type' => 'guide'
            ],
            [
                'title' => "Top Tips for {$main_topic}",
                'content' => "Discover expert tips and tricks for {$query}. These proven strategies will help you achieve better results.",
                'type' => 'tips'
            ],
            [
                'title' => "{$main_topic}: Getting Started",
                'content' => "New to {$query}? This beginner-friendly introduction will help you get started with the fundamentals.",
                'type' => 'beginner'
            ]
        ];
        
        foreach ($templates as $index => $template) {
            $synthetic_results[] = [
                'id' => 'synthetic_' . ($index + 1),
                'title' => $template['title'],
                'content' => $template['content'],
                'excerpt' => wp_trim_words($template['content'], 20),
                'url' => '#',
                'type' => 'synthetic',
                'metadata' => [
                    'author' => 'Smart Page Builder',
                    'publishDate' => current_time('mysql'),
                    'categories' => [$main_topic],
                    'tags' => $query_words
                ],
                'source' => 'synthetic_content',
                'sources' => ['synthetic_content'],
                'base_score' => 0.4, // Lower score for synthetic content
                'composite_score' => 0.4,
                'original_result' => $template
            ];
        }
        
        return $synthetic_results;
    }
    
    /**
     * Get content discovery statistics
     *
     * @param int $days Number of days to analyze
     * @return array Discovery statistics
     */
    public function get_discovery_stats($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_discoveries,
                AVG(JSON_EXTRACT(generated_content, '$.processing_time')) as avg_processing_time,
                SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved_count
            FROM {$table_name} 
            WHERE created_at >= %s 
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ", $date_limit), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Test WP Engine integration
     *
     * @return array Test results
     */
    public function test_integration() {
        $test_results = [
            'api_connection' => false,
            'smart_search' => false,
            'vector_search' => false,
            'recommendations' => false,
            'query_enhancement' => false,
            'overall_status' => 'failed',
            'errors' => []
        ];
        
        try {
            // Test API connection
            $connection_test = $this->api_client->test_connection();
            $test_results['api_connection'] = $connection_test['success'];
            
            if (!$connection_test['success']) {
                $test_results['errors'][] = 'API Connection: ' . $connection_test['error'];
                return $test_results;
            }
            
            // Test Smart Search
            $smart_search_test = $this->api_client->smart_search('test query', ['limit' => 1]);
            $test_results['smart_search'] = !is_wp_error($smart_search_test);
            
            if (is_wp_error($smart_search_test)) {
                $test_results['errors'][] = 'Smart Search: ' . $smart_search_test->get_error_message();
            }
            
            // Test Vector Search
            $vector_search_test = $this->api_client->vector_search('test query', ['limit' => 1]);
            $test_results['vector_search'] = !is_wp_error($vector_search_test);
            
            if (is_wp_error($vector_search_test)) {
                $test_results['errors'][] = 'Vector Search: ' . $vector_search_test->get_error_message();
            }
            
            // Test Recommendations
            $recommendations_test = $this->api_client->get_recommendations([], ['limit' => 1]);
            $test_results['recommendations'] = !is_wp_error($recommendations_test);
            
            if (is_wp_error($recommendations_test)) {
                $test_results['errors'][] = 'Recommendations: ' . $recommendations_test->get_error_message();
            }
            
            // Test Query Enhancement
            $enhancement_test = $this->query_enhancer->enhance_query('test query');
            $test_results['query_enhancement'] = !empty($enhancement_test['enhanced_query']);
            
            // Determine overall status
            $successful_tests = array_filter([
                $test_results['api_connection'],
                $test_results['smart_search'],
                $test_results['vector_search'],
                $test_results['recommendations'],
                $test_results['query_enhancement']
            ]);
            
            if (count($successful_tests) >= 4) {
                $test_results['overall_status'] = 'passed';
            } elseif (count($successful_tests) >= 2) {
                $test_results['overall_status'] = 'partial';
            }
            
        } catch (Exception $e) {
            $test_results['errors'][] = 'Integration Test Error: ' . $e->getMessage();
        }
        
        return $test_results;
    }
    
    /**
     * Clear content discovery cache
     */
    public function clear_cache() {
        if ($this->cache_manager) {
            $this->cache_manager->delete_group('spb_content_discovery');
        }
        
        $this->query_enhancer->clear_cache();
    }
    
    /**
     * Update discovery options
     *
     * @param array $new_options New options to merge
     */
    public function update_options($new_options) {
        $this->default_options = wp_parse_args($new_options, $this->default_options);
    }
    
    /**
     * Get current discovery options
     *
     * @return array Current options
     */
    public function get_options() {
        return $this->default_options;
    }
}
