<?php
/**
 * Page Generator Class
 *
 * @package ProgrammaticSEO\Generator
 */

namespace ProgrammaticSEO\Generator;

use ProgrammaticSEO\Database\Database_Manager;
use ProgrammaticSEO\Templates\Template_Manager;
use ProgrammaticSEO\DataSource\Data_Source_Manager;

/**
 * Class Page_Generator
 */
class Page_Generator {

	/**
	 * Template Manager instance
	 *
	 * @var Template_Manager
	 */
	private $template_manager;

	/**
	 * Data Source Manager instance
	 *
	 * @var Data_Source_Manager
	 */
	private $data_source_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->template_manager     = new Template_Manager();
		$this->data_source_manager  = new Data_Source_Manager();
		add_action( 'admin_init', array( $this, 'handle_generation_actions' ) );
	}

	/**
	 * Generate pages from template and data source
	 *
	 * @param int $template_id Template ID.
	 * @param int $data_source_id Data source ID.
	 * @return array Generated page IDs
	 */
	public function generate_pages( $template_id, $data_source_id ) {
		$template = $this->template_manager->get_template( $template_id );
		if ( ! $template ) {
			return array();
		}

		$data = $this->data_source_manager->fetch_data( $data_source_id );
		if ( ! $data ) {
			return array();
		}

		$generated_ids = array();

		foreach ( $data as $row ) {
			$page_id = $this->generate_single_page( $template, $data_source_id, $row );
			if ( $page_id ) {
				$generated_ids[] = $page_id;
			}
		}

		return $generated_ids;
	}

	/**
	 * Generate single page
	 *
	 * @param array $template Template data.
	 * @param int   $data_source_id Data source ID.
	 * @param array $row Data row.
	 * @return int|false Post ID or false
	 */
	private function generate_single_page( $template, $data_source_id, $row ) {
		// Generate slug from data
		$slug = $this->generate_slug( $row, $template );

		// Check if page already exists
		global $wpdb;
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}pseo_generated_pages WHERE slug = %s",
				$slug
			)
		);

		if ( $existing ) {
			return $existing;
		}

		// Generate content
		$title   = isset( $row[ $template['data_mapping']['title'] ?? 'title' ] ) ? $row[ $template['data_mapping']['title'] ?? 'title' ] : '';
		$content = $this->template_manager->render_template( $template['id'], $row );

		// Create WordPress post
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_content' => $content,
				'post_status' => 'publish',
				'post_type'   => 'pseo_generated',
				'post_name'   => $slug,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Generate SEO metadata
		$seo_meta = apply_filters( 'pseo_generate_seo_meta', array(
			'title'       => $title,
			'description' => isset( $row['description'] ) ? $row['description'] : '',
			'keywords'    => isset( $row['keywords'] ) ? $row['keywords'] : '',
		), $row, $template );

		// Record in database
		Database_Manager::insert_generated_page(
			array(
				'post_id'        => $post_id,
				'template_id'    => $template['id'],
				'data_source_id' => $data_source_id,
				'slug'           => $slug,
				'title'          => $title,
				'content'        => $content,
				'seo_meta'       => $seo_meta,
			)
		);

		// Store data as post meta
		update_post_meta( $post_id, '_pseo_data', $row );
		update_post_meta( $post_id, '_pseo_template_id', $template['id'] );
		update_post_meta( $post_id, '_pseo_data_source_id', $data_source_id );
		update_post_meta( $post_id, '_pseo_meta', $seo_meta );

		return $post_id;
	}

	/**
	 * Generate slug from data
	 *
	 * @param array $row Data row.
	 * @param array $template Template data.
	 * @return string Slug
	 */
	private function generate_slug( $row, $template ) {
		// Use mapping to generate slug
		$slug_pattern = isset( $template['data_mapping']['slug_pattern'] ) ? $template['data_mapping']['slug_pattern'] : null;

		if ( $slug_pattern ) {
			// Replace placeholders in pattern
			foreach ( $row as $key => $value ) {
				$slug_pattern = str_replace( '{{' . $key . '}}', $value, $slug_pattern );
			}
			$slug = sanitize_title( $slug_pattern );
		} else {
			// Default slug from title or first field
			$slug = isset( $row['title'] ) ? $row['title'] : reset( $row );
			$slug = sanitize_title( $slug );
		}

		// Ensure uniqueness
		$slug = $this->ensure_unique_slug( $slug );

		return $slug;
	}

	/**
	 * Ensure slug is unique
	 *
	 * @param string $slug Base slug.
	 * @return string Unique slug
	 */
	private function ensure_unique_slug( $slug ) {
		global $wpdb;

		$original_slug = $slug;
		$counter       = 1;

		while ( true ) {
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->prefix}pseo_generated_pages WHERE slug = %s",
					$slug
				)
			);

			if ( ! $existing ) {
				break;
			}

			$slug = $original_slug . '-' . $counter;
			$counter++;
		}

		return $slug;
	}

	/**
	 * Handle generation actions from admin
	 */
	public function handle_generation_actions() {
		if ( ! isset( $_POST['action'] ) || 'generate_pages' !== $_POST['action'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		check_admin_referer( 'pseo_generate_pages' );

		$template_id    = isset( $_POST['template_id'] ) ? absint( $_POST['template_id'] ) : 0;
		$data_source_id = isset( $_POST['data_source_id'] ) ? absint( $_POST['data_source_id'] ) : 0;

		if ( $template_id && $data_source_id ) {
			$this->generate_pages( $template_id, $data_source_id );
		}
	}
}
