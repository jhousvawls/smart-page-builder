<?php
/**
 * Abstract AI Provider Base Class
 *
 * Defines the interface and common functionality for all AI providers.
 * All provider implementations must extend this class.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes/ai-providers
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract AI Provider Class
 *
 * Base class for all AI provider implementations. Defines the required
 * methods and provides common functionality for API communication,
 * error handling, and response processing.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes/ai-providers
 */
abstract class Smart_Page_Builder_Abstract_Provider {

    /**
     * Provider configuration
     *
     * @since    2.0.0
     * @access   protected
     * @var      array    $config
     */
    protected $config;

    /**
     * Provider name
     *
     * @since    2.0.0
     * @access   protected
     * @var      string   $provider_name
     */
    protected $provider_name;

    /**
     * API endpoint URL
     *
     * @since    2.0.0
     * @access   protected
     * @var      string   $api_endpoint
     */
    protected $api_endpoint;

    /**
     * Default model to use
     *
     * @since    2.0.0
     * @access   protected
     * @var      string   $default_model
     */
    protected $default_model;

    /**
     * Request timeout in seconds
     *
     * @since    2.0.0
     * @access   protected
     * @var      int      $timeout
     */
    protected $timeout = 30;

    /**
     * Maximum retry attempts
     *
     * @since    2.0.0
     * @access   protected
     * @var      int      $max_retries
     */
    protected $max_retries = 3;

    /**
     * Content type optimization settings
     *
     * @since    2.0.0
     * @access   protected
     * @var      array    $content_type_settings
     */
    protected $content_type_settings = array();

    /**
     * Initialize the provider
     *
     * @since    2.0.0
     * @param    array    $config    Provider configuration
     */
    public function __construct($config = array()) {
        $this->config = $config;
        $this->init_provider();
        $this->setup_content_type_optimization();
    }

    /**
     * Initialize provider-specific settings
     *
     * @since    2.0.0
     * @access   protected
     */
    abstract protected function init_provider();

    /**
     * Generate content using the AI provider
     *
     * @since    2.0.0
     * @param    string   $prompt     Content prompt
     * @param    array    $options    Generation options
     * @return   array|WP_Error    Generation result or error
     */
    abstract public function generate_content($prompt, $options = array());

    /**
     * Optimize existing content
     *
     * @since    2.0.0
     * @param    string   $content    Content to optimize
     * @param    array    $options    Optimization options
     * @return   array|WP_Error    Optimization result or error
     */
    abstract public function optimize_content($content, $options = array());

    /**
     * Analyze content quality
     *
     * @since    2.0.0
     * @param    string   $content    Content to analyze
     * @return   array|WP_Error    Quality analysis result or error
     */
    abstract public function analyze_quality($content);

    /**
     * Test provider connection
     *
     * @since    2.0.0
     * @return   array    Test result with success status and message
     */
    abstract public function test_connection();

    /**
     * Get current model being used
     *
     * @since    2.0.0
     * @return   string   Model name
     */
    public function get_model() {
        return isset($this->config['model']) ? $this->config['model'] : $this->default_model;
    }

    /**
     * Get provider name
     *
     * @since    2.0.0
     * @return   string   Provider name
     */
    public function get_provider_name() {
        return $this->provider_name;
    }

    /**
     * Make HTTP request to API
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $endpoint    API endpoint
     * @param    array    $data        Request data
     * @param    array    $headers     Request headers
     * @param    string   $method      HTTP method
     * @return   array|WP_Error    Response data or error
     */
    protected function make_request($endpoint, $data = array(), $headers = array(), $method = 'POST') {
        $attempt = 0;
        $last_error = null;

        while ($attempt < $this->max_retries) {
            $attempt++;

            $args = array(
                'method' => $method,
                'timeout' => $this->timeout,
                'headers' => $headers,
                'body' => $method === 'POST' ? wp_json_encode($data) : null,
                'user-agent' => 'Smart Page Builder/' . SPB_VERSION
            );

            $response = wp_remote_request($endpoint, $args);

            if (is_wp_error($response)) {
                $last_error = $response;
                $this->log_error('HTTP request failed', array(
                    'attempt' => $attempt,
                    'error' => $response->get_error_message(),
                    'endpoint' => $endpoint
                ));
                
                // Wait before retry (exponential backoff)
                if ($attempt < $this->max_retries) {
                    sleep(pow(2, $attempt - 1));
                }
                continue;
            }

            $status_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);

            // Handle rate limiting
            if ($status_code === 429) {
                $retry_after = wp_remote_retrieve_header($response, 'retry-after');
                $wait_time = $retry_after ? intval($retry_after) : pow(2, $attempt);
                
                $this->log_error('Rate limit exceeded', array(
                    'attempt' => $attempt,
                    'wait_time' => $wait_time,
                    'endpoint' => $endpoint
                ));
                
                if ($attempt < $this->max_retries) {
                    sleep($wait_time);
                }
                continue;
            }

            // Handle other HTTP errors
            if ($status_code >= 400) {
                $error_data = json_decode($body, true);
                $error_message = isset($error_data['error']['message']) 
                    ? $error_data['error']['message'] 
                    : 'HTTP ' . $status_code . ' error';
                
                $last_error = new WP_Error('api_error', $error_message, array(
                    'status_code' => $status_code,
                    'response_body' => $body
                ));
                
                $this->log_error('API error response', array(
                    'attempt' => $attempt,
                    'status_code' => $status_code,
                    'error' => $error_message,
                    'endpoint' => $endpoint
                ));
                
                // Don't retry on authentication errors
                if ($status_code === 401 || $status_code === 403) {
                    break;
                }
                
                if ($attempt < $this->max_retries) {
                    sleep(pow(2, $attempt - 1));
                }
                continue;
            }

            // Success - parse and return response
            $parsed_response = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $last_error = new WP_Error('json_parse_error', 'Failed to parse API response');
                continue;
            }

            return $parsed_response;
        }

        // All retries failed
        return $last_error ?: new WP_Error('max_retries_exceeded', 'Maximum retry attempts exceeded');
    }

    /**
     * Build content prompt based on content type
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $search_term     Search term
     * @param    string   $content_type    Content type
     * @param    array    $options         Additional options
     * @return   string   Optimized prompt
     */
    protected function build_content_prompt($search_term, $content_type, $options = array()) {
        $base_prompt = isset($options['custom_prompt']) ? $options['custom_prompt'] : '';
        
        if (empty($base_prompt)) {
            $base_prompt = $this->get_content_type_prompt($content_type, $search_term);
        }

        // Add content type specific instructions
        $instructions = $this->get_content_type_instructions($content_type);
        if ($instructions) {
            $base_prompt .= "\n\n" . $instructions;
        }

        // Add general guidelines
        $base_prompt .= "\n\nGeneral Guidelines:";
        $base_prompt .= "\n- Write in a clear, engaging, and informative style";
        $base_prompt .= "\n- Use proper headings and structure";
        $base_prompt .= "\n- Include practical examples where relevant";
        $base_prompt .= "\n- Ensure content is accurate and helpful";
        $base_prompt .= "\n- Target length: " . $this->get_target_length($content_type) . " words";

        return apply_filters('spb_ai_content_prompt', $base_prompt, $search_term, $content_type, $options);
    }

    /**
     * Get content type specific prompt template
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $content_type    Content type
     * @param    string   $search_term     Search term
     * @return   string   Prompt template
     */
    protected function get_content_type_prompt($content_type, $search_term) {
        $prompts = array(
            'how-to' => "Write a comprehensive how-to guide about '{$search_term}'. Include step-by-step instructions, required tools and materials, safety considerations, and helpful tips.",
            
            'tool-recommendation' => "Create a detailed tool recommendation guide for '{$search_term}'. Include the best tools available, their features, pros and cons, price ranges, and specific use cases.",
            
            'safety-tips' => "Write an informative safety guide about '{$search_term}'. Cover important safety precautions, potential hazards, protective equipment needed, and best practices.",
            
            'troubleshooting' => "Create a comprehensive troubleshooting guide for '{$search_term}'. Include common problems, their causes, step-by-step solutions, and prevention tips.",
            
            'default' => "Write an informative and helpful article about '{$search_term}'. Provide valuable insights, practical information, and actionable advice."
        );

        return isset($prompts[$content_type]) ? $prompts[$content_type] : $prompts['default'];
    }

    /**
     * Get content type specific instructions
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $content_type    Content type
     * @return   string   Additional instructions
     */
    protected function get_content_type_instructions($content_type) {
        $instructions = array(
            'how-to' => "Structure: Use numbered steps, include a materials list, add safety warnings where appropriate, and conclude with maintenance or follow-up tips.",
            
            'tool-recommendation' => "Structure: Start with an overview, categorize tools by type or price range, include comparison tables if helpful, and end with purchasing recommendations.",
            
            'safety-tips' => "Structure: Begin with why safety matters, list specific hazards, provide detailed safety measures, include emergency procedures, and emphasize prevention.",
            
            'troubleshooting' => "Structure: Start with symptom identification, provide diagnostic steps, offer multiple solution approaches, include when to seek professional help."
        );

        return isset($instructions[$content_type]) ? $instructions[$content_type] : '';
    }

    /**
     * Get target content length for content type
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $content_type    Content type
     * @return   int      Target word count
     */
    protected function get_target_length($content_type) {
        $lengths = array(
            'how-to' => 800,
            'tool-recommendation' => 600,
            'safety-tips' => 500,
            'troubleshooting' => 700,
            'default' => 600
        );

        return isset($lengths[$content_type]) ? $lengths[$content_type] : $lengths['default'];
    }

    /**
     * Setup content type optimization settings
     *
     * @since    2.0.0
     * @access   protected
     */
    protected function setup_content_type_optimization() {
        $this->content_type_settings = array(
            'how-to' => array(
                'temperature' => 0.7,
                'max_tokens' => 1200,
                'focus' => 'step-by-step clarity'
            ),
            'tool-recommendation' => array(
                'temperature' => 0.6,
                'max_tokens' => 1000,
                'focus' => 'detailed analysis'
            ),
            'safety-tips' => array(
                'temperature' => 0.5,
                'max_tokens' => 800,
                'focus' => 'accuracy and caution'
            ),
            'troubleshooting' => array(
                'temperature' => 0.6,
                'max_tokens' => 1100,
                'focus' => 'problem-solving logic'
            ),
            'default' => array(
                'temperature' => 0.7,
                'max_tokens' => 1000,
                'focus' => 'informative content'
            )
        );
    }

    /**
     * Get content type specific settings
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $content_type    Content type
     * @return   array    Content type settings
     */
    protected function get_content_type_settings($content_type) {
        return isset($this->content_type_settings[$content_type]) 
            ? $this->content_type_settings[$content_type] 
            : $this->content_type_settings['default'];
    }

    /**
     * Validate API response
     *
     * @since    2.0.0
     * @access   protected
     * @param    array    $response    API response
     * @return   bool     Whether response is valid
     */
    protected function validate_response($response) {
        return is_array($response) && !empty($response);
    }

    /**
     * Extract content from API response
     *
     * @since    2.0.0
     * @access   protected
     * @param    array    $response    API response
     * @return   string|false    Extracted content or false
     */
    abstract protected function extract_content_from_response($response);

    /**
     * Calculate content quality score
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $content    Generated content
     * @return   float    Quality score (0-100)
     */
    protected function calculate_quality_score($content) {
        $score = 0;
        $max_score = 100;

        // Length check (20 points)
        $word_count = str_word_count($content);
        if ($word_count >= 300) {
            $score += 20;
        } elseif ($word_count >= 150) {
            $score += 10;
        }

        // Structure check (30 points)
        if (preg_match('/<h[1-6]/', $content)) $score += 10; // Has headings
        if (preg_match('/<[uo]l>/', $content)) $score += 10; // Has lists
        if (preg_match('/<p>/', $content)) $score += 10; // Has paragraphs

        // Content quality indicators (30 points)
        if (preg_match('/\b(step|steps|first|second|third|finally)\b/i', $content)) $score += 10; // Sequential content
        if (preg_match('/\b(important|note|tip|warning|caution)\b/i', $content)) $score += 10; // Helpful indicators
        if (preg_match('/\b(example|for instance|such as)\b/i', $content)) $score += 10; // Examples

        // Readability check (20 points)
        $sentences = preg_split('/[.!?]+/', $content);
        $avg_sentence_length = $word_count / max(count($sentences), 1);
        if ($avg_sentence_length <= 20) $score += 20; // Good readability
        elseif ($avg_sentence_length <= 30) $score += 10; // Acceptable readability

        return min($score, $max_score);
    }

    /**
     * Log error for debugging
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $message    Error message
     * @param    array    $context    Additional context
     */
    protected function log_error($message, $context = array()) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[SPB AI Provider %s] %s: %s',
                $this->provider_name,
                $message,
                wp_json_encode($context)
            ));
        }

        // Track error for analytics
        do_action('spb_ai_provider_error', array(
            'provider' => $this->provider_name,
            'message' => $message,
            'context' => $context,
            'timestamp' => current_time('mysql')
        ));
    }

    /**
     * Get API key from configuration
     *
     * @since    2.0.0
     * @access   protected
     * @return   string|false    API key or false if not set
     */
    protected function get_api_key() {
        return isset($this->config['api_key']) ? $this->config['api_key'] : false;
    }

    /**
     * Check if provider is properly configured
     *
     * @since    2.0.0
     * @return   bool    Whether provider is configured
     */
    public function is_configured() {
        return !empty($this->get_api_key());
    }

    /**
     * Get provider capabilities
     *
     * @since    2.0.0
     * @return   array    Provider capabilities
     */
    public function get_capabilities() {
        return array(
            'content_generation' => true,
            'content_optimization' => true,
            'quality_analysis' => true,
            'connection_test' => true
        );
    }

    /**
     * Format response for consistent output
     *
     * @since    2.0.0
     * @access   protected
     * @param    string   $content         Generated content
     * @param    array    $metadata        Additional metadata
     * @return   array    Formatted response
     */
    protected function format_response($content, $metadata = array()) {
        return array(
            'content' => $content,
            'provider' => $this->provider_name,
            'model' => $this->get_model(),
            'quality_score' => $this->calculate_quality_score($content),
            'word_count' => str_word_count($content),
            'generated_at' => current_time('mysql'),
            'metadata' => $metadata
        );
    }
}
