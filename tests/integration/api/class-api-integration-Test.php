<?php
/**
 * Tests for API. Tests core functionality of the plugin.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\api;


use BH_WP_Autologin_URLs\Psr\Log\LogLevel;

/**
 * Class API_Integration_Test
 */
class API_Integration_Test extends \Codeception\TestCase\WPTestCase {

	protected $plugin_name;

	protected $version;

	/** @var Settings_Interface */
	protected $settings;

	public function _setUp() {
		parent::_setUp();

		// Codeception/WP-Browser tests return localhost as the site_url, whereas WP_UnitTestCase was returning example.org.
		add_filter( 'site_url', function( $url, $path, $scheme, $blog_id ) {
			return str_replace('localhost', 'example.org', $url );
		}, 10, 4 );

		$this->plugin_name = 'bh-wp-autologin-urls';
		$this->version = '1.2.0';

		$this->settings = new class() implements Settings_Interface {

			public function get_expiry_age(): int {
				return WEEK_IN_SECONDS;
			}

			public function get_add_autologin_for_admins_is_enabled(): bool {
				return false;
			}

			public function get_disallowed_subjects_regex_array(): array {
				return [];
			}

			public function get_disallowed_subjects_regex_dictionary():array {
				return [];
			}

			public function get_log_level(): string {
				return LogLevel::INFO;
			}

			public function get_should_use_wp_login(): bool {
				return false;
			}
		};

		$this->set_return_password_for_test_user();
	}

	/**
	 * Test helper method to ensure tests always generate the same password
	 * for user 123: ?autologin=123~mockpassw0rd.
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	protected function set_return_password_for_test_user() {

		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'brian',
				'user_email' => 'brianhenryie@gmail.com',
			)
		);

		// Specify the user id for later comparing.

		global $wpdb;

		$wpdb->get_results( $wpdb->prepare( 'UPDATE ' . $wpdb->users . ' SET ID = 123 WHERE ID = %d', $user_id ) );

		// Empty the cache or the old user id will continue to work.
		wp_cache_flush();

		// Specify the password for later comparing.
		add_filter(
			'random_password',
			function( $password, $length, $special_chars, $extra_special_chars ) {

				return 'mockpassw0rd';

			},
			10,
			4
		);

	}


	/**
	 * Test the global function is working.
	 */
	public function test_public_function_add_autologin_to_url() {

		$url      = 'http://example.org/test_public_function_add_autologin_to_url/';
		$expected = 'http://example.org/test_public_function_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$actual = add_autologin_to_url( $url, 123, 3600 );

		$this->assertEquals( $expected, $actual );

	}


	/**
	 * Test autologin URLs can be added using the url filter.
	 */
	public function test_filter_add_autologin_to_url() {

		$url      = 'http://example.org/test_public_function_add_autologin_to_url/';
		$expected = 'http://example.org/test_public_function_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$actual = apply_filters( 'add_autologin_to_url', $url, 123 );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * Test autologin URLs can be added using the message filter.
	 */
	public function test_filter_add_autologin_to_message() {

		$message      = 'Hello http://example.org/test_public_function_add_autologin_to_url/ Goodbye';
		$expected = 'Hello http://example.org/test_public_function_add_autologin_to_url/?autologin=123~mockpassw0rd Goodbye';

		$actual = apply_filters( 'add_autologin_to_message', $message, 123 );

		$this->assertEquals( $expected, $actual );

	}

}
