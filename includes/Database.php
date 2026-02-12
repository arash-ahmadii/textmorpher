<?php

namespace TextMorpher;

class Database {
    
    
    private $wpdb;
    
    
    private $overrides_table;
    private $jobs_table;
    private $backups_table;
    
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        $this->overrides_table = $wpdb->prefix . 'textmorpher_overrides';
        $this->jobs_table = $wpdb->prefix . 'textmorpher_jobs';
        $this->backups_table = $wpdb->prefix . 'textmorpher_backups';
    }
    
    
    public function createTables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        $sql_overrides = "CREATE TABLE {$this->overrides_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            domain varchar(64) NOT NULL,
            locale varchar(16) NOT NULL,
            context varchar(191) DEFAULT NULL,
            original_text longtext NOT NULL,
            replacement longtext NOT NULL,
            status tinyint(1) NOT NULL DEFAULT 1,
            original_hash char(32) NOT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_override (domain, locale, context, original_hash),
            KEY domain_locale (domain, locale),
            KEY status (status),
            KEY updated_at (updated_at)
        ) $charset_collate;";
        $sql_jobs = "CREATE TABLE {$this->jobs_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type enum('build','replace','restore') NOT NULL,
            payload longtext NOT NULL,
            stats longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_by bigint(20) unsigned NOT NULL,
            PRIMARY KEY (id),
            KEY type (type),
            KEY created_at (created_at),
            KEY created_by (created_by)
        ) $charset_collate;";
        $sql_backups = "CREATE TABLE {$this->backups_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            job_id bigint(20) unsigned NOT NULL,
            table_name varchar(64) NOT NULL,
            record_id bigint(20) unsigned NOT NULL,
            original_data longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY job_id (job_id),
            KEY table_name (table_name),
            KEY record_id (record_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_overrides);
        dbDelta($sql_jobs);
        dbDelta($sql_backups);
    }
    
    
    public function dropTables() {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->overrides_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->jobs_table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->backups_table}");
    }
    
    
    public function saveOverride($data) {
        $user_id = get_current_user_id();
        $original_hash = md5($data['original_text'] . $data['context']);
        
        $data_to_save = [
            'domain' => $data['domain'],
            'locale' => $data['locale'],
            'context' => $data['context'] ?: null,
            'original_text' => $data['original_text'],
            'replacement' => $data['replacement'],
            'status' => $data['status'],
            'original_hash' => $original_hash,
            'updated_by' => $user_id
        ];
        $existing = $this->getOverrideByHash($data['domain'], $data['locale'], $data['context'], $original_hash);
        
        if ($existing) {
            $result = $this->wpdb->update(
                $this->overrides_table,
                $data_to_save,
                ['id' => $existing->id],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d'],
                ['%d']
            );
            
            return $result !== false;
        } else {
            $result = $this->wpdb->insert(
                $this->overrides_table,
                $data_to_save,
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d']
            );
            
            return $result !== false;
        }
    }
    
    
    private function getOverrideByHash($domain, $locale, $context, $hash) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->overrides_table} WHERE domain = %s AND locale = %s AND context = %s AND original_hash = %s",
            $domain,
            $locale,
            $context ?: '',
            $hash
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    
    public function getOverrides($filters = []) {
        $where = ['1=1'];
        $values = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(original_text LIKE %s OR replacement LIKE %s)";
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $values[] = $search;
            $values[] = $search;
        }
        
        if (!empty($filters['domain'])) {
            $where[] = "domain = %s";
            $values[] = $filters['domain'];
        }
        
        if (!empty($filters['locale'])) {
            $where[] = "locale = %s";
            $values[] = $filters['locale'];
        }
        
        if (isset($filters['status'])) {
            $where[] = "status = %d";
            $values[] = $filters['status'];
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "SELECT * FROM {$this->overrides_table} WHERE {$where_clause} ORDER BY updated_at DESC";
        
        if (!empty($values)) {
            $sql = $this->wpdb->prepare($sql, ...$values);
        }
        
        return $this->wpdb->get_results($sql);
    }
    
    
    public function getOverride($id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->overrides_table} WHERE id = %d",
            $id
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    
    public function deleteOverride($id) {
        $result = $this->wpdb->delete(
            $this->overrides_table,
            ['id' => $id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    
    public function updateOverrideStatus($id, $status) {
        $result = $this->wpdb->update(
            $this->overrides_table,
            ['status' => $status],
            ['id' => $id],
            ['%d'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    
    public function getOverridesForDomain($domain, $locale) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->overrides_table} WHERE domain = %s AND locale = %s AND status = 1 ORDER BY context ASC, original_text ASC",
            $domain,
            $locale
        );
        
        return $this->wpdb->get_results($sql);
    }
    
    
    public function createJob($type, $payload, $stats = []) {
        $user_id = get_current_user_id();
        
        $result = $this->wpdb->insert(
            $this->jobs_table,
            [
                'type' => $type,
                'payload' => json_encode($payload),
                'stats' => json_encode($stats),
                'created_by' => $user_id
            ],
            ['%s', '%s', '%s', '%d']
        );
        
        if ($result === false) {
            return false;
        }
        
        return $this->wpdb->insert_id;
    }
    
    
    public function getJobs($limit = 50) {
        $sql = "SELECT * FROM {$this->jobs_table} ORDER BY created_at DESC LIMIT %d";
        $sql = $this->wpdb->prepare($sql, $limit);
        
        return $this->wpdb->get_results($sql);
    }
    
    
    public function getJob($id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->jobs_table} WHERE id = %d",
            $id
        );
        
        return $this->wpdb->get_row($sql);
    }
    
    
    public function createBackup($job_id, $table_name, $record_id, $original_data) {
        $result = $this->wpdb->insert(
            $this->backups_table,
            [
                'job_id' => $job_id,
                'table_name' => $table_name,
                'record_id' => $record_id,
                'original_data' => json_encode($original_data)
            ],
            ['%d', '%s', '%d', '%s']
        );
        
        return $result !== false;
    }
    
    
    public function getBackupsForJob($job_id) {
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->backups_table} WHERE job_id = %d ORDER BY created_at ASC",
            $job_id
        );
        
        return $this->wpdb->get_results($sql);
    }
    
    
    public function getAvailableDomains() {
        $sql = "SELECT DISTINCT domain FROM {$this->overrides_table} ORDER BY domain ASC";
        return $this->wpdb->get_col($sql);
    }
    
    
    public function getAvailableLocales() {
        $sql = "SELECT DISTINCT locale FROM {$this->overrides_table} ORDER BY locale ASC";
        return $this->wpdb->get_col($sql);
    }
    
    
    public function getStatistics() {
        $stats = [];
        $stats['total_overrides'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->overrides_table}");
        $stats['active_overrides'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->overrides_table} WHERE status = 1");
        $stats['total_jobs'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->jobs_table}");
        $stats['total_backups'] = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->backups_table}");
        $stats['domains_count'] = $this->wpdb->get_var("SELECT COUNT(DISTINCT domain) FROM {$this->overrides_table}");
        $stats['locales_count'] = $this->wpdb->get_var("SELECT COUNT(DISTINCT locale) FROM {$this->overrides_table}");
        
        return $stats;
    }
    
    
    public function cleanOldBackups($days = 30) {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $sql = $this->wpdb->prepare(
            "DELETE FROM {$this->backups_table} WHERE created_at < %s",
            $date
        );
        
        return $this->wpdb->query($sql);
    }
    
    
    public function exportOverrides($filters = []) {
        $overrides = $this->getOverrides($filters);
        
        $export_data = [];
        foreach ($overrides as $override) {
            $export_data[] = [
                'domain' => $override->domain,
                'locale' => $override->locale,
                'context' => $override->context,
                'original_text' => $override->original_text,
                'replacement' => $override->replacement,
                'status' => $override->status,
                'updated_at' => $override->updated_at
            ];
        }
        
        return $export_data;
    }
    
    
    public function importOverrides($data) {
        $imported = 0;
        $errors = [];
        
        foreach ($data as $index => $row) {
            try {
                $result = $this->saveOverride($row);
                if ($result) {
                    $imported++;
                } else {
                    $errors[] = "Row " . ($index + 1) . ": Failed to save";
                }
            } catch (Exception $e) {
                $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
            }
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }
}
