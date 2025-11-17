<?php
/**
 * Plugin Name: Programmatic SEO
 * Plugin URI: https://example.com/programmatic-seo
 * Description: Quản lý SEO programmatic - tạo hàng nghìn trang tối ưu SEO tự động từ templates và dữ liệu
 * Version: 1.0.0
 * Author: Auto Web
 * Author URI: https://example.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: programmatic-seo
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package ProgrammaticSEO
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'PSEO_PLUGIN_FILE', __FILE__ );
define( 'PSEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PSEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PSEO_VERSION', '1.0.0' );

// Load composer autoloader if exists
if ( file_exists( PSEO_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once PSEO_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load Database Manager first (needed for activation)
require_once PSEO_PLUGIN_DIR . 'includes/Database/Database_Manager.php';

// Load plugin main class
require_once PSEO_PLUGIN_DIR . 'includes/Core/Programmatic_SEO.php';

/**
 * Plugin activation hook
 */
function pseo_activate_plugin() {
	ProgrammaticSEO\Database\Database_Manager::create_tables();
	flush_rewrite_rules();
	update_option( 'pseo_plugin_activated', true );
}
register_activation_hook( PSEO_PLUGIN_FILE, 'pseo_activate_plugin' );

/**
 * Plugin deactivation hook
 */
function pseo_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( PSEO_PLUGIN_FILE, 'pseo_deactivate_plugin' );

/**
 * Initialize plugin on plugins_loaded
 */
add_action( 'plugins_loaded', function() {
	// Load all dependencies
	require_once PSEO_PLUGIN_DIR . 'includes/Admin/Admin_Dashboard.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Templates/Template_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/DataSource/Data_Source_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Generator/Page_Generator.php';
	require_once PSEO_PLUGIN_DIR . 'includes/SEO/SEO_Meta_Generator.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Schema/Schema_Generator.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Linking/Internal_Link_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Sitemap/Sitemap_Generator.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Analytics/Analytics_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Cache/Cache_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Database/Database_Optimizer.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Queue/Queue_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Monitor/System_Monitor.php';
	require_once PSEO_PLUGIN_DIR . 'includes/SEO/Technical_SEO_Automation.php';
	require_once PSEO_PLUGIN_DIR . 'includes/AI/AI_Content_Optimizer.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Reports/Advanced_Reporting.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Analytics/Predictive_Analytics.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Linking/Advanced_Internal_Linking.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Competitive/Competitor_Monitor.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Multilingual/Multilingual_Manager.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Automation/Workflow_Automation.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Dashboard/Analytics_Dashboard.php';
	require_once PSEO_PLUGIN_DIR . 'includes/Helpers/Helper_Functions.php';

	// Initialize plugin
	ProgrammaticSEO\Core\Programmatic_SEO::instance();
} );
