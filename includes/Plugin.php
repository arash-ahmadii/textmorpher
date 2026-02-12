<?php

namespace TextMorpher;

class Plugin {
    
    
    private static $instance = null;
    
    
    private $admin;
    
    
    private $rest;
    
    
    private $database;
    
    
    private $builder;
    
    
    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    
    private function __construct() {
    }
    
    
    public function init() {
        load_plugin_textdomain('textmorpher', false, dirname(TM_PLUGIN_BASENAME) . '/languages');
        $this->initComponents();
        $this->addHooks();
    }
    
    
    private function initComponents() {
        $this->database = new Database();
        $this->builder = new Builder();
        if (is_admin()) {
            $this->admin = new Admin\Admin();
        }
        $this->rest = new REST\RESTController();
    }
    
    
    private function addHooks() {
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('rest_api_init', [$this->rest, 'registerRoutes']);
        add_action('wp_ajax_hto_ajax_handler', [$this, 'handleAjax']);
    }
    
    
    public function addAdminMenu() {
        add_menu_page(
            __('TextMorpher', 'textmorpher'),
            __('TextMorpher', 'textmorpher'),
            'manage_options',
            'textmorpher',
            [$this->admin, 'renderMainPage'],
            'dashicons-translation',
            30
        );
    }
    
    
    public function enqueueAdminAssets($hook) {
        if (strpos($hook, 'textmorpher') === false) {
            return;
        }
        
        wp_enqueue_script(
            'hto-admin',
            TM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-element', 'wp-components', 'wp-api-fetch'],
            TM_VERSION,
            true
        );
        
        wp_enqueue_style(
            'hto-admin',
            TM_PLUGIN_URL . 'assets/css/admin.css',
            [],
            TM_VERSION
        );
        wp_localize_script('hto-admin', 'htoAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('textmorpher/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'strings' => [
                'confirmDelete' => __('Are you sure you want to delete this item?', 'textmorpher'),
                'confirmApply' => __('Are you sure you want to apply these changes?', 'textmorpher'),
                'confirmRestore' => __('Are you sure you want to restore from backup?', 'textmorpher'),
                'success' => __('Operation completed successfully!', 'textmorpher'),
                'error' => __('An error occurred. Please try again.', 'textmorpher'),
            ]
        ]);
    }
    
    
    public function handleAjax() {
        if (!wp_verify_nonce($_POST['nonce'], 'hto_ajax_nonce')) {
            wp_die(__('Security check failed', 'textmorpher'));
        }
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'textmorpher'));
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        
        switch ($action) {
            case 'save_override':
                $this->handleSaveOverride();
                break;
            case 'delete_override':
                $this->handleDeleteOverride();
                break;
            case 'build_translations':
                $this->handleBuildTranslations();
                break;
            default:
                wp_send_json_error(__('Invalid action', 'textmorpher'));
        }
    }
    
    
    private function handleSaveOverride() {
        $data = [
            'domain' => sanitize_text_field($_POST['domain']),
            'locale' => sanitize_text_field($_POST['locale']),
            'context' => sanitize_text_field($_POST['context']),
            'original_text' => sanitize_textarea_field($_POST['original_text']),
            'replacement' => sanitize_textarea_field($_POST['replacement']),
            'status' => intval($_POST['status'])
        ];
        
        $result = $this->database->saveOverride($data);
        
        if ($result) {
            wp_send_json_success(__('Override saved successfully', 'textmorpher'));
        } else {
            wp_send_json_error(__('Failed to save override', 'textmorpher'));
        }
    }
    
    
    private function handleDeleteOverride() {
        $id = intval($_POST['id']);
        
        $result = $this->database->deleteOverride($id);
        
        if ($result) {
            wp_send_json_success(__('Override deleted successfully', 'textmorpher'));
        } else {
            wp_send_json_error(__('Failed to delete override', 'textmorpher'));
        }
    }
    
    
    private function handleBuildTranslations() {
        $domain = sanitize_text_field($_POST['domain']);
        $locale = sanitize_text_field($_POST['locale']);
        
        $result = $this->builder->buildTranslations($domain, $locale);
        
        if ($result) {
            wp_send_json_success(__('Translations built successfully', 'textmorpher'));
        } else {
            wp_send_json_error(__('Failed to build translations', 'textmorpher'));
        }
    }
    
    
    public function activate() {
        $this->database->createTables();
        $this->createLanguagesDirectory();
        $this->installMuPlugin();
        flush_rewrite_rules();
    }
    
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    
    public function uninstall() {
        $this->database->dropTables();
        $this->removeLanguagesDirectory();
        $this->removeMuPlugin();
    }
    
    
    private function createLanguagesDirectory() {
        $dir = WP_CONTENT_DIR . '/languages/custom';
        
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            $content = "Order deny,allow\nDeny from all\n";
            file_put_contents($htaccess, $content);
        }
    }
    
    
    private function removeLanguagesDirectory() {
        $dir = WP_CONTENT_DIR . '/languages/custom';
        
        if (file_exists($dir)) {
            $this->removeDirectory($dir);
        }
    }
    
    
    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
    
    
    private function installMuPlugin() {
        $mu_dir = WPMU_PLUGIN_DIR;
        
        if (!file_exists($mu_dir)) {
            wp_mkdir_p($mu_dir);
        }
        
        $source = TM_PLUGIN_DIR . 'mu-plugins/textmorpher-l10n-loader.php';
        $destination = $mu_dir . '/textmorpher-l10n-loader.php';
        
        if (file_exists($source)) {
            copy($source, $destination);
        }
    }
    
    
    private function removeMuPlugin() {
        $mu_plugin = WPMU_PLUGIN_DIR . '/textmorpher-l10n-loader.php';
        
        if (file_exists($mu_plugin)) {
            unlink($mu_plugin);
        }
    }
}
