<?php
/**
 * Security Helper
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Helpers;

/**
 * Security helper class
 */
class Security {

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	public static function get_client_ip() {
		$ip_keys = array(
			'HTTP_CF_CONNECTING_IP', // Cloudflare
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = $_SERVER[ $key ];
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = explode( ',', $ip )[0];
				}
				$ip = trim( $ip );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get user agent
	 *
	 * @return string
	 */
	public static function get_user_agent() {
		return isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '';
	}

	/**
	 * Verify nonce
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Action name.
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action = 'wp_rest' ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Sanitize license key
	 *
	 * @param string $key License key.
	 * @return string
	 */
	public static function sanitize_license_key( $key ) {
		return preg_replace( '/[^A-Z0-9]/', '', strtoupper( $key ) );
	}

	/**
	 * Validate site URL
	 *
	 * @param string $url Site URL.
	 * @return bool
	 */
	public static function validate_site_url( $url ) {
		$url = esc_url_raw( $url );
		return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
	}
}

