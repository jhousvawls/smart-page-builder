<?php
/**
 * Search Database Manager
 *
 * Handles database operations for search-triggered AI page generation
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Search Database Manager class
 */
class SPB_Search_Database_Manager {
    
    /**
     * Database version for search features
     */
    const SEARCH_DB_VERSION = '1.0.0';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into activation to create tables
        add_action('spb_search_activation', [$this, 'create_search_tables']);
    }
    
    /**
     * Create all search-related database tables
     */
    public function create_search_tables() {
        $this->create_search_pages_table();
        $this->create_query_enhancements_table();
        $this->create_generated_components_table();
        
        // Update database version
        update_option('spb_search_db_version', self::SEARCH_DB_VERSION);
    }
    
    /**
     * Create search pages table
     */
    private function create_search_pages_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_query varchar(500) NOT NULL,
            query_hash varchar(64) NOT NULL,
            page_url varchar(500) NOT NULL,
            generated_content longtext,
            approval_status enum('pending','approved','rejected') DEFAULT 'pending',
            confidence_score decimal(3,2) DEFAULT 0.00,
            user_session_id varchar(100) DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            approved_at datetime NULL,
            approved_by bigint(20) unsigned NULL,
            views_count int(11) DEFAULT 0,
            last_viewed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY query_hash (query_hash),
            KEY search_query (search_query(255)),
            KEY approval_status (approval_status),
            KEY confidence_score (confidence_score),
            KEY created_at (created_at),
            KEY user_session_id (user_session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create query enhancements table
     */
    private function create_query_enhancements_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_query_enhancements';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            original_query varchar(500) NOT NULL,
            enhanced_query text,
            detected_intent varchar(100) DEFAULT 'informational',
            enhancement_data json,
            processing_time decimal(8,3) DEFAULT 0.000,
            confidence_score decimal(3,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY original_query (original_query(255)),
            KEY detected_intent (detected_intent),
            KEY confidence_score (confidence_score),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Create generated components table
     */
    private function create_generated_components_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_generated_components';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            search_page_id bigint(20) unsigned NOT NULL,
            component_type varchar(100) NOT NULL,
            component_data json,
            ai_provider varchar(50) DEFAULT '',
            generation_time decimal(8,3) DEFAULT 0.000,
            confidence_score decimal(3,2) DEFAULT 0.00,
            approval_status enum('pending','approved','rejected') DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY search_page_id (search_page_id),
            KEY component_type (component_type),
            KEY ai_provider (ai_provider),
            KEY approval_status (approval_status),
            KEY confidence_score (confidence_score),
            KEY created_at (created_at),
            FOREIGN KEY (search_page_id) REFERENCES {$wpdb->prefix}spb_search_pages(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if search tables exist
     *
     * @return bool Whether all tables exist
     */
    public function search_tables_exist() {
        global $wpdb;
        
        $required_tables = [
            $wpdb->prefix . 'spb_search_pages',
            $wpdb->prefix . 'spb_query_enhancements',
            $wpdb->prefix . 'spb_generated_components'
        ];
        
        foreach ($required_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get search page by ID
     *
     * @param int $page_id Search page ID
     * @return array|null Search page data or null
     */
    public function get_search_page($page_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $page_id
        ), ARRAY_A);
        
        return $search_page;
    }
    
    /**
     * Get search page by query hash
     *
     * @param string $query_hash Query hash
     * @return array|null Search page data or null
     */
    public function get_search_page_by_hash($query_hash) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_page = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE query_hash = %s ORDER BY created_at DESC LIMIT 1",
            $query_hash
        ), ARRAY_A);
        
        return $search_page;
    }
    
    /**
     * Update search page approval status
     *
     * @param int $page_id Search page ID
     * @param string $status Approval status
     * @param int $approved_by User ID who approved
     * @return bool Success status
     */
    public function update_approval_status($page_id, $status, $approved_by = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $update_data = [
            'approval_status' => $status
        ];
        
        if ($status === 'approved') {
            $update_data['approved_at'] = current_time('mysql');
            if ($approved_by > 0) {
                $update_data['approved_by'] = $approved_by;
            }
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            ['id' => $page_id],
            ['%s', '%s', '%d'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Increment page view count
     *
     * @param int $page_id Search page ID
     * @return bool Success status
     */
    public function increment_view_count($page_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $result = $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET views_count = views_count + 1, last_viewed_at = %s WHERE id = %d",
            current_time('mysql'),
            $page_id
        ));
        
        return $result !== false;
    }
    
    /**
     * Get search pages for approval queue
     *
     * @param string $status Approval status filter
     * @param int $limit Number of results to return
     * @param int $offset Offset for pagination
     * @return array Search pages
     */
    public function get_approval_queue($status = 'pending', $limit = 20, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        
        $search_pages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE approval_status = %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $status,
            $limit,
            $offset
        ), ARRAY_A);
        
        return $search_pages;
    }
    
    /**
     * Get search statistics
     *
     * @param int $days Number of days to analyze
     * @return array Search statistics
     */
    public function get_search_stats($days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_searches,
                SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved_searches,
                SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending_searches,
                SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected_searches,
                SUM(views_count) as total_views,
                AVG(confidence_score) as avg_confidence,
                AVG(CASE WHEN approval_status = 'approved' THEN confidence_score END) as avg_approved_confidence
            FROM {$table_name} 
            WHERE created_at >= %s
        ", $date_limit), ARRAY_A);
        
        return $stats;
    }
    
    /**
     * Get popular search queries
     *
     * @param int $limit Number of results to return
     * @param int $days Number of days to analyze
     * @return array Popular queries
     */
    public function get_popular_queries($limit = 10, $days = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $queries = $wpdb->get_results($wpdb->prepare("
            SELECT 
                search_query,
                COUNT(*) as search_count,
                SUM(views_count) as total_views,
                AVG(confidence_score) as avg_confidence,
                MAX(created_at) as last_searched
            FROM {$table_name} 
            WHERE created_at >= %s 
            GROUP BY search_query 
            ORDER BY search_count DESC, total_views DESC 
            LIMIT %d
        ", $date_limit, $limit), ARRAY_A);
        
        return $queries;
    }
    
    /**
     * Clean up old search pages
     *
     * @param int $days Number of days to keep
     * @return int Number of deleted pages
     */
    public function cleanup_old_pages($days = 90) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_search_pages';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Only delete rejected or low-confidence pending pages
        $deleted = $wpdb->query($wpdb->prepare("
            DELETE FROM {$table_name} 
            WHERE created_at < %s 
            AND (
                approval_status = 'rejected' 
                OR (approval_status = 'pending' AND confidence_score < 0.5)
            )
        ", $date_limit));
        
        return $deleted;
    }
    
    /**
     * Store query enhancement data
     *
     * @param string $original_query Original query
     * @param string $enhanced_query Enhanced query
     * @param string $intent Detected intent
     * @param array $enhancement_data Enhancement data
     * @param float $processing_time Processing time in milliseconds
     * @param float $confidence_score Confidence score
     * @return int|false Enhancement ID or false on failure
     */
    public function store_query_enhancement($original_query, $enhanced_query, $intent, $enhancement_data, $processing_time, $confidence_score) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_query_enhancements';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'original_query' => $original_query,
                'enhanced_query' => $enhanced_query,
                'detected_intent' => $intent,
                'enhancement_data' => wp_json_encode($enhancement_data),
                'processing_time' => $processing_time / 1000, // Convert to seconds
                'confidence_score' => $confidence_score,
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%s', '%f', '%f', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Store generated component data
     *
     * @param int $search_page_id Search page ID
     * @param string $component_type Component type
     * @param array $component_data Component data
     * @param string $ai_provider AI provider used
     * @param float $generation_time Generation time in milliseconds
     * @param float $confidence_score Confidence score
     * @return int|false Component ID or false on failure
     */
    public function store_generated_component($search_page_id, $component_type, $component_data, $ai_provider, $generation_time, $confidence_score) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_generated_components';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'search_page_id' => $search_page_id,
                'component_type' => $component_type,
                'component_data' => wp_json_encode($component_data),
                'ai_provider' => $ai_provider,
                'generation_time' => $generation_time / 1000, // Convert to seconds
                'confidence_score' => $confidence_score,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%s']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Get components for a search page
     *
     * @param int $search_page_id Search page ID
     * @return array Components
     */
    public function get_page_components($search_page_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_generated_components';
        
        $components = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE search_page_id = %d ORDER BY created_at ASC",
            $search_page_id
        ), ARRAY_A);
        
        return $components;
    }
    
    /**
     * Drop all search tables (for uninstall)
     */
    public function drop_search_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'spb_generated_components',
            $wpdb->prefix . 'spb_query_enhancements',
            $wpdb->prefix . 'spb_search_pages'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        
        delete_option('spb_search_db_version');
    }
    
    /**
     * Check if database needs upgrade
     *
     * @return bool Whether upgrade is needed
     */
    public function needs_upgrade() {
        $current_version = get_option('spb_search_db_version', '0.0.0');
        return version_compare($current_version, self::SEARCH_DB_VERSION, '<');
    }
    
    /**
     * Upgrade database if needed
     */
    public function maybe_upgrade() {
        if ($this->needs_upgrade()) {
            $this->create_search_tables();
        }
    }
}
