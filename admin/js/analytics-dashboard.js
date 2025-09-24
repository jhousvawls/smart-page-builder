/**
 * Smart Page Builder Analytics Dashboard JavaScript
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

(function($) {
    'use strict';

    /**
     * Analytics Dashboard Object
     */
    var SPBAnalytics = {
        
        /**
         * Chart instances
         */
        charts: {},
        
        /**
         * Current filters
         */
        filters: {
            dateRange: '7d',
            metric: 'all',
            page: 1,
            search: ''
        },
        
        /**
         * Real-time update interval
         */
        updateInterval: null,
        
        /**
         * Initialize analytics dashboard
         */
        init: function() {
            this.bindEvents();
            this.initCharts();
            this.loadMetrics();
            this.loadData();
            this.startRealTimeUpdates();
            this.initFilters();
            this.initExport();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Chart filter buttons
            $(document).on('click', '.spb-chart-filter', this.handleChartFilter);
            
            // Data table search
            $(document).on('input', '.spb-data-search', this.debounce(this.handleSearch, 300));
            
            // Pagination
            $(document).on('click', '.spb-pagination-btn', this.handlePagination);
            
            // Filter controls
            $(document).on('click', '.spb-filter-apply', this.applyFilters);
            $(document).on('click', '.spb-filter-reset', this.resetFilters);
            
            // Export buttons
            $(document).on('click', '.spb-data-export', this.handleExport);
            
            // Metric card clicks
            $(document).on('click', '.spb-metric-card', this.handleMetricClick);
            
            // Window resize for responsive charts
            $(window).on('resize', this.debounce(this.resizeCharts, 250));
            
            // Visibility change for real-time updates
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    self.stopRealTimeUpdates();
                } else {
                    self.startRealTimeUpdates();
                }
            });
        },

        /**
         * Initialize charts
         */
        initCharts: function() {
            this.initPageViewsChart();
            this.initConversionsChart();
            this.initTopPagesChart();
        },

        /**
         * Initialize page views chart
         */
        initPageViewsChart: function() {
            var ctx = document.getElementById('spb-pageviews-chart');
            if (!ctx) return;

            this.charts.pageViews = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Page Views',
                        data: [],
                        borderColor: '#2271b1',
                        backgroundColor: 'rgba(34, 113, 177, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f0f0f1'
                            }
                        },
                        x: {
                            grid: {
                                color: '#f0f0f1'
                            }
                        }
                    }
                }
            });
        },

        /**
         * Initialize conversions chart
         */
        initConversionsChart: function() {
            var ctx = document.getElementById('spb-conversions-chart');
            if (!ctx) return;

            this.charts.conversions = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Converted', 'Not Converted'],
                    datasets: [{
                        data: [0, 0],
                        backgroundColor: ['#00a32a', '#f0f0f1'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Initialize top pages chart
         */
        initTopPagesChart: function() {
            var ctx = document.getElementById('spb-toppages-chart');
            if (!ctx) return;

            this.charts.topPages = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Views',
                        data: [],
                        backgroundColor: '#2271b1',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                color: '#f0f0f1'
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        /**
         * Load metrics data
         */
        loadMetrics: function() {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_get_metrics',
                    nonce: spb_analytics.nonce,
                    filters: this.filters
                },
                success: function(response) {
                    if (response.success) {
                        self.updateMetrics(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load metrics:', error);
                }
            });
        },

        /**
         * Update metrics display
         */
        updateMetrics: function(data) {
            // Update metric values
            $('.spb-metric-value[data-metric="pages"]').text(this.formatNumber(data.total_pages));
            $('.spb-metric-value[data-metric="users"]').text(this.formatNumber(data.unique_users));
            $('.spb-metric-value[data-metric="conversions"]').text(this.formatNumber(data.conversions));
            $('.spb-metric-value[data-metric="performance"]').text(data.avg_load_time + 's');
            
            // Update metric changes
            this.updateMetricChange('pages', data.pages_change);
            this.updateMetricChange('users', data.users_change);
            this.updateMetricChange('conversions', data.conversions_change);
            this.updateMetricChange('performance', data.performance_change);
            
            // Update last updated time
            $('.spb-last-updated').text('Last updated: ' + new Date().toLocaleTimeString());
        },

        /**
         * Update metric change indicator
         */
        updateMetricChange: function(metric, change) {
            var $change = $('.spb-metric-change[data-metric="' + metric + '"]');
            var $icon = $change.find('.spb-metric-change-icon');
            var $value = $change.find('.spb-metric-change-value');
            
            $change.removeClass('positive negative neutral');
            
            if (change > 0) {
                $change.addClass('positive');
                $icon.html('↗');
                $value.text('+' + change.toFixed(1) + '%');
            } else if (change < 0) {
                $change.addClass('negative');
                $icon.html('↘');
                $value.text(change.toFixed(1) + '%');
            } else {
                $change.addClass('neutral');
                $icon.html('→');
                $value.text('0%');
            }
        },

        /**
         * Load chart and table data
         */
        loadData: function() {
            var self = this;
            
            // Show loading states
            $('.spb-chart-loading').show();
            $('.spb-data-section .spb-loading').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_get_analytics_data',
                    nonce: spb_analytics.nonce,
                    filters: this.filters
                },
                success: function(response) {
                    if (response.success) {
                        self.updateCharts(response.data.charts);
                        self.updateTable(response.data.table);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load data:', error);
                },
                complete: function() {
                    $('.spb-chart-loading').hide();
                    $('.spb-data-section .spb-loading').hide();
                }
            });
        },

        /**
         * Update charts with new data
         */
        updateCharts: function(data) {
            // Update page views chart
            if (this.charts.pageViews && data.pageViews) {
                this.charts.pageViews.data.labels = data.pageViews.labels;
                this.charts.pageViews.data.datasets[0].data = data.pageViews.data;
                this.charts.pageViews.update();
            }
            
            // Update conversions chart
            if (this.charts.conversions && data.conversions) {
                this.charts.conversions.data.datasets[0].data = [
                    data.conversions.converted,
                    data.conversions.total - data.conversions.converted
                ];
                this.charts.conversions.update();
            }
            
            // Update top pages chart
            if (this.charts.topPages && data.topPages) {
                this.charts.topPages.data.labels = data.topPages.labels;
                this.charts.topPages.data.datasets[0].data = data.topPages.data;
                this.charts.topPages.update();
            }
        },

        /**
         * Update data table
         */
        updateTable: function(data) {
            var $tbody = $('.spb-analytics-table tbody');
            $tbody.empty();
            
            if (data.rows && data.rows.length > 0) {
                data.rows.forEach(function(row) {
                    var $row = $('<tr>');
                    
                    // Add cells based on row data
                    Object.values(row).forEach(function(cellData) {
                        var $cell = $('<td>').html(cellData);
                        $row.append($cell);
                    });
                    
                    $tbody.append($row);
                });
                
                // Update pagination
                this.updatePagination(data.pagination);
            } else {
                $tbody.append('<tr><td colspan="100%" style="text-align: center; padding: 40px;">No data available</td></tr>');
            }
        },

        /**
         * Update pagination controls
         */
        updatePagination: function(pagination) {
            var $pagination = $('.spb-pagination-controls');
            $pagination.empty();
            
            // Previous button
            var prevDisabled = pagination.current_page <= 1 ? 'disabled' : '';
            $pagination.append('<button class="spb-pagination-btn" data-page="' + (pagination.current_page - 1) + '" ' + prevDisabled + '>Previous</button>');
            
            // Page numbers
            for (var i = 1; i <= pagination.total_pages; i++) {
                var activeClass = i === pagination.current_page ? 'active' : '';
                $pagination.append('<button class="spb-pagination-btn ' + activeClass + '" data-page="' + i + '">' + i + '</button>');
            }
            
            // Next button
            var nextDisabled = pagination.current_page >= pagination.total_pages ? 'disabled' : '';
            $pagination.append('<button class="spb-pagination-btn" data-page="' + (pagination.current_page + 1) + '" ' + nextDisabled + '>Next</button>');
            
            // Update pagination info
            $('.spb-pagination-info').text('Showing ' + pagination.start + '-' + pagination.end + ' of ' + pagination.total + ' results');
        },

        /**
         * Handle chart filter clicks
         */
        handleChartFilter: function(e) {
            e.preventDefault();
            
            var $filter = $(this);
            var period = $filter.data('period');
            
            // Update active filter
            $filter.siblings().removeClass('active');
            $filter.addClass('active');
            
            // Update filters and reload data
            SPBAnalytics.filters.dateRange = period;
            SPBAnalytics.loadMetrics();
            SPBAnalytics.loadData();
        },

        /**
         * Handle search input
         */
        handleSearch: function() {
            SPBAnalytics.filters.search = $(this).val();
            SPBAnalytics.filters.page = 1; // Reset to first page
            SPBAnalytics.loadData();
        },

        /**
         * Handle pagination clicks
         */
        handlePagination: function(e) {
            e.preventDefault();
            
            if ($(this).prop('disabled')) return;
            
            var page = parseInt($(this).data('page'));
            if (page > 0) {
                SPBAnalytics.filters.page = page;
                SPBAnalytics.loadData();
            }
        },

        /**
         * Apply filters
         */
        applyFilters: function() {
            // Collect filter values
            SPBAnalytics.filters.dateRange = $('#spb-filter-date').val();
            SPBAnalytics.filters.metric = $('#spb-filter-metric').val();
            SPBAnalytics.filters.page = 1; // Reset to first page
            
            // Reload data
            SPBAnalytics.loadMetrics();
            SPBAnalytics.loadData();
        },

        /**
         * Reset filters
         */
        resetFilters: function() {
            // Reset filter values
            $('#spb-filter-date').val('7d');
            $('#spb-filter-metric').val('all');
            $('.spb-data-search').val('');
            
            // Reset filters object
            SPBAnalytics.filters = {
                dateRange: '7d',
                metric: 'all',
                page: 1,
                search: ''
            };
            
            // Reload data
            SPBAnalytics.loadMetrics();
            SPBAnalytics.loadData();
        },

        /**
         * Handle metric card clicks
         */
        handleMetricClick: function() {
            var metric = $(this).data('metric');
            if (metric) {
                SPBAnalytics.filters.metric = metric;
                SPBAnalytics.loadData();
            }
        },

        /**
         * Handle export
         */
        handleExport: function() {
            var format = $(this).data('format') || 'csv';
            
            // Create download link
            var params = $.param({
                action: 'spb_export_analytics',
                nonce: spb_analytics.nonce,
                format: format,
                filters: JSON.stringify(SPBAnalytics.filters)
            });
            
            var url = ajaxurl + '?' + params;
            
            // Trigger download
            var link = document.createElement('a');
            link.href = url;
            link.download = 'analytics-export.' + format;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        /**
         * Initialize filters
         */
        initFilters: function() {
            // Set default filter values
            $('#spb-filter-date').val(this.filters.dateRange);
            $('#spb-filter-metric').val(this.filters.metric);
        },

        /**
         * Initialize export functionality
         */
        initExport: function() {
            // Add export options if not present
            if (!$('.spb-data-export').length) {
                $('.spb-data-actions').append(
                    '<button class="spb-data-export" data-format="csv">Export CSV</button>'
                );
            }
        },

        /**
         * Start real-time updates
         */
        startRealTimeUpdates: function() {
            var self = this;
            
            // Clear existing interval
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }
            
            // Start new interval (update every 30 seconds)
            this.updateInterval = setInterval(function() {
                self.loadMetrics();
            }, 30000);
            
            // Show real-time indicator
            $('.spb-realtime-indicator').show();
        },

        /**
         * Stop real-time updates
         */
        stopRealTimeUpdates: function() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
            
            // Hide real-time indicator
            $('.spb-realtime-indicator').hide();
        },

        /**
         * Resize charts for responsive design
         */
        resizeCharts: function() {
            Object.values(this.charts).forEach(function(chart) {
                if (chart && chart.resize) {
                    chart.resize();
                }
            });
        },

        /**
         * Format numbers for display
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toString();
        },

        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        /**
         * Destroy charts and cleanup
         */
        destroy: function() {
            // Stop real-time updates
            this.stopRealTimeUpdates();
            
            // Destroy charts
            Object.values(this.charts).forEach(function(chart) {
                if (chart && chart.destroy) {
                    chart.destroy();
                }
            });
            
            this.charts = {};
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        // Only initialize if we're on the analytics page
        if ($('.spb-analytics-dashboard').length) {
            SPBAnalytics.init();
        }
    });

    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        SPBAnalytics.destroy();
    });

    /**
     * Make SPBAnalytics globally available
     */
    window.SPBAnalytics = SPBAnalytics;

})(jQuery);
