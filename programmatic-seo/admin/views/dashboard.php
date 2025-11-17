<?php
/**
 * Dashboard View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pseo-dashboard">
    <h1><?php _e('Programmatic SEO Dashboard', 'programmatic-seo'); ?></h1>

    <div class="pseo-stats-grid">
        <!-- Scheduled Posts Stats -->
        <div class="pseo-stat-card">
            <div class="pseo-stat-icon dashicons dashicons-clock"></div>
            <div class="pseo-stat-content">
                <h3><?php _e('Scheduled Posts', 'programmatic-seo'); ?></h3>
                <div class="pseo-stat-number"><?php echo esc_html($stats['pending']); ?></div>
                <p class="pseo-stat-label"><?php _e('Pending', 'programmatic-seo'); ?></p>
            </div>
        </div>

        <!-- Published Stats -->
        <div class="pseo-stat-card">
            <div class="pseo-stat-icon dashicons dashicons-yes-alt"></div>
            <div class="pseo-stat-content">
                <h3><?php _e('Published', 'programmatic-seo'); ?></h3>
                <div class="pseo-stat-number"><?php echo esc_html($stats['published']); ?></div>
                <p class="pseo-stat-label"><?php _e('Total Published', 'programmatic-seo'); ?></p>
            </div>
        </div>

        <!-- Internal Links Stats -->
        <div class="pseo-stat-card">
            <div class="pseo-stat-icon dashicons dashicons-admin-links"></div>
            <div class="pseo-stat-content">
                <h3><?php _e('Internal Links', 'programmatic-seo'); ?></h3>
                <div class="pseo-stat-number"><?php echo esc_html($link_stats['total_links']); ?></div>
                <p class="pseo-stat-label"><?php printf(__('Avg: %s per post', 'programmatic-seo'), $link_stats['avg_links_per_post']); ?></p>
            </div>
        </div>

        <!-- Failed Stats -->
        <div class="pseo-stat-card">
            <div class="pseo-stat-icon dashicons dashicons-dismiss"></div>
            <div class="pseo-stat-content">
                <h3><?php _e('Failed', 'programmatic-seo'); ?></h3>
                <div class="pseo-stat-number"><?php echo esc_html($stats['failed']); ?></div>
                <p class="pseo-stat-label"><?php _e('Total Failed', 'programmatic-seo'); ?></p>
            </div>
        </div>
    </div>

    <div class="pseo-row">
        <!-- Quick Actions -->
        <div class="pseo-col-6">
            <div class="pseo-card">
                <h2><?php _e('Quick Actions', 'programmatic-seo'); ?></h2>
                <div class="pseo-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=new'); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Create New Template', 'programmatic-seo'); ?>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=programmatic-seo-import'); ?>" class="button button-secondary button-large">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Import CSV', 'programmatic-seo'); ?>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=programmatic-seo-links'); ?>" class="button button-secondary button-large">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Build Internal Links', 'programmatic-seo'); ?>
                    </a>

                    <a href="<?php echo admin_url('admin.php?page=programmatic-seo-settings'); ?>" class="button button-secondary button-large">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('Settings', 'programmatic-seo'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Next Scheduled Post -->
        <div class="pseo-col-6">
            <div class="pseo-card">
                <h2><?php _e('Next Scheduled Post', 'programmatic-seo'); ?></h2>
                <?php if (!empty($stats['next_scheduled'])): ?>
                    <div class="pseo-next-scheduled">
                        <p><strong><?php _e('Schedule Time:', 'programmatic-seo'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($stats['next_scheduled']['schedule_time']))); ?>
                        </p>
                        <p><strong><?php _e('Time Until:', 'programmatic-seo'); ?></strong>
                            <?php echo human_time_diff(current_time('timestamp'), strtotime($stats['next_scheduled']['schedule_time'])); ?>
                        </p>
                        <a href="<?php echo admin_url('admin.php?page=programmatic-seo-scheduled'); ?>" class="button">
                            <?php _e('View All Scheduled Posts', 'programmatic-seo'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <p><?php _e('No posts currently scheduled', 'programmatic-seo'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Templates -->
    <div class="pseo-card">
        <h2><?php _e('Recent Templates', 'programmatic-seo'); ?></h2>
        <?php
        $recent_templates = PSEO_Database::get_templates(array('limit' => 5));
        if (!empty($recent_templates)):
        ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Template Name', 'programmatic-seo'); ?></th>
                        <th><?php _e('Post Type', 'programmatic-seo'); ?></th>
                        <th><?php _e('Created', 'programmatic-seo'); ?></th>
                        <th><?php _e('Actions', 'programmatic-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_templates as $template): ?>
                        <tr>
                            <td><strong><?php echo esc_html($template['name']); ?></strong></td>
                            <td><?php echo esc_html($template['post_type']); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($template['created_at']))); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=edit&template_id=' . $template['id']); ?>" class="button button-small">
                                    <?php _e('Edit', 'programmatic-seo'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No templates created yet.', 'programmatic-seo'); ?>
                <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates&action=new'); ?>">
                    <?php _e('Create your first template', 'programmatic-seo'); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Getting Started -->
    <div class="pseo-card pseo-getting-started">
        <h2><?php _e('Getting Started', 'programmatic-seo'); ?></h2>
        <ol>
            <li>
                <strong><?php _e('Create a Template', 'programmatic-seo'); ?></strong>
                <p><?php _e('Define how your content should be structured using variables like {{city}}, {{keyword}}, etc.', 'programmatic-seo'); ?></p>
            </li>
            <li>
                <strong><?php _e('Prepare Your Data', 'programmatic-seo'); ?></strong>
                <p><?php _e('Create a CSV file with columns matching your template variables.', 'programmatic-seo'); ?></p>
            </li>
            <li>
                <strong><?php _e('Import & Generate', 'programmatic-seo'); ?></strong>
                <p><?php _e('Import your CSV and let the plugin automatically generate SEO-optimized content.', 'programmatic-seo'); ?></p>
            </li>
            <li>
                <strong><?php _e('Optimize', 'programmatic-seo'); ?></strong>
                <p><?php _e('Enable auto-SEO, internal linking, and schema markup for better rankings.', 'programmatic-seo'); ?></p>
            </li>
        </ol>
    </div>
</div>
