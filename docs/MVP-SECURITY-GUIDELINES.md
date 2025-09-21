# Smart Page Builder - MVP Security Guidelines

## Document Overview

This document provides essential security guidelines for the Smart Page Builder MVP development. These guidelines establish secure coding patterns from day one and protect against common WordPress plugin security vulnerabilities.

**Target Audience:** Developers building the Smart Page Builder plugin  
**Scope:** MVP security requirements and implementation guidelines  
**Priority:** CRITICAL - Must be implemented before any production deployment

---

## Table of Contents

1. [Security Principles](#security-principles)
2. [API Key Management](#api-key-management)
3. [Input Validation & Sanitization](#input-validation--sanitization)
4. [Content Security](#content-security)
5. [Access Control & Permissions](#access-control--permissions)
6. [Data Transmission Security](#data-transmission-security)
7. [Error Handling & Information Disclosure](#error-handling--information-disclosure)
8. [WordPress Security Best Practices](#wordpress-security-best-practices)
9. [Security Checklist](#security-checklist)

---

## Security Principles

### Core Security Philosophy

**1. Defense in Depth**
- Multiple layers of security controls
- Fail-safe defaults (deny by default)
- Principle of least privilege

**2. Data Minimization**
- Collect only necessary data
- Transmit minimal data to AI providers
- Store only essential information

**3. Transparency & Control**
- Clear user consent for data usage
- Admin controls for security settings
- Audit trails for sensitive operations

---

## API Key Management

### Storage Requirements

#### ✅ **REQUIRED: Encrypted Storage**

```php
/**
 * Store API key with encryption
 * NEVER store API keys in plain text
 */
function spb_store_api_key($provider, $api_key) {
    // Validate input
    if (empty($api_key) || !is_string($api_key)) {
        return new WP_Error('invalid_key', 'Invalid API key provided');
    }
    
    // Encrypt the API key
    $encrypted_key = spb_encrypt_data($api_key);
    
    // Store with provider prefix
    $option_name = 'spb_' . sanitize_key($provider) . '_api_key';
    return update_option($option_name, $encrypted_key);
}

/**
 * Retrieve and decrypt API key
 */
function spb_get_api_key($provider) {
    $option_name = 'spb_' . sanitize_key($provider) . '_api_key';
    $encrypted_key = get_option($option_name);
    
    if (empty($encrypted_key)) {
        return false;
    }
    
    return spb_decrypt_data($encrypted_key);
}

/**
 * Simple encryption using WordPress salts
 * For MVP - consider stronger encryption for production
 */
function spb_encrypt_data($data) {
    if (!defined('AUTH_KEY') || !defined('SECURE_AUTH_KEY')) {
        wp_die('WordPress security keys not configured');
    }
    
    $key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    
    return base64_encode($iv . $encrypted);
}

function spb_decrypt_data($encrypted_data) {
    if (!defined('AUTH_KEY') || !defined('SECURE_AUTH_KEY')) {
        return false;
    }
    
    $key = hash('sha256', AUTH_KEY . SECURE_AUTH_KEY);
    $data = base64_decode($encrypted_data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}
```

#### ❌ **NEVER DO:**
```php
// NEVER store API keys in plain text
update_option('spb_api_key', $api_key); // INSECURE

// NEVER expose API keys in frontend
wp_localize_script('spb-script', 'spb_config', [
    'api_key' => $api_key // INSECURE
]);

// NEVER log API keys
error_log('API Key: ' . $api_key); // INSECURE
```

### Key Validation

```php
/**
 * Validate API key format before storage
 */
function spb_validate_api_key($provider, $api_key) {
    $patterns = [
        'openai' => '/^sk-[a-zA-Z0-9]{48}$/',
        'anthropic' => '/^sk-ant-[a-zA-Z0-9\-]{95}$/',
        // Add other provider patterns
    ];
    
    if (!isset($patterns[$provider])) {
        return new WP_Error('unsupported_provider', 'Unsupported AI provider');
    }
    
    if (!preg_match($patterns[$provider], $api_key)) {
        return new WP_Error('invalid_format', 'Invalid API key format');
    }
    
    return true;
}
```

---

## Input Validation & Sanitization

### User Input Handling

#### ✅ **REQUIRED: Comprehensive Sanitization**

```php
/**
 * Sanitize content generation parameters
 */
function spb_sanitize_generation_params($params) {
    $sanitized = [];
    
    // Content type validation
    $allowed_types = ['tool_recommendation', 'project_step', 'safety_tip', 'related_content'];
    $sanitized['type'] = in_array($params['type'], $allowed_types) ? $params['type'] : 'tool_recommendation';
    
    // Project type sanitization
    $sanitized['project_type'] = sanitize_text_field($params['project_type'] ?? '');
    
    // Difficulty level validation
    $allowed_difficulties = ['beginner', 'intermediate', 'advanced'];
    $sanitized['difficulty'] = in_array($params['difficulty'], $allowed_difficulties) ? $params['difficulty'] : 'beginner';
    
    // Content length limits
    $sanitized['max_length'] = absint($params['max_length'] ?? 500);
    $sanitized['max_length'] = min($sanitized['max_length'], 2000); // Hard limit
    
    // Boolean options
    $sanitized['include_images'] = !empty($params['include_images']);
    
    return $sanitized;
}

/**
 * Sanitize content for AI processing
 */
function spb_sanitize_content_for_ai($content) {
    // Remove potentially sensitive information
    $content = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '[EMAIL]', $content);
    $content = preg_replace('/\b\d{3}-\d{3}-\d{4}\b/', '[PHONE]', $content);
    $content = preg_replace('/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', '[CARD]', $content);
    
    // Strip HTML tags but preserve structure
    $content = wp_strip_all_tags($content, true);
    
    // Limit content length
    $content = wp_trim_words($content, 500);
    
    return $content;
}
```

### Admin Settings Validation

```php
/**
 * Validate and sanitize admin settings
 */
function spb_validate_admin_settings($settings) {
    $validated = [];
    
    // Cache duration validation
    $cache_duration = absint($settings['cache_duration'] ?? 3600);
    $validated['cache_duration'] = max(300, min($cache_duration, 86400)); // 5 min to 24 hours
    
    // User role validation
    $allowed_roles = ['subscriber', 'contributor', 'author', 'editor', 'administrator'];
    $validated['min_user_role'] = in_array($settings['min_user_role'], $allowed_roles) 
        ? $settings['min_user_role'] 
        : 'author';
    
    // Boolean settings
    $validated['auto_generate'] = !empty($settings['auto_generate']);
    $validated['content_moderation'] = !empty($settings['content_moderation']);
    
    return $validated;
}
```

---

## Content Security

### Generated Content Sanitization

#### ✅ **REQUIRED: Content Filtering**

```php
/**
 * Sanitize AI-generated content before display
 */
function spb_sanitize_generated_content($content) {
    // Allow specific HTML tags for formatting
    $allowed_tags = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'blockquote' => [],
        'a' => ['href' => [], 'title' => [], 'target' => []]
    ];
    
    // Sanitize HTML content
    $content = wp_kses($content, $allowed_tags);
    
    // Validate and sanitize URLs
    $content = preg_replace_callback(
        '/href=["\']([^"\']*)["\']/',
        function($matches) {
            $url = esc_url($matches[1]);
            return $url ? 'href="' . $url . '"' : '';
        },
        $content
    );
    
    return $content;
}

/**
 * Content moderation filters
 */
function spb_moderate_content($content) {
    // Basic inappropriate content filtering
    $blocked_patterns = [
        '/\b(spam|scam|fraud)\b/i',
        '/\b(dangerous|unsafe|illegal)\b/i',
        // Add more patterns as needed
    ];
    
    foreach ($blocked_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return new WP_Error('content_blocked', 'Content flagged by moderation filters');
        }
    }
    
    return $content;
}

/**
 * Content audit logging
 */
function spb_log_generated_content($content_type, $content, $context) {
    if (!defined('SPB_AUDIT_LOG') || !SPB_AUDIT_LOG) {
        return;
    }
    
    $log_entry = [
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'content_type' => $content_type,
        'content_length' => strlen($content),
        'context' => wp_json_encode($context),
        'ip_address' => spb_get_client_ip()
    ];
    
    // Store in custom table or use WordPress logging
    spb_store_audit_log($log_entry);
}
```

### Content Validation

```php
/**
 * Validate generated content quality and safety
 */
function spb_validate_generated_content($content, $type) {
    $validation_rules = [
        'tool_recommendation' => [
            'min_length' => 50,
            'max_length' => 1000,
            'required_elements' => ['tool', 'purpose']
        ],
        'safety_tip' => [
            'min_length' => 30,
            'max_length' => 500,
            'required_elements' => ['safety', 'warning']
        ]
    ];
    
    if (!isset($validation_rules[$type])) {
        return new WP_Error('invalid_type', 'Unknown content type');
    }
    
    $rules = $validation_rules[$type];
    
    // Length validation
    $content_length = strlen(wp_strip_all_tags($content));
    if ($content_length < $rules['min_length']) {
        return new WP_Error('content_too_short', 'Generated content is too short');
    }
    
    if ($content_length > $rules['max_length']) {
        return new WP_Error('content_too_long', 'Generated content is too long');
    }
    
    // Element validation
    foreach ($rules['required_elements'] as $element) {
        if (stripos($content, $element) === false) {
            return new WP_Error('missing_element', "Content missing required element: {$element}");
        }
    }
    
    return true;
}
```

---

## Access Control & Permissions

### User Capability Management

#### ✅ **REQUIRED: Proper Capability Checks**

```php
/**
 * Define custom capabilities
 */
function spb_add_capabilities() {
    $admin_role = get_role('administrator');
    $editor_role = get_role('editor');
    
    // Add custom capabilities
    if ($admin_role) {
        $admin_role->add_cap('spb_manage_settings');
        $admin_role->add_cap('spb_generate_content');
        $admin_role->add_cap('spb_view_analytics');
    }
    
    if ($editor_role) {
        $editor_role->add_cap('spb_generate_content');
    }
}
add_action('admin_init', 'spb_add_capabilities');

/**
 * Check user permissions for content generation
 */
function spb_user_can_generate_content($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    // Check custom capability first
    if (user_can($user_id, 'spb_generate_content')) {
        return true;
    }
    
    // Fallback to configurable minimum role
    $min_role = get_option('spb_min_user_role', 'author');
    $user = get_userdata($user_id);
    
    if (!$user) {
        return false;
    }
    
    $role_hierarchy = [
        'subscriber' => 1,
        'contributor' => 2,
        'author' => 3,
        'editor' => 4,
        'administrator' => 5
    ];
    
    $user_level = 0;
    foreach ($user->roles as $role) {
        if (isset($role_hierarchy[$role])) {
            $user_level = max($user_level, $role_hierarchy[$role]);
        }
    }
    
    $min_level = $role_hierarchy[$min_role] ?? 3;
    
    return $user_level >= $min_level;
}

/**
 * Secure admin settings access
 */
function spb_check_admin_access() {
    if (!current_user_can('spb_manage_settings') && !current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Verify nonce for all admin actions
    if ($_POST && !wp_verify_nonce($_POST['spb_nonce'], 'spb_admin_action')) {
        wp_die(__('Security check failed. Please try again.'));
    }
}
```

### AJAX Security

```php
/**
 * Secure AJAX content generation
 */
function spb_ajax_generate_content() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'spb_generate_content')) {
        wp_die('Security check failed');
    }
    
    // Check user permissions
    if (!spb_user_can_generate_content()) {
        wp_send_json_error('Insufficient permissions');
        return;
    }
    
    // Rate limiting check
    if (!spb_check_rate_limit(get_current_user_id())) {
        wp_send_json_error('Rate limit exceeded');
        return;
    }
    
    // Sanitize input
    $params = spb_sanitize_generation_params($_POST);
    
    // Generate content
    $result = spb_generate_content($params['type'], $params);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    } else {
        wp_send_json_success($result);
    }
}
add_action('wp_ajax_spb_generate_content', 'spb_ajax_generate_content');
```

---

## Data Transmission Security

### AI Provider Communication

#### ✅ **REQUIRED: Secure API Calls**

```php
/**
 * Secure API request to AI provider
 */
function spb_make_secure_api_request($provider, $endpoint, $data) {
    // Get encrypted API key
    $api_key = spb_get_api_key($provider);
    if (!$api_key) {
        return new WP_Error('no_api_key', 'API key not configured');
    }
    
    // Sanitize data before transmission
    $sanitized_data = spb_sanitize_api_data($data);
    
    // Prepare request
    $args = [
        'method' => 'POST',
        'timeout' => 30,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Smart-Page-Builder/' . SPB_VERSION
        ],
        'body' => wp_json_encode($sanitized_data),
        'sslverify' => true // Always verify SSL
    ];
    
    // Make request
    $response = wp_remote_request($endpoint, $args);
    
    // Clear sensitive data from memory
    unset($api_key, $args['headers']['Authorization']);
    
    if (is_wp_error($response)) {
        spb_log_api_error($provider, $response->get_error_message());
        return $response;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        $error_message = wp_remote_retrieve_body($response);
        spb_log_api_error($provider, "HTTP {$response_code}: {$error_message}");
        return new WP_Error('api_error', 'API request failed');
    }
    
    return wp_remote_retrieve_body($response);
}

/**
 * Sanitize data before sending to AI provider
 */
function spb_sanitize_api_data($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        switch ($key) {
            case 'content':
                $sanitized[$key] = spb_sanitize_content_for_ai($value);
                break;
            case 'max_tokens':
                $sanitized[$key] = min(absint($value), 2000);
                break;
            case 'temperature':
                $sanitized[$key] = max(0, min(floatval($value), 1));
                break;
            default:
                $sanitized[$key] = sanitize_text_field($value);
        }
    }
    
    return $sanitized;
}
```

### Data Minimization

```php
/**
 * Minimize data sent to AI providers
 */
function spb_prepare_minimal_context($post_id, $content_type) {
    $context = [];
    
    // Only include essential post data
    $post = get_post($post_id);
    if ($post) {
        $context['title'] = wp_strip_all_tags($post->post_title);
        $context['excerpt'] = wp_trim_words(wp_strip_all_tags($post->post_excerpt), 50);
        
        // Get categories but not sensitive metadata
        $categories = get_the_category($post_id);
        $context['categories'] = array_map(function($cat) {
            return $cat->name;
        }, $categories);
    }
    
    // Add content-type specific context
    switch ($content_type) {
        case 'tool_recommendation':
            $context['focus'] = 'tools and equipment';
            break;
        case 'safety_tip':
            $context['focus'] = 'safety and precautions';
            break;
    }
    
    return $context;
}
```

---

## Error Handling & Information Disclosure

### Secure Error Handling

#### ✅ **REQUIRED: Safe Error Messages**

```php
/**
 * Sanitize error messages for user display
 */
function spb_get_safe_error_message($error_code, $debug_message = '') {
    $safe_messages = [
        'api_error' => 'Content generation temporarily unavailable. Please try again later.',
        'invalid_input' => 'Invalid input provided. Please check your settings.',
        'permission_denied' => 'You do not have permission to perform this action.',
        'rate_limit' => 'Too many requests. Please wait before trying again.',
        'content_blocked' => 'Content could not be generated due to policy restrictions.'
    ];
    
    $safe_message = $safe_messages[$error_code] ?? 'An error occurred. Please try again.';
    
    // Log detailed error for debugging (not shown to user)
    if (defined('WP_DEBUG') && WP_DEBUG && !empty($debug_message)) {
        error_log("SPB Error [{$error_code}]: {$debug_message}");
    }
    
    return $safe_message;
}

/**
 * Log security events without exposing sensitive data
 */
function spb_log_security_event($event_type, $details = []) {
    $log_entry = [
        'timestamp' => current_time('mysql'),
        'event_type' => sanitize_key($event_type),
        'user_id' => get_current_user_id(),
        'ip_address' => spb_get_client_ip(),
        'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'details' => wp_json_encode($details)
    ];
    
    // Store in secure log (implement based on requirements)
    spb_store_security_log($log_entry);
}

/**
 * Get client IP safely
 */
function spb_get_client_ip() {
    $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = sanitize_text_field($_SERVER[$key]);
            // Handle comma-separated IPs (from proxies)
            $ip = trim(explode(',', $ip)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return 'unknown';
}
```

---

## WordPress Security Best Practices

### Database Security

```php
/**
 * Secure database operations
 */
function spb_create_secure_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'spb_generated_content';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        cache_key varchar(255) NOT NULL,
        content_type varchar(100) NOT NULL,
        content_html longtext,
        content_text longtext,
        context_data longtext,
        generation_time decimal(10,3),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        expires_at datetime,
        user_id bigint(20) unsigned,
        ip_address varchar(45),
        PRIMARY KEY (id),
        UNIQUE KEY cache_key (cache_key),
        KEY content_type (content_type),
        KEY expires_at (expires_at),
        KEY user_id (user_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Secure database queries
 */
function spb_get_cached_content_secure($cache_key, $user_id = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'spb_generated_content';
    
    // Use prepared statements
    $query = $wpdb->prepare(
        "SELECT content_html, content_text, expires_at 
         FROM $table_name 
         WHERE cache_key = %s 
         AND (expires_at IS NULL OR expires_at > NOW())
         AND (user_id = %d OR user_id IS NULL)
         LIMIT 1",
        $cache_key,
        $user_id ?? get_current_user_id()
    );
    
    return $wpdb->get_row($query);
}
```

### File Security

```php
/**
 * Secure file operations
 */
function spb_secure_file_upload($file) {
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return new WP_Error('invalid_file_type', 'File type not allowed');
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return new WP_Error('file_too_large', 'File size exceeds limit');
    }
    
    // Sanitize filename
    $filename = sanitize_file_name($file['name']);
    
    // Use WordPress upload handling
    $upload = wp_handle_upload($file, ['test_form' => false]);
    
    if (isset($upload['error'])) {
        return new WP_Error('upload_failed', $upload['error']);
    }
    
    return $upload;
}
```

---

## Security Checklist

### Pre-Development Checklist

- [ ] **API Key Encryption** - Implement encrypted storage for all API keys
- [ ] **Input Validation** - Create sanitization functions for all user inputs
- [ ] **Content Filtering** - Implement content moderation and sanitization
- [ ] **Access Control** - Define user capabilities and permission checks
- [ ] **Error Handling** - Create safe error messages that don't expose sensitive data
- [ ] **Audit Logging** - Implement security event logging
- [ ] **Rate Limiting** - Plan rate limiting strategy for API calls

### Development Phase Checklist

- [ ] **Nonce Verification** - Add nonces to all forms and AJAX requests
- [ ] **Capability Checks** - Verify user permissions for all actions
- [ ] **SQL Injection Prevention** - Use prepared statements for all database queries
- [ ] **XSS Prevention** - Escape all output using appropriate WordPress functions
- [ ] **CSRF Protection** - Implement proper nonce verification
- [ ] **File Upload Security** - Validate and sanitize all file uploads
- [ ] **API Security** - Secure all external API communications

### Pre-Production Checklist

- [ ] **Security Audit** - Conduct comprehensive security review
- [ ] **Penetration Testing** - Test for common vulnerabilities
- [ ] **Code Review** - Review all code for security issues
- [ ] **Dependency Check** - Audit all third-party dependencies
- [ ] **Configuration Review** - Verify secure default settings
- [ ] **Documentation Review** - Ensure security guidelines are followed
- [ ] **Backup Strategy** - Implement secure backup procedures

### Ongoing Security Checklist

- [ ] **Regular Updates** - Keep WordPress and dependencies updated
- [ ] **Security Monitoring** - Monitor for suspicious activity
- [ ] **Log Review** - Regularly review security logs
- [ ] **Vulnerability Scanning** - Regular security scans
- [ ] **Access Review** - Periodic review of user permissions
- [ ] **Incident Response** - Maintain incident response procedures

---

## Security Resources

### WordPress Security References

- [WordPress Security Handbook](https://developer.wordpress.org/plugins/security/)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Plugin Security Guidelines](https://developer.wordpress.org/plugins/security/)
- [Data Validation](https://developer.wordpress.org/plugins/security/data-validation/)

### Security Tools

- **WordPress Security Plugins:** Wordfence, Sucuri, iThemes Security
- **Code Analysis:** PHP_CodeSniffer with WordPress rules
- **Vulnerability Scanning:** WPScan, Sucuri SiteCheck
- **Security Headers:** Security Headers checker

### Emergency Contacts

- **WordPress Security Team:** security@wordpress.org
- **Plugin Review Team:** plugins@wordpress.org
- **Hosting Security:** Contact your hosting provider's security team

---

## Conclusion

These security guidelines provide the foundation for secure Smart Page Builder MVP development. **All guidelines marked as "REQUIRED" must be implemented before any production deployment.**

Remember:
- **Security is not optional** - Implement from day one
- **Defense in depth** - Multiple security layers
- **Fail securely** - Default to deny access
- **Log everything** - Maintain audit trails
- **Regular reviews** - Security is an ongoing process

**Next Steps:**
1. Implement all REQUIRED security measures
2. Conduct security code review
3. Test security implementations
4. Document security procedures
5. Plan ongoing security maintenance

For questions or security concerns, consult the WordPress Security Handbook and consider professional security review before production deployment.
