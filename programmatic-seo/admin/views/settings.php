<?php
/**
 * Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pseo-settings">
    <h1><?php _e('Programmatic SEO Settings', 'programmatic-seo'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('pseo_settings'); ?>

        <div class="pseo-row">
            <div class="pseo-col-8">
                <!-- General Settings -->
                <div class="pseo-card">
                    <h2><?php _e('General Settings', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="pseo_max_posts_per_batch"><?php _e('Max Posts per Batch', 'programmatic-seo'); ?></label></th>
                            <td>
                                <input type="number" id="pseo_max_posts_per_batch" name="pseo_max_posts_per_batch"
                                    value="<?php echo esc_attr(get_option('pseo_max_posts_per_batch', 50)); ?>"
                                    min="1" max="500" class="small-text">
                                <p class="description"><?php _e('Maximum number of posts to process in a single batch operation', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SEO Settings -->
                <div class="pseo-card">
                    <h2><?php _e('SEO Optimization', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><?php _e('Auto SEO Optimization', 'programmatic-seo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="pseo_enable_auto_seo" value="1"
                                        <?php checked(get_option('pseo_enable_auto_seo', 1), 1); ?>>
                                    <?php _e('Automatically optimize posts for SEO', 'programmatic-seo'); ?>
                                </label>
                                <p class="description"><?php _e('Auto-generate meta descriptions, optimize titles, add alt text to images', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>

                        <tr>
                            <th><?php _e('Schema Markup', 'programmatic-seo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="pseo_enable_schema_markup" value="1"
                                        <?php checked(get_option('pseo_enable_schema_markup', 1), 1); ?>>
                                    <?php _e('Add Schema.org structured data', 'programmatic-seo'); ?>
                                </label>
                                <p class="description"><?php _e('Automatically add JSON-LD schema markup for better search visibility', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Internal Linking Settings -->
                <div class="pseo-card">
                    <h2><?php _e('Internal Linking', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><?php _e('Auto Internal Linking', 'programmatic-seo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="pseo_enable_internal_linking" value="1"
                                        <?php checked(get_option('pseo_enable_internal_linking', 1), 1); ?>>
                                    <?php _e('Automatically build internal links', 'programmatic-seo'); ?>
                                </label>
                                <p class="description"><?php _e('Automatically create internal links between related posts', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Content Variation Settings -->
                <div class="pseo-card">
                    <h2><?php _e('Content Variation', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="pseo_content_variation_level"><?php _e('Variation Level', 'programmatic-seo'); ?></label></th>
                            <td>
                                <select id="pseo_content_variation_level" name="pseo_content_variation_level" class="regular-text">
                                    <option value="low" <?php selected(get_option('pseo_content_variation_level', 'medium'), 'low'); ?>>
                                        <?php _e('Low (10% variation)', 'programmatic-seo'); ?>
                                    </option>
                                    <option value="medium" <?php selected(get_option('pseo_content_variation_level', 'medium'), 'medium'); ?>>
                                        <?php _e('Medium (30% variation)', 'programmatic-seo'); ?>
                                    </option>
                                    <option value="high" <?php selected(get_option('pseo_content_variation_level', 'medium'), 'high'); ?>>
                                        <?php _e('High (50% variation)', 'programmatic-seo'); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('How much to vary spun content to avoid duplicate content', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Scheduling Settings -->
                <div class="pseo-card">
                    <h2><?php _e('Scheduling', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="pseo_schedule_interval"><?php _e('Publishing Interval', 'programmatic-seo'); ?></label></th>
                            <td>
                                <select id="pseo_schedule_interval" name="pseo_schedule_interval" class="regular-text">
                                    <option value="hourly" <?php selected(get_option('pseo_schedule_interval', 'hourly'), 'hourly'); ?>>
                                        <?php _e('Hourly', 'programmatic-seo'); ?>
                                    </option>
                                    <option value="twicedaily" <?php selected(get_option('pseo_schedule_interval', 'hourly'), 'twicedaily'); ?>>
                                        <?php _e('Twice Daily', 'programmatic-seo'); ?>
                                    </option>
                                    <option value="daily" <?php selected(get_option('pseo_schedule_interval', 'hourly'), 'daily'); ?>>
                                        <?php _e('Daily', 'programmatic-seo'); ?>
                                    </option>
                                </select>
                                <p class="description"><?php _e('How often to check for scheduled posts to publish', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <p class="submit">
                    <button type="submit" name="pseo_save_settings" class="button button-primary button-large">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Save Settings', 'programmatic-seo'); ?>
                    </button>
                </p>
            </div>

            <div class="pseo-col-4">
                <!-- Plugin Info -->
                <div class="pseo-card">
                    <h2><?php _e('Plugin Information', 'programmatic-seo'); ?></h2>
                    <p><strong><?php _e('Version:', 'programmatic-seo'); ?></strong> <?php echo PSEO_VERSION; ?></p>
                    <p><strong><?php _e('Status:', 'programmatic-seo'); ?></strong> <span class="pseo-badge pseo-badge-active"><?php _e('Active', 'programmatic-seo'); ?></span></p>
                </div>

                <!-- System Info -->
                <div class="pseo-card">
                    <h2><?php _e('System Information', 'programmatic-seo'); ?></h2>
                    <ul>
                        <li><strong><?php _e('WordPress Version:', 'programmatic-seo'); ?></strong> <?php echo get_bloginfo('version'); ?></li>
                        <li><strong><?php _e('PHP Version:', 'programmatic-seo'); ?></strong> <?php echo PHP_VERSION; ?></li>
                        <li><strong><?php _e('Database:', 'programmatic-seo'); ?></strong> <?php echo $GLOBALS['wpdb']->db_version(); ?></li>
                    </ul>
                </div>

                <!-- Tools -->
                <div class="pseo-card">
                    <h2><?php _e('Tools', 'programmatic-seo'); ?></h2>

                    <p>
                        <button type="button" class="button button-secondary" id="pseo-cleanup-old-records">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Clean Up Old Records', 'programmatic-seo'); ?>
                        </button>
                    </p>

                    <p>
                        <button type="button" class="button button-secondary" id="pseo-recalculate-stats">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Recalculate Statistics', 'programmatic-seo'); ?>
                        </button>
                    </p>
                </div>

                <!-- Support -->
                <div class="pseo-card">
                    <h2><?php _e('Support & Documentation', 'programmatic-seo'); ?></h2>
                    <p><?php _e('Need help? Check out our documentation and support resources.', 'programmatic-seo'); ?></p>
                    <p>
                        <a href="#" class="button button-secondary" target="_blank">
                            <?php _e('View Documentation', 'programmatic-seo'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#pseo-cleanup-old-records').on('click', function() {
        if (!confirm('<?php esc_attr_e('Clean up old scheduled post records (older than 30 days)?', 'programmatic-seo'); ?>')) {
            return;
        }

        $(this).prop('disabled', true).text('<?php esc_attr_e('Cleaning...', 'programmatic-seo'); ?>');

        $.post(pseoAjax.ajaxurl, {
            action: 'pseo_cleanup_records',
            nonce: pseoAjax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            }
            location.reload();
        });
    });

    $('#pseo-recalculate-stats').on('click', function() {
        $(this).prop('disabled', true).text('<?php esc_attr_e('Recalculating...', 'programmatic-seo'); ?>');

        $.post(pseoAjax.ajaxurl, {
            action: 'pseo_recalculate_stats',
            nonce: pseoAjax.nonce
        }, function(response) {
            alert('<?php esc_attr_e('Statistics recalculated', 'programmatic-seo'); ?>');
            location.reload();
        });
    });
});
</script>
