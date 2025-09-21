<?php
/**
 * OpenAI Provider Class
 *
 * Handles OpenAI GPT API integration for content generation,
 * optimization, and quality analysis.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes/ai-providers
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load abstract provider if not already loaded
if (!class_exists('Smart_Page_Builder_Abstract_Provider')) {
    require_once SPB_PLUGIN_DIR . 'includes/ai-providers/abstract-ai-provider.php';
}

/**
 * OpenAI Provider Class
 *
 * Implements OpenAI GPT API integration with support for GPT-3.5-turbo
 * and GPT-4 models. Provides content generation, optimization, and
 * quality analysis capabilities.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes/ai-providers
 */
class Smart_Page_Builder_OpenAI_Provider extends Smart_Page_Builder_Abstract_Provider {

    /**
     * Initialize OpenAI provider
     *
     * @since    2.0.0
     * @access   protected
     */
    protected function init_provider() {
        $this->provider_name = 'OpenAI';
        $this->api_endpoint = 'https://api.openai.com/v1/chat/completions';
        $this->default_model = 'gpt-3.5-turbo';
        $this->timeout = 60; // OpenAI can be slower for complex requests
    }

    /**
     * Generate content using OpenAI GPT
     *
     * @since    2.0.0
     * @param    string   $prompt     Content prompt
     * @param    array    $options    Generation options
     * @return   array|WP_Error    Generation result or error
     */
    public function generate_content($prompt, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OpenAI provider is not configured with an API key');
        }

        // Extract content type and search term from options
        $content_type = isset($options['content_type']) ? $options['content_type'] : 'default';
        $search_term = isset($options['search_term']) ? $options['search_term'] : '';

        // Build optimized prompt for content type
        if (!empty($search_term)) {
            $optimized_prompt = $this->build_content_prompt($search_term, $content_type, $options);
        } else {
            $optimized_prompt = $prompt;
        }

        // Get content type specific settings
        $type_settings = $this->get_content_type_settings($content_type);

        // Prepare request data
        $request_data = array(
            'model' => $this->get_model(),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => $this->get_system_prompt($content_type)
                ),
                array(
                    'role' => 'user',
                    'content' => $optimized_prompt
                )
            ),
            'temperature' => isset($options['temperature']) ? $options['temperature'] : $type_settings['temperature'],
            'max_tokens' => isset($options['max_tokens']) ? $options['max_tokens'] : $type_settings['max_tokens'],
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        );

        // Add response format for structured content
        if ($content_type === 'how-to') {
            $request_data['response_format'] = array('type' => 'text');
        }

        $headers = array(
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type' => 'application/json',
            'User-Agent' => 'Smart Page Builder/' . SPB_VERSION
        );

        $response = $this->make_request($this->api_endpoint, $request_data, $headers);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!$this->validate_response($response)) {
            return new WP_Error('invalid_response', 'Invalid response from OpenAI API');
        }

        $content = $this->extract_content_from_response($response);
        if (!$content) {
            return new WP_Error('content_extraction_failed', 'Failed to extract content from OpenAI response');
        }

        // Process and format content
        $formatted_content = $this->format_content($content, $content_type);

        // Calculate usage and cost
        $usage = isset($response['usage']) ? $response['usage'] : array();
        $cost = $this->calculate_cost($usage);

        return $this->format_response($formatted_content, array(
            'usage' => $usage,
            'cost' => $cost,
            'model_used' => $this->get_model(),
            'content_type' => $content_type,
            'prompt_tokens' => isset($usage['prompt_tokens']) ? $usage['prompt_tokens'] : 0,
            'completion_tokens' => isset($usage['completion_tokens']) ? $usage['completion_tokens'] : 0,
            'total_tokens' => isset($usage['total_tokens']) ? $usage['total_tokens'] : 0
        ));
    }

    /**
     * Optimize existing content using OpenAI
     *
     * @since    2.0.0
     * @param    string   $content    Content to optimize
     * @param    array    $options    Optimization options
     * @return   array|WP_Error    Optimization result or error
     */
    public function optimize_content($content, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OpenAI provider is not configured with an API key');
        }

        $optimization_type = isset($options['type']) ? $options['type'] : 'general';
        $content_type = isset($options['content_type']) ? $options['content_type'] : 'default';

        $optimization_prompts = array(
            'readability' => "Improve the readability of this content while maintaining its meaning and accuracy. Make sentences clearer, use simpler language where appropriate, and improve the overall flow:",
            'seo' => "Optimize this content for SEO while maintaining its quality and readability. Improve keyword usage, add relevant subheadings, and enhance the structure:",
            'engagement' => "Make this content more engaging and compelling while keeping it informative and accurate. Add hooks, improve transitions, and make it more interesting to read:",
            'length' => "Expand this content to be more comprehensive and detailed while maintaining quality. Add more examples, explanations, and useful information:",
            'general' => "Improve this content to make it clearer, more informative, and better structured. Enhance readability and ensure it provides maximum value to readers:"
        );

        $prompt = isset($optimization_prompts[$optimization_type]) 
            ? $optimization_prompts[$optimization_type] 
            : $optimization_prompts['general'];

        $prompt .= "\n\nOriginal content:\n" . $content;

        $request_data = array(
            'model' => $this->get_model(),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an expert content optimizer. Your task is to improve content while maintaining its accuracy and core message.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'temperature' => 0.3, // Lower temperature for optimization
            'max_tokens' => 2000
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type' => 'application/json'
        );

        $response = $this->make_request($this->api_endpoint, $request_data, $headers);

        if (is_wp_error($response)) {
            return $response;
        }

        $optimized_content = $this->extract_content_from_response($response);
        if (!$optimized_content) {
            return new WP_Error('optimization_failed', 'Failed to optimize content');
        }

        $formatted_content = $this->format_content($optimized_content, $content_type);

        $usage = isset($response['usage']) ? $response['usage'] : array();
        $cost = $this->calculate_cost($usage);

        return $this->format_response($formatted_content, array(
            'optimization_type' => $optimization_type,
            'usage' => $usage,
            'cost' => $cost,
            'original_length' => str_word_count($content),
            'optimized_length' => str_word_count($formatted_content)
        ));
    }

    /**
     * Analyze content quality using OpenAI
     *
     * @since    2.0.0
     * @param    string   $content    Content to analyze
     * @return   array|WP_Error    Quality analysis result or error
     */
    public function analyze_quality($content) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', 'OpenAI provider is not configured with an API key');
        }

        $analysis_prompt = "Analyze the quality of this content and provide a detailed assessment. Consider the following aspects:

1. Clarity and readability
2. Structure and organization
3. Accuracy and helpfulness
4. Engagement and interest level
5. Completeness and depth
6. Grammar and writing quality

Provide a score from 1-100 for each aspect and an overall score. Also provide specific suggestions for improvement.

Content to analyze:
" . $content;

        $request_data = array(
            'model' => $this->get_model(),
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'You are an expert content analyst. Provide detailed, constructive analysis of content quality with specific scores and actionable feedback.'
                ),
                array(
                    'role' => 'user',
                    'content' => $analysis_prompt
                )
            ),
            'temperature' => 0.2, // Low temperature for consistent analysis
            'max_tokens' => 1000
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type' => 'application/json'
        );

        $response = $this->make_request($this->api_endpoint, $request_data, $headers);

        if (is_wp_error($response)) {
            return $response;
        }

        $analysis_text = $this->extract_content_from_response($response);
        if (!$analysis_text) {
            return new WP_Error('analysis_failed', 'Failed to analyze content quality');
        }

        // Extract scores from analysis text
        $scores = $this->extract_quality_scores($analysis_text);
        
        // Calculate basic quality score as fallback
        $basic_score = $this->calculate_quality_score($content);

        $usage = isset($response['usage']) ? $response['usage'] : array();

        return array(
            'overall_score' => isset($scores['overall']) ? $scores['overall'] : $basic_score,
            'detailed_scores' => $scores,
            'analysis_text' => $analysis_text,
            'word_count' => str_word_count($content),
            'readability_estimate' => $this->estimate_readability($content),
            'suggestions' => $this->extract_suggestions($analysis_text),
            'usage' => $usage,
            'cost' => $this->calculate_cost($usage)
        );
    }

    /**
     * Test OpenAI connection
     *
     * @since    2.0.0
     * @return   array    Test result with success status and message
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return array(
                'success' => false,
                'message' => 'OpenAI API key is not configured'
            );
        }

        $test_data = array(
            'model' => $this->get_model(),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => 'Hello, this is a connection test. Please respond with "Connection successful".'
                )
            ),
            'max_tokens' => 10,
            'temperature' => 0
        );

        $headers = array(
            'Authorization' => 'Bearer ' . $this->get_api_key(),
            'Content-Type' => 'application/json'
        );

        $response = $this->make_request($this->api_endpoint, $test_data, $headers);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => 'Connection failed: ' . $response->get_error_message()
            );
        }

        $content = $this->extract_content_from_response($response);
        
        return array(
            'success' => !empty($content),
            'message' => !empty($content) ? 'Connection successful' : 'Connection failed: No response content',
            'model' => $this->get_model(),
            'response' => $content
        );
    }

    /**
     * Extract content from OpenAI API response
     *
     * @since    2.0.0
     * @access   protected
     * @param    array    $response    API response
     * @return   string|false    Extracted content or false
     */
    protected function extract_content_from_response($response) {
        if (!isset($response['choices']) || empty($response['choices'])) {
            return false;
        }

        $choice = $response['choices'][0];
        
        if (isset($choice['message']['content'])) {
            return trim($choice['message']['content']);
        }

        return false;
    }

    /**
     * Get system prompt for content type
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content_type    Content type
     * @return   string   System prompt
     */
    private function get_system_prompt($content_type) {
        $system_prompts = array(
            'how-to' => 'You are an expert technical writer specializing in clear, step-by-step instructional content. Create comprehensive how-to guides that are easy to follow, well-structured, and include all necessary details for successful completion.',
            
            'tool-recommendation' => 'You are a knowledgeable product expert who provides detailed, unbiased tool and equipment recommendations. Focus on practical features, value for money, and specific use cases to help users make informed decisions.',
            
            'safety-tips' => 'You are a safety expert who creates comprehensive safety guides. Prioritize accuracy, emphasize important precautions, and provide clear, actionable safety advice that helps prevent accidents and injuries.',
            
            'troubleshooting' => 'You are a technical problem-solving expert who creates systematic troubleshooting guides. Provide logical diagnostic steps, multiple solution approaches, and clear explanations of when to seek professional help.',
            
            'default' => 'You are an expert content writer who creates informative, engaging, and well-structured articles. Focus on providing valuable information that helps readers understand topics clearly and take appropriate action.'
        );

        return isset($system_prompts[$content_type]) ? $system_prompts[$content_type] : $system_prompts['default'];
    }

    /**
     * Format content based on content type
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content         Raw content
     * @param    string   $content_type    Content type
     * @return   string   Formatted content
     */
    private function format_content($content, $content_type) {
        // Convert markdown-style formatting to HTML
        $content = $this->convert_markdown_to_html($content);
        
        // Add content type specific formatting
        switch ($content_type) {
            case 'how-to':
                $content = $this->format_howto_content($content);
                break;
            case 'tool-recommendation':
                $content = $this->format_tool_content($content);
                break;
            case 'safety-tips':
                $content = $this->format_safety_content($content);
                break;
            case 'troubleshooting':
                $content = $this->format_troubleshooting_content($content);
                break;
        }

        return $content;
    }

    /**
     * Convert markdown-style formatting to HTML
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content    Content with markdown
     * @return   string   HTML formatted content
     */
    private function convert_markdown_to_html($content) {
        // Convert headers
        $content = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $content);
        
        // Convert bold text
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        
        // Convert italic text
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        
        // Convert numbered lists
        $content = preg_replace('/^\d+\.\s+(.*)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*<\/li>)/s', '<ol>$1</ol>', $content);
        
        // Convert bullet lists
        $content = preg_replace('/^[-*]\s+(.*)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content);
        
        // Convert paragraphs
        $paragraphs = explode("\n\n", $content);
        $formatted_paragraphs = array();
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph) && !preg_match('/^<[huo]/', $paragraph)) {
                $formatted_paragraphs[] = '<p>' . $paragraph . '</p>';
            } else {
                $formatted_paragraphs[] = $paragraph;
            }
        }
        
        return implode("\n\n", $formatted_paragraphs);
    }

    /**
     * Format how-to content with special structure
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content    Raw content
     * @return   string   Formatted content
     */
    private function format_howto_content($content) {
        // Add step highlighting
        $content = preg_replace('/Step (\d+):/i', '<strong class="spb-step-number">Step $1:</strong>', $content);
        
        // Highlight materials and tools sections
        $content = preg_replace('/(Materials?|Tools?|Equipment) (needed|required):/i', '<h3 class="spb-materials-heading">$1 $2:</h3>', $content);
        
        // Highlight safety warnings
        $content = preg_replace('/(Warning|Caution|Important|Note):/i', '<div class="spb-warning"><strong>$1:</strong>', $content);
        $content = preg_replace('/(<div class="spb-warning">.*?)(\n|$)/s', '$1</div>$2', $content);
        
        return $content;
    }

    /**
     * Format tool recommendation content
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content    Raw content
     * @return   string   Formatted content
     */
    private function format_tool_content($content) {
        // Highlight product names
        $content = preg_replace('/(\d+\.\s+)([A-Z][^-\n]+)(\s*-)/m', '$1<strong class="spb-product-name">$2</strong>$3', $content);
        
        // Highlight price ranges
        $content = preg_replace('/(\$[\d,]+(?:\.\d{2})?(?:\s*-\s*\$[\d,]+(?:\.\d{2})?)?)/', '<span class="spb-price">$1</span>', $content);
        
        return $content;
    }

    /**
     * Format safety content with warnings
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content    Raw content
     * @return   string   Formatted content
     */
    private function format_safety_content($content) {
        // Highlight safety warnings
        $content = preg_replace('/(Danger|Warning|Caution|Alert):/i', '<div class="spb-safety-alert spb-alert-high"><strong>$1:</strong>', $content);
        $content = preg_replace('/(<div class="spb-safety-alert">.*?)(\n\n|$)/s', '$1</div>$2', $content);
        
        // Highlight safety equipment
        $content = preg_replace('/(safety glasses|gloves|helmet|mask|protection)/i', '<strong class="spb-safety-equipment">$1</strong>', $content);
        
        return $content;
    }

    /**
     * Format troubleshooting content
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content    Raw content
     * @return   string   Formatted content
     */
    private function format_troubleshooting_content($content) {
        // Highlight problem symptoms
        $content = preg_replace('/(Problem|Issue|Symptom):/i', '<h4 class="spb-problem-heading">$1:</h4>', $content);
        
        // Highlight solutions
        $content = preg_replace('/(Solution|Fix|Resolution):/i', '<h4 class="spb-solution-heading">$1:</h4>', $content);
        
        return $content;
    }

    /**
     * Calculate API usage cost
     *
     * @since    2.0.0
     * @access   private
     * @param    array    $usage    Usage data from API response
     * @return   float    Cost in USD
     */
    private function calculate_cost($usage) {
        if (empty($usage) || !isset($usage['total_tokens'])) {
            return 0;
        }

        $model = $this->get_model();
        
        // OpenAI pricing (as of 2024)
        $pricing = array(
            'gpt-3.5-turbo' => 0.002, // $0.002 per 1K tokens
            'gpt-4' => 0.03,          // $0.03 per 1K tokens
            'gpt-4-turbo' => 0.01     // $0.01 per 1K tokens
        );

        $rate = isset($pricing[$model]) ? $pricing[$model] : $pricing['gpt-3.5-turbo'];
        
        return ($usage['total_tokens'] / 1000) * $rate;
    }

    /**
     * Extract quality scores from analysis text
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $analysis_text    Analysis text from AI
     * @return   array    Extracted scores
     */
    private function extract_quality_scores($analysis_text) {
        $scores = array();
        
        // Look for score patterns
        preg_match_all('/(\w+(?:\s+\w+)*?):\s*(\d+)(?:\/100)?/i', $analysis_text, $matches);
        
        if (!empty($matches[1]) && !empty($matches[2])) {
            foreach ($matches[1] as $index => $aspect) {
                $aspect = strtolower(trim($aspect));
                $score = intval($matches[2][$index]);
                $scores[$aspect] = $score;
            }
        }
        
        // Look for overall score
        if (preg_match('/overall.*?(\d+)(?:\/100)?/i', $analysis_text, $overall_match)) {
            $scores['overall'] = intval($overall_match[1]);
        }
        
        return $scores;
    }

    /**
     * Extract suggestions from analysis text
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $analysis_text    Analysis text from AI
     * @return   array    Extracted suggestions
     */
    private function extract_suggestions($analysis_text) {
        $suggestions = array();
        
        // Look for suggestion patterns
        if (preg_match('/suggestions?:(.*)$/is', $analysis_text, $matches)) {
            $suggestion_text = trim($matches[1]);
            $suggestions = array_filter(explode("\n", $suggestion_text));
        }
        
        return $suggestions;
    }

    /**
     * Estimate content readability
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $content    Content to analyze
     * @return   array    Readability metrics
     */
    private function estimate_readability($content) {
        $text = strip_tags($content);
        $word_count = str_word_count($text);
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $syllable_count = $this->count_syllables($text);
        
        // Flesch Reading Ease Score
        if ($sentence_count > 0 && $word_count > 0) {
            $avg_sentence_length = $word_count / $sentence_count;
            $avg_syllables_per_word = $syllable_count / $word_count;
            
            $flesch_score = 206.835 - (1.015 * $avg_sentence_length) - (84.6 * $avg_syllables_per_word);
            $flesch_score = max(0, min(100, $flesch_score));
        } else {
            $flesch_score = 0;
        }
        
        return array(
            'flesch_score' => round($flesch_score, 1),
            'reading_level' => $this->get_reading_level($flesch_score),
            'avg_sentence_length' => $sentence_count > 0 ? round($word_count / $sentence_count, 1) : 0,
            'avg_syllables_per_word' => $word_count > 0 ? round($syllable_count / $word_count, 1) : 0
        );
    }

    /**
     * Count syllables in text (approximate)
     *
     * @since    2.0.0
     * @access   private
     * @param    string   $text    Text to analyze
     * @return   int      Estimated syllable count
     */
    private function count_syllables($text) {
        $text = strtolower($text);
        $syllable_count = 0;
        $words = str_word_count($text, 1);
        
        foreach ($words as $word) {
            $syllable_count += max(1, preg_match_all('/[aeiouy]+/', $word));
        }
        
        return $syllable_count;
    }

    /**
     * Get reading level from Flesch score
     *
     * @since    2.0.0
     * @access   private
     * @param    float    $flesch_score    Flesch reading ease score
     * @return   string   Reading level description
     */
    private function get_reading_level($flesch_score) {
        if ($flesch_score >= 90) return 'Very Easy';
        if ($flesch_score >= 80) return 'Easy';
        if ($flesch_score >= 70) return 'Fairly Easy';
        if ($flesch_score >= 60) return 'Standard';
        if ($flesch_score >= 50) return 'Fairly Difficult';
        if ($flesch_score >= 30) return 'Difficult';
        return 'Very Difficult';
    }
}
