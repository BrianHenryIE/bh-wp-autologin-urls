<?php
/**
 * Interface for creating integrations.
 *
 * Implementation must use the querystring to return an array containing the WP_User object or a user_data array
 * with data which can be used to pre-fill the WooCommerce checkout.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\WP_Autologin_URLs\API\Integrations\User_Finder_Factory;
use WP_User;

/**
 * User finders do not actually log the user in, they just return the user data.
 *
 * @used-by User_Finder_Factory::get_user_finder()
 */
interface User_Finder_Interface {

	/**
	 * Determine is the querystring relevant for this integration present.
	 */
	public function is_querystring_valid(): bool;

	/**
	 * Try to find/validate the WP_User.
	 * Otherwise, return user_data array for WooCommerce fields.
	 * Return the source for logging.
	 *
	 * @return array{source:string, wp_user:WP_User|null, user_data?:array<string,string>}
	 */
	public function get_wp_user_array(): array;
}
