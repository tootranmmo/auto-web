<?php
/**
 * Admin Dashboard Class
 *
 * @package ProgrammaticSEO\Admin
 */

namespace ProgrammaticSEO\Admin;

use ProgrammaticSEO\Database\Database_Manager;

/**
 * Class Admin_Dashboard
 */
class Admin_Dashboard {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Programmatic SEO', 'programmatic-seo' ),
			__( 'Programmatic SEO', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo',
			array( $this, 'render_dashboard' ),
			'dashicons-chart-line',
			26
		);

		add_submenu_page(
			'programmatic-seo',
			__( 'Dashboard', 'programmatic-seo' ),
			__( 'Dashboard', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'programmatic-seo',
			__( 'Templates', 'programmatic-seo' ),
			__( 'Templates', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo-templates',
			array( $this, 'render_templates_page' )
		);

		add_submenu_page(
			'programmatic-seo',
			__( 'Data Sources', 'programmatic-seo' ),
			__( 'Data Sources', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo-data-sources',
			array( $this, 'render_data_sources_page' )
		);

		add_submenu_page(
			'programmatic-seo',
			__( 'Generated Pages', 'programmatic-seo' ),
			__( 'Generated Pages', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo-generated-pages',
			array( $this, 'render_generated_pages_page' )
		);

		add_submenu_page(
			'programmatic-seo',
			__( 'Analytics', 'programmatic-seo' ),
			__( 'Analytics', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo-analytics',
			array( $this, 'render_analytics_page' )
		);

		add_submenu_page(
			'programmatic-seo',
			__( 'Settings', 'programmatic-seo' ),
			__( 'Settings', 'programmatic-seo' ),
			'manage_options',
			'programmatic-seo-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render dashboard
	 */
	public function render_dashboard() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Programmatic SEO Dashboard', 'programmatic-seo' ); ?></h1>
			<div id="pseo-dashboard" class="pseo-dashboard">
				<div class="dashboard-cards">
					<div class="card">
						<h3><?php esc_html_e( 'Total Templates', 'programmatic-seo' ); ?></h3>
						<p class="number"><?php echo esc_html( $this->get_total_templates() ); ?></p>
					</div>
					<div class="card">
						<h3><?php esc_html_e( 'Total Data Sources', 'programmatic-seo' ); ?></h3>
						<p class="number"><?php echo esc_html( $this->get_total_data_sources() ); ?></p>
					</div>
					<div class="card">
						<h3><?php esc_html_e( 'Generated Pages', 'programmatic-seo' ); ?></h3>
						<p class="number"><?php echo esc_html( $this->get_total_generated_pages() ); ?></p>
					</div>
					<div class="card">
						<h3><?php esc_html_e( 'Total Page Views', 'programmatic-seo' ); ?></h3>
						<p class="number"><?php echo esc_html( $this->get_total_page_views() ); ?></p>
					</div>
				</div>

				<div class="dashboard-info">
					<h2><?php esc_html_e( 'Getting Started', 'programmatic-seo' ); ?></h2>
					<ol>
						<li><?php esc_html_e( 'Create a template for your pages', 'programmatic-seo' ); ?></li>
						<li><?php esc_html_e( 'Add a data source (CSV, JSON, or API)', 'programmatic-seo' ); ?></li>
						<li><?php esc_html_e( 'Generate pages automatically', 'programmatic-seo' ); ?></li>
						<li><?php esc_html_e( 'Monitor analytics and performance', 'programmatic-seo' ); ?></li>
					</ol>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render templates page
	 */
	public function render_templates_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Programmatic SEO Templates', 'programmatic-seo' ); ?></h1>
			<a href="?page=programmatic-seo-templates&action=new" class="button button-primary"><?php esc_html_e( 'Create New Template', 'programmatic-seo' ); ?></a>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Slug', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Created', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'programmatic-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$templates = Database_Manager::get_all_templates();
					foreach ( $templates as $template ) {
						?>
						<tr>
							<td><?php echo esc_html( $template['name'] ); ?></td>
							<td><code><?php echo esc_html( $template['slug'] ); ?></code></td>
							<td><?php echo esc_html( ucfirst( $template['status'] ) ); ?></td>
							<td><?php echo esc_html( $template['created_at'] ); ?></td>
							<td>
								<a href="?page=programmatic-seo-templates&action=edit&id=<?php echo esc_attr( $template['id'] ); ?>"><?php esc_html_e( 'Edit', 'programmatic-seo' ); ?></a> |
								<a href="?page=programmatic-seo-templates&action=delete&id=<?php echo esc_attr( $template['id'] ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'programmatic-seo' ); ?>');"><?php esc_html_e( 'Delete', 'programmatic-seo' ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render data sources page
	 */
	public function render_data_sources_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Data Sources', 'programmatic-seo' ); ?></h1>
			<a href="?page=programmatic-seo-data-sources&action=new" class="button button-primary"><?php esc_html_e( 'Add New Data Source', 'programmatic-seo' ); ?></a>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Name', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Type', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Sync Status', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Last Sync', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'programmatic-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					global $wpdb;
					$sources = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pseo_data_sources ORDER BY created_at DESC", ARRAY_A );
					foreach ( $sources as $source ) {
						?>
						<tr>
							<td><?php echo esc_html( $source['name'] ); ?></td>
							<td><?php echo esc_html( ucfirst( $source['type'] ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $source['sync_status'] ) ); ?></td>
							<td><?php echo esc_html( $source['last_sync'] ?? '-' ); ?></td>
							<td>
								<a href="?page=programmatic-seo-data-sources&action=edit&id=<?php echo esc_attr( $source['id'] ); ?>"><?php esc_html_e( 'Edit', 'programmatic-seo' ); ?></a> |
								<a href="?page=programmatic-seo-data-sources&action=delete&id=<?php echo esc_attr( $source['id'] ); ?>" onclick="return confirm('<?php esc_attr_e( 'Are you sure?', 'programmatic-seo' ); ?>');"><?php esc_html_e( 'Delete', 'programmatic-seo' ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render generated pages page
	 */
	public function render_generated_pages_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Generated Pages', 'programmatic-seo' ); ?></h1>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Title', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Slug', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Views', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Created', 'programmatic-seo' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'programmatic-seo' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$pages = Database_Manager::get_generated_pages( array( 'limit' => 50 ) );
					foreach ( $pages as $page ) {
						?>
						<tr>
							<td><?php echo esc_html( $page['title'] ); ?></td>
							<td><code><?php echo esc_html( $page['slug'] ); ?></code></td>
							<td><?php echo esc_html( ucfirst( $page['status'] ) ); ?></td>
							<td><?php echo esc_html( $page['views'] ); ?></td>
							<td><?php echo esc_html( $page['created_at'] ); ?></td>
							<td>
								<a href="<?php echo esc_url( get_permalink( $page['post_id'] ) ); ?>" target="_blank"><?php esc_html_e( 'View', 'programmatic-seo' ); ?></a> |
								<a href="post.php?post=<?php echo esc_attr( $page['post_id'] ); ?>&action=edit"><?php esc_html_e( 'Edit', 'programmatic-seo' ); ?></a>
							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render analytics page
	 */
	public function render_analytics_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Analytics', 'programmatic-seo' ); ?></h1>
			<div id="pseo-analytics">
				<p><?php esc_html_e( 'Analytics dashboard coming soon', 'programmatic-seo' ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Programmatic SEO Settings', 'programmatic-seo' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'programmatic_seo_settings' ); ?>
				<?php do_settings_sections( 'programmatic_seo_settings' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'programmatic_seo_settings', 'pseo_settings' );

		add_settings_section(
			'pseo_general',
			__( 'General Settings', 'programmatic-seo' ),
			array( $this, 'general_settings_callback' ),
			'programmatic_seo_settings'
		);

		add_settings_field(
			'pseo_auto_internal_links',
			__( 'Auto Internal Linking', 'programmatic-seo' ),
			array( $this, 'auto_internal_links_callback' ),
			'programmatic_seo_settings',
			'pseo_general'
		);
	}

	/**
	 * General settings callback
	 */
	public function general_settings_callback() {
		echo wp_kses_post( '<p>Configure general plugin settings</p>' );
	}

	/**
	 * Auto internal links callback
	 */
	public function auto_internal_links_callback() {
		$options = get_option( 'pseo_settings' );
		$value   = isset( $options['auto_internal_links'] ) ? $options['auto_internal_links'] : 0;
		?>
		<input type="checkbox" name="pseo_settings[auto_internal_links]" value="1" <?php checked( $value, 1 ); ?> />
		<label><?php esc_html_e( 'Enable automatic internal linking', 'programmatic-seo' ); ?></label>
		<?php
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Hook name.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( strpos( $hook, 'programmatic-seo' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'pseo-admin',
			PSEO_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			PSEO_VERSION
		);

		wp_enqueue_script(
			'pseo-admin',
			PSEO_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			PSEO_VERSION,
			true
		);
	}

	/**
	 * Get total templates
	 *
	 * @return int
	 */
	private function get_total_templates() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_templates" );
	}

	/**
	 * Get total data sources
	 *
	 * @return int
	 */
	private function get_total_data_sources() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_data_sources" );
	}

	/**
	 * Get total generated pages
	 *
	 * @return int
	 */
	private function get_total_generated_pages() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages" );
	}

	/**
	 * Get total page views
	 *
	 * @return int
	 */
	private function get_total_page_views() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT SUM(views) FROM {$wpdb->prefix}pseo_generated_pages" );
	}
}
