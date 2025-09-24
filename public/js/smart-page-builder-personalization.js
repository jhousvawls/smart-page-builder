/**
 * Smart Page Builder Personalization JavaScript
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

(function($) {
    'use strict';

    /**
     * Smart Page Builder Personalization Class
     */
    class SmartPageBuilderPersonalization {
        constructor() {
            this.sessionId = spb_personalization.session_id;
            this.trackingEnabled = spb_personalization.tracking_enabled;
            this.userInterests = {};
            this.recommendations = [];
            this.init();
        }

        /**
         * Initialize personalization functionality
         */
        init() {
            if (!this.trackingEnabled) {
                return;
            }

            this.bindEvents();
            this.initPersonalizationUI();
            this.loadUserProfile();
            
            // Initialize when DOM is ready
            $(document).ready(() => {
                this.onDOMReady();
            });
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            // Content interaction tracking
            $(document).on('click', '.spb-personalized-content', this.trackContentInteraction.bind(this));
            $(document).on('mouseenter', '.spb-interest-based', this.trackContentHover.bind(this));
            
            // Recommendation interactions
            $(document).on('click', '.spb-recommendation-item', this.trackRecommendationClick.bind(this));
            
            // Interest tag interactions
            $(document).on('click', '.spb-interest-tag', this.handleInterestTagClick.bind(this));
            
            // Personalization controls
            $(document).on('click', '.spb-personalization-toggle', this.togglePersonalizationPanel.bind(this));
            $(document).on('click', '.spb-privacy-button', this.handlePrivacyAction.bind(this));
            
            // Page visibility changes
            $(document).on('visibilitychange', this.handleVisibilityChange.bind(this));
            
            // Time-based tracking
            this.startTimeTracking();
        }

        /**
         * Initialize personalization UI
         */
        initPersonalizationUI() {
            this.createPersonalizationToggle();
            this.createPersonalizationPanel();
            this.showPrivacyNoticeIfNeeded();
        }

        /**
         * Handle DOM ready
         */
        onDOMReady() {
            this.personalizeContent();
            this.loadRecommendations();
            this.initInterestVisualization();
            this.trackPageEngagement();
        }

        /**
         * Load user profile and interests
         */
        loadUserProfile() {
            $.ajax({
                url: spb_personalization.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_user_profile',
                    session_id: this.sessionId,
                    nonce: spb_personalization.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.userInterests = response.data.interests || {};
                        this.updateInterestVisualization();
                    }
                }
            });
        }

        /**
         * Personalize content on the page
         */
        personalizeContent() {
            $('.spb-personalized-content').each((index, element) => {
                this.personalizeElement($(element));
            });

            $('.spb-interest-based').each((index, element) => {
                this.checkInterestMatch($(element));
            });
        }

        /**
         * Personalize individual element
         */
        personalizeElement($element) {
            const interests = $element.data('interests');
            const minConfidence = parseFloat($element.data('min-confidence')) || 0.5;

            if (!interests) return;

            const interestArray = interests.split(',').map(i => i.trim());
            let maxRelevance = 0;

            interestArray.forEach(interest => {
                const userInterest = this.userInterests[interest] || 0;
                maxRelevance = Math.max(maxRelevance, userInterest);
            });

            // Apply relevance styling
            if (maxRelevance >= 0.8) {
                $element.addClass('spb-high-relevance');
                this.addPersonalizationIndicator($element, 'high', maxRelevance);
            } else if (maxRelevance >= 0.5) {
                $element.addClass('spb-medium-relevance');
                this.addPersonalizationIndicator($element, 'medium', maxRelevance);
            } else if (maxRelevance >= 0.2) {
                $element.addClass('spb-low-relevance');
                this.addPersonalizationIndicator($element, 'low', maxRelevance);
            }

            // Hide content if below minimum confidence
            if (maxRelevance < minConfidence) {
                $element.hide();
            }
        }

        /**
         * Check interest match for interest-based content
         */
        checkInterestMatch($element) {
            const interest = $element.data('interest');
            const threshold = parseFloat($element.data('threshold')) || 0.3;

            if (!interest) return;

            const userInterest = this.userInterests[interest] || 0;

            if (userInterest >= threshold) {
                $element.addClass('spb-matched');
                this.addPersonalizationIndicator($element, 'matched', userInterest);
            }
        }

        /**
         * Add personalization indicator
         */
        addPersonalizationIndicator($element, type, score) {
            const percentage = Math.round(score * 100);
            const indicator = $(`<div class="spb-personalization-indicator spb-${type}-match">${percentage}%</div>`);
            $element.css('position', 'relative').append(indicator);
        }

        /**
         * Load personalized recommendations
         */
        loadRecommendations() {
            $.ajax({
                url: spb_personalization.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_get_recommendations',
                    session_id: this.sessionId,
                    page_url: window.location.href,
                    nonce: spb_personalization.nonce
                },
                success: (response) => {
                    if (response.success && response.data.recommendations) {
                        this.recommendations = response.data.recommendations;
                        this.displayRecommendations();
                    }
                }
            });
        }

        /**
         * Display recommendations
         */
        displayRecommendations() {
            if (this.recommendations.length === 0) return;

            const $recommendationsContainer = $('.spb-recommendations');
            if ($recommendationsContainer.length === 0) {
                this.createRecommendationsContainer();
            }

            const $container = $('.spb-recommendations');
            let html = '<div class="spb-recommendations-header">';
            html += '<div class="spb-recommendations-icon"></div>';
            html += '<h3 class="spb-recommendations-title">Recommended for You</h3>';
            html += '</div>';

            this.recommendations.forEach(rec => {
                html += `
                    <a href="${rec.url}" class="spb-recommendation-item" data-rec-id="${rec.id}">
                        <div class="spb-recommendation-content">
                            <div class="spb-recommendation-title">${rec.title}</div>
                            <div class="spb-recommendation-excerpt">${rec.excerpt}</div>
                        </div>
                        <div class="spb-recommendation-score">${Math.round(rec.score * 100)}%</div>
                    </a>
                `;
            });

            $container.html(html);
        }

        /**
         * Create recommendations container
         */
        createRecommendationsContainer() {
            const $container = $('<div class="spb-recommendations"></div>');
            
            // Try to insert after main content
            if ($('.entry-content').length) {
                $('.entry-content').after($container);
            } else if ($('main').length) {
                $('main').append($container);
            } else {
                $('body').append($container);
            }
        }

        /**
         * Initialize interest visualization
         */
        initInterestVisualization() {
            this.createInterestMeters();
            this.createInterestTags();
        }

        /**
         * Create interest meters
         */
        createInterestMeters() {
            if (Object.keys(this.userInterests).length === 0) return;

            const $container = $('<div class="spb-interest-visualization"></div>');
            
            Object.entries(this.userInterests).forEach(([interest, score]) => {
                if (score > 0.1) { // Only show interests above 10%
                    const $meter = $(`
                        <div class="spb-interest-meter">
                            <div class="spb-interest-meter-label">${this.formatInterestName(interest)}</div>
                            <div class="spb-interest-meter-bar">
                                <div class="spb-interest-meter-fill" style="width: ${score * 100}%"></div>
                            </div>
                        </div>
                    `);
                    $container.append($meter);
                }
            });

            // Add to personalization panel
            $('.spb-personalization-controls').append($container);
        }

        /**
         * Create interest tags
         */
        createInterestTags() {
            const topInterests = Object.entries(this.userInterests)
                .sort(([,a], [,b]) => b - a)
                .slice(0, 5)
                .filter(([,score]) => score > 0.2);

            if (topInterests.length === 0) return;

            const $tagsContainer = $('<div class="spb-interest-tags"></div>');
            
            topInterests.forEach(([interest, score]) => {
                const $tag = $(`
                    <span class="spb-interest-tag spb-active" data-interest="${interest}">
                        ${this.formatInterestName(interest)}
                    </span>
                `);
                $tagsContainer.append($tag);
            });

            // Insert after page title or at top of content
            if ($('h1').first().length) {
                $('h1').first().after($tagsContainer);
            } else if ($('.entry-content').length) {
                $('.entry-content').prepend($tagsContainer);
            }
        }

        /**
         * Update interest visualization
         */
        updateInterestVisualization() {
            $('.spb-interest-meter-fill').each(function() {
                const $fill = $(this);
                const interest = $fill.closest('.spb-interest-meter').find('.spb-interest-meter-label').text().toLowerCase();
                const score = this.userInterests[interest] || 0;
                $fill.css('width', (score * 100) + '%');
            });
        }

        /**
         * Create personalization toggle button
         */
        createPersonalizationToggle() {
            const $toggle = $(`
                <button class="spb-personalization-toggle" title="Personalization Settings">
                    ⚙️
                </button>
            `);
            $('body').append($toggle);
        }

        /**
         * Create personalization panel
         */
        createPersonalizationPanel() {
            const $panel = $(`
                <div class="spb-personalization-controls">
                    <h3>Personalization Settings</h3>
                    <p>Your content is personalized based on your interests and behavior.</p>
                    <div class="spb-personalization-actions">
                        <button class="spb-privacy-button spb-reset-profile">Reset Profile</button>
                        <button class="spb-privacy-button spb-disable-tracking">Disable Tracking</button>
                    </div>
                </div>
            `);
            $('body').append($panel);
        }

        /**
         * Toggle personalization panel
         */
        togglePersonalizationPanel() {
            $('.spb-personalization-controls').toggleClass('spb-visible');
            $('.spb-personalization-toggle').toggleClass('spb-active');
        }

        /**
         * Show privacy notice if needed
         */
        showPrivacyNoticeIfNeeded() {
            const hasConsent = localStorage.getItem('spb_privacy_consent');
            if (!hasConsent) {
                this.showPrivacyNotice();
            }
        }

        /**
         * Show privacy notice
         */
        showPrivacyNotice() {
            const $notice = $(`
                <div class="spb-privacy-notice">
                    <div class="spb-privacy-notice-title">Personalized Experience</div>
                    <p>We use cookies and tracking to personalize your content experience. This helps us show you more relevant content.</p>
                    <div class="spb-privacy-controls">
                        <button class="spb-privacy-button spb-accept">Accept</button>
                        <button class="spb-privacy-button spb-decline">Decline</button>
                    </div>
                </div>
            `);

            $('body').prepend($notice);
        }

        /**
         * Handle privacy actions
         */
        handlePrivacyAction(event) {
            const $button = $(event.currentTarget);
            const action = $button.hasClass('spb-accept') ? 'accept' : 
                          $button.hasClass('spb-decline') ? 'decline' :
                          $button.hasClass('spb-reset-profile') ? 'reset' :
                          $button.hasClass('spb-disable-tracking') ? 'disable' : '';

            switch (action) {
                case 'accept':
                    localStorage.setItem('spb_privacy_consent', 'true');
                    $('.spb-privacy-notice').fadeOut();
                    break;
                    
                case 'decline':
                    localStorage.setItem('spb_privacy_consent', 'false');
                    $('.spb-privacy-notice').fadeOut();
                    this.disableTracking();
                    break;
                    
                case 'reset':
                    this.resetUserProfile();
                    break;
                    
                case 'disable':
                    this.disableTracking();
                    break;
            }
        }

        /**
         * Track content interaction
         */
        trackContentInteraction(event) {
            const $element = $(event.currentTarget);
            const interactionData = {
                element_type: 'personalized_content',
                interests: $element.data('interests'),
                relevance_class: this.getRelevanceClass($element),
                position: this.getElementPosition($element)
            };

            this.sendInteractionData('content_interaction', interactionData);
        }

        /**
         * Track content hover
         */
        trackContentHover(event) {
            const $element = $(event.currentTarget);
            const interactionData = {
                element_type: 'interest_based_content',
                interest: $element.data('interest'),
                threshold: $element.data('threshold'),
                matched: $element.hasClass('spb-matched')
            };

            this.sendInteractionData('content_hover', interactionData);
        }

        /**
         * Track recommendation click
         */
        trackRecommendationClick(event) {
            const $item = $(event.currentTarget);
            const recId = $item.data('rec-id');
            const recommendation = this.recommendations.find(r => r.id === recId);

            if (recommendation) {
                this.sendInteractionData('recommendation_click', {
                    recommendation_id: recId,
                    title: recommendation.title,
                    score: recommendation.score,
                    position: $item.index()
                });
            }
        }

        /**
         * Handle interest tag click
         */
        handleInterestTagClick(event) {
            const $tag = $(event.currentTarget);
            const interest = $tag.data('interest');
            
            $tag.toggleClass('spb-active');
            
            this.sendInteractionData('interest_tag_click', {
                interest: interest,
                active: $tag.hasClass('spb-active')
            });
        }

        /**
         * Start time tracking
         */
        startTimeTracking() {
            this.pageStartTime = Date.now();
            this.lastActivityTime = Date.now();
            
            // Track activity
            $(document).on('mousemove keypress scroll click', () => {
                this.lastActivityTime = Date.now();
            });
            
            // Send time data periodically
            setInterval(() => {
                this.sendTimeData();
            }, 30000); // Every 30 seconds
        }

        /**
         * Handle visibility change
         */
        handleVisibilityChange() {
            if (document.hidden) {
                this.sendTimeData();
            } else {
                this.lastActivityTime = Date.now();
            }
        }

        /**
         * Send time tracking data
         */
        sendTimeData() {
            const now = Date.now();
            const totalTime = now - this.pageStartTime;
            const activeTime = now - this.lastActivityTime < 5000 ? totalTime : totalTime - (now - this.lastActivityTime);

            this.sendInteractionData('time_tracking', {
                total_time: totalTime,
                active_time: activeTime,
                url: window.location.href
            });
        }

        /**
         * Track page engagement
         */
        trackPageEngagement() {
            // Track scroll depth
            let maxScrollDepth = 0;
            $(window).on('scroll', () => {
                const scrollDepth = $(window).scrollTop() / ($(document).height() - $(window).height());
                if (scrollDepth > maxScrollDepth) {
                    maxScrollDepth = scrollDepth;
                }
            });

            // Send engagement data when leaving page
            $(window).on('beforeunload', () => {
                this.sendInteractionData('page_engagement', {
                    max_scroll_depth: maxScrollDepth,
                    time_on_page: Date.now() - this.pageStartTime,
                    interactions: this.interactionCount || 0
                });
            });
        }

        /**
         * Reset user profile
         */
        resetUserProfile() {
            $.ajax({
                url: spb_personalization.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_reset_user_profile',
                    session_id: this.sessionId,
                    nonce: spb_personalization.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.userInterests = {};
                        this.updateInterestVisualization();
                        location.reload();
                    }
                }
            });
        }

        /**
         * Disable tracking
         */
        disableTracking() {
            this.trackingEnabled = false;
            localStorage.setItem('spb_tracking_disabled', 'true');
            $('.spb-personalization-controls').removeClass('spb-visible');
            $('.spb-personalization-toggle').hide();
        }

        /**
         * Send interaction data
         */
        sendInteractionData(type, data) {
            if (!this.trackingEnabled) return;

            this.interactionCount = (this.interactionCount || 0) + 1;

            $.ajax({
                url: spb_personalization.ajax_url,
                type: 'POST',
                data: {
                    action: 'spb_track_personalization_interaction',
                    session_id: this.sessionId,
                    interaction_type: type,
                    interaction_data: data,
                    timestamp: Date.now(),
                    nonce: spb_personalization.nonce
                },
                timeout: 5000
            });
        }

        /**
         * Get relevance class from element
         */
        getRelevanceClass($element) {
            if ($element.hasClass('spb-high-relevance')) return 'high';
            if ($element.hasClass('spb-medium-relevance')) return 'medium';
            if ($element.hasClass('spb-low-relevance')) return 'low';
            return 'none';
        }

        /**
         * Get element position on page
         */
        getElementPosition($element) {
            const offset = $element.offset();
            const windowHeight = $(window).height();
            return {
                top: offset.top,
                visible: offset.top < $(window).scrollTop() + windowHeight
            };
        }

        /**
         * Format interest name for display
         */
        formatInterestName(interest) {
            return interest.replace(/[_-]/g, ' ')
                          .replace(/\b\w/g, l => l.toUpperCase());
        }
    }

    // Initialize personalization when script loads
    if (typeof spb_personalization !== 'undefined') {
        new SmartPageBuilderPersonalization();
    }

})(jQuery);
