<?php
/**
 * System Monitor - Performance & Health Tracking
 *
 * @package ProgrammaticSEO\Monitor
 */

namespace ProgrammaticSEO\Monitor;

/**
 * Class System_Monitor
 * Monitor system health, performance, and send alerts
 */
class System_Monitor {

	/**
	 * Alert threshold levels
	 *
	 * @var array
	 */
	private $thresholds = array(
		'cpu_usage'      => 80,        // %
		'memory_usage'   => 85,        // %
		'error_rate'     => 5,         // %
		'response_time'  => 1000,      // ms
		'database_size'  => 500,       // MB
	);

	/**
	 * Metrics storage table
	 *
	 * @var string
	 */
	private $metrics_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->metrics_table = $wpdb->prefix . 'pseo_metrics';

		// Create metrics table
		$this->create_metrics_table();

		// Schedule monitoring
		add_action( 'admin_init', array( $this, 'schedule_monitoring' ) );
		add_action( 'pseo_collect_metrics', array( $this, 'collect_metrics' ) );
	}

	/**
	 * Create metrics table
	 */
	private function create_metrics_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->metrics_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			metric_type VARCHAR(100) NOT NULL,
			metric_name VARCHAR(255) NOT NULL,
			metric_value FLOAT,
			unit VARCHAR(50),
			status VARCHAR(50),
			metadata JSON,
			recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY metric_type (metric_type),
			KEY recorded_at (recorded_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Collect system metrics
	 */
	public function collect_metrics() {
		$metrics = array(
			'server_metrics' => $this->get_server_metrics(),
			'performance'    => $this->get_performance_metrics(),
			'database'       => $this->get_database_metrics(),
			'wordpress'      => $this->get_wordpress_metrics(),
		);

		// Store metrics
		foreach ( $metrics as $type => $data ) {
			foreach ( $data as $name => $value ) {
				$this->store_metric( $type, $name, $value );
			}
		}

		// Check thresholds and send alerts
		$this->check_thresholds( $metrics );

		pseo_log( 'Metrics collected', 'info' );
	}

	/**
	 * Get server metrics
	 *
	 * @return array Server metrics
	 */
	private function get_server_metrics() {
		$metrics = array();

		// CPU usage
		if ( function_exists( 'sys_getloadavg' ) ) {
			$load_avg = sys_getloadavg();
			$cpu_cores = (int) shell_exec( 'nproc' );
			$metrics['cpu_usage'] = round( ( $load_avg[0] / $cpu_cores ) * 100, 2 );
		}

		// Memory usage
		$memory_limit = (int) wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		$memory_usage = memory_get_usage( true );
		$metrics['memory_usage'] = round( ( $memory_usage / $memory_limit ) * 100, 2 );
		$metrics['memory_used_mb'] = round( $memory_usage / 1024 / 1024, 2 );

		// Disk space
		if ( function_exists( 'disk_free_space' ) ) {
			$free_space = disk_free_space( ABSPATH );
			$total_space = disk_total_space( ABSPATH );
			$metrics['disk_usage'] = round( ( ( $total_space - $free_space ) / $total_space ) * 100, 2 );
			$metrics['disk_free_gb'] = round( $free_space / 1024 / 1024 / 1024, 2 );
		}

		// Uptime
		if ( function_exists( 'shell_exec' ) ) {
			$uptime = @shell_exec( 'uptime -p' );
			$metrics['uptime'] = trim( $uptime );
		}

		return $metrics;
	}

	/**
	 * Get performance metrics
	 *
	 * @return array Performance metrics
	 */
	private function get_performance_metrics() {
		$metrics = array();

		// Page generation time
		$start_time = microtime( true );
		// Simulate a typical operation
		for ( $i = 0; $i < 100; $i++ ) {
			$test = 1 + 1;
		}
		$metrics['operation_time_ms'] = round( ( microtime( true ) - $start_time ) * 1000, 2 );

		// Cache hit rate (if Cache Manager exists)
		if ( function_exists( 'pseo_get_plugin' ) ) {
			$plugin = pseo_get_plugin();
			if ( isset( $plugin->cache_manager ) ) {
				$stats = $plugin->cache_manager->get_stats();
				$metrics['cache_hit_rate'] = $stats['hit_rate'] ?? 0;
			}
		}

		// Error rate
		$errors = $this->get_error_count( 1 ); // Last hour
		$total_requests = $this->get_request_count( 1 );
		$metrics['error_rate'] = $total_requests > 0 ? round( ( $errors / $total_requests ) * 100, 2 ) : 0;

		return $metrics;
	}

	/**
	 * Get database metrics
	 *
	 * @return array Database metrics
	 */
	private function get_database_metrics() {
		global $wpdb;

		$metrics = array();

		// Total database size
		$result = $wpdb->get_var(
			"SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2)
			FROM INFORMATION_SCHEMA.TABLES
			WHERE table_schema = DATABASE()"
		);

		$metrics['database_size_mb'] = (float) $result;

		// Query count
		$metrics['query_count'] = (int) $wpdb->num_queries;

		// Slow queries
		$slow_queries = $wpdb->get_var(
			"SELECT COUNT(*) FROM INFORMATION_SCHEMA.PROCESSLIST
			WHERE TIME > 5 AND COMMAND != 'Sleep'"
		);

		$metrics['slow_queries'] = (int) $slow_queries;

		// Connection count
		$connections = $wpdb->get_var( "SHOW STATUS LIKE 'Threads_connected'" );
		$metrics['active_connections'] = (int) $connections;

		return $metrics;
	}

	/**
	 * Get WordPress metrics
	 *
	 * @return array WordPress metrics
	 */
	private function get_wordpress_metrics() {
		$metrics = array();

		// Total posts
		$metrics['total_posts'] = (int) wp_count_posts()->publish;

		// Active plugins
		$active_plugins = get_option( 'active_plugins' );
		$metrics['active_plugins'] = is_array( $active_plugins ) ? count( $active_plugins ) : 0;

		// Users online
		$metrics['users_online'] = count( get_users( array( 'fields' => 'ID' ) ) );

		// Plugin stats
		if ( function_exists( 'pseo_get_generated_pages_count' ) ) {
			$metrics['generated_pages'] = pseo_get_generated_pages_count();
		}

		return $metrics;
	}

	/**
	 * Store metric
	 *
	 * @param string $type Metric type.
	 * @param string $name Metric name.
	 * @param mixed  $value Metric value.
	 */
	private function store_metric( $type, $name, $value ) {
		global $wpdb;

		$wpdb->insert(
			$this->metrics_table,
			array(
				'metric_type'  => $type,
				'metric_name'  => $name,
				'metric_value' => is_numeric( $value ) ? $value : 0,
				'status'       => 'recorded',
			),
			array( '%s', '%s', '%f', '%s' )
		);
	}

	/**
	 * Check thresholds and send alerts
	 *
	 * @param array $metrics Collected metrics.
	 */
	private function check_thresholds( $metrics ) {
		$alerts = array();

		// Check CPU usage
		if ( isset( $metrics['server_metrics']['cpu_usage'] ) &&
			$metrics['server_metrics']['cpu_usage'] > $this->thresholds['cpu_usage'] ) {
			$alerts[] = array(
				'type'    => 'cpu_high',
				'message' => 'CPU usage is high: ' . $metrics['server_metrics']['cpu_usage'] . '%',
			);
		}

		// Check memory usage
		if ( isset( $metrics['server_metrics']['memory_usage'] ) &&
			$metrics['server_metrics']['memory_usage'] > $this->thresholds['memory_usage'] ) {
			$alerts[] = array(
				'type'    => 'memory_high',
				'message' => 'Memory usage is high: ' . $metrics['server_metrics']['memory_usage'] . '%',
			);
		}

		// Check error rate
		if ( isset( $metrics['performance']['error_rate'] ) &&
			$metrics['performance']['error_rate'] > $this->thresholds['error_rate'] ) {
			$alerts[] = array(
				'type'    => 'error_rate_high',
				'message' => 'Error rate is high: ' . $metrics['performance']['error_rate'] . '%',
			);
		}

		// Check database size
		if ( isset( $metrics['database']['database_size_mb'] ) &&
			$metrics['database']['database_size_mb'] > $this->thresholds['database_size'] ) {
			$alerts[] = array(
				'type'    => 'database_large',
				'message' => 'Database size is large: ' . $metrics['database']['database_size_mb'] . ' MB',
			);
		}

		// Send alerts
		foreach ( $alerts as $alert ) {
			$this->send_alert( $alert );
		}
	}

	/**
	 * Send alert
	 *
	 * @param array $alert Alert data.
	 */
	private function send_alert( $alert ) {
		global $wpdb;

		// Store alert
		$wpdb->insert(
			$wpdb->prefix . 'pseo_alerts',
			array(
				'alert_type' => $alert['type'],
				'message'    => $alert['message'],
				'severity'   => 'warning',
			)
		);

		// Send email notification
		$admin_email = get_option( 'admin_email' );
		wp_mail(
			$admin_email,
			'Programmatic SEO Alert: ' . $alert['type'],
			$alert['message'],
			array( 'Content-Type: text/html; charset=UTF-8' )
		);

		pseo_log( 'Alert sent: ' . $alert['type'], 'warning' );
	}

	/**
	 * Get error count
	 *
	 * @param int $hours Hours to check.
	 * @return int Error count
	 */
	private function get_error_count( $hours = 1 ) {
		global $wpdb;

		$date = gmdate( 'Y-m-d H:i:s', time() - ( $hours * 3600 ) );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}pseo_metrics
				WHERE metric_type = 'error'
				AND recorded_at > %s",
				$date
			)
		);
	}

	/**
	 * Get request count
	 *
	 * @param int $hours Hours to check.
	 * @return int Request count
	 */
	private function get_request_count( $hours = 1 ) {
		global $wpdb;

		$date = gmdate( 'Y-m-d H:i:s', time() - ( $hours * 3600 ) );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}pseo_metrics
				WHERE recorded_at > %s",
				$date
			)
		);
	}

	/**
	 * Get health status
	 *
	 * @return array Health status
	 */
	public function get_health_status() {
		$metrics = array(
			'server_metrics' => $this->get_server_metrics(),
			'performance'    => $this->get_performance_metrics(),
			'database'       => $this->get_database_metrics(),
		);

		$health = array(
			'status'  => 'healthy',
			'issues'  => array(),
			'warnings' => array(),
		);

		// Check thresholds
		if ( isset( $metrics['server_metrics']['cpu_usage'] ) &&
			$metrics['server_metrics']['cpu_usage'] > 80 ) {
			$health['warnings'][] = 'High CPU usage';
		}

		if ( isset( $metrics['server_metrics']['memory_usage'] ) &&
			$metrics['server_metrics']['memory_usage'] > 85 ) {
			$health['status'] = 'warning';
			$health['warnings'][] = 'High memory usage';
		}

		if ( isset( $metrics['database']['slow_queries'] ) &&
			$metrics['database']['slow_queries'] > 5 ) {
			$health['warnings'][] = 'Many slow queries detected';
		}

		return $health;
	}

	/**
	 * Schedule monitoring
	 */
	public function schedule_monitoring() {
		if ( ! wp_next_scheduled( 'pseo_collect_metrics' ) ) {
			wp_schedule_event( time(), 'every_five_minutes', 'pseo_collect_metrics' );
		}
	}

	/**
	 * Get metrics history
	 *
	 * @param string $metric_type Metric type.
	 * @param int    $hours Hours to retrieve.
	 * @return array Metrics
	 */
	public function get_metrics_history( $metric_type, $hours = 24 ) {
		global $wpdb;

		$date = gmdate( 'Y-m-d H:i:s', time() - ( $hours * 3600 ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->metrics_table}
				WHERE metric_type = %s
				AND recorded_at > %s
				ORDER BY recorded_at DESC",
				$metric_type,
				$date
			),
			ARRAY_A
		);
	}
}
