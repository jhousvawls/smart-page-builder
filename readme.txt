=== Smart Page Builder ===
Contributors: jhousvawls
Donate link: https://github.com/jhousvawls/smart-page-builder
Tags: ai, content-generation, seo, automation, wordpress
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WordPress plugin that transforms user search queries into valuable, SEO-optimized content pages through intelligent content assembly and draft-first approval workflow.

== Description ==

Smart Page Builder is an innovative WordPress plugin that leverages artificial intelligence to automatically generate high-quality, SEO-optimized content pages based on user search behavior. The plugin intelligently analyzes failed search queries and existing site content to create relevant, valuable pages that fill content gaps and improve user experience.

= Key Features =

* **AI-Powered Content Generation** - Automatically creates content from failed search queries
* **Draft-First Approval Workflow** - All generated content requires manual approval before publication
* **Content Assembly Engine** - Intelligently combines existing site content using TF-IDF analysis
* **SEO Optimization** - Automatic meta tags, schema markup, and URL optimization
* **Multiple Content Types** - Tool recommendations, safety tips, how-to guides, and more
* **A/B Testing Framework** - Test different templates and content algorithms
* **Performance Monitoring** - Real-time analytics and content gap analysis
* **Security First** - Encrypted API keys and comprehensive input validation

= How It Works =

1. **Search Interception** - Plugin detects when users search for content that doesn't exist
2. **Content Analysis** - AI analyzes existing site content for relevance and quality
3. **Draft Creation** - Generates draft pages with confidence scores for admin review
4. **Manual Approval** - Site administrators review and approve content before publication
5. **SEO Optimization** - Approved content is automatically optimized for search engines

= Content Types =

* **Tool Recommendations** - Suggests relevant tools for DIY projects
* **Safety Tips** - Provides safety guidelines and best practices
* **How-To Guides** - Step-by-step instructional content
* **Product Comparisons** - Side-by-side feature comparisons
* **Resource Hubs** - Curated collections of related content

= Theme Integration =

Designed specifically for the DIY Home Improvement theme with seamless integration hooks. Also compatible with most WordPress themes through the template system.

= Privacy & Data =

* 30-day automatic data retention policy
* GDPR compliant with user consent management
* Encrypted API key storage
* No personal data stored without consent

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Smart Page Builder"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate the plugin through the 'Plugins' menu in WordPress

= Configuration =

1. Navigate to Smart Page Builder > Settings
2. Add your AI provider API key (OpenAI recommended)
3. Configure content types and approval settings
4. Set user permissions and content preferences
5. Review the approval queue for generated content

== Frequently Asked Questions ==

= What AI providers are supported? =

Currently supports OpenAI GPT models with plans to add more providers. The plugin uses a provider abstraction layer for easy integration of additional AI services.

= Do I need an API key? =

Yes, you'll need an API key from a supported AI provider (like OpenAI) to generate content. The plugin securely encrypts and stores your API keys.

= Will this replace my existing content? =

No, the plugin only creates new content based on search queries that don't return relevant results. It never modifies or replaces existing content.

= How much does it cost to run? =

The plugin itself is free, but you'll pay for API usage from your chosen AI provider. Costs are typically very low due to intelligent caching and optimization.

= Is the generated content SEO-friendly? =

Yes, all generated content includes automatic SEO optimization including meta tags, schema markup, proper heading structure, and internal linking.

= Can I edit the generated content? =

Absolutely! All content goes through a draft-first approval workflow where you can review, edit, and approve content before it goes live.

= What happens to user data? =

The plugin follows a strict 30-day data retention policy and is GDPR compliant. Search queries and user behavior data are automatically purged after 30 days.

= Is it compatible with my theme? =

The plugin is designed for the DIY Home Improvement theme but includes a template system that works with most WordPress themes. Custom integration may be needed for optimal results.

== Screenshots ==

1. Admin approval queue showing generated content awaiting review
2. Content generation settings and API configuration
3. Analytics dashboard with performance metrics
4. Generated content example with source attribution
5. A/B testing interface for template optimization

== Changelog ==

= 1.0.0 =
* Initial release
* AI-powered content generation with draft-first approval workflow
* Complete database schema with custom tables
* Security framework with encrypted API key storage
* SEO optimization with schema markup
* A/B testing framework
* Performance monitoring and analytics
* WordPress 6.0+ compatibility
* PHP 8.0+ requirement

== Upgrade Notice ==

= 1.0.0 =
Initial release of Smart Page Builder. Requires WordPress 6.0+ and PHP 8.0+.

== Developer Information ==

= GitHub Repository =
https://github.com/jhousvawls/smart-page-builder

= Documentation =
Complete API documentation and development guides available in the GitHub repository.

= Contributing =
We welcome contributions! Please see our contributing guidelines on GitHub.

= Support =
For support, bug reports, or feature requests, please use the GitHub issues page.

== Technical Requirements ==

* WordPress 6.0 or higher
* PHP 8.0 or higher
* MySQL 5.7 or higher
* 256MB memory minimum (512MB recommended)
* SSL certificate for API communications
* AI provider API key (OpenAI recommended)

== Privacy Policy ==

This plugin may send data to external AI services for content generation. Please review your AI provider's privacy policy and ensure compliance with applicable privacy laws. The plugin includes features to help with GDPR compliance including data retention controls and user consent management.
