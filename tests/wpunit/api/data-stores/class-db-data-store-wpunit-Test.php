<?php

namespace BrianHenryIE\WP_Autologin_URLs\API\Data_Stores;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Stub\Expected;
use DateTimeImmutable;
use DateTimeZone;
use stdClass;
use wpdb;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\API\Data_Stores\DB_Data_Store
 */
class DB_Data_Store_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::create_db
	 */
	public function test_create_db(): void {

		$logger = new ColorLogger();

		// Assert table is absent.
		global $wpdb;

		// Need to remove the action that adds "TEMPORARY" into queries during test.
		remove_action( 'query', array( $this, '_drop_temporary_tables' ) );
		remove_action( 'query', array( $this, '_create_temporary_tables' ) );

		$wpdb->query( 'DROP TABLE IF EXISTS wp_autologin_urls' );

		$tables_before = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );

		$table_exists_before = array_reduce(
			$tables_before,
			function ( bool $carry, $element ) {
				return $carry || 'wp_autologin_urls' === $element[0];
			},
			false
		);

		assert( false === $table_exists_before );

		$sut = new DB_Data_Store( $logger );

		$sut->create_db();

		$this->assertFalse( $logger->hasErrorRecords() );

		$tables_after = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );

		$table_exists_after = array_reduce(
			$tables_after,
			function ( bool $carry, $element ) {
				return $carry || 'wp_autologin_urls' === $element[0];
			},
			false
		);

		$this->assertTrue( $table_exists_after );

		add_action( 'query', array( $this, '_drop_temporary_tables' ) );
		add_action( 'query', array( $this, '_create_temporary_tables' ) );
	}

	/**
	 * @covers ::save
	 */
	public function test_save(): void {

		$logger = new ColorLogger();

		$sut = new DB_Data_Store( $logger );
		$sut->create_db();

		$user_id    = 1;
		$code       = 'abcdef';
		$expires_in = 300; // 5 minutes.

		add_filter(
			'query',
			function ( $query ) {

				if ( false === strpos( $query, 'expires_at' ) ) {
					return $query;
				}

				$exception = new \Exception( $query );

				throw $exception;
			}
		);

		$query = '';

		try {
			$sut->save( $user_id, $code, $expires_in );
		} catch ( \Exception $exception ) {
			$query = $exception->getMessage();
		}

		// phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		// `INSERT INTO `wp_autologin_urls` (`expires_at`, `hash`, `userhash`) VALUES ('2022-03-08 20:06:08', 'bef57ec7f53a6d40beb640a780a639c83bc29ac8a9816f1fc6c5c6dcd93c4721', 'b9ef7926a7516f02aeab3ff6bb0d04b4e95456528026f6bfad1709d83e86039b')`.
		$matches = preg_match( '/INSERT INTO `\w+autologin_urls` \(`expires_at`, `hash`, `userhash`\) VALUES \(\'.*\', \'\w+\', \'\w+\'\)/', $query );

		$this->assertEquals( 1, $matches );
	}

	/**
	 * @covers ::get_value_for_code
	 */
	public function test_get_value_for_code(): void {
		$logger = new ColorLogger();

		$sut = new DB_Data_Store( $logger );
		$sut->create_db();

		$user_id    = 1;
		$code       = 'abcdef';
		$expires_in = 300; // 5 minutes.

		$sut->save( $user_id, $code, $expires_in );

		$result = $sut->get_value_for_code( $code );

		$expected = hash( 'sha256', $user_id . $code );

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @covers ::get_value_for_code
	 */
	public function test_get_value_for_code_delete_after(): void {
		$logger = new ColorLogger();

		$sut = new DB_Data_Store( $logger );

		$db_query_result             = new stdClass();
		$db_query_result->expires_at = ( (int) gmdate( 'Y' ) + 1 ) . '-01-01 01:01:01';
		$db_query_result->userhash   = 'not_relevant_to_this_test';

		global $wpdb;
		$wpdb_before = $wpdb;

		$wpdb             = $this->make(
			wpdb::class,
			array(
				'get_row' => Expected::once( $db_query_result ),
				'delete'  => Expected::once(),
			)
		);
		$wpdb->last_error = null; // Must be empty for this test.

		$sut->get_value_for_code( 'dummy_code', true );

		$wpdb = $wpdb_before;
	}

	/**
	 * @covers ::get_value_for_code
	 */
	public function test_get_value_for_code_do_not_delete_after(): void {
		$logger = new ColorLogger();

		$sut = new DB_Data_Store( $logger );

		$db_query_result             = new stdClass();
		$db_query_result->expires_at = ( (int) gmdate( 'Y' ) + 1 ) . '-01-01 01:01:01';
		$db_query_result->userhash   = 'not_relevant_to_this_test';

		global $wpdb;
		$wpdb_before = $wpdb;

		$wpdb             = $this->make(
			wpdb::class,
			array(
				'get_row' => Expected::once( $db_query_result ),
				'delete'  => Expected::never(),
			)
		);
		$wpdb->last_error = null; // Must be empty for this test.

		$sut->get_value_for_code( 'dummy_code', false );

		$wpdb = $wpdb_before;
	}

	/**
	 * @covers ::get_value_for_code
	 */
	public function test_get_value_for_expired_code_return_null(): void {
		$logger = new ColorLogger();

		$sut = new DB_Data_Store( $logger );
		$sut->create_db();

		$user_id    = 1;
		$code       = 'abcdef';
		$expires_in = 1; // 1 second.

		$sut->save( $user_id, $code, $expires_in );

		sleep( 2 );

		$result = $sut->get_value_for_code( $code );

		$this->assertNull( $result );
	}

	/**
	 * @covers ::delete_expired_codes
	 */
	public function test_delete_expired_codes(): void {

		$logger = new ColorLogger();

		$sut = new DB_Data_Store( $logger );
		$sut->create_db();

		$user_id    = 1;
		$code       = 'abcdef';
		$expires_in = 1; // 1 second.

		$sut->save( $user_id, $code, $expires_in );

		sleep( 2 );

		$before = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );

		$result = $sut->delete_expired_codes( $before );

		$this->assertEquals( 1, $result['deleted_count'] );
	}
}
