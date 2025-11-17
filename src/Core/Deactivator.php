<?php
/**
 * Plugin Deactivator
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Core;

/**
 * Deactivator class
 */
class Deactivator {

	/**
	 * Deactivate plugin
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}

