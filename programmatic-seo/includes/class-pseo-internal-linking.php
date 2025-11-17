<?php
/**
 * Internal Linking Engine Class
 * Automatically creates internal links between related posts
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_Internal_Linking {

    private $max_links_per_post = 5;
    private $min_content_length = 100;

    /**
     * Build internal links for posts
     */
    public function build_links($post_ids) {
        if (empty($post_ids)) {
            return array(
                'success' => false,
                'message' => __('No posts provided', 'programmatic-seo')
            );
        }

        $links_created = 0;

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);

            if (!$post || strlen($post->post_content) < $this->min_content_length) {
                continue;
            }

            // Find related posts
            $related_posts = $this->find_related_posts($post_id);

            if (empty($related_posts)) {
                continue;
            }

            // Add links to content
            $updated_content = $this->add_links_to_content($post, $related_posts);

            if ($updated_content !== $post->post_content) {
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $updated_content
                ));

                $links_created++;
            }
        }

        return array(
            'success' => true,
            'links_created' => $links_created,
            'message' => sprintf(
                __('Added internal links to %d posts', 'programmatic-seo'),
                $links_created
            )
        );
    }

    /**
     * Find related posts
     */
    private function find_related_posts($post_id) {
        $related = array();

        // Method 1: By keywords
        $keyword_related = PSEO_Database::get_related_posts_by_keywords($post_id, 10);

        if (!empty($keyword_related)) {
            foreach ($keyword_related as $item) {
                $related[] = $item['post_id'];
            }
        }

        // Method 2: By categories
        $categories = wp_get_post_categories($post_id);
        if (!empty($categories)) {
            $category_related = get_posts(array(
                'category__in' => $categories,
                'post__not_in' => array($post_id),
                'posts_per_page' => 10,
                'post_status' => 'publish',
                'fields' => 'ids'
            ));

            $related = array_merge($related, $category_related);
        }

        // Method 3: By tags
        $tags = wp_get_post_tags($post_id, array('fields' => 'ids'));
        if (!empty($tags)) {
            $tag_related = get_posts(array(
                'tag__in' => $tags,
                'post__not_in' => array_merge(array($post_id), $related),
                'posts_per_page' => 10,
                'post_status' => 'publish',
                'fields' => 'ids'
            ));

            $related = array_merge($related, $tag_related);
        }

        // Remove duplicates and limit
        $related = array_unique($related);
        $related = array_slice($related, 0, $this->max_links_per_post);

        return $related;
    }

    /**
     * Add links to post content
     */
    private function add_links_to_content($post, $related_post_ids) {
        $content = $post->post_content;
        $links_added = 0;

        foreach ($related_post_ids as $related_id) {
            if ($links_added >= $this->max_links_per_post) {
                break;
            }

            $related_post = get_post($related_id);
            if (!$related_post) {
                continue;
            }

            // Find suitable anchor text from related post title
            $anchor_text = $this->generate_anchor_text($related_post);

            // Try to find and replace the anchor text in content
            $pattern = '/\b' . preg_quote($anchor_text, '/') . '\b/i';

            // Check if link already exists
            if (strpos($content, 'href="' . get_permalink($related_id) . '"') !== false) {
                continue;
            }

            // Add link (only once)
            $replacement = '<a href="' . get_permalink($related_id) . '" title="' . esc_attr($related_post->post_title) . '">' . $anchor_text . '</a>';
            $new_content = preg_replace($pattern, $replacement, $content, 1, $count);

            if ($count > 0) {
                $content = $new_content;
                $links_added++;

                // Save link record
                PSEO_Database::save_internal_link($post->ID, $related_id, $anchor_text);
            }
        }

        return $content;
    }

    /**
     * Generate anchor text from post title
     */
    private function generate_anchor_text($post) {
        $title = $post->post_title;

        // Try to use a portion of the title
        $words = explode(' ', $title);

        if (count($words) <= 4) {
            return $title;
        }

        // Use first 3-4 words
        $anchor_words = array_slice($words, 0, rand(3, 4));
        return implode(' ', $anchor_words);
    }

    /**
     * Add contextual links (more intelligent linking)
     */
    public function add_contextual_links($post_id, $max_links = 3) {
        $post = get_post($post_id);

        if (!$post) {
            return false;
        }

        $content = $post->post_content;
        $paragraphs = $this->split_into_paragraphs($content);

        if (empty($paragraphs)) {
            return false;
        }

        // Find relevant posts for linking
        $related_posts = $this->find_related_posts($post_id);

        $links_added = 0;
        $new_paragraphs = array();

        foreach ($paragraphs as $paragraph) {
            if ($links_added >= $max_links) {
                $new_paragraphs[] = $paragraph;
                continue;
            }

            // Skip if paragraph already has links
            if (strpos($paragraph, '<a href') !== false) {
                $new_paragraphs[] = $paragraph;
                continue;
            }

            // Try to add a link to this paragraph
            $updated_paragraph = $paragraph;

            foreach ($related_posts as $related_id) {
                $related_post = get_post($related_id);
                if (!$related_post) {
                    continue;
                }

                $keywords = $this->extract_keywords_from_title($related_post->post_title);

                foreach ($keywords as $keyword) {
                    if (stripos($paragraph, $keyword) !== false) {
                        // Found a match, add link
                        $link = '<a href="' . get_permalink($related_id) . '">' . $keyword . '</a>';
                        $updated_paragraph = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link, $paragraph, 1);

                        PSEO_Database::save_internal_link($post_id, $related_id, $keyword);

                        $links_added++;
                        break 2; // Exit both loops
                    }
                }
            }

            $new_paragraphs[] = $updated_paragraph;
        }

        $new_content = implode("\n\n", $new_paragraphs);

        if ($new_content !== $content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $new_content
            ));

            return true;
        }

        return false;
    }

    /**
     * Split content into paragraphs
     */
    private function split_into_paragraphs($content) {
        // Remove HTML tags except p
        $content = strip_tags($content, '<p>');

        // Split by <p> tags
        $paragraphs = preg_split('/<p[^>]*>|<\/p>/', $content, -1, PREG_SPLIT_NO_EMPTY);

        // Filter out empty paragraphs
        $paragraphs = array_filter($paragraphs, function($p) {
            return trim(strip_tags($p)) !== '';
        });

        return array_values($paragraphs);
    }

    /**
     * Extract keywords from title
     */
    private function extract_keywords_from_title($title) {
        // Remove common words
        $common_words = array('the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'from', 'as', 'is', 'was', 'are', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can');

        $words = str_word_count(strtolower($title), 1);
        $keywords = array_diff($words, $common_words);

        // Also include multi-word phrases (up to 3 words)
        $title_words = explode(' ', $title);
        $phrases = array();

        for ($i = 0; $i < count($title_words) - 1; $i++) {
            $phrases[] = $title_words[$i] . ' ' . $title_words[$i + 1];

            if (isset($title_words[$i + 2])) {
                $phrases[] = $title_words[$i] . ' ' . $title_words[$i + 1] . ' ' . $title_words[$i + 2];
            }
        }

        return array_merge($keywords, $phrases);
    }

    /**
     * Get internal linking statistics
     */
    public function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_internal_links';

        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        $posts_with_links = $wpdb->get_var("SELECT COUNT(DISTINCT source_post_id) FROM $table");

        $avg_links_per_post = $posts_with_links > 0 ? round($total_links / $posts_with_links, 2) : 0;

        $top_linked_posts = $wpdb->get_results(
            "SELECT target_post_id, COUNT(*) as link_count
            FROM $table
            GROUP BY target_post_id
            ORDER BY link_count DESC
            LIMIT 10",
            ARRAY_A
        );

        return array(
            'total_links' => $total_links,
            'posts_with_links' => $posts_with_links,
            'avg_links_per_post' => $avg_links_per_post,
            'top_linked_posts' => $top_linked_posts
        );
    }

    /**
     * Remove broken internal links
     */
    public function remove_broken_links() {
        global $wpdb;
        $table = $wpdb->prefix . 'pseo_internal_links';

        // Get all links
        $links = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);

        $removed_count = 0;

        foreach ($links as $link) {
            $source_exists = get_post_status($link['source_post_id']) !== false;
            $target_exists = get_post_status($link['target_post_id']) !== false;

            if (!$source_exists || !$target_exists) {
                $wpdb->delete($table, array('id' => $link['id']));
                $removed_count++;
            }
        }

        return array(
            'success' => true,
            'removed' => $removed_count,
            'message' => sprintf(
                __('Removed %d broken links', 'programmatic-seo'),
                $removed_count
            )
        );
    }

    /**
     * Rebuild all internal links
     */
    public function rebuild_all_links($post_type = 'post') {
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        return $this->build_links($posts);
    }
}
