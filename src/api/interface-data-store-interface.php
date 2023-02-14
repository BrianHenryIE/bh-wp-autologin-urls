<?php
/**
 * Interface for classes saving, retrieving and expiring passwords.
 *
 * @link       https://BrianHenry.ie
 * @since      1.2.1
 *
 * @package    bh-wp-autologin-urls
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use DateTimeInterface;

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
	 * @param string $code The autologin code in the user's URL.
	 * @param bool   $delete Indicate should the code be deleted after retrieving it.
	 *
	 * @return string|null
	 */
	public function get_value_for_code( string $code, bool $delete = true ): ?string;

	/**
	 * Delete codes that are no longer valid.
	 *
	 * @param DateTimeInterface $before The date from which to purge old codes.
	 *
	 * @return array{deleted_count:int|null}
	 */
	public function delete_expired_codes( DateTimeInterface $before ): array;
}
