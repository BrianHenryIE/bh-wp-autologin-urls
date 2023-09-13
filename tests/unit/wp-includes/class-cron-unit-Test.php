<?php

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\API_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WP_Includes\Cron
 */
class Cron_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::delete_expired_codes
	 * @covers ::__construct
	 */
	public function test_delete_expired_codes(): void {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_expired_codes' => Expected::once(
					function () {
						return array();
					}
				),
			)
		);

		\WP_Mock::userFunction(
			'current_action',
			array(
				'times'  => 1,
				'return' => Cron::DELETE_EXPIRED_CODES_JOB_NAME,
			)
		);

		$sut = new Cron( $api, $logger );

		$sut->delete_expired_codes();
	}

	/**
	 * @covers ::schedule_job
	 */
	public function test_schedule_job(): void {
		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Cron( $api, $logger );

		\WP_Mock::userFunction(
			'wp_next_scheduled',
			array(
				'times'  => 1,
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_schedule_event',
			array(
				'times' => 1,
				'args'  => array( \WP_Mock\Functions::type( 'int' ), 'daily', 'bh_wp_autologin_urls_delete_expired_codes' ),
			),
		);

		$sut->schedule_job();
	}

	/**
	 * @covers ::schedule_job
	 */
	public function test_schedule_job_not_when_already_scheduled(): void {
		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Cron( $api, $logger );

		\WP_Mock::userFunction(
			'wp_next_scheduled',
			array(
				'times'  => 1,
				'return' => time(),
			)
		);

		\WP_Mock::userFunction(
			'wp_schedule_event',
			array(
				'times' => 0,
			),
		);

		$sut->schedule_job();
	}
}
