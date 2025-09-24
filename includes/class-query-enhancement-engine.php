<?php
/**
 * Query Enhancement Engine
 *
 * Handles AI-powered query expansion, synonym detection, and intent classification
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Query Enhancement Engine class
 */
class SPB_Query_Enhancement_Engine {
    
    /**
     * AI Provider Manager instance
     */
    private $ai_provider_manager;
    
    /**
     * Cache manager instance
     */
    private $cache_manager;
    
    /**
     * Intent classification patterns
     */
    private $intent_patterns = [
        'educational' => [
            'keywords' => ['how to', 'what is', 'why', 'when', 'where', 'tutorial', 'guide', 'learn', 'explain'],
            'patterns' => ['/^(how|what|why|when|where)\s+/i', '/\b(tutorial|guide|learn|explain)\b/i']
        ],
        'commercial' => [
            'keywords' => ['buy', 'purchase', 'price', 'cost', 'sale', 'discount', 'deal', 'shop', 'order'],
            'patterns' => ['/\b(buy|purchase|price|cost|sale|discount|deal|shop|order)\b/i']
        ],
        'informational' => [
            'keywords' => ['about', 'information', 'details', 'facts', 'overview', 'summary'],
            'patterns' => ['/\b(about|information|details|facts|overview|summary)\b/i']
        ],
        'navigational' => [
            'keywords' => ['contact', 'location', 'address', 'phone', 'email', 'hours', 'directions'],
            'patterns' => ['/\b(contact|location|address|phone|email|hours|directions)\b/i']
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load dependencies if available
        if (class_exists('SPB_AI_Provider_Manager')) {
            $this->ai_provider_manager = new SPB_AI_Provider_Manager();
        }
        
        if (class_exists('SPB_Cache_Manager')) {
            $this->cache_manager = new SPB_Cache_Manager();
        }
    }
    
    /**
     * Enhance search query with AI-powered expansion
     *
     * @param string $original_query Original search query
     * @param array $options Enhancement options
     * @return array Enhanced query data
     */
    public function enhance_query($original_query, $options = []) {
        $default_options = [
            'expand_synonyms' => true,
            'detect_intent' => true,
            'add_context' => true,
            'max_synonyms' => 5,
            'cache_duration' => 3600
        ];
        
        $options = wp_parse_args($options, $default_options);
        
        // Check cache first
        $cache_key = 'spb_query_enhancement_' . md5($original_query . serialize($options));
        if ($this->cache_manager && $options['cache_duration'] > 0) {
            $cached_result = $this->cache_manager->get($cache_key);
            if ($cached_result !== false) {
                return $cached_result;
            }
        }
        
        $enhancement_data = [
            'original_query' => $original_query,
            'enhanced_query' => $original_query,
            'synonyms' => [],
            'intent' => 'informational',
            'confidence' => 0.0,
            'context_keywords' => [],
            'suggested_filters' => [],
            'processing_time' => 0
        ];
        
        $start_time = microtime(true);
        
        try {
            // Detect search intent
            if ($options['detect_intent']) {
                $enhancement_data['intent'] = $this->detect_search_intent($original_query);
            }
            
            // Expand with synonyms
            if ($options['expand_synonyms']) {
                $synonyms = $this->generate_synonyms($original_query, $options['max_synonyms']);
                $enhancement_data['synonyms'] = $synonyms;
                
                if (!empty($synonyms)) {
                    $enhancement_data['enhanced_query'] = $this->build_enhanced_query($original_query, $synonyms);
                }
            }
            
            // Add contextual keywords
            if ($options['add_context']) {
                $enhancement_data['context_keywords'] = $this->extract_context_keywords($original_query);
                $enhancement_data['suggested_filters'] = $this->suggest_content_filters($original_query, $enhancement_data['intent']);
            }
            
            // Calculate confidence score
            $enhancement_data['confidence'] = $this->calculate_enhancement_confidence($enhancement_data);
            
        } catch (Exception $e) {
            error_log('SPB Query Enhancement Error: ' . $e->getMessage());
            $enhancement_data['error'] = $e->getMessage();
        }
        
        $enhancement_data['processing_time'] = round((microtime(true) - $start_time) * 1000, 2);
        
        // Cache the result
        if ($this->cache_manager && $options['cache_duration'] > 0) {
            $this->cache_manager->set($cache_key, $enhancement_data, $options['cache_duration']);
        }
        
        // Store enhancement data for analytics
        $this->store_enhancement_data($enhancement_data);
        
        return $enhancement_data;
    }
    
    /**
     * Detect search intent from query
     *
     * @param string $query Search query
     * @return string Detected intent
     */
    public function detect_search_intent($query) {
        $query_lower = strtolower(trim($query));
        $intent_scores = [];
        
        foreach ($this->intent_patterns as $intent => $patterns) {
            $score = 0;
            
            // Check keyword matches
            foreach ($patterns['keywords'] as $keyword) {
                if (strpos($query_lower, $keyword) !== false) {
                    $score += 2;
                }
            }
            
            // Check pattern matches
            foreach ($patterns['patterns'] as $pattern) {
                if (preg_match($pattern, $query_lower)) {
                    $score += 3;
                }
            }
            
            $intent_scores[$intent] = $score;
        }
        
        // Return intent with highest score, default to informational
        $max_score = max($intent_scores);
        if ($max_score > 0) {
            return array_search($max_score, $intent_scores);
        }
        
        return 'informational';
    }
    
    /**
     * Generate synonyms for query terms using AI
     *
     * @param string $query Original query
     * @param int $max_synonyms Maximum number of synonyms
     * @return array Array of synonyms
     */
    private function generate_synonyms($query, $max_synonyms = 5) {
        if (!$this->ai_provider_manager) {
            return $this->get_basic_synonyms($query);
        }
        
        try {
            $prompt = "Generate {$max_synonyms} relevant synonyms and related terms for the search query: \"{$query}\"\n\n";
            $prompt .= "Return only the synonyms as a comma-separated list, no explanations.\n";
            $prompt .= "Focus on terms that would help find related content.\n";
            $prompt .= "Example: For 'web design' return: website design, UI design, web development, digital design, frontend design";
            
            $response = $this->ai_provider_manager->generate_content($prompt, [
                'max_tokens' => 100,
                'temperature' => 0.3
            ]);
            
            if (!empty($response['content'])) {
                $synonyms = array_map('trim', explode(',', $response['content']));
                return array_slice(array_filter($synonyms), 0, $max_synonyms);
            }
            
        } catch (Exception $e) {
            error_log('SPB Synonym Generation Error: ' . $e->getMessage());
        }
        
        return $this->get_basic_synonyms($query);
    }
    
    /**
     * Get basic synonyms without AI (fallback)
     *
     * @param string $query Original query
     * @return array Basic synonyms
     */
    private function get_basic_synonyms($query) {
        $basic_synonyms = [
            'web design' => ['website design', 'web development', 'UI design'],
            'marketing' => ['advertising', 'promotion', 'branding'],
            'business' => ['company', 'enterprise', 'organization'],
            'technology' => ['tech', 'digital', 'innovation'],
            'development' => ['programming', 'coding', 'software']
        ];
        
        $query_lower = strtolower($query);
        foreach ($basic_synonyms as $term => $synonyms) {
            if (strpos($query_lower, $term) !== false) {
                return array_slice($synonyms, 0, 3);
            }
        }
        
        return [];
    }
    
    /**
     * Build enhanced query with synonyms
     *
     * @param string $original_query Original query
     * @param array $synonyms Array of synonyms
     * @return string Enhanced query
     */
    private function build_enhanced_query($original_query, $synonyms) {
        if (empty($synonyms)) {
            return $original_query;
        }
        
        // Create OR conditions for synonyms
        $synonym_terms = array_slice($synonyms, 0, 3); // Limit to avoid overly complex queries
        $enhanced_parts = [$original_query];
        
        foreach ($synonym_terms as $synonym) {
            $enhanced_parts[] = $synonym;
        }
        
        return implode(' OR ', $enhanced_parts);
    }
    
    /**
     * Extract context keywords from query
     *
     * @param string $query Search query
     * @return array Context keywords
     */
    private function extract_context_keywords($query) {
        // Remove common stop words
        $stop_words = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were'];
        
        $words = preg_split('/\s+/', strtolower($query));
        $keywords = array_diff($words, $stop_words);
        
        // Filter out very short words
        $keywords = array_filter($keywords, function($word) {
            return strlen($word) > 2;
        });
        
        return array_values($keywords);
    }
    
    /**
     * Suggest content filters based on query and intent
     *
     * @param string $query Search query
     * @param string $intent Detected intent
     * @return array Suggested filters
     */
    private function suggest_content_filters($query, $intent) {
        $filters = [];
        
        switch ($intent) {
            case 'educational':
                $filters = ['post_type' => ['post', 'tutorial'], 'category' => ['guides', 'how-to']];
                break;
                
            case 'commercial':
                $filters = ['post_type' => ['product', 'service'], 'category' => ['products', 'services']];
                break;
                
            case 'navigational':
                $filters = ['post_type' => ['page'], 'category' => ['contact', 'about']];
                break;
                
            default:
                $filters = ['post_type' => ['post', 'page']];
        }
        
        return $filters;
    }
    
    /**
     * Calculate enhancement confidence score
     *
     * @param array $enhancement_data Enhancement data
     * @return float Confidence score (0-1)
     */
    private function calculate_enhancement_confidence($enhancement_data) {
        $confidence = 0.5; // Base confidence
        
        // Boost confidence if synonyms were found
        if (!empty($enhancement_data['synonyms'])) {
            $confidence += 0.2;
        }
        
        // Boost confidence if intent was clearly detected
        if ($enhancement_data['intent'] !== 'informational') {
            $confidence += 0.2;
        }
        
        // Boost confidence if context keywords were extracted
        if (!empty($enhancement_data['context_keywords'])) {
            $confidence += 0.1;
        }
        
        return min(1.0, $confidence);
    }
    
    /**
     * Store enhancement data for analytics
     *
     * @param array $enhancement_data Enhancement data
     */
    private function store_enhancement_data($enhancement_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_query_enhancements';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return; // Table doesn't exist yet
        }
        
        try {
            $wpdb->insert(
                $table_name,
                [
                    'original_query' => $enhancement_data['original_query'],
                    'enhanced_query' => $enhancement_data['enhanced_query'],
                    'detected_intent' => $enhancement_data['intent'],
                    'enhancement_data' => wp_json_encode([
                        'synonyms' => $enhancement_data['synonyms'],
                        'context_keywords' => $enhancement_data['context_keywords'],
                        'suggested_filters' => $enhancement_data['suggested_filters'],
                        'confidence' => $enhancement_data['confidence'],
                        'processing_time' => $enhancement_data['processing_time']
                    ]),
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );
        } catch (Exception $e) {
            error_log('SPB Enhancement Storage Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get enhancement statistics
     *
     * @param int $days Number of days to analyze
     * @return array Enhancement statistics
     */
    public function get_enhancement_stats($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_query_enhancements';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_results($wpdb->prepare("
            SELECT 
                detected_intent,
                COUNT(*) as count,
                AVG(JSON_EXTRACT(enhancement_data, '$.confidence')) as avg_confidence,
                AVG(JSON_EXTRACT(enhancement_data, '$.processing_time')) as avg_processing_time
            FROM {$table_name} 
            WHERE created_at >= %s 
            GROUP BY detected_intent
        ", $date_limit), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Clear enhancement cache
     */
    public function clear_cache() {
        if ($this->cache_manager) {
            $this->cache_manager->delete_group('spb_query_enhancement');
        }
    }
}
