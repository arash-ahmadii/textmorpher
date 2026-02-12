# Installation Guide - TextMorpher

## Prerequisites

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher
- Write access to `wp-content/` directory
- Write access to `wp-content/mu-plugins/` directory

## Installation Steps

### 1. Upload Plugin Files

Upload the entire `textmorpher` folder to your WordPress plugins directory:
```
/wp-content/plugins/textmorpher/
```

### 2. Activate Plugin

1. Go to WordPress Admin → Plugins
2. Find "TextMorpher" in the list
3. Click "Activate"

### 3. Verify Installation

1. Check that the plugin appears in the admin menu
2. Navigate to "TextMorpher" in the admin menu
3. Verify all 5 tabs are visible and functional

## Post-Installation Setup

### 1. Install MU Plugin

The plugin will automatically attempt to install the MU plugin. If it fails:

1. Go to "Build & Load" tab
2. Click "Install MU Plugin" button
3. Verify the status shows "Installed"

### 2. Create Custom Languages Directory

The plugin automatically creates:
```
wp-content/languages/custom/
```

### 3. Set Permissions

Ensure these directories are writable:
- `wp-content/languages/custom/`
- `wp-content/mu-plugins/`

## First Use

### 1. Add Translation Overrides

1. Go to "Custom Dictionary" tab
2. Click "Add New Override"
3. Fill in the form:
   - Domain: e.g., `your-theme-slug` or `woocommerce`
   - Locale: e.g., `en_US` (or any WordPress locale you use)
   - Original Text: The text to replace
   - Replacement: the new text
4. Click "Save Override"

### 2. Build Translation Files

1. Go to "Build & Load" tab
2. Select domain and locale (or leave empty for all)
3. Click "Build .po & .mo Files"
4. Verify files are created in `wp-content/languages/custom/`

### 3. Test Translations

1. Switch your site language to the target locale
2. Verify that your custom translations appear
3. Check that no runtime filters are active

## Troubleshooting

### Common Issues

#### MU Plugin Not Installing
- Check write permissions on `wp-content/mu-plugins/`
- Try manual installation via "Install MU Plugin" button

#### Translation Files Not Loading
- Verify MU plugin is installed
- Check file permissions on custom languages directory
- Ensure .mo files exist and are readable

#### Database Tables Not Created
- Deactivate and reactivate the plugin
- Check WordPress database permissions
- Verify `dbDelta` function is available

#### Admin Interface Not Loading
- Check browser console for JavaScript errors
- Verify WordPress version compatibility
- Check for plugin conflicts

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### File Permissions

Recommended permissions:
- Directories: 755
- Files: 644
- Custom languages: 755
- MU plugins: 755

## Security Considerations

- Plugin requires `manage_options` capability
- All inputs are sanitized
- Nonce verification on all forms
- SQL prepared statements used
- File access controlled via .htaccess

## Performance Notes

- No runtime filters - maximum performance
- Translation files loaded once per request
- Efficient database queries with indexes
- Minimal memory footprint

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Review WordPress error logs
3. Verify server requirements
4. Contact plugin support

## Uninstallation

To completely remove the plugin:

1. Deactivate the plugin
2. Delete plugin files
3. Remove database tables (optional)
4. Remove custom language files (optional)
5. Remove MU plugin (optional)

**Note**: Custom translations will stop working after uninstallation.
