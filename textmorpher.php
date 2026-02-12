<?php
/**
 * Plugin Name: TextMorpher
 * Plugin URI: https://dashweb.agency
 * Description: Site-wide text override and database replacement suite for WordPress. Override theme and plugin strings and safely replace texts in the database without custom code.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Author: Dashweb.agency
 * Author URI: https://dashweb.agency
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: textmorpher
 * Domain Path: /languages
 * Network: true
 */

if (!defined('ABSPATH')) {
    exit;
}
define('TM_VERSION', '1.0.0');
define('TM_PLUGIN_FILE', __FILE__);
define('TM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TM_PLUGIN_BASENAME', plugin_basename(__FILE__));
spl_autoload_register(function ($class) {
    $prefix = 'TextMorpher\\';
    $base_dir = TM_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});
add_action('plugins_loaded', function() {
    if (!class_exists('TextMorpher\\Plugin')) {
        return;
    }
    
    $plugin = new TextMorpher\Plugin();
    $plugin->init();
});
register_activation_hook(__FILE__, function() {
    if (!class_exists('TextMorpher\\Plugin')) {
        return;
    }
    
    $plugin = new TextMorpher\Plugin();
    $plugin->activate();
});
register_deactivation_hook(__FILE__, function() {
    if (!class_exists('TextMorpher\\Plugin')) {
        return;
    }
    
    $plugin = new TextMorpher\Plugin();
    $plugin->deactivate();
});
register_uninstall_hook(__FILE__, function() {
    if (!class_exists('TextMorpher\\Plugin')) {
        return;
    }
    
    $plugin = new TextMorpher\Plugin();
    $plugin->uninstall();
});
