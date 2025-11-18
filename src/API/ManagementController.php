<?php
/**
 * License Management API Controller
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\API;

use WP_Licensing\Core\Database;
use WP_Licensing\Helpers\Email;
use WP_Licensing\Models\Activation;
use WP_Licensing\Models\License;
use WP_Licensing\Models\Product;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Management controller class
 */
class ManagementController {

	/**
	 * Get licenses
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_licenses( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'licenses' );

		$page = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset = ( $page - 1 ) * $per_page;

		$status = $request->get_param( 'status' );
		$product_id = $request->get_param( 'product_id' );
		$search = $request->get_param( 'search' );

		$where = array( '1=1' );
		$where_values = array();

		if ( $status ) {
			$where[] = 'status = %s';
			$where_values[] = $status;
		}

		if ( $product_id ) {
			$where[] = 'product_id = %d';
			$where_values[] = (int) $product_id;
		}

		if ( $search ) {
			$where[] = '(license_key LIKE %s OR customer_email LIKE %s OR customer_name LIKE %s)';
			$search_term = '%' . $wpdb->esc_like( $search ) . '%';
			$where_values[] = $search_term;
			$where_values[] = $search_term;
			$where_values[] = $search_term;
		}

		$where_clause = implode( ' AND ', $where );

		// Build query safely
		$query = "SELECT * FROM $table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$total_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";

		if ( ! empty( $where_values ) ) {
			$query = $wpdb->prepare(
				$query,
				array_merge( $where_values, array( $per_page, $offset ) )
			);
			$total_query = $wpdb->prepare( $total_query, $where_values );
		} else {
			$query = $wpdb->prepare( $query, $per_page, $offset );
		}

		$licenses = $wpdb->get_results( $query );
		$total = ! empty( $where_values ) ? $wpdb->get_var( $total_query ) : $wpdb->get_var( "SELECT COUNT(*) FROM $table" );

		$results = array();
		foreach ( $licenses as $row ) {
			$license = License::from_row( $row );
			$results[] = $license->to_array();
		}

		return new WP_REST_Response(
			array(
				'licenses' => $results,
				'total'    => (int) $total,
				'page'     => $page,
				'per_page' => $per_page,
			),
			200
		);
	}

	/**
	 * Create license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_license( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$license = new License();
		$license->product_id = (int) $request->get_param( 'product_id' );
		$license->customer_email = sanitize_email( $request->get_param( 'customer_email' ) );
		$license->customer_name = sanitize_text_field( $request->get_param( 'customer_name' ) );
		$license->status = sanitize_text_field( $request->get_param( 'status' ) ) ?: 'active';
		$license->activation_limit = max( 1, (int) $request->get_param( 'activation_limit' ) ) ?: 1;
		$expires_at = $request->get_param( 'expires_at' );

		
		
		// If no expiry date provided, use setting to calculate expiry
		if ( empty( $expires_at ) || $expires_at === '0000-00-00 00:00:00' || trim( $expires_at ) === '' ) {
			// Get license expiry days from settings
			$settings = get_option( 'wp_licensing_settings', array() );
			$expiry_days = isset( $settings['license_expiry_days'] ) ? absint( $settings['license_expiry_days'] ) : 365;
			
			if ( $expiry_days > 0 ) {
				// Calculate expiry date: current date + expiry_days
				$expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$expiry_days} days" ) );
				$license->expires_at = $expires_at;
			} else {
				// If expiry_days is 0, license never expires (NULL)
				$license->expires_at = null;
			}
		} else {
			$license->expires_at = $expires_at;
		}

		if ( ! $license->product_id || ! $license->customer_email ) {
			return new WP_REST_Response(
				array( 'error' => 'Missing required fields.' ),
				400
			);
		}

		$result = $license->save();
		if ( $result ) {
			// Send email notification
			Email::send_license_created( $license );

			return new WP_REST_Response( $license->to_array(), 201 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to create license.' ), 500 );
	}

	/**
	 * Update license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_license( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$id = (int) $request->get_param( 'id' );
		$license = License::find( $id );

		if ( ! $license ) {
			return new WP_REST_Response( array( 'error' => 'License not found.' ), 404 );
		}

		if ( $request->has_param( 'customer_email' ) ) {
			$license->customer_email = sanitize_email( $request->get_param( 'customer_email' ) );
		}
		if ( $request->has_param( 'customer_name' ) ) {
			$license->customer_name = sanitize_text_field( $request->get_param( 'customer_name' ) );
		}
		if ( $request->has_param( 'status' ) ) {
			$license->status = sanitize_text_field( $request->get_param( 'status' ) );
		}
		if ( $request->has_param( 'activation_limit' ) ) {
			$license->activation_limit = max( 1, (int) $request->get_param( 'activation_limit' ) );
		}
		if ( $request->has_param( 'expires_at' ) ) {
			$expires_at = $request->get_param( 'expires_at' );
			// Empty string means never expire (set to null)
			$license->expires_at = ( $expires_at === '' || $expires_at === null ) ? null : $expires_at;
		}

		$result = $license->save();
		if ( $result ) {
			return new WP_REST_Response( $license->to_array(), 200 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to update license.' ), 500 );
	}

	/**
	 * Delete license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function delete_license( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$id = (int) $request->get_param( 'id' );
		$license = License::find( $id );

		if ( ! $license ) {
			return new WP_REST_Response( array( 'error' => 'License not found.' ), 404 );
		}

		$result = $license->delete();
		if ( $result ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to delete license.' ), 500 );
	}

	/**
	 * Get activations for a license
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_activations( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$license_id = (int) $request->get_param( 'license_id' );
		$activations = Activation::find_by_license_id( $license_id );

		$results = array();
		foreach ( $activations as $activation ) {
			$results[] = $activation->to_array();
		}

		return new WP_REST_Response( array( 'activations' => $results ), 200 );
	}

	/**
	 * Get products
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_products( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$products = Product::all();
		$results = array();
		foreach ( $products as $product ) {
			$results[] = $product->to_array();
		}

		return new WP_REST_Response( array( 'products' => $results ), 200 );
	}

	/**
	 * Create product
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function create_product( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$product = new Product();
		$product->name = sanitize_text_field( $request->get_param( 'name' ) );
		$product->slug = sanitize_title( $request->get_param( 'slug' ) );
		$product->version = sanitize_text_field( $request->get_param( 'version' ) ) ?: '1.0.0';
		$product->changelog = wp_kses_post( $request->get_param( 'changelog' ) );
		$product->description = wp_kses_post( $request->get_param( 'description' ) );

		if ( ! $product->name ) {
			return new WP_REST_Response( array( 'error' => 'Product name is required.' ), 400 );
		}

		// Handle file upload if provided
		$download_url = esc_url_raw( $request->get_param( 'download_url' ) );
		
		// Check if a file was uploaded
		if ( ! empty( $_FILES['plugin_file'] ) && $_FILES['plugin_file']['error'] === UPLOAD_ERR_OK ) {
			$upload_result = $this->handle_plugin_upload( $_FILES['plugin_file'], $product->slug, $product->version );
			
			if ( is_wp_error( $upload_result ) ) {
				return new WP_REST_Response( 
					array( 'error' => $upload_result->get_error_message() ), 
					400 
				);
			}
			
			$download_url = $upload_result['url'];
		}
		
		$product->download_url = $download_url;

		$result = $product->save();
		if ( $result ) {
			return new WP_REST_Response( $product->to_array(), 201 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to create product.' ), 500 );
	}

	/**
	 * Update product
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function update_product( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$id = (int) $request->get_param( 'id' );
		$product = Product::find( $id );

		if ( ! $product ) {
			return new WP_REST_Response( array( 'error' => 'Product not found.' ), 404 );
		}

		// Get updatable parameters (name and slug are NOT updatable)
		// Check for file upload - $_FILES only works with POST, not PUT
		$has_file_upload = false;
		$uploaded_file = null;
		
		// Check $_FILES (works for POST requests with file uploads)
		if ( ! empty( $_FILES['plugin_file'] ) && $_FILES['plugin_file']['error'] === UPLOAD_ERR_OK ) {
			$has_file_upload = true;
			$uploaded_file = $_FILES['plugin_file'];
		}
		
		if ( $has_file_upload ) {
			// For FormData requests, get parameters from $_POST or body_params
			$body_params = $request->get_body_params();
			$version = isset( $_POST['version'] ) ? sanitize_text_field( $_POST['version'] ) : ( isset( $body_params['version'] ) ? sanitize_text_field( $body_params['version'] ) : null );
			$changelog = isset( $_POST['changelog'] ) ? $_POST['changelog'] : ( isset( $body_params['changelog'] ) ? $body_params['changelog'] : null );
			$description = isset( $_POST['description'] ) ? $_POST['description'] : ( isset( $body_params['description'] ) ? $body_params['description'] : null );
			$download_url_param = isset( $_POST['download_url'] ) ? $_POST['download_url'] : ( isset( $body_params['download_url'] ) ? $body_params['download_url'] : null );
		} else {
			// For JSON requests, use get_param()
			$version = $request->get_param( 'version' );
			$changelog = $request->get_param( 'changelog' );
			$description = $request->get_param( 'description' );
			$download_url_param = $request->get_param( 'download_url' );
		}

		// Update version if provided
		if ( $version !== null && $version !== '' ) {
			$product->version = sanitize_text_field( $version );
		}
		
		// Update changelog if provided (even if empty)
		if ( $has_file_upload ) {
			// For FormData, always update if key exists in $_POST
			if ( isset( $_POST['changelog'] ) ) {
				$product->changelog = wp_kses_post( $changelog );
			}
		} else {
			// For JSON, check if parameter was sent
			if ( $request->has_param( 'changelog' ) ) {
				$product->changelog = wp_kses_post( $changelog );
			}
		}
		
		// Update description if provided (even if empty)
		if ( $has_file_upload ) {
			// For FormData, always update if key exists in $_POST
			if ( isset( $_POST['description'] ) ) {
				$product->description = wp_kses_post( $description );
			}
		} else {
			// For JSON, check if parameter was sent
			if ( $request->has_param( 'description' ) ) {
				$product->description = wp_kses_post( $description );
			}
		}

		// Handle file upload if provided
		$download_url = $product->download_url;
		
		if ( $download_url_param !== null && $download_url_param !== '' ) {
			$download_url = esc_url_raw( $download_url_param );
		}
		
		// Check if a new file was uploaded
		if ( $has_file_upload && $uploaded_file ) {
			// Get version for filename (use updated version if provided, otherwise current)
			$file_version = ( $version !== null && $version !== '' ) ? $version : $product->version;
			$upload_result = $this->handle_plugin_upload( $uploaded_file, $product->slug, $file_version );
			
			if ( is_wp_error( $upload_result ) ) {
				return new WP_REST_Response( 
					array( 'error' => $upload_result->get_error_message() ), 
					400 
				);
			}
			
			$download_url = $upload_result['url'];
		}
		
		$product->download_url = $download_url;

		$result = $product->save();
		if ( $result ) {
			return new WP_REST_Response( $product->to_array(), 200 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to update product.' ), 500 );
	}

	/**
	 * Delete product
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function delete_product( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$id = (int) $request->get_param( 'id' );
		$product = Product::find( $id );

		if ( ! $product ) {
			return new WP_REST_Response( array( 'error' => 'Product not found.' ), 404 );
		}

		$result = $product->delete();
		if ( $result ) {
			return new WP_REST_Response( array( 'success' => true ), 200 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to delete product.' ), 500 );
	}

	/**
	 * Handle plugin file upload
	 *
	 * @param array  $file Uploaded file array from $_FILES.
	 * @param string $product_slug Product slug for naming.
	 * @param string $version Product version to include in filename.
	 * @return array|WP_Error Upload result with 'file' and 'url' keys, or WP_Error on failure.
	 */
	private function handle_plugin_upload( $file, $product_slug = '', $version = '' ) {
		// Validate file type
		$file_type = wp_check_filetype( $file['name'] );
		if ( 'zip' !== $file_type['ext'] && 'application/zip' !== $file['type'] ) {
			return new \WP_Error( 'invalid_file_type', 'Only ZIP files are allowed.' );
		}

		// Validate file size - get from settings
		$settings = get_option( 'wp_licensing_settings', array() );
		$max_upload_size_mb = isset( $settings['max_upload_size'] ) ? absint( $settings['max_upload_size'] ) : 50;
		$max_size = $max_upload_size_mb * 1024 * 1024; // Convert MB to bytes
		
		if ( $file['size'] > $max_size ) {
			return new \WP_Error( 
				'file_too_large', 
				sprintf( 'File size exceeds %dMB limit.', $max_upload_size_mb )
			);
		}

		// Create upload directory structure: wp-content/uploads/wp-licensing/
		$upload_dir = wp_upload_dir();
		$licensing_dir = $upload_dir['basedir'] . '/wp-licensing';
		
		if ( ! file_exists( $licensing_dir ) ) {
			wp_mkdir_p( $licensing_dir );
		}

		// Generate filename with version: product-slug-version.zip
		$original_filename = sanitize_file_name( $file['name'] );
		$filename_parts = pathinfo( $original_filename );
		$base_name = $filename_parts['filename'];
		$extension = isset( $filename_parts['extension'] ) ? '.' . $filename_parts['extension'] : '.zip';
		
		// Build filename: product-slug-version.zip
		$filename = '';
		if ( $product_slug ) {
			$filename = sanitize_file_name( $product_slug );
		}
		if ( $version ) {
			$version_clean = sanitize_file_name( $version );
			$filename .= ( $filename ? '-' : '' ) . $version_clean;
		}
		// If no slug/version, use original filename base
		if ( ! $filename ) {
			$filename = $base_name;
		}
		$filename .= $extension;
		
		// Ensure unique filename (add number if exists, but keep version in name)
		$filename = wp_unique_filename( $licensing_dir, $filename );

		// Move uploaded file
		$destination = $licensing_dir . '/' . $filename;
		
		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			return new \WP_Error( 'upload_failed', 'Failed to move uploaded file.' );
		}

		// Set correct file permissions
		chmod( $destination, 0644 );

		// Generate URL
		$url = $upload_dir['baseurl'] . '/wp-licensing/' . $filename;

		return array(
			'file' => $destination,
			'url'  => $url,
			'type' => $file_type['type'],
		);
	}

	/**
	 * Get dashboard stats
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_stats( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		global $wpdb;
		$db = Database::get_instance();
		$licenses_table = $db->get_table( 'licenses' );
		$activations_table = $db->get_table( 'activations' );
		$products_table = $db->get_table( 'products' );

		$stats = array(
			'total_licenses'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $licenses_table" ),
			'active_licenses'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $licenses_table WHERE status = 'active'" ),
			'total_activations' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $activations_table WHERE status = 'active'" ),
			'total_products'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $products_table" ),
		);

		return new WP_REST_Response( $stats, 200 );
	}

	/**
	 * Get settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$defaults = array(
			'max_upload_size'        => 50, // MB
			'license_expiry_days'    => 365,
			'max_activations'        => 5,
			'enable_auto_updates'    => true,
			'update_check_interval' => 12, // hours
		);

		$settings = get_option( 'wp_licensing_settings', $defaults );
		
		// Merge with defaults to ensure all keys exist
		$settings = wp_parse_args( $settings, $defaults );

		return new WP_REST_Response( array( 'settings' => $settings ), 200 );
	}

	/**
	 * Save settings
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_settings( WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 403 );
		}

		$settings = array(
			'max_upload_size'        => absint( $request->get_param( 'max_upload_size' ) ) ?: 50,
			'license_expiry_days'    => absint( $request->get_param( 'license_expiry_days' ) ) ?: 365,
			'max_activations'        => absint( $request->get_param( 'max_activations' ) ) ?: 5,
			'enable_auto_updates'    => (bool) $request->get_param( 'enable_auto_updates' ),
			'update_check_interval'  => absint( $request->get_param( 'update_check_interval' ) ) ?: 12,
		);

		// Validate ranges
		$settings['max_upload_size'] = min( max( $settings['max_upload_size'], 1 ), 1000 );
		$settings['license_expiry_days'] = min( max( $settings['license_expiry_days'], 1 ), 3650 );
		$settings['max_activations'] = min( max( $settings['max_activations'], 1 ), 100 );
		$settings['update_check_interval'] = min( max( $settings['update_check_interval'], 1 ), 168 );

		$result = update_option( 'wp_licensing_settings', $settings );

		if ( $result ) {
			return new WP_REST_Response( array( 'success' => true, 'settings' => $settings ), 200 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to save settings.' ), 500 );
	}
}

