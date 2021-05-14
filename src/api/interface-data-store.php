<?php
/**
 * Interface for classes saving, retrieving and expiring passwords.
 */

namespace BrianHenryIE\WP_Autologin_URLs\api;

/**
 * Interface Data_Store_Interface
 *
 * @package BH_WP_Autologin_URLs\api
 */
interface Data_Store_Interface {

	/**
	 * @param int    $user_id
	 * @param string $password
	 * @param int    $expires_at
	 */
	public function save( int $user_id, string $password, int $expires_at ): void;

	/**
	 * @param string $password
	 *
	 * @return string
	 */
	public function get_value_for_password( string $password ): string;
}
