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

namespace BH_WP_Autologin_URLs\includes;

use BH_WP_Autologin_URLs\api\API_Interface;
use BH_WP_Autologin_URLs\WPPB\WPPB_Object;
use MailPoet\Models\Subscriber;
use MailPoet\Newsletter\Links\Links;
use MailPoet\Router\Router;
use MailPoet\Subscribers\LinkTokens;
use Newsletter;
use NewsletterModule;
use NewsletterStatistics;

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

			// TODO: Add an option "always expire codes when used".

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

				// TODO: Action to allow logging.
				// Could we save what email the user clicked?

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

	/**
	 * Check is the URL a tracking URL for The Newsletter Plugin and if so, log in the user being tracked.
	 *
	 * @hooked plugins_loaded
	 *
	 * @see https://wordpress.org/plugins/newsletter/
	 * @see NewsletterStatistics::hook_wp_loaded()
	 */
	public function login_newsletter_urls() {

		if ( ! isset( $_GET['nltr'] ) ) {
			return;
		}

		if ( ! class_exists( NewsletterStatistics::class ) ) {
			return;
		}

		// This code mostly lifted from Newsletter plugin.

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$nltr_param = base64_decode( filter_var( '0bda890bd176d3e219614dde964cb07f==', FILTER_SANITIZE_STRIPPED ) );

		// e.g. "1;2;https://example.org;;0bda890bd176d3e219614dde964cb07f".

		$parts     = explode( ';', $nltr_param );
		$email_id  = (int) array_shift( $parts );
		$user_id   = (int) array_shift( $parts );
		$signature = array_pop( $parts );
		$anchor    = array_pop( $parts );

		$url = implode( ';', $parts );

		$key = NewsletterStatistics::instance()->options['key'];

		$verified = ( md5( $email_id . ';' . $user_id . ';' . $url . ';' . $anchor . $key ) === $signature );

		if ( ! $verified ) {
			// TODO: ban IP for repeated abuse.
			return;
		}

		$tnp_user = Newsletter::instance()->get_user( $user_id );

		$user_email_address = $tnp_user->email;

		$wp_user = get_user_by( 'email', $user_email_address );

		if ( get_current_user_id() === $wp_user->ID ) {
			// Already logged in.
			return;
		}

		if ( $wp_user ) {

			wp_set_current_user( $wp_user->ID, $wp_user->user_login );
			wp_set_auth_cookie( $wp_user->ID );
			do_action( 'wp_login', $wp_user->user_login, $wp_user );

			return;

		}
	}

	/**
	 * Check is the URL a tracking URL for MailPoet plugin and if so, log in the user being tracked.
	 *
	 * @hooked plugins_loaded
	 *
	 * @see https://wordpress.org/plugins/mailpoet/
	 */
	public function login_mailpoet_urls() {

		// https://staging.redmeatsupplement.com/?mailpoet_router&endpoint=track&action=click&data=WyI0IiwiZDAzYWE3IiwiMiIsImFlNzViYjI5YjVjOSIsZmFsc2Vd
		// TODO: verify this works!
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

		$transformed_data = Links::transformUrlDataObject( $data );

		if ( ! isset( $transformed_data['subscriber_id'] ) ) {
			return;
		}

		// "["4","d03aa7","2","ae75bb29b5c9",false]"
		//
		// https://staging.redmeatsupplement.com/?mailpoet_router&endpoint=track&action=click&data=WyI0IiwiZDAzYWE3IiwiMiIsIjVjMGU5YWRlMjNjZCIsZmFsc2Vd
		//
		// Links::transformUrlDataObject()
		//
		// $this->linkTokens->verifyToken($subscriber, $data['subscriber_token']))

		$subscriber = Subscriber::where( 'id', $transformed_data['subscriber_id'] )->findOne();

		if ( ! $subscriber ) {
			return;
		}

		$link_tokens = new LinkTokens();
		$valid       = $link_tokens->verifyToken( $subscriber, $transformed_data['subscriber_token'] );

		if ( ! $valid ) {
			return;
		}

		$user_email_address = $subscriber->email;

		$wp_user = get_user_by( 'email', $user_email_address );

		if ( get_current_user_id() === $wp_user->ID ) {
			// Already logged in.
			return;
		}

		if ( $wp_user ) {

			wp_set_current_user( $wp_user->ID, $wp_user->user_login );
			wp_set_auth_cookie( $wp_user->ID );
			do_action( 'wp_login', $wp_user->user_login, $wp_user );

			return;

		}
	}
}
