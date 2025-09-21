# Smart Page Builder - Development Best Practices

## Document Overview

This guide provides essential development and testing best practices for building the Smart Page Builder MVP. It serves as a quick-start reference to ensure consistent, secure, and efficient development from day one.

**Target Audience:** Development team starting Smart Page Builder implementation  
**Scope:** MVP development workflow, standards, and testing procedures  
**Priority:** Essential for maintaining code quality and development velocity

---

## Table of Contents

1. [Development Environment Setup](#development-environment-setup)
2. [Code Standards & Structure](#code-standards--structure)
3. [Security-First Development](#security-first-development)
4. [Testing Strategy](#testing-strategy)
5. [Git Workflow & Version Control](#git-workflow--version-control)
6. [Performance Guidelines](#performance-guidelines)
7. [Documentation Standards](#documentation-standards)
8. [Quality Assurance Checklist](#quality-assurance-checklist)
9. [Pre-Development Checklist](#pre-development-checklist)
10. [Development Milestones](#development-milestones)

---

## Development Environment Setup

### Essential Configuration

#### ✅ **WordPress Development Environment**

```php
// wp-config.php - Development Settings
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', true);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);

// Smart Page Builder Debug Settings
define('SPB_DEBUG', true);
define('SPB_LOG_LEVEL', 'debug');
define('SPB_ENVIRONMENT', 'development');

// Memory and Execution Settings
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
```

#### Required Tools & Extensions

```bash
# Development Tools
- WordPress 6.0+ with DIY Home Improvement theme
- PHP 8.0+ with required extensions (openssl, curl, json)
- MySQL 5.7+ or MariaDB equivalent
- Git for version control
- Code editor with WordPress coding standards support

# Recommended VS Code Extensions
- PHP Intelephense
- WordPress Snippets
- PHP CodeSniffer
- GitLens
- REST Client for API testing
```

### Local Development Structure

```
smart-page-builder/
├── smart-page-builder.php          # Main plugin file
├── includes/                       # Core plugin files
│   ├── class-smart-page-builder.php
│   ├── class-content-generator.php
│   ├── class-cache-manager.php
│   └── class-security-manager.php
├── admin/                          # Admin interface
│   ├── class-admin.php
│   ├── views/
│   └── assets/
├── public/                         # Frontend functionality
│   ├── class-frontend.php
│   ├── js/
│   └── css/
├── tests/                          # Test files
│   ├── unit/
│   ├── integration/
│   └── fixtures/
├── docs/                           # Documentation
└── README.md
```

---

## Code Standards & Structure

### WordPress Coding Standards

#### ✅ **PHP Standards**

```php
<?php
/**
 * Smart Page Builder Content Generator
 *
 * @package SmartPageBuilder
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Generator Class
 *
 * Handles AI-powered content generation with security and caching.
 *
 * @since 1.0.0
 */
class Smart_Page_Builder_Content_Generator {
    
    /**
     * API provider instance
     *
     * @since 1.0.0
     * @var Smart_Page_Builder_API_Provider
     */
    private $api_provider;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->api_provider = new Smart_Page_Builder_API_Provider();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('wp_ajax_spb_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_nopriv_spb_generate_content', array($this, 'ajax_generate_content'));
    }
    
    /**
     * Generate content via AJAX
     *
     * @since 1.0.0
     */
    public function ajax_generate_content() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'spb_generate_content')) {
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Check permissions
        if (!spb_user_can_generate_content()) {
            wp_send_json_error('Insufficient permissions');
            return;
        }
        
        // Sanitize input
        $type = sanitize_text_field($_POST['type']);
        $context = spb_sanitize_generation_params($_POST['context']);
        
        // Generate content
        $result = $this->generate_content($type, $context);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
}
```

#### JavaScript Standards

```javascript
/**
 * Smart Page Builder Frontend JavaScript
 *
 * @package SmartPageBuilder
 * @since 1.0.0
 */

(function($) {
    'use strict';
    
    /**
     * Smart Page Builder object
     *
     * @since 1.0.0
     */
    window.SmartPageBuilder = {
        
        /**
         * Configuration options
         *
         * @since 1.0.0
         */
        config: {
            ajaxUrl: spb_ajax.url,
            nonce: spb_ajax.nonce,
            debug: spb_ajax.debug || false
        },
        
        /**
         * Initialize the plugin
         *
         * @since 1.0.0
         * @param {Object} options Configuration options
         */
        init: function(options) {
            this.config = $.extend(this.config, options);
            this.bindEvents();
            this.loadPlaceholders();
        },
        
        /**
         * Bind event handlers
         *
         * @since 1.0.0
         */
        bindEvents: function() {
            $(document).on('click', '.spb-generate-btn', this.handleGenerateClick.bind(this));
            $(document).on('spb:contentLoaded', this.handleContentLoaded.bind(this));
        },
        
        /**
         * Handle generate button click
         *
         * @since 1.0.0
         * @param {Event} e Click event
         */
        handleGenerateClick: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $placeholder = $button.closest('.spb-placeholder');
            const contentType = $placeholder.data('content-type');
            const context = $placeholder.data('context');
            
            this.generateContent($placeholder, contentType, context);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        SmartPageBuilder.init();
    });
    
})(jQuery);
```

### File Organization Best Practices

#### Class Naming Convention
- Use `Smart_Page_Builder_` prefix for all classes
- Follow WordPress naming: `class-smart-page-builder-feature.php`
- One class per file

#### Function Naming
- Use `spb_` prefix for all global functions
- Descriptive names: `spb_generate_content()`, `spb_sanitize_input()`
- Private methods: `_spb_internal_function()`

---

## Security-First Development

### Implementation Checklist

#### ✅ **Every Feature Must Include:**

```php
// 1. Input Validation
function spb_process_user_input($input) {
    // Validate input type
    if (!is_array($input)) {
        return new WP_Error('invalid_input', 'Input must be an array');
    }
    
    // Sanitize each field
    $sanitized = array();
    foreach ($input as $key => $value) {
        $sanitized[sanitize_key($key)] = sanitize_text_field($value);
    }
    
    return $sanitized;
}

// 2. Permission Checks
function spb_secure_action() {
    // Check user capabilities
    if (!current_user_can('spb_generate_content')) {
        wp_die('Access denied');
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'spb_action')) {
        wp_die('Security check failed');
    }
    
    // Proceed with action
}

// 3. Output Escaping
function spb_display_content($content) {
    // Escape for HTML context
    echo '<div class="spb-content">' . esc_html($content) . '</div>';
    
    // For HTML content, use wp_kses
    echo wp_kses_post($generated_html);
    
    // For URLs
    echo '<a href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
}
```

### Security Testing Checklist

- [ ] All user inputs validated and sanitized
- [ ] All outputs properly escaped
- [ ] Nonce verification on all forms/AJAX
- [ ] Capability checks on all actions
- [ ] SQL queries use prepared statements
- [ ] API keys encrypted in storage
- [ ] Error messages don't expose sensitive data

---

## Testing Strategy

### Test-Driven Development Approach

#### ✅ **Unit Testing Structure**

```php
<?php
/**
 * Test Content Generator
 *
 * @package SmartPageBuilder
 * @subpackage Tests
 */

class Test_Content_Generator extends WP_UnitTestCase {
    
    /**
     * Content generator instance
     *
     * @var Smart_Page_Builder_Content_Generator
     */
    private $generator;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        $this->generator = new Smart_Page_Builder_Content_Generator();
    }
    
    /**
     * Test content generation with valid input
     */
    public function test_generate_content_valid_input() {
        $type = 'tool_recommendation';
        $context = array(
            'project_type' => 'plumbing',
            'difficulty' => 'beginner'
        );
        
        $result = $this->generator->generate_content($type, $context);
        
        $this->assertNotInstanceOf('WP_Error', $result);
        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('text', $result);
    }
    
    /**
     * Test content generation with invalid input
     */
    public function test_generate_content_invalid_input() {
        $result = $this->generator->generate_content('invalid_type', array());
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_content_type', $result->get_error_code());
    }
    
    /**
     * Test content sanitization
     */
    public function test_content_sanitization() {
        $malicious_content = '<script>alert("xss")</script><p>Safe content</p>';
        $sanitized = $this->generator->sanitize_content($malicious_content);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringContainsString('<p>Safe content</p>', $sanitized);
    }
}
```

#### Integration Testing

```php
<?php
/**
 * Integration Test for API Provider
 */
class Test_API_Integration extends WP_UnitTestCase {
    
    /**
     * Test API connectivity
     */
    public function test_api_connectivity() {
        // Mock API response
        add_filter('pre_http_request', array($this, 'mock_api_response'), 10, 3);
        
        $provider = new Smart_Page_Builder_API_Provider();
        $result = $provider->test_connection();
        
        $this->assertTrue($result);
        
        remove_filter('pre_http_request', array($this, 'mock_api_response'));
    }
    
    /**
     * Mock API response for testing
     */
    public function mock_api_response($preempt, $args, $url) {
        if (strpos($url, 'api.openai.com') !== false) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array('data' => array('id' => 'test-model')))
            );
        }
        return $preempt;
    }
}
```

### Testing Commands

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test class
vendor/bin/phpunit tests/unit/test-content-generator.php

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/

# Run integration tests
vendor/bin/phpunit tests/integration/
```

---

## Git Workflow & Version Control

### Branch Strategy

```bash
# Main branches
main                    # Production-ready code
develop                 # Integration branch for features

# Feature branches
feature/content-generator
feature/admin-interface
feature/api-integration
feature/cache-system

# Release branches
release/1.0.0

# Hotfix branches
hotfix/security-fix
```

### Commit Message Standards

```bash
# Format: type(scope): description

feat(generator): add content generation API
fix(security): sanitize user input in AJAX handler
docs(api): update function documentation
test(generator): add unit tests for content validation
refactor(cache): optimize cache key generation
style(admin): fix CSS formatting
perf(api): reduce API call frequency
```

### Pre-commit Checklist

- [ ] Code follows WordPress coding standards
- [ ] All functions have proper documentation
- [ ] Security checks implemented
- [ ] Tests written and passing
- [ ] No debug code or console.log statements
- [ ] Performance impact considered

---

## Performance Guidelines

### Optimization Best Practices

#### ✅ **Caching Strategy**

```php
/**
 * Implement intelligent caching
 */
function spb_get_cached_or_generate($cache_key, $generator_callback, $expiration = 3600) {
    // Check cache first
    $cached = spb_get_cached_content($cache_key);
    if ($cached !== false) {
        spb_increment_cache_hits();
        return $cached;
    }
    
    // Generate new content
    $start_time = microtime(true);
    $content = call_user_func($generator_callback);
    $generation_time = microtime(true) - $start_time;
    
    // Cache the result
    if (!is_wp_error($content)) {
        spb_cache_content($cache_key, $content, $expiration);
        spb_log_performance('content_generation', $generation_time);
    }
    
    spb_increment_cache_misses();
    return $content;
}
```

#### Database Optimization

```php
/**
 * Efficient database queries
 */
function spb_get_content_batch($post_ids) {
    global $wpdb;
    
    // Use prepared statements with IN clause
    $placeholders = implode(',', array_fill(0, count($post_ids), '%d'));
    $query = $wpdb->prepare(
        "SELECT post_id, content_html, expires_at 
         FROM {$wpdb->prefix}spb_generated_content 
         WHERE post_id IN ($placeholders) 
         AND (expires_at IS NULL OR expires_at > NOW())",
        $post_ids
    );
    
    return $wpdb->get_results($query, OBJECT_K);
}
```

### Performance Monitoring

```php
/**
 * Monitor performance metrics
 */
function spb_monitor_performance($operation, $start_time, $memory_start) {
    $execution_time = microtime(true) - $start_time;
    $memory_used = memory_get_usage() - $memory_start;
    $memory_peak = memory_get_peak_usage();
    
    // Log if performance thresholds exceeded
    if ($execution_time > 5.0) { // 5 seconds
        spb_debug_log("Slow operation: {$operation}", 'warning', [
            'execution_time' => $execution_time,
            'memory_used' => $memory_used,
            'memory_peak' => $memory_peak
        ]);
    }
    
    // Store metrics for analysis
    spb_store_performance_metric($operation, $execution_time, $memory_used);
}
```

---

## Documentation Standards

### Code Documentation

#### ✅ **Function Documentation**

```php
/**
 * Generate AI-powered content for specified type and context
 *
 * This function handles the complete content generation workflow including
 * input validation, caching checks, API calls, and content sanitization.
 *
 * @since 1.0.0
 *
 * @param string $type    Content type (tool_recommendation, safety_tip, etc.)
 * @param array  $context Context data for content generation
 * @param array  $options Optional generation parameters
 *
 * @return array|WP_Error Generated content array on success, WP_Error on failure
 *
 * @example
 * $content = spb_generate_content('tool_recommendation', [
 *     'project_type' => 'plumbing',
 *     'difficulty' => 'beginner'
 * ]);
 */
function spb_generate_content($type, $context, $options = []) {
    // Implementation
}
```

#### Inline Documentation

```php
// Validate content type against allowed types
$allowed_types = ['tool_recommendation', 'safety_tip', 'project_step'];
if (!in_array($type, $allowed_types)) {
    return new WP_Error('invalid_type', 'Content type not supported');
}

// Generate cache key based on type and context hash
$cache_key = 'spb_' . $type . '_' . md5(serialize($context));

// Check cache before making expensive API call
$cached_content = spb_get_cached_content($cache_key);
if ($cached_content !== false) {
    return $cached_content;
}
```

---

## Quality Assurance Checklist

### Pre-Release Checklist

#### ✅ **Code Quality**
- [ ] All functions documented with proper PHPDoc
- [ ] WordPress coding standards followed
- [ ] No PHP errors or warnings
- [ ] JavaScript passes linting
- [ ] CSS follows BEM methodology

#### ✅ **Security**
- [ ] All inputs validated and sanitized
- [ ] All outputs properly escaped
- [ ] Nonce verification implemented
- [ ] Capability checks in place
- [ ] API keys encrypted
- [ ] SQL injection prevention

#### ✅ **Performance**
- [ ] Caching implemented where appropriate
- [ ] Database queries optimized
- [ ] Large operations use background processing
- [ ] Memory usage monitored
- [ ] API rate limiting implemented

#### ✅ **Testing**
- [ ] Unit tests written and passing
- [ ] Integration tests cover API interactions
- [ ] Manual testing completed
- [ ] Edge cases tested
- [ ] Error conditions handled

#### ✅ **Compatibility**
- [ ] WordPress 6.0+ compatibility verified
- [ ] PHP 8.0+ compatibility tested
- [ ] DIY theme integration working
- [ ] Common plugin conflicts tested
- [ ] Mobile responsiveness verified

---

## Pre-Development Checklist

### Environment Preparation

#### ✅ **Before Starting Development:**

**1. Documentation Review**
- [ ] Read all Smart Page Builder documentation
- [ ] Understand security guidelines
- [ ] Review API specifications
- [ ] Study troubleshooting procedures

**2. Development Environment**
- [ ] WordPress development site configured
- [ ] DIY Home Improvement theme installed
- [ ] Debug settings enabled
- [ ] Required tools installed
- [ ] Git repository initialized

**3. API Access**
- [ ] OpenAI API key obtained (for testing)
- [ ] API rate limits understood
- [ ] Test API connectivity verified
- [ ] Backup API provider identified

**4. Security Setup**
- [ ] Security guidelines reviewed
- [ ] Encryption methods understood
- [ ] Input validation patterns ready
- [ ] Error handling strategy defined

**5. Testing Framework**
- [ ] PHPUnit installed and configured
- [ ] Test database created
- [ ] Mock data prepared
- [ ] CI/CD pipeline planned

### Team Coordination

#### ✅ **Team Readiness:**
- [ ] Development roles assigned
- [ ] Code review process established
- [ ] Communication channels set up
- [ ] Project timeline agreed upon
- [ ] Quality standards understood

---

## Development Milestones

### MVP Development Phases

#### **Phase 1: Core Foundation (Week 1-2)**
- [ ] Plugin structure and activation
- [ ] Basic admin interface
- [ ] Security framework implementation
- [ ] Database schema creation
- [ ] Basic logging system

**Deliverables:**
- Plugin activates without errors
- Admin menu appears
- Database tables created
- Security functions operational
- Debug logging working

#### **Phase 2: API Integration (Week 3-4)**
- [ ] API provider abstraction layer
- [ ] OpenAI integration
- [ ] API key management
- [ ] Rate limiting implementation
- [ ] Error handling for API failures

**Deliverables:**
- API connectivity established
- Content generation working
- API errors handled gracefully
- Rate limiting functional
- API debugging tools ready

#### **Phase 3: Content Generation (Week 5-6)**
- [ ] Content type implementations
- [ ] Context processing
- [ ] Content validation
- [ ] Caching system
- [ ] Content sanitization

**Deliverables:**
- All content types generating
- Content quality validation
- Caching reducing API calls
- Generated content secure
- Performance acceptable

#### **Phase 4: Theme Integration (Week 7-8)**
- [ ] DIY theme hook integration
- [ ] Placeholder system
- [ ] Frontend JavaScript
- [ ] AJAX functionality
- [ ] Mobile responsiveness

**Deliverables:**
- Content appears in theme
- User interaction working
- Mobile-friendly display
- AJAX loading smooth
- Theme compatibility verified

#### **Phase 5: Testing & Polish (Week 9-10)**
- [ ] Comprehensive testing
- [ ] Performance optimization
- [ ] Security audit
- [ ] Documentation updates
- [ ] Deployment preparation

**Deliverables:**
- All tests passing
- Performance benchmarks met
- Security review completed
- Documentation current
- Production deployment ready

### Success Criteria

#### ✅ **MVP Completion Criteria:**
- [ ] Content generates reliably for all types
- [ ] Security guidelines fully implemented
- [ ] Performance meets acceptable thresholds
- [ ] DIY theme integration seamless
- [ ] Error handling comprehensive
- [ ] Documentation complete and accurate
- [ ] Testing coverage adequate
- [ ] Production deployment successful

---

## Conclusion

This development best practices guide provides the foundation for building a secure, performant, and maintainable Smart Page Builder MVP. Key principles:

### 🎯 **Core Principles**
- **Security First** - Every feature built with security in mind
- **Test-Driven** - Tests written alongside code
- **Performance Aware** - Optimization considered from start
- **Documentation Driven** - Code documented as it's written
- **WordPress Standards** - Full compliance with WordPress guidelines

### 🚀 **Ready to Start Development**

With this guide and the complete documentation suite, the development team has everything needed to begin building the Smart Page Builder MVP efficiently and securely.

**Next Step:** Begin Phase 1 development following the milestones and checklists provided.

---

**Document Version:** 1.0.0  
**Last Updated:** September 2025  
**Review Schedule:** Weekly during development
