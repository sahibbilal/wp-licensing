<?php
/**
 * Product Model
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Models;

use WP_Licensing\Core\Database;

/**
 * Product model class
 */
class Product {

	/**
	 * Product ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Product name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Product slug
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Version
	 *
	 * @var string
	 */
	public $version;

	/**
	 * Download URL
	 *
	 * @var string
	 */
	public $download_url;

	/**
	 * Changelog
	 *
	 * @var string
	 */
	public $changelog;

	/**
	 * Description
	 *
	 * @var string
	 */
	public $description;

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
		return $db->get_table( 'products' );
	}

	/**
	 * Find product by ID
	 *
	 * @param int $id Product ID.
	 * @return Product|null
	 */
	public static function find( $id ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'products' );

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
	 * Find product by slug
	 *
	 * @param string $slug Product slug.
	 * @return Product|null
	 */
	public static function find_by_slug( $slug ) {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'products' );

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $table WHERE slug = %s",
				$slug
			)
		);

		if ( ! $row ) {
			return null;
		}

		return self::from_row( $row );
	}

	/**
	 * Get all products
	 *
	 * @return array
	 */
	public static function all() {
		global $wpdb;
		$db = Database::get_instance();
		$table = $db->get_table( 'products' );

		$results = $wpdb->get_results( "SELECT * FROM $table ORDER BY name ASC" );

		return array_map( array( __CLASS__, 'from_row' ), $results );
	}

	/**
	 * Create product from database row
	 *
	 * @param object $row Database row.
	 * @return Product
	 */
	private static function from_row( $row ) {
		$product = new self();
		$product->id = (int) $row->id;
		$product->name = $row->name;
		$product->slug = $row->slug;
		$product->version = $row->version;
		$product->download_url = $row->download_url;
		$product->changelog = $row->changelog;
		$product->description = $row->description;
		$product->created_at = $row->created_at;
		$product->updated_at = $row->updated_at;
		return $product;
	}

	/**
	 * Save product
	 *
	 * @return bool|int
	 */
	public function save() {
		global $wpdb;
		$table = $this->get_table();

		if ( ! $this->slug ) {
			$this->slug = sanitize_title( $this->name );
		}

		$data = array(
			'name'         => sanitize_text_field( $this->name ),
			'slug'         => sanitize_title( $this->slug ),
			'version'      => sanitize_text_field( $this->version ),
			'download_url' => esc_url_raw( $this->download_url ),
			'changelog'    => wp_kses_post( $this->changelog ),
			'description'  => wp_kses_post( $this->description ),
		);

		if ( $this->id ) {
			$result = $wpdb->update(
				$table,
				$data,
				array( 'id' => $this->id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			return $result !== false;
		} else {
			$result = $wpdb->insert( $table, $data, array( '%s', '%s', '%s', '%s', '%s', '%s' ) );
			if ( $result ) {
				$this->id = $wpdb->insert_id;
				return $this->id;
			}
			return false;
		}
	}

	/**
	 * Delete product
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
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'id'           => $this->id,
			'name'         => $this->name,
			'slug'         => $this->slug,
			'version'      => $this->version,
			'download_url' => $this->download_url,
			'changelog'    => $this->changelog,
			'description'  => $this->description,
			'created_at'   => $this->created_at,
			'updated_at'   => $this->updated_at,
		);
	}
}

