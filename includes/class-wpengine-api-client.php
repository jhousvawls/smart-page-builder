<?php
/**
 * WP Engine AI Toolkit API Client
 *
 * Handles communication with WP Engine Smart Search, Vector Database, and Recommendations
 *
 * @package Smart_Page_Builder
 * @since   3.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WP Engine API Client class
 */
class SPB_WPEngine_API_Client {
    
    /**
     * API endpoint base URL
     */
    private $api_base_url;
    
    /**
     * API access token
     */
    private $access_token;
    
    /**
     * Site ID
     */
    private $site_id;
    
    /**
     * HTTP timeout in seconds
     */
    private $timeout = 30;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_base_url = get_option('spb_wpengine_api_url', '');
        $this->access_token = get_option('spb_wpengine_access_token', '');
        $this->site_id = get_option('spb_wpengine_site_id', '');
    }
    
    /**
     * Test API connection
     *
     * @return array Connection test result
     */
    public function test_connection() {
        if (empty($this->api_base_url) || empty($this->access_token) || empty($this->site_id)) {
            return [
                'success' => false,
                'error' => 'Missing API credentials. Please configure WP Engine settings.',
                'error_code' => 'MISSING_CREDENTIALS'
            ];
        }
        
        $response = $this->make_request('GET', '/health', []);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
                'error_code' => 'CONNECTION_ERROR'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Successfully connected to WP Engine AI Toolkit',
            'response' => $response
        ];
    }
    
    /**
     * Execute Smart Search query
     *
     * @param string $query Search query
     * @param array $options Search options
     * @return array|WP_Error Search results or error
     */
    public function smart_search($query, $options = []) {
        $default_options = [
            'limit' => 10,
            'offset' => 0,
            'include_content' => true,
            'include_metadata' => true,
            'semantic_search' => true
        ];
        
        $options = wp_parse_args($options, $default_options);
        
        $graphql_query = '
            query SmartSearch($query: String!, $limit: Int, $offset: Int, $siteId: String!) {
                smartSearch(
                    query: $query
                    limit: $limit
                    offset: $offset
                    siteId: $siteId
                    includeContent: ' . ($options['include_content'] ? 'true' : 'false') . '
                    includeMetadata: ' . ($options['include_metadata'] ? 'true' : 'false') . '
                    semanticSearch: ' . ($options['semantic_search'] ? 'true' : 'false') . '
                ) {
                    results {
                        id
                        title
                        content
                        excerpt
                        url
                        type
                        score
                        metadata {
                            author
                            publishDate
                            categories
                            tags
                        }
                    }
                    totalResults
                    searchTime
                }
            }
        ';
        
        $variables = [
            'query' => $query,
            'limit' => $options['limit'],
            'offset' => $options['offset'],
            'siteId' => $this->site_id
        ];
        
        return $this->make_graphql_request($graphql_query, $variables);
    }
    
    /**
     * Query vector database for semantic content discovery
     *
     * @param string $query Query text
     * @param array $options Query options
     * @return array|WP_Error Vector search results or error
     */
    public function vector_search($query, $options = []) {
        $default_options = [
            'limit' => 10,
            'similarity_threshold' => 0.7,
            'include_embeddings' => false,
            'content_types' => ['post', 'page']
        ];
        
        $options = wp_parse_args($options, $default_options);
        
        $graphql_query = '
            query VectorSearch($query: String!, $limit: Int, $threshold: Float, $siteId: String!, $contentTypes: [String!]) {
                vectorSearch(
                    query: $query
                    limit: $limit
                    similarityThreshold: $threshold
                    siteId: $siteId
                    contentTypes: $contentTypes
                ) {
                    results {
                        id
                        title
                        content
                        excerpt
                        url
                        type
                        similarity
                        embedding
                        metadata {
                            author
                            publishDate
                            categories
                            tags
                        }
                    }
                    totalResults
                    queryTime
                }
            }
        ';
        
        $variables = [
            'query' => $query,
            'limit' => $options['limit'],
            'threshold' => $options['similarity_threshold'],
            'siteId' => $this->site_id,
            'contentTypes' => $options['content_types']
        ];
        
        return $this->make_graphql_request($graphql_query, $variables);
    }
    
    /**
     * Get personalized content recommendations
     *
     * @param array $user_context User context data
     * @param array $options Recommendation options
     * @return array|WP_Error Recommendations or error
     */
    public function get_recommendations($user_context = [], $options = []) {
        $default_options = [
            'limit' => 5,
            'content_types' => ['post', 'page'],
            'exclude_ids' => [],
            'include_trending' => true
        ];
        
        $options = wp_parse_args($options, $default_options);
        
        $graphql_query = '
            query GetRecommendations($userContext: UserContextInput, $limit: Int, $siteId: String!, $contentTypes: [String!], $excludeIds: [String!]) {
                recommendations(
                    userContext: $userContext
                    limit: $limit
                    siteId: $siteId
                    contentTypes: $contentTypes
                    excludeIds: $excludeIds
                ) {
                    results {
                        id
                        title
                        content
                        excerpt
                        url
                        type
                        score
                        reason
                        metadata {
                            author
                            publishDate
                            categories
                            tags
                        }
                    }
                    totalResults
                    recommendationTime
                }
            }
        ';
        
        $variables = [
            'userContext' => $user_context,
            'limit' => $options['limit'],
            'siteId' => $this->site_id,
            'contentTypes' => $options['content_types'],
            'excludeIds' => $options['exclude_ids']
        ];
        
        return $this->make_graphql_request($graphql_query, $variables);
    }
    
    /**
     * Make GraphQL request to WP Engine API
     *
     * @param string $query GraphQL query
     * @param array $variables Query variables
     * @return array|WP_Error Response data or error
     */
    private function make_graphql_request($query, $variables = []) {
        $body = [
            'query' => $query,
            'variables' => $variables
        ];
        
        return $this->make_request('POST', '/graphql', $body);
    }
    
    /**
     * Make HTTP request to WP Engine API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|WP_Error Response data or error
     */
    private function make_request($method, $endpoint, $data = []) {
        if (empty($this->api_base_url) || empty($this->access_token)) {
            return new WP_Error(
                'missing_credentials',
                'WP Engine API credentials not configured'
            );
        }
        
        $url = rtrim($this->api_base_url, '/') . $endpoint;
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json',
            'User-Agent' => 'Smart-Page-Builder/' . Smart_Page_Builder::VERSION
        ];
        
        $args = [
            'method' => $method,
            'headers' => $headers,
            'timeout' => $this->timeout,
            'sslverify' => true
        ];
        
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 400) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'API request failed';
            
            return new WP_Error(
                'api_error',
                $error_message,
                [
                    'status' => $response_code,
                    'response' => $error_data
                ]
            );
        }
        
        $decoded_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'invalid_response',
                'Invalid JSON response from API'
            );
        }
        
        // Handle GraphQL errors
        if (isset($decoded_response['errors']) && !empty($decoded_response['errors'])) {
            $error_messages = array_map(function($error) {
                return $error['message'];
            }, $decoded_response['errors']);
            
            return new WP_Error(
                'graphql_error',
                implode('; ', $error_messages),
                $decoded_response['errors']
            );
        }
        
        return $decoded_response;
    }
    
    /**
     * Update API credentials
     *
     * @param string $api_url API base URL
     * @param string $access_token Access token
     * @param string $site_id Site ID
     * @return bool Success status
     */
    public function update_credentials($api_url, $access_token, $site_id) {
        $this->api_base_url = $api_url;
        $this->access_token = $access_token;
        $this->site_id = $site_id;
        
        update_option('spb_wpengine_api_url', $api_url);
        update_option('spb_wpengine_access_token', $access_token);
        update_option('spb_wpengine_site_id', $site_id);
        
        return true;
    }
    
    /**
     * Get current API configuration
     *
     * @return array API configuration
     */
    public function get_configuration() {
        return [
            'api_url' => $this->api_base_url,
            'site_id' => $this->site_id,
            'has_token' => !empty($this->access_token),
            'timeout' => $this->timeout
        ];
    }
    
    /**
     * Set request timeout
     *
     * @param int $timeout Timeout in seconds
     */
    public function set_timeout($timeout) {
        $this->timeout = max(5, min(120, intval($timeout)));
    }
}
