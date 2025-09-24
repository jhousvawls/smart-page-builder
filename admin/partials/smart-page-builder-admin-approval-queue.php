<?php
/**
 * Admin Approval Queue Interface for Smart Page Builder
 *
 * Simplified approval queue interface focused on quick review
 * and bulk operations for AI-generated content.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get approval system instance
$approval_system = new SPB_Content_Approval_System();

// Handle filter parameters
$current_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'pending_review';
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

// Build filters for pending items only
$filters = ['status' => $current_status];

// Get approval queue data
$queue_data = $approval_system->get_approval_queue($filters, $current_page, 50);
$approval_stats = $approval_system->get_approval_statistics();
?>

<div class="wrap spb-approval-queue">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Approval Queue', 'smart-page-builder'); ?>
        <span class="title-count theme-count"><?php echo esc_html($approval_stats['pending']); ?></span>
    </h1>

    <!-- Quick Stats -->
    <div class="spb-quick-stats">
        <div class="spb-stat-item">
            <span class="spb-stat-number pending"><?php echo esc_html($approval_stats['pending']); ?></span>
            <span class="spb-stat-label"><?php esc_html_e('Pending', 'smart-page-builder'); ?></span>
        </div>
        <div class="spb-stat-item">
            <span class="spb-stat-number overdue"><?php echo esc_html($approval_stats['overdue']); ?></span>
            <span class="spb-stat-label"><?php esc_html_e('Overdue', 'smart-page-builder'); ?></span>
        </div>
        <div class="spb-stat-item">
            <span class="spb-stat-number approved"><?php echo esc_html($approval_stats['approved']); ?></span>
            <span class="spb-stat-label"><?php esc_html_e('Today', 'smart-page-builder'); ?></span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="spb-quick-actions">
        <button type="button" class="button button-primary" id="approve-all-high-quality" 
                data-threshold="0.8">
            <?php esc_html_e('Approve All High Quality (80%+)', 'smart-page-builder'); ?>
        </button>
        <button type="button" class="button button-secondary" id="bulk-review-selected" disabled>
            <?php esc_html_e('Review Selected', 'smart-page-builder'); ?>
        </button>
        <button type="button" class="button" id="refresh-queue">
            <?php esc_html_e('Refresh Queue', 'smart-page-builder'); ?>
        </button>
    </div>

    <!-- Status Filter -->
    <div class="spb-status-filter">
        <form method="get" action="">
            <input type="hidden" name="page" value="spb-approval-queue" />
            <select name="status" id="status-filter" onchange="this.form.submit()">
                <option value="pending_review" <?php selected($current_status, 'pending_review'); ?>>
                    <?php esc_html_e('Pending Review', 'smart-page-builder'); ?>
                </option>
                <option value="under_review" <?php selected($current_status, 'under_review'); ?>>
                    <?php esc_html_e('Under Review', 'smart-page-builder'); ?>
                </option>
                <option value="all" <?php selected($current_status, 'all'); ?>>
                    <?php esc_html_e('All Items', 'smart-page-builder'); ?>
                </option>
            </select>
        </form>
    </div>

    <!-- Approval Queue Cards -->
    <div class="spb-queue-container">
        <?php if (!empty($queue_data['items'])): ?>
            <form id="spb-queue-form" method="post">
                <?php wp_nonce_field('spb_bulk_approval', 'spb_bulk_nonce'); ?>
                
                <div class="spb-queue-grid">
                    <?php foreach ($queue_data['items'] as $item): ?>
                        <div class="spb-queue-card" data-approval-id="<?php echo esc_attr($item['id']); ?>" 
                             data-quality-score="<?php echo esc_attr($item['quality_score']); ?>">
                            
                            <!-- Card Header -->
                            <div class="spb-card-header">
                                <div class="spb-card-checkbox">
                                    <input type="checkbox" name="approval_ids[]" 
                                           value="<?php echo esc_attr($item['id']); ?>" 
                                           id="approval-<?php echo esc_attr($item['id']); ?>" />
                                </div>
                                <div class="spb-card-priority">
                                    <span class="spb-priority spb-priority-<?php echo esc_attr($item['priority']); ?>">
                                        <?php echo esc_html(ucfirst($item['priority'])); ?>
                                    </span>
                                </div>
                                <div class="spb-card-time">
                                    <?php echo esc_html(human_time_diff(strtotime($item['created_at']), current_time('timestamp'))); ?> ago
                                </div>
                            </div>

                            <!-- Search Query -->
                            <div class="spb-card-query">
                                <h3><?php echo esc_html($item['search_query']); ?></h3>
                            </div>

                            <!-- Quality Score -->
                            <div class="spb-card-quality">
                                <div class="spb-quality-indicator">
                                    <div class="spb-quality-bar">
                                        <div class="spb-quality-fill" 
                                             style="width: <?php echo esc_attr($item['quality_score'] * 100); ?>%"></div>
                                    </div>
                                    <span class="spb-quality-text">
                                        <?php echo esc_html(round($item['quality_score'] * 100)); ?>% Quality
                                    </span>
                                </div>
                            </div>

                            <!-- Content Preview -->
                            <div class="spb-card-preview">
                                <?php 
                                $content_data = json_decode($item['content_data'], true);
                                if (!empty($content_data['hero']['headline'])): 
                                ?>
                                    <div class="spb-preview-headline">
                                        <?php echo esc_html(wp_trim_words($content_data['hero']['headline'], 8)); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($content_data['article']['content'])): ?>
                                    <div class="spb-preview-content">
                                        <?php echo esc_html(wp_trim_words(strip_tags($content_data['article']['content']), 15)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Actions -->
                            <div class="spb-card-actions">
                                <button type="button" class="button button-small spb-preview-btn" 
                                        data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                    <?php esc_html_e('Preview', 'smart-page-builder'); ?>
                                </button>
                                
                                <?php if (in_array($item['status'], ['pending_review', 'under_review'])): ?>
                                    <button type="button" class="button button-primary button-small spb-quick-approve" 
                                            data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                        <?php esc_html_e('Approve', 'smart-page-builder'); ?>
                                    </button>
                                    <button type="button" class="button button-secondary button-small spb-quick-reject" 
                                            data-approval-id="<?php echo esc_attr($item['id']); ?>">
                                        <?php esc_html_e('Reject', 'smart-page-builder'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>

                            <!-- Status Indicator -->
                            <div class="spb-card-status">
                                <span class="spb-status spb-status-<?php echo esc_attr($item['status']); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $item['status']))); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php else: ?>
            <div class="spb-empty-queue">
                <div class="spb-empty-icon">✓</div>
                <h2><?php esc_html_e('All caught up!', 'smart-page-builder'); ?></h2>
                <p><?php esc_html_e('No items in the approval queue. Great work!', 'smart-page-builder'); ?></p>
            </div>
        <?php endif; ?>
    </div>

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

<!-- Quick Preview Modal -->
<div id="spb-quick-preview-modal" class="spb-modal" style="display: none;">
    <div class="spb-modal-content spb-quick-modal">
        <div class="spb-modal-header">
            <h2><?php esc_html_e('Quick Preview', 'smart-page-builder'); ?></h2>
            <button type="button" class="spb-modal-close">&times;</button>
        </div>
        <div class="spb-modal-body">
            <div id="spb-quick-preview-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
        <div class="spb-modal-footer">
            <button type="button" class="button button-secondary spb-modal-close">
                <?php esc_html_e('Close', 'smart-page-builder'); ?>
            </button>
            <button type="button" class="button button-primary" id="spb-approve-from-preview">
                <?php esc_html_e('Approve', 'smart-page-builder'); ?>
            </button>
            <button type="button" class="button button-secondary" id="spb-reject-from-preview">
                <?php esc_html_e('Reject', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.spb-approval-queue {
    max-width: 100%;
}

.spb-quick-stats {
    display: flex;
    gap: 30px;
    margin: 20px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 4px;
}

.spb-stat-item {
    text-align: center;
}

.spb-stat-number {
    display: block;
    font-size: 2em;
    font-weight: bold;
    margin-bottom: 5px;
}

.spb-stat-number.pending { color: #f39c12; }
.spb-stat-number.overdue { color: #e74c3c; }
.spb-stat-number.approved { color: #27ae60; }

.spb-stat-label {
    font-size: 0.9em;
    color: #666;
}

.spb-quick-actions {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.spb-quick-actions .button {
    margin-right: 10px;
}

.spb-status-filter {
    margin: 20px 0;
}

.spb-queue-container {
    margin: 20px 0;
}

.spb-queue-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.spb-queue-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: box-shadow 0.2s ease;
    position: relative;
}

.spb-queue-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.spb-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.spb-card-checkbox input {
    margin: 0;
}

.spb-card-time {
    font-size: 0.85em;
    color: #666;
}

.spb-card-query h3 {
    margin: 0 0 15px 0;
    font-size: 1.1em;
    line-height: 1.4;
    color: #333;
}

.spb-card-quality {
    margin: 15px 0;
}

.spb-quality-indicator {
    display: flex;
    align-items: center;
    gap: 10px;
}

.spb-quality-bar {
    flex: 1;
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
}

.spb-quality-fill {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c 0%, #f39c12 50%, #27ae60 100%);
    transition: width 0.3s ease;
}

.spb-quality-text {
    font-size: 0.85em;
    font-weight: bold;
    color: #666;
}

.spb-card-preview {
    margin: 15px 0;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
    border-left: 3px solid #007cba;
}

.spb-preview-headline {
    font-weight: bold;
    margin-bottom: 8px;
    color: #333;
}

.spb-preview-content {
    font-size: 0.9em;
    color: #666;
    line-height: 1.4;
}

.spb-card-actions {
    display: flex;
    gap: 8px;
    margin: 15px 0;
    flex-wrap: wrap;
}

.spb-card-actions .button {
    flex: 1;
    min-width: auto;
    text-align: center;
}

.spb-card-status {
    position: absolute;
    top: 10px;
    right: 10px;
}

.spb-priority {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.spb-priority-high { background: #e74c3c; color: white; }
.spb-priority-normal { background: #95a5a6; color: white; }
.spb-priority-low { background: #bdc3c7; color: #333; }

.spb-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.spb-status-pending_review { background: #f39c12; color: white; }
.spb-status-under_review { background: #3498db; color: white; }
.spb-status-approved { background: #27ae60; color: white; }
.spb-status-rejected { background: #e74c3c; color: white; }

.spb-empty-queue {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.spb-empty-icon {
    font-size: 4em;
    color: #27ae60;
    margin-bottom: 20px;
}

.spb-empty-queue h2 {
    color: #333;
    margin-bottom: 10px;
}

.spb-quick-modal .spb-modal-content {
    max-width: 600px;
}

.spb-pagination {
    margin: 30px 0;
    text-align: center;
}

@media (max-width: 768px) {
    .spb-queue-grid {
        grid-template-columns: 1fr;
    }
    
    .spb-quick-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .spb-quick-actions .button {
        display: block;
        margin: 5px 0;
        width: 100%;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var currentPreviewId = null;
    
    // Checkbox handling
    $('input[name="approval_ids[]"]').on('change', function() {
        updateBulkButtons();
    });
    
    function updateBulkButtons() {
        var hasSelection = $('input[name="approval_ids[]"]:checked').length > 0;
        $('#bulk-review-selected').prop('disabled', !hasSelection);
    }
    
    // Quick approve/reject
    $('.spb-quick-approve').on('click', function() {
        var approvalId = $(this).data('approval-id');
        quickApprove(approvalId);
    });
    
    $('.spb-quick-reject').on('click', function() {
        var approvalId = $(this).data('approval-id');
        quickReject(approvalId);
    });
    
    // Preview functionality
    $('.spb-preview-btn').on('click', function() {
        var approvalId = $(this).data('approval-id');
        currentPreviewId = approvalId;
        showQuickPreview(approvalId);
    });
    
    // Approve all high quality
    $('#approve-all-high-quality').on('click', function() {
        var threshold = $(this).data('threshold');
        var highQualityItems = $('.spb-queue-card').filter(function() {
            return parseFloat($(this).data('quality-score')) >= threshold;
        });
        
        if (highQualityItems.length > 0) {
            if (confirm('Approve ' + highQualityItems.length + ' high quality items?')) {
                var approvalIds = highQualityItems.map(function() {
                    return $(this).data('approval-id');
                }).get();
                
                bulkApprove(approvalIds);
            }
        } else {
            alert('No items meet the quality threshold.');
        }
    });
    
    // Refresh queue
    $('#refresh-queue').on('click', function() {
        location.reload();
    });
    
    // Modal actions
    $('#spb-approve-from-preview').on('click', function() {
        if (currentPreviewId) {
            quickApprove(currentPreviewId);
            $('#spb-quick-preview-modal').hide();
        }
    });
    
    $('#spb-reject-from-preview').on('click', function() {
        if (currentPreviewId) {
            quickReject(currentPreviewId);
            $('#spb-quick-preview-modal').hide();
        }
    });
    
    // Modal close
    $('.spb-modal-close').on('click', function() {
        $(this).closest('.spb-modal').hide();
        currentPreviewId = null;
    });
    
    function quickApprove(approvalId) {
        $.post(spbApproval.ajaxUrl, {
            action: 'spb_approve_content',
            approval_id: approvalId,
            notes: 'Quick approved from queue',
            nonce: spbApproval.nonce
        })
        .done(function(response) {
            if (response.success) {
                removeCardFromQueue(approvalId);
                showNotification('Content approved successfully', 'success');
            } else {
                showNotification('Error: ' + response.data.message, 'error');
            }
        })
        .fail(function() {
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
    
    function quickReject(approvalId) {
        $.post(spbApproval.ajaxUrl, {
            action: 'spb_reject_content',
            approval_id: approvalId,
            reason: 'quality',
            notes: 'Quick rejected from queue',
            nonce: spbApproval.nonce
        })
        .done(function(response) {
            if (response.success) {
                removeCardFromQueue(approvalId);
                showNotification('Content rejected', 'success');
            } else {
                showNotification('Error: ' + response.data.message, 'error');
            }
        })
        .fail(function() {
            showNotification('An error occurred. Please try again.', 'error');
        });
    }
    
    function bulkApprove(approvalIds) {
        $.post(spbApproval.ajaxUrl, {
            action: 'spb_bulk_approve',
            approval_ids: approvalIds,
            notes: 'Bulk approved - high quality',
            nonce: spbApproval.bulkNonce
        })
        .done(function(response) {
            if (response.success) {
                approvalIds.forEach(function(id) {
                    removeCardFromQueue(id);
                });
                showNotification(response.data.message, 'success');
            } else {
                showNotification('Error: ' + response.data.message, 'error');
            }
        });
    }
    
    function showQuickPreview(approvalId) {
        $.post(spbApproval.ajaxUrl, {
            action: 'spb_preview_content',
            approval_id: approvalId,
            nonce: spbApproval.nonce
        })
        .done(function(response) {
            if (response.success) {
                $('#spb-quick-preview-content').html(response.data.content);
                $('#spb-quick-preview-modal').show();
            } else {
                showNotification('Error loading preview: ' + response.data.message, 'error');
            }
        });
    }
    
    function removeCardFromQueue(approvalId) {
        $('.spb-queue-card[data-approval-id="' + approvalId + '"]').fadeOut(300, function() {
            $(this).remove();
            
            // Check if queue is empty
            if ($('.spb-queue-card').length === 0) {
                $('.spb-queue-grid').html(
                    '<div class="spb-empty-queue">' +
                    '<div class="spb-empty-icon">✓</div>' +
                    '<h2>All caught up!</h2>' +
                    '<p>No items in the approval queue. Great work!</p>' +
                    '</div>'
                );
            }
        });
    }
    
    function showNotification(message, type) {
        // Simple notification - could be enhanced with a proper notification system
        var className = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + className + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut();
        }, 3000);
    }
});
</script>
