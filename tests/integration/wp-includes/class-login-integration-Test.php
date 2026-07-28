<?php
/**
 * Tests for BH_WP_Autologin_Login.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\Settings;

/**
 * Class Login_Develop_Test
 *
 * @see Login
 */
class Login_Integration_Test extends \Codeception\TestCase\WPTestCase {


	public function setUp(): void {
		parent::setUp();

		_delete_all_data();
	}

	/**
	 * Run the real login flow.
	 *
	 * `Login::process()` is hooked to the `determine_current_user` filter, and removes itself from
	 * the filter after it first runs (once during WPLoader bootstrap), so the filter cannot simply
	 * be applied here. Instantiate `Login` with the plugin's real API instance and call it directly.
	 *
	 * @param int|bool $user_id The already-determined user id, or 0/false if none.
	 * @return int|bool
	 */
	protected function process_login_request( $user_id = 0 ) {
		$api      = $GLOBALS['bh-wp-autologin-urls'];
		$settings = new Settings();
		$logger   = new ColorLogger();

		$login = new Login( $api, $settings, $logger );

		return $login->process( $user_id );
	}

	/**
	 * Simple successful login.
	 */
	public function test_login() {

		$user_id = $this->factory->user->create();

		$url = get_site_url();

		$url = add_autologin_to_url( $url, $user_id, 3600 );

		$this->go_to( $url );

		$current_user_id = $this->process_login_request();

		$this->assertEquals( $user_id, $current_user_id );
	}


	/**
	 * Simple unsuccessful login.
	 *
	 * TODO: This test fails naturally; a pre-assert would be useful to add confidence.
	 */
	public function test_login_failure() {

		$user_id = $this->factory->user->create();

		$url = get_site_url() . '/?autologin=' . $user_id . '~badautco';

		$this->go_to( $url );

		$current_user_id = $this->process_login_request();

		$this->assertEquals( 0, $current_user_id );
	}

	/**
	 * The previous implementation recorded malformed/failed attempts in
	 * `bh-wp-autologin-urls-failure-{ip}`/`-{user_id}` transients; that bookkeeping was replaced
	 * by brianhenryie/bh-wp-rate-limiter, which only records login attempts once a user has been
	 * identified from the querystring. Verify repeated logins for one user are rate limited.
	 */
	public function test_repeated_logins_rate_limited() {

		$user_id = $this->factory->user->create();

		// The rate limiter allows MAX_BAD_LOGIN_ATTEMPTS per day for the wp_user and for the IP,
		// so log in up to the limit, then confirm the next attempt is refused.
		for ( $attempt = 1; $attempt <= Login::MAX_BAD_LOGIN_ATTEMPTS; $attempt++ ) {
			// Vary `expires_in` — the API instance caches the generated code per user+expiry,
			// and each code is deleted after use.
			$url = add_autologin_to_url( get_site_url(), $user_id, 3600 + $attempt );
			$this->go_to( $url );
			wp_set_current_user( 0 );
			$current_user_id = $this->process_login_request();
			$this->assertEquals( $user_id, $current_user_id, "Attempt {$attempt} should have logged the user in." );
		}

		$url = add_autologin_to_url( get_site_url(), $user_id, 60 );
		$this->go_to( $url );
		wp_set_current_user( 0 );
		$current_user_id = $this->process_login_request();

		$this->assertEquals( 0, $current_user_id, 'Attempts above the rate limit should not log the user in.' );
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

		$current_user_id = $this->process_login_request();

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

		$current_user_id = $this->process_login_request();

		$this->assertEquals( 0, $current_user_id );
	}
}
