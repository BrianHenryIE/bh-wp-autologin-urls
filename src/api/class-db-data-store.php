<?php
/**
 * Uses a custom db table (as distinct from transients) to store the autologin codes.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.1
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/api
 */

namespace BrianHenryIE\WP_Autologin_URLs\api;

use DateTime;

/**
 * Creates a custom database table via standard $wpdb functions to store and retrieve the autologin codes.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 *
 * Class DB_Data_Store
 *
 * @package BH_WP_Autologin_URLs\api
 */
class DB_Data_Store implements Data_Store_Interface {

	/**
	 * The current plugin databse version.
	 *
	 * @var int
	 */
	protected static $db_version = 1;

	/**
	 * Option name in wp_options for checking the database version on install/upgrades.
	 *
	 * @var string
	 */
	public static $db_version_option_name = 'bh-wp-autologin-urls-db-version';

	/**
	 * The full table name of the database table and table name.
	 *
	 * @return string
	 */
	protected function get_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'autologin_urls';
	}

	/**
	 * Create or upgrade the database.
	 *
	 * Check the saved wp_option for the last time the database was created and update accordingly.
	 *
	 * @hooked plugins_loaded
	 */
	public function create_db(): void {

		$current_db_version = get_option( self::$db_version_option_name, 0 );

		if ( ! ( self::$db_version > $current_db_version ) ) {
			return;
		}

		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->get_table_name()} (
		  expires datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  hash varchar(64) NOT NULL,
		  userhash varchar(64) NOT NULL, 
		  PRIMARY KEY  (hash),
		  KEY expires (expires)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		update_option( self::$db_version_option_name, self::$db_version );
	}

	/**
	 * Save an autologin code for a user so it can be checked in future, before the expires_in time.
	 *
	 * @param int    $user_id The user id the code is being saved for.
	 * @param string $code The autologin code being used in the user's URL.
	 * @param int    $expires_in Number of seconds the code is valid for.
	 */
	public function save( int $user_id, string $code, int $expires_in ): void {

		$key = hash( 'sha256', $code );

		// Concatenate $user_id and $password so the database cannot be searched by username.
		$value = hash( 'sha256', $user_id . $code );

		global $wpdb;

		$datetime = new DateTime();
		$datetime->add( new \DateInterval( "PT{$expires_in}S" ) );
		$expires = $datetime->format( 'Y-m-d H:i:s' );

		$result = $wpdb->insert(
			$this->get_table_name(),
			array(
				'expires'  => $expires,
				'hash'     => $key,
				'userhash' => $value,
			)
		);
	}

	/**
	 * Retrieve the value stored for the given autologin code, if it has not expired.
	 *
	 * @param string $code The autologin code in the user's URL.
	 *
	 * @return string|null
	 * @throws \Exception DateTime exception.
	 */
	public function get_value_for_code( string $code ): ?string {

		global $wpdb;

		$key = hash( 'sha256', $code );

		/**
		 * We've no interest in caching, rather, for security we're deleting the entry as soon as it's found.
		 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		 */
		$result = $wpdb->get_row(
			$wpdb->prepare( 'SELECT expires, userhash FROM ' . $this->get_table_name() . ' WHERE hash = %s', $key )
		);

		if ( is_null( $result ) ) {
			return null;
		}

		// Delete the code so it can only be used once (whether valid or not).
		$wpdb->delete(
			$this->get_table_name(),
			array( 'hash' => $key )
		);

		$expires = new DateTime( $result->expires );

		$now = new DateTime();

		if ( $now > $expires ) {
			return null;
		}

		return $result->userhash;
	}
}
