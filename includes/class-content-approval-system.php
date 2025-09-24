<?php
/**
 * Content Approval System for Smart Page Builder
 *
 * Multi-level approval workflow with role-based permissions,
 * automated routing, and admin interface integration.
 *
 * @package Smart_Page_Builder
 * @subpackage Content_Approval
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content Approval System Class
 *
 * Manages multi-level approval workflow for AI-generated content
 * with role-based permissions and automated routing.
 */
class SPB_Content_Approval_System {

    /**
     * Approval workflow stages
     *
     * @var array
     */
    private $approval_stages = [
        'auto_approved' => 'Automatically Approved',
        'pending_review' => 'Pending Review',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'revision_required' => 'Revision Required'
    ];

    /**
     * User roles and their approval permissions
     *
     * @var array
     */
    private $role_permissions = [
        'administrator' => ['approve', 'reject', 'edit', 'publish', 'bulk_operations'],
        'editor' => ['approve', 'reject', 'edit', 'bulk_operations'],
        'author' => ['edit', 'submit_for_review'],
        'contributor' => ['submit_for_review'],
        'subscriber' => []
    ];

    /**
     * Quality thresholds for automated routing
     *
     * @var array
     */
    private $quality_thresholds = [
        'auto_approve' => 0.85,
        'fast_track' => 0.75,
        'standard_review' => 0.60,
        'detailed_review' => 0.40,
        'auto_reject' => 0.30
    ];

    /**
     * Approval workflow configuration
     *
     * @var array
     */
    private $workflow_config = [
        'require_dual_approval' => false,
        'auto_publish_approved' => true,
        'notification_enabled' => true,
        'escalation_timeout' => 48, // hours
        'bulk_operation_limit' => 50
    ];

    /**
     * Initialize the content approval system
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_workflow_config();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Content submission hooks
        add_action('spb_content_generated', [$this, 'route_content_for_approval'], 10, 3);
        add_action('spb_content_submitted', [$this, 'process_content_submission'], 10, 2);
        
        // Approval action hooks
        add_action('wp_ajax_spb_approve_content', [$this, 'handle_approve_content']);
        add_action('wp_ajax_spb_reject_content', [$this, 'handle_reject_content']);
        add_action('wp_ajax_spb_bulk_approve', [$this, 'handle_bulk_approve']);
        add_action('wp_ajax_spb_bulk_reject', [$this, 'handle_bulk_reject']);
        
        // Admin interface hooks
        add_action('admin_menu', [$this, 'add_approval_admin_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_approval_assets']);
        
        // Notification hooks
        add_action('spb_content_approved', [$this, 'send_approval_notification'], 10, 2);
        add_action('spb_content_rejected', [$this, 'send_rejection_notification'], 10, 3);
        
        // Scheduled tasks
        add_action('spb_approval_escalation_check', [$this, 'check_approval_escalations']);
        
        // REST API endpoints
        add_action('rest_api_init', [$this, 'register_approval_endpoints']);
    }

    /**
     * Route content for approval based on quality assessment
     *
     * @param array $content_data Generated content
     * @param array $quality_assessment Quality assessment results
     * @param array $generation_context Generation context
     */
    public function route_content_for_approval($content_data, $quality_assessment, $generation_context) {
        try {
            $overall_score = $quality_assessment['overall_score'] ?? 0;
            $approval_recommendation = $quality_assessment['approval_recommendation'] ?? 'manual_review';
            
            // Determine routing based on quality score
            $routing_decision = $this->determine_routing($overall_score, $approval_recommendation);
            
            // Create approval record
            $approval_record = $this->create_approval_record($content_data, $quality_assessment, $generation_context, $routing_decision);
            
            // Execute routing decision
            $this->execute_routing_decision($approval_record, $routing_decision);
            
            // Log routing decision
            $this->log_routing_decision($approval_record['id'], $routing_decision, $overall_score);
            
        } catch (Exception $e) {
            error_log('SPB Approval Routing Error: ' . $e->getMessage());
            
            // Fallback to manual review
            $this->route_to_manual_review($content_data, $quality_assessment, $generation_context);
        }
    }

    /**
     * Determine routing decision based on quality assessment
     *
     * @param float $overall_score Overall quality score
     * @param string $recommendation Quality assessment recommendation
     * @return array Routing decision
     */
    private function determine_routing($overall_score, $recommendation) {
        $routing = [
            'action' => 'manual_review',
            'priority' => 'normal',
            'assigned_role' => 'editor',
            'escalation_timeout' => $this->workflow_config['escalation_timeout']
        ];
        
        if ($overall_score >= $this->quality_thresholds['auto_approve'] && $recommendation === 'auto_approve') {
            $routing['action'] = 'auto_approve';
            $routing['priority'] = 'auto';
        } elseif ($overall_score >= $this->quality_thresholds['fast_track']) {
            $routing['action'] = 'fast_track_review';
            $routing['priority'] = 'high';
            $routing['escalation_timeout'] = 24; // Faster escalation
        } elseif ($overall_score >= $this->quality_thresholds['standard_review']) {
            $routing['action'] = 'standard_review';
            $routing['priority'] = 'normal';
        } elseif ($overall_score >= $this->quality_thresholds['detailed_review']) {
            $routing['action'] = 'detailed_review';
            $routing['priority'] = 'low';
            $routing['assigned_role'] = 'administrator';
            $routing['escalation_timeout'] = 72; // Longer review time
        } else {
            $routing['action'] = 'auto_reject';
            $routing['priority'] = 'auto';
        }
        
        return $routing;
    }

    /**
     * Create approval record in database
     *
     * @param array $content_data Content data
     * @param array $quality_assessment Quality assessment
     * @param array $generation_context Generation context
     * @param array $routing_decision Routing decision
     * @return array Approval record
     */
    private function create_approval_record($content_data, $quality_assessment, $generation_context, $routing_decision) {
        global $wpdb;
        
        $approval_data = [
            'content_id' => $generation_context['content_id'] ?? uniqid('spb_'),
            'search_query' => $generation_context['search_query'] ?? '',
            'content_data' => wp_json_encode($content_data),
            'quality_score' => $quality_assessment['overall_score'] ?? 0,
            'quality_details' => wp_json_encode($quality_assessment),
            'routing_action' => $routing_decision['action'],
            'priority' => $routing_decision['priority'],
            'assigned_role' => $routing_decision['assigned_role'] ?? 'editor',
            'status' => $this->get_initial_status($routing_decision['action']),
            'created_at' => current_time('mysql'),
            'escalation_deadline' => $this->calculate_escalation_deadline($routing_decision['escalation_timeout']),
            'user_context' => wp_json_encode($generation_context['user_context'] ?? [])
        ];
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        $wpdb->insert($table_name, $approval_data);
        
        $approval_data['id'] = $wpdb->insert_id;
        
        return $approval_data;
    }

    /**
     * Execute routing decision
     *
     * @param array $approval_record Approval record
     * @param array $routing_decision Routing decision
     */
    private function execute_routing_decision($approval_record, $routing_decision) {
        switch ($routing_decision['action']) {
            case 'auto_approve':
                $this->auto_approve_content($approval_record);
                break;
                
            case 'auto_reject':
                $this->auto_reject_content($approval_record);
                break;
                
            case 'fast_track_review':
            case 'standard_review':
            case 'detailed_review':
                $this->assign_for_review($approval_record, $routing_decision);
                break;
                
            default:
                $this->assign_for_review($approval_record, $routing_decision);
                break;
        }
    }

    /**
     * Auto-approve content
     *
     * @param array $approval_record Approval record
     */
    private function auto_approve_content($approval_record) {
        $this->update_approval_status($approval_record['id'], 'auto_approved', [
            'approved_by' => 'system',
            'approved_at' => current_time('mysql'),
            'approval_notes' => 'Automatically approved based on quality score'
        ]);
        
        // Publish content if configured
        if ($this->workflow_config['auto_publish_approved']) {
            $this->publish_approved_content($approval_record);
        }
        
        // Send notification
        do_action('spb_content_approved', $approval_record, 'auto');
    }

    /**
     * Auto-reject content
     *
     * @param array $approval_record Approval record
     */
    private function auto_reject_content($approval_record) {
        $this->update_approval_status($approval_record['id'], 'rejected', [
            'rejected_by' => 'system',
            'rejected_at' => current_time('mysql'),
            'rejection_reason' => 'Quality score below minimum threshold'
        ]);
        
        // Send notification
        do_action('spb_content_rejected', $approval_record, 'Quality score too low', 'auto');
    }

    /**
     * Assign content for review
     *
     * @param array $approval_record Approval record
     * @param array $routing_decision Routing decision
     */
    private function assign_for_review($approval_record, $routing_decision) {
        // Find available reviewer
        $assigned_user = $this->find_available_reviewer($routing_decision['assigned_role'], $routing_decision['priority']);
        
        $this->update_approval_status($approval_record['id'], 'pending_review', [
            'assigned_to' => $assigned_user,
            'assigned_at' => current_time('mysql'),
            'priority' => $routing_decision['priority']
        ]);
        
        // Send assignment notification
        $this->send_assignment_notification($approval_record, $assigned_user);
        
        // Schedule escalation check
        $this->schedule_escalation_check($approval_record['id'], $routing_decision['escalation_timeout']);
    }

    /**
     * Handle content approval AJAX request
     */
    public function handle_approve_content() {
        check_ajax_referer('spb_approval_action', 'nonce');
        
        if (!$this->user_can_approve()) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $approval_id = intval($_POST['approval_id'] ?? 0);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$approval_id) {
            wp_send_json_error(['message' => 'Invalid approval ID']);
            return;
        }
        
        $approval_record = $this->get_approval_record($approval_id);
        if (!$approval_record) {
            wp_send_json_error(['message' => 'Approval record not found']);
            return;
        }
        
        // Process approval
        $result = $this->approve_content($approval_record, get_current_user_id(), $notes);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => 'Content approved successfully',
                'approval_id' => $approval_id
            ]);
        } else {
            wp_send_json_error(['message' => $result['error']]);
        }
    }

    /**
     * Handle content rejection AJAX request
     */
    public function handle_reject_content() {
        check_ajax_referer('spb_approval_action', 'nonce');
        
        if (!$this->user_can_approve()) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $approval_id = intval($_POST['approval_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (!$approval_id) {
            wp_send_json_error(['message' => 'Invalid approval ID']);
            return;
        }
        
        $approval_record = $this->get_approval_record($approval_id);
        if (!$approval_record) {
            wp_send_json_error(['message' => 'Approval record not found']);
            return;
        }
        
        // Process rejection
        $result = $this->reject_content($approval_record, get_current_user_id(), $reason, $notes);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => 'Content rejected successfully',
                'approval_id' => $approval_id
            ]);
        } else {
            wp_send_json_error(['message' => $result['error']]);
        }
    }

    /**
     * Handle bulk approval AJAX request
     */
    public function handle_bulk_approve() {
        check_ajax_referer('spb_bulk_approval', 'nonce');
        
        if (!$this->user_can_bulk_approve()) {
            wp_send_json_error(['message' => 'Insufficient permissions for bulk operations']);
            return;
        }
        
        $approval_ids = array_map('intval', $_POST['approval_ids'] ?? []);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (empty($approval_ids)) {
            wp_send_json_error(['message' => 'No items selected']);
            return;
        }
        
        if (count($approval_ids) > $this->workflow_config['bulk_operation_limit']) {
            wp_send_json_error(['message' => 'Too many items selected for bulk operation']);
            return;
        }
        
        $results = $this->bulk_approve_content($approval_ids, get_current_user_id(), $notes);
        
        wp_send_json_success([
            'message' => sprintf('%d items approved successfully', $results['approved']),
            'approved' => $results['approved'],
            'failed' => $results['failed']
        ]);
    }

    /**
     * Handle bulk rejection AJAX request
     */
    public function handle_bulk_reject() {
        check_ajax_referer('spb_bulk_approval', 'nonce');
        
        if (!$this->user_can_bulk_approve()) {
            wp_send_json_error(['message' => 'Insufficient permissions for bulk operations']);
            return;
        }
        
        $approval_ids = array_map('intval', $_POST['approval_ids'] ?? []);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        
        if (empty($approval_ids)) {
            wp_send_json_error(['message' => 'No items selected']);
            return;
        }
        
        if (count($approval_ids) > $this->workflow_config['bulk_operation_limit']) {
            wp_send_json_error(['message' => 'Too many items selected for bulk operation']);
            return;
        }
        
        $results = $this->bulk_reject_content($approval_ids, get_current_user_id(), $reason, $notes);
        
        wp_send_json_success([
            'message' => sprintf('%d items rejected successfully', $results['rejected']),
            'rejected' => $results['rejected'],
            'failed' => $results['failed']
        ]);
    }

    /**
     * Approve content
     *
     * @param array $approval_record Approval record
     * @param int $user_id Approving user ID
     * @param string $notes Approval notes
     * @return array Result
     */
    private function approve_content($approval_record, $user_id, $notes = '') {
        try {
            // Check if dual approval is required
            if ($this->workflow_config['require_dual_approval'] && !$this->has_dual_approval($approval_record['id'])) {
                return $this->process_first_approval($approval_record, $user_id, $notes);
            }
            
            // Update approval status
            $this->update_approval_status($approval_record['id'], 'approved', [
                'approved_by' => $user_id,
                'approved_at' => current_time('mysql'),
                'approval_notes' => $notes
            ]);
            
            // Publish content if configured
            if ($this->workflow_config['auto_publish_approved']) {
                $this->publish_approved_content($approval_record);
            }
            
            // Send notification
            do_action('spb_content_approved', $approval_record, 'manual');
            
            // Log approval action
            $this->log_approval_action($approval_record['id'], 'approved', $user_id, $notes);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log('SPB Content Approval Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Reject content
     *
     * @param array $approval_record Approval record
     * @param int $user_id Rejecting user ID
     * @param string $reason Rejection reason
     * @param string $notes Additional notes
     * @return array Result
     */
    private function reject_content($approval_record, $user_id, $reason, $notes = '') {
        try {
            // Update approval status
            $this->update_approval_status($approval_record['id'], 'rejected', [
                'rejected_by' => $user_id,
                'rejected_at' => current_time('mysql'),
                'rejection_reason' => $reason,
                'rejection_notes' => $notes
            ]);
            
            // Send notification
            do_action('spb_content_rejected', $approval_record, $reason, 'manual');
            
            // Log rejection action
            $this->log_approval_action($approval_record['id'], 'rejected', $user_id, $reason . ' | ' . $notes);
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log('SPB Content Rejection Error: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bulk approve content
     *
     * @param array $approval_ids Approval IDs
     * @param int $user_id Approving user ID
     * @param string $notes Approval notes
     * @return array Results
     */
    private function bulk_approve_content($approval_ids, $user_id, $notes = '') {
        $approved = 0;
        $failed = 0;
        
        foreach ($approval_ids as $approval_id) {
            $approval_record = $this->get_approval_record($approval_id);
            if ($approval_record) {
                $result = $this->approve_content($approval_record, $user_id, $notes);
                if ($result['success']) {
                    $approved++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
        
        return ['approved' => $approved, 'failed' => $failed];
    }

    /**
     * Bulk reject content
     *
     * @param array $approval_ids Approval IDs
     * @param int $user_id Rejecting user ID
     * @param string $reason Rejection reason
     * @param string $notes Additional notes
     * @return array Results
     */
    private function bulk_reject_content($approval_ids, $user_id, $reason, $notes = '') {
        $rejected = 0;
        $failed = 0;
        
        foreach ($approval_ids as $approval_id) {
            $approval_record = $this->get_approval_record($approval_id);
            if ($approval_record) {
                $result = $this->reject_content($approval_record, $user_id, $reason, $notes);
                if ($result['success']) {
                    $rejected++;
                } else {
                    $failed++;
                }
            } else {
                $failed++;
            }
        }
        
        return ['rejected' => $rejected, 'failed' => $failed];
    }

    /**
     * Add approval admin pages
     */
    public function add_approval_admin_pages() {
        add_submenu_page(
            'smart-page-builder',
            'Content Approval',
            'Content Approval',
            'edit_posts',
            'spb-content-approval',
            [$this, 'render_approval_page']
        );
        
        add_submenu_page(
            'smart-page-builder',
            'Approval Queue',
            'Approval Queue',
            'edit_posts',
            'spb-approval-queue',
            [$this, 'render_approval_queue']
        );
    }

    /**
     * Render approval page
     */
    public function render_approval_page() {
        include plugin_dir_path(__FILE__) . '../admin/partials/smart-page-builder-admin-approval.php';
    }

    /**
     * Render approval queue
     */
    public function render_approval_queue() {
        include plugin_dir_path(__FILE__) . '../admin/partials/smart-page-builder-admin-approval-queue.php';
    }

    /**
     * Enqueue approval assets
     *
     * @param string $hook_suffix Current admin page
     */
    public function enqueue_approval_assets($hook_suffix) {
        if (strpos($hook_suffix, 'spb-') !== false) {
            wp_enqueue_script(
                'spb-approval-admin',
                plugin_dir_url(__FILE__) . '../admin/js/approval-admin.js',
                ['jquery'],
                '1.0.0',
                true
            );
            
            wp_enqueue_style(
                'spb-approval-admin',
                plugin_dir_url(__FILE__) . '../admin/css/approval-admin.css',
                [],
                '1.0.0'
            );
            
            wp_localize_script('spb-approval-admin', 'spbApproval', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('spb_approval_action'),
                'bulkNonce' => wp_create_nonce('spb_bulk_approval'),
                'strings' => [
                    'confirmApprove' => 'Are you sure you want to approve this content?',
                    'confirmReject' => 'Are you sure you want to reject this content?',
                    'confirmBulkApprove' => 'Are you sure you want to approve the selected items?',
                    'confirmBulkReject' => 'Are you sure you want to reject the selected items?'
                ]
            ]);
        }
    }

    /**
     * Register REST API endpoints
     */
    public function register_approval_endpoints() {
        register_rest_route('spb/v1', '/approvals', [
            'methods' => 'GET',
            'callback' => [$this, 'get_approvals_endpoint'],
            'permission_callback' => [$this, 'check_approval_permissions']
        ]);
        
        register_rest_route('spb/v1', '/approvals/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_approval_endpoint'],
            'permission_callback' => [$this, 'check_approval_permissions']
        ]);
        
        register_rest_route('spb/v1', '/approvals/(?P<id>\d+)/approve', [
            'methods' => 'POST',
            'callback' => [$this, 'approve_content_endpoint'],
            'permission_callback' => [$this, 'check_approval_permissions']
        ]);
        
        register_rest_route('spb/v1', '/approvals/(?P<id>\d+)/reject', [
            'methods' => 'POST',
            'callback' => [$this, 'reject_content_endpoint'],
            'permission_callback' => [$this, 'check_approval_permissions']
        ]);
    }

    /**
     * Get approval queue data
     *
     * @param array $filters Filters to apply
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Approval queue data
     */
    public function get_approval_queue($filters = [], $page = 1, $per_page = 20) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return [
                'items' => [],
                'total_items' => 0,
                'total_pages' => 0,
                'current_page' => $page,
                'per_page' => $per_page
            ];
        }
        
        // Build WHERE clause
        $where_conditions = ['1=1'];
        $where_values = [];
        
        if (!empty($filters['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = $filters['status'];
        }
        
        if (!empty($filters['priority'])) {
            $where_conditions[] = 'priority = %s';
            $where_values[] = $filters['priority'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $where_conditions[] = 'assigned_to = %d';
            $where_values[] = $filters['assigned_to'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
        if (!empty($where_values)) {
            $count_query = $wpdb->prepare($count_query, $where_values);
        }
        $total_items = $wpdb->get_var($count_query);
        
        // Get paginated results
        $offset = ($page - 1) * $per_page;
        $query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $query_values = array_merge($where_values, [$per_page, $offset]);
        $results = $wpdb->get_results($wpdb->prepare($query, $query_values), ARRAY_A);
        
        // Process results
        foreach ($results as &$result) {
            $result['content_data'] = json_decode($result['content_data'], true);
            $result['quality_details'] = json_decode($result['quality_details'], true);
            $result['user_context'] = json_decode($result['user_context'], true);
        }
        
        return [
            'items' => $results,
            'total_items' => $total_items,
            'total_pages' => ceil($total_items / $per_page),
            'current_page' => $page,
            'per_page' => $per_page
        ];
    }

    /**
     * Get approval statistics
     *
     * @return array Approval statistics
     */
    public function get_approval_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        
        $stats = [
            'total' => 0,
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'auto_approved' => 0,
            'overdue' => 0
        ];
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));
        
        if (!$table_exists) {
            return $stats;
        }
        
        // Get status counts
        $status_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status",
            ARRAY_A
        );
        
        foreach ($status_counts as $status_count) {
            $status = $status_count['status'];
            $count = intval($status_count['count']);
            
            $stats['total'] += $count;
            
            if (isset($stats[$status])) {
                $stats[$status] = $count;
            }
        }
        
        // Get overdue count
        $stats['overdue'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE status IN ('pending_review', 'under_review') AND escalation_deadline < %s",
            current_time('mysql')
        ));
        
        return $stats;
    }

    // Helper methods for internal operations
    
    private function get_initial_status($action) {
        switch ($action) {
            case 'auto_approve':
                return 'auto_approved';
            case 'auto_reject':
                return 'rejected';
            default:
                return 'pending_review';
        }
    }
    
    private function calculate_escalation_deadline($timeout_hours) {
        return date('Y-m-d H:i:s', strtotime("+{$timeout_hours} hours"));
    }
    
    private function find_available_reviewer($role, $priority) {
        // Simplified implementation - would use more sophisticated assignment logic
        $users = get_users(['role' => $role, 'number' => 1]);
        return !empty($users) ? $users[0]->ID : null;
    }
    
    private function update_approval_status($approval_id, $status, $additional_data = []) {
        global $wpdb;
        
        $update_data = array_merge(['status' => $status], $additional_data);
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        return $wpdb->update($table_name, $update_data, ['id' => $approval_id]);
    }
    
    private function get_approval_record($approval_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $approval_id
        ), ARRAY_A);
    }
    
    private function user_can_approve() {
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        foreach ($user_roles as $role) {
            if (isset($this->role_permissions[$role]) && 
                in_array('approve', $this->role_permissions[$role])) {
                return true;
            }
        }
        
        return false;
    }
    
    private function user_can_bulk_approve() {
        $user = wp_get_current_user();
        $user_roles = $user->roles;
        
        foreach ($user_roles as $role) {
            if (isset($this->role_permissions[$role]) && 
                in_array('bulk_operations', $this->role_permissions[$role])) {
                return true;
            }
        }
        
        return false;
    }
    
    private function has_dual_approval($approval_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_approval_actions';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE approval_id = %d AND action = 'approved'",
            $approval_id
        ));
        
        return $count >= 1;
    }
    
    private function process_first_approval($approval_record, $user_id, $notes) {
        // Update status to require second approval
        $this->update_approval_status($approval_record['id'], 'awaiting_second_approval', [
            'first_approved_by' => $user_id,
            'first_approved_at' => current_time('mysql'),
            'first_approval_notes' => $notes
        ]);
        
        // Find second approver
        $second_approver = $this->find_second_approver($user_id);
        if ($second_approver) {
            $this->send_second_approval_notification($approval_record, $second_approver);
        }
        
        return ['success' => true, 'requires_second_approval' => true];
    }
    
    private function find_second_approver($first_approver_id) {
        $users = get_users(['role__in' => ['administrator', 'editor']]);
        foreach ($users as $user) {
            if ($user->ID !== $first_approver_id) {
                return $user->ID;
            }
        }
        return null;
    }
    
    private function publish_approved_content($approval_record) {
        // Implementation for publishing approved content
        // This would integrate with WordPress post creation
        
        $content_data = json_decode($approval_record['content_data'], true);
        
        $post_data = [
            'post_title' => $content_data['hero']['headline'] ?? 'Generated Content',
            'post_content' => $this->format_content_for_post($content_data),
            'post_status' => 'publish',
            'post_type' => 'page',
            'meta_input' => [
                'spb_generated_content' => true,
                'spb_approval_id' => $approval_record['id'],
                'spb_search_query' => $approval_record['search_query']
            ]
        ];
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && !is_wp_error($post_id)) {
            // Update approval record with post ID
            $this->update_approval_status($approval_record['id'], null, [
                'published_post_id' => $post_id,
                'published_at' => current_time('mysql')
            ]);
        }
        
        return $post_id;
    }
    
    private function format_content_for_post($content_data) {
        $formatted_content = '';
        
        // Add hero content
        if (!empty($content_data['hero'])) {
            $formatted_content .= '<div class="spb-hero-section">';
            if (!empty($content_data['hero']['headline'])) {
                $formatted_content .= '<h1>' . esc_html($content_data['hero']['headline']) . '</h1>';
            }
            if (!empty($content_data['hero']['subheadline'])) {
                $formatted_content .= '<p class="lead">' . esc_html($content_data['hero']['subheadline']) . '</p>';
            }
            $formatted_content .= '</div>';
        }
        
        // Add article content
        if (!empty($content_data['article']['content'])) {
            $formatted_content .= '<div class="spb-article-content">';
            $formatted_content .= wp_kses_post($content_data['article']['content']);
            $formatted_content .= '</div>';
        }
        
        // Add CTA content
        if (!empty($content_data['cta'])) {
            $formatted_content .= '<div class="spb-cta-section">';
            if (!empty($content_data['cta']['headline'])) {
                $formatted_content .= '<h2>' . esc_html($content_data['cta']['headline']) . '</h2>';
            }
            if (!empty($content_data['cta']['description'])) {
                $formatted_content .= '<p>' . esc_html($content_data['cta']['description']) . '</p>';
            }
            $formatted_content .= '</div>';
        }
        
        return $formatted_content;
    }
    
    private function log_routing_decision($approval_id, $routing_decision, $score) {
        global $wpdb;
        
        $log_data = [
            'approval_id' => $approval_id,
            'action' => 'routing_decision',
            'details' => wp_json_encode([
                'routing_action' => $routing_decision['action'],
                'priority' => $routing_decision['priority'],
                'quality_score' => $score
            ]),
            'created_at' => current_time('mysql')
        ];
        
        $table_name = $wpdb->prefix . 'spb_approval_logs';
        $wpdb->insert($table_name, $log_data);
    }
    
    private function log_approval_action($approval_id, $action, $user_id, $notes) {
        global $wpdb;
        
        $log_data = [
            'approval_id' => $approval_id,
            'action' => $action,
            'user_id' => $user_id,
            'notes' => $notes,
            'created_at' => current_time('mysql')
        ];
        
        $table_name = $wpdb->prefix . 'spb_approval_actions';
        $wpdb->insert($table_name, $log_data);
    }
    
    private function send_assignment_notification($approval_record, $assigned_user) {
        if (!$this->workflow_config['notification_enabled'] || !$assigned_user) {
            return;
        }
        
        $user = get_user_by('ID', $assigned_user);
        if (!$user) {
            return;
        }
        
        $subject = 'Content Review Assignment - Smart Page Builder';
        $message = sprintf(
            "Hello %s,\n\nYou have been assigned a new content review:\n\nSearch Query: %s\nQuality Score: %.2f\nPriority: %s\n\nPlease review the content in your admin dashboard.\n\nBest regards,\nSmart Page Builder",
            $user->display_name,
            $approval_record['search_query'],
            $approval_record['quality_score'],
            $approval_record['priority']
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    private function send_second_approval_notification($approval_record, $second_approver) {
        if (!$this->workflow_config['notification_enabled']) {
            return;
        }
        
        $user = get_user_by('ID', $second_approver);
        if (!$user) {
            return;
        }
        
        $subject = 'Second Approval Required - Smart Page Builder';
        $message = sprintf(
            "Hello %s,\n\nA content item requires your second approval:\n\nSearch Query: %s\nQuality Score: %.2f\n\nPlease review the content in your admin dashboard.\n\nBest regards,\nSmart Page Builder",
            $user->display_name,
            $approval_record['search_query'],
            $approval_record['quality_score']
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    private function schedule_escalation_check($approval_id, $timeout_hours) {
        // Schedule a WordPress cron job for escalation check
        $timestamp = time() + ($timeout_hours * HOUR_IN_SECONDS);
        wp_schedule_single_event($timestamp, 'spb_approval_escalation_check', [$approval_id]);
    }
    
    private function route_to_manual_review($content_data, $quality_assessment, $generation_context) {
        // Fallback routing to manual review
        $routing_decision = [
            'action' => 'manual_review',
            'priority' => 'normal',
            'assigned_role' => 'editor',
            'escalation_timeout' => $this->workflow_config['escalation_timeout']
        ];
        
        $approval_record = $this->create_approval_record($content_data, $quality_assessment, $generation_context, $routing_decision);
        $this->assign_for_review($approval_record, $routing_decision);
    }
    
    private function load_workflow_config() {
        $this->workflow_config = array_merge($this->workflow_config, get_option('spb_approval_workflow_config', []));
    }
    
    public function send_approval_notification($approval_record, $type) {
        // Implementation for approval notifications
        do_action('spb_send_approval_notification', $approval_record, $type);
    }
    
    public function send_rejection_notification($approval_record, $reason, $type) {
        // Implementation for rejection notifications
        do_action('spb_send_rejection_notification', $approval_record, $reason, $type);
    }
    
    public function check_approval_escalations() {
        // Implementation for checking approval escalations
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'spb_content_approvals';
        $overdue_approvals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE status IN ('pending_review', 'under_review') AND escalation_deadline < %s",
            current_time('mysql')
        ), ARRAY_A);
        
        foreach ($overdue_approvals as $approval) {
            $this->escalate_approval($approval);
        }
    }
    
    private function escalate_approval($approval_record) {
        // Escalate to administrator role
        $admin_users = get_users(['role' => 'administrator', 'number' => 1]);
        if (!empty($admin_users)) {
            $this->update_approval_status($approval_record['id'], 'escalated', [
                'escalated_to' => $admin_users[0]->ID,
                'escalated_at' => current_time('mysql')
            ]);
            
            $this->send_escalation_notification($approval_record, $admin_users[0]->ID);
        }
    }
    
    private function send_escalation_notification($approval_record, $admin_user_id) {
        if (!$this->workflow_config['notification_enabled']) {
            return;
        }
        
        $user = get_user_by('ID', $admin_user_id);
        if (!$user) {
            return;
        }
        
        $subject = 'Content Approval Escalation - Smart Page Builder';
        $message = sprintf(
            "Hello %s,\n\nA content approval has been escalated to you due to timeout:\n\nSearch Query: %s\nQuality Score: %.2f\nOriginal Deadline: %s\n\nPlease review the content urgently.\n\nBest regards,\nSmart Page Builder",
            $user->display_name,
            $approval_record['search_query'],
            $approval_record['quality_score'],
            $approval_record['escalation_deadline']
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    // REST API endpoint methods
    
    public function get_approvals_endpoint($request) {
        $page = $request->get_param('page') ?: 1;
        $per_page = $request->get_param('per_page') ?: 20;
        $filters = $request->get_params();
        
        return $this->get_approval_queue($filters, $page, $per_page);
    }
    
    public function get_approval_endpoint($request) {
        $approval_id = $request->get_param('id');
        return $this->get_approval_record($approval_id);
    }
    
    public function approve_content_endpoint($request) {
        $approval_id = $request->get_param('id');
        $notes = $request->get_param('notes') ?: '';
        
        $approval_record = $this->get_approval_record($approval_id);
        if (!$approval_record) {
            return new WP_Error('not_found', 'Approval record not found', ['status' => 404]);
        }
        
        return $this->approve_content($approval_record, get_current_user_id(), $notes);
    }
    
    public function reject_content_endpoint($request) {
        $approval_id = $request->get_param('id');
        $reason = $request->get_param('reason') ?: '';
        $notes = $request->get_param('notes') ?: '';
        
        $approval_record = $this->get_approval_record($approval_id);
        if (!$approval_record) {
            return new WP_Error('not_found', 'Approval record not found', ['status' => 404]);
        }
        
        return $this->reject_content($approval_record, get_current_user_id(), $reason, $notes);
    }
    
    public function check_approval_permissions($request) {
        return $this->user_can_approve();
    }
}
