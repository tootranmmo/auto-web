<?php
/**
 * Internal Links View
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap pseo-internal-links">
    <h1><?php _e('Internal Links Manager', 'programmatic-seo'); ?></h1>

    <div class="pseo-row">
        <div class="pseo-col-8">
            <!-- Build Links Tool -->
            <div class="pseo-card">
                <h2><?php _e('Build Internal Links', 'programmatic-seo'); ?></h2>

                <form id="pseo-build-links-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="link_post_type"><?php _e('Post Type', 'programmatic-seo'); ?></label></th>
                            <td>
                                <select id="link_post_type" name="post_type" class="regular-text">
                                    <?php
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    foreach ($post_types as $pt):
                                    ?>
                                        <option value="<?php echo esc_attr($pt->name); ?>">
                                            <?php echo esc_html($pt->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="max_links"><?php _e('Max Links per Post', 'programmatic-seo'); ?></label></th>
                            <td>
                                <input type="number" id="max_links" name="max_links" value="5" min="1" max="20" class="small-text">
                                <p class="description"><?php _e('Maximum number of internal links to add per post', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary button-large">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php _e('Build Internal Links', 'programmatic-seo'); ?>
                        </button>
                    </p>
                </form>

                <div id="pseo-links-progress" style="display: none;">
                    <div class="pseo-progress-bar">
                        <div class="pseo-progress-fill"></div>
                    </div>
                    <p class="pseo-progress-text"></p>
                </div>

                <div id="pseo-links-results" style="display: none;"></div>
            </div>

            <!-- Clean Up Tool -->
            <div class="pseo-card">
                <h2><?php _e('Maintenance', 'programmatic-seo'); ?></h2>

                <p><?php _e('Remove broken internal links (links to deleted posts)', 'programmatic-seo'); ?></p>

                <button type="button" class="button button-secondary" id="pseo-remove-broken-links">
                    <span class="dashicons dashicons-trash"></span>
                    <?php _e('Remove Broken Links', 'programmatic-seo'); ?>
                </button>
            </div>
        </div>

        <div class="pseo-col-4">
            <!-- Statistics -->
            <div class="pseo-card">
                <h2><?php _e('Statistics', 'programmatic-seo'); ?></h2>

                <div class="pseo-stat-item">
                    <div class="pseo-stat-label"><?php _e('Total Internal Links', 'programmatic-seo'); ?></div>
                    <div class="pseo-stat-value"><?php echo esc_html($stats['total_links']); ?></div>
                </div>

                <div class="pseo-stat-item">
                    <div class="pseo-stat-label"><?php _e('Posts with Links', 'programmatic-seo'); ?></div>
                    <div class="pseo-stat-value"><?php echo esc_html($stats['posts_with_links']); ?></div>
                </div>

                <div class="pseo-stat-item">
                    <div class="pseo-stat-label"><?php _e('Avg Links per Post', 'programmatic-seo'); ?></div>
                    <div class="pseo-stat-value"><?php echo esc_html($stats['avg_links_per_post']); ?></div>
                </div>
            </div>

            <!-- Top Linked Posts -->
            <div class="pseo-card">
                <h2><?php _e('Most Linked Posts', 'programmatic-seo'); ?></h2>

                <?php if (!empty($stats['top_linked_posts'])): ?>
                    <ul class="pseo-top-posts">
                        <?php foreach ($stats['top_linked_posts'] as $top_post): ?>
                            <?php $post = get_post($top_post['target_post_id']); ?>
                            <?php if ($post): ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($post->ID); ?>" target="_blank">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                    <span class="pseo-link-count"><?php echo esc_html($top_post['link_count']); ?> links</span>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php _e('No internal links yet', 'programmatic-seo'); ?></p>
                <?php endif; ?>
            </div>

            <!-- Tips -->
            <div class="pseo-card pseo-tips">
                <h2><?php _e('Best Practices', 'programmatic-seo'); ?></h2>
                <ul>
                    <li><?php _e('3-5 internal links per post is ideal', 'programmatic-seo'); ?></li>
                    <li><?php _e('Use descriptive anchor text', 'programmatic-seo'); ?></li>
                    <li><?php _e('Link to related content only', 'programmatic-seo'); ?></li>
                    <li><?php _e('Avoid over-optimization', 'programmatic-seo'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Build internal links
    $('#pseo-build-links-form').on('submit', function(e) {
        e.preventDefault();

        var postType = $('#link_post_type').val();

        $('#pseo-links-progress').show();
        $('#pseo-links-results').hide();
        $('.pseo-progress-text').text('<?php esc_attr_e('Building internal links...', 'programmatic-seo'); ?>');

        $.post(pseoAjax.ajaxurl, {
            action: 'pseo_build_internal_links',
            nonce: pseoAjax.nonce,
            post_type: postType
        }, function(response) {
            $('#pseo-links-progress').hide();
            $('#pseo-links-results').show();

            if (response.success) {
                $('#pseo-links-results').html(
                    '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                );
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $('#pseo-links-results').html(
                    '<div class="notice notice-error"><p>' + response.data.message + '</p></div>'
                );
            }
        });
    });

    // Remove broken links
    $('#pseo-remove-broken-links').on('click', function() {
        if (!confirm('<?php esc_attr_e('Remove all broken internal links?', 'programmatic-seo'); ?>')) {
            return;
        }

        $(this).prop('disabled', true);

        $.post(pseoAjax.ajaxurl, {
            action: 'pseo_remove_broken_links',
            nonce: pseoAjax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    });
});
</script>
