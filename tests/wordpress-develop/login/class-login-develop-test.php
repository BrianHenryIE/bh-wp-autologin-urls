<?php
/**
 * Tests for BH_WP_Autologin_Login.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\login;

/**
 * Class BH_WP_Autlogin_URLs_Login_Develop_Test
 *
 * @see Login
 */
class Login_Develop_Test extends \WP_UnitTestCase {

	/**
	 * Simple successful login.
	 */
	public function test_login() {

		$user_id = $this->factory->user->create();

		$url = get_home_url();

		$url = add_autologin_to_url( $url, $user_id, 3600 );

		$this->go_to( $url );

		// This is needed for the test to pass... I guess because the
		// plugin is loaded in bootstrap.php and not by WordPress.
		do_action( 'plugins_loaded' );

		// Check is user logged in.
		$current_user_id = get_current_user_id();

		$this->assertEquals( $user_id, $current_user_id );

	}
}

