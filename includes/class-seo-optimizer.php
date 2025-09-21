<?php
/**
 * SEO Optimizer Class
 *
 * Handles SEO optimization features including Schema.org markup,
 * meta tag optimization, and internal linking suggestions.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 * @since      2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * SEO Optimizer Class
 *
 * Provides comprehensive SEO optimization for generated content including
 * structured data, meta tags, internal linking, and sitemap integration.
 *
 * @since      2.0.0
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/includes
 */
class Smart_Page_Builder_SEO_Optimizer {

    /**
     * Cache manager instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Cache_Manager    $cache_manager
     */
    private $cache_manager;

    /**
     * Database instance
     *
     * @since    2.0.0
     * @access   private
     * @var      Smart_Page_Builder_Database    $database
     */
    private $database;

    /**
     * Initialize the SEO optimizer
     *
     * @since    2.0.0
     */
    public function __construct() {
        $this->cache_manager = new Smart_Page_Builder_Cache_Manager();
        $this->database = new Smart_Page_Builder_Database();
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since    2.0.0
     * @access   private
     */
    private function init_hooks() {
        // Add Schema.org markup to generated content
        add_action('wp_head', array($this, 'add_schema_markup'));
        
        // Optimize meta tags for generated content
        add_filter('wp_title', array($this, 'optimize_title'), 10, 2);
        add_action('wp_head', array($this, 'add_meta_tags'));
        
        // Add internal linking suggestions
        add_filter('the_content', array($this, 'add_internal_links'), 20);
        
        // Update sitemap when content is published
        add_action('spb_content_published', array($this, 'update_sitemap'));
        
        // Add Open Graph and Twitter Card meta tags
        add_action('wp_head', array($this, 'add_social_meta_tags'));
        
        // Add canonical URL for generated content
        add_action('wp_head', array($this, 'add_canonical_url'));
    }

    /**
     * Add Schema.org structured data markup
     *
     * @since    2.0.0
     */
    public function add_schema_markup() {
        if (!is_singular('spb_dynamic_page')) {
            return;
        }

        global $post;
        
        $schema_data = $this->generate_schema_markup($post);
        if ($schema_data) {
            echo '<script type="application/ld+json">' . wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }

    /**
     * Generate Schema.org markup for content
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   array|false    Schema markup data or false
     */
    private function generate_schema_markup($post) {
        $content_type = get_post_meta($post->ID, '_spb_content_type', true);
        $search_term = get_post_meta($post->ID, '_spb_search_term', true);
        $sources = get_post_meta($post->ID, '_spb_sources', true);
        
        $base_schema = array(
            '@context' => 'https://schema.org',
            '@type' => $this->get_schema_type($content_type),
            'name' => $post->post_title,
            'description' => $this->get_meta_description($post),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url(),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo_url()
                )
            )
        );

        // Add content-type specific schema
        switch ($content_type) {
            case 'how-to':
                $base_schema = array_merge($base_schema, $this->get_howto_schema($post));
                break;
            case 'tool-recommendation':
                $base_schema = array_merge($base_schema, $this->get_product_schema($post));
                break;
            case 'safety-tips':
                $base_schema = array_merge($base_schema, $this->get_safety_schema($post));
                break;
            case 'troubleshooting':
                $base_schema = array_merge($base_schema, $this->get_troubleshooting_schema($post));
                break;
            default:
                $base_schema['@type'] = 'Article';
                break;
        }

        // Add breadcrumb schema
        $breadcrumb_schema = $this->get_breadcrumb_schema($post);
        if ($breadcrumb_schema) {
            $base_schema['breadcrumb'] = $breadcrumb_schema;
        }

        // Add FAQ schema if applicable
        $faq_schema = $this->extract_faq_schema($post->post_content);
        if ($faq_schema) {
            $base_schema['mainEntity'] = $faq_schema;
        }

        return apply_filters('spb_schema_markup', $base_schema, $post);
    }

    /**
     * Get appropriate Schema.org type for content
     *
     * @since    2.0.0
     * @param    string   $content_type    Content type
     * @return   string   Schema.org type
     */
    private function get_schema_type($content_type) {
        $schema_types = array(
            'how-to' => 'HowTo',
            'tool-recommendation' => 'Product',
            'safety-tips' => 'Article',
            'troubleshooting' => 'TechArticle',
            'default' => 'Article'
        );

        return isset($schema_types[$content_type]) ? $schema_types[$content_type] : $schema_types['default'];
    }

    /**
     * Get HowTo schema markup
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   array    HowTo schema data
     */
    private function get_howto_schema($post) {
        $steps = $this->extract_steps_from_content($post->post_content);
        $tools = $this->extract_tools_from_content($post->post_content);
        $materials = $this->extract_materials_from_content($post->post_content);

        $schema = array(
            '@type' => 'HowTo',
            'totalTime' => $this->estimate_completion_time($post->post_content),
            'step' => $steps
        );

        if (!empty($tools)) {
            $schema['tool'] = $tools;
        }

        if (!empty($materials)) {
            $schema['supply'] = $materials;
        }

        return $schema;
    }

    /**
     * Get Product schema markup for tool recommendations
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   array    Product schema data
     */
    private function get_product_schema($post) {
        $products = $this->extract_products_from_content($post->post_content);
        
        if (empty($products)) {
            return array('@type' => 'Article');
        }

        // If multiple products, use ItemList
        if (count($products) > 1) {
            return array(
                '@type' => 'ItemList',
                'itemListElement' => $products
            );
        }

        // Single product
        return $products[0];
    }

    /**
     * Optimize page title
     *
     * @since    2.0.0
     * @param    string   $title      Current title
     * @param    string   $separator  Title separator
     * @return   string   Optimized title
     */
    public function optimize_title($title, $separator = '|') {
        if (!is_singular('spb_dynamic_page')) {
            return $title;
        }

        global $post;
        
        $search_term = get_post_meta($post->ID, '_spb_search_term', true);
        $content_type = get_post_meta($post->ID, '_spb_content_type', true);
        
        // Generate SEO-optimized title
        $optimized_title = $this->generate_seo_title($post->post_title, $search_term, $content_type);
        
        return $optimized_title . ' ' . $separator . ' ' . get_bloginfo('name');
    }

    /**
     * Add meta tags for SEO
     *
     * @since    2.0.0
     */
    public function add_meta_tags() {
        if (!is_singular('spb_dynamic_page')) {
            return;
        }

        global $post;
        
        $meta_description = $this->get_meta_description($post);
        $meta_keywords = $this->get_meta_keywords($post);
        $canonical_url = get_permalink($post->ID);

        echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '">' . "\n";
        echo '<meta name="robots" content="index, follow">' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    }

    /**
     * Add social media meta tags
     *
     * @since    2.0.0
     */
    public function add_social_meta_tags() {
        if (!is_singular('spb_dynamic_page')) {
            return;
        }

        global $post;
        
        $title = $post->post_title;
        $description = $this->get_meta_description($post);
        $url = get_permalink($post->ID);
        $image = $this->get_featured_image_url($post);
        $site_name = get_bloginfo('name');

        // Open Graph tags
        echo '<meta property="og:type" content="article">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";
        
        if ($image) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        }

        // Twitter Card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
        
        if ($image) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        }
    }

    /**
     * Add internal links to content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   string   Content with internal links
     */
    public function add_internal_links($content) {
        if (!is_singular('spb_dynamic_page')) {
            return $content;
        }

        global $post;
        
        $cache_key = "spb_internal_links_{$post->ID}";
        $cached_content = $this->cache_manager->get($cache_key);
        
        if ($cached_content !== false) {
            return $cached_content;
        }

        $enhanced_content = $this->add_contextual_links($content, $post);
        
        // Cache for 24 hours
        $this->cache_manager->set($cache_key, $enhanced_content, 86400);
        
        return $enhanced_content;
    }

    /**
     * Add contextual internal links to content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @param    WP_Post  $post       Post object
     * @return   string   Enhanced content
     */
    private function add_contextual_links($content, $post) {
        $search_term = get_post_meta($post->ID, '_spb_search_term', true);
        $content_type = get_post_meta($post->ID, '_spb_content_type', true);
        
        // Find related posts
        $related_posts = $this->find_related_posts($search_term, $content_type, $post->ID);
        
        if (empty($related_posts)) {
            return $content;
        }

        // Extract keywords from content
        $keywords = $this->extract_keywords($content);
        
        // Add links for relevant keywords
        foreach ($related_posts as $related_post) {
            $link_text = $this->find_best_link_text($related_post, $keywords);
            if ($link_text) {
                $link_url = get_permalink($related_post->ID);
                $link_html = '<a href="' . esc_url($link_url) . '" title="' . esc_attr($related_post->post_title) . '">' . esc_html($link_text) . '</a>';
                
                // Replace first occurrence of the keyword with link
                $content = preg_replace('/\b' . preg_quote($link_text, '/') . '\b/', $link_html, $content, 1);
            }
        }

        return $content;
    }

    /**
     * Find related posts for internal linking
     *
     * @since    2.0.0
     * @param    string   $search_term     Search term
     * @param    string   $content_type    Content type
     * @param    int      $exclude_id      Post ID to exclude
     * @return   array    Related posts
     */
    private function find_related_posts($search_term, $content_type, $exclude_id) {
        $cache_key = "spb_related_posts_" . md5($search_term . $content_type . $exclude_id);
        $cached_posts = $this->cache_manager->get($cache_key);
        
        if ($cached_posts !== false) {
            return $cached_posts;
        }

        $args = array(
            'post_type' => array('post', 'page', 'spb_dynamic_page'),
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'post__not_in' => array($exclude_id),
            's' => $search_term,
            'orderby' => 'relevance'
        );

        $related_posts = get_posts($args);
        
        // Cache for 12 hours
        $this->cache_manager->set($cache_key, $related_posts, 43200);
        
        return $related_posts;
    }

    /**
     * Generate SEO-optimized meta description
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   string   Meta description
     */
    private function get_meta_description($post) {
        $search_term = get_post_meta($post->ID, '_spb_search_term', true);
        $content_type = get_post_meta($post->ID, '_spb_content_type', true);
        
        // Extract first paragraph or create from search term
        $content = strip_tags($post->post_content);
        $first_paragraph = $this->extract_first_paragraph($content);
        
        if (strlen($first_paragraph) > 160) {
            $description = substr($first_paragraph, 0, 157) . '...';
        } else {
            $description = $first_paragraph;
        }

        // Ensure search term is included
        if (!empty($search_term) && stripos($description, $search_term) === false) {
            $description = $search_term . ' - ' . $description;
        }

        return $description;
    }

    /**
     * Generate meta keywords
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   string   Meta keywords
     */
    private function get_meta_keywords($post) {
        $search_term = get_post_meta($post->ID, '_spb_search_term', true);
        $content_type = get_post_meta($post->ID, '_spb_content_type', true);
        
        $keywords = array();
        
        // Add search term
        if (!empty($search_term)) {
            $keywords[] = $search_term;
        }

        // Add content type related keywords
        $type_keywords = $this->get_content_type_keywords($content_type);
        $keywords = array_merge($keywords, $type_keywords);
        
        // Extract keywords from content
        $content_keywords = $this->extract_keywords($post->post_content, 5);
        $keywords = array_merge($keywords, $content_keywords);
        
        // Remove duplicates and limit to 10 keywords
        $keywords = array_unique($keywords);
        $keywords = array_slice($keywords, 0, 10);
        
        return implode(', ', $keywords);
    }

    /**
     * Extract keywords from content
     *
     * @since    2.0.0
     * @param    string   $content    Content to analyze
     * @param    int      $limit      Maximum number of keywords
     * @return   array    Extracted keywords
     */
    private function extract_keywords($content, $limit = 10) {
        // Remove HTML tags and normalize text
        $text = strip_tags($content);
        $text = strtolower($text);
        
        // Remove common stop words
        $stop_words = array('the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'a', 'an');
        
        // Extract words
        preg_match_all('/\b[a-z]{3,}\b/', $text, $matches);
        $words = $matches[0];
        
        // Remove stop words
        $words = array_diff($words, $stop_words);
        
        // Count word frequency
        $word_counts = array_count_values($words);
        
        // Sort by frequency
        arsort($word_counts);
        
        // Return top keywords
        return array_slice(array_keys($word_counts), 0, $limit);
    }

    /**
     * Update sitemap when content is published
     *
     * @since    2.0.0
     * @param    int      $post_id    Published post ID
     */
    public function update_sitemap($post_id) {
        // Trigger sitemap regeneration
        do_action('spb_regenerate_sitemap');
        
        // If using WordPress core sitemaps (WP 5.5+)
        if (function_exists('wp_sitemaps_get_server')) {
            wp_cache_delete('sitemap_posts_1', 'sitemaps');
        }
        
        // If using Yoast SEO
        if (class_exists('WPSEO_Sitemaps_Cache')) {
            WPSEO_Sitemaps_Cache::clear();
        }
        
        // If using RankMath
        if (class_exists('RankMath\Sitemap\Cache')) {
            RankMath\Sitemap\Cache::invalidate();
        }
    }

    /**
     * Add canonical URL
     *
     * @since    2.0.0
     */
    public function add_canonical_url() {
        if (!is_singular('spb_dynamic_page')) {
            return;
        }

        global $post;
        $canonical_url = get_permalink($post->ID);
        
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
    }

    /**
     * Get content type specific keywords
     *
     * @since    2.0.0
     * @param    string   $content_type    Content type
     * @return   array    Related keywords
     */
    private function get_content_type_keywords($content_type) {
        $keywords_map = array(
            'how-to' => array('tutorial', 'guide', 'instructions', 'step-by-step', 'diy'),
            'tool-recommendation' => array('tools', 'equipment', 'recommendations', 'best', 'review'),
            'safety-tips' => array('safety', 'precautions', 'tips', 'secure', 'protection'),
            'troubleshooting' => array('troubleshooting', 'problems', 'solutions', 'fix', 'repair'),
            'default' => array('home improvement', 'diy', 'projects')
        );

        return isset($keywords_map[$content_type]) ? $keywords_map[$content_type] : $keywords_map['default'];
    }

    /**
     * Extract first paragraph from content
     *
     * @since    2.0.0
     * @param    string   $content    Content text
     * @return   string   First paragraph
     */
    private function extract_first_paragraph($content) {
        $paragraphs = explode("\n", $content);
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (!empty($paragraph) && strlen($paragraph) > 50) {
                return $paragraph;
            }
        }
        
        // Fallback to first 160 characters
        return substr($content, 0, 160);
    }

    /**
     * Get site logo URL for schema markup
     *
     * @since    2.0.0
     * @return   string   Logo URL
     */
    private function get_site_logo_url() {
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                return $logo_url;
            }
        }
        
        // Fallback to site icon
        $site_icon_url = get_site_icon_url();
        if ($site_icon_url) {
            return $site_icon_url;
        }
        
        // Default fallback
        return home_url('/wp-content/uploads/logo.png');
    }

    /**
     * Get featured image URL
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   string|false    Image URL or false
     */
    private function get_featured_image_url($post) {
        if (has_post_thumbnail($post->ID)) {
            return get_the_post_thumbnail_url($post->ID, 'large');
        }
        
        // Extract first image from content
        preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post->post_content, $matches);
        
        if (!empty($matches[1])) {
            return $matches[1];
        }
        
        return false;
    }

    /**
     * Extract steps from HowTo content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   array    Steps array for schema
     */
    private function extract_steps_from_content($content) {
        $steps = array();
        
        // Look for numbered lists or step patterns
        preg_match_all('/<li[^>]*>(.*?)<\/li>/s', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $step_content) {
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'name' => 'Step ' . ($index + 1),
                    'text' => strip_tags($step_content)
                );
            }
        }
        
        return $steps;
    }

    /**
     * Extract tools from content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   array    Tools array for schema
     */
    private function extract_tools_from_content($content) {
        $tools = array();
        
        // Look for tool mentions
        $tool_patterns = array(
            'drill', 'hammer', 'screwdriver', 'saw', 'wrench', 'pliers',
            'level', 'measuring tape', 'safety glasses', 'gloves'
        );
        
        foreach ($tool_patterns as $tool) {
            if (stripos($content, $tool) !== false) {
                $tools[] = array(
                    '@type' => 'HowToTool',
                    'name' => ucfirst($tool)
                );
            }
        }
        
        return $tools;
    }

    /**
     * Extract materials from content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   array    Materials array for schema
     */
    private function extract_materials_from_content($content) {
        $materials = array();
        
        // Look for material mentions
        $material_patterns = array(
            'screws', 'nails', 'wood', 'metal', 'plastic', 'adhesive',
            'paint', 'primer', 'sandpaper', 'wire'
        );
        
        foreach ($material_patterns as $material) {
            if (stripos($content, $material) !== false) {
                $materials[] = array(
                    '@type' => 'HowToSupply',
                    'name' => ucfirst($material)
                );
            }
        }
        
        return $materials;
    }

    /**
     * Estimate completion time for HowTo content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   string   ISO 8601 duration
     */
    private function estimate_completion_time($content) {
        $word_count = str_word_count(strip_tags($content));
        
        // Estimate based on word count and complexity
        $base_minutes = ceil($word_count / 100); // Rough estimate
        
        // Add time for complexity indicators
        if (stripos($content, 'difficult') !== false) {
            $base_minutes *= 1.5;
        }
        
        if (stripos($content, 'advanced') !== false) {
            $base_minutes *= 2;
        }
        
        // Convert to ISO 8601 duration format
        $hours = floor($base_minutes / 60);
        $minutes = $base_minutes % 60;
        
        if ($hours > 0) {
            return "PT{$hours}H{$minutes}M";
        } else {
            return "PT{$minutes}M";
        }
    }

    /**
     * Extract products from tool recommendation content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   array    Products array for schema
     */
    private function extract_products_from_content($content) {
        $products = array();
        
        // Look for product mentions with prices or ratings
        preg_match_all('/([A-Z][a-zA-Z\s]+(?:drill|saw|hammer|tool))[^.]*?(\$[\d,]+(?:\.\d{2})?)/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $product_name) {
                $price = isset($matches[2][$index]) ? $matches[2][$index] : null;
                
                $product = array(
                    '@type' => 'Product',
                    'name' => trim($product_name)
                );
                
                if ($price) {
                    $product['offers'] = array(
                        '@type' => 'Offer',
                        'price' => str_replace(array('$', ','), '', $price),
                        'priceCurrency' => 'USD'
                    );
                }
                
                $products[] = $product;
            }
        }
        
        return $products;
    }

    /**
     * Extract FAQ schema from content
     *
     * @since    2.0.0
     * @param    string   $content    Post content
     * @return   array|false    FAQ schema or false
     */
    private function extract_faq_schema($content) {
        $faqs = array();
        
        // Look for Q&A patterns
        preg_match_all('/<h[3-6][^>]*>([^<]*\?[^<]*)<\/h[3-6]>\s*<p[^>]*>([^<]+)<\/p>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $question) {
                $answer = isset($matches[2][$index]) ? $matches[2][$index] : '';
                
                if (!empty($answer)) {
                    $faqs[] = array(
                        '@type' => 'Question',
                        'name' => trim($question),
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text' => trim($answer)
                        )
                    );
                }
            }
        }
        
        return !empty($faqs) ? $faqs : false;
    }

    /**
     * Get breadcrumb schema
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   array|false    Breadcrumb schema or false
     */
    private function get_breadcrumb_schema($post) {
        $breadcrumbs = array();
        
        // Home page
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => get_bloginfo('name'),
            'item' => home_url()
        );
        
        // Add category if applicable
        $content_type = get_post_meta($post->ID, '_spb_content_type', true);
        if ($content_type) {
            $breadcrumbs[] = array(
                '@type' => 'ListItem',
                'position' => 2,
                'name' => ucfirst(str_replace('-', ' ', $content_type)),
                'item' => home_url('/category/' . $content_type)
            );
        }
        
        // Current page
        $breadcrumbs[] = array(
            '@type' => 'ListItem',
            'position' => count($breadcrumbs) + 1,
            'name' => $post->post_title,
            'item' => get_permalink($post->ID)
        );
        
        return array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs
        );
    }

    /**
     * Generate SEO-optimized title
     *
     * @since    2.0.0
     * @param    string   $title         Original title
     * @param    string   $search_term   Search term
     * @param    string   $content_type  Content type
     * @return   string   Optimized title
     */
    private function generate_seo_title($title, $search_term, $content_type) {
        // If title already contains search term, return as is
        if (!empty($search_term) && stripos($title, $search_term) !== false) {
            return $title;
        }
        
        // Add content type prefix if appropriate
        $type_prefixes = array(
            'how-to' => 'How to',
            'tool-recommendation' => 'Best Tools for',
            'safety-tips' => 'Safety Tips for',
            'troubleshooting' => 'Troubleshooting'
        );
        
        if (isset($type_prefixes[$content_type]) && !empty($search_term)) {
            return $type_prefixes[$content_type] . ' ' . $search_term;
        }
        
        return $title;
    }

    /**
     * Find best link text for related post
     *
     * @since    2.0.0
     * @param    WP_Post  $related_post  Related post object
     * @param    array    $keywords      Available keywords
     * @return   string|false    Best link text or false
     */
    private function find_best_link_text($related_post, $keywords) {
        $post_title_words = explode(' ', strtolower($related_post->post_title));
        
        // Find keywords that appear in the post title
        foreach ($keywords as $keyword) {
            if (in_array(strtolower($keyword), $post_title_words)) {
                return $keyword;
            }
        }
        
        // Fallback to first few words of title
        $title_words = explode(' ', $related_post->post_title);
        if (count($title_words) >= 2) {
            return implode(' ', array_slice($title_words, 0, 2));
        }
        
        return false;
    }

    /**
     * Get safety schema markup
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   array    Safety schema data
     */
    private function get_safety_schema($post) {
        return array(
            '@type' => 'Article',
            'articleSection' => 'Safety',
            'about' => array(
                '@type' => 'Thing',
                'name' => 'Safety Guidelines'
            )
        );
    }

    /**
     * Get troubleshooting schema markup
     *
     * @since    2.0.0
     * @param    WP_Post  $post    Post object
     * @return   array    Troubleshooting schema data
     */
    private function get_troubleshooting_schema($post) {
        return array(
            '@type' => 'TechArticle',
            'articleSection' => 'Troubleshooting',
            'about' => array(
                '@type' => 'Thing',
                'name' => 'Technical Support'
            )
        );
    }
}
