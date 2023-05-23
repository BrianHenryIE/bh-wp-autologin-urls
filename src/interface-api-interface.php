<?php
/**
 * Defines the core plugin functionality
 *
 * The public functions for use by other classes in the plugin and by other plugin developers.
 *
 * @link       https://BrianHenry.ie
 * @since      1.0.0
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use DateTimeInterface;
use WP_User;

interface API_Interface {

	/**
	 * Adds autologin code to all URLs in a long string for a user.
	 *
	 * @param string             $message The text presumably containing URLs.
	 * @param int|string|WP_User $user     A user id, email, username or user object.
	 * @param ?int               $expires_in Number ofs econds the password should work for.
	 *
	 * @return string
	 */
	public function add_autologin_to_message( string $message, $user, ?int $expires_in = null );

	/**
	 * Public function for other plugins to use on links.
	 *
	 * @param string             $url         The url to append the autologin code to. This must be a link to this site.
	 * @param int|string|WP_User $user        A valid user id, email, login or user object.
	 * @param ?int               $expires_in  The number of seconds the code will work for.
	 *
	 * @return string The URL with added autologin code if possible, or the unchanged url.
	 */
	public function add_autologin_to_url( string $url, $user, ?int $expires_in = null ): string;

	/**
	 * Establishes if the autologin password used by the user is valid to log them in.
	 *
	 * @param int    $user_id User id the password purports to be for.
	 * @param string $password The password.
	 *
	 * @return bool
	 */
	public function verify_autologin_password( int $user_id, string $password ): bool;

	/**
	 * Purge codes that are no longer valid.
	 *
	 * @param ?DateTimeInterface $before The date from which to purge old codes.
	 *
	 * @return array{deleted_count:int|null}
	 */
	public function delete_expired_codes( ?DateTimeInterface $before = null ): array;

	/**
	 * Records each login attempt and checks if the same user/ip/querystring has been used too many times today.
	 *
	 * @param string $identifier An IP address or user login name to rate limit by.
	 *
	 * @return bool
	 */
	public function should_allow_login_attempt( string $identifier ): bool;

	/**
	 * Get the IP address for the current request.
	 *
	 * @used-by Login::process()
	 *
	 * @return ?string IPv4|v6 IP address or null, presumable if CLI/cron.
	 */
	public function get_ip_address(): ?string;

	/**
	 * Maybe send email to the wp_user with a "magic link" to log in.
	 *
	 * @param string  $username_or_email_address The username or email as entered by the user in the login form.
	 * @param ?string $url The page the user should be sent, e.g. checkout, my-account. Defaults to site URL.
	 * @param int     $expires_in Number of seconds the link should be valid. Defaults to 15 minutes.
	 *
	 * @return array{username_or_email_address:string, expires_in:int, expires_in_friendly:string, wp_user?:WP_User, template_path?:string, success:bool, error?:bool, message?:string}
	 */
	public function send_magic_link( string $username_or_email_address, ?string $url = null, int $expires_in = 900 ): array;
}
