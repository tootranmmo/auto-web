<?php
/**
 * Plugin Name: Programmatic SEO Pro
 * Plugin URI: https://example.com/programmatic-seo
 * Description: Advanced programmatic SEO plugin for bulk content generation, template management, CSV import, auto SEO optimization, and internal linking.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: programmatic-seo
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PSEO_VERSION', '1.0.0');
define('PSEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PSEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PSEO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Programmatic_SEO {

    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-database.php';
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-template-engine.php';
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-csv-importer.php';
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-seo-optimizer.php';
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-internal-linking.php';
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-content-spinner.php';
        require_once PSEO_PLUGIN_DIR . 'includes/class-pseo-scheduler.php';
        require_once PSEO_PLUGIN_DIR . 'admin/class-pseo-admin.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_ajax_pseo_import_csv', array($this, 'ajax_import_csv'));
        add_action('wp_ajax_pseo_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_pseo_preview_template', array($this, 'ajax_preview_template'));
        add_action('wp_ajax_pseo_build_internal_links', array($this, 'ajax_build_internal_links'));

        // Initialize admin
        if (is_admin()) {
            PSEO_Admin::get_instance();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        PSEO_Database::create_tables();

        // Set default options
        $defaults = array(
            'pseo_max_posts_per_batch' => 50,
            'pseo_enable_auto_seo' => 1,
            'pseo_enable_internal_linking' => 1,
            'pseo_enable_schema_markup' => 1,
            'pseo_content_variation_level' => 'medium',
            'pseo_schedule_interval' => 'hourly'
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Schedule cron jobs
        if (!wp_next_scheduled('pseo_scheduled_publish')) {
            wp_schedule_event(time(), 'hourly', 'pseo_scheduled_publish');
        }

        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('pseo_scheduled_publish');
        flush_rewrite_rules();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'programmatic-seo',
            false,
            dirname(PSEO_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'programmatic-seo') === false) {
            return;
        }

        wp_enqueue_style(
            'pseo-admin-css',
            PSEO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            PSEO_VERSION
        );

        wp_enqueue_script(
            'pseo-admin-js',
            PSEO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            PSEO_VERSION,
            true
        );

        wp_localize_script('pseo-admin-js', 'pseoAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pseo_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'programmatic-seo'),
                'success' => __('Success!', 'programmatic-seo'),
                'error' => __('An error occurred', 'programmatic-seo'),
                'confirmDelete' => __('Are you sure you want to delete this?', 'programmatic-seo')
            )
        ));
    }

    /**
     * AJAX: Import CSV
     */
    public function ajax_import_csv() {
        check_ajax_referer('pseo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'programmatic-seo')));
        }

        $importer = new PSEO_CSV_Importer();
        $result = $importer->import($_FILES['csv_file'], $_POST);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Generate Content
     */
    public function ajax_generate_content() {
        check_ajax_referer('pseo_nonce', 'nonce');

        if (!current_user_can('publish_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'programmatic-seo')));
        }

        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $data = isset($_POST['data']) ? $_POST['data'] : array();

        $engine = new PSEO_Template_Engine();
        $result = $engine->generate_content($template_id, $data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Preview Template
     */
    public function ajax_preview_template() {
        check_ajax_referer('pseo_nonce', 'nonce');

        $template = isset($_POST['template']) ? $_POST['template'] : '';
        $sample_data = isset($_POST['sample_data']) ? $_POST['sample_data'] : array();

        $engine = new PSEO_Template_Engine();
        $preview = $engine->render_template($template, $sample_data);

        wp_send_json_success(array('preview' => $preview));
    }

    /**
     * AJAX: Build Internal Links
     */
    public function ajax_build_internal_links() {
        check_ajax_referer('pseo_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'programmatic-seo')));
        }

        $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : array();

        $linker = new PSEO_Internal_Linking();
        $result = $linker->build_links($post_ids);

        wp_send_json_success($result);
    }
}

/**
 * Initialize the plugin
 */
function pseo_init() {
    return Programmatic_SEO::get_instance();
}

// Start the plugin
pseo_init();
