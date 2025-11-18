<?php
/**
 * Main Plugin Class
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Core;

use WP_Licensing\API\Routes;
use WP_Licensing\Controllers\AdminController;

/**
 * Plugin class
 */
class Plugin {

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Run the plugin
	 */
	public function run() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load dependencies
	 */
	private function load_dependencies() {
		// Database migration
		require_once WP_LICENSING_PLUGIN_DIR . 'src/Core/Database.php';
		
		// Initialize database
		Database::get_instance();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		// REST API routes
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		
		// Admin menu
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		
		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		
		// Load text domain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		$routes = new Routes();
		$routes->register();
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Licensing', 'wp-licensing' ),
			__( 'Licensing', 'wp-licensing' ),
			'manage_options',
			'wp-licensing',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-network',
			30
		);
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		$controller = new AdminController();
		$controller->render();
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_wp-licensing' !== $hook ) {
			return;
		}

		// Enqueue React app
		$asset_file_path = WP_LICENSING_PLUGIN_DIR . 'build/admin-app.asset.php';
		$build_file = WP_LICENSING_PLUGIN_DIR . 'build/admin-app.js';
		
		if ( ! file_exists( $build_file ) ) {
			return; // Don't enqueue if build doesn't exist
		}
		
		$asset_file = file_exists( $asset_file_path ) 
			? include $asset_file_path 
			: array(
				'dependencies' => array(), // React is bundled, no external deps needed
				'version'      => WP_LICENSING_VERSION,
			);
		
		wp_enqueue_script(
			'wp-licensing-admin',
			WP_LICENSING_PLUGIN_URL . 'build/admin-app.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_enqueue_style(
			'wp-licensing-admin',
			WP_LICENSING_PLUGIN_URL . 'build/admin-app.css',
			array(),
			WP_LICENSING_VERSION
		);

		// Localize script
		wp_localize_script(
			'wp-licensing-admin',
			'wpLicensing',
			array(
				'apiUrl'   => rest_url( 'wp-licensing/v1/' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'root'     => esc_url_raw( rest_url() ),
				'baseURL'  => admin_url( 'admin.php?page=wp-licensing' ),
				'siteUrl'  => home_url(),
			)
		);
	}

	/**
	 * Load text domain
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-licensing',
			false,
			dirname( WP_LICENSING_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

