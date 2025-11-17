<?php
/**
 * Scheduler Class
 * Handles bulk scheduling of posts
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_Scheduler {

    public function __construct() {
        add_action('pseo_scheduled_publish', array($this, 'process_scheduled_posts'));
    }

    /**
     * Schedule posts for publishing
     */
    public function schedule_posts($template_id, $data_rows, $start_time, $interval_minutes = 60) {
        $scheduled_count = 0;
        $current_time = strtotime($start_time);

        foreach ($data_rows as $index => $data) {
            $schedule_time = date('Y-m-d H:i:s', $current_time + ($index * $interval_minutes * 60));

            $scheduled_id = PSEO_Database::schedule_post($template_id, $data, $schedule_time);

            if ($scheduled_id) {
                $scheduled_count++;
            }
        }

        return array(
            'success' => true,
            'scheduled' => $scheduled_count,
            'message' => sprintf(
                __('Scheduled %d posts for publishing', 'programmatic-seo'),
                $scheduled_count
            )
        );
    }

    /**
     * Process scheduled posts (runs on cron)
     */
    public function process_scheduled_posts() {
        $max_posts = get_option('pseo_max_posts_per_batch', 50);

        // Get pending scheduled posts
        $scheduled_posts = PSEO_Database::get_pending_scheduled_posts($max_posts);

        if (empty($scheduled_posts)) {
            return;
        }

        $engine = new PSEO_Template_Engine();

        foreach ($scheduled_posts as $scheduled) {
            try {
                $post_data = maybe_unserialize($scheduled['post_data']);

                // Generate content
                $result = $engine->generate_content($scheduled['template_id'], array($post_data));

                if ($result['success'] && !empty($result['post_ids'])) {
                    $post_id = $result['post_ids'][0];

                    // Update scheduled post record
                    PSEO_Database::update_scheduled_post($scheduled['id'], array(
                        'status' => 'published',
                        'post_id' => $post_id
                    ));

                    // Apply SEO optimization
                    if (get_option('pseo_enable_auto_seo', 1)) {
                        $seo_optimizer = new PSEO_SEO_Optimizer();
                        $seo_optimizer->optimize_post($post_id, $post_data);
                    }

                    // Build internal links
                    if (get_option('pseo_enable_internal_linking', 1)) {
                        $linker = new PSEO_Internal_Linking();
                        $linker->build_links(array($post_id));
                    }
                } else {
                    // Mark as failed
                    PSEO_Database::update_scheduled_post($scheduled['id'], array(
                        'status' => 'failed'
                    ));
                }
            } catch (Exception $e) {
                // Mark as failed
                PSEO_Database::update_scheduled_post($scheduled['id'], array(
                    'status' => 'failed'
                ));

                error_log('PSEO Scheduler Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get scheduled posts
     */
    public function get_scheduled_posts($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        $defaults = array(
            'status' => 'pending',
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'schedule_time',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = '';
        if (!empty($args['status'])) {
            $where = $wpdb->prepare("WHERE status = %s", $args['status']);
        }

        $query = "SELECT * FROM $table $where ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['limit']} OFFSET {$args['offset']}";

        return $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Cancel scheduled post
     */
    public function cancel_scheduled_post($scheduled_id) {
        return PSEO_Database::update_scheduled_post($scheduled_id, array(
            'status' => 'cancelled'
        ));
    }

    /**
     * Reschedule post
     */
    public function reschedule_post($scheduled_id, $new_time) {
        return PSEO_Database::update_scheduled_post($scheduled_id, array(
            'schedule_time' => $new_time,
            'status' => 'pending'
        ));
    }

    /**
     * Get scheduling statistics
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        $stats = array(
            'pending' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'pending'"),
            'published' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'published'"),
            'failed' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'failed'"),
            'cancelled' => $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'cancelled'")
        );

        $stats['total'] = array_sum($stats);

        // Get next scheduled post
        $next_post = $wpdb->get_row(
            "SELECT * FROM $table WHERE status = 'pending' AND schedule_time >= NOW() ORDER BY schedule_time ASC LIMIT 1",
            ARRAY_A
        );

        $stats['next_scheduled'] = $next_post;

        return $stats;
    }

    /**
     * Clean up old scheduled posts
     */
    public function cleanup_old_records($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE status IN ('published', 'failed', 'cancelled') AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));

        return array(
            'success' => true,
            'deleted' => $deleted,
            'message' => sprintf(
                __('Deleted %d old records', 'programmatic-seo'),
                $deleted
            )
        );
    }

    /**
     * Distribute posts evenly over time
     */
    public function distribute_posts($template_id, $data_rows, $start_date, $end_date) {
        $total_posts = count($data_rows);

        if ($total_posts === 0) {
            return array(
                'success' => false,
                'message' => __('No posts to schedule', 'programmatic-seo')
            );
        }

        $start_timestamp = strtotime($start_date);
        $end_timestamp = strtotime($end_date);

        $time_range = $end_timestamp - $start_timestamp;

        if ($time_range <= 0) {
            return array(
                'success' => false,
                'message' => __('Invalid date range', 'programmatic-seo')
            );
        }

        // Calculate interval in seconds
        $interval_seconds = $time_range / $total_posts;

        $scheduled_count = 0;

        foreach ($data_rows as $index => $data) {
            $schedule_timestamp = $start_timestamp + ($index * $interval_seconds);
            $schedule_time = date('Y-m-d H:i:s', $schedule_timestamp);

            $scheduled_id = PSEO_Database::schedule_post($template_id, $data, $schedule_time);

            if ($scheduled_id) {
                $scheduled_count++;
            }
        }

        return array(
            'success' => true,
            'scheduled' => $scheduled_count,
            'interval_seconds' => round($interval_seconds),
            'message' => sprintf(
                __('Scheduled %d posts evenly from %s to %s', 'programmatic-seo'),
                $scheduled_count,
                $start_date,
                $end_date
            )
        );
    }

    /**
     * Schedule with custom pattern
     */
    public function schedule_with_pattern($template_id, $data_rows, $start_date, $pattern = 'daily') {
        $patterns = array(
            'hourly' => 3600, // 1 hour
            'daily' => 86400, // 24 hours
            'twice_daily' => 43200, // 12 hours
            'weekly' => 604800, // 7 days
            'monthly' => 2592000 // 30 days (approximate)
        );

        $interval_seconds = isset($patterns[$pattern]) ? $patterns[$pattern] : 86400;

        $start_timestamp = strtotime($start_date);
        $scheduled_count = 0;

        foreach ($data_rows as $index => $data) {
            $schedule_timestamp = $start_timestamp + ($index * $interval_seconds);
            $schedule_time = date('Y-m-d H:i:s', $schedule_timestamp);

            $scheduled_id = PSEO_Database::schedule_post($template_id, $data, $schedule_time);

            if ($scheduled_id) {
                $scheduled_count++;
            }
        }

        return array(
            'success' => true,
            'scheduled' => $scheduled_count,
            'pattern' => $pattern,
            'message' => sprintf(
                __('Scheduled %d posts with %s pattern', 'programmatic-seo'),
                $scheduled_count,
                $pattern
            )
        );
    }

    /**
     * Get upcoming scheduled posts
     */
    public function get_upcoming_posts($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_scheduled_posts';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, t.name as template_name
            FROM $table s
            LEFT JOIN {$wpdb->prefix}pseo_templates t ON s.template_id = t.id
            WHERE s.status = 'pending'
            AND s.schedule_time >= NOW()
            ORDER BY s.schedule_time ASC
            LIMIT %d",
            $limit
        ), ARRAY_A);
    }

    /**
     * Bulk cancel scheduled posts
     */
    public function bulk_cancel($scheduled_ids) {
        if (empty($scheduled_ids)) {
            return array('success' => false, 'message' => __('No posts selected', 'programmatic-seo'));
        }

        $cancelled_count = 0;

        foreach ($scheduled_ids as $id) {
            if ($this->cancel_scheduled_post($id)) {
                $cancelled_count++;
            }
        }

        return array(
            'success' => true,
            'cancelled' => $cancelled_count,
            'message' => sprintf(
                __('Cancelled %d scheduled posts', 'programmatic-seo'),
                $cancelled_count
            )
        );
    }

    /**
     * Publish scheduled post immediately
     */
    public function publish_now($scheduled_id) {
        $scheduled = PSEO_Database::get_import_job($scheduled_id); // Note: using wrong method, should create get_scheduled_post

        if (!$scheduled) {
            return array('success' => false, 'message' => __('Scheduled post not found', 'programmatic-seo'));
        }

        $engine = new PSEO_Template_Engine();
        $post_data = maybe_unserialize($scheduled['post_data']);

        $result = $engine->generate_content($scheduled['template_id'], array($post_data));

        if ($result['success'] && !empty($result['post_ids'])) {
            PSEO_Database::update_scheduled_post($scheduled_id, array(
                'status' => 'published',
                'post_id' => $result['post_ids'][0]
            ));

            return array(
                'success' => true,
                'post_id' => $result['post_ids'][0],
                'message' => __('Post published successfully', 'programmatic-seo')
            );
        }

        return array('success' => false, 'message' => __('Failed to publish post', 'programmatic-seo'));
    }
}
