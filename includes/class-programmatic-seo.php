<?php
/**
 * Plugin Loader Class
 *
 * @package ProgrammaticSEO
 */

// Load core class
require_once PSEO_PLUGIN_DIR . 'includes/Core/Programmatic_SEO.php';

use ProgrammaticSEO\Core\Programmatic_SEO;

/**
 * Get plugin instance on plugins_loaded
 */
add_action(
	'plugins_loaded',
	function() {
		return Programmatic_SEO::instance();
	}
);
