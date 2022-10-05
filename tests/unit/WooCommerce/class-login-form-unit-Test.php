<?php
/**
 * Tests for Login JS and CSS.
 *
 * @see \BrianHenryIE\WP_Autologin_URLs\Login\Login_Form
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\WooCommerce;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\WooCommerce\Login_Form
 */
class Login_Form_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies enqueue_scripts() calls wp_enqueue_script() with appropriate parameters.
	 * Verifies the .js file exists.
	 * Verifies ajaxurl and nonce are enqueued via wp_add_inline_script.
	 *
	 * @covers ::enqueue_script
	 * @covers ::__construct
	 *
	 * @see wp_enqueue_script()
	 */
	public function test_enqueue_scripts(): void {

		global $plugin_root_dir;

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => 'http://localhost/wp-conent/plugins/bh-wp-autologin-urls/',
				'times'  => 1,
			)
		);

		$handle    = 'bh-wp-autologin-urls-woocommerce-login-form';
		$src       = $plugin_root_dir . '/assets/bh-wp-autologin-urls-woocommerce-login.js';
		$url       = 'http://localhost/wp-conent/plugins/bh-wp-autologin-urls/assets/bh-wp-autologin-urls-woocommerce-login.js';
		$deps      = array( 'jquery' );
		$ver       = '1.2.3';
		$in_footer = true;

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'times' => 1,
				'args'  => array( $handle, $url, $deps, $ver, $in_footer ),
			)
		);

		$admin_ajax_url = 'http://localhost/wp-conent/plugins/bh-wp-autologin-urls/wp-admin/admin-ajax.php';

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'times'  => 1,
				'args'   => array( 'admin-ajax.php' ),
				'return' => $admin_ajax_url,
			)
		);

		\WP_Mock::userFunction(
			'wp_create_nonce',
			array(
				'times'  => 1,
				'return' => 'abc123',
			)
		);

		\WP_Mock::userFunction(
			'wp_json_encode',
			array(
				'times'  => 1,
				'args'   => array( \WP_Mock\Functions::type( 'array' ), JSON_PRETTY_PRINT ),
				'return' => '{}',
			)
		);

		\WP_Mock::userFunction(
			'wp_add_inline_script',
			array(
				'times' => 1,
				'args'  => array( $handle, \WP_Mock\Functions::type( 'string' ), 'before' ),
			)
		);

		$settings                   = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_version' => Expected::once( '1.2.3' ),
			)
		);
		$bh_wp_autologin_urls_login = new Login_Form( $settings );

		$bh_wp_autologin_urls_login->enqueue_script();

		$this->assertFileExists( $src );
	}
}
