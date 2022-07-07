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

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use MailPoet\API\API;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use WC_Order;
use WP_User;
use WC_Geolocation;

/**
 * The actual logging-in functionality of the plugin.
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/includes
 * @author     BrianHenryIE <BrianHenryIE@gmail.com>
 */
class Login {

	use LoggerAwareTrait;

	const QUERYSTRING_PARAMETER_NAME = 'autologin';

	const MAX_BAD_LOGIN_ATTEMPTS       = 5;
	const MAX_BAD_LOGIN_PERIOD_SECONDS = 60 * 60 * 24; // Aka DAY_IN_SECONDS.


	/**
	 * Core API methods for verifying autologin querystring.
	 *
	 * @var API_Interface
	 */
	protected $api;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param   API_Interface   $api The core plugin functions.
	 * @param   LoggerInterface $logger The logger instance.
	 *
	 * @since   1.0.0
	 */
	public function __construct( $api, $logger ) {

		$this->api    = $api;
		$this->logger = $logger;
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
	 */
	public function wp_init_process_autologin(): bool {

		// This input is not coming from a WordPress page so cannot have a nonce.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) ) {
			// Nothing to do here.
			return false;
		}

		$autologin_querystring = sanitize_text_field( wp_unslash( $_GET[ self::QUERYSTRING_PARAMETER_NAME ] ) );

		list( $user_id, $password ) = explode( '~', $autologin_querystring, 2 );


		$user_id = intval( $user_id );

		if ( get_current_user_id() === $user_id ) {

			$this->logger->debug( "User {$user_id} already logged in." );

			// TODO: always expire codes when used.
			// TODO: Test this thoroughly.

			$wp_login_endpoint = str_replace( get_site_url(), '', wp_login_url() );
			if ( stristr( filter_var( getenv( 'REQUEST_URI' ) ), $wp_login_endpoint )
				&& isset( $_GET['redirect_to'] ) ) {

				$redirect_to = urldecode( filter_var( wp_unslash( $_GET['redirect_to'] ), FILTER_SANITIZE_STRING ) );
				wp_safe_redirect( $redirect_to );
				exit;

			}

			return false;
		}





		if ( $this->api->verify_autologin_password( $user_id, $password ) ) {

			// @see https://developer.wordpress.org/reference/functions/wp_set_current_user/

			$user = get_user_by( 'id', $user_id );

			if ( $user ) {

				wp_set_current_user( $user_id, $user->user_login );
				wp_set_auth_cookie( $user_id );
				do_action( 'wp_login', $user->user_login, $user );

				$this->logger->info( "User {$user->user_login} logged in via Autologin URL." );

				// TODO: Test this thoroughly.
				$wp_login_endpoint = str_replace( get_site_url(), '', wp_login_url() );
				$request_uri       = filter_var( getenv( 'REQUEST_URI' ) );
				if ( false !== $request_uri
					&& stristr( $request_uri, $wp_login_endpoint )
					&& isset( $_GET['redirect_to'] ) ) {

					$url = filter_var( wp_unslash( $_GET['redirect_to'] ), FILTER_SANITIZE_STRING );
					if ( false === $url ) {
						return false;
					}
					$redirect_to = urldecode( $url );
					wp_safe_redirect( $redirect_to );
					exit();

				}

				return true;

			}
		}

		return false;

	}


			return;
			return;
		}






			return;
		}


		$input = filter_var( wp_unslash( $_GET['nltr'] ), FILTER_SANITIZE_STRIPPED );
		if ( false === $input ) {
			return;
		}

		// Log each attempt to log in, prevent too many attempts by any one IP.
		if ( ! $this->api->should_allow_login_attempt( $ip_address ) ) {
			return;
		}


			return;
		}


			if ( get_current_user_id() === $wp_user->ID ) {

	}

	/**
	 * Check is the URL a tracking URL for MailPoet plugin and if so, log in the user being tracked.
	 *
	 * Uses MailPoet's verification process as the autologin code.
	 *
	 * @see LinkTokens::verifyToken()
	 *
	 * TODO: The time since the newsletter was sent should be respected for the expiry time.
	 *
	 * @hooked plugins_loaded
	 *
	 * @see https://wordpress.org/plugins/mailpoet/
	 *
	 * phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	 */
	public function login_mailpoet_urls(): void {

		if ( ! isset( $_GET['mailpoet_router'] ) ) {
			return;
		}

		if ( ! isset( $_GET['data'] ) ) {
			return;
		}

		if ( ! class_exists( Router::class ) ) {
			return;
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$data = Router::decodeRequestData( filter_var( wp_unslash( $_GET['data'] ), FILTER_SANITIZE_STRIPPED ) );

		/**
		 * The required data from the MailPoet querystring.
		 *
		 * @see Links::transformUrlDataObject()
		 */
		$subscriber_id = $data[0];
		$request_token = $data[1];

		/**
		 * The MailPoet subscriber object, false if none found.
		 *
		 * @var \MailPoet\Models\Subscriber $subscriber
		 */
		$subscriber = Subscriber::where( 'id', $subscriber_id )->findOne();

		if ( empty( $subscriber ) ) {
			return;
		}

		$database_token = $subscriber->linkToken;
		$request_token  = substr( $request_token, 0, strlen( $database_token ) );
		$valid          = hash_equals( $database_token, $request_token );

		if ( ! $valid ) {
			return;
		}

		$user_email_address = $subscriber->email;

		$wp_user = get_user_by( 'email', $user_email_address );

		if ( $wp_user instanceof WP_User ) {

			if ( get_current_user_id() === $wp_user->ID ) {
				$this->logger->debug( "User {$wp_user->user_login} already logged in." );

				// Already logged in.
				return;
			}

			wp_set_current_user( $wp_user->ID, $wp_user->user_login );
			wp_set_auth_cookie( $wp_user->ID );
			do_action( 'wp_login', $wp_user->user_login, $wp_user );

			$this->logger->info( "User {$wp_user->user_login} logged in via MailPoet URL." );

			return;

		} else {
		}
	}
		}

	}
}
