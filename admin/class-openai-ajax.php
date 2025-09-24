<?php
/**
 * OpenAI AJAX Handlers
 *
 * @package Smart_Page_Builder
 * @since   3.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OpenAI AJAX Handler class
 */
class SPB_OpenAI_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_spb_test_openai_connection', [$this, 'test_connection']);
        add_action('wp_ajax_spb_test_openai_generation', [$this, 'test_generation']);
        add_action('wp_ajax_spb_save_openai_settings', [$this, 'save_settings']);
    }
    
    /**
     * Test OpenAI API connection
     */
    public function test_connection() {
        check_ajax_referer('spb_openai_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'smart-page-builder'));
        }
        
        try {
            // Load AI providers
            require_once SPB_PLUGIN_DIR . 'includes/ai-providers/abstract-ai-provider.php';
            require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-openai-provider.php';
            
            $openai_provider = new SPB_OpenAI_Provider();
            $result = $openai_provider->test_connection();
            
            if ($result) {
                wp_send_json_success(__('Connection successful', 'smart-page-builder'));
            } else {
                wp_send_json_error(__('Connection failed - no response from API', 'smart-page-builder'));
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Test OpenAI content generation
     */
    public function test_generation() {
        check_ajax_referer('spb_openai_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'smart-page-builder'));
        }
        
        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        
        if (empty($prompt)) {
            wp_send_json_error(__('No prompt provided', 'smart-page-builder'));
        }
        
        try {
            // Load AI providers
            require_once SPB_PLUGIN_DIR . 'includes/ai-providers/abstract-ai-provider.php';
            require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-openai-provider.php';
            
            $openai_provider = new SPB_OpenAI_Provider();
            $response = $openai_provider->generate_content($prompt, [
                'max_tokens' => 500,
                'temperature' => 0.7
            ]);
            
            wp_send_json_success([
                'content' => $response['content'],
                'metadata' => [
                    'model' => $response['model'],
                    'tokens_used' => $response['usage']['total_tokens'],
                    'finish_reason' => $response['finish_reason'],
                    'provider' => $response['provider'],
                    'timestamp' => $response['timestamp']
                ]
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * Save OpenAI settings
     */
    public function save_settings() {
        check_ajax_referer('spb_openai_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'smart-page-builder'));
        }
        
        $settings = [
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'default_model' => sanitize_text_field($_POST['default_model'] ?? 'gpt-3.5-turbo'),
            'search_model' => sanitize_text_field($_POST['search_model'] ?? 'gpt-4'),
            'temperature' => floatval($_POST['temperature'] ?? 0.7),
            'max_tokens' => intval($_POST['max_tokens'] ?? 2000),
            'enabled' => !empty($_POST['enabled'])
        ];
        
        // Validate settings
        if (!empty($settings['api_key']) && !preg_match('/^sk-[a-zA-Z0-9]{48}$/', $settings['api_key'])) {
            wp_send_json_error(__('Invalid API key format', 'smart-page-builder'));
        }
        
        if ($settings['temperature'] < 0 || $settings['temperature'] > 1) {
            wp_send_json_error(__('Temperature must be between 0 and 1', 'smart-page-builder'));
        }
        
        if ($settings['max_tokens'] < 100 || $settings['max_tokens'] > 4000) {
            wp_send_json_error(__('Max tokens must be between 100 and 4000', 'smart-page-builder'));
        }
        
        // Save settings
        foreach ($settings as $key => $value) {
            update_option('spb_openai_' . $key, $value);
        }
        
        wp_send_json_success(__('Settings saved successfully', 'smart-page-builder'));
    }
}

// Initialize AJAX handlers
new SPB_OpenAI_Ajax();
