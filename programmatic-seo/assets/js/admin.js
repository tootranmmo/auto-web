/**
 * Programmatic SEO Admin JavaScript
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        PSEO_Admin.init();
    });

    var PSEO_Admin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Template preview
            $('#pseo-preview-template').on('click', this.previewTemplate);

            // CSV file preview
            $('#csv_file').on('change', this.previewCSV);

            // Delete confirmations
            $('.button-link-delete').on('click', function(e) {
                if (!confirm(pseoAjax.strings.confirmDelete)) {
                    e.preventDefault();
                    return false;
                }
            });

            // Dynamic form updates
            this.handleDynamicForms();
        },

        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            // Add tooltips to elements with data-tooltip attribute
            $('[data-tooltip]').each(function() {
                var $this = $(this);
                var tooltipText = $this.data('tooltip');

                $this.attr('title', tooltipText);
            });
        },

        /**
         * Preview template
         */
        previewTemplate: function(e) {
            e.preventDefault();

            var template = $('#content_template').val();
            var sampleData = {
                city: 'New York',
                service: 'Plumbing',
                price: '299',
                keyword: 'emergency plumber',
                date: new Date().getFullYear()
            };

            $.post(pseoAjax.ajaxurl, {
                action: 'pseo_preview_template',
                nonce: pseoAjax.nonce,
                template: template,
                sample_data: sampleData
            }, function(response) {
                if (response.success) {
                    var modal = $('<div class="pseo-modal">')
                        .html('<div class="pseo-modal-content"><h2>Template Preview</h2><div class="pseo-modal-body">' + response.data.preview + '</div><button class="button pseo-modal-close">Close</button></div>')
                        .appendTo('body');

                    modal.find('.pseo-modal-close').on('click', function() {
                        modal.remove();
                    });
                }
            });
        },

        /**
         * Preview CSV file
         */
        previewCSV: function() {
            var file = this.files[0];

            if (!file) {
                return;
            }

            var reader = new FileReader();

            reader.onload = function(e) {
                var csv = e.target.result;
                var lines = csv.split('\n').slice(0, 6); // First 6 lines (header + 5 rows)

                if (lines.length === 0) {
                    return;
                }

                var html = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
                var headers = lines[0].split(',');

                headers.forEach(function(header) {
                    html += '<th>' + header.trim() + '</th>';
                });

                html += '</tr></thead><tbody>';

                for (var i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;

                    html += '<tr>';
                    var cells = lines[i].split(',');

                    cells.forEach(function(cell) {
                        html += '<td>' + cell.trim() + '</td>';
                    });

                    html += '</tr>';
                }

                html += '</tbody></table>';

                $('#pseo-csv-preview').show().find('#pseo-preview-content').html(html);
            };

            reader.readAsText(file);
        },

        /**
         * Handle dynamic forms
         */
        handleDynamicForms: function() {
            // Add variable to template
            $('.pseo-add-variable').on('click', function(e) {
                e.preventDefault();

                var variable = $(this).data('variable');
                var textarea = $('#content_template');
                var cursorPos = textarea.prop('selectionStart');
                var textBefore = textarea.val().substring(0, cursorPos);
                var textAfter = textarea.val().substring(cursorPos);

                textarea.val(textBefore + '{{' + variable + '}}' + textAfter);

                // Move cursor
                var newPos = cursorPos + variable.length + 4;
                textarea.prop('selectionStart', newPos);
                textarea.prop('selectionEnd', newPos);
                textarea.focus();
            });

            // Character counter for meta description
            $('#meta_description_template').on('input', function() {
                var length = $(this).val().length;
                var counter = $(this).siblings('.char-counter');

                if (counter.length === 0) {
                    counter = $('<div class="char-counter"></div>').insertAfter($(this));
                }

                var color = length >= 150 && length <= 160 ? 'green' : (length > 160 ? 'red' : 'orange');
                counter.html('<span style="color: ' + color + '">' + length + ' characters</span> (recommended: 150-160)');
            }).trigger('input');

            // Title counter
            $('#title_template').on('input', function() {
                var length = $(this).val().length;
                var counter = $(this).siblings('.char-counter');

                if (counter.length === 0) {
                    counter = $('<div class="char-counter"></div>').insertAfter($(this));
                }

                var color = length >= 30 && length <= 60 ? 'green' : (length > 70 ? 'red' : 'orange');
                counter.html('<span style="color: ' + color + '">' + length + ' characters</span> (recommended: 30-60)');
            }).trigger('input');
        },

        /**
         * Show notification
         */
        showNotification: function(message, type) {
            type = type || 'success';

            var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').after(notice);

            // Auto dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Update progress bar
         */
        updateProgress: function(percent, status) {
            $('.pseo-progress-fill').css('width', percent + '%');
            $('.pseo-progress-text').text(percent + '%');

            if (status) {
                $('.pseo-progress-status').text(status);
            }
        },

        /**
         * Confirm action
         */
        confirmAction: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        },

        /**
         * AJAX helper
         */
        ajax: function(action, data, successCallback, errorCallback) {
            data = data || {};
            data.action = action;
            data.nonce = pseoAjax.nonce;

            $.ajax({
                url: pseoAjax.ajaxurl,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success && successCallback) {
                        successCallback(response.data);
                    } else if (!response.success && errorCallback) {
                        errorCallback(response.data);
                    }
                },
                error: function() {
                    if (errorCallback) {
                        errorCallback({message: pseoAjax.strings.error});
                    }
                }
            });
        }
    };

    // Make PSEO_Admin globally available
    window.PSEO_Admin = PSEO_Admin;

    // Additional helper functions

    /**
     * Format number with commas
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * Truncate text
     */
    function truncateText(text, maxLength) {
        if (text.length <= maxLength) {
            return text;
        }
        return text.substr(0, maxLength) + '...';
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Export utilities
    window.PSEOUtils = {
        formatNumber: formatNumber,
        truncateText: truncateText,
        escapeHtml: escapeHtml
    };

})(jQuery);
