# Smart Page Builder - API Documentation

## Document Overview

This document provides comprehensive API documentation for the Smart Page Builder plugin, including all functions, hooks, filters, classes, and integration points. The Smart Page Builder is designed to work seamlessly with the DIY Home Improvement WordPress theme and provides AI-powered content generation capabilities.

## Table of Contents

1. [Core API Functions](#core-api-functions)
2. [WordPress Hooks & Filters](#wordpress-hooks--filters)
3. [Theme Integration API](#theme-integration-api)
4. [Content Generation API](#content-generation-api)
5. [Admin Interface API](#admin-interface-api)
6. [JavaScript API](#javascript-api)
7. [REST API Endpoints](#rest-api-endpoints)
8. [Database Schema](#database-schema)
9. [Configuration API](#configuration-api)
10. [Extension API](#extension-api)

---

## Core API Functions

### Plugin Initialization

#### `smart_page_builder_init()`
Initializes the Smart Page Builder plugin and registers all necessary hooks.

```php
function smart_page_builder_init() {
    // Initialize plugin components
    Smart_Page_Builder_Core::instance();
    Smart_Page_Builder_Admin::instance();
    Smart_Page_Builder_Frontend::instance();
    
    // Register activation/deactivation hooks
    register_activation_hook(__FILE__, 'smart_page_builder_activate');
    register_deactivation_hook(__FILE__, 'smart_page_builder_deactivate');
}
add_action('plugins_loaded', 'smart_page_builder_init');
```

**Parameters:** None  
**Returns:** `void`  
**Since:** 1.0.0

#### `smart_page_builder_activate()`
Handles plugin activation tasks including database table creation and default settings.

```php
function smart_page_builder_activate() {
    // Create database tables
    Smart_Page_Builder_Database::create_tables();
    
    // Set default options
    Smart_Page_Builder_Settings::set_defaults();
    
    // Schedule cron jobs
    Smart_Page_Builder_Cron::schedule_events();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
```

**Parameters:** None  
**Returns:** `void`  
**Since:** 1.0.0

### Content Generation Functions

#### `spb_generate_content($type, $context, $options)`
Generates AI-powered content based on specified type and context.

```php
function spb_generate_content($type, $context = [], $options = []) {
    $generator = new Smart_Page_Builder_Generator();
    
    $defaults = [
        'max_length' => 500,
        'tone' => 'professional',
        'include_images' => true,
        'cache_duration' => 3600
    ];
    
    $options = wp_parse_args($options, $defaults);
    
    return $generator->generate($type, $context, $options);
}
```

**Parameters:**
- `$type` (string) - Content type: 'tool_recommendation', 'project_step', 'safety_tip', 'related_content'
- `$context` (array) - Context data for content generation
- `$options` (array) - Generation options and settings

**Returns:** `array|WP_Error` - Generated content array or error object  
**Since:** 1.0.0

**Example Usage:**
```php
// Generate tool recommendations for a plumbing project
$content = spb_generate_content('tool_recommendation', [
    'project_type' => 'plumbing',
    'difficulty' => 'beginner',
    'post_id' => 123
], [
    'max_length' => 300,
    'include_images' => true
]);

if (!is_wp_error($content)) {
    echo $content['html'];
}
```

#### `spb_get_cached_content($cache_key)`
Retrieves cached generated content.

```php
function spb_get_cached_content($cache_key) {
    return Smart_Page_Builder_Cache::get($cache_key);
}
```

**Parameters:**
- `$cache_key` (string) - Unique cache identifier

**Returns:** `mixed|false` - Cached content or false if not found  
**Since:** 1.0.0

#### `spb_cache_content($cache_key, $content, $expiration)`
Caches generated content for future use.

```php
function spb_cache_content($cache_key, $content, $expiration = 3600) {
    return Smart_Page_Builder_Cache::set($cache_key, $content, $expiration);
}
```

**Parameters:**
- `$cache_key` (string) - Unique cache identifier
- `$content` (mixed) - Content to cache
- `$expiration` (int) - Cache expiration in seconds

**Returns:** `bool` - Success status  
**Since:** 1.0.0

### Theme Integration Functions

#### `spb_render_placeholder($type, $args)`
Renders a Smart Page Builder placeholder in the theme.

```php
function spb_render_placeholder($type = 'default', $args = []) {
    $defaults = [
        'title' => __('Smart Content Loading...', 'smart-page-builder'),
        'description' => __('AI-powered content will appear here.', 'smart-page-builder'),
        'icon' => 'dashicons-admin-tools',
        'show_loading' => true,
        'auto_load' => true
    ];
    
    $args = wp_parse_args($args, $defaults);
    
    // Apply filters for customization
    $args = apply_filters('spb_placeholder_args', $args, $type);
    
    Smart_Page_Builder_Frontend::render_placeholder($type, $args);
}
```

**Parameters:**
- `$type` (string) - Placeholder type identifier
- `$args` (array) - Placeholder configuration options

**Returns:** `void` - Outputs HTML directly  
**Since:** 1.0.0

**Example Usage:**
```php
// In theme template
spb_render_placeholder('tool_recommendation', [
    'title' => 'Recommended Tools',
    'description' => 'AI will suggest the best tools for this project',
    'auto_load' => true
]);
```

#### `spb_is_active()`
Checks if Smart Page Builder plugin is active and properly configured.

```php
function spb_is_active() {
    return class_exists('Smart_Page_Builder_Core') && 
           Smart_Page_Builder_Settings::is_configured();
}
```

**Parameters:** None  
**Returns:** `bool` - Plugin active status  
**Since:** 1.0.0

---

## WordPress Hooks & Filters

### Action Hooks

#### `spb_before_content_generation`
Fired before content generation begins.

```php
do_action('spb_before_content_generation', $type, $context, $options);
```

**Parameters:**
- `$type` (string) - Content type being generated
- `$context` (array) - Generation context
- `$options` (array) - Generation options

**Since:** 1.0.0

#### `spb_after_content_generation`
Fired after content generation completes.

```php
do_action('spb_after_content_generation', $content, $type, $context);
```

**Parameters:**
- `$content` (array) - Generated content
- `$type` (string) - Content type
- `$context` (array) - Generation context

**Since:** 1.0.0

#### `spb_placeholder_rendered`
Fired when a placeholder is rendered in the frontend.

```php
do_action('spb_placeholder_rendered', $type, $args, $html);
```

**Parameters:**
- `$type` (string) - Placeholder type
- `$args` (array) - Placeholder arguments
- `$html` (string) - Rendered HTML

**Since:** 1.0.0

### Filter Hooks

#### `spb_content_types`
Filters available content types for generation.

```php
$content_types = apply_filters('spb_content_types', [
    'tool_recommendation' => 'Tool Recommendations',
    'project_step' => 'Project Steps',
    'safety_tip' => 'Safety Tips',
    'related_content' => 'Related Content',
    'material_list' => 'Material Lists'
]);
```

**Parameters:**
- `$content_types` (array) - Array of content type key => label pairs

**Returns:** `array` - Filtered content types  
**Since:** 1.0.0

#### `spb_generation_context`
Filters the context data before content generation.

```php
$context = apply_filters('spb_generation_context', $context, $type, $post_id);
```

**Parameters:**
- `$context` (array) - Context data
- `$type` (string) - Content type
- `$post_id` (int) - Current post ID

**Returns:** `array` - Filtered context data  
**Since:** 1.0.0

#### `spb_generated_content`
Filters generated content before caching and display.

```php
$content = apply_filters('spb_generated_content', $content, $type, $context);
```

**Parameters:**
- `$content` (array) - Generated content
- `type` (string) - Content type
- `$context` (array) - Generation context

**Returns:** `array` - Filtered content  
**Since:** 1.0.0

#### `spb_placeholder_html`
Filters placeholder HTML before output.

```php
$html = apply_filters('spb_placeholder_html', $html, $type, $args);
```

**Parameters:**
- `$html` (string) - Placeholder HTML
- `$type` (string) - Placeholder type
- `$args` (array) - Placeholder arguments

**Returns:** `string` - Filtered HTML  
**Since:** 1.0.0

---

## Theme Integration API

### DIY Theme Hooks

The Smart Page Builder integrates with the DIY Home Improvement theme through specific action hooks:

#### `diy_before_posts_loop`
Hook for content before the posts loop on archive pages.

```php
function spb_archive_content() {
    if (spb_is_active() && is_category()) {
        $category = get_queried_object();
        spb_render_placeholder('category_intro', [
            'title' => sprintf(__('About %s Projects', 'smart-page-builder'), $category->name),
            'context' => ['category_id' => $category->term_id]
        ]);
    }
}
add_action('diy_before_posts_loop', 'spb_archive_content');
```

#### `diy_after_post_excerpt`
Hook for content after each post excerpt.

```php
function spb_post_enhancement($post_id) {
    if (spb_is_active()) {
        spb_render_placeholder('related_tools', [
            'title' => __('Tools for This Project', 'smart-page-builder'),
            'context' => ['post_id' => $post_id],
            'auto_load' => true
        ]);
    }
}
add_action('diy_after_post_excerpt', 'spb_post_enhancement');
```

#### `diy_footer_smart_content`
Hook for smart content in the footer area.

```php
function spb_footer_content() {
    if (spb_is_active() && is_single()) {
        spb_render_placeholder('project_tips', [
            'title' => __('Pro Tips', 'smart-page-builder'),
            'description' => __('Expert advice for your DIY project', 'smart-page-builder')
        ]);
    }
}
add_action('diy_footer_smart_content', 'spb_footer_content');
```

### Theme Detection

#### `spb_is_diy_theme_active()`
Checks if the DIY Home Improvement theme is currently active.

```php
function spb_is_diy_theme_active() {
    $theme = wp_get_theme();
    return $theme->get('Name') === 'DIY Home Improvement' || 
           $theme->get('Template') === 'diy-home-improvement';
}
```

**Parameters:** None  
**Returns:** `bool` - Theme active status  
**Since:** 1.0.0

---

## Content Generation API

### Content Types

#### Tool Recommendations
Generates tool suggestions based on project context.

```php
$tools = spb_generate_content('tool_recommendation', [
    'project_type' => 'electrical',
    'difficulty' => 'intermediate',
    'budget_range' => 'medium',
    'post_content' => get_post_field('post_content', $post_id)
]);
```

**Context Parameters:**
- `project_type` (string) - Type of DIY project
- `difficulty` (string) - Project difficulty level
- `budget_range` (string) - Budget consideration
- `post_content` (string) - Post content for analysis

#### Project Steps
Generates detailed step-by-step instructions.

```php
$steps = spb_generate_content('project_step', [
    'project_title' => get_the_title(),
    'materials' => ['wood', 'screws', 'drill'],
    'skill_level' => 'beginner',
    'estimated_time' => '2 hours'
]);
```

#### Safety Tips
Generates safety recommendations for projects.

```php
$safety = spb_generate_content('safety_tip', [
    'tools_used' => ['power_drill', 'saw'],
    'project_type' => 'woodworking',
    'workspace' => 'garage'
]);
```

### AI Provider Integration

#### `spb_register_ai_provider($provider_id, $provider_class)`
Registers a new AI content generation provider.

```php
function spb_register_ai_provider($provider_id, $provider_class) {
    Smart_Page_Builder_Providers::register($provider_id, $provider_class);
}

// Example: Register OpenAI provider
spb_register_ai_provider('openai', 'Smart_Page_Builder_OpenAI_Provider');
```

**Parameters:**
- `$provider_id` (string) - Unique provider identifier
- `$provider_class` (string) - Provider class name

**Returns:** `bool` - Registration success  
**Since:** 1.0.0

---

## Admin Interface API

### Settings API

#### `spb_get_setting($key, $default)`
Retrieves a plugin setting value.

```php
function spb_get_setting($key, $default = null) {
    return Smart_Page_Builder_Settings::get($key, $default);
}

// Example usage
$api_key = spb_get_setting('openai_api_key');
$cache_duration = spb_get_setting('cache_duration', 3600);
```

**Parameters:**
- `$key` (string) - Setting key
- `$default` (mixed) - Default value if setting not found

**Returns:** `mixed` - Setting value  
**Since:** 1.0.0

#### `spb_update_setting($key, $value)`
Updates a plugin setting value.

```php
function spb_update_setting($key, $value) {
    return Smart_Page_Builder_Settings::update($key, $value);
}

// Example usage
spb_update_setting('generation_limit', 100);
spb_update_setting('auto_generate', true);
```

**Parameters:**
- `$key` (string) - Setting key
- `$value` (mixed) - Setting value

**Returns:** `bool` - Update success  
**Since:** 1.0.0

### Admin Menu Integration

#### `spb_add_admin_menu_item($page_title, $menu_title, $capability, $menu_slug, $callback)`
Adds a custom admin menu item.

```php
function spb_add_admin_menu_item($page_title, $menu_title, $capability, $menu_slug, $callback) {
    add_submenu_page(
        'smart-page-builder',
        $page_title,
        $menu_title,
        $capability,
        $menu_slug,
        $callback
    );
}
```

**Parameters:**
- `$page_title` (string) - Page title
- `$menu_title` (string) - Menu title
- `$capability` (string) - Required capability
- `$menu_slug` (string) - Menu slug
- `$callback` (callable) - Page callback function

**Returns:** `string|false` - Menu hook suffix  
**Since:** 1.0.0

---

## JavaScript API

### Frontend JavaScript

#### `SmartPageBuilder.init(options)`
Initializes the frontend JavaScript functionality.

```javascript
SmartPageBuilder.init({
    ajaxUrl: spb_ajax.url,
    nonce: spb_ajax.nonce,
    autoLoad: true,
    loadingText: 'Generating content...',
    errorText: 'Failed to load content'
});
```

**Parameters:**
- `options` (object) - Configuration options

**Returns:** `void`  
**Since:** 1.0.0

#### `SmartPageBuilder.loadContent(element, type, context)`
Loads content for a specific placeholder element.

```javascript
SmartPageBuilder.loadContent(
    document.getElementById('spb-placeholder-1'),
    'tool_recommendation',
    {
        post_id: 123,
        project_type: 'plumbing'
    }
);
```

**Parameters:**
- `element` (HTMLElement) - Target placeholder element
- `type` (string) - Content type to generate
- `context` (object) - Generation context

**Returns:** `Promise` - Content loading promise  
**Since:** 1.0.0

#### `SmartPageBuilder.on(event, callback)`
Registers event listeners for Smart Page Builder events.

```javascript
SmartPageBuilder.on('contentLoaded', function(data) {
    console.log('Content loaded:', data);
});

SmartPageBuilder.on('contentError', function(error) {
    console.error('Content error:', error);
});
```

**Events:**
- `contentLoaded` - Fired when content is successfully loaded
- `contentError` - Fired when content loading fails
- `placeholderRendered` - Fired when a placeholder is rendered

**Since:** 1.0.0

---

## REST API Endpoints

### Content Generation Endpoints

#### `POST /wp-json/spb/v1/generate`
Generates content via REST API.

**Request:**
```json
{
    "type": "tool_recommendation",
    "context": {
        "post_id": 123,
        "project_type": "electrical"
    },
    "options": {
        "max_length": 300,
        "include_images": true
    }
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "html": "<div class='spb-content'>...</div>",
        "text": "Recommended tools for electrical work...",
        "metadata": {
            "generation_time": 1.23,
            "cache_key": "spb_123_electrical_tools"
        }
    }
}
```

**Authentication:** Requires valid nonce or API key  
**Since:** 1.0.0

#### `GET /wp-json/spb/v1/content/{cache_key}`
Retrieves cached content by cache key.

**Response:**
```json
{
    "success": true,
    "data": {
        "html": "<div class='spb-content'>...</div>",
        "cached_at": "2025-09-20T20:30:00Z",
        "expires_at": "2025-09-20T21:30:00Z"
    }
}
```

**Since:** 1.0.0

### Settings Endpoints

#### `GET /wp-json/spb/v1/settings`
Retrieves plugin settings (admin only).

#### `POST /wp-json/spb/v1/settings`
Updates plugin settings (admin only).

---

## Database Schema

### Tables

#### `wp_spb_generated_content`
Stores generated content and metadata.

```sql
CREATE TABLE wp_spb_generated_content (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    cache_key varchar(255) NOT NULL,
    content_type varchar(100) NOT NULL,
    content_html longtext,
    content_text longtext,
    context_data longtext,
    generation_time decimal(10,3),
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    expires_at datetime,
    PRIMARY KEY (id),
    UNIQUE KEY cache_key (cache_key),
    KEY content_type (content_type),
    KEY expires_at (expires_at)
);
```

#### `wp_spb_usage_stats`
Tracks plugin usage statistics.

```sql
CREATE TABLE wp_spb_usage_stats (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    date date NOT NULL,
    content_type varchar(100) NOT NULL,
    generation_count int(11) DEFAULT 0,
    cache_hits int(11) DEFAULT 0,
    cache_misses int(11) DEFAULT 0,
    avg_generation_time decimal(10,3),
    PRIMARY KEY (id),
    UNIQUE KEY date_type (date, content_type)
);
```

### Database Functions

#### `spb_create_tables()`
Creates plugin database tables.

```php
function spb_create_tables() {
    Smart_Page_Builder_Database::create_tables();
}
```

#### `spb_cleanup_expired_content()`
Removes expired cached content from database.

```php
function spb_cleanup_expired_content() {
    Smart_Page_Builder_Database::cleanup_expired();
}
```

---

## Configuration API

### Plugin Configuration

#### `spb_configure_provider($provider, $config)`
Configures an AI content generation provider.

```php
spb_configure_provider('openai', [
    'api_key' => 'sk-...',
    'model' => 'gpt-3.5-turbo',
    'max_tokens' => 500,
    'temperature' => 0.7
]);
```

#### `spb_set_cache_settings($settings)`
Configures content caching settings.

```php
spb_set_cache_settings([
    'default_duration' => 3600,
    'max_cache_size' => '100MB',
    'cleanup_interval' => 'daily'
]);
```

---

## Extension API

### Creating Extensions

#### `spb_register_extension($extension_id, $extension_class)`
Registers a Smart Page Builder extension.

```php
class My_SPB_Extension extends Smart_Page_Builder_Extension {
    public function init() {
        add_filter('spb_content_types', [$this, 'add_content_types']);
        add_action('spb_after_content_generation', [$this, 'process_content'], 10, 3);
    }
    
    public function add_content_types($types) {
        $types['custom_type'] = 'Custom Content Type';
        return $types;
    }
}

spb_register_extension('my_extension', 'My_SPB_Extension');
```

#### Extension Base Class

```php
abstract class Smart_Page_Builder_Extension {
    abstract public function init();
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_version() {
        return $this->version;
    }
    
    protected function log($message, $level = 'info') {
        Smart_Page_Builder_Logger::log($message, $level, $this->get_id());
    }
}
```

---

## Error Handling

### Error Codes

- `SPB_ERROR_INVALID_TYPE` - Invalid content type specified
- `SPB_ERROR_MISSING_CONTEXT` - Required context data missing
- `SPB_ERROR_API_FAILURE` - AI provider API failure
- `SPB_ERROR_CACHE_FAILURE` - Cache operation failure
- `SPB_ERROR_PERMISSION_DENIED` - Insufficient permissions

### Error Functions

#### `spb_get_error_message($error_code)`
Retrieves human-readable error message.

```php
$message = spb_get_error_message('SPB_ERROR_API_FAILURE');
// Returns: "Failed to connect to AI content generation service"
```

---

## Debugging & Logging

### Debug Functions

#### `spb_debug_log($message, $data)`
Logs debug information when WP_DEBUG is enabled.

```php
spb_debug_log('Content generation started', [
    'type' => $type,
    'context' => $context,
    'timestamp' => time()
]);
```

#### `spb_get_debug_info()`
Returns comprehensive debug information.

```php
$debug_info = spb_get_debug_info();
// Returns array with plugin status, settings, cache stats, etc.
```

---

## Performance Optimization

### Caching Strategies

#### Content Caching
- Generated content cached for 1 hour by default
- Cache keys based on content type and context hash
- Automatic cleanup of expired content

#### Database Optimization
- Indexed cache keys for fast retrieval
- Batch operations for bulk content generation
- Query optimization for large datasets

### Rate Limiting

#### `spb_check_rate_limit($user_id, $action)`
Checks if user has exceeded rate limits.

```php
if (!spb_check_rate_limit(get_current_user_id(), 'generate_content')) {
    return new WP_Error('rate_limit_exceeded', 'Too many requests');
}
```

---

## Security Considerations

### Input Validation
- All user inputs sanitized and validated
- Context data filtered before AI processing
- SQL injection prevention through prepared statements

### API Security
- Nonce verification for AJAX requests
- Capability checks for admin functions
- API key encryption for external services

### Content Security
- Generated content sanitized before display
- XSS prevention through proper escaping
- Content filtering for inappropriate material

---

## Migration & Compatibility

### Version Compatibility

#### `spb_get_version()`
Returns current plugin version.

#### `spb_check_compatibility()`
Checks WordPress and theme compatibility.

### Database Migrations

#### `spb_migrate_database($from_version, $to_version)`
Handles database schema migrations between versions.

---

## Conclusion

This API documentation provides comprehensive coverage of all Smart Page Builder functions, hooks, and integration points. For additional examples and tutorials, refer to the development documentation and feature specifications.

**Plugin Version:** 1.0.0  
**WordPress Compatibility:** 6.0+  
**PHP Compatibility:** 8.0+  
**Theme Integration:** DIY Home Improvement Theme

For support and updates, visit the plugin documentation or contact the development team.
