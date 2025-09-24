/**
 * Smart Page Builder Public JavaScript
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

(function($) {
    'use strict';

    /**
     * Smart Page Builder Public Class
     */
    class SmartPageBuilderPublic {
        constructor() {
            this.init();
        }

        /**
         * Initialize the public functionality
         */
        init() {
            this.bindEvents();
            this.initComponents();
            this.initSearchGeneration();
            
            // Initialize when DOM is ready
            $(document).ready(() => {
                this.onDOMReady();
            });
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Component interactions
            $(document).on('click', '.spb-component-button a', this.handleButtonClick.bind(this));
            $(document).on('click', '.spb-cta-button', this.handleCTAClick.bind(this));
            
            // Search form handling
            $(document).on('submit', 'form[role="search"]', this.handleSearchSubmit.bind(this));
            $(document).on('submit', '.search-form', this.handleSearchSubmit.bind(this));
            $(document).on('submit', '#searchform', this.handleSearchSubmit.bind(this));
            
            // Window events
            $(window).on('scroll', this.handleScroll.bind(this));
            $(window).on('resize', this.handleResize.bind(this));
        }

        /**
         * Initialize components
         */
        initComponents() {
            this.initLazyLoading();
            this.initAnimations();
            this.initAccessibility();
        }

        /**
         * Initialize search-triggered page generation
         */
        initSearchGeneration() {
            // Check if search generation is enabled
            if (!spb_public.personalization_enabled) {
                return;
            }

            // Monitor search queries
            this.monitorSearchQueries();
        }

        /**
         * Handle DOM ready
         */
        onDOMReady() {
            // Initialize any components that need DOM to be ready
            this.initProgressiveEnhancement();
            this.trackPageView();
        }

        /**
         * Handle button clicks
         */
        handleButtonClick(event) {
            const $button = $(event.currentTarget);
            const buttonData = {
                text: $button.text().trim(),
                href: $button.attr('href'),
                component: $button.closest('.spb-component').attr('class')
            };

            // Track button interaction
            this.trackInteraction('button_click', buttonData);

            // Add visual feedback
            this.addButtonFeedback($button);
        }

        /**
         * Handle CTA clicks
         */
        handleCTAClick(event) {
            const $cta = $(event.currentTarget);
            const ctaData = {
                text: $cta.text().trim(),
                href: $cta.attr('href'),
                context: $cta.closest('.spb-generated-cta, .spb-generated-hero').length > 0 ? 'generated' : 'manual'
            };

            // Track CTA interaction
            this.trackInteraction('cta_click', ctaData);

            // Add visual feedback
            this.addButtonFeedback($cta);
        }

        /**
         * Handle search form submission
         */
        handleSearchSubmit(event) {
            const $form = $(event.currentTarget);
            const $searchInput = $form.find('input[type="search"], input[name="s"]').first();
            const query = $searchInput.val().trim();

            // Check if search generation is enabled and query meets criteria
            if (this.shouldInterceptSearch(query)) {
                event.preventDefault();
                this.handleSearchGeneration(query, $form);
                return false;
            }

            // Track regular search
            this.trackInteraction('search_submit', { query: query });
        }

        /**
         * Check if search should be intercepted for generation
         */
        shouldInterceptSearch(query) {
            // Check if personalization is enabled
            if (!spb_public.personalization_enabled) {
                return false;
            }

            // Check query length
            if (query.length < 3 || query.length > 200) {
                return false;
            }

            // Check for excluded patterns
            const excludedPatterns = [
                /^[0-9]+$/,  // Only numbers
                /^[a-zA-Z]$/,  // Single letter
                /admin/i,
                /login/i,
                /wp-/i
            ];

            for (let pattern of excludedPatterns) {
                if (pattern.test(query)) {
                    return false;
                }
            }

            return true;
        }

        /**
         * Handle search-triggered page generation
         */
        handleSearchGeneration(query, $form) {
            // Show loading state
            this.showSearchLoading($form, query);

            // Track search generation attempt
            this.trackInteraction('search_generation_start', { query: query });

            // Make AJAX request for page generation
            $.ajax({
                url: spb_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_generate_search_page',
                    query: query,
                    nonce: spb_public.nonce
                },
                timeout: 30000,
                success: (response) => {
                    this.handleSearchGenerationSuccess(response, query);
                },
                error: (xhr, status, error) => {
                    this.handleSearchGenerationError(error, query, $form);
                }
            });
        }

        /**
         * Show search loading state
         */
        showSearchLoading($form, query) {
            const loadingHTML = `
                <div class="spb-search-loading" id="spb-search-loading">
                    <div class="spb-loading">
                        <div class="spb-loading-spinner"></div>
                        <div class="spb-loading-text">Generating personalized content for "${query}"...</div>
                        <div class="spb-loading-progress">
                            <div class="spb-loading-progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="spb-loading-steps">
                            <div class="spb-loading-step spb-active">Analyzing your query...</div>
                            <div class="spb-loading-step">Discovering relevant content...</div>
                            <div class="spb-loading-step">Generating personalized page...</div>
                            <div class="spb-loading-step">Finalizing content...</div>
                        </div>
                    </div>
                </div>
            `;

            // Insert loading screen
            $('body').append(loadingHTML);
            
            // Animate progress
            this.animateSearchProgress();
        }

        /**
         * Animate search progress
         */
        animateSearchProgress() {
            const $progressBar = $('.spb-loading-progress-bar');
            const $steps = $('.spb-loading-step');
            let currentStep = 0;
            let progress = 0;

            const progressInterval = setInterval(() => {
                progress += Math.random() * 15 + 5; // Random progress between 5-20%
                
                if (progress > 100) {
                    progress = 100;
                    clearInterval(progressInterval);
                }

                $progressBar.css('width', progress + '%');

                // Update steps
                const stepProgress = Math.floor((progress / 100) * $steps.length);
                if (stepProgress > currentStep && stepProgress < $steps.length) {
                    $steps.eq(currentStep).removeClass('spb-active').addClass('spb-completed');
                    $steps.eq(stepProgress).addClass('spb-active');
                    currentStep = stepProgress;
                }
            }, 800);
        }

        /**
         * Handle successful search generation
         */
        handleSearchGenerationSuccess(response, query) {
            if (response.success && response.data.redirect_url) {
                // Track success
                this.trackInteraction('search_generation_success', { 
                    query: query,
                    page_id: response.data.page_id 
                });

                // Redirect to generated page
                window.location.href = response.data.redirect_url;
            } else {
                this.handleSearchGenerationError(response.data.message || 'Generation failed', query);
            }
        }

        /**
         * Handle search generation error
         */
        handleSearchGenerationError(error, query, $form = null) {
            // Remove loading screen
            $('#spb-search-loading').remove();

            // Track error
            this.trackInteraction('search_generation_error', { 
                query: query,
                error: error 
            });

            // Show error message
            this.showErrorMessage('Unable to generate personalized content. Showing regular search results.');

            // Fallback to regular search
            if ($form) {
                $form.off('submit').submit();
            } else {
                window.location.href = `/?s=${encodeURIComponent(query)}`;
            }
        }

        /**
         * Show error message
         */
        showErrorMessage(message) {
            const $errorDiv = $('<div class="spb-error-message">')
                .text(message)
                .css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: '#dc3545',
                    color: 'white',
                    padding: '15px 20px',
                    borderRadius: '8px',
                    zIndex: 10000,
                    maxWidth: '400px'
                });

            $('body').append($errorDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                $errorDiv.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }

        /**
         * Handle scroll events
         */
        handleScroll() {
            const scrollTop = $(window).scrollTop();
            
            // Trigger scroll-based animations
            this.triggerScrollAnimations(scrollTop);
            
            // Track scroll depth
            this.trackScrollDepth(scrollTop);
        }

        /**
         * Handle resize events
         */
        handleResize() {
            // Debounce resize handling
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                this.handleResizeDebounced();
            }, 250);
        }

        /**
         * Handle debounced resize
         */
        handleResizeDebounced() {
            // Recalculate component positions
            this.recalculateComponents();
        }

        /**
         * Initialize lazy loading
         */
        initLazyLoading() {
            // Lazy load images in components
            $('.spb-component img[data-src]').each(function() {
                const $img = $(this);
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            observer.unobserve(img);
                        }
                    });
                });
                observer.observe(this);
            });
        }

        /**
         * Initialize animations
         */
        initAnimations() {
            // Fade in components on scroll
            $('.spb-component, .spb-generated-hero, .spb-generated-article, .spb-generated-cta').each(function() {
                const $element = $(this);
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            $element.addClass('spb-animate-in');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                observer.observe(this);
            });
        }

        /**
         * Initialize accessibility features
         */
        initAccessibility() {
            // Add ARIA labels to interactive elements
            $('.spb-component-button a, .spb-cta-button').each(function() {
                const $button = $(this);
                if (!$button.attr('aria-label') && $button.text().trim()) {
                    $button.attr('aria-label', $button.text().trim());
                }
            });

            // Add keyboard navigation
            $('.spb-component').attr('tabindex', '0');
        }

        /**
         * Initialize progressive enhancement
         */
        initProgressiveEnhancement() {
            // Add enhanced functionality for JavaScript-enabled browsers
            $('body').addClass('spb-js-enabled');

            // Enhance forms
            this.enhanceForms();
        }

        /**
         * Enhance forms
         */
        enhanceForms() {
            // Add real-time validation
            $('form input, form textarea').on('blur', function() {
                const $field = $(this);
                if ($field.val().trim() === '' && $field.prop('required')) {
                    $field.addClass('spb-field-error');
                } else {
                    $field.removeClass('spb-field-error');
                }
            });
        }

        /**
         * Add button feedback
         */
        addButtonFeedback($button) {
            $button.addClass('spb-clicked');
            setTimeout(() => {
                $button.removeClass('spb-clicked');
            }, 200);
        }

        /**
         * Trigger scroll animations
         */
        triggerScrollAnimations(scrollTop) {
            // Parallax effects for hero sections
            $('.spb-generated-hero').each(function() {
                const $hero = $(this);
                const offset = $hero.offset().top;
                const speed = 0.5;
                const yPos = -(scrollTop - offset) * speed;
                $hero.css('background-position', `center ${yPos}px`);
            });
        }

        /**
         * Track scroll depth
         */
        trackScrollDepth(scrollTop) {
            const documentHeight = $(document).height();
            const windowHeight = $(window).height();
            const scrollPercent = Math.round((scrollTop / (documentHeight - windowHeight)) * 100);

            // Track milestone percentages
            const milestones = [25, 50, 75, 90];
            milestones.forEach(milestone => {
                if (scrollPercent >= milestone && !this.scrollMilestones[milestone]) {
                    this.scrollMilestones[milestone] = true;
                    this.trackInteraction('scroll_depth', { percent: milestone });
                }
            });
        }

        /**
         * Recalculate component positions
         */
        recalculateComponents() {
            // Recalculate any position-dependent features
            $('.spb-component').each(function() {
                const $component = $(this);
                // Trigger any necessary recalculations
                $component.trigger('spb:recalculate');
            });
        }

        /**
         * Track page view
         */
        trackPageView() {
            this.trackInteraction('page_view', {
                url: window.location.href,
                title: document.title,
                referrer: document.referrer
            });
        }

        /**
         * Track interaction
         */
        trackInteraction(action, data = {}) {
            // Only track if personalization is enabled
            if (!spb_public.personalization_enabled) {
                return;
            }

            $.ajax({
                url: spb_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_track_interaction',
                    interaction_type: action,
                    interaction_data: data,
                    nonce: spb_public.nonce
                },
                timeout: 5000
            });
        }

        /**
         * Monitor search queries for analytics
         */
        monitorSearchQueries() {
            // Track search input focus and typing
            $('input[type="search"], input[name="s"]').on('focus', () => {
                this.trackInteraction('search_focus');
            }).on('input', this.debounce((event) => {
                const query = $(event.target).val().trim();
                if (query.length >= 3) {
                    this.trackInteraction('search_typing', { query_length: query.length });
                }
            }, 1000));
        }

        /**
         * Debounce utility function
         */
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    }

    // Initialize scroll milestones tracking
    SmartPageBuilderPublic.prototype.scrollMilestones = {};

    // Initialize when script loads
    new SmartPageBuilderPublic();

    // Add CSS for enhanced functionality
    const enhancedCSS = `
        <style>
        .spb-js-enabled .spb-component {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease;
        }
        
        .spb-js-enabled .spb-component.spb-animate-in {
            opacity: 1;
            transform: translateY(0);
        }
        
        .spb-clicked {
            transform: scale(0.95);
            transition: transform 0.1s ease;
        }
        
        .spb-field-error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        
        .spb-search-loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .spb-loading-steps {
            margin-top: 20px;
            text-align: left;
        }
        
        .spb-loading-step {
            padding: 5px 0;
            color: #666;
            opacity: 0.5;
            transition: all 0.3s ease;
        }
        
        .spb-loading-step.spb-active {
            opacity: 1;
            color: #0073aa;
            font-weight: 600;
        }
        
        .spb-loading-step.spb-completed {
            opacity: 0.8;
            color: #28a745;
        }
        
        .spb-loading-step.spb-completed::before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
        }
        </style>
    `;
    
    $('head').append(enhancedCSS);

})(jQuery);
