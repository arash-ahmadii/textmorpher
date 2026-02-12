<?php

namespace TextMorpher;

class Builder {
    
    
    private $database;
    
    
    public function __construct() {
        $this->database = new Database();
    }
    
    
    public function buildTranslations($domain = null, $locale = null) {
        try {
            if ($domain && $locale) {
                $overrides = $this->database->getOverridesForDomain($domain, $locale);
                $this->buildPoFile($domain, $locale, $overrides);
                $this->buildMoFile($domain, $locale);
                $this->database->createJob('build', [
                    'domain' => $domain,
                    'locale' => $locale,
                    'overrides_count' => count($overrides)
                ], [
                    'files_created' => 2,
                    'overrides_processed' => count($overrides)
                ]);
                
                return true;
            } else {
                $domains = $this->database->getAvailableDomains();
                $locales = $this->database->getAvailableLocales();
                
                $total_files = 0;
                $total_overrides = 0;
                
                foreach ($domains as $domain) {
                    foreach ($locales as $locale) {
                        $overrides = $this->database->getOverridesForDomain($domain, $locale);
                        if (!empty($overrides)) {
                            $this->buildPoFile($domain, $locale, $overrides);
                            $this->buildMoFile($domain, $locale);
                            $total_files += 2;
                            $total_overrides += count($overrides);
                        }
                    }
                }
                $this->database->createJob('build', [
                    'all_domains' => true,
                    'domains' => $domains,
                    'locales' => $locales
                ], [
                    'files_created' => $total_files,
                    'overrides_processed' => $total_overrides
                ]);
                
                return true;
            }
        } catch (Exception $e) {
            error_log('TextMorpher: Failed to build translations: ' . $e->getMessage());
            return false;
        }
    }
    
    
    private function buildPoFile($domain, $locale, $overrides) {
        $po_content = $this->generatePoContent($domain, $locale, $overrides);
        
        $dir = WP_CONTENT_DIR . "/languages/custom/{$domain}";
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }
        
        $file_path = "{$dir}/{$domain}-{$locale}.po";
        $result = file_put_contents($file_path, $po_content);
        
        if ($result === false) {
            throw new Exception("Failed to write PO file: {$file_path}");
        }
        
        return $file_path;
    }
    
    
    private function buildMoFile($domain, $locale) {
        $po_file = WP_CONTENT_DIR . "/languages/custom/{$domain}/{$domain}-{$locale}.po";
        $mo_file = WP_CONTENT_DIR . "/languages/custom/{$domain}/{$domain}-{$locale}.mo";
        
        if (!file_exists($po_file)) {
            throw new Exception("PO file not found: {$po_file}");
        }
        if ($this->isMsgfmtAvailable()) {
            $this->compileWithMsgfmt($po_file, $mo_file);
        } else {
            $this->compileWithPhp($po_file, $mo_file);
        }
        
        return $mo_file;
    }
    
    
    private function generatePoContent($domain, $locale, $overrides) {
        $content = "msgid \"\"\n";
        $content .= "msgstr \"\"\n";
        $content .= "\"Project-Id-Version: {$domain}\\n\"\n";
        $content .= "\"POT-Creation-Date: " . date('Y-m-d H:i:sO') . "\\n\"\n";
        $content .= "\"PO-Revision-Date: " . date('Y-m-d H:i:sO') . "\\n\"\n";
        $content .= "\"Last-Translator: TextMorpher Plugin\\n\"\n";
        $content .= "\"Language-Team: {$locale}\\n\"\n";
        $content .= "\"Language: {$locale}\\n\"\n";
        $content .= "\"MIME-Version: 1.0\\n\"\n";
        $content .= "\"Content-Type: text/plain; charset=UTF-8\\n\"\n";
        $content .= "\"Content-Transfer-Encoding: 8bit\\n\"\n";
        $content .= "\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n\n";
        
        foreach ($overrides as $override) {
            if ($override->context) {
                $content .= "msgctxt \"{$override->context}\"\n";
            }
            
            $content .= "msgid \"" . $this->escapePoString($override->original_text) . "\"\n";
            $content .= "msgstr \"" . $this->escapePoString($override->replacement) . "\"\n\n";
        }
        
        return $content;
    }
    
    
    private function escapePoString($string) {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace('"', '\\"', $string);
        $string = str_replace("\n", "\\n", $string);
        $string = str_replace("\r", "\\r", $string);
        $string = str_replace("\t", "\\t", $string);
        return $string;
    }
    
    
    private function isMsgfmtAvailable() {
        $output = [];
        $return_var = 0;
        exec('which msgfmt 2>/dev/null', $output, $return_var);
        return $return_var === 0;
    }
    
    
    private function compileWithMsgfmt($po_file, $mo_file) {
        $command = "msgfmt -o '{$mo_file}' '{$po_file}' 2>&1";
        $output = [];
        $return_var = 0;
        
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception("msgfmt failed: " . implode("\n", $output));
        }
        
        if (!file_exists($mo_file)) {
            throw new Exception("MO file was not created by msgfmt");
        }
    }
    
    
    private function compileWithPhp($po_file, $mo_file) {
        $po_content = file_get_contents($po_file);
        $translations = $this->parsePoFile($po_content);
        
        $mo_writer = new MoWriter();
        $mo_data = $mo_writer->write($translations);
        
        $result = file_put_contents($mo_file, $mo_data);
        
        if ($result === false) {
            throw new Exception("Failed to write MO file: {$mo_file}");
        }
    }
    
    
    private function parsePoFile($content) {
        $translations = [];
        $lines = explode("\n", $content);
        
        $current_msgid = '';
        $current_msgstr = '';
        $current_context = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (strpos($line, 'msgctxt "') === 0) {
                $current_context = substr($line, 9, -1);
            } elseif (strpos($line, 'msgid "') === 0) {
                $current_msgid = substr($line, 7, -1);
            } elseif (strpos($line, 'msgstr "') === 0) {
                $current_msgstr = substr($line, 8, -1);
                
                if ($current_msgid !== '') {
                    $key = $current_context ? $current_context . "\4" . $current_msgid : $current_msgid;
                    $translations[$key] = $current_msgstr;
                }
                
                $current_msgid = '';
                $current_context = '';
            } elseif (strpos($line, '"') === 0 && strrpos($line, '"') === strlen($line) - 1) {
                $text = substr($line, 1, -1);
                if ($current_msgid !== '') {
                    $current_msgid .= $text;
                } elseif ($current_msgstr !== '') {
                    $current_msgstr .= $text;
                }
            }
        }
        
        return $translations;
    }
    
    
    public function getCustomLanguagesPath() {
        return WP_CONTENT_DIR . '/languages/custom';
    }
    
    
    public function customLanguageFileExists($domain, $locale) {
        $file = WP_CONTENT_DIR . "/languages/custom/{$domain}/{$domain}-{$locale}.mo";
        return file_exists($file);
    }
    
    
    public function getCustomLanguageFilePath($domain, $locale) {
        return WP_CONTENT_DIR . "/languages/custom/{$domain}/{$domain}-{$locale}.mo";
    }
    
    
    public function cleanOldLanguageFiles($days = 30) {
        $custom_dir = $this->getCustomLanguagesPath();
        
        if (!file_exists($custom_dir)) {
            return;
        }
        
        $cutoff_time = time() - ($days * 24 * 60 * 60);
        
        $this->cleanDirectory($custom_dir, $cutoff_time);
    }
    
    
    private function cleanDirectory($dir, $cutoff_time) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            
            if (is_dir($path)) {
                $this->cleanDirectory($path, $cutoff_time);
                if (count(array_diff(scandir($path), ['.', '..'])) === 0) {
                    rmdir($path);
                }
            } else {
                if (filemtime($path) < $cutoff_time) {
                    unlink($path);
                }
            }
        }
    }
}

class MoWriter {
    
    
    public function write($translations) {
        $data = '';
        $data .= pack('V', 0x950412de);
        $data .= pack('V', 0);
        $data .= pack('V', count($translations));
        $data .= pack('V', 28);
        $data .= pack('V', 28 + count($translations) * 8);
        $data .= pack('V', 0);
        $data .= pack('V', 28 + count($translations) * 16);
        
        $strings = '';
        $offsets = '';
        $current_offset = 28 + count($translations) * 16;
        
        foreach ($translations as $msgid => $msgstr) {
            $msgid_len = strlen($msgid);
            $msgstr_len = strlen($msgstr);
            $offsets .= pack('V', $current_offset);
            $offsets .= pack('V', $msgid_len);
            $offsets .= pack('V', $current_offset + $msgid_len);
            $offsets .= pack('V', $msgstr_len);
            
            $strings .= $msgid . "\0" . $msgstr . "\0";
            $current_offset += $msgid_len + $msgstr_len + 2;
        }
        
        $data .= $offsets . $strings;
        
        return $data;
    }
}
