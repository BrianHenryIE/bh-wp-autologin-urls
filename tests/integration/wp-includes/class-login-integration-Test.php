<?php
/**
 * Tests for BH_WP_Autologin_Login.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\API\Settings;

/**
 * Class Login_Develop_Test
 *
 * @see Login
 */
class Login_Integration_Test extends \BrianHenryIE\WP_Autologin_URLs\WPUnit_Testcase {


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

		$login = new Login( $api, $settings, $this->logger );

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
	 * Failed autologin attempts are rate limited per IP and per user.
	 *
	 * The previous implementation recorded failures in `bh-wp-autologin-urls-failure-{ip}` /
	 * `-{user_id}` transients; that bookkeeping was replaced by brianhenryie/bh-wp-rate-limiter.
	 */
	public function test_repeated_bad_codes_rate_limited() {

		$user_id = $this->factory->user->create();

		for ( $attempt = 1; $attempt <= Login::MAX_BAD_LOGIN_ATTEMPTS; $attempt++ ) {

			$this->go_to( get_site_url() . '/?autologin=' . $user_id . '~badautocode' . $attempt );
			wp_set_current_user( 0 );

			$this->assertEquals( 0, $this->process_login_request(), "Bad attempt {$attempt} should not have logged the user in." );
		}

		// A valid code, but the limit for both `ip:` and `wp_user:` has been reached.
		$this->go_to( add_autologin_to_url( get_site_url(), $user_id, 3600 ) );
		wp_set_current_user( 0 );

		$this->assertEquals( 0, $this->process_login_request(), 'Attempts above the rate limit should not log the user in.' );
	}

	/**
	 * Only failures are counted, so a valid autologin URL can be used any number of times.
	 */
	public function test_repeated_successful_logins_not_rate_limited() {

		$user_id = $this->factory->user->create();

		for ( $attempt = 1; $attempt <= Login::MAX_BAD_LOGIN_ATTEMPTS + 1; $attempt++ ) {

			// `API::generate_code()` caches codes per `"$user_id~$seconds_valid"` per request, and
			// each code is single-use, so vary `expires_in` to get a fresh code each time.
			$this->go_to( add_autologin_to_url( get_site_url(), $user_id, 3600 + $attempt ) );
			wp_set_current_user( 0 );

			$this->assertEquals( $user_id, $this->process_login_request(), "Attempt {$attempt} should have logged the user in." );
		}
	}
}
