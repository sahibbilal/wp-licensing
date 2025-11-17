<?php
/**
 * Plugin Activator
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Core;

/**
 * Activator class
 */
class Activator {

	/**
	 * Activate plugin
	 */
	public static function activate() {
		// Create database tables
		Database::get_instance()->create_tables();
		
		// Set default options
		self::set_default_options();
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Set default options
	 */
	private static function set_default_options() {
		if ( ! get_option( 'wp_licensing_version' ) ) {
			add_option( 'wp_licensing_version', WP_LICENSING_VERSION );
		}
	}
}

