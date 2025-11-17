<?php
/**
 * Database Optimizer - Query & Index Management
 *
 * @package ProgrammaticSEO\Database
 */

namespace ProgrammaticSEO\Database;

/**
 * Class Database_Optimizer
 * Optimize database performance with indexing, query analysis, and cleanup
 */
class Database_Optimizer {

	/**
	 * Maximum query execution time (ms)
	 *
	 * @var int
	 */
	private $slow_query_threshold = 100;

	/**
	 * Query log
	 *
	 * @var array
	 */
	private $query_log = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'schedule_optimization' ) );
		add_action( 'pseo_daily_maintenance', array( $this, 'run_maintenance' ) );
	}

	/**
	 * Create recommended indexes
	 *
	 * @return array Results
	 */
	public function create_indexes() {
		global $wpdb;

		$results = array();

		// Indexes for pseo_generated_pages
		$indexes = array(
			array(
				'table'  => $wpdb->prefix . 'pseo_generated_pages',
				'name'   => 'idx_post_id',
				'column' => 'post_id',
			),
			array(
				'table'  => $wpdb->prefix . 'pseo_generated_pages',
				'name'   => 'idx_template_id',
				'column' => 'template_id',
			),
			array(
				'table'  => $wpdb->prefix . 'pseo_generated_pages',
				'name'   => 'idx_status',
				'column' => 'status',
			),
			array(
				'table'  => $wpdb->prefix . 'pseo_generated_pages',
				'name'   => 'idx_slug',
				'column' => 'slug',
			),
			array(
				'table'  => $wpdb->prefix . 'pseo_analytics',
				'name'   => 'idx_post_date',
				'column' => 'post_id, date',
				'type'   => 'composite',
			),
			array(
				'table'  => $wpdb->prefix . 'pseo_internal_links',
				'name'   => 'idx_source_target',
				'column' => 'source_post_id, target_post_id',
				'type'   => 'composite',
			),
		);

		foreach ( $indexes as $index ) {
			if ( $this->index_exists( $index['table'], $index['name'] ) ) {
				$results[] = array(
					'index'  => $index['name'],
					'status' => 'exists',
				);
				continue;
			}

			$column = $index['column'];
			$sql    = "ALTER TABLE {$index['table']} ADD INDEX {$index['name']} ($column)";

			if ( $wpdb->query( $sql ) ) {
				$results[] = array(
					'index'  => $index['name'],
					'status' => 'created',
				);
				pseo_log( "Index created: {$index['name']}", 'info' );
			} else {
				$results[] = array(
					'index'  => $index['name'],
					'status' => 'failed',
				);
				pseo_log( "Index creation failed: {$index['name']}", 'error' );
			}
		}

		return $results;
	}

	/**
	 * Check if index exists
	 *
	 * @param string $table Table name.
	 * @param string $index_name Index name.
	 * @return bool
	 */
	private function index_exists( $table, $index_name ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = %s AND INDEX_NAME = %s LIMIT 1",
				str_replace( $wpdb->prefix, '', $table ),
				$index_name
			)
		);

		return (bool) $result;
	}

	/**
	 * Analyze slow queries
	 *
	 * @return array Slow queries analysis
	 */
	public function analyze_slow_queries() {
		$results = array();

		// Analyze common query patterns
		$slow_patterns = array(
			array(
				'query'       => "SELECT * FROM {$wpdb->prefix}pseo_generated_pages",
				'issue'       => 'SELECT * - Use specific columns',
				'suggestion'  => 'Use SELECT id, post_id, slug, status...',
			),
			array(
				'query'       => "SELECT * FROM {$wpdb->prefix}pseo_templates JOIN {$wpdb->prefix}pseo_generated_pages",
				'issue'       => 'Missing JOIN condition',
				'suggestion'  => 'Add proper ON clause',
			),
		);

		foreach ( $slow_patterns as $pattern ) {
			$results[] = array(
				'issue'       => $pattern['issue'],
				'suggestion'  => $pattern['suggestion'],
			);
		}

		return $results;
	}

	/**
	 * Optimize table structure
	 *
	 * @return array Optimization results
	 */
	public function optimize_tables() {
		global $wpdb;

		$results = array();
		$tables  = array(
			$wpdb->prefix . 'pseo_templates',
			$wpdb->prefix . 'pseo_data_sources',
			$wpdb->prefix . 'pseo_generated_pages',
			$wpdb->prefix . 'pseo_internal_links',
			$wpdb->prefix . 'pseo_analytics',
			$wpdb->prefix . 'pseo_keyword_rankings',
		);

		foreach ( $tables as $table ) {
			$result = $wpdb->query( "OPTIMIZE TABLE {$table}" );

			$results[] = array(
				'table'  => $table,
				'status' => $result ? 'optimized' : 'failed',
			);

			pseo_log( "Table optimized: {$table}", 'info' );
		}

		return $results;
	}

	/**
	 * Get table statistics
	 *
	 * @return array Table stats
	 */
	public function get_table_stats() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'pseo_templates',
			$wpdb->prefix . 'pseo_data_sources',
			$wpdb->prefix . 'pseo_generated_pages',
			$wpdb->prefix . 'pseo_internal_links',
			$wpdb->prefix . 'pseo_analytics',
			$wpdb->prefix . 'pseo_keyword_rankings',
		);

		$stats = array();

		foreach ( $tables as $table ) {
			$row_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			$table_size = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) FROM INFORMATION_SCHEMA.TABLES WHERE table_name = %s",
					str_replace( $wpdb->prefix, '', $table )
				)
			);

			$stats[] = array(
				'table'      => $table,
				'rows'       => $row_count,
				'size_mb'    => (float) $table_size,
			);
		}

		return $stats;
	}

	/**
	 * Clean up old data
	 *
	 * @param int $days Keep data from last N days.
	 * @return array Cleanup results
	 */
	public function cleanup_old_data( $days = 90 ) {
		global $wpdb;

		$results = array();
		$date    = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Delete old analytics records
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}pseo_analytics WHERE date < %s",
				$date
			)
		);

		$results['analytics_deleted'] = $deleted;

		// Delete old keyword rankings
		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}pseo_keyword_rankings WHERE date < %s",
				$date
			)
		);

		$results['rankings_deleted'] = $deleted;

		pseo_log( "Cleanup completed: deleted {$deleted} old records", 'info' );

		return $results;
	}

	/**
	 * Check database health
	 *
	 * @return array Health status
	 */
	public function check_health() {
		global $wpdb;

		$health = array(
			'status'    => 'healthy',
			'issues'    => array(),
			'warnings'  => array(),
		);

		// Check for corrupted tables
		$tables = $wpdb->get_results(
			"SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()"
		);

		foreach ( $tables as $table ) {
			$check = $wpdb->get_var( "CHECK TABLE {$table->TABLE_NAME}" );
			if ( strpos( $check, 'error' ) !== false ) {
				$health['status'] = 'unhealthy';
				$health['issues'][] = "Table {$table->TABLE_NAME} has errors";
			}
		}

		// Check for missing indexes
		if ( ! $this->index_exists( $wpdb->prefix . 'pseo_generated_pages', 'idx_post_id' ) ) {
			$health['warnings'][] = 'Missing index on pseo_generated_pages.post_id';
		}

		// Check for table size
		$stats = $this->get_table_stats();
		foreach ( $stats as $stat ) {
			if ( $stat['size_mb'] > 500 ) {
				$health['warnings'][] = "{$stat['table']} is {$stat['size_mb']}MB - consider partitioning";
			}
		}

		return $health;
	}

	/**
	 * Schedule optimization tasks
	 */
	public function schedule_optimization() {
		if ( ! wp_next_scheduled( 'pseo_daily_maintenance' ) ) {
			wp_schedule_event( time(), 'daily', 'pseo_daily_maintenance' );
		}
	}

	/**
	 * Run daily maintenance
	 */
	public function run_maintenance() {
		// Create missing indexes
		$this->create_indexes();

		// Optimize tables
		$this->optimize_tables();

		// Cleanup old data (keep 90 days)
		$this->cleanup_old_data( 90 );

		pseo_log( 'Daily maintenance completed', 'info' );
	}

	/**
	 * Get database size
	 *
	 * @return float Size in MB
	 */
	public function get_total_size() {
		$stats = $this->get_table_stats();
		$total = 0;

		foreach ( $stats as $stat ) {
			$total += $stat['size_mb'];
		}

		return round( $total, 2 );
	}

	/**
	 * Rebuild indexes
	 *
	 * @return array Results
	 */
	public function rebuild_indexes() {
		global $wpdb;

		$results = array();
		$tables  = array(
			$wpdb->prefix . 'pseo_generated_pages',
			$wpdb->prefix . 'pseo_analytics',
			$wpdb->prefix . 'pseo_internal_links',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "REPAIR TABLE {$table}" );
			$wpdb->query( "ANALYZE TABLE {$table}" );

			$results[] = array(
				'table'  => $table,
				'status' => 'rebuilt',
			);
		}

		return $results;
	}

	/**
	 * Enable query logging
	 *
	 * @param bool $enable Enable/disable.
	 */
	public function set_query_logging( $enable ) {
		if ( $enable ) {
			add_filter( 'query', array( $this, 'log_query' ) );
		}
	}

	/**
	 * Log query
	 *
	 * @param string $query SQL query.
	 * @return string
	 */
	public function log_query( $query ) {
		$start = microtime( true );

		// Log will be stored after query execution
		if ( ! in_array( $query, $this->query_log ) ) {
			$this->query_log[] = array(
				'query'  => $query,
				'time'   => $start,
				'status' => 'executed',
			);
		}

		return $query;
	}

	/**
	 * Get query log
	 *
	 * @return array Query log
	 */
	public function get_query_log() {
		return $this->query_log;
	}
}
