<?php
/**
 * Tests for API. Tests core functionality of the plugin.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\api;

use BH_WP_Autologin_URLs\includes\Settings_Interface;

/**
 * Class API_Mock_Test
 */
class API_Mock_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}


	/**
	 * The plugin name. Unlikely to change.
	 *
	 * @var string Plugin name.
	 */
	private $plugin_name = 'bh-wp-autologin-urls';

	/**
	 * The plugin version, matching the version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

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
	 * @see wp_generate_password()
	 * @see set_transient()
	 */
	public function test_generate_code() {

		$settings_mock      = $this->createMock( Settings_Interface::class );
		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

		$user     = $this->createMock( '\WP_User' );
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

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
			)
		);

		$generated_code = $autologin_urls_api->generate_code( $user, 3600 );

		$this->assertEquals( '123~mockpassw0rd', $generated_code );

	}

	/**
	 * Basically the same as above but run it twice and verify it returns the same value.
	 */
	public function test_generate_code_cached() {

		$settings_mock      = $this->createMock( Settings_Interface::class );
		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

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

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
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

		$settings_mock      = $this->createMock( Settings_Interface::class );
		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

		$generated_code = $autologin_urls_api->generate_code( null, 3600 );

		$this->assertNull( $generated_code );

	}


	/**
	 * If the seconds paramater isn't given, it should be pulled from settings.
	 */
	public function test_generate_code_null_seconds_valid() {

		$settings_mock = \Mockery::mock( 'Settings_Interface' );

		$settings_mock->shouldReceive( 'get_expiry_age' )
					  ->andReturn( 123456 );

		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

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

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'int' ),
				),
				'times' => 1,
			)
		);

		$user = $this->createMock( '\WP_User' );

		$generated_code = $autologin_urls_api->generate_code( $user, null );

		$this->assertEquals( '0~mockpassw0rd', $generated_code );

	}

	/**
	 * If the transient does not exist.
	 */
	public function test_verify_autologin_password_not_found() {

		$settings_mock      = $this->createMock( Settings_Interface::class );
		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => false,
			)
		);

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertFalse( $is_valid_autologin_password );

	}

	/**
	 * Weird scenario... maybe the same password was generated for two users and the
	 * earlier one is trying to log in, but the later one has overwritten their transient.
	 */
	public function test_verify_autologin_transient_found_hash_mismatch() {

		$settings_mock      = $this->createMock( Settings_Interface::class );
		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => 'has-mismatch-value',
			)
		);

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertFalse( $is_valid_autologin_password );

	}

	/**
	 * Verify the correct transient is fetched then deleted.
	 */
	public function test_verify_autologin_password_success() {

		$settings_mock      = $this->createMock( Settings_Interface::class );
		$autologin_urls_api = new API( $this->plugin_name, $this->version, $settings_mock );

		$autologin_code_hashed = hash( 'sha256', 'q1w2e3r4t5y6' );
		$transient_name        = 'bh_autologin_' . $autologin_code_hashed;

		$value = hash( 'sha256', '123q1w2e3r4t5y6' );

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( $transient_name ),
				'times'  => 1,
				'return' => $value,
			)
		);

		\WP_Mock::userFunction(
			'delete_transient',
			array(
				'args'   => array( $transient_name ),
				'times'  => 1,
				'return' => true,
			)
		);

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertTrue( $is_valid_autologin_password );

	}

}
