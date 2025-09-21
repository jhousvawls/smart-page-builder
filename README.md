# Smart Page Builder

[![CI/CD Pipeline](https://github.com/jhousvawls/smart-page-builder/actions/workflows/ci.yml/badge.svg)](https://github.com/jhousvawls/smart-page-builder/actions/workflows/ci.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org)

AI-powered WordPress plugin that transforms user search queries into valuable, SEO-optimized content pages through intelligent content assembly and draft-first approval workflow.

## 🎯 Current Status

- **Phase 1**: ✅ Complete and Production Ready (v1.0.0)
- **Phase 2**: ✅ Complete and Production Ready (v2.0.0) - **Automatic Activation**

### Phase 2 Features (Automatically Available)
- **Advanced Analytics Dashboard**: Real-time metrics, content gap analysis, and A/B testing
- **Multi-AI Provider Support**: OpenAI, Anthropic Claude, and Google Gemini integration with intelligent fallback
- **Enhanced SEO Optimization**: Schema.org markup and intelligent internal linking
- **Professional Admin Interface**: Real-time charts, export functionality, and responsive design
- **AI Provider Management**: Automatic provider switching, cost tracking, and performance monitoring
- **Zero Configuration**: Phase 2 features activate automatically upon plugin installation

## 🚀 Features

### 🤖 AI-Powered Content Generation
- **Multi-Provider AI System**: OpenAI GPT-3.5/4, Anthropic Claude, and Google Gemini support
- **Intelligent Fallback**: Automatically tries all providers before failing
- **Content Type Optimization**: Specialized prompts for how-to, troubleshooting, tool recommendations, safety tips
- **Search Query Interception**: Automatically captures failed search queries
- **Content Assembly Engine**: Intelligently combines existing site content using TF-IDF analysis
- **Quality Analysis**: AI-powered content scoring and readability assessment
- **Cost Tracking**: Real-time API usage and cost monitoring across all providers

### 📝 Draft-First Approval Workflow
- **Human Oversight**: All AI-generated content starts as drafts requiring manual approval
- **Approval Queue**: Streamlined admin interface for reviewing generated content
- **Bulk Operations**: Approve or reject multiple pages simultaneously
- **Source Attribution**: Clear tracking of which existing posts were used

### 🎯 SEO Optimization
- **Smart URLs**: Clean `/smart-page/` URL structure for generated content
- **Schema Markup**: Automatic structured data for enhanced search results
- **Meta Generation**: Automated title and description optimization
- **Internal Linking**: Intelligent cross-referencing to existing content

### 📊 Analytics & Testing
- **Real-Time Dashboard**: Live provider performance metrics and analytics
- **A/B Testing Framework**: Test different templates, content algorithms, and AI providers
- **Provider Statistics**: Success rates, response times, and cost analysis
- **Performance Monitoring**: Real-time generation time and success metrics
- **Content Gap Analysis**: Identify trending searches and content opportunities
- **Export Functionality**: CSV and JSON data export for external analysis
- **Data Retention**: 30-day automatic cleanup for privacy compliance

### 🔒 Security & Performance
- **Encrypted API Keys**: Secure storage using WordPress salts
- **Input Validation**: Comprehensive sanitization and validation
- **Caching System**: Multi-layer caching for optimal performance
- **Rate Limiting**: Protection against API abuse

## 📋 Requirements

- **WordPress**: 6.0 or higher
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Memory**: 256MB minimum (512MB recommended)
- **SSL Certificate**: Required for API communications

## 🛠️ Installation

### Manual Installation

1. **Download the plugin**:
   ```bash
   git clone https://github.com/jhousvawls/smart-page-builder.git
   cd smart-page-builder
   ```

2. **Install dependencies**:
   ```bash
   composer install --no-dev
   npm install
   npm run build
   ```

3. **Upload to WordPress**:
   - Copy the plugin folder to `/wp-content/plugins/`
   - Activate through the WordPress admin interface

### Development Installation

1. **Clone and setup**:
   ```bash
   git clone https://github.com/jhousvawls/smart-page-builder.git
   cd smart-page-builder
   composer install
   npm install
   ```

2. **Development commands**:
   ```bash
   # Run tests
   composer test
   npm test
   
   # Code quality checks
   composer phpcs
   npm run lint
   
   # Build assets
   npm run build
   npm run dev  # Watch mode
   ```

## ⚙️ Configuration

### 1. AI Provider Setup

Navigate to **Smart Page Builder > Settings** in your WordPress admin:

#### Primary Provider (OpenAI) - Production Ready
- **OpenAI API Key**: Add your OpenAI API key for immediate content generation
- **Model Selection**: Choose between GPT-3.5-turbo (cost-effective) or GPT-4 (higher quality)
- **Content Types**: Enable/disable specific content types (how-to, troubleshooting, etc.)

#### Secondary Providers (Optional)
- **Anthropic Claude**: Add API key to enable Claude integration (excellent for troubleshooting)
- **Google Gemini**: Add API key to enable Gemini integration (great for tool recommendations)

#### Advanced Settings
- **Provider Priority**: Customize fallback order (default: OpenAI → Anthropic → Google)
- **Confidence Threshold**: Minimum AI confidence for content creation (default: 60%)
- **Cost Limits**: Set daily/monthly spending limits per provider

### 2. User Permissions

The plugin adds custom capabilities:
- `spb_manage_settings` - Configure plugin settings
- `spb_generate_content` - Generate new content
- `spb_approve_content` - Approve/reject generated content
- `spb_view_analytics` - Access analytics dashboard

### 3. Theme Integration

For optimal integration with the DIY Home Improvement theme:

```php
// Add to your theme's functions.php
add_action('diy_after_post_excerpt', 'spb_add_tool_recommendations');
add_action('diy_footer_smart_content', 'spb_add_safety_tips');

function spb_add_tool_recommendations() {
    if (spb_is_active()) {
        spb_render_placeholder('tool_recommendation', [
            'title' => 'Recommended Tools',
            'auto_load' => true
        ]);
    }
}
```

## 🔧 Usage

### Content Generation Workflow

1. **Search Interception**: Plugin automatically detects failed searches
2. **Content Analysis**: Analyzes existing site content for relevance
3. **Draft Creation**: Generates draft pages with confidence scores
4. **Admin Review**: Content appears in approval queue for review
5. **Publication**: Approved content goes live with SEO optimization

### Approval Queue

Access **Smart Page Builder > Approval Queue** to:
- Review generated content with confidence scores
- Preview full pages before approval
- Edit content before publishing
- Bulk approve/reject multiple pages
- View source attribution

### Analytics Dashboard

Monitor performance through **Smart Page Builder > Analytics**:
- Content generation success rates
- Search query trends and gaps
- A/B testing results
- Performance metrics

## 🧪 Testing

### Running Tests

```bash
# PHP Unit Tests
composer test

# JavaScript Tests
npm test

# Code Quality
composer phpcs
npm run lint

# All tests
composer test && npm test && composer phpcs && npm run lint
```

### Test Coverage

The plugin includes comprehensive test coverage:
- Unit tests for all core functionality
- Integration tests for API interactions
- Security validation tests
- Performance benchmarking

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guidelines](CONTRIBUTING.md) for details.

### Development Workflow

1. Fork the repository at https://github.com/jhousvawls/smart-page-builder
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes and add tests
4. Run the test suite: `composer test && npm test`
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Code Standards

- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Write comprehensive tests for new features
- Document all functions and classes
- Ensure security best practices

## 📚 Documentation

- [Architecture Overview](docs/SMART-PAGE-BUILDER-ARCHITECTURE.md)
- [API Reference](docs/SMART-PAGE-BUILDER-API.md)
- [Development Guide](docs/SMART-PAGE-BUILDER-DEVELOPMENT.md)
- [Security Guidelines](docs/MVP-SECURITY-GUIDELINES.md)
- [Troubleshooting](docs/DEVELOPER-TROUBLESHOOTING.md)

## 🔐 Security

Security is a top priority. The plugin implements:

- **Encrypted API Key Storage**: All API keys encrypted using WordPress salts
- **Input Validation**: Comprehensive sanitization of all user inputs
- **Output Escaping**: Proper escaping of all generated content
- **Capability Checks**: User permission verification for all actions
- **Rate Limiting**: Protection against API abuse
- **Audit Logging**: Security event tracking

### Reporting Security Issues

Please report security vulnerabilities privately to [security@example.com](mailto:security@example.com).

## 📄 License

This project is licensed under the GPL v2 or later - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- WordPress community for coding standards and best practices
- OpenAI for AI content generation capabilities
- Contributors and testers who help improve the plugin

## 📞 Support

- **Documentation**: [Plugin Documentation](docs/)
- **Issues**: [GitHub Issues](https://github.com/jhousvawls/smart-page-builder/issues)
- **Discussions**: [GitHub Discussions](https://github.com/jhousvawls/smart-page-builder/discussions)

---

**Smart Page Builder** - Transforming search queries into valuable content with AI-powered intelligence and human oversight.
