<?php

namespace TextMorpher\REST;

class RESTController {
    
    
    private $database;
    
    
    private $builder;
    
    
    private $replacer;
    
    
    public function __construct() {
        $this->database = new \TextMorpher\Database();
        $this->builder = new \TextMorpher\Builder();
        $this->replacer = new \TextMorpher\DatabaseReplacer();
    }
    
    
    public function registerRoutes() {
        register_rest_route('textmorpher/v1', '/overrides', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getOverrides'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'search' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'domain' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'locale' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'status' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'default' => 20
                    ],
                    'page' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'default' => 1
                    ]
                ]
            ],
            [
                'methods' => 'POST',
                'callback' => [$this, 'createOverride'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'domain' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'locale' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'context' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'original_text' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'replacement' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'status' => [
                        'type' => 'integer',
                        'default' => 1,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/overrides/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getOverride'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            [
                'methods' => 'PUT',
                'callback' => [$this, 'updateOverride'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ],
                    'domain' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'locale' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'context' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'original_text' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'replacement' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'status' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ],
            [
                'methods' => 'DELETE',
                'callback' => [$this, 'deleteOverride'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/overrides/(?P<id>\d+)/status', [
            [
                'methods' => 'PATCH',
                'callback' => [$this, 'updateOverrideStatus'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ],
                    'status' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/overrides/bulk', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'bulkAction'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'action' => [
                        'type' => 'string',
                        'required' => true,
                        'enum' => ['delete', 'enable', 'disable']
                    ],
                    'ids' => [
                        'type' => 'array',
                        'required' => true,
                        'items' => [
                            'type' => 'integer'
                        ]
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/build', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'buildTranslations'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'domain' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'locale' => [
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/db-replace/dry-run', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'dryRun'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'find_text' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'replace_text' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'options' => [
                        'type' => 'object',
                        'default' => []
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/db-replace/apply', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'applyReplacement'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'find_text' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'replace_text' => [
                        'type' => 'string',
                        'required' => true,
                        'sanitize_callback' => 'sanitize_textarea_field'
                    ],
                    'options' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    'selected_items' => [
                        'type' => 'array',
                        'required' => true,
                        'items' => [
                            'type' => 'object'
                        ]
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/db-replace/restore', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'restoreFromBackup'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'job_id' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/jobs', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getJobs'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'type' => [
                        'type' => 'string',
                        'enum' => ['build', 'replace', 'restore']
                    ],
                    'per_page' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'default' => 20
                    ],
                    'page' => [
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'default' => 1
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/jobs/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getJob'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'id' => [
                        'type' => 'integer',
                        'required' => true,
                        'sanitize_callback' => 'absint'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/env/checks', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getEnvironmentChecks'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/mu-plugin/install', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'installMuPlugin'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/statistics', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'getStatistics'],
                'permission_callback' => [$this, 'checkPermissions']
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/export', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'exportOverrides'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'filters' => [
                        'type' => 'object',
                        'default' => []
                    ],
                    'format' => [
                        'type' => 'string',
                        'enum' => ['json', 'csv'],
                        'default' => 'json'
                    ]
                ]
            ]
        ]);
        
        register_rest_route('textmorpher/v1', '/import', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'importOverrides'],
                'permission_callback' => [$this, 'checkPermissions'],
                'args' => [
                    'data' => [
                        'type' => 'array',
                        'required' => true,
                        'items' => [
                            'type' => 'object'
                        ]
                    ]
                ]
            ]
        ]);
    }
    
    
    public function checkPermissions() {
        return current_user_can('manage_options');
    }
    
    
    public function getOverrides($request) {
        $filters = [
            'search' => $request->get_param('search'),
            'domain' => $request->get_param('domain'),
            'locale' => $request->get_param('locale'),
            'status' => $request->get_param('status')
        ];
        
        $overrides = $this->database->getOverrides($filters);
        
        return rest_ensure_response([
            'success' => true,
            'data' => $overrides,
            'total' => count($overrides)
        ]);
    }
    
    
    public function getOverride($request) {
        $id = $request->get_param('id');
        $override = $this->database->getOverride($id);
        
        if (!$override) {
            return new \WP_Error('not_found', 'Override not found', ['status' => 404]);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $override
        ]);
    }
    
    
    public function createOverride($request) {
        $data = [
            'domain' => $request->get_param('domain'),
            'locale' => $request->get_param('locale'),
            'context' => $request->get_param('context'),
            'original_text' => $request->get_param('original_text'),
            'replacement' => $request->get_param('replacement'),
            'status' => $request->get_param('status')
        ];
        
        $result = $this->database->saveOverride($data);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Override created successfully'
            ]);
        } else {
            return new \WP_Error('save_failed', 'Failed to save override', ['status' => 500]);
        }
    }
    
    
    public function updateOverride($request) {
        $id = $request->get_param('id');
        $data = $request->get_params();
        unset($data['id']);
        
        $result = $this->database->saveOverride(array_merge(['id' => $id], $data));
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Override updated successfully'
            ]);
        } else {
            return new \WP_Error('update_failed', 'Failed to update override', ['status' => 500]);
        }
    }
    
    
    public function deleteOverride($request) {
        $id = $request->get_param('id');
        $result = $this->database->deleteOverride($id);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Override deleted successfully'
            ]);
        } else {
            return new \WP_Error('delete_failed', 'Failed to delete override', ['status' => 500]);
        }
    }
    
    
    public function updateOverrideStatus($request) {
        $id = $request->get_param('id');
        $status = $request->get_param('status');
        
        $result = $this->database->updateOverrideStatus($id, $status);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } else {
            return new \WP_Error('update_failed', 'Failed to update status', ['status' => 500]);
        }
    }
    
    
    public function bulkAction($request) {
        $action = $request->get_param('action');
        $ids = $request->get_param('ids');
        
        $results = [];
        
        foreach ($ids as $id) {
            switch ($action) {
                case 'delete':
                    $results[] = $this->database->deleteOverride($id);
                    break;
                case 'enable':
                    $results[] = $this->database->updateOverrideStatus($id, 1);
                    break;
                case 'disable':
                    $results[] = $this->database->updateOverrideStatus($id, 0);
                    break;
            }
        }
        
        $success_count = count(array_filter($results));
        
        return rest_ensure_response([
            'success' => true,
            'message' => "Bulk action completed. {$success_count} items processed successfully.",
            'processed' => $success_count,
            'total' => count($ids)
        ]);
    }
    
    
    public function buildTranslations($request) {
        $domain = $request->get_param('domain');
        $locale = $request->get_param('locale');
        
        $result = $this->builder->buildTranslations($domain, $locale);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Translations built successfully'
            ]);
        } else {
            return new \WP_Error('build_failed', 'Failed to build translations', ['status' => 500]);
        }
    }
    
    
    public function dryRun($request) {
        $find_text = $request->get_param('find_text');
        $replace_text = $request->get_param('replace_text');
        $options = $request->get_param('options');
        
        try {
            $results = $this->replacer->dryRun($find_text, $replace_text, $options);
            
            return rest_ensure_response([
                'success' => true,
                'data' => $results
            ]);
        } catch (Exception $e) {
            return new \WP_Error('dry_run_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    
    public function applyReplacement($request) {
        $find_text = $request->get_param('find_text');
        $replace_text = $request->get_param('replace_text');
        $options = $request->get_param('options');
        $selected_items = $request->get_param('selected_items');
        
        try {
            $results = $this->replacer->apply($find_text, $replace_text, $options, $selected_items);
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Replacement applied successfully',
                'data' => $results
            ]);
        } catch (Exception $e) {
            return new \WP_Error('apply_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    
    public function restoreFromBackup($request) {
        $job_id = $request->get_param('job_id');
        
        try {
            $results = $this->replacer->restore($job_id);
            
            return rest_ensure_response([
                'success' => true,
                'message' => 'Restore completed successfully',
                'data' => $results
            ]);
        } catch (Exception $e) {
            return new \WP_Error('restore_failed', $e->getMessage(), ['status' => 500]);
        }
    }
    
    
    public function getJobs($request) {
        $jobs = $this->database->getJobs();
        
        return rest_ensure_response([
            'success' => true,
            'data' => $jobs,
            'total' => count($jobs)
        ]);
    }
    
    
    public function getJob($request) {
        $id = $request->get_param('id');
        $job = $this->database->getJob($id);
        
        if (!$job) {
            return new \WP_Error('not_found', 'Job not found', ['status' => 404]);
        }
        
        return rest_ensure_response([
            'success' => true,
            'data' => $job
        ]);
    }
    
    
    public function getEnvironmentChecks() {
        $checks = [
            'wp_content_writable' => wp_is_writable(WP_CONTENT_DIR),
            'mu_plugins_dir_exists' => file_exists(WPMU_PLUGIN_DIR),
            'mu_plugins_writable' => wp_is_writable(WPMU_PLUGIN_DIR),
            'msgfmt_available' => $this->builder->isMsgfmtAvailable(),
            'custom_languages_dir_exists' => file_exists(WP_CONTENT_DIR . '/languages/custom'),
            'mu_plugin_installed' => file_exists(WPMU_PLUGIN_DIR . '/textmorpher-l10n-loader.php')
        ];
        
        return rest_ensure_response([
            'success' => true,
            'data' => $checks
        ]);
    }
    
    
    public function installMuPlugin() {
        $source = TM_PLUGIN_DIR . 'mu-plugins/textmorpher-l10n-loader.php';
        $destination = WPMU_PLUGIN_DIR . '/textmorpher-l10n-loader.php';
        
        if (!file_exists($source)) {
            return new \WP_Error('source_not_found', 'MU plugin source not found', ['status' => 404]);
        }
        
        if (!wp_is_writable(WPMU_PLUGIN_DIR)) {
            return new \WP_Error('not_writable', 'MU plugins directory is not writable', ['status' => 500]);
        }
        
        $result = copy($source, $destination);
        
        if ($result) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'MU plugin installed successfully'
            ]);
        } else {
            return new \WP_Error('install_failed', 'Failed to install MU plugin', ['status' => 500]);
        }
    }
    
    
    public function getStatistics() {
        $stats = $this->database->getStatistics();
        
        return rest_ensure_response([
            'success' => true,
            'data' => $stats
        ]);
    }
    
    
    public function exportOverrides($request) {
        $filters = $request->get_param('filters');
        $format = $request->get_param('format');
        
        $data = $this->database->exportOverrides($filters);
        
        if ($format === 'csv') {
            return $this->exportAsCsv($data);
        } else {
            return rest_ensure_response([
                'success' => true,
                'data' => $data,
                'format' => 'json'
            ]);
        }
    }
    
    
    public function importOverrides($request) {
        $data = $request->get_param('data');
        
        $result = $this->database->importOverrides($data);
        
        return rest_ensure_response([
            'success' => true,
            'message' => "Import completed. {$result['imported']} items imported successfully.",
            'data' => $result
        ]);
    }
    
    
    private function exportAsCsv($data) {
        $filename = 'textmorpher-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php:
        fputcsv($output, ['Domain', 'Locale', 'Context', 'Original Text', 'Replacement', 'Status', 'Updated At']);
        foreach ($data as $row) {
            fputcsv($output, [
                $row['domain'],
                $row['locale'],
                $row['context'] ?: '',
                $row['original_text'],
                $row['replacement'],
                $row['status'],
                $row['updated_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
