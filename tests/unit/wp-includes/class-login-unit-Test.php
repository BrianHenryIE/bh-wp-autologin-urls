<?php

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\Integrations\User_Finder_Factory;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login
 */
class Login_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::process
	 */
	public function test_ignores_bots(): void {

		$this->markTestSkipped( 'Unable to mock $_SERVER HTTP_USER_AGENT input to filter_input.' );

		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla\/5.0 (compatible; bingbot\/2.0; +http:\/\/www.bing.com\/bingbot.htm)';

		$api                 = $this->makeEmpty( API_Interface::class );
		$settings            = $this->makeEmpty( Settings_Interface::class );
		$logger              = new ColorLogger();
		$user_finder_factory = $this->makeEmpty(
			User_Finder_Factory::class,
			array(
				'get_user_finder' => Expected::never(),
			)
		);

		$sut = new Login( $api, $settings, $logger, $user_finder_factory );

		$sut->process( false );
	}

}
