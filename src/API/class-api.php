<?php
/**
 * The core plugin functionality
 *
 * Contains functions relied on by other parts of the plugin and public for other plugin developers.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WP_User;

/**
 * The core plugin functionality.
 *
 * @since      1.0.0
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/api
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	/**
	 * Plugin settings as [maybe] configured by the user.
	 *
	 * @var Settings_Interface $settings The plugin settings from the database.
	 */
	protected $settings;

	/**
	 * Briefly caches generated codes to save regenerating multiple codes per email.
	 *
	 * @var array<string,string> Dictionary ["user_id~seconds_valid" : code]
	 */
	protected $cache = array();

	/**
	 * Class for saving, retrieving and expiring passwords.
	 *
	 * @var Data_Store_Interface
	 */
	protected Data_Store_Interface $data_store;

	/**
	 * API constructor.
	 *
	 * @param Settings_Interface        $settings   The plugin settings from the database.
	 * @param LoggerInterface           $logger     The logger instance.
	 * @param Data_Store_Interface|null $data_store Class for saving, retrieving and expiring passwords.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger, Data_Store_Interface $data_store = null ) {

		$this->setLogger( $logger );
		$this->data_store = $data_store ?? new Transient_Data_Store( $logger );

		$this->settings = $settings;
	}

	/**
	 * Adds autologin codes to every url for this site in a string.
	 *
	 * @param string             $message  A string to update the URLs in.
	 * @param int|string|WP_User $user     A user id, email, username or user object.
	 * @param int|null           $expires_in Number of seconds the password should work for.
	 *
	 * @return string
	 */
	public function add_autologin_to_message( string $message, $user, ?int $expires_in = null ): string {

		$replace_with = function ( $matches ) use ( $user, $expires_in ) {

			$url = $matches[0];

			$login_url = $this->add_autologin_to_url( $url, $user, $expires_in );

			return $login_url;
		};

		$escaped_site_url = preg_quote( get_site_url(), '/' );

		$updated = preg_replace_callback( '/[\s\"](' . $escaped_site_url . '[^\s\"<]*)/m', $replace_with, $message );

		if ( is_null( $updated ) ) {
			$this->logger->warning( 'Failed to update message', array( 'message' => $message ) );
		} else {
			$message = $updated;
		}

		return $message;
	}

	/**
	 * Public function for other plugins to use on links.
	 *
	 * @param string                  $url         The url to append the autologin code to. This must be a link to this site.
	 * @param null|int|string|WP_User $user        A valid user id, email, login or user object.
	 * @param ?int                    $expires_in  The number of seconds the code will work for.
	 *
	 * @return string
	 */
	public function add_autologin_to_url( string $url, $user, ?int $expires_in = null ): string {

		if ( ! stristr( $url, get_site_url() ) ) {
			return $url;
		}

		if ( is_null( $user ) ) {
			return $url;
		}

		if ( ! $user instanceof WP_User ) {

			if ( is_int( $user ) ) {

				$user = get_user_by( 'ID', $user );

			} elseif ( is_string( $user ) && 0 !== intval( $user ) ) {

				// When string '123' is passed as the user id, convert it to an int.
				$user = intval( $user );
				$user = get_user_by( 'ID', $user );

			} elseif ( is_string( $user ) && is_email( $user ) ) {

				// When a string which is an email is passed.
				$user = get_user_by( 'email', $user );

			} elseif ( is_string( $user ) ) {

				// When any other string is passed, assume it is a username.
				$user = get_user_by( 'login', $user );

			} else {

				return $url;
			}

			if ( false === $user ) {

				return $url;
			}
		}

		// Although this method could return null, the checks to prevent that have already
		// taken place in this method.
		$autologin_code = $this->generate_code( $user, $expires_in );

		if ( ! $this->settings->get_should_use_wp_login() ) {
			$user_link = add_query_arg( Login::QUERYSTRING_PARAMETER_NAME, $autologin_code, $url );
		} else {
			$user_link = add_query_arg( Login::QUERYSTRING_PARAMETER_NAME, $autologin_code, wp_login_url( $url ) );
		}

		return $user_link;

	}

	/**
	 * Returns a code that can be verified, containing the user id and a single-use password separated
	 * by ~, e.g. 11~mdBpC879oJSs.
	 *
	 * If the user does not exist, null is returned.
	 *
	 * @param ?WP_User $user           WordPress user.
	 * @param ?int     $seconds_valid  Number of seconds after which the password will expire.
	 *
	 * @return ?string
	 */
	public function generate_code( $user, ?int $seconds_valid ): ?string {

		if ( is_null( $user ) || ! ( $user instanceof WP_User ) ) {
			return null;
		}

		if ( is_null( $seconds_valid ) ) {
			$seconds_valid = $this->settings->get_expiry_age();
		}

		$user_id = $user->ID;

		if ( array_key_exists( "$user_id~$seconds_valid", $this->cache ) ) {
			return $this->cache[ "$user_id~$seconds_valid" ];
		}

		$password = $this->generate_password( $user_id, $seconds_valid );

		$code = "$user_id~$password";

		$this->cache[ "$user_id~$seconds_valid" ] = $code;

		return $code;
	}

	/**
	 * Generates a password that can be used in autologin links, never stored, expires after $seconds_valid.
	 *
	 * @param int $user_id       WordPress user id.
	 * @param int $seconds_valid Number of seconds after which the password will expire.
	 *
	 * @return string password
	 */
	protected function generate_password( int $user_id, int $seconds_valid ): string {

		// Generate a password using only alphanumerics (to avoid urlencoding worries).
		// Length of 12 was chosen arbitrarily.
		$password = wp_generate_password( 12, false );

		$this->data_store->save( $user_id, $password, $seconds_valid );

		return $password;
	}

	/**
	 * Verifies the autologin code.
	 * The datastore deletes codes when found to prevent reuse.
	 *
	 * @param int    $user_id  WordPress user id.
	 * @param string $password Plugin generated password to verify.
	 *
	 * @return bool
	 */
	public function verify_autologin_password( int $user_id, string $password ): bool {

		$saved_details = $this->data_store->get_value_for_code( $password );

		if ( null === $saved_details ) {
			return false;
		}

		$provided_details = hash( 'sha256', $user_id . $password );

		return hash_equals( $provided_details, $saved_details );
	}

	/**
	 * Purge codes that are no longer valid.
	 *
	 * @return array{count:int}
	 */
	public function delete_expired_codes(): array {
		return $this->data_store->delete_expired_codes();
	}
}
