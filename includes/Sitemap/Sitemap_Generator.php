<?php
/**
 * Dynamic Sitemap Generator Class
 *
 * @package ProgrammaticSEO\Sitemap
 */

namespace ProgrammaticSEO\Sitemap;

use ProgrammaticSEO\Database\Database_Manager;

/**
 * Class Sitemap_Generator
 */
class Sitemap_Generator {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_sitemap_routes' ) );
		add_filter( 'wp_sitemaps_post_types', array( $this, 'add_post_type_to_sitemap' ) );
	}

	/**
	 * Register custom sitemap routes
	 */
	public function register_sitemap_routes() {
		// Register route for programmatic SEO pages sitemap
		add_rewrite_rule(
			'^sitemap-pseo\.xml$',
			'index.php?pseo_sitemap=1',
			'top'
		);

		// Register route for main programmatic SEO sitemap index
		add_rewrite_rule(
			'^sitemap-pseo-index\.xml$',
			'index.php?pseo_sitemap_index=1',
			'top'
		);

		add_query_var( 'pseo_sitemap' );
		add_query_var( 'pseo_sitemap_index' );
	}

	/**
	 * Add pseo_generated post type to sitemap
	 *
	 * @param array $post_types Post types.
	 * @return array Modified post types
	 */
	public function add_post_type_to_sitemap( $post_types ) {
		$post_types[] = 'pseo_generated';
		return $post_types;
	}

	/**
	 * Generate XML sitemap for programmatic SEO pages
	 *
	 * @return string XML sitemap
	 */
	public function generate_xml_sitemap() {
		$pages = Database_Manager::get_generated_pages( array( 'limit' => 999999 ) );

		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		foreach ( $pages as $page ) {
			$post = get_post( $page['post_id'] );
			if ( ! $post ) {
				continue;
			}

			$xml .= '  <url>' . PHP_EOL;
			$xml .= '    <loc>' . esc_url( get_permalink( $post->ID ) ) . '</loc>' . PHP_EOL;
			$xml .= '    <lastmod>' . mysql2date( 'Y-m-d\TH:i:sP', $post->post_modified_gmt ) . '</lastmod>' . PHP_EOL;
			$xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
			$xml .= '    <priority>0.8</priority>' . PHP_EOL;
			$xml .= '  </url>' . PHP_EOL;
		}

		$xml .= '</urlset>';

		return $xml;
	}

	/**
	 * Generate sitemap index
	 *
	 * @return string XML sitemap index
	 */
	public function generate_sitemap_index() {
		$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

		// Add main WordPress sitemap
		$xml .= '  <sitemap>' . PHP_EOL;
		$xml .= '    <loc>' . esc_url( home_url( 'sitemap.xml' ) ) . '</loc>' . PHP_EOL;
		$xml .= '  </sitemap>' . PHP_EOL;

		// Add programmatic SEO sitemap
		$xml .= '  <sitemap>' . PHP_EOL;
		$xml .= '    <loc>' . esc_url( home_url( 'sitemap-pseo.xml' ) ) . '</loc>' . PHP_EOL;
		$xml .= '  </sitemap>' . PHP_EOL;

		$xml .= '</sitemapindex>';

		return $xml;
	}

	/**
	 * Generate HTML sitemap
	 *
	 * @return string HTML sitemap
	 */
	public function generate_html_sitemap() {
		$pages = Database_Manager::get_generated_pages( array( 'limit' => 999999 ) );

		$html = '<div class="pseo-html-sitemap">' . PHP_EOL;
		$html .= '<h1>' . esc_html__( 'Sitemap', 'programmatic-seo' ) . '</h1>' . PHP_EOL;
		$html .= '<ul>' . PHP_EOL;

		foreach ( $pages as $page ) {
			$post = get_post( $page['post_id'] );
			if ( ! $post ) {
				continue;
			}

			$html .= '  <li><a href="' . esc_url( get_permalink( $post->ID ) ) . '">' . esc_html( $post->post_title ) . '</a></li>' . PHP_EOL;
		}

		$html .= '</ul>' . PHP_EOL;
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get page count
	 *
	 * @return int Number of pages
	 */
	public function get_page_count() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published'" );
	}

	/**
	 * Update sitemap
	 *
	 * @param int $post_id Post ID.
	 */
	public function update_sitemap( $post_id = null ) {
		// Ping search engines
		$this->ping_search_engines();
	}

	/**
	 * Ping search engines about sitemap update
	 */
	private function ping_search_engines() {
		$sitemap_url = home_url( 'sitemap-pseo.xml' );

		// Ping Google
		wp_remote_get(
			'https://www.google.com/ping?sitemap=' . urlencode( $sitemap_url ),
			array( 'blocking' => false )
		);

		// Ping Bing
		wp_remote_get(
			'https://www.bing.com/ping?sitemap=' . urlencode( $sitemap_url ),
			array( 'blocking' => false )
		);
	}

	/**
	 * Output sitemap headers
	 *
	 * @param string $type Sitemap type (xml, index, html).
	 */
	public function output_sitemap( $type = 'xml' ) {
		switch ( $type ) {
			case 'xml':
				header( 'Content-Type: application/xml; charset=UTF-8' );
				echo wp_kses_post( $this->generate_xml_sitemap() );
				break;

			case 'index':
				header( 'Content-Type: application/xml; charset=UTF-8' );
				echo wp_kses_post( $this->generate_sitemap_index() );
				break;

			case 'html':
				echo wp_kses_post( $this->generate_html_sitemap() );
				break;
		}

		exit;
	}

	/**
	 * Register sitemap shortcode
	 */
	public function register_shortcodes() {
		add_shortcode( 'pseo_sitemap', array( $this, 'sitemap_shortcode' ) );
	}

	/**
	 * Sitemap shortcode
	 *
	 * @return string HTML sitemap
	 */
	public function sitemap_shortcode() {
		return $this->generate_html_sitemap();
	}

	/**
	 * Get sitemap statistics
	 *
	 * @return array Statistics
	 */
	public function get_statistics() {
		global $wpdb;

		return array(
			'total_pages'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages" ),
			'published'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published'" ),
			'draft'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'draft'" ),
			'trash'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'trash'" ),
		);
	}
}
