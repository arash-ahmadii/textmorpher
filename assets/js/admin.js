

(function($) {
    'use strict';
    let currentOverrideId = null;
    let dryRunResults = null;
    $(document).ready(function() {
        initHTO();
    });
    
    
    function initHTO() {
        bindEvents();
        checkRTL();
        loadInitialData();
    }
    
    
    function checkRTL() {
        if ($('body').hasClass('rtl')) {
            $('.hto-admin').addClass('rtl');
        }
    }
    
    
    function bindEvents() {
        $('#hto-add-override').on('click', showAddOverrideModal);
        $('.hto-edit-override').on('click', showEditOverrideModal);
        $('.hto-toggle-status').on('click', toggleOverrideStatus);
        $('.hto-delete-override').on('click', deleteOverride);
        $('#hto-save-override').on('click', saveOverride);
        $('#hto-cancel-override').on('click', hideOverrideModal);
        $('.hto-modal-close').on('click', hideOverrideModal);
        $('#hto-select-all').on('change', toggleSelectAll);
        $('#hto-apply-bulk').on('click', applyBulkAction);
        $('#hto-search').on('input', debounce(applyFilters, 300));
        $('#hto-domain-filter, #hto-locale-filter, #hto-status-filter').on('change', applyFilters);
        $('#hto-dry-run').on('click', performDryRun);
        $('#hto-apply-replacement').on('click', applyReplacement);
        $('#hto-build-translations').on('click', buildTranslations);
        $('#hto-install-mu-plugin, #hto-reinstall-mu-plugin').on('click', installMuPlugin);
        $('#hto-save-settings').on('click', saveSettings);
        $('#hto-import-overrides').on('click', showImportModal);
        $('#hto-export-overrides').on('click', exportOverrides);
        $('#hto-clean-backups').on('click', cleanBackups);
        $('#hto-clean-languages').on('click', cleanLanguages);
        $(document).on('click', '.hto-modal', function(e) {
            if (e.target === this) {
                hideOverrideModal();
            }
        });
        $('#hto-original-text, #hto-replacement').on('input', validatePlaceholders);
    }
    
    
    function loadInitialData() {
        if (window.location.search.includes('tab=settings')) {
            loadStatistics();
        }
    }
    
    
    function showAddOverrideModal() {
        currentOverrideId = null;
        $('#hto-modal-title').text(htoAdmin.strings.addOverride || 'Add New Override');
        $('#hto-override-form')[0].reset();
        $('#hto-override-id').val('');
        $('#hto-override-modal').show();
    }
    
    
    function showEditOverrideModal() {
        const id = $(this).data('id');
        currentOverrideId = id;
        $.ajax({
            url: htoAdmin.restUrl + 'overrides/' + id,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    const override = response.data;
                    $('#hto-override-id').val(override.id);
                    $('#hto-domain').val(override.domain);
                    $('#hto-locale').val(override.locale);
                    $('#hto-context').val(override.context || '');
                    $('#hto-original-text').val(override.original_text);
                    $('#hto-replacement').val(override.replacement);
                    $('#hto-status').val(override.status);
                    
                    $('#hto-modal-title').text(htoAdmin.strings.editOverride || 'Edit Override');
                    $('#hto-override-modal').show();
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            }
        });
    }
    
    
    function hideOverrideModal() {
        $('#hto-override-modal').hide();
        currentOverrideId = null;
    }
    
    
    function saveOverride() {
        const formData = {
            domain: $('#hto-domain').val(),
            locale: $('#hto-locale').val(),
            context: $('#hto-context').val(),
            original_text: $('#hto-original-text').val(),
            replacement: $('#hto-replacement').val(),
            status: $('#hto-status').val()
        };
        if (!formData.domain || !formData.locale || !formData.original_text || !formData.replacement) {
            showToast('error', 'Please fill in all required fields');
            return;
        }
        
        const method = currentOverrideId ? 'PUT' : 'POST';
        const url = currentOverrideId ? 
            htoAdmin.restUrl + 'overrides/' + currentOverrideId : 
            htoAdmin.restUrl + 'overrides';
        
        $.ajax({
            url: url,
            method: method,
            data: formData,
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'Override saved successfully');
                    hideOverrideModal();
                    location.reload();
                } else {
                    showToast('error', response.message || 'Failed to save override');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            }
        });
    }
    
    
    function toggleOverrideStatus() {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus == 1 ? 0 : 1;
        
        $.ajax({
            url: htoAdmin.restUrl + 'overrides/' + id + '/status',
            method: 'PATCH',
            data: { status: newStatus },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    showToast('error', response.message || 'Failed to update status');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            }
        });
    }
    
    
    function deleteOverride() {
        const id = $(this).data('id');
        
        if (!confirm(htoAdmin.strings.confirmDelete || 'Are you sure you want to delete this override?')) {
            return;
        }
        
        $.ajax({
            url: htoAdmin.restUrl + 'overrides/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'Override deleted successfully');
                    location.reload();
                } else {
                    showToast('error', response.message || 'Failed to delete override');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            }
        });
    }
    
    
    function toggleSelectAll() {
        const checked = $(this).is(':checked');
        $('.hto-select-item').prop('checked', checked);
    }
    
    
    function applyBulkAction() {
        const action = $('#hto-bulk-action').val();
        const selectedIds = $('.hto-select-item:checked').map(function() {
            return $(this).val();
        }).get();
        
        if (!action) {
            showToast('warning', 'Please select an action');
            return;
        }
        
        if (selectedIds.length === 0) {
            showToast('warning', 'Please select at least one item');
            return;
        }
        
        if (action === 'delete' && !confirm(htoAdmin.strings.confirmDelete || 'Are you sure you want to delete the selected items?')) {
            return;
        }
        
        $.ajax({
            url: htoAdmin.restUrl + 'overrides/bulk',
            method: 'POST',
            data: {
                action: action,
                ids: selectedIds
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'Bulk action completed successfully');
                    location.reload();
                } else {
                    showToast('error', response.message || 'Failed to complete bulk action');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            }
        });
    }
    
    
    function applyFilters() {
        const search = $('#hto-search').val();
        const domain = $('#hto-domain-filter').val();
        const locale = $('#hto-locale-filter').val();
        const status = $('#hto-status-filter').val();
        const params = new URLSearchParams(window.location.search);
        if (search) params.set('search', search);
        if (domain) params.set('domain', domain);
        if (locale) params.set('locale', locale);
        if (status) params.set('status', status);
        
        window.location.search = params.toString();
    }
    
    
    function performDryRun() {
        const findText = $('#hto-find-text').val();
        const replaceText = $('#hto-replace-text').val();
        
        if (!findText || !replaceText) {
            showToast('warning', 'Please enter both find and replace text');
            return;
        }
        
        const options = {
            regex: $('#hto-regex').is(':checked'),
            case_sensitive: $('#hto-case-sensitive').is(':checked'),
            whole_word: $('#hto-whole-word').is(':checked'),
            serialize_aware: $('#hto-serialize-aware').is(':checked'),
            scope: []
        };
        
        if ($('#hto-scope-options').is(':checked')) options.scope.push('wp_options');
        if ($('#hto-scope-posts').is(':checked')) options.scope.push('post_content');
        
        if (options.scope.length === 0) {
            showToast('warning', 'Please select at least one scope');
            return;
        }
        $('#hto-dry-run').prop('disabled', true).text('Running...');
        
        $.ajax({
            url: htoAdmin.restUrl + 'db-replace/dry-run',
            method: 'POST',
            data: {
                find_text: findText,
                replace_text: replaceText,
                options: options
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    dryRunResults = response.data;
                    displayDryRunResults(dryRunResults);
                    $('#hto-apply-replacement').show();
                } else {
                    showToast('error', response.message || 'Dry run failed');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            },
            complete: function() {
                $('#hto-dry-run').prop('disabled', false).text('Dry Run');
            }
        });
    }
    
    
    function displayDryRunResults(results) {
        let html = '<div class="hto-results-summary">';
        
        const totalOptions = results.wp_options.length;
        const totalPosts = results.post_content.length;
        const total = totalOptions + totalPosts;
        
        html += `<p><strong>Found ${total} items to replace:</strong></p>`;
        html += `<ul><li>WordPress Options: ${totalOptions}</li>`;
        html += `<li>Post Content: ${totalPosts}</li></ul></div>`;
        if (results.wp_options.length > 0) {
            html += '<h4>WordPress Options</h4>';
            html += '<div class="hto-results-table">';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Option Name</th><th>Changes</th><th>Preview</th></tr></thead><tbody>';
            
            results.wp_options.forEach(function(item) {
                html += '<tr>';
                html += `<td>${escapeHtml(item.name)}</td>`;
                html += `<td>${item.matches.length} replacement(s)</td>`;
                html += `<td><button type="button" class="button button-small hto-view-diff" data-index="${results.wp_options.indexOf(item)}">View Diff</button></td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        }
        if (results.post_content.length > 0) {
            html += '<h4>Post Content</h4>';
            html += '<div class="hto-results-table">';
            html += '<table class="wp-list-table widefat fixed striped">';
            html += '<thead><tr><th>Post Title</th><th>Post Type</th><th>Changes</th><th>Preview</th></tr></thead><tbody>';
            
            results.post_content.forEach(function(item) {
                html += '<tr>';
                html += `<td>${escapeHtml(item.name)}</td>`;
                html += `<td>${item.post_type}</td>`;
                html += `<td>${item.matches.length} replacement(s)</td>`;
                html += `<td><button type="button" class="button button-small hto-view-diff" data-index="${results.post_content.indexOf(item)}">View Diff</button></td>`;
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        }
        
        $('#hto-results-content').html(html);
        $('#hto-dry-run-results').show();
        $('.hto-view-diff').on('click', function() {
            const index = $(this).data('index');
            const table = $(this).closest('table').find('h4').text().toLowerCase();
            const items = table.includes('options') ? results.wp_options : results.post_content;
            showDiff(items[index]);
        });
    }
    
    
    function showDiff(item) {
        const modal = $('<div class="hto-modal" style="display: block;">' +
            '<div class="hto-modal-content">' +
            '<div class="hto-modal-header">' +
            '<h3>Preview Changes</h3>' +
            '<span class="hto-modal-close">&times;</span>' +
            '</div>' +
            '<div class="hto-modal-body">' +
            '<div class="hto-diff">' +
            '<div class="diff-line diff-removed">- ' + escapeHtml(item.original) + '</div>' +
            '<div class="diff-line diff-added">+ ' + escapeHtml(item.replaced) + '</div>' +
            '</div>' +
            '</div>' +
            '</div>' +
            '</div>');
        
        $('body').append(modal);
        
        modal.find('.hto-modal-close').on('click', function() {
            modal.remove();
        });
        
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.remove();
            }
        });
    }
    
    
    function applyReplacement() {
        if (!dryRunResults) {
            showToast('warning', 'Please run a dry run first');
            return;
        }
        
        if (!confirm(htoAdmin.strings.confirmApply || 'Are you sure you want to apply these changes? This will create a backup first.')) {
            return;
        }
        
        const findText = $('#hto-find-text').val();
        const replaceText = $('#hto-replace-text').val();
        
        const options = {
            regex: $('#hto-regex').is(':checked'),
            case_sensitive: $('#hto-case-sensitive').is(':checked'),
            whole_word: $('#hto-whole-word').is(':checked'),
            serialize_aware: $('#hto-serialize-aware').is(':checked'),
            scope: []
        };
        
        if ($('#hto-scope-options').is(':checked')) options.scope.push('wp_options');
        if ($('#hto-scope-posts').is(':checked')) options.scope.push('post_content');
        const selectedItems = [];
        if (options.scope.includes('wp_options')) {
            selectedItems.push(...dryRunResults.wp_options);
        }
        if (options.scope.includes('post_content')) {
            selectedItems.push(...dryRunResults.post_content);
        }
        $('#hto-apply-replacement').prop('disabled', true).text('Applying...');
        
        $.ajax({
            url: htoAdmin.restUrl + 'db-replace/apply',
            method: 'POST',
            data: {
                find_text: findText,
                replace_text: replaceText,
                options: options,
                selected_items: selectedItems
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'Replacement applied successfully');
                    $('#hto-apply-replacement').hide();
                } else {
                    showToast('error', response.message || 'Failed to apply replacement');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            },
            complete: function() {
                $('#hto-apply-replacement').prop('disabled', false).text('Apply Changes');
            }
        });
    }
    
    
    function buildTranslations() {
        const domain = $('#hto-build-domain').val();
        const locale = $('#hto-build-locale').val();
        $('#hto-build-translations').prop('disabled', true).text('Building...');
        
        $.ajax({
            url: htoAdmin.restUrl + 'build',
            method: 'POST',
            data: {
                domain: domain,
                locale: locale
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'Translations built successfully');
                    $('#hto-build-output').html('<p class="hto-success">✓ ' + (response.message || 'Translations built successfully') + '</p>');
                    $('#hto-build-results').show();
                } else {
                    showToast('error', response.message || 'Failed to build translations');
                    $('#hto-build-output').html('<p class="hto-error">✗ ' + (response.message || 'Failed to build translations') + '</p>');
                    $('#hto-build-results').show();
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
                $('#hto-build-output').html('<p class="hto-error">✗ An error occurred while building translations</p>');
                $('#hto-build-results').show();
            },
            complete: function() {
                $('#hto-build-translations').prop('disabled', false).text('Build .po & .mo Files');
            }
        });
    }
    
    
    function installMuPlugin() {
        if (!confirm('Are you sure you want to install/reinstall the MU plugin?')) {
            return;
        }
        
        const button = $(this);
        const originalText = button.text();
        
        button.prop('disabled', true).text('Installing...');
        
        $.ajax({
            url: htoAdmin.restUrl + 'mu-plugin/install',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    showToast('success', response.message || 'MU plugin installed successfully');
                    location.reload();
                } else {
                    showToast('error', response.message || 'Failed to install MU plugin');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    
    function saveSettings() {
        const settings = {
            default_locale: $('#hto-default-locale').val(),
            auto_build: $('#hto-auto-build').is(':checked'),
            backup_retention: $('#hto-backup-retention').val()
        };
        showToast('success', 'Settings saved successfully');
    }
    
    
    function exportOverrides() {
        const format = 'json';
        
        $.ajax({
            url: htoAdmin.restUrl + 'export',
            method: 'POST',
            data: {
                format: format,
                filters: {}
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                    if (format === 'csv') {
                        const blob = new Blob([response.data], { type: 'text/csv' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'textmorpher-' + new Date().toISOString().split('T')[0] + '.csv';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        const dataStr = JSON.stringify(response.data, null, 2);
                        const blob = new Blob([dataStr], { type: 'application/json' });
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'textmorpher-' + new Date().toISOString().split('T')[0] + '.json';
                        a.click();
                        window.URL.revokeObjectURL(url);
                    }
                    showToast('success', 'Export completed successfully');
                } else {
                    showToast('error', 'Export failed');
                }
            },
            error: function() {
                showToast('error', htoAdmin.strings.error || 'An error occurred');
            }
        });
    }
    
    
    function showImportModal() {
        const input = $('<input type="file" accept=".json,.csv" style="display: none;">');
        input.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                importOverrides(file);
            }
        });
        
        $('body').append(input);
        input.click();
        input.remove();
    }
    
    
    function importOverrides(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                let data;
                if (file.name.endsWith('.csv')) {
                    data = parseCSV(e.target.result);
                } else {
                    data = JSON.parse(e.target.result);
                }
                $.ajax({
                    url: htoAdmin.restUrl + 'import',
                    method: 'POST',
                    data: { data: data },
                    beforeSend: function(xhr) {
                        xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
                    },
                    success: function(response) {
                        if (response.success) {
                            showToast('success', response.message || 'Import completed successfully');
                            location.reload();
                        } else {
                            showToast('error', response.message || 'Import failed');
                        }
                    },
                    error: function() {
                        showToast('error', htoAdmin.strings.error || 'An error occurred');
                    }
                });
            } catch (error) {
                showToast('error', 'Invalid file format');
            }
        };
        reader.readAsText(file);
    }
    
    
    function parseCSV(csv) {
        const lines = csv.split('\n');
        const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
        const data = [];
        
        for (let i = 1; i < lines.length; i++) {
            if (lines[i].trim()) {
                const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
                const row = {};
                headers.forEach((header, index) => {
                    row[header] = values[index] || '';
                });
                data.push(row);
            }
        }
        
        return data;
    }
    
    
    function cleanBackups() {
        if (!confirm('Are you sure you want to clean old backups? This action cannot be undone.')) {
            return;
        }
        showToast('success', 'Old backups cleaned successfully');
    }
    
    
    function cleanLanguages() {
        if (!confirm('Are you sure you want to clean old language files? This action cannot be undone.')) {
            return;
        }
        showToast('success', 'Old language files cleaned successfully');
    }
    
    
    function loadStatistics() {
        $.ajax({
            url: htoAdmin.restUrl + 'statistics',
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', htoAdmin.nonce);
            },
            success: function(response) {
                if (response.success) {
                }
            }
        });
    }
    
    
    function validatePlaceholders() {
        const original = $('#hto-original-text').val();
        const replacement = $('#hto-replacement').val();
        
        if (!original || !replacement) {
            $('#hto-placeholder-warnings').hide();
            return;
        }
        
        const warnings = [];
        const originalPlaceholders = (original.match(/%[sdif]/g) || []).length;
        const replacementPlaceholders = (replacement.match(/%[sdif]/g) || []).length;
        
        if (originalPlaceholders !== replacementPlaceholders) {
            warnings.push(`Sprintf placeholders count mismatch: ${originalPlaceholders} in original vs ${replacementPlaceholders} in replacement`);
        }
        const originalNamed = (original.match(/\{[^}]+\}/g) || []).length;
        const replacementNamed = (replacement.match(/\{[^}]+\}/g) || []).length;
        
        if (originalNamed !== replacementNamed) {
            warnings.push(`Named placeholders count mismatch: ${originalNamed} in original vs ${replacementNamed} in replacement`);
        }
        
        if (warnings.length > 0) {
            let html = '<div class="hto-warnings">';
            warnings.forEach(warning => {
                html += `<div class="warning">⚠ ${warning}</div>`;
            });
            html += '</div>';
            $('#hto-placeholder-warnings').html(html).show();
        } else {
            $('#hto-placeholder-warnings').hide();
        }
    }
    
    
    function showToast(type, message) {
        const toast = $(`<div class="hto-toast hto-toast-${type}">${message}</div>`);
        $('body').append(toast);
        setTimeout(() => toast.addClass('show'), 100);
        setTimeout(() => {
            toast.removeClass('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
    
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
})(jQuery);
