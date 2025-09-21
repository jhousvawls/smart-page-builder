<?php
/**
 * The approval workflow functionality of the plugin.
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The approval workflow class.
 *
 * This class defines all code necessary to handle the draft-first approval workflow
 * for AI-generated content.
 *
 * @since      1.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_Approval_Workflow {

    /**
     * Initialize the approval workflow
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Constructor can be used for initialization if needed
    }

    /**
     * Intercept search queries to identify content gaps
     *
     * @since    1.0.0
     * @param    WP_Query    $query    The WordPress query object
     * @return   WP_Query              The modified query object
     */
    public function intercept_search_queries($query) {
        // Only process main search queries on frontend
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            $search_term = get_search_query();
            
            if (!empty($search_term) && strlen($search_term) >= 3) {
                // Check if we should generate content for this search
                $this->maybe_generate_content($search_term, $query);
            }
        }
        
        return $query;
    }

    /**
     * Maybe generate content for search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string      $search_term    The search term
     * @param    WP_Query    $query         The query object
     */
    private function maybe_generate_content($search_term, $query) {
        // Check if content already exists for this search term
        if ($this->has_existing_content($search_term)) {
            return;
        }

        // Check if we already have a draft for this search term
        if ($this->has_pending_draft($search_term)) {
            return;
        }

        // Analyze search term and create draft if confidence is high enough
        $confidence = $this->analyze_search_term($search_term);
        $threshold = get_option('spb_confidence_threshold', 0.6);

        if ($confidence >= $threshold) {
            $this->create_draft_content($search_term, $confidence);
        }
    }

    /**
     * Check if existing content covers the search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   bool                      Whether existing content exists
     */
    private function has_existing_content($search_term) {
        $posts = get_posts(array(
            'post_type' => array('post', 'page', 'spb_dynamic_page'),
            'post_status' => 'publish',
            's' => $search_term,
            'numberposts' => 1
        ));

        return !empty($posts);
    }

    /**
     * Check if there's already a pending draft for this search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   bool                      Whether a pending draft exists
     */
    private function has_pending_draft($search_term) {
        $drafts = get_posts(array(
            'post_type' => 'spb_dynamic_page',
            'post_status' => 'draft',
            'meta_key' => '_spb_search_term',
            'meta_value' => $search_term,
            'numberposts' => 1
        ));

        return !empty($drafts);
    }

    /**
     * Analyze search term to determine content generation confidence
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   float                     Confidence score (0.0 to 1.0)
     */
    private function analyze_search_term($search_term) {
        // Simple confidence calculation based on term characteristics
        $confidence = 0.0;

        // Length factor
        $length = strlen($search_term);
        if ($length >= 10 && $length <= 100) {
            $confidence += 0.3;
        }

        // Word count factor
        $word_count = str_word_count($search_term);
        if ($word_count >= 2 && $word_count <= 8) {
            $confidence += 0.2;
        }

        // DIY-related keywords
        $diy_keywords = array('how', 'fix', 'repair', 'install', 'build', 'make', 'diy', 'tool', 'project');
        foreach ($diy_keywords as $keyword) {
            if (stripos($search_term, $keyword) !== false) {
                $confidence += 0.2;
                break;
            }
        }

        // Question format
        if (preg_match('/^(how|what|why|when|where|which)/i', $search_term)) {
            $confidence += 0.3;
        }

        return min($confidence, 1.0);
    }

    /**
     * Create draft content for the search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @param    float     $confidence     The confidence score
     */
    private function create_draft_content($search_term, $confidence) {
        // Create draft post
        $post_data = array(
            'post_title' => $this->generate_title($search_term),
            'post_content' => $this->generate_placeholder_content($search_term),
            'post_status' => 'draft',
            'post_type' => 'spb_dynamic_page',
            'post_author' => 1, // System user
            'meta_input' => array(
                '_spb_search_term' => $search_term,
                '_spb_confidence' => $confidence,
                '_spb_generated_date' => current_time('mysql'),
                '_spb_status' => 'pending_approval'
            )
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            // Log the content generation
            $this->log_content_generation($post_id, $search_term, $confidence);
        }
    }

    /**
     * Generate title from search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   string                    Generated title
     */
    private function generate_title($search_term) {
        // Capitalize first letter and clean up
        $title = ucfirst(trim($search_term));
        
        // Add "How to" prefix if it's a question-like search
        if (!preg_match('/^(how|what|why|when|where|which)/i', $title)) {
            if (stripos($title, 'fix') !== false || stripos($title, 'repair') !== false) {
                $title = 'How to ' . $title;
            }
        }

        return $title;
    }

    /**
     * Generate placeholder content
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   string                    Placeholder content
     */
    private function generate_placeholder_content($search_term) {
        $content = '<p><em>This content was automatically generated based on the search query: "' . esc_html($search_term) . '"</em></p>';
        $content .= '<p>This is a placeholder for AI-generated content. The actual content will be generated when this draft is reviewed and approved.</p>';
        $content .= '<p><strong>Search Term:</strong> ' . esc_html($search_term) . '</p>';
        $content .= '<p><strong>Generated:</strong> ' . current_time('F j, Y g:i a') . '</p>';

        return $content;
    }

    /**
     * Log content generation for analytics
     *
     * @since    1.0.0
     * @access   private
     * @param    int       $post_id       The post ID
     * @param    string    $search_term   The search term
     * @param    float     $confidence    The confidence score
     */
    private function log_content_generation($post_id, $search_term, $confidence) {
        // This would typically log to the analytics system
        // For now, just use WordPress logging if debug is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SPB: Generated draft content for '{$search_term}' with confidence {$confidence} (Post ID: {$post_id})");
        }
    }

    /**
     * Add approval queue menu
     *
     * @since    1.0.0
     */
    public function add_approval_queue_menu() {
        add_submenu_page(
            'smart-page-builder',
            __('Approval Queue', 'smart-page-builder'),
            __('Approval Queue', 'smart-page-builder'),
            'spb_approve_content',
            'smart-page-builder-approval',
            array($this, 'display_approval_queue')
        );
    }

    /**
     * Display the approval queue page
     *
     * @since    1.0.0
     */
    public function display_approval_queue() {
        // Get pending drafts
        $pending_drafts = get_posts(array(
            'post_type' => 'spb_dynamic_page',
            'post_status' => 'draft',
            'meta_key' => '_spb_status',
            'meta_value' => 'pending_approval',
            'numberposts' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_spb_confidence',
            'order' => 'DESC'
        ));

        // Include the approval queue template
        include_once SPB_PLUGIN_DIR . 'admin/partials/approval-queue-display.php';
    }
}
