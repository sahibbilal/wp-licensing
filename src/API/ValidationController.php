<?php
/**
 * License Validation API Controller
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\API;

use WP_Licensing\Helpers\Email;
use WP_Licensing\Helpers\RateLimiter;
use WP_Licensing\Helpers\Security;
use WP_Licensing\Models\Activation;
use WP_Licensing\Models\License;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Validation controller class
 */
class ValidationController {

	/**
	 * Validate license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function validate( WP_REST_Request $request ) {
		$start_time = microtime( true );

		// Rate limiting
		$ip_address = Security::get_client_ip();
		if ( ! RateLimiter::check( $ip_address, 60, 60 ) ) {
			RateLimiter::log_request( '/validate', 'POST', null, 429, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'Rate limit exceeded. Please try again later.',
				),
				429
			);
		}

		// Get parameters
		$license_key = Security::sanitize_license_key( $request->get_param( 'license_key' ) );
		$site_url = $request->get_param( 'site_url' );
		$product_id = (int) $request->get_param( 'product_id' );

		// Validate inputs
		if ( empty( $license_key ) || empty( $site_url ) || empty( $product_id ) ) {
			RateLimiter::log_request( '/validate', 'POST', $license_key, 400, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'Missing required parameters.',
				),
				400
			);
		}

		if ( ! Security::validate_site_url( $site_url ) ) {
			RateLimiter::log_request( '/validate', 'POST', $license_key, 400, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'Invalid site URL.',
				),
				400
			);
		}

		// Find license
		$license = License::find_by_key( $license_key );

		if ( ! $license ) {
			RateLimiter::log_request( '/validate', 'POST', $license_key, 404, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'License key not found.',
				),
				404
			);
		}

		// Check product match
		if ( $license->product_id !== $product_id ) {
			RateLimiter::log_request( '/validate', 'POST', $license_key, 403, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'License key does not match product.',
				),
				403
			);
		}

		// Check license validity
		if ( ! $license->is_valid() ) {
			RateLimiter::log_request( '/validate', 'POST', $license_key, 403, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'License is not active or has expired.',
				),
				403
			);
		}

		// Check if already activated
		$activation = Activation::find_by_license_and_site( $license->id, $site_url );

		if ( $activation ) {
			// Update last check (existing activation, no email needed)
			$activation->update_last_check();

			$response_time = microtime( true ) - $start_time;
			RateLimiter::log_request( '/validate', 'POST', $license_key, 200, $response_time );

			return new WP_REST_Response(
				array(
					'valid'     => true,
					'message'   => 'License is valid.',
					'expires_at' => $license->expires_at,
					'status'    => $license->status,
				),
				200
			);
		}

		// Check activation limit
		if ( ! $license->can_activate() ) {
			$response_time = microtime( true ) - $start_time;
			RateLimiter::log_request( '/validate', 'POST', $license_key, 403, $response_time );
			return new WP_REST_Response(
				array(
					'valid'   => false,
					'message' => 'Activation limit reached.',
				),
				403
			);
		}

		// Create new activation
		$activation = new Activation();
		$activation->license_id = $license->id;
		$activation->site_url = $site_url;
		$activation->ip_address = Security::get_client_ip();
		$activation->user_agent = Security::get_user_agent();
		$activation->status = 'active';
		$activation->save();

		// Send email notification for new activation
		Email::send_license_activated( $license, $site_url );

		$response_time = microtime( true ) - $start_time;
		RateLimiter::log_request( '/validate', 'POST', $license_key, 200, $response_time );

		return new WP_REST_Response(
			array(
				'valid'      => true,
				'message'    => 'License activated successfully.',
				'expires_at' => $license->expires_at,
				'status'     => $license->status,
			),
			200
		);
	}

	/**
	 * Deactivate license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function deactivate( WP_REST_Request $request ) {
		$start_time = microtime( true );

		// Rate limiting
		$ip_address = Security::get_client_ip();
		if ( ! RateLimiter::check( $ip_address, 30, 60 ) ) {
			RateLimiter::log_request( '/deactivate', 'POST', null, 429, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Rate limit exceeded.',
				),
				429
			);
		}

		$license_key = Security::sanitize_license_key( $request->get_param( 'license_key' ) );
		$site_url = $request->get_param( 'site_url' );

		if ( empty( $license_key ) || empty( $site_url ) ) {
			RateLimiter::log_request( '/deactivate', 'POST', $license_key, 400, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing required parameters.',
				),
				400
			);
		}

		$license = License::find_by_key( $license_key );
		if ( ! $license ) {
			RateLimiter::log_request( '/deactivate', 'POST', $license_key, 404, microtime( true ) - $start_time );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'License key not found.',
				),
				404
			);
		}

		$activation = Activation::find_by_license_and_site( $license->id, $site_url );
		if ( ! $activation ) {
			$response_time = microtime( true ) - $start_time;
			RateLimiter::log_request( '/deactivate', 'POST', $license_key, 404, $response_time );
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Activation not found.',
				),
				404
			);
		}

		$activation->delete();

		$response_time = microtime( true ) - $start_time;
		RateLimiter::log_request( '/deactivate', 'POST', $license_key, 200, $response_time );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'License deactivated successfully.',
			),
			200
		);
	}
}

