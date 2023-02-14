<?php
/**
 * Uses a custom db table (as distinct from transients) to store the autologin codes.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.1
 *
 * @package    brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API\Data_Stores;

use BrianHenryIE\WP_Autologin_URLs\API\Data_Store_Interface;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Creates a custom database table via standard $wpdb functions to store and retrieve the autologin codes.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 */
class DB_Data_Store implements Data_Store_Interface {
	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( LoggerInterface $logger ) {
		$this->setLogger( $logger );

		add_action( 'plugins_loaded', array( $this, 'create_db' ), 1 );
	}

	/**
	 * The current plugin database version.
	 *
	 * @var int
	 */
	protected static $db_version = 1;

	/**
	 * Option name in wp_options for checking the database version on install/upgrades.
	 *
	 * @var string
	 */
	public static $db_version_option_name = 'bh_wp_autologin_urls_db_version';

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

		$table_name      = $wpdb->prefix . 'autologin_urls';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
		  expires_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  hash varchar(64) NOT NULL,
		  userhash varchar(64) NOT NULL, 
		  PRIMARY KEY  (hash),
		  KEY expires_at (expires_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		update_option( self::$db_version_option_name, self::$db_version );

		$this->logger->info( 'Updated database to version ' . self::$db_version );
	}

	/**
	 * Save an autologin code for a user so it can be checked in future, before the expires_in time.
	 *
	 * @param int    $user_id The user id the code is being saved for.
	 * @param string $code The autologin code being used in the user's URL.
	 * @param int    $expires_in Number of seconds the code is valid for.
	 *
	 * @throws Exception DateTime exception.
	 * @throws Exception For `$wpdb->last_error`.
	 */
	public function save( int $user_id, string $code, int $expires_in ): void {

		$key = hash( 'sha256', $code );

		// Concatenate $user_id and $password so the database cannot be searched by username.
		$value = hash( 'sha256', $user_id . $code );

		global $wpdb;

		$datetime = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$datetime->add( new DateInterval( "PT{$expires_in}S" ) );
		$expires_at = $datetime->format( 'Y-m-d H:i:s' );

		$result = $wpdb->insert(
			$wpdb->prefix . 'autologin_urls',
			array(
				'expires_at' => $expires_at,
				'hash'       => $key,
				'userhash'   => $value,
			)
		);

		if ( ! empty( $wpdb->last_error ) ) {
			$this->logger->error( $wpdb->last_error );
			throw new Exception( $wpdb->last_error );
		}

		if ( false === $result ) {
			$this->logger->error(
				'Error saving autologin code for wp_user:' . $user_id,
				array(
					'user_id'    => $user_id,
					'code'       => $code,
					'expires_in' => $expires_in,
					'expires_at' => $expires_at,
					'hash'       => $key,
					'userhash'   => $value,
				)
			);
			return;
		}

		$this->logger->debug(
			'Saved autologin code for wp_user:' . $user_id,
			array(
				'user_id' => $user_id,
				'expires' => $expires_at,
			)
		);
	}

	/**
	 * Retrieve the value stored for the given autologin code, if it has not expired.
	 *
	 * @param string $code The autologin code in the user's URL.
	 * @param bool   $delete Delete the code after fetching it. I.e. this is a single use code.
	 *
	 * @return string|null
	 * @throws Exception DateTime exception.
	 * @throws Exception For `$wpdb->last_error`.
	 */
	public function get_value_for_code( string $code, bool $delete = true ): ?string {

		global $wpdb;

		$key = hash( 'sha256', $code );

		/**
		 * We've no interest in caching, rather, for security, we're deleting the entry as soon as it's found.
		 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
		 */
		$result = $wpdb->get_row(
			$wpdb->prepare( 'SELECT expires_at, userhash FROM ' . $wpdb->prefix . 'autologin_urls WHERE hash = %s', $key )
		);

		if ( ! empty( $wpdb->last_error ) ) {
			$this->logger->error( $wpdb->last_error );
			throw new Exception( $wpdb->last_error );
		}

		if ( is_null( $result ) ) {
			$this->logger->debug( 'Code not found.' );
			return null;
		}

		// Delete the code so it can only be used once (whether valid or not).
		if ( $delete ) {
			$wpdb->delete(
				$wpdb->prefix . 'autologin_urls',
				array( 'hash' => $key )
			);
		}

		$expires_at = new DateTimeImmutable( $result->expires_at, new DateTimeZone( 'UTC' ) );

		$now = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		if ( $now > $expires_at ) {
			$this->logger->debug(
				'Valid code but already expired',
				array(
					'code'       => $code,
					'expires_at' => $expires_at,
					'hash'       => $key,
				)
			);
			return null;
		}

		return $result->userhash;
	}

	/**
	 * Delete codes that are no longer valid.
	 *
	 * @param DateTimeInterface $before The date from which to purge old codes.
	 *
	 * @return array{deleted_count:int|null}
	 * @throws Exception For `$wpdb->last_error`.
	 */
	public function delete_expired_codes( DateTimeInterface $before ): array {

		// get current datetime in mysql format.
		$mysql_formatted_date = $before->format( 'Y-m-d H:i:s' );

		global $wpdb;
		$result = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $wpdb->prefix . 'autologin_urls WHERE expires_at < %s', $mysql_formatted_date ) );

		if ( ! empty( $wpdb->last_error ) ) {
			$this->logger->error( $wpdb->last_error );
			throw new Exception( $wpdb->last_error );
		}

		// I think this is the number of entries deleted.
		$this->logger->info( 'Delete expired codes wpdb result: ' . $result, array( 'result' => $result ) );

		return array( 'deleted_count' => intval( $result ) );
	}
}
