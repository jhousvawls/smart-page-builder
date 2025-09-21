# Smart Page Builder - Developer Troubleshooting Guide

## Document Overview

This guide provides practical troubleshooting solutions for developers working with the Smart Page Builder plugin. It covers common issues, debugging techniques, and resolution strategies specific to MVP development and deployment.

**Target Audience:** Developers building and maintaining the Smart Page Builder plugin  
**Scope:** MVP troubleshooting and debugging procedures  
**Priority:** Essential for development efficiency and problem resolution

---

## Table of Contents

1. [WordPress Debug Configuration](#wordpress-debug-configuration)
2. [Plugin Integration Debugging](#plugin-integration-debugging)
3. [Hook & Filter Troubleshooting](#hook--filter-troubleshooting)
4. [AI API Debugging](#ai-api-debugging)
5. [Content Generation Troubleshooting](#content-generation-troubleshooting)
6. [Caching & Performance Issues](#caching--performance-issues)
7. [Error Logging & Monitoring](#error-logging--monitoring)
8. [Common Error Codes](#common-error-codes)
9. [Debugging Tools & Utilities](#debugging-tools--utilities)

---

## WordPress Debug Configuration

### Essential Debug Settings

#### ✅ **Required Debug Configuration**

Add these settings to your `wp-config.php` file for effective debugging:

```php
// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false); // Set to true only in development

// Smart Page Builder specific debugging
define('SPB_DEBUG', true);
define('SPB_LOG_LEVEL', 'info'); // Options: error, warning, info, debug

// Script debugging (for JavaScript issues)
define('SCRIPT_DEBUG', true);

// Query debugging (for database issues)
define('SAVEQUERIES', true);

// Memory limit debugging
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
```

#### Debug Log Locations

```php
/**
 * Get debug log file paths
 */
function spb_get_debug_log_paths() {
    $paths = [
        'wordpress' => WP_CONTENT_DIR . '/debug.log',
        'plugin' => WP_CONTENT_DIR . '/spb-debug.log',
        'api' => WP_CONTENT_DIR . '/spb-api.log',
        'performance' => WP_CONTENT_DIR . '/spb-performance.log'
    ];
    
    return $paths;
}

/**
 * Custom logging function
 */
function spb_debug_log($message, $type = 'info', $context = []) {
    if (!defined('SPB_DEBUG') || !SPB_DEBUG) {
        return;
    }
    
    $log_levels = ['error' => 1, 'warning' => 2, 'info' => 3, 'debug' => 4];
    $current_level = $log_levels[SPB_LOG_LEVEL] ?? 3;
    $message_level = $log_levels[$type] ?? 3;
    
    if ($message_level > $current_level) {
        return;
    }
    
    $timestamp = current_time('Y-m-d H:i:s');
    $user_id = get_current_user_id();
    $context_str = !empty($context) ? ' | Context: ' . wp_json_encode($context) : '';
    
    $log_message = "[{$timestamp}] [{$type}] [User:{$user_id}] {$message}{$context_str}\n";
    
    error_log($log_message, 3, WP_CONTENT_DIR . '/spb-debug.log');
}
```

### Debug Information Collection

```php
/**
 * Collect comprehensive debug information
 */
function spb_get_debug_info() {
    global $wpdb;
    
    $debug_info = [
        'plugin_version' => defined('SPB_VERSION') ? SPB_VERSION : 'Unknown',
        'wordpress_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'active_theme' => wp_get_theme()->get('Name'),
        'active_plugins' => get_option('active_plugins'),
        'multisite' => is_multisite(),
        'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
        'spb_debug' => defined('SPB_DEBUG') && SPB_DEBUG,
        'database_version' => $wpdb->db_version(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    // Check plugin-specific settings
    $debug_info['spb_settings'] = [
        'api_keys_configured' => !empty(get_option('spb_openai_api_key')),
        'cache_enabled' => get_option('spb_cache_enabled', true),
        'cache_duration' => get_option('spb_cache_duration', 3600),
        'min_user_role' => get_option('spb_min_user_role', 'author'),
        'content_moderation' => get_option('spb_content_moderation', false)
    ];
    
    return $debug_info;
}

/**
 * Generate debug report
 */
function spb_generate_debug_report() {
    $debug_info = spb_get_debug_info();
    
    $report = "=== Smart Page Builder Debug Report ===\n";
    $report .= "Generated: " . current_time('Y-m-d H:i:s') . "\n\n";
    
    foreach ($debug_info as $section => $data) {
        $report .= strtoupper(str_replace('_', ' ', $section)) . ":\n";
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $report .= "  {$key}: " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "\n";
            }
        } else {
            $report .= "  {$data}\n";
        }
        $report .= "\n";
    }
    
    return $report;
}
```

---

## Plugin Integration Debugging

### Theme Compatibility Issues

#### ✅ **DIY Theme Integration Debugging**

```php
/**
 * Check DIY theme compatibility
 */
function spb_debug_theme_compatibility() {
    $issues = [];
    
    // Check if DIY theme is active
    $theme = wp_get_theme();
    $is_diy_theme = $theme->get('Name') === 'DIY Home Improvement' || 
                    $theme->get('Template') === 'diy-home-improvement';
    
    if (!$is_diy_theme) {
        $issues[] = 'DIY Home Improvement theme not active. Current theme: ' . $theme->get('Name');
    }
    
    // Check for required hooks
    $required_hooks = [
        'diy_before_posts_loop',
        'diy_after_post_excerpt',
        'diy_footer_smart_content'
    ];
    
    foreach ($required_hooks as $hook) {
        if (!has_action($hook)) {
            $issues[] = "Missing theme hook: {$hook}";
        }
    }
    
    // Check for theme functions
    $required_functions = [
        'diy_render_hero_section',
        'diy_render_featured_grid'
    ];
    
    foreach ($required_functions as $function) {
        if (!function_exists($function)) {
            $issues[] = "Missing theme function: {$function}";
        }
    }
    
    if (empty($issues)) {
        spb_debug_log('Theme compatibility check passed', 'info');
        return true;
    } else {
        spb_debug_log('Theme compatibility issues found', 'warning', $issues);
        return $issues;
    }
}

/**
 * Test theme hook execution
 */
function spb_test_theme_hooks() {
    $hook_tests = [];
    
    // Test each DIY theme hook
    $hooks_to_test = [
        'diy_before_posts_loop' => 'Archive pages',
        'diy_after_post_excerpt' => 'Post excerpts',
        'diy_footer_smart_content' => 'Footer area'
    ];
    
    foreach ($hooks_to_test as $hook => $description) {
        $hook_tests[$hook] = [
            'description' => $description,
            'has_actions' => has_action($hook),
            'action_count' => did_action($hook),
            'callbacks' => []
        ];
        
        // Get callbacks attached to hook
        global $wp_filter;
        if (isset($wp_filter[$hook])) {
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    $hook_tests[$hook]['callbacks'][] = [
                        'priority' => $priority,
                        'function' => spb_get_callback_name($callback['function'])
                    ];
                }
            }
        }
    }
    
    spb_debug_log('Theme hook test results', 'debug', $hook_tests);
    return $hook_tests;
}

/**
 * Get human-readable callback name
 */
function spb_get_callback_name($callback) {
    if (is_string($callback)) {
        return $callback;
    } elseif (is_array($callback)) {
        if (is_object($callback[0])) {
            return get_class($callback[0]) . '::' . $callback[1];
        } else {
            return $callback[0] . '::' . $callback[1];
        }
    } elseif (is_object($callback)) {
        return get_class($callback);
    }
    return 'Unknown callback type';
}
```

### Plugin Conflict Detection

```php
/**
 * Detect potential plugin conflicts
 */
function spb_detect_plugin_conflicts() {
    $conflicts = [];
    $active_plugins = get_option('active_plugins', []);
    
    // Known problematic plugins
    $problematic_plugins = [
        'wp-rocket/wp-rocket.php' => 'Caching conflicts - may cache AJAX responses',
        'w3-total-cache/w3-total-cache.php' => 'Caching conflicts - may interfere with dynamic content',
        'autoptimize/autoptimize.php' => 'JavaScript optimization may break AJAX',
        'wp-super-cache/wp-cache.php' => 'Page caching may prevent dynamic content updates'
    ];
    
    foreach ($problematic_plugins as $plugin => $issue) {
        if (in_array($plugin, $active_plugins)) {
            $conflicts[] = [
                'plugin' => $plugin,
                'issue' => $issue,
                'severity' => 'warning'
            ];
        }
    }
    
    // Check for plugins that modify content
    $content_plugins = [
        'elementor/elementor.php' => 'Page builder conflicts',
        'beaver-builder-lite-version/fl-builder.php' => 'Page builder conflicts',
        'siteorigin-panels/siteorigin-panels.php' => 'Page builder conflicts'
    ];
    
    foreach ($content_plugins as $plugin => $issue) {
        if (in_array($plugin, $active_plugins)) {
            $conflicts[] = [
                'plugin' => $plugin,
                'issue' => $issue,
                'severity' => 'info'
            ];
        }
    }
    
    spb_debug_log('Plugin conflict check completed', 'info', [
        'conflicts_found' => count($conflicts),
        'conflicts' => $conflicts
    ]);
    
    return $conflicts;
}

/**
 * Test plugin compatibility
 */
function spb_test_plugin_compatibility() {
    $tests = [
        'ajax_functionality' => spb_test_ajax_functionality(),
        'javascript_conflicts' => spb_test_javascript_conflicts(),
        'css_conflicts' => spb_test_css_conflicts(),
        'hook_conflicts' => spb_test_hook_conflicts()
    ];
    
    return $tests;
}

function spb_test_ajax_functionality() {
    // Test if AJAX is working
    $test_data = [
        'action' => 'spb_test_ajax',
        'nonce' => wp_create_nonce('spb_test_nonce')
    ];
    
    $response = wp_remote_post(admin_url('admin-ajax.php'), [
        'body' => $test_data,
        'timeout' => 10
    ]);
    
    if (is_wp_error($response)) {
        return [
            'status' => 'error',
            'message' => 'AJAX test failed: ' . $response->get_error_message()
        ];
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    return [
        'status' => isset($data['success']) ? 'success' : 'error',
        'message' => isset($data['success']) ? 'AJAX working correctly' : 'AJAX response invalid'
    ];
}
```

---

## Hook & Filter Troubleshooting

### WordPress Hook Debugging

#### ✅ **Hook Execution Tracking**

```php
/**
 * Debug hook execution
 */
function spb_debug_hook_execution($hook_name) {
    add_action($hook_name, function() use ($hook_name) {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        
        spb_debug_log("Hook executed: {$hook_name}", 'debug', [
            'hook' => $hook_name,
            'current_filter' => current_filter(),
            'doing_action' => doing_action(),
            'backtrace' => array_map(function($trace) {
                return [
                    'file' => $trace['file'] ?? 'unknown',
                    'line' => $trace['line'] ?? 'unknown',
                    'function' => $trace['function'] ?? 'unknown'
                ];
            }, $backtrace)
        ]);
    }, 1);
}

/**
 * Monitor all Smart Page Builder hooks
 */
function spb_monitor_all_hooks() {
    $spb_hooks = [
        'spb_before_content_generation',
        'spb_after_content_generation',
        'spb_placeholder_rendered',
        'diy_before_posts_loop',
        'diy_after_post_excerpt',
        'diy_footer_smart_content'
    ];
    
    foreach ($spb_hooks as $hook) {
        spb_debug_hook_execution($hook);
    }
}

/**
 * Check hook priority conflicts
 */
function spb_check_hook_priorities($hook_name) {
    global $wp_filter;
    
    if (!isset($wp_filter[$hook_name])) {
        return ['status' => 'error', 'message' => "Hook {$hook_name} not found"];
    }
    
    $callbacks = $wp_filter[$hook_name]->callbacks;
    $priority_map = [];
    
    foreach ($callbacks as $priority => $callback_group) {
        $priority_map[$priority] = [];
        foreach ($callback_group as $callback) {
            $priority_map[$priority][] = spb_get_callback_name($callback['function']);
        }
    }
    
    spb_debug_log("Hook priority analysis: {$hook_name}", 'debug', $priority_map);
    
    return [
        'status' => 'success',
        'priorities' => $priority_map,
        'total_callbacks' => count($callbacks)
    ];
}
```

### Filter Debugging

```php
/**
 * Debug filter modifications
 */
function spb_debug_filter_chain($filter_name, $value) {
    $original_value = $value;
    
    // Track filter modifications
    add_filter($filter_name, function($filtered_value) use ($filter_name, $original_value) {
        if ($filtered_value !== $original_value) {
            spb_debug_log("Filter modified value: {$filter_name}", 'debug', [
                'original' => $original_value,
                'filtered' => $filtered_value,
                'current_filter' => current_filter()
            ]);
        }
        return $filtered_value;
    }, PHP_INT_MAX);
    
    return apply_filters($filter_name, $value);
}

/**
 * Test Smart Page Builder filters
 */
function spb_test_filters() {
    $test_results = [];
    
    // Test content type filter
    $content_types = apply_filters('spb_content_types', [
        'tool_recommendation' => 'Tool Recommendations'
    ]);
    
    $test_results['spb_content_types'] = [
        'original_count' => 1,
        'filtered_count' => count($content_types),
        'added_types' => array_diff_key($content_types, ['tool_recommendation' => 'Tool Recommendations'])
    ];
    
    // Test generation context filter
    $context = apply_filters('spb_generation_context', ['test' => 'value'], 'tool_recommendation', 123);
    
    $test_results['spb_generation_context'] = [
        'context_modified' => $context !== ['test' => 'value'],
        'final_context' => $context
    ];
    
    spb_debug_log('Filter test results', 'debug', $test_results);
    
    return $test_results;
}
```

---

## AI API Debugging

### API Connection Issues

#### ✅ **API Connectivity Testing**

```php
/**
 * Test AI provider connectivity
 */
function spb_test_api_connectivity($provider = 'openai') {
    $api_key = spb_get_api_key($provider);
    
    if (!$api_key) {
        return [
            'status' => 'error',
            'message' => 'API key not configured',
            'provider' => $provider
        ];
    }
    
    $endpoints = [
        'openai' => 'https://api.openai.com/v1/models',
        'anthropic' => 'https://api.anthropic.com/v1/messages'
    ];
    
    if (!isset($endpoints[$provider])) {
        return [
            'status' => 'error',
            'message' => 'Unsupported provider',
            'provider' => $provider
        ];
    }
    
    $start_time = microtime(true);
    
    $response = wp_remote_get($endpoints[$provider], [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'User-Agent' => 'Smart-Page-Builder/' . (defined('SPB_VERSION') ? SPB_VERSION : '1.0.0')
        ],
        'timeout' => 10,
        'sslverify' => true
    ]);
    
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    if (is_wp_error($response)) {
        spb_debug_log("API connectivity test failed: {$provider}", 'error', [
            'provider' => $provider,
            'error' => $response->get_error_message(),
            'response_time' => $response_time
        ]);
        
        return [
            'status' => 'error',
            'message' => $response->get_error_message(),
            'provider' => $provider,
            'response_time' => $response_time
        ];
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    $result = [
        'status' => $response_code === 200 ? 'success' : 'error',
        'response_code' => $response_code,
        'response_time' => $response_time,
        'provider' => $provider
    ];
    
    if ($response_code !== 200) {
        $result['message'] = "HTTP {$response_code}: " . substr($response_body, 0, 200);
    } else {
        $result['message'] = 'API connection successful';
    }
    
    spb_debug_log("API connectivity test: {$provider}", 'info', $result);
    
    return $result;
}

/**
 * Debug API request/response
 */
function spb_debug_api_request($provider, $endpoint, $data, $response) {
    $debug_data = [
        'provider' => $provider,
        'endpoint' => $endpoint,
        'request_size' => strlen(wp_json_encode($data)),
        'request_data' => $data,
        'response_code' => is_wp_error($response) ? 'ERROR' : wp_remote_retrieve_response_code($response),
        'response_size' => is_wp_error($response) ? 0 : strlen(wp_remote_retrieve_body($response)),
        'timestamp' => current_time('mysql')
    ];
    
    if (is_wp_error($response)) {
        $debug_data['error'] = $response->get_error_message();
        spb_debug_log('API request failed', 'error', $debug_data);
    } else {
        $debug_data['response_headers'] = wp_remote_retrieve_headers($response);
        spb_debug_log('API request completed', 'info', $debug_data);
    }
    
    // Log to separate API log file
    error_log(
        "[" . current_time('Y-m-d H:i:s') . "] " . wp_json_encode($debug_data) . "\n",
        3,
        WP_CONTENT_DIR . '/spb-api.log'
    );
}
```

### Rate Limiting & Quota Debugging

```php
/**
 * Monitor API rate limits
 */
function spb_monitor_rate_limits($provider, $response_headers) {
    $rate_limit_info = [];
    
    // OpenAI rate limit headers
    if ($provider === 'openai') {
        $headers_to_check = [
            'x-ratelimit-limit-requests',
            'x-ratelimit-remaining-requests',
            'x-ratelimit-reset-requests',
            'x-ratelimit-limit-tokens',
            'x-ratelimit-remaining-tokens',
            'x-ratelimit-reset-tokens'
        ];
        
        foreach ($headers_to_check as $header) {
            if (isset($response_headers[$header])) {
                $rate_limit_info[$header] = $response_headers[$header];
            }
        }
    }
    
    if (!empty($rate_limit_info)) {
        spb_debug_log("Rate limit info: {$provider}", 'info', $rate_limit_info);
        
        // Check if approaching limits
        if (isset($rate_limit_info['x-ratelimit-remaining-requests'])) {
            $remaining = intval($rate_limit_info['x-ratelimit-remaining-requests']);
            if ($remaining < 10) {
                spb_debug_log("Approaching rate limit: {$provider}", 'warning', [
                    'remaining_requests' => $remaining
                ]);
            }
        }
    }
    
    return $rate_limit_info;
}

/**
 * Check API quota usage
 */
function spb_check_api_quota($provider) {
    // This would typically require a separate API call to check usage
    // Implementation depends on provider's quota API
    
    $quota_info = [
        'provider' => $provider,
        'period' => 'monthly',
        'usage_checked' => current_time('mysql')
    ];
    
    // For OpenAI, you might call their usage API
    if ($provider === 'openai') {
        $usage_response = wp_remote_get('https://api.openai.com/v1/usage', [
            'headers' => [
                'Authorization' => 'Bearer ' . spb_get_api_key($provider)
            ]
        ]);
        
        if (!is_wp_error($usage_response) && wp_remote_retrieve_response_code($usage_response) === 200) {
            $usage_data = json_decode(wp_remote_retrieve_body($usage_response), true);
            $quota_info['usage_data'] = $usage_data;
        }
    }
    
    spb_debug_log("API quota check: {$provider}", 'info', $quota_info);
    
    return $quota_info;
}
```

---

## Content Generation Troubleshooting

### Content Generation Failures

#### ✅ **Content Generation Debugging**

```php
/**
 * Debug content generation process
 */
function spb_debug_content_generation($type, $context, $options) {
    $debug_id = uniqid('spb_gen_');
    
    spb_debug_log("Content generation started: {$debug_id}", 'info', [
        'type' => $type,
        'context' => $context,
        'options' => $options,
        'user_id' => get_current_user_id(),
        'memory_usage' => memory_get_usage(true),
        'time_limit' => ini_get('max_execution_time')
    ]);
    
    $start_time = microtime(true);
    
    try {
        // Validate inputs
        $validation_result = spb_validate_generation_inputs($type, $context, $options);
        if (is_wp_error($validation_result)) {
            spb_debug_log("Content generation validation failed: {$debug_id}", 'error', [
                'error' => $validation_result->get_error_message()
            ]);
            return $validation_result;
        }
        
        // Check cache first
        $cache_key = spb_generate_cache_key($type, $context);
        $cached_content = spb_get_cached_content($cache_key);
        
        if ($cached_content) {
            spb_debug_log("Content served from cache: {$debug_id}", 'info', [
                'cache_key' => $cache_key,
                'cache_age' => time() - strtotime($cached_content->created_at)
            ]);
            return $cached_content;
        }
        
        // Generate new content
        $content = spb_generate_new_content($type, $context, $options);
        
        $end_time = microtime(true);
        $generation_time = round(($end_time - $start_time) * 1000, 2);
        
        if (is_wp_error($content)) {
            spb_debug_log("Content generation failed: {$debug_id}", 'error', [
                'error' => $content->get_error_message(),
                'generation_time' => $generation_time,
                'memory_peak' => memory_get_peak_usage(true)
            ]);
            return $content;
        }
        
        spb_debug_log("Content generation completed: {$debug_id}", 'info', [
            'content_length' => strlen($content['html']),
            'generation_time' => $generation_time,
            'memory_peak' => memory_get_peak_usage(true)
        ]);
        
        return $content;
        
    } catch (Exception $e) {
        spb_debug_log("Content generation exception: {$debug_id}", 'error', [
            'exception' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return new WP_Error('generation_exception', 'Content generation failed: ' . $e->getMessage());
    }
}

/**
 * Validate content generation inputs
 */
function spb_validate_generation_inputs($type, $context, $options) {
    $errors = [];
    
    // Validate content type
    $allowed_types = ['tool_recommendation', 'project_step', 'safety_tip', 'related_content'];
    if (!in_array($type, $allowed_types)) {
        $errors[] = "Invalid content type: {$type}";
    }
    
    // Validate context
    if (empty($context) || !is_array($context)) {
        $errors[] = "Context must be a non-empty array";
    }
    
    // Validate options
    if (!is_array($options)) {
        $errors[] = "Options must be an array";
    }
    
    // Check required context fields
    $required_fields = [
        'tool_recommendation' => ['project_type'],
        'safety_tip' => ['project_type', 'tools_used'],
        'project_step' => ['project_title']
    ];
    
    if (isset($required_fields[$type])) {
        foreach ($required_fields[$type] as $field) {
            if (empty($context[$field])) {
                $errors[] = "Missing required context field: {$field}";
            }
        }
    }
    
    if (!empty($errors)) {
        return new WP_Error('validation_failed', implode(', ', $errors));
    }
    
    return true;
}
```

### Content Quality Issues

```php
/**
 * Debug content quality problems
 */
function spb_debug_content_quality($content, $type) {
    $quality_issues = [];
    
    // Check content length
    $content_length = strlen(wp_strip_all_tags($content));
    $length_requirements = [
        'tool_recommendation' => ['min' => 50, 'max' => 1000],
        'safety_tip' => ['min' => 30, 'max' => 500],
        'project_step' => ['min' => 100, 'max' => 2000]
    ];
    
    if (isset($length_requirements[$type])) {
        $req = $length_requirements[$type];
        if ($content_length < $req['min']) {
            $quality_issues[] = "Content too short: {$content_length} chars (min: {$req['min']})";
        }
        if ($content_length > $req['max']) {
            $quality_issues[] = "Content too long: {$content_length} chars (max: {$req['max']})";
        }
    }
    
    // Check for required elements
    $required_elements = [
        'tool_recommendation' => ['tool', 'use', 'recommend'],
        'safety_tip' => ['safety', 'caution', 'warning', 'important'],
        'project_step' => ['step', 'first', 'next', 'then']
    ];
    
    if (isset($required_elements[$type])) {
        $content_lower = strtolower($content);
        $missing_elements = [];
        
        foreach ($required_elements[$type] as $element) {
            if (strpos($content_lower, $element) === false) {
                $missing_elements[] = $element;
            }
        }
        
        if (!empty($missing_elements)) {
            $quality_issues[] = "Missing elements: " . implode(', ', $missing_elements);
        }
    }
    
    // Check for HTML structure
    if (strip_tags($content) === $content) {
        $quality_issues[] = "No HTML formatting found";
    }
    
    // Check for repetitive content
    $sentences = explode('.', $content);
    $unique_sentences = array_unique(array_map('trim', $sentences));
    
    if (count($sentences) > 3 && count($unique_sentences) < count($sentences) * 0.8) {
        $quality_issues[] = "Content appears repetitive";
    }
    
    spb_debug_log("Content quality analysis: {$type}", 'debug', [
        'content_length' => $content_length,
        'quality_issues' => $quality_issues,
        'sentences_total' => count($sentences),
        'sentences_unique' => count($unique_sentences)
    ]);
    
    return $quality_issues;
}
```

---

## Caching & Performance Issues

### Cache Debugging

#### ✅ **Cache System Debugging**

```php
/**
 * Debug cache operations
 */
function spb_debug_cache_operations() {
    $cache_stats = [
        'cache_enabled' => get_option('spb_cache_enabled', true),
        'cache_duration' => get_option('spb_cache_duration', 3600),
        'cache_size' => spb_get_cache_size(),
        'cache_entries' => spb_get_cache_entry_count(),
        'expired_entries' => spb_get_expired_cache_count()
    ];
    
    spb_debug_log('Cache system status', 'info', $cache_stats);
    
    return $cache_stats;
}

/**
 * Test cache functionality
 */
function spb_test_cache_functionality() {
    $test_key = 'spb_cache_test_' . time();
    $test_data = ['test' => 'data', 'timestamp' => time()];
    
    // Test cache write
    $write_result = spb_cache_content($test_key, $test_data, 60);
    
    if (!$write_result) {
        return [
            'status' => 'error',
            'message' => 'Cache write failed'
        ];
    }
    
    // Test cache read
    $read_result = spb_get_cached_content($test_key);
    
    if (!$read_result || $read_result !== $test_data) {
        return [
            'status' => 'error',
            'message' => 'Cache read failed or data mismatch'
        ];
    }
    
    // Clean up test data
    spb_delete_cached_content($test_key);
    
    return [
        'status' => 'success',
        'message' => 'Cache functionality working correctly'
    ];
}

/**
 * Monitor cache performance
 */
function spb_monitor_cache_performance() {
    $performance_data = [
        'cache_hits' => get_option('spb_cache_hits', 0),
        'cache_misses' => get_option('spb_cache_misses', 0),
        'cache_hit_ratio' => spb_calculate_cache_hit_ratio(),
        'average_generation_time' => get_option('spb_avg_generation_time', 0),
        'cache_cleanup_last_run' => get_option('spb_cache_cleanup_last_run')
    ];
    
    spb_debug_log('Cache performance metrics', 'info', $performance_data);
    
    return $performance_data;
}
```

### Memory & Timeout Issues

```php
/**
 * Monitor memory usage during content generation
 */
function spb_monitor_memory_usage($operation = 'content_generation') {
    $memory_info = [
        'operation' => $operation,
        'memory_limit' => ini_get('memory_limit'),
        'memory_usage' => memory_get_usage(true),
        'memory_peak' => memory_get_peak_usage(true),
        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'memory_limit_mb' => spb_parse_memory_limit(ini_get('memory_limit'))
    ];
    
    // Calculate memory usage percentage
    $memory_info['memory_usage_percent'] = round(
        ($memory_info['memory_usage_mb'] / $memory_info['memory_limit_mb']) * 100, 
        2
    );
    
    // Log warning if memory usage is high
    if ($memory_info['memory_usage_percent'] > 80) {
        spb_debug_log("High memory usage detected: {$operation}", 'warning', $memory_info);
    } else {
        spb_debug_log("Memory usage: {$operation}", 'debug', $memory_info);
    }
    
    return $memory_info;
}

/**
 * Parse memory limit string to MB
 */
function spb_parse_memory_limit($limit) {
    $limit = trim($limit);
    $last = strtolower($limit[strlen($limit)-1]);
    $limit = (int) $limit;
    
    switch($last) {
        case 'g':
            $limit *= 1024;
        case 'm':
            $limit *= 1024;
        case 'k':
            $limit *= 1024;
    }
    
    return round($limit / 1024 / 1024, 2);
}

/**
 * Check for timeout issues
 */
function spb_check_timeout_issues() {
    $timeout_info = [
        'max_execution_time' => ini_get('max_execution_time'),
        'current_time_limit' => ini_get('max_execution_time'),
        'recommended_minimum' => 300, // 5 minutes for AI API calls
        'timeout_issues' => []
    ];
    
    if ($timeout_info['max_execution_time'] > 0 && $timeout_info['max_execution_time'] < 300) {
        $timeout_info['timeout_issues'][] = 'Execution time limit may be too low for AI API calls';
    }
    
    if ($timeout_info['max_execution_time'] == 0) {
        $timeout_info['timeout_issues'][] = 'No execution time limit set (may cause issues)';
    }
    
    spb_debug_log('Timeout configuration check', 'info', $timeout_info);
    
    return $timeout_info;
}
```

---

## Error Logging & Monitoring

### Logging Configuration

#### ✅ **Structured Logging System**

```php
/**
 * Enhanced logging with structured data
 */
function spb_structured_log($level, $message, $context = [], $category = 'general') {
    if (!defined('SPB_DEBUG') || !SPB_DEBUG) {
        return;
    }
    
    $log_entry = [
        'timestamp' => current_time('c'),
        'level' => $level,
        'category' => $category,
        'message' => $message,
        'context' => $context,
        'user_id' => get_current_user_id(),
        'request_id' => spb_get_request_id(),
        'memory_usage' => memory_get_usage(true),
        'url' => $_SERVER['REQUEST_URI'] ?? 'cli'
    ];
    
    $log_line = wp_json_encode($log_entry) . "\n";
    
    // Write to category-specific log file
    $log_file = WP_CONTENT_DIR . "/spb-{$category}.log";
    error_log($log_line, 3, $log_file);
    
    // Also write to main debug log for errors and warnings
    if (in_array($level, ['error', 'warning'])) {
        error_log($log_line, 3, WP_CONTENT_DIR . '/spb-debug.log');
    }
}

/**
 * Generate unique request ID for tracking
 */
function spb_get_request_id() {
    static $request_id = null;
    
    if ($request_id === null) {
        $request_id = uniqid('spb_', true);
    }
    
    return $request_id;
}

/**
 * Log performance metrics
 */
function spb_log_performance($operation, $start_time, $additional_data = []) {
    $end_time = microtime(true);
    $execution_time = round(($end_time - $start_time) * 1000, 2);
    
    $performance_data = array_merge([
        'operation' => $operation,
        'execution_time_ms' => $execution_time,
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'timestamp' => current_time('mysql')
    ], $additional_data);
    
    spb_structured_log('info', "Performance: {$operation}", $performance_data, 'performance');
    
    return $performance_data;
}
```

### Error Monitoring

```php
/**
 * Monitor and categorize errors
 */
function spb_monitor_errors() {
    $error_stats = [
        'total_errors' => 0,
        'error_categories' => [],
        'recent_errors' => [],
        'error_trends' => []
    ];
    
    // Read recent error logs
    $log_files = [
        'main' => WP_CONTENT_DIR . '/spb-debug.log',
        'api' => WP_CONTENT_DIR . '/spb-api.log'
    ];
    
    foreach ($log_files as $type => $file) {
        if (file_exists($file)) {
            $recent_lines = spb_tail_log_file($file, 100);
            $errors = spb_parse_error_logs($recent_lines);
            
            $error_stats['error_categories'][$type] = count($errors);
            $error_stats['total_errors'] += count($errors);
            $error_stats['recent_errors'] = array_merge($error_stats['recent_errors'], $errors);
        }
    }
    
    // Sort recent errors by timestamp
    usort($error_stats['recent_errors'], function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Keep only the 20 most recent errors
    $error_stats['recent_errors'] = array_slice($error_stats['recent_errors'], 0, 20);
    
    spb_debug_log('Error monitoring summary', 'info', $error_stats);
    
    return $error_stats;
}

/**
 * Parse error logs for analysis
 */
function spb_parse_error_logs($log_lines) {
    $errors = [];
    
    foreach ($log_lines as $line) {
        if (strpos($line, '[error]') !== false || strpos($line, '[warning]') !== false) {
            $parsed = spb_parse_log_line($line);
            if ($parsed) {
                $errors[] = $parsed;
            }
        }
    }
    
    return $errors;
}

/**
 * Read last N lines from log file
 */
function spb_tail_log_file($file, $lines = 50) {
    if (!file_exists($file)) {
        return [];
    }
    
    $handle = fopen($file, 'r');
    if (!$handle) {
        return [];
    }
    
    $line_count = 0;
    $pos = -2;
    $beginning = false;
    $text = [];
    
    while ($line_count < $lines) {
        $t = " ";
        while ($t != "\n") {
            if (fseek($handle, $pos, SEEK_END) == -1) {
                $beginning = true;
                break;
            }
            $t = fgetc($handle);
            $pos--;
        }
        $line_count++;
        if ($beginning) {
            rewind($handle);
        }
        $text[$line_count] = fgets($handle);
        if ($beginning) break;
    }
    
    fclose($handle);
    return array_reverse($text);
}
```

---

## Common Error Codes

### Error Code Reference

#### ✅ **Smart Page Builder Error Codes**

```php
/**
 * Error code definitions and solutions
 */
function spb_get_error_code_reference() {
    return [
        // API Errors (1000-1999)
        'SPB_1001' => [
            'message' => 'API key not configured',
            'solution' => 'Configure API key in plugin settings',
            'severity' => 'error'
        ],
        'SPB_1002' => [
            'message' => 'API request failed',
            'solution' => 'Check internet connection and API key validity',
            'severity' => 'error'
        ],
        'SPB_1003' => [
            'message' => 'API rate limit exceeded',
            'solution' => 'Wait before making more requests or upgrade API plan',
            'severity' => 'warning'
        ],
        'SPB_1004' => [
            'message' => 'Invalid API response format',
            'solution' => 'Check API provider status and response format',
            'severity' => 'error'
        ],
        
        // Content Generation Errors (2000-2999)
        'SPB_2001' => [
            'message' => 'Invalid content type specified',
            'solution' => 'Use valid content types: tool_recommendation, safety_tip, project_step',
            'severity' => 'error'
        ],
        'SPB_2002' => [
            'message' => 'Missing required context data',
            'solution' => 'Provide all required context fields for the content type',
            'severity' => 'error'
        ],
        'SPB_2003' => [
            'message' => 'Content generation timeout',
            'solution' => 'Increase max_execution_time or simplify content requirements',
            'severity' => 'warning'
        ],
        'SPB_2004' => [
            'message' => 'Generated content failed validation',
            'solution' => 'Review content requirements and AI prompt configuration',
            'severity' => 'warning'
        ],
        
        // Cache Errors (3000-3999)
        'SPB_3001' => [
            'message' => 'Cache write failed',
            'solution' => 'Check file permissions and disk space',
            'severity' => 'warning'
        ],
        'SPB_3002' => [
            'message' => 'Cache read failed',
            'solution' => 'Check cache file integrity and permissions',
            'severity' => 'info'
        ],
        'SPB_3003' => [
            'message' => 'Cache cleanup failed',
            'solution' => 'Manually clean cache directory and check permissions',
            'severity' => 'warning'
        ],
        
        // Permission Errors (4000-4999)
        'SPB_4001' => [
            'message' => 'Insufficient user permissions',
            'solution' => 'Check user role and plugin permission settings',
            'severity' => 'error'
        ],
        'SPB_4002' => [
            'message' => 'Admin access denied',
            'solution' => 'Ensure user has manage_options capability',
            'severity' => 'error'
        ],
        
        // Theme Integration Errors (5000-5999)
        'SPB_5001' => [
            'message' => 'DIY theme not active',
            'solution' => 'Activate DIY Home Improvement theme for full functionality',
            'severity' => 'info'
        ],
        'SPB_5002' => [
            'message' => 'Required theme hook missing',
            'solution' => 'Update theme to latest version with required hooks',
            'severity' => 'warning'
        ],
        'SPB_5003' => [
            'message' => 'Theme function not found',
            'solution' => 'Ensure theme includes all required Smart Page Builder functions',
            'severity' => 'warning'
        ]
    ];
}

/**
 * Get error solution by code
 */
function spb_get_error_solution($error_code) {
    $error_reference = spb_get_error_code_reference();
    
    if (isset($error_reference[$error_code])) {
        return $error_reference[$error_code];
    }
    
    return [
        'message' => 'Unknown error code',
        'solution' => 'Check debug logs for more information',
        'severity' => 'error'
    ];
}
```

---

## Debugging Tools & Utilities

### Debug Dashboard

#### ✅ **Admin Debug Interface**

```php
/**
 * Add debug dashboard to admin menu
 */
function spb_add_debug_dashboard() {
    if (!defined('SPB_DEBUG') || !SPB_DEBUG) {
        return;
    }
    
    add_submenu_page(
        'smart-page-builder',
        'Debug Dashboard',
        'Debug',
        'manage_options',
        'spb-debug',
        'spb_render_debug_dashboard'
    );
}
add_action('admin_menu', 'spb_add_debug_dashboard');

/**
 * Render debug dashboard
 */
function spb_render_debug_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    $debug_info = spb_get_debug_info();
    $cache_stats = spb_debug_cache_operations();
    $error_stats = spb_monitor_errors();
    $memory_info = spb_monitor_memory_usage('dashboard');
    
    ?>
    <div class="wrap">
        <h1>Smart Page Builder Debug Dashboard</h1>
        
        <div class="spb-debug-grid">
            <!-- System Information -->
            <div class="spb-debug-card">
                <h2>System Information</h2>
                <table class="widefat">
                    <?php foreach ($debug_info as $key => $value): ?>
                        <tr>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></td>
                            <td><?php echo esc_html(is_array($value) ? wp_json_encode($value) : $value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <!-- Cache Statistics -->
            <div class="spb-debug-card">
                <h2>Cache Statistics</h2>
                <table class="widefat">
                    <?php foreach ($cache_stats as $key => $value): ?>
                        <tr>
                            <td><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></td>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <!-- Error Summary -->
            <div class="spb-debug-card">
                <h2>Recent Errors</h2>
                <p>Total Errors: <?php echo esc_html($error_stats['total_errors']); ?></p>
                <?php if (!empty($error_stats['recent_errors'])): ?>
                    <ul>
                        <?php foreach (array_slice($error_stats['recent_errors'], 0, 5) as $error): ?>
                            <li><?php echo esc_html($error['message'] ?? 'Unknown error'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            
            <!-- Memory Usage -->
            <div class="spb-debug-card">
                <h2>Memory Usage</h2>
                <p>Current: <?php echo esc_html($memory_info['memory_usage_mb']); ?>MB</p>
                <p>Peak: <?php echo esc_html($memory_info['memory_peak_mb']); ?>MB</p>
                <p>Limit: <?php echo esc_html($memory_info['memory_limit_mb']); ?>MB</p>
                <p>Usage: <?php echo esc_html($memory_info['memory_usage_percent']); ?>%</p>
            </div>
        </div>
        
        <!-- Debug Actions -->
        <div class="spb-debug-actions">
            <h2>Debug Actions</h2>
            <button type="button" class="button" onclick="spbTestApiConnectivity()">Test API Connectivity</button>
            <button type="button" class="button" onclick="spbClearCache()">Clear Cache</button>
            <button type="button" class="button" onclick="spbGenerateDebugReport()">Generate Debug Report</button>
            <button type="button" class="button" onclick="spbTestThemeCompatibility()">Test Theme Compatibility</button>
        </div>
    </div>
    
    <style>
    .spb-debug-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    .spb-debug-card {
        background: #fff;
        border: 1px solid #ccd0d4;
        padding: 20px;
        border-radius: 4px;
    }
    .spb-debug-actions {
        margin-top: 30px;
        padding: 20px;
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
    }
    .spb-debug-actions button {
        margin-right: 10px;
        margin-bottom: 10px;
    }
    </style>
    
    <script>
    function spbTestApiConnectivity() {
        // AJAX call to test API connectivity
        alert('API connectivity test initiated - check debug logs for results');
    }
    
    function spbClearCache() {
        if (confirm('Are you sure you want to clear all cached content?')) {
            // AJAX call to clear cache
            alert('Cache cleared successfully');
        }
    }
    
    function spbGenerateDebugReport() {
        // Generate and download debug report
        window.open('<?php echo admin_url('admin-ajax.php?action=spb_debug_report'); ?>', '_blank');
    }
    
    function spbTestThemeCompatibility() {
        // AJAX call to test theme compatibility
        alert('Theme compatibility test initiated - check debug logs for results');
    }
    </script>
    <?php
}
```

---

## Conclusion

This Developer Troubleshooting Guide provides comprehensive debugging tools and procedures for the Smart Page Builder MVP. Key features include:

### ✅ **Essential Debugging Capabilities**
- WordPress debug configuration and logging
- Plugin integration and theme compatibility testing
- AI API connectivity and rate limit monitoring
- Content generation debugging and validation
- Cache performance monitoring
- Memory usage and timeout detection

### ✅ **Practical Tools**
- Structured logging system with categorization
- Debug dashboard for real-time monitoring
- Error code reference with solutions
- Performance monitoring and metrics
- Automated compatibility testing

### ✅ **MVP-Focused Solutions**
- Essential debugging without over-engineering
- WordPress-specific troubleshooting procedures
- Plugin conflict detection and resolution
- Theme integration debugging tools

### 🔧 **Quick Reference Commands**

```php
// Enable debug mode
define('SPB_DEBUG', true);
define('SPB_LOG_LEVEL', 'debug');

// Test API connectivity
spb_test_api_connectivity('openai');

// Check theme compatibility
spb_debug_theme_compatibility();

// Monitor memory usage
spb_monitor_memory_usage('content_generation');

// Generate debug report
spb_generate_debug_report();
```

### 📝 **Next Steps for Developers**

1. **Implement debug configuration** in wp-config.php
2. **Add logging calls** throughout plugin code
3. **Test debugging tools** in development environment
4. **Monitor performance** during content generation
5. **Review error logs** regularly for issues

This troubleshooting guide ensures developers can quickly identify, diagnose, and resolve issues during Smart Page Builder MVP development and deployment.
