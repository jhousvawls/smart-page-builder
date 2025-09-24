<?php
/**
 * Mock WordPress functions and classes for testing
 * This file provides minimal WordPress functionality for testing purposes
 */

// Mock WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

if (!defined('WP_CLI')) {
    define('WP_CLI', false);
}

// Mock global $wpdb
global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new stdClass();
    $wpdb->prefix = 'wp_';
    $wpdb->insert = function($table, $data) { return true; };
    $wpdb->query = function($sql) { return true; };
    $wpdb->get_results = function($sql) { return []; };
    $wpdb->get_var = function($sql) { return 'wp_test_table'; };
    $wpdb->prepare = function($sql, ...$args) { return $sql; };
    $wpdb->check_connection = function() { return true; };
}

// Mock WordPress functions
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $options = [
            'spb_alert_email' => 'vscode@ahsodesigns.com',
            'spb_slack_webhook' => '',
            'spb_wpengine_api_key' => 'test_key',
            'spb_openai_api_key' => 'test_key',
            'spb_anthropic_api_key' => 'test_key',
            'spb_google_api_key' => 'test_key',
            'spb_last_health_check' => [],
            'spb_dashboard_alerts' => []
        ];
        return isset($options[$option]) ? $options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        return true;
    }
}

if (!function_exists('wp_next_scheduled')) {
    function wp_next_scheduled($hook) {
        return false;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook) {
        return true;
    }
}

if (!function_exists('current_time')) {
    function current_time($type) {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message) {
        echo "📧 Email sent to {$to}: {$subject}\n";
        return true;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args) {
        echo "📱 Slack notification sent\n";
        return ['response' => ['code' => 200]];
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "📝 Log: {$message}\n";
    }
}

// Mock WP_UnitTestCase
if (!class_exists('WP_UnitTestCase')) {
    class WP_UnitTestCase {
        public function setUp() {}
        public function tearDown() {}
        public function assertTrue($condition, $message = '') {
            return $condition;
        }
        public function assertFalse($condition, $message = '') {
            return !$condition;
        }
        public function assertEquals($expected, $actual, $message = '') {
            return $expected === $actual;
        }
    }
}

// Mock SPB_Dummy_Data_Detector class
if (!class_exists('SPB_Dummy_Data_Detector')) {
    class SPB_Dummy_Data_Detector {
        public function scan_for_dummy_data() {
            return [
                'high_severity' => [],
                'medium_severity' => [],
                'low_severity' => []
            ];
        }
    }
}

// Mock WP_CLI
if (!class_exists('WP_CLI')) {
    class WP_CLI {
        public static function add_command($name, $callback) {
            return true;
        }
        
        public static function success($message) {
            echo "✅ WP_CLI Success: {$message}\n";
        }
        
        public static function warning($message) {
            echo "⚠️ WP_CLI Warning: {$message}\n";
        }
        
        public static function error($message) {
            echo "❌ WP_CLI Error: {$message}\n";
        }
    }
}
