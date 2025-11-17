<?php
/**
 * License Management API Controller
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\API;

use WP_Licensing\Core\Database;
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
		$license->expires_at = $request->get_param( 'expires_at' );

		if ( ! $license->product_id || ! $license->customer_email ) {
			return new WP_REST_Response(
				array( 'error' => 'Missing required fields.' ),
				400
			);
		}

		$result = $license->save();
		if ( $result ) {
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
			$license->expires_at = $request->get_param( 'expires_at' );
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
		$product->download_url = esc_url_raw( $request->get_param( 'download_url' ) );
		$product->changelog = wp_kses_post( $request->get_param( 'changelog' ) );
		$product->description = wp_kses_post( $request->get_param( 'description' ) );

		if ( ! $product->name ) {
			return new WP_REST_Response( array( 'error' => 'Product name is required.' ), 400 );
		}

		$result = $product->save();
		if ( $result ) {
			return new WP_REST_Response( $product->to_array(), 201 );
		}

		return new WP_REST_Response( array( 'error' => 'Failed to create product.' ), 500 );
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
}

