<?php

namespace BrianHenryIE\WP_Klaviyo\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\Integrations\Klaviyo;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\Klaviyo\API\ProfilesApi;
use Codeception\Stub\Expected;


/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Integrations\Klaviyo
 */
class Klaviyo_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::is_querystring_valid
	 * @covers ::__construct
	 */
	public function test_is_querystring_valid_present(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_klaviyo_private_api_key' => Expected::once( 'secret' ),
			)
		);

		$sut         = new Klaviyo( $settings, $logger );
		$_GET['_kx'] = 'IgYA62x7VPCfu9FPAyNfJ12-2CkG9UW5q1bYjwNuc4E%3D.KxPkM5';

		$result = $sut->is_querystring_valid();

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::is_querystring_valid
	 */
	public function test_is_querystring_valid_absent(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_klaviyo_private_api_key' => Expected::once( 'secret' ),
			)
		);

		$sut = new Klaviyo( $settings, $logger );

		unset( $_GET['_kx'] );

		$result = $sut->is_querystring_valid();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::get_wp_user_array
	 * @covers ::get_user_data
	 */
	public function test_get_wp_user_array(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_klaviyo_private_api_key' => Expected::once( 'secret' ),
			)
		);

		$user_email = 'test@example.org';
		$user_id    = wp_create_user( 'test-user', '123', $user_email );

		$profiles_api = $this->makeEmpty(
			ProfilesApi::class,
			array(
				'exchange'   => array(
					'id' => 321,
				),
				'getProfile' => array(
					'$email' => $user_email,
				),
			)
		);

		$client = $this->makeEmpty(
			\BrianHenryIE\WP_Autologin_URLs\Klaviyo\Client::class,
			array(
				'Profiles' => $profiles_api,
			)
		);

		$sut = new Klaviyo( $settings, $logger, $client );

		$_GET['_kx'] = 'IgYA62x7VPCfu9FPAyNfJ12-2CkG9UW5q1bYjwNuc4E%3D.KxPkM5';

		$result = $sut->get_wp_user_array();

		$this->assertEquals( $user_id, $result['wp_user']->ID );

	}

}
