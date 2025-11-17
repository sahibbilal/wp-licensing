<?php
/**
 * Database Migration Class
 *
 * @package WP_Licensing
 */

namespace WP_Licensing\Core;

/**
 * Database class
 */
class Database {

	/**
	 * Database instance
	 *
	 * @var Database
	 */
	private static $instance = null;

	/**
	 * Get database instance
	 *
	 * @return Database
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get table prefix
	 *
	 * @return string
	 */
	private function get_table_prefix() {
		global $wpdb;
		return $wpdb->prefix . 'wplic_';
	}

	/**
	 * Create all tables
	 */
	public function create_tables() {
		$this->create_licenses_table();
		$this->create_activations_table();
		$this->create_products_table();
		$this->create_api_logs_table();
	}

	/**
	 * Create licenses table
	 */
	private function create_licenses_table() {
		global $wpdb;
		$table_name = $this->get_table_prefix() . 'licenses';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			license_key varchar(255) NOT NULL,
			product_id bigint(20) UNSIGNED NOT NULL,
			customer_email varchar(255) NOT NULL,
			customer_name varchar(255) DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			activation_limit int(11) DEFAULT 1,
			expires_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY license_key (license_key),
			KEY product_id (product_id),
			KEY status (status),
			KEY customer_email (customer_email)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create activations table
	 */
	private function create_activations_table() {
		global $wpdb;
		$table_name = $this->get_table_prefix() . 'activations';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			license_id bigint(20) UNSIGNED NOT NULL,
			site_url varchar(255) NOT NULL,
			site_name varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text,
			activated_at datetime DEFAULT CURRENT_TIMESTAMP,
			last_check datetime DEFAULT CURRENT_TIMESTAMP,
			status varchar(20) DEFAULT 'active',
			PRIMARY KEY (id),
			KEY license_id (license_id),
			KEY site_url (site_url),
			KEY status (status),
			UNIQUE KEY unique_activation (license_id, site_url)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create products table
	 */
	private function create_products_table() {
		global $wpdb;
		$table_name = $this->get_table_prefix() . 'products';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			slug varchar(255) NOT NULL,
			version varchar(50) DEFAULT '1.0.0',
			download_url text,
			changelog text,
			description text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY slug (slug)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create API logs table
	 */
	private function create_api_logs_table() {
		global $wpdb;
		$table_name = $this->get_table_prefix() . 'api_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			endpoint varchar(255) NOT NULL,
			method varchar(10) NOT NULL,
			license_key varchar(255) DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			user_agent text,
			request_data text,
			response_code int(11) DEFAULT NULL,
			response_time float DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY endpoint (endpoint),
			KEY license_key (license_key),
			KEY ip_address (ip_address),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get table name
	 *
	 * @param string $table Table name without prefix.
	 * @return string
	 */
	public function get_table( $table ) {
		return $this->get_table_prefix() . $table;
	}
}

