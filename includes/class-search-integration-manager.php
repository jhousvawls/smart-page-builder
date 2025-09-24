<?php
/**
 * Search Integration Manager
 *
 * Intercepts WP Engine Smart Search requests and triggers AI page generation
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search Integration Manager class
 */
class SPB_Search_Integration_Manager {
    
    /**
     * WP Engine Integration Hub instance
     */
    private $integration_hub;
    
    /**
     * Cache manager instance
     */
    private $cache_manager;
    
    /**
     * Session manager instance
     */
    private $session_manager;
    
    /**
     * Search page generation options
     */
    private $generation_options = [
        'enable_search_interception' => true,
        'min_query_length' => 3,
        'max_query_length' => 200,
        'generation_timeout' => 30,
        'cache_duration' => 3600,
        'auto_approve_threshold' => 0.8,
        'enable_seo_urls' => true
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->integration_hub = new SPB_WPEngine_Integration_Hub();
        
        if (class_exists('SPB_Cache_Manager')) {
            $this->cache_manager = new SPB_Cache_Manager();
        }
        
        if (class_exists('SPB_Session_Manager')) {
            $this->session_manager = new SPB_Session_Manager();
        }
        
        $this->init_hooks();
    }
    
    /**
     * AJAX handler for checking generation status
     */
    public function ajax_check_generation_status() {
        // Debug logging
        error_log('=== SPB DEBUG: AJAX status check called ===');
        error_log('SPB DEBUG: POST data: ' . print_r($_POST, true));
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'spb_check_generation')) {
            error_log('❌ SPB DEBUG: Security check failed');
            wp_send_json_error('Security check failed');
            return;
        }
        
        // Fix: Accept both query_hash and search_query parameters
        $query_hash = sanitize_text_field($_POST['query_hash'] ?? '');
        $search_query = sanitize_text_field($_POST['search_query'] ?? '');
        
        // If we have query_hash, try to find the original query
        if (!empty($query_hash) && empty($search_query)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'spb_search_pages';
            $page = $wpdb->get_row($wpdb->prepare(
                "SELECT search_query FROM {$table_name} WHERE page_slug = %s ORDER BY created_at DESC LIMIT 1",
                $query_hash
            ));
            if ($page) {
                $search_query = $page->search_query;
            }
        }
        
        error_log("SPB DEBUG: Checking status for query: " . $search_query . " (hash: " . $query_hash . ")");
        
        if (empty($search_query)) {
            error_log('❌ SPB DEBUG: No search query provided');
            wp_send_json_error('No search query provided');
            return;
        }
        
        // Check if page generation is complete
        global $wpdb;
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        error_log("SPB DEBUG: Checking table: " . $table_name);
        
        $page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE search_query = %s ORDER BY created_at DESC LIMIT 1",
            $search_query
        ));
        
        if ($wpdb->last_error) {
            error_log('❌ SPB DEBUG: Database error: ' . $wpdb->last_error);
        }
        
        if ($page) {
            error_log("✅ SPB DEBUG: Found page with status: " . $page->approval_status);
            if ($page->approval_status === 'approved') {
                wp_send_json_success([
                    'status' => 'completed', // Fix: Use 'completed' to match JavaScript
                    'page_url' => home_url('/smart-page/' . $page->page_slug . '/'), // Fix: Use 'page_url' to match JavaScript
                    'message' => 'Page generated successfully!'
                ]);
            } else {
                wp_send_json_success([
                    'status' => 'pending_approval',
                    'message' => 'Page generated and pending approval.',
                    'admin_url' => admin_url('admin.php?page=smart-page-builder-approval')
                ]);
            }
        } else {
            error_log('SPB DEBUG: No page found, still generating or failed');
            
            // Check if generation failed
            $generation_failed = get_transient('spb_generation_failed_' . md5($search_query));
            if ($generation_failed) {
                error_log('❌ SPB DEBUG: Generation marked as failed');
                wp_send_json_success([
                    'status' => 'error', // Fix: Use 'error' to match JavaScript
                    'error' => $generation_failed,
                    'message' => 'Page generation failed. Please try again.'
                ]);
                return;
            }
            
            // Check if generation was attempted but failed
            $generation_attempts = get_transient('spb_generation_attempt_' . md5($search_query));
            if ($generation_attempts && $generation_attempts > 3) {
                error_log('❌ SPB DEBUG: Too many generation attempts, likely failed');
                wp_send_json_success([
                    'status' => 'error', // Fix: Use 'error' to match JavaScript
                    'error' => 'Generation attempts exceeded limit',
                    'message' => 'Page generation failed. Please try again.'
                ]);
                return;
            }
            
            // Still generating
            wp_send_json_success([
                'status' => 'generating',
                'message' => 'Generating your personalized page...',
                'debug' => 'No page found in database yet'
            ]);
        }
    }
    
    /**
     * Initialize hooks and filters
     */
    private function init_hooks() {
        // Search interception hooks
        add_action('pre_get_posts', array($this, 'intercept_search_query'));
        add_filter('posts_pre_query', array($this, 'handle_search_interception'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_spb_check_generation_status', array($this, 'ajax_check_generation_status'));
        add_action('wp_ajax_nopriv_spb_check_generation_status', array($this, 'ajax_check_generation_status'));
        add_action('wp_ajax_spb_generate_search_page', array($this, 'ajax_generate_search_page'));
        add_action('wp_ajax_nopriv_spb_generate_search_page', array($this, 'ajax_generate_search_page'));
        
        // URL rewriting
        add_action('init', array($this, 'add_search_page_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_search_page_query_vars'));
        add_action('template_redirect', array($this, 'handle_search_page_request'));
        
        // Search form modification
        add_filter('get_search_form', array($this, 'modify_search_form'));
        
        // Background generation
        add_action('spb_generate_search_page_background', array($this, 'background_generate_search_page'));
    }
    
    /**
     * Intercept search queries
     *
     * @param WP_Query $query WordPress query object
     */
    public function intercept_search_query($query) {
        // Only process main search queries on frontend
        if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
            return;
        }
        
        $search_query = get_search_query();
        error_log("=== SPB DEBUG: Search detected ===");
        error_log("SPB DEBUG: Search query: " . $search_query);
        
        if (!$this->is_valid_search_query($search_query)) {
            error_log("SPB DEBUG: Invalid search query, skipping interception");
            return;
        }
        
        // Check if we already have a generated page for this query
        $existing_page = $this->get_existing_search_page($search_query);
        
        if ($existing_page && $existing_page['approval_status'] === 'approved') {
            error_log("SPB DEBUG: Found approved existing page, redirecting");
            $page_url = $this->generate_search_page_url($this->generate_query_hash($search_query));
            wp_redirect($page_url);
            exit;
        }
        
        error_log("SPB DEBUG: No existing approved page, triggering generation");
        // Trigger page generation with immediate response
        $this->trigger_search_page_generation($search_query, $query);
    }
    
    /**
     * Handle search query interception
     *
     * @param array|null $posts Array of posts or null
     * @param WP_Query $query WordPress query object
     * @return array|null Modified posts array or null
     */
    public function handle_search_interception($posts, $query) {
        // Only process main search queries
        if (!$query->is_main_query() || !$query->is_search()) {
            return $posts;
        }
        
        $search_query = get_search_query();
        
        if (!$this->is_valid_search_query($search_query)) {
            return $posts;
        }
        
        // Check if we have a generated page ready
        $search_page = $this->get_existing_search_page($search_query);
        
        if ($search_page && $search_page['approval_status'] === 'approved') {
            // Return empty posts array to prevent normal search results
            return [];
        }
        
        return $posts;
    }
    
    /**
     * Add custom rewrite rules for search pages
     */
    public function add_search_page_rewrite_rules() {
        if ($this->generation_options['enable_seo_urls']) {
            add_rewrite_rule(
                '^smart-page/([^/]+)/?$',
                'index.php?spb_search_page=$matches[1]',
                'top'
            );
        }
    }
    
    /**
     * Add custom query variables
     *
     * @param array $vars Existing query variables
     * @return array Modified query variables
     */
    public function add_search_page_query_vars($vars) {
        $vars[] = 'spb_search_page';
        return $vars;
    }
    
    /**
     * Handle search page requests
     */
    public function handle_search_page_request() {
        $search_page_hash = get_query_var('spb_search_page');
        
        if (empty($search_page_hash)) {
            return;
        }
        
        $search_page = $this->get_search_page_by_hash($search_page_hash);
        
        if (!$search_page || $search_page['approval_status'] !== 'approved') {
            wp_redirect(home_url('/'));
            exit;
        }
        
        // Load the search page template
        $this->load_search_page_template($search_page);
    }
    
    /**
     * Validate search query
     *
     * @param string $query Search query
     * @return bool Whether query is valid
     */
    private function is_valid_search_query($query) {
        $query = trim($query);
        $query_length = strlen($query);
        
        if ($query_length < $this->generation_options['min_query_length']) {
            return false;
        }
        
        if ($query_length > $this->generation_options['max_query_length']) {
            return false;
        }
        
        // Check for spam patterns
        if (preg_match('/[<>"\']/', $query)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get existing search page for query
     *
     * @param string $query Search query
     * @return array|null Search page data or null
     */
    private function get_existing_search_page($query) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE search_query = %s ORDER BY created_at DESC LIMIT 1",
            $query
        ), ARRAY_A);
        
        return $search_page;
    }
    
    /**
     * Get search page by hash
     *
     * @param string $hash Query hash (page_slug)
     * @return array|null Search page data or null
     */
    private function get_search_page_by_hash($hash) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE page_slug = %s AND approval_status = 'approved' LIMIT 1",
            $hash
        ), ARRAY_A);
        
        return $search_page;
    }
    
    /**
     * Generate query hash for caching and URL generation
     *
     * @param string $query Search query
     * @return string Query hash
     */
    private function generate_query_hash($query) {
        return substr(md5(strtolower(trim($query))), 0, 16);
    }
    
    /**
     * Trigger search page generation
     *
     * @param string $query Search query
     * @param WP_Query $wp_query WordPress query object
     */
    private function trigger_search_page_generation($query, $wp_query) {
        // Get user context for personalization
        $user_context = $this->get_user_context();
        
        // Generate content immediately instead of background processing
        $generation_data = [
            'query' => $query,
            'query_hash' => $this->generate_query_hash($query),
            'user_context' => $user_context,
            'user_session_id' => $this->session_manager ? $this->session_manager->get_session_id() : '',
            'generation_options' => $this->generation_options
        ];
        
        error_log('SPB DEBUG: Starting immediate content generation for: ' . $query);
        
        // Generate content immediately
        try {
            $result = $this->generate_enhanced_search_page($query, $user_context);
            
            if ($result['success']) {
                error_log('SPB DEBUG: Content generation successful, redirecting to: ' . $result['page_url']);
                // Redirect to the generated page
                wp_redirect($result['page_url']);
                exit;
            } else {
                error_log('SPB DEBUG: Content generation failed: ' . $result['error']);
                // Fall back to loading page with error handling
                $this->show_generation_loading_page($query, $generation_data);
            }
        } catch (Exception $e) {
            error_log('SPB DEBUG: Content generation exception: ' . $e->getMessage());
            // Fall back to loading page
            $this->show_generation_loading_page($query, $generation_data);
        }
    }
    
    /**
     * Get user context for personalization
     *
     * @return array User context data
     */
    private function get_user_context() {
        $context = [
            'user_id' => get_current_user_id(),
            'is_logged_in' => is_user_logged_in(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'referrer' => wp_get_referer(),
            'timestamp' => time()
        ];
        
        // Add interest vector data if available
        if ($this->session_manager && class_exists('SPB_Interest_Vector_Calculator')) {
            $interest_calculator = new SPB_Interest_Vector_Calculator();
            $context['interest_vectors'] = $interest_calculator->get_user_vectors($context['user_id']);
        }
        
        return $context;
    }
    
    /**
     * Show loading page during generation
     *
     * @param string $query Search query
     * @param array $generation_data Generation data
     */
    private function show_generation_loading_page($query, $generation_data) {
        // Set appropriate headers
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        
        // Generate loading page HTML
        $loading_html = $this->generate_loading_page_html($query, $generation_data);
        
        echo $loading_html;
        exit;
    }
    
    /**
     * Generate loading page HTML
     *
     * @param string $query Search query
     * @param array $generation_data Generation data
     * @return string Loading page HTML
     */
    private function generate_loading_page_html($query, $generation_data) {
        $site_name = get_bloginfo('name');
        $query_escaped = esc_html($query);
        $ajax_url = admin_url('admin-ajax.php');
        $query_hash = $generation_data['query_hash'];
        
        return "<!DOCTYPE html>
<html " . get_language_attributes() . ">
<head>
    <meta charset=\"" . get_bloginfo('charset') . "\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <title>Generating Smart Page for \"{$query_escaped}\" - {$site_name}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 40px 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; text-align: center; }
        .loading-spinner { width: 60px; height: 60px; border: 4px solid #e3e3e3; border-top: 4px solid #007cba; border-radius: 50%; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .progress-bar { width: 100%; height: 8px; background: #e3e3e3; border-radius: 4px; margin: 20px 0; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #007cba, #00a0d2); width: 0%; transition: width 0.3s ease; }
        .status-text { color: #666; margin: 20px 0; }
        .error-message { color: #d63638; background: #fff; padding: 15px; border-radius: 4px; margin: 20px 0; display: none; }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>Creating Your Smart Page</h1>
        <p>We're generating a personalized page for your search: <strong>\"{$query_escaped}\"</strong></p>
        
        <div class=\"loading-spinner\"></div>
        
        <div class=\"progress-bar\">
            <div class=\"progress-fill\" id=\"progress-fill\"></div>
        </div>
        
        <div class=\"status-text\" id=\"status-text\">Analyzing your search query...</div>
        
        <div class=\"error-message\" id=\"error-message\"></div>
    </div>
    
    <script>
        let progress = 0;
        let statusMessages = [
            'Analyzing your search query...',
            'Discovering relevant content...',
            'Generating personalized components...',
            'Assembling your smart page...',
            'Finalizing and optimizing...'
        ];
        let currentMessage = 0;
        
        function updateProgress() {
            progress += Math.random() * 15 + 5;
            if (progress > 95) progress = 95;
            
            document.getElementById('progress-fill').style.width = progress + '%';
            
            if (currentMessage < statusMessages.length - 1 && progress > (currentMessage + 1) * 20) {
                currentMessage++;
                document.getElementById('status-text').textContent = statusMessages[currentMessage];
            }
        }
        
        function checkGenerationStatus() {
            fetch('{$ajax_url}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=spb_check_generation_status&query_hash={$query_hash}&nonce=' + encodeURIComponent('" . wp_create_nonce('spb_check_generation') . "')
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.status === 'completed') {
                    document.getElementById('progress-fill').style.width = '100%';
                    document.getElementById('status-text').textContent = 'Redirecting to your smart page...';
                    setTimeout(() => {
                        window.location.href = data.data.page_url;
                    }, 1000);
                } else if (data.success && data.data.status === 'error') {
                    document.getElementById('error-message').textContent = data.data.error || 'An error occurred during generation.';
                    document.getElementById('error-message').style.display = 'block';
                } else {
                    setTimeout(checkGenerationStatus, 2000);
                }
            })
            .catch(error => {
                console.error('Error checking status:', error);
                setTimeout(checkGenerationStatus, 3000);
            });
        }
        
        // Start progress animation
        setInterval(updateProgress, 800);
        
        // Start checking generation status
        setTimeout(checkGenerationStatus, 3000);
        
        // Fallback timeout
        setTimeout(() => {
            if (progress < 100) {
                document.getElementById('error-message').textContent = 'Generation is taking longer than expected. Please try your search again.';
                document.getElementById('error-message').style.display = 'block';
            }
        }, 30000);
    </script>
</body>
</html>";
    }
    
    /**
     * AJAX handler for search page generation
     */
    public function ajax_generate_search_page() {
        check_ajax_referer('spb_generate_search_page', 'nonce');
        
        $query = sanitize_text_field($_POST['query'] ?? '');
        
        if (!$this->is_valid_search_query($query)) {
            wp_send_json_error(['message' => 'Invalid search query']);
        }
        
        $user_context = $this->get_user_context();
        
        $result = $this->generate_search_page($query, $user_context);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Get search page by hash (any approval status)
     *
     * @param string $hash Query hash (page_slug)
     * @return array|null Search page data or null
     */
    private function get_search_page_by_hash_any_status($hash) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE page_slug = %s ORDER BY created_at DESC LIMIT 1",
            $hash
        ), ARRAY_A);
        
        return $search_page;
    }
    
    /**
     * Generate search page
     *
     * @param string $query Search query
     * @param array $user_context User context data
     * @return array Generation result
     */
    public function generate_search_page($query, $user_context = []) {
        $start_time = microtime(true);
        
        try {
            // Discover content using WP Engine integration
            $discovery_result = $this->integration_hub->discover_content($query, $user_context);
            
            if (empty($discovery_result['merged_results'])) {
                return [
                    'success' => false,
                    'error' => 'No relevant content found for this search query'
                ];
            }
            
            // Generate page URL
            $query_hash = $this->generate_query_hash($query);
            $page_url = $this->generate_search_page_url($query_hash);
            
            // Store search page data
            $search_page_id = $this->store_search_page_data($query, $query_hash, $page_url, $discovery_result, $user_context);
            
            $processing_time = round((microtime(true) - $start_time) * 1000, 2);
            
            return [
                'success' => true,
                'search_page_id' => $search_page_id,
                'page_url' => $page_url,
                'query_hash' => $query_hash,
                'total_results' => $discovery_result['total_results'],
                'processing_time' => $processing_time
            ];
            
        } catch (Exception $e) {
            error_log('SPB Search Page Generation Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => 'Failed to generate search page: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate search page URL
     *
     * @param string $query_hash Query hash
     * @return string Page URL
     */
    private function generate_search_page_url($query_hash) {
        if ($this->generation_options['enable_seo_urls']) {
            return home_url("/smart-page/{$query_hash}/");
        } else {
            return home_url("/?spb_search_page={$query_hash}");
        }
    }
    
    /**
     * Store search page data in database
     *
     * @param string $query Search query
     * @param string $query_hash Query hash
     * @param string $page_url Page URL
     * @param array $discovery_result Discovery result
     * @param array $user_context User context
     * @return int|false Search page ID or false on failure
     */
    private function store_search_page_data($query, $query_hash, $page_url, $discovery_result, $user_context) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        // Check if table exists first
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            error_log('❌ SPB DEBUG: Table ' . $table_name . ' does not exist!');
            // Try to create the table
            $this->create_search_pages_table();
        }
        
        // Calculate confidence score
        $confidence_score = $this->calculate_page_confidence($discovery_result);
        
        // Determine approval status
        $approval_status = $confidence_score >= $this->generation_options['auto_approve_threshold'] ? 'approved' : 'pending';
        
        // Generate page title and content for the existing table structure
        $page_title = 'Smart Page: ' . ucfirst($query);
        $page_content = wp_json_encode($discovery_result);
        $page_slug = sanitize_title($query_hash);
        
        error_log('SPB DEBUG: Attempting to store search page data:');
        error_log('SPB DEBUG: - Query: ' . $query);
        error_log('SPB DEBUG: - Page Title: ' . $page_title);
        error_log('SPB DEBUG: - Page Slug: ' . $page_slug);
        error_log('SPB DEBUG: - Approval Status: ' . $approval_status);
        error_log('SPB DEBUG: - Quality Score: ' . $confidence_score);
        
        $result = $wpdb->insert(
            $table_name,
            [
                'search_query' => $query,
                'page_title' => $page_title,
                'page_content' => $page_content,
                'page_slug' => $page_slug,
                'page_status' => 'published',
                'quality_score' => $confidence_score,
                'approval_status' => $approval_status,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s']
        );
        
        if ($result === false) {
            error_log('❌ SPB DEBUG: Database insert failed!');
            error_log('❌ SPB DEBUG: MySQL Error: ' . $wpdb->last_error);
            error_log('❌ SPB DEBUG: Last Query: ' . $wpdb->last_query);
            return false;
        } else {
            $insert_id = $wpdb->insert_id;
            error_log('✅ SPB DEBUG: Successfully stored search page with ID: ' . $insert_id);
            return $insert_id;
        }
    }
    
    /**
     * Calculate page confidence score
     *
     * @param array $discovery_result Discovery result
     * @return float Confidence score (0-1)
     */
    private function calculate_page_confidence($discovery_result) {
        $confidence = 0.5; // Base confidence
        
        // Boost confidence based on number of results
        if ($discovery_result['total_results'] >= 5) {
            $confidence += 0.2;
        } elseif ($discovery_result['total_results'] >= 3) {
            $confidence += 0.1;
        }
        
        // Boost confidence based on query enhancement
        if (isset($discovery_result['query_enhancement']['confidence'])) {
            $confidence += $discovery_result['query_enhancement']['confidence'] * 0.2;
        }
        
        // Boost confidence based on processing time (faster = more confident)
        if ($discovery_result['processing_time'] < 2000) {
            $confidence += 0.1;
        }
        
        return min(1.0, max(0.0, $confidence));
    }
    
    /**
     * Fix missing page_url for existing search page
     *
     * @param array $existing_page Existing search page data
     */
    private function fix_missing_page_url($existing_page) {
        global $wpdb;
        
        error_log("SPB DEBUG: Fixing missing page_url for existing page");
        
        // Generate the missing page_url
        $query_hash = $this->generate_query_hash($existing_page['search_query']);
        $page_url = $this->generate_search_page_url($query_hash);
        
        error_log("SPB DEBUG: Generated page_url: " . $page_url);
        
        // Update the database record with the missing page_url
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $result = $wpdb->update(
            $table_name,
            ['page_url' => $page_url], // Data to update
            ['id' => $existing_page['id']], // Where condition
            ['%s'], // Data format
            ['%d']  // Where format
        );
        
        if ($result !== false) {
            error_log("SPB DEBUG: Successfully updated page_url in database");
            
            // Update the existing_page array and redirect
            $existing_page['page_url'] = $page_url;
            $this->redirect_to_search_page($existing_page);
        } else {
            error_log("SPB DEBUG: Failed to update page_url in database, falling back to normal search");
            // If we can't fix the database, just return and let WordPress handle the search normally
            return;
        }
    }
    
    /**
     * Redirect to search page
     *
     * @param array $search_page Search page data
     */
    private function redirect_to_search_page($search_page) {
        wp_redirect($search_page['page_url']);
        exit;
    }
    
    /**
     * Load search page template
     *
     * @param array $search_page Search page data
     */
    private function load_search_page_template($search_page) {
        // Set global search page data
        global $spb_search_page_data;
        $spb_search_page_data = $search_page;
        
        // Parse and prepare content for template
        $content = $this->parse_page_content_for_template($search_page);
        
        // Make content available to template
        global $spb_template_content;
        $spb_template_content = $content;
        
        // Determine template type based on content or intent
        $template_type = $this->determine_template_type($search_page, $content);
        
        // Try to load specific template first
        $template_path = $this->locate_search_page_template($template_type);
        
        if ($template_path && file_exists($template_path)) {
            // Load the template with content available
            include $template_path;
        } else {
            // Fallback to enhanced template with proper content
            $this->load_enhanced_fallback_template($search_page, $content);
        }
        
        exit;
    }
    
    /**
     * Parse page content for template rendering
     *
     * @param array $search_page Search page data
     * @return array Parsed content structure
     */
    private function parse_page_content_for_template($search_page) {
        // Parse the stored page_content
        $stored_content = json_decode($search_page['page_content'] ?? '{}', true);
        
        if (empty($stored_content)) {
            // Generate basic content structure if none exists
            return $this->generate_basic_content_structure($search_page);
        }
        
        // Check if this is AI-enhanced content
        if (isset($stored_content['type']) && $stored_content['type'] === 'ai_enhanced') {
            return $stored_content['components'] ?? [];
        }
        
        // Handle legacy content format (WP Engine discovery results)
        if (isset($stored_content['merged_results'])) {
            return $this->convert_discovery_results_to_content($stored_content, $search_page);
        }
        
        // Fallback to basic structure
        return $this->generate_basic_content_structure($search_page);
    }
    
    /**
     * Generate basic content structure from search page data
     *
     * @param array $search_page Search page data
     * @return array Basic content structure
     */
    private function generate_basic_content_structure($search_page) {
        $query = $search_page['search_query'] ?? '';
        
        return [
            'hero' => [
                'headline' => 'Smart Page: ' . ucfirst($query),
                'subheadline' => 'Discover comprehensive information about ' . $query,
                'cta_primary' => [
                    'text' => 'Learn More',
                    'url' => '#content'
                ],
                'visual_suggestion' => 'Relevant image for ' . $query
            ],
            'article' => [
                'title' => 'About ' . ucfirst($query),
                'content' => '<p>We\'re gathering the best information about <strong>' . esc_html($query) . '</strong> to create a comprehensive resource for you.</p>',
                'key_points' => [
                    'Comprehensive information gathering',
                    'AI-powered content curation',
                    'Personalized recommendations'
                ]
            ],
            'cta' => [
                'headline' => 'Want to Learn More?',
                'description' => 'Get personalized recommendations and expert insights.',
                'primary_button' => [
                    'text' => 'Get Started',
                    'url' => '#contact'
                ]
            ]
        ];
    }
    
    /**
     * Convert discovery results to content structure
     *
     * @param array $discovery_data Discovery results
     * @param array $search_page Search page data
     * @return array Content structure
     */
    private function convert_discovery_results_to_content($discovery_data, $search_page) {
        $query = $search_page['search_query'] ?? '';
        $results = $discovery_data['merged_results'] ?? [];
        
        // Extract key information from discovery results
        $key_points = [];
        $content_pieces = [];
        $sources = [];
        
        foreach ($results as $result) {
            if (!empty($result['title'])) {
                $key_points[] = $result['title'];
            }
            if (!empty($result['excerpt'])) {
                $content_pieces[] = $result['excerpt'];
                $sources[] = [
                    'title' => $result['title'] ?? 'Source',
                    'url' => $result['url'] ?? '#',
                    'excerpt' => $result['excerpt']
                ];
            }
        }
        
        // Limit key points to most relevant
        $key_points = array_slice($key_points, 0, 6);
        
        // Create comprehensive content
        $article_content = '<p>Based on our research about <strong>' . esc_html($query) . '</strong>, here\'s what we found:</p>';
        
        if (!empty($content_pieces)) {
            $article_content .= '<div class="spb-content-sources">';
            foreach ($sources as $index => $source) {
                $article_content .= '<div class="spb-source-item">';
                $article_content .= '<h4><a href="' . esc_url($source['url']) . '" target="_blank">' . esc_html($source['title']) . '</a></h4>';
                $article_content .= '<p>' . esc_html($source['excerpt']) . '</p>';
                $article_content .= '<small class="spb-source-attribution">Source: <a href="' . esc_url($source['url']) . '" target="_blank">' . esc_url($source['url']) . '</a></small>';
                $article_content .= '</div>';
            }
            $article_content .= '</div>';
        }
        
        return [
            'hero' => [
                'headline' => 'Everything About ' . ucfirst($query),
                'subheadline' => 'Comprehensive information gathered from ' . count($sources) . ' trusted sources',
                'cta_primary' => [
                    'text' => 'Explore Content',
                    'url' => '#content'
                ],
                'cta_secondary' => [
                    'text' => 'View Sources',
                    'url' => '#sources'
                ],
                'visual_suggestion' => 'Infographic about ' . $query
            ],
            'article' => [
                'title' => 'Comprehensive Guide to ' . ucfirst($query),
                'content' => $article_content,
                'key_points' => $key_points,
                'sources' => $sources
            ],
            'cta' => [
                'headline' => 'Need More Information?',
                'description' => 'Get personalized recommendations based on your interests.',
                'primary_button' => [
                    'text' => 'Get Personalized Results',
                    'url' => '/?s=' . urlencode($query . ' advanced')
                ],
                'secondary_button' => [
                    'text' => 'Browse Related Topics',
                    'url' => '#related'
                ],
                'value_propositions' => [
                    'AI-powered content curation',
                    'Multiple trusted sources',
                    'Regularly updated information'
                ]
            ]
        ];
    }
    
    /**
     * Determine template type based on content and intent
     *
     * @param array $search_page Search page data
     * @param array $content Parsed content
     * @return string Template type
     */
    private function determine_template_type($search_page, $content) {
        // Check if we have intent information
        $query = strtolower($search_page['search_query'] ?? '');
        
        // Commercial intent patterns
        if (preg_match('/\b(buy|purchase|price|cost|deal|discount|review|best|compare)\b/', $query)) {
            return 'commercial';
        }
        
        // Educational intent patterns
        if (preg_match('/\b(how to|tutorial|guide|learn|course|education)\b/', $query)) {
            return 'educational';
        }
        
        // Default to informational
        return 'informational';
    }
    
    /**
     * Locate search page template file
     *
     * @param string $template_type Template type
     * @return string|false Template path or false
     */
    private function locate_search_page_template($template_type) {
        // Check theme first
        $theme_template = locate_template(["spb-search-page-{$template_type}.php", 'spb-search-page.php']);
        if ($theme_template) {
            return $theme_template;
        }
        
        // Check plugin templates
        $plugin_template = SPB_PLUGIN_DIR . "templates/search-page-templates/{$template_type}.php";
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
        
        // Fallback to generic template
        $generic_template = SPB_PLUGIN_DIR . 'templates/search-page.php';
        if (file_exists($generic_template)) {
            return $generic_template;
        }
        
        return false;
    }
    
    /**
     * Load enhanced fallback template with proper content structure
     *
     * @param array $search_page Search page data
     * @param array $content Parsed content structure
     */
    private function load_enhanced_fallback_template($search_page, $content) {
        // Set up WordPress environment
        get_header();
        
        // Include the commercial template directly with content
        echo '<div class="spb-search-page-wrapper">';
        
        // Make content available to the template
        include SPB_PLUGIN_DIR . 'templates/search-page-templates/commercial.php';
        
        echo '</div>';
        
        // Add attribution and source information
        if (!empty($content['article']['sources'])) {
            echo '<div class="spb-content-attribution">';
            echo '<h3>Sources</h3>';
            echo '<p>This page was generated using content from the following sources:</p>';
            echo '<ul class="spb-source-list">';
            foreach ($content['article']['sources'] as $source) {
                echo '<li><a href="' . esc_url($source['url']) . '" target="_blank">' . esc_html($source['title']) . '</a></li>';
            }
            echo '</ul>';
            echo '<p><small>Content is curated and enhanced by AI to provide comprehensive information.</small></p>';
            echo '</div>';
        }
        
        // Add custom styles for the enhanced template
        echo '<style>
        .spb-search-page-wrapper { margin: 2rem 0; }
        .spb-content-sources { margin: 2rem 0; }
        .spb-source-item { 
            background: #f8f9fa; 
            padding: 1.5rem; 
            margin: 1rem 0; 
            border-radius: 0.5rem; 
            border-left: 4px solid #2563eb; 
        }
        .spb-source-item h4 { margin: 0 0 0.5rem 0; color: #2563eb; }
        .spb-source-item p { margin: 0.5rem 0; }
        .spb-source-attribution { color: #666; font-style: italic; }
        .spb-content-attribution { 
            background: #f1f5f9; 
            padding: 2rem; 
            margin: 2rem 0; 
            border-radius: 0.5rem; 
        }
        .spb-source-list { list-style: none; padding: 0; }
        .spb-source-list li { 
            padding: 0.5rem 0; 
            border-bottom: 1px solid #e2e8f0; 
        }
        .spb-source-list li:last-child { border-bottom: none; }
        .spb-source-list a { color: #2563eb; text-decoration: none; }
        .spb-source-list a:hover { text-decoration: underline; }
        </style>';
        
        get_footer();
    }
    
    /**
     * Modify search form to support smart page generation
     *
     * @param string $form Search form HTML
     * @return string Modified form HTML
     */
    public function modify_search_form($form) {
        if (!$this->generation_options['enable_search_interception']) {
            return $form;
        }
        
        // Add hidden field to indicate smart page generation
        $form = str_replace(
            '</form>',
            '<input type="hidden" name="spb_smart_search" value="1"></form>',
            $form
        );
        
        return $form;
    }
    
    /**
     * Background search page generation (CRITICAL MISSING METHOD)
     *
     * @param array $generation_data Generation data from scheduled event
     */
    public function background_generate_search_page($generation_data) {
        error_log('=== SPB DEBUG: Background generation started ===');
        error_log('SPB DEBUG: Generation data: ' . print_r($generation_data, true));
        
        $query = $generation_data['query'] ?? '';
        $user_context = $generation_data['user_context'] ?? [];
        
        if (empty($query)) {
            error_log('❌ SPB DEBUG: No query provided for background generation');
            return;
        }
        
        // Track generation attempts
        $attempt_key = 'spb_generation_attempt_' . md5($query);
        $attempts = get_transient($attempt_key) ?: 0;
        $attempts++;
        set_transient($attempt_key, $attempts, 300); // 5 minutes
        
        error_log("SPB DEBUG: Generation attempt #{$attempts} for query: {$query}");
        
        try {
            // Generate the search page with AI enhancement
            $result = $this->generate_enhanced_search_page($query, $user_context);
            
            if ($result['success']) {
                error_log('✅ SPB DEBUG: Background generation completed successfully');
                error_log('SPB DEBUG: Page ID: ' . $result['search_page_id']);
                error_log('SPB DEBUG: Page URL: ' . $result['page_url']);
                
                // Clear generation attempts on success
                delete_transient($attempt_key);
                
                // Set success flag for AJAX polling
                set_transient('spb_generation_success_' . md5($query), $result, 300);
                
            } else {
                error_log('❌ SPB DEBUG: Background generation failed: ' . $result['error']);
                
                // If too many attempts, mark as permanently failed
                if ($attempts >= 3) {
                    error_log('❌ SPB DEBUG: Max attempts reached, marking as failed');
                    set_transient('spb_generation_failed_' . md5($query), $result['error'], 300);
                }
            }
            
        } catch (Exception $e) {
            error_log('❌ SPB DEBUG: Background generation exception: ' . $e->getMessage());
            
            // If too many attempts, mark as permanently failed
            if ($attempts >= 3) {
                error_log('❌ SPB DEBUG: Max attempts reached, marking as failed');
                set_transient('spb_generation_failed_' . md5($query), $e->getMessage(), 300);
            }
        }
    }
    
    /**
     * Generate enhanced search page with AI content
     *
     * @param string $query Search query
     * @param array $user_context User context data
     * @return array Generation result
     */
    public function generate_enhanced_search_page($query, $user_context = []) {
        $start_time = microtime(true);
        
        try {
            // Check if OpenAI is enabled and configured
            $openai_enabled = get_option('spb_openai_enabled', false);
            $openai_api_key = get_option('spb_openai_api_key', '');
            
            if ($openai_enabled && !empty($openai_api_key)) {
                error_log('SPB DEBUG: Using OpenAI for enhanced content generation');
                return $this->generate_ai_enhanced_page($query, $user_context);
            } else {
                error_log('SPB DEBUG: OpenAI not configured, using fallback generation');
                return $this->generate_search_page($query, $user_context);
            }
            
        } catch (Exception $e) {
            error_log('SPB Enhanced Generation Error: ' . $e->getMessage());
            
            // Fallback to basic generation if AI fails
            error_log('SPB DEBUG: AI generation failed, falling back to basic generation');
            return $this->generate_search_page($query, $user_context);
        }
    }
    
    /**
     * Generate AI-enhanced search page
     *
     * @param string $query Search query
     * @param array $user_context User context data
     * @return array Generation result
     */
    private function generate_ai_enhanced_page($query, $user_context = []) {
        $start_time = microtime(true);
        
        try {
            // Load AI providers
            require_once SPB_PLUGIN_DIR . 'includes/ai-providers/abstract-ai-provider.php';
            require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-openai-provider.php';
            
            $openai_provider = new SPB_OpenAI_Provider();
            
            // Get existing content context from WP Engine if available
            $existing_content = [];
            if ($this->integration_hub) {
                try {
                    $discovery_result = $this->integration_hub->discover_content($query, $user_context);
                    $existing_content = $discovery_result['merged_results'] ?? [];
                } catch (Exception $e) {
                    error_log('SPB DEBUG: WP Engine discovery failed: ' . $e->getMessage());
                }
            }
            
            // Determine user intent
            $user_intent = $this->determine_user_intent($query);
            
            // Generate AI content
            $ai_content = $openai_provider->generate_search_page_content($query, [
                'existing_content' => $existing_content,
                'user_intent' => $user_intent,
                'user_context' => $user_context
            ]);
            
            // Generate page URL
            $query_hash = $this->generate_query_hash($query);
            $page_url = $this->generate_search_page_url($query_hash);
            
            // Store enhanced search page data
            $search_page_id = $this->store_ai_enhanced_page_data($query, $query_hash, $page_url, $ai_content, $user_context);
            
            $processing_time = round((microtime(true) - $start_time) * 1000, 2);
            
            return [
                'success' => true,
                'search_page_id' => $search_page_id,
                'page_url' => $page_url,
                'query_hash' => $query_hash,
                'content_type' => 'ai_enhanced',
                'ai_provider' => 'openai',
                'processing_time' => $processing_time
            ];
            
        } catch (Exception $e) {
            error_log('SPB AI Enhanced Generation Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Determine user intent from search query
     *
     * @param string $query Search query
     * @return string User intent type
     */
    private function determine_user_intent($query) {
        $query_lower = strtolower($query);
        
        // Informational intent patterns
        $informational_patterns = ['how to', 'what is', 'why', 'when', 'where', 'guide', 'tutorial', 'tips'];
        foreach ($informational_patterns as $pattern) {
            if (strpos($query_lower, $pattern) !== false) {
                return 'informational';
            }
        }
        
        // Commercial intent patterns
        $commercial_patterns = ['best', 'review', 'compare', 'vs', 'price', 'cost', 'buy', 'purchase'];
        foreach ($commercial_patterns as $pattern) {
            if (strpos($query_lower, $pattern) !== false) {
                return 'commercial';
            }
        }
        
        // Transactional intent patterns
        $transactional_patterns = ['buy', 'purchase', 'order', 'shop', 'deal', 'discount', 'coupon'];
        foreach ($transactional_patterns as $pattern) {
            if (strpos($query_lower, $pattern) !== false) {
                return 'transactional';
            }
        }
        
        // Default to informational
        return 'informational';
    }
    
    /**
     * Store AI-enhanced page data in database
     *
     * @param string $query Search query
     * @param string $query_hash Query hash
     * @param string $page_url Page URL
     * @param array $ai_content AI-generated content
     * @param array $user_context User context
     * @return int|false Search page ID or false on failure
     */
    private function store_ai_enhanced_page_data($query, $query_hash, $page_url, $ai_content, $user_context) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        // Calculate confidence score (AI content gets higher base score)
        $confidence_score = 0.85; // Higher base score for AI content
        
        // Determine approval status
        $approval_status = $confidence_score >= $this->generation_options['auto_approve_threshold'] ? 'approved' : 'pending';
        
        // Create structured page content
        $page_title = $ai_content['hero']['headline'] ?? ('Smart Page: ' . ucfirst($query));
        $page_content = $this->format_ai_content_for_storage($ai_content);
        $page_slug = sanitize_title($query_hash);
        
        $result = $wpdb->insert(
            $table_name,
            [
                'search_query' => $query,
                'page_title' => $page_title,
                'page_content' => $page_content,
                'page_slug' => $page_slug,
                'page_status' => 'published',
                'quality_score' => $confidence_score,
                'approval_status' => $approval_status,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Format AI content for database storage
     *
     * @param array $ai_content AI-generated content components
     * @return string Formatted content for storage
     */
    private function format_ai_content_for_storage($ai_content) {
        // Create a comprehensive page structure
        $formatted_content = [
            'type' => 'ai_enhanced',
            'provider' => 'openai',
            'components' => $ai_content,
            'generated_at' => current_time('mysql')
        ];
        
        return wp_json_encode($formatted_content);
    }
    
    /**
     * Create search pages table if it doesn't exist
     */
    private function create_search_pages_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_query varchar(255) NOT NULL,
            page_title varchar(255) NOT NULL,
            page_content longtext NOT NULL,
            page_slug varchar(200) NOT NULL,
            page_status varchar(20) DEFAULT 'pending',
            quality_score decimal(3,2) DEFAULT NULL,
            approval_status varchar(20) DEFAULT 'pending',
            approved_by bigint(20) unsigned DEFAULT NULL,
            approved_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY page_slug (page_slug),
            KEY search_query (search_query),
            KEY page_status (page_status),
            KEY approval_status (approval_status),
            KEY quality_score (quality_score)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $result = dbDelta($sql);
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            error_log('✅ SPB DEBUG: Successfully created table ' . $table_name);
            return true;
        } else {
            error_log('❌ SPB DEBUG: Failed to create table ' . $table_name);
            error_log('❌ SPB DEBUG: dbDelta result: ' . print_r($result, true));
            return false;
        }
    }
    
    /**
     * Update generation options
     *
     * @param array $new_options New options to merge
     */
    public function update_options($new_options) {
        $this->generation_options = wp_parse_args($new_options, $this->generation_options);
    }
    
    /**
     * Get current generation options
     *
     * @return array Current options
     */
    public function get_options() {
        return $this->generation_options;
    }
}
