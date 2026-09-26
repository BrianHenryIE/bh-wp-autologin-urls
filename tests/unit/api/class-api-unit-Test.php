<?php
/**
 * Tests for API. Tests core functionality of the plugin.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\Psr\Log\LoggerInterface;
use Codeception\Stub\Expected;
use WP_Mock;
use WP_Mock\Filter;
use WP_Mock\Functions;
use WP_Mock\Matcher\AnyInstance;
use WP_User;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\API
 */
class API_Unit_Test extends \BrianHenryIE\WP_Autologin_URLs\Unit_Testcase {

	/**
	 * Simple, successful generation of autologin code.
	 *
	 * Method should call wp_generate_password, save the code and return the code.
	 *
	 * @see wp_generate_password()
	 *
	 * @covers ::generate_code
	 * @covers ::generate_password
	 */
	public function test_generate_code() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		$user     = $this->make( WP_User::class );
		$user->ID = 123;

		/**
		 * Inside private method.
		 *
		 * @see API::generate_password()
		 */
		WP_Mock::userFunction(
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
	 *
	 * @covers ::generate_code
	 */
	public function test_generate_code_cached() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		$user     = $this->createMock( '\WP_User' );
		$user->ID = 123;

		// phpcs:disable WordPress.WP.AlternativeFunctions.rand_rand
		WP_Mock::userFunction(
			'wp_generate_password',
			array(
				'args'   => array( 12, false ),
				'times'  => 1,
				'return' => random_int( 100000000000, 999999999999 ),
			)
		);

		$generated_code_1 = $autologin_urls_api->generate_code( $user, 3600 );

		$generated_code_2 = $autologin_urls_api->generate_code( $user, 3600 );

		$this->assertEquals( $generated_code_1, $generated_code_2 );
	}

	/**
	 * If there is no user object, generate_code() should return null.
	 *
	 * @covers ::generate_code
	 */
	public function test_generate_code_null_user() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$data_store_mock    = $this->makeEmpty( Data_Store_Interface::class );
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		$generated_code = $autologin_urls_api->generate_code( null, 3600 );

		$this->assertNull( $generated_code );
	}


	/**
	 * If the seconds parameter isn't given, it should be pulled from settings.
	 *
	 * @covers ::generate_code
	 */
	public function test_generate_code_null_seconds_valid() {

		$settings_mock      = $this->makeEmpty(
			Settings_Interface::class,
			array( 'get_expiry_age' => 123456 )
		);
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'save' => Expected::once() )
		);
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		/**
		 * Inside private method.
		 *
		 * @see API::generate_password()
		 */
		WP_Mock::userFunction(
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
	 *
	 * @covers ::verify_autologin_password
	 */
	public function test_verify_autologin_password_not_found() {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'get_value_for_password' => false )
		);
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		WP_Mock::expectFilter( 'bh_wp_autologin_urls_should_delete_code_after_use', true, 123 );

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertFalse( $is_valid_autologin_password );
	}

	/**
	 * Weird scenario... maybe the same password was generated for two users and the
	 * earlier one is trying to log in, but the later one has cause a hash collision of sorts.
	 *
	 * @covers ::verify_autologin_password
	 */
	public function test_verify_autologin_found_hash_mismatch() {
		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$data_store_mock    = $this->makeEmpty(
			Data_Store_Interface::class,
			array( 'get_value_for_password' => 'the-wrong-value' )
		);
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertFalse( $is_valid_autologin_password );
	}

	/**
	 * Verify the verify method.
	 *
	 * @covers ::verify_autologin_password
	 */
	public function test_verify_autologin_password_success(): void {

		$user_id = 123;
		$code    = 'q1w2e3r4t5y6';

		$value = hash( 'sha256', "{$user_id}{$code}" );

		$settings_mock           = $this->makeEmpty( Settings_Interface::class );
				$data_store_mock = $this->makeEmpty(
					Data_Store_Interface::class,
					array( 'get_value_for_code' => $value )
				);
		$autologin_urls_api      = new API( $settings_mock, $this->logger, $data_store_mock );

		$is_valid_autologin_password = $autologin_urls_api->verify_autologin_password( 123, 'q1w2e3r4t5y6' );

		$this->assertTrue( $is_valid_autologin_password );
	}

	/**
	 * @covers ::get_request_details
	 * @covers ::get_browser_from_user_agent
	 * @dataProvider user_agent_provider
	 *
	 * @param string  $user_agent The HTTP User-Agent header.
	 * @param ?string $expected The friendly browser name expected.
	 */
	public function test_get_request_details_browser( string $user_agent, ?string $expected ): void {

		$settings_mock      = $this->makeEmpty( Settings_Interface::class );
		$data_store_mock    = $this->makeEmpty( Data_Store_Interface::class );
		$autologin_urls_api = new API( $settings_mock, $this->logger, $data_store_mock );

		$_SERVER['HTTP_USER_AGENT'] = $user_agent;
		$_SERVER['REMOTE_ADDR']     = '203.0.113.7';

		WP_Mock::passthruFunction( 'wp_unslash' );
		WP_Mock::passthruFunction( 'sanitize_text_field' );

		$result = $autologin_urls_api->get_request_details();

		unset( $_SERVER['HTTP_USER_AGENT'], $_SERVER['REMOTE_ADDR'] );

		$this->assertSame( $expected, $result['browser'] );
		$this->assertSame( '203.0.113.7', $result['ip_address'] );
		// No WooCommerce in unit tests, so no geolocation.
		$this->assertNull( $result['location'] );
	}

	/**
	 * @return array<string, array{0:string, 1:?string}>
	 */
	public function user_agent_provider(): array {
		return array(
			'chrome mac'     => array( 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36', 'Chrome on macOS' ),
			'safari iphone'  => array( 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1', 'Safari on iPhone' ),
			'firefox win'    => array( 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:130.0) Gecko/20100101 Firefox/130.0', 'Firefox on Windows' ),
			'edge win'       => array( 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36 Edg/128.0.0.0', 'Edge on Windows' ),
			'chrome android' => array( 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Mobile Safari/537.36', 'Chrome on Android' ),
			'unknown'        => array( 'curl/8.4.0', 'curl/8.4.0' ),
			'empty'          => array( '', null ),
		);
	}
}
