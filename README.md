# TextMorpher - WordPress Plugin

A powerful WordPress plugin for persistent text translation and database replacement, specifically designed for Woodmart/WooCommerce themes and plugins.

## 🎯 Key Features

- **Persistent i18n Translations**: Create custom .mo files without runtime filters
- **Database Text Replacement**: Safely replace text in database content with backup/restore
- **MU Plugin Integration**: Automatic loading of custom translation files
- **RTL Support**: Full right-to-left language support
- **Modern Admin Interface**: 5-tab admin panel with React-like components
- **REST API**: Complete API for external integrations
- **Security**: Nonce verification and capability checks

## 🚀 Quick Start

### Installation

1. Upload the plugin to `/wp-content/plugins/textmorpher/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'TextMorpher' in the admin menu

### Basic Usage

1. **Add Translation Overrides**: Go to the "Custom Dictionary" tab
2. **Build Translation Files**: Use the "Build & Load" tab to generate .mo files
3. **Database Replacement**: Use the "Database Replacement" tab for safe text changes

## 📁 Plugin Structure

```
textmorpher/
├── textmorpher.php          # Main plugin file
├── includes/                         # Core classes
│   ├── Plugin.php                   # Main plugin class
│   ├── Database.php                 # Database operations
│   ├── Builder.php                  # .po/.mo file generation
│   ├── DatabaseReplacer.php        # Database text replacement
│   └── REST/                        # REST API
│       └── RESTController.php       # API endpoints
├── admin/                           # Admin interface
│   └── Admin.php                    # Admin class
├── assets/                          # Frontend assets
│   ├── css/admin.css               # Admin styles
│   └── js/admin.js                 # Admin JavaScript
├── mu-plugins/                      # Must-use plugins
│   └── textmorpher-l10n-loader.php # Translation loader
├── languages/                       # Plugin translations
├── composer.json                    # Dependencies
└── README.md                        # This file
```

## 🔧 Configuration

### Default Settings

- **Sample translation locale**: `fa_IR` (Persian example; plugin UI is English by default)
- **Required Capability**: `manage_options`
- **Auto-build**: Disabled by default
- **Backup Retention**: 30 days

### Customization

The plugin automatically creates:
- Custom languages directory: `wp-content/languages/custom/`
- MU plugin: `wp-content/mu-plugins/textmorpher-l10n-loader.php`
- Database tables with prefix

## 📚 API Reference

### REST Endpoints

Base URL: `/wp-json/textmorpher/v1/`

#### Overrides
- `GET /overrides` - List overrides with filters
- `POST /overrides` - Create new override
- `PUT /overrides/{id}` - Update override
- `DELETE /overrides/{id}` - Delete override
- `PATCH /overrides/{id}/status` - Update status

#### Build & Load
- `POST /build` - Build translation files
- `POST /mu-plugin/install` - Install MU plugin

#### Database Replacement
- `POST /db-replace/dry-run` - Preview changes
- `POST /db-replace/apply` - Apply changes
- `POST /db-replace/restore` - Restore from backup

#### Jobs & Logs
- `GET /jobs` - List operation logs
- `GET /jobs/{id}` - Get job details

### Authentication

All endpoints require:
- WordPress authentication
- `manage_options` capability
- Valid nonce

## 🗄️ Database Schema

### Tables

#### `{prefix}textmorpher_overrides`
- `id` - Primary key
- `domain` - Text domain
- `locale` - Language locale
- `context` - Translation context
- `original_text` - Original text
- `replacement` - Replacement text
- `status` - Active/inactive
- `original_hash` - MD5 hash for uniqueness
- `updated_at` - Last update timestamp
- `updated_by` - User ID

#### `{prefix}textmorpher_jobs`
- `id` - Primary key
- `type` - Job type (build/replace/restore)
- `payload` - JSON job data
- `stats` - JSON statistics
- `created_at` - Creation timestamp
- `created_by` - User ID

#### `{prefix}textmorpher_backups`
- `id` - Primary key
- `job_id` - Reference to job
- `table_name` - Database table
- `record_id` - Record ID
- `original_data` - JSON backup data
- `created_at` - Creation timestamp

## 🔒 Security Features

- **Nonce Verification**: All forms and AJAX requests
- **Capability Checks**: `manage_options` required
- **Input Sanitization**: All user inputs sanitized
- **SQL Prepared Statements**: Database queries protected
- **File Access Control**: .htaccess protection for custom languages

## 🌐 Internationalization

### Supported Locales
- Persian (fa_IR) - Default
- English (en_US)
- Any WordPress locale

### RTL Support
- Full right-to-left language support
- Responsive design for mobile devices
- CSS classes for RTL layout

## 🚨 Important Notes

### Performance
- **No Runtime Filters**: Uses file-based translations for maximum performance
- **Efficient Database Queries**: Indexed tables for fast searches
- **Minimal Memory Usage**: Only loads necessary components

### Compatibility
- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+
- **Themes**: Compatible with all themes
- **Plugins**: No conflicts with other plugins

### Limitations
- Requires write access to `wp-content/` directory
- MU plugins directory must be writable
- Some shared hosting environments may have restrictions

## 🛠️ Development

### Requirements
- PHP 7.4+
- Composer
- WordPress development environment

### Setup
```bash
# Clone repository
git clone https://github.com/your-username/textmorpher.git

# Install dependencies
composer install

# Run tests
composer test

# Code style check
composer phpcs
```

### Testing
```bash
# Run PHPUnit tests
composer test

# Run with coverage
composer test -- --coverage-html coverage/
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Core translation override functionality
- Database replacement tools
- MU plugin integration
- REST API endpoints
- Admin interface

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🆘 Support

- **Documentation**: [Plugin Wiki](https://github.com/your-username/textmorpher/wiki)
- **Issues**: [GitHub Issues](https://github.com/your-username/textmorpher/issues)
- **Discussions**: [GitHub Discussions](https://github.com/your-username/textmorpher/discussions)

## 🙏 Acknowledgments

- WordPress community for the excellent platform
- Contributors and testers
- Open source projects that inspired this work

---

**Made with ❤️ for the WordPress community**
