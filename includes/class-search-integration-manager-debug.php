<?php
/**
 * Search Integration Manager - DEBUG VERSION
 *
 * This is a debug version with extensive logging to identify why search interception isn't working
 *
 * @package Smart_Page_Builder
 * @since   3.4.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search Integration Manager Debug class
 */
class SPB_Search_Integration_Manager_Debug {
    
    /**
     * Constructor
     */
    public function __construct() {
        error_log('=== SPB DEBUG: Search Integration Manager Debug initialized ===');
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks and filters
     */
    private function init_hooks() {
        error_log('SPB DEBUG: Initializing search hooks');
        
        // Search interception hooks
        add_action('pre_get_posts', array($this, 'intercept_search_query'), 1);
        add_filter('posts_pre_query', array($this, 'handle_search_interception'), 1, 2);
        
        // Additional debug hooks
        add_action('init', array($this, 'debug_init'));
        add_action('wp', array($this, 'debug_wp'));
        add_action('template_redirect', array($this, 'debug_template_redirect'));
        
        error_log('SPB DEBUG: Search hooks initialized');
    }
    
    /**
     * Debug init hook
     */
    public function debug_init() {
        error_log('SPB DEBUG: WordPress init hook fired');
        error_log('SPB DEBUG: Is search: ' . (is_search() ? 'YES' : 'NO'));
        error_log('SPB DEBUG: Query string: ' . $_SERVER['QUERY_STRING']);
        error_log('SPB DEBUG: Request URI: ' . $_SERVER['REQUEST_URI']);
    }
    
    /**
     * Debug wp hook
     */
    public function debug_wp() {
        global $wp_query;
        error_log('SPB DEBUG: WordPress wp hook fired');
        error_log('SPB DEBUG: Is search in wp: ' . ($wp_query->is_search() ? 'YES' : 'NO'));
        if ($wp_query->is_search()) {
            error_log('SPB DEBUG: Search query in wp: ' . get_search_query());
        }
    }
    
    /**
     * Debug template redirect hook
     */
    public function debug_template_redirect() {
        error_log('SPB DEBUG: Template redirect hook fired');
        error_log('SPB DEBUG: Is search in template_redirect: ' . (is_search() ? 'YES' : 'NO'));
        if (is_search()) {
            error_log('SPB DEBUG: Search query in template_redirect: ' . get_search_query());
        }
    }
    
    /**
     * Intercept search queries - DEBUG VERSION
     *
     * @param WP_Query $query WordPress query object
     */
    public function intercept_search_query($query) {
        error_log('=== SPB DEBUG: intercept_search_query called ===');
        error_log('SPB DEBUG: Is admin: ' . (is_admin() ? 'YES' : 'NO'));
        error_log('SPB DEBUG: Is main query: ' . ($query->is_main_query() ? 'YES' : 'NO'));
        error_log('SPB DEBUG: Is search: ' . ($query->is_search() ? 'YES' : 'NO'));
        
        // Only process main search queries on frontend
        if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
            error_log('SPB DEBUG: Skipping - not a main frontend search query');
            return;
        }
        
        $search_query = get_search_query();
        error_log("SPB DEBUG: Processing search query: '{$search_query}'");
        
        if (empty($search_query) || strlen($search_query) < 3) {
            error_log("SPB DEBUG: Search query too short or empty, skipping");
            return;
        }
        
        error_log("SPB DEBUG: Valid search query detected, triggering Smart Page Builder generation");
        
        // For debugging, let's immediately show a simple page
        $this->show_debug_page($search_query);
    }
    
    /**
     * Handle search query interception - DEBUG VERSION
     *
     * @param array|null $posts Array of posts or null
     * @param WP_Query $query WordPress query object
     * @return array|null Modified posts array or null
     */
    public function handle_search_interception($posts, $query) {
        error_log('=== SPB DEBUG: handle_search_interception called ===');
        error_log('SPB DEBUG: Is main query: ' . ($query->is_main_query() ? 'YES' : 'NO'));
        error_log('SPB DEBUG: Is search: ' . ($query->is_search() ? 'YES' : 'NO'));
        
        // Only process main search queries
        if (!$query->is_main_query() || !$query->is_search()) {
            error_log('SPB DEBUG: Not intercepting - not main search query');
            return $posts;
        }
        
        $search_query = get_search_query();
        error_log("SPB DEBUG: Intercepting search for: '{$search_query}'");
        
        if (empty($search_query) || strlen($search_query) < 3) {
            error_log("SPB DEBUG: Search query too short, not intercepting");
            return $posts;
        }
        
        error_log("SPB DEBUG: Returning empty posts array to prevent normal search results");
        
        // Return empty posts array to prevent normal search results
        return [];
    }
    
    /**
     * Show debug page immediately
     *
     * @param string $query Search query
     */
    private function show_debug_page($query) {
        error_log("SPB DEBUG: Showing debug page for query: {$query}");
        
        // Set appropriate headers
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        
        $site_name = get_bloginfo('name');
        $query_escaped = esc_html($query);
        
        $debug_html = "<!DOCTYPE html>
<html " . get_language_attributes() . ">
<head>
    <meta charset=\"" . get_bloginfo('charset') . "\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <title>Smart Page Builder DEBUG - Search for \"{$query_escaped}\" - {$site_name}</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 40px 20px; 
            background: #f8f9fa; 
            line-height: 1.6;
        }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { 
            background: #d4edda; 
            color: #155724; 
            padding: 20px; 
            border-radius: 4px; 
            margin: 20px 0; 
            border: 1px solid #c3e6cb;
        }
        .info { 
            background: #d1ecf1; 
            color: #0c5460; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 15px 0; 
            border: 1px solid #bee5eb;
        }
        .debug-info { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 4px; 
            margin: 15px 0; 
            font-family: monospace; 
            font-size: 14px;
            border: 1px solid #dee2e6;
        }
        h1 { color: #007cba; margin-bottom: 10px; }
        h2 { color: #333; margin-top: 30px; }
        .next-steps { 
            background: #fff3cd; 
            color: #856404; 
            padding: 20px; 
            border-radius: 4px; 
            margin: 20px 0; 
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>🎉 Smart Page Builder Search Interception WORKING!</h1>
        
        <div class=\"success\">
            <strong>SUCCESS:</strong> Your search for <strong>\"{$query_escaped}\"</strong> was successfully intercepted by Smart Page Builder!
        </div>
        
        <h2>What This Means</h2>
        <div class=\"info\">
            The search interception is working correctly. Instead of showing regular WordPress search results, 
            Smart Page Builder has taken over and is now in control of the search experience.
        </div>
        
        <h2>Debug Information</h2>
        <div class=\"debug-info\">
            Search Query: {$query_escaped}<br>
            Plugin Version: " . Smart_Page_Builder::VERSION . "<br>
            WordPress Version: " . get_bloginfo('version') . "<br>
            PHP Version: " . PHP_VERSION . "<br>
            Time: " . current_time('mysql') . "<br>
            User Agent: " . esc_html($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "
        </div>
        
        <h2>What Should Happen Next</h2>
        <div class=\"next-steps\">
            <strong>In a fully working system:</strong><br>
            1. Smart Page Builder would generate AI-powered content for your search<br>
            2. You would see a loading page while content is being generated<br>
            3. You would be redirected to a custom Smart Page with relevant information<br>
            4. The generated page would appear in your admin content management section
        </div>
        
        <h2>Why You're Seeing This Debug Page</h2>
        <div class=\"info\">
            This debug page confirms that the search interception mechanism is working. 
            The issue in v3.4.4 was that search queries were not being intercepted at all. 
            Since you're seeing this page, that core functionality is now working in v3.4.5.
        </div>
        
        <div class=\"info\">
            <strong>Next Step:</strong> The remaining issue is likely in the content generation or page display logic, 
            not in the search interception itself.
        </div>
        
        <p><a href=\"" . home_url() . "\">← Back to Home</a></p>
    </div>
</body>
</html>";
        
        echo $debug_html;
        exit;
    }
}
