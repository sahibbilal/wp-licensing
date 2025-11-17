<?php
/**
 * Rate Limiter Helper
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Helpers;

use WP_Licensing\Core\Database;

/**
 * Rate limiter class
 */
class RateLimiter {

	/**
	 * Check rate limit
	 *
	 * @param string $identifier Identifier (IP or license key).
	 * @param int    $max_requests Maximum requests.
	 * @param int    $time_window Time window in seconds.
	 * @return bool True if allowed, false if rate limited.
	 */
	public static function check( $identifier, $max_requests = 60, $time_window = 60 ) {
		$transient_key = 'wp_licensing_rate_limit_' . md5( $identifier );
		$requests = get_transient( $transient_key );

		if ( false === $requests ) {
			set_transient( $transient_key, 1, $time_window );
			return true;
		}

		if ( $requests >= $max_requests ) {
			return false;
		}

		set_transient( $transient_key, $requests + 1, $time_window );
		return true;
	}

	/**
	 * Log API request
	 *
	 * @param string $endpoint Endpoint.
	 * @param string $method HTTP method.
	 * @param string $license_key License key.
	 * @param int    $response_code Response code.
	 * @param float  $response_time Response time.
	 */
	public static function log_request( $endpoint, $method, $license_key = null, $response_code = null, $response_time = null ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'api_logs' );

		$ip_address = Security::get_client_ip();
		$user_agent = Security::get_user_agent();
		$request_data = wp_json_encode( $_REQUEST );

		$wpdb->insert(
			$table,
			array(
				'endpoint'      => $endpoint,
				'method'        => $method,
				'license_key'   => $license_key,
				'ip_address'    => $ip_address,
				'user_agent'    => $user_agent,
				'request_data'  => $request_data,
				'response_code' => $response_code,
				'response_time' => $response_time,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f' )
		);
	}
}

