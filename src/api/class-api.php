<?php
/**
 * The core plugin functionality
 *
 * Contains functions relied on by other parts of the plugin and public for other plugin developers.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/api
 */

namespace BH_WP_Autologin_URLs\api;

use BH_WP_Autologin_URLs\includes\Settings_Interface;
use BH_WP_Autologin_URLs\login\Login;
use BH_WP_Autologin_URLs\WPPB\WPPB_Object;


/**
 * The core plugin functionality.
 *
 * @since      1.0.0
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/api
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class API extends WPPB_Object implements API_Interface {

	const TRANSIENT_PREFIX = 'bh_autologin_';

	/**
	 * Plugin settings as [maybe] configured by the user.
	 *
	 * @var Settings_Interface $settings The plugin settings from the database.
	 */
	private $settings;

	/**
	 * Briefly caches generated codes to save regenerating multiple codes per email.
	 *
	 * @var array Dictionary ["user_id~seconds_valid" : code]
	 */
	private $cache = array();

	/**
	 * API constructor.
	 *
	 * @param string             $plugin_name The name of this plugin.
	 * @param string             $version     The version of this plugin.
	 * @param Settings_Interface $settings The plugin settings from the database.
	 */
	public function __construct( $plugin_name, $version, $settings ) {
		parent::__construct( $plugin_name, $version );

		$this->settings = $settings;
	}

	/**
	 * Adds autologin codes to every url for this site in a string.
	 *
	 * @param string|string[]     $message  A string (or array of strings) to update the URLs in.
	 * @param int|string|\WP_User $user     A user id, email, username or user object.
	 * @param int                 $expires_in Number of seconds the password should work for.
	 *
	 * @return string|string[]
	 */
	public function add_autologin_to_message( $message, $user, $expires_in = null ) {

		$replace_with = function ( $matches ) use ( $user, $expires_in ) {

			$url = $matches[0];

			$login_url = $this->add_autologin_to_url( $url, $user, $expires_in );

			return $login_url;
		};

		$escaped_site_url = str_replace( '/', '\/', get_site_url() );

		$message = preg_replace_callback( '/[\s\"](' . $escaped_site_url . '[^\s\"<]*)/m', $replace_with, $message );

		return $message;
	}

	/**
	 * Public function for other plugins to use on links.
	 *
	 * @param string              $url         The url to append the autologin code to. This must be a link to this site.
	 * @param int|string|\WP_User $user        A valid user id, email, login or user object.
	 * @param int                 $expires_in  The number of seconds the code will work for.
	 *
	 * @return null|string
	 */
	public function add_autologin_to_url( $url, $user, $expires_in = null ) {

		if ( is_null( $url ) || ! stristr( $url, get_site_url() ) ) {
			return $url;
		}

		if ( is_null( $user ) ) {
			return $url;
		}

		if ( ! $user instanceof \WP_User ) {

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

		$user_link = add_query_arg( Login::QUERYSTRING_PARAMETER_NAME, $autologin_code, $url );

		return $user_link;

	}

	/**
	 * Returns a code that can be verified, containing the user id and a single-use password separated
	 * by ~, e.g. 11~mdBpC879oJSs.
	 *
	 * If the user does not exist, null is returned.
	 *
	 * @param \WP_User $user           WordPress user.
	 * @param int      $seconds_valid  Number of seconds after which the password will expire.
	 *
	 * @return String|null
	 */
	public function generate_code( $user, $seconds_valid ) {

		if ( is_null( $user ) || ! $user instanceof \WP_User ) {
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
	 * @return String password
	 */
	private function generate_password( $user_id, $seconds_valid ) {

		// Generate a password using only alphanumerics (to avoid urlencoding worries).
		// Length of 12 was chosen arbitrarily.
		$password = wp_generate_password( 12, false );

		// In the unlikely event there is a colission, someone won't get to log in. Oh well.
		$transient_name = self::TRANSIENT_PREFIX . hash( 'sha256', $password );

		// Concatenate $user_id and $password so the database cannot be searched by username.
		$value = hash( 'sha256', $user_id . $password );

		$expires_at = time() + $seconds_valid;

		// This could return false if not set.
		set_transient( $transient_name, $value, $expires_at );

		return $password;
	}

	/**
	 * Verifies the autologin code and deletes so it cannot be reused.
	 *
	 * @param int    $user_id  WordPress user id.
	 * @param String $password Plugin generated password to verify.
	 *
	 * @return bool
	 */
	public function verify_autologin_password( $user_id, $password ) {

		$transient_name = self::TRANSIENT_PREFIX . hash( 'sha256', $password );

		$value = get_transient( $transient_name );

		if ( false !== $value ) {

			if ( hash( 'sha256', $user_id . $password ) === $value ) {

				delete_transient( $transient_name );

				return true;
			}
		}

		return false;
	}

}
