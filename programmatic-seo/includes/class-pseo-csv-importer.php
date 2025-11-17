<?php
/**
 * CSV Importer Class
 * Handles CSV file import and content generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_CSV_Importer {

    private $max_rows_per_batch = 50;
    private $allowed_mime_types = array(
        'text/csv',
        'text/plain',
        'application/csv',
        'application/vnd.ms-excel'
    );

    public function __construct() {
        $this->max_rows_per_batch = get_option('pseo_max_posts_per_batch', 50);
    }

    /**
     * Import CSV file
     */
    public function import($file, $options = array()) {
        // Validate file
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array(
                'success' => false,
                'message' => __('Invalid file upload', 'programmatic-seo')
            );
        }

        // Check file type
        if (!in_array($file['type'], $this->allowed_mime_types)) {
            return array(
                'success' => false,
                'message' => __('Invalid file type. Please upload a CSV file.', 'programmatic-seo')
            );
        }

        // Parse CSV
        $data = $this->parse_csv($file['tmp_name']);

        if (empty($data)) {
            return array(
                'success' => false,
                'message' => __('No data found in CSV file', 'programmatic-seo')
            );
        }

        // Get template ID
        $template_id = isset($options['template_id']) ? intval($options['template_id']) : 0;

        if ($template_id <= 0) {
            return array(
                'success' => false,
                'message' => __('Invalid template ID', 'programmatic-seo')
            );
        }

        // Create import job
        $import_id = PSEO_Database::save_import_job(array(
            'template_id' => $template_id,
            'file_name' => sanitize_file_name($file['name']),
            'total_rows' => count($data)
        ));

        // Process import
        $result = $this->process_import($import_id, $template_id, $data, $options);

        return $result;
    }

    /**
     * Parse CSV file
     */
    private function parse_csv($file_path) {
        $data = array();
        $headers = array();

        if (($handle = fopen($file_path, 'r')) !== false) {
            $row_index = 0;

            while (($row = fgetcsv($handle, 10000, ',')) !== false) {
                if ($row_index === 0) {
                    // First row is headers
                    $headers = array_map('trim', $row);
                } else {
                    // Data rows
                    $row_data = array();
                    foreach ($row as $index => $value) {
                        if (isset($headers[$index])) {
                            $row_data[$headers[$index]] = trim($value);
                        }
                    }
                    if (!empty($row_data)) {
                        $data[] = $row_data;
                    }
                }
                $row_index++;
            }

            fclose($handle);
        }

        return $data;
    }

    /**
     * Process import
     */
    private function process_import($import_id, $template_id, $data, $options) {
        $engine = new PSEO_Template_Engine();
        $scheduler = new PSEO_Scheduler();

        $success_count = 0;
        $failed_count = 0;
        $errors = array();

        $schedule_posts = isset($options['schedule_posts']) && $options['schedule_posts'] === 'yes';
        $schedule_start = isset($options['schedule_start']) ? $options['schedule_start'] : '';
        $schedule_interval = isset($options['schedule_interval']) ? intval($options['schedule_interval']) : 60;

        foreach ($data as $index => $row_data) {
            try {
                if ($schedule_posts && !empty($schedule_start)) {
                    // Schedule for later
                    $schedule_time = date('Y-m-d H:i:s', strtotime($schedule_start) + ($index * $schedule_interval * 60));
                    PSEO_Database::schedule_post($template_id, $row_data, $schedule_time);
                    $success_count++;
                } else {
                    // Create immediately
                    $result = $engine->generate_content($template_id, array($row_data));

                    if ($result['success'] && !empty($result['post_ids'])) {
                        $success_count++;

                        // Build internal links if enabled
                        if (get_option('pseo_enable_internal_linking', 1)) {
                            $linker = new PSEO_Internal_Linking();
                            $linker->build_links($result['post_ids']);
                        }
                    } else {
                        $failed_count++;
                        if (!empty($result['errors'])) {
                            $errors = array_merge($errors, $result['errors']);
                        }
                    }
                }
            } catch (Exception $e) {
                $failed_count++;
                $errors[] = array(
                    'row' => $index + 1,
                    'error' => $e->getMessage()
                );
            }

            // Update progress every 10 rows
            if ($index % 10 === 0) {
                PSEO_Database::update_import_job($import_id, array(
                    'processed_rows' => $index + 1,
                    'success_rows' => $success_count,
                    'failed_rows' => $failed_count
                ));
            }
        }

        // Final update
        PSEO_Database::update_import_job($import_id, array(
            'processed_rows' => count($data),
            'success_rows' => $success_count,
            'failed_rows' => $failed_count,
            'status' => 'completed',
            'completed_at' => current_time('mysql'),
            'error_log' => !empty($errors) ? maybe_serialize($errors) : null
        ));

        return array(
            'success' => true,
            'import_id' => $import_id,
            'total' => count($data),
            'success' => $success_count,
            'failed' => $failed_count,
            'errors' => $errors,
            'message' => sprintf(
                __('Import completed. %d posts created, %d failed.', 'programmatic-seo'),
                $success_count,
                $failed_count
            )
        );
    }

    /**
     * Get sample data from CSV
     */
    public function get_csv_sample($file_path, $rows = 5) {
        $data = $this->parse_csv($file_path);
        return array_slice($data, 0, $rows);
    }

    /**
     * Validate CSV structure
     */
    public function validate_csv($file_path, $template_id) {
        $data = $this->parse_csv($file_path);

        if (empty($data)) {
            return array(
                'valid' => false,
                'message' => __('CSV file is empty', 'programmatic-seo')
            );
        }

        $template = PSEO_Database::get_template($template_id);

        if (!$template) {
            return array(
                'valid' => false,
                'message' => __('Template not found', 'programmatic-seo')
            );
        }

        // Extract variables from template
        $required_variables = $this->extract_template_variables($template);
        $csv_columns = array_keys($data[0]);

        // Check if all required variables are in CSV
        $missing_columns = array_diff($required_variables, $csv_columns);

        if (!empty($missing_columns)) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    __('Missing columns in CSV: %s', 'programmatic-seo'),
                    implode(', ', $missing_columns)
                ),
                'missing_columns' => $missing_columns
            );
        }

        return array(
            'valid' => true,
            'rows' => count($data),
            'columns' => $csv_columns,
            'sample' => array_slice($data, 0, 3)
        );
    }

    /**
     * Extract variables from template
     */
    private function extract_template_variables($template) {
        $variables = array();
        $content = $template['title_template'] . ' ' . $template['content_template'];

        // Match {{variable}} patterns
        preg_match_all('/\{\{([a-zA-Z0-9_]+)\}\}/', $content, $matches);

        if (!empty($matches[1])) {
            $variables = array_unique($matches[1]);
        }

        // Remove special keywords
        $special_keywords = array('date', 'random', 'this', '@index');
        $variables = array_diff($variables, $special_keywords);

        return array_values($variables);
    }

    /**
     * Export template data to CSV
     */
    public function export_to_csv($post_ids, $filename = 'export.csv') {
        $data = array();

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;

            $row = array(
                'ID' => $post->ID,
                'Title' => $post->post_title,
                'Content' => wp_strip_all_tags($post->post_content),
                'Status' => $post->post_status,
                'Date' => $post->post_date,
                'Author' => get_the_author_meta('display_name', $post->post_author),
                'URL' => get_permalink($post->ID)
            );

            // Add custom fields
            $template_data = get_post_meta($post->ID, '_pseo_data', true);
            if ($template_data) {
                $template_data = maybe_unserialize($template_data);
                if (is_array($template_data)) {
                    $row = array_merge($row, $template_data);
                }
            }

            $data[] = $row;
        }

        if (empty($data)) {
            return false;
        }

        // Create CSV
        $output = fopen('php://temp', 'w');

        // Write headers
        fputcsv($output, array_keys($data[0]));

        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        // Get content
        rewind($output);
        $csv_content = stream_get_contents($output);
        fclose($output);

        // Send download headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv_content));

        echo $csv_content;
        exit;
    }
}
