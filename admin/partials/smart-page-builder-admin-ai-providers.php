<?php
/**
 * AI Providers Configuration Admin Tab
 *
 * @package Smart_Page_Builder
 * @since   3.4.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Load AI providers
require_once SPB_PLUGIN_DIR . 'includes/ai-providers/abstract-ai-provider.php';
require_once SPB_PLUGIN_DIR . 'includes/ai-providers/class-openai-provider.php';

// Initialize OpenAI provider
$openai_provider = new SPB_OpenAI_Provider();

// Get current settings
$openai_settings = [
    'api_key' => get_option('spb_openai_api_key', ''),
    'default_model' => get_option('spb_openai_default_model', 'gpt-3.5-turbo'),
    'search_model' => get_option('spb_openai_search_model', 'gpt-4'),
    'temperature' => get_option('spb_openai_temperature', 0.7),
    'max_tokens' => get_option('spb_openai_max_tokens', 2000),
    'enabled' => get_option('spb_openai_enabled', false)
];

// Test connection if API key is provided
$connection_status = false;
$connection_error = '';
if (!empty($openai_settings['api_key'])) {
    try {
        $connection_status = $openai_provider->test_connection();
    } catch (Exception $e) {
        $connection_error = $e->getMessage();
    }
}

// Get usage statistics
$usage_stats = $openai_provider->get_usage_stats();
?>

<div class="spb-admin-section">
    <h2><?php _e('AI Content Generation', 'smart-page-builder'); ?></h2>
    
    <div class="spb-ai-providers-overview">
        <p><?php _e('Configure AI providers to generate rich, engaging content for your search pages. AI-generated content will replace the basic fallback content with comprehensive, professional pages.', 'smart-page-builder'); ?></p>
    </div>

    <!-- OpenAI Configuration -->
    <div class="spb-provider-section">
        <h3>
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('OpenAI (ChatGPT)', 'smart-page-builder'); ?>
        </h3>
        
        <div class="spb-provider-status">
            <?php if ($connection_status): ?>
                <div class="notice notice-success inline">
                    <p><span class="dashicons dashicons-yes-alt"></span> 
                       <?php _e('Connected to OpenAI API', 'smart-page-builder'); ?></p>
                </div>
            <?php elseif (!empty($openai_settings['api_key'])): ?>
                <div class="notice notice-error inline">
                    <p><span class="dashicons dashicons-warning"></span> 
                       <?php _e('Connection failed:', 'smart-page-builder'); ?> 
                       <?php echo esc_html($connection_error); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-info inline">
                    <p><span class="dashicons dashicons-info"></span> 
                       <?php _e('Configure your OpenAI API key below to enable AI content generation', 'smart-page-builder'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <form method="post" action="options.php" class="spb-provider-form">
            <?php settings_fields('spb_openai_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="spb_openai_enabled"><?php _e('Enable OpenAI', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="spb_openai_enabled" 
                                   name="spb_openai_enabled" 
                                   value="1" 
                                   <?php checked($openai_settings['enabled']); ?> />
                            <?php _e('Use OpenAI for AI content generation', 'smart-page-builder'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, search pages will use OpenAI to generate rich, comprehensive content instead of basic fallback content.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="spb_openai_api_key"><?php _e('API Key', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="password" 
                               id="spb_openai_api_key" 
                               name="spb_openai_api_key" 
                               value="<?php echo esc_attr($openai_settings['api_key']); ?>" 
                               class="regular-text" 
                               placeholder="sk-..." />
                        <button type="button" class="button" id="spb-toggle-api-key">
                            <?php _e('Show/Hide', 'smart-page-builder'); ?>
                        </button>
                        <p class="description">
                            <?php _e('Your OpenAI API key. Get one from', 'smart-page-builder'); ?> 
                            <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="spb_openai_default_model"><?php _e('Default Model', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <select id="spb_openai_default_model" name="spb_openai_default_model">
                            <?php foreach ($openai_provider->get_available_models() as $model_id => $model_info): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                        <?php selected($openai_settings['default_model'], $model_id); ?>>
                                    <?php echo esc_html($model_info['name']); ?> 
                                    - $<?php echo number_format($model_info['cost_per_1k_tokens'], 3); ?>/1K tokens
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Default model for general content generation. GPT-4 provides higher quality but costs more.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="spb_openai_search_model"><?php _e('Search Page Model', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <select id="spb_openai_search_model" name="spb_openai_search_model">
                            <?php foreach ($openai_provider->get_available_models() as $model_id => $model_info): ?>
                                <option value="<?php echo esc_attr($model_id); ?>" 
                                        <?php selected($openai_settings['search_model'], $model_id); ?>>
                                    <?php echo esc_html($model_info['name']); ?> 
                                    - $<?php echo number_format($model_info['cost_per_1k_tokens'], 3); ?>/1K tokens
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php _e('Model specifically for search page generation. Recommended: GPT-4 for best quality.', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="spb_openai_temperature"><?php _e('Creativity Level', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="range" 
                               id="spb_openai_temperature" 
                               name="spb_openai_temperature" 
                               min="0" 
                               max="1" 
                               step="0.1" 
                               value="<?php echo esc_attr($openai_settings['temperature']); ?>" />
                        <span id="temperature-value"><?php echo esc_html($openai_settings['temperature']); ?></span>
                        <p class="description">
                            <?php _e('0 = More focused and deterministic, 1 = More creative and varied. Recommended: 0.7', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="spb_openai_max_tokens"><?php _e('Max Response Length', 'smart-page-builder'); ?></label>
                    </th>
                    <td>
                        <input type="number" 
                               id="spb_openai_max_tokens" 
                               name="spb_openai_max_tokens" 
                               value="<?php echo esc_attr($openai_settings['max_tokens']); ?>" 
                               min="100" 
                               max="4000" 
                               step="100" />
                        <p class="description">
                            <?php _e('Maximum tokens for AI responses. Higher = longer content but higher cost. Recommended: 2000-3000', 'smart-page-builder'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save OpenAI Settings', 'smart-page-builder')); ?>
        </form>
        
        <?php if ($connection_status): ?>
        <div class="spb-provider-actions">
            <h4><?php _e('Actions', 'smart-page-builder'); ?></h4>
            
            <p>
                <button type="button" class="button" id="spb-test-openai-connection">
                    <?php _e('Test Connection', 'smart-page-builder'); ?>
                </button>
                
                <button type="button" class="button" id="spb-generate-test-content">
                    <?php _e('Generate Test Content', 'smart-page-builder'); ?>
                </button>
                
                <button type="button" class="button" id="spb-view-usage-stats">
                    <?php _e('View Usage Statistics', 'smart-page-builder'); ?>
                </button>
            </p>
        </div>
        
        <!-- Usage Statistics -->
        <div class="spb-usage-stats">
            <h4><?php _e('Usage Statistics', 'smart-page-builder'); ?></h4>
            
            <div class="spb-stats-grid">
                <div class="spb-stat-item">
                    <div class="spb-stat-number"><?php echo number_format($usage_stats['total_requests']); ?></div>
                    <div class="spb-stat-label"><?php _e('Total Requests', 'smart-page-builder'); ?></div>
                </div>
                
                <div class="spb-stat-item">
                    <div class="spb-stat-number"><?php echo number_format($usage_stats['total_tokens']); ?></div>
                    <div class="spb-stat-label"><?php _e('Total Tokens', 'smart-page-builder'); ?></div>
                </div>
                
                <div class="spb-stat-item">
                    <div class="spb-stat-number">
                        <?php echo $usage_stats['last_request'] ? human_time_diff(strtotime($usage_stats['last_request'])) . ' ago' : 'Never'; ?>
                    </div>
                    <div class="spb-stat-label"><?php _e('Last Request', 'smart-page-builder'); ?></div>
                </div>
                
                <div class="spb-stat-item">
                    <div class="spb-stat-number">
                        <?php 
                        $current_month = date('Y-m');
                        $monthly_requests = $usage_stats['monthly_usage'][$current_month]['requests'] ?? 0;
                        echo number_format($monthly_requests);
                        ?>
                    </div>
                    <div class="spb-stat-label"><?php _e('This Month', 'smart-page-builder'); ?></div>
                </div>
                
                <div class="spb-stat-item">
                    <div class="spb-stat-number">
                        <?php 
                        $monthly_cost = $openai_provider->get_monthly_cost();
                        echo '$' . number_format($monthly_cost['total_cost'], 3);
                        ?>
                    </div>
                    <div class="spb-stat-label"><?php _e('Monthly Cost', 'smart-page-builder'); ?></div>
                </div>
                
                <div class="spb-stat-item">
                    <div class="spb-stat-number">
                        <?php 
                        $avg_cost = $openai_provider->get_average_cost_per_request();
                        echo '$' . number_format($avg_cost, 4);
                        ?>
                    </div>
                    <div class="spb-stat-label"><?php _e('Avg Cost/Request', 'smart-page-builder'); ?></div>
                </div>
            </div>
            
            <?php if (!empty($monthly_cost['model_breakdown'])): ?>
            <div class="spb-cost-breakdown">
                <h5><?php _e('Monthly Cost Breakdown', 'smart-page-builder'); ?></h5>
                <div class="spb-model-costs">
                    <?php foreach ($monthly_cost['model_breakdown'] as $model => $breakdown): ?>
                        <div class="spb-model-cost-item">
                            <span class="model-name"><?php echo esc_html($openai_provider->get_available_models()[$model]['name'] ?? $model); ?></span>
                            <span class="model-usage"><?php echo number_format($breakdown['tokens']); ?> tokens</span>
                            <span class="model-cost">$<?php echo number_format($breakdown['cost'], 4); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Test Content Generation -->
    <div id="spb-test-content-modal" class="spb-modal" style="display: none;">
        <div class="spb-modal-content">
            <span class="spb-modal-close">&times;</span>
            <h3><?php _e('Test Content Generation', 'smart-page-builder'); ?></h3>
            
            <div class="spb-test-form">
                <label for="test-prompt"><?php _e('Test Prompt:', 'smart-page-builder'); ?></label>
                <textarea id="test-prompt" rows="4" cols="50" placeholder="Enter a test prompt like 'Write about bathroom remodeling tips'"></textarea>
                
                <button type="button" class="button button-primary" id="spb-run-test">
                    <?php _e('Generate Content', 'smart-page-builder'); ?>
                </button>
            </div>
            
            <div id="spb-test-results" style="display: none;">
                <h4><?php _e('Generated Content:', 'smart-page-builder'); ?></h4>
                <div id="spb-test-content"></div>
                
                <h4><?php _e('Metadata:', 'smart-page-builder'); ?></h4>
                <div id="spb-test-metadata"></div>
            </div>
        </div>
    </div>
</div>

<style>
.spb-provider-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.spb-provider-section h3 {
    margin-top: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.spb-provider-status {
    margin-bottom: 20px;
}

.spb-provider-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.spb-usage-stats {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.spb-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    margin-top: 15px;
}

.spb-stat-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.spb-stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.spb-stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.spb-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.spb-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 600px;
    position: relative;
}

.spb-modal-close {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 24px;
    cursor: pointer;
}

.spb-test-form {
    margin-bottom: 20px;
}

.spb-test-form textarea {
    width: 100%;
    margin: 10px 0;
}

#spb-test-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    white-space: pre-wrap;
    max-height: 300px;
    overflow-y: auto;
}

#spb-test-metadata {
    background: #f0f0f0;
    padding: 10px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 12px;
}

.spb-cost-breakdown {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.spb-cost-breakdown h5 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 14px;
}

.spb-model-costs {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.spb-model-cost-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 13px;
}

.spb-model-cost-item .model-name {
    font-weight: 600;
    color: #333;
    flex: 1;
}

.spb-model-cost-item .model-usage {
    color: #666;
    margin: 0 10px;
}

.spb-model-cost-item .model-cost {
    font-weight: 600;
    color: #0073aa;
    min-width: 60px;
    text-align: right;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle API key visibility
    $('#spb-toggle-api-key').on('click', function() {
        var input = $('#spb_openai_api_key');
        input.attr('type', input.attr('type') === 'password' ? 'text' : 'password');
    });
    
    // Update temperature display
    $('#spb_openai_temperature').on('input', function() {
        $('#temperature-value').text($(this).val());
    });
    
    // Test connection
    $('#spb-test-openai-connection').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Testing...', 'smart-page-builder'); ?>');
        
        $.post(ajaxurl, {
            action: 'spb_test_openai_connection',
            nonce: '<?php echo wp_create_nonce('spb_openai_test'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('Connection successful!', 'smart-page-builder'); ?>');
            } else {
                alert('<?php _e('Connection failed:', 'smart-page-builder'); ?> ' + response.data);
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Test Connection', 'smart-page-builder'); ?>');
        });
    });
    
    // Generate test content
    $('#spb-generate-test-content').on('click', function() {
        $('#spb-test-content-modal').show();
    });
    
    // Close modal
    $('.spb-modal-close, .spb-modal').on('click', function(e) {
        if (e.target === this) {
            $('.spb-modal').hide();
        }
    });
    
    // Run test generation
    $('#spb-run-test').on('click', function() {
        var prompt = $('#test-prompt').val();
        if (!prompt) {
            alert('<?php _e('Please enter a test prompt', 'smart-page-builder'); ?>');
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Generating...', 'smart-page-builder'); ?>');
        
        $.post(ajaxurl, {
            action: 'spb_test_openai_generation',
            prompt: prompt,
            nonce: '<?php echo wp_create_nonce('spb_openai_test'); ?>'
        }, function(response) {
            if (response.success) {
                $('#spb-test-content').text(response.data.content);
                $('#spb-test-metadata').text(JSON.stringify(response.data.metadata, null, 2));
                $('#spb-test-results').show();
            } else {
                alert('<?php _e('Generation failed:', 'smart-page-builder'); ?> ' + response.data);
            }
        }).always(function() {
            button.prop('disabled', false).text('<?php _e('Generate Content', 'smart-page-builder'); ?>');
        });
    });
});
</script>
