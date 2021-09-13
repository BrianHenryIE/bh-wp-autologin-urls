<?php
/**
 * Saves the expiring autologin code as a WordPress transient.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.1
 *
 * @package    bh-wp-autologin-urls
 * @subpackage bh-wp-autologin-urls/api
 */

namespace BrianHenryIE\WP_Autologin_URLs\api;

/**
 * Class Transient_Data_Store
 *
 * @package BH_WP_Autologin_URLs\api
 */
class Transient_Data_Store implements Data_Store_Interface {

	public const TRANSIENT_PREFIX = 'bh_autologin_';

	/**
	 * Save the hashed password and hashed value of user_id and password as a WordPress transient.
	 *
	 * @param int    $user_id The user id for the password being saved.
	 * @param string $code The unhashed password.
	 * @param int    $expires_in Number of seconds until the password should expire.
	 */
	public function save( int $user_id, string $code, int $expires_in ): void {

		// In the unlikely event there is a collision, someone won't get to log in. Oh well.
		$transient_name = self::TRANSIENT_PREFIX . hash( 'sha256', $code );

		// Concatenate $user_id and $password so the database cannot be searched by username.
		$value = hash( 'sha256', $user_id . $code );

		// This could return false if not set.
		set_transient( $transient_name, $value, $expires_in );

	}

	/**
	 * Return the hashed value corresponding to the inputted autologin code, or null if none is found.
	 *
	 * Deletes the transient when found, so autologin codes can only be used once.
	 *
	 * @param string $code The code as supplied to the user, which has never been saved by us.
	 *
	 * @return string|null
	 */
	public function get_value_for_code( string $code ): ?string {

		$transient_name = self::TRANSIENT_PREFIX . hash( 'sha256', $code );

		$value = get_transient( $transient_name );

		if ( false === $value ) {
			return null;
		}

		delete_transient( $transient_name );

		return $value;
	}
}
