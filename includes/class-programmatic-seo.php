<?php
/**
 * Plugin Loader Class
 *
 * @package ProgrammaticSEO
 */

namespace ProgrammaticSEO;

use ProgrammaticSEO\Core\Programmatic_SEO;

/**
 * Load and initialize the plugin
 */
require_once PSEO_PLUGIN_DIR . 'includes/Core/Programmatic_SEO.php';

/**
 * Get plugin instance on plugins_loaded
 */
add_action(
	'plugins_loaded',
	function() {
		return Programmatic_SEO::instance();
	}
);
