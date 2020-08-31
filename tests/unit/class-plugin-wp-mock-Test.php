<?php
/**
 * Tests for the root plugin file.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs;

use BH_WP_Autologin_URLs\includes\BH_WP_Autologin_URLs;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}


	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include() {

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		// Hit in Settings constructor.
		\WP_Mock::userFunction(
			'get_option'
		);

		include $plugin_root_dir . '/bh-wp-autologin-urls.php';

		$this->assertArrayHasKey( 'bh-wp-autologin-urls', $GLOBALS );

		$this->assertInstanceOf( BH_WP_Autologin_URLs::class, $GLOBALS['bh-wp-autologin-urls'] );

	}


	/**
	 * Verifies the plugin does not output anything to screen.
	 */
	public function test_plugin_include_no_output() {

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		// Hit in Settings constructor.
		\WP_Mock::userFunction(
			'get_option'
		);

		ob_start();

		require_once $plugin_root_dir . '/bh-wp-autologin-urls.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

	}


	/**
	 * Verify the add_autologin_to_url is globally available after defining it.
	 */
	public function test_define_public_functions_add_autologin_to_url() {

		global $plugin_root_dir;

		if ( function_exists( '\add_autologin_to_url' ) ) {

			$this->markTestIncomplete( 'Attempting to test creation of function add_autologin_to_url in define_public_fuctions but function already exists.' );

			return;
		}

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'get_option'
		);

		require_once $plugin_root_dir . '/bh-wp-autologin-urls.php';

		define_add_autologin_to_url_function();

		$this->assertTrue( function_exists( '\add_autologin_to_url' ) );

	}
}
