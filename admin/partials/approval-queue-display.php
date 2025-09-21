<?php
/**
 * Provide a admin area view for the approval queue
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/jhousvawls/smart-page-builder
 * @since      1.0.0
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin/partials
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle bulk actions
if (isset($_POST['action']) && isset($_POST['post_ids']) && wp_verify_nonce($_POST['_wpnonce'], 'spb_bulk_action')) {
    $post_ids = array_map('intval', $_POST['post_ids']);
    $approval_workflow = new Smart_Page_Builder_Approval_Workflow();
    
    if ($_POST['action'] === 'bulk_approve') {
        $results = $approval_workflow->bulk_approve_drafts($post_ids);
        echo '<div class="notice notice-success"><p>' . 
             sprintf(__('Approved %d drafts. %d failed.', 'smart-page-builder'), $results['approved'], $results['failed']) . 
             '</p></div>';
    } elseif ($_POST['action'] === 'bulk_reject') {
        $results = $approval_workflow->bulk_reject_drafts($post_ids);
        echo '<div class="notice notice-success"><p>' . 
             sprintf(__('Rejected %d drafts. %d failed.', 'smart-page-builder'), $results['rejected'], $results['failed']) . 
             '</p></div>';
    }
    
    // Refresh the drafts list
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
}

// Handle individual actions
if (isset($_GET['action']) && isset($_GET['post_id']) && wp_verify_nonce($_GET['_wpnonce'], 'spb_individual_action')) {
    $post_id = intval($_GET['post_id']);
    $approval_workflow = new Smart_Page_Builder_Approval_Workflow();
    
    if ($_GET['action'] === 'approve') {
        if ($approval_workflow->approve_draft($post_id)) {
            echo '<div class="notice notice-success"><p>' . __('Draft approved and published.', 'smart-page-builder') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to approve draft.', 'smart-page-builder') . '</p></div>';
        }
    } elseif ($_GET['action'] === 'reject') {
        if ($approval_workflow->reject_draft($post_id)) {
            echo '<div class="notice notice-success"><p>' . __('Draft rejected and moved to trash.', 'smart-page-builder') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Failed to reject draft.', 'smart-page-builder') . '</p></div>';
        }
    }
    
    // Refresh the drafts list
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
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="spb-approval-queue">
        <?php if (empty($pending_drafts)): ?>
            <div class="notice notice-info">
                <p><?php _e('No pending drafts found. Content will appear here when the system generates new drafts based on search queries.', 'smart-page-builder'); ?></p>
            </div>
        <?php else: ?>
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <form method="post" id="spb-bulk-form">
                        <?php wp_nonce_field('spb_bulk_action'); ?>
                        <select name="action" id="bulk-action-selector-top">
                            <option value="-1"><?php _e('Bulk Actions', 'smart-page-builder'); ?></option>
                            <option value="bulk_approve"><?php _e('Approve', 'smart-page-builder'); ?></option>
                            <option value="bulk_reject"><?php _e('Reject', 'smart-page-builder'); ?></option>
                        </select>
                        <input type="submit" id="doaction" class="button action" value="<?php _e('Apply', 'smart-page-builder'); ?>">
                    </form>
                </div>
                <div class="alignright">
                    <span class="displaying-num"><?php printf(__('%d items', 'smart-page-builder'), count($pending_drafts)); ?></span>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" class="manage-column column-title column-primary">
                            <?php _e('Title', 'smart-page-builder'); ?>
                        </th>
                        <th scope="col" class="manage-column column-search-term">
                            <?php _e('Search Term', 'smart-page-builder'); ?>
                        </th>
                        <th scope="col" class="manage-column column-confidence">
                            <?php _e('Confidence', 'smart-page-builder'); ?>
                        </th>
                        <th scope="col" class="manage-column column-content-type">
                            <?php _e('Type', 'smart-page-builder'); ?>
                        </th>
                        <th scope="col" class="manage-column column-generated">
                            <?php _e('Generated', 'smart-page-builder'); ?>
                        </th>
                        <th scope="col" class="manage-column column-actions">
                            <?php _e('Actions', 'smart-page-builder'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_drafts as $draft): 
                        $search_term = get_post_meta($draft->ID, '_spb_search_term', true);
                        $confidence = get_post_meta($draft->ID, '_spb_confidence', true);
                        $content_type = get_post_meta($draft->ID, '_spb_content_type', true);
                        $generated_date = get_post_meta($draft->ID, '_spb_generated_date', true);
                        $sources = maybe_unserialize(get_post_meta($draft->ID, '_spb_sources', true));
                        
                        $confidence_class = '';
                        if ($confidence >= 0.8) {
                            $confidence_class = 'high-confidence';
                        } elseif ($confidence >= 0.6) {
                            $confidence_class = 'medium-confidence';
                        } else {
                            $confidence_class = 'low-confidence';
                        }
                    ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($draft->ID); ?>" form="spb-bulk-form">
                            </th>
                            <td class="title column-title column-primary">
                                <strong>
                                    <a href="#" class="spb-preview-content" data-post-id="<?php echo esc_attr($draft->ID); ?>">
                                        <?php echo esc_html($draft->post_title); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="approve">
                                        <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'approve', 'post_id' => $draft->ID)), 'spb_individual_action'); ?>" class="spb-approve-link">
                                            <?php _e('Approve', 'smart-page-builder'); ?>
                                        </a> |
                                    </span>
                                    <span class="reject">
                                        <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'reject', 'post_id' => $draft->ID)), 'spb_individual_action'); ?>" class="spb-reject-link">
                                            <?php _e('Reject', 'smart-page-builder'); ?>
                                        </a> |
                                    </span>
                                    <span class="preview">
                                        <a href="#" class="spb-preview-content" data-post-id="<?php echo esc_attr($draft->ID); ?>">
                                            <?php _e('Preview', 'smart-page-builder'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="search-term column-search-term">
                                <code><?php echo esc_html($search_term); ?></code>
                            </td>
                            <td class="confidence column-confidence">
                                <span class="confidence-score <?php echo esc_attr($confidence_class); ?>">
                                    <?php echo number_format($confidence * 100, 1); ?>%
                                </span>
                            </td>
                            <td class="content-type column-content-type">
                                <span class="content-type-badge content-type-<?php echo esc_attr($content_type); ?>">
                                    <?php echo esc_html(ucwords(str_replace('_', ' ', $content_type))); ?>
                                </span>
                            </td>
                            <td class="generated column-generated">
                                <?php echo esc_html(human_time_diff(strtotime($generated_date), current_time('timestamp')) . ' ago'); ?>
                            </td>
                            <td class="actions column-actions">
                                <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'approve', 'post_id' => $draft->ID)), 'spb_individual_action'); ?>" 
                                   class="button button-primary button-small spb-approve-btn">
                                    <?php _e('Approve', 'smart-page-builder'); ?>
                                </a>
                                <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'reject', 'post_id' => $draft->ID)), 'spb_individual_action'); ?>" 
                                   class="button button-secondary button-small spb-reject-btn">
                                    <?php _e('Reject', 'smart-page-builder'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Content Preview Modal -->
<div id="spb-preview-modal" class="spb-modal" style="display: none;">
    <div class="spb-modal-content">
        <div class="spb-modal-header">
            <h2 id="spb-preview-title"><?php _e('Content Preview', 'smart-page-builder'); ?></h2>
            <span class="spb-modal-close">&times;</span>
        </div>
        <div class="spb-modal-body">
            <div id="spb-preview-content-area">
                <!-- Content will be loaded here via AJAX -->
            </div>
            <div class="spb-preview-meta">
                <h4><?php _e('Sources Used:', 'smart-page-builder'); ?></h4>
                <div id="spb-preview-sources">
                    <!-- Sources will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <div class="spb-modal-footer">
            <button type="button" class="button button-primary" id="spb-approve-from-preview">
                <?php _e('Approve & Publish', 'smart-page-builder'); ?>
            </button>
            <button type="button" class="button button-secondary" id="spb-reject-from-preview">
                <?php _e('Reject', 'smart-page-builder'); ?>
            </button>
            <button type="button" class="button" id="spb-close-preview">
                <?php _e('Close', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.spb-approval-queue .confidence-score {
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: bold;
    font-size: 11px;
}

.spb-approval-queue .high-confidence {
    background-color: #d4edda;
    color: #155724;
}

.spb-approval-queue .medium-confidence {
    background-color: #fff3cd;
    color: #856404;
}

.spb-approval-queue .low-confidence {
    background-color: #f8d7da;
    color: #721c24;
}

.content-type-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.content-type-how_to {
    background-color: #e3f2fd;
    color: #1565c0;
}

.content-type-tool_recommendation {
    background-color: #f3e5f5;
    color: #7b1fa2;
}

.content-type-safety_tips {
    background-color: #fff8e1;
    color: #f57f17;
}

.content-type-troubleshooting {
    background-color: #ffebee;
    color: #c62828;
}

.content-type-default {
    background-color: #f5f5f5;
    color: #616161;
}

.spb-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.spb-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 800px;
    border-radius: 4px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.spb-modal-header {
    padding: 15px 20px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.spb-modal-header h2 {
    margin: 0;
    font-size: 18px;
}

.spb-modal-close {
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.spb-modal-close:hover {
    color: #000;
}

.spb-modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.spb-modal-footer {
    padding: 15px 20px;
    background-color: #f1f1f1;
    border-top: 1px solid #ddd;
    text-align: right;
}

.spb-modal-footer .button {
    margin-left: 10px;
}

.spb-preview-meta {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.spb-preview-meta h4 {
    margin-bottom: 10px;
}

#spb-preview-sources ul {
    list-style: none;
    padding: 0;
}

#spb-preview-sources li {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

#spb-preview-sources li:last-child {
    border-bottom: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle select all checkbox
    $('#cb-select-all-1').on('change', function() {
        $('input[name="post_ids[]"]').prop('checked', this.checked);
    });
    
    // Handle individual checkboxes
    $('input[name="post_ids[]"]').on('change', function() {
        var allChecked = $('input[name="post_ids[]"]:checked').length === $('input[name="post_ids[]"]').length;
        $('#cb-select-all-1').prop('checked', allChecked);
    });
    
    // Handle content preview
    $('.spb-preview-content').on('click', function(e) {
        e.preventDefault();
        var postId = $(this).data('post-id');
        loadContentPreview(postId);
    });
    
    // Handle modal close
    $('.spb-modal-close, #spb-close-preview').on('click', function() {
        $('#spb-preview-modal').hide();
    });
    
    // Handle approve from preview
    $('#spb-approve-from-preview').on('click', function() {
        var postId = $(this).data('post-id');
        if (postId) {
            window.location.href = '<?php echo admin_url('admin.php?page=smart-page-builder-approval'); ?>&action=approve&post_id=' + postId + '&_wpnonce=<?php echo wp_create_nonce('spb_individual_action'); ?>';
        }
    });
    
    // Handle reject from preview
    $('#spb-reject-from-preview').on('click', function() {
        var postId = $(this).data('post-id');
        if (postId) {
            if (confirm('<?php _e('Are you sure you want to reject this content?', 'smart-page-builder'); ?>')) {
                window.location.href = '<?php echo admin_url('admin.php?page=smart-page-builder-approval'); ?>&action=reject&post_id=' + postId + '&_wpnonce=<?php echo wp_create_nonce('spb_individual_action'); ?>';
            }
        }
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target.id === 'spb-preview-modal') {
            $('#spb-preview-modal').hide();
        }
    });
    
    function loadContentPreview(postId) {
        $('#spb-preview-content-area').html('<p><?php _e('Loading...', 'smart-page-builder'); ?></p>');
        $('#spb-preview-sources').html('<p><?php _e('Loading...', 'smart-page-builder'); ?></p>');
        $('#spb-preview-modal').show();
        
        // Set post ID for action buttons
        $('#spb-approve-from-preview, #spb-reject-from-preview').data('post-id', postId);
        
        // Load content via AJAX
        $.post(ajaxurl, {
            action: 'spb_load_content_preview',
            post_id: postId,
            nonce: '<?php echo wp_create_nonce('spb_load_preview'); ?>'
        }, function(response) {
            if (response.success) {
                $('#spb-preview-content-area').html(response.data.content);
                $('#spb-preview-sources').html(response.data.sources);
                $('#spb-preview-title').text(response.data.title);
            } else {
                $('#spb-preview-content-area').html('<p><?php _e('Error loading content preview.', 'smart-page-builder'); ?></p>');
            }
        });
    }
});
</script>
