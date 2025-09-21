# Smart Page Builder - Testing Guide

## Overview

This guide provides comprehensive instructions for testing the Smart Page Builder WordPress plugin, with a focus on Phase 2 automatic activation and analytics dashboard functionality.

## Testing Status: ✅ COMPLETE

**Implementation Date**: September 21, 2025  
**Version**: 2.0.0  
**Coverage**: Unit Tests, Integration Tests, Browser Compatibility, Performance Testing

## Test Suite Structure

```
smart-page-builder/tests/
├── unit/                           # Unit tests for individual components
│   ├── test-smart-page-builder.php    # Phase 1 core functionality
│   ├── test-phase-2-activation.php    # Phase 2 automatic activation
│   ├── test-analytics-manager.php     # Analytics manager functionality
│   └── test-ab-testing.php           # A/B testing framework
├── integration/                    # Integration tests
│   └── test-fresh-installation.php   # Fresh WordPress installation testing
├── browser/                        # Browser compatibility tests
│   └── test-analytics-dashboard.html # Analytics dashboard browser testing
└── bootstrap.php                   # Test environment setup
```

## Prerequisites

### System Requirements
- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **PHPUnit**: 9.0 or higher
- **Modern Browser**: Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

### Development Environment Setup
```bash
# 1. Install dependencies
composer install
npm install

# 2. Set up WordPress test environment
# Follow WordPress testing documentation for your environment

# 3. Configure test database
# Update wp-tests-config.php with test database credentials
```

## Running Tests

### 1. Unit Tests

**Run all unit tests:**
```bash
cd smart-page-builder
composer test
# or
vendor/bin/phpunit tests/unit/
```

**Run specific test files:**
```bash
# Phase 2 activation tests
vendor/bin/phpunit tests/unit/test-phase-2-activation.php

# Analytics manager tests
vendor/bin/phpunit tests/unit/test-analytics-manager.php

# A/B testing framework tests
vendor/bin/phpunit tests/unit/test-ab-testing.php
```

**Run with coverage:**
```bash
vendor/bin/phpunit --coverage-html coverage/ tests/unit/
```

### 2. Integration Tests

**Fresh installation testing:**
```bash
vendor/bin/phpunit tests/integration/test-fresh-installation.php
```

**Manual integration testing:**
1. Set up fresh WordPress installation
2. Install Smart Page Builder plugin
3. Activate plugin
4. Verify Phase 2 features are automatically available
5. Test analytics dashboard functionality

### 3. Browser Compatibility Tests

**Automated browser testing:**
1. Open `tests/browser/test-analytics-dashboard.html` in target browsers
2. Review compatibility test results
3. Test analytics dashboard functionality
4. Verify responsive design on mobile devices

**Supported browsers:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari 14+
- Chrome Mobile 90+

**Manual browser testing checklist:**
- [ ] Analytics dashboard loads without errors
- [ ] Charts render correctly with Chart.js
- [ ] Real-time updates work (30-second intervals)
- [ ] Export functionality (CSV/JSON) works
- [ ] Mobile responsiveness confirmed
- [ ] Accessibility features work (keyboard navigation, screen readers)

### 4. Performance Testing

**Dashboard load time testing:**
```bash
# Use browser developer tools to measure:
# - Initial page load time (target: <500ms)
# - Chart rendering time
# - AJAX response times
# - Memory usage during operation
```

**Database performance testing:**
```bash
# Test database operations:
# - Table creation during activation
# - Analytics data insertion
# - Query performance with large datasets
# - Cache effectiveness
```

## Test Categories

### 1. Phase 2 Automatic Activation Tests

**File**: `tests/unit/test-phase-2-activation.php`

**Test Coverage:**
- ✅ Plugin activation creates Phase 2 options
- ✅ Phase 2 database tables are created automatically
- ✅ Phase 2 availability detection works correctly
- ✅ Phase 2 can be disabled via admin setting
- ✅ Graceful fallback when Phase 2 tables don't exist
- ✅ Upgrade from Phase 1 to Phase 2 works seamlessly
- ✅ Admin class Phase 2 detection functions
- ✅ Default options are set during activation
- ✅ User capabilities are added correctly
- ✅ Cron jobs are scheduled properly

### 2. A/B Testing Framework Tests

**File**: `tests/unit/test-ab-testing.php`

**Test Coverage:**
- ✅ A/B testing initialization
- ✅ Creating new A/B tests
- ✅ Creating test variants
- ✅ Traffic allocation algorithms
- ✅ Recording test results
- ✅ Statistical significance calculation
- ✅ Test management (stop, archive)
- ✅ Variant assignment consistency
- ✅ Active test retrieval

### 3. Analytics Manager Tests

**File**: `tests/unit/test-analytics-manager.php`

**Test Coverage:**
- ✅ Analytics manager initialization
- ✅ Page view tracking
- ✅ Search query tracking
- ✅ Content generation tracking
- ✅ Dashboard analytics data retrieval
- ✅ Analytics data caching
- ✅ Content approval/rejection tracking
- ✅ Opportunity score calculation
- ✅ Client IP detection
- ✅ Session ID generation

### 4. Fresh Installation Integration Tests

**File**: `tests/integration/test-fresh-installation.php`

**Test Coverage:**
- ✅ Fresh plugin installation and activation
- ✅ Plugin activation with existing WordPress content
- ✅ WordPress version compatibility
- ✅ PHP version compatibility
- ✅ Database permissions and table creation
- ✅ Memory usage during activation
- ✅ Activation performance timing
- ✅ Multisite compatibility
- ✅ Deactivation/reactivation cycle

### 5. Browser Compatibility Tests

**File**: `tests/browser/test-analytics-dashboard.html`

**Test Coverage:**
- ✅ ES6 JavaScript support
- ✅ Fetch API availability
- ✅ Promise support
- ✅ Local Storage functionality
- ✅ CSS Grid and Flexbox support
- ✅ Chart.js library loading
- ✅ Canvas support for charts
- ✅ JSON parsing support
- ✅ AJAX/XMLHttpRequest support
- ✅ Performance metrics (DOM ready, page load, memory)

## Test Data Generation

### Sample Analytics Data
```php
// Generate sample analytics data for testing
$sample_data = array(
    'page_views' => rand(100, 1000),
    'content_generated' => rand(10, 50),
    'approval_rate' => rand(60, 100),
    'avg_confidence' => rand(70, 100),
    'search_queries' => array(
        'how to fix leaky faucet',
        'best power drill for home use',
        'safety tips for electrical work'
    )
);
```

### A/B Test Scenarios
```php
// Sample A/B test configuration
$ab_test_config = array(
    'name' => 'Template Comparison Test',
    'test_type' => 'template',
    'variants' => array(
        array('name' => 'Control', 'traffic' => 50, 'is_control' => true),
        array('name' => 'Variant A', 'traffic' => 50, 'is_control' => false)
    ),
    'target_sample_size' => 100,
    'confidence_level' => 95.0
);
```

## Performance Benchmarks

### Target Performance Metrics
- **Plugin Activation**: < 5 seconds
- **Dashboard Load Time**: < 500ms
- **Chart Rendering**: < 200ms
- **AJAX Response Time**: < 100ms
- **Memory Usage**: < 10MB during activation
- **Database Queries**: < 50ms per query

### Performance Testing Tools
- **Browser DevTools**: Network and Performance tabs
- **WordPress Query Monitor**: Database query analysis
- **PHP Profiling**: Xdebug or Blackfire for detailed analysis
- **Load Testing**: Apache Bench (ab) for stress testing

## Continuous Integration

### GitHub Actions Workflow
```yaml
# .github/workflows/ci.yml
name: CI/CD Pipeline
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php-version: [8.0, 8.1, 8.2]
        wordpress-version: [6.0, 6.1, 6.2, latest]
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php-version }}
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: vendor/bin/phpunit
```

### Automated Testing Schedule
- **On Push**: Unit tests and basic integration tests
- **Daily**: Full test suite including browser compatibility
- **Weekly**: Performance regression testing
- **Monthly**: Security and accessibility audits

## Troubleshooting

### Common Test Issues

**1. WordPress Test Environment Setup**
```bash
# If tests fail to run, verify WordPress test environment:
echo $WP_TESTS_DIR
echo $WP_CORE_DIR

# Reinstall WordPress test suite if needed:
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

**2. Database Connection Issues**
```php
// Check wp-tests-config.php settings:
define('DB_NAME', 'wordpress_test');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
```

**3. Memory Limit Issues**
```php
// Increase memory limit in wp-tests-config.php:
ini_set('memory_limit', '512M');
```

**4. Browser Test Issues**
- Ensure Chart.js CDN is accessible
- Check browser console for JavaScript errors
- Verify local file access permissions
- Test with different browsers for compatibility

### Debug Mode Testing
```php
// Enable debug mode for testing:
define('SPB_DEBUG_MODE', true);
define('SPB_ENVIRONMENT', 'testing');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Test Results Documentation

### Test Report Template
```
# Test Execution Report

**Date**: [Date]
**Version**: [Plugin Version]
**Environment**: [Test Environment Details]

## Test Results Summary
- **Unit Tests**: [Pass/Fail Count]
- **Integration Tests**: [Pass/Fail Count]
- **Browser Tests**: [Browser Compatibility Results]
- **Performance Tests**: [Performance Metrics]

## Issues Found
- [Issue 1 Description]
- [Issue 2 Description]

## Recommendations
- [Recommendation 1]
- [Recommendation 2]
```

### Coverage Reports
- Generate HTML coverage reports with PHPUnit
- Aim for >90% code coverage on Phase 2 components
- Document any uncovered code and justification

## Best Practices

### Writing Tests
1. **Follow WordPress Testing Standards**: Use WP_UnitTestCase for WordPress-specific tests
2. **Test Isolation**: Each test should be independent and not rely on other tests
3. **Descriptive Names**: Use clear, descriptive test method names
4. **Setup/Teardown**: Properly clean up test data in tearDown() methods
5. **Mock External Dependencies**: Mock API calls and external services

### Test Maintenance
1. **Regular Updates**: Update tests when functionality changes
2. **Performance Monitoring**: Track test execution time and optimize slow tests
3. **Documentation**: Keep test documentation current with code changes
4. **Review Process**: Include test reviews in code review process

## Conclusion

This comprehensive testing suite ensures the Smart Page Builder plugin meets high standards for:
- **Functionality**: All features work as expected
- **Compatibility**: Works across supported WordPress/PHP versions and browsers
- **Performance**: Meets performance benchmarks
- **Reliability**: Handles edge cases and error conditions gracefully
- **User Experience**: Provides seamless automatic Phase 2 activation

Regular execution of these tests helps maintain plugin quality and catch regressions early in the development process.
