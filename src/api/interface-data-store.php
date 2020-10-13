<?php
/**
 * Interface for classes saving, retrieving and expiring passwords.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.1
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/api
 */

namespace BH_WP_Autologin_URLs\api;

/**
 * Interface Data_Store_Interface
 *
 * @package BH_WP_Autologin_URLs\api
 */
interface Data_Store_Interface {

	/**
	 * Save an autologin code for a user so it can be checked in future, before the expires_in time.
	 *
	 * @param int    $user_id The user id the code is being saved for.
	 * @param string $code The autologin code being used in the user's URL.
	 * @param int    $expires_in Number of seconds the code is valid for.
	 */
	public function save( int $user_id, string $code, int $expires_in ): void;

	/**
	 * Retrieve the value stored for the given autologin code, if it has not expired.
	 *
	 * TODO Would be nice to know the user id for logging.
	 *
	 * @param string $code The autologin code in the user's URL.
	 *
	 * @return string|null
	 */
	public function get_value_for_code( string $code ): ?string;
}
