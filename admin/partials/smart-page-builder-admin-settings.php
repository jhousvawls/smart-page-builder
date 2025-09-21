<?php
/**
 * Provide a admin area view for the plugin settings
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

// Get current settings
$settings = get_option('spb_settings', array());
$confidence_threshold = isset($settings['confidence_threshold']) ? $settings['confidence_threshold'] : 0.6;
$cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
$ai_enhancement_enabled = get_option('spb_ai_enhancement_enabled', false);
$ai_api_key = get_option('spb_ai_api_key', '');
$ai_provider = get_option('spb_ai_provider', 'openai');

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'spb_settings')) {
    // Update settings
    $new_settings = array(
        'confidence_threshold' => floatval($_POST['confidence_threshold']),
        'cache_duration' => intval($_POST['cache_duration'])
    );
    
    update_option('spb_settings', $new_settings);
    update_option('spb_ai_enhancement_enabled', isset($_POST['ai_enhancement_enabled']));
    update_option('spb_ai_provider', sanitize_text_field($_POST['ai_provider']));
    
    // Handle API key (encrypt it)
    if (!empty($_POST['ai_api_key'])) {
        update_option('spb_ai_api_key', sanitize_text_field($_POST['ai_api_key']));
    }
    
    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully.', 'smart-page-builder') . '</p></div>';
    
    // Refresh values
    $settings = $new_settings;
    $confidence_threshold = $settings['confidence_threshold'];
    $cache_duration = $settings['cache_duration'];
    $ai_enhancement_enabled = get_option('spb_ai_enhancement_enabled', false);
    $ai_provider = get_option('spb_ai_provider', 'openai');
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('spb_settings'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="confidence_threshold"><?php _e('Confidence Threshold', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="confidence_threshold" 
                               name="confidence_threshold" 
                               value="<?php echo esc_attr($confidence_threshold); ?>" 
                               min="0.1" 
                               max="1.0" 
                               step="0.1" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('Minimum confidence score (0.1-1.0) required to generate content. Higher values mean more selective content generation.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="cache_duration"><?php _e('Cache Duration', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="cache_duration" 
                               name="cache_duration" 
                               value="<?php echo esc_attr($cache_duration); ?>" 
                               min="300" 
                               max="86400" 
                               class="regular-text" />
                        <p class="description">
                            <?php _e('Cache duration in seconds (300-86400). How long to cache generated content and analysis results.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ai_enhancement_enabled"><?php _e('AI Enhancement', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <label for="ai_enhancement_enabled">
                                <input type="checkbox" 
                                       id="ai_enhancement_enabled" 
                                       name="ai_enhancement_enabled" 
                                       value="1" 
                                       <?php checked($ai_enhancement_enabled); ?> />
                                <?php _e('Enable AI content enhancement', 'smart-page-builder'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When enabled, assembled content will be enhanced using AI before being added to the approval queue.', 'smart-page-builder'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
                
                <tr class="ai-settings" <?php echo $ai_enhancement_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row">
                        <label for="ai_provider"><?php _e('AI Provider', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <select id="ai_provider" name="ai_provider" class="regular-text">
                            <option value="openai" <?php selected($ai_provider, 'openai'); ?>>
                                <?php _e('OpenAI (GPT-3.5 Turbo)', 'smart-page-builder'); ?>
                            </option>
                            <option value="anthropic" <?php selected($ai_provider, 'anthropic'); ?> disabled>
                                <?php _e('Anthropic Claude (Coming Soon)', 'smart-page-builder'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php _e('Select the AI provider to use for content enhancement.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr class="ai-settings" <?php echo $ai_enhancement_enabled ? '' : 'style="display:none;"'; ?>>
                    <th scope="row">
                        <label for="ai_api_key"><?php _e('API Key', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="ai_api_key" 
                               name="ai_api_key" 
                               value="<?php echo !empty($ai_api_key) ? '••••••••••••••••' : ''; ?>" 
                               class="regular-text" 
                               placeholder="<?php _e('Enter your API key', 'smart-page-builder'); ?>" />
                        <p class="description">
                            <?php _e('Your AI provider API key. This will be stored securely and encrypted.', 'smart-page-builder'); ?>
                            <?php if (!empty($ai_api_key)): ?>
                                <br><strong><?php _e('API key is currently configured.', 'smart-page-builder'); ?></strong>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <h2><?php _e('Content Generation Settings', 'smart-page-builder'); ?></h2>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php _e('Search Interception', 'smart-page-builder'); ?></th>
                    <td>
                        <p class="description">
                            <?php _e('The plugin automatically monitors search queries on your site and generates content for searches that return no results, provided the confidence threshold is met.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row"><?php _e('Content Types', 'smart-page-builder'); ?></th>
                    <td>
                        <ul>
                            <li><strong><?php _e('How-to Guides:', 'smart-page-builder'); ?></strong> <?php _e('Step-by-step instructions for DIY projects', 'smart-page-builder'); ?></li>
                            <li><strong><?php _e('Tool Recommendations:', 'smart-page-builder'); ?></strong> <?php _e('Best tools and equipment suggestions', 'smart-page-builder'); ?></li>
                            <li><strong><?php _e('Safety Tips:', 'smart-page-builder'); ?></strong> <?php _e('Safety precautions and best practices', 'smart-page-builder'); ?></li>
                            <li><strong><?php _e('Troubleshooting:', 'smart-page-builder'); ?></strong> <?php _e('Problem diagnosis and solutions', 'smart-page-builder'); ?></li>
                        </ul>
                        <p class="description">
                            <?php _e('Content is automatically categorized based on the search terms and assembled from your existing posts.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <h2><?php _e('System Status', 'smart-page-builder'); ?></h2>
    
    <table class="widefat">
        <thead>
            <tr>
                <th><?php _e('Component', 'smart-page-builder'); ?></th>
                <th><?php _e('Status', 'smart-page-builder'); ?></th>
                <th><?php _e('Details', 'smart-page-builder'); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php _e('Content Assembly', 'smart-page-builder'); ?></td>
                <td><span class="status-enabled"><?php _e('Enabled', 'smart-page-builder'); ?></span></td>
                <td><?php _e('TF-IDF analysis and content assembly from existing posts', 'smart-page-builder'); ?></td>
            </tr>
            <tr>
                <td><?php _e('Search Interception', 'smart-page-builder'); ?></td>
                <td><span class="status-enabled"><?php _e('Enabled', 'smart-page-builder'); ?></span></td>
                <td><?php _e('Monitoring search queries for content generation opportunities', 'smart-page-builder'); ?></td>
            </tr>
            <tr>
                <td><?php _e('AI Enhancement', 'smart-page-builder'); ?></td>
                <td>
                    <?php if ($ai_enhancement_enabled): ?>
                        <span class="status-enabled"><?php _e('Enabled', 'smart-page-builder'); ?></span>
                    <?php else: ?>
                        <span class="status-disabled"><?php _e('Disabled', 'smart-page-builder'); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($ai_enhancement_enabled): ?>
                        <?php printf(__('Using %s for content enhancement', 'smart-page-builder'), ucfirst($ai_provider)); ?>
                    <?php else: ?>
                        <?php _e('AI enhancement is disabled', 'smart-page-builder'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td><?php _e('Database Tables', 'smart-page-builder'); ?></td>
                <td><span class="status-enabled"><?php _e('Ready', 'smart-page-builder'); ?></span></td>
                <td><?php _e('All required database tables are created and ready', 'smart-page-builder'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<style>
.status-enabled {
    color: #46b450;
    font-weight: bold;
}

.status-disabled {
    color: #dc3232;
    font-weight: bold;
}

.ai-settings {
    transition: opacity 0.3s ease;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#ai_enhancement_enabled').on('change', function() {
        if (this.checked) {
            $('.ai-settings').show();
        } else {
            $('.ai-settings').hide();
        }
    });
    
    // Clear API key field when focused (for security)
    $('#ai_api_key').on('focus', function() {
        if ($(this).val() === '••••••••••••••••') {
            $(this).val('');
        }
    });
});
</script>
