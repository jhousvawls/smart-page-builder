/**
 * Smart Page Builder Admin JavaScript
 *
 * @package Smart_Page_Builder
 * @since   3.0.0
 */

(function($) {
    'use strict';

    /**
     * Smart Page Builder Admin Object
     */
    var SPBAdmin = {
        
        /**
         * Initialize admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTabs();
            this.initForms();
            this.initNotices();
            this.initTooltips();
            this.initValidation();
            
            // Initialize feature-specific modules
            if (typeof SPBPersonalization !== 'undefined') {
                SPBPersonalization.init();
            }
            
            if (typeof SPBSearchGeneration !== 'undefined') {
                SPBSearchGeneration.init();
            }
            
            if (typeof SPBApprovalQueue !== 'undefined') {
                SPBApprovalQueue.init();
            }
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Tab navigation
            $(document).on('click', '.spb-nav-tabs a', this.handleTabClick);
            
            // Form submissions
            $(document).on('submit', '.spb-form', this.handleFormSubmit);
            
            // Button actions
            $(document).on('click', '.spb-button[data-action]', this.handleButtonAction);
            
            // Settings changes
            $(document).on('change', '.spb-setting', this.handleSettingChange);
            
            // Notice dismissals
            $(document).on('click', '.spb-notice .notice-dismiss', this.dismissNotice);
            
            // AJAX error handling
            $(document).ajaxError(function(event, xhr, settings, error) {
                self.handleAjaxError(xhr, error);
            });
            
            // Auto-save functionality
            this.initAutoSave();
        },

        /**
         * Handle tab navigation
         */
        handleTabClick: function(e) {
            e.preventDefault();
            
            var $tab = $(this);
            var target = $tab.attr('href');
            
            // Update active tab
            $tab.closest('.spb-nav-tabs').find('a').removeClass('active');
            $tab.addClass('active');
            
            // Show target content
            $('.spb-tab-content').hide();
            $(target).show();
            
            // Update URL hash
            if (history.pushState) {
                history.pushState(null, null, target);
            }
            
            // Trigger tab change event
            $(document).trigger('spb:tab-changed', [target]);
        },

        /**
         * Initialize tab functionality
         */
        initTabs: function() {
            // Show active tab on page load
            var hash = window.location.hash;
            if (hash && $(hash).length) {
                $('.spb-nav-tabs a[href="' + hash + '"]').trigger('click');
            } else {
                $('.spb-nav-tabs a:first').trigger('click');
            }
        },

        /**
         * Handle form submissions
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var $submitBtn = $form.find('[type="submit"]');
            
            // Prevent double submission
            if ($submitBtn.prop('disabled')) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $submitBtn.find('.spb-loading').show();
            
            // Validate form
            if (!SPBAdmin.validateForm($form)) {
                e.preventDefault();
                $submitBtn.prop('disabled', false);
                $submitBtn.find('.spb-loading').hide();
                return false;
            }
            
            // AJAX submission for forms with data-ajax attribute
            if ($form.data('ajax')) {
                e.preventDefault();
                SPBAdmin.submitFormAjax($form);
            }
        },

        /**
         * Submit form via AJAX
         */
        submitFormAjax: function($form) {
            var self = this;
            var formData = new FormData($form[0]);
            
            $.ajax({
                url: $form.attr('action') || ajaxurl,
                type: $form.attr('method') || 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    self.handleAjaxSuccess(response, $form);
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, error);
                },
                complete: function() {
                    $form.find('[type="submit"]').prop('disabled', false);
                    $form.find('.spb-loading').hide();
                }
            });
        },

        /**
         * Handle button actions
         */
        handleButtonAction: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var action = $button.data('action');
            var confirm_msg = $button.data('confirm');
            
            // Confirmation dialog
            if (confirm_msg && !confirm(confirm_msg)) {
                return false;
            }
            
            // Show loading state
            $button.prop('disabled', true);
            $button.find('.spb-loading').show();
            
            // Execute action
            switch (action) {
                case 'test-connection':
                    SPBAdmin.testConnection($button);
                    break;
                case 'clear-cache':
                    SPBAdmin.clearCache($button);
                    break;
                case 'export-settings':
                    SPBAdmin.exportSettings($button);
                    break;
                case 'import-settings':
                    SPBAdmin.importSettings($button);
                    break;
                case 'run-validation':
                    SPBAdmin.runValidation($button);
                    break;
                default:
                    SPBAdmin.genericAction($button, action);
            }
        },

        /**
         * Test API connection
         */
        testConnection: function($button) {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_test_connection',
                    nonce: spb_admin.nonce,
                    provider: $button.data('provider')
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Connection successful!', 'success');
                    } else {
                        self.showNotice('Connection failed: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.find('.spb-loading').hide();
                }
            });
        },

        /**
         * Clear cache
         */
        clearCache: function($button) {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_clear_cache',
                    nonce: spb_admin.nonce,
                    cache_type: $button.data('cache-type') || 'all'
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('Cache cleared successfully!', 'success');
                    } else {
                        self.showNotice('Failed to clear cache: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.find('.spb-loading').hide();
                }
            });
        },

        /**
         * Run validation
         */
        runValidation: function($button) {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_run_validation',
                    nonce: spb_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#spb-validation-results').html(response.data.report);
                        self.showNotice('Validation completed!', 'success');
                    } else {
                        self.showNotice('Validation failed: ' + response.data, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.find('.spb-loading').hide();
                }
            });
        },

        /**
         * Generic AJAX action handler
         */
        genericAction: function($button, action) {
            var self = this;
            var data = $button.data();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: $.extend({
                    action: 'spb_' + action.replace('-', '_'),
                    nonce: spb_admin.nonce
                }, data),
                success: function(response) {
                    self.handleAjaxSuccess(response);
                },
                error: function(xhr, status, error) {
                    self.handleAjaxError(xhr, error);
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $button.find('.spb-loading').hide();
                }
            });
        },

        /**
         * Handle setting changes
         */
        handleSettingChange: function() {
            var $setting = $(this);
            var setting_name = $setting.attr('name');
            var setting_value = $setting.val();
            
            // Auto-save setting
            if ($setting.data('auto-save')) {
                SPBAdmin.autoSaveSetting(setting_name, setting_value);
            }
            
            // Trigger setting change event
            $(document).trigger('spb:setting-changed', [setting_name, setting_value]);
        },

        /**
         * Auto-save setting
         */
        autoSaveSetting: function(name, value) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'spb_auto_save_setting',
                    nonce: spb_admin.nonce,
                    setting_name: name,
                    setting_value: value
                },
                success: function(response) {
                    if (response.success) {
                        SPBAdmin.showNotice('Setting saved', 'success', 2000);
                    }
                }
            });
        },

        /**
         * Initialize auto-save functionality
         */
        initAutoSave: function() {
            var autoSaveTimer;
            
            $(document).on('input change', '.spb-setting[data-auto-save]', function() {
                var $setting = $(this);
                
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    SPBAdmin.autoSaveSetting($setting.attr('name'), $setting.val());
                }, 1000);
            });
        },

        /**
         * Initialize form functionality
         */
        initForms: function() {
            // Initialize select2 dropdowns
            if ($.fn.select2) {
                $('.spb-select2').select2({
                    width: '100%'
                });
            }
            
            // Initialize color pickers
            if ($.fn.wpColorPicker) {
                $('.spb-color-picker').wpColorPicker();
            }
            
            // Initialize date pickers
            if ($.fn.datepicker) {
                $('.spb-date-picker').datepicker({
                    dateFormat: 'yy-mm-dd'
                });
            }
        },

        /**
         * Form validation
         */
        validateForm: function($form) {
            var isValid = true;
            
            // Clear previous errors
            $form.find('.spb-error').removeClass('spb-error');
            $form.find('.spb-error-message').remove();
            
            // Validate required fields
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (!value || value.trim() === '') {
                    SPBAdmin.showFieldError($field, 'This field is required');
                    isValid = false;
                }
            });
            
            // Validate email fields
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && !SPBAdmin.isValidEmail(value)) {
                    SPBAdmin.showFieldError($field, 'Please enter a valid email address');
                    isValid = false;
                }
            });
            
            // Validate URL fields
            $form.find('input[type="url"]').each(function() {
                var $field = $(this);
                var value = $field.val();
                
                if (value && !SPBAdmin.isValidUrl(value)) {
                    SPBAdmin.showFieldError($field, 'Please enter a valid URL');
                    isValid = false;
                }
            });
            
            return isValid;
        },

        /**
         * Show field error
         */
        showFieldError: function($field, message) {
            $field.addClass('spb-error');
            $field.after('<div class="spb-error-message">' + message + '</div>');
        },

        /**
         * Email validation
         */
        isValidEmail: function(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        },

        /**
         * URL validation
         */
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Initialize validation
         */
        initValidation: function() {
            // Real-time validation
            $(document).on('blur', '.spb-form input, .spb-form textarea, .spb-form select', function() {
                var $field = $(this);
                var $form = $field.closest('.spb-form');
                
                // Clear previous error
                $field.removeClass('spb-error');
                $field.next('.spb-error-message').remove();
                
                // Validate field
                if ($field.attr('required') && !$field.val()) {
                    SPBAdmin.showFieldError($field, 'This field is required');
                } else if ($field.attr('type') === 'email' && $field.val() && !SPBAdmin.isValidEmail($field.val())) {
                    SPBAdmin.showFieldError($field, 'Please enter a valid email address');
                } else if ($field.attr('type') === 'url' && $field.val() && !SPBAdmin.isValidUrl($field.val())) {
                    SPBAdmin.showFieldError($field, 'Please enter a valid URL');
                }
            });
        },

        /**
         * Initialize notices
         */
        initNotices: function() {
            // Auto-dismiss notices
            $('.spb-notice[data-auto-dismiss]').each(function() {
                var $notice = $(this);
                var delay = parseInt($notice.data('auto-dismiss')) || 5000;
                
                setTimeout(function() {
                    $notice.fadeOut();
                }, delay);
            });
        },

        /**
         * Show notice
         */
        showNotice: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;
            
            var $notice = $('<div class="spb-notice spb-notice-' + type + '">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' +
                '</div>');
            
            $('.spb-admin-header').after($notice);
            
            if (duration > 0) {
                setTimeout(function() {
                    $notice.fadeOut();
                }, duration);
            }
        },

        /**
         * Dismiss notice
         */
        dismissNotice: function() {
            $(this).closest('.spb-notice').fadeOut();
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            if ($.fn.tooltip) {
                $('.spb-tooltip').tooltip();
            }
        },

        /**
         * Handle AJAX success
         */
        handleAjaxSuccess: function(response, $form) {
            if (response.success) {
                this.showNotice(response.data.message || 'Operation completed successfully', 'success');
                
                // Trigger success event
                $(document).trigger('spb:ajax-success', [response, $form]);
            } else {
                this.showNotice(response.data || 'Operation failed', 'error');
            }
        },

        /**
         * Handle AJAX errors
         */
        handleAjaxError: function(xhr, error) {
            var message = 'An error occurred';
            
            if (xhr.responseJSON && xhr.responseJSON.data) {
                message = xhr.responseJSON.data;
            } else if (xhr.responseText) {
                message = xhr.responseText;
            } else if (error) {
                message = error;
            }
            
            this.showNotice(message, 'error');
            
            // Log error for debugging
            if (console && console.error) {
                console.error('SPB AJAX Error:', xhr, error);
            }
        },

        /**
         * Utility: Debounce function
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
         * Utility: Throttle function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        }
    };

    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        SPBAdmin.init();
    });

    /**
     * Make SPBAdmin globally available
     */
    window.SPBAdmin = SPBAdmin;

})(jQuery);
