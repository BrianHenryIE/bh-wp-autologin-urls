<?php
/**
 * Tests for Transient_Data_Store.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Settings_Interface;

/**
 * Class Transient_Data_Store_Unit_Test
 */
class Transient_Data_Store_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	public function test_save() {

		$logger = new ColorLogger();
		$sut    = new Transient_Data_Store( $logger );

		$user_id    = 123;
		$password   = 'abc';
		$expires_at = 3600;

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array(
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'string' ),
					3600,
				),
				'times' => 1,
			)
		);

		$sut->save( $user_id, $password, $expires_at );
	}


	public function test_get_value_for_password() {

		$logger = new ColorLogger();
		$sut    = new Transient_Data_Store( $logger );

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => 'hashed_value',
			)
		);

		\WP_Mock::userFunction(
			'delete_transient',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'string' ) ),
				'times' => 1,
			)
		);

		$sut->get_value_for_code( 'abc' );

	}


}
