<?php
/**
 * Template Engine Class
 * Handles template rendering with dynamic variables
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_Template_Engine {

    /**
     * Render template with data
     */
    public function render_template($template, $data) {
        if (empty($template) || empty($data)) {
            return $template;
        }

        // Replace simple variables: {{variable}}
        $template = $this->replace_variables($template, $data);

        // Handle conditional blocks: {{#if variable}}...{{/if}}
        $template = $this->handle_conditionals($template, $data);

        // Handle loops: {{#each items}}...{{/each}}
        $template = $this->handle_loops($template, $data);

        // Handle functions: {{uppercase:variable}}, {{lowercase:variable}}, etc.
        $template = $this->handle_functions($template, $data);

        // Handle spintax: {option1|option2|option3}
        $template = $this->handle_spintax($template);

        return $template;
    }

    /**
     * Replace simple variables
     */
    private function replace_variables($template, $data) {
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $template = str_replace('{{' . $key . '}}', $value, $template);
                $template = str_replace('{{ ' . $key . ' }}', $value, $template);
            }
        }
        return $template;
    }

    /**
     * Handle conditional blocks
     */
    private function handle_conditionals($template, $data) {
        $pattern = '/\{\{#if\s+(\w+)\}\}(.*?)\{\{\/if\}\}/s';

        return preg_replace_callback($pattern, function($matches) use ($data) {
            $variable = $matches[1];
            $content = $matches[2];

            if (isset($data[$variable]) && !empty($data[$variable])) {
                return $content;
            }

            return '';
        }, $template);
    }

    /**
     * Handle loops
     */
    private function handle_loops($template, $data) {
        $pattern = '/\{\{#each\s+(\w+)\}\}(.*?)\{\{\/each\}\}/s';

        return preg_replace_callback($pattern, function($matches) use ($data) {
            $variable = $matches[1];
            $content = $matches[2];

            if (!isset($data[$variable]) || !is_array($data[$variable])) {
                return '';
            }

            $output = '';
            foreach ($data[$variable] as $index => $item) {
                $item_content = $content;
                if (is_array($item)) {
                    foreach ($item as $key => $value) {
                        $item_content = str_replace('{{' . $key . '}}', $value, $item_content);
                    }
                } else {
                    $item_content = str_replace('{{this}}', $item, $item_content);
                }
                $item_content = str_replace('{{@index}}', $index, $item_content);
                $output .= $item_content;
            }

            return $output;
        }, $template);
    }

    /**
     * Handle template functions
     */
    private function handle_functions($template, $data) {
        // Uppercase: {{uppercase:variable}}
        $template = preg_replace_callback('/\{\{uppercase:(\w+)\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            return isset($data[$key]) ? strtoupper($data[$key]) : '';
        }, $template);

        // Lowercase: {{lowercase:variable}}
        $template = preg_replace_callback('/\{\{lowercase:(\w+)\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            return isset($data[$key]) ? strtolower($data[$key]) : '';
        }, $template);

        // Capitalize: {{capitalize:variable}}
        $template = preg_replace_callback('/\{\{capitalize:(\w+)\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            return isset($data[$key]) ? ucwords($data[$key]) : '';
        }, $template);

        // Slug: {{slug:variable}}
        $template = preg_replace_callback('/\{\{slug:(\w+)\}\}/', function($matches) use ($data) {
            $key = $matches[1];
            return isset($data[$key]) ? sanitize_title($data[$key]) : '';
        }, $template);

        // Date: {{date:format}}
        $template = preg_replace_callback('/\{\{date:([^\}]+)\}\}/', function($matches) {
            return date($matches[1]);
        }, $template);

        // Random number: {{random:min:max}}
        $template = preg_replace_callback('/\{\{random:(\d+):(\d+)\}\}/', function($matches) {
            return rand(intval($matches[1]), intval($matches[2]));
        }, $template);

        return $template;
    }

    /**
     * Handle spintax for content variation
     */
    private function handle_spintax($template) {
        while (preg_match('/\{([^{}]*)\}/', $template, $matches)) {
            $options = explode('|', $matches[1]);
            $selected = $options[array_rand($options)];
            $template = preg_replace('/\{' . preg_quote($matches[1], '/') . '\}/', $selected, $template, 1);
        }
        return $template;
    }

    /**
     * Generate content from template
     */
    public function generate_content($template_id, $data_rows) {
        $template = PSEO_Database::get_template($template_id);

        if (!$template) {
            return array(
                'success' => false,
                'message' => __('Template not found', 'programmatic-seo')
            );
        }

        $generated_posts = array();
        $errors = array();

        foreach ($data_rows as $index => $data) {
            try {
                $post_data = $this->prepare_post_data($template, $data);

                // Create or update post
                $post_id = $this->create_post($post_data);

                if (is_wp_error($post_id)) {
                    $errors[] = array(
                        'row' => $index + 1,
                        'error' => $post_id->get_error_message()
                    );
                } else {
                    $generated_posts[] = $post_id;

                    // Apply SEO optimization if enabled
                    if (get_option('pseo_enable_auto_seo', 1)) {
                        $seo_optimizer = new PSEO_SEO_Optimizer();
                        $seo_optimizer->optimize_post($post_id, $data);
                    }

                    // Extract and save keywords
                    $this->extract_keywords($post_id, $post_data['post_content']);
                }
            } catch (Exception $e) {
                $errors[] = array(
                    'row' => $index + 1,
                    'error' => $e->getMessage()
                );
            }
        }

        return array(
            'success' => true,
            'generated' => count($generated_posts),
            'post_ids' => $generated_posts,
            'errors' => $errors
        );
    }

    /**
     * Prepare post data from template and data
     */
    private function prepare_post_data($template, $data) {
        $title = $this->render_template($template['title_template'], $data);
        $content = $this->render_template($template['content_template'], $data);
        $meta_description = $this->render_template($template['meta_description_template'], $data);

        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $template['post_status'],
            'post_type' => $template['post_type'],
            'post_author' => $template['author_id'] ?: get_current_user_id(),
            'meta_input' => array(
                '_pseo_meta_description' => $meta_description,
                '_pseo_template_id' => $template['id'],
                '_pseo_data' => maybe_serialize($data)
            )
        );

        // Add categories
        if (!empty($template['category_ids'])) {
            $post_data['post_category'] = $template['category_ids'];
        }

        // Add tags
        if (!empty($template['tags'])) {
            $tags = $this->render_template($template['tags'], $data);
            $post_data['tags_input'] = array_map('trim', explode(',', $tags));
        }

        return $post_data;
    }

    /**
     * Create post
     */
    private function create_post($post_data) {
        // Check if post with same title exists
        $existing = get_page_by_title($post_data['post_title'], OBJECT, $post_data['post_type']);

        if ($existing) {
            $post_data['ID'] = $existing->ID;
        }

        $post_id = wp_insert_post($post_data);

        if (!is_wp_error($post_id) && isset($post_data['meta_input'])) {
            foreach ($post_data['meta_input'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }

        return $post_id;
    }

    /**
     * Extract keywords from content
     */
    private function extract_keywords($post_id, $content) {
        // Remove HTML tags
        $text = wp_strip_all_tags($content);

        // Simple keyword extraction (words with 4+ characters)
        $words = str_word_count(strtolower($text), 1);
        $keywords = array_filter($words, function($word) {
            return strlen($word) >= 4;
        });

        // Get top keywords by frequency
        $keyword_counts = array_count_values($keywords);
        arsort($keyword_counts);
        $top_keywords = array_slice($keyword_counts, 0, 20, true);

        foreach ($top_keywords as $keyword => $count) {
            if ($count >= 2) { // Only save keywords mentioned at least twice
                PSEO_Database::save_keyword($keyword, $post_id, $count);
            }
        }
    }

    /**
     * Get available template variables from data
     */
    public function get_template_variables($data_sample) {
        if (empty($data_sample)) {
            return array();
        }

        return array_keys($data_sample);
    }

    /**
     * Validate template syntax
     */
    public function validate_template($template) {
        $errors = array();

        // Check for unclosed tags
        $open_if = substr_count($template, '{{#if');
        $close_if = substr_count($template, '{{/if}}');
        if ($open_if != $close_if) {
            $errors[] = __('Unclosed {{#if}} tag', 'programmatic-seo');
        }

        $open_each = substr_count($template, '{{#each');
        $close_each = substr_count($template, '{{/each}}');
        if ($open_each != $close_each) {
            $errors[] = __('Unclosed {{#each}} tag', 'programmatic-seo');
        }

        return $errors;
    }
}
