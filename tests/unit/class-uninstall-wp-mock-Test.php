<?php
/**
 * Tests the uninstallion procedure.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs;

/**
 * Class Uninstall_WP_Mock_Test
 */
class Uninstall_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies uninstall does not run without 'WP_UNINSTALL_PLUGIN' defined.
	 */
	public function test_exit_without_defined() {

		$this->markTestSkipped( "Can't test for exit();" );

		global $plugin_root_dir;

		global $wpdb;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$wpdb = \Mockery::mock( '\wpdb' );

		// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
		$wpdb->shouldReceive( 'query' )
			 ->never();

		require_once $plugin_root_dir . '/uninstall.php';

	}

	/**
	 * Verifies transients are deleted during uninstall.
	 */
	public function test_transients_deleted() {

		define( 'WP_UNINSTALL_PLUGIN', 'WP_UNINSTALL_PLUGIN' );

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'return' => $plugin_root_dir . '/',
			)
		);

		// Make the mock available globally to be used in uninstall.php.
		global $wpdb;

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.P
		$wpdb = \Mockery::mock( '\wpdb' );

		$delete_transients_sql = "DELETE 
            FROM wp_options 
            WHERE option_name LIKE '\_transient\_bh_autologin_\_%' 
            OR option_name LIKE '\_transient\_timeout\_bh_autologin_\_%'";

		$delete_transients_sql = str_replace( array( "\n", "\r", "\t" ), '', $delete_transients_sql );

		$delete_transients_sql = trim( preg_replace( '!\s+!', ' ', $delete_transients_sql ) );

		$wpdb->shouldReceive( 'query' )
			->once()
			->with( $delete_transients_sql );

		$wpdb->shouldReceive( 'prepare' )
			 ->once()
			->andReturn( $delete_transients_sql );

		$wpdb->shouldReceive( 'esc_like' )
			 ->andReturn( '\_transient\_timeout\_bh\_autologin\_\%' );

		$wpdb->options = 'wp_options';

		// Should delete all three options.
		\WP_Mock::userFunction(
			'delete_option',
			array(
				'times' => 3,
			)
		);

		include $plugin_root_dir . '/uninstall.php';

		$this->assertEquals( 3, $wpdb->mockery_getExpectationCount() );

	}

}
