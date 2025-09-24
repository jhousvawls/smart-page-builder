<?php
/**
 * Hero Component Generator
 *
 * Generates personalized hero banner components for search-triggered pages.
 * Creates compelling headlines, descriptions, and call-to-action elements
 * based on user search intent and interest vectors.
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
 * Hero Component Generator Class
 *
 * Specializes in generating hero banner components with personalized
 * headlines, descriptions, and visual elements.
 */
class SPB_Hero_Generator extends SPB_Abstract_Component_Generator {

    /**
     * Get component type identifier
     *
     * @return string Component type identifier
     */
    protected function get_component_type() {
        return 'hero';
    }

    /**
     * Get provider preferences for hero generation
     *
     * Heroes benefit from creative, engaging content - prefer providers
     * that excel at marketing copy and creative writing.
     *
     * @param array $generation_context Generation context
     * @return array Ordered list of preferred providers
     */
    protected function get_provider_preferences($generation_context) {
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        
        // Commercial intent benefits from creative marketing-focused providers
        if ($intent === 'commercial') {
            return ['anthropic', 'openai', 'google'];
        }
        
        // Educational content benefits from structured, clear providers
        if ($intent === 'educational') {
            return ['openai', 'google', 'anthropic'];
        }
        
        // Default preference order
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
        
        // Extract key themes from discovery results
        $content_themes = $this->extract_content_themes($discovery_results);
        
        // Determine hero style based on intent
        $hero_style = $this->determine_hero_style($intent_context, $user_interests);
        
        // Build generation context
        return [
            'search_query' => $search_query,
            'intent_context' => $intent_context,
            'user_interests' => $user_interests,
            'content_themes' => $content_themes,
            'hero_style' => $hero_style,
            'tone_preference' => $personalization_context['tone_preference'] ?? 'professional',
            'complexity_level' => $personalization_context['complexity_level'] ?? 'medium',
            'available_content' => $personalization_context['available_content'] ?? [],
            'target_audience' => $this->determine_target_audience($user_interests, $intent_context),
            'emotional_tone' => $this->determine_emotional_tone($intent_context, $user_interests)
        ];
    }

    /**
     * Build AI prompt for hero generation
     *
     * @param array $generation_context Generation context
     * @return string AI prompt
     */
    protected function build_ai_prompt($generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $hero_style = $generation_context['hero_style'] ?? 'informational';
        $tone = $generation_context['tone_preference'] ?? 'professional';
        $emotional_tone = $generation_context['emotional_tone'] ?? 'neutral';
        
        $prompt = "Create a compelling hero banner for a search-triggered page.\n\n";
        $prompt .= "CONTEXT:\n";
        $prompt .= "- Search Query: \"{$search_query}\"\n";
        $prompt .= "- User Intent: {$intent}\n";
        $prompt .= "- Hero Style: {$hero_style}\n";
        $prompt .= "- Tone: {$tone}\n";
        $prompt .= "- Emotional Tone: {$emotional_tone}\n";
        
        // Add user interests if available
        if (!empty($generation_context['user_interests'])) {
            $interests = array_keys($generation_context['user_interests']);
            $interests_text = implode(', ', array_slice($interests, 0, 5));
            $prompt .= "- User Interests: {$interests_text}\n";
        }
        
        // Add content themes
        if (!empty($generation_context['content_themes'])) {
            $themes_text = implode(', ', $generation_context['content_themes']);
            $prompt .= "- Content Themes: {$themes_text}\n";
        }
        
        $prompt .= "\nREQUIREMENTS:\n";
        $prompt .= $this->get_component_requirements();
        
        $prompt .= "\nOUTPUT FORMAT (JSON):\n";
        $prompt .= "{\n";
        $prompt .= "  \"headline\": \"Compelling main headline (max 60 characters)\",\n";
        $prompt .= "  \"subheadline\": \"Supporting subheadline (max 120 characters)\",\n";
        $prompt .= "  \"description\": \"Detailed description (max 200 characters)\",\n";
        $prompt .= "  \"cta_text\": \"Call-to-action button text (max 25 characters)\",\n";
        $prompt .= "  \"cta_url\": \"#relevant-section\",\n";
        $prompt .= "  \"background_style\": \"gradient|solid|image\",\n";
        $prompt .= "  \"text_alignment\": \"left|center|right\",\n";
        $prompt .= "  \"visual_elements\": [\"element1\", \"element2\"],\n";
        $prompt .= "  \"keywords\": [\"keyword1\", \"keyword2\", \"keyword3\"]\n";
        $prompt .= "}\n";
        
        return $prompt;
    }

    /**
     * Get component-specific requirements for AI prompt
     *
     * @return string Component requirements
     */
    protected function get_component_requirements() {
        return "- Create an attention-grabbing headline that directly addresses the search query\n" .
               "- Write a compelling subheadline that expands on the main message\n" .
               "- Include a clear, actionable call-to-action\n" .
               "- Ensure all text is concise and scannable\n" .
               "- Match the emotional tone to user intent\n" .
               "- Include relevant keywords naturally\n" .
               "- Design for mobile-first responsive layout\n" .
               "- Create urgency or value proposition when appropriate";
    }

    /**
     * Get maximum tokens for hero generation
     *
     * @return int Maximum tokens
     */
    protected function get_max_tokens() {
        return 800; // Heroes need more tokens for detailed JSON structure
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
        $processed_content = $this->validate_and_clean_hero_content($json_content, $generation_context);
        
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
        
        // Extract lines and try to identify components
        $lines = explode("\n", $response_text);
        $content = [
            'headline' => '',
            'subheadline' => '',
            'description' => '',
            'cta_text' => 'Learn More',
            'cta_url' => '#content',
            'background_style' => 'gradient',
            'text_alignment' => 'center',
            'visual_elements' => [],
            'keywords' => []
        ];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Try to identify headline (usually first substantial line)
            if (empty($content['headline']) && strlen($line) > 10 && strlen($line) < 80) {
                $content['headline'] = $line;
                continue;
            }
            
            // Try to identify description (longer lines)
            if (empty($content['description']) && strlen($line) > 50 && strlen($line) < 250) {
                $content['description'] = $line;
                continue;
            }
            
            // Try to identify subheadline
            if (empty($content['subheadline']) && strlen($line) > 20 && strlen($line) < 150) {
                $content['subheadline'] = $line;
            }
        }
        
        // Fallback values if parsing failed
        if (empty($content['headline'])) {
            $content['headline'] = 'Discover ' . ucwords($search_query);
        }
        
        if (empty($content['description'])) {
            $content['description'] = 'Find exactly what you\'re looking for with our comprehensive resources and expert guidance.';
        }
        
        return $content;
    }

    /**
     * Validate and clean hero content
     *
     * @param array $content Raw content
     * @param array $generation_context Generation context
     * @return array Validated and cleaned content
     */
    private function validate_and_clean_hero_content($content, $generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        
        // Ensure all required fields exist
        $default_content = [
            'headline' => 'Discover ' . ucwords($search_query),
            'subheadline' => 'Find exactly what you need',
            'description' => 'Explore our comprehensive resources and expert guidance.',
            'cta_text' => 'Learn More',
            'cta_url' => '#content',
            'background_style' => 'gradient',
            'text_alignment' => 'center',
            'visual_elements' => [],
            'keywords' => []
        ];
        
        $content = array_merge($default_content, $content);
        
        // Validate and truncate text fields
        $content['headline'] = $this->validate_text_field($content['headline'], 60, 'Discover ' . ucwords($search_query));
        $content['subheadline'] = $this->validate_text_field($content['subheadline'], 120, 'Find exactly what you need');
        $content['description'] = $this->validate_text_field($content['description'], 200, 'Explore our comprehensive resources and expert guidance.');
        $content['cta_text'] = $this->validate_text_field($content['cta_text'], 25, 'Learn More');
        
        // Validate style options
        $valid_backgrounds = ['gradient', 'solid', 'image'];
        if (!in_array($content['background_style'], $valid_backgrounds)) {
            $content['background_style'] = 'gradient';
        }
        
        $valid_alignments = ['left', 'center', 'right'];
        if (!in_array($content['text_alignment'], $valid_alignments)) {
            $content['text_alignment'] = 'center';
        }
        
        // Ensure arrays are arrays
        if (!is_array($content['visual_elements'])) {
            $content['visual_elements'] = [];
        }
        
        if (!is_array($content['keywords'])) {
            $content['keywords'] = [];
        }
        
        // Add search query to keywords if not present
        $search_words = explode(' ', strtolower($search_query));
        foreach ($search_words as $word) {
            if (strlen($word) > 2 && !in_array($word, $content['keywords'])) {
                $content['keywords'][] = $word;
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
     * Apply interest-based personalization to hero content
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
        
        // Enhance keywords with user interests
        foreach ($top_interests as $interest) {
            if (!in_array($interest, $content['keywords'])) {
                $content['keywords'][] = $interest;
            }
        }
        
        // Adjust visual elements based on interests
        $content['visual_elements'] = $this->suggest_visual_elements($top_interests);
        
        return $content;
    }

    /**
     * Apply tone personalization to hero content
     *
     * @param array $content Content to personalize
     * @param array $personalization_context Personalization context
     * @return array Personalized content
     */
    protected function apply_tone_personalization($content, $personalization_context) {
        $tone = $personalization_context['tone_preference'] ?? 'professional';
        $intent = $personalization_context['intent_context']['primary_intent'] ?? 'informational';
        
        // Adjust CTA text based on tone and intent
        $content['cta_text'] = $this->personalize_cta_text($content['cta_text'], $tone, $intent);
        
        // Adjust text alignment based on tone
        if ($tone === 'casual' || $tone === 'friendly') {
            $content['text_alignment'] = 'left';
        } elseif ($tone === 'professional' || $tone === 'authoritative') {
            $content['text_alignment'] = 'center';
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
        $search_query = $personalization_context['search_query'] ?? 'your search';
        $intent = $personalization_context['intent_context']['primary_intent'] ?? 'informational';
        
        $fallback_content = [
            'headline' => 'Find What You\'re Looking For',
            'subheadline' => 'Discover relevant content for "' . ucwords($search_query) . '"',
            'description' => 'We\'ve found some great resources that match your search. Explore the content below to find exactly what you need.',
            'cta_text' => 'Explore Results',
            'cta_url' => '#content',
            'background_style' => 'gradient',
            'text_alignment' => 'center',
            'visual_elements' => ['search-icon', 'content-grid'],
            'keywords' => explode(' ', strtolower($search_query))
        ];
        
        // Customize based on intent
        if ($intent === 'commercial') {
            $fallback_content['headline'] = 'Find the Perfect Solution';
            $fallback_content['cta_text'] = 'Shop Now';
        } elseif ($intent === 'educational') {
            $fallback_content['headline'] = 'Learn About ' . ucwords($search_query);
            $fallback_content['cta_text'] = 'Start Learning';
        }
        
        return $fallback_content;
    }

    /**
     * Get required content fields for hero components
     *
     * @return array Required field names
     */
    protected function get_required_content_fields() {
        return ['headline', 'subheadline', 'description', 'cta_text'];
    }

    /**
     * Helper methods for hero-specific functionality
     */

    /**
     * Extract content themes from discovery results
     *
     * @param array $discovery_results Discovery results
     * @return array Content themes
     */
    private function extract_content_themes($discovery_results) {
        $themes = [];
        
        foreach ($discovery_results as $result) {
            if (isset($result['category'])) {
                $themes[] = $result['category'];
            }
            
            if (isset($result['tags']) && is_array($result['tags'])) {
                $themes = array_merge($themes, $result['tags']);
            }
        }
        
        // Return unique themes, limited to top 5
        return array_slice(array_unique($themes), 0, 5);
    }

    /**
     * Determine hero style based on intent and interests
     *
     * @param array $intent_context Intent context
     * @param array $user_interests User interests
     * @return string Hero style
     */
    private function determine_hero_style($intent_context, $user_interests) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        $style_map = [
            'commercial' => 'conversion-focused',
            'informational' => 'content-focused',
            'educational' => 'learning-focused',
            'navigational' => 'navigation-focused'
        ];
        
        return $style_map[$intent] ?? 'content-focused';
    }

    /**
     * Determine target audience based on interests and intent
     *
     * @param array $user_interests User interests
     * @param array $intent_context Intent context
     * @return string Target audience
     */
    private function determine_target_audience($user_interests, $intent_context) {
        // Simple audience determination based on interests
        if (isset($user_interests['technology']) && $user_interests['technology'] > 0.7) {
            return 'tech-savvy';
        }
        
        if (isset($user_interests['business']) && $user_interests['business'] > 0.7) {
            return 'business-professional';
        }
        
        return 'general';
    }

    /**
     * Determine emotional tone based on intent and interests
     *
     * @param array $intent_context Intent context
     * @param array $user_interests User interests
     * @return string Emotional tone
     */
    private function determine_emotional_tone($intent_context, $user_interests) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        $tone_map = [
            'commercial' => 'exciting',
            'informational' => 'helpful',
            'educational' => 'encouraging',
            'navigational' => 'efficient'
        ];
        
        return $tone_map[$intent] ?? 'neutral';
    }

    /**
     * Suggest visual elements based on user interests
     *
     * @param array $interests Top user interests
     * @return array Visual element suggestions
     */
    private function suggest_visual_elements($interests) {
        $element_map = [
            'photography' => ['camera-icon', 'gallery-grid', 'image-showcase'],
            'technology' => ['tech-icons', 'code-blocks', 'device-mockups'],
            'business' => ['chart-icons', 'professional-imagery', 'growth-graphics'],
            'travel' => ['map-elements', 'destination-photos', 'journey-icons'],
            'education' => ['book-icons', 'learning-graphics', 'progress-indicators']
        ];
        
        $suggested_elements = [];
        
        foreach ($interests as $interest) {
            if (isset($element_map[$interest])) {
                $suggested_elements = array_merge($suggested_elements, $element_map[$interest]);
            }
        }
        
        return array_slice(array_unique($suggested_elements), 0, 3);
    }

    /**
     * Personalize CTA text based on tone and intent
     *
     * @param string $original_cta Original CTA text
     * @param string $tone Tone preference
     * @param string $intent User intent
     * @return string Personalized CTA text
     */
    private function personalize_cta_text($original_cta, $tone, $intent) {
        $cta_options = [
            'commercial' => [
                'professional' => ['Shop Now', 'Get Started', 'Learn More'],
                'casual' => ['Check It Out', 'See What\'s New', 'Explore'],
                'friendly' => ['Find Your Perfect Match', 'Discover More', 'See Options']
            ],
            'informational' => [
                'professional' => ['Read More', 'Learn More', 'Explore'],
                'casual' => ['Check It Out', 'See More', 'Dive In'],
                'friendly' => ['Discover More', 'Find Out More', 'Explore Together']
            ],
            'educational' => [
                'professional' => ['Start Learning', 'Begin Course', 'Access Resources'],
                'casual' => ['Let\'s Learn', 'Get Started', 'Jump In'],
                'friendly' => ['Start Your Journey', 'Begin Learning', 'Explore Together']
            ]
        ];
        
        if (isset($cta_options[$intent][$tone])) {
            $options = $cta_options[$intent][$tone];
            return $options[array_rand($options)];
        }
        
        return $original_cta;
    }
}
