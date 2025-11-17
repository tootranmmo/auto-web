<?php
/**
 * Performance Analytics Manager Class
 *
 * @package ProgrammaticSEO\Analytics
 */

namespace ProgrammaticSEO\Analytics;

use ProgrammaticSEO\Database\Database_Manager;

/**
 * Class Analytics_Manager
 */
class Analytics_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_footer', array( $this, 'track_page_view' ) );
		add_action( 'admin_init', array( $this, 'register_analytics_menu' ) );
	}

	/**
	 * Track page view
	 */
	public function track_page_view() {
		if ( ! is_singular( 'pseo_generated' ) ) {
			return;
		}

		global $post;

		// Record view in database
		Database_Manager::record_page_view( $post->ID );

		// Track via analytics service
		$this->log_event( $post->ID, 'pageview' );
	}

	/**
	 * Log event
	 *
	 * @param int    $post_id Post ID.
	 * @param string $event_type Event type.
	 * @param array  $data Additional event data.
	 */
	public function log_event( $post_id, $event_type = 'pageview', $data = array() ) {
		// Store event in database
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_analytics',
			array(
				'post_id' => $post_id,
				'date'    => gmdate( 'Y-m-d' ),
				'views'   => isset( $data['views'] ) ? $data['views'] : 1,
				'clicks'  => isset( $data['clicks'] ) ? $data['clicks'] : 0,
			),
			array( '%d', '%s', '%d', '%d' )
		);
	}

	/**
	 * Get page analytics
	 *
	 * @param int $post_id Post ID.
	 * @return array Analytics data
	 */
	public function get_page_analytics( $post_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(views) as total_views,
					COUNT(DISTINCT date) as days_tracked,
					AVG(views) as avg_views_per_day,
					MAX(views) as peak_views
				FROM {$wpdb->prefix}pseo_analytics
				WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get analytics for date range
	 *
	 * @param int    $post_id Post ID.
	 * @param string $start_date Start date (Y-m-d).
	 * @param string $end_date End date (Y-m-d).
	 * @return array Analytics records
	 */
	public function get_analytics_range( $post_id, $start_date, $end_date ) {
		return Database_Manager::get_analytics( $post_id, $start_date, $end_date );
	}

	/**
	 * Get top performing pages
	 *
	 * @param int $limit Number of pages.
	 * @param int $days Number of days to analyze.
	 * @return array Top pages
	 */
	public function get_top_pages( $limit = 10, $days = 30 ) {
		global $wpdb;

		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.ID,
					p.post_title,
					SUM(a.views) as total_views,
					AVG(a.views) as avg_views
				FROM {$wpdb->prefix}pseo_generated_pages p
				LEFT JOIN {$wpdb->prefix}pseo_analytics a ON p.post_id = a.post_id
				WHERE a.date >= %s
				GROUP BY p.ID
				ORDER BY total_views DESC
				LIMIT %d",
				$start_date,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get page impressions
	 *
	 * @param int $post_id Post ID.
	 * @return int Total impressions
	 */
	public function get_impressions( $post_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(views) FROM {$wpdb->prefix}pseo_analytics WHERE post_id = %d",
				$post_id
			)
		);
	}

	/**
	 * Get click-through rate
	 *
	 * @param int $post_id Post ID.
	 * @return float CTR percentage
	 */
	public function get_ctr( $post_id ) {
		global $wpdb;

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT SUM(clicks) as clicks, SUM(views) as views FROM {$wpdb->prefix}pseo_analytics WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		if ( ! $result || $result['views'] == 0 ) {
			return 0;
		}

		return ( $result['clicks'] / $result['views'] ) * 100;
	}

	/**
	 * Get bounce rate
	 *
	 * @param int $post_id Post ID.
	 * @return float Bounce rate percentage
	 */
	public function get_bounce_rate( $post_id ) {
		global $wpdb;

		$bounce_rate = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(bounce_rate) FROM {$wpdb->prefix}pseo_analytics WHERE post_id = %d",
				$post_id
			)
		);

		return (float) $bounce_rate;
	}

	/**
	 * Track keyword ranking
	 *
	 * @param int    $post_id Post ID.
	 * @param string $keyword Keyword.
	 * @param int    $position Search result position.
	 * @param int    $search_volume Search volume.
	 */
	public function track_keyword_ranking( $post_id, $keyword, $position = 0, $search_volume = 0 ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_keyword_rankings',
			array(
				'post_id'       => $post_id,
				'keyword'       => $keyword,
				'search_volume' => $search_volume,
				'position'      => $position,
				'date'          => gmdate( 'Y-m-d' ),
			),
			array( '%d', '%s', '%d', '%d', '%s' )
		);
	}

	/**
	 * Get keyword rankings
	 *
	 * @param int $post_id Post ID.
	 * @return array Rankings
	 */
	public function get_keyword_rankings( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT keyword, position, search_volume FROM {$wpdb->prefix}pseo_keyword_rankings WHERE post_id = %d ORDER BY position ASC",
				$post_id
			),
			ARRAY_A
		);
	}

	/**
	 * Generate analytics report
	 *
	 * @param int $days Number of days for report.
	 * @return array Report data
	 */
	public function generate_report( $days = 30 ) {
		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		global $wpdb;

		$total_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(views) FROM {$wpdb->prefix}pseo_analytics WHERE date >= %s",
				$start_date
			)
		);

		$total_pages = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published'"
		);

		return array(
			'period'          => "{$days} days",
			'start_date'      => $start_date,
			'end_date'        => gmdate( 'Y-m-d' ),
			'total_views'     => $total_views,
			'total_pages'     => $total_pages,
			'avg_views_per_page' => $total_pages > 0 ? round( $total_views / $total_pages, 2 ) : 0,
			'top_pages'       => $this->get_top_pages( 5, $days ),
		);
	}

	/**
	 * Register analytics menu
	 */
	public function register_analytics_menu() {
		add_action( 'admin_menu', array( $this, 'add_analytics_submenu' ) );
	}

	/**
	 * Add analytics submenu
	 */
	public function add_analytics_submenu() {
		// Analytics submenu is already added in Admin_Dashboard
	}

	/**
	 * Export analytics data
	 *
	 * @param string $format Export format (csv, json, xlsx).
	 * @param int    $days Number of days to export.
	 * @return string Exported data
	 */
	public function export_analytics( $format = 'csv', $days = 30 ) {
		$report = $this->generate_report( $days );

		switch ( $format ) {
			case 'json':
				return wp_json_encode( $report );

			case 'csv':
			default:
				return $this->array_to_csv( $report );
		}
	}

	/**
	 * Convert array to CSV
	 *
	 * @param array $data Data to convert.
	 * @return string CSV string
	 */
	private function array_to_csv( $data ) {
		$output = fopen( 'php://memory', 'r+' );

		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				fputcsv( $output, array( $key, wp_json_encode( $value ) ) );
			} else {
				fputcsv( $output, array( $key, $value ) );
			}
		}

		rewind( $output );
		return stream_get_contents( $output );
	}
}
