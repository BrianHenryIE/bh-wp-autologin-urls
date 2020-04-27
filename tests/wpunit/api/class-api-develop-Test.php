<?php
/**
 * Tests for API. Tests core functionality of the plugin.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\api;

use \BH_WP_Autologin_URLs\includes\BH_WP_Autologin_URLs;
use BH_WP_Autologin_URLs\includes\Settings_Interface;

/**
 * Class API_Develop_Test
 */
class API_Develop_Test extends \Codeception\TestCase\WPTestCase {

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

			public function get_expiry_age() {
				return WEEK_IN_SECONDS;
			}

			public function get_add_autologin_for_admins_is_enabled() {
				return false;
			}

			public function get_disallowed_subjects_regex_array() {
				return [];
			}

			public function get_disallowed_subjects_regex_dictionary() {
				return [];
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
	private function set_return_password_for_test_user() {

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
	 * Test helper method for verifying the number of transients in the database before and after executing methods.
	 *
	 * @return int
	 */
	private function get_plugin_transients_count() {

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$rowcount = $wpdb->get_var(
			'SELECT COUNT(*) FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_bh_autologin_%"'
		);

		// An empty set is null, whereas I want its count as zero.
		return null === $rowcount ? 0 : $rowcount;
	}


	/**
	 * Test a simple successful code generation and its being saved in the database.
	 */
	public function test_generate_code() {

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$plugin_transients_count_before = $this->get_plugin_transients_count();

		$generated_code = $api->generate_code( $user, 3600 );

		$plugin_transients_count_after = $this->get_plugin_transients_count();

		$this->assertRegExp( '/^\d+~[A-Za-z\d]+$/', $generated_code );

		$this->assertEquals( $plugin_transients_count_before + 1, $plugin_transients_count_after );

	}

	/**
	 * What if the user id doesn't exist?
	 *
	 * It's better not to create a password for a user that is yet to exist!
	 * (recently deleted is weird, but probably not a security issue).
	 */
	public function test_user_not_exist() {

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$generated_code = $api->generate_code( null, 3600 );

		$this->assertNull( $generated_code );
	}

	/**
	 * Verify that a code created through the API can be verified!
	 */
	public function test_verify_autologin_password() {

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$generated_code = $api->generate_code( $user, 3600 );

		preg_match( '/^\d+~(.+)$/', $generated_code, $output_array );

		$password = $output_array[1];

		$plugin_transients_count_before = $this->get_plugin_transients_count();

		$is_verified = $api->verify_autologin_password( $user_id, $password );

		$plugin_transients_count_after = $this->get_plugin_transients_count();

		$this->assertTrue( $is_verified );

		$this->assertEquals( $plugin_transients_count_before - 1, $plugin_transients_count_after );

	}

	/**
	 * Vanilla passing test where an autologin code is added to a url.
	 */
	public function test_add_autologin_to_url() {

		$url = 'http://example.org/product/woocommerce-product/';

		$expected = 'http://example.org/product/woocommerce-product/?autologin=123~mockpassw0rd';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );
	}


	/**
	 * When a null url is passed in, the method should just return it.
	 */
	public function test_add_autologin_to_url_null_url() {

		$url = null;

		$expected = null;

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * If the url is not for this website, just return it.
	 */
	public function test_add_autologin_to_url_wrong_site() {

		$url      = 'http://example.com/test_add_autologin_to_url/';
		$expected = 'http://example.com/test_add_autologin_to_url/';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * When the user passed to the method is null, the method should return the url unchanged.
	 */
	public function test_add_autologin_to_url_null_user() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, null );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * If no user is found for the user id passed, just return the url.
	 */
	public function test_add_autologin_to_url_user_id_does_not_exist() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 321 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Confirm the method adds the autologin code when a user id is passed as the $user argument as an int.
	 */
	public function test_add_autologin_to_url_user_id_does_exist_is_int() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Confirm the method adds the autologin code when a user id is passed as the $user argument as a string.
	 */
	public function test_add_autologin_to_url_user_id_does_exist_is_string() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, '123' );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * If the user parameter passed is an email address for a user that does not exist,
	 * the url should be returned unchanged.
	 */
	public function test_add_autologin_to_url_user_email_does_not_exist() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 'brian@example.org', null );

		$this->assertEquals( $expected, $actual );
	}


	/**
	 * When a valid email address is passed as the email parameter, the
	 * autologin code should be added to the url.
	 */
	public function test_add_autologin_to_url_user_email_does_exist() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 'brianhenryie@gmail.com' );

		$this->assertEquals( $expected, $actual );
	}


	/**
	 * When a string is passed, assume it is a username. If there is no
	 * user account present, don't change the url.
	 */
	public function test_add_autologin_to_url_string_for_nonexistant_login() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 'nouserpresent' );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * When there is a user account for a string passed as $user, add the autologin code.
	 */
	public function test_add_autologin_to_url_string_for_existing_login() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, 'brian' );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * When something other than an actual WP_User object, an int (user id),
	 * a string (email) or a string (login) is passed, the method should just return
	 * the submitted url.
	 */
	public function test_add_autologin_to_url_unexpected_input() {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		$actual = $api->add_autologin_to_url( $url, new \stdClass() );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * This method reads the testdata/testdata directory, tests the
	 * add_autologin_to_messages() method and compares the results with
	 * the testdata/expected directory's matching filenames.
	 */
	public function test_add_autologin_to_messages() {

		$api = new API( $this->plugin_name, $this->version, $this->settings );

		global $project_root_dir;

		$test_data_path   = $project_root_dir . '/tests/_data/testdata/';
		$test_result_path = $project_root_dir . '/tests/_data/expected/';

		$files = array_diff( scandir( $test_data_path ), array( '.', '..' ) );

		$user_id = 123;

		foreach ( $files as $file ) {

			// Check we have an expected output to compare with.
			$this->assertFileExists( $test_result_path . $file, 'No corresponding test result expectation for ' . $file . '.' );

			// phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$message = file_get_contents( $test_data_path . $file );

			$updated_string = $api->add_autologin_to_message( $message, $user_id );

			$this->assertStringEqualsFile( $test_result_path . $file, $updated_string );

		}

	}

}
