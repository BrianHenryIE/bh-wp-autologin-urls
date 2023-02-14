<?php
/**
 * Create global functions for other plugins to use.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

/**
 * This file is hooked early on plugins_loaded so other plugins can define the function first.
 *
 * This approach avoids users instantiating this object each time it is needed, thus preserving the cache.
 */

use BrianHenryIE\WP_Autologin_URLs\API_Interface;

if ( ! function_exists( 'add_autologin_to_url' ) ) {

	/**
	 * Adds an autologin parameter to a URLs when possible.
	 *
	 * @param string             $url         The URL to append the autologin code to. This must be a link to this site.
	 * @param int|string|WP_User $user        A valid user id, email, login or user object.
	 * @param ?int               $expires_in  The number of seconds the code will work for.
	 *
	 * @return string
	 */
	function add_autologin_to_url( string $url, $user, ?int $expires_in = null ): string {

		/**
		 * The main plugin class.
		 *
		 * @var API_Interface $plugin_api
		 */
		$plugin_api = $GLOBALS['bh-wp-autologin-urls'];

		return $plugin_api->add_autologin_to_url( $url, $user, $expires_in );
	}
}
