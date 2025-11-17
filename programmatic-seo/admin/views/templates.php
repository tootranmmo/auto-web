<?php
/**
 * Templates List View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pseo-templates">
    <h1>
        <?php _e('Content Templates', 'programmatic-seo'); ?>
        <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=new'); ?>" class="page-title-action">
            <?php _e('Add New', 'programmatic-seo'); ?>
        </a>
    </h1>

    <?php if (isset($_GET['message'])): ?>
        <?php if ($_GET['message'] === 'saved'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Template saved successfully', 'programmatic-seo'); ?></p>
            </div>
        <?php elseif ($_GET['message'] === 'deleted'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Template deleted successfully', 'programmatic-seo'); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (empty($templates)): ?>
        <div class="pseo-empty-state">
            <div class="pseo-empty-icon dashicons dashicons-editor-table"></div>
            <h2><?php _e('No Templates Yet', 'programmatic-seo'); ?></h2>
            <p><?php _e('Create your first template to start generating programmatic content.', 'programmatic-seo'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=new'); ?>" class="button button-primary button-large">
                <?php _e('Create Your First Template', 'programmatic-seo'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php _e('ID', 'programmatic-seo'); ?></th>
                    <th><?php _e('Template Name', 'programmatic-seo'); ?></th>
                    <th><?php _e('Description', 'programmatic-seo'); ?></th>
                    <th><?php _e('Post Type', 'programmatic-seo'); ?></th>
                    <th><?php _e('Status', 'programmatic-seo'); ?></th>
                    <th><?php _e('Created', 'programmatic-seo'); ?></th>
                    <th><?php _e('Actions', 'programmatic-seo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td><?php echo esc_html($template['id']); ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=edit&template_id=' . $template['id']); ?>">
                                    <?php echo esc_html($template['name']); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html($template['description']); ?></td>
                        <td><span class="pseo-badge"><?php echo esc_html($template['post_type']); ?></span></td>
                        <td><span class="pseo-badge pseo-badge-<?php echo esc_attr($template['post_status']); ?>"><?php echo esc_html($template['post_status']); ?></span></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($template['created_at']))); ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=edit&template_id=' . $template['id']); ?>" class="button button-small">
                                <?php _e('Edit', 'programmatic-seo'); ?>
                            </a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=programmatic-seo-templates&action=delete&template_id=' . $template['id']), 'delete_template_' . $template['id']); ?>"
                               class="button button-small button-link-delete"
                               onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this template?', 'programmatic-seo'); ?>')">
                                <?php _e('Delete', 'programmatic-seo'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
