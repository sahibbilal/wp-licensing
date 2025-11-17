<?php
/**
 * Auto Update API Controller
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\API;

use WP_Licensing\Helpers\RateLimiter;
use WP_Licensing\Helpers\Security;
use WP_Licensing\Models\License;
use WP_Licensing\Models\Product;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Update controller class
 */
class UpdateController {

	/**
	 * Check for updates
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function check_update( WP_REST_Request $request ) {
		$start_time = microtime( true );

		// Rate limiting
		$ip_address = Security::get_client_ip();
		if ( ! RateLimiter::check( $ip_address, 30, 60 ) ) {
			RateLimiter::log_request( '/update', 'GET', null, 429, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'error' => 'Rate limit exceeded.',
				),
				429
			);
		}

		$license_key = Security::sanitize_license_key( $request->get_param( 'license_key' ) );
		$current_version = sanitize_text_field( $request->get_param( 'version' ) );
		$product_id = (int) $request->get_param( 'product_id' );

		if ( empty( $license_key ) || empty( $product_id ) ) {
			RateLimiter::log_request( '/update', 'GET', $license_key, 400, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'error' => 'Missing required parameters.',
				),
				400
			);
		}

		// Validate license
		$license = License::find_by_key( $license_key );
		if ( ! $license || ! $license->is_valid() || $license->product_id !== $product_id ) {
			RateLimiter::log_request( '/update', 'GET', $license_key, 403, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'error' => 'Invalid license key.',
				),
				403
			);
		}

		// Get product
		$product = Product::find( $product_id );
		if ( ! $product ) {
			RateLimiter::log_request( '/update', 'GET', $license_key, 404, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'error' => 'Product not found.',
				),
				404
			);
		}

		// Check if update is available
		if ( version_compare( $current_version, $product->version, '>=' ) ) {
			$response_time = microtime( true ) - $start_time;
			RateLimiter::log_request( '/update', 'GET', $license_key, 200, $response_time );
			return new WP_REST_Response(
				array(
					'version'     => $current_version,
					'update'      => false,
					'message'     => 'You are running the latest version.',
				),
				200
			);
		}

		$response_time = microtime( true ) - $start_time;
		RateLimiter::log_request( '/update', 'GET', $license_key, 200, $response_time );

		return new WP_REST_Response(
			array(
				'version'      => $product->version,
				'update'       => true,
				'download_url' => $product->download_url,
				'changelog'    => $product->changelog,
			),
			200
		);
	}
}

