<?php
/**
 * Tests for BH_WP_Autologin_Login.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Includes;

use BrianHenryIE\WP_Autologin_URLs\API\DB_Data_Store;

/**
 * Class Login_Develop_Test
 *
 * @see Login
 */
class Login_Integration_Test extends \Codeception\TestCase\WPTestCase {


	public function setUp() {
		parent::setUp();

		_delete_all_data();
	}

	/**
	 * Simple successful login.
	 */
	public function test_login() {

		$user_id = $this->factory->user->create();

		$url = get_home_url();

		$url = add_autologin_to_url( $url, $user_id, 3600 );

		$this->go_to( $url );

		do_action( 'plugins_loaded' );

		// Check is user logged in.
		$current_user_id = get_current_user_id();

		$this->assertEquals( $user_id, $current_user_id );
	}


	/**
	 * Simple unsuccessful login.
	 *
	 * TODO: This test fails naturally; a pre-assert would be useful to add confidence.
	 */
	public function test_login_failure() {

		$user_id = $this->factory->user->create();

		$url = get_home_url() . '/?autologin=' . $user_id . '~badautco';

		$this->go_to( $url );

		// This is needed for the test to pass... I guess because the
		// plugin is loaded in bootstrap.php and not by WordPress.
		do_action( 'plugins_loaded' );

		// Check is user logged in.
		$current_user_id = get_current_user_id();

		$this->assertEquals( 0, $current_user_id );

	}

	/**
	 * Test IP address is recorded when the querystring is malformed.
	 */
	public function test_bad_attempt_records_ip() {

		$autologin_querystring = 'a~testpassword';

		$url = get_home_url() . '/?autologin=' . $autologin_querystring;

		$this->go_to( $url );

		do_action( 'plugins_loaded' );

		$ip_address = str_replace( '.', '-', $_SERVER['REMOTE_ADDR'] );

		$failure_transient_name_ip = 'bh-wp-autologin-urls-failure-' . $ip_address;

		$failure_transient_ip = get_transient( $failure_transient_name_ip );

		$ip_failure = array(
			'count'     => 1,
			'malformed' => array( $autologin_querystring ),
			'users'     => array(),
		);

		$this->assertEquals( $ip_failure, $failure_transient_ip );
	}


	/**
	 * Test the user id is recorded for bad attempts to prevent an attack on a specific user.
	 */
	public function test_bad_user_records() {

		$autologin_querystring = '123~abaspass';

		$url = get_home_url() . '/?autologin=' . $autologin_querystring;

		$failure_transient_name_for_user = 'bh-wp-autologin-urls-failure-123';

		$failures_for_user = get_transient( $failure_transient_name_for_user );

		$this->assertFalse( $failures_for_user );

		$this->go_to( $url );

		do_action( 'plugins_loaded' );

		$failures_for_user = get_transient( $failure_transient_name_for_user );

		$this->assertNotFalse( $failures_for_user );

		$this->assertEquals( 1, $failures_for_user['count'] );

		$this->go_to( $url );

		do_action( 'plugins_loaded' );

		$failures_for_user = get_transient( $failure_transient_name_for_user );

		$this->assertEquals( 2, $failures_for_user['count'] );

	}

	/**
	 * Test when recording IP address for malformed attempt, the transient is recorded.
	 */
	public function test_bad_code_records() {

		$ip_address = str_replace( '.', '-', $_SERVER['REMOTE_ADDR'] );

		$failure_transient_name_ip = 'bh-wp-autologin-urls-failure-' . $ip_address;

		$url = get_home_url() . '/?autologin=a~testpassword';

		$this->go_to( $url );

		do_action( 'plugins_loaded' );

		$failure_transient_ip = get_transient( $failure_transient_name_ip );

		$this->assertNotFalse( $failure_transient_ip );

		$this->assertEquals( 1, $failure_transient_ip['count'] );

		$this->go_to( $url );

		do_action( 'plugins_loaded' );

		$failure_transient_ip = get_transient( $failure_transient_name_ip );

		$this->assertEquals( 2, $failure_transient_ip['count'] );

	}

	/**
	 * Confirm that when there are too many bad attempts from an IP, it is blocked
	 */
	public function test_ip_block() {

		$user_id = $this->factory->user->create();

		$url = get_home_url();

		$url = add_autologin_to_url( $url, $user_id, 3600 );

		$ip_failure = array(
			'count'     => 5,
			'users'     => array(),
			'malformed' => array(),
		);

		$ip_address = str_replace( '.', '-', $_SERVER['REMOTE_ADDR'] );

		$failure_transient_name_ip = 'bh-wp-autologin-urls-failure-' . $ip_address;

		set_transient( $failure_transient_name_ip, $ip_failure, DAY_IN_SECONDS );

		$this->go_to( $url );

		// This is needed for the test to pass... I guess because the
		// plugin is loaded in bootstrap.php and not by WordPress.
		do_action( 'plugins_loaded' );

		// Check is user logged in.
		$current_user_id = get_current_user_id();

		$this->assertEquals( 0, $current_user_id );

	}

	/**
	 * Test that after too many bad login attempts for a user, it won't try log them in anymore.
	 */
	public function test_user_attempts_block() {

		$user_id = $this->factory->user->create();

		$url = get_home_url();

		$url = add_autologin_to_url( $url, $user_id, 3600 );

		$user_failure = array(
			'count' => 6,
			'ip'    => array(),
		);

		$failure_transient_name = 'bh-wp-autologin-urls-failure-' . $user_id;

		set_transient( $failure_transient_name, $user_failure, DAY_IN_SECONDS );

		$this->go_to( $url );

		// This is needed for the test to pass... I guess because the
		// plugin is loaded in bootstrap.php and not by WordPress.
		do_action( 'plugins_loaded' );

		// Check is user logged in.
		$current_user_id = get_current_user_id();

		$this->assertEquals( 0, $current_user_id );

	}
}

