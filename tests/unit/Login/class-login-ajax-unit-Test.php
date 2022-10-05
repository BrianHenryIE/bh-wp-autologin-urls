<?php

namespace BrianHenryIE\WP_Autologin_URLs\Login;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\Login\Login_Ajax
 */
class Login_Ajax_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::email_magic_link
	 * @covers ::__construct
	 */
	public function test_email_magic_link_happy_path(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'send_magic_link' => Expected::once(
					array(
						'expires_in' => 900,
					)
				),
			)
		);

		$login_ajax = new Login_Ajax( $api, $logger );

		$_POST['username'] = 'bob';

		\WP_Mock::userFunction(
			'check_ajax_referer',
			array(
				'args'   => array( Login_Ajax::class, false, false ),
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'args'       => array( 'bob' ),
				'return_arg' => true,
				'times'      => 1,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_user',
			array(
				'args'       => array( 'bob' ),
				'return_arg' => true,
				'times'      => 1,
			)
		);

		\WP_Mock::userFunction(
			'human_time_diff',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'int' ) ),
				'return' => '15 mins',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_send_json',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'array' ) ),
				'times' => 1,
			)
		);

		$login_ajax->email_magic_link();
	}

}
