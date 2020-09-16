<?php


namespace BH_WP_Autologin_URLs\api;

interface Data_Store_Interface {

	public function save( int $user_id, string $password, int $expires_at );

	public function get_value_for_password( string $password );
}
