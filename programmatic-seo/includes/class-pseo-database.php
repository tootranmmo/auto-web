<?php
/**
 * Database Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_Database {

    /**
     * Create plugin tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Templates table
        $table_templates = $wpdb->prefix . 'pseo_templates';
        $sql_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            title_template text NOT NULL,
            content_template longtext NOT NULL,
            meta_description_template text,
            post_type varchar(50) DEFAULT 'post',
            post_status varchar(20) DEFAULT 'draft',
            category_ids text,
            tags text,
            author_id bigint(20),
            featured_image_url text,
            schema_type varchar(50),
            custom_fields text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Import jobs table
        $table_imports = $wpdb->prefix . 'pseo_imports';
        $sql_imports = "CREATE TABLE IF NOT EXISTS $table_imports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_id bigint(20),
            file_name varchar(255),
            total_rows int(11) DEFAULT 0,
            processed_rows int(11) DEFAULT 0,
            success_rows int(11) DEFAULT 0,
            failed_rows int(11) DEFAULT 0,
            status varchar(20) DEFAULT 'pending',
            error_log longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            completed_at datetime,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Scheduled posts table
        $table_scheduled = $wpdb->prefix . 'pseo_scheduled_posts';
        $sql_scheduled = "CREATE TABLE IF NOT EXISTS $table_scheduled (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            template_id bigint(20),
            post_data longtext,
            schedule_time datetime,
            status varchar(20) DEFAULT 'pending',
            post_id bigint(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY schedule_time (schedule_time),
            KEY status (status)
        ) $charset_collate;";

        // Keywords table
        $table_keywords = $wpdb->prefix . 'pseo_keywords';
        $sql_keywords = "CREATE TABLE IF NOT EXISTS $table_keywords (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            post_id bigint(20),
            usage_count int(11) DEFAULT 0,
            priority int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY keyword (keyword),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Internal links table
        $table_links = $wpdb->prefix . 'pseo_internal_links';
        $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_post_id bigint(20) NOT NULL,
            target_post_id bigint(20) NOT NULL,
            anchor_text varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_post_id (source_post_id),
            KEY target_post_id (target_post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_imports);
        dbDelta($sql_scheduled);
        dbDelta($sql_keywords);
        dbDelta($sql_links);
    }

    /**
     * Save template
     */
    public static function save_template($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_templates';

        $template_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description']),
            'title_template' => wp_kses_post($data['title_template']),
            'content_template' => wp_kses_post($data['content_template']),
            'meta_description_template' => sanitize_textarea_field($data['meta_description_template']),
            'post_type' => sanitize_text_field($data['post_type']),
            'post_status' => sanitize_text_field($data['post_status']),
            'category_ids' => maybe_serialize($data['category_ids']),
            'tags' => sanitize_text_field($data['tags']),
            'author_id' => intval($data['author_id']),
            'featured_image_url' => esc_url_raw($data['featured_image_url']),
            'schema_type' => sanitize_text_field($data['schema_type']),
            'custom_fields' => maybe_serialize($data['custom_fields'])
        );

        if (isset($data['id']) && $data['id'] > 0) {
            $wpdb->update($table, $template_data, array('id' => intval($data['id'])));
            return intval($data['id']);
        } else {
            $wpdb->insert($table, $template_data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Get template by ID
     */
    public static function get_template($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_templates';

        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ), ARRAY_A);

        if ($template) {
            $template['category_ids'] = maybe_unserialize($template['category_ids']);
            $template['custom_fields'] = maybe_unserialize($template['custom_fields']);
        }

        return $template;
    }

    /**
     * Get all templates
     */
    public static function get_templates($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_templates';

        $defaults = array(
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => 100,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $query = "SELECT * FROM $table ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['limit']} OFFSET {$args['offset']}";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Delete template
     */
    public static function delete_template($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_templates';

        return $wpdb->delete($table, array('id' => intval($id)));
    }

    /**
     * Save import job
     */
    public static function save_import_job($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_imports';

        $wpdb->insert($table, array(
            'template_id' => intval($data['template_id']),
            'file_name' => sanitize_text_field($data['file_name']),
            'total_rows' => intval($data['total_rows']),
            'status' => 'processing'
        ));

        return $wpdb->insert_id;
    }

    /**
     * Update import job
     */
    public static function update_import_job($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_imports';

        return $wpdb->update($table, $data, array('id' => intval($id)));
    }

    /**
     * Get import job
     */
    public static function get_import_job($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_imports';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ), ARRAY_A);
    }

    /**
     * Schedule post
     */
    public static function schedule_post($template_id, $post_data, $schedule_time) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        $wpdb->insert($table, array(
            'template_id' => intval($template_id),
            'post_data' => maybe_serialize($post_data),
            'schedule_time' => $schedule_time,
            'status' => 'pending'
        ));

        return $wpdb->insert_id;
    }

    /**
     * Get pending scheduled posts
     */
    public static function get_pending_scheduled_posts($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table
            WHERE status = 'pending'
            AND schedule_time <= %s
            ORDER BY schedule_time ASC
            LIMIT %d",
            current_time('mysql'),
            $limit
        ), ARRAY_A);
    }

    /**
     * Update scheduled post
     */
    public static function update_scheduled_post($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        return $wpdb->update($table, $data, array('id' => intval($id)));
    }

    /**
     * Save keyword
     */
    public static function save_keyword($keyword, $post_id = null, $priority = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_keywords';

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE keyword = %s AND post_id = %d",
            $keyword,
            $post_id
        ));

        if ($existing) {
            $wpdb->update(
                $table,
                array('usage_count' => $existing->usage_count + 1),
                array('id' => $existing->id)
            );
            return $existing->id;
        } else {
            $wpdb->insert($table, array(
                'keyword' => $keyword,
                'post_id' => $post_id,
                'usage_count' => 1,
                'priority' => $priority
            ));
            return $wpdb->insert_id;
        }
    }

    /**
     * Get related posts by keywords
     */
    public static function get_related_posts_by_keywords($post_id, $limit = 5) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_keywords';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT k2.post_id
            FROM $table k1
            JOIN $table k2 ON k1.keyword = k2.keyword
            WHERE k1.post_id = %d
            AND k2.post_id != %d
            AND k2.post_id IS NOT NULL
            ORDER BY k2.usage_count DESC
            LIMIT %d",
            $post_id,
            $post_id,
            $limit
        ), ARRAY_A);
    }

    /**
     * Save internal link
     */
    public static function save_internal_link($source_post_id, $target_post_id, $anchor_text) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_internal_links';

        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE source_post_id = %d AND target_post_id = %d",
            $source_post_id,
            $target_post_id
        ));

        if (!$existing) {
            $wpdb->insert($table, array(
                'source_post_id' => $source_post_id,
                'target_post_id' => $target_post_id,
                'anchor_text' => $anchor_text
            ));
            return $wpdb->insert_id;
        }

        return $existing->id;
    }
}
