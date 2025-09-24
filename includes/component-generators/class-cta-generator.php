<?php
/**
 * CTA Component Generator
 *
 * Generates personalized call-to-action components for search-triggered pages.
 * Creates compelling CTAs with optimized messaging, buttons, and conversion elements
 * based on user search intent and behavioral patterns.
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
 * CTA Component Generator Class
 *
 * Specializes in generating call-to-action components with personalized
 * messaging, button text, and conversion optimization.
 */
class SPB_CTA_Generator extends SPB_Abstract_Component_Generator {

    /**
     * Get component type identifier
     *
     * @return string Component type identifier
     */
    protected function get_component_type() {
        return 'cta';
    }

    /**
     * Get provider preferences for CTA generation
     *
     * CTAs benefit from providers that excel at persuasive, conversion-focused
     * copy and marketing language.
     *
     * @param array $generation_context Generation context
     * @return array Ordered list of preferred providers
     */
    protected function get_provider_preferences($generation_context) {
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $emotional_tone = $generation_context['emotional_tone'] ?? 'neutral';
        
        // Commercial intent benefits from persuasive, marketing-focused providers
        if ($intent === 'commercial') {
            return ['anthropic', 'openai', 'google'];
        }
        
        // Emotional/exciting tone benefits from creative providers
        if ($emotional_tone === 'exciting' || $emotional_tone === 'urgent') {
            return ['anthropic', 'openai', 'google'];
        }
        
        // Default preference for conversion-focused content
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
        
        // Determine CTA strategy based on intent and user behavior
        $cta_strategy = $this->determine_cta_strategy($intent_context, $user_interests);
        
        // Analyze conversion opportunities from discovery results
        $conversion_opportunities = $this->analyze_conversion_opportunities($discovery_results);
        
        // Determine urgency level and value proposition
        $urgency_level = $this->determine_urgency_level($intent_context, $user_interests);
        $value_proposition = $this->extract_value_proposition($discovery_results, $search_query);
        
        // Build generation context
        return [
            'search_query' => $search_query,
            'intent_context' => $intent_context,
            'user_interests' => $user_interests,
            'cta_strategy' => $cta_strategy,
            'conversion_opportunities' => $conversion_opportunities,
            'urgency_level' => $urgency_level,
            'value_proposition' => $value_proposition,
            'tone_preference' => $personalization_context['tone_preference'] ?? 'professional',
            'emotional_tone' => $this->determine_emotional_tone($intent_context, $user_interests),
            'target_action' => $this->determine_target_action($intent_context),
            'user_stage' => $this->determine_user_stage($user_interests, $intent_context),
            'competitive_context' => $this->analyze_competitive_context($discovery_results)
        ];
    }

    /**
     * Build AI prompt for CTA generation
     *
     * @param array $generation_context Generation context
     * @return string AI prompt
     */
    protected function build_ai_prompt($generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $strategy = $generation_context['cta_strategy'] ?? 'informational';
        $urgency = $generation_context['urgency_level'] ?? 'medium';
        $target_action = $generation_context['target_action'] ?? 'learn_more';
        $user_stage = $generation_context['user_stage'] ?? 'awareness';
        $emotional_tone = $generation_context['emotional_tone'] ?? 'helpful';
        
        $prompt = "Create a compelling call-to-action component for a search-triggered page.\n\n";
        $prompt .= "CONTEXT:\n";
        $prompt .= "- Search Query: \"{$search_query}\"\n";
        $prompt .= "- User Intent: {$intent}\n";
        $prompt .= "- CTA Strategy: {$strategy}\n";
        $prompt .= "- Urgency Level: {$urgency}\n";
        $prompt .= "- Target Action: {$target_action}\n";
        $prompt .= "- User Stage: {$user_stage}\n";
        $prompt .= "- Emotional Tone: {$emotional_tone}\n";
        
        // Add user interests if available
        if (!empty($generation_context['user_interests'])) {
            $interests = array_keys($generation_context['user_interests']);
            $interests_text = implode(', ', array_slice($interests, 0, 5));
            $prompt .= "- User Interests: {$interests_text}\n";
        }
        
        // Add value proposition
        if (!empty($generation_context['value_proposition'])) {
            $prompt .= "- Value Proposition: {$generation_context['value_proposition']}\n";
        }
        
        // Add conversion opportunities
        if (!empty($generation_context['conversion_opportunities'])) {
            $opportunities_text = implode(', ', $generation_context['conversion_opportunities']);
            $prompt .= "- Conversion Opportunities: {$opportunities_text}\n";
        }
        
        $prompt .= "\nREQUIREMENTS:\n";
        $prompt .= $this->get_component_requirements();
        
        $prompt .= "\nOUTPUT FORMAT (JSON):\n";
        $prompt .= "{\n";
        $prompt .= "  \"headline\": \"CTA headline (max 50 characters)\",\n";
        $prompt .= "  \"description\": \"Supporting description (max 120 characters)\",\n";
        $prompt .= "  \"primary_button\": {\n";
        $prompt .= "    \"text\": \"Primary button text (max 20 characters)\",\n";
        $prompt .= "    \"url\": \"#target-action\",\n";
        $prompt .= "    \"style\": \"primary|secondary|outline\"\n";
        $prompt .= "  },\n";
        $prompt .= "  \"secondary_button\": {\n";
        $prompt .= "    \"text\": \"Secondary button text (max 20 characters)\",\n";
        $prompt .= "    \"url\": \"#alternative-action\",\n";
        $prompt .= "    \"style\": \"secondary|outline|text\"\n";
        $prompt .= "  },\n";
        $prompt .= "  \"urgency_indicator\": \"Limited time|Popular choice|Recommended|null\",\n";
        $prompt .= "  \"value_highlights\": [\"benefit1\", \"benefit2\", \"benefit3\"],\n";
        $prompt .= "  \"social_proof\": \"testimonial or statistic (max 80 characters)\",\n";
        $prompt .= "  \"layout_style\": \"centered|split|banner|sidebar\",\n";
        $prompt .= "  \"color_scheme\": \"primary|accent|neutral|custom\",\n";
        $prompt .= "  \"conversion_goal\": \"signup|purchase|download|contact|learn\"\n";
        $prompt .= "}\n";
        
        return $prompt;
    }

    /**
     * Get component-specific requirements for AI prompt
     *
     * @return string Component requirements
     */
    protected function get_component_requirements() {
        return "- Create compelling, action-oriented copy that motivates immediate response\n" .
               "- Use clear, specific language that communicates value proposition\n" .
               "- Include strong action verbs in button text\n" .
               "- Match urgency level to user intent and context\n" .
               "- Provide clear benefit statements that address user needs\n" .
               "- Use social proof when appropriate to build trust\n" .
               "- Ensure mobile-friendly button sizes and layout\n" .
               "- Create hierarchy with primary and secondary actions\n" .
               "- Optimize for conversion while maintaining authenticity";
    }

    /**
     * Get maximum tokens for CTA generation
     *
     * @return int Maximum tokens
     */
    protected function get_max_tokens() {
        return 600; // CTAs need focused, concise content
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
        $processed_content = $this->validate_and_clean_cta_content($json_content, $generation_context);
        
        // Optimize for conversion
        $processed_content = $this->optimize_for_conversion($processed_content, $generation_context);
        
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
        $target_action = $generation_context['target_action'] ?? 'learn_more';
        
        // Extract lines and try to identify components
        $lines = array_filter(explode("\n", $response_text));
        
        $content = [
            'headline' => '',
            'description' => '',
            'primary_button' => [
                'text' => 'Get Started',
                'url' => '#action',
                'style' => 'primary'
            ],
            'secondary_button' => [
                'text' => 'Learn More',
                'url' => '#info',
                'style' => 'secondary'
            ],
            'urgency_indicator' => null,
            'value_highlights' => [],
            'social_proof' => '',
            'layout_style' => 'centered',
            'color_scheme' => 'primary',
            'conversion_goal' => $target_action
        ];
        
        // Try to identify headline and description
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Try to identify headline (shorter, punchy lines)
            if (empty($content['headline']) && strlen($line) > 10 && strlen($line) < 60) {
                $content['headline'] = $line;
                continue;
            }
            
            // Try to identify description (longer explanatory lines)
            if (empty($content['description']) && strlen($line) > 30 && strlen($line) < 150) {
                $content['description'] = $line;
                continue;
            }
        }
        
        // Fallback values
        if (empty($content['headline'])) {
            $content['headline'] = 'Ready to Get Started?';
        }
        
        if (empty($content['description'])) {
            $content['description'] = 'Take the next step and discover what ' . $search_query . ' can do for you.';
        }
        
        return $content;
    }

    /**
     * Validate and clean CTA content
     *
     * @param array $content Raw content
     * @param array $generation_context Generation context
     * @return array Validated and cleaned content
     */
    private function validate_and_clean_cta_content($content, $generation_context) {
        $search_query = $generation_context['search_query'] ?? '';
        $target_action = $generation_context['target_action'] ?? 'learn_more';
        
        // Ensure all required fields exist
        $default_content = [
            'headline' => 'Ready to Get Started?',
            'description' => 'Take the next step with ' . $search_query,
            'primary_button' => [
                'text' => 'Get Started',
                'url' => '#action',
                'style' => 'primary'
            ],
            'secondary_button' => [
                'text' => 'Learn More',
                'url' => '#info',
                'style' => 'secondary'
            ],
            'urgency_indicator' => null,
            'value_highlights' => [],
            'social_proof' => '',
            'layout_style' => 'centered',
            'color_scheme' => 'primary',
            'conversion_goal' => $target_action
        ];
        
        $content = array_merge($default_content, $content);
        
        // Validate and truncate text fields
        $content['headline'] = $this->validate_text_field($content['headline'], 50, $default_content['headline']);
        $content['description'] = $this->validate_text_field($content['description'], 120, $default_content['description']);
        $content['social_proof'] = $this->validate_text_field($content['social_proof'], 80, '');
        
        // Validate button structures
        $content['primary_button'] = $this->validate_button_structure($content['primary_button'], $default_content['primary_button']);
        $content['secondary_button'] = $this->validate_button_structure($content['secondary_button'], $default_content['secondary_button']);
        
        // Validate arrays
        $content['value_highlights'] = $this->validate_array_field($content['value_highlights'], 4);
        
        // Validate enum fields
        $valid_urgency_indicators = ['Limited time', 'Popular choice', 'Recommended', null];
        if (!in_array($content['urgency_indicator'], $valid_urgency_indicators)) {
            $content['urgency_indicator'] = null;
        }
        
        $valid_layout_styles = ['centered', 'split', 'banner', 'sidebar'];
        if (!in_array($content['layout_style'], $valid_layout_styles)) {
            $content['layout_style'] = 'centered';
        }
        
        $valid_color_schemes = ['primary', 'accent', 'neutral', 'custom'];
        if (!in_array($content['color_scheme'], $valid_color_schemes)) {
            $content['color_scheme'] = 'primary';
        }
        
        $valid_conversion_goals = ['signup', 'purchase', 'download', 'contact', 'learn'];
        if (!in_array($content['conversion_goal'], $valid_conversion_goals)) {
            $content['conversion_goal'] = 'learn';
        }
        
        return $content;
    }

    /**
     * Validate button structure
     *
     * @param mixed $button Button data to validate
     * @param array $default_button Default button structure
     * @return array Validated button structure
     */
    private function validate_button_structure($button, $default_button) {
        if (!is_array($button)) {
            return $default_button;
        }
        
        $button = array_merge($default_button, $button);
        
        // Validate button text
        $button['text'] = $this->validate_text_field($button['text'], 20, $default_button['text']);
        
        // Validate button style
        $valid_styles = ['primary', 'secondary', 'outline', 'text'];
        if (!in_array($button['style'], $valid_styles)) {
            $button['style'] = $default_button['style'];
        }
        
        // Ensure URL is present
        if (empty($button['url'])) {
            $button['url'] = $default_button['url'];
        }
        
        return $button;
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
     * Optimize CTA for conversion
     *
     * @param array $content CTA content
     * @param array $generation_context Generation context
     * @return array Optimized content
     */
    private function optimize_for_conversion($content, $generation_context) {
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        $urgency_level = $generation_context['urgency_level'] ?? 'medium';
        
        // Add urgency indicator for commercial intent
        if ($intent === 'commercial' && $urgency_level === 'high' && empty($content['urgency_indicator'])) {
            $content['urgency_indicator'] = 'Popular choice';
        }
        
        // Enhance value highlights if empty
        if (empty($content['value_highlights'])) {
            $content['value_highlights'] = $this->generate_default_value_highlights($generation_context);
        }
        
        // Add social proof for commercial intent
        if ($intent === 'commercial' && empty($content['social_proof'])) {
            $content['social_proof'] = 'Join thousands of satisfied customers';
        }
        
        return $content;
    }

    /**
     * Apply interest-based personalization to CTA content
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
        
        // Personalize button text based on interests
        $content['primary_button']['text'] = $this->personalize_button_text(
            $content['primary_button']['text'],
            $top_interests,
            'primary'
        );
        
        // Personalize value highlights
        $content['value_highlights'] = $this->personalize_value_highlights(
            $content['value_highlights'],
            $top_interests
        );
        
        return $content;
    }

    /**
     * Apply tone personalization to CTA content
     *
     * @param array $content Content to personalize
     * @param array $personalization_context Personalization context
     * @return array Personalized content
     */
    protected function apply_tone_personalization($content, $personalization_context) {
        $tone = $personalization_context['tone_preference'] ?? 'professional';
        $emotional_tone = $personalization_context['emotional_tone'] ?? 'helpful';
        
        // Adjust urgency indicator based on tone
        if ($tone === 'casual' && $content['urgency_indicator'] === 'Limited time') {
            $content['urgency_indicator'] = 'Popular choice';
        }
        
        // Adjust color scheme based on emotional tone
        if ($emotional_tone === 'exciting') {
            $content['color_scheme'] = 'accent';
        } elseif ($emotional_tone === 'professional') {
            $content['color_scheme'] = 'primary';
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
            'headline' => 'Ready to Learn More?',
            'description' => 'Get the information you need about ' . $search_query,
            'primary_button' => [
                'text' => 'Get Started',
                'url' => '#content',
                'style' => 'primary'
            ],
            'secondary_button' => [
                'text' => 'Learn More',
                'url' => '#info',
                'style' => 'secondary'
            ],
            'urgency_indicator' => null,
            'value_highlights' => [
                'Expert guidance',
                'Comprehensive resources',
                'Trusted information'
            ],
            'social_proof' => '',
            'layout_style' => 'centered',
            'color_scheme' => 'primary',
            'conversion_goal' => 'learn'
        ];
        
        // Customize based on intent
        if ($intent === 'commercial') {
            $fallback_content['headline'] = 'Find Your Perfect Solution';
            $fallback_content['primary_button']['text'] = 'Shop Now';
            $fallback_content['conversion_goal'] = 'purchase';
            $fallback_content['urgency_indicator'] = 'Popular choice';
        } elseif ($intent === 'educational') {
            $fallback_content['headline'] = 'Start Learning Today';
            $fallback_content['primary_button']['text'] = 'Begin Course';
            $fallback_content['conversion_goal'] = 'signup';
        }
        
        return $fallback_content;
    }

    /**
     * Get required content fields for CTA components
     *
     * @return array Required field names
     */
    protected function get_required_content_fields() {
        return ['headline', 'description', 'primary_button'];
    }

    /**
     * Helper methods for CTA-specific functionality
     */

    /**
     * Determine CTA strategy based on intent and interests
     *
     * @param array $intent_context Intent context
     * @param array $user_interests User interests
     * @return string CTA strategy
     */
    private function determine_cta_strategy($intent_context, $user_interests) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        $strategy_map = [
            'commercial' => 'conversion',
            'educational' => 'engagement',
            'informational' => 'discovery',
            'navigational' => 'guidance'
        ];
        
        return $strategy_map[$intent] ?? 'discovery';
    }

    /**
     * Analyze conversion opportunities from discovery results
     *
     * @param array $discovery_results Discovery results
     * @return array Conversion opportunities
     */
    private function analyze_conversion_opportunities($discovery_results) {
        $opportunities = [];
        
        foreach ($discovery_results as $result) {
            if (isset($result['category'])) {
                $category = strtolower($result['category']);
                
                if (strpos($category, 'product') !== false || strpos($category, 'service') !== false) {
                    $opportunities[] = 'purchase';
                } elseif (strpos($category, 'course') !== false || strpos($category, 'tutorial') !== false) {
                    $opportunities[] = 'signup';
                } elseif (strpos($category, 'download') !== false) {
                    $opportunities[] = 'download';
                }
            }
        }
        
        return array_unique($opportunities);
    }

    /**
     * Determine urgency level based on intent and interests
     *
     * @param array $intent_context Intent context
     * @param array $user_interests User interests
     * @return string Urgency level
     */
    private function determine_urgency_level($intent_context, $user_interests) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        if ($intent === 'commercial') {
            return 'high';
        } elseif ($intent === 'educational') {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Extract value proposition from discovery results and search query
     *
     * @param array $discovery_results Discovery results
     * @param string $search_query Search query
     * @return string Value proposition
     */
    private function extract_value_proposition($discovery_results, $search_query) {
        // Simple value proposition based on search query
        $benefits = [
            'learn' => 'comprehensive learning resources',
            'find' => 'exactly what you\'re looking for',
            'get' => 'immediate access to solutions',
            'buy' => 'the best products and deals',
            'compare' => 'detailed comparisons and reviews'
        ];
        
        $query_lower = strtolower($search_query);
        
        foreach ($benefits as $keyword => $benefit) {
            if (strpos($query_lower, $keyword) !== false) {
                return $benefit;
            }
        }
        
        return 'valuable insights and information';
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
            'educational' => 'encouraging',
            'informational' => 'helpful',
            'navigational' => 'efficient'
        ];
        
        return $tone_map[$intent] ?? 'helpful';
    }

    /**
     * Determine target action based on intent
     *
     * @param array $intent_context Intent context
     * @return string Target action
     */
    private function determine_target_action($intent_context) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        $action_map = [
            'commercial' => 'purchase',
            'educational' => 'signup',
            'informational' => 'learn_more',
            'navigational' => 'contact'
        ];
        
        return $action_map[$intent] ?? 'learn_more';
    }

    /**
     * Determine user stage in conversion funnel
     *
     * @param array $user_interests User interests
     * @param array $intent_context Intent context
     * @return string User stage
     */
    private function determine_user_stage($user_interests, $intent_context) {
        $intent = $intent_context['primary_intent'] ?? 'informational';
        
        if ($intent === 'commercial') {
            return 'consideration';
        } elseif ($intent === 'educational') {
            return 'awareness';
        } else {
            return 'discovery';
        }
    }

    /**
     * Analyze competitive context from discovery results
     *
     * @param array $discovery_results Discovery results
     * @return array Competitive context
     */
    private function analyze_competitive_context($discovery_results) {
        $context = [
            'has_alternatives' => count($discovery_results) > 3,
            'competitive_keywords' => [],
            'differentiation_opportunities' => []
        ];
        
        // Extract competitive keywords
        foreach ($discovery_results as $result) {
            if (isset($result['title'])) {
                $title_words = explode(' ', strtolower($result['title']));
                foreach ($title_words as $word) {
                    if (strlen($word) > 4) {
                        $context['competitive_keywords'][] = $word;
                    }
                }
            }
        }
        
        $context['competitive_keywords'] = array_slice(array_unique($context['competitive_keywords']), 0, 5);
        
        return $context;
    }

    /**
     * Generate default value highlights
     *
     * @param array $generation_context Generation context
     * @return array Default value highlights
     */
    private function generate_default_value_highlights($generation_context) {
        $intent = $generation_context['intent_context']['primary_intent'] ?? 'informational';
        
        $highlights_map = [
            'commercial' => ['Best value', 'Fast delivery', 'Money-back guarantee'],
            'educational' => ['Expert instruction', 'Practical skills', 'Lifetime access'],
            'informational' => ['Comprehensive guide', 'Expert insights', 'Up-to-date info'],
            'navigational' => ['Quick access', 'Easy navigation', 'Helpful support']
        ];
        
        return $highlights_map[$intent] ?? ['Quality content', 'Trusted source', 'Easy to use'];
    }

    /**
     * Personalize button text based on user interests
     *
     * @param string $original_text Original button text
     * @param array $interests User interests
     * @param string $button_type Button type (primary/secondary)
     * @return string Personalized button text
     */
    private function personalize_button_text($original_text, $interests, $button_type) {
        $interest_buttons = [
            'technology' => ['Explore Tech', 'Get Started', 'Try Now'],
            'business' => ['Grow Business', 'Get Results', 'Start Now'],
            'education' => ['Learn More', 'Start Course', 'Begin Learning'],
            'creative' => ['Get Inspired', 'Create Now', 'Explore'],
            'health' => ['Improve Health', 'Get Fit', 'Start Today']
        ];
        
        foreach ($interests as $interest) {
            if (isset($interest_buttons[$interest])) {
                $options = $interest_buttons[$interest];
                return $options[array_rand($options)];
            }
        }
        
        return $original_text;
    }

    /**
     * Personalize value highlights based on user interests
     *
     * @param array $original_highlights Original value highlights
     * @param array $interests User interests
     * @return array Personalized value highlights
     */
    private function personalize_value_highlights($original_highlights, $interests) {
        $interest_highlights = [
            'technology' => ['Cutting-edge solutions', 'Technical expertise', 'Innovation-driven'],
            'business' => ['ROI-focused', 'Scalable solutions', 'Professional results'],
            'education' => ['Expert instruction', 'Practical skills', 'Proven methods'],
            'creative' => ['Inspiring content', 'Creative freedom', 'Artistic excellence'],
            'health' => ['Science-backed', 'Proven results', 'Expert guidance']
        ];
        
        $personalized = $original_highlights;
        
        foreach ($interests as $interest) {
            if (isset($interest_highlights[$interest])) {
                $personalized = array_merge($personalized, $interest_highlights[$interest]);
            }
        }
        
        return array_slice(array_unique($personalized), 0, 4);
    }
}
