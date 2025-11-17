<?php
/**
 * Activation Model
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Models;

use WP_Licensing\Core\Database;

/**
 * Activation model class
 */
class Activation {

	/**
	 * Activation ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * License ID
	 *
	 * @var int
	 */
	public $license_id;

	/**
	 * Site URL
	 *
	 * @var string
	 */
	public $site_url;

	/**
	 * Site name
	 *
	 * @var string
	 */
	public $site_name;

	/**
	 * IP address
	 *
	 * @var string
	 */
	public $ip_address;

	/**
	 * User agent
	 *
	 * @var string
	 */
	public $user_agent;

	/**
	 * Activated at
	 *
	 * @var string
	 */
	public $activated_at;

	/**
	 * Last check
	 *
	 * @var string
	 */
	public $last_check;

	/**
	 * Status
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Get table name
	 *
	 * @return string
	 */
	private function get_table() {
		$db = Database::get_instance();
		return $db->get_table( 'activations' );
	}

	/**
	 * Find activation by license and site
	 *
	 * @param int    $license_id License ID.
	 * @param string $site_url Site URL.
	 * @return Activation|null
	 */
	public static function find_by_license_and_site( $license_id, $site_url ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'activations' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE license_id = %d AND site_url = %s",
				$license_id,
				$site_url
			)
		);

		if ( ! $row ) {
			return null;
		}

		return self::from_row( $row );
	}

	/**
	 * Find activations by license ID
	 *
	 * @param int $license_id License ID.
	 * @return array
	 */
	public static function find_by_license_id( $license_id ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'activations' );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE license_id = %d ORDER BY activated_at DESC",
				$license_id
			)
		);

		return array_map( array( __CLASS__, 'from_row' ), $results );
	}

	/**
	 * Create activation from database row
	 *
	 * @param object $row Database row.
	 * @return Activation
	 */
	private static function from_row( $row ) {
		$activation = new self();
		$activation->id = (int) $row->id;
		$activation->license_id = (int) $row->license_id;
		$activation->site_url = $row->site_url;
		$activation->site_name = $row->site_name;
		$activation->ip_address = $row->ip_address;
		$activation->user_agent = $row->user_agent;
		$activation->activated_at = $row->activated_at;
		$activation->last_check = $row->last_check;
		$activation->status = $row->status;
		return $activation;
	}

	/**
	 * Save activation
	 *
	 * @return bool|int
	 */
	public function save() {
		global $wpdb;
		$table = $this->get_table();

		$data = array(
			'license_id'  => $this->license_id,
			'site_url'    => esc_url_raw( $this->site_url ),
			'site_name'   => sanitize_text_field( $this->site_name ),
			'ip_address'  => $this->ip_address,
			'user_agent'  => $this->user_agent,
			'last_check'  => current_time( 'mysql' ),
			'status'      => $this->status,
		);

		if ( $this->id ) {
			$result = $wpdb->update(
				$table,
				$data,
				array( 'id' => $this->id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return $result !== false;
		} else {
			$data['activated_at'] = current_time( 'mysql' );
			$result = $wpdb->insert( $table, $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
			if ( $result ) {
				$this->id = $wpdb->insert_id;
				return $this->id;
			}
			return false;
		}
	}

	/**
	 * Delete activation
	 *
	 * @return bool
	 */
	public function delete() {
		if ( ! $this->id ) {
			return false;
		}

		global $wpdb;
		$table = $this->get_table();
		return $wpdb->delete( $table, array( 'id' => $this->id ), array( '%d' ) ) !== false;
	}

	/**
	 * Update last check time
	 *
	 * @return bool
	 */
	public function update_last_check() {
		$this->last_check = current_time( 'mysql' );
		return $this->save();
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'           => $this->id,
			'license_id'   => $this->license_id,
			'site_url'     => $this->site_url,
			'site_name'    => $this->site_name,
			'ip_address'   => $this->ip_address,
			'user_agent'   => $this->user_agent,
			'activated_at' => $this->activated_at,
			'last_check'   => $this->last_check,
			'status'       => $this->status,
		);
	}
}

