<?php

namespace BrianHenryIE\WP_Autologin_URLs\API\Integrations;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API\API_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Integrations\Autologin_URLs
 */
class Autologin_URLs_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::is_querystring_valid
	 * @covers ::__construct
	 */
	public function test_is_querystring_valid_present(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut               = new Autologin_URLs( $api, $logger );
		$_GET['autologin'] = '123~abc';

		$result = $sut->is_querystring_valid();

		$this->assertTrue( $result );
	}


	/**
	 * @covers ::is_querystring_valid
	 */
	public function test_is_querystring_valid_absent(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Autologin_URLs( $api, $logger );

		unset( $_GET['autologin'] );

		$result = $sut->is_querystring_valid();

		$this->assertFalse( $result );
	}


	/**
	 * @covers ::get_wp_user_array
	 */
	public function test_get_wp_user_array(): void {

		$user_id = wp_create_user( 'Autologin URLs Test User', 'test', 'user@example.org' );

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'verify_autologin_password' => Expected::once( true ),
			)
		);

		$sut = new Autologin_URLs( $api, $logger );

		$_GET['autologin'] = "{$user_id}~abcdef";

		$result = $sut->get_wp_user_array();

		$this->assertEquals( $user_id, $result['wp_user']->ID );
	}

	/**
	 * @covers ::get_wp_user_array
	 */
	public function test_get_wp_user_array_no_querystring(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'verify_autologin_password' => Expected::never(),
			)
		);

		$sut = new Autologin_URLs( $api, $logger );

		$result = $sut->get_wp_user_array();

		$this->assertNull( $result['wp_user'] );

	}


	/**
	 * @covers ::get_wp_user_array
	 */
	public function test_get_wp_user_array_no_valid_db_entry(): void {

		$user_id = wp_create_user( 'Autologin URLs Test User', 'test', 'user@example.org' );

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'verify_autologin_password' => Expected::once( false ),
			)
		);

		$sut = new Autologin_URLs( $api, $logger );

		$_GET['autologin'] = "{$user_id}~abcdef";

		$result = $sut->get_wp_user_array();

		$this->assertNull( $result['wp_user'] );
	}


	/**
	 * @covers ::get_wp_user_array
	 */
	public function test_get_wp_user_array_malformed_input(): void {

		$user_id = wp_create_user( 'Autologin URLs Test User', 'test', 'user@example.org' );

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'verify_autologin_password' => Expected::never(),
			)
		);

		$sut = new Autologin_URLs( $api, $logger );

		$_GET['autologin'] = "{$user_id}~ab!c%def";

		$result = $sut->get_wp_user_array();

		$this->assertNull( $result['wp_user'] );
	}
}
