<?php
/**
 * CSV Import View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pseo-import">
    <h1><?php _e('Import CSV & Generate Content', 'programmatic-seo'); ?></h1>

    <div class="pseo-row">
        <div class="pseo-col-8">
            <!-- Import Form -->
            <div class="pseo-card">
                <h2><?php _e('Upload CSV File', 'programmatic-seo'); ?></h2>

                <form id="pseo-import-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('pseo_import_csv'); ?>

                    <table class="form-table">
                        <tr>
                            <th><label for="template_id"><?php _e('Select Template', 'programmatic-seo'); ?> *</label></th>
                            <td>
                                <select id="template_id" name="template_id" class="regular-text" required>
                                    <option value=""><?php _e('-- Select Template --', 'programmatic-seo'); ?></option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo esc_attr($template['id']); ?>">
                                            <?php echo esc_html($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php _e('Choose which template to use for content generation', 'programmatic-seo'); ?>
                                    <?php if (empty($templates)): ?>
                                        <br><strong><?php _e('No templates available.', 'programmatic-seo'); ?>
                                        <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=new'); ?>">
                                            <?php _e('Create one first', 'programmatic-seo'); ?>
                                        </a></strong>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="csv_file"><?php _e('CSV File', 'programmatic-seo'); ?> *</label></th>
                            <td>
                                <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                                <p class="description">
                                    <?php _e('Upload a CSV file with columns matching your template variables', 'programmatic-seo'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th><label for="schedule_posts"><?php _e('Scheduling', 'programmatic-seo'); ?></label></th>
                            <td>
                                <label>
                                    <input type="radio" name="schedule_posts" value="no" checked>
                                    <?php _e('Publish immediately', 'programmatic-seo'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="radio" name="schedule_posts" value="yes">
                                    <?php _e('Schedule for later', 'programmatic-seo'); ?>
                                </label>

                                <div id="schedule-options" style="display: none; margin-top: 10px;">
                                    <label>
                                        <?php _e('Start Date & Time:', 'programmatic-seo'); ?>
                                        <input type="datetime-local" name="schedule_start" class="regular-text">
                                    </label>
                                    <br><br>
                                    <label>
                                        <?php _e('Interval (minutes):', 'programmatic-seo'); ?>
                                        <input type="number" name="schedule_interval" value="60" min="1" class="small-text">
                                        <span class="description"><?php _e('Time between each post', 'programmatic-seo'); ?></span>
                                    </label>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large" id="pseo-import-submit">
                            <span class="dashicons dashicons-upload"></span>
                            <?php _e('Import & Generate Content', 'programmatic-seo'); ?>
                        </button>
                    </p>
                </form>

                <!-- Progress Bar -->
                <div id="pseo-import-progress" style="display: none;">
                    <h3><?php _e('Import Progress', 'programmatic-seo'); ?></h3>
                    <div class="pseo-progress-bar">
                        <div class="pseo-progress-fill" style="width: 0%"></div>
                    </div>
                    <p class="pseo-progress-text">0%</p>
                    <p class="pseo-progress-status"><?php _e('Processing...', 'programmatic-seo'); ?></p>
                </div>

                <!-- Results -->
                <div id="pseo-import-results" style="display: none;">
                    <h3><?php _e('Import Results', 'programmatic-seo'); ?></h3>
                    <div class="pseo-results-content"></div>
                </div>
            </div>

            <!-- CSV Preview -->
            <div class="pseo-card" id="pseo-csv-preview" style="display: none;">
                <h2><?php _e('CSV Preview', 'programmatic-seo'); ?></h2>
                <div id="pseo-preview-content"></div>
            </div>
        </div>

        <div class="pseo-col-4">
            <!-- Instructions -->
            <div class="pseo-card">
                <h2><?php _e('How to Prepare Your CSV', 'programmatic-seo'); ?></h2>
                <ol>
                    <li><?php _e('Create a CSV file with a header row', 'programmatic-seo'); ?></li>
                    <li><?php _e('Column names must match template variables', 'programmatic-seo'); ?></li>
                    <li><?php _e('Each row will generate one post', 'programmatic-seo'); ?></li>
                    <li><?php _e('Save as CSV (UTF-8) format', 'programmatic-seo'); ?></li>
                </ol>

                <h3><?php _e('Example CSV Structure', 'programmatic-seo'); ?></h3>
                <pre>city,service,price
New York,Plumbing,299
Los Angeles,Electrical,399
Chicago,HVAC,499</pre>
            </div>

            <!-- Sample CSV Download -->
            <div class="pseo-card">
                <h2><?php _e('Download Sample CSV', 'programmatic-seo'); ?></h2>
                <p><?php _e('Download a sample CSV file to see the correct format:', 'programmatic-seo'); ?></p>
                <button type="button" class="button button-secondary" id="pseo-download-sample">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Download Sample CSV', 'programmatic-seo'); ?>
                </button>
            </div>

            <!-- Tips -->
            <div class="pseo-card pseo-tips">
                <h2><?php _e('Pro Tips', 'programmatic-seo'); ?></h2>
                <ul>
                    <li><?php _e('Start with a small batch to test your template', 'programmatic-seo'); ?></li>
                    <li><?php _e('Use scheduling to spread posts over time', 'programmatic-seo'); ?></li>
                    <li><?php _e('Enable auto-SEO in settings for optimization', 'programmatic-seo'); ?></li>
                    <li><?php _e('Review generated content before publishing', 'programmatic-seo'); ?></li>
                </ul>
            </div>

            <!-- Statistics -->
            <div class="pseo-card">
                <h2><?php _e('Recent Imports', 'programmatic-seo'); ?></h2>
                <?php
                global $wpdb;
                $recent_imports = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}pseo_imports ORDER BY created_at DESC LIMIT 5",
                    ARRAY_A
                );

                if (!empty($recent_imports)):
                ?>
                    <ul class="pseo-recent-imports">
                        <?php foreach ($recent_imports as $import): ?>
                            <li>
                                <strong><?php echo esc_html($import['file_name']); ?></strong>
                                <br>
                                <small>
                                    <?php printf(__('%d of %d rows processed', 'programmatic-seo'), $import['success_rows'], $import['total_rows']); ?>
                                    <br>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($import['created_at']))); ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php _e('No imports yet', 'programmatic-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle schedule options
    $('input[name="schedule_posts"]').on('change', function() {
        if ($(this).val() === 'yes') {
            $('#schedule-options').slideDown();
        } else {
            $('#schedule-options').slideUp();
        }
    });

    // Download sample CSV
    $('#pseo-download-sample').on('click', function() {
        var csv = 'city,service,price\n';
        csv += 'New York,Plumbing,299\n';
        csv += 'Los Angeles,Electrical,399\n';
        csv += 'Chicago,HVAC,499\n';

        var blob = new Blob([csv], { type: 'text/csv' });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'sample-import.csv';
        a.click();
    });

    // Handle form submission
    $('#pseo-import-form').on('submit', function(e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'pseo_import_csv');
        formData.append('nonce', pseoAjax.nonce);

        $('#pseo-import-submit').prop('disabled', true);
        $('#pseo-import-progress').show();
        $('#pseo-import-results').hide();

        $.ajax({
            url: pseoAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#pseo-import-progress').hide();
                $('#pseo-import-results').show();

                if (response.success) {
                    var html = '<div class="notice notice-success"><p>' + response.data.message + '</p></div>';
                    html += '<ul>';
                    html += '<li>' + pseoAjax.strings.success + ': ' + response.data.success + '</li>';
                    if (response.data.failed > 0) {
                        html += '<li>' + pseoAjax.strings.error + ': ' + response.data.failed + '</li>';
                    }
                    html += '</ul>';

                    $('.pseo-results-content').html(html);
                } else {
                    $('.pseo-results-content').html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
                }

                $('#pseo-import-submit').prop('disabled', false);
            },
            error: function() {
                $('#pseo-import-progress').hide();
                $('#pseo-import-results').show();
                $('.pseo-results-content').html('<div class="notice notice-error"><p>' + pseoAjax.strings.error + '</p></div>');
                $('#pseo-import-submit').prop('disabled', false);
            }
        });
    });
});
</script>
