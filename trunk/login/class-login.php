<?php
/**
 * The actual logging in functionality of the plugin.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/login
 */

namespace BH_WP_Autologin_URLs\login;

use BH_WP_Autologin_URLs\api\API_Interface;
use BH_WP_Autologin_URLs\WPPB\WPPB_Object;

/**
 * The actual logging in functionality of the plugin.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Login extends WPPB_Object {

	const QUERYSTRING_PARAMETER_NAME = 'autologin';

	const FAILURE_TRANSIENT_PREFIX = 'bh-wp-autologin-urls-failure-';

	const MAX_BAD_LOGIN_ATTEMPTS = 5;

	/**
	 * Core API methods for verifying autologin querystring.
	 *
	 * @var API_Interface
	 */
	private $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   string        $plugin_name The name of this plugin.
	 * @param   string        $version     The version of this plugin.
	 * @param   API_Interface $api The core plugin functions.
	 *
	 * @since   1.0.0
	 */
	public function __construct( $plugin_name, $version, $api ) {

		parent::__construct( $plugin_name, $version );

		$this->api = $api;
	}

	/**
	 * The actual code for logging the user in. Should run before wp_set_current_user
	 * so it is run before other code expects a user to be set, i.e. run it on
	 * plugins_loaded and not init.
	 *
	 * Record ba
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference
	 * @see _wp_get_current_user()
	 */
	public function wp_init_process_autologin() {

		// This input is not coming from a WordPress page so cannot have a nonce.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) ) {
			// Nothing to do here.
			return false;
		}

		$autologin_querystring = sanitize_text_field( wp_unslash( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) );

		list( $user_id, $password ) = explode( '~', $autologin_querystring, 2 );

		if ( empty( $user_id ) || empty( $password ) || ! is_numeric( $user_id ) || ! ctype_alnum( $password ) ) {

			$this->record_bad_attempts( $autologin_querystring );

			return false;
		}

		$user_id = intval( $user_id );

		if ( get_current_user_id() === $user_id ) {
			// Already logged in.
			return false;
		}

		// Check for blocked IP.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {

			$ip_address = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );

			$failure_transient_name_for_ip = self::FAILURE_TRANSIENT_PREFIX . str_replace( '.', '-', $ip_address );

			$ip_failure = get_transient( $failure_transient_name_for_ip );

			if ( ! empty( $ip_failure ) && is_array( $ip_failure ) && isset( $ip_failure['count'] ) ) {

				if ( $ip_failure['count'] >= self::MAX_BAD_LOGIN_ATTEMPTS ) {

					$this->record_bad_attempts( $autologin_querystring );

					return false;
				}
			}
		}

		$failure_transient_name_for_user = self::FAILURE_TRANSIENT_PREFIX . $user_id;

		$user_failures = get_transient( $failure_transient_name_for_user );

		if ( ! empty( $user_failures ) && is_array( $user_failures ) && isset( $user_failures['count'] ) ) {

			if ( $user_failures['count'] >= self::MAX_BAD_LOGIN_ATTEMPTS ) {

				$this->record_bad_attempts( $autologin_querystring );

				return false;

			}
		}

		if ( $this->api->verify_autologin_password( $user_id, $password ) ) {

			// @see https://developer.wordpress.org/reference/functions/wp_set_current_user/

			$user = get_user_by( 'id', $user_id );

			if ( $user ) {

				wp_set_current_user( $user_id, $user->user_login );
				wp_set_auth_cookie( $user_id );
				do_action( 'wp_login', $user->user_login, $user );

				return true;

			}
		}

		$this->record_bad_attempts( $autologin_querystring );

		return false;

	}

	/**
	 * Record failed attempts in transients for blocking.
	 *
	 * @param string $autologin_querystring The autologin code which did not work.
	 */
	private function record_bad_attempts( $autologin_querystring ) {

		// This is how WordPress gets the IP in WP_Session_Tokens().
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {

			$ip_address = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
		}

		// TODO: What to do when there's no IP address?

		list( $user_id, $password ) = explode( '~', $autologin_querystring, 2 );

		if ( ! empty( $user_id ) && is_numeric( $user_id ) ) {

			$failure_transient_name_for_user = self::FAILURE_TRANSIENT_PREFIX . $user_id;

			$user_failure = get_transient( $failure_transient_name_for_user );

			if ( empty( $user_failure ) ) {

				$user_failure = array(
					'count' => 0,
					'ip'    => array(),
				);
			}

			$user_failure['count'] = $user_failure['count'] + 1;
			$user_failure['ip'][]  = $ip_address;

			set_transient( $failure_transient_name_for_user, $user_failure, DAY_IN_SECONDS );
		}

		$failure_transient_name_for_ip = self::FAILURE_TRANSIENT_PREFIX . str_replace( '.', '-', $ip_address );

		$ip_failure = get_transient( $failure_transient_name_for_ip );

		if ( empty( $ip_failure ) ) {
			$ip_failure = array(
				'count'     => 0,
				'users'     => array(),
				'malformed' => array(),
			);
		}

		$ip_failure['count'] = $ip_failure['count'] + 1;

		if ( ! empty( $user_id ) && is_numeric( $user_id ) ) {
			$ip_failure['users'][] = $user_id;
		} else {
			$ip_failure['malformed'][] = $autologin_querystring;
		}

		set_transient( $failure_transient_name_for_ip, $ip_failure, DAY_IN_SECONDS );
	}


}
