<?php
/**
 * Uninstall Script
 *
 * @package ProgrammaticSEO
 */

// If uninstall not called from WordPress, exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Database Manager
require_once plugin_dir_path( __FILE__ ) . 'includes/Database/Database_Manager.php';

// Drop all plugin tables
ProgrammaticSEO\Database\Database_Manager::drop_tables();

// Delete plugin options
delete_option( 'pseo_plugin_activated' );
delete_option( 'pseo_settings' );

// Delete all post meta for generated pages
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_pseo_%'" );

// Delete generated posts
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type = 'pseo_generated'" );
