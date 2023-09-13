<?php

namespace BrianHenryIE\WP_The_Newsletter_Plugin\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\Integrations\The_Newsletter_Plugin;


/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Integrations\The_Newsletter_Plugin
 */
class The_Newsletter_Plugin_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::is_querystring_valid
	 */
	public function test_is_querystring_valid_present(): void {

		$logger = new ColorLogger();

		$sut          = new The_Newsletter_Plugin( $logger );
		$_GET['nltr'] = 'MTsyO2h0dHBzOi8vZXhhbXBsZS5vcmc7OzBiZGE4OTBiZDE3NmQzZTIxOTYxNGRkZTk2NGNiMDdm';

		$result = $sut->is_querystring_valid();

		$this->assertTrue( $result );
	}


	/**
	 * @covers ::is_querystring_valid
	 */
	public function test_is_querystring_valid_absent(): void {

		$logger = new ColorLogger();

		$sut = new The_Newsletter_Plugin( $logger );

		unset( $_GET['nltr'] );

		$result = $sut->is_querystring_valid();

		$this->assertFalse( $result );
	}
}
