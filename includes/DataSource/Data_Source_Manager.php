<?php
/**
 * Data Source Manager Class
 *
 * @package ProgrammaticSEO\DataSource
 */

namespace ProgrammaticSEO\DataSource;

use ProgrammaticSEO\Database\Database_Manager;

/**
 * Class Data_Source_Manager
 */
class Data_Source_Manager {

	/**
	 * Supported data source types
	 *
	 * @var array
	 */
	private $supported_types = array( 'csv', 'json', 'api', 'database' );

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_source_actions' ) );
	}

	/**
	 * Create data source
	 *
	 * @param array $data Data source data.
	 * @return int Data source ID
	 */
	public function create_source( $data ) {
		$data = $this->sanitize_source_data( $data );

		if ( ! in_array( $data['type'], $this->supported_types ) ) {
			return false;
		}

		return Database_Manager::insert_data_source( $data );
	}

	/**
	 * Get data source
	 *
	 * @param int $id Data source ID.
	 * @return array|null
	 */
	public function get_source( $id ) {
		return Database_Manager::get_data_source( $id );
	}

	/**
	 * Get all data sources
	 *
	 * @return array
	 */
	public function get_all_sources() {
		global $wpdb;
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pseo_data_sources ORDER BY created_at DESC", ARRAY_A );
	}

	/**
	 * Fetch data from source
	 *
	 * @param int $id Data source ID.
	 * @return array|false Data or false on error
	 */
	public function fetch_data( $id ) {
		$source = $this->get_source( $id );
		if ( ! $source ) {
			return false;
		}

		switch ( $source['type'] ) {
			case 'csv':
				return $this->fetch_csv_data( $source );
			case 'json':
				return $this->fetch_json_data( $source );
			case 'api':
				return $this->fetch_api_data( $source );
			case 'database':
				return $this->fetch_database_data( $source );
			default:
				return false;
		}
	}

	/**
	 * Fetch data from CSV file
	 *
	 * @param array $source Source configuration.
	 * @return array|false
	 */
	private function fetch_csv_data( $source ) {
		$config = $source['config'];

		if ( ! isset( $config['file_path'] ) ) {
			return false;
		}

		$file_path = $config['file_path'];
		if ( ! file_exists( $file_path ) ) {
			return false;
		}

		$data = array();
		if ( ( $handle = fopen( $file_path, 'r' ) ) !== false ) {
			$headers = fgetcsv( $handle );

			while ( ( $row = fgetcsv( $handle ) ) !== false ) {
				if ( count( $row ) === count( $headers ) ) {
					$data[] = array_combine( $headers, $row );
				}
			}

			fclose( $handle );
		}

		return $data;
	}

	/**
	 * Fetch data from JSON file or URL
	 *
	 * @param array $source Source configuration.
	 * @return array|false
	 */
	private function fetch_json_data( $source ) {
		$config = $source['config'];

		if ( ! isset( $config['source'] ) ) {
			return false;
		}

		$source_path = $config['source'];
		$content     = false;

		// Check if it's a URL or file path
		if ( filter_var( $source_path, FILTER_VALIDATE_URL ) ) {
			$response = wp_remote_get( $source_path, array( 'timeout' => 30 ) );
			if ( is_wp_error( $response ) ) {
				return false;
			}
			$content = wp_remote_retrieve_body( $response );
		} elseif ( file_exists( $source_path ) ) {
			$content = file_get_contents( $source_path );
		}

		if ( ! $content ) {
			return false;
		}

		$data = json_decode( $content, true );

		// Handle different JSON structures
		if ( isset( $config['data_path'] ) ) {
			$paths = explode( '.', $config['data_path'] );
			foreach ( $paths as $path ) {
				if ( isset( $data[ $path ] ) ) {
					$data = $data[ $path ];
				}
			}
		}

		return is_array( $data ) ? $data : false;
	}

	/**
	 * Fetch data from API
	 *
	 * @param array $source Source configuration.
	 * @return array|false
	 */
	private function fetch_api_data( $source ) {
		$config = $source['config'];

		if ( ! isset( $config['endpoint'] ) ) {
			return false;
		}

		$endpoint = $config['endpoint'];
		$headers  = isset( $config['headers'] ) ? $config['headers'] : array();
		$params   = isset( $config['params'] ) ? $config['params'] : array();

		$args = array(
			'timeout' => 30,
			'headers' => $headers,
		);

		if ( ! empty( $params ) ) {
			$endpoint = add_query_arg( $params, $endpoint );
		}

		$response = wp_remote_get( $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		// Handle data path
		if ( isset( $config['data_path'] ) ) {
			$paths = explode( '.', $config['data_path'] );
			foreach ( $paths as $path ) {
				if ( isset( $data[ $path ] ) ) {
					$data = $data[ $path ];
				}
			}
		}

		return is_array( $data ) ? $data : false;
	}

	/**
	 * Fetch data from database
	 *
	 * @param array $source Source configuration.
	 * @return array|false
	 */
	private function fetch_database_data( $source ) {
		global $wpdb;

		$config = $source['config'];

		if ( ! isset( $config['query'] ) ) {
			return false;
		}

		$results = $wpdb->get_results( $config['query'], ARRAY_A );

		return is_array( $results ) ? $results : false;
	}

	/**
	 * Sanitize source data
	 *
	 * @param array $data Data source data.
	 * @return array Sanitized data
	 */
	private function sanitize_source_data( $data ) {
		return array(
			'name'   => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'type'   => isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : '',
			'config' => isset( $data['config'] ) ? $data['config'] : array(),
		);
	}

	/**
	 * Handle source actions from admin
	 */
	public function handle_source_actions() {
		if ( ! isset( $_GET['page'] ) || 'programmatic-seo-data-sources' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		switch ( $action ) {
			case 'sync':
				if ( isset( $_GET['id'] ) ) {
					$id = absint( $_GET['id'] );
					$this->sync_source( $id );
				}
				break;
			case 'delete':
				if ( isset( $_GET['id'] ) ) {
					$id = absint( $_GET['id'] );
					global $wpdb;
					$wpdb->delete( $wpdb->prefix . 'pseo_data_sources', array( 'id' => $id ), array( '%d' ) );
				}
				break;
		}
	}

	/**
	 * Sync data source
	 *
	 * @param int $id Data source ID.
	 * @return bool
	 */
	public function sync_source( $id ) {
		global $wpdb;

		$source = $this->get_source( $id );
		if ( ! $source ) {
			return false;
		}

		$wpdb->update(
			$wpdb->prefix . 'pseo_data_sources',
			array( 'sync_status' => 'syncing' ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		$data = $this->fetch_data( $id );

		if ( $data === false ) {
			$wpdb->update(
				$wpdb->prefix . 'pseo_data_sources',
				array( 'sync_status' => 'failed' ),
				array( 'id' => $id ),
				array( '%s' ),
				array( '%d' )
			);
			return false;
		}

		$wpdb->update(
			$wpdb->prefix . 'pseo_data_sources',
			array(
				'sync_status' => 'synced',
				'last_sync'   => current_time( 'mysql' ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return true;
	}
}
