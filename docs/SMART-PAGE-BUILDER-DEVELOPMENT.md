# Smart Page Builder AI Agent - Development Guide

## Development Overview

This comprehensive development guide outlines the implementation strategy for the Smart Page Builder AI Agent WordPress plugin. The development follows a structured 4-phase approach with a draft-first approval workflow to ensure content accuracy and quality control.

## Development Philosophy

### Core Principles
- **Content Accuracy First**: All dynamic pages require approval before going live
- **WordPress Standards Compliance**: Strict adherence to WordPress Codex guidelines
- **Test-Driven Development**: Unit tests written before implementation
- **Performance First**: 2-second page generation threshold
- **Security by Design**: Security measures integrated from the start

### Content Quality Control Strategy
Based on user requirements, the plugin implements a **Draft-First Approval Workflow**:

1. **Dynamic Page Creation**: All AI-generated pages start as WordPress DRAFT posts
2. **Admin Review Queue**: Dashboard displays pending pages for site owner review
3. **Approval Process**: Only manually approved pages become publicly accessible
4. **Confidence Scoring**: AI confidence levels help prioritize review queue
5. **Bulk Actions**: Admins can approve/reject multiple pages simultaneously

## Development Phases

### Phase 1: Foundation (Weeks 1-2)

#### Objectives
- Establish plugin structure and core architecture
- Implement draft-first approval workflow
- Set up development environment and testing framework
- Create initial database schema with approval tracking

#### Plugin Structure
```
smart-page-builder/
├── smart-page-builder.php          # Main plugin file
├── uninstall.php                   # Cleanup on uninstall
├── README.md                       # Plugin documentation
├── includes/                       # Core plugin files
│   ├── class-smart-page-builder.php
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-loader.php
│   ├── class-content-assembler.php
│   ├── class-approval-workflow.php
│   └── class-ai-processor.php
├── admin/                          # Admin interface
│   ├── class-admin.php
│   ├── class-dashboard.php
│   ├── class-approval-queue.php
│   ├── css/admin-dashboard.css
│   ├── js/dashboard.js
│   └── partials/
│       ├── dashboard-display.php
│       ├── approval-queue.php
│       └── settings-page.php
├── public/                         # Public-facing functionality
│   ├── class-public.php
│   ├── class-dynamic-pages.php
│   └── css/
├── templates/                      # Dynamic page templates
│   ├── how-to-guide.php
│   ├── product-comparison.php
│   ├── problem-solution.php
│   ├── resource-hub.php
│   └── default.php
├── languages/                      # Internationalization
└── tests/                         # Unit and integration tests
    ├── unit/
    ├── integration/
    └── bootstrap.php
```

#### Core Plugin Implementation
```php
<?php
/**
 * The core plugin class with draft-first approval workflow.
 */
class Smart_Page_Builder {
    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $approval_workflow;

    public function __construct() {
        $this->version = '1.0.0';
        $this->plugin_name = 'smart-page-builder';
        
        $this->load_dependencies();
        $this->init_approval_workflow();
    }

    private function init_approval_workflow() {
        $this->approval_workflow = new SPB_Approval_Workflow();
        
        // Hook into search queries to create draft pages
        add_filter('pre_get_posts', array($this->approval_workflow, 'intercept_search_queries'));
        
        // Add admin menu for approval queue
        add_action('admin_menu', array($this->approval_workflow, 'add_approval_queue_menu'));
    }
}
```

### Phase 2: Dynamic Page Core (Weeks 3-5)

#### Objectives
- Implement content assembly engine with source attribution
- Create template system for different content types
- Build URL structure with `/smart-page/` prefix
- Implement fallback system for AI service unavailability

#### Content Assembly Engine
```php
<?php
/**
 * Assembles content from existing site posts with proper attribution.
 */
class SPB_Content_Assembler {

    public function assemble_content($search_term) {
        // Step 1: Find relevant existing content
        $relevant_posts = $this->find_relevant_content($search_term);
        
        if (empty($relevant_posts)) {
            return $this->handle_no_content_found($search_term);
        }

        // Step 2: Extract and score content blocks
        $content_blocks = $this->extract_content_blocks($relevant_posts, $search_term);

        // Step 3: Select appropriate template
        $template = $this->select_template($search_term, $content_blocks);

        // Step 4: Assemble final content with attribution
        $assembled_content = $this->assemble_final_content($content_blocks, $template);

        return array(
            'title'      => $this->generate_title($search_term),
            'content'    => $assembled_content,
            'template'   => $template,
            'sources'    => $this->get_source_attribution($relevant_posts),
            'confidence' => $this->calculate_confidence($content_blocks)
        );
    }

    private function handle_no_content_found($search_term) {
        // Log the content gap for future content creation
        $this->log_content_gap($search_term);

        // Return helpful message instead of generating inaccurate content
        return array(
            'title'      => sprintf(__('Information about "%s"', 'smart-page-builder'), $search_term),
            'content'    => $this->generate_no_content_message($search_term),
            'template'   => 'no-content',
            'sources'    => array(),
            'confidence' => 0.0
        );
    }

    private function generate_no_content_message($search_term) {
        $message = sprintf(
            __('We don\'t currently have specific information about "%s" on our site. However, we\'ve noted your interest and will work on creating content for this topic.', 'smart-page-builder'),
            esc_html($search_term)
        );

        $message .= "\n\n" . __('In the meantime, you might find these related topics helpful:', 'smart-page-builder');

        // Suggest related content
        $related_content = $this->find_related_content($search_term);
        if (!empty($related_content)) {
            $message .= "\n\n";
            foreach ($related_content as $content) {
                $message .= sprintf('• <a href="%s">%s</a>' . "\n", get_permalink($content->ID), get_the_title($content->ID));
            }
        }

        return $message;
    }
}
```

#### URL Structure Implementation
```php
<?php
/**
 * Handles URL structure for dynamic pages with /smart-page/ prefix.
 */
class SPB_URL_Manager {

    public function init() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_smart_page_requests'));
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^smart-page/([^/]+)/?$',
            'index.php?spb_dynamic_page=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = 'spb_dynamic_page';
        return $vars;
    }

    public function handle_smart_page_requests() {
        $page_slug = get_query_var('spb_dynamic_page');
        
        if ($page_slug) {
            $this->serve_dynamic_page($page_slug);
        }
    }

    private function serve_dynamic_page($page_slug) {
        // Look for approved dynamic page
        $pages = get_posts(array(
            'post_type'   => 'spb_dynamic_page',
            'post_status' => 'publish',
            'name'        => $page_slug,
            'numberposts' => 1
        ));

        if (!empty($pages)) {
            $page = $pages[0];
            $this->load_dynamic_page_template($page);
        } else {
            // Page not found or not approved - redirect to search
            wp_redirect(home_url('/?s=' . urlencode(str_replace('-', ' ', $page_slug))));
            exit;
        }
    }
}
```

### Phase 3: AI Agent Features (Weeks 6-8)

#### Objectives
- Implement A/B testing framework for templates and content algorithms
- Build predictive content analysis with 30-day data retention
- Create bulk approval/rejection interface
- Implement confidence scoring and prioritization

#### A/B Testing Framework
```php
<?php
/**
 * A/B Testing framework for templates and content algorithms.
 */
class SPB_AB_Testing {

    public function create_test($test_config) {
        global $wpdb;

        $test_data = array(
            'test_name'           => $test_config['name'],
            'rule_id'            => $test_config['rule_id'],
            'variant_a_template' => $test_config['template_a'],
            'variant_b_template' => $test_config['template_b'],
            'traffic_split'      => $test_config['split'] ?? 0.50,
            'start_date'         => current_time('mysql'),
            'status'             => 'running'
        );

        $wpdb->insert($wpdb->prefix . 'spb_ab_tests', $test_data);
        return $wpdb->insert_id;
    }

    public function assign_variant($test_id, $user_session) {
        // Use consistent hashing for user assignment
        $hash = md5($user_session . $test_id);
        $hash_int = hexdec(substr($hash, 0, 8));
        $variant = ($hash_int % 100) < 50 ? 'a' : 'b';

        // Log the assignment
        $this->log_variant_assignment($test_id, $variant, $user_session);

        return $variant;
    }

    public function calculate_significance($test_id) {
        global $wpdb;

        // Get conversion data for both variants
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT 
                variant,
                COUNT(*) as total_visitors,
                SUM(CASE WHEN metric_type = 'conversion' THEN metric_value ELSE 0 END) as conversions
            FROM {$wpdb->prefix}spb_metrics 
            WHERE test_id = %d 
            GROUP BY variant
        ", $test_id));

        if (count($results) < 2) {
            return array('significance' => 0, 'winner' => null);
        }

        // Perform chi-square test
        $significance = $this->chi_square_test($results);
        $winner = $this->determine_winner($results);

        return array(
            'significance' => $significance,
            'winner'       => $winner,
            'results'      => $results
        );
    }
}
```

#### Data Retention Management
```php
<?php
/**
 * Manages data retention with 30-day policy.
 */
class SPB_Data_Retention {

    public function cleanup_old_data() {
        $this->cleanup_search_queries();
        $this->cleanup_metrics();
        $this->cleanup_approval_logs();
    }

    private function cleanup_search_queries() {
        global $wpdb;

        $wpdb->query("
            DELETE FROM {$wpdb->prefix}spb_ai_insights 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }

    private function cleanup_metrics() {
        global $wpdb;

        $wpdb->query("
            DELETE FROM {$wpdb->prefix}spb_metrics 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }

    public function schedule_cleanup() {
        if (!wp_next_scheduled('spb_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'spb_daily_cleanup');
        }
    }
}
```

### Phase 4: Optimization & Deployment (Weeks 9-10)

#### Objectives
- Performance optimization to meet 2-second generation threshold
- Security hardening and capability management
- Comprehensive testing and documentation
- Production deployment preparation

#### Performance Optimization
```php
<?php
/**
 * Performance optimization for 2-second threshold.
 */
class SPB_Performance_Manager {

    public function optimize_content_generation() {
        // Implement caching strategy
        $cache_key = 'spb_content_' . md5($search_term);
        $content = wp_cache_get($cache_key);

        if (false === $content) {
            $start_time = microtime(true);
            $content = $this->generate_content($search_term);
            $generation_time = microtime(true) - $start_time;

            // Cache for 1 hour if generation was successful
            if ($content && $generation_time < 2.0) {
                wp_cache_set($cache_key, $content, '', 3600);
            }

            // Log performance metrics
            $this->log_performance_metric('generation_time', $generation_time);
        }

        return $content;
    }

    public function optimize_database_queries() {
        // Use efficient queries with proper indexing
        $posts = get_posts(array(
            'post_type'      => 'post',
            'posts_per_page' => 10,
            'meta_query'     => array(
                array(
                    'key'     => '_spb_processed',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'fields' => 'ids' // Only get IDs to reduce memory usage
        ));

        return $posts;
    }
}
```

## Testing Strategy

### Unit Testing with PHPUnit
```php
<?php
/**
 * Unit tests for content assembly engine.
 */
class SPB_Content_Assembler_Test extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        $this->assembler = new SPB_Content_Assembler();
    }

    public function test_assemble_content_with_existing_posts() {
        // Create test posts
        $post_id = $this->factory->post->create(array(
            'post_title'   => 'How to Fix a Leaky Faucet',
            'post_content' => 'Turn off water supply. Remove handle. Replace washer.'
        ));

        $result = $this->assembler->assemble_content('fix leaky faucet');

        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertContains($post_id, $result['sources']);
    }

    public function test_no_content_found_scenario() {
        $result = $this->assembler->assemble_content('quantum physics repair');

        $this->assertEquals(0.0, $result['confidence']);
        $this->assertEquals('no-content', $result['template']);
        $this->assertContains('don\'t currently have', $result['content']);
    }
}
```

### Integration Testing
```php
<?php
/**
 * Integration tests for approval workflow.
 */
class SPB_Approval_Workflow_Test extends WP_UnitTestCase {

    public function test_search_creates_draft_page() {
        // Simulate search query
        $_GET['s'] = 'how to install ceiling fan';
        
        $workflow = new SPB_Approval_Workflow();
        $query = new WP_Query(array('s' => 'how to install ceiling fan'));
        
        $workflow->intercept_search_queries($query);

        // Check if draft page was created
        $drafts = get_posts(array(
            'post_type'   => 'spb_dynamic_page',
            'post_status' => 'draft',
            'meta_key'    => '_spb_search_term',
            'meta_value'  => 'how to install ceiling fan'
        ));

        $this->assertNotEmpty($drafts);
    }
}
```

## WordPress Integration Standards

### Hook Usage
```php
// Proper hook implementation
add_action('init', array($this, 'init_plugin'));
add_filter('pre_get_posts', array($this, 'modify_search_query'));
add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
```

### Security Implementation
```php
// Input sanitization
$search_term = sanitize_text_field($_GET['s']);

// Output escaping
echo esc_html($dynamic_content);
echo wp_kses_post($assembled_content);

// Nonce verification
if (!wp_verify_nonce($_POST['spb_nonce'], 'spb_approve_page')) {
    wp_die('Security check failed');
}

// Capability checking
if (!current_user_can('manage_options')) {
    wp_die('Insufficient permissions');
}
```

## Key Implementation Features

### Draft-First Approval Workflow
- **Search Interception**: Plugin intercepts search queries
- **Content Analysis**: Analyzes existing site content for relevance
- **Draft Creation**: Creates draft post if confidence > 0.6
- **Admin Notification**: Adds to approval queue for review
- **Manual Approval**: Admin reviews and approves/rejects
- **Publication**: Only approved content goes live

### URL Structure
- **Format**: `/smart-page/how-to-fix-leaky-faucet/`
- **SEO Benefits**: Clean, descriptive URLs for search engines
- **Fallback**: Redirects to search if page not found/approved

### AI Processing Workflow
1. **Local Processing**: TF-IDF analysis and pattern matching
2. **WP Engine AI Toolkit**: Advanced semantic analysis (when available)
3. **No Content Found**: Display helpful message instead of inaccurate content

### Performance Requirements
- **2-Second Threshold**: Page generation must complete within 2 seconds
- **Caching Strategy**: Multi-layer caching (object, transient, WP Engine)
- **Database Optimization**: Indexed queries and efficient schemas
- **Memory Management**: Chunked processing for large datasets

### Data Retention
- **30-Day Policy**: Search queries and metrics purged after 30 days
- **GDPR Compliance**: User behavior data anonymized
- **Audit Trail**: Approval logs maintained for accountability

## Deployment Checklist

### Pre-Deployment
- [ ] All unit tests passing
- [ ] Integration tests completed
- [ ] Performance benchmarks met (2-second threshold)
- [ ] Security audit completed
- [ ] WordPress Coding Standards validation
- [ ] Documentation updated

### Deployment
- [ ] Plugin tested on staging environment
- [ ] Database migration scripts tested
- [ ] Backup procedures verified
- [ ] Rollback plan prepared

### Post-Deployment
- [ ] Monitor error logs
- [ ] Verify approval workflow functioning
- [ ] Check performance metrics
- [ ] Validate A/B testing framework
- [ ] Confirm data retention policies active

## Conclusion

This development guide provides a comprehensive roadmap for implementing the Smart Page Builder AI Agent with a focus on content accuracy, user control, and WordPress best practices. The draft-first approval workflow ensures that site owners maintain complete control over AI-generated content while benefiting from intelligent content suggestions and gap analysis.

The phased approach allows for iterative development and testing, ensuring each component is thoroughly validated before moving to the next phase. The emphasis on WordPress standards compliance and security ensures the plugin will integrate seamlessly with existing WordPress installations while providing robust, scalable functionality.
