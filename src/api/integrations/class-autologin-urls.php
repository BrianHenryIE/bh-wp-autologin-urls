<?php

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\User_Finder_Interface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WP_User;

/**
 * The $_GET data is coming from links clicked outside WordPress; it will not have a nonce.
 *
 * phpcs:disable WordPress.Security.NonceVerification.Recommended
 */
class Autologin_URLs implements User_Finder_Interface, LoggerAwareInterface {
	use LoggerAwareTrait;

	const QUERYSTRING_PARAMETER_NAME = 'autologin';

	protected API_Interface $api;

	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * Determine is the querystring needed for this integration present.
	 */
	public function is_querystring_valid(): bool {
		return isset( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] );
	}

	/**
	 * The actual code for logging the user in. Should run before wp_set_current_user
	 * so it is run before other code expects a user to be set, i.e. run it on
	 * plugins_loaded and not init.
	 *
	 * @hooked plugins_loaded
	 *
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference
	 * @see _wp_get_current_user()
	 *
	 * @return array{source:string, wp_user:WP_User|null, user_data?:array<string,string>}
	 */
	public function get_wp_user_array(): array {

		$result              = array();
		$result['source']    = 'Autologin URL';
		$result['wp_user']   = null;
		$result['user_data'] = array();

		// This input is not coming from a WordPress page so cannot have a nonce.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended

		if ( ! isset( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) ) {
			return $result;
		}

		$autologin_querystring = sanitize_text_field( wp_unslash( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) );

		list( $user_id, $password ) = explode( '~', $autologin_querystring, 2 );

		if ( empty( $user_id ) || empty( $password ) || ! is_numeric( $user_id ) || ! ctype_alnum( $password ) ) {

			return $result;
		}

		$user_id = intval( $user_id );

		if ( $this->api->verify_autologin_password( $user_id, $password ) ) {

			$wp_user = get_user_by( 'id', $user_id );
			if ( $wp_user instanceof WP_User ) {
				// e.g. The user account may have been deleted since the link was created.
				$result['wp_user'] = $wp_user;
			}
		}

		if ( isset( $_GET['magic'] ) ) {
			$result['source'] = 'Magic Email';
		}

		return $result;
	}
}
