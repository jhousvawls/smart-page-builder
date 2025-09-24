<?php
/**
 * Abstract Component Generator
 *
 * Base class for all AI-powered component generators in the Smart Page Builder
 * search-triggered page generation system. Provides common functionality and
 * interface for generating personalized content components.
 *
 * @package Smart_Page_Builder
 * @subpackage Component_Generators
 * @since 3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Component Generator Class
 *
 * Defines the interface and common functionality for all component generators.
 * Each specific generator (Hero, Article, CTA) extends this base class.
 */
abstract class SPB_Abstract_Component_Generator {

    /**
     * AI Provider Manager instance
     *
     * @var SPB_AI_Provider_Manager
     */
    protected $ai_provider_manager;

    /**
     * Cache Manager instance
     *
     * @var SPB_Cache_Manager
     */
    protected $cache_manager;

    /**
     * Component type identifier
     *
     * @var string
     */
    protected $component_type;

    /**
     * Default AI provider for this component
     *
     * @var string
     */
    protected $default_provider = 'openai';

    /**
     * Generation statistics
     *
     * @var array
     */
    protected $generation_stats = [];

    /**
     * Constructor
     *
     * @param SPB_AI_Provider_Manager $ai_provider_manager AI provider manager instance
     */
    public function __construct($ai_provider_manager) {
        $this->ai_provider_manager = $ai_provider_manager;
        $this->cache_manager = new SPB_Cache_Manager();
        $this->component_type = $this->get_component_type();
    }

    /**
     * Generate component content
     *
     * Main method that orchestrates the component generation process.
     * This method is called by the AI Page Generation Engine.
     *
     * @param array $personalization_context Personalization context data
     * @param array $discovery_results Content discovery results
     * @return array Generated component data
     */
    public function generate_component($personalization_context, $discovery_results) {
        $start_time = microtime(true);
        
        try {
            // Check cache first
            $cache_key = $this->build_cache_key($personalization_context, $discovery_results);
            $cached_component = $this->cache_manager->get($cache_key);
            
            if ($cached_component !== false && $this->is_cache_valid($cached_component)) {
                $cached_component['from_cache'] = true;
                return $cached_component;
            }

            // Validate input data
            $validation_result = $this->validate_input($personalization_context, $discovery_results);
            if (!$validation_result['valid']) {
                throw new Exception('Invalid input data: ' . $validation_result['error']);
            }

            // Prepare generation context
            $generation_context = $this->prepare_generation_context($personalization_context, $discovery_results);

            // Select best AI provider for this generation
            $selected_provider = $this->select_ai_provider($generation_context);

            // Generate component content using AI
            $ai_response = $this->generate_with_ai($generation_context, $selected_provider);

            // Process and validate AI response
            $processed_content = $this->process_ai_response($ai_response, $generation_context);

            // Apply personalization enhancements
            $personalized_content = $this->apply_personalization($processed_content, $personalization_context);

            // Calculate quality metrics
            $quality_metrics = $this->assess_component_quality($personalized_content, $generation_context);

            // Build final component data
            $component_data = [
                'success' => true,
                'content' => $personalized_content,
                'metadata' => [
                    'component_type' => $this->component_type,
                    'ai_provider' => $selected_provider,
                    'generation_time' => microtime(true) - $start_time,
                    'quality_metrics' => $quality_metrics,
                    'personalization_applied' => true,
                    'cache_key' => $cache_key,
                    'generated_at' => current_time('mysql')
                ],
                'confidence' => $quality_metrics['overall_confidence'] ?? 0.7,
                'generation_time' => microtime(true) - $start_time,
                'ai_provider' => $selected_provider
            ];

            // Cache the result
            $this->cache_component($cache_key, $component_data);

            // Track generation statistics
            $this->track_generation_stats($component_data, $generation_context);

            return $component_data;

        } catch (Exception $e) {
            error_log("Component generation error ({$this->component_type}): " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_content' => $this->generate_fallback_content($personalization_context, $discovery_results),
                'metadata' => [
                    'component_type' => $this->component_type,
                    'generation_time' => microtime(true) - $start_time,
                    'error_occurred' => true
                ]
            ];
        }
    }

    /**
     * Get component type identifier
     *
     * Must be implemented by each concrete generator class.
     *
     * @return string Component type identifier
     */
    abstract protected function get_component_type();

    /**
     * Prepare generation context for AI
     *
     * Must be implemented by each concrete generator class.
     * Transforms personalization context into AI-specific prompts and parameters.
     *
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Discovery results
     * @return array Generation context for AI
     */
    abstract protected function prepare_generation_context($personalization_context, $discovery_results);

    /**
     * Process AI response into component content
     *
     * Must be implemented by each concrete generator class.
     * Converts raw AI response into structured component content.
     *
     * @param array $ai_response Raw AI response
     * @param array $generation_context Generation context
     * @return array Processed component content
     */
    abstract protected function process_ai_response($ai_response, $generation_context);

    /**
     * Generate fallback content when AI generation fails
     *
     * Must be implemented by each concrete generator class.
     *
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Discovery results
     * @return array Fallback component content
     */
    abstract protected function generate_fallback_content($personalization_context, $discovery_results);

    /**
     * Validate input data
     *
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Discovery results
     * @return array Validation result
     */
    protected function validate_input($personalization_context, $discovery_results) {
        $errors = [];

        // Check required personalization context fields
        $required_fields = ['search_query', 'user_interests', 'intent_context'];
        foreach ($required_fields as $field) {
            if (!isset($personalization_context[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }

        // Validate search query
        if (isset($personalization_context['search_query'])) {
            $query = trim($personalization_context['search_query']);
            if (empty($query) || strlen($query) < 2) {
                $errors[] = "Search query too short or empty";
            }
        }

        // Validate discovery results
        if (!is_array($discovery_results)) {
            $errors[] = "Discovery results must be an array";
        }

        return [
            'valid' => empty($errors),
            'error' => implode(', ', $errors)
        ];
    }

    /**
     * Select best AI provider for generation
     *
     * @param array $generation_context Generation context
     * @return string Selected AI provider
     */
    protected function select_ai_provider($generation_context) {
        // Get available providers
        $available_providers = $this->ai_provider_manager->get_available_providers();
        
        if (empty($available_providers)) {
            return $this->default_provider;
        }

        // Component-specific provider preferences
        $provider_preferences = $this->get_provider_preferences($generation_context);
        
        // Select based on preferences and availability
        foreach ($provider_preferences as $preferred_provider) {
            if (in_array($preferred_provider, $available_providers)) {
                return $preferred_provider;
            }
        }

        // Fallback to first available provider
        return $available_providers[0];
    }

    /**
     * Get provider preferences for this component type
     *
     * Can be overridden by concrete generators for component-specific preferences.
     *
     * @param array $generation_context Generation context
     * @return array Ordered list of preferred providers
     */
    protected function get_provider_preferences($generation_context) {
        // Default preferences - can be overridden by specific generators
        return ['openai', 'anthropic', 'google'];
    }

    /**
     * Generate content using AI provider
     *
     * @param array $generation_context Generation context
     * @param string $provider_name AI provider name
     * @return array AI response
     */
    protected function generate_with_ai($generation_context, $provider_name) {
        $provider = $this->ai_provider_manager->get_provider($provider_name);
        
        if (!$provider) {
            throw new Exception("AI provider '{$provider_name}' not available");
        }

        // Build AI prompt
        $prompt = $this->build_ai_prompt($generation_context);
        
        // Set AI parameters
        $ai_parameters = $this->get_ai_parameters($generation_context);

        // Generate content
        $response = $provider->generate_content($prompt, $ai_parameters);

        if (!$response || !$response['success']) {
            throw new Exception("AI generation failed: " . ($response['error'] ?? 'Unknown error'));
        }

        return $response;
    }

    /**
     * Build AI prompt for content generation
     *
     * Can be overridden by concrete generators for component-specific prompts.
     *
     * @param array $generation_context Generation context
     * @return string AI prompt
     */
    protected function build_ai_prompt($generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $user_interests = $generation_context['user_interests'] ?? [];
        
        $prompt = "Generate a {$this->component_type} component for a search-triggered page.\n\n";
        $prompt .= "Search Query: {$search_query}\n";
        $prompt .= "Intent: {$intent}\n";
        
        if (!empty($user_interests)) {
            $interests_text = implode(', ', array_keys($user_interests));
            $prompt .= "User Interests: {$interests_text}\n";
        }
        
        $prompt .= "\nRequirements:\n";
        $prompt .= $this->get_component_requirements();
        
        return $prompt;
    }

    /**
     * Get component-specific requirements for AI prompt
     *
     * Should be overridden by concrete generators.
     *
     * @return string Component requirements
     */
    protected function get_component_requirements() {
        return "- Create engaging, relevant content\n- Match user intent and interests\n- Use clear, professional language";
    }

    /**
     * Get AI parameters for generation
     *
     * @param array $generation_context Generation context
     * @return array AI parameters
     */
    protected function get_ai_parameters($generation_context) {
        return [
            'max_tokens' => $this->get_max_tokens(),
            'temperature' => $this->get_temperature($generation_context),
            'top_p' => 0.9,
            'frequency_penalty' => 0.1,
            'presence_penalty' => 0.1
        ];
    }

    /**
     * Get maximum tokens for this component type
     *
     * Can be overridden by concrete generators.
     *
     * @return int Maximum tokens
     */
    protected function get_max_tokens() {
        return 500;
    }

    /**
     * Get temperature setting based on context
     *
     * @param array $generation_context Generation context
     * @return float Temperature value
     */
    protected function get_temperature($generation_context) {
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        
        // More creative for commercial/marketing content
        $temperature_map = [
            'commercial' => 0.8,
            'informational' => 0.6,
            'educational' => 0.5,
            'navigational' => 0.4
        ];

        return $temperature_map[$intent] ?? 0.6;
    }

    /**
     * Apply personalization to generated content
     *
     * @param array $content Generated content
     * @param array $personalization_context Personalization context
     * @return array Personalized content
     */
    protected function apply_personalization($content, $personalization_context) {
        // Apply user interest-based modifications
        $content = $this->apply_interest_personalization($content, $personalization_context['user_interests'] ?? []);
        
        // Apply tone personalization
        $content = $this->apply_tone_personalization($content, $personalization_context);
        
        // Apply complexity level adjustments
        $content = $this->apply_complexity_personalization($content, $personalization_context);

        return $content;
    }

    /**
     * Apply interest-based personalization
     *
     * @param array $content Content to personalize
     * @param array $user_interests User interests
     * @return array Personalized content
     */
    protected function apply_interest_personalization($content, $user_interests) {
        // Default implementation - can be overridden by specific generators
        return $content;
    }

    /**
     * Apply tone personalization
     *
     * @param array $content Content to personalize
     * @param array $personalization_context Personalization context
     * @return array Personalized content
     */
    protected function apply_tone_personalization($content, $personalization_context) {
        // Default implementation - can be overridden by specific generators
        return $content;
    }

    /**
     * Apply complexity level personalization
     *
     * @param array $content Content to personalize
     * @param array $personalization_context Personalization context
     * @return array Personalized content
     */
    protected function apply_complexity_personalization($content, $personalization_context) {
        // Default implementation - can be overridden by specific generators
        return $content;
    }

    /**
     * Assess component quality
     *
     * @param array $content Generated content
     * @param array $generation_context Generation context
     * @return array Quality metrics
     */
    protected function assess_component_quality($content, $generation_context) {
        $quality_metrics = [
            'overall_confidence' => 0.7,
            'content_relevance' => 0.0,
            'personalization_score' => 0.0,
            'completeness_score' => 0.0,
            'readability_score' => 0.0
        ];

        // Assess content relevance
        $quality_metrics['content_relevance'] = $this->calculate_content_relevance($content, $generation_context);
        
        // Assess personalization effectiveness
        $quality_metrics['personalization_score'] = $this->calculate_personalization_effectiveness($content, $generation_context);
        
        // Assess content completeness
        $quality_metrics['completeness_score'] = $this->calculate_content_completeness($content);
        
        // Calculate overall confidence
        $quality_metrics['overall_confidence'] = (
            $quality_metrics['content_relevance'] * 0.4 +
            $quality_metrics['personalization_score'] * 0.3 +
            $quality_metrics['completeness_score'] * 0.3
        );

        return $quality_metrics;
    }

    /**
     * Calculate content relevance score
     *
     * @param array $content Generated content
     * @param array $generation_context Generation context
     * @return float Relevance score (0-1)
     */
    protected function calculate_content_relevance($content, $generation_context) {
        $search_query = strtolower($generation_context['search_query'] ?? '');
        $content_text = strtolower($this->extract_text_from_content($content));
        
        if (empty($search_query) || empty($content_text)) {
            return 0.5;
        }

        // Simple keyword matching - can be enhanced with semantic analysis
        $query_words = explode(' ', $search_query);
        $matches = 0;
        
        foreach ($query_words as $word) {
            if (strlen($word) > 2 && strpos($content_text, $word) !== false) {
                $matches++;
            }
        }

        return min(1.0, $matches / max(count($query_words), 1));
    }

    /**
     * Calculate personalization effectiveness
     *
     * @param array $content Generated content
     * @param array $generation_context Generation context
     * @return float Personalization score (0-1)
     */
    protected function calculate_personalization_effectiveness($content, $generation_context) {
        // Default implementation - can be enhanced by specific generators
        return 0.7;
    }

    /**
     * Calculate content completeness
     *
     * @param array $content Generated content
     * @return float Completeness score (0-1)
     */
    protected function calculate_content_completeness($content) {
        // Check if required fields are present and non-empty
        $required_fields = $this->get_required_content_fields();
        $present_fields = 0;
        
        foreach ($required_fields as $field) {
            if (isset($content[$field]) && !empty($content[$field])) {
                $present_fields++;
            }
        }

        return $present_fields / max(count($required_fields), 1);
    }

    /**
     * Get required content fields for this component type
     *
     * Should be overridden by concrete generators.
     *
     * @return array Required field names
     */
    protected function get_required_content_fields() {
        return ['title', 'content'];
    }

    /**
     * Extract text content for analysis
     *
     * @param array $content Content array
     * @return string Extracted text
     */
    protected function extract_text_from_content($content) {
        $text_parts = [];
        
        foreach ($content as $key => $value) {
            if (is_string($value)) {
                $text_parts[] = strip_tags($value);
            } elseif (is_array($value)) {
                $text_parts[] = $this->extract_text_from_content($value);
            }
        }

        return implode(' ', $text_parts);
    }

    /**
     * Build cache key for component
     *
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Discovery results
     * @return string Cache key
     */
    protected function build_cache_key($personalization_context, $discovery_results) {
        $key_data = [
            'component_type' => $this->component_type,
            'search_query' => $personalization_context['search_query'] ?? '',
            'intent' => $personalization_context['intent_context']['primary_intent'] ?? '',
            'interests_hash' => md5(serialize($personalization_context['user_interests'] ?? [])),
            'content_hash' => md5(serialize(array_slice($discovery_results, 0, 5))) // First 5 results
        ];

        return 'spb_component_' . md5(serialize($key_data));
    }

    /**
     * Check if cached component is still valid
     *
     * @param array $cached_component Cached component data
     * @return bool True if cache is valid
     */
    protected function is_cache_valid($cached_component) {
        // Check cache age (default: 1 hour)
        $cache_duration = apply_filters('spb_component_cache_duration', 3600);
        $generated_at = strtotime($cached_component['metadata']['generated_at'] ?? '');
        
        return (time() - $generated_at) < $cache_duration;
    }

    /**
     * Cache component data
     *
     * @param string $cache_key Cache key
     * @param array $component_data Component data
     */
    protected function cache_component($cache_key, $component_data) {
        $cache_duration = apply_filters('spb_component_cache_duration', 3600);
        $this->cache_manager->set($cache_key, $component_data, $cache_duration);
    }

    /**
     * Track generation statistics
     *
     * @param array $component_data Generated component data
     * @param array $generation_context Generation context
     */
    protected function track_generation_stats($component_data, $generation_context) {
        $this->generation_stats[] = [
            'component_type' => $this->component_type,
            'generation_time' => $component_data['generation_time'],
            'confidence_score' => $component_data['confidence'],
            'ai_provider' => $component_data['ai_provider'],
            'search_query' => $generation_context['search_query'] ?? '',
            'timestamp' => current_time('mysql')
        ];
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
