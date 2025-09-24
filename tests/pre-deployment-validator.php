<?php
/**
 * Pre-Deployment Validator for Smart Page Builder
 *
 * Comprehensive testing tool to validate plugin before production deployment.
 * This can be run as a Cline extension or standalone testing tool.
 *
 * @package Smart_Page_Builder
 * @subpackage Tests
 * @since 3.1.7
 */

if (!defined('ABSPATH')) {
    // Allow running from command line for testing
    if (php_sapi_name() === 'cli') {
        define('ABSPATH', dirname(__FILE__) . '/../../../../');
        define('WP_DEBUG', true);
    } else {
        exit;
    }
}

/**
 * Pre-Deployment Validator Class
 * 
 * Comprehensive validation system to catch errors before production deployment
 */
class SPB_Pre_Deployment_Validator {
    
    private $errors = array();
    private $warnings = array();
    private $passed = array();
    private $plugin_dir;
    
    public function __construct() {
        $this->plugin_dir = dirname(__FILE__, 2);
    }
    
    /**
     * Run all validation tests
     */
    public function run_all_tests() {
        echo "🚀 Smart Page Builder Pre-Deployment Validator v3.1.7\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        $this->test_file_existence();
        $this->test_php_syntax();
        $this->test_class_definitions();
        $this->test_wordpress_functions();
        $this->test_admin_partials();
        $this->test_css_js_assets();
        $this->test_database_schema();
        $this->test_security_compliance();
        $this->test_performance_requirements();
        
        $this->display_results();
        
        return empty($this->errors);
    }
    
    /**
     * Test 1: File Existence
     */
    private function test_file_existence() {
        echo "📁 Testing File Existence...\n";
        
        $required_files = array(
            // Core files
            'smart-page-builder.php',
            'readme.txt',
            'CHANGELOG.md',
            
            // Includes
            'includes/class-smart-page-builder.php',
            'includes/class-activator.php',
            'includes/class-deactivator.php',
            'includes/class-loader.php',
            'includes/class-i18n.php',
            
            // Admin files
            'admin/class-admin.php',
            'admin/css/smart-page-builder-admin.css',
            'admin/js/smart-page-builder-admin.js',
            'admin/css/analytics-dashboard.css',
            'admin/js/analytics-dashboard.js',
            
            // Admin partials
            'admin/partials/smart-page-builder-admin-display.php',
            'admin/partials/smart-page-builder-admin-approval.php',
            'admin/partials/smart-page-builder-admin-personalization.php',
            'admin/partials/smart-page-builder-admin-wpengine.php',
            'admin/partials/smart-page-builder-admin-approval-queue.php',
            
            // Phase 2 components
            'includes/class-ai-page-generation-engine.php',
            'includes/class-template-engine.php',
            'includes/class-quality-assessment-engine.php',
            'includes/class-content-approval-system.php',
            
            // Component generators
            'includes/component-generators/abstract-component-generator.php',
            'includes/component-generators/class-hero-generator.php',
            'includes/component-generators/class-article-generator.php',
            'includes/component-generators/class-cta-generator.php',
            
            // Templates
            'templates/search-page-templates/commercial.php',
            
            // Public
            'public/class-public.php',
            
            // Tests
            'tests/class-plugin-validator.php'
        );
        
        foreach ($required_files as $file) {
            $full_path = $this->plugin_dir . '/' . $file;
            if (file_exists($full_path)) {
                $this->passed[] = "✅ File exists: {$file}";
            } else {
                $this->errors[] = "❌ Missing file: {$file}";
            }
        }
    }
    
    /**
     * Test 2: PHP Syntax Validation
     */
    private function test_php_syntax() {
        echo "🔍 Testing PHP Syntax...\n";
        
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $output = array();
            $return_var = 0;
            
            exec("php -l " . escapeshellarg($file), $output, $return_var);
            
            if ($return_var === 0) {
                $this->passed[] = "✅ Syntax OK: " . basename($file);
            } else {
                $this->errors[] = "❌ Syntax Error in " . basename($file) . ": " . implode(' ', $output);
            }
        }
    }
    
    /**
     * Test 3: Class Definitions
     */
    private function test_class_definitions() {
        echo "🏗️ Testing Class Definitions...\n";
        
        $required_classes = array(
            'Smart_Page_Builder',
            'Smart_Page_Builder_Activator',
            'Smart_Page_Builder_Deactivator',
            'Smart_Page_Builder_Loader',
            'Smart_Page_Builder_Admin',
            'Smart_Page_Builder_Public',
            'SPB_AI_Page_Generation_Engine',
            'SPB_Template_Engine',
            'SPB_Quality_Assessment_Engine',
            'SPB_Content_Approval_System',
            'SPB_Abstract_Component_Generator',
            'SPB_Hero_Generator',
            'SPB_Article_Generator',
            'SPB_CTA_Generator'
        );
        
        foreach ($required_classes as $class_name) {
            $file_found = false;
            $php_files = $this->get_php_files();
            
            foreach ($php_files as $file) {
                $content = file_get_contents($file);
                if (preg_match('/class\s+' . preg_quote($class_name, '/') . '\s*[{]/', $content)) {
                    $this->passed[] = "✅ Class found: {$class_name}";
                    $file_found = true;
                    break;
                }
            }
            
            if (!$file_found) {
                $this->errors[] = "❌ Missing class: {$class_name}";
            }
        }
    }
    
    /**
     * Test 4: WordPress Functions Usage
     */
    private function test_wordpress_functions() {
        echo "🔌 Testing WordPress Functions...\n";
        
        $admin_partials = glob($this->plugin_dir . '/admin/partials/*.php');
        
        foreach ($admin_partials as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);
            
            // Check for proper WordPress function usage
            if (strpos($content, 'esc_html') === false && strpos($content, 'esc_attr') === false) {
                $this->warnings[] = "⚠️ {$filename}: No escaping functions found";
            } else {
                $this->passed[] = "✅ {$filename}: Proper escaping functions used";
            }
            
            // Check for ABSPATH protection
            if (strpos($content, 'ABSPATH') === false) {
                $this->errors[] = "❌ {$filename}: Missing ABSPATH protection";
            } else {
                $this->passed[] = "✅ {$filename}: ABSPATH protection present";
            }
        }
    }
    
    /**
     * Test 5: Admin Partials Validation
     */
    private function test_admin_partials() {
        echo "🎛️ Testing Admin Partials...\n";
        
        $partials = array(
            'smart-page-builder-admin-display.php',
            'smart-page-builder-admin-approval.php',
            'smart-page-builder-admin-personalization.php',
            'smart-page-builder-admin-wpengine.php',
            'smart-page-builder-admin-approval-queue.php'
        );
        
        foreach ($partials as $partial) {
            $file = $this->plugin_dir . '/admin/partials/' . $partial;
            
            if (!file_exists($file)) {
                $this->errors[] = "❌ Missing admin partial: {$partial}";
                continue;
            }
            
            $content = file_get_contents($file);
            
            // Check for basic structure
            if (strpos($content, '<?php') === false) {
                $this->errors[] = "❌ {$partial}: Missing PHP opening tag";
            }
            
            // Check for WordPress integration
            if (strpos($content, 'esc_html') !== false || strpos($content, 'esc_attr') !== false) {
                $this->passed[] = "✅ {$partial}: WordPress integration present";
            } else {
                $this->warnings[] = "⚠️ {$partial}: Limited WordPress integration";
            }
        }
    }
    
    /**
     * Test 6: CSS/JS Assets
     */
    private function test_css_js_assets() {
        echo "🎨 Testing CSS/JS Assets...\n";
        
        $assets = array(
            'admin/css/smart-page-builder-admin.css',
            'admin/js/smart-page-builder-admin.js',
            'admin/css/analytics-dashboard.css',
            'admin/js/analytics-dashboard.js'
        );
        
        foreach ($assets as $asset) {
            $file = $this->plugin_dir . '/' . $asset;
            
            if (file_exists($file)) {
                $size = filesize($file);
                if ($size > 0) {
                    $this->passed[] = "✅ Asset exists and has content: {$asset} ({$size} bytes)";
                } else {
                    $this->warnings[] = "⚠️ Asset exists but is empty: {$asset}";
                }
            } else {
                $this->errors[] = "❌ Missing asset: {$asset}";
            }
        }
    }
    
    /**
     * Test 7: Database Schema
     */
    private function test_database_schema() {
        echo "🗄️ Testing Database Schema...\n";
        
        $activator_file = $this->plugin_dir . '/includes/class-activator.php';
        
        if (file_exists($activator_file)) {
            $content = file_get_contents($activator_file);
            
            if (strpos($content, 'upgrade()') !== false) {
                $this->passed[] = "✅ Activator has upgrade() method";
            } else {
                $this->errors[] = "❌ Activator missing upgrade() method";
            }
            
            if (strpos($content, 'CREATE TABLE') !== false || strpos($content, 'dbDelta') !== false) {
                $this->passed[] = "✅ Database schema creation present";
            } else {
                $this->warnings[] = "⚠️ No database schema creation found";
            }
        } else {
            $this->errors[] = "❌ Activator file not found";
        }
    }
    
    /**
     * Test 8: Security Compliance
     */
    private function test_security_compliance() {
        echo "🔒 Testing Security Compliance...\n";
        
        $php_files = $this->get_php_files();
        $security_issues = 0;
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            $filename = basename($file);
            
            // Check for direct access protection
            if (strpos($content, 'ABSPATH') === false && strpos($content, 'defined(') === false) {
                $this->warnings[] = "⚠️ {$filename}: No direct access protection";
                $security_issues++;
            }
            
            // Check for SQL injection protection
            if (strpos($content, '$wpdb->prepare') !== false) {
                $this->passed[] = "✅ {$filename}: Uses prepared statements";
            }
            
            // Check for XSS protection
            if (strpos($content, 'esc_') !== false) {
                $this->passed[] = "✅ {$filename}: Uses escaping functions";
            }
        }
        
        if ($security_issues === 0) {
            $this->passed[] = "✅ No major security issues found";
        }
    }
    
    /**
     * Test 9: Performance Requirements
     */
    private function test_performance_requirements() {
        echo "⚡ Testing Performance Requirements...\n";
        
        $main_file = $this->plugin_dir . '/smart-page-builder.php';
        
        if (file_exists($main_file)) {
            $content = file_get_contents($main_file);
            
            // Check for autoloading
            if (strpos($content, 'spl_autoload_register') !== false || strpos($content, 'require_once') !== false) {
                $this->passed[] = "✅ Autoloading mechanism present";
            } else {
                $this->warnings[] = "⚠️ No clear autoloading mechanism";
            }
            
            // Check file size
            $size = filesize($main_file);
            if ($size < 50000) { // 50KB limit
                $this->passed[] = "✅ Main file size acceptable: {$size} bytes";
            } else {
                $this->warnings[] = "⚠️ Main file is large: {$size} bytes";
            }
        }
    }
    
    /**
     * Get all PHP files in the plugin
     */
    private function get_php_files() {
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->plugin_dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Display validation results
     */
    private function display_results() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 VALIDATION RESULTS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // Summary
        $total_tests = count($this->passed) + count($this->warnings) + count($this->errors);
        $success_rate = $total_tests > 0 ? round((count($this->passed) / $total_tests) * 100, 1) : 0;
        
        echo "📈 SUMMARY:\n";
        echo "  ✅ Passed: " . count($this->passed) . "\n";
        echo "  ⚠️ Warnings: " . count($this->warnings) . "\n";
        echo "  ❌ Errors: " . count($this->errors) . "\n";
        echo "  📊 Success Rate: {$success_rate}%\n\n";
        
        // Errors (Critical)
        if (!empty($this->errors)) {
            echo "🚨 CRITICAL ERRORS (Must Fix Before Deployment):\n";
            foreach ($this->errors as $error) {
                echo "  {$error}\n";
            }
            echo "\n";
        }
        
        // Warnings (Recommended)
        if (!empty($this->warnings)) {
            echo "⚠️ WARNINGS (Recommended to Fix):\n";
            foreach ($this->warnings as $warning) {
                echo "  {$warning}\n";
            }
            echo "\n";
        }
        
        // Deployment Recommendation
        if (empty($this->errors)) {
            echo "🎉 DEPLOYMENT STATUS: ✅ READY FOR PRODUCTION\n";
            echo "   All critical tests passed. Plugin is safe to deploy.\n";
            if (!empty($this->warnings)) {
                echo "   Consider addressing warnings for optimal performance.\n";
            }
        } else {
            echo "🛑 DEPLOYMENT STATUS: ❌ NOT READY\n";
            echo "   Critical errors found. Fix all errors before deployment.\n";
        }
        
        echo "\n" . str_repeat("=", 60) . "\n";
    }
    
    /**
     * Generate detailed report
     */
    public function generate_report() {
        $report = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'plugin_version' => '3.1.7',
            'total_tests' => count($this->passed) + count($this->warnings) + count($this->errors),
            'passed' => count($this->passed),
            'warnings' => count($this->warnings),
            'errors' => count($this->errors),
            'ready_for_deployment' => empty($this->errors),
            'details' => array(
                'passed' => $this->passed,
                'warnings' => $this->warnings,
                'errors' => $this->errors
            )
        );
        
        return $report;
    }
}

// CLI Usage
if (php_sapi_name() === 'cli') {
    $validator = new SPB_Pre_Deployment_Validator();
    $success = $validator->run_all_tests();
    exit($success ? 0 : 1);
}

// WordPress Integration
if (defined('ABSPATH')) {
    // Add admin menu for validation
    add_action('admin_menu', function() {
        add_submenu_page(
            'smart-page-builder',
            'Pre-Deployment Validator',
            'Validator',
            'manage_options',
            'spb-validator',
            function() {
                if (isset($_GET['run_validation'])) {
                    echo '<div class="wrap">';
                    echo '<h1>Smart Page Builder - Pre-Deployment Validator</h1>';
                    echo '<pre style="background: #f1f1f1; padding: 20px; border-radius: 5px;">';
                    
                    $validator = new SPB_Pre_Deployment_Validator();
                    $validator->run_all_tests();
                    
                    echo '</pre>';
                    echo '</div>';
                } else {
                    echo '<div class="wrap">';
                    echo '<h1>Smart Page Builder - Pre-Deployment Validator</h1>';
                    echo '<p>This tool validates your plugin before deployment to catch potential issues.</p>';
                    echo '<a href="?page=spb-validator&run_validation=1" class="button button-primary">Run Validation</a>';
                    echo '</div>';
                }
            }
        );
    });
}
