<?php
/**
 * Tests for the root plugin file.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\WC_Shipment_Tracking_Updates\Includes\BH_WC_Shipment_Tracking_Updates;
use BrianHenryIE\WP_Autologin_URLs\API\API;
use BrianHenryIE\WP_Autologin_URLs\Includes\BH_WP_Autologin_URLs;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_Unit_Test extends \Codeception\Test\Unit {
	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include(): void {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( BH_WP_Autologin_URLs::class, '__construct' ),
			function( $api, $settings, $logger ) {}
		);
		// \Patchwork\redefine(
		// array( Settings::class, 'get_plugin_slug' ),
		// function(): string {
		// return 'bh-wc-shipment-tracking-updates'; }
		// );

		// \Patchwork\redefine(
		// array( Settings::class, 'get_plugin_basename' ),
		// function(): string {
		// return 'bh-wc-shipment-tracking-updates/bh-wc-shipment-tracking-updates.php'; }
		// );

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\Patchwork\redefine(
			array( Logger::class, '__construct' ),
			function( $settings ) {}
		);

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wp-autologin-urls/bh-wp-autologin-urls.php',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		// \WP_Mock::userFunction(
		// 'get_option',
		// array(
		// 'args'   => array( 'bh_wp_logger_log_level', 'info' ),
		// 'return' => 'notice',
		// )
		// );

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins' ),
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_current_user_id'
		);

		\WP_Mock::userFunction(
			'wp_normalize_path',
			array(
				'return_arg' => true,
			)
		);

		ob_start();

		include $plugin_root_dir . '/bh-wp-autologin-urls.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh-wp-autologin-urls', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh-wp-autologin-urls'] );

	}

}
