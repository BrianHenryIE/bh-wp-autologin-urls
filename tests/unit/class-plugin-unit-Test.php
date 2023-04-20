<?php
/**
 * Tests for the root plugin file.
 *
 * @package brianhenryie/bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\WP_Autologin_URLs\API\API;
use BrianHenryIE\WP_Autologin_URLs\WP_Logger\Logger;

class Plugin_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
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

		global $plugin_root_dir;

		\Patchwork\redefine(
			array( Logger::class, '__construct' ),
			function( $settings ) {}
		);

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'times'  => 1,
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'times'  => 1,
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wp-autologin-urls/bh-wp-autologin-urls.php',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'times'  => 1,
				'args'   => array( 'bh_wp_autologin_urls_log_level', 'notice' ),
				'return' => 'notice',
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
