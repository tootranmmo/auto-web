<?php
/**
 * Template Edit/Create View
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_new = empty($template);
$template_id = $is_new ? 0 : intval($template['id']);

// Handle form submission
if (isset($_POST['pseo_save_template'])) {
    check_admin_referer('pseo_template_' . $template_id);

    $template_data = array(
        'id' => $template_id,
        'name' => sanitize_text_field($_POST['template_name']),
        'description' => sanitize_textarea_field($_POST['template_description']),
        'title_template' => wp_kses_post($_POST['title_template']),
        'content_template' => wp_kses_post($_POST['content_template']),
        'meta_description_template' => sanitize_textarea_field($_POST['meta_description_template']),
        'post_type' => sanitize_text_field($_POST['post_type']),
        'post_status' => sanitize_text_field($_POST['post_status']),
        'category_ids' => isset($_POST['category_ids']) ? array_map('intval', $_POST['category_ids']) : array(),
        'tags' => sanitize_text_field($_POST['tags']),
        'author_id' => intval($_POST['author_id']),
        'featured_image_url' => esc_url_raw($_POST['featured_image_url']),
        'schema_type' => sanitize_text_field($_POST['schema_type']),
        'custom_fields' => array()
    );

    $saved_id = PSEO_Database::save_template($template_data);

    if ($saved_id) {
        wp_redirect(admin_url('admin.php?page=programmatic-seo-templates&action=edit&template_id=' . $saved_id . '&message=saved'));
        exit;
    }
}

// Default values
$name = $is_new ? '' : $template['name'];
$description = $is_new ? '' : $template['description'];
$title_template = $is_new ? '' : $template['title_template'];
$content_template = $is_new ? '' : $template['content_template'];
$meta_description_template = $is_new ? '' : $template['meta_description_template'];
$post_type = $is_new ? 'post' : $template['post_type'];
$post_status = $is_new ? 'draft' : $template['post_status'];
$category_ids = $is_new ? array() : (array)$template['category_ids'];
$tags = $is_new ? '' : $template['tags'];
$author_id = $is_new ? get_current_user_id() : $template['author_id'];
$featured_image_url = $is_new ? '' : $template['featured_image_url'];
$schema_type = $is_new ? 'Article' : $template['schema_type'];
?>

<div class="wrap pseo-template-edit">
    <h1><?php echo $is_new ? __('Create New Template', 'programmatic-seo') : __('Edit Template', 'programmatic-seo'); ?></h1>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'saved'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e('Template saved successfully', 'programmatic-seo'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="" class="pseo-template-form">
        <?php wp_nonce_field('pseo_template_' . $template_id); ?>

        <div class="pseo-row">
            <div class="pseo-col-8">
                <!-- Basic Information -->
                <div class="pseo-card">
                    <h2><?php _e('Basic Information', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="template_name"><?php _e('Template Name', 'programmatic-seo'); ?> *</label></th>
                            <td>
                                <input type="text" id="template_name" name="template_name" class="regular-text" value="<?php echo esc_attr($name); ?>" required>
                                <p class="description"><?php _e('A descriptive name for this template', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="template_description"><?php _e('Description', 'programmatic-seo'); ?></label></th>
                            <td>
                                <textarea id="template_description" name="template_description" class="large-text" rows="3"><?php echo esc_textarea($description); ?></textarea>
                                <p class="description"><?php _e('Optional description of this template', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Content Templates -->
                <div class="pseo-card">
                    <h2><?php _e('Content Templates', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="title_template"><?php _e('Title Template', 'programmatic-seo'); ?> *</label></th>
                            <td>
                                <input type="text" id="title_template" name="title_template" class="large-text" value="<?php echo esc_attr($title_template); ?>" required>
                                <p class="description">
                                    <?php _e('Use variables like {{city}}, {{keyword}}, {{date:Y}}, etc.', 'programmatic-seo'); ?>
                                    <br><?php _e('Example: Best {{service}} in {{city}} - {{date:Y}}', 'programmatic-seo'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="content_template"><?php _e('Content Template', 'programmatic-seo'); ?> *</label></th>
                            <td>
                                <?php
                                wp_editor($content_template, 'content_template', array(
                                    'textarea_rows' => 15,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'tinymce' => true
                                ));
                                ?>
                                <p class="description">
                                    <?php _e('Template for post content. Available syntax:', 'programmatic-seo'); ?>
                                    <br><code>{{variable}}</code> - <?php _e('Simple variable', 'programmatic-seo'); ?>
                                    <br><code>{{#if variable}}...{{/if}}</code> - <?php _e('Conditional', 'programmatic-seo'); ?>
                                    <br><code>{{#each items}}...{{/each}}</code> - <?php _e('Loop', 'programmatic-seo'); ?>
                                    <br><code>{{uppercase:variable}}</code> - <?php _e('Transform functions', 'programmatic-seo'); ?>
                                    <br><code>{option1|option2|option3}</code> - <?php _e('Spintax (random selection)', 'programmatic-seo'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="meta_description_template"><?php _e('Meta Description', 'programmatic-seo'); ?></label></th>
                            <td>
                                <textarea id="meta_description_template" name="meta_description_template" class="large-text" rows="3"><?php echo esc_textarea($meta_description_template); ?></textarea>
                                <p class="description"><?php _e('SEO meta description template (150-160 characters recommended)', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Template Variables Helper -->
                <div class="pseo-card">
                    <h2><?php _e('Template Variables & Functions', 'programmatic-seo'); ?></h2>
                    <div class="pseo-variables-help">
                        <div class="pseo-help-section">
                            <h3><?php _e('Available Functions', 'programmatic-seo'); ?></h3>
                            <ul>
                                <li><code>{{uppercase:variable}}</code> - Convert to uppercase</li>
                                <li><code>{{lowercase:variable}}</code> - Convert to lowercase</li>
                                <li><code>{{capitalize:variable}}</code> - Capitalize words</li>
                                <li><code>{{slug:variable}}</code> - Convert to URL slug</li>
                                <li><code>{{date:Y-m-d}}</code> - Current date with format</li>
                                <li><code>{{random:1:100}}</code> - Random number</li>
                            </ul>
                        </div>
                        <div class="pseo-help-section">
                            <h3><?php _e('Spintax Examples', 'programmatic-seo'); ?></h3>
                            <ul>
                                <li><code>{Best|Top|Leading} {{service}}</code></li>
                                <li><code>{We offer|Our company provides|Get} {{service}}</code></li>
                                <li><code>{excellent|great|outstanding} results</code></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pseo-col-4">
                <!-- Publish Settings -->
                <div class="pseo-card">
                    <h2><?php _e('Publish Settings', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="post_type"><?php _e('Post Type', 'programmatic-seo'); ?></label></th>
                            <td>
                                <select id="post_type" name="post_type" class="regular-text">
                                    <?php
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    foreach ($post_types as $pt):
                                    ?>
                                        <option value="<?php echo esc_attr($pt->name); ?>" <?php selected($post_type, $pt->name); ?>>
                                            <?php echo esc_html($pt->label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="post_status"><?php _e('Post Status', 'programmatic-seo'); ?></label></th>
                            <td>
                                <select id="post_status" name="post_status" class="regular-text">
                                    <option value="draft" <?php selected($post_status, 'draft'); ?>><?php _e('Draft', 'programmatic-seo'); ?></option>
                                    <option value="publish" <?php selected($post_status, 'publish'); ?>><?php _e('Published', 'programmatic-seo'); ?></option>
                                    <option value="pending" <?php selected($post_status, 'pending'); ?>><?php _e('Pending Review', 'programmatic-seo'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="author_id"><?php _e('Author', 'programmatic-seo'); ?></label></th>
                            <td>
                                <?php
                                wp_dropdown_users(array(
                                    'name' => 'author_id',
                                    'selected' => $author_id,
                                    'show_option_none' => __('Current User', 'programmatic-seo')
                                ));
                                ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Taxonomy Settings -->
                <div class="pseo-card">
                    <h2><?php _e('Categories & Tags', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label><?php _e('Categories', 'programmatic-seo'); ?></label></th>
                            <td>
                                <?php
                                $categories = get_categories(array('hide_empty' => false));
                                foreach ($categories as $category):
                                ?>
                                    <label style="display: block;">
                                        <input type="checkbox" name="category_ids[]" value="<?php echo esc_attr($category->term_id); ?>"
                                            <?php checked(in_array($category->term_id, $category_ids)); ?>>
                                        <?php echo esc_html($category->name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="tags"><?php _e('Tags', 'programmatic-seo'); ?></label></th>
                            <td>
                                <input type="text" id="tags" name="tags" class="regular-text" value="<?php echo esc_attr($tags); ?>">
                                <p class="description"><?php _e('Comma-separated. Can use variables like {{keyword}}', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SEO Settings -->
                <div class="pseo-card">
                    <h2><?php _e('SEO Settings', 'programmatic-seo'); ?></h2>

                    <table class="form-table">
                        <tr>
                            <th><label for="schema_type"><?php _e('Schema Type', 'programmatic-seo'); ?></label></th>
                            <td>
                                <select id="schema_type" name="schema_type" class="regular-text">
                                    <option value="Article" <?php selected($schema_type, 'Article'); ?>>Article</option>
                                    <option value="BlogPosting" <?php selected($schema_type, 'BlogPosting'); ?>>Blog Posting</option>
                                    <option value="NewsArticle" <?php selected($schema_type, 'NewsArticle'); ?>>News Article</option>
                                    <option value="Product" <?php selected($schema_type, 'Product'); ?>>Product</option>
                                    <option value="Service" <?php selected($schema_type, 'Service'); ?>>Service</option>
                                    <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQ Page</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="featured_image_url"><?php _e('Featured Image URL', 'programmatic-seo'); ?></label></th>
                            <td>
                                <input type="url" id="featured_image_url" name="featured_image_url" class="regular-text" value="<?php echo esc_url($featured_image_url); ?>">
                                <p class="description"><?php _e('Can use variables like {{image_url}}', 'programmatic-seo'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Actions -->
                <div class="pseo-card">
                    <button type="submit" name="pseo_save_template" class="button button-primary button-large" style="width: 100%;">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Save Template', 'programmatic-seo'); ?>
                    </button>

                    <a href="<?php echo admin_url('admin.php?page=programmatic-seo-templates'); ?>" class="button button-secondary" style="width: 100%; text-align: center; margin-top: 10px;">
                        <?php _e('Cancel', 'programmatic-seo'); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
