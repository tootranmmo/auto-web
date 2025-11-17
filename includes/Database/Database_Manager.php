<?php
/**
 * Database Manager Class
 *
 * @package ProgrammaticSEO\Database
 */

namespace ProgrammaticSEO\Database;

/**
 * Class Database_Manager
 */
class Database_Manager {

	/**
	 * Create plugin tables
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Templates table
		$templates_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pseo_templates (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			slug VARCHAR(255) NOT NULL UNIQUE,
			description TEXT,
			template_content LONGTEXT,
			data_mapping JSON,
			status VARCHAR(50) DEFAULT 'draft',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY slug (slug),
			KEY status (status)
		) $charset_collate;";

		// Data sources table
		$data_sources_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pseo_data_sources (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			type VARCHAR(50) NOT NULL, -- csv, json, api, database
			config JSON,
			last_sync DATETIME,
			sync_status VARCHAR(50) DEFAULT 'pending',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY type (type)
		) $charset_collate;";

		// Generated pages table
		$generated_pages_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pseo_generated_pages (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) NOT NULL,
			template_id BIGINT(20) NOT NULL,
			data_source_id BIGINT(20) NOT NULL,
			slug VARCHAR(255) NOT NULL UNIQUE,
			title VARCHAR(255) NOT NULL,
			content LONGTEXT,
			seo_meta JSON,
			schema_data JSON,
			status VARCHAR(50) DEFAULT 'published',
			views INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY template_id (template_id),
			KEY slug (slug),
			KEY status (status),
			FOREIGN KEY (post_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
		) $charset_collate;";

		// Internal links table
		$internal_links_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pseo_internal_links (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			source_post_id BIGINT(20) NOT NULL,
			target_post_id BIGINT(20) NOT NULL,
			anchor_text VARCHAR(255),
			link_type VARCHAR(50) DEFAULT 'contextual', -- contextual, related, internal
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY source_post_id (source_post_id),
			KEY target_post_id (target_post_id),
			UNIQUE KEY unique_link (source_post_id, target_post_id)
		) $charset_collate;";

		// Analytics table
		$analytics_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pseo_analytics (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) NOT NULL,
			date DATE NOT NULL,
			views INT DEFAULT 0,
			clicks INT DEFAULT 0,
			avg_position DECIMAL(5,2),
			avg_ctr DECIMAL(5,2),
			bounce_rate DECIMAL(5,2),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY date (date),
			UNIQUE KEY unique_analytics (post_id, date)
		) $charset_collate;";

		// Keyword rankings table
		$keyword_rankings_table = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pseo_keyword_rankings (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) NOT NULL,
			keyword VARCHAR(255) NOT NULL,
			search_volume INT,
			position INT,
			ctr DECIMAL(5,2),
			date DATE,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY keyword (keyword),
			KEY date (date)
		) $charset_collate;";

		// Execute table creation queries
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $templates_table );
		dbDelta( $data_sources_table );
		dbDelta( $generated_pages_table );
		dbDelta( $internal_links_table );
		dbDelta( $analytics_table );
		dbDelta( $keyword_rankings_table );
	}

	/**
	 * Drop plugin tables
	 */
	public static function drop_tables() {
		global $wpdb;

		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pseo_keyword_rankings" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pseo_analytics" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pseo_internal_links" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pseo_generated_pages" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pseo_data_sources" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pseo_templates" );
	}

	/**
	 * Insert template
	 *
	 * @param array $data Template data.
	 * @return int Template ID
	 */
	public static function insert_template( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_templates',
			array(
				'name'             => $data['name'],
				'slug'             => $data['slug'],
				'description'      => $data['description'] ?? '',
				'template_content' => $data['template_content'],
				'data_mapping'     => json_encode( $data['data_mapping'] ?? array() ),
				'status'           => $data['status'] ?? 'draft',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get template by ID
	 *
	 * @param int $id Template ID.
	 * @return array|null Template data
	 */
	public static function get_template( $id ) {
		global $wpdb;

		$template = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pseo_templates WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( $template && isset( $template['data_mapping'] ) ) {
			$template['data_mapping'] = json_decode( $template['data_mapping'], true );
		}

		return $template;
	}

	/**
	 * Get all templates
	 *
	 * @return array Templates
	 */
	public static function get_all_templates() {
		global $wpdb;

		$templates = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}pseo_templates ORDER BY created_at DESC",
			ARRAY_A
		);

		foreach ( $templates as &$template ) {
			if ( isset( $template['data_mapping'] ) ) {
				$template['data_mapping'] = json_decode( $template['data_mapping'], true );
			}
		}

		return $templates;
	}

	/**
	 * Update template
	 *
	 * @param int   $id Template ID.
	 * @param array $data Template data.
	 * @return bool
	 */
	public static function update_template( $id, $data ) {
		global $wpdb;

		return $wpdb->update(
			$wpdb->prefix . 'pseo_templates',
			array(
				'name'             => $data['name'] ?? '',
				'slug'             => $data['slug'] ?? '',
				'description'      => $data['description'] ?? '',
				'template_content' => $data['template_content'] ?? '',
				'data_mapping'     => json_encode( $data['data_mapping'] ?? array() ),
				'status'           => $data['status'] ?? 'draft',
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Delete template
	 *
	 * @param int $id Template ID.
	 * @return bool
	 */
	public static function delete_template( $id ) {
		global $wpdb;
		return $wpdb->delete( $wpdb->prefix . 'pseo_templates', array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Insert data source
	 *
	 * @param array $data Data source data.
	 * @return int Data source ID
	 */
	public static function insert_data_source( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_data_sources',
			array(
				'name'   => $data['name'],
				'type'   => $data['type'],
				'config' => json_encode( $data['config'] ?? array() ),
			),
			array( '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get data source by ID
	 *
	 * @param int $id Data source ID.
	 * @return array|null Data source data
	 */
	public static function get_data_source( $id ) {
		global $wpdb;

		$source = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pseo_data_sources WHERE id = %d", $id ),
			ARRAY_A
		);

		if ( $source && isset( $source['config'] ) ) {
			$source['config'] = json_decode( $source['config'], true );
		}

		return $source;
	}

	/**
	 * Insert generated page
	 *
	 * @param array $data Page data.
	 * @return int Generated page ID
	 */
	public static function insert_generated_page( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_generated_pages',
			array(
				'post_id'        => $data['post_id'],
				'template_id'    => $data['template_id'],
				'data_source_id' => $data['data_source_id'],
				'slug'           => $data['slug'],
				'title'          => $data['title'],
				'content'        => $data['content'] ?? '',
				'seo_meta'       => json_encode( $data['seo_meta'] ?? array() ),
				'schema_data'    => json_encode( $data['schema_data'] ?? array() ),
				'status'         => $data['status'] ?? 'published',
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get generated pages
	 *
	 * @param array $args Query arguments.
	 * @return array Generated pages
	 */
	public static function get_generated_pages( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'template_id'    => null,
			'data_source_id' => null,
			'status'         => null,
			'limit'          => 20,
			'offset'         => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = '1=1';
		$values = array();

		if ( ! is_null( $args['template_id'] ) ) {
			$where    .= ' AND template_id = %d';
			$values[] = $args['template_id'];
		}

		if ( ! is_null( $args['data_source_id'] ) ) {
			$where    .= ' AND data_source_id = %d';
			$values[] = $args['data_source_id'];
		}

		if ( ! is_null( $args['status'] ) ) {
			$where    .= ' AND status = %s';
			$values[] = $args['status'];
		}

		$sql = "SELECT * FROM {$wpdb->prefix}pseo_generated_pages WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$values[] = $args['limit'];
		$values[] = $args['offset'];

		$query = $wpdb->prepare( $sql, $values );
		return $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Update generated page
	 *
	 * @param int   $id Page ID.
	 * @param array $data Page data.
	 * @return bool
	 */
	public static function update_generated_page( $id, $data ) {
		global $wpdb;

		return $wpdb->update(
			$wpdb->prefix . 'pseo_generated_pages',
			array(
				'title'       => $data['title'] ?? '',
				'content'     => $data['content'] ?? '',
				'seo_meta'    => json_encode( $data['seo_meta'] ?? array() ),
				'schema_data' => json_encode( $data['schema_data'] ?? array() ),
				'status'      => $data['status'] ?? 'published',
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Record page view
	 *
	 * @param int $post_id Post ID.
	 */
	public static function record_page_view( $post_id ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}pseo_generated_pages SET views = views + 1 WHERE post_id = %d",
				$post_id
			)
		);
	}

	/**
	 * Insert internal link
	 *
	 * @param int    $source_post_id Source post ID.
	 * @param int    $target_post_id Target post ID.
	 * @param string $anchor_text Anchor text.
	 * @param string $link_type Link type.
	 * @return int Link ID
	 */
	public static function insert_internal_link( $source_post_id, $target_post_id, $anchor_text = '', $link_type = 'contextual' ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_internal_links',
			array(
				'source_post_id' => $source_post_id,
				'target_post_id' => $target_post_id,
				'anchor_text'    => $anchor_text,
				'link_type'      => $link_type,
			),
			array( '%d', '%d', '%s', '%s' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get internal links for post
	 *
	 * @param int $post_id Post ID.
	 * @return array Internal links
	 */
	public static function get_internal_links( $post_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pseo_internal_links WHERE source_post_id = %d ORDER BY created_at DESC",
				$post_id
			),
			ARRAY_A
		);
	}

	/**
	 * Insert analytics record
	 *
	 * @param array $data Analytics data.
	 * @return int Analytics record ID
	 */
	public static function insert_analytics( $data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'pseo_analytics',
			array(
				'post_id'     => $data['post_id'],
				'date'        => $data['date'],
				'views'       => $data['views'] ?? 0,
				'clicks'      => $data['clicks'] ?? 0,
				'avg_position' => $data['avg_position'] ?? null,
				'avg_ctr'     => $data['avg_ctr'] ?? null,
				'bounce_rate' => $data['bounce_rate'] ?? null,
			),
			array( '%d', '%s', '%d', '%d', '%f', '%f', '%f' )
		);

		return $wpdb->insert_id;
	}

	/**
	 * Get analytics for post
	 *
	 * @param int    $post_id Post ID.
	 * @param string $date_from From date.
	 * @param string $date_to To date.
	 * @return array Analytics records
	 */
	public static function get_analytics( $post_id, $date_from = null, $date_to = null ) {
		global $wpdb;

		$where = "post_id = %d";
		$values = array( $post_id );

		if ( $date_from ) {
			$where    .= ' AND date >= %s';
			$values[] = $date_from;
		}

		if ( $date_to ) {
			$where    .= ' AND date <= %s';
			$values[] = $date_to;
		}

		$sql = "SELECT * FROM {$wpdb->prefix}pseo_analytics WHERE $where ORDER BY date DESC";
		$query = $wpdb->prepare( $sql, $values );

		return $wpdb->get_results( $query, ARRAY_A );
	}
}
