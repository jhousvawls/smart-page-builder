/**
 * Analytics Dashboard JavaScript
 *
 * Handles real-time analytics dashboard functionality including charts,
 * data visualization, and interactive components.
 *
 * @package    SmartPageBuilder
 * @subpackage SmartPageBuilder/admin/js
 * @since      2.0.0
 */

(function($) {
    'use strict';

    /**
     * Analytics Dashboard Class
     */
    class SPBAnalyticsDashboard {
        constructor() {
            this.charts = {};
            this.refreshInterval = null;
            this.currentPeriod = 'week';
            this.isRealTimeEnabled = true;
            
            this.init();
        }

        /**
         * Initialize the dashboard
         */
        init() {
            this.bindEvents();
            this.initializeCharts();
            this.startRealTimeUpdates();
            this.setupExportDropdown();
            this.setupABTestModal();
        }

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Refresh button
            $('#spb-refresh-analytics').on('click', (e) => {
                e.preventDefault();
                this.refreshData();
            });

            // Period filter
            $('#spb-analytics-period').on('change', (e) => {
                this.currentPeriod = $(e.target).val();
                this.updatePeriodData();
            });

            // Export dropdown toggle
            $('#spb-export-toggle').on('click', (e) => {
                e.preventDefault();
                this.toggleExportDropdown();
            });

            // A/B test creation
            $('#spb-create-test').on('click', (e) => {
                e.preventDefault();
                this.showABTestModal();
            });

            // Content generation from gaps
            $('.spb-generate-content').on('click', (e) => {
                e.preventDefault();
                const searchTerm = $(e.target).data('term');
                this.generateContentFromGap(searchTerm);
            });

            // Modal close handlers
            $('#spb-test-modal-close, #spb-test-cancel').on('click', (e) => {
                e.preventDefault();
                this.hideABTestModal();
            });

            // A/B test form submission
            $('#spb-create-test-form').on('submit', (e) => {
                e.preventDefault();
                this.createABTest();
            });

            // Close export dropdown when clicking outside
            $(document).on('click', (e) => {
                if (!$(e.target).closest('.spb-export-dropdown').length) {
                    this.hideExportDropdown();
                }
            });

            // Window resize handler for responsive charts
            $(window).on('resize', () => {
                this.resizeCharts();
            });
        }

        /**
         * Initialize charts
         */
        initializeCharts() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded. Charts will not be displayed.');
                return;
            }

            this.initPerformanceChart();
            this.initApprovalChart();
        }

        /**
         * Initialize performance trends chart
         */
        initPerformanceChart() {
            const ctx = document.getElementById('spb-performance-chart');
            if (!ctx) return;

            const data = this.getPerformanceChartData();
            
            this.charts.performance = new Chart(ctx, {
                type: 'line',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            borderColor: '#ddd',
                            borderWidth: 1
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            display: true,
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        /**
         * Initialize approval rates chart
         */
        initApprovalChart() {
            const ctx = document.getElementById('spb-approval-chart');
            if (!ctx) return;

            const data = this.getApprovalChartData();
            
            this.charts.approval = new Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value}%`;
                                }
                            }
                        }
                    },
                    cutout: '60%'
                }
            });
        }

        /**
         * Get performance chart data
         */
        getPerformanceChartData() {
            // Generate sample data for the last 7 days
            const labels = [];
            const pageViews = [];
            const contentGenerated = [];
            
            for (let i = 6; i >= 0; i--) {
                const date = new Date();
                date.setDate(date.getDate() - i);
                labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
                
                // Sample data - in real implementation, this would come from analytics data
                pageViews.push(Math.floor(Math.random() * 100) + 50);
                contentGenerated.push(Math.floor(Math.random() * 20) + 5);
            }

            return {
                labels: labels,
                datasets: [
                    {
                        label: 'Page Views',
                        data: pageViews,
                        borderColor: '#0073aa',
                        backgroundColor: 'rgba(0, 115, 170, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Content Generated',
                        data: contentGenerated,
                        borderColor: '#00a32a',
                        backgroundColor: 'rgba(0, 163, 42, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            };
        }

        /**
         * Get approval chart data
         */
        getApprovalChartData() {
            // Sample approval rate data
            const approvalRate = window.spbAnalyticsData?.weekly?.approval_rate || 75;
            const rejectionRate = 100 - approvalRate;

            return {
                labels: ['Approved', 'Rejected'],
                datasets: [{
                    data: [approvalRate, rejectionRate],
                    backgroundColor: [
                        '#00a32a',
                        '#d63638'
                    ],
                    borderWidth: 0
                }]
            };
        }

        /**
         * Start real-time updates
         */
        startRealTimeUpdates() {
            if (!this.isRealTimeEnabled) return;

            this.refreshInterval = setInterval(() => {
                this.updateRealTimeMetrics();
            }, 30000); // Update every 30 seconds
        }

        /**
         * Stop real-time updates
         */
        stopRealTimeUpdates() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
        }

        /**
         * Update real-time metrics
         */
        updateRealTimeMetrics() {
            // Update timestamp
            const now = new Date();
            $('#spb-last-update-time').text(now.toLocaleTimeString());

            // Animate indicator
            $('.spb-indicator-dot').addClass('spb-pulse');
            setTimeout(() => {
                $('.spb-indicator-dot').removeClass('spb-pulse');
            }, 1000);

            // In a real implementation, this would fetch fresh data via AJAX
            this.simulateMetricUpdates();
        }

        /**
         * Simulate metric updates for demo purposes
         */
        simulateMetricUpdates() {
            // Simulate small changes in metrics
            const metrics = ['#spb-page-views', '#spb-content-generated', '#spb-search-queries'];
            
            metrics.forEach(selector => {
                const $element = $(selector);
                const currentValue = parseInt($element.text().replace(/,/g, ''));
                const change = Math.floor(Math.random() * 3); // 0-2 increase
                const newValue = currentValue + change;
                
                if (change > 0) {
                    $element.text(newValue.toLocaleString());
                    $element.parent().addClass('spb-metric-updated');
                    setTimeout(() => {
                        $element.parent().removeClass('spb-metric-updated');
                    }, 2000);
                }
            });
        }

        /**
         * Refresh all data
         */
        refreshData() {
            const $button = $('#spb-refresh-analytics');
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Refreshing...');
            
            // Simulate data refresh
            setTimeout(() => {
                this.updateCharts();
                this.updateRealTimeMetrics();
                
                $button.prop('disabled', false).text(originalText);
                
                // Show success message
                this.showNotification('Analytics data refreshed successfully', 'success');
            }, 1500);
        }

        /**
         * Update period data
         */
        updatePeriodData() {
            // Update metrics based on selected period
            const periodData = this.getPeriodData(this.currentPeriod);
            this.updateMetricCards(periodData);
            this.updateCharts();
        }

        /**
         * Get data for specific period
         */
        getPeriodData(period) {
            // In real implementation, this would fetch data from server
            const baseData = window.spbAnalyticsData || {};
            
            switch (period) {
                case 'today':
                    return baseData.today || {};
                case 'week':
                    return baseData.weekly || {};
                case 'month':
                    return baseData.monthly || {};
                default:
                    return baseData.weekly || {};
            }
        }

        /**
         * Update metric cards
         */
        updateMetricCards(data) {
            $('#spb-page-views').text((data.page_views || 0).toLocaleString());
            $('#spb-content-generated').text((data.content_generated || 0).toLocaleString());
            $('#spb-search-queries').text((data.search_queries || 0).toLocaleString());
            $('#spb-avg-confidence').text((data.avg_confidence || 0).toFixed(1) + '%');
        }

        /**
         * Update charts with new data
         */
        updateCharts() {
            if (this.charts.performance) {
                this.charts.performance.data = this.getPerformanceChartData();
                this.charts.performance.update('active');
            }
            
            if (this.charts.approval) {
                this.charts.approval.data = this.getApprovalChartData();
                this.charts.approval.update('active');
            }
        }

        /**
         * Resize charts for responsive design
         */
        resizeCharts() {
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }

        /**
         * Setup export dropdown
         */
        setupExportDropdown() {
            // Initially hidden
            $('#spb-export-menu').hide();
        }

        /**
         * Toggle export dropdown
         */
        toggleExportDropdown() {
            $('#spb-export-menu').toggle();
        }

        /**
         * Hide export dropdown
         */
        hideExportDropdown() {
            $('#spb-export-menu').hide();
        }

        /**
         * Setup A/B test modal
         */
        setupABTestModal() {
            // Modal is initially hidden via CSS
        }

        /**
         * Show A/B test modal
         */
        showABTestModal() {
            $('#spb-test-modal').fadeIn(300);
            $('#spb-test-name').focus();
        }

        /**
         * Hide A/B test modal
         */
        hideABTestModal() {
            $('#spb-test-modal').fadeOut(300);
            $('#spb-create-test-form')[0].reset();
        }

        /**
         * Create A/B test
         */
        createABTest() {
            const formData = {
                action: 'spb_create_ab_test',
                nonce: spbAnalytics.nonce,
                test_name: $('#spb-test-name').val(),
                test_type: $('#spb-test-type').val(),
                description: $('#spb-test-description').val()
            };

            $.post(spbAnalytics.ajaxUrl, formData)
                .done((response) => {
                    if (response.success) {
                        this.hideABTestModal();
                        this.showNotification('A/B test created successfully', 'success');
                        // Refresh the testing section
                        this.refreshTestingSection();
                    } else {
                        this.showNotification(response.data || 'Failed to create A/B test', 'error');
                    }
                })
                .fail(() => {
                    this.showNotification('Network error. Please try again.', 'error');
                });
        }

        /**
         * Generate content from gap
         */
        generateContentFromGap(searchTerm) {
            const $button = $(`.spb-generate-content[data-term="${searchTerm}"]`);
            const originalText = $button.text();
            
            $button.prop('disabled', true).text('Generating...');
            
            // Simulate content generation
            setTimeout(() => {
                $button.prop('disabled', false).text(originalText);
                this.showNotification(`Content generation started for "${searchTerm}"`, 'success');
            }, 2000);
        }

        /**
         * Refresh testing section
         */
        refreshTestingSection() {
            // In real implementation, this would reload the A/B testing section
            $('.spb-test-placeholder').html(`
                <span class="dashicons dashicons-yes-alt"></span>
                <p>A/B test created successfully. Results will appear here as data is collected.</p>
            `);
        }

        /**
         * Show notification
         */
        showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="notice notice-${type} is-dismissible spb-notification">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            $('.spb-analytics-dashboard h1').after($notification);

            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Manual dismiss
            $notification.find('.notice-dismiss').on('click', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }

        /**
         * Destroy the dashboard
         */
        destroy() {
            this.stopRealTimeUpdates();
            
            // Destroy charts
            Object.values(this.charts).forEach(chart => {
                if (chart && typeof chart.destroy === 'function') {
                    chart.destroy();
                }
            });
            
            // Remove event listeners
            $(window).off('resize');
            $(document).off('click');
        }
    }

    /**
     * Initialize dashboard when document is ready
     */
    $(document).ready(function() {
        // Only initialize if we're on the analytics page
        if ($('.spb-analytics-dashboard').length > 0) {
            window.spbDashboard = new SPBAnalyticsDashboard();
        }
    });

    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        if (window.spbDashboard) {
            window.spbDashboard.destroy();
        }
    });

})(jQuery);

/**
 * Global analytics utilities
 */
window.SPBAnalytics = {
    /**
     * Format number with commas
     */
    formatNumber: function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    },

    /**
     * Calculate percentage change
     */
    calculateChange: function(current, previous) {
        if (previous === 0) return current > 0 ? 100 : 0;
        return ((current - previous) / previous) * 100;
    },

    /**
     * Format percentage
     */
    formatPercentage: function(value, decimals = 1) {
        return value.toFixed(decimals) + '%';
    },

    /**
     * Get time ago string
     */
    timeAgo: function(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);

        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    }
};
