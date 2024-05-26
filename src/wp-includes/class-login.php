<?php
/**
 * The actual logging in functionality of the plugin.
 *
 * Distinct from login UI.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Integrations\User_Finder_Factory;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\WooCommerce\Checkout;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WP_User;

/**
 * The actual logging-in functionality of the plugin.
 */
class Login {

	use LoggerAwareTrait;

	const MAX_BAD_LOGIN_ATTEMPTS       = 5;
	const MAX_BAD_LOGIN_PERIOD_SECONDS = 60 * 60 * 24; // Aka DAY_IN_SECONDS.

	/**
	 * Not in use?
	 *
	 * @var Settings_Interface
	 */
	protected Settings_Interface $settings;

	/**
	 * Core API methods for verifying autologin querystring.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * This plugin can parse URLs for MailPoet, The Newsletter Plugin, and Klaviyo, and AutologinUrls own
	 * querystring parameter. The factory returns valid User_Finders for each.
	 *
	 * @var User_Finder_Factory
	 */
	protected User_Finder_Factory $user_finder_factory;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param API_Interface        $api The core plugin functions.
	 * @param Settings_Interface   $settings The plugin's settings.
	 * @param LoggerInterface      $logger The logger instance.
	 * @param ?User_Finder_Factory $user_finder_factory Factory to return a class that can determine the user from the URL.
	 *
	 * @since   1.0.0
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger, ?User_Finder_Factory $user_finder_factory = null ) {
		$this->setLogger( $logger );

		$this->settings = $settings;
		$this->api      = $api;

		$this->user_finder_factory = $user_finder_factory ?? new User_Finder_Factory( $this->api, $this->settings, $this->logger );
	}

	/**
	 * The primary handler of the plugin, that reads the request querystring and checks it for autologin parameters.
	 *
	 * @hooked determine_current_user
	 *
	 * @param int|bool $user_id The already determined user ID, or false if none.
	 * @return int|bool
	 */
	public function process( $user_id ) {

		remove_action( 'determine_current_user', array( $this, 'process' ), 30 );

		// If we're logged in already, or there's no querystring to parse, just return.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $user_id || empty( $_GET ) ) {
			return $user_id;
		}

		// Check for bots.
		// Use the null coalescing operator to ensure $user_agent is always a string.
		// This prevents passing null to strpos, which is deprecated in newer PHP versions.
		$user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' ) ?? '';
		$bot        = false !== strpos( $user_agent, 'bot' );
		if ( $bot ) {
			return $user_id;
		}

		// Maybe use a cookie to only use an autologin URL once every x minutes.

		// Checks does the querystring contain an autologin parameter.
		$user_finder = $this->user_finder_factory->get_user_finder();

		if ( is_null( $user_finder ) ) {
			// No querystring was present, this was not an attempt to log in.
			return $user_id;
		}

		$user_array = $user_finder->get_wp_user_array();

		if ( isset( $user_array['wp_user'] ) && $user_array['wp_user'] instanceof WP_User ) {
			$this->logger->debug( "Found `wp_user:{$user_array['wp_user']->ID}`." );
			$wp_user = $user_array['wp_user'];
			$user_id = $wp_user->ID;
		} elseif ( ! empty( $user_array['user_data'] ) ) {
			// If no WP_User account was found, but other user data was found that could be used for WooCommerce, prepopulate the checkout fields.
			$this->logger->debug( 'No wp_user found, preloading WooCommerce fields.', $user_array );
			$prefill_checkout_fields = function () use ( $user_array ) {
				$woocommerce_checkout = new Checkout( $this->logger );
				$woocommerce_checkout->prefill_checkout_fields( $user_array['user_data'] );
			};
			if ( did_action( 'woocommerce_init' ) ) {
				$prefill_checkout_fields();
			} else {
				add_action( 'woocommerce_init', $prefill_checkout_fields );
			}
			return $user_id;
		} else {
			$this->logger->debug( 'Could not find wp_user or user data using request URL.' );
			return $user_id;
		}

		$ip_address = $this->api->get_ip_address();

		if ( empty( $ip_address ) ) {
			// This would be empty during cron jobs and WP CLI.
			return $user_id;
		}

		// Log each attempt to log in, prevent too many attempts by any one IP.
		if ( ! $this->api->should_allow_login_attempt( "ip:{$ip_address}" ) ) {
			return $user_id;
		}

		// Rate limit too many failed attempts at logging in the one user.
		if ( ! $this->api->should_allow_login_attempt( "wp_user:{$wp_user->ID}" ) ) {
			return $user_id;
		}

		/**
		 * Although cookies will be set in a moment, they won't be available in the `$_COOKIE` array,
		 * and they need to be to log into wp-admin via the autologin URL.
		 *
		 * @see wp-admin/admin.php
		 * @see auth_redirect()
		 * @see wp_parse_auth_cookie()
		 */
		add_action(
			'set_auth_cookie',
			function ( $auth_cookie ) {
				global $_COOKIE;
				$_COOKIE[ AUTH_COOKIE ]        = $auth_cookie;
				$_COOKIE[ SECURE_AUTH_COOKIE ] = $auth_cookie;
			}
		);

		add_action(
			'set_logged_in_cookie',
			function ( $logged_in_cookie ) {
				global $_COOKIE;
				$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
			}
		);

		// @see https://developer.wordpress.org/reference/functions/wp_set_current_user/
		wp_set_current_user( $wp_user->ID, $wp_user->user_login );
		wp_set_auth_cookie( $wp_user->ID );
		add_action(
			'init',
			function () use ( $wp_user ) {
				do_action( 'wp_login', $wp_user->user_login, $wp_user );
			}
		);

		$this->logger->info( "User wp_user:{$wp_user->ID} logged in via {$user_array['source']}." );

		$this->maybe_redirect();

		return $user_id;
	}

	/**
	 * If the request is for wp-login.php, we should redirect to home or to the specified redirect_to url.
	 */
	protected function maybe_redirect(): void {

		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			// Cron, WP CLI.
			return;
		}

		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		// Check is the requested URL wp-login.php. Otherwise we don't want to redirect.
		$wp_login_endpoint = str_replace( get_site_url(), '', wp_login_url() );
		if ( ! stristr( $request_uri, $wp_login_endpoint ) ) {
			return;
		}

		// Check we're on wp-login.php?redirect_to=...
		// We won't have a nonce here if the link is from an email.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['redirect_to'] ) ) {

			$url = filter_var( wp_unslash( $_GET['redirect_to'] ), FILTER_SANITIZE_STRING );
			if ( false === $url ) {
				return;
			}
			$redirect_to = urldecode( $url );

		} else {
			// TODO: There's a filter determining what the destination URL should be when logging in a user.
			$redirect_to = get_site_url();
		}

		if ( wp_safe_redirect( $redirect_to ) ) {
			exit();
		}
	}
}
