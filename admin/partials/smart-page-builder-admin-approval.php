<?php
/**
 * Admin Approval Interface for Smart Page Builder
 *
 * Provides the main approval interface for AI-generated content
 * with comprehensive review and management capabilities.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure the approval system class is loaded
if (!class_exists('SPB_Content_Approval_System')) {
    require_once SPB_PLUGIN_DIR . 'includes/class-content-approval-system.php';
}

// Check if class exists before instantiating
if (class_exists('SPB_Content_Approval_System')) {
    $approval_system = new SPB_Content_Approval_System();
    $approval_stats = $approval_system->get_approval_statistics();
} else {
    // Fallback if class is not available
    $approval_stats = array(
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'auto_approved' => 0,
        'rejected' => 0,
        'overdue' => 0
    );
}

// Handle filter parameters
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$current_priority = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : 'all';
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Build filters
$filters = [];
if ($current_status !== 'all') {
    $filters['status'] = $current_status;
}
if ($current_priority !== 'all') {
    $filters['priority'] = $current_priority;
}

// Get approval queue data
$queue_data = $approval_system->get_approval_queue($filters, $current_page, 20);
?>

<div class="wrap spb-approval-interface">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Content Approval', 'smart-page-builder'); ?>
        <span class="title-count theme-count"><?php echo esc_html($approval_stats['total']); ?></span>
    </h1>

    <!-- Approval Statistics Dashboard -->
    <div class="spb-approval-stats">
        <div class="spb-stats-grid">
            <div class="spb-stat-card pending">
                <div class="spb-stat-number"><?php echo esc_html($approval_stats['pending']); ?></div>
                <div class="spb-stat-label"><?php esc_html_e('Pending Review', 'smart-page-builder'); ?></div>
            </div>
            <div class="spb-stat-card approved">
                <div class="spb-stat-number"><?php echo esc_html($approval_stats['approved']); ?></div>
                <div class="spb-stat-label"><?php esc_html_e('Approved', 'smart-page-builder'); ?></div>
            </div>
            <div class="spb-stat-card auto-approved">
                <div class="spb-stat-number"><?php echo esc_html($approval_stats['auto_approved']); ?></div>
                <div class="spb-stat-label"><?php esc_html_e('Auto-Approved', 'smart-page-builder'); ?></div>
            </div>
            <div class="spb-stat-card rejected">
                <div class="spb-stat-number"><?php echo esc_html($approval_stats['rejected']); ?></div>
                <div class="spb-stat-label"><?php esc_html_e('Rejected', 'smart-page-builder'); ?></div>
            </div>
            <div class="spb-stat-card overdue">
                <div class="spb-stat-number"><?php echo esc_html($approval_stats['overdue']); ?></div>
                <div class="spb-stat-label"><?php esc_html_e('Overdue', 'smart-page-builder'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="spb-approval-filters">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="spb-content-approval" />
                
                <select name="status" id="filter-by-status">
                    <option value="all" <?php selected($current_status, 'all'); ?>><?php esc_html_e('All Statuses', 'smart-page-builder'); ?></option>
                    <option value="pending_review" <?php selected($current_status, 'pending_review'); ?>><?php esc_html_e('Pending Review', 'smart-page-builder'); ?></option>
                    <option value="under_review" <?php selected($current_status, 'under_review'); ?>><?php esc_html_e('Under Review', 'smart-page-builder'); ?></option>
                    <option value="approved" <?php selected($current_status, 'approved'); ?>><?php esc_html_e('Approved', 'smart-page-builder'); ?></option>
                    <option value="rejected" <?php selected($current_status, 'rejected'); ?>><?php esc_html_e('Rejected', 'smart-page-builder'); ?></option>
                    <option value="auto_approved" <?php selected($current_status, 'auto_approved'); ?>><?php esc_html_e('Auto-Approved', 'smart-page-builder'); ?></option>
                </select>

                <select name="priority" id="filter-by-priority">
                    <option value="all" <?php selected($current_priority, 'all'); ?>><?php esc_html_e('All Priorities', 'smart-page-builder'); ?></option>
                    <option value="high" <?php selected($current_priority, 'high'); ?>><?php esc_html_e('High Priority', 'smart-page-builder'); ?></option>
                    <option value="normal" <?php selected($current_priority, 'normal'); ?>><?php esc_html_e('Normal Priority', 'smart-page-builder'); ?></option>
                    <option value="low" <?php selected($current_priority, 'low'); ?>><?php esc_html_e('Low Priority', 'smart-page-builder'); ?></option>
                </select>

                <?php submit_button(__('Filter', 'smart-page-builder'), 'secondary', 'filter_action', false); ?>
            </form>
        </div>

        <div class="alignright actions">
            <button type="button" class="button button-primary" id="bulk-approve-btn" disabled>
                <?php esc_html_e('Bulk Approve', 'smart-page-builder'); ?>
            </button>
            <button type="button" class="button button-secondary" id="bulk-reject-btn" disabled>
                <?php esc_html_e('Bulk Reject', 'smart-page-builder'); ?>
            </button>
        </div>
        <div class="clear"></div>
    </div>

    <!-- Approval Queue Table -->
    <form id="spb-approval-form" method="post">
        <?php wp_nonce_field('spb_bulk_approval', 'spb_bulk_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped spb-approval-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all" />
                    </td>
                    <th class="manage-column column-search-query"><?php esc_html_e('Search Query', 'smart-page-builder'); ?></th>
                    <th class="manage-column column-quality-score"><?php esc_html_e('Quality Score', 'smart-page-builder'); ?></th>
                    <th class="manage-column column-status"><?php esc_html_e('Status', 'smart-page-builder'); ?></th>
                    <th class="manage-column column-priority"><?php esc_html_e('Priority', 'smart-page-builder'); ?></th>
                    <th class="manage-column column-created"><?php esc_html_e('Created', 'smart-page-builder'); ?></th>
                    <th class="manage-column column-actions"><?php esc_html_e('Actions', 'smart-page-builder'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($queue_data['items'])): ?>
                    <?php foreach ($queue_data['items'] as $item): ?>
                        <tr data-approval-id="<?php echo esc_attr($item['id']); ?>">
                            <th class="check-column">
                                <input type="checkbox" name="approval_ids[]" value="<?php echo esc_attr($item['id']); ?>" />
                            </th>
                            <td class="column-search-query">
                                <strong><?php echo esc_html($item['search_query']); ?></strong>
                                <div class="row-actions">
                                    <span class="preview">
                                        <a href="#" class="spb-preview-content" data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                            <?php esc_html_e('Preview', 'smart-page-builder'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-quality-score">
                                <div class="spb-quality-score">
                                    <div class="spb-score-bar">
                                        <div class="spb-score-fill" style="width: <?php echo esc_attr($item['quality_score'] * 100); ?>%"></div>
                                    </div>
                                    <span class="spb-score-text"><?php echo esc_html(round($item['quality_score'] * 100)); ?>%</span>
                                </div>
                            </td>
                            <td class="column-status">
                                <span class="spb-status spb-status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $item['status']))); ?>
                                </span>
                            </td>
                            <td class="column-priority">
                                <span class="spb-priority spb-priority-<?php echo esc_attr($item['priority']); ?>">
                                    <?php echo esc_html(ucfirst($item['priority'])); ?>
                                </span>
                            </td>
                            <td class="column-created">
                                <?php echo esc_html(human_time_diff(strtotime($item['created_at']), current_time('timestamp'))); ?> ago
                            </td>
                            <td class="column-actions">
                                <div class="spb-action-buttons">
                                    <?php if (in_array($item['status'], ['pending_review', 'under_review'])): ?>
                                        <button type="button" class="button button-primary button-small spb-approve-btn" 
                                                data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                            <?php esc_html_e('Approve', 'smart-page-builder'); ?>
                                        </button>
                                        <button type="button" class="button button-secondary button-small spb-reject-btn" 
                                                data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                            <?php esc_html_e('Reject', 'smart-page-builder'); ?>
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="button button-small spb-edit-btn" 
                                            data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                        <?php esc_html_e('Edit', 'smart-page-builder'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="spb-no-items">
                            <?php esc_html_e('No items found matching your criteria.', 'smart-page-builder'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <!-- Pagination -->
    <?php if ($queue_data['total_pages'] > 1): ?>
        <div class="spb-pagination">
            <?php
            $pagination_args = [
                'base' => add_query_arg('paged', '%#%'),
                'format' => '',
                'current' => $current_page,
                'total' => $queue_data['total_pages'],
                'prev_text' => '&laquo; ' . __('Previous', 'smart-page-builder'),
                'next_text' => __('Next', 'smart-page-builder') . ' &raquo;'
            ];
            echo paginate_links($pagination_args);
            ?>
        </div>
    <?php endif; ?>
</div>

<!-- Content Preview Modal -->
<div id="spb-preview-modal" class="spb-modal" style="display: none;">
    <div class="spb-modal-content">
        <div class="spb-modal-header">
            <h2><?php esc_html_e('Content Preview', 'smart-page-builder'); ?></h2>
            <button type="button" class="spb-modal-close">&times;</button>
        </div>
        <div class="spb-modal-body">
            <div id="spb-preview-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
        <div class="spb-modal-footer">
            <button type="button" class="button button-secondary spb-modal-close">
                <?php esc_html_e('Close', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Approval/Rejection Modal -->
<div id="spb-action-modal" class="spb-modal" style="display: none;">
    <div class="spb-modal-content">
        <div class="spb-modal-header">
            <h2 id="spb-action-modal-title"><?php esc_html_e('Approve Content', 'smart-page-builder'); ?></h2>
            <button type="button" class="spb-modal-close">&times;</button>
        </div>
        <div class="spb-modal-body">
            <form id="spb-action-form">
                <input type="hidden" id="spb-action-approval-id" name="approval_id" />
                <input type="hidden" id="spb-action-type" name="action_type" />
                
                <div id="spb-rejection-reason" style="display: none;">
                    <label for="spb-rejection-reason-select">
                        <?php esc_html_e('Rejection Reason:', 'smart-page-builder'); ?>
                    </label>
                    <select id="spb-rejection-reason-select" name="reason">
                        <option value="quality"><?php esc_html_e('Quality Issues', 'smart-page-builder'); ?></option>
                        <option value="relevance"><?php esc_html_e('Not Relevant', 'smart-page-builder'); ?></option>
                        <option value="accuracy"><?php esc_html_e('Factual Inaccuracy', 'smart-page-builder'); ?></option>
                        <option value="inappropriate"><?php esc_html_e('Inappropriate Content', 'smart-page-builder'); ?></option>
                        <option value="other"><?php esc_html_e('Other', 'smart-page-builder'); ?></option>
                    </select>
                </div>
                
                <label for="spb-action-notes">
                    <?php esc_html_e('Notes (Optional):', 'smart-page-builder'); ?>
                </label>
                <textarea id="spb-action-notes" name="notes" rows="4" cols="50" 
                          placeholder="<?php esc_attr_e('Add any additional comments...', 'smart-page-builder'); ?>"></textarea>
            </form>
        </div>
        <div class="spb-modal-footer">
            <button type="button" class="button button-secondary spb-modal-close">
                <?php esc_html_e('Cancel', 'smart-page-builder'); ?>
            </button>
            <button type="button" class="button button-primary" id="spb-confirm-action">
                <?php esc_html_e('Confirm', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.spb-approval-interface {
    max-width: 100%;
}

.spb-approval-stats {
    margin: 20px 0;
}

.spb-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spb-stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.spb-stat-number {
    font-size: 2.5em;
    font-weight: bold;
    margin-bottom: 5px;
}

.spb-stat-card.pending .spb-stat-number { color: #f39c12; }
.spb-stat-card.approved .spb-stat-number { color: #27ae60; }
.spb-stat-card.auto-approved .spb-stat-number { color: #2ecc71; }
.spb-stat-card.rejected .spb-stat-number { color: #e74c3c; }
.spb-stat-card.overdue .spb-stat-number { color: #c0392b; }

.spb-approval-filters {
    margin: 20px 0;
    padding: 15px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.spb-quality-score {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spb-score-bar {
    width: 60px;
    height: 8px;
    background: #ddd;
    border-radius: 4px;
    overflow: hidden;
}

.spb-score-fill {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c 0%, #f39c12 50%, #27ae60 100%);
    transition: width 0.3s ease;
}

.spb-status, .spb-priority {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.spb-status-pending_review { background: #f39c12; color: white; }
.spb-status-under_review { background: #3498db; color: white; }
.spb-status-approved { background: #27ae60; color: white; }
.spb-status-rejected { background: #e74c3c; color: white; }
.spb-status-auto_approved { background: #2ecc71; color: white; }

.spb-priority-high { background: #e74c3c; color: white; }
.spb-priority-normal { background: #95a5a6; color: white; }
.spb-priority-low { background: #bdc3c7; color: #333; }

.spb-action-buttons {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.spb-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spb-modal-content {
    background: white;
    border-radius: 4px;
    max-width: 800px;
    width: 90%;
    max-height: 90%;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}

.spb-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spb-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.spb-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.spb-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spb-no-items {
    text-align: center;
    padding: 40px;
    color: #666;
    font-style: italic;
}

.spb-pagination {
    margin: 20px 0;
    text-align: center;
}

#spb-action-form label {
    display: block;
    margin: 15px 0 5px;
    font-weight: bold;
}

#spb-action-form select,
#spb-action-form textarea {
    width: 100%;
    margin-bottom: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Checkbox selection handling
    $('#cb-select-all').on('change', function() {
        $('input[name="approval_ids[]"]').prop('checked', this.checked);
        updateBulkButtons();
    });
    
    $('input[name="approval_ids[]"]').on('change', function() {
        updateBulkButtons();
        
        // Update select all checkbox
        var total = $('input[name="approval_ids[]"]').length;
        var checked = $('input[name="approval_ids[]"]:checked').length;
        $('#cb-select-all').prop('indeterminate', checked > 0 && checked < total);
        $('#cb-select-all').prop('checked', checked === total);
    });
    
    function updateBulkButtons() {
        var hasSelection = $('input[name="approval_ids[]"]:checked').length > 0;
        $('#bulk-approve-btn, #bulk-reject-btn').prop('disabled', !hasSelection);
    }
    
    // Individual approval/rejection
    $('.spb-approve-btn').on('click', function() {
        var approvalId = $(this).data('approval-id');
        showActionModal('approve', approvalId);
    });
    
    $('.spb-reject-btn').on('click', function() {
        var approvalId = $(this).data('approval-id');
        showActionModal('reject', approvalId);
    });
    
    // Bulk operations
    $('#bulk-approve-btn').on('click', function() {
        var selectedIds = getSelectedIds();
        if (selectedIds.length > 0) {
            showBulkActionModal('approve', selectedIds);
        }
    });
    
    $('#bulk-reject-btn').on('click', function() {
        var selectedIds = getSelectedIds();
        if (selectedIds.length > 0) {
            showBulkActionModal('reject', selectedIds);
        }
    });
    
    function getSelectedIds() {
        return $('input[name="approval_ids[]"]:checked').map(function() {
            return this.value;
        }).get();
    }
    
    function showActionModal(action, approvalId) {
        $('#spb-action-approval-id').val(approvalId);
        $('#spb-action-type').val(action);
        
        if (action === 'approve') {
            $('#spb-action-modal-title').text('Approve Content');
            $('#spb-rejection-reason').hide();
            $('#spb-confirm-action').text('Approve').removeClass('button-secondary').addClass('button-primary');
        } else {
            $('#spb-action-modal-title').text('Reject Content');
            $('#spb-rejection-reason').show();
            $('#spb-confirm-action').text('Reject').removeClass('button-primary').addClass('button-secondary');
        }
        
        $('#spb-action-modal').show();
    }
    
    function showBulkActionModal(action, approvalIds) {
        // Similar to showActionModal but for bulk operations
        // Implementation would handle multiple IDs
    }
    
    // Modal handling
    $('.spb-modal-close').on('click', function() {
        $(this).closest('.spb-modal').hide();
        resetActionForm();
    });
    
    function resetActionForm() {
        $('#spb-action-form')[0].reset();
        $('#spb-rejection-reason').hide();
    }
    
    // Confirm action
    $('#spb-confirm-action').on('click', function() {
        var formData = {
            action: 'spb_' + $('#spb-action-type').val() + '_content',
            approval_id: $('#spb-action-approval-id').val(),
            reason: $('#spb-rejection-reason-select').val(),
            notes: $('#spb-action-notes').val(),
            nonce: spbApproval.nonce
        };
        
        $.post(spbApproval.ajaxUrl, formData)
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            })
            .fail(function() {
                alert('An error occurred. Please try again.');
            });
    });
    
    // Content preview
    $('.spb-preview-content').on('click', function(e) {
        e.preventDefault();
        var approvalId = $(this).data('approval-id');
        
        // Load content preview via AJAX
        $.post(spbApproval.ajaxUrl, {
            action: 'spb_preview_content',
            approval_id: approvalId,
            nonce: spbApproval.nonce
        })
        .done(function(response) {
            if (response.success) {
                $('#spb-preview-content').html(response.data.content);
                $('#spb-preview-modal').show();
            } else {
                alert('Error loading preview: ' + response.data.message);
            }
        });
    });
});
</script>
