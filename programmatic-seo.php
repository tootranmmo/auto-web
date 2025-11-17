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

// Load composer autoloader
require_once PSEO_PLUGIN_DIR . 'vendor/autoload.php';

// Load plugin main class
require_once PSEO_PLUGIN_DIR . 'includes/class-programmatic-seo.php';

/**
 * Plugin activation hook
 */
register_activation_hook( PSEO_PLUGIN_FILE, array( 'ProgrammaticSEO\Core\Programmatic_SEO', 'activate' ) );

/**
 * Plugin deactivation hook
 */
register_deactivation_hook( PSEO_PLUGIN_FILE, array( 'ProgrammaticSEO\Core\Programmatic_SEO', 'deactivate' ) );

/**
 * Plugin uninstall hook
 */
register_uninstall_hook( PSEO_PLUGIN_FILE, array( 'ProgrammaticSEO\Core\Programmatic_SEO', 'uninstall' ) );

/**
 * Initialize plugin
 */
add_action( 'plugins_loaded', function() {
	ProgrammaticSEO\Core\Programmatic_SEO::instance();
} );
