<?php
/**
 * Advanced Reporting & Business Intelligence
 *
 * @package ProgrammaticSEO\Reports
 */

namespace ProgrammaticSEO\Reports;

/**
 * Class Advanced_Reporting
 * Custom reports, scheduled emails, and BI export
 */
class Advanced_Reporting {

	/**
	 * Reports table
	 *
	 * @var string
	 */
	private $reports_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->reports_table = $wpdb->prefix . 'pseo_reports';

		$this->create_reports_table();
		add_action( 'admin_init', array( $this, 'schedule_reports' ) );
		add_action( 'pseo_generate_scheduled_reports', array( $this, 'generate_scheduled_reports' ) );
	}

	/**
	 * Create reports table
	 */
	private function create_reports_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->reports_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			report_type VARCHAR(100) NOT NULL,
			title VARCHAR(255) NOT NULL,
			description TEXT,
			data LONGTEXT,
			generated_at DATETIME,
			sent_to EMAIL,
			format VARCHAR(50) DEFAULT 'html',
			file_path VARCHAR(255),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY report_type (report_type),
			KEY generated_at (generated_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Generate comprehensive SEO report
	 *
	 * @param array $args Report arguments.
	 * @return array Report data
	 */
	public function generate_seo_report( $args = array() ) {
		$defaults = array(
			'start_date' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
			'end_date'   => gmdate( 'Y-m-d' ),
			'include'    => array( 'overview', 'pages', 'keywords', 'traffic', 'rankings' ),
		);

		$args = wp_parse_args( $args, $defaults );

		$report = array(
			'title'     => 'SEO Performance Report',
			'period'    => $args['start_date'] . ' to ' . $args['end_date'],
			'generated' => current_time( 'mysql' ),
			'sections'  => array(),
		);

		// Overview section
		if ( in_array( 'overview', $args['include'] ) ) {
			$report['sections']['overview'] = $this->get_overview_section( $args['start_date'], $args['end_date'] );
		}

		// Top pages section
		if ( in_array( 'pages', $args['include'] ) ) {
			$report['sections']['top_pages'] = $this->get_top_pages_section( $args['start_date'], $args['end_date'] );
		}

		// Keywords section
		if ( in_array( 'keywords', $args['include'] ) ) {
			$report['sections']['keywords'] = $this->get_keywords_section( $args['start_date'], $args['end_date'] );
		}

		// Traffic section
		if ( in_array( 'traffic', $args['include'] ) ) {
			$report['sections']['traffic'] = $this->get_traffic_section( $args['start_date'], $args['end_date'] );
		}

		// Rankings section
		if ( in_array( 'rankings', $args['include'] ) ) {
			$report['sections']['rankings'] = $this->get_rankings_section( $args['start_date'], $args['end_date'] );
		}

		// Recommendations section
		$report['sections']['recommendations'] = $this->get_recommendations_section( $report );

		return $report;
	}

	/**
	 * Get overview section
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Overview data
	 */
	private function get_overview_section( $start_date, $end_date ) {
		global $wpdb;

		$total_pages = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published'"
		);

		$total_views = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(views) FROM {$wpdb->prefix}pseo_analytics
				WHERE date BETWEEN %s AND %s",
				$start_date,
				$end_date
			)
		);

		$total_clicks = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(clicks) FROM {$wpdb->prefix}pseo_analytics
				WHERE date BETWEEN %s AND %s",
				$start_date,
				$end_date
			)
		);

		$avg_position = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT AVG(position) FROM {$wpdb->prefix}pseo_keyword_rankings
				WHERE date BETWEEN %s AND %s",
				$start_date,
				$end_date
			)
		);

		return array(
			'total_pages'   => $total_pages,
			'total_views'   => $total_views ?? 0,
			'total_clicks'  => $total_clicks ?? 0,
			'avg_position'  => round( $avg_position ?? 0, 2 ),
			'ctr'           => $total_views > 0 ? round( ( $total_clicks / $total_views ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get top pages section
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Top pages data
	 */
	private function get_top_pages_section( $start_date, $end_date ) {
		global $wpdb;

		$pages = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					p.post_id,
					p.title,
					p.slug,
					SUM(a.views) as total_views,
					SUM(a.clicks) as total_clicks,
					AVG(a.avg_position) as avg_position
				FROM {$wpdb->prefix}pseo_generated_pages p
				LEFT JOIN {$wpdb->prefix}pseo_analytics a ON p.post_id = a.post_id
				WHERE a.date BETWEEN %s AND %s
				GROUP BY p.post_id
				ORDER BY total_views DESC
				LIMIT 10",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		return array(
			'count'  => count( $pages ),
			'pages'  => $pages,
		);
	}

	/**
	 * Get keywords section
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Keywords data
	 */
	private function get_keywords_section( $start_date, $end_date ) {
		global $wpdb;

		// Top ranking keywords
		$keywords = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					keyword,
					MIN(position) as best_position,
					MAX(position) as worst_position,
					AVG(position) as avg_position,
					search_volume,
					COUNT(*) as tracking_days
				FROM {$wpdb->prefix}pseo_keyword_rankings
				WHERE date BETWEEN %s AND %s
				GROUP BY keyword
				ORDER BY avg_position ASC
				LIMIT 50",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		// Keywords with position improvement
		$improving = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					keyword,
					(SELECT position FROM {$wpdb->prefix}pseo_keyword_rankings
					 WHERE keyword = kr.keyword AND date = %s LIMIT 1) as current_position,
					(SELECT position FROM {$wpdb->prefix}pseo_keyword_rankings
					 WHERE keyword = kr.keyword AND date = %s LIMIT 1) as previous_position
				FROM {$wpdb->prefix}pseo_keyword_rankings kr
				GROUP BY keyword
				HAVING previous_position > current_position",
				$end_date,
				$start_date
			),
			ARRAY_A
		);

		return array(
			'total_keywords'   => count( $keywords ),
			'top_keywords'     => array_slice( $keywords, 0, 10 ),
			'improving'        => array_slice( $improving, 0, 10 ),
			'all_keywords'     => $keywords,
		);
	}

	/**
	 * Get traffic section
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Traffic data
	 */
	private function get_traffic_section( $start_date, $end_date ) {
		global $wpdb;

		// Daily traffic data for charting
		$daily_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					date,
					SUM(views) as views,
					SUM(clicks) as clicks,
					AVG(bounce_rate) as bounce_rate
				FROM {$wpdb->prefix}pseo_analytics
				WHERE date BETWEEN %s AND %s
				GROUP BY date
				ORDER BY date ASC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		// Traffic by device (if available)
		$total_views = array_sum( array_column( $daily_data, 'views' ) );

		return array(
			'total_views'  => $total_views,
			'daily_data'   => $daily_data,
			'avg_daily'    => count( $daily_data ) > 0 ? round( $total_views / count( $daily_data ), 2 ) : 0,
		);
	}

	/**
	 * Get rankings section
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return array Rankings data
	 */
	private function get_rankings_section( $start_date, $end_date ) {
		global $wpdb;

		$rankings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					position,
					COUNT(*) as count
				FROM {$wpdb->prefix}pseo_keyword_rankings
				WHERE date BETWEEN %s AND %s
				GROUP BY position
				ORDER BY position ASC",
				$start_date,
				$end_date
			),
			ARRAY_A
		);

		$position_buckets = array(
			'top3'       => 0,
			'top10'      => 0,
			'top20'      => 0,
			'top50'      => 0,
			'top100'     => 0,
			'below100'   => 0,
		);

		foreach ( $rankings as $ranking ) {
			if ( $ranking['position'] <= 3 ) {
				$position_buckets['top3'] += $ranking['count'];
			}
			if ( $ranking['position'] <= 10 ) {
				$position_buckets['top10'] += $ranking['count'];
			}
			if ( $ranking['position'] <= 20 ) {
				$position_buckets['top20'] += $ranking['count'];
			}
			if ( $ranking['position'] <= 50 ) {
				$position_buckets['top50'] += $ranking['count'];
			}
			if ( $ranking['position'] <= 100 ) {
				$position_buckets['top100'] += $ranking['count'];
			}
			if ( $ranking['position'] > 100 ) {
				$position_buckets['below100'] += $ranking['count'];
			}
		}

		return $position_buckets;
	}

	/**
	 * Get recommendations section
	 *
	 * @param array $report Report data.
	 * @return array Recommendations
	 */
	private function get_recommendations_section( $report ) {
		$recommendations = array();

		// Analyze top pages
		if ( isset( $report['sections']['top_pages'] ) ) {
			$top_pages = $report['sections']['top_pages'];
			$recommendations[] = array(
				'title'       => 'Top Performing Pages',
				'description' => 'You have ' . $top_pages['count'] . ' top performing pages. Consider creating related content.',
				'priority'    => 'high',
			);
		}

		// Analyze keywords
		if ( isset( $report['sections']['keywords'] ) ) {
			$keywords = $report['sections']['keywords'];
			if ( ! empty( $keywords['improving'] ) ) {
				$recommendations[] = array(
					'title'       => 'Keywords with Position Improvement',
					'description' => count( $keywords['improving'] ) . ' keywords are improving in rankings.',
					'priority'    => 'high',
				);
			}
		}

		// Analyze traffic
		if ( isset( $report['sections']['traffic'] ) ) {
			$traffic = $report['sections']['traffic'];
			if ( $traffic['total_views'] < 100 ) {
				$recommendations[] = array(
					'title'       => 'Low Traffic',
					'description' => 'Traffic is low. Consider promoting your content and building more backlinks.',
					'priority'    => 'medium',
				);
			}
		}

		return $recommendations;
	}

	/**
	 * Generate PDF report
	 *
	 * @param array $report Report data.
	 * @return string|false PDF file path or false
	 */
	public function generate_pdf_report( $report ) {
		// This would require a PDF library like TCPDF or mPDF
		// For now, we'll return a placeholder

		$filename = 'pseo-report-' . gmdate( 'Y-m-d-H-i-s' ) . '.pdf';
		$filepath = WP_CONTENT_DIR . '/uploads/pseo-reports/' . $filename;

		// Create directory if not exists
		if ( ! is_dir( dirname( $filepath ) ) ) {
			wp_mkdir_p( dirname( $filepath ) );
		}

		// Convert HTML to PDF (simplified implementation)
		$html = $this->generate_html_report( $report );

		// Save as file
		file_put_contents( $filepath, $html );

		pseo_log( "Report generated: {$filepath}", 'info' );

		return $filepath;
	}

	/**
	 * Generate HTML report
	 *
	 * @param array $report Report data.
	 * @return string HTML report
	 */
	public function generate_html_report( $report ) {
		$html = '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>' . esc_html( $report['title'] ) . '</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; color: #333; }
		h1 { color: #0073aa; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
		h2 { color: #0073aa; margin-top: 30px; }
		table { width: 100%; border-collapse: collapse; margin: 20px 0; }
		th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
		th { background-color: #f5f5f5; font-weight: bold; }
		.metric { display: inline-block; width: 23%; margin: 1%; padding: 15px; background: #f9f9f9; text-align: center; border-radius: 5px; }
		.metric-value { font-size: 24px; font-weight: bold; color: #0073aa; }
		.metric-label { font-size: 12px; color: #666; margin-top: 5px; }
		.section { page-break-inside: avoid; }
		.recommendation { background: #fffbea; border-left: 4px solid #ffb700; padding: 15px; margin: 10px 0; }
		.footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
	</style>
</head>
<body>
	<h1>' . esc_html( $report['title'] ) . '</h1>
	<p>Period: ' . esc_html( $report['period'] ) . '</p>
	<p>Generated: ' . esc_html( $report['generated'] ) . '</p>';

		// Overview metrics
		if ( isset( $report['sections']['overview'] ) ) {
			$overview = $report['sections']['overview'];
			$html .= '<div class="section">
		<h2>Overview</h2>
		<div class="metric">
			<div class="metric-value">' . number_format( $overview['total_pages'] ) . '</div>
			<div class="metric-label">Total Pages</div>
		</div>
		<div class="metric">
			<div class="metric-value">' . number_format( $overview['total_views'] ) . '</div>
			<div class="metric-label">Total Views</div>
		</div>
		<div class="metric">
			<div class="metric-value">' . number_format( $overview['total_clicks'] ) . '</div>
			<div class="metric-label">Total Clicks</div>
		</div>
		<div class="metric">
			<div class="metric-value">' . number_format( $overview['ctr'], 2 ) . '%</div>
			<div class="metric-label">Click-Through Rate</div>
		</div>
	</div>';
		}

		// Top pages
		if ( isset( $report['sections']['top_pages'] ) ) {
			$top_pages = $report['sections']['top_pages'];
			$html .= '<div class="section">
		<h2>Top Performing Pages</h2>
		<table>
			<tr>
				<th>Title</th>
				<th>Views</th>
				<th>Clicks</th>
				<th>Avg Position</th>
			</tr>';
			foreach ( $top_pages['pages'] as $page ) {
				$html .= '<tr>
				<td>' . esc_html( $page['title'] ) . '</td>
				<td>' . number_format( $page['total_views'] ?? 0 ) . '</td>
				<td>' . number_format( $page['total_clicks'] ?? 0 ) . '</td>
				<td>' . number_format( $page['avg_position'] ?? 0, 1 ) . '</td>
			</tr>';
			}
			$html .= '</table>
	</div>';
		}

		// Recommendations
		if ( isset( $report['sections']['recommendations'] ) ) {
			$html .= '<div class="section">
		<h2>Recommendations</h2>';
			foreach ( $report['sections']['recommendations'] as $rec ) {
				$html .= '<div class="recommendation">
			<strong>' . esc_html( $rec['title'] ) . '</strong>
			<p>' . esc_html( $rec['description'] ) . '</p>
		</div>';
			}
			$html .= '</div>';
		}

		$html .= '<div class="footer">
	<p>Report generated by Programmatic SEO Plugin</p>
</div>
</body>
</html>';

		return $html;
	}

	/**
	 * Send report via email
	 *
	 * @param array  $report Report data.
	 * @param string $email_to Recipient email.
	 * @param string $format Report format (html, pdf).
	 * @return bool Success
	 */
	public function send_report_email( $report, $email_to, $format = 'html' ) {
		$subject = $report['title'] . ' - ' . $report['period'];

		if ( $format === 'pdf' ) {
			$filepath = $this->generate_pdf_report( $report );
			if ( ! $filepath ) {
				return false;
			}

			$attachments = array( $filepath );
			$body = 'Please find the attached SEO report.';
		} else {
			$body = $this->generate_html_report( $report );
			$attachments = array();
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$result = wp_mail( $email_to, $subject, $body, $headers, $attachments );

		if ( $result ) {
			pseo_log( "Report sent to {$email_to}", 'info' );
		}

		return $result;
	}

	/**
	 * Schedule reports
	 */
	public function schedule_reports() {
		if ( ! wp_next_scheduled( 'pseo_generate_scheduled_reports' ) ) {
			wp_schedule_event( time(), 'weekly', 'pseo_generate_scheduled_reports' );
		}
	}

	/**
	 * Generate scheduled reports
	 */
	public function generate_scheduled_reports() {
		$admin_email = get_option( 'admin_email' );

		// Generate weekly report
		$report = $this->generate_seo_report(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				'end_date'   => gmdate( 'Y-m-d' ),
			)
		);

		// Send email
		$this->send_report_email( $report, $admin_email, 'html' );

		pseo_log( 'Scheduled reports generated and sent', 'info' );
	}

	/**
	 * Export data to CSV
	 *
	 * @param array $data Data to export.
	 * @param string $filename Output filename.
	 * @return string CSV data
	 */
	public function export_csv( $data, $filename = 'report.csv' ) {
		$output = fopen( 'php://memory', 'r+' );

		// Write header
		if ( ! empty( $data ) && is_array( $data[0] ) ) {
			fputcsv( $output, array_keys( $data[0] ) );
		}

		// Write data rows
		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		rewind( $output );
		$csv = stream_get_contents( $output );
		fclose( $output );

		return $csv;
	}

	/**
	 * Export data to JSON
	 *
	 * @param array $data Data to export.
	 * @return string JSON data
	 */
	public function export_json( $data ) {
		return wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}
}
