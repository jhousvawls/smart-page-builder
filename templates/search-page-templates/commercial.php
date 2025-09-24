<?php
/**
 * Commercial Template for Smart Page Builder
 *
 * Conversion-focused template for commercial/sales intent searches
 *
 * @package Smart_Page_Builder
 * @subpackage Templates
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="spb-commercial-template spb-conversion-focused">
    <!-- Hero Section with Strong CTA -->
    <section class="spb-hero-commercial">
        <div class="spb-hero-container">
            <div class="spb-hero-content">
                <h1 class="spb-hero-headline"><?php echo esc_html($content['hero']['headline'] ?? 'Transform Your Business Today'); ?></h1>
                <p class="spb-hero-subheadline"><?php echo esc_html($content['hero']['subheadline'] ?? 'Discover the solution that drives results'); ?></p>
                
                <div class="spb-hero-actions">
                    <a href="<?php echo esc_url($content['hero']['cta_primary']['url'] ?? '#contact'); ?>" class="spb-btn spb-btn-primary spb-btn-large">
                        <?php echo esc_html($content['hero']['cta_primary']['text'] ?? 'Get Started Now'); ?>
                    </a>
                    <a href="<?php echo esc_url($content['hero']['cta_secondary']['url'] ?? '#learn-more'); ?>" class="spb-btn spb-btn-secondary">
                        <?php echo esc_html($content['hero']['cta_secondary']['text'] ?? 'Learn More'); ?>
                    </a>
                </div>
                
                <!-- Trust indicators -->
                <div class="spb-trust-indicators">
                    <span class="spb-trust-item">✓ Trusted by 10,000+ businesses</span>
                    <span class="spb-trust-item">✓ 30-day money-back guarantee</span>
                    <span class="spb-trust-item">✓ 24/7 support</span>
                </div>
            </div>
            
            <div class="spb-hero-visual">
                <div class="spb-hero-image-placeholder">
                    <span class="spb-visual-hint"><?php echo esc_html($content['hero']['visual_suggestion'] ?? 'Product showcase image'); ?></span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Features/Benefits Section -->
    <section class="spb-features-section">
        <div class="spb-container">
            <h2 class="spb-section-title">Why Choose Our Solution?</h2>
            <div class="spb-features-grid">
                <?php if (!empty($content['article']['key_points'])): ?>
                    <?php foreach ($content['article']['key_points'] as $index => $point): ?>
                        <div class="spb-feature-item">
                            <div class="spb-feature-icon">
                                <span class="spb-icon-placeholder">🚀</span>
                            </div>
                            <h3 class="spb-feature-title">Feature <?php echo $index + 1; ?></h3>
                            <p class="spb-feature-description"><?php echo esc_html($point); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- Main Content -->
    <section class="spb-content-section">
        <div class="spb-container">
            <?php if (!empty($content['article']['content'])): ?>
                <div class="spb-content-wrapper">
                    <?php echo wp_kses_post($content['article']['content']); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Final CTA Section -->
    <section class="spb-final-cta">
        <div class="spb-container">
            <div class="spb-cta-content">
                <h2 class="spb-cta-headline"><?php echo esc_html($content['cta']['headline'] ?? 'Ready to Get Started?'); ?></h2>
                <p class="spb-cta-description"><?php echo esc_html($content['cta']['description'] ?? 'Join thousands of satisfied customers today'); ?></p>
                
                <div class="spb-cta-actions">
                    <?php if (!empty($content['cta']['primary_button'])): ?>
                        <a href="<?php echo esc_url($content['cta']['primary_button']['url'] ?? '#'); ?>" class="spb-btn spb-btn-cta-primary spb-btn-large">
                            <?php echo esc_html($content['cta']['primary_button']['text'] ?? 'Get Started'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($content['cta']['secondary_button'])): ?>
                        <a href="<?php echo esc_url($content['cta']['secondary_button']['url'] ?? '#'); ?>" class="spb-btn spb-btn-cta-secondary">
                            <?php echo esc_html($content['cta']['secondary_button']['text'] ?? 'Contact Sales'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Value propositions -->
                <?php if (!empty($content['cta']['value_propositions'])): ?>
                    <div class="spb-value-props">
                        <?php foreach ($content['cta']['value_propositions'] as $prop): ?>
                            <div class="spb-value-prop">
                                <span class="spb-value-prop-icon">✓</span>
                                <span class="spb-value-prop-text"><?php echo esc_html($prop); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>

<style>
/* Commercial Template Styles */
.spb-commercial-template {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.spb-hero-commercial {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: white;
    padding: 4rem 2rem;
    text-align: center;
}

.spb-hero-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.spb-hero-headline {
    font-size: 3rem;
    font-weight: bold;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.spb-hero-subheadline {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.spb-hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 2rem;
}

.spb-btn {
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.spb-btn-large {
    padding: 1.25rem 2.5rem;
    font-size: 1.125rem;
}

.spb-btn-primary {
    background-color: #f59e0b;
    color: white;
}

.spb-btn-primary:hover {
    background-color: #d97706;
}

.spb-btn-secondary {
    background-color: transparent;
    color: white;
    border: 2px solid white;
}

.spb-btn-secondary:hover {
    background-color: white;
    color: #2563eb;
}

.spb-trust-indicators {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
    font-size: 0.875rem;
    opacity: 0.9;
}

.spb-hero-image-placeholder {
    background-color: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    padding: 3rem;
    text-align: center;
    border: 2px dashed rgba(255, 255, 255, 0.3);
}

.spb-features-section {
    padding: 4rem 2rem;
    background-color: #f8fafc;
}

.spb-container {
    max-width: 1200px;
    margin: 0 auto;
}

.spb-section-title {
    text-align: center;
    font-size: 2.5rem;
    margin-bottom: 3rem;
    color: #1f2937;
}

.spb-features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.spb-feature-item {
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.spb-feature-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.spb-feature-title {
    font-size: 1.25rem;
    margin-bottom: 1rem;
    color: #2563eb;
}

.spb-content-section {
    padding: 4rem 2rem;
}

.spb-content-wrapper {
    max-width: 800px;
    margin: 0 auto;
    font-size: 1.125rem;
    line-height: 1.7;
}

.spb-final-cta {
    background-color: #2563eb;
    color: white;
    padding: 4rem 2rem;
    text-align: center;
}

.spb-cta-headline {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.spb-cta-description {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.spb-cta-actions {
    margin-bottom: 2rem;
}

.spb-btn-cta-primary {
    background-color: #f59e0b;
    color: white;
}

.spb-btn-cta-secondary {
    background-color: transparent;
    color: white;
    border: 2px solid white;
    margin-left: 1rem;
}

.spb-value-props {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.spb-value-prop {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.spb-value-prop-icon {
    color: #f59e0b;
    font-weight: bold;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .spb-hero-container {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .spb-hero-headline {
        font-size: 2rem;
    }
    
    .spb-hero-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .spb-trust-indicators {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .spb-features-grid {
        grid-template-columns: 1fr;
    }
    
    .spb-value-props {
        flex-direction: column;
        align-items: center;
    }
}
</style>
