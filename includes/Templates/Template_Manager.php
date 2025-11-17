<?php
/**
 * Template Manager Class
 *
 * @package ProgrammaticSEO\Templates
 */

namespace ProgrammaticSEO\Templates;

use ProgrammaticSEO\Database\Database_Manager;

/**
 * Class Template_Manager
 */
class Template_Manager {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_template_actions' ) );
	}

	/**
	 * Create template
	 *
	 * @param array $data Template data.
	 * @return int Template ID
	 */
	public function create_template( $data ) {
		$data = $this->sanitize_template_data( $data );
		return Database_Manager::insert_template( $data );
	}

	/**
	 * Update template
	 *
	 * @param int   $id Template ID.
	 * @param array $data Template data.
	 * @return bool
	 */
	public function update_template( $id, $data ) {
		$data = $this->sanitize_template_data( $data );
		return Database_Manager::update_template( $id, $data );
	}

	/**
	 * Get template
	 *
	 * @param int $id Template ID.
	 * @return array|null
	 */
	public function get_template( $id ) {
		return Database_Manager::get_template( $id );
	}

	/**
	 * Get all templates
	 *
	 * @return array
	 */
	public function get_all_templates() {
		return Database_Manager::get_all_templates();
	}

	/**
	 * Delete template
	 *
	 * @param int $id Template ID.
	 * @return bool
	 */
	public function delete_template( $id ) {
		return Database_Manager::delete_template( $id );
	}

	/**
	 * Render template with data
	 *
	 * @param int   $template_id Template ID.
	 * @param array $data Data to render.
	 * @return string Rendered HTML
	 */
	public function render_template( $template_id, $data ) {
		$template = $this->get_template( $template_id );
		if ( ! $template ) {
			return '';
		}

		$content = $template['template_content'];

		// Replace placeholders with actual data
		foreach ( $data as $key => $value ) {
			$placeholder = '{{' . $key . '}}';
			$content     = str_replace( $placeholder, wp_kses_post( $value ), $content );
		}

		return $content;
	}

	/**
	 * Get template variables
	 *
	 * @param int $id Template ID.
	 * @return array Variable names
	 */
	public function get_template_variables( $id ) {
		$template = $this->get_template( $id );
		if ( ! $template ) {
			return array();
		}

		$content = $template['template_content'];
		$matches = array();

		// Find all {{variable}} patterns
		preg_match_all( '/\{\{([a-zA-Z0-9_\-]+)\}\}/', $content, $matches );

		return array_unique( $matches[1] );
	}

	/**
	 * Validate template
	 *
	 * @param array $data Template data.
	 * @return array Validation errors
	 */
	public function validate_template( $data ) {
		$errors = array();

		if ( empty( $data['name'] ) ) {
			$errors[] = __( 'Template name is required', 'programmatic-seo' );
		}

		if ( empty( $data['slug'] ) ) {
			$errors[] = __( 'Template slug is required', 'programmatic-seo' );
		}

		if ( empty( $data['template_content'] ) ) {
			$errors[] = __( 'Template content is required', 'programmatic-seo' );
		}

		// Check slug uniqueness
		$existing = Database_Manager::get_all_templates();
		foreach ( $existing as $template ) {
			if ( $template['slug'] === $data['slug'] && ( ! isset( $data['id'] ) || $template['id'] != $data['id'] ) ) {
				$errors[] = __( 'Template slug must be unique', 'programmatic-seo' );
				break;
			}
		}

		return $errors;
	}

	/**
	 * Sanitize template data
	 *
	 * @param array $data Template data.
	 * @return array Sanitized data
	 */
	private function sanitize_template_data( $data ) {
		return array(
			'id'                => isset( $data['id'] ) ? absint( $data['id'] ) : null,
			'name'              => isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '',
			'slug'              => isset( $data['slug'] ) ? sanitize_title( $data['slug'] ) : '',
			'description'       => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			'template_content'  => isset( $data['template_content'] ) ? wp_kses_post( $data['template_content'] ) : '',
			'data_mapping'      => isset( $data['data_mapping'] ) ? $data['data_mapping'] : array(),
			'status'            => isset( $data['status'] ) && in_array( $data['status'], array( 'draft', 'published' ) ) ? $data['status'] : 'draft',
		);
	}

	/**
	 * Handle template actions from admin
	 */
	public function handle_template_actions() {
		if ( ! isset( $_GET['page'] ) || 'programmatic-seo-templates' !== $_GET['page'] ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : '';

		switch ( $action ) {
			case 'delete':
				if ( isset( $_GET['id'] ) ) {
					$id = absint( $_GET['id'] );
					$this->delete_template( $id );
					wp_safe_remote_post(
						admin_url( 'admin.php?page=programmatic-seo-templates' ),
						array( 'blocking' => false )
					);
				}
				break;
		}
	}
}
