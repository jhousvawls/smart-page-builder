<?php
/**
 * Admin Personalization Interface for Smart Page Builder
 *
 * Provides the personalization management interface for the admin area.
 *
 * @package Smart_Page_Builder
 * @subpackage Admin
 * @since 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get personalization data with safety checks
$interest_calculator = null;
$component_personalizer = null;

if (class_exists('Smart_Page_Builder_Interest_Vector_Calculator')) {
    $interest_calculator = new Smart_Page_Builder_Interest_Vector_Calculator();
}

if (class_exists('Smart_Page_Builder_Component_Personalizer')) {
    $component_personalizer = new Smart_Page_Builder_Component_Personalizer();
}

// Handle form submissions
if (isset($_POST['spb_save_personalization']) && wp_verify_nonce($_POST['spb_personalization_nonce'], 'spb_save_personalization')) {
    // Save personalization settings
    $settings = array(
        'enable_personalization' => isset($_POST['enable_personalization']) ? 1 : 0,
        'interest_decay_rate' => floatval($_POST['interest_decay_rate']),
        'min_confidence_threshold' => floatval($_POST['min_confidence_threshold']),
        'max_interests_tracked' => intval($_POST['max_interests_tracked']),
        'personalization_strength' => floatval($_POST['personalization_strength'])
    );
    
    update_option('spb_personalization_settings', $settings);
    echo '<div class="notice notice-success"><p>' . esc_html__('Personalization settings saved successfully.', 'smart-page-builder') . '</p></div>';
}

// Get current settings
$settings = get_option('spb_personalization_settings', array(
    'enable_personalization' => 1,
    'interest_decay_rate' => 0.1,
    'min_confidence_threshold' => 0.3,
    'max_interests_tracked' => 50,
    'personalization_strength' => 0.7
));

// Get analytics data with safety check
$analytics_data = array(
    'active_users' => 0,
    'total_vectors' => 0,
    'personalized_components' => 0,
    'avg_confidence' => 0
);

if ($interest_calculator && method_exists($interest_calculator, 'get_analytics_summary')) {
    $analytics_data = $interest_calculator->get_analytics_summary();
}
?>

<div class="wrap spb-personalization-interface">
    <h1><?php esc_html_e('Personalization Settings', 'smart-page-builder'); ?></h1>
    
    <!-- Personalization Overview -->
    <div class="spb-personalization-overview">
        <div class="spb-overview-cards">
            <div class="spb-overview-card">
                <h3><?php esc_html_e('Active Users', 'smart-page-builder'); ?></h3>
                <div class="spb-metric-value"><?php echo esc_html($analytics_data['active_users'] ?? 0); ?></div>
            </div>
            <div class="spb-overview-card">
                <h3><?php esc_html_e('Interest Vectors', 'smart-page-builder'); ?></h3>
                <div class="spb-metric-value"><?php echo esc_html($analytics_data['total_vectors'] ?? 0); ?></div>
            </div>
            <div class="spb-overview-card">
                <h3><?php esc_html_e('Personalized Components', 'smart-page-builder'); ?></h3>
                <div class="spb-metric-value"><?php echo esc_html($analytics_data['personalized_components'] ?? 0); ?></div>
            </div>
            <div class="spb-overview-card">
                <h3><?php esc_html_e('Avg. Confidence', 'smart-page-builder'); ?></h3>
                <div class="spb-metric-value"><?php echo esc_html(round(($analytics_data['avg_confidence'] ?? 0) * 100, 1)); ?>%</div>
            </div>
        </div>
    </div>

    <!-- Settings Form -->
    <form method="post" action="" class="spb-personalization-form">
        <?php wp_nonce_field('spb_save_personalization', 'spb_personalization_nonce'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="enable_personalization"><?php esc_html_e('Enable Personalization', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_personalization" name="enable_personalization" value="1" <?php checked($settings['enable_personalization'], 1); ?> />
                        <p class="description"><?php esc_html_e('Enable or disable the personalization engine.', 'smart-page-builder'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="interest_decay_rate"><?php esc_html_e('Interest Decay Rate', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="interest_decay_rate" name="interest_decay_rate" value="<?php echo esc_attr($settings['interest_decay_rate']); ?>" step="0.01" min="0" max="1" />
                        <p class="description"><?php esc_html_e('Rate at which user interests decay over time (0.0 - 1.0).', 'smart-page-builder'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="min_confidence_threshold"><?php esc_html_e('Minimum Confidence Threshold', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="min_confidence_threshold" name="min_confidence_threshold" value="<?php echo esc_attr($settings['min_confidence_threshold']); ?>" step="0.01" min="0" max="1" />
                        <p class="description"><?php esc_html_e('Minimum confidence level required for personalization (0.0 - 1.0).', 'smart-page-builder'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="max_interests_tracked"><?php esc_html_e('Maximum Interests Tracked', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="max_interests_tracked" name="max_interests_tracked" value="<?php echo esc_attr($settings['max_interests_tracked']); ?>" min="10" max="200" />
                        <p class="description"><?php esc_html_e('Maximum number of interest categories to track per user.', 'smart-page-builder'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="personalization_strength"><?php esc_html_e('Personalization Strength', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="personalization_strength" name="personalization_strength" value="<?php echo esc_attr($settings['personalization_strength']); ?>" step="0.01" min="0" max="1" />
                        <p class="description"><?php esc_html_e('How strongly personalization affects content selection (0.0 - 1.0).', 'smart-page-builder'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Personalization Settings', 'smart-page-builder'), 'primary', 'spb_save_personalization'); ?>
    </form>

    <!-- Interest Categories Management -->
    <div class="spb-interest-categories">
        <h2><?php esc_html_e('Interest Categories', 'smart-page-builder'); ?></h2>
        
        <div class="spb-categories-grid">
            <?php
            $categories = array();
            if ($interest_calculator && method_exists($interest_calculator, 'get_interest_categories')) {
                $categories = $interest_calculator->get_interest_categories();
            }
            if (!empty($categories)):
                foreach ($categories as $category => $data):
            ?>
                <div class="spb-category-card">
                    <h4><?php echo esc_html(ucwords(str_replace('_', ' ', $category))); ?></h4>
                    <div class="spb-category-stats">
                        <div class="spb-stat">
                            <span class="spb-stat-label"><?php esc_html_e('Users', 'smart-page-builder'); ?></span>
                            <span class="spb-stat-value"><?php echo esc_html($data['user_count'] ?? 0); ?></span>
                        </div>
                        <div class="spb-stat">
                            <span class="spb-stat-label"><?php esc_html_e('Avg. Score', 'smart-page-builder'); ?></span>
                            <span class="spb-stat-value"><?php echo esc_html(round(($data['avg_score'] ?? 0) * 100, 1)); ?>%</span>
                        </div>
                    </div>
                    <div class="spb-category-actions">
                        <button type="button" class="button button-small spb-view-category" data-category="<?php echo esc_attr($category); ?>">
                            <?php esc_html_e('View Details', 'smart-page-builder'); ?>
                        </button>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <p><?php esc_html_e('No interest categories found. Categories will appear as users interact with your content.', 'smart-page-builder'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Interest Vectors -->
    <div class="spb-user-vectors">
        <h2><?php esc_html_e('Recent User Interest Vectors', 'smart-page-builder'); ?></h2>
        
        <div class="spb-vectors-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User ID', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Top Interests', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Confidence', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Last Updated', 'smart-page-builder'); ?></th>
                        <th><?php esc_html_e('Actions', 'smart-page-builder'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_vectors = array();
                    if ($interest_calculator && method_exists($interest_calculator, 'get_recent_vectors')) {
                        $recent_vectors = $interest_calculator->get_recent_vectors(10);
                    }
                    if (!empty($recent_vectors)):
                        foreach ($recent_vectors as $vector):
                    ?>
                        <tr>
                            <td><?php echo esc_html($vector['user_id']); ?></td>
                            <td>
                                <?php
                                $top_interests = array_slice($vector['interests'], 0, 3);
                                foreach ($top_interests as $interest => $score):
                                ?>
                                    <span class="spb-interest-tag">
                                        <?php echo esc_html(ucwords(str_replace('_', ' ', $interest))); ?>
                                        <span class="spb-interest-score"><?php echo esc_html(round($score * 100, 1)); ?>%</span>
                                    </span>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <div class="spb-confidence-bar">
                                    <div class="spb-confidence-fill" style="width: <?php echo esc_attr($vector['confidence'] * 100); ?>%"></div>
                                </div>
                                <span class="spb-confidence-text"><?php echo esc_html(round($vector['confidence'] * 100, 1)); ?>%</span>
                            </td>
                            <td><?php echo esc_html(human_time_diff(strtotime($vector['updated_at']), current_time('timestamp'))); ?> ago</td>
                            <td>
                                <button type="button" class="button button-small spb-view-vector" data-user-id="<?php echo esc_attr($vector['user_id']); ?>">
                                    <?php esc_html_e('View', 'smart-page-builder'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php
                        endforeach;
                    else:
                    ?>
                        <tr>
                            <td colspan="5"><?php esc_html_e('No user interest vectors found.', 'smart-page-builder'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Vector Details Modal -->
<div id="spb-vector-modal" class="spb-modal" style="display: none;">
    <div class="spb-modal-content">
        <div class="spb-modal-header">
            <h2><?php esc_html_e('User Interest Vector Details', 'smart-page-builder'); ?></h2>
            <button type="button" class="spb-modal-close">&times;</button>
        </div>
        <div class="spb-modal-body">
            <div id="spb-vector-details">
                <!-- Vector details will be loaded here -->
            </div>
        </div>
        <div class="spb-modal-footer">
            <button type="button" class="button button-secondary spb-modal-close">
                <?php esc_html_e('Close', 'smart-page-builder'); ?>
            </button>
        </div>
    </div>
</div>

<style>
.spb-personalization-interface {
    max-width: 100%;
}

.spb-personalization-overview {
    margin: 20px 0;
}

.spb-overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.spb-overview-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.spb-overview-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spb-metric-value {
    font-size: 2.5em;
    font-weight: bold;
    color: #2271b1;
}

.spb-personalization-form {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin: 20px 0;
}

.spb-interest-categories {
    margin: 30px 0;
}

.spb-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.spb-category-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.spb-category-card h4 {
    margin: 0 0 15px 0;
    color: #333;
}

.spb-category-stats {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.spb-stat {
    text-align: center;
}

.spb-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spb-stat-value {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #2271b1;
}

.spb-category-actions {
    text-align: center;
}

.spb-user-vectors {
    margin: 30px 0;
}

.spb-vectors-table-container {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-top: 20px;
}

.spb-interest-tag {
    display: inline-block;
    background: #f0f0f1;
    border-radius: 12px;
    padding: 4px 8px;
    margin: 2px;
    font-size: 12px;
}

.spb-interest-score {
    background: #2271b1;
    color: white;
    border-radius: 8px;
    padding: 2px 6px;
    margin-left: 4px;
}

.spb-confidence-bar {
    width: 60px;
    height: 8px;
    background: #ddd;
    border-radius: 4px;
    overflow: hidden;
    display: inline-block;
    vertical-align: middle;
    margin-right: 8px;
}

.spb-confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c 0%, #f39c12 50%, #27ae60 100%);
    transition: width 0.3s ease;
}

.spb-confidence-text {
    font-size: 12px;
    color: #666;
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

@media (max-width: 768px) {
    .spb-overview-cards {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .spb-categories-grid {
        grid-template-columns: 1fr;
    }
    
    .spb-category-stats {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // View category details
    $('.spb-view-category').on('click', function() {
        var category = $(this).data('category');
        // Implementation for viewing category details
        alert('Category details for: ' + category);
    });
    
    // View user vector details
    $('.spb-view-vector').on('click', function() {
        var userId = $(this).data('user-id');
        
        // Load vector details via AJAX
        $.post(ajaxurl, {
            action: 'spb_get_user_vector',
            user_id: userId,
            nonce: spbPersonalization.nonce
        })
        .done(function(response) {
            if (response.success) {
                $('#spb-vector-details').html(response.data.html);
                $('#spb-vector-modal').show();
            } else {
                alert('Error loading vector details: ' + response.data.message);
            }
        });
    });
    
    // Modal handling
    $('.spb-modal-close').on('click', function() {
        $(this).closest('.spb-modal').hide();
    });
    
    // Click outside modal to close
    $('.spb-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
</script>
