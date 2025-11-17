<?php
/**
 * Admin Interface Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSEO_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Programmatic SEO', 'programmatic-seo'),
            __('Programmatic SEO', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'programmatic-seo',
            __('Dashboard', 'programmatic-seo'),
            __('Dashboard', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'programmatic-seo',
            __('Templates', 'programmatic-seo'),
            __('Templates', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo-templates',
            array($this, 'render_templates')
        );

        add_submenu_page(
            'programmatic-seo',
            __('Import CSV', 'programmatic-seo'),
            __('Import CSV', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo-import',
            array($this, 'render_import')
        );

        add_submenu_page(
            'programmatic-seo',
            __('Scheduled Posts', 'programmatic-seo'),
            __('Scheduled Posts', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo-scheduled',
            array($this, 'render_scheduled')
        );

        add_submenu_page(
            'programmatic-seo',
            __('Internal Links', 'programmatic-seo'),
            __('Internal Links', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo-links',
            array($this, 'render_links')
        );

        add_submenu_page(
            'programmatic-seo',
            __('Settings', 'programmatic-seo'),
            __('Settings', 'programmatic-seo'),
            'manage_options',
            'programmatic-seo-settings',
            array($this, 'render_settings')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('pseo_settings', 'pseo_max_posts_per_batch');
        register_setting('pseo_settings', 'pseo_enable_auto_seo');
        register_setting('pseo_settings', 'pseo_enable_internal_linking');
        register_setting('pseo_settings', 'pseo_enable_schema_markup');
        register_setting('pseo_settings', 'pseo_content_variation_level');
        register_setting('pseo_settings', 'pseo_schedule_interval');
    }

    /**
     * Render dashboard
     */
    public function render_dashboard() {
        $scheduler = new PSEO_Scheduler();
        $stats = $scheduler->get_stats();

        $linker = new PSEO_Internal_Linking();
        $link_stats = $linker->get_stats();

        include PSEO_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render templates page
     */
    public function render_templates() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        $template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

        if ($action === 'edit' || $action === 'new') {
            $template = null;
            if ($template_id > 0) {
                $template = PSEO_Database::get_template($template_id);
            }
            include PSEO_PLUGIN_DIR . 'admin/views/template-edit.php';
        } elseif ($action === 'delete') {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_template_' . $template_id)) {
                PSEO_Database::delete_template($template_id);
                wp_redirect(admin_url('admin.php?page=programmatic-seo-templates&message=deleted'));
                exit;
            }
        } else {
            $templates = PSEO_Database::get_templates();
            include PSEO_PLUGIN_DIR . 'admin/views/templates.php';
        }
    }

    /**
     * Render import page
     */
    public function render_import() {
        $templates = PSEO_Database::get_templates();
        include PSEO_PLUGIN_DIR . 'admin/views/import.php';
    }

    /**
     * Render scheduled posts page
     */
    public function render_scheduled() {
        $scheduler = new PSEO_Scheduler();
        $scheduled_posts = $scheduler->get_upcoming_posts(50);
        include PSEO_PLUGIN_DIR . 'admin/views/scheduled.php';
    }

    /**
     * Render internal links page
     */
    public function render_links() {
        $linker = new PSEO_Internal_Linking();
        $stats = $linker->get_stats();
        include PSEO_PLUGIN_DIR . 'admin/views/internal-links.php';
    }

    /**
     * Render settings page
     */
    public function render_settings() {
        if (isset($_POST['pseo_save_settings'])) {
            check_admin_referer('pseo_settings');

            update_option('pseo_max_posts_per_batch', intval($_POST['pseo_max_posts_per_batch']));
            update_option('pseo_enable_auto_seo', isset($_POST['pseo_enable_auto_seo']) ? 1 : 0);
            update_option('pseo_enable_internal_linking', isset($_POST['pseo_enable_internal_linking']) ? 1 : 0);
            update_option('pseo_enable_schema_markup', isset($_POST['pseo_enable_schema_markup']) ? 1 : 0);
            update_option('pseo_content_variation_level', sanitize_text_field($_POST['pseo_content_variation_level']));
            update_option('pseo_schedule_interval', sanitize_text_field($_POST['pseo_schedule_interval']));

            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully', 'programmatic-seo') . '</p></div>';
        }

        include PSEO_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
