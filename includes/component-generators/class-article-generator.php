<?php
/**
 * Article Component Generator
 *
 * Generates personalized article and content components for search-triggered pages.
 * Creates informative content summaries, featured articles, and content recommendations
 * based on user search intent and discovery results.
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
 * Article Component Generator Class
 *
 * Specializes in generating article components with personalized
 * content summaries, recommendations, and related content.
 */
class SPB_Article_Generator extends SPB_Abstract_Component_Generator {

    /**
     * Get component type identifier
     *
     * @return string Component type identifier
     */
    protected function get_component_type() {
        return 'article';
    }

    /**
     * Get provider preferences for article generation
     *
     * Articles benefit from providers that excel at structured content
     * and informational writing.
     *
     * @param array $generation_context Generation context
     * @return array Ordered list of preferred providers
     */
    protected function get_provider_preferences($generation_context) {
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $complexity = $generation_context['complexity_level'] ?? 'medium';
        
        // Educational content benefits from structured, detailed providers
        if ($intent === 'educational') {
            return ['openai', 'google', 'anthropic'];
        }
        
        // Complex content benefits from analytical providers
        if ($complexity === 'high') {
            return ['google', 'openai', 'anthropic'];
        }
        
        // Default preference for informational content
        return ['openai', 'anthropic', 'google'];
    }

    /**
     * Prepare generation context for AI
     *
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Discovery results
     * @return array Generation context for AI
     */
    protected function prepare_generation_context($personalization_context, $discovery_results) {
        $search_query = $personalization_context['search_query'] ?? '';
        $intent_context = $personalization_context['intent_context'] ?? [];
        $user_interests = $personalization_context['user_interests'] ?? [];
        
        // Process discovery results for content generation
        $content_sources = $this->process_content_sources($discovery_results);
        
        // Determine article structure based on intent and available content
        $article_structure = $this->determine_article_structure($intent_context, $content_sources);
        
        // Extract key topics and themes
        $key_topics = $this->extract_key_topics($discovery_results, $search_query);
        
        // Build generation context
        return [
            'search_query' => $search_query,
            'intent_context' => $intent_context,
            'user_interests' => $user_interests,
            'content_sources' => $content_sources,
            'article_structure' => $article_structure,
            'key_topics' => $key_topics,
            'tone_preference' => $personalization_context['tone_preference'] ?? 'informative',
            'complexity_level' => $personalization_context['complexity_level'] ?? 'medium',
            'reading_level' => $this->determine_reading_level($user_interests, $intent_context),
            'content_depth' => $this->determine_content_depth($discovery_results),
            'target_length' => $this->determine_target_length($intent_context)
        ];
    }

    /**
     * Build AI prompt for article generation
     *
     * @param array $generation_context Generation context
     * @return string AI prompt
     */
    protected function build_ai_prompt($generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $structure = $generation_context['article_structure'] ?? 'summary';
        $tone = $generation_context['tone_preference'] ?? 'informative';
        $reading_level = $generation_context['reading_level'] ?? 'intermediate';
        $target_length = $generation_context['target_length'] ?? 'medium';
        
        $prompt = "Create an informative article component for a search-triggered page.\n\n";
        $prompt .= "CONTEXT:\n";
        $prompt .= "- Search Query: \"{$search_query}\"\n";
        $prompt .= "- User Intent: {$intent}\n";
        $prompt .= "- Article Structure: {$structure}\n";
        $prompt .= "- Tone: {$tone}\n";
        $prompt .= "- Reading Level: {$reading_level}\n";
        $prompt .= "- Target Length: {$target_length}\n";
        
        // Add user interests if available
        if (!empty($generation_context['user_interests'])) {
            $interests = array_keys($generation_context['user_interests']);
            $interests_text = implode(', ', array_slice($interests, 0, 5));
            $prompt .= "- User Interests: {$interests_text}\n";
        }
        
        // Add key topics
        if (!empty($generation_context['key_topics'])) {
            $topics_text = implode(', ', $generation_context['key_topics']);
            $prompt .= "- Key Topics: {$topics_text}\n";
        }
        
        // Add content sources information
        if (!empty($generation_context['content_sources'])) {
            $prompt .= "- Available Content Sources: " . count($generation_context['content_sources']) . " items\n";
        }
        
        $prompt .= "\nREQUIREMENTS:\n";
        $prompt .= $this->get_component_requirements();
        
        $prompt .= "\nOUTPUT FORMAT (JSON):\n";
        $prompt .= "{\n";
        $prompt .= "  \"title\": \"Article title (max 80 characters)\",\n";
        $prompt .= "  \"summary\": \"Brief summary (max 150 characters)\",\n";
        $prompt .= "  \"introduction\": \"Opening paragraph (max 300 characters)\",\n";
        $prompt .= "  \"main_content\": \"Main article content (max 800 characters)\",\n";
        $prompt .= "  \"key_points\": [\"point1\", \"point2\", \"point3\"],\n";
        $prompt .= "  \"related_topics\": [\"topic1\", \"topic2\", \"topic3\"],\n";
        $prompt .= "  \"reading_time\": \"estimated reading time in minutes\",\n";
        $prompt .= "  \"difficulty_level\": \"beginner|intermediate|advanced\",\n";
        $prompt .= "  \"content_type\": \"guide|tutorial|overview|analysis\",\n";
        $prompt .= "  \"tags\": [\"tag1\", \"tag2\", \"tag3\"]\n";
        $prompt .= "}\n";
        
        return $prompt;
    }

    /**
     * Get component-specific requirements for AI prompt
     *
     * @return string Component requirements
     */
    protected function get_component_requirements() {
        return "- Create informative, well-structured content that directly addresses the search query\n" .
               "- Write in a clear, engaging style appropriate for the target reading level\n" .
               "- Include practical information and actionable insights\n" .
               "- Structure content with clear introduction, main points, and conclusion\n" .
               "- Use relevant keywords naturally throughout the content\n" .
               "- Ensure content is scannable with clear key points\n" .
               "- Match the tone to user intent and preferences\n" .
               "- Provide value that goes beyond basic search results\n" .
               "- Include related topics for further exploration";
    }

    /**
     * Get maximum tokens for article generation
     *
     * @return int Maximum tokens
     */
    protected function get_max_tokens() {
        return 1200; // Articles need more tokens for detailed content
    }

    /**
     * Process AI response into component content
     *
     * @param array $ai_response Raw AI response
     * @param array $generation_context Generation context
     * @return array Processed component content
     */
    protected function process_ai_response($ai_response, $generation_context) {
        $content_text = $ai_response['content'] ?? '';
        
        // Try to extract JSON from response
        $json_content = $this->extract_json_from_response($content_text);
        
        if (!$json_content) {
            // Fallback: parse text response manually
            $json_content = $this->parse_text_response($content_text, $generation_context);
        }
        
        // Validate and clean the content
        $processed_content = $this->validate_and_clean_article_content($json_content, $generation_context);
        
        // Enhance with discovery results
        $processed_content = $this->enhance_with_discovery_results($processed_content, $generation_context);
        
        return $processed_content;
    }

    /**
     * Extract JSON from AI response
     *
     * @param string $response_text AI response text
     * @return array|null Parsed JSON content or null if failed
     */
    private function extract_json_from_response($response_text) {
        // Look for JSON block in response
        if (preg_match('/\{[\s\S]*\}/', $response_text, $matches)) {
            $json_text = $matches[0];
            $decoded = json_decode($json_text, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        return null;
    }

    /**
     * Parse text response manually when JSON extraction fails
     *
     * @param string $response_text AI response text
     * @param array $generation_context Generation context
     * @return array Parsed content
     */
    private function parse_text_response($response_text, $generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        
        // Split into paragraphs
        $paragraphs = array_filter(explode("\n\n", $response_text));
        
        $content = [
            'title' => '',
            'summary' => '',
            'introduction' => '',
            'main_content' => '',
            'key_points' => [],
            'related_topics' => [],
            'reading_time' => '3',
            'difficulty_level' => 'intermediate',
            'content_type' => 'overview',
            'tags' => []
        ];
        
        // Try to identify title (first line or heading)
        if (!empty($paragraphs)) {
            $first_paragraph = trim($paragraphs[0]);
            if (strlen($first_paragraph) < 100) {
                $content['title'] = $first_paragraph;
                array_shift($paragraphs);
            }
        }
        
        // Process remaining paragraphs
        if (!empty($paragraphs)) {
            $content['introduction'] = trim($paragraphs[0]);
            
            if (count($paragraphs) > 1) {
                $content['main_content'] = implode("\n\n", array_slice($paragraphs, 1));
            }
        }
        
        // Extract key points from content
        $content['key_points'] = $this->extract_key_points_from_text($content['main_content']);
        
        // Fallback values
        if (empty($content['title'])) {
            $content['title'] = 'Understanding ' . ucwords($search_query);
        }
        
        if (empty($content['introduction'])) {
            $content['introduction'] = 'Learn about ' . $search_query . ' with this comprehensive guide.';
        }
        
        return $content;
    }

    /**
     * Validate and clean article content
     *
     * @param array $content Raw content
     * @param array $generation_context Generation context
     * @return array Validated and cleaned content
     */
    private function validate_and_clean_article_content($content, $generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        
        // Ensure all required fields exist
        $default_content = [
            'title' => 'Understanding ' . ucwords($search_query),
            'summary' => 'A comprehensive guide to ' . $search_query,
            'introduction' => 'Learn about ' . $search_query . ' with this detailed overview.',
            'main_content' => 'This guide covers the essential aspects of ' . $search_query . ' that you need to know.',
            'key_points' => [],
            'related_topics' => [],
            'reading_time' => '3',
            'difficulty_level' => 'intermediate',
            'content_type' => 'overview',
            'tags' => []
        ];
        
        $content = array_merge($default_content, $content);
        
        // Validate and truncate text fields
        $content['title'] = $this->validate_text_field($content['title'], 80, $default_content['title']);
        $content['summary'] = $this->validate_text_field($content['summary'], 150, $default_content['summary']);
        $content['introduction'] = $this->validate_text_field($content['introduction'], 300, $default_content['introduction']);
        $content['main_content'] = $this->validate_text_field($content['main_content'], 800, $default_content['main_content']);
        
        // Validate arrays
        $content['key_points'] = $this->validate_array_field($content['key_points'], 5);
        $content['related_topics'] = $this->validate_array_field($content['related_topics'], 5);
        $content['tags'] = $this->validate_array_field($content['tags'], 8);
        
        // Validate enum fields
        $valid_difficulty_levels = ['beginner', 'intermediate', 'advanced'];
        if (!in_array($content['difficulty_level'], $valid_difficulty_levels)) {
            $content['difficulty_level'] = 'intermediate';
        }
        
        $valid_content_types = ['guide', 'tutorial', 'overview', 'analysis'];
        if (!in_array($content['content_type'], $valid_content_types)) {
            $content['content_type'] = 'overview';
        }
        
        // Validate reading time
        if (!is_numeric($content['reading_time']) || $content['reading_time'] < 1) {
            $content['reading_time'] = '3';
        }
        
        // Add search query to tags if not present
        $search_words = explode(' ', strtolower($search_query));
        foreach ($search_words as $word) {
            if (strlen($word) > 2 && !in_array($word, $content['tags'])) {
                $content['tags'][] = $word;
            }
        }
        
        return $content;
    }

    /**
     * Validate text field length and content
     *
     * @param string $text Text to validate
     * @param int $max_length Maximum allowed length
     * @param string $fallback Fallback text if validation fails
     * @return string Validated text
     */
    private function validate_text_field($text, $max_length, $fallback) {
        if (empty($text) || !is_string($text)) {
            return $fallback;
        }
        
        $text = trim(strip_tags($text));
        
        if (strlen($text) > $max_length) {
            $text = substr($text, 0, $max_length - 3) . '...';
        }
        
        return $text;
    }

    /**
     * Validate array field
     *
     * @param mixed $array Array to validate
     * @param int $max_items Maximum number of items
     * @return array Validated array
     */
    private function validate_array_field($array, $max_items) {
        if (!is_array($array)) {
            return [];
        }
        
        // Filter out empty values and limit items
        $filtered = array_filter($array, function($item) {
            return !empty($item) && is_string($item);
        });
        
        return array_slice($filtered, 0, $max_items);
    }

    /**
     * Enhance content with discovery results
     *
     * @param array $content Processed content
     * @param array $generation_context Generation context
     * @return array Enhanced content
     */
    private function enhance_with_discovery_results($content, $generation_context) {
        $content_sources = $generation_context['content_sources'] ?? [];
        
        if (empty($content_sources)) {
            return $content;
        }
        
        // Add source references if available
        $content['source_references'] = $this->create_source_references($content_sources);
        
        // Enhance related topics with discovered content
        $discovered_topics = $this->extract_topics_from_sources($content_sources);
        $content['related_topics'] = array_unique(array_merge($content['related_topics'], $discovered_topics));
        $content['related_topics'] = array_slice($content['related_topics'], 0, 5);
        
        return $content;
    }

    /**
     * Apply interest-based personalization to article content
     *
     * @param array $content Content to personalize
     * @param array $user_interests User interests
     * @return array Personalized content
     */
    protected function apply_interest_personalization($content, $user_interests) {
        if (empty($user_interests)) {
            return $content;
        }
        
        // Get top interests
        arsort($user_interests);
        $top_interests = array_slice(array_keys($user_interests), 0, 3);
        
        // Enhance tags with user interests
        foreach ($top_interests as $interest) {
            if (!in_array($interest, $content['tags'])) {
                $content['tags'][] = $interest;
            }
        }
        
        // Adjust content type based on interests
        $content['content_type'] = $this->personalize_content_type($content['content_type'], $top_interests);
        
        return $content;
    }

    /**
     * Apply tone personalization to article content
     *
     * @param array $content Content to personalize
     * @param array $personalization_context Personalization context
     * @return array Personalized content
     */
    protected function apply_tone_personalization($content, $personalization_context) {
        $tone = $personalization_context['tone_preference'] ?? 'informative';
        $complexity = $personalization_context['complexity_level'] ?? 'medium';
        
        // Adjust difficulty level based on complexity preference
        if ($complexity === 'high') {
            $content['difficulty_level'] = 'advanced';
        } elseif ($complexity === 'low') {
            $content['difficulty_level'] = 'beginner';
        }
        
        return $content;
    }

    /**
     * Generate fallback content when AI generation fails
     *
     * @param array $personalization_context Personalization context
     * @param array $discovery_results Discovery results
     * @return array Fallback component content
     */
    protected function generate_fallback_content($personalization_context, $discovery_results) {
        $search_query = $personalization_context['search_query'] ?? 'your topic';
        $intent = $personalization_context['intent_context']['primary_intent'] ?? 'informational';
        
        $fallback_content = [
            'title' => 'Learn About ' . ucwords($search_query),
            'summary' => 'Discover comprehensive information about ' . $search_query,
            'introduction' => 'This guide provides essential information about ' . $search_query . ' to help you understand the key concepts and applications.',
            'main_content' => 'Understanding ' . $search_query . ' is important for making informed decisions. This overview covers the fundamental aspects you should know.',
            'key_points' => [
                'Key concepts and definitions',
                'Practical applications',
                'Important considerations'
            ],
            'related_topics' => $this->extract_topics_from_sources($discovery_results),
            'reading_time' => '3',
            'difficulty_level' => 'intermediate',
            'content_type' => 'overview',
            'tags' => explode(' ', strtolower($search_query))
        ];
        
        // Customize based on intent
        if ($intent === 'educational') {
            $fallback_content['content_type'] = 'tutorial';
            $fallback_content['title'] = 'How to Learn ' . ucwords($search_query);
        } elseif ($intent === 'commercial') {
            $fallback_content['content_type'] = 'guide';
            $fallback_content['title'] = 'Complete Guide to ' . ucwords($search_query);
        }
        
        return $fallback_content;
    }

    /**
     * Get required content fields for article components
     *
     * @return array Required field names
     */
    protected function get_required_content_fields() {
        return ['title', 'summary', 'introduction', 'main_content'];
    }

    /**
     * Helper methods for article-specific functionality
     */

    /**
     * Process content sources from discovery results
     *
     * @param array $discovery_results Discovery results
     * @return array Processed content sources
     */
    private function process_content_sources($discovery_results) {
        $sources = [];
        
        foreach ($discovery_results as $result) {
            $source = [
                'title' => $result['title'] ?? '',
                'url' => $result['url'] ?? '',
                'excerpt' => $result['excerpt'] ?? '',
                'category' => $result['category'] ?? '',
                'relevance_score' => $result['relevance_score'] ?? 0.5
            ];
            
            if (!empty($source['title'])) {
                $sources[] = $source;
            }
        }
        
        // Sort by relevance score
        usort($sources, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return array_slice($sources, 0, 10); // Limit to top 10 sources
    }

    /**
     * Determine article structure based on intent and content
     *
     * @param array $intent_context Intent context
     * @param array $content_sources Content sources
     * @return string Article structure type
     */
    private function determine_article_structure($intent_context, $content_sources) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        $source_count = count($content_sources);
        
        if ($intent === 'educational') {
            return 'tutorial';
        } elseif ($intent === 'commercial') {
            return 'comparison';
        } elseif ($source_count > 5) {
            return 'comprehensive';
        } else {
            return 'summary';
        }
    }

    /**
     * Extract key topics from discovery results and search query
     *
     * @param array $discovery_results Discovery results
     * @param string $search_query Search query
     * @return array Key topics
     */
    private function extract_key_topics($discovery_results, $search_query) {
        $topics = [];
        
        // Add search query words as topics
        $search_words = explode(' ', strtolower($search_query));
        foreach ($search_words as $word) {
            if (strlen($word) > 3) {
                $topics[] = $word;
            }
        }
        
        // Extract topics from discovery results
        foreach ($discovery_results as $result) {
            if (isset($result['category'])) {
                $topics[] = $result['category'];
            }
            
            if (isset($result['tags']) && is_array($result['tags'])) {
                $topics = array_merge($topics, $result['tags']);
            }
        }
        
        return array_slice(array_unique($topics), 0, 8);
    }

    /**
     * Determine reading level based on user interests and intent
     *
     * @param array $user_interests User interests
     * @param array $intent_context Intent context
     * @return string Reading level
     */
    private function determine_reading_level($user_interests, $intent_context) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        // Check for technical interests
        $technical_interests = ['technology', 'programming', 'science', 'engineering'];
        $has_technical_interest = false;
        
        foreach ($technical_interests as $tech_interest) {
            if (isset($user_interests[$tech_interest]) && $user_interests[$tech_interest] > 0.6) {
                $has_technical_interest = true;
                break;
            }
        }
        
        if ($has_technical_interest) {
            return 'advanced';
        } elseif ($intent === 'educational') {
            return 'intermediate';
        } else {
            return 'beginner';
        }
    }

    /**
     * Determine content depth based on discovery results
     *
     * @param array $discovery_results Discovery results
     * @return string Content depth
     */
    private function determine_content_depth($discovery_results) {
        $result_count = count($discovery_results);
        
        if ($result_count > 10) {
            return 'comprehensive';
        } elseif ($result_count > 5) {
            return 'detailed';
        } else {
            return 'overview';
        }
    }

    /**
     * Determine target length based on intent
     *
     * @param array $intent_context Intent context
     * @return string Target length
     */
    private function determine_target_length($intent_context) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        $length_map = [
            'educational' => 'long',
            'informational' => 'medium',
            'commercial' => 'short',
            'navigational' => 'short'
        ];
        
        return $length_map[$intent] ?? 'medium';
    }

    /**
     * Extract key points from text content
     *
     * @param string $text Text content
     * @return array Key points
     */
    private function extract_key_points_from_text($text) {
        $sentences = explode('.', $text);
        $key_points = [];
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 20 && strlen($sentence) < 100) {
                $key_points[] = $sentence;
                
                if (count($key_points) >= 5) {
                    break;
                }
            }
        }
        
        return $key_points;
    }

    /**
     * Create source references from content sources
     *
     * @param array $content_sources Content sources
     * @return array Source references
     */
    private function create_source_references($content_sources) {
        $references = [];
        
        foreach (array_slice($content_sources, 0, 5) as $source) {
            if (!empty($source['title']) && !empty($source['url'])) {
                $references[] = [
                    'title' => $source['title'],
                    'url' => $source['url'],
                    'relevance' => $source['relevance_score'] ?? 0.5
                ];
            }
        }
        
        return $references;
    }

    /**
     * Extract topics from content sources
     *
     * @param array $content_sources Content sources
     * @return array Extracted topics
     */
    private function extract_topics_from_sources($content_sources) {
        $topics = [];
        
        foreach ($content_sources as $source) {
            if (isset($source['category'])) {
                $topics[] = $source['category'];
            }
        }
        
        return array_slice(array_unique($topics), 0, 5);
    }

    /**
     * Personalize content type based on user interests
     *
     * @param string $original_type Original content type
     * @param array $interests User interests
     * @return string Personalized content type
     */
    private function personalize_content_type($original_type, $interests) {
        $type_preferences = [
            'technology' => 'tutorial',
            'business' => 'analysis',
            'education' => 'guide',
            'creative' => 'overview'
        ];
        
        foreach ($interests as $interest) {
            if (isset($type_preferences[$interest])) {
                return $type_preferences[$interest];
            }
        }
        
        return $original_type;
    }
}
