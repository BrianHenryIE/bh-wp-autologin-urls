<?php
/**
 * Tests for API. Tests core functionality of the plugin.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use Psr\Log\LoggerInterface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\API
 */
class API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/** @var Settings_Interface */
	protected $settings;

	public function setUp(): void {
		parent::_setUp();

		$this->settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_expiry_age'                           => WEEK_IN_SECONDS,
				'get_add_autologin_for_admins_is_enabled'  => false,
				'get_disallowed_subjects_regex_array'      => array(),
				'get_disallowed_subjects_regex_dictionary' => array(),
			)
		);

		$this->set_return_password_for_test_user();
	}

	/**
	 * Test helper method to ensure tests always generate the same password
	 * for user 123: ?autologin=123~mockpassw0rd.
	 *
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
	 */
	protected function set_return_password_for_test_user(): void {

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
		$specify_password = function( $password, $length, $special_chars, $extra_special_chars ) {
			return 'mockpassw0rd';
		};
		add_filter( 'random_password', $specify_password, 10, 4 );

	}

	/**
	 * Test helper method for verifying the number of transients in the database before and after executing methods.
	 *
	 * @return int
	 */
	private function get_plugin_transients_count(): int {

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
	 *
	 * @covers ::generate_code
	 */
	public function test_generate_code(): void {

		$logger = new ColorLogger();

		$data_store_mock = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$user_id = $this->factory->user->create();
		$user    = get_user_by( 'id', $user_id );

		$generated_code = $api->generate_code( $user, 3600 );

		$this->assertRegExp( '/^\d+~[A-Za-z\d]+$/', $generated_code );

	}

	/**
	 * What if the user id doesn't exist?
	 *
	 * It's better not to create a password for a user that is yet to exist!
	 * (recently deleted is weird, but probably not a security issue).
	 *
	 * @covers ::generate_code
	 */
	public function test_user_not_exist(): void {

		$logger = new ColorLogger();

		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$generated_code = $api->generate_code( null, 3600 );

		$this->assertNull( $generated_code );
	}

	/**
	 * Vanilla passing test where an autologin code is added to a url.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url(): void {

		$site_url = get_site_url();

		$url = "{$site_url}/product/woocommerce-product/";

		$expected = "{$site_url}/product/woocommerce-product/?autologin=123~mockpassw0rd";

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * If the url is not for this website, just return it.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_wrong_site(): void {

		$url      = 'http://example.com/test_add_autologin_to_url/';
		$expected = 'http://example.com/test_add_autologin_to_url/';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * When the user passed to the method is null, the method should return the url unchanged.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_null_user(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, null );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * If no user is found for the user id passed, just return the url.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_user_id_does_not_exist(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 321 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Confirm the method adds the autologin code when a user id is passed as the $user argument as an int.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_user_id_does_exist_is_int(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 123 );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * Confirm the method adds the autologin code when a user id is passed as the $user argument as a string.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_user_id_does_exist_is_string(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, '123' );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * If the user parameter passed is an email address for a user that does not exist,
	 * the url should be returned unchanged.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_user_email_does_not_exist(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 'brian@example.org', null );

		$this->assertEquals( $expected, $actual );
	}


	/**
	 * When a valid email address is passed as the email parameter, the
	 * autologin code should be added to the url.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_user_email_does_exist(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 'brianhenryie@gmail.com' );

		$this->assertEquals( $expected, $actual );
	}


	/**
	 * When a string is passed, assume it is a username. If there is no
	 * user account present, don't change the url.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_string_for_nonexistent_login(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 'nouserpresent' );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * When there is a user account for a string passed as $user, add the autologin code.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_string_for_existing_login(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/?autologin=123~mockpassw0rd';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, 'brian' );

		$this->assertEquals( $expected, $actual );

	}

	/**
	 * When something other than an actual WP_User object, an int (user id),
	 * a string (email) or a string (login) is passed, the method should just return
	 * the submitted url.
	 *
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_unexpected_input(): void {

		$url      = 'http://example.org/test_add_autologin_to_url/';
		$expected = 'http://example.org/test_add_autologin_to_url/';

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

		$actual = $api->add_autologin_to_url( $url, new \stdClass() );

		$this->assertEquals( $expected, $actual );
	}

	/**
	 * This method reads the testdata/testdata directory, tests the
	 * add_autologin_to_messages() method and compares the results with
	 * the testdata/expected directory's matching filenames.
	 *
	 * @covers ::add_autologin_to_message
	 */
	public function test_add_autologin_to_messages(): void {

		$logger          = new ColorLogger();
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $this->settings, $logger, $data_store_mock );

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

	/**
	 * @covers ::add_autologin_to_url
	 */
	public function test_add_autologin_to_url_use_login_php(): void {

		$logger          = new ColorLogger();
		$settings        = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_should_use_wp_login' => true,
			)
		);
		$data_store_mock = $this->makeEmpty( Data_Store_Interface::class );
		$api             = new API( $settings, $logger, $data_store_mock );

		$url = get_site_url() . '/my-account/';

		$user = wp_create_user( 'test-user', '123' );

		$result = $api->add_autologin_to_url( $url, $user );

		$expected = 'http://example.org/wp-login.php?redirect_to=http%3A%2F%2Fexample.org%2Fmy-account%2F&autologin=' . $user . '~mockpassw0rd';

		$this->assertEquals( $expected, $result );

	}
}
