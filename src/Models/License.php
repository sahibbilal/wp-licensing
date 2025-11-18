<?php
/**
 * License Model
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Models;

use WP_Licensing\Core\Database;

/**
 * License model class
 */
class License {

	/**
	 * License ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * License key
	 *
	 * @var string
	 */
	public $license_key;

	/**
	 * Product ID
	 *
	 * @var int
	 */
	public $product_id;

	/**
	 * Customer email
	 *
	 * @var string
	 */
	public $customer_email;

	/**
	 * Customer name
	 *
	 * @var string
	 */
	public $customer_name;

	/**
	 * Status (active, inactive, expired, blocked)
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Activation limit
	 *
	 * @var int
	 */
	public $activation_limit;

	/**
	 * Expiration date
	 *
	 * @var string
	 */
	public $expires_at;

	/**
	 * Created at
	 *
	 * @var string
	 */
	public $created_at;

	/**
	 * Updated at
	 *
	 * @var string
	 */
	public $updated_at;

	/**
	 * Get table name
	 *
	 * @return string
	 */
	private function get_table() {
		$db = Database::get_instance();
		return $db->get_table( 'licenses' );
	}

	/**
	 * Generate license key
	 *
	 * @return string
	 */
	public static function generate_key() {
		return strtoupper( wp_generate_password( 32, false ) );
	}

	/**
	 * Find license by key
	 *
	 * @param string $license_key License key.
	 * @return License|null
	 */
	public static function find_by_key( $license_key ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'licenses' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE license_key = %s",
				$license_key
			)
		);

		if ( ! $row ) {
			return null;
		}

		return self::from_row( $row );
	}

	/**
	 * Find license by ID
	 *
	 * @param int $id License ID.
	 * @return License|null
	 */
	public static function find( $id ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'licenses' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE id = %d",
				$id
			)
		);

		if ( ! $row ) {
			return null;
		}

		return self::from_row( $row );
	}

	/**
	 * Create license from database row
	 *
	 * @param object $row Database row.
	 * @return License
	 */
	public static function from_row( $row ) {
		$license = new self();
		$license->id = (int) $row->id;
		$license->license_key = $row->license_key;
		$license->product_id = (int) $row->product_id;
		$license->customer_email = $row->customer_email;
		$license->customer_name = $row->customer_name;
		$license->status = $row->status;
		$license->activation_limit = (int) $row->activation_limit;
		// Normalize invalid MySQL zero date to null
		$license->expires_at = ( $row->expires_at && $row->expires_at !== '0000-00-00 00:00:00' ) ? $row->expires_at : null;
		$license->created_at = $row->created_at;
		$license->updated_at = $row->updated_at;
		return $license;
	}

	/**
	 * Save license
	 *
	 * @return bool|int
	 */
	public function save() {
		global $wpdb;
		$table = $this->get_table();

		// Normalize expires_at: empty string, null, or invalid date should be NULL
		$expires_at = $this->expires_at;
		if ( empty( $expires_at ) || $expires_at === '0000-00-00 00:00:00' || trim( $expires_at ) === '' ) {
			$expires_at = null;
		} else {
			// Validate date format
			$timestamp = strtotime( $expires_at );
			if ( $timestamp === false ) {
				$expires_at = null;
			}
		}

		$data = array(
			'license_key'    => $this->license_key,
			'product_id'     => $this->product_id,
			'customer_email' => sanitize_email( $this->customer_email ),
			'customer_name'  => sanitize_text_field( $this->customer_name ),
			'status'         => $this->status,
			'activation_limit' => $this->activation_limit,
		);

		// Handle expires_at separately for NULL values using direct SQL
		if ( $this->id ) {
			// Update existing license
			if ( $expires_at === null ) {
				// Use direct SQL for NULL value
				$result = $wpdb->query(
					$wpdb->prepare(
						"UPDATE $table SET 
							license_key = %s,
							product_id = %d,
							customer_email = %s,
							customer_name = %s,
							status = %s,
							activation_limit = %d,
							expires_at = NULL
						WHERE id = %d",
						$data['license_key'],
						$data['product_id'],
						$data['customer_email'],
						$data['customer_name'],
						$data['status'],
						$data['activation_limit'],
						$this->id
					)
				);
			} else {
				$data['expires_at'] = $expires_at;
				$result = $wpdb->update(
					$table,
					$data,
					array( 'id' => $this->id ),
					array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' ),
					array( '%d' )
				);
			}
			return $result !== false;
		} else {
			// Insert new license
			if ( ! $this->license_key ) {
				$this->license_key = self::generate_key();
				$data['license_key'] = $this->license_key;
			}
			
			if ( $expires_at === null ) {
				// Use direct SQL for NULL value
				$result = $wpdb->query(
					$wpdb->prepare(
						"INSERT INTO $table 
							(license_key, product_id, customer_email, customer_name, status, activation_limit, expires_at)
						VALUES (%s, %d, %s, %s, %s, %d, NULL)",
						$data['license_key'],
						$data['product_id'],
						$data['customer_email'],
						$data['customer_name'],
						$data['status'],
						$data['activation_limit']
					)
				);
				if ( $result ) {
					$this->id = $wpdb->insert_id;
					return $this->id;
				}
			} else {
				$data['expires_at'] = $expires_at;
				$result = $wpdb->insert( $table, $data, array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' ) );
				if ( $result ) {
					$this->id = $wpdb->insert_id;
					return $this->id;
				}
			}
			return false;
		}
	}

	/**
	 * Delete license
	 *
	 * @return bool
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table = $this->get_table();

		// Delete associated activations
		$activation_table = Database::get_instance()->get_table( 'activations' );
		$wpdb->delete( $activation_table, array( 'license_id' => $this->id ), array( '%d' ) );

		// Delete license
		return $wpdb->delete( $table, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}

	/**
	 * Get activation count
	 *
	 * @return int
	 */
	public function get_activation_count() {
		global $wpdb;
		$table = Database::get_instance()->get_table( 'activations' );

		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $table WHERE license_id = %d AND status = 'active'",
				$this->id
			)
		);

		return (int) $count;
	}

	/**
	 * Check if license is valid
	 *
	 * @return bool
	 */
	public function is_valid() {
		if ( $this->status !== 'active' ) {
			return false;
		}

		if ( $this->expires_at && strtotime( $this->expires_at ) < time() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if license can be activated
	 *
	 * @return bool
	 */
	public function can_activate() {
		if ( ! $this->is_valid() ) {
			return false;
		}

		$activation_count = $this->get_activation_count();
		return $activation_count < $this->activation_limit;
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'              => $this->id,
			'license_key'    => $this->license_key,
			'product_id'     => $this->product_id,
			'customer_email' => $this->customer_email,
			'customer_name'  => $this->customer_name,
			'status'          => $this->status,
			'activation_limit' => $this->activation_limit,
			'activations'     => $this->get_activation_count(),
			'expires_at'      => $this->expires_at,
			'created_at'      => $this->created_at,
			'updated_at'      => $this->updated_at,
		);
	}
}

