<?php
/**
 * REST API Routes
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\API;

/**
 * Routes class
 */
class Routes {

	/**
	 * Register all routes
	 */
	public function register() {
		$this->register_validation_routes();
		$this->register_update_routes();
		$this->register_management_routes();
	}

	/**
	 * Register validation routes
	 */
	private function register_validation_routes() {
		$controller = new ValidationController();

		register_rest_route(
			'wp-licensing/v1',
			'/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $controller, 'validate' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/deactivate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $controller, 'deactivate' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register update routes
	 */
	private function register_update_routes() {
		$controller = new UpdateController();

		register_rest_route(
			'wp-licensing/v1',
			'/update',
			array(
				'methods'             => 'GET',
				'callback'            => array( $controller, 'check_update' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Register management routes
	 */
	private function register_management_routes() {
		$controller = new ManagementController();

		register_rest_route(
			'wp-licensing/v1',
			'/licenses',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $controller, 'get_licenses' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $controller, 'create_license' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/licenses/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $controller, 'update_license' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $controller, 'delete_license' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/licenses/(?P<license_id>\d+)/activations',
			array(
				'methods'             => 'GET',
				'callback'            => array( $controller, 'get_activations' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/products',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $controller, 'get_products' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $controller, 'create_product' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/products/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $controller, 'update_product' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $controller, 'delete_product' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/stats',
			array(
				'methods'             => 'GET',
				'callback'            => array( $controller, 'get_stats' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);

		register_rest_route(
			'wp-licensing/v1',
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $controller, 'get_settings' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $controller, 'save_settings' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);
	}

	/**
	 * Check admin permission
	 *
	 * @return bool
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}
}

