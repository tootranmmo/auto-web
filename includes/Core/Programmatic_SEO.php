<?php
/**
 * Main Plugin Class
 *
 * @package ProgrammaticSEO\Core
 */

namespace ProgrammaticSEO\Core;

use ProgrammaticSEO\Admin\Admin_Dashboard;
use ProgrammaticSEO\Templates\Template_Manager;
use ProgrammaticSEO\DataSource\Data_Source_Manager;
use ProgrammaticSEO\Generator\Page_Generator;
use ProgrammaticSEO\SEO\SEO_Meta_Generator;
use ProgrammaticSEO\Schema\Schema_Generator;
use ProgrammaticSEO\Linking\Internal_Link_Manager;
use ProgrammaticSEO\Sitemap\Sitemap_Generator;
use ProgrammaticSEO\Analytics\Analytics_Manager;
use ProgrammaticSEO\Cache\Cache_Manager;
use ProgrammaticSEO\Database\Database_Optimizer;
use ProgrammaticSEO\Queue\Queue_Manager;
use ProgrammaticSEO\Monitor\System_Monitor;

/**
 * Class Programmatic_SEO
 */
class Programmatic_SEO {

	/**
	 * Plugin instance
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Admin Dashboard instance
	 *
	 * @var Admin_Dashboard
	 */
	public $admin_dashboard;

	/**
	 * Template Manager instance
	 *
	 * @var Template_Manager
	 */
	public $template_manager;

	/**
	 * Data Source Manager instance
	 *
	 * @var Data_Source_Manager
	 */
	public $data_source_manager;

	/**
	 * Page Generator instance
	 *
	 * @var Page_Generator
	 */
	public $page_generator;

	/**
	 * SEO Meta Generator instance
	 *
	 * @var SEO_Meta_Generator
	 */
	public $seo_meta_generator;

	/**
	 * Schema Generator instance
	 *
	 * @var Schema_Generator
	 */
	public $schema_generator;

	/**
	 * Internal Link Manager instance
	 *
	 * @var Internal_Link_Manager
	 */
	public $internal_link_manager;

	/**
	 * Sitemap Generator instance
	 *
	 * @var Sitemap_Generator
	 */
	public $sitemap_generator;

	/**
	 * Analytics Manager instance
	 *
	 * @var Analytics_Manager
	 */
	public $analytics_manager;

	/**
	 * Cache Manager instance
	 *
	 * @var Cache_Manager
	 */
	public $cache_manager;

	/**
	 * Database Optimizer instance
	 *
	 * @var Database_Optimizer
	 */
	public $db_optimizer;

	/**
	 * Queue Manager instance
	 *
	 * @var Queue_Manager
	 */
	public $queue_manager;

	/**
	 * System Monitor instance
	 *
	 * @var System_Monitor
	 */
	public $system_monitor;

	/**
	 * Get plugin instance
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->setup_hooks();
		$this->init_modules();
	}

	/**
	 * Load plugin dependencies
	 */
	private function load_dependencies() {
		require_once PSEO_PLUGIN_DIR . 'includes/Database/Database_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Admin/Admin_Dashboard.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Templates/Template_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/DataSource/Data_Source_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Generator/Page_Generator.php';
		require_once PSEO_PLUGIN_DIR . 'includes/SEO/SEO_Meta_Generator.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Schema/Schema_Generator.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Linking/Internal_Link_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Sitemap/Sitemap_Generator.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Analytics/Analytics_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Cache/Cache_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Database/Database_Optimizer.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Queue/Queue_Manager.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Monitor/System_Monitor.php';
		require_once PSEO_PLUGIN_DIR . 'includes/Helpers/Helper_Functions.php';
	}

	/**
	 * Setup WordPress hooks
	 */
	private function setup_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
		add_action( 'wp_head', array( $this, 'add_seo_tags' ), 1 );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_action( 'init', array( $this, 'handle_dynamic_pages' ) );
	}

	/**
	 * Initialize all modules
	 */
	private function init_modules() {
		// Core modules
		$this->admin_dashboard       = new Admin_Dashboard();
		$this->template_manager      = new Template_Manager();
		$this->data_source_manager   = new Data_Source_Manager();
		$this->page_generator        = new Page_Generator();
		$this->seo_meta_generator    = new SEO_Meta_Generator();
		$this->schema_generator      = new Schema_Generator();
		$this->internal_link_manager = new Internal_Link_Manager();
		$this->sitemap_generator     = new Sitemap_Generator();
		$this->analytics_manager     = new Analytics_Manager();

		// Performance & Monitoring modules (Phase 1)
		$this->cache_manager         = new Cache_Manager();
		$this->db_optimizer          = new Database_Optimizer();
		$this->queue_manager         = new Queue_Manager();
		$this->system_monitor        = new System_Monitor();
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {
		wp_enqueue_style(
			'pseo-frontend',
			PSEO_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			PSEO_VERSION
		);

		wp_enqueue_script(
			'pseo-frontend',
			PSEO_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			PSEO_VERSION,
			true
		);
	}

	/**
	 * Add SEO tags to head
	 */
	public function add_seo_tags() {
		if ( is_singular( 'pseo_generated' ) ) {
			global $post;
			$meta = get_post_meta( $post->ID, '_pseo_meta', true );

			if ( $meta ) {
				echo wp_kses_post( $this->seo_meta_generator->render_meta_tags( $meta ) );
				echo wp_kses_post( $this->schema_generator->render_schema( $post->ID ) );
			}
		}
	}

	/**
	 * Add custom query vars
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'pseo_slug';
		$vars[] = 'pseo_data';
		return $vars;
	}

	/**
	 * Handle dynamic page routing
	 */
	public function handle_dynamic_pages() {
		// Register custom post type
		$this->register_post_types();

		// Setup rewrite rules for dynamic pages
		$this->setup_rewrite_rules();
	}

	/**
	 * Register custom post types
	 */
	private function register_post_types() {
		register_post_type(
			'pseo_generated',
			array(
				'label'       => __( 'Programmatic SEO Pages', 'programmatic-seo' ),
				'description' => __( 'Auto-generated pages from Programmatic SEO', 'programmatic-seo' ),
				'public'      => true,
				'supports'    => array( 'title', 'content', 'editor' ),
				'has_archive' => false,
				'show_in_rest' => true,
			)
		);

		register_post_type(
			'pseo_template',
			array(
				'label'       => __( 'Programmatic SEO Templates', 'programmatic-seo' ),
				'description' => __( 'Templates for generating pages', 'programmatic-seo' ),
				'public'      => false,
				'show_ui'     => true,
				'show_in_rest' => true,
				'supports'    => array( 'title', 'content', 'editor' ),
			)
		);
	}

	/**
	 * Setup rewrite rules
	 */
	private function setup_rewrite_rules() {
		// Add rewrite rules for dynamic pages
		add_rewrite_rule(
			'^pseo/([a-z0-9-]+)/?$',
			'index.php?pseo_slug=$matches[1]',
			'top'
		);
	}

	/**
	 * Activate plugin
	 */
	public static function activate() {
		// Create database tables
		Database_Manager::create_tables();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Set initial plugin options
		update_option( 'pseo_plugin_activated', true );
	}

	/**
	 * Deactivate plugin
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Uninstall plugin
	 */
	public static function uninstall() {
		// Drop database tables
		Database_Manager::drop_tables();

		// Delete options
		delete_option( 'pseo_plugin_activated' );
		delete_option( 'pseo_settings' );
	}
}
