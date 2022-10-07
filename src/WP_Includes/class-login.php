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
	 * @hooked plugins_loaded
	 *
	 * phpcs:disable WordPress.Security.NonceVerification.Recommended
	 */
	public function process(): void {

		// Check for bots.
		$user_agent = filter_input( INPUT_SERVER, 'HTTP_USER_AGENT' );
		$bot        = false !== strpos( $user_agent, 'bot' );
		if ( $bot ) {
			return;
		}

		// TODO: If we're logged in already, just return. It will save a lot of wasted processing.
		// Maybe use a cookie to only use an autologin URL once every x minutes.

		// Checks does the querystring contain an autologin parameter.
		$user_finder = $this->user_finder_factory->get_user_finder();

		if ( is_null( $user_finder ) ) {
			// No querystring was present, this was not an attempt to log in.
			return;
		}

		$user_array = $user_finder->get_wp_user_array();

		if ( isset( $user_array['wp_user'] ) && $user_array['wp_user'] instanceof WP_User ) {
			$wp_user = $user_array['wp_user'];

		} elseif ( ! empty( $user_array['user_data'] ) && 0 === get_current_user_id() ) {
			// If no WP_User account was found, but other user data was found that could be used for WooCommerce, prepopulate the checkout fields.
			$woocommerce_checkout = new Checkout( $this->logger );
			$woocommerce_checkout->prefill_checkout_fields( $user_array['user_data'] );
			return;
		} else {
			return;
		}

		$current_user = wp_get_current_user();

		if ( $current_user->ID === $wp_user->ID ) {
			// Already logged in.

			// TODO: always expire codes when used.
			// TODO: Test this thoroughly.

			$this->logger->debug( "User {$wp_user->ID} already logged in." );

			$this->maybe_redirect();

			return;
		}

		$ip_address = $this->api->get_ip_address();

		if ( empty( $ip_address ) ) {
			// This would be empty during cron jobs and WP CLI.
			return;
		}

		// Log each attempt to log in, prevent too many attempts by any one IP.
		if ( ! $this->api->should_allow_login_attempt( "ip:{$ip_address}" ) ) {
			return;
		}

		// Rate limit too many failed attempts at logging in the one user.
		if ( ! $this->api->should_allow_login_attempt( "wp_user:{$wp_user->ID}" ) ) {
			return;
		}

		// @see https://developer.wordpress.org/reference/functions/wp_set_current_user/
		wp_set_current_user( $wp_user->ID, $wp_user->user_login );
		wp_set_auth_cookie( $wp_user->ID );
		do_action( 'wp_login', $wp_user->user_login, $wp_user );

		$this->logger->info( "User wp_user:{$wp_user->ID} logged in via {$user_array['source']}." );

		$this->maybe_redirect();
	}

	/**
	 * If the request is for wp-login.php, we should redirect to home or to the specified redirect_to url.
	 *
	 * @return void
	 */
	protected function maybe_redirect(): void {

		$request_uri = filter_var( getenv( 'REQUEST_URI' ) );
		if ( empty( $request_uri ) ) {
			// Unusual.
			return;
		}

		// Check is the requested URL wp-login.php.
		$wp_login_endpoint = str_replace( get_site_url(), '', wp_login_url() );
		if ( ! stristr( $request_uri, $wp_login_endpoint ) ) {
			return;
		}

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

		wp_safe_redirect( $redirect_to );
		exit();
	}

}
