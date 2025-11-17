<?php
/**
 * Advanced Analytics Dashboard
 *
 * @package ProgrammaticSEO\Dashboard
 */

namespace ProgrammaticSEO\Dashboard;

/**
 * Class Analytics_Dashboard
 * Real-time visitor tracking, keyword rankings, and performance analytics
 */
class Analytics_Dashboard {

	/**
	 * Visitors table
	 *
	 * @var string
	 */
	private $visitors_table;

	/**
	 * Events table
	 *
	 * @var string
	 */
	private $events_table;

	/**
	 * Conversions table
	 *
	 * @var string
	 */
	private $conversions_table;

	/**
	 * Session heatmaps table
	 *
	 * @var string
	 */
	private $heatmaps_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->visitors_table = $wpdb->prefix . 'pseo_visitors';
		$this->events_table = $wpdb->prefix . 'pseo_events';
		$this->conversions_table = $wpdb->prefix . 'pseo_conversions';
		$this->heatmaps_table = $wpdb->prefix . 'pseo_heatmaps';

		$this->create_tables();
		add_action( 'wp_footer', array( $this, 'load_tracking_script' ) );
		add_action( 'wp_ajax_nopriv_pseo_track_event', array( $this, 'track_event_ajax' ) );
		add_action( 'wp_ajax_pseo_track_event', array( $this, 'track_event_ajax' ) );
	}

	/**
	 * Create necessary database tables
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Visitors table
		$sql_visitors = "CREATE TABLE IF NOT EXISTS {$this->visitors_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(100) NOT NULL,
			post_id BIGINT(20),
			user_id BIGINT(20),
			ip_address VARCHAR(45),
			user_agent TEXT,
			referer VARCHAR(500),
			device_type VARCHAR(50),
			country VARCHAR(100),
			city VARCHAR(100),
			bounce_rate FLOAT DEFAULT 0,
			session_duration INT DEFAULT 0,
			page_views INT DEFAULT 1,
			visited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY session_idx (session_id),
			INDEX post_idx (post_id),
			INDEX user_idx (user_id),
			INDEX date_idx (visited_at)
		) $charset_collate;";

		// Events table
		$sql_events = "CREATE TABLE IF NOT EXISTS {$this->events_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(100),
			post_id BIGINT(20),
			event_type VARCHAR(100),
			event_label VARCHAR(255),
			event_value VARCHAR(255),
			custom_data JSON,
			utm_source VARCHAR(100),
			utm_medium VARCHAR(100),
			utm_campaign VARCHAR(100),
			utm_content VARCHAR(100),
			utm_term VARCHAR(100),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX session_idx (session_id),
			INDEX post_idx (post_id),
			INDEX event_idx (event_type),
			INDEX date_idx (created_at)
		) $charset_collate;";

		// Conversions table
		$sql_conversions = "CREATE TABLE IF NOT EXISTS {$this->conversions_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(100),
			post_id BIGINT(20),
			conversion_type VARCHAR(100),
			conversion_value DECIMAL(10, 2),
			conversion_currency VARCHAR(3) DEFAULT 'USD',
			revenue DECIMAL(10, 2),
			form_id VARCHAR(100),
			form_name VARCHAR(255),
			custom_data JSON,
			converted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX post_idx (post_id),
			INDEX type_idx (conversion_type),
			INDEX date_idx (converted_at)
		) $charset_collate;";

		// Heatmaps table
		$sql_heatmaps = "CREATE TABLE IF NOT EXISTS {$this->heatmaps_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) NOT NULL,
			scroll_depth FLOAT,
			click_x INT,
			click_y INT,
			time_on_page INT,
			element_class VARCHAR(255),
			element_id VARCHAR(255),
			interaction_type VARCHAR(50),
			collected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX post_idx (post_id),
			INDEX date_idx (collected_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_visitors );
		dbDelta( $sql_events );
		dbDelta( $sql_conversions );
		dbDelta( $sql_heatmaps );
	}

	/**
	 * Load tracking script
	 */
	public function load_tracking_script() {
		if ( ! is_singular( 'pseo_generated' ) ) {
			return;
		}

		global $post;

		$tracking_script = "
		<script>
		(function() {
			var sessionId = localStorage.getItem('pseo_session_id');
			if (!sessionId) {
				sessionId = 'session_' + Math.random().toString(36).substr(2, 9);
				localStorage.setItem('pseo_session_id', sessionId);
			}

			window.pseoTracking = {
				sessionId: sessionId,
				postId: {$post->ID},
				startTime: Date.now(),
				scrollDepth: 0,

				trackEvent: function(eventType, label, value, customData) {
					var data = {
						action: 'pseo_track_event',
						session_id: this.sessionId,
						post_id: this.postId,
						event_type: eventType,
						event_label: label || '',
						event_value: value || '',
						custom_data: customData || {},
						utm_source: new URL(window.location).searchParams.get('utm_source') || '',
						utm_medium: new URL(window.location).searchParams.get('utm_medium') || '',
						utm_campaign: new URL(window.location).searchParams.get('utm_campaign') || ''
					};

					fetch('" . admin_url( 'admin-ajax.php' ) . "', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: Object.keys(data).map(k => encodeURIComponent(k) + '=' + encodeURIComponent(data[k])).join('&')
					});
				},

				trackScroll: function() {
					var windowHeight = window.innerHeight;
					var documentHeight = document.documentElement.scrollHeight;
					var scrollTop = window.scrollY;
					var totalScroll = documentHeight - windowHeight;
					var scrollPercentage = totalScroll > 0 ? (scrollTop / totalScroll) * 100 : 0;

					if (scrollPercentage > this.scrollDepth) {
						this.scrollDepth = scrollPercentage;
						if (scrollPercentage > 0 && scrollPercentage % 25 === 0) {
							this.trackEvent('scroll_depth', scrollPercentage + '%', scrollPercentage);
						}
					}
				},

				trackClick: function(e) {
					var rect = e.target.getBoundingClientRect();
					this.trackEvent('click', e.target.tagName, null, {
						x: Math.round(rect.left),
						y: Math.round(rect.top),
						class: e.target.className,
						id: e.target.id
					});
				}
			};

			window.addEventListener('scroll', function() {
				window.pseoTracking.trackScroll();
			});

			document.addEventListener('click', function(e) {
				if (e.target.tagName === 'A' && e.target.href.indexOf(window.location.origin) !== -1) {
					window.pseoTracking.trackClick(e);
				}
			});

			window.addEventListener('beforeunload', function() {
				var timeOnPage = Math.round((Date.now() - window.pseoTracking.startTime) / 1000);
				navigator.sendBeacon('" . admin_url( 'admin-ajax.php' ) . "?action=pseo_track_event&session_id=' + window.pseoTracking.sessionId + '&event_type=time_on_page&event_value=' + timeOnPage);
			});
		})();
		</script>
		";

		echo $tracking_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Track event via AJAX
	 */
	public function track_event_ajax() {
		$session_id = isset( $_POST['session_id'] ) ? sanitize_text_field( $_POST['session_id'] ) : '';
		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$event_type = isset( $_POST['event_type'] ) ? sanitize_text_field( $_POST['event_type'] ) : '';
		$event_label = isset( $_POST['event_label'] ) ? sanitize_text_field( $_POST['event_label'] ) : '';
		$event_value = isset( $_POST['event_value'] ) ? sanitize_text_field( $_POST['event_value'] ) : '';
		$custom_data = isset( $_POST['custom_data'] ) ? map_deep( wp_unslash( $_POST['custom_data'] ), 'sanitize_text_field' ) : array();
		$utm_source = isset( $_POST['utm_source'] ) ? sanitize_text_field( $_POST['utm_source'] ) : '';
		$utm_medium = isset( $_POST['utm_medium'] ) ? sanitize_text_field( $_POST['utm_medium'] ) : '';
		$utm_campaign = isset( $_POST['utm_campaign'] ) ? sanitize_text_field( $_POST['utm_campaign'] ) : '';

		$this->track_event( $session_id, $post_id, $event_type, $event_label, $event_value, $custom_data, $utm_source, $utm_medium, $utm_campaign );

		wp_die();
	}

	/**
	 * Track visitor event
	 *
	 * @param string $session_id Session ID.
	 * @param int    $post_id Post ID.
	 * @param string $event_type Event type.
	 * @param string $event_label Event label.
	 * @param string $event_value Event value.
	 * @param array  $custom_data Custom event data.
	 * @param string $utm_source UTM source.
	 * @param string $utm_medium UTM medium.
	 * @param string $utm_campaign UTM campaign.
	 */
	private function track_event( $session_id, $post_id, $event_type, $event_label = '', $event_value = '', $custom_data = array(), $utm_source = '', $utm_medium = '', $utm_campaign = '' ) {
		global $wpdb;

		$wpdb->insert(
			$this->events_table,
			array(
				'session_id'    => $session_id,
				'post_id'       => $post_id,
				'event_type'    => $event_type,
				'event_label'   => $event_label,
				'event_value'   => $event_value,
				'custom_data'   => wp_json_encode( $custom_data ),
				'utm_source'    => $utm_source,
				'utm_medium'    => $utm_medium,
				'utm_campaign'  => $utm_campaign,
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Track conversion
	 *
	 * @param string $session_id Session ID.
	 * @param int    $post_id Post ID.
	 * @param string $conversion_type Conversion type.
	 * @param float  $value Conversion value.
	 * @param string $currency Currency code.
	 * @param array  $custom_data Custom data.
	 * @return int|false Insert ID or false
	 */
	public function track_conversion( $session_id, $post_id, $conversion_type, $value = 0, $currency = 'USD', $custom_data = array() ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->conversions_table,
			array(
				'session_id'      => $session_id,
				'post_id'         => $post_id,
				'conversion_type' => $conversion_type,
				'conversion_value' => $value,
				'conversion_currency' => $currency,
				'revenue'         => $value,
				'custom_data'     => wp_json_encode( $custom_data ),
			),
			array( '%s', '%d', '%s', '%f', '%s', '%f', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Get real-time dashboard data
	 *
	 * @return array Dashboard data
	 */
	public function get_realtime_data() {
		global $wpdb;

		$last_hour = gmdate( 'Y-m-d H:i:s', strtotime( '-1 hour' ) );
		$last_24h = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );

		return array(
			'active_visitors'     => $this->get_active_visitors(),
			'events_last_hour'    => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->events_table} WHERE created_at > %s",
					$last_hour
				)
			),
			'conversions_last_24h' => (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->conversions_table} WHERE converted_at > %s",
					$last_24h
				)
			),
			'revenue_last_24h'    => (float) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT SUM(revenue) FROM {$this->conversions_table} WHERE converted_at > %s",
					$last_24h
				)
			) ?? 0,
			'top_pages'           => $this->get_top_pages(),
			'top_events'          => $this->get_top_events(),
		);
	}

	/**
	 * Get active visitors
	 *
	 * @return int Active visitor count
	 */
	private function get_active_visitors() {
		global $wpdb;

		$last_5_minutes = gmdate( 'Y-m-d H:i:s', strtotime( '-5 minutes' ) );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$this->events_table} WHERE created_at > %s",
				$last_5_minutes
			)
		);
	}

	/**
	 * Get top performing pages
	 *
	 * @param int $limit Number of pages.
	 * @return array Top pages
	 */
	private function get_top_pages( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					post_id,
					COUNT(DISTINCT session_id) as unique_visitors,
					COUNT(*) as total_events,
					AVG(CAST(event_value as FLOAT)) as avg_value
				FROM {$this->events_table}
				WHERE post_id IS NOT NULL
				GROUP BY post_id
				ORDER BY unique_visitors DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get top events
	 *
	 * @param int $limit Number of events.
	 * @return array Top events
	 */
	private function get_top_events( $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					event_type,
					event_label,
					COUNT(*) as count
				FROM {$this->events_table}
				GROUP BY event_type, event_label
				ORDER BY count DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get page analytics
	 *
	 * @param int $post_id Post ID.
	 * @return array Page analytics
	 */
	public function get_page_analytics( $post_id ) {
		global $wpdb;

		$unique_visitors = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$this->events_table} WHERE post_id = %d",
				$post_id
			)
		);

		$events = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->events_table} WHERE post_id = %d",
				$post_id
			)
		);

		$conversions = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->conversions_table} WHERE post_id = %d",
				$post_id
			)
		);

		$revenue = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(revenue) FROM {$this->conversions_table} WHERE post_id = %d",
				$post_id
			)
		) ?? 0;

		$scroll_stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT AVG(scroll_depth) as avg_scroll, MAX(scroll_depth) as max_scroll FROM {$this->heatmaps_table} WHERE post_id = %d",
				$post_id
			),
			ARRAY_A
		);

		return array(
			'post_id'         => $post_id,
			'unique_visitors' => $unique_visitors,
			'total_events'    => $events,
			'conversions'     => $conversions,
			'conversion_rate' => $unique_visitors > 0 ? round( ( $conversions / $unique_visitors ) * 100, 2 ) : 0,
			'revenue'         => $revenue,
			'avg_scroll_depth' => $scroll_stats['avg_scroll'] ?? 0,
			'max_scroll_depth' => $scroll_stats['max_scroll'] ?? 0,
		);
	}

	/**
	 * Get user journey for session
	 *
	 * @param string $session_id Session ID.
	 * @return array Journey events
	 */
	public function get_user_journey( $session_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					id,
					post_id,
					event_type,
					event_label,
					event_value,
					created_at
				FROM {$this->events_table}
				WHERE session_id = %s
				ORDER BY created_at ASC",
				$session_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get heatmap data for page
	 *
	 * @param int   $post_id Post ID.
	 * @param string $interaction_type Filter by interaction type.
	 * @return array Heatmap data
	 */
	public function get_heatmap_data( $post_id, $interaction_type = '' ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT click_x, click_y, interaction_type, COUNT(*) as count FROM {$this->heatmaps_table} WHERE post_id = %d",
			$post_id
		);

		if ( ! empty( $interaction_type ) ) {
			$query .= $wpdb->prepare( ' AND interaction_type = %s', $interaction_type );
		}

		$query .= ' GROUP BY click_x, click_y, interaction_type';

		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Get conversion funnel
	 *
	 * @param int $post_id Post ID.
	 * @return array Funnel data
	 */
	public function get_conversion_funnel( $post_id ) {
		global $wpdb;

		$visitors = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$this->events_table} WHERE post_id = %d",
				$post_id
			)
		);

		$engaged = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$this->events_table} WHERE post_id = %d AND event_type != 'page_view'",
				$post_id
			)
		);

		$converted = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$this->conversions_table} WHERE post_id = %d",
				$post_id
			)
		);

		return array(
			'visitors'              => $visitors,
			'engagement_rate'       => $visitors > 0 ? round( ( $engaged / $visitors ) * 100, 2 ) : 0,
			'engaged_users'         => $engaged,
			'conversions'           => $converted,
			'conversion_rate'       => $visitors > 0 ? round( ( $converted / $visitors ) * 100, 2 ) : 0,
			'engaged_to_converted'  => $engaged > 0 ? round( ( $converted / $engaged ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get traffic sources
	 *
	 * @param int   $post_id Post ID.
	 * @param array $date_range Date range array with start and end.
	 * @return array Traffic sources
	 */
	public function get_traffic_sources( $post_id, $date_range = array() ) {
		global $wpdb;

		$where = $wpdb->prepare( 'WHERE post_id = %d', $post_id );

		if ( ! empty( $date_range['start'] ) && ! empty( $date_range['end'] ) ) {
			$where .= $wpdb->prepare(
				' AND created_at BETWEEN %s AND %s',
				$date_range['start'],
				$date_range['end']
			);
		}

		return $wpdb->get_results(
			"SELECT
				COALESCE(utm_source, 'direct') as source,
				COALESCE(utm_medium, 'organic') as medium,
				COUNT(DISTINCT session_id) as visitors,
				COUNT(*) as events
			FROM {$this->events_table}
			{$where}
			GROUP BY source, medium
			ORDER BY visitors DESC",
			ARRAY_A
		);
	}

	/**
	 * Track revenue attribution
	 *
	 * @param int    $post_id Post ID.
	 * @param string $session_id Session ID.
	 * @param float  $revenue Revenue amount.
	 * @param string $currency Currency code.
	 */
	public function track_revenue( $post_id, $session_id, $revenue, $currency = 'USD' ) {
		return $this->track_conversion( $session_id, $post_id, 'revenue', $revenue, $currency );
	}

	/**
	 * Get revenue report
	 *
	 * @param int   $post_id Post ID.
	 * @param array $date_range Date range.
	 * @return array Revenue data
	 */
	public function get_revenue_report( $post_id, $date_range = array() ) {
		global $wpdb;

		$where = $wpdb->prepare( 'WHERE post_id = %d', $post_id );

		if ( ! empty( $date_range['start'] ) && ! empty( $date_range['end'] ) ) {
			$where .= $wpdb->prepare(
				' AND converted_at BETWEEN %s AND %s',
				$date_range['start'],
				$date_range['end']
			);
		}

		return $wpdb->get_row(
			"SELECT
				COUNT(*) as total_conversions,
				SUM(revenue) as total_revenue,
				AVG(revenue) as avg_order_value,
				MAX(revenue) as max_order_value,
				MIN(revenue) as min_order_value,
				COUNT(DISTINCT session_id) as unique_customers
			FROM {$this->conversions_table}
			{$where}",
			ARRAY_A
		);
	}

	/**
	 * Cleanup old tracking data
	 *
	 * @param int $days Days to retain.
	 */
	public function cleanup_old_data( $days = 90 ) {
		global $wpdb;

		$cutoff_date = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->events_table} WHERE created_at < %s",
				$cutoff_date
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->conversions_table} WHERE converted_at < %s",
				$cutoff_date
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->heatmaps_table} WHERE collected_at < %s",
				$cutoff_date
			)
		);

		pseo_log( "Cleaned up tracking data older than {$days} days", 'info' );
	}

	/**
	 * Get predictive analytics
	 *
	 * @param int $post_id Post ID.
	 * @return array Predictions
	 */
	public function get_predictive_analytics( $post_id ) {
		global $wpdb;

		// Get last 30 days of conversion data
		$daily_data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(converted_at) as date, COUNT(*) as conversions FROM {$this->conversions_table} WHERE post_id = %d AND converted_at > DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY DATE(converted_at)",
				$post_id
			),
			ARRAY_A
		);

		if ( count( $daily_data ) < 7 ) {
			return array( 'message' => 'Not enough data for predictions' );
		}

		$conversions = array_column( $daily_data, 'conversions' );
		$avg = array_sum( $conversions ) / count( $conversions );
		$trend = 0;

		if ( count( $conversions ) > 7 ) {
			$recent_avg = array_sum( array_slice( $conversions, -7 ) ) / 7;
			$old_avg = array_sum( array_slice( $conversions, 0, 7 ) ) / 7;
			$trend = ( $recent_avg - $old_avg ) / $old_avg;
		}

		return array(
			'predicted_conversions_7days' => round( $avg * 7 ),
			'predicted_conversions_30days' => round( $avg * 30 ),
			'trend'                       => round( $trend * 100, 2 ) . '%',
			'trend_direction'             => $trend > 0 ? 'up' : ( $trend < 0 ? 'down' : 'stable' ),
		);
	}
}
