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
use Newsletter;
use NewsletterStatistics;
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

	const FAILURE_TRANSIENT_PREFIX = 'bh-wp-autologin-urls-failure-';

	const MAX_BAD_LOGIN_ATTEMPTS = 5;

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

		if ( empty( $user_id ) || empty( $password ) || ! is_numeric( $user_id ) || ! ctype_alnum( $password ) ) {

			$this->record_bad_attempts( $autologin_querystring );

			return false;
		}

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

		// Check for blocked IP.
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {

			if ( class_exists( WC_Geolocation::class ) ) {
				$ip_address = WC_Geolocation::get_ip_address();
			} else {
				if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
					$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
				} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
					$ip_address = (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
				} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
					$ip_address = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
				}
			}
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

		$this->record_bad_attempts( $autologin_querystring );

		return false;

	}

	/**
	 * Record failed attempts in transients for blocking.
	 *
	 * @param string $autologin_querystring The autologin code which did not work.
	 */
	protected function record_bad_attempts( $autologin_querystring ): void {

		// This is how WordPress gets the IP in WP_Session_Tokens().
		// TODO: What to do when there's no IP address?
		if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return;
		}

		$ip_address = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );

		if ( false === $ip_address ) {
			return;
		}

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
	public function login_newsletter_urls(): void {

		if ( ! isset( $_GET['nltr'] ) ) {
			return;
		}

		if ( ! class_exists( NewsletterStatistics::class ) ) {
			return;
		}

		// This code mostly lifted from Newsletter plugin.

		$input = filter_var( wp_unslash( $_GET['nltr'] ), FILTER_SANITIZE_STRIPPED );
		if ( false === $input ) {
			return;
		}

		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$nltr_param = base64_decode( $input );

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

		if ( is_null( $tnp_user ) ) {
			$this->logger->info( 'No user object returned for Newsletter user ' . $tnp_user );
			return;
		}

		$user_email_address = $tnp_user->email;

		$wp_user = get_user_by( 'email', $user_email_address );

		if ( $wp_user ) {

			if ( get_current_user_id() === $wp_user->ID ) {

				$this->logger->debug( "User {$wp_user->user_login} already logged in." );

				return;
			}

			wp_set_current_user( $user_id, $wp_user->user_login );
			wp_set_auth_cookie( $user_id );

			do_action( 'wp_login', $wp_user->user_login, $wp_user );

			$this->logger->info( "User {$wp_user->user_login} logged in via Newsletter URL." );

		} else {

			// We have their email address but they have no account,
			// if WooCommerce is installed, record the email address for
			// UX and abandoned cart.
			$user_info = array(
				'first_name' => $tnp_user->name,
				'last_name'  => $tnp_user->surname,
			);
			$this->woocommerce_ux( $user_email_address, $user_info );
		}
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

			// We have their email address but they have no account,
			// if WooCommerce is installed, record the email address for
			// UX and abandoned cart.
			$user_info = array(
				'first_name' => $subscriber->firstName,
				'last_name'  => $subscriber->lastName,
			);
			$this->woocommerce_ux( $user_email_address, $user_info );
		}
	}

	/**
	 * If WooCommerce is installed, when there is no WP_User, attempt to populate the user checkout
	 * fields using data from Newsletter/MailPoet and from past orders by that email address.
	 *
	 * @param string                                     $email_address The user's email address.
	 * @param array{first_name:string, last_name:string} $user_info Information e.g. first name, last name that might be available from MailPoet/Newsletter.
	 */
	protected function woocommerce_ux( string $email_address, array $user_info ): void {

		if ( ! function_exists( 'WC' ) ) {
			return;
		}

		WC()->initialize_cart();

		WC()->customer->set_billing_email( $email_address );

		if ( ! empty( $user_info['first_name'] ) ) {
			WC()->customer->set_first_name( $user_info['first_name'] );
			WC()->customer->set_billing_first_name( $user_info['first_name'] );
			WC()->customer->set_shipping_first_name( $user_info['first_name'] );
		}

		if ( ! empty( $user_info['last_name'] ) ) {
			WC()->customer->set_last_name( $user_info['last_name'] );
			WC()->customer->set_billing_last_name( $user_info['last_name'] );
			WC()->customer->set_shipping_last_name( $user_info['last_name'] );
		}

		/**
		 * Try to get one past order placed by this email address.
		 *
		 * @var WC_Order[] $customer_orders
		 */
		$customer_orders = wc_get_orders(
			array(
				'customer' => $email_address,
				'limit'    => 1,
				'order'    => 'DESC',
				'orderby'  => 'id',
				'paginate' => false,
			)
		);

		if ( count( $customer_orders ) > 0 ) {

			$order = $customer_orders[0];

			WC()->customer->set_billing_country( $order->get_billing_country() );
			WC()->customer->set_billing_postcode( $order->get_billing_postcode() );
			WC()->customer->set_billing_state( $order->get_billing_state() );
			WC()->customer->set_billing_last_name( $order->get_billing_last_name() );
			WC()->customer->set_billing_first_name( $order->get_billing_first_name() );
			WC()->customer->set_billing_address_1( $order->get_billing_address_1() );
			WC()->customer->set_billing_address_2( $order->get_billing_address_2() );
			WC()->customer->set_billing_city( $order->get_billing_city() );
			WC()->customer->set_billing_company( $order->get_billing_company() );
			WC()->customer->set_billing_phone( $order->get_billing_phone() );

			$this->logger->info( "Set customer checkout details from past order #{$order->get_id()}" );
		}

	}
}
