<?php
/**
 * OpenAI Provider Implementation
 *
 * @package Smart_Page_Builder
 * @since   3.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OpenAI Provider class
 */
class SPB_OpenAI_Provider extends SPB_Abstract_AI_Provider {
    
    /**
     * Load provider configuration
     */
    protected function load_configuration() {
        $this->provider_name = 'openai';
        $this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->api_key = get_option('spb_openai_api_key', '');
        $this->default_model = get_option('spb_openai_default_model', 'gpt-3.5-turbo');
        
        $this->available_models = [
            'gpt-4' => [
                'name' => 'GPT-4',
                'description' => 'Most capable model, best for complex tasks',
                'max_tokens' => 8192,
                'cost_per_1k_tokens' => 0.03
            ],
            'gpt-4-turbo-preview' => [
                'name' => 'GPT-4 Turbo',
                'description' => 'Latest GPT-4 model with improved performance',
                'max_tokens' => 4096,
                'cost_per_1k_tokens' => 0.01
            ],
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'description' => 'Fast and efficient for most tasks',
                'max_tokens' => 4096,
                'cost_per_1k_tokens' => 0.002
            ],
            'gpt-3.5-turbo-16k' => [
                'name' => 'GPT-3.5 Turbo 16K',
                'description' => 'Extended context length version',
                'max_tokens' => 16384,
                'cost_per_1k_tokens' => 0.004
            ]
        ];
    }
    
    /**
     * Generate content using OpenAI
     *
     * @param string $prompt The prompt to send to OpenAI
     * @param array $options Additional options
     * @return array Response with content and metadata
     */
    public function generate_content($prompt, $options = []) {
        if (!$this->is_configured()) {
            throw new Exception('OpenAI API key not configured');
        }
        
        $prompt = $this->sanitize_prompt($prompt);
        
        // Prepare request options
        $model = $options['model'] ?? $this->default_model;
        $temperature = $options['temperature'] ?? 0.7;
        $max_tokens = $options['max_tokens'] ?? 2000;
        $system_message = $options['system_message'] ?? 'You are a helpful assistant that creates high-quality, engaging content for websites.';
        
        $messages = [
            [
                'role' => 'system',
                'content' => $system_message
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        $request_data = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $max_tokens,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ];
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        ];
        
        try {
            $start_time = microtime(true);
            $response = $this->make_request($this->api_endpoint, $request_data, $headers);
            $processing_time = microtime(true) - $start_time;
            
            $parsed_response = $this->parse_response($response);
            
            // Log usage
            $this->log_usage('content_generation', [
                'model' => $model,
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
                'processing_time' => $processing_time
            ]);
            
            // Update usage statistics
            $this->update_usage_stats([
                'tokens' => $response['usage']['total_tokens'] ?? 0,
                'model' => $model,
                'processing_time' => $processing_time
            ]);
            
            return $parsed_response;
            
        } catch (Exception $e) {
            error_log('OpenAI API Error: ' . $e->getMessage());
            throw new Exception('Failed to generate content: ' . $e->getMessage());
        }
    }
    
    /**
     * Test API connection
     *
     * @return bool Whether connection is successful
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return false;
        }
        
        try {
            $test_prompt = 'Say "Hello" if you can hear me.';
            $response = $this->generate_content($test_prompt, [
                'max_tokens' => 10,
                'temperature' => 0
            ]);
            
            return !empty($response['content']);
            
        } catch (Exception $e) {
            error_log('OpenAI connection test failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Parse OpenAI response and extract content
     *
     * @param array $response Raw API response
     * @return array Parsed content with metadata
     */
    protected function parse_response($response) {
        if (!isset($response['choices']) || empty($response['choices'])) {
            throw new Exception('No content generated by OpenAI');
        }
        
        $choice = $response['choices'][0];
        $content = $choice['message']['content'] ?? '';
        
        if (empty($content)) {
            throw new Exception('Empty content generated by OpenAI');
        }
        
        return [
            'content' => trim($content),
            'model' => $response['model'] ?? 'unknown',
            'usage' => [
                'prompt_tokens' => $response['usage']['prompt_tokens'] ?? 0,
                'completion_tokens' => $response['usage']['completion_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0
            ],
            'finish_reason' => $choice['finish_reason'] ?? 'unknown',
            'provider' => 'openai',
            'timestamp' => current_time('mysql')
        ];
    }
    
    /**
     * Generate structured content for search pages
     *
     * @param string $search_query The search query
     * @param array $context Additional context
     * @return array Structured content components
     */
    public function generate_search_page_content($search_query, $context = []) {
        $existing_content = $context['existing_content'] ?? [];
        $user_intent = $context['user_intent'] ?? 'informational';
        
        // Create comprehensive prompt for search page generation
        $prompt = $this->build_search_page_prompt($search_query, $existing_content, $user_intent);
        
        $system_message = 'You are an expert content creator specializing in comprehensive, SEO-optimized web pages. Create detailed, engaging content that provides real value to users searching for specific information.';
        
        $response = $this->generate_content($prompt, [
            'model' => get_option('spb_openai_search_model', 'gpt-4'),
            'temperature' => 0.7,
            'max_tokens' => 3000,
            'system_message' => $system_message
        ]);
        
        return $this->parse_search_content($response['content'], $search_query);
    }
    
    /**
     * Build comprehensive prompt for search page generation
     *
     * @param string $search_query Search query
     * @param array $existing_content Existing content context
     * @param string $user_intent User intent type
     * @return string Generated prompt
     */
    private function build_search_page_prompt($search_query, $existing_content, $user_intent) {
        $prompt = "Create a comprehensive, engaging web page for the search query: \"{$search_query}\"\n\n";
        
        $prompt .= "User Intent: {$user_intent}\n\n";
        
        if (!empty($existing_content)) {
            $prompt .= "Existing related content on the site:\n";
            foreach ($existing_content as $content) {
                $prompt .= "- {$content['title']}: {$content['excerpt']}\n";
            }
            $prompt .= "\n";
        }
        
        $prompt .= "Please create content with the following structure:\n\n";
        $prompt .= "HERO_SECTION:\n";
        $prompt .= "- Compelling headline that addresses the search query\n";
        $prompt .= "- Engaging subheadline that provides value proposition\n";
        $prompt .= "- Brief introduction paragraph (2-3 sentences)\n\n";
        
        $prompt .= "MAIN_CONTENT:\n";
        $prompt .= "- Comprehensive article addressing the search query\n";
        $prompt .= "- Include practical tips, step-by-step guidance, or detailed information\n";
        $prompt .= "- Use clear headings and subheadings\n";
        $prompt .= "- Aim for 800-1200 words\n\n";
        
        $prompt .= "KEY_POINTS:\n";
        $prompt .= "- 5-7 key takeaways or important points\n";
        $prompt .= "- Each point should be actionable or informative\n\n";
        
        $prompt .= "CALL_TO_ACTION:\n";
        $prompt .= "- Relevant call-to-action that encourages user engagement\n";
        $prompt .= "- Should relate to the search query and user intent\n\n";
        
        $prompt .= "RELATED_TOPICS:\n";
        $prompt .= "- 3-5 related topics or questions users might have\n";
        $prompt .= "- These can be used for internal linking or future content\n\n";
        
        $prompt .= "Format the response with clear section markers like [HERO_SECTION], [MAIN_CONTENT], etc.";
        
        return $prompt;
    }
    
    /**
     * Parse generated content into structured components
     *
     * @param string $content Generated content
     * @param string $search_query Original search query
     * @return array Structured content components
     */
    private function parse_search_content($content, $search_query) {
        $components = [
            'hero' => [],
            'main_content' => '',
            'key_points' => [],
            'call_to_action' => '',
            'related_topics' => [],
            'meta' => [
                'search_query' => $search_query,
                'generated_at' => current_time('mysql'),
                'provider' => 'openai'
            ]
        ];
        
        // Parse sections using markers
        $sections = [
            'HERO_SECTION' => 'hero',
            'MAIN_CONTENT' => 'main_content',
            'KEY_POINTS' => 'key_points',
            'CALL_TO_ACTION' => 'call_to_action',
            'RELATED_TOPICS' => 'related_topics'
        ];
        
        foreach ($sections as $marker => $key) {
            $pattern = '/\[' . $marker . '\](.*?)(?=\[|$)/s';
            if (preg_match($pattern, $content, $matches)) {
                $section_content = trim($matches[1]);
                
                if ($key === 'hero') {
                    $components[$key] = $this->parse_hero_section($section_content);
                } elseif ($key === 'key_points' || $key === 'related_topics') {
                    $components[$key] = $this->parse_list_section($section_content);
                } else {
                    $components[$key] = $section_content;
                }
            }
        }
        
        // If no structured content found, treat entire content as main content
        if (empty($components['main_content']) && !empty($content)) {
            $components['main_content'] = $content;
            $components['hero'] = [
                'headline' => ucfirst($search_query),
                'subheadline' => 'Comprehensive guide and information',
                'introduction' => 'Find everything you need to know about ' . $search_query . '.'
            ];
        }
        
        return $components;
    }
    
    /**
     * Parse hero section content
     *
     * @param string $content Hero section content
     * @return array Parsed hero components
     */
    private function parse_hero_section($content) {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        
        return [
            'headline' => $lines[0] ?? '',
            'subheadline' => $lines[1] ?? '',
            'introduction' => implode(' ', array_slice($lines, 2))
        ];
    }
    
    /**
     * Parse list section content
     *
     * @param string $content List section content
     * @return array Parsed list items
     */
    private function parse_list_section($content) {
        $lines = array_filter(array_map('trim', explode("\n", $content)));
        $items = [];
        
        foreach ($lines as $line) {
            // Remove bullet points and numbering
            $line = preg_replace('/^[-*•]\s*/', '', $line);
            $line = preg_replace('/^\d+\.\s*/', '', $line);
            
            if (!empty($line)) {
                $items[] = $line;
            }
        }
        
        return $items;
    }
    
    /**
     * Get model pricing information
     *
     * @param string $model Model name
     * @return array Pricing information
     */
    public function get_model_pricing($model = null) {
        $model = $model ?? $this->default_model;
        
        if (isset($this->available_models[$model])) {
            return [
                'model' => $model,
                'cost_per_1k_tokens' => $this->available_models[$model]['cost_per_1k_tokens'],
                'max_tokens' => $this->available_models[$model]['max_tokens']
            ];
        }
        
        return null;
    }
    
    /**
     * Estimate cost for a given prompt
     *
     * @param string $prompt Prompt text
     * @param string $model Model to use
     * @return array Cost estimation
     */
    public function estimate_cost($prompt, $model = null) {
        $model = $model ?? $this->default_model;
        $pricing = $this->get_model_pricing($model);
        
        if (!$pricing) {
            return null;
        }
        
        // Rough token estimation (1 token ≈ 4 characters)
        $estimated_prompt_tokens = strlen($prompt) / 4;
        $estimated_completion_tokens = 1000; // Assume average completion
        $total_tokens = $estimated_prompt_tokens + $estimated_completion_tokens;
        
        $estimated_cost = ($total_tokens / 1000) * $pricing['cost_per_1k_tokens'];
        
        return [
            'estimated_tokens' => round($total_tokens),
            'estimated_cost' => round($estimated_cost, 4),
            'currency' => 'USD',
            'model' => $model
        ];
    }
}
