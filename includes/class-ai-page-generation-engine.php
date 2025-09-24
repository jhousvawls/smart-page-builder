<?php
/**
 * AI Page Generation Engine
 *
 * Orchestrates multi-provider AI content generation for search-triggered pages.
 * Integrates with existing Smart Page Builder v3.0 AI provider system and
 * personalization engine to create dynamic, user-tailored content.
 *
 * @package Smart_Page_Builder
 * @subpackage Search_Integration
 * @since 3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Page Generation Engine Class
 *
 * Coordinates AI content generation across multiple providers and component types.
 * Integrates with existing personalization engine and interest vector system.
 */
class SPB_AI_Page_Generation_Engine {

    /**
     * AI Provider Manager instance
     *
     * @var SPB_AI_Provider_Manager
     */
    private $ai_provider_manager;

    /**
     * Interest Vector Calculator instance
     *
     * @var SPB_Interest_Vector_Calculator
     */
    private $interest_calculator;

    /**
     * Component Personalizer instance
     *
     * @var SPB_Component_Personalizer
     */
    private $component_personalizer;

    /**
     * Cache Manager instance
     *
     * @var SPB_Cache_Manager
     */
    private $cache_manager;

    /**
     * Search Database Manager instance
     *
     * @var SPB_Search_Database_Manager
     */
    private $database_manager;

    /**
     * Component generators registry
     *
     * @var array
     */
    private $component_generators = [];

    /**
     * Generation statistics
     *
     * @var array
     */
    private $generation_stats = [];

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_dependencies();
        $this->register_component_generators();
        $this->init_hooks();
    }

    /**
     * Initialize dependencies
     */
    private function init_dependencies() {
        // Initialize existing Smart Page Builder components
        $this->ai_provider_manager = new SPB_AI_Provider_Manager();
        $this->interest_calculator = new SPB_Interest_Vector_Calculator();
        $this->component_personalizer = new SPB_Component_Personalizer();
        $this->cache_manager = new SPB_Cache_Manager();
        $this->database_manager = new SPB_Search_Database_Manager();
    }

    /**
     * Register component generators
     */
    private function register_component_generators() {
        // Load component generators
        require_once plugin_dir_path(__FILE__) . 'component-generators/abstract-component-generator.php';
        require_once plugin_dir_path(__FILE__) . 'component-generators/class-hero-generator.php';
        require_once plugin_dir_path(__FILE__) . 'component-generators/class-article-generator.php';
        require_once plugin_dir_path(__FILE__) . 'component-generators/class-cta-generator.php';

        // Register generators
        $this->component_generators = [
            'hero' => new SPB_Hero_Generator($this->ai_provider_manager),
            'article' => new SPB_Article_Generator($this->ai_provider_manager),
            'cta' => new SPB_CTA_Generator($this->ai_provider_manager)
        ];
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_spb_generate_page_content', [$this, 'ajax_generate_page_content']);
        add_action('wp_ajax_nopriv_spb_generate_page_content', [$this, 'ajax_generate_page_content']);
        add_filter('spb_personalization_context', [$this, 'add_search_context'], 10, 2);
    }

    /**
     * Generate complete page content for search query
     *
     * @param string $search_query The search query
     * @param array $discovery_results Content discovery results from WP Engine
     * @param string $user_session_id User session identifier
     * @param array $user_context Additional user context
     * @return array Generated page content and metadata
     */
    public function generate_page_content($search_query, $discovery_results, $user_session_id, $user_context = []) {
        $start_time = microtime(true);
        
        try {
            // Get user interest vector for personalization
            $interest_vector = $this->get_user_interest_vector($user_session_id, $search_query);
            
            // Determine page intent and structure
            $page_intent = $this->analyze_page_intent($search_query, $discovery_results);
            
            // Generate personalization context
            $personalization_context = $this->build_personalization_context(
                $search_query,
                $discovery_results,
                $interest_vector,
                $user_context,
                $page_intent
            );

            // Generate components based on intent
            $components = $this->generate_page_components(
                $page_intent,
                $personalization_context,
                $discovery_results
            );

            // Assemble final page structure
            $page_content = $this->assemble_page_content($components, $page_intent);

            // Calculate quality metrics
            $quality_metrics = $this->assess_content_quality($page_content, $personalization_context);

            // Track generation statistics
            $generation_time = microtime(true) - $start_time;
            $this->track_generation_stats($search_query, $generation_time, $quality_metrics);

            return [
                'success' => true,
                'content' => $page_content,
                'components' => $components,
                'quality_metrics' => $quality_metrics,
                'personalization_context' => $personalization_context,
                'generation_time' => $generation_time,
                'intent' => $page_intent
            ];

        } catch (Exception $e) {
            error_log('SPB AI Page Generation Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_content' => $this->generate_fallback_content($search_query, $discovery_results)
            ];
        }
    }

    /**
     * Get user interest vector for personalization
     *
     * @param string $user_session_id User session ID
     * @param string $search_query Current search query
     * @return array Interest vector data
     */
    private function get_user_interest_vector($user_session_id, $search_query) {
        // Check cache first
        $cache_key = "spb_interest_vector_{$user_session_id}";
        $cached_vector = $this->cache_manager->get($cache_key);
        
        if ($cached_vector !== false) {
            return $cached_vector;
        }

        // Calculate interest vector using existing system
        $interest_vector = $this->interest_calculator->calculate_interest_vector($user_session_id);
        
        // Update vector with current search context
        $search_interests = $this->extract_search_interests($search_query);
        $interest_vector = $this->merge_search_interests($interest_vector, $search_interests);

        // Cache for 5 minutes
        $this->cache_manager->set($cache_key, $interest_vector, 300);

        return $interest_vector;
    }

    /**
     * Analyze page intent from search query and discovery results
     *
     * @param string $search_query The search query
     * @param array $discovery_results Content discovery results
     * @return array Page intent analysis
     */
    private function analyze_page_intent($search_query, $discovery_results) {
        // Use existing query enhancement engine for intent detection
        $query_engine = new SPB_Query_Enhancement_Engine();
        $enhancement_data = $query_engine->enhance_query($search_query);

        $intent = [
            'primary_intent' => $enhancement_data['detected_intent'] ?? 'informational',
            'confidence' => $enhancement_data['confidence_score'] ?? 0.5,
            'keywords' => $enhancement_data['context_keywords'] ?? [],
            'suggested_components' => $this->suggest_components_for_intent($enhancement_data['detected_intent'] ?? 'informational'),
            'content_depth' => $this->determine_content_depth($discovery_results),
            'personalization_level' => $this->determine_personalization_level($discovery_results)
        ];

        return $intent;
    }

    /**
     * Suggest components based on detected intent
     *
     * @param string $intent Detected intent type
     * @return array Suggested component types
     */
    private function suggest_components_for_intent($intent) {
        $component_map = [
            'informational' => ['hero', 'article', 'related_content'],
            'commercial' => ['hero', 'product_showcase', 'cta', 'testimonials'],
            'navigational' => ['hero', 'navigation_guide', 'quick_links'],
            'educational' => ['hero', 'tutorial', 'resources', 'cta']
        ];

        return $component_map[$intent] ?? ['hero', 'article', 'cta'];
    }

    /**
     * Build personalization context for content generation
     *
     * @param string $search_query Search query
     * @param array $discovery_results Discovery results
     * @param array $interest_vector User interest vector
     * @param array $user_context User context
     * @param array $page_intent Page intent analysis
     * @return array Personalization context
     */
    private function build_personalization_context($search_query, $discovery_results, $interest_vector, $user_context, $page_intent) {
        return [
            'search_query' => $search_query,
            'user_interests' => $interest_vector,
            'content_preferences' => $this->extract_content_preferences($interest_vector),
            'tone_preference' => $this->determine_tone_preference($interest_vector, $page_intent),
            'complexity_level' => $this->determine_complexity_level($user_context, $interest_vector),
            'available_content' => $this->process_discovery_results($discovery_results),
            'personalization_signals' => $user_context,
            'intent_context' => $page_intent,
            'timestamp' => current_time('mysql')
        ];
    }

    /**
     * Generate page components based on intent and context
     *
     * @param array $page_intent Page intent analysis
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Content discovery results
     * @return array Generated components
     */
    private function generate_page_components($page_intent, $personalization_context, $discovery_results) {
        $components = [];
        $suggested_components = $page_intent['suggested_components'];

        foreach ($suggested_components as $component_type) {
            if (isset($this->component_generators[$component_type])) {
                $generator = $this->component_generators[$component_type];
                
                try {
                    $component_data = $generator->generate_component(
                        $personalization_context,
                        $discovery_results
                    );

                    if ($component_data && $component_data['success']) {
                        $components[$component_type] = [
                            'type' => $component_type,
                            'content' => $component_data['content'],
                            'metadata' => $component_data['metadata'],
                            'confidence' => $component_data['confidence'] ?? 0.7,
                            'generation_time' => $component_data['generation_time'] ?? 0,
                            'ai_provider' => $component_data['ai_provider'] ?? 'unknown'
                        ];
                    }
                } catch (Exception $e) {
                    error_log("Component generation error for {$component_type}: " . $e->getMessage());
                    
                    // Generate fallback component
                    $components[$component_type] = $this->generate_fallback_component(
                        $component_type,
                        $personalization_context
                    );
                }
            }
        }

        return $components;
    }

    /**
     * Assemble final page content from components
     *
     * @param array $components Generated components
     * @param array $page_intent Page intent analysis
     * @return array Assembled page content
     */
    private function assemble_page_content($components, $page_intent) {
        // Select appropriate template based on intent
        $template_type = $this->select_template_type($page_intent, $components);
        
        // Load template system
        $template_engine = new SPB_Template_Engine();
        
        // Assemble page structure
        $page_structure = [
            'template' => $template_type,
            'sections' => $this->organize_components_into_sections($components, $page_intent),
            'metadata' => [
                'title' => $this->generate_page_title($components, $page_intent),
                'description' => $this->generate_page_description($components, $page_intent),
                'keywords' => $page_intent['keywords'],
                'intent' => $page_intent['primary_intent']
            ],
            'styling' => $this->determine_page_styling($page_intent, $components),
            'scripts' => $this->determine_required_scripts($components)
        ];

        return $page_structure;
    }

    /**
     * Assess content quality and generate confidence scores
     *
     * @param array $page_content Generated page content
     * @param array $personalization_context Personalization context
     * @return array Quality metrics
     */
    private function assess_content_quality($page_content, $personalization_context) {
        $quality_metrics = [
            'overall_confidence' => 0.0,
            'content_relevance' => 0.0,
            'personalization_score' => 0.0,
            'completeness_score' => 0.0,
            'readability_score' => 0.0,
            'component_scores' => []
        ];

        // Assess individual components
        if (isset($page_content['sections'])) {
            $component_scores = [];
            $total_confidence = 0;
            $component_count = 0;

            foreach ($page_content['sections'] as $section_name => $section_data) {
                if (isset($section_data['components'])) {
                    foreach ($section_data['components'] as $component) {
                        $score = $component['confidence'] ?? 0.5;
                        $component_scores[$component['type']] = $score;
                        $total_confidence += $score;
                        $component_count++;
                    }
                }
            }

            $quality_metrics['component_scores'] = $component_scores;
            $quality_metrics['overall_confidence'] = $component_count > 0 ? $total_confidence / $component_count : 0.5;
        }

        // Assess content relevance to search query
        $quality_metrics['content_relevance'] = $this->calculate_content_relevance(
            $page_content,
            $personalization_context['search_query']
        );

        // Assess personalization effectiveness
        $quality_metrics['personalization_score'] = $this->calculate_personalization_score(
            $page_content,
            $personalization_context['user_interests']
        );

        // Assess content completeness
        $quality_metrics['completeness_score'] = $this->calculate_completeness_score($page_content);

        // Calculate final overall confidence
        $quality_metrics['overall_confidence'] = (
            $quality_metrics['overall_confidence'] * 0.4 +
            $quality_metrics['content_relevance'] * 0.3 +
            $quality_metrics['personalization_score'] * 0.2 +
            $quality_metrics['completeness_score'] * 0.1
        );

        return $quality_metrics;
    }

    /**
     * Generate fallback content when AI generation fails
     *
     * @param string $search_query Search query
     * @param array $discovery_results Discovery results
     * @return array Fallback content structure
     */
    private function generate_fallback_content($search_query, $discovery_results) {
        return [
            'template' => 'basic',
            'sections' => [
                'header' => [
                    'components' => [
                        [
                            'type' => 'hero',
                            'content' => [
                                'title' => 'Search Results for: ' . esc_html($search_query),
                                'description' => 'We found some relevant content for your search.',
                                'image' => null
                            ],
                            'confidence' => 0.3
                        ]
                    ]
                ],
                'main' => [
                    'components' => [
                        [
                            'type' => 'content_list',
                            'content' => $this->format_discovery_results_as_list($discovery_results),
                            'confidence' => 0.4
                        ]
                    ]
                ]
            ],
            'metadata' => [
                'title' => $search_query . ' - Search Results',
                'description' => 'Search results for ' . $search_query,
                'keywords' => [$search_query]
            ]
        ];
    }

    /**
     * AJAX handler for page content generation
     */
    public function ajax_generate_page_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_generate_content')) {
            wp_die('Security check failed');
        }

        $search_query = sanitize_text_field($_POST['search_query'] ?? '');
        $user_session_id = sanitize_text_field($_POST['session_id'] ?? '');
        $discovery_results = json_decode(stripslashes($_POST['discovery_results'] ?? '[]'), true);
        $user_context = json_decode(stripslashes($_POST['user_context'] ?? '[]'), true);

        if (empty($search_query)) {
            wp_send_json_error('Search query is required');
            return;
        }

        $result = $this->generate_page_content(
            $search_query,
            $discovery_results,
            $user_session_id,
            $user_context
        );

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Add search context to personalization
     *
     * @param array $context Existing context
     * @param string $session_id Session ID
     * @return array Enhanced context
     */
    public function add_search_context($context, $session_id) {
        // Add search-specific personalization context
        $search_history = $this->get_user_search_history($session_id);
        
        if (!empty($search_history)) {
            $context['search_patterns'] = $this->analyze_search_patterns($search_history);
            $context['search_interests'] = $this->extract_interests_from_searches($search_history);
        }

        return $context;
    }

    /**
     * Track generation statistics
     *
     * @param string $search_query Search query
     * @param float $generation_time Generation time in seconds
     * @param array $quality_metrics Quality metrics
     */
    private function track_generation_stats($search_query, $generation_time, $quality_metrics) {
        $this->generation_stats[] = [
            'search_query' => $search_query,
            'generation_time' => $generation_time,
            'confidence_score' => $quality_metrics['overall_confidence'],
            'timestamp' => current_time('mysql')
        ];

        // Store in database for analytics
        $this->database_manager->store_generation_stats([
            'search_query' => $search_query,
            'generation_time' => $generation_time,
            'quality_metrics' => json_encode($quality_metrics),
            'created_at' => current_time('mysql')
        ]);
    }

    /**
     * Helper methods for content analysis and processing
     */

    private function extract_search_interests($search_query) {
        // Extract interests from search query using NLP techniques
        $keywords = explode(' ', strtolower($search_query));
        $interests = [];
        
        // Simple keyword-to-interest mapping (can be enhanced with ML)
        $interest_map = [
            'photography' => ['photo', 'camera', 'lens', 'portrait'],
            'web_design' => ['design', 'website', 'ui', 'ux', 'css'],
            'travel' => ['travel', 'vacation', 'trip', 'destination'],
            'technology' => ['tech', 'software', 'app', 'digital']
        ];

        foreach ($interest_map as $interest => $terms) {
            $score = 0;
            foreach ($terms as $term) {
                if (in_array($term, $keywords)) {
                    $score += 0.2;
                }
            }
            if ($score > 0) {
                $interests[$interest] = min($score, 1.0);
            }
        }

        return $interests;
    }

    private function merge_search_interests($existing_vector, $search_interests) {
        foreach ($search_interests as $interest => $score) {
            if (isset($existing_vector[$interest])) {
                // Boost existing interest
                $existing_vector[$interest] = min(1.0, $existing_vector[$interest] + ($score * 0.3));
            } else {
                // Add new interest with reduced weight
                $existing_vector[$interest] = $score * 0.5;
            }
        }

        return $existing_vector;
    }

    private function determine_content_depth($discovery_results) {
        $content_count = count($discovery_results);
        
        if ($content_count > 10) {
            return 'comprehensive';
        } elseif ($content_count > 5) {
            return 'detailed';
        } else {
            return 'basic';
        }
    }

    private function determine_personalization_level($discovery_results) {
        // Determine how much personalization to apply based on available content
        $content_variety = $this->calculate_content_variety($discovery_results);
        
        if ($content_variety > 0.7) {
            return 'high';
        } elseif ($content_variety > 0.4) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    private function calculate_content_variety($discovery_results) {
        if (empty($discovery_results)) {
            return 0;
        }

        $categories = [];
        foreach ($discovery_results as $result) {
            if (isset($result['category'])) {
                $categories[] = $result['category'];
            }
        }

        $unique_categories = array_unique($categories);
        return count($unique_categories) / max(count($categories), 1);
    }

    /**
     * Get generation statistics
     *
     * @return array Generation statistics
     */
    public function get_generation_stats() {
        return $this->generation_stats;
    }

    /**
     * Clear generation statistics
     */
    public function clear_generation_stats() {
        $this->generation_stats = [];
    }
}
