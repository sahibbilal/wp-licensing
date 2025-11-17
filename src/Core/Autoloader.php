<?php
/**
 * PSR-4 Autoloader
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Core;

/**
 * Autoloader class
 */
class Autoloader {

	/**
	 * Register autoloader
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load_class' ) );
	}

	/**
	 * Load class file
	 *
	 * @param string $class_name Class name.
	 */
	public static function load_class( $class_name ) {
		// Only load our namespace
		if ( strpos( $class_name, 'WP_Licensing\\' ) !== 0 ) {
			return;
		}

		// Remove namespace prefix
		$class_name = str_replace( 'WP_Licensing\\', '', $class_name );
		
		// Convert namespace separators to directory separators
		$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );
		
		// Build file path
		$file = WP_LICENSING_PLUGIN_DIR . 'src' . DIRECTORY_SEPARATOR . $class_name . '.php';
		
		// Load file if it exists
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}

