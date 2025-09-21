<?php
/**
 * The content assembler functionality of the plugin.
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
 * The content assembler class.
 *
 * This class defines all code necessary to assemble content from existing
 * site posts with proper attribution using TF-IDF analysis.
 *
 * @since      1.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_Content_Assembler {

    /**
     * Content templates for different types of content
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $templates    Content templates
     */
    private $templates;

    /**
     * Stop words to exclude from TF-IDF analysis
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $stop_words    Common words to exclude
     */
    private $stop_words;

    /**
     * Initialize the content assembler
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->init_templates();
        $this->init_stop_words();
    }

    /**
     * Initialize content templates
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_templates() {
        $this->templates = array(
            'how_to' => array(
                'title_prefix' => 'How to',
                'sections' => array('introduction', 'tools_needed', 'steps', 'tips', 'conclusion'),
                'confidence_boost' => 0.2
            ),
            'tool_recommendation' => array(
                'title_prefix' => 'Best Tools for',
                'sections' => array('introduction', 'top_tools', 'comparison', 'recommendations'),
                'confidence_boost' => 0.15
            ),
            'safety_tips' => array(
                'title_prefix' => 'Safety Tips for',
                'sections' => array('introduction', 'safety_precautions', 'common_mistakes', 'best_practices'),
                'confidence_boost' => 0.1
            ),
            'troubleshooting' => array(
                'title_prefix' => 'Troubleshooting',
                'sections' => array('introduction', 'common_problems', 'solutions', 'prevention'),
                'confidence_boost' => 0.15
            ),
            'default' => array(
                'title_prefix' => '',
                'sections' => array('introduction', 'main_content', 'conclusion'),
                'confidence_boost' => 0.0
            )
        );
    }

    /**
     * Initialize stop words for TF-IDF analysis
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_stop_words() {
        $this->stop_words = array(
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'will', 'with', 'you', 'your', 'have', 'had', 'this',
            'they', 'we', 'or', 'but', 'not', 'can', 'could', 'should', 'would'
        );
    }

    /**
     * Assemble content from existing site posts using TF-IDF analysis
     *
     * @since    1.0.0
     * @param    string    $search_term    The search term to generate content for
     * @return   array                     The assembled content data
     */
    public function assemble_content($search_term) {
        // Analyze search term to determine content type
        $content_type = $this->determine_content_type($search_term);
        
        // Get relevant posts using TF-IDF analysis
        $relevant_posts = $this->find_relevant_posts($search_term);
        
        // Extract and score content snippets
        $content_snippets = $this->extract_content_snippets($relevant_posts, $search_term);
        
        // Generate title based on content type and search term
        $title = $this->generate_title($search_term, $content_type);
        
        // Assemble content using template
        $assembled_content = $this->assemble_content_by_template($content_snippets, $content_type, $search_term);
        
        // Calculate confidence score
        $confidence = $this->calculate_confidence($content_snippets, $content_type, $search_term);
        
        return array(
            'title' => $title,
            'content' => $assembled_content,
            'template' => $content_type,
            'sources' => $this->get_source_attribution($relevant_posts),
            'confidence' => $confidence,
            'search_term' => $search_term,
            'generated_at' => current_time('mysql')
        );
    }

    /**
     * Determine content type based on search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   string                    Content type
     */
    private function determine_content_type($search_term) {
        $search_lower = strtolower($search_term);
        
        // How-to content
        if (preg_match('/^how\s+to|how\s+do|how\s+can/', $search_lower) || 
            strpos($search_lower, 'install') !== false ||
            strpos($search_lower, 'fix') !== false ||
            strpos($search_lower, 'repair') !== false ||
            strpos($search_lower, 'build') !== false) {
            return 'how_to';
        }
        
        // Tool recommendations
        if (strpos($search_lower, 'best') !== false && strpos($search_lower, 'tool') !== false ||
            strpos($search_lower, 'recommend') !== false ||
            strpos($search_lower, 'which tool') !== false) {
            return 'tool_recommendation';
        }
        
        // Safety content
        if (strpos($search_lower, 'safety') !== false ||
            strpos($search_lower, 'safe') !== false ||
            strpos($search_lower, 'danger') !== false ||
            strpos($search_lower, 'precaution') !== false) {
            return 'safety_tips';
        }
        
        // Troubleshooting
        if (strpos($search_lower, 'problem') !== false ||
            strpos($search_lower, 'issue') !== false ||
            strpos($search_lower, 'troubleshoot') !== false ||
            strpos($search_lower, 'not working') !== false) {
            return 'troubleshooting';
        }
        
        return 'default';
    }

    /**
     * Find relevant posts using TF-IDF analysis
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @return   array                     Relevant posts with scores
     */
    private function find_relevant_posts($search_term) {
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'numberposts' => 50, // Limit for performance
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (empty($posts)) {
            return array();
        }
        
        // Tokenize search term
        $search_tokens = $this->tokenize($search_term);
        
        // Calculate TF-IDF scores for each post
        $scored_posts = array();
        $document_count = count($posts);
        
        foreach ($posts as $post) {
            $content = $post->post_title . ' ' . $post->post_content;
            $content_tokens = $this->tokenize($content);
            
            $tf_idf_score = $this->calculate_tf_idf($search_tokens, $content_tokens, $posts);
            
            if ($tf_idf_score > 0) {
                $scored_posts[] = array(
                    'post' => $post,
                    'score' => $tf_idf_score,
                    'tokens' => $content_tokens
                );
            }
        }
        
        // Sort by score descending
        usort($scored_posts, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Return top 10 most relevant posts
        return array_slice($scored_posts, 0, 10);
    }

    /**
     * Tokenize text for TF-IDF analysis
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $text    Text to tokenize
     * @return   array              Array of tokens
     */
    private function tokenize($text) {
        // Convert to lowercase and remove HTML
        $text = strtolower(wp_strip_all_tags($text));
        
        // Extract words (alphanumeric only)
        preg_match_all('/\b[a-z0-9]+\b/', $text, $matches);
        $tokens = $matches[0];
        
        // Remove stop words
        $tokens = array_diff($tokens, $this->stop_words);
        
        // Remove very short words
        $tokens = array_filter($tokens, function($token) {
            return strlen($token) >= 3;
        });
        
        return array_values($tokens);
    }

    /**
     * Calculate TF-IDF score
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $query_tokens     Query tokens
     * @param    array    $document_tokens  Document tokens
     * @param    array    $all_posts        All posts for IDF calculation
     * @return   float                      TF-IDF score
     */
    private function calculate_tf_idf($query_tokens, $document_tokens, $all_posts) {
        $score = 0.0;
        $document_length = count($document_tokens);
        
        if ($document_length === 0) {
            return 0.0;
        }
        
        foreach ($query_tokens as $term) {
            // Calculate Term Frequency (TF)
            $term_count = array_count_values($document_tokens)[$term] ?? 0;
            $tf = $term_count / $document_length;
            
            // Calculate Inverse Document Frequency (IDF)
            $documents_with_term = 0;
            foreach ($all_posts as $post) {
                $post_content = strtolower($post->post_title . ' ' . $post->post_content);
                if (strpos($post_content, $term) !== false) {
                    $documents_with_term++;
                }
            }
            
            $idf = $documents_with_term > 0 ? log(count($all_posts) / $documents_with_term) : 0;
            
            // Add to total score
            $score += $tf * $idf;
        }
        
        return $score;
    }

    /**
     * Extract relevant content snippets from posts
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $relevant_posts    Posts with relevance scores
     * @param    string    $search_term       The search term
     * @return   array                        Content snippets
     */
    private function extract_content_snippets($relevant_posts, $search_term) {
        $snippets = array();
        $search_tokens = $this->tokenize($search_term);
        
        foreach ($relevant_posts as $post_data) {
            $post = $post_data['post'];
            $content = wp_strip_all_tags($post->post_content);
            
            // Split content into sentences
            $sentences = preg_split('/[.!?]+/', $content);
            
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (strlen($sentence) < 50) continue; // Skip very short sentences
                
                // Check if sentence contains search terms
                $sentence_tokens = $this->tokenize($sentence);
                $relevance = 0;
                
                foreach ($search_tokens as $token) {
                    if (in_array($token, $sentence_tokens)) {
                        $relevance++;
                    }
                }
                
                if ($relevance > 0) {
                    $snippets[] = array(
                        'text' => $sentence,
                        'relevance' => $relevance,
                        'post_id' => $post->ID,
                        'post_title' => $post->post_title,
                        'post_url' => get_permalink($post->ID)
                    );
                }
            }
        }
        
        // Sort by relevance
        usort($snippets, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        return array_slice($snippets, 0, 20); // Return top 20 snippets
    }

    /**
     * Assemble content using template
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $snippets       Content snippets
     * @param    string    $content_type   Content type
     * @param    string    $search_term    Search term
     * @return   string                    Assembled content
     */
    private function assemble_content_by_template($snippets, $content_type, $search_term) {
        $template = $this->templates[$content_type];
        $content = '';
        
        // Introduction
        $content .= '<h2>Introduction</h2>';
        $content .= '<p>Based on our analysis of existing content, here\'s what we found about "' . esc_html($search_term) . '":</p>';
        
        // Add relevant snippets organized by sections
        $snippets_per_section = ceil(count($snippets) / count($template['sections']));
        $snippet_index = 0;
        
        foreach ($template['sections'] as $section) {
            if ($section === 'introduction') continue; // Already added
            
            $content .= '<h2>' . ucwords(str_replace('_', ' ', $section)) . '</h2>';
            
            $section_snippets = array_slice($snippets, $snippet_index, $snippets_per_section);
            $snippet_index += $snippets_per_section;
            
            if (!empty($section_snippets)) {
                foreach ($section_snippets as $snippet) {
                    $content .= '<p>' . esc_html($snippet['text']) . '</p>';
                }
            } else {
                $content .= '<p>Additional information about ' . esc_html($search_term) . ' will be added as more content becomes available.</p>';
            }
        }
        
        // Add sources section
        $content .= '<h2>Sources</h2>';
        $content .= '<p>This content was assembled from the following sources on your site:</p>';
        $content .= '<ul>';
        
        $source_posts = array();
        foreach ($snippets as $snippet) {
            if (!in_array($snippet['post_id'], $source_posts)) {
                $source_posts[] = $snippet['post_id'];
                $content .= '<li><a href="' . esc_url($snippet['post_url']) . '">' . esc_html($snippet['post_title']) . '</a></li>';
            }
        }
        
        $content .= '</ul>';
        
        return $content;
    }

    /**
     * Generate title based on content type and search term
     *
     * @since    1.0.0
     * @access   private
     * @param    string    $search_term    The search term
     * @param    string    $content_type   Content type
     * @return   string                    Generated title
     */
    private function generate_title($search_term, $content_type) {
        $template = $this->templates[$content_type];
        $title = trim($search_term);
        
        // Capitalize first letter
        $title = ucfirst($title);
        
        // Add prefix if specified in template
        if (!empty($template['title_prefix'])) {
            // Check if title already starts with the prefix
            if (stripos($title, $template['title_prefix']) !== 0) {
                $title = $template['title_prefix'] . ' ' . $title;
            }
        }
        
        return $title;
    }

    /**
     * Calculate confidence score for assembled content
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $snippets       Content snippets
     * @param    string    $content_type   Content type
     * @param    string    $search_term    Search term
     * @return   float                     Confidence score (0.0 to 1.0)
     */
    private function calculate_confidence($snippets, $content_type, $search_term) {
        $base_confidence = 0.3;
        
        // Boost based on number of relevant snippets
        $snippet_boost = min(count($snippets) * 0.05, 0.4);
        
        // Boost based on content type
        $template_boost = $this->templates[$content_type]['confidence_boost'];
        
        // Boost based on search term characteristics
        $term_boost = 0.0;
        if (str_word_count($search_term) >= 2) {
            $term_boost += 0.1;
        }
        if (strlen($search_term) >= 10) {
            $term_boost += 0.1;
        }
        
        $total_confidence = $base_confidence + $snippet_boost + $template_boost + $term_boost;
        
        return min($total_confidence, 1.0);
    }

    /**
     * Get source attribution for assembled content
     *
     * @since    1.0.0
     * @access   private
     * @param    array    $relevant_posts    Relevant posts
     * @return   array                       Source attribution data
     */
    private function get_source_attribution($relevant_posts) {
        $sources = array();
        
        foreach ($relevant_posts as $post_data) {
            $post = $post_data['post'];
            $sources[] = array(
                'post_id' => $post->ID,
                'title' => $post->post_title,
                'url' => get_permalink($post->ID),
                'relevance_score' => $post_data['score'],
                'excerpt' => wp_trim_words($post->post_content, 30)
            );
        }
        
        return $sources;
    }
}
