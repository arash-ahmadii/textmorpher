<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('load_textdomain_mofile', function($mofile, $domain) {
    $custom_mofile = WP_CONTENT_DIR . "/languages/custom/{$domain}/{$domain}-" . get_locale() . ".mo";
    
    if (file_exists($custom_mofile)) {
        return $custom_mofile;
    }
    return $mofile;
}, 10, 2);

add_filter('load_textdomain_mofile', function($mofile, $domain) {
    $custom_dir = WP_CONTENT_DIR . "/languages/custom/{$domain}";
    
    if (is_dir($custom_dir)) {
        load_plugin_textdomain($domain, false, "languages/custom/{$domain}");
    }
    
    return $mofile;
}, 5, 2);

if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', function() {
        if (isset($_GET['hto_debug']) && current_user_can('manage_options')) {
            $domains = ['woodmart', 'woocommerce', 'default'];
            $locale = get_locale();
            
            echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
            echo '<h3>TextMorpher Debug Info</h3>';
            echo '<p><strong>Current Locale:</strong> ' . esc_html($locale) . '</p>';
            
            foreach ($domains as $domain) {
                $custom_file = WP_CONTENT_DIR . "/languages/custom/{$domain}/{$domain}-{$locale}.mo";
                $exists = file_exists($custom_file);
                
                echo '<p><strong>' . esc_html($domain) . ':</strong> ';
                if ($exists) {
                    echo '<span style="color: green;">Custom file exists: ' . esc_html($custom_file) . '</span>';
                } else {
                    echo '<span style="color: red;">Custom file not found</span>';
                }
                echo '</p>';
            }
            
            echo '</div>';
        }
    });
}
