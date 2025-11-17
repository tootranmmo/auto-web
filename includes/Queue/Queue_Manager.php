<?php
/**
 * Queue Manager - Batch Processing & Async Jobs
 *
 * @package ProgrammaticSEO\Queue
 */

namespace ProgrammaticSEO\Queue;

/**
 * Class Queue_Manager
 * Handle async job processing with priorities and retry logic
 */
class Queue_Manager {

	/**
	 * Queue database table
	 *
	 * @var string
	 */
	private $table_name;

	/**
	 * Job timeout (seconds)
	 *
	 * @var int
	 */
	private $job_timeout = 300;

	/**
	 * Max retries
	 *
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'pseo_queue';

		// Create queue table if not exists
		$this->create_queue_table();

		// Setup cron for queue processing
		add_action( 'pseo_process_queue', array( $this, 'process_queue' ) );
		add_action( 'admin_init', array( $this, 'schedule_queue_processing' ) );
	}

	/**
	 * Create queue table
	 */
	private function create_queue_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			job_id VARCHAR(255) NOT NULL UNIQUE,
			job_type VARCHAR(100) NOT NULL,
			payload JSON,
			priority INT DEFAULT 50,
			attempts INT DEFAULT 0,
			max_attempts INT DEFAULT 3,
			status VARCHAR(50) DEFAULT 'pending',
			started_at DATETIME,
			completed_at DATETIME,
			error_message LONGTEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY job_id (job_id),
			KEY status (status),
			KEY priority (priority),
			KEY job_type (job_type)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Queue a job
	 *
	 * @param string $job_type Job type identifier.
	 * @param array  $payload Job data.
	 * @param int    $priority Priority (1-100, higher = more important).
	 * @return string Job ID
	 */
	public function enqueue( $job_type, $payload = array(), $priority = 50 ) {
		global $wpdb;

		$job_id = uniqid( 'job_' );

		$wpdb->insert(
			$this->table_name,
			array(
				'job_id'     => $job_id,
				'job_type'   => $job_type,
				'payload'    => wp_json_encode( $payload ),
				'priority'   => $priority,
				'max_attempts' => $this->max_retries,
			),
			array( '%s', '%s', '%s', '%d', '%d' )
		);

		pseo_log( "Job queued: {$job_id} (type: {$job_type})", 'info' );

		return $job_id;
	}

	/**
	 * Enqueue bulk job (batch processing)
	 *
	 * @param string $job_type Job type.
	 * @param array  $items Items to process.
	 * @param int    $batch_size Batch size.
	 * @return array Job IDs
	 */
	public function enqueue_batch( $job_type, $items, $batch_size = 100 ) {
		$job_ids = array();
		$batches = array_chunk( $items, $batch_size );

		foreach ( $batches as $batch ) {
			$job_id = $this->enqueue(
				$job_type,
				array( 'items' => $batch ),
				75 // Higher priority for batch jobs
			);
			$job_ids[] = $job_id;
		}

		pseo_log( "Batch queued: " . count( $job_ids ) . " jobs with {$batch_size} items each", 'info' );

		return $job_ids;
	}

	/**
	 * Process queue
	 *
	 * @param int $limit Maximum jobs to process.
	 * @return int Jobs processed
	 */
	public function process_queue( $limit = 10 ) {
		global $wpdb;

		// Get pending jobs, ordered by priority
		$jobs = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}
				WHERE status = 'pending'
				ORDER BY priority DESC, created_at ASC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		$processed = 0;

		foreach ( $jobs as $job ) {
			if ( $this->process_job( $job ) ) {
				$processed++;
			}
		}

		return $processed;
	}

	/**
	 * Process single job
	 *
	 * @param array $job Job data.
	 * @return bool Success
	 */
	private function process_job( $job ) {
		global $wpdb;

		// Update status to processing
		$wpdb->update(
			$this->table_name,
			array(
				'status'     => 'processing',
				'started_at' => current_time( 'mysql' ),
			),
			array( 'id' => $job['id'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		try {
			$payload = json_decode( $job['payload'], true );

			// Execute job handler
			$result = $this->handle_job( $job['job_type'], $payload );

			if ( $result ) {
				// Job success
				$wpdb->update(
					$this->table_name,
					array(
						'status'       => 'completed',
						'completed_at' => current_time( 'mysql' ),
					),
					array( 'id' => $job['id'] ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				pseo_log( "Job completed: {$job['job_id']}", 'info' );
				return true;
			} else {
				// Job failed
				return $this->retry_job( $job );
			}
		} catch ( \Exception $e ) {
			// Error during execution
			return $this->handle_job_error( $job, $e );
		}
	}

	/**
	 * Handle job based on type
	 *
	 * @param string $job_type Job type.
	 * @param array  $payload Job data.
	 * @return bool Success
	 */
	private function handle_job( $job_type, $payload ) {
		switch ( $job_type ) {
			case 'generate_pages':
				return $this->handle_generate_pages( $payload );

			case 'generate_links':
				return $this->handle_generate_links( $payload );

			case 'update_analytics':
				return $this->handle_update_analytics( $payload );

			case 'sync_data_source':
				return $this->handle_sync_data_source( $payload );

			case 'optimize_content':
				return $this->handle_optimize_content( $payload );

			default:
				return apply_filters( 'pseo_handle_job_' . $job_type, false, $payload );
		}
	}

	/**
	 * Handle page generation job
	 *
	 * @param array $payload Payload.
	 * @return bool
	 */
	private function handle_generate_pages( $payload ) {
		$template_id    = $payload['template_id'] ?? 0;
		$data_source_id = $payload['data_source_id'] ?? 0;
		$items          = $payload['items'] ?? array();

		if ( ! $template_id || ! $data_source_id ) {
			return false;
		}

		$plugin = pseo_get_plugin();

		foreach ( $items as $item ) {
			try {
				$plugin->page_generator->generate_single_page( $template_id, $data_source_id, $item );
			} catch ( \Exception $e ) {
				pseo_log( "Error generating page: " . $e->getMessage(), 'error' );
			}
		}

		return true;
	}

	/**
	 * Handle link generation job
	 *
	 * @param array $payload Payload.
	 * @return bool
	 */
	private function handle_generate_links( $payload ) {
		$post_ids = $payload['post_ids'] ?? array();

		$plugin = pseo_get_plugin();

		foreach ( $post_ids as $post_id ) {
			try {
				$plugin->internal_link_manager->generate_links_for_post( $post_id );
			} catch ( \Exception $e ) {
				pseo_log( "Error generating links for post {$post_id}: " . $e->getMessage(), 'error' );
			}
		}

		return true;
	}

	/**
	 * Handle analytics update job
	 *
	 * @param array $payload Payload.
	 * @return bool
	 */
	private function handle_update_analytics( $payload ) {
		$post_id = $payload['post_id'] ?? 0;

		if ( ! $post_id ) {
			return false;
		}

		$plugin = pseo_get_plugin();
		$plugin->analytics_manager->log_event( $post_id, 'pageview' );

		return true;
	}

	/**
	 * Handle data source sync job
	 *
	 * @param array $payload Payload.
	 * @return bool
	 */
	private function handle_sync_data_source( $payload ) {
		$data_source_id = $payload['data_source_id'] ?? 0;

		if ( ! $data_source_id ) {
			return false;
		}

		$plugin = pseo_get_plugin();
		return $plugin->data_source_manager->sync_source( $data_source_id );
	}

	/**
	 * Handle content optimization job
	 *
	 * @param array $payload Payload.
	 * @return bool
	 */
	private function handle_optimize_content( $payload ) {
		$post_id = $payload['post_id'] ?? 0;

		if ( ! $post_id ) {
			return false;
		}

		// Placeholder for content optimization
		do_action( 'pseo_optimize_content', $post_id );

		return true;
	}

	/**
	 * Retry failed job
	 *
	 * @param array $job Job data.
	 * @return bool
	 */
	private function retry_job( $job ) {
		global $wpdb;

		$attempts = (int) $job['attempts'] + 1;

		if ( $attempts >= (int) $job['max_attempts'] ) {
			// Max retries reached
			$wpdb->update(
				$this->table_name,
				array( 'status' => 'failed' ),
				array( 'id' => $job['id'] ),
				array( '%s' ),
				array( '%d' )
			);

			pseo_log( "Job failed after {$attempts} attempts: {$job['job_id']}", 'error' );
			return false;
		}

		// Retry with exponential backoff
		$delay = pow( 2, $attempts ) * 60; // 2min, 4min, 8min...

		$wpdb->update(
			$this->table_name,
			array(
				'status'     => 'pending',
				'attempts'   => $attempts,
				'created_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
			),
			array( 'id' => $job['id'] ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		pseo_log( "Job retry scheduled: {$job['job_id']} (attempt {$attempts})", 'info' );
		return false;
	}

	/**
	 * Handle job error
	 *
	 * @param array      $job Job data.
	 * @param \Exception $exception Exception.
	 * @return bool
	 */
	private function handle_job_error( $job, $exception ) {
		global $wpdb;

		$error_message = $exception->getMessage();

		$wpdb->update(
			$this->table_name,
			array(
				'error_message' => $error_message,
			),
			array( 'id' => $job['id'] ),
			array( '%s' ),
			array( '%d' )
		);

		return $this->retry_job( $job );
	}

	/**
	 * Get job status
	 *
	 * @param string $job_id Job ID.
	 * @return array|null Job status
	 */
	public function get_status( $job_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name} WHERE job_id = %s",
				$job_id
			),
			ARRAY_A
		);
	}

	/**
	 * Get queue statistics
	 *
	 * @return array Stats
	 */
	public function get_stats() {
		global $wpdb;

		return array(
			'pending'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'pending'" ),
			'processing' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'processing'" ),
			'completed' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed'" ),
			'failed'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'failed'" ),
		);
	}

	/**
	 * Schedule queue processing
	 */
	public function schedule_queue_processing() {
		if ( ! wp_next_scheduled( 'pseo_process_queue' ) ) {
			wp_schedule_event( time(), 'every_minute', 'pseo_process_queue' );
		}
	}

	/**
	 * Clear completed jobs
	 *
	 * @param int $days Delete jobs older than N days.
	 * @return int Rows deleted
	 */
	public function cleanup_completed( $days = 7 ) {
		global $wpdb;

		$date = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name}
				WHERE status = 'completed'
				AND completed_at < %s",
				$date
			)
		);
	}
}
