<?php
/**
 * Tests for Admin.
 *
 * @see Admin
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin;

use BrianHenryIE\WP_Autologin_URLs\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\Admin\Admin_Assets
 */
class Admin_Assets_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @covers ::enqueue_styles
	 *
	 * @see Admin::enqueue_styles()
	 * @see wp_enqueue_style()
	 */
	public function test_enqueue_styles() {

		global $plugin_root_dir;

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => 'http://localhost/wp-content/plugins/bh-wp-autologin-urls/',
			)
		);

		$css_file = $plugin_root_dir . '/assets/bh-wp-autologin-urls-admin.css';
		$css_url  = 'http://localhost/wp-content/plugins/bh-wp-autologin-urls/assets/bh-wp-autologin-urls-admin.css';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'times' => 1,
				'args'  => array( 'bh-wp-autologin-urls', $css_url, array(), '1.2.3', 'all' ),
			)
		);

		$settings                   = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'    => 'bh-wp-autologin-urls',
				'get_plugin_version' => '1.2.3',
			)
		);
		$bh_wp_autologin_urls_admin = new Admin_Assets( $settings );

		$bh_wp_autologin_urls_admin->enqueue_styles();

		$this->assertFileExists( $css_file );
	}

	/**
	 * Verifies enqueue_scripts() calls wp_enqueue_script() with appropriate parameters.
	 * Verifies the .js file exists.
	 *
	 * @covers ::enqueue_scripts
	 *
	 * @see Admin::enqueue_scripts()
	 * @see wp_enqueue_script()
	 */
	public function test_enqueue_scripts() {

		global $plugin_root_dir;

		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => 'http://localhost/wp-conent/plugins/bh-wp-autologin-urls/',
			)
		);

		$handle    = 'bh-wp-autologin-urls';
		$src       = $plugin_root_dir . '/assets/bh-wp-autologin-urls-admin.js';
		$url       = 'http://localhost/wp-conent/plugins/bh-wp-autologin-urls/assets/bh-wp-autologin-urls-admin.js';
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

		$settings                   = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_slug'    => 'bh-wp-autologin-urls',
				'get_plugin_version' => '1.2.3',
			)
		);
		$bh_wp_autologin_urls_admin = new Admin_Assets( $settings );

		$bh_wp_autologin_urls_admin->enqueue_scripts();

		$this->assertFileExists( $src );
	}
}
