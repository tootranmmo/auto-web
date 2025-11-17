<?php
/**
 * SEO Optimizer Class
 * Handles automatic SEO optimization for posts
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_SEO_Optimizer {

    /**
     * Optimize post for SEO
     */
    public function optimize_post($post_id, $data = array()) {
        $post = get_post($post_id);

        if (!$post) {
            return false;
        }

        // Optimize meta description
        $this->optimize_meta_description($post_id, $post);

        // Generate and add schema markup
        if (get_option('pseo_enable_schema_markup', 1)) {
            $this->add_schema_markup($post_id, $post, $data);
        }

        // Optimize images
        $this->optimize_images($post_id, $post);

        // Add canonical URL
        $this->add_canonical_url($post_id);

        // Generate open graph tags
        $this->add_open_graph_tags($post_id, $post);

        // Add Twitter card tags
        $this->add_twitter_card_tags($post_id, $post);

        return true;
    }

    /**
     * Optimize meta description
     */
    private function optimize_meta_description($post_id, $post) {
        $meta_description = get_post_meta($post_id, '_pseo_meta_description', true);

        if (empty($meta_description)) {
            // Generate from content
            $content = wp_strip_all_tags($post->post_content);
            $meta_description = wp_trim_words($content, 25, '...');
        }

        // Ensure optimal length (150-160 characters)
        if (strlen($meta_description) > 160) {
            $meta_description = substr($meta_description, 0, 157) . '...';
        }

        update_post_meta($post_id, '_pseo_meta_description', $meta_description);
        update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description); // Yoast compatibility
    }

    /**
     * Add schema markup
     */
    private function add_schema_markup($post_id, $post, $data) {
        $template = PSEO_Database::get_template(get_post_meta($post_id, '_pseo_template_id', true));
        $schema_type = !empty($template['schema_type']) ? $template['schema_type'] : 'Article';

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'headline' => $post->post_title,
            'datePublished' => $post->post_date,
            'dateModified' => $post->post_modified,
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author)
            )
        );

        // Add description
        $meta_description = get_post_meta($post_id, '_pseo_meta_description', true);
        if ($meta_description) {
            $schema['description'] = $meta_description;
        }

        // Add image if available
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            if ($image_url) {
                $schema['image'] = $image_url;
            }
        }

        // Add URL
        $schema['url'] = get_permalink($post_id);

        // Add mainEntityOfPage
        $schema['mainEntityOfPage'] = array(
            '@type' => 'WebPage',
            '@id' => get_permalink($post_id)
        );

        // Add publisher (for Article type)
        if ($schema_type === 'Article') {
            $schema['publisher'] = array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            );
        }

        // Add custom schema data
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                if (!isset($schema[$key]) && is_scalar($value)) {
                    $schema[$key] = $value;
                }
            }
        }

        update_post_meta($post_id, '_pseo_schema_markup', wp_json_encode($schema));
    }

    /**
     * Optimize images in content
     */
    private function optimize_images($post_id, $post) {
        $content = $post->post_content;

        // Add alt text to images without it
        $content = preg_replace_callback('/<img([^>]+)>/', function($matches) use ($post) {
            $img_tag = $matches[0];

            // Check if alt attribute exists
            if (strpos($img_tag, 'alt=') === false) {
                // Add alt text based on post title
                $alt_text = esc_attr($post->post_title);
                $img_tag = str_replace('<img', '<img alt="' . $alt_text . '"', $img_tag);
            }

            // Add loading="lazy" for better performance
            if (strpos($img_tag, 'loading=') === false) {
                $img_tag = str_replace('<img', '<img loading="lazy"', $img_tag);
            }

            return $img_tag;
        }, $content);

        // Update post content if changed
        if ($content !== $post->post_content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $content
            ));
        }
    }

    /**
     * Add canonical URL
     */
    private function add_canonical_url($post_id) {
        $canonical_url = get_permalink($post_id);
        update_post_meta($post_id, '_pseo_canonical_url', $canonical_url);
    }

    /**
     * Add Open Graph tags
     */
    private function add_open_graph_tags($post_id, $post) {
        $og_tags = array(
            'og:title' => $post->post_title,
            'og:description' => get_post_meta($post_id, '_pseo_meta_description', true),
            'og:url' => get_permalink($post_id),
            'og:type' => 'article',
            'og:site_name' => get_bloginfo('name')
        );

        // Add image
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            if ($image_url) {
                $og_tags['og:image'] = $image_url;
                $image_meta = wp_get_attachment_metadata($thumbnail_id);
                if ($image_meta) {
                    $og_tags['og:image:width'] = $image_meta['width'];
                    $og_tags['og:image:height'] = $image_meta['height'];
                }
            }
        }

        update_post_meta($post_id, '_pseo_og_tags', $og_tags);
    }

    /**
     * Add Twitter Card tags
     */
    private function add_twitter_card_tags($post_id, $post) {
        $twitter_tags = array(
            'twitter:card' => 'summary_large_image',
            'twitter:title' => $post->post_title,
            'twitter:description' => get_post_meta($post_id, '_pseo_meta_description', true)
        );

        // Add image
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            if ($image_url) {
                $twitter_tags['twitter:image'] = $image_url;
            }
        }

        update_post_meta($post_id, '_pseo_twitter_tags', $twitter_tags);
    }

    /**
     * Calculate SEO score
     */
    public function calculate_seo_score($post_id) {
        $post = get_post($post_id);
        $score = 0;
        $max_score = 100;

        // Title length (10 points)
        $title_length = strlen($post->post_title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 10;
        } elseif ($title_length >= 20 && $title_length <= 70) {
            $score += 5;
        }

        // Meta description (10 points)
        $meta_desc = get_post_meta($post_id, '_pseo_meta_description', true);
        if (!empty($meta_desc)) {
            $desc_length = strlen($meta_desc);
            if ($desc_length >= 120 && $desc_length <= 160) {
                $score += 10;
            } elseif ($desc_length >= 100 && $desc_length <= 180) {
                $score += 5;
            }
        }

        // Content length (20 points)
        $content_length = str_word_count(wp_strip_all_tags($post->post_content));
        if ($content_length >= 300) {
            $score += 20;
        } elseif ($content_length >= 150) {
            $score += 10;
        }

        // Featured image (10 points)
        if (has_post_thumbnail($post_id)) {
            $score += 10;
        }

        // Internal links (15 points)
        $internal_links = $this->count_internal_links($post->post_content);
        if ($internal_links >= 3) {
            $score += 15;
        } elseif ($internal_links >= 1) {
            $score += 8;
        }

        // External links (10 points)
        $external_links = $this->count_external_links($post->post_content);
        if ($external_links >= 1) {
            $score += 10;
        }

        // Headings (10 points)
        $headings = $this->count_headings($post->post_content);
        if ($headings >= 3) {
            $score += 10;
        } elseif ($headings >= 1) {
            $score += 5;
        }

        // Schema markup (10 points)
        if (get_post_meta($post_id, '_pseo_schema_markup', true)) {
            $score += 10;
        }

        // Categories and tags (5 points)
        $categories = wp_get_post_categories($post_id);
        $tags = wp_get_post_tags($post_id);
        if (!empty($categories) && !empty($tags)) {
            $score += 5;
        } elseif (!empty($categories) || !empty($tags)) {
            $score += 3;
        }

        update_post_meta($post_id, '_pseo_seo_score', $score);

        return array(
            'score' => $score,
            'max_score' => $max_score,
            'percentage' => round(($score / $max_score) * 100)
        );
    }

    /**
     * Count internal links in content
     */
    private function count_internal_links($content) {
        $site_url = get_site_url();
        preg_match_all('/<a[^>]+href=["\'](' . preg_quote($site_url, '/') . '[^"\']*)["\'][^>]*>/i', $content, $matches);
        return count($matches[0]);
    }

    /**
     * Count external links in content
     */
    private function count_external_links($content) {
        $site_url = get_site_url();
        preg_match_all('/<a[^>]+href=["\']https?:\/\/[^"\']*["\'][^>]*>/i', $content, $matches);
        $total_links = count($matches[0]);
        $internal_links = $this->count_internal_links($content);
        return $total_links - $internal_links;
    }

    /**
     * Count headings in content
     */
    private function count_headings($content) {
        preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/i', $content, $matches);
        return count($matches[0]);
    }

    /**
     * Output schema markup in head
     */
    public static function output_schema_markup() {
        if (!is_single() && !is_page()) {
            return;
        }

        $post_id = get_the_ID();
        $schema = get_post_meta($post_id, '_pseo_schema_markup', true);

        if ($schema) {
            echo '<script type="application/ld+json">' . $schema . '</script>' . "\n";
        }
    }

    /**
     * Output Open Graph tags in head
     */
    public static function output_og_tags() {
        if (!is_single() && !is_page()) {
            return;
        }

        $post_id = get_the_ID();
        $og_tags = get_post_meta($post_id, '_pseo_og_tags', true);

        if ($og_tags && is_array($og_tags)) {
            foreach ($og_tags as $property => $content) {
                echo '<meta property="' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
            }
        }
    }

    /**
     * Output Twitter Card tags in head
     */
    public static function output_twitter_tags() {
        if (!is_single() && !is_page()) {
            return;
        }

        $post_id = get_the_ID();
        $twitter_tags = get_post_meta($post_id, '_pseo_twitter_tags', true);

        if ($twitter_tags && is_array($twitter_tags)) {
            foreach ($twitter_tags as $name => $content) {
                echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
            }
        }
    }
}

// Add hooks for outputting SEO tags
add_action('wp_head', array('PSEO_SEO_Optimizer', 'output_schema_markup'), 1);
add_action('wp_head', array('PSEO_SEO_Optimizer', 'output_og_tags'), 2);
add_action('wp_head', array('PSEO_SEO_Optimizer', 'output_twitter_tags'), 3);
