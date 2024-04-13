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

use BrianHenryIE\WP_Autologin_URLs\API\Data_Stores\Transient_Data_Store;
use BrianHenryIE\WP_Autologin_URLs\API\Integrations\Autologin_URLs;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\RateLimit\Rate;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login;
use BrianHenryIE\WP_Autologin_URLs\WP_Rate_Limiter\WordPress_Rate_Limiter;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Geolocation;
use WP_User;

/**
 * The core plugin functionality.
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
	 * Get the actual WP_User object from the id/username/email. Null when not found.
	 *
	 * @param null|int|string|WP_User $user A valid user id, email, login or user object.
	 */
	public function get_wp_user( $user ): ?WP_User {

		if ( $user instanceof WP_User ) {
			return $user;
		}

		if ( is_int( $user ) ) {

			$user = get_user_by( 'ID', $user );

		} elseif ( is_numeric( $user ) && 0 !== intval( $user ) ) {

			// When string '123' is passed as the user id, convert it to an int.
			$user = absint( $user );
			$user = get_user_by( 'ID', $user );

		} elseif ( is_string( $user ) && is_email( $user ) ) {

			// When a string which is an email is passed.
			$user = get_user_by( 'email', $user );

		} elseif ( is_string( $user ) ) {

			// When any other string is passed, assume it is a username.
			$user = get_user_by( 'login', $user );
		}

		return $user instanceof WP_User ? $user : null;
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

		/**
		 * If The Newsletter Plugin tracking is already added to the link, use the integration to handle logging in,
		 * rather than adding an autologin code.
		 */
		if ( stristr( $url, 'nltr=' ) ) {
			return $url;
		}

		$user = $this->get_wp_user( $user );

		if ( is_null( $user ) ) {
			return $url;
		}

		// Although this method could return null, the checks to prevent that have already
		// taken place in this method.
		$autologin_code = $this->generate_code( $user, $expires_in );

		/**
		 * The typical `wp-login.php` can be configured to be a different URL.
		 *
		 * @var array{host:string,path:string} $parsed_wp_login_url
		 */
		$parsed_wp_login_url = wp_parse_url( wp_login_url() );
		$parsed_url          = wp_parse_url( $url );

		// Redirecting to wp-login.php always send the user to a login screen, defeating the point of this plugin.
		while ( isset( $parsed_url['host'], $parsed_url['path'] ) && $parsed_url['host'] === $parsed_wp_login_url['host'] && $parsed_url['path'] === $parsed_wp_login_url['path'] ) {

			if ( isset( $parsed_url['query'] ) ) {
				parse_str( $parsed_url['query'], $query_get );

				if ( isset( $query_get['redirect_to'] ) ) {
					$url = $query_get['redirect_to'];
				} else {
					$url = get_site_url();
				}
			} else {
				// There must be a neater way of assigning $url but today I want to work on something else.
				$url = get_site_url();
			}

			$parsed_url = wp_parse_url( $url );
		}

		if ( ! $this->settings->get_should_use_wp_login() ) {
			$user_link = add_query_arg( Autologin_URLs::QUERYSTRING_PARAMETER_NAME, $autologin_code, $url );
		} else {
			// TODO: If it is already a wp-login.php URL, deconstruct to avoid a Russian doll situation.
			$user_link = add_query_arg( Autologin_URLs::QUERYSTRING_PARAMETER_NAME, $autologin_code, wp_login_url( $url ) );
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
		/**
		 * Filter to enable reuse of autologin codes.
		 *
		 * The codes continue to be deleted at their expiry date, but returning false here allows each code to be used
		 * an unlimited number of times until then.
		 *
		 * @param bool $delete Indicate if the code should be deleted after use.
		 * @param int $user_id The id of the user we are attempting to log in.
		 */
		$delete = apply_filters( 'bh_wp_autologin_urls_should_delete_code_after_use', true, $user_id );

		$saved_details = $this->data_store->get_value_for_code( $password, $delete );

		if ( null === $saved_details ) {
			return false;
		}

		$provided_details = hash( 'sha256', $user_id . $password );

		return hash_equals( $provided_details, $saved_details );
	}

	/**
	 * Purge codes that are no longer valid.
	 *
	 * @param ?DateTimeInterface $before The date from which to purge old codes.
	 *
	 * @return array{deleted_count:int|null}
	 * @throws \Exception A DateTime exception when 'now' is used. I.e. never.
	 */
	public function delete_expired_codes( ?DateTimeInterface $before = null ): array {
		$before = $before ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		return $this->data_store->delete_expired_codes( $before );
	}

	/**
	 * Records each login attempt and checks if the same user/ip/querystring has been used too many times today.
	 *
	 * Transient e.g. `_transient_bh-wp-autologin-urls/bh-wp-autologin-urlsip-127.0.0.1-86400`.
	 * Transient e.g. `_transient_bh-wp-autologin-urls/bh-wp-autologin-urlswp_user-1-86400`.
	 *
	 * @param string $identifier An IP address or user login name to rate limit by.
	 */
	public function should_allow_login_attempt( string $identifier ): bool {

		$allowed_access_count = Login::MAX_BAD_LOGIN_ATTEMPTS;
		$interval             = Login::MAX_BAD_LOGIN_PERIOD_SECONDS;

		$rate = Rate::custom( $allowed_access_count, $interval );

		static $rate_limiter;

		if ( empty( $rate_limiter ) ) {
			$rate_limiter = new WordPress_Rate_Limiter( $rate, 'bh-wp-autologin-urls' );
		}

		try {
			$status = $rate_limiter->limitSilently( $identifier );
		} catch ( \RuntimeException $e ) {
			$this->logger->error(
				'Rate Limiter encountered an error when storing the access count.',
				array(
					'exception'            => $e,
					'identifier'           => $identifier,
					'interval'             => $interval,
					'allowed_access_count' => $allowed_access_count,
				)
			);

			// Play it safe and don't log them in.
			return false;
		}

		/**
		 * TODO: Log the $_REQUEST data.
		 */
		if ( $status->limitExceeded() ) {

			$this->logger->notice(
				"{$identifier} blocked with {$status->getRemainingAttempts()} remaining attempts for rate limit {$allowed_access_count} per {$interval} seconds.",
				array(
					'identifier'           => $identifier,
					'interval'             => $interval,
					'allowed_access_count' => $allowed_access_count,
					'status'               => $status,
					'_SERVER'              => $_SERVER,
				)
			);

			return false;

		} else {

			$this->logger->debug(
				"{$identifier} allowed with {$status->getRemainingAttempts()} remaining attempts for rate limit {$allowed_access_count} per {$interval} seconds.",
				array(
					'identifier'           => $identifier,
					'interval'             => $interval,
					'allowed_access_count' => $allowed_access_count,
					'status'               => $status,
				)
			);
		}

		return true;
	}

	/**
	 * Finds the IP address of the current request.
	 *
	 * A copy of WooCommerce's IP address logic.
	 * If behind Cloudflare, the Cloudflare plugin should be installed.
	 *
	 * @return ?string
	 */
	public function get_ip_address(): ?string {

		if ( class_exists( WC_Geolocation::class ) ) {
			$ip_address = WC_Geolocation::get_ip_address();
		} elseif ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
				$ip_address = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip_address = (string) rest_is_ip_address( trim( current( preg_split( '/,/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = filter_var( wp_unslash( $_SERVER['REMOTE_ADDR'] ), FILTER_VALIDATE_IP );
		}

		// Return null, not false, or the string.
		return empty( $ip_address ) ? null : $ip_address;
	}

	/**
	 * Maybe send email to the wp_user with a "magic link" to log in.
	 *
	 * TODO: Add settings options: enable/disable feature, configure subject, configure expiry time.
	 *
	 * @param string  $username_or_email_address The username or email as entered by the user in the login form.
	 * @param ?string $url The page the user should be sent, e.g. checkout, my-account. Defaults to site URL.
	 * @param int     $expires_in Number of seconds the link should be valid. Defaults to 15 minutes.
	 *
	 * @return array{username_or_email_address:string, expires_in:int, expires_in_friendly:string, wp_user?:WP_User, template_path?:string, success:bool, error?:bool, message?:string}
	 */
	public function send_magic_link( string $username_or_email_address, ?string $url = null, int $expires_in = 900 ): array {

		$url = $url ?? get_site_url();

		$expires_in_friendly = human_time_diff( time() - $expires_in );

		$result = array(
			'username_or_email_address' => $username_or_email_address,
			'expires_in'                => $expires_in,
			'expires_in_friendly'       => $expires_in_friendly,
		);

		$wp_user = get_user_by( 'login', $username_or_email_address );

		if ( ! ( $wp_user instanceof WP_User ) ) {

			$wp_user = get_user_by( 'email', $username_or_email_address );

			if ( ! ( $wp_user instanceof WP_User ) ) {

				// NB: Do not tell the user if the username exists.
				$result['success'] = false;

				$this->logger->debug( "No WP_User found for {$username_or_email_address}", array( 'result' => $result ) );

				return $result;
			}
		}

		$result['wp_user'] = $wp_user;

		$to = $wp_user->user_email;

		$subject = __( 'Sign-in Link', 'bh-wp-autologin-urls' );

		$template = 'email/magic-link.php';

		$template_email_magic_link = WP_PLUGIN_DIR . '/' . plugin_dir_path( $this->settings->get_plugin_basename() ) . 'templates/' . $template;

		// Check the child theme for template overrides.
		if ( file_exists( get_stylesheet_directory() . $template ) ) {
			$template_email_magic_link = get_stylesheet_directory() . $template;
		} elseif ( file_exists( get_stylesheet_directory() . 'templates/' . $template ) ) {
			$template_email_magic_link = get_stylesheet_directory() . 'templates/' . $template;
		}

		$autologin_url = $this->add_autologin_to_url( $url, $wp_user, $expires_in );

		// Add a marker for later logging use of the email.
		$autologin_url = add_query_arg( array( 'magic' => 'true' ), $autologin_url );

		/**
		 * Allow overriding the email template.
		 *
		 * @var string $autologin_url The URL which will log the user in.
		 * @var string $expires_in_friendly Human-readable form of the number of seconds until expiry.
		 */
		$template_email_magic_link = apply_filters( 'bh_wp_autologin_urls_magic_link_email_template', $template_email_magic_link );

		$result['template_path'] = $template_email_magic_link;

		ob_start();

		include $template_email_magic_link;

		// NB: Do not log the message because it contains a password!
		$message = ob_get_clean();

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$mail_success = wp_mail( $to, $subject, $message, $headers );

		$result['success'] = $mail_success;

		if ( false === $mail_success ) {
			$result['error'] = true;
			$this->logger->error( 'Failed sending magic login email.', array( 'result' => $result ) );
		} else {

			$result['message'] = 'If a user exists for `' . $username_or_email_address . '` an email has been sent to that user with a login link.';

			$this->logger->info( "Magic login email sent to wp_user:{$wp_user->ID}." );
		}

		return $result;
	}
}
