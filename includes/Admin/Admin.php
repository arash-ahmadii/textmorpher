<?php

namespace TextMorpher\Admin;

class Admin {
    
    
    private $database;
    
    
    private $builder;
    
    
    private $replacer;
    
    
    private $current_tab;
    
    
    public function __construct() {
        $this->database = new \TextMorpher\Database();
        $this->builder = new \TextMorpher\Builder();
        $this->replacer = new \TextMorpher\DatabaseReplacer();
        
        $this->current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overrides';
    }
    
    
    public function renderMainPage() {
        ?>
        <div class="wrap hto-admin">
            <h1><?php _e('TextMorpher', 'textmorpher'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=textmorpher&tab=overrides" 
                   class="nav-tab <?php echo $this->current_tab === 'overrides' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Custom Dictionary (i18n)', 'textmorpher'); ?>
                </a>
                <a href="?page=textmorpher&tab=db-replace" 
                   class="nav-tab <?php echo $this->current_tab === 'db-replace' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Database Replacement', 'textmorpher'); ?>
                </a>
                <a href="?page=textmorpher&tab=build" 
                   class="nav-tab <?php echo $this->current_tab === 'build' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Build & Load', 'textmorpher'); ?>
                </a>
                <a href="?page=textmorpher&tab=logs" 
                   class="nav-tab <?php echo $this->current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Logs & Restore', 'textmorpher'); ?>
                </a>
                <a href="?page=textmorpher&tab=settings" 
                   class="nav-tab <?php echo $this->current_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Settings', 'textmorpher'); ?>
                </a>
            </nav>
            
            <div class="hto-content">
                <?php
                switch ($this->current_tab) {
                    case 'overrides':
                        $this->renderOverridesTab();
                        break;
                    case 'db-replace':
                        $this->renderDbReplaceTab();
                        break;
                    case 'build':
                        $this->renderBuildTab();
                        break;
                    case 'logs':
                        $this->renderLogsTab();
                        break;
                    case 'settings':
                        $this->renderSettingsTab();
                        break;
                    default:
                        $this->renderOverridesTab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    
    private function renderOverridesTab() {
        $overrides = $this->database->getOverrides();
        $domains = $this->database->getAvailableDomains();
        $locales = $this->database->getAvailableLocales();
        ?>
        <div class="hto-tab-content">
            <div class="hto-header">
                <h2><?php _e('Custom Translation Dictionary', 'textmorpher'); ?></h2>
                <p><?php _e('Manage custom text overrides for i18n translations. These will be compiled into .mo files.', 'textmorpher'); ?></p>
            </div>
            
            <div class="hto-actions">
                <button type="button" class="button button-primary" id="hto-add-override">
                    <?php _e('Add New Override', 'textmorpher'); ?>
                </button>
                <button type="button" class="button" id="hto-import-overrides">
                    <?php _e('Import', 'textmorpher'); ?>
                </button>
                <button type="button" class="button" id="hto-export-overrides">
                    <?php _e('Export', 'textmorpher'); ?>
                </button>
            </div>
            
            <div class="hto-filters">
                <input type="text" id="hto-search" placeholder="<?php _e('Search overrides...', 'textmorpher'); ?>" class="regular-text">
                <select id="hto-domain-filter">
                    <option value=""><?php _e('All Domains', 'textmorpher'); ?></option>
                    <?php foreach ($domains as $domain): ?>
                        <option value="<?php echo esc_attr($domain); ?>"><?php echo esc_html($domain); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="hto-locale-filter">
                    <option value=""><?php _e('All Locales', 'textmorpher'); ?></option>
                    <?php foreach ($locales as $locale): ?>
                        <option value="<?php echo esc_attr($locale); ?>"><?php echo esc_html($locale); ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="hto-status-filter">
                    <option value=""><?php _e('All Status', 'textmorpher'); ?></option>
                    <option value="1"><?php _e('Active', 'textmorpher'); ?></option>
                    <option value="0"><?php _e('Inactive', 'textmorpher'); ?></option>
                </select>
            </div>
            
            <div class="hto-overrides-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="check-column"><input type="checkbox" id="hto-select-all"></th>
                            <th><?php _e('Original Text', 'textmorpher'); ?></th>
                            <th><?php _e('Replacement', 'textmorpher'); ?></th>
                            <th><?php _e('Context', 'textmorpher'); ?></th>
                            <th><?php _e('Domain', 'textmorpher'); ?></th>
                            <th><?php _e('Locale', 'textmorpher'); ?></th>
                            <th><?php _e('Status', 'textmorpher'); ?></th>
                            <th><?php _e('Updated', 'textmorpher'); ?></th>
                            <th><?php _e('Actions', 'textmorpher'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="hto-overrides-list">
                        <?php if (empty($overrides)): ?>
                            <tr>
                                <td colspan="9"><?php _e('No overrides found. Add your first override to get started.', 'textmorpher'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($overrides as $override): ?>
                                <tr data-id="<?php echo esc_attr($override->id); ?>">
                                    <td><input type="checkbox" class="hto-select-item" value="<?php echo esc_attr($override->id); ?>"></td>
                                    <td><?php echo esc_html($override->original_text); ?></td>
                                    <td><?php echo esc_html($override->replacement); ?></td>
                                    <td><?php echo esc_html($override->context ?: '-'); ?></td>
                                    <td><?php echo esc_html($override->domain); ?></td>
                                    <td><?php echo esc_html($override->locale); ?></td>
                                    <td>
                                        <span class="hto-status hto-status-<?php echo $override->status ? 'active' : 'inactive'; ?>">
                                            <?php echo $override->status ? __('Active', 'textmorpher') : __('Inactive', 'textmorpher'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i', strtotime($override->updated_at))); ?></td>
                                    <td>
                                        <button type="button" class="button button-small hto-edit-override" data-id="<?php echo esc_attr($override->id); ?>">
                                            <?php _e('Edit', 'textmorpher'); ?>
                                        </button>
                                        <button type="button" class="button button-small hto-toggle-status" data-id="<?php echo esc_attr($override->id); ?>" data-status="<?php echo esc_attr($override->status); ?>">
                                            <?php echo $override->status ? __('Disable', 'textmorpher') : __('Enable', 'textmorpher'); ?>
                                        </button>
                                        <button type="button" class="button button-small button-link-delete hto-delete-override" data-id="<?php echo esc_attr($override->id); ?>">
                                            <?php _e('Delete', 'textmorpher'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="hto-bulk-actions">
                <select id="hto-bulk-action">
                    <option value=""><?php _e('Bulk Actions', 'textmorpher'); ?></option>
                    <option value="enable"><?php _e('Enable', 'textmorpher'); ?></option>
                    <option value="disable"><?php _e('Disable', 'textmorpher'); ?></option>
                    <option value="delete"><?php _e('Delete', 'textmorpher'); ?></option>
                </select>
                <button type="button" class="button" id="hto-apply-bulk"><?php _e('Apply', 'textmorpher'); ?></button>
            </div>
        </div>
        
        
        <div id="hto-override-modal" class="hto-modal" style="display: none;">
            <div class="hto-modal-content">
                <div class="hto-modal-header">
                    <h3 id="hto-modal-title"><?php _e('Add New Override', 'textmorpher'); ?></h3>
                    <span class="hto-modal-close">&times;</span>
                </div>
                <div class="hto-modal-body">
                    <form id="hto-override-form">
                        <input type="hidden" id="hto-override-id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="hto-domain"><?php _e('Domain', 'textmorpher'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="hto-domain" name="domain" class="regular-text" required>
                                    <p class="description"><?php _e('Text domain (e.g., woodmart, woocommerce)', 'textmorpher'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hto-locale"><?php _e('Locale', 'textmorpher'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="hto-locale" name="locale" class="regular-text" value="fa_IR" required>
                                    <p class="description"><?php _e('Language locale (e.g., fa_IR, en_US)', 'textmorpher'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hto-context"><?php _e('Context', 'textmorpher'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="hto-context" name="context" class="regular-text">
                                    <p class="description"><?php _e('Optional context for _x() functions', 'textmorpher'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hto-original-text"><?php _e('Original Text', 'textmorpher'); ?></label>
                                </th>
                                <td>
                                    <textarea id="hto-original-text" name="original_text" rows="3" class="large-text" required></textarea>
                                    <p class="description"><?php _e('The original text to be replaced', 'textmorpher'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hto-replacement"><?php _e('Replacement', 'textmorpher'); ?></label>
                                </th>
                                <td>
                                    <textarea id="hto-replacement" name="replacement" rows="3" class="large-text" required></textarea>
                                    <p class="description"><?php _e('The replacement text. Preserve placeholders like %s, %d, {name}', 'textmorpher'); ?></p>
                                    <div id="hto-placeholder-warnings" class="hto-warnings" style="display: none;"></div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="hto-status"><?php _e('Status', 'textmorpher'); ?></label>
                                </th>
                                <td>
                                    <select id="hto-status" name="status">
                                        <option value="1"><?php _e('Active', 'textmorpher'); ?></option>
                                        <option value="0"><?php _e('Inactive', 'textmorpher'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                <div class="hto-modal-footer">
                    <button type="button" class="button button-primary" id="hto-save-override"><?php _e('Save Override', 'textmorpher'); ?></button>
                    <button type="button" class="button" id="hto-cancel-override"><?php _e('Cancel', 'textmorpher'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }
    
    
    private function renderDbReplaceTab() {
        $post_types = $this->replacer->getAvailablePostTypes();
        $option_prefixes = $this->replacer->getCommonOptionPrefixes();
        ?>
        <div class="hto-tab-content">
            <div class="hto-header">
                <h2><?php _e('Database Text Replacement', 'textmorpher'); ?></h2>
                <p><?php _e('Safely replace text in database content with backup and restore functionality.', 'textmorpher'); ?></p>
            </div>
            
            <div class="hto-db-replace-form">
                <h3><?php _e('Find & Replace', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hto-find-text"><?php _e('Find Text', 'textmorpher'); ?></label>
                        </th>
                        <td>
                            <textarea id="hto-find-text" rows="3" class="large-text" placeholder="<?php _e('Enter text to find...', 'textmorpher'); ?>"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hto-replace-text"><?php _e('Replace With', 'textmorpher'); ?></label>
                        </th>
                        <td>
                            <textarea id="hto-replace-text" rows="3" class="large-text" placeholder="<?php _e('Enter replacement text...', 'textmorpher'); ?>"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Options', 'textmorpher'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="hto-regex" value="1">
                                <?php _e('Use Regular Expression', 'textmorpher'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="hto-case-sensitive" value="1">
                                <?php _e('Case Sensitive', 'textmorpher'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="hto-whole-word" value="1">
                                <?php _e('Whole Word Only', 'textmorpher'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="hto-serialize-aware" value="1" checked>
                                <?php _e('Serialize/JSON Aware', 'textmorpher'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Scope', 'textmorpher'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="hto-scope-options" value="wp_options" checked>
                                <?php _e('WordPress Options (wp_options)', 'textmorpher'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" id="hto-scope-posts" value="post_content" checked>
                                <?php _e('Post Content (wp_posts)', 'textmorpher'); ?>
                            </label>
                            
                            <div id="hto-post-types" style="margin-left: 20px; margin-top: 10px;">
                                <strong><?php _e('Post Types:', 'textmorpher'); ?></strong><br>
                                <?php foreach ($post_types as $post_type): ?>
                                    <label style="display: inline-block; margin-right: 15px;">
                                        <input type="checkbox" class="hto-post-type" value="<?php echo esc_attr($post_type->name); ?>" checked>
                                        <?php echo esc_html($post_type->label); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div class="hto-actions">
                    <button type="button" class="button button-primary" id="hto-dry-run">
                        <?php _e('Dry Run', 'textmorpher'); ?>
                    </button>
                    <button type="button" class="button" id="hto-apply-replacement" style="display: none;">
                        <?php _e('Apply Changes', 'textmorpher'); ?>
                    </button>
                </div>
            </div>
            
            <div id="hto-dry-run-results" class="hto-dry-run-results" style="display: none;">
                <h3><?php _e('Dry Run Results', 'textmorpher'); ?></h3>
                <div id="hto-results-content"></div>
            </div>
        </div>
        <?php
    }
    
    
    private function renderBuildTab() {
        $domains = $this->database->getAvailableDomains();
        $locales = $this->database->getAvailableLocales();
        $custom_path = $this->builder->getCustomLanguagesPath();
        $mu_plugin_exists = file_exists(WPMU_PLUGIN_DIR . '/textmorpher-l10n-loader.php');
        ?>
        <div class="hto-tab-content">
            <div class="hto-header">
                <h2><?php _e('Build & Load Translations', 'textmorpher'); ?></h2>
                <p><?php _e('Generate .po and .mo files from your custom overrides and manage the MU plugin.', 'textmorpher'); ?></p>
            </div>
            
            <div class="hto-build-section">
                <h3><?php _e('Build Translation Files', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="hto-build-domain"><?php _e('Domain', 'textmorpher'); ?></label>
                        </th>
                        <td>
                            <select id="hto-build-domain">
                                <option value=""><?php _e('All Domains', 'textmorpher'); ?></option>
                                <?php foreach ($domains as $domain): ?>
                                    <option value="<?php echo esc_attr($domain); ?>"><?php echo esc_html($domain); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="hto-build-locale"><?php _e('Locale', 'textmorpher'); ?></label>
                        </th>
                        <td>
                            <select id="hto-build-locale">
                                <option value=""><?php _e('All Locales', 'textmorpher'); ?></option>
                                <?php foreach ($locales as $locale): ?>
                                    <option value="<?php echo esc_attr($locale); ?>"><?php echo esc_html($locale); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <div class="hto-actions">
                    <button type="button" class="button button-primary" id="hto-build-translations">
                        <?php _e('Build .po & .mo Files', 'textmorpher'); ?>
                    </button>
                </div>
                
                <div id="hto-build-results" class="hto-build-results" style="display: none;">
                    <h4><?php _e('Build Results', 'textmorpher'); ?></h4>
                    <div id="hto-build-output"></div>
                </div>
            </div>
            
            <div class="hto-mu-plugin-section">
                <h3><?php _e('MU Plugin Management', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Status', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($mu_plugin_exists): ?>
                                <span class="hto-status hto-status-active"><?php _e('Installed', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('Not Installed', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Actions', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($mu_plugin_exists): ?>
                                <button type="button" class="button" id="hto-reinstall-mu-plugin">
                                    <?php _e('Reinstall MU Plugin', 'textmorpher'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="button button-primary" id="hto-install-mu-plugin">
                                    <?php _e('Install MU Plugin', 'textmorpher'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="hto-paths-section">
                <h3><?php _e('File Paths', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Custom Languages Directory', 'textmorpher'); ?></th>
                        <td>
                            <code><?php echo esc_html($custom_path); ?></code>
                            <?php if (file_exists($custom_path)): ?>
                                <span class="hto-status hto-status-active"><?php _e('Exists', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('Not Found', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('MU Plugins Directory', 'textmorpher'); ?></th>
                        <td>
                            <code><?php echo esc_html(WPMU_PLUGIN_DIR); ?></code>
                            <?php if (file_exists(WPMU_PLUGIN_DIR)): ?>
                                <span class="hto-status hto-status-active"><?php _e('Exists', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('Not Found', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    
    private function renderLogsTab() {
        $jobs = $this->database->getJobs();
        ?>
        <div class="hto-tab-content">
            <div class="hto-header">
                <h2><?php _e('Logs & Restore', 'textmorpher'); ?></h2>
                <p><?php _e('View operation logs and restore from backups when needed.', 'textmorpher'); ?></p>
            </div>
            
            <div class="hto-jobs-list">
                <h3><?php _e('Operation Logs', 'textmorpher'); ?></h3>
                
                <?php if (empty($jobs)): ?>
                    <p><?php _e('No operations logged yet.', 'textmorpher'); ?></p>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Type', 'textmorpher'); ?></th>
                                <th><?php _e('Date', 'textmorpher'); ?></th>
                                <th><?php _e('User', 'textmorpher'); ?></th>
                                <th><?php _e('Details', 'textmorpher'); ?></th>
                                <th><?php _e('Actions', 'textmorpher'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td>
                                        <span class="hto-job-type hto-job-type-<?php echo esc_attr($job->type); ?>">
                                            <?php echo esc_html(ucfirst($job->type)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html(date('Y-m-d H:i:s', strtotime($job->created_at))); ?></td>
                                    <td>
                                        <?php
                                        $user = get_user_by('id', $job->created_by);
                                        echo $user ? esc_html($user->display_name) : __('Unknown', 'textmorpher');
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $payload = json_decode($job->payload, true);
                                        $stats = json_decode($job->stats, true);
                                        
                                        if ($job->type === 'build') {
                                            if (isset($payload['all_domains'])) {
                                                echo sprintf(__('Built translations for %d domains and %d locales', 'textmorpher'), count($payload['domains']), count($payload['locales']));
                                            } else {
                                                echo sprintf(__('Built translations for %s (%s)', 'textmorpher'), $payload['domain'], $payload['locale']);
                                            }
                                        } elseif ($job->type === 'replace') {
                                            echo sprintf(__('Replaced "%s" with "%s"', 'textmorpher'), $payload['find_text'], $payload['replace_text']);
                                        } elseif ($job->type === 'restore') {
                                            echo sprintf(__('Restored from job #%d', 'textmorpher'), $payload['original_job_id']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($job->type === 'replace'): ?>
                                            <button type="button" class="button button-small" onclick="htoViewJobDetails(<?php echo esc_js($job->id); ?>)">
                                                <?php _e('View Details', 'textmorpher'); ?>
                                            </button>
                                            <button type="button" class="button button-small button-primary" onclick="htoRestoreFromJob(<?php echo esc_js($job->id); ?>)">
                                                <?php _e('Restore', 'textmorpher'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    
    private function renderSettingsTab() {
        $stats = $this->database->getStatistics();
        $env_checks = [
            'wp_content_writable' => wp_is_writable(WP_CONTENT_DIR),
            'mu_plugins_dir_exists' => file_exists(WPMU_PLUGIN_DIR),
            'mu_plugins_writable' => wp_is_writable(WPMU_PLUGIN_DIR),
            'custom_languages_dir_exists' => file_exists(WP_CONTENT_DIR . '/languages/custom'),
            'mu_plugin_installed' => file_exists(WPMU_PLUGIN_DIR . '/textmorpher-l10n-loader.php')
        ];
        ?>
        <div class="hto-tab-content">
            <div class="hto-header">
                <h2><?php _e('Settings & Status', 'textmorpher'); ?></h2>
                <p><?php _e('Configure plugin settings and view system status.', 'textmorpher'); ?></p>
            </div>
            
            <div class="hto-settings-section">
                <h3><?php _e('Plugin Settings', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Locale', 'textmorpher'); ?></th>
                        <td>
                            <input type="text" id="hto-default-locale" value="fa_IR" class="regular-text">
                            <p class="description"><?php _e('Default locale for new overrides', 'textmorpher'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Auto-build on Save', 'textmorpher'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" id="hto-auto-build" value="1">
                                <?php _e('Automatically rebuild translation files when overrides are saved', 'textmorpher'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Backup Retention', 'textmorpher'); ?></th>
                        <td>
                            <input type="number" id="hto-backup-retention" value="30" min="1" max="365" class="small-text">
                            <p class="description"><?php _e('Number of days to keep backups (1-365)', 'textmorpher'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <div class="hto-actions">
                    <button type="button" class="button button-primary" id="hto-save-settings">
                        <?php _e('Save Settings', 'textmorpher'); ?>
                    </button>
                </div>
            </div>
            
            <div class="hto-status-section">
                <h3><?php _e('System Status', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Total Overrides', 'textmorpher'); ?></th>
                        <td><?php echo esc_html($stats['total_overrides']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Active Overrides', 'textmorpher'); ?></th>
                        <td><?php echo esc_html($stats['active_overrides']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Domains', 'textmorpher'); ?></th>
                        <td><?php echo esc_html($stats['domains_count']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Locales', 'textmorpher'); ?></th>
                        <td><?php echo esc_html($stats['locales_count']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Total Jobs', 'textmorpher'); ?></th>
                        <td><?php echo esc_html($stats['total_jobs']); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Total Backups', 'textmorpher'); ?></th>
                        <td><?php echo esc_html($stats['total_backups']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="hto-environment-section">
                <h3><?php _e('Environment Checks', 'textmorpher'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('WP Content Writable', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($env_checks['wp_content_writable']): ?>
                                <span class="hto-status hto-status-active"><?php _e('Yes', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('No', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('MU Plugins Directory', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($env_checks['mu_plugins_dir_exists']): ?>
                                <span class="hto-status hto-status-active"><?php _e('Exists', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('Not Found', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('MU Plugins Writable', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($env_checks['mu_plugins_writable']): ?>
                                <span class="hto-status hto-status-active"><?php _e('Yes', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('No', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Custom Languages Directory', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($env_checks['custom_languages_dir_exists']): ?>
                                <span class="hto-status hto-status-active"><?php _e('Exists', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('Not Found', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('MU Plugin Installed', 'textmorpher'); ?></th>
                        <td>
                            <?php if ($env_checks['mu_plugin_installed']): ?>
                                <span class="hto-status hto-status-active"><?php _e('Yes', 'textmorpher'); ?></span>
                            <?php else: ?>
                                <span class="hto-status hto-status-inactive"><?php _e('No', 'textmorpher'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="hto-maintenance-section">
                <h3><?php _e('Maintenance', 'textmorpher'); ?></h3>
                
                <div class="hto-actions">
                    <button type="button" class="button" id="hto-clean-backups">
                        <?php _e('Clean Old Backups', 'textmorpher'); ?>
                    </button>
                    <button type="button" class="button" id="hto-clean-languages">
                        <?php _e('Clean Old Language Files', 'textmorpher'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
