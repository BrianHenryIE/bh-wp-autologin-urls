<?php


namespace BH_WP_Autologin_URLs\api;

class Transient_Data_Store implements Data_Store_Interface {

	public const TRANSIENT_PREFIX = 'bh_autologin_';

	public function save( int $user_id, string $password, int $expires_at ): void {

		// In the unlikely event there is a collision, someone won't get to log in. Oh well.
		$transient_name = self::TRANSIENT_PREFIX . hash( 'sha256', $password );

		// Concatenate $user_id and $password so the database cannot be searched by username.
		$value = hash( 'sha256', $user_id . $password );

		// This could return false if not set.
		set_transient( $transient_name, $value, $expires_at );

	}

	public function get_value_for_password( string $password ): string {

		$transient_name = self::TRANSIENT_PREFIX . hash( 'sha256', $password );

		$value = get_transient( $transient_name );

		delete_transient( $transient_name );

		return $value;
	}
}
