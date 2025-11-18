<?php
/**
 * Email Helper
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Helpers;

use WP_Licensing\Models\License;
use WP_Licensing\Models\Product;

/**
 * Email helper class
 */
class Email {

	/**
	 * Send license creation email
	 *
	 * @param License $license License object.
	 * @return bool
	 */
	public static function send_license_created( License $license ) {
		$product = Product::find( $license->product_id );
		$product_name = $product ? $product->name : 'Product #' . $license->product_id;

		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'Your License Key for %s', 'wp-licensing' ),
			$product_name
		);

		$message = self::get_license_created_template( $license, $product_name );

		return self::send_email( $license->customer_email, $subject, $message );
	}

	/**
	 * Send license activation email
	 *
	 * @param License $license License object.
	 * @param string  $site_url Site URL where license was activated.
	 * @return bool
	 */
	public static function send_license_activated( License $license, $site_url ) {
		$product = Product::find( $license->product_id );
		$product_name = $product ? $product->name : 'Product #' . $license->product_id;

		$subject = sprintf(
			/* translators: %s: Product name */
			__( 'License Activated for %s', 'wp-licensing' ),
			$product_name
		);

		$message = self::get_license_activated_template( $license, $product_name, $site_url );

		return self::send_email( $license->customer_email, $subject, $message );
	}

	/**
	 * Get license created email template
	 *
	 * @param License $license License object.
	 * @param string  $product_name Product name.
	 * @return string
	 */
	private static function get_license_created_template( License $license, $product_name ) {
		$site_name = get_bloginfo( 'name' );
		$site_url = home_url();

		$message = sprintf(
			/* translators: %s: Site name */
			__( 'Hello %s,', 'wp-licensing' ) . "\n\n",
			$license->customer_name ?: $license->customer_email
		);

		$message .= sprintf(
			/* translators: %1$s: Product name, %2$s: Site name */
			__( 'Thank you for your purchase! Your license key for %1$s has been created on %2$s.', 'wp-licensing' ) . "\n\n",
			$product_name,
			$site_name
		);

		$message .= __( 'License Details:', 'wp-licensing' ) . "\n";
		$message .= __( 'License Key:', 'wp-licensing' ) . ' ' . $license->license_key . "\n";
		$message .= __( 'Product:', 'wp-licensing' ) . ' ' . $product_name . "\n";
		$message .= __( 'Activation Limit:', 'wp-licensing' ) . ' ' . $license->activation_limit . "\n";

		if ( $license->expires_at ) {
			$message .= __( 'Expires:', 'wp-licensing' ) . ' ' . date_i18n( get_option( 'date_format' ), strtotime( $license->expires_at ) ) . "\n";
		}

		$message .= "\n" . __( 'You can use this license key to activate your product on your website.', 'wp-licensing' ) . "\n\n";
		$message .= sprintf(
			/* translators: %s: Site URL */
			__( 'If you have any questions, please contact us at %s', 'wp-licensing' ) . "\n",
			$site_url
		);

		return $message;
	}

	/**
	 * Get license activated email template
	 *
	 * @param License $license License object.
	 * @param string  $product_name Product name.
	 * @param string  $site_url Site URL.
	 * @return string
	 */
	private static function get_license_activated_template( License $license, $product_name, $site_url ) {
		$site_name = get_bloginfo( 'name' );

		$message = sprintf(
			/* translators: %s: Customer name or email */
			__( 'Hello %s,', 'wp-licensing' ) . "\n\n",
			$license->customer_name ?: $license->customer_email
		);

		$message .= sprintf(
			/* translators: %1$s: Product name, %2$s: Site URL */
			__( 'Your license for %1$s has been successfully activated on %2$s.', 'wp-licensing' ) . "\n\n",
			$product_name,
			$site_url
		);

		$message .= __( 'License Details:', 'wp-licensing' ) . "\n";
		$message .= __( 'License Key:', 'wp-licensing' ) . ' ' . $license->license_key . "\n";
		$message .= __( 'Activated Site:', 'wp-licensing' ) . ' ' . $site_url . "\n";
		$message .= __( 'Remaining Activations:', 'wp-licensing' ) . ' ' . ( $license->activation_limit - $license->get_activation_count() ) . "\n";

		$message .= "\n" . __( 'If you did not authorize this activation, please contact us immediately.', 'wp-licensing' ) . "\n";

		return $message;
	}

	/**
	 * Send email
	 *
	 * @param string $to Email address.
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 * @return bool
	 */
	private static function send_email( $to, $subject, $message ) {
		if ( ! is_email( $to ) ) {
			return false;
		}

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
		);

		$sent = wp_mail( $to, $subject, $message, $headers );

		// Log email sending
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf( 'WP Licensing: Email sent to %s - %s', $to, $sent ? 'Success' : 'Failed' ) );
		}

		return $sent;
	}
}

