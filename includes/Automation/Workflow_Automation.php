<?php
/**
 * Workflow Automation Engine
 *
 * @package ProgrammaticSEO\Automation
 */

namespace ProgrammaticSEO\Automation;

/**
 * Class Workflow_Automation
 * Rule-based automation with webhooks and conditional logic
 */
class Workflow_Automation {

	/**
	 * Workflows table
	 *
	 * @var string
	 */
	private $workflows_table;

	/**
	 * Workflow executions table
	 *
	 * @var string
	 */
	private $executions_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->workflows_table = $wpdb->prefix . 'pseo_workflows';
		$this->executions_table = $wpdb->prefix . 'pseo_workflow_executions';

		$this->create_tables();
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'pseo_workflow_trigger', array( $this, 'execute_triggered_workflows' ) );
	}

	/**
	 * Create necessary database tables
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Workflows table
		$sql_workflows = "CREATE TABLE IF NOT EXISTS {$this->workflows_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			enabled BOOLEAN DEFAULT TRUE,
			trigger_type VARCHAR(100) NOT NULL,
			trigger_conditions JSON,
			actions JSON NOT NULL,
			webhook_url VARCHAR(500),
			webhook_headers JSON,
			retry_attempts INT DEFAULT 3,
			timeout INT DEFAULT 30,
			is_public BOOLEAN DEFAULT FALSE,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX trigger_idx (trigger_type),
			INDEX enabled_idx (enabled)
		) $charset_collate;";

		// Workflow executions table
		$sql_executions = "CREATE TABLE IF NOT EXISTS {$this->executions_table} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			workflow_id BIGINT(20) NOT NULL,
			status VARCHAR(50) NOT NULL,
			trigger_data JSON,
			execution_log TEXT,
			executed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			completed_at DATETIME,
			PRIMARY KEY (id),
			INDEX workflow_idx (workflow_id),
			INDEX status_idx (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_workflows );
		dbDelta( $sql_executions );
	}

	/**
	 * Create workflow
	 *
	 * @param array $workflow_data Workflow data.
	 * @return int|false Workflow ID or false
	 */
	public function create_workflow( $workflow_data ) {
		global $wpdb;

		$result = $wpdb->insert(
			$this->workflows_table,
			array(
				'name'               => sanitize_text_field( $workflow_data['name'] ?? '' ),
				'description'        => sanitize_textarea_field( $workflow_data['description'] ?? '' ),
				'trigger_type'       => sanitize_text_field( $workflow_data['trigger_type'] ?? '' ),
				'trigger_conditions' => wp_json_encode( $workflow_data['conditions'] ?? array() ),
				'actions'            => wp_json_encode( $workflow_data['actions'] ?? array() ),
				'webhook_url'        => isset( $workflow_data['webhook_url'] ) ? esc_url_raw( $workflow_data['webhook_url'] ) : '',
				'webhook_headers'    => wp_json_encode( $workflow_data['headers'] ?? array() ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			pseo_log( 'Workflow created: ' . $workflow_data['name'], 'info' );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get all workflows
	 *
	 * @return array Workflows
	 */
	public function get_workflows() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$this->workflows_table} WHERE enabled = TRUE ORDER BY created_at DESC",
			ARRAY_A
		);
	}

	/**
	 * Get workflow by ID
	 *
	 * @param int $workflow_id Workflow ID.
	 * @return array|null Workflow data
	 */
	public function get_workflow( $workflow_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->workflows_table} WHERE id = %d",
				$workflow_id
			),
			ARRAY_A
		);
	}

	/**
	 * Update workflow
	 *
	 * @param int   $workflow_id Workflow ID.
	 * @param array $workflow_data New workflow data.
	 * @return bool Success
	 */
	public function update_workflow( $workflow_id, $workflow_data ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->workflows_table,
			array(
				'name'               => sanitize_text_field( $workflow_data['name'] ?? '' ),
				'description'        => sanitize_textarea_field( $workflow_data['description'] ?? '' ),
				'trigger_type'       => sanitize_text_field( $workflow_data['trigger_type'] ?? '' ),
				'trigger_conditions' => wp_json_encode( $workflow_data['conditions'] ?? array() ),
				'actions'            => wp_json_encode( $workflow_data['actions'] ?? array() ),
				'webhook_url'        => isset( $workflow_data['webhook_url'] ) ? esc_url_raw( $workflow_data['webhook_url'] ) : '',
				'webhook_headers'    => wp_json_encode( $workflow_data['headers'] ?? array() ),
			),
			array( 'id' => $workflow_id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			pseo_log( 'Workflow updated: ID ' . $workflow_id, 'info' );
			return true;
		}

		return false;
	}

	/**
	 * Delete workflow
	 *
	 * @param int $workflow_id Workflow ID.
	 * @return bool Success
	 */
	public function delete_workflow( $workflow_id ) {
		global $wpdb;

		return (bool) $wpdb->delete(
			$this->workflows_table,
			array( 'id' => $workflow_id ),
			array( '%d' )
		);
	}

	/**
	 * Register workflow trigger
	 *
	 * @param string $trigger_type Trigger type.
	 * @param array  $trigger_data Trigger data.
	 */
	public function register_trigger( $trigger_type, $trigger_data ) {
		do_action( 'pseo_workflow_trigger', $trigger_type, $trigger_data );
	}

	/**
	 * Execute triggered workflows
	 *
	 * @param string $trigger_type Trigger type.
	 * @param array  $trigger_data Trigger data.
	 */
	public function execute_triggered_workflows( $trigger_type, $trigger_data ) {
		global $wpdb;

		$workflows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->workflows_table} WHERE trigger_type = %s AND enabled = TRUE",
				$trigger_type
			),
			ARRAY_A
		);

		foreach ( $workflows as $workflow ) {
			// Check conditions
			$conditions = json_decode( $workflow['trigger_conditions'], true ) ?? array();

			if ( ! $this->evaluate_conditions( $conditions, $trigger_data ) ) {
				continue;
			}

			// Execute workflow
			$this->execute_workflow( $workflow, $trigger_data );
		}
	}

	/**
	 * Evaluate workflow conditions
	 *
	 * @param array $conditions Conditions array.
	 * @param array $trigger_data Trigger data.
	 * @return bool Evaluation result
	 */
	private function evaluate_conditions( $conditions, $trigger_data ) {
		if ( empty( $conditions ) ) {
			return true;
		}

		$logic = $conditions['logic'] ?? 'AND';
		$rules = $conditions['rules'] ?? array();

		$results = array();

		foreach ( $rules as $rule ) {
			$field = $rule['field'] ?? '';
			$operator = $rule['operator'] ?? '=';
			$value = $rule['value'] ?? '';
			$data_value = $trigger_data[ $field ] ?? '';

			switch ( $operator ) {
				case '=':
					$results[] = $data_value === $value;
					break;
				case '!=':
					$results[] = $data_value !== $value;
					break;
				case '>':
					$results[] = (float) $data_value > (float) $value;
					break;
				case '<':
					$results[] = (float) $data_value < (float) $value;
					break;
				case '>=':
					$results[] = (float) $data_value >= (float) $value;
					break;
				case '<=':
					$results[] = (float) $data_value <= (float) $value;
					break;
				case 'contains':
					$results[] = strpos( $data_value, $value ) !== false;
					break;
				case 'not_contains':
					$results[] = strpos( $data_value, $value ) === false;
					break;
				default:
					$results[] = true;
			}
		}

		if ( 'AND' === $logic ) {
			return ! in_array( false, $results, true );
		} else {
			return in_array( true, $results, true );
		}
	}

	/**
	 * Execute workflow actions
	 *
	 * @param array $workflow Workflow data.
	 * @param array $trigger_data Trigger data.
	 */
	private function execute_workflow( $workflow, $trigger_data ) {
		global $wpdb;

		$execution_id = $wpdb->insert(
			$this->executions_table,
			array(
				'workflow_id' => $workflow['id'],
				'status'      => 'pending',
				'trigger_data' => wp_json_encode( $trigger_data ),
			),
			array( '%d', '%s', '%s' )
		);

		if ( ! $execution_id ) {
			return;
		}

		$execution_id = $wpdb->insert_id;

		$actions = json_decode( $workflow['actions'], true ) ?? array();
		$logs = array();

		foreach ( $actions as $action ) {
			try {
				$result = $this->execute_action( $action, $trigger_data );
				$logs[] = $action['type'] . ': ' . ( $result ? 'success' : 'failed' );
			} catch ( Exception $e ) {
				$logs[] = $action['type'] . ': error - ' . $e->getMessage();
			}
		}

		// Send webhook if configured
		if ( ! empty( $workflow['webhook_url'] ) ) {
			$this->send_webhook( $workflow, $trigger_data, $logs );
		}

		// Update execution status
		$wpdb->update(
			$this->executions_table,
			array(
				'status'       => 'completed',
				'execution_log' => wp_json_encode( $logs ),
				'completed_at' => current_time( 'mysql' ),
			),
			array( 'id' => $execution_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		pseo_log( 'Workflow executed: ' . $workflow['name'], 'info' );
	}

	/**
	 * Execute single action
	 *
	 * @param array $action Action data.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function execute_action( $action, $trigger_data ) {
		$type = $action['type'] ?? '';
		$config = $action['config'] ?? array();

		switch ( $type ) {
			case 'send_email':
				return $this->action_send_email( $config, $trigger_data );

			case 'create_post':
				return $this->action_create_post( $config, $trigger_data );

			case 'update_post':
				return $this->action_update_post( $config, $trigger_data );

			case 'update_meta':
				return $this->action_update_meta( $config, $trigger_data );

			case 'send_webhook':
				return $this->action_send_webhook( $config, $trigger_data );

			case 'slack_notification':
				return $this->action_slack_notification( $config, $trigger_data );

			case 'log_event':
				return $this->action_log_event( $config, $trigger_data );

			default:
				return apply_filters( 'pseo_workflow_action_' . $type, false, $config, $trigger_data );
		}
	}

	/**
	 * Action: Send Email
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_send_email( $config, $trigger_data ) {
		$to = $this->interpolate_string( $config['to'] ?? '', $trigger_data );
		$subject = $this->interpolate_string( $config['subject'] ?? '', $trigger_data );
		$message = $this->interpolate_string( $config['message'] ?? '', $trigger_data );

		return wp_mail( $to, $subject, $message );
	}

	/**
	 * Action: Create Post
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_create_post( $config, $trigger_data ) {
		$post_data = array(
			'post_title'   => $this->interpolate_string( $config['title'] ?? '', $trigger_data ),
			'post_content' => $this->interpolate_string( $config['content'] ?? '', $trigger_data ),
			'post_status'  => $config['status'] ?? 'draft',
			'post_type'    => $config['post_type'] ?? 'post',
		);

		$post_id = wp_insert_post( $post_data );

		return ! is_wp_error( $post_id ) && $post_id > 0;
	}

	/**
	 * Action: Update Post
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_update_post( $config, $trigger_data ) {
		$post_id = isset( $trigger_data['post_id'] ) ? (int) $trigger_data['post_id'] : 0;

		if ( $post_id <= 0 ) {
			return false;
		}

		$post_data = array(
			'ID'           => $post_id,
			'post_content' => $this->interpolate_string( $config['content'] ?? '', $trigger_data ),
		);

		if ( isset( $config['status'] ) ) {
			$post_data['post_status'] = $config['status'];
		}

		$result = wp_update_post( $post_data );

		return ! is_wp_error( $result ) && $result > 0;
	}

	/**
	 * Action: Update Post Meta
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_update_meta( $config, $trigger_data ) {
		$post_id = isset( $trigger_data['post_id'] ) ? (int) $trigger_data['post_id'] : 0;
		$meta_key = $config['meta_key'] ?? '';
		$meta_value = $this->interpolate_string( $config['meta_value'] ?? '', $trigger_data );

		if ( $post_id <= 0 || empty( $meta_key ) ) {
			return false;
		}

		return (bool) update_post_meta( $post_id, $meta_key, $meta_value );
	}

	/**
	 * Action: Send Webhook
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_send_webhook( $config, $trigger_data ) {
		$url = $this->interpolate_string( $config['url'] ?? '', $trigger_data );
		$method = $config['method'] ?? 'POST';
		$headers = $config['headers'] ?? array();
		$body = $this->interpolate_string( $config['body'] ?? '', $trigger_data );

		if ( empty( $url ) ) {
			return false;
		}

		$response = wp_remote_request(
			$url,
			array(
				'method'  => strtoupper( $method ),
				'headers' => $headers,
				'body'    => $body,
				'timeout' => 30,
			)
		);

		return ! is_wp_error( $response );
	}

	/**
	 * Action: Slack Notification
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_slack_notification( $config, $trigger_data ) {
		$webhook_url = $config['webhook_url'] ?? '';
		$message = $this->interpolate_string( $config['message'] ?? '', $trigger_data );

		if ( empty( $webhook_url ) || empty( $message ) ) {
			return false;
		}

		$response = wp_remote_post(
			$webhook_url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( array( 'text' => $message ) ),
				'timeout' => 30,
			)
		);

		return ! is_wp_error( $response );
	}

	/**
	 * Action: Log Event
	 *
	 * @param array $config Action config.
	 * @param array $trigger_data Trigger data.
	 * @return bool Success
	 */
	private function action_log_event( $config, $trigger_data ) {
		$message = $this->interpolate_string( $config['message'] ?? '', $trigger_data );
		$level = $config['level'] ?? 'info';

		pseo_log( $message, $level );
		return true;
	}

	/**
	 * Send workflow webhook
	 *
	 * @param array $workflow Workflow data.
	 * @param array $trigger_data Trigger data.
	 * @param array $logs Execution logs.
	 */
	private function send_webhook( $workflow, $trigger_data, $logs ) {
		$url = $workflow['webhook_url'];
		$headers = json_decode( $workflow['webhook_headers'], true ) ?? array();

		$payload = array(
			'workflow_id'  => $workflow['id'],
			'workflow_name' => $workflow['name'],
			'trigger_type' => $workflow['trigger_type'],
			'trigger_data' => $trigger_data,
			'execution_logs' => $logs,
			'timestamp'    => current_time( 'c' ),
		);

		$headers['Content-Type'] = 'application/json';

		wp_remote_post(
			$url,
			array(
				'method'  => 'POST',
				'headers' => $headers,
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);
	}

	/**
	 * Interpolate variables in strings
	 *
	 * @param string $string String with {{variable}} placeholders.
	 * @param array  $data Data to interpolate.
	 * @return string Interpolated string
	 */
	private function interpolate_string( $string, $data ) {
		foreach ( $data as $key => $value ) {
			$string = str_replace( '{{' . $key . '}}', $value, $string );
		}

		return $string;
	}

	/**
	 * Get workflow execution history
	 *
	 * @param int $workflow_id Workflow ID.
	 * @param int $limit Number of records.
	 * @return array Executions
	 */
	public function get_execution_history( $workflow_id, $limit = 50 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->executions_table} WHERE workflow_id = %d ORDER BY executed_at DESC LIMIT %d",
				$workflow_id,
				$limit
			),
			ARRAY_A
		);
	}

	/**
	 * Get workflow statistics
	 *
	 * @param int $workflow_id Workflow ID.
	 * @return array Statistics
	 */
	public function get_statistics( $workflow_id ) {
		global $wpdb;

		$total = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->executions_table} WHERE workflow_id = %d",
				$workflow_id
			)
		);

		$completed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->executions_table} WHERE workflow_id = %d AND status = 'completed'",
				$workflow_id
			)
		);

		$failed = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->executions_table} WHERE workflow_id = %d AND status = 'failed'",
				$workflow_id
			)
		);

		return array(
			'total_executions' => $total,
			'successful'       => $completed,
			'failed'           => $failed,
			'success_rate'     => $total > 0 ? round( ( $completed / $total ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Prepare trigger for common workflows
	 *
	 * @param string $trigger_name Trigger name.
	 * @param array  $params Trigger parameters.
	 */
	public static function trigger( $trigger_name, $params = array() ) {
		do_action( 'pseo_workflow_' . $trigger_name, $params );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting( 'programmatic_seo_settings', 'pseo_workflow_settings' );
	}
}
