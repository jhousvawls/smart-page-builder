<?php
/**
 * Template Engine for Smart Page Builder
 *
 * Handles mobile-first responsive template system with intent-based selection
 * and customization options for AI-generated search pages.
 *
 * @package Smart_Page_Builder
 * @subpackage Template_Engine
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Template Engine Class
 *
 * Manages responsive page templates for generated content with mobile-first design,
 * intent-based template selection, and customization options.
 */
class SPB_Template_Engine {

    /**
     * Template cache for performance optimization
     *
     * @var array
     */
    private $template_cache = [];

    /**
     * Available template types
     *
     * @var array
     */
    private $template_types = [
        'commercial' => 'Commercial/Sales focused templates',
        'educational' => 'Educational/Learning focused templates',
        'informational' => 'Information/Reference focused templates',
        'entertainment' => 'Entertainment/Engagement focused templates',
        'news' => 'News/Current events focused templates'
    ];

    /**
     * Mobile breakpoints for responsive design
     *
     * @var array
     */
    private $breakpoints = [
        'mobile' => '320px',
        'tablet' => '768px',
        'desktop' => '1024px',
        'large' => '1200px'
    ];

    /**
     * Template customization options
     *
     * @var array
     */
    private $customization_options = [
        'color_scheme' => ['primary', 'secondary', 'accent', 'background'],
        'typography' => ['heading_font', 'body_font', 'font_sizes'],
        'layout' => ['sidebar', 'full_width', 'boxed', 'fluid'],
        'branding' => ['logo_position', 'brand_colors', 'custom_css']
    ];

    /**
     * Initialize the template engine
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_template_cache();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_template_assets']);
        add_filter('spb_template_variables', [$this, 'add_responsive_variables'], 10, 2);
        add_action('wp_head', [$this, 'output_custom_styles']);
    }

    /**
     * Generate a complete page template based on intent and content
     *
     * @param array $content_data Generated content from AI components
     * @param string $intent User search intent (commercial, educational, etc.)
     * @param array $customization_options Template customization settings
     * @return array Template data with HTML, CSS, and metadata
     */
    public function generate_page_template($content_data, $intent = 'informational', $customization_options = []) {
        try {
            // Validate input data
            if (empty($content_data) || !is_array($content_data)) {
                throw new Exception('Invalid content data provided');
            }

            // Select appropriate template based on intent
            $template_config = $this->select_template_by_intent($intent);
            
            // Apply customization options
            $template_config = $this->apply_customization($template_config, $customization_options);
            
            // Generate responsive HTML structure
            $html_content = $this->build_responsive_html($content_data, $template_config);
            
            // Generate mobile-first CSS
            $css_styles = $this->generate_responsive_css($template_config);
            
            // Generate JavaScript for interactivity
            $javascript = $this->generate_template_javascript($template_config);
            
            // Compile template metadata
            $metadata = $this->compile_template_metadata($template_config, $content_data);
            
            // Cache the generated template
            $cache_key = $this->generate_cache_key($content_data, $intent, $customization_options);
            $this->cache_template($cache_key, [
                'html' => $html_content,
                'css' => $css_styles,
                'javascript' => $javascript,
                'metadata' => $metadata
            ]);
            
            return [
                'success' => true,
                'template' => [
                    'html' => $html_content,
                    'css' => $css_styles,
                    'javascript' => $javascript,
                    'metadata' => $metadata
                ],
                'performance' => [
                    'generation_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                    'cache_key' => $cache_key,
                    'template_type' => $template_config['type']
                ]
            ];
            
        } catch (Exception $e) {
            error_log('SPB Template Engine Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_template' => $this->get_fallback_template($content_data)
            ];
        }
    }

    /**
     * Select template configuration based on user intent
     *
     * @param string $intent User search intent
     * @return array Template configuration
     */
    private function select_template_by_intent($intent) {
        $templates = [
            'commercial' => [
                'type' => 'commercial',
                'layout' => 'conversion_focused',
                'color_scheme' => 'professional',
                'components' => ['hero_banner', 'features', 'testimonials', 'cta_section'],
                'mobile_priority' => 'cta_visibility',
                'performance_target' => 'fast_loading'
            ],
            'educational' => [
                'type' => 'educational',
                'layout' => 'content_focused',
                'color_scheme' => 'academic',
                'components' => ['hero_banner', 'article_content', 'related_topics', 'resources'],
                'mobile_priority' => 'readability',
                'performance_target' => 'content_delivery'
            ],
            'informational' => [
                'type' => 'informational',
                'layout' => 'balanced',
                'color_scheme' => 'neutral',
                'components' => ['hero_banner', 'article_content', 'sidebar', 'footer_cta'],
                'mobile_priority' => 'navigation',
                'performance_target' => 'balanced'
            ],
            'entertainment' => [
                'type' => 'entertainment',
                'layout' => 'engaging',
                'color_scheme' => 'vibrant',
                'components' => ['hero_banner', 'featured_content', 'gallery', 'social_sharing'],
                'mobile_priority' => 'visual_impact',
                'performance_target' => 'engagement'
            ],
            'news' => [
                'type' => 'news',
                'layout' => 'timeline',
                'color_scheme' => 'clean',
                'components' => ['hero_banner', 'article_content', 'related_articles', 'breaking_news'],
                'mobile_priority' => 'quick_scanning',
                'performance_target' => 'real_time'
            ]
        ];

        return $templates[$intent] ?? $templates['informational'];
    }

    /**
     * Apply customization options to template configuration
     *
     * @param array $template_config Base template configuration
     * @param array $customization_options User customization preferences
     * @return array Modified template configuration
     */
    private function apply_customization($template_config, $customization_options) {
        // Apply color scheme customization
        if (!empty($customization_options['color_scheme'])) {
            $template_config['colors'] = array_merge(
                $this->get_default_colors($template_config['color_scheme']),
                $customization_options['color_scheme']
            );
        } else {
            $template_config['colors'] = $this->get_default_colors($template_config['color_scheme']);
        }

        // Apply typography customization
        if (!empty($customization_options['typography'])) {
            $template_config['typography'] = array_merge(
                $this->get_default_typography(),
                $customization_options['typography']
            );
        } else {
            $template_config['typography'] = $this->get_default_typography();
        }

        // Apply layout customization
        if (!empty($customization_options['layout'])) {
            $template_config['layout_options'] = array_merge(
                $template_config,
                $customization_options['layout']
            );
        }

        // Apply branding customization
        if (!empty($customization_options['branding'])) {
            $template_config['branding'] = $customization_options['branding'];
        }

        return $template_config;
    }

    /**
     * Build responsive HTML structure
     *
     * @param array $content_data Generated content components
     * @param array $template_config Template configuration
     * @return string Complete HTML structure
     */
    private function build_responsive_html($content_data, $template_config) {
        $html = '';
        
        // Start HTML document structure
        $html .= $this->get_html_header($template_config);
        
        // Build main content container
        $html .= '<div class="spb-page-container spb-' . $template_config['type'] . '-template">';
        
        // Add hero section if available
        if (!empty($content_data['hero'])) {
            $html .= $this->build_hero_section($content_data['hero'], $template_config);
        }
        
        // Add main content area
        $html .= '<main class="spb-main-content" role="main">';
        
        // Add article content if available
        if (!empty($content_data['article'])) {
            $html .= $this->build_article_section($content_data['article'], $template_config);
        }
        
        // Add CTA section if available
        if (!empty($content_data['cta'])) {
            $html .= $this->build_cta_section($content_data['cta'], $template_config);
        }
        
        $html .= '</main>';
        
        // Add footer
        $html .= $this->build_footer_section($template_config);
        
        $html .= '</div>'; // Close page container
        
        return $html;
    }

    /**
     * Build hero section HTML
     *
     * @param array $hero_data Hero component data
     * @param array $template_config Template configuration
     * @return string Hero section HTML
     */
    private function build_hero_section($hero_data, $template_config) {
        $html = '<section class="spb-hero-section" role="banner">';
        $html .= '<div class="spb-hero-container">';
        
        // Hero content
        $html .= '<div class="spb-hero-content">';
        
        if (!empty($hero_data['headline'])) {
            $html .= '<h1 class="spb-hero-headline">' . esc_html($hero_data['headline']) . '</h1>';
        }
        
        if (!empty($hero_data['subheadline'])) {
            $html .= '<p class="spb-hero-subheadline">' . esc_html($hero_data['subheadline']) . '</p>';
        }
        
        // Hero CTA buttons
        if (!empty($hero_data['cta_primary'])) {
            $html .= '<div class="spb-hero-actions">';
            $html .= '<a href="' . esc_url($hero_data['cta_primary']['url'] ?? '#') . '" class="spb-btn spb-btn-primary">';
            $html .= esc_html($hero_data['cta_primary']['text'] ?? 'Learn More');
            $html .= '</a>';
            
            if (!empty($hero_data['cta_secondary'])) {
                $html .= '<a href="' . esc_url($hero_data['cta_secondary']['url'] ?? '#') . '" class="spb-btn spb-btn-secondary">';
                $html .= esc_html($hero_data['cta_secondary']['text'] ?? 'Get Started');
                $html .= '</a>';
            }
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close hero content
        
        // Hero visual element
        if (!empty($hero_data['visual_suggestion'])) {
            $html .= '<div class="spb-hero-visual">';
            $html .= '<div class="spb-hero-placeholder" data-visual="' . esc_attr($hero_data['visual_suggestion']) . '">';
            $html .= '<span class="spb-visual-hint">' . esc_html($hero_data['visual_suggestion']) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close hero container
        $html .= '</section>';
        
        return $html;
    }

    /**
     * Build article section HTML
     *
     * @param array $article_data Article component data
     * @param array $template_config Template configuration
     * @return string Article section HTML
     */
    private function build_article_section($article_data, $template_config) {
        $html = '<section class="spb-article-section">';
        $html .= '<div class="spb-article-container">';
        
        if (!empty($article_data['title'])) {
            $html .= '<h2 class="spb-article-title">' . esc_html($article_data['title']) . '</h2>';
        }
        
        if (!empty($article_data['content'])) {
            $html .= '<div class="spb-article-content">';
            $html .= wp_kses_post($article_data['content']);
            $html .= '</div>';
        }
        
        // Key points section
        if (!empty($article_data['key_points']) && is_array($article_data['key_points'])) {
            $html .= '<div class="spb-key-points">';
            $html .= '<h3 class="spb-key-points-title">Key Points</h3>';
            $html .= '<ul class="spb-key-points-list">';
            
            foreach ($article_data['key_points'] as $point) {
                $html .= '<li class="spb-key-point">' . esc_html($point) . '</li>';
            }
            
            $html .= '</ul>';
            $html .= '</div>';
        }
        
        // Related topics
        if (!empty($article_data['related_topics']) && is_array($article_data['related_topics'])) {
            $html .= '<div class="spb-related-topics">';
            $html .= '<h3 class="spb-related-title">Related Topics</h3>';
            $html .= '<div class="spb-related-grid">';
            
            foreach ($article_data['related_topics'] as $topic) {
                $html .= '<div class="spb-related-item">';
                $html .= '<a href="#" class="spb-related-link">' . esc_html($topic) . '</a>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close article container
        $html .= '</section>';
        
        return $html;
    }

    /**
     * Build CTA section HTML
     *
     * @param array $cta_data CTA component data
     * @param array $template_config Template configuration
     * @return string CTA section HTML
     */
    private function build_cta_section($cta_data, $template_config) {
        $html = '<section class="spb-cta-section">';
        $html .= '<div class="spb-cta-container">';
        
        if (!empty($cta_data['headline'])) {
            $html .= '<h2 class="spb-cta-headline">' . esc_html($cta_data['headline']) . '</h2>';
        }
        
        if (!empty($cta_data['description'])) {
            $html .= '<p class="spb-cta-description">' . esc_html($cta_data['description']) . '</p>';
        }
        
        // CTA buttons
        $html .= '<div class="spb-cta-actions">';
        
        if (!empty($cta_data['primary_button'])) {
            $html .= '<a href="' . esc_url($cta_data['primary_button']['url'] ?? '#') . '" class="spb-btn spb-btn-cta-primary">';
            $html .= esc_html($cta_data['primary_button']['text'] ?? 'Get Started');
            $html .= '</a>';
        }
        
        if (!empty($cta_data['secondary_button'])) {
            $html .= '<a href="' . esc_url($cta_data['secondary_button']['url'] ?? '#') . '" class="spb-btn spb-btn-cta-secondary">';
            $html .= esc_html($cta_data['secondary_button']['text'] ?? 'Learn More');
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        // Value propositions
        if (!empty($cta_data['value_propositions']) && is_array($cta_data['value_propositions'])) {
            $html .= '<div class="spb-value-props">';
            
            foreach ($cta_data['value_propositions'] as $prop) {
                $html .= '<div class="spb-value-prop">';
                $html .= '<span class="spb-value-prop-text">' . esc_html($prop) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        $html .= '</div>'; // Close CTA container
        $html .= '</section>';
        
        return $html;
    }

    /**
     * Build footer section HTML
     *
     * @param array $template_config Template configuration
     * @return string Footer section HTML
     */
    private function build_footer_section($template_config) {
        $html = '<footer class="spb-footer-section" role="contentinfo">';
        $html .= '<div class="spb-footer-container">';
        
        $html .= '<div class="spb-footer-content">';
        $html .= '<p class="spb-footer-text">Generated by Smart Page Builder</p>';
        $html .= '<p class="spb-footer-timestamp">Generated on ' . date('F j, Y') . '</p>';
        $html .= '</div>';
        
        $html .= '</div>'; // Close footer container
        $html .= '</footer>';
        
        return $html;
    }

    /**
     * Generate mobile-first responsive CSS
     *
     * @param array $template_config Template configuration
     * @return string CSS styles
     */
    private function generate_responsive_css($template_config) {
        $css = '';
        
        // Base mobile-first styles
        $css .= $this->get_base_mobile_styles($template_config);
        
        // Tablet styles
        $css .= '@media (min-width: ' . $this->breakpoints['tablet'] . ') {';
        $css .= $this->get_tablet_styles($template_config);
        $css .= '}';
        
        // Desktop styles
        $css .= '@media (min-width: ' . $this->breakpoints['desktop'] . ') {';
        $css .= $this->get_desktop_styles($template_config);
        $css .= '}';
        
        // Large screen styles
        $css .= '@media (min-width: ' . $this->breakpoints['large'] . ') {';
        $css .= $this->get_large_screen_styles($template_config);
        $css .= '}';
        
        return $css;
    }

    /**
     * Get base mobile styles
     *
     * @param array $template_config Template configuration
     * @return string Mobile CSS styles
     */
    private function get_base_mobile_styles($template_config) {
        $colors = $template_config['colors'];
        $typography = $template_config['typography'];
        
        return "
        /* Mobile-first base styles */
        .spb-page-container {
            width: 100%;
            margin: 0;
            padding: 0;
            font-family: {$typography['body_font']};
            line-height: 1.6;
            color: {$colors['text']};
            background-color: {$colors['background']};
        }
        
        .spb-hero-section {
            padding: 2rem 1rem;
            background: linear-gradient(135deg, {$colors['primary']}, {$colors['secondary']});
            color: white;
            text-align: center;
        }
        
        .spb-hero-headline {
            font-size: 1.75rem;
            font-weight: bold;
            margin-bottom: 1rem;
            font-family: {$typography['heading_font']};
        }
        
        .spb-hero-subheadline {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        
        .spb-hero-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
        }
        
        .spb-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 0.375rem;
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            min-width: 200px;
        }
        
        .spb-btn-primary {
            background-color: {$colors['accent']};
            color: white;
        }
        
        .spb-btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .spb-article-section {
            padding: 2rem 1rem;
        }
        
        .spb-article-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-family: {$typography['heading_font']};
            color: {$colors['primary']};
        }
        
        .spb-article-content {
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        
        .spb-key-points {
            background-color: {$colors['light_background']};
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .spb-key-points-title {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: {$colors['primary']};
        }
        
        .spb-key-points-list {
            list-style: none;
            padding: 0;
        }
        
        .spb-key-point {
            padding: 0.5rem 0;
            border-bottom: 1px solid {$colors['border']};
            position: relative;
            padding-left: 1.5rem;
        }
        
        .spb-key-point:before {
            content: '✓';
            position: absolute;
            left: 0;
            color: {$colors['accent']};
            font-weight: bold;
        }
        
        .spb-cta-section {
            background-color: {$colors['primary']};
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }
        
        .spb-cta-headline {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            font-family: {$typography['heading_font']};
        }
        
        .spb-cta-description {
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        
        .spb-cta-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .spb-btn-cta-primary {
            background-color: {$colors['accent']};
            color: white;
        }
        
        .spb-btn-cta-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .spb-footer-section {
            background-color: {$colors['dark_background']};
            color: {$colors['light_text']};
            padding: 1.5rem 1rem;
            text-align: center;
        }
        
        .spb-footer-text {
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .spb-footer-timestamp {
            font-size: 0.75rem;
            opacity: 0.7;
        }
        ";
    }

    /**
     * Get tablet styles
     *
     * @param array $template_config Template configuration
     * @return string Tablet CSS styles
     */
    private function get_tablet_styles($template_config) {
        return "
        /* Tablet styles */
        .spb-hero-section {
            padding: 3rem 2rem;
        }
        
        .spb-hero-container {
            display: flex;
            align-items: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .spb-hero-content {
            flex: 1;
            text-align: left;
        }
        
        .spb-hero-visual {
            flex: 1;
        }
        
        .spb-hero-headline {
            font-size: 2.25rem;
        }
        
        .spb-hero-actions {
            flex-direction: row;
            justify-content: flex-start;
        }
        
        .spb-article-section {
            padding: 3rem 2rem;
        }
        
        .spb-article-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .spb-related-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        
        .spb-cta-actions {
            flex-direction: row;
            justify-content: center;
        }
        ";
    }

    /**
     * Get desktop styles
     *
     * @param array $template_config Template configuration
     * @return string Desktop CSS styles
     */
    private function get_desktop_styles($template_config) {
        return "
        /* Desktop styles */
        .spb-hero-section {
            padding: 4rem 2rem;
        }
        
        .spb-hero-headline {
            font-size: 3rem;
        }
        
        .spb-hero-subheadline {
            font-size: 1.25rem;
        }
        
        .spb-article-section {
            padding: 4rem 2rem;
        }
        
        .spb-related-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .spb-value-props {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
        }
        ";
    }

    /**
     * Get large screen styles
     *
     * @param array $template_config Template configuration
     * @return string Large screen CSS styles
     */
    private function get_large_screen_styles($template_config) {
        return "
        /* Large screen styles */
        .spb-hero-container,
        .spb-article-container,
        .spb-cta-container,
        .spb-footer-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .spb-related-grid {
            grid-template-columns: repeat(4, 1fr);
        }
        ";
    }

    /**
     * Generate template JavaScript for interactivity
     *
     * @param array $template_config Template configuration
     * @return string JavaScript code
     */
    private function generate_template_javascript($template_config) {
        return "
        // Smart Page Builder Template JavaScript
        (function() {
            'use strict';
            
            // Mobile menu toggle functionality
            function initMobileMenu() {
                const menuToggle = document.querySelector('.spb-menu-toggle');
                const mobileMenu = document.querySelector('.spb-mobile-menu');
                
                if (menuToggle && mobileMenu) {
                    menuToggle.addEventListener('click', function() {
                        mobileMenu.classList.toggle('active');
                    });
                }
            }
            
            // Smooth scrolling for anchor links
            function initSmoothScrolling() {
                const links = document.querySelectorAll('a[href^=\"#\"]');
                
                links.forEach(link => {
                    link.addEventListener('click', function(e) {
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            e.preventDefault();
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });
            }
            
            // Initialize on DOM ready
            document.addEventListener('DOMContentLoaded', function() {
                initMobileMenu();
                initSmoothScrolling();
            });
            
        })();
        ";
    }

    /**
     * Get default color scheme
     *
     * @param string $scheme_name Color scheme name
     * @return array Color values
     */
    private function get_default_colors($scheme_name) {
        $schemes = [
            'professional' => [
                'primary' => '#2563eb',
                'secondary' => '#1e40af',
                'accent' => '#f59e0b',
                'background' => '#ffffff',
                'light_background' => '#f8fafc',
                'dark_background' => '#1e293b',
                'text' => '#1f2937',
                'light_text' => '#9ca3af',
                'border' => '#e5e7eb'
            ],
            'academic' => [
                'primary' => '#059669',
                'secondary' => '#047857',
                'accent' => '#dc2626',
                'background' => '#ffffff',
                'light_background' => '#f0fdf4',
                'dark_background' => '#064e3b',
                'text' => '#1f2937',
                'light_text' => '#6b7280',
                'border' => '#d1d5db'
            ],
            'neutral' => [
                'primary' => '#6b7280',
                'secondary' => '#4b5563',
                'accent' => '#3b82f6',
                'background' => '#ffffff',
                'light_background' => '#f9fafb',
                'dark_background' => '#374151',
                'text' => '#1f2937',
                'light_text' => '#9ca3af',
                'border' => '#e5e7eb'
            ],
            'vibrant' => [
                'primary' => '#ec4899',
                'secondary' => '#be185d',
                'accent' => '#f59e0b',
                'background' => '#ffffff',
                'light_background' => '#fdf2f8',
                'dark_background' => '#831843',
                'text' => '#1f2937',
                'light_text' => '#9ca3af',
                'border' => '#e5e7eb'
            ],
            'clean' => [
                'primary' => '#0f172a',
                'secondary' => '#334155',
                'accent' => '#0ea5e9',
                'background' => '#ffffff',
                'light_background' => '#f8fafc',
                'dark_background' => '#0f172a',
                'text' => '#1e293b',
                'light_text' => '#64748b',
                'border' => '#e2e8f0'
            ]
        ];

        return $schemes[$scheme_name] ?? $schemes['neutral'];
    }

    /**
     * Get default typography settings
     *
     * @return array Typography configuration
     */
    private function get_default_typography() {
        return [
            'heading_font' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'body_font' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
            'font_sizes' => [
                'small' => '0.875rem',
                'base' => '1rem',
                'large' => '1.125rem',
                'xl' => '1.25rem',
                'xxl' => '1.5rem'
            ]
        ];
    }

    /**
     * Get HTML header for template
     *
     * @param array $template_config Template configuration
     * @return string HTML header
     */
    private function get_html_header($template_config) {
        return '<!-- Smart Page Builder Generated Template -->';
    }

    /**
     * Compile template metadata
     *
     * @param array $template_config Template configuration
     * @param array $content_data Content data
     * @return array Template metadata
     */
    private function compile_template_metadata($template_config, $content_data) {
        return [
            'template_type' => $template_config['type'],
            'generation_timestamp' => current_time('mysql'),
            'components_included' => array_keys($content_data),
            'mobile_optimized' => true,
            'responsive_breakpoints' => $this->breakpoints,
            'performance_target' => $template_config['performance_target'] ?? 'balanced'
        ];
    }

    /**
     * Generate cache key for template
     *
     * @param array $content_data Content data
     * @param string $intent User intent
     * @param array $customization_options Customization options
     * @return string Cache key
     */
    private function generate_cache_key($content_data, $intent, $customization_options) {
        $key_data = [
            'content_hash' => md5(serialize($content_data)),
            'intent' => $intent,
            'customization_hash' => md5(serialize($customization_options))
        ];
        
        return 'spb_template_' . md5(serialize($key_data));
    }

    /**
     * Cache template data
     *
     * @param string $cache_key Cache key
     * @param array $template_data Template data to cache
     */
    private function cache_template($cache_key, $template_data) {
        $this->template_cache[$cache_key] = $template_data;
        
        // Also cache in WordPress transients for persistence
        set_transient($cache_key, $template_data, HOUR_IN_SECONDS * 2);
    }

    /**
     * Load template cache from WordPress transients
     */
    private function load_template_cache() {
        // Template cache is loaded on-demand from transients
        // This method can be extended for more sophisticated caching
    }

    /**
     * Get fallback template for error cases
     *
     * @param array $content_data Content data
     * @return array Fallback template
     */
    private function get_fallback_template($content_data) {
        return [
            'html' => '<div class="spb-fallback-template"><h1>Content Generated</h1><p>Template generation encountered an issue, but your content is ready.</p></div>',
            'css' => '.spb-fallback-template { padding: 2rem; text-align: center; }',
            'javascript' => '',
            'metadata' => [
                'template_type' => 'fallback',
                'generation_timestamp' => current_time('mysql')
            ]
        ];
    }

    /**
     * Enqueue template assets
     */
    public function enqueue_template_assets() {
        // Enqueue template-specific CSS and JavaScript
        wp_enqueue_style('spb-template-styles', plugin_dir_url(__FILE__) . '../assets/css/template-styles.css', [], '1.0.0');
        wp_enqueue_script('spb-template-scripts', plugin_dir_url(__FILE__) . '../assets/js/template-scripts.js', ['jquery'], '1.0.0', true);
    }

    /**
     * Add responsive variables to template
     *
     * @param array $variables Existing variables
     * @param array $template_config Template configuration
     * @return array Modified variables
     */
    public function add_responsive_variables($variables, $template_config) {
        $variables['breakpoints'] = $this->breakpoints;
        $variables['mobile_first'] = true;
        return $variables;
    }

    /**
     * Output custom styles in head
     */
    public function output_custom_styles() {
        // Output any custom CSS for the current template
        if (!empty($this->current_template_css)) {
            echo '<style type="text/css">' . $this->current_template_css . '</style>';
        }
    }

    /**
     * Get available template types
     *
     * @return array Template types
     */
    public function get_template_types() {
        return $this->template_types;
    }

    /**
     * Get mobile breakpoints
     *
     * @return array Breakpoints
     */
    public function get_breakpoints() {
        return $this->breakpoints;
    }

    /**
     * Validate template configuration
     *
     * @param array $config Template configuration
     * @return bool|WP_Error Validation result
     */
    public function validate_template_config($config) {
        if (empty($config['type'])) {
            return new WP_Error('missing_type', 'Template type is required');
        }

        if (!array_key_exists($config['type'], $this->template_types)) {
            return new WP_Error('invalid_type', 'Invalid template type specified');
        }

        return true;
    }
}
