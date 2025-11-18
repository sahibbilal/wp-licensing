<?php
/**
 * Plugin Name: WP Licensing
 * Plugin URI: https://wpcorex.com/products/wp-licensing
 * Description: Professional WordPress SaaS Licensing & Auto Update System
 * Version: 1.0.0
 * Author: Bilal Mahmood
 * Author URI: https://wpcorex.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: wp-licensing
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'WP_LICENSING_VERSION', '1.0.0' );
define( 'WP_LICENSING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_LICENSING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_LICENSING_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader
 */
require_once WP_LICENSING_PLUGIN_DIR . 'src/Core/Autoloader.php';
WP_Licensing\Core\Autoloader::register();

/**
 * Activation & Deactivation hooks
 */
register_activation_hook( __FILE__, array( 'WP_Licensing\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Licensing\Core\Deactivator', 'deactivate' ) );

/**
 * Initialize the plugin
 */
function wp_licensing_init() {
	$plugin = new WP_Licensing\Core\Plugin();
	$plugin->run();
}
wp_licensing_init();

