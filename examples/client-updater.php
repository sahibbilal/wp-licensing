<?php
/**
 * Example Client-Side Updater for WordPress Plugins/Themes
 *
 * This is an example implementation that you can use in your commercial
 * WordPress plugins or themes to integrate with the WP Licensing system.
 *
 * @package YourPlugin
 */

/**
 * Class Client_Updater
 */
class Client_Updater {

	/**
	 * License server URL
	 *
	 * @var string
	 */
	private $license_server_url;

	/**
	 * Product ID
	 *
	 * @var int
	 */
	private $product_id;

	/**
	 * Current version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Plugin/Theme slug
	 *
	 * @var string
	 */
	private $slug;

	/**
	 * Constructor
	 *
	 * @param string $license_server_url License server URL.
	 * @param int    $product_id Product ID.
	 * @param string $version Current version.
	 * @param string $slug Plugin/Theme slug.
	 */
	public function __construct( $license_server_url, $product_id, $version, $slug ) {
		$this->license_server_url = trailingslashit( $license_server_url );
		$this->product_id = $product_id;
		$this->version = $version;
		$this->slug = $slug;

		// Hook into WordPress update system
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 10, 3 );
	}

	/**
	 * Get license key from options
	 *
	 * @return string
	 */
	private function get_license_key() {
		return get_option( $this->slug . '_license_key', '' );
	}

	/**
	 * Check for updates
	 *
	 * @param object $transient Update transient.
	 * @return object
	 */
	public function check_for_updates( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$license_key = $this->get_license_key();
		if ( empty( $license_key ) ) {
			return $transient;
		}

		$update_info = $this->get_update_info( $license_key );

		if ( $update_info && isset( $update_info->update ) && $update_info->update ) {
			$transient->response[ $this->slug . '/' . $this->slug . '.php' ] = (object) array(
				'slug'        => $this->slug,
				'new_version' => $update_info->version,
				'package'     => $update_info->download_url,
				'url'         => '',
			);
		}

		return $transient;
	}

	/**
	 * Get update info from license server
	 *
	 * @param string $license_key License key.
	 * @return object|null
	 */
	private function get_update_info( $license_key ) {
		$url = add_query_arg(
			array(
				'license_key' => $license_key,
				'version'     => $this->version,
				'product_id'  => $this->product_id,
			),
			$this->license_server_url . 'wp-json/wp-licensing/v1/update'
		);

		$response = wp_remote_get( $url, array( 'timeout' => 15 ) );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		return $data;
	}

	/**
	 * Plugin info for update screen
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action Action.
	 * @param object             $args Arguments.
	 * @return false|object|array
	 */
	public function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || $args->slug !== $this->slug ) {
			return $result;
		}

		$license_key = $this->get_license_key();
		if ( empty( $license_key ) ) {
			return $result;
		}

		$update_info = $this->get_update_info( $license_key );

		if ( $update_info && isset( $update_info->update ) && $update_info->update ) {
			$result = (object) array(
				'name'          => $this->slug,
				'slug'          => $this->slug,
				'version'       => $update_info->version,
				'download_link' => $update_info->download_url,
				'sections'      => array(
					'changelog' => $update_info->changelog,
				),
			);
		}

		return $result;
	}

	/**
	 * Validate license
	 *
	 * @param string $license_key License key.
	 * @param string $site_url Site URL.
	 * @return array
	 */
	public function validate_license( $license_key, $site_url = null ) {
		if ( null === $site_url ) {
			$site_url = home_url();
		}

		$url = $this->license_server_url . 'wp-json/wp-licensing/v1/validate';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key' => $license_key,
					'site_url'    => $site_url,
					'product_id'  => $this->product_id,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'valid'   => false,
				'message' => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return $data;
	}

	/**
	 * Deactivate license
	 *
	 * @param string $license_key License key.
	 * @param string $site_url Site URL.
	 * @return array
	 */
	public function deactivate_license( $license_key, $site_url = null ) {
		if ( null === $site_url ) {
			$site_url = home_url();
		}

		$url = $this->license_server_url . 'wp-json/wp-licensing/v1/deactivate';

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 15,
				'body'    => array(
					'license_key' => $license_key,
					'site_url'    => $site_url,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return $data;
	}
}

// Example usage:
/*
$updater = new Client_Updater(
	'https://your-license-server.com',  // License server URL
	1,                                  // Product ID
	'1.0.0',                            // Current version
	'your-plugin-slug'                  // Plugin/Theme slug
);

// Validate license on activation
$result = $updater->validate_license( 'YOUR_LICENSE_KEY' );
if ( $result['valid'] ) {
	update_option( 'your_plugin_license_key', 'YOUR_LICENSE_KEY' );
	update_option( 'your_plugin_license_status', 'active' );
}
*/

