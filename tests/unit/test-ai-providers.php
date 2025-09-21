<?php
/**
 * AI Providers Test Class
 *
 * Tests for the AI provider system including OpenAI integration,
 * provider management, and fallback functionality.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 * @since      2.0.0
 */

/**
 * AI Providers Test Class
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/tests/unit
 */
class Test_AI_Providers extends WP_UnitTestCase {

    /**
     * AI Provider Manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_AI_Provider_Manager    $provider_manager
     */
    private $provider_manager;

    /**
     * Set up test environment
     *
     * @since    2.0.0
     */
    public function setUp(): void {
        parent::setUp();
        
        // Load required classes
        require_once SPB_PLUGIN_DIR . 'includes/class-ai-provider-manager.php';
        require_once SPB_PLUGIN_DIR . 'includes/ai-providers/abstract-ai-provider.php';
        require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-openai-provider.php';
        require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-anthropic-provider.php';
        require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-google-provider.php';
        
        $this->provider_manager = new Smart_Page_Builder_AI_Provider_Manager();
    }

    /**
     * Test provider manager initialization
     *
     * @since    2.0.0
     */
    public function test_provider_manager_initialization() {
        $this->assertInstanceOf('Smart_Page_Builder_AI_Provider_Manager', $this->provider_manager);
        
        // Test that providers are loaded
        $providers = $this->provider_manager->get_providers();
        $this->assertIsArray($providers);
        $this->assertArrayHasKey('openai', $providers);
        $this->assertArrayHasKey('anthropic', $providers);
        $this->assertArrayHasKey('google', $providers);
    }

    /**
     * Test provider configuration
     *
     * @since    2.0.0
     */
    public function test_provider_configuration() {
        // Test OpenAI configuration
        $openai_config = array(
            'api_key' => 'test-api-key',
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7,
            'max_tokens' => 1000
        );
        
        $result = $this->provider_manager->configure_provider('openai', $openai_config);
        $this->assertTrue($result);
        
        // Test invalid provider
        $result = $this->provider_manager->configure_provider('invalid', $openai_config);
        $this->assertFalse($result);
    }

    /**
     * Test provider instance creation
     *
     * @since    2.0.0
     */
    public function test_provider_instance_creation() {
        // Configure OpenAI provider first
        $this->provider_manager->configure_provider('openai', array(
            'api_key' => 'test-api-key'
        ));
        
        // Test OpenAI provider instance
        $openai_provider = $this->provider_manager->get_provider_instance('openai');
        $this->assertInstanceOf('Smart_Page_Builder_OpenAI_Provider', $openai_provider);
        $this->assertEquals('OpenAI', $openai_provider->get_provider_name());
        
        // Test Anthropic provider instance (mock)
        $anthropic_provider = $this->provider_manager->get_provider_instance('anthropic');
        $this->assertInstanceOf('Smart_Page_Builder_Anthropic_Provider', $anthropic_provider);
        $this->assertEquals('Anthropic Claude', $anthropic_provider->get_provider_name());
        
        // Test Google provider instance (mock)
        $google_provider = $this->provider_manager->get_provider_instance('google');
        $this->assertInstanceOf('Smart_Page_Builder_Google_Provider', $google_provider);
        $this->assertEquals('Google Gemini', $google_provider->get_provider_name());
    }

    /**
     * Test OpenAI provider functionality
     *
     * @since    2.0.0
     */
    public function test_openai_provider_functionality() {
        $openai_provider = new Smart_Page_Builder_OpenAI_Provider(array(
            'api_key' => 'test-api-key'
        ));
        
        // Test provider configuration
        $this->assertTrue($openai_provider->is_configured());
        $this->assertEquals('gpt-3.5-turbo', $openai_provider->get_model());
        
        // Test capabilities
        $capabilities = $openai_provider->get_capabilities();
        $this->assertIsArray($capabilities);
        $this->assertTrue($capabilities['content_generation']);
        $this->assertTrue($capabilities['content_optimization']);
        $this->assertTrue($capabilities['quality_analysis']);
        
        // Test connection test (will fail without real API key, but should return proper format)
        $connection_test = $openai_provider->test_connection();
        $this->assertIsArray($connection_test);
        $this->assertArrayHasKey('success', $connection_test);
        $this->assertArrayHasKey('message', $connection_test);
    }

    /**
     * Test mock provider functionality
     *
     * @since    2.0.0
     */
    public function test_mock_provider_functionality() {
        $anthropic_provider = new Smart_Page_Builder_Anthropic_Provider();
        
        // Test that mock provider is not configured
        $this->assertFalse($anthropic_provider->is_configured());
        
        // Test capabilities
        $capabilities = $anthropic_provider->get_capabilities();
        $this->assertIsArray($capabilities);
        $this->assertFalse($capabilities['content_generation']);
        $this->assertTrue($capabilities['quality_analysis']); // Basic fallback
        $this->assertEquals('mock', $capabilities['status']);
        
        // Test content generation returns error
        $result = $anthropic_provider->generate_content('test prompt');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('provider_not_implemented', $result->get_error_code());
        
        // Test quality analysis returns basic analysis
        $analysis = $anthropic_provider->analyze_quality('This is test content for analysis.');
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('overall_score', $analysis);
        $this->assertArrayHasKey('provider_status', $analysis);
        $this->assertEquals('mock', $analysis['provider_status']);
    }

    /**
     * Test provider switching and fallback
     *
     * @since    2.0.0
     */
    public function test_provider_switching_and_fallback() {
        // Set OpenAI as active provider
        $result = $this->provider_manager->set_active_provider('openai');
        $this->assertTrue($result);
        $this->assertEquals('openai', $this->provider_manager->get_active_provider());
        
        // Test switching to Anthropic
        $result = $this->provider_manager->set_active_provider('anthropic');
        $this->assertTrue($result);
        $this->assertEquals('anthropic', $this->provider_manager->get_active_provider());
        
        // Test invalid provider
        $result = $this->provider_manager->set_active_provider('invalid');
        $this->assertFalse($result);
    }

    /**
     * Test content generation with fallback
     *
     * @since    2.0.0
     */
    public function test_content_generation_with_fallback() {
        // Configure only OpenAI (others will be mock)
        $this->provider_manager->configure_provider('openai', array(
            'api_key' => 'test-api-key'
        ));
        
        // Test content generation (will try all providers and fail gracefully)
        $result = $this->provider_manager->generate_content('test prompt', array(
            'content_type' => 'how-to',
            'search_term' => 'test search'
        ));
        
        // Should return WP_Error since we don't have real API keys
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test content type optimization
     *
     * @since    2.0.0
     */
    public function test_content_type_optimization() {
        $openai_provider = new Smart_Page_Builder_OpenAI_Provider(array(
            'api_key' => 'test-api-key'
        ));
        
        // Test different content types have different settings
        $reflection = new ReflectionClass($openai_provider);
        $method = $reflection->getMethod('get_content_type_settings');
        $method->setAccessible(true);
        
        $howto_settings = $method->invoke($openai_provider, 'how-to');
        $safety_settings = $method->invoke($openai_provider, 'safety-tips');
        
        $this->assertIsArray($howto_settings);
        $this->assertIsArray($safety_settings);
        $this->assertArrayHasKey('temperature', $howto_settings);
        $this->assertArrayHasKey('max_tokens', $howto_settings);
        
        // Safety tips should have lower temperature for accuracy
        $this->assertLessThan($howto_settings['temperature'], $safety_settings['temperature']);
    }

    /**
     * Test prompt building for different content types
     *
     * @since    2.0.0
     */
    public function test_prompt_building() {
        $openai_provider = new Smart_Page_Builder_OpenAI_Provider(array(
            'api_key' => 'test-api-key'
        ));
        
        $reflection = new ReflectionClass($openai_provider);
        $method = $reflection->getMethod('build_content_prompt');
        $method->setAccessible(true);
        
        // Test how-to prompt
        $howto_prompt = $method->invoke($openai_provider, 'install ceiling fan', 'how-to');
        $this->assertStringContainsString('how-to guide', $howto_prompt);
        $this->assertStringContainsString('install ceiling fan', $howto_prompt);
        $this->assertStringContainsString('step-by-step', $howto_prompt);
        
        // Test safety tips prompt
        $safety_prompt = $method->invoke($openai_provider, 'electrical work', 'safety-tips');
        $this->assertStringContainsString('safety guide', $safety_prompt);
        $this->assertStringContainsString('electrical work', $safety_prompt);
        $this->assertStringContainsString('safety precautions', $safety_prompt);
    }

    /**
     * Test quality score calculation
     *
     * @since    2.0.0
     */
    public function test_quality_score_calculation() {
        $openai_provider = new Smart_Page_Builder_OpenAI_Provider();
        
        $reflection = new ReflectionClass($openai_provider);
        $method = $reflection->getMethod('calculate_quality_score');
        $method->setAccessible(true);
        
        // Test high-quality content
        $high_quality_content = '<h2>How to Install a Ceiling Fan</h2>
            <p>Installing a ceiling fan is an important home improvement project.</p>
            <ol>
                <li>First, turn off the power at the circuit breaker</li>
                <li>Second, remove the old fixture</li>
                <li>Third, install the mounting bracket</li>
            </ol>
            <p><strong>Warning:</strong> Always use proper safety equipment.</p>';
        
        $score = $method->invoke($openai_provider, $high_quality_content);
        $this->assertGreaterThan(70, $score);
        
        // Test low-quality content
        $low_quality_content = 'Short text.';
        $score = $method->invoke($openai_provider, $low_quality_content);
        $this->assertLessThan(30, $score);
    }

    /**
     * Test provider usage tracking
     *
     * @since    2.0.0
     */
    public function test_provider_usage_tracking() {
        // Test that usage tracking hooks are properly set up
        $this->assertTrue(has_action('spb_ai_provider_usage'));
        $this->assertTrue(has_action('spb_ai_provider_error'));
    }

    /**
     * Test provider connection testing
     *
     * @since    2.0.0
     */
    public function test_provider_connection_testing() {
        // Test OpenAI connection test
        $openai_result = $this->provider_manager->test_provider_connection('openai');
        $this->assertIsArray($openai_result);
        $this->assertArrayHasKey('success', $openai_result);
        $this->assertArrayHasKey('message', $openai_result);
        
        // Test mock provider connection test
        $anthropic_result = $this->provider_manager->test_provider_connection('anthropic');
        $this->assertIsArray($anthropic_result);
        $this->assertFalse($anthropic_result['success']);
        $this->assertArrayHasKey('provider_status', $anthropic_result);
        $this->assertEquals('mock', $anthropic_result['provider_status']);
        
        // Test invalid provider
        $invalid_result = $this->provider_manager->test_provider_connection('invalid');
        $this->assertIsArray($invalid_result);
        $this->assertFalse($invalid_result['success']);
    }

    /**
     * Test provider statistics
     *
     * @since    2.0.0
     */
    public function test_provider_statistics() {
        $stats = $this->provider_manager->get_usage_statistics();
        $this->assertIsArray($stats);
        
        // Test provider-specific stats
        $openai_stats = $this->provider_manager->get_usage_statistics('openai');
        $this->assertIsArray($openai_stats);
    }

    /**
     * Test error handling and logging
     *
     * @since    2.0.0
     */
    public function test_error_handling() {
        $openai_provider = new Smart_Page_Builder_OpenAI_Provider(array(
            'api_key' => 'invalid-key'
        ));
        
        // Test that errors are properly handled
        $result = $openai_provider->generate_content('test prompt');
        $this->assertInstanceOf('WP_Error', $result);
        
        // Test connection with invalid key
        $connection_result = $openai_provider->test_connection();
        $this->assertIsArray($connection_result);
        $this->assertFalse($connection_result['success']);
    }

    /**
     * Test content formatting
     *
     * @since    2.0.0
     */
    public function test_content_formatting() {
        $openai_provider = new Smart_Page_Builder_OpenAI_Provider();
        
        $reflection = new ReflectionClass($openai_provider);
        $method = $reflection->getMethod('convert_markdown_to_html');
        $method->setAccessible(true);
        
        // Test markdown conversion
        $markdown = "# Heading\n\n**Bold text** and *italic text*\n\n1. First item\n2. Second item";
        $html = $method->invoke($openai_provider, $markdown);
        
        $this->assertStringContainsString('<h1>Heading</h1>', $html);
        $this->assertStringContainsString('<strong>Bold text</strong>', $html);
        $this->assertStringContainsString('<em>italic text</em>', $html);
        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>First item</li>', $html);
    }

    /**
     * Test provider priority system
     *
     * @since    2.0.0
     */
    public function test_provider_priority_system() {
        $providers = $this->provider_manager->get_providers();
        
        // Test that OpenAI has highest priority (lowest number)
        $this->assertEquals(1, $providers['openai']['priority']);
        $this->assertEquals(2, $providers['anthropic']['priority']);
        $this->assertEquals(3, $providers['google']['priority']);
    }

    /**
     * Clean up after tests
     *
     * @since    2.0.0
     */
    public function tearDown(): void {
        // Clean up any test data
        delete_option('spb_ai_provider_configs');
        delete_option('spb_active_ai_provider');
        
        parent::tearDown();
    }
}
