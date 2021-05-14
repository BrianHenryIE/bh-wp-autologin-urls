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
 * @subpackage bh-wp-autologin-urls/api
 */

namespace BrianHenryIE\WP_Autologin_URLs\api;

use WP_User;

/**
 * Interface API_Interface
 */
interface API_Interface {

	/**
	 * Adds autologin code to all URLs in a long string for a user.
	 *
	 * @param string|string[]    $message The text presumably containing URLs.
	 * @param int|string|WP_User $user     A user id, email, username or user object.
	 * @param int                $expires_in Number ofs econds the password should work for.
	 *
	 * @return string|string[]
	 */
	public function add_autologin_to_message( string $message, $user, int $expires_in );

	/**
	 * Public function for other plugins to use on links.
	 *
	 * @param string             $url         The url to append the autologin code to. This must be a link to this site.
	 * @param int|string|WP_User $user        A valid user id, email, login or user object.
	 * @param int                $expires_in  The number of seconds the code will work for.
	 *
	 * @return string The URL with added autologin code if possible, or the unchanged url.
	 */
	public function add_autologin_to_url( string $url, $user, int $expires_in );

	/**
	 * Establishes if the autologin password used by the user is valid to log them in.
	 *
	 * @param int    $user_id User id the password purports to be for.
	 * @param string $password The password.
	 *
	 * @return mixed
	 */
	public function verify_autologin_password( int $user_id, string $password );

}
