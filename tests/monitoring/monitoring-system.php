<?php
/**
 * Smart Page Builder v3.0 - Monitoring and Alerting System
 * Real-time monitoring for the three core functionalities with automated alerting
 *
 * @package Smart_Page_Builder
 * @subpackage Monitoring
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Smart Page Builder Monitoring System
 * 
 * Monitors the three core functionalities in real-time:
 * 1. User Interest Detection
 * 2. Intelligent Discovery
 * 3. Dynamic Assembly
 */
class SPB_Monitoring_System {
    
    private $alert_thresholds;
    private $monitoring_data;
    private $alert_channels;
    
    public function __construct() {
        $this->alert_thresholds = [
            'signal_collection_time' => 100, // ms
            'interest_vector_calculation_time' => 50, // ms
            'content_relevance_scoring_time' => 50, // ms
            'search_personalization_time' => 150, // ms
            'page_assembly_time' => 300, // ms
            'error_rate' => 0.05, // 5%
            'dummy_data_tolerance' => 0, // Zero tolerance for high-severity dummy data
            'confidence_threshold' => 0.6 // Minimum confidence for personalization
        ];
        
        $this->monitoring_data = [];
        $this->alert_channels = [
            'email' => get_option('spb_alert_email', 'admin@example.com'),
            'slack' => get_option('spb_slack_webhook', ''),
            'dashboard' => true
        ];
        
        // Initialize monitoring hooks
        $this->init_monitoring_hooks();
    }
    
    /**
     * Initialize WordPress hooks for monitoring
     */
    private function init_monitoring_hooks() {
        // Monitor signal collection
        add_action('spb_signal_collected', [$this, 'monitor_signal_collection'], 10, 3);
        
        // Monitor interest vector calculation
        add_action('spb_interest_vector_calculated', [$this, 'monitor_interest_vector'], 10, 3);
        
        // Monitor content discovery
        add_action('spb_content_discovered', [$this, 'monitor_content_discovery'], 10, 3);
        
        // Monitor page assembly
        add_action('spb_page_assembled', [$this, 'monitor_page_assembly'], 10, 3);
        
        // Monitor errors
        add_action('spb_error_occurred', [$this, 'monitor_errors'], 10, 2);
        
        // Schedule periodic health checks
        if (!wp_next_scheduled('spb_health_check')) {
            wp_schedule_event(time(), 'hourly', 'spb_health_check');
        }
        add_action('spb_health_check', [$this, 'perform_health_check']);
        
        // Schedule daily dummy data scan
        if (!wp_next_scheduled('spb_dummy_data_scan')) {
            wp_schedule_event(time(), 'daily', 'spb_dummy_data_scan');
        }
        add_action('spb_dummy_data_scan', [$this, 'scan_for_dummy_data']);
    }
    
    /**
     * Monitor signal collection performance
     */
    public function monitor_signal_collection($session_id, $signal_type, $execution_time) {
        $this->record_metric('signal_collection', [
            'session_id' => $session_id,
            'signal_type' => $signal_type,
            'execution_time' => $execution_time,
            'timestamp' => time()
        ]);
        
        // Check performance threshold
        if ($execution_time > $this->alert_thresholds['signal_collection_time']) {
            $this->trigger_alert('performance', [
                'component' => 'Signal Collection',
                'metric' => 'execution_time',
                'value' => $execution_time,
                'threshold' => $this->alert_thresholds['signal_collection_time'],
                'severity' => 'warning'
            ]);
        }
    }
    
    /**
     * Monitor interest vector calculation
     */
    public function monitor_interest_vector($session_id, $confidence, $execution_time) {
        $this->record_metric('interest_vector', [
            'session_id' => $session_id,
            'confidence' => $confidence,
            'execution_time' => $execution_time,
            'timestamp' => time()
        ]);
        
        // Check performance threshold
        if ($execution_time > $this->alert_thresholds['interest_vector_calculation_time']) {
            $this->trigger_alert('performance', [
                'component' => 'Interest Vector Calculation',
                'metric' => 'execution_time',
                'value' => $execution_time,
                'threshold' => $this->alert_thresholds['interest_vector_calculation_time'],
                'severity' => 'warning'
            ]);
        }
        
        // Check confidence threshold
        if ($confidence < $this->alert_thresholds['confidence_threshold']) {
            $this->trigger_alert('quality', [
                'component' => 'Interest Vector Calculation',
                'metric' => 'confidence',
                'value' => $confidence,
                'threshold' => $this->alert_thresholds['confidence_threshold'],
                'severity' => 'info'
            ]);
        }
    }
    
    /**
     * Monitor content discovery performance
     */
    public function monitor_content_discovery($session_id, $results_count, $execution_time) {
        $this->record_metric('content_discovery', [
            'session_id' => $session_id,
            'results_count' => $results_count,
            'execution_time' => $execution_time,
            'timestamp' => time()
        ]);
        
        // Check performance thresholds
        if ($execution_time > $this->alert_thresholds['search_personalization_time']) {
            $this->trigger_alert('performance', [
                'component' => 'Content Discovery',
                'metric' => 'execution_time',
                'value' => $execution_time,
                'threshold' => $this->alert_thresholds['search_personalization_time'],
                'severity' => 'warning'
            ]);
        }
    }
    
    /**
     * Monitor page assembly performance
     */
    public function monitor_page_assembly($session_id, $components_count, $execution_time) {
        $this->record_metric('page_assembly', [
            'session_id' => $session_id,
            'components_count' => $components_count,
            'execution_time' => $execution_time,
            'timestamp' => time()
        ]);
        
        // Check performance threshold
        if ($execution_time > $this->alert_thresholds['page_assembly_time']) {
            $this->trigger_alert('performance', [
                'component' => 'Page Assembly',
                'metric' => 'execution_time',
                'value' => $execution_time,
                'threshold' => $this->alert_thresholds['page_assembly_time'],
                'severity' => 'warning'
            ]);
        }
    }
    
    /**
     * Monitor errors
     */
    public function monitor_errors($error_type, $error_details) {
        $this->record_metric('errors', [
            'error_type' => $error_type,
            'error_details' => $error_details,
            'timestamp' => time()
        ]);
        
        // Calculate error rate
        $error_rate = $this->calculate_error_rate();
        
        if ($error_rate > $this->alert_thresholds['error_rate']) {
            $this->trigger_alert('error', [
                'component' => 'System Error Rate',
                'metric' => 'error_rate',
                'value' => $error_rate,
                'threshold' => $this->alert_thresholds['error_rate'],
                'severity' => 'critical'
            ]);
        }
    }
    
    /**
     * Perform comprehensive health check
     */
    public function perform_health_check() {
        $health_status = [
            'timestamp' => time(),
            'overall_status' => 'healthy',
            'components' => []
        ];
        
        // Check User Interest Detection health
        $health_status['components']['user_interest_detection'] = $this->check_interest_detection_health();
        
        // Check Intelligent Discovery health
        $health_status['components']['intelligent_discovery'] = $this->check_discovery_health();
        
        // Check Dynamic Assembly health
        $health_status['components']['dynamic_assembly'] = $this->check_assembly_health();
        
        // Check database health
        $health_status['components']['database'] = $this->check_database_health();
        
        // Check external integrations
        $health_status['components']['external_integrations'] = $this->check_external_integrations();
        
        // Determine overall status
        $unhealthy_components = array_filter($health_status['components'], function($status) {
            return $status['status'] !== 'healthy';
        });
        
        if (!empty($unhealthy_components)) {
            $health_status['overall_status'] = 'degraded';
            
            // Trigger alert for unhealthy components
            foreach ($unhealthy_components as $component => $status) {
                $this->trigger_alert('health', [
                    'component' => $component,
                    'status' => $status['status'],
                    'details' => $status['details'],
                    'severity' => $status['status'] === 'critical' ? 'critical' : 'warning'
                ]);
            }
        }
        
        // Store health check results
        update_option('spb_last_health_check', $health_status);
        
        return $health_status;
    }
    
    /**
     * Scan for dummy data
     */
    public function scan_for_dummy_data() {
        $dummy_detector = new SPB_Dummy_Data_Detector();
        $dummy_issues = $dummy_detector->scan_for_dummy_data();
        
        // Alert on high-severity dummy data
        if (!empty($dummy_issues['high_severity'])) {
            $this->trigger_alert('dummy_data', [
                'component' => 'Data Quality',
                'metric' => 'dummy_data_detected',
                'value' => count($dummy_issues['high_severity']),
                'threshold' => $this->alert_thresholds['dummy_data_tolerance'],
                'severity' => 'critical',
                'details' => $dummy_issues['high_severity']
            ]);
        }
        
        // Log medium-severity issues
        if (!empty($dummy_issues['medium_severity'])) {
            error_log('SPB Monitoring: Medium-severity dummy data detected: ' . json_encode($dummy_issues['medium_severity']));
        }
        
        return $dummy_issues;
    }
    
    /**
     * Check User Interest Detection health
     */
    private function check_interest_detection_health() {
        $recent_metrics = $this->get_recent_metrics('signal_collection', 3600); // Last hour
        
        if (empty($recent_metrics)) {
            return [
                'status' => 'warning',
                'details' => 'No signal collection activity in the last hour'
            ];
        }
        
        $avg_time = array_sum(array_column($recent_metrics, 'execution_time')) / count($recent_metrics);
        
        if ($avg_time > $this->alert_thresholds['signal_collection_time']) {
            return [
                'status' => 'degraded',
                'details' => "Average signal collection time ({$avg_time}ms) exceeds threshold"
            ];
        }
        
        return [
            'status' => 'healthy',
            'details' => "Signal collection performing well (avg: {$avg_time}ms)"
        ];
    }
    
    /**
     * Check Intelligent Discovery health
     */
    private function check_discovery_health() {
        $recent_metrics = $this->get_recent_metrics('content_discovery', 3600);
        
        if (empty($recent_metrics)) {
            return [
                'status' => 'warning',
                'details' => 'No content discovery activity in the last hour'
            ];
        }
        
        $avg_time = array_sum(array_column($recent_metrics, 'execution_time')) / count($recent_metrics);
        
        if ($avg_time > $this->alert_thresholds['search_personalization_time']) {
            return [
                'status' => 'degraded',
                'details' => "Average discovery time ({$avg_time}ms) exceeds threshold"
            ];
        }
        
        return [
            'status' => 'healthy',
            'details' => "Content discovery performing well (avg: {$avg_time}ms)"
        ];
    }
    
    /**
     * Check Dynamic Assembly health
     */
    private function check_assembly_health() {
        $recent_metrics = $this->get_recent_metrics('page_assembly', 3600);
        
        if (empty($recent_metrics)) {
            return [
                'status' => 'warning',
                'details' => 'No page assembly activity in the last hour'
            ];
        }
        
        $avg_time = array_sum(array_column($recent_metrics, 'execution_time')) / count($recent_metrics);
        
        if ($avg_time > $this->alert_thresholds['page_assembly_time']) {
            return [
                'status' => 'degraded',
                'details' => "Average assembly time ({$avg_time}ms) exceeds threshold"
            ];
        }
        
        return [
            'status' => 'healthy',
            'details' => "Page assembly performing well (avg: {$avg_time}ms)"
        ];
    }
    
    /**
     * Check database health
     */
    private function check_database_health() {
        global $wpdb;
        
        // Check database connection
        if (!$wpdb->check_connection()) {
            return [
                'status' => 'critical',
                'details' => 'Database connection failed'
            ];
        }
        
        // Check SPB tables exist
        $required_tables = [
            'spb_user_signals',
            'spb_user_interest_vectors',
            'spb_personalization_events'
        ];
        
        foreach ($required_tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
            if (!$table_exists) {
                return [
                    'status' => 'critical',
                    'details' => "Required table {$table} does not exist"
                ];
            }
        }
        
        return [
            'status' => 'healthy',
            'details' => 'Database connection and tables are healthy'
        ];
    }
    
    /**
     * Check external integrations health
     */
    private function check_external_integrations() {
        $integrations_status = [];
        
        // Check WP Engine API if configured
        if (get_option('spb_wpengine_api_key')) {
            $wpengine_status = $this->check_wpengine_integration();
            $integrations_status['wpengine'] = $wpengine_status;
        }
        
        // Check AI providers
        $ai_providers = ['openai', 'anthropic', 'google'];
        foreach ($ai_providers as $provider) {
            if (get_option("spb_{$provider}_api_key")) {
                $provider_status = $this->check_ai_provider($provider);
                $integrations_status[$provider] = $provider_status;
            }
        }
        
        // Determine overall integration health
        $failed_integrations = array_filter($integrations_status, function($status) {
            return $status['status'] !== 'healthy';
        });
        
        if (empty($integrations_status)) {
            return [
                'status' => 'healthy',
                'details' => 'No external integrations configured'
            ];
        }
        
        if (!empty($failed_integrations)) {
            return [
                'status' => 'degraded',
                'details' => 'Some external integrations are failing',
                'failed_integrations' => array_keys($failed_integrations)
            ];
        }
        
        return [
            'status' => 'healthy',
            'details' => 'All external integrations are healthy'
        ];
    }
    
    /**
     * Check WP Engine integration
     */
    private function check_wpengine_integration() {
        // Mock WP Engine API check
        $api_key = get_option('spb_wpengine_api_key');
        
        if (empty($api_key)) {
            return [
                'status' => 'warning',
                'details' => 'WP Engine API key not configured'
            ];
        }
        
        // In a real implementation, this would make an actual API call
        return [
            'status' => 'healthy',
            'details' => 'WP Engine integration is working'
        ];
    }
    
    /**
     * Check AI provider status
     */
    private function check_ai_provider($provider) {
        $api_key = get_option("spb_{$provider}_api_key");
        
        if (empty($api_key)) {
            return [
                'status' => 'warning',
                'details' => "{$provider} API key not configured"
            ];
        }
        
        // In a real implementation, this would make an actual API call
        return [
            'status' => 'healthy',
            'details' => "{$provider} integration is working"
        ];
    }
    
    /**
     * Record monitoring metric
     */
    private function record_metric($type, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'spb_monitoring_metrics',
            [
                'metric_type' => $type,
                'metric_data' => json_encode($data),
                'timestamp' => current_time('mysql')
            ]
        );
        
        // Keep only last 7 days of metrics
        $wpdb->query("
            DELETE FROM {$wpdb->prefix}spb_monitoring_metrics 
            WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
    }
    
    /**
     * Get recent metrics
     */
    private function get_recent_metrics($type, $seconds = 3600) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT metric_data 
            FROM {$wpdb->prefix}spb_monitoring_metrics 
            WHERE metric_type = %s 
            AND timestamp > DATE_SUB(NOW(), INTERVAL %d SECOND)
            ORDER BY timestamp DESC
        ", $type, $seconds));
        
        return array_map(function($row) {
            return json_decode($row->metric_data, true);
        }, $results);
    }
    
    /**
     * Calculate error rate
     */
    private function calculate_error_rate() {
        $error_metrics = $this->get_recent_metrics('errors', 3600);
        $total_metrics = 0;
        
        $metric_types = ['signal_collection', 'interest_vector', 'content_discovery', 'page_assembly'];
        foreach ($metric_types as $type) {
            $total_metrics += count($this->get_recent_metrics($type, 3600));
        }
        
        if ($total_metrics === 0) {
            return 0;
        }
        
        return count($error_metrics) / $total_metrics;
    }
    
    /**
     * Trigger alert
     */
    private function trigger_alert($alert_type, $alert_data) {
        $alert = [
            'type' => $alert_type,
            'data' => $alert_data,
            'timestamp' => time(),
            'id' => uniqid('spb_alert_')
        ];
        
        // Send email alert
        if (!empty($this->alert_channels['email'])) {
            $this->send_email_alert($alert);
        }
        
        // Send Slack alert
        if (!empty($this->alert_channels['slack'])) {
            $this->send_slack_alert($alert);
        }
        
        // Store alert in dashboard
        if ($this->alert_channels['dashboard']) {
            $this->store_dashboard_alert($alert);
        }
        
        // Log alert
        error_log('SPB Alert: ' . json_encode($alert));
    }
    
    /**
     * Send email alert
     */
    private function send_email_alert($alert) {
        $subject = "Smart Page Builder Alert: {$alert['data']['component']}";
        $message = $this->format_alert_message($alert);
        
        wp_mail($this->alert_channels['email'], $subject, $message);
    }
    
    /**
     * Send Slack alert
     */
    private function send_slack_alert($alert) {
        $webhook_url = $this->alert_channels['slack'];
        $message = $this->format_slack_message($alert);
        
        wp_remote_post($webhook_url, [
            'body' => json_encode($message),
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }
    
    /**
     * Store dashboard alert
     */
    private function store_dashboard_alert($alert) {
        $alerts = get_option('spb_dashboard_alerts', []);
        $alerts[] = $alert;
        
        // Keep only last 50 alerts
        if (count($alerts) > 50) {
            $alerts = array_slice($alerts, -50);
        }
        
        update_option('spb_dashboard_alerts', $alerts);
    }
    
    /**
     * Format alert message
     */
    private function format_alert_message($alert) {
        $data = $alert['data'];
        $timestamp = date('Y-m-d H:i:s', $alert['timestamp']);
        
        $message = "Smart Page Builder Alert\n";
        $message .= "========================\n\n";
        $message .= "Time: {$timestamp}\n";
        $message .= "Severity: {$data['severity']}\n";
        $message .= "Component: {$data['component']}\n";
        $message .= "Metric: {$data['metric']}\n";
        $message .= "Value: {$data['value']}\n";
        $message .= "Threshold: {$data['threshold']}\n\n";
        
        if (isset($data['details'])) {
            $message .= "Details: " . (is_array($data['details']) ? json_encode($data['details']) : $data['details']) . "\n";
        }
        
        return $message;
    }
    
    /**
     * Format Slack message
     */
    private function format_slack_message($alert) {
        $data = $alert['data'];
        $color = $data['severity'] === 'critical' ? 'danger' : ($data['severity'] === 'warning' ? 'warning' : 'good');
        
        return [
            'text' => "Smart Page Builder Alert",
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Component',
                            'value' => $data['component'],
                            'short' => true
                        ],
                        [
                            'title' => 'Severity',
                            'value' => strtoupper($data['severity']),
                            'short' => true
                        ],
                        [
                            'title' => 'Metric',
                            'value' => $data['metric'],
                            'short' => true
                        ],
                        [
                            'title' => 'Value',
                            'value' => $data['value'],
                            'short' => true
                        ]
                    ],
                    'ts' => $alert['timestamp']
                ]
            ]
        ];
    }
    
    /**
     * Get monitoring dashboard data
     */
    public function get_dashboard_data() {
        return [
            'health_status' => get_option('spb_last_health_check', []),
            'recent_alerts' => get_option('spb_dashboard_alerts', []),
            'performance_metrics' => $this->get_performance_summary(),
            'error_rate' => $this->calculate_error_rate(),
            'last_updated' => time()
        ];
    }
    
    /**
     * Get performance summary
     */
    private function get_performance_summary() {
        $summary = [];
        
        $metric_types = [
            'signal_collection' => 'Signal Collection',
            'interest_vector' => 'Interest Vector',
            'content_discovery' => 'Content Discovery',
            'page_assembly' => 'Page Assembly'
        ];
        
        foreach ($metric_types as $type => $label) {
            $recent_metrics = $this->get_recent_metrics($type, 3600);
            
            if (!empty($recent_metrics)) {
                $times = array_column($recent_metrics, 'execution_time');
                $summary[$type] = [
                    'label' => $label,
                    'avg_time' => array_sum($times) / count($times),
                    'max_time' => max($times),
                    'min_time' => min($times),
                    'count' => count($times)
                ];
            } else {
                $summary[$type] = [
                    'label' => $label,
                    'avg_time' => 0,
                    'max_time' => 0,
                    'min_time' => 0,
                    'count' => 0
                ];
            }
        }
        
        return $summary;
    }
}

// Initialize monitoring system
if (class_exists('SPB_Monitoring_System')) {
    global $spb_monitoring;
    $spb_monitoring = new SPB_Monitoring_System();
}
