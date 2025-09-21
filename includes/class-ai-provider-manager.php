<?php
/**
 * AI Provider Manager Class
 *
 * Manages multiple AI providers and handles provider switching,
 * load balancing, and fallback mechanisms.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Provider Manager Class
 *
 * Handles multiple AI providers including OpenAI, Anthropic Claude, and Google Gemini.
 * Provides intelligent provider selection, load balancing, and fallback mechanisms.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_AI_Provider_Manager {

    /**
     * Available AI providers
     *
     * @since    2.0.0
     * @access   private
     * @var      array    $providers
     */
    private $providers = array();

    /**
     * Current active provider
     *
     * @since    2.0.0
     * @access   private
     * @var      string   $active_provider
     */
    private $active_provider;

    /**
     * Provider configurations
     *
     * @since    2.0.0
     * @access   private
     * @var      array    $provider_configs
     */
    private $provider_configs = array();

    /**
     * Cache manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Cache_Manager    $cache_manager
     */
    private $cache_manager;

    /**
     * Security manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Security_Manager    $security_manager
     */
    private $security_manager;

    /**
     * Initialize the AI provider manager
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->cache_manager = new Smart_Page_Builder_Cache_Manager();
        $this->security_manager = new Smart_Page_Builder_Security_Manager();
        
        $this->init_providers();
        $this->load_provider_configs();
    }

    /**
     * Initialize available AI providers
     *
     * @since    2.0.0
     * @access   private
     */
    private function init_providers() {
        $this->providers = array(
            'openai' => array(
                'name' => 'OpenAI GPT',
                'class' => 'Smart_Page_Builder_OpenAI_Provider',
                'models' => array('gpt-3.5-turbo', 'gpt-4', 'gpt-4-turbo'),
                'capabilities' => array('text_generation', 'content_optimization', 'summarization'),
                'rate_limits' => array(
                    'requests_per_minute' => 60,
                    'tokens_per_minute' => 90000,
                    'requests_per_day' => 10000
                ),
                'cost_per_1k_tokens' => 0.002,
                'priority' => 1
            ),
            'anthropic' => array(
                'name' => 'Anthropic Claude',
                'class' => 'Smart_Page_Builder_Anthropic_Provider',
                'models' => array('claude-3-haiku', 'claude-3-sonnet', 'claude-3-opus'),
                'capabilities' => array('text_generation', 'content_optimization', 'analysis'),
                'rate_limits' => array(
                    'requests_per_minute' => 50,
                    'tokens_per_minute' => 100000,
                    'requests_per_day' => 8000
                ),
                'cost_per_1k_tokens' => 0.0015,
                'priority' => 2
            ),
            'google' => array(
                'name' => 'Google Gemini',
                'class' => 'Smart_Page_Builder_Google_Provider',
                'models' => array('gemini-pro', 'gemini-pro-vision'),
                'capabilities' => array('text_generation', 'multimodal', 'reasoning'),
                'rate_limits' => array(
                    'requests_per_minute' => 60,
                    'tokens_per_minute' => 120000,
                    'requests_per_day' => 12000
                ),
                'cost_per_1k_tokens' => 0.001,
                'priority' => 3
            )
        );

        // Allow plugins to register additional providers
        $this->providers = apply_filters('spb_ai_providers', $this->providers);
    }

    /**
     * Load provider configurations from database
     *
     * @since    2.0.0
     * @access   private
     */
    private function load_provider_configs() {
        $this->provider_configs = get_option('spb_ai_provider_configs', array());
        $this->active_provider = get_option('spb_active_ai_provider', 'openai');
    }

    /**
     * Get available providers
     *
     * @since    2.0.0
     * @return   array    Available providers
     */
    public function get_providers() {
        return $this->providers;
    }

    /**
     * Get provider configuration
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @return   array|false    Provider configuration or false if not found
     */
    public function get_provider_config($provider_id) {
        return isset($this->providers[$provider_id]) ? $this->providers[$provider_id] : false;
    }

    /**
     * Set active provider
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @return   bool     Success status
     */
    public function set_active_provider($provider_id) {
        if (!isset($this->providers[$provider_id])) {
            return false;
        }

        $this->active_provider = $provider_id;
        update_option('spb_active_ai_provider', $provider_id);
        
        // Clear provider-specific caches
        $this->cache_manager->delete_group('ai_responses');
        
        return true;
    }

    /**
     * Get active provider
     *
     * @since    2.0.0
     * @return   string   Active provider ID
     */
    public function get_active_provider() {
        return $this->active_provider;
    }

    /**
     * Configure provider settings
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @param    array    $config         Provider configuration
     * @return   bool     Success status
     */
    public function configure_provider($provider_id, $config) {
        if (!isset($this->providers[$provider_id])) {
            return false;
        }

        // Validate configuration
        $validated_config = $this->validate_provider_config($provider_id, $config);
        if ($validated_config === false) {
            return false;
        }

        // Encrypt sensitive data
        if (isset($validated_config['api_key'])) {
            $validated_config['api_key'] = $this->security_manager->encrypt($validated_config['api_key']);
        }

        $this->provider_configs[$provider_id] = $validated_config;
        update_option('spb_ai_provider_configs', $this->provider_configs);
        
        return true;
    }

    /**
     * Get provider instance
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID (optional, uses active if not specified)
     * @return   object|false    Provider instance or false on failure
     */
    public function get_provider_instance($provider_id = null) {
        if ($provider_id === null) {
            $provider_id = $this->active_provider;
        }

        if (!isset($this->providers[$provider_id])) {
            return false;
        }

        $provider_class = $this->providers[$provider_id]['class'];
        
        // Check if provider class exists
        if (!class_exists($provider_class)) {
            $this->load_provider_class($provider_id);
        }

        if (!class_exists($provider_class)) {
            return false;
        }

        // Get provider configuration
        $config = isset($this->provider_configs[$provider_id]) ? $this->provider_configs[$provider_id] : array();
        
        // Decrypt API key if present
        if (isset($config['api_key'])) {
            $config['api_key'] = $this->security_manager->decrypt($config['api_key']);
        }

        return new $provider_class($config);
    }

    /**
     * Generate content using the best available provider
     *
     * @since    2.0.0
     * @param    string   $prompt         Content prompt
     * @param    array    $options        Generation options
     * @return   array    Generation result
     */
    public function generate_content($prompt, $options = array()) {
        $providers_to_try = $this->get_providers_by_priority();
        $last_error = null;

        foreach ($providers_to_try as $provider_id) {
            // Check if provider is configured and available
            if (!$this->is_provider_available($provider_id)) {
                continue;
            }

            // Check rate limits
            if (!$this->check_rate_limits($provider_id)) {
                continue;
            }

            try {
                $provider = $this->get_provider_instance($provider_id);
                if (!$provider) {
                    continue;
                }

                $result = $provider->generate_content($prompt, $options);
                
                if ($result && !is_wp_error($result)) {
                    // Track successful generation
                    $this->track_provider_usage($provider_id, 'success');
                    
                    // Add provider info to result
                    $result['provider'] = $provider_id;
                    $result['model'] = $provider->get_model();
                    
                    return $result;
                }
                
                $last_error = $result;
                
            } catch (Exception $e) {
                $last_error = new WP_Error('provider_error', $e->getMessage());
                $this->track_provider_usage($provider_id, 'error', $e->getMessage());
            }
        }

        // All providers failed
        return $last_error ?: new WP_Error('no_providers', 'No AI providers available');
    }

    /**
     * Optimize content using AI
     *
     * @since    2.0.0
     * @param    string   $content        Content to optimize
     * @param    array    $options        Optimization options
     * @return   array    Optimization result
     */
    public function optimize_content($content, $options = array()) {
        $provider = $this->get_provider_instance();
        if (!$provider) {
            return new WP_Error('no_provider', 'No AI provider available');
        }

        if (!method_exists($provider, 'optimize_content')) {
            return new WP_Error('not_supported', 'Content optimization not supported by current provider');
        }

        return $provider->optimize_content($content, $options);
    }

    /**
     * Get content quality score
     *
     * @since    2.0.0
     * @param    string   $content        Content to analyze
     * @return   array    Quality analysis result
     */
    public function analyze_content_quality($content) {
        $provider = $this->get_provider_instance();
        if (!$provider) {
            return new WP_Error('no_provider', 'No AI provider available');
        }

        if (!method_exists($provider, 'analyze_quality')) {
            // Fallback to basic analysis
            return $this->basic_quality_analysis($content);
        }

        return $provider->analyze_quality($content);
    }

    /**
     * Get providers sorted by priority and availability
     *
     * @since    2.0.0
     * @return   array    Provider IDs sorted by priority
     */
    private function get_providers_by_priority() {
        $providers = $this->providers;
        
        // Sort by priority (lower number = higher priority)
        uasort($providers, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return array_keys($providers);
    }

    /**
     * Check if provider is available and configured
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @return   bool     Availability status
     */
    private function is_provider_available($provider_id) {
        // Check if provider is configured
        if (!isset($this->provider_configs[$provider_id])) {
            return false;
        }

        $config = $this->provider_configs[$provider_id];
        
        // Check if API key is present
        if (empty($config['api_key'])) {
            return false;
        }

        // Check if provider is enabled
        if (isset($config['enabled']) && !$config['enabled']) {
            return false;
        }

        return true;
    }

    /**
     * Check rate limits for provider
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @return   bool     Rate limit status
     */
    private function check_rate_limits($provider_id) {
        $provider_config = $this->providers[$provider_id];
        $rate_limits = $provider_config['rate_limits'];
        
        $cache_key = "spb_rate_limit_{$provider_id}";
        $usage = $this->cache_manager->get($cache_key);
        
        if ($usage === false) {
            $usage = array(
                'requests_per_minute' => 0,
                'requests_per_day' => 0,
                'last_reset_minute' => current_time('i'),
                'last_reset_day' => current_time('j')
            );
        }

        $current_minute = current_time('i');
        $current_day = current_time('j');

        // Reset minute counter if needed
        if ($usage['last_reset_minute'] !== $current_minute) {
            $usage['requests_per_minute'] = 0;
            $usage['last_reset_minute'] = $current_minute;
        }

        // Reset day counter if needed
        if ($usage['last_reset_day'] !== $current_day) {
            $usage['requests_per_day'] = 0;
            $usage['last_reset_day'] = $current_day;
        }

        // Check limits
        if ($usage['requests_per_minute'] >= $rate_limits['requests_per_minute']) {
            return false;
        }

        if ($usage['requests_per_day'] >= $rate_limits['requests_per_day']) {
            return false;
        }

        // Update usage
        $usage['requests_per_minute']++;
        $usage['requests_per_day']++;
        
        $this->cache_manager->set($cache_key, $usage, 3600);
        
        return true;
    }

    /**
     * Track provider usage for analytics
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @param    string   $status         Usage status (success, error, rate_limited)
     * @param    string   $details        Additional details
     */
    private function track_provider_usage($provider_id, $status, $details = '') {
        $usage_data = array(
            'provider_id' => $provider_id,
            'status' => $status,
            'details' => $details,
            'timestamp' => current_time('mysql')
        );

        do_action('spb_ai_provider_usage', $usage_data);
    }

    /**
     * Load provider class file
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     */
    private function load_provider_class($provider_id) {
        $provider_file = SPB_PLUGIN_DIR . "includes/ai-providers/class-{$provider_id}-provider.php";
        
        if (file_exists($provider_file)) {
            require_once $provider_file;
        }
    }

    /**
     * Validate provider configuration
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @param    array    $config         Configuration to validate
     * @return   array|false    Validated configuration or false on failure
     */
    private function validate_provider_config($provider_id, $config) {
        $validated = array();
        
        // Common validation rules
        $required_fields = array('api_key');
        $optional_fields = array('model', 'temperature', 'max_tokens', 'enabled');
        
        // Check required fields
        foreach ($required_fields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return false;
            }
            $validated[$field] = sanitize_text_field($config[$field]);
        }

        // Process optional fields
        foreach ($optional_fields as $field) {
            if (isset($config[$field])) {
                switch ($field) {
                    case 'model':
                        $validated[$field] = sanitize_text_field($config[$field]);
                        break;
                    case 'temperature':
                        $validated[$field] = floatval($config[$field]);
                        $validated[$field] = max(0, min(2, $validated[$field])); // Clamp between 0-2
                        break;
                    case 'max_tokens':
                        $validated[$field] = intval($config[$field]);
                        $validated[$field] = max(1, min(4096, $validated[$field])); // Clamp between 1-4096
                        break;
                    case 'enabled':
                        $validated[$field] = (bool) $config[$field];
                        break;
                }
            }
        }

        return $validated;
    }

    /**
     * Basic content quality analysis fallback
     *
     * @since    2.0.0
     * @param    string   $content        Content to analyze
     * @return   array    Quality analysis result
     */
    private function basic_quality_analysis($content) {
        $word_count = str_word_count($content);
        $sentence_count = preg_match_all('/[.!?]+/', $content);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        
        // Basic readability score (simplified Flesch formula)
        $readability_score = 206.835 - (1.015 * $avg_sentence_length);
        $readability_score = max(0, min(100, $readability_score));
        
        // Content structure analysis
        $has_headings = preg_match('/<h[1-6]/', $content) > 0;
        $has_lists = preg_match('/<[uo]l>/', $content) > 0;
        $has_links = preg_match('/<a\s+href/', $content) > 0;
        
        $structure_score = 0;
        if ($has_headings) $structure_score += 30;
        if ($has_lists) $structure_score += 25;
        if ($has_links) $structure_score += 20;
        if ($word_count >= 300) $structure_score += 25;
        
        $overall_score = ($readability_score * 0.6) + ($structure_score * 0.4);
        
        return array(
            'overall_score' => round($overall_score, 2),
            'readability_score' => round($readability_score, 2),
            'structure_score' => $structure_score,
            'word_count' => $word_count,
            'sentence_count' => $sentence_count,
            'avg_sentence_length' => round($avg_sentence_length, 2),
            'has_headings' => $has_headings,
            'has_lists' => $has_lists,
            'has_links' => $has_links,
            'analysis_method' => 'basic'
        );
    }

    /**
     * Get provider usage statistics
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID (optional)
     * @return   array    Usage statistics
     */
    public function get_usage_statistics($provider_id = null) {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'spb_analytics';
        $where_clause = $provider_id ? $wpdb->prepare("AND provider_id = %s", $provider_id) : "";
        
        $stats = $wpdb->get_results(
            "SELECT 
                provider_id,
                status,
                COUNT(*) as count,
                DATE(timestamp) as date
             FROM {$analytics_table} 
             WHERE event_type = 'ai_provider_usage'
             {$where_clause}
             AND timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY provider_id, status, DATE(timestamp)
             ORDER BY date DESC"
        );

        return $stats;
    }

    /**
     * Test provider connection
     *
     * @since    2.0.0
     * @param    string   $provider_id    Provider ID
     * @return   array    Test result
     */
    public function test_provider_connection($provider_id) {
        if (!isset($this->providers[$provider_id])) {
            return array(
                'success' => false,
                'message' => 'Provider not found'
            );
        }

        $provider = $this->get_provider_instance($provider_id);
        if (!$provider) {
            return array(
                'success' => false,
                'message' => 'Failed to initialize provider'
            );
        }

        if (!method_exists($provider, 'test_connection')) {
            return array(
                'success' => false,
                'message' => 'Provider does not support connection testing'
            );
        }

        return $provider->test_connection();
    }
}
