<?php
/**
 * Helper Functions for Programmatic SEO
 *
 * @package ProgrammaticSEO\Helpers
 */

/**
 * Get plugin instance
 *
 * @return \ProgrammaticSEO\Core\Programmatic_SEO
 */
function pseo_get_plugin() {
	return \ProgrammaticSEO\Core\Programmatic_SEO::instance();
}

/**
 * Create a programmatic SEO template
 *
 * @param array $args Template arguments.
 * @return int Template ID
 */
function pseo_create_template( $args ) {
	$plugin = pseo_get_plugin();
	return $plugin->template_manager->create_template( $args );
}

/**
 * Get a programmatic SEO template
 *
 * @param int $id Template ID.
 * @return array|null Template data
 */
function pseo_get_template( $id ) {
	$plugin = pseo_get_plugin();
	return $plugin->template_manager->get_template( $id );
}

/**
 * Get all programmatic SEO templates
 *
 * @return array Templates
 */
function pseo_get_templates() {
	$plugin = pseo_get_plugin();
	return $plugin->template_manager->get_all_templates();
}

/**
 * Create a data source
 *
 * @param array $args Data source arguments.
 * @return int Data source ID
 */
function pseo_create_data_source( $args ) {
	$plugin = pseo_get_plugin();
	return $plugin->data_source_manager->create_source( $args );
}

/**
 * Get a data source
 *
 * @param int $id Data source ID.
 * @return array|null Data source data
 */
function pseo_get_data_source( $id ) {
	$plugin = pseo_get_plugin();
	return $plugin->data_source_manager->get_source( $id );
}

/**
 * Fetch data from a data source
 *
 * @param int $id Data source ID.
 * @return array|false Data or false
 */
function pseo_fetch_data( $id ) {
	$plugin = pseo_get_plugin();
	return $plugin->data_source_manager->fetch_data( $id );
}

/**
 * Generate pages from template and data source
 *
 * @param int $template_id Template ID.
 * @param int $data_source_id Data source ID.
 * @return array Generated page IDs
 */
function pseo_generate_pages( $template_id, $data_source_id ) {
	$plugin = pseo_get_plugin();
	return $plugin->page_generator->generate_pages( $template_id, $data_source_id );
}

/**
 * Get page analytics
 *
 * @param int $post_id Post ID.
 * @return array Analytics data
 */
function pseo_get_analytics( $post_id ) {
	$plugin = pseo_get_plugin();
	return $plugin->analytics_manager->get_page_analytics( $post_id );
}

/**
 * Get top performing pages
 *
 * @param int $limit Number of pages.
 * @param int $days Number of days.
 * @return array Top pages
 */
function pseo_get_top_pages( $limit = 10, $days = 30 ) {
	$plugin = pseo_get_plugin();
	return $plugin->analytics_manager->get_top_pages( $limit, $days );
}

/**
 * Generate internal links for a post
 *
 * @param int $post_id Post ID.
 * @param int $max_links Maximum links.
 */
function pseo_generate_links( $post_id, $max_links = 5 ) {
	$plugin = pseo_get_plugin();
	$plugin->internal_link_manager->generate_links_for_post( $post_id, $max_links );
}

/**
 * Get related pages
 *
 * @param int $post_id Post ID.
 * @param int $limit Number of pages.
 * @return array Related pages
 */
function pseo_get_related_pages( $post_id, $limit = 5 ) {
	$plugin = pseo_get_plugin();
	return $plugin->internal_link_manager->get_related_pages( $post_id, $limit );
}

/**
 * Generate XML sitemap
 *
 * @return string XML sitemap
 */
function pseo_generate_xml_sitemap() {
	$plugin = pseo_get_plugin();
	return $plugin->sitemap_generator->generate_xml_sitemap();
}

/**
 * Generate HTML sitemap
 *
 * @return string HTML sitemap
 */
function pseo_generate_html_sitemap() {
	$plugin = pseo_get_plugin();
	return $plugin->sitemap_generator->generate_html_sitemap();
}

/**
 * Check if a post is a programmatic SEO page
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function pseo_is_programmatic_page( $post_id ) {
	$post = get_post( $post_id );
	return $post && $post->post_type === 'pseo_generated';
}

/**
 * Get programmatic SEO post meta
 *
 * @param int    $post_id Post ID.
 * @param string $key Meta key.
 * @return mixed Meta value
 */
function pseo_get_meta( $post_id, $key ) {
	return get_post_meta( $post_id, '_pseo_' . $key, true );
}

/**
 * Update programmatic SEO post meta
 *
 * @param int    $post_id Post ID.
 * @param string $key Meta key.
 * @param mixed  $value Meta value.
 * @return bool
 */
function pseo_update_meta( $post_id, $key, $value ) {
	return update_post_meta( $post_id, '_pseo_' . $key, $value );
}

/**
 * Get generated pages count
 *
 * @return int Count
 */
function pseo_get_generated_pages_count() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pseo_generated_pages WHERE status = 'published'" );
}

/**
 * Get plugin settings
 *
 * @param string $key Setting key.
 * @param mixed  $default Default value.
 * @return mixed Setting value
 */
function pseo_get_setting( $key = null, $default = null ) {
	$settings = get_option( 'pseo_settings', array() );

	if ( $key === null ) {
		return $settings;
	}

	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update plugin settings
 *
 * @param string $key Setting key.
 * @param mixed  $value Setting value.
 * @return bool
 */
function pseo_update_setting( $key, $value ) {
	$settings = get_option( 'pseo_settings', array() );
	$settings[ $key ] = $value;
	return update_option( 'pseo_settings', $settings );
}

/**
 * Log message
 *
 * @param string $message Log message.
 * @param string $level Log level (info, warning, error).
 */
function pseo_log( $message, $level = 'info' ) {
	if ( ! defined( 'PSEO_DEBUG' ) || ! PSEO_DEBUG ) {
		return;
	}

	$log_file = PSEO_PLUGIN_DIR . 'logs/debug.log';

	if ( ! is_dir( dirname( $log_file ) ) ) {
		wp_mkdir_p( dirname( $log_file ) );
	}

	$timestamp = current_time( 'mysql' );
	error_log( "[{$timestamp}] [{$level}] {$message}\n", 3, $log_file );
}

/**
 * Render programmatic SEO page template
 *
 * @param int   $template_id Template ID.
 * @param array $data Page data.
 * @return string Rendered content
 */
function pseo_render_template( $template_id, $data ) {
	$plugin = pseo_get_plugin();
	return $plugin->template_manager->render_template( $template_id, $data );
}

/**
 * Get template variables
 *
 * @param int $template_id Template ID.
 * @return array Variables
 */
function pseo_get_template_variables( $template_id ) {
	$plugin = pseo_get_plugin();
	return $plugin->template_manager->get_template_variables( $template_id );
}
