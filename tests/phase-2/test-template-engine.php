<?php
/**
 * Template Engine Tests for Smart Page Builder Phase 2
 *
 * Comprehensive testing suite for the Enhanced Template System
 * with mobile optimization and intent-based template selection.
 *
 * @package Smart_Page_Builder
 * @subpackage Tests
 * @since 3.1.0
 */

class SPB_Template_Engine_Test extends WP_UnitTestCase {

    /**
     * Template Engine instance
     *
     * @var SPB_Template_Engine
     */
    private $template_engine;

    /**
     * Sample content data for testing
     *
     * @var array
     */
    private $sample_content;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        $this->template_engine = new SPB_Template_Engine();
        
        $this->sample_content = [
            'hero' => [
                'headline' => 'Test Headline',
                'subheadline' => 'Test Subheadline',
                'cta_primary' => [
                    'text' => 'Get Started',
                    'url' => '#get-started'
                ],
                'cta_secondary' => [
                    'text' => 'Learn More',
                    'url' => '#learn-more'
                ],
                'visual_suggestion' => 'Hero image placeholder'
            ],
            'article' => [
                'title' => 'Test Article Title',
                'content' => '<p>This is test article content with <strong>formatting</strong>.</p>',
                'key_points' => [
                    'First key point',
                    'Second key point',
                    'Third key point'
                ],
                'related_topics' => [
                    'Related Topic 1',
                    'Related Topic 2',
                    'Related Topic 3'
                ]
            ],
            'cta' => [
                'headline' => 'Ready to Get Started?',
                'description' => 'Join thousands of satisfied customers today',
                'primary_button' => [
                    'text' => 'Sign Up Now',
                    'url' => '#signup'
                ],
                'secondary_button' => [
                    'text' => 'Contact Sales',
                    'url' => '#contact'
                ],
                'value_propositions' => [
                    'Free trial available',
                    '24/7 customer support',
                    'Money-back guarantee'
                ]
            ]
        ];
    }

    /**
     * Test template generation with commercial intent
     */
    public function test_generate_commercial_template() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('template', $result);
        $this->assertArrayHasKey('html', $result['template']);
        $this->assertArrayHasKey('css', $result['template']);
        $this->assertArrayHasKey('javascript', $result['template']);
        $this->assertArrayHasKey('metadata', $result['template']);

        // Check that HTML contains expected elements
        $html = $result['template']['html'];
        $this->assertStringContainsString('spb-commercial-template', $html);
        $this->assertStringContainsString('Test Headline', $html);
        $this->assertStringContainsString('Get Started', $html);
    }

    /**
     * Test template generation with educational intent
     */
    public function test_generate_educational_template() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'educational'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('educational', $result['template']['metadata']['template_type']);
        
        // Educational templates should prioritize readability
        $html = $result['template']['html'];
        $this->assertStringContainsString('spb-educational-template', $html);
    }

    /**
     * Test template generation with informational intent
     */
    public function test_generate_informational_template() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'informational'
        );

        $this->assertTrue($result['success']);
        $this->assertEquals('informational', $result['template']['metadata']['template_type']);
    }

    /**
     * Test template customization options
     */
    public function test_template_customization() {
        $customization_options = [
            'color_scheme' => [
                'primary' => '#ff0000',
                'secondary' => '#00ff00',
                'accent' => '#0000ff'
            ],
            'typography' => [
                'heading_font' => 'Georgia, serif',
                'body_font' => 'Arial, sans-serif'
            ]
        ];

        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial',
            $customization_options
        );

        $this->assertTrue($result['success']);
        
        // Check that custom colors are applied
        $css = $result['template']['css'];
        $this->assertStringContainsString('#ff0000', $css);
        $this->assertStringContainsString('Georgia, serif', $css);
    }

    /**
     * Test mobile-first responsive design
     */
    public function test_mobile_responsive_design() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );

        $css = $result['template']['css'];
        
        // Check for mobile-first approach
        $this->assertStringContainsString('/* Mobile-first base styles */', $css);
        
        // Check for responsive breakpoints
        $this->assertStringContainsString('@media (min-width: 768px)', $css);
        $this->assertStringContainsString('@media (min-width: 1024px)', $css);
        $this->assertStringContainsString('@media (min-width: 1200px)', $css);
        
        // Check for mobile-optimized elements
        $this->assertStringContainsString('flex-direction: column', $css);
    }

    /**
     * Test template caching functionality
     */
    public function test_template_caching() {
        // Generate template first time
        $start_time = microtime(true);
        $result1 = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );
        $first_generation_time = microtime(true) - $start_time;

        $this->assertTrue($result1['success']);
        $this->assertArrayHasKey('cache_key', $result1['performance']);

        // Generate same template second time (should be faster due to caching)
        $start_time = microtime(true);
        $result2 = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );
        $second_generation_time = microtime(true) - $start_time;

        $this->assertTrue($result2['success']);
        
        // Cache key should be the same
        $this->assertEquals(
            $result1['performance']['cache_key'],
            $result2['performance']['cache_key']
        );
    }

    /**
     * Test template validation
     */
    public function test_template_validation() {
        // Test with valid configuration
        $valid_config = ['type' => 'commercial'];
        $result = $this->template_engine->validate_template_config($valid_config);
        $this->assertTrue($result);

        // Test with invalid configuration
        $invalid_config = ['type' => 'invalid_type'];
        $result = $this->template_engine->validate_template_config($invalid_config);
        $this->assertInstanceOf('WP_Error', $result);

        // Test with missing type
        $missing_type_config = [];
        $result = $this->template_engine->validate_template_config($missing_type_config);
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test template performance requirements
     */
    public function test_template_performance() {
        $start_time = microtime(true);
        
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );
        
        $generation_time = microtime(true) - $start_time;

        $this->assertTrue($result['success']);
        
        // Template generation should be under 2 seconds
        $this->assertLessThan(2.0, $generation_time);
        
        // Check performance metadata
        $this->assertArrayHasKey('performance', $result);
        $this->assertArrayHasKey('generation_time', $result['performance']);
    }

    /**
     * Test error handling
     */
    public function test_error_handling() {
        // Test with empty content data
        $result = $this->template_engine->generate_page_template([], 'commercial');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('fallback_template', $result);
    }

    /**
     * Test template types availability
     */
    public function test_template_types() {
        $template_types = $this->template_engine->get_template_types();
        
        $this->assertIsArray($template_types);
        $this->assertArrayHasKey('commercial', $template_types);
        $this->assertArrayHasKey('educational', $template_types);
        $this->assertArrayHasKey('informational', $template_types);
        $this->assertArrayHasKey('entertainment', $template_types);
        $this->assertArrayHasKey('news', $template_types);
    }

    /**
     * Test responsive breakpoints
     */
    public function test_responsive_breakpoints() {
        $breakpoints = $this->template_engine->get_breakpoints();
        
        $this->assertIsArray($breakpoints);
        $this->assertArrayHasKey('mobile', $breakpoints);
        $this->assertArrayHasKey('tablet', $breakpoints);
        $this->assertArrayHasKey('desktop', $breakpoints);
        $this->assertArrayHasKey('large', $breakpoints);
        
        // Check breakpoint values
        $this->assertEquals('320px', $breakpoints['mobile']);
        $this->assertEquals('768px', $breakpoints['tablet']);
        $this->assertEquals('1024px', $breakpoints['desktop']);
        $this->assertEquals('1200px', $breakpoints['large']);
    }

    /**
     * Test HTML structure and accessibility
     */
    public function test_html_accessibility() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );

        $html = $result['template']['html'];
        
        // Check for semantic HTML elements
        $this->assertStringContainsString('<main', $html);
        $this->assertStringContainsString('role="main"', $html);
        $this->assertStringContainsString('<section', $html);
        $this->assertStringContainsString('<footer', $html);
        $this->assertStringContainsString('role="contentinfo"', $html);
        
        // Check for proper heading hierarchy
        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('<h2', $html);
    }

    /**
     * Test CSS generation and structure
     */
    public function test_css_generation() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );

        $css = $result['template']['css'];
        
        // Check for essential CSS classes
        $this->assertStringContainsString('.spb-page-container', $css);
        $this->assertStringContainsString('.spb-hero-section', $css);
        $this->assertStringContainsString('.spb-article-section', $css);
        $this->assertStringContainsString('.spb-cta-section', $css);
        $this->assertStringContainsString('.spb-footer-section', $css);
        
        // Check for responsive design
        $this->assertStringContainsString('flex-direction: column', $css);
        $this->assertStringContainsString('flex-direction: row', $css);
    }

    /**
     * Test JavaScript generation
     */
    public function test_javascript_generation() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );

        $javascript = $result['template']['javascript'];
        
        // Check for essential JavaScript functionality
        $this->assertStringContainsString('initMobileMenu', $javascript);
        $this->assertStringContainsString('initSmoothScrolling', $javascript);
        $this->assertStringContainsString('DOMContentLoaded', $javascript);
    }

    /**
     * Test template metadata generation
     */
    public function test_metadata_generation() {
        $result = $this->template_engine->generate_page_template(
            $this->sample_content,
            'commercial'
        );

        $metadata = $result['template']['metadata'];
        
        $this->assertArrayHasKey('template_type', $metadata);
        $this->assertArrayHasKey('generation_timestamp', $metadata);
        $this->assertArrayHasKey('components_included', $metadata);
        $this->assertArrayHasKey('mobile_optimized', $metadata);
        $this->assertArrayHasKey('responsive_breakpoints', $metadata);
        
        $this->assertEquals('commercial', $metadata['template_type']);
        $this->assertTrue($metadata['mobile_optimized']);
        $this->assertContains('hero', $metadata['components_included']);
        $this->assertContains('article', $metadata['components_included']);
        $this->assertContains('cta', $metadata['components_included']);
    }

    /**
     * Test template with missing content components
     */
    public function test_partial_content_handling() {
        $partial_content = [
            'hero' => [
                'headline' => 'Test Headline'
            ]
            // Missing article and cta components
        ];

        $result = $this->template_engine->generate_page_template(
            $partial_content,
            'commercial'
        );

        $this->assertTrue($result['success']);
        
        $html = $result['template']['html'];
        $this->assertStringContainsString('Test Headline', $html);
        
        // Should handle missing components gracefully
        $this->assertStringNotContainsString('spb-article-section', $html);
        $this->assertStringNotContainsString('spb-cta-section', $html);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        parent::tearDown();
        
        // Clean up any cached templates
        wp_cache_flush();
    }
}
