<?php

namespace TextMorpher;

class DatabaseReplacer {
    
    
    private $database;
    
    
    private $wpdb;
    
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->database = new Database();
    }
    
    
    public function dryRun($find_text, $replace_text, $options = []) {
        $defaults = [
            'regex' => false,
            'case_sensitive' => false,
            'whole_word' => false,
            'serialize_aware' => true,
            'scope' => ['wp_options', 'post_content']
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $results = [
            'wp_options' => [],
            'post_content' => []
        ];
        if (in_array('wp_options', $options['scope'])) {
            $results['wp_options'] = $this->dryRunOptions($find_text, $replace_text, $options);
        }
        if (in_array('post_content', $options['scope'])) {
            $results['post_content'] = $this->dryRunPostContent($find_text, $replace_text, $options);
        }
        
        return $results;
    }
    
    
    private function dryRunOptions($find_text, $replace_text, $options) {
        $results = [];
        $sql = "SELECT option_id, option_name, option_value FROM {$this->wpdb->options} WHERE option_value LIKE %s";
        $search_pattern = '%' . $this->wpdb->esc_like($find_text) . '%';
        $sql = $this->wpdb->prepare($sql, $search_pattern);
        
        $options_data = $this->wpdb->get_results($sql);
        
        foreach ($options_data as $option) {
            $matches = $this->findMatches($option->option_value, $find_text, $replace_text, $options);
            
            if (!empty($matches)) {
                $results[] = [
                    'table' => 'wp_options',
                    'id' => $option->option_id,
                    'field' => 'option_value',
                    'name' => $option->option_name,
                    'original' => $option->option_value,
                    'replaced' => $this->performReplacement($option->option_value, $find_text, $replace_text, $options),
                    'matches' => $matches,
                    'is_serialized' => is_serialized($option->option_value)
                ];
            }
        }
        
        return $results;
    }
    
    
    private function dryRunPostContent($find_text, $replace_text, $options) {
        $results = [];
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            $sql = "SELECT ID, post_title, post_content FROM {$this->wpdb->posts} WHERE post_type = %s AND post_content LIKE %s";
            $search_pattern = '%' . $this->wpdb->esc_like($find_text) . '%';
            $sql = $this->wpdb->prepare($sql, $post_type, $search_pattern);
            
            $posts = $this->wpdb->get_results($sql);
            
            foreach ($posts as $post) {
                $matches = $this->findMatches($post->post_content, $find_text, $replace_text, $options);
                
                if (!empty($matches)) {
                    $results[] = [
                        'table' => 'wp_posts',
                        'id' => $post->ID,
                        'field' => 'post_content',
                        'name' => $post->post_title,
                        'post_type' => $post_type,
                        'original' => $post->post_content,
                        'replaced' => $this->performReplacement($post->post_content, $find_text, $replace_text, $options),
                        'matches' => $matches,
                        'is_serialized' => false
                    ];
                }
            }
        }
        
        return $results;
    }
    
    
    private function findMatches($text, $find_text, $replace_text, $options) {
        $matches = [];
        
        if ($options['regex']) {
            $pattern = $find_text;
            $flags = $options['case_sensitive'] ? '' : 'i';
            
            if (preg_match_all("/{$pattern}/{$flags}", $text, $matches_array, PREG_OFFSET_CAPTURE)) {
                foreach ($matches_array[0] as $match) {
                    $matches[] = [
                        'text' => $match[0],
                        'position' => $match[1],
                        'length' => strlen($match[0])
                    ];
                }
            }
        } else {
            $search_text = $options['case_sensitive'] ? $text : strtolower($text);
            $find_lower = $options['case_sensitive'] ? $find_text : strtolower($find_text);
            
            $offset = 0;
            while (($pos = strpos($search_text, $find_lower, $offset)) !== false) {
                if ($options['whole_word']) {
                    $before = $pos > 0 ? $search_text[$pos - 1] : ' ';
                    $after = $pos + strlen($find_lower) < strlen($search_text) ? $search_text[$pos + strlen($find_lower)] : ' ';
                    
                    if (preg_match('/\b/', $before) && preg_match('/\b/', $after)) {
                        $matches[] = [
                            'text' => substr($text, $pos, strlen($find_lower)),
                            'position' => $pos,
                            'length' => strlen($find_lower)
                        ];
                    }
                } else {
                    $matches[] = [
                        'text' => substr($text, $pos, strlen($find_lower)),
                        'position' => $pos,
                        'length' => strlen($find_lower)
                    ];
                }
                
                $offset = $pos + 1;
            }
        }
        
        return $matches;
    }
    
    
    private function performReplacement($text, $find_text, $replace_text, $options) {
        if ($options['regex']) {
            $flags = $options['case_sensitive'] ? '' : 'i';
            return preg_replace("/{$find_text}/{$flags}", $replace_text, $text);
        } else {
            if ($options['case_sensitive']) {
                return str_replace($find_text, $replace_text, $text);
            } else {
                return str_ireplace($find_text, $replace_text, $text);
            }
        }
    }
    
    
    public function apply($find_text, $replace_text, $options = [], $selected_items = []) {
        $job_id = $this->database->createJob('replace', [
            'find_text' => $find_text,
            'replace_text' => $replace_text,
            'options' => $options,
            'selected_items' => $selected_items
        ]);
        
        if (!$job_id) {
            throw new Exception('Failed to create backup job');
        }
        
        $results = [
            'wp_options' => [],
            'post_content' => [],
            'backup_job_id' => $job_id
        ];
        if (in_array('wp_options', $options['scope'])) {
            $results['wp_options'] = $this->applyOptions($find_text, $replace_text, $options, $selected_items, $job_id);
        }
        if (in_array('post_content', $options['scope'])) {
            $results['post_content'] = $this->applyPostContent($find_text, $replace_text, $options, $selected_items, $job_id);
        }
        $total_processed = count($results['wp_options']) + count($results['post_content']);
        $this->database->updateJobStats($job_id, ['total_processed' => $total_processed]);
        
        return $results;
    }
    
    
    private function applyOptions($find_text, $replace_text, $options, $selected_items, $job_id) {
        $results = [];
        
        foreach ($selected_items as $item) {
            if ($item['table'] !== 'wp_options') {
                continue;
            }
            $this->database->createBackup($job_id, 'wp_options', $item['id'], [
                'option_value' => $item['original']
            ]);
            $replaced_value = $this->performReplacement($item['original'], $find_text, $replace_text, $options);
            
            $result = $this->wpdb->update(
                $this->wpdb->options,
                ['option_value' => $replaced_value],
                ['option_id' => $item['id']],
                ['%s'],
                ['%d']
            );
            
            if ($result !== false) {
                $results[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'success' => true,
                    'changes' => count($item['matches'])
                ];
            } else {
                $results[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'success' => false,
                    'error' => 'Database update failed'
                ];
            }
        }
        
        return $results;
    }
    
    
    private function applyPostContent($find_text, $replace_text, $options, $selected_items, $job_id) {
        $results = [];
        
        foreach ($selected_items as $item) {
            if ($item['table'] !== 'wp_posts') {
                continue;
            }
            $this->database->createBackup($job_id, 'wp_posts', $item['id'], [
                'post_content' => $item['original']
            ]);
            $replaced_value = $this->performReplacement($item['original'], $find_text, $replace_text, $options);
            
            $result = $this->wpdb->update(
                $this->wpdb->posts,
                ['post_content' => $replaced_value],
                ['ID' => $item['id']],
                ['%s'],
                ['%d']
            );
            
            if ($result !== false) {
                $results[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'success' => true,
                    'changes' => count($item['matches'])
                ];
            } else {
                $results[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'success' => false,
                    'error' => 'Database update failed'
                ];
            }
        }
        
        return $results;
    }
    
    
    public function restore($job_id) {
        $job = $this->database->getJob($job_id);
        
        if (!$job || $job->type !== 'replace') {
            throw new Exception('Invalid restore job');
        }
        
        $backups = $this->database->getBackupsForJob($job_id);
        
        if (empty($backups)) {
            throw new Exception('No backups found for this job');
        }
        $restore_job_id = $this->database->createJob('restore', [
            'original_job_id' => $job_id,
            'backups_count' => count($backups)
        ]);
        
        $results = [];
        
        foreach ($backups as $backup) {
            $original_data = json_decode($backup->original_data, true);
            
            if ($backup->table_name === 'wp_options') {
                $result = $this->wpdb->update(
                    $this->wpdb->options,
                    ['option_value' => $original_data['option_value']],
                    ['option_id' => $backup->record_id],
                    ['%s'],
                    ['%d']
                );
            } elseif ($backup->table_name === 'wp_posts') {
                $result = $this->wpdb->update(
                    $this->wpdb->posts,
                    ['post_content' => $original_data['post_content']],
                    ['ID' => $backup->record_id],
                    ['%s'],
                    ['%d']
                );
            }
            
            $results[] = [
                'table' => $backup->table_name,
                'id' => $backup->record_id,
                'success' => $result !== false
            ];
        }
        $this->database->updateJobStats($restore_job_id, [
            'total_restored' => count($backups),
            'success_count' => count(array_filter($results, function($r) { return $r['success']; }))
        ]);
        
        return [
            'restore_job_id' => $restore_job_id,
            'results' => $results
        ];
    }
    
    
    public function getAvailablePostTypes() {
        return get_post_types(['public' => true], 'objects');
    }
    
    
    public function getCommonOptionPrefixes() {
        return [
            'woodmart_' => 'Woodmart Theme',
            'theme_mods_' => 'Theme Modifications',
            'elementor_' => 'Elementor',
            'woocommerce_' => 'WooCommerce',
            'jetpack_' => 'Jetpack',
            'yoast_' => 'Yoast SEO'
        ];
    }
    
    
    public function validatePlaceholders($original_text, $replacement_text) {
        $warnings = [];
        $original_placeholders = [];
        preg_match_all('/%[sdif]/', $original_text, $original_placeholders);
        
        $replacement_placeholders = [];
        preg_match_all('/%[sdif]/', $replacement_text, $replacement_placeholders);
        
        if (count($original_placeholders[0]) !== count($replacement_placeholders[0])) {
            $warnings[] = 'Number of sprintf placeholders (%s, %d, etc.) does not match between original and replacement text.';
        }
        $original_named = [];
        preg_match_all('/\{[^}]+\}/', $original_text, $original_named);
        
        $replacement_named = [];
        preg_match_all('/\{[^}]+\}/', $replacement_text, $replacement_named);
        
        if (count($original_named[0]) !== count($replacement_named[0])) {
            $warnings[] = 'Number of named placeholders ({name}, {value}, etc.) does not match between original and replacement text.';
        }
        
        return $warnings;
    }
}
