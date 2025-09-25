<?php
/**
 * Abstract AI Provider Base Class
 *
 * @package Smart_Page_Builder
 * @since   3.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract AI Provider class
 */
abstract class SPB_Abstract_AI_Provider {
    
    /**
     * Provider name
     */
    protected $provider_name;
    
    /**
     * API key
     */
    protected $api_key;
    
    /**
     * API endpoint
     */
    protected $api_endpoint;
    
    /**
     * Default model
     */
    protected $default_model;
    
    /**
     * Available models
     */
    protected $available_models = [];
    
    /**
     * Request timeout
     */
    protected $timeout = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_configuration();
    }
    
    /**
     * Load provider configuration
     */
    abstract protected function load_configuration();
    
    /**
     * Generate content using AI
     *
     * @param string $prompt The prompt to send to AI
     * @param array $options Additional options
     * @return array Response with content and metadata
     */
    abstract public function generate_content($prompt, $options = []);
    
    /**
     * Test API connection
     *
     * @return bool Whether connection is successful
     */
    abstract public function test_connection();
    
    /**
     * Get available models
     *
     * @return array Available models
     */
    public function get_available_models() {
        return $this->available_models;
    }
    
    /**
     * Get provider name
     *
     * @return string Provider name
     */
    public function get_provider_name() {
        return $this->provider_name;
    }
    
    /**
     * Set API key
     *
     * @param string $api_key API key
     */
    public function set_api_key($api_key) {
        $this->api_key = $api_key;
    }
    
    /**
     * Check if provider is configured
     *
     * @return bool Whether provider is configured
     */
    public function is_configured() {
        return !empty($this->api_key);
    }
    
    /**
     * Make HTTP request to AI provider
     *
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param array $headers Request headers
     * @return array Response data
     */
    protected function make_request($endpoint, $data, $headers = []) {
        $default_headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Smart-Page-Builder/' . SPB_VERSION
        ];
        
        $headers = array_merge($default_headers, $headers);
        
        $response = wp_remote_post($endpoint, [
            'headers' => $headers,
            'body' => wp_json_encode($data),
            'timeout' => $this->timeout,
            'sslverify' => true
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception('API request failed: ' . $response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($status_code !== 200) {
            $error_data = json_decode($body, true);
            $error_message = isset($error_data['error']['message']) 
                ? $error_data['error']['message'] 
                : 'API request failed with status: ' . $status_code;
            throw new Exception($error_message);
        }
        
        $decoded_response = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response from API');
        }
        
        return $decoded_response;
    }
    
    /**
     * Log API usage for analytics
     *
     * @param string $operation Operation type
     * @param array $metadata Operation metadata
     */
    protected function log_usage($operation, $metadata = []) {
        if (class_exists('Smart_Page_Builder_Analytics_Manager')) {
            $analytics = Smart_Page_Builder_Analytics_Manager::get_instance();
            $analytics->track_event('ai_provider_usage', array_merge([
                'provider' => $this->provider_name,
                'operation' => $operation,
                'timestamp' => current_time('mysql')
            ], $metadata));
        }
    }
    
    /**
     * Sanitize and validate prompt
     *
     * @param string $prompt Raw prompt
     * @return string Sanitized prompt
     */
    protected function sanitize_prompt($prompt) {
        // Remove potentially harmful content
        $prompt = strip_tags($prompt);
        $prompt = trim($prompt);
        
        // Limit prompt length
        if (strlen($prompt) > 4000) {
            $prompt = substr($prompt, 0, 4000) . '...';
        }
        
        return $prompt;
    }
    
    /**
     * Parse AI response and extract content
     *
     * @param array $response Raw API response
     * @return array Parsed content with metadata
     */
    abstract protected function parse_response($response);
    
    /**
     * Get usage statistics
     *
     * @return array Usage statistics
     */
    public function get_usage_stats() {
        $option_key = 'spb_ai_usage_' . $this->provider_name;
        return get_option($option_key, [
            'total_requests' => 0,
            'total_tokens' => 0,
            'last_request' => null,
            'monthly_usage' => []
        ]);
    }
    
    /**
     * Update usage statistics
     *
     * @param array $usage_data Usage data to add
     */
    protected function update_usage_stats($usage_data) {
        $option_key = 'spb_ai_usage_' . $this->provider_name;
        $stats = $this->get_usage_stats();
        
        $stats['total_requests']++;
        $stats['total_tokens'] += $usage_data['tokens'] ?? 0;
        $stats['last_request'] = current_time('mysql');
        
        // Track monthly usage
        $current_month = date('Y-m');
        if (!isset($stats['monthly_usage'][$current_month])) {
            $stats['monthly_usage'][$current_month] = [
                'requests' => 0,
                'tokens' => 0,
                'model_usage' => []
            ];
        }
        
        $stats['monthly_usage'][$current_month]['requests']++;
        $stats['monthly_usage'][$current_month]['tokens'] += $usage_data['tokens'] ?? 0;
        
        // Track model-specific usage for cost calculation
        $model = $usage_data['model'] ?? $this->default_model;
        if (!isset($stats['monthly_usage'][$current_month]['model_usage'][$model])) {
            $stats['monthly_usage'][$current_month]['model_usage'][$model] = [
                'requests' => 0,
                'tokens' => 0
            ];
        }
        
        $stats['monthly_usage'][$current_month]['model_usage'][$model]['requests']++;
        $stats['monthly_usage'][$current_month]['model_usage'][$model]['tokens'] += $usage_data['tokens'] ?? 0;
        
        update_option($option_key, $stats);
    }
}
