<?php
/**
 * Tests for Rate_Limiter.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\API;

use BrianHenryIE\WP_Autologin_URLs\RateLimit\Rate;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Rate_Limiter
 */
class Rate_Limiter_WPUnit_Test extends \BrianHenryIE\WP_Autologin_URLs\WPUnit_Testcase {

	protected function get_sut(): Rate_Limiter {
		return new Rate_Limiter( Rate::custom( 3, DAY_IN_SECONDS ), 'bh-wp-autologin-urls-test' );
	}

	/**
	 * @covers ::is_limit_exceeded
	 */
	public function test_limit_not_exceeded_before_anything_is_recorded(): void {

		$sut = $this->get_sut();

		$this->assertFalse( $sut->is_limit_exceeded( 'ip:127.0.0.1' ) );
	}

	/**
	 * Checking the limit must not itself count as an event.
	 *
	 * @covers ::is_limit_exceeded
	 */
	public function test_is_limit_exceeded_does_not_record(): void {

		$sut = $this->get_sut();

		for ( $i = 0; $i < 10; $i++ ) {
			$sut->is_limit_exceeded( 'ip:127.0.0.2' );
		}

		$this->assertFalse( $sut->is_limit_exceeded( 'ip:127.0.0.2' ) );
	}

	/**
	 * Three per interval means the limit is reached – not exceeded – on the third recorded event.
	 *
	 * @covers ::record
	 * @covers ::is_limit_exceeded
	 */
	public function test_limit_exceeded_after_rate_events_recorded(): void {

		$sut = $this->get_sut();

		$sut->record( 'ip:127.0.0.3' );
		$sut->record( 'ip:127.0.0.3' );
		$this->assertFalse( $sut->is_limit_exceeded( 'ip:127.0.0.3' ), 'Two of three recorded should still be allowed.' );

		$sut->record( 'ip:127.0.0.3' );
		$this->assertTrue( $sut->is_limit_exceeded( 'ip:127.0.0.3' ) );
	}

	/**
	 * Identifiers must not share a counter.
	 *
	 * @covers ::record
	 * @covers ::is_limit_exceeded
	 */
	public function test_identifiers_are_counted_separately(): void {

		$sut = $this->get_sut();

		for ( $i = 0; $i < 5; $i++ ) {
			$sut->record( 'wp_user:1' );
		}

		$this->assertTrue( $sut->is_limit_exceeded( 'wp_user:1' ) );
		$this->assertFalse( $sut->is_limit_exceeded( 'wp_user:2' ) );
	}
}
