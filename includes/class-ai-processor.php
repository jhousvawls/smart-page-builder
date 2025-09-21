<?php
/**
 * The AI processor functionality of the plugin.
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The AI processor class.
 *
 * This class handles AI provider integration and content processing.
 *
 * @since      1.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_AI_Processor {

    /**
     * Available AI providers
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $providers    Available AI providers
     */
    private $providers;

    /**
     * Rate limiting data
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $rate_limits    Rate limiting configuration
     */
    private $rate_limits;

    /**
     * Initialize the AI processor
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->init_providers();
        $this->init_rate_limits();
    }

    /**
     * Initialize AI providers
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_providers() {
        $this->providers = array(
            'openai' => array(
                'name' => 'OpenAI',
                'endpoint' => 'https://api.openai.com/v1/chat/completions',
                'model' => 'gpt-3.5-turbo',
                'max_tokens' => 4000,
                'enabled' => true
            ),
            'anthropic' => array(
                'name' => 'Anthropic Claude',
                'endpoint' => 'https://api.anthropic.com/v1/messages',
                'model' => 'claude-3-haiku-20240307',
                'max_tokens' => 4000,
                'enabled' => false // Disabled by default for Phase 1
            )
        );
    }

    /**
     * Initialize rate limiting
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_rate_limits() {
        $this->rate_limits = array(
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'requests_per_day' => 1000
        );
    }

    /**
     * Enhance content with AI
     *
     * @since    1.0.0
     * @param    string    $content       The assembled content to enhance
     * @param    string    $search_term   The original search term
     * @param    string    $content_type  The content type
     * @return   array                    Enhanced content data
     */
    public function enhance_content($content, $search_term, $content_type = 'default') {
        // Check if AI enhancement is enabled
        if (!get_option('spb_ai_enhancement_enabled', false)) {
            return array(
                'enhanced_content' => $content,
                'enhancement_applied' => false,
                'error' => 'AI enhancement is disabled'
            );
        }

        // Check rate limits
        if (!$this->check_rate_limits()) {
            return array(
                'enhanced_content' => $content,
                'enhancement_applied' => false,
                'error' => 'Rate limit exceeded'
            );
        }

        // Get the active provider
        $provider = $this->get_active_provider();
        if (!$provider) {
            return array(
                'enhanced_content' => $content,
                'enhancement_applied' => false,
                'error' => 'No AI provider configured'
            );
        }

        // Generate enhancement prompt
        $prompt = $this->generate_enhancement_prompt($content, $search_term, $content_type);

        // Call AI provider
        $response = $this->call_ai_provider($provider, $prompt);

        if ($response['success']) {
            // Log the enhancement
            $this->log_ai_usage($search_term, $content_type, $provider['name']);

            return array(
                'enhanced_content' => $response['content'],
                'enhancement_applied' => true,
                'provider' => $provider['name'],
                'tokens_used' => $response['tokens_used'] ?? 0
            );
        } else {
            return array(
                'enhanced_content' => $content,
                'enhancement_applied' => false,
                'error' => $response['error']
            );
        }
    }

    /**
     * Generate enhancement prompt for AI
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $content       The content to enhance
     * @param    string    $search_term   The search term
     * @param    string    $content_type  The content type
     * @return   string                   The enhancement prompt
     */
    private function generate_enhancement_prompt($content, $search_term, $content_type) {
        $prompts = array(
            'how_to' => "Please enhance this how-to guide about '{$search_term}'. Make it more comprehensive, add safety tips, and ensure the steps are clear and actionable. Keep the existing structure but improve the content quality and add helpful details.",
            'tool_recommendation' => "Please enhance this tool recommendation content about '{$search_term}'. Add more detailed comparisons, pros and cons, and specific use cases. Make the recommendations more helpful and actionable.",
            'safety_tips' => "Please enhance this safety content about '{$search_term}'. Add more comprehensive safety precautions, potential hazards to avoid, and best practices. Ensure all safety information is accurate and thorough.",
            'troubleshooting' => "Please enhance this troubleshooting guide about '{$search_term}'. Add more detailed diagnostic steps, additional solutions, and prevention tips. Make the troubleshooting process more systematic and comprehensive.",
            'default' => "Please enhance this content about '{$search_term}'. Improve the clarity, add helpful details, and make it more comprehensive while maintaining the existing structure and sources."
        );

        $base_prompt = $prompts[$content_type] ?? $prompts['default'];
        
        $full_prompt = $base_prompt . "\n\n";
        $full_prompt .= "IMPORTANT GUIDELINES:\n";
        $full_prompt .= "- Keep all existing source links and attributions\n";
        $full_prompt .= "- Maintain the HTML structure\n";
        $full_prompt .= "- Add value without changing the core message\n";
        $full_prompt .= "- Ensure accuracy and helpfulness\n";
        $full_prompt .= "- Keep the content focused on DIY and home improvement\n\n";
        $full_prompt .= "Content to enhance:\n\n" . $content;

        return $full_prompt;
    }

    /**
     * Get the active AI provider
     *
     * @since    1.0.0
     * @access   private
     * @return   array|false    Provider configuration or false if none available
     */
    private function get_active_provider() {
        $preferred_provider = get_option('spb_ai_provider', 'openai');
        
        if (isset($this->providers[$preferred_provider]) && $this->providers[$preferred_provider]['enabled']) {
            return $this->providers[$preferred_provider];
        }

        // Fallback to first enabled provider
        foreach ($this->providers as $key => $provider) {
            if ($provider['enabled']) {
                return $provider;
            }
        }

        return false;
    }

    /**
     * Call AI provider API
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $provider    Provider configuration
     * @param    string    $prompt      The prompt to send
     * @return   array                  Response data
     */
    private function call_ai_provider($provider, $prompt) {
        $api_key = get_option('spb_ai_api_key');
        if (empty($api_key)) {
            return array(
                'success' => false,
                'error' => 'API key not configured'
            );
        }

        // Prepare request based on provider
        if (strpos($provider['endpoint'], 'openai.com') !== false) {
            return $this->call_openai($provider, $prompt, $api_key);
        } elseif (strpos($provider['endpoint'], 'anthropic.com') !== false) {
            return $this->call_anthropic($provider, $prompt, $api_key);
        }

        return array(
            'success' => false,
            'error' => 'Unsupported provider'
        );
    }

    /**
     * Call OpenAI API
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $provider    Provider configuration
     * @param    string    $prompt      The prompt to send
     * @param    string    $api_key     API key
     * @return   array                  Response data
     */
    private function call_openai($provider, $prompt, $api_key) {
        $body = array(
            'model' => $provider['model'],
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => $provider['max_tokens'],
            'temperature' => 0.7
        );

        $response = wp_remote_post($provider['endpoint'], array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return array(
                'success' => false,
                'error' => 'API request failed with code: ' . $response_code
            );
        }

        $data = json_decode($response_body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => false,
                'error' => 'Invalid API response format'
            );
        }

        return array(
            'success' => true,
            'content' => $data['choices'][0]['message']['content'],
            'tokens_used' => $data['usage']['total_tokens'] ?? 0
        );
    }

    /**
     * Call Anthropic API (placeholder for future implementation)
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $provider    Provider configuration
     * @param    string    $prompt      The prompt to send
     * @param    string    $api_key     API key
     * @return   array                  Response data
     */
    private function call_anthropic($provider, $prompt, $api_key) {
        // Placeholder for Anthropic implementation
        return array(
            'success' => false,
            'error' => 'Anthropic provider not yet implemented'
        );
    }

    /**
     * Check rate limits
     *
     * @since    1.0.0
     * @access   private
     * @return   bool    Whether request is within rate limits
     */
    private function check_rate_limits() {
        $current_time = time();
        
        // Check minute limit
        $minute_key = 'spb_ai_requests_' . floor($current_time / 60);
        $minute_count = get_transient($minute_key) ?: 0;
        
        if ($minute_count >= $this->rate_limits['requests_per_minute']) {
            return false;
        }

        // Check hour limit
        $hour_key = 'spb_ai_requests_' . floor($current_time / 3600);
        $hour_count = get_transient($hour_key) ?: 0;
        
        if ($hour_count >= $this->rate_limits['requests_per_hour']) {
            return false;
        }

        // Check day limit
        $day_key = 'spb_ai_requests_' . floor($current_time / 86400);
        $day_count = get_transient($day_key) ?: 0;
        
        if ($day_count >= $this->rate_limits['requests_per_day']) {
            return false;
        }

        // Increment counters
        set_transient($minute_key, $minute_count + 1, 60);
        set_transient($hour_key, $hour_count + 1, 3600);
        set_transient($day_key, $day_count + 1, 86400);

        return true;
    }

    /**
     * Log AI usage for analytics
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @param    string    $content_type   The content type
     * @param    string    $provider       The AI provider used
     */
    private function log_ai_usage($search_term, $content_type, $provider) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SPB AI: Enhanced content for '{$search_term}' (type: {$content_type}, provider: {$provider})");
        }

        // Increment usage counter
        $usage_count = get_option('spb_ai_usage_count', 0);
        update_option('spb_ai_usage_count', $usage_count + 1);
    }

    /**
     * Process content with AI (legacy method for backward compatibility)
     *
     * @since    1.0.0
     * @param    string    $content    The content to process
     * @return   string               The processed content
     */
    public function process_content($content) {
        $result = $this->enhance_content($content, '', 'default');
        return $result['enhanced_content'];
    }

    /**
     * Get AI usage statistics
     *
     * @since    1.0.0
     * @return   array    Usage statistics
     */
    public function get_usage_stats() {
        return array(
            'total_requests' => get_option('spb_ai_usage_count', 0),
            'rate_limits' => $this->rate_limits,
            'active_provider' => $this->get_active_provider()['name'] ?? 'None'
        );
    }
}
