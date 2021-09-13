<?php
/**
 * Tests for API. Tests core functionality of the plugin.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use Psr\Log\LoggerInterface;
use Codeception\Stub\Expected;
use WP_User;

/**
 * Class API_Mock_Test
 */
class API_Unit_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}

	/**
	 * Include required files.
	 */
	public function setUp(): void {

		\WP_Mock::setUp();

		global $project_root_dir;

		require_once $project_root_dir . '/vendor/wordpress/wordpress/src/wp-includes/class-wp-user.php';
	}

	/**
	 * Simple, successful generation of autologin code.
	 *
	 * Method should call wp_generate_password, save the code and return the code.
	 *
	 * @see wp_generate_password()
	 */
	public function test_generate_code() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		$user     = $this->make( WP_User::class );
		$user->ID = 123;

		/**
		 * Inside private method.
		 *
		 * @see API::generate_password()
		 */
		\WP_Mock::userFunction(
			'wp_generate_password',
			array(
				'args'   => array( 12, false ),
				'times'  => 1,
				'return' => 'mockpassw0rd',
			)
		);

		$generated_code = $autologin_urls_api->generate_code( $user, 3600 );

		$this->assertEquals( '123~mockpassw0rd', $generated_code );

	}

	/**
	 * Basically the same as above but run it twice and verify it returns the same value.
	 */
	public function test_generate_code_cached() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		$user     = $this->createMock( '\WP_User' );
		$user->ID = 123;

		// phpcs:disable WordPress.WP.AlternativeFunctions.rand_rand
		\WP_Mock::userFunction(
			'wp_generate_password',
			array(
				'args'   => array( 12, false ),
				'times'  => 1,
				'return' => rand( 100000000000, 999999999999 ),
			)
		);

		$generated_code_1 = $autologin_urls_api->generate_code( $user, 3600 );

		$generated_code_2 = $autologin_urls_api->generate_code( $user, 3600 );

		$this->assertEquals( $generated_code_1, $generated_code_2 );

	}

	/**
	 * If there is no user object, generate_code() should return null.
	 */
	public function test_generate_code_null_user() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty( Data_Store_Interface::class );
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		$generated_code = $autologin_urls_api->generate_code( null, 3600 );

		$this->assertNull( $generated_code );

	}


	/**
	 * If the seconds paramater isn't given, it should be pulled from settings.
	 */
	public function test_generate_code_null_seconds_valid() {

		$settings_mock      = $this->makeEmpty(
			Settings_Interface::class,
			array( 'get_expiry_age' => 123456 )
		);
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		/**
		 * Inside private method.
		 *
		 * @see API::generate_password()
		 */
		\WP_Mock::userFunction(
			'wp_generate_password',
			array(
				'args'   => array( 12, false ),
				'times'  => 1,
				'return' => 'mockpassw0rd',
			)
		);

		$user = $this->createMock( '\WP_User' );

		$generated_code = $autologin_urls_api->generate_code( $user, null );

		$this->assertEquals( '0~mockpassw0rd', $generated_code );

	}

	/**
	 * If no saved entry for the password exists.
	 */
	public function test_verify_autologin_password_not_found() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'get_value_for_password' => false )
		);
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertFalse( $is_valid_autologin_password );

	}

	/**
	 * Weird scenario... maybe the same password was generated for two users and the
	 * earlier one is trying to log in, but the later one has cause a hash collision of sorts.
	 */
	public function test_verify_autologin_found_hash_mismatch() {
		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'get_value_for_password' => 'the-wrong-value' )
		);
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertFalse( $is_valid_autologin_password );

	}

	/**
	 * Verify the verify method.
	 */
	public function test_verify_autologin_password_success() {

		$value = hash( 'sha256', '123' . 'q1w2e3r4t5y6' );

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$logger_mock        = $this->makeEmpty( LoggerInterface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'get_value_for_code' => $value )
		);
		$autologin_urls_api = new API( $settings_mock, $logger_mock, $data_store_mock );

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertTrue( $is_valid_autologin_password );

	}

}
