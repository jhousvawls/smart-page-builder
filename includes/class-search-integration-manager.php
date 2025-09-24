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
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into search requests
        add_action('pre_get_posts', [$this, 'intercept_search_query'], 10, 1);
        add_filter('posts_pre_query', [$this, 'handle_search_interception'], 10, 2);
        
        // Custom rewrite rules for search pages
        add_action('init', [$this, 'add_search_page_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_search_page_query_vars']);
        add_action('template_redirect', [$this, 'handle_search_page_request']);
        
        // AJAX handlers for search page generation
        add_action('wp_ajax_spb_generate_search_page', [$this, 'ajax_generate_search_page']);
        add_action('wp_ajax_nopriv_spb_generate_search_page', [$this, 'ajax_generate_search_page']);
        
        // Search form modifications
        add_filter('get_search_form', [$this, 'modify_search_form']);
    }
    
    /**
     * Intercept search queries to trigger page generation
     *
     * @param WP_Query $query WordPress query object
     */
    public function intercept_search_query($query) {
        // Only process main search queries
        if (!$query->is_main_query() || !$query->is_search()) {
            return;
        }
        
        // Check if search interception is enabled
        if (!$this->generation_options['enable_search_interception']) {
            return;
        }
        
        $search_query = get_search_query();
        
        // Validate search query
        if (!$this->is_valid_search_query($search_query)) {
            return;
        }
        
        // Check if we already have a generated page for this query
        $existing_page = $this->get_existing_search_page($search_query);
        
        if ($existing_page) {
            // Redirect to existing generated page
            $this->redirect_to_search_page($existing_page);
            return;
        }
        
        // Trigger page generation
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
        $query_hash = $this->generate_query_hash($query);
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE query_hash = %s ORDER BY created_at DESC LIMIT 1",
            $query_hash
        ), ARRAY_A);
        
        return $search_page;
    }
    
    /**
     * Get search page by hash
     *
     * @param string $hash Query hash
     * @return array|null Search page data or null
     */
    private function get_search_page_by_hash($hash) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE query_hash = %s AND approval_status = 'approved' LIMIT 1",
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
        
        // Start page generation in background
        $generation_data = [
            'query' => $query,
            'query_hash' => $this->generate_query_hash($query),
            'user_context' => $user_context,
            'user_session_id' => $this->session_manager ? $this->session_manager->get_session_id() : '',
            'generation_options' => $this->generation_options
        ];
        
        // Schedule immediate generation
        wp_schedule_single_event(time(), 'spb_generate_search_page_background', [$generation_data]);
        
        // For immediate response, start generation and show loading page
        $this->show_generation_loading_page($query, $generation_data);
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
        
        // Calculate confidence score
        $confidence_score = $this->calculate_page_confidence($discovery_result);
        
        // Determine approval status
        $approval_status = $confidence_score >= $this->generation_options['auto_approve_threshold'] ? 'approved' : 'pending';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'search_query' => $query,
                'query_hash' => $query_hash,
                'page_url' => $page_url,
                'generated_content' => wp_json_encode($discovery_result),
                'approval_status' => $approval_status,
                'confidence_score' => $confidence_score,
                'user_session_id' => $user_context['user_session_id'] ?? '',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
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
        
        // Load template
        $template_path = locate_template(['spb-search-page.php']);
        
        if (!$template_path) {
            $template_path = SPB_PLUGIN_DIR . 'templates/search-page.php';
        }
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback to basic template
            $this->load_fallback_template($search_page);
        }
        
        exit;
    }
    
    /**
     * Load fallback template
     *
     * @param array $search_page Search page data
     */
    private function load_fallback_template($search_page) {
        get_header();
        
        echo '<div class="spb-search-page-content">';
        echo '<h1>Smart Page: ' . esc_html($search_page['search_query']) . '</h1>';
        
        $content_data = json_decode($search_page['generated_content'], true);
        
        if (!empty($content_data['merged_results'])) {
            echo '<div class="spb-search-results">';
            foreach ($content_data['merged_results'] as $result) {
                echo '<div class="spb-result-item">';
                echo '<h3><a href="' . esc_url($result['url']) . '">' . esc_html($result['title']) . '</a></h3>';
                echo '<p>' . esc_html($result['excerpt']) . '</p>';
                echo '</div>';
            }
            echo '</div>';
        }
        
        echo '</div>';
        
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
