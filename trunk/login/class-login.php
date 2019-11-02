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

/**
 * The actual logging in functionality of the plugin.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Login extends \WPPB_Object {

	const QUERYSTRING_PARAMETER_NAME = 'autologin';

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
			// Malformed login code.
			return false;
		}

		$user_id = intval( $user_id );

		if ( get_current_user_id() === $user_id ) {
			// Already logged in.
			return false;
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

		return false;

	}

}
