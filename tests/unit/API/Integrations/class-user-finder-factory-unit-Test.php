<?php

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\API\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Integrations\User_Finder_Factory
 */
class User_Finder_Factory_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::get_user_finder
	 * @covers ::__construct
	 */
	public function test_get_user_finder(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		$sut = new User_Finder_Factory( $api, $settings, $logger );

		$_GET[ Autologin_URLs::QUERYSTRING_PARAMETER_NAME ] = '123~abc';

		$result = $sut->get_user_finder();

		$this->assertInstanceOf( Autologin_URLs::class, $result );

	}

	/**
	 * Klaviyo integration allows supplying a Client instance for testing.
	 *
	 * @covers ::get_user_finder
	 */
	public function test_nullable_default_null(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_klaviyo_private_api_key' => Expected::once( 'secret' ),
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );

		$sut = new User_Finder_Factory( $api, $settings, $logger );

		$_GET[ Klaviyo::QUERYSTRING_PARAMETER_NAME ] = 'whatever';

		$result = $sut->get_user_finder();

		$this->assertInstanceOf( Klaviyo::class, $result );
	}

}
