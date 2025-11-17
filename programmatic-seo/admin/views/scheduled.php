<?php
/**
 * Scheduled Posts View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pseo-scheduled">
    <h1><?php _e('Scheduled Posts', 'programmatic-seo'); ?></h1>

    <?php if (empty($scheduled_posts)): ?>
        <div class="pseo-empty-state">
            <div class="pseo-empty-icon dashicons dashicons-clock"></div>
            <h2><?php _e('No Scheduled Posts', 'programmatic-seo'); ?></h2>
            <p><?php _e('You don\'t have any posts scheduled for publishing.', 'programmatic-seo'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=programmatic-seo-import'); ?>" class="button button-primary">
                <?php _e('Import CSV to Schedule Posts', 'programmatic-seo'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', 'programmatic-seo'); ?></th>
                    <th><?php _e('Template', 'programmatic-seo'); ?></th>
                    <th><?php _e('Scheduled Time', 'programmatic-seo'); ?></th>
                    <th><?php _e('Time Until', 'programmatic-seo'); ?></th>
                    <th><?php _e('Status', 'programmatic-seo'); ?></th>
                    <th><?php _e('Actions', 'programmatic-seo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($scheduled_posts as $scheduled): ?>
                    <tr>
                        <td><?php echo esc_html($scheduled['id']); ?></td>
                        <td><strong><?php echo esc_html($scheduled['template_name'] ?: 'N/A'); ?></strong></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($scheduled['schedule_time']))); ?></td>
                        <td><?php echo human_time_diff(current_time('timestamp'), strtotime($scheduled['schedule_time'])); ?></td>
                        <td><span class="pseo-badge pseo-badge-<?php echo esc_attr($scheduled['status']); ?>"><?php echo esc_html($scheduled['status']); ?></span></td>
                        <td>
                            <button class="button button-small pseo-publish-now" data-id="<?php echo esc_attr($scheduled['id']); ?>">
                                <?php _e('Publish Now', 'programmatic-seo'); ?>
                            </button>
                            <button class="button button-small button-link-delete pseo-cancel-scheduled" data-id="<?php echo esc_attr($scheduled['id']); ?>">
                                <?php _e('Cancel', 'programmatic-seo'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Publish now
    $('.pseo-publish-now').on('click', function() {
        if (!confirm('<?php esc_attr_e('Publish this post immediately?', 'programmatic-seo'); ?>')) {
            return;
        }

        var id = $(this).data('id');
        var button = $(this);

        button.prop('disabled', true);

        $.post(pseoAjax.ajaxurl, {
            action: 'pseo_publish_now',
            nonce: pseoAjax.nonce,
            scheduled_id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message);
                button.prop('disabled', false);
            }
        });
    });

    // Cancel scheduled
    $('.pseo-cancel-scheduled').on('click', function() {
        if (!confirm('<?php esc_attr_e('Cancel this scheduled post?', 'programmatic-seo'); ?>')) {
            return;
        }

        var id = $(this).data('id');
        var button = $(this);

        button.prop('disabled', true);

        $.post(pseoAjax.ajaxurl, {
            action: 'pseo_cancel_scheduled',
            nonce: pseoAjax.nonce,
            scheduled_id: id
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message);
                button.prop('disabled', false);
            }
        });
    });
});
</script>
