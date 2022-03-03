<?php
/**
 * Tests for the included settings page HTML/PHP file
 *
 * Ensures the template calls the correct WordPress APIs.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Admin\partials;

/**
 * Class BH_WP_Autologin_URLs_Admin_Display_Test
 */
class BH_WP_Autologin_URLs_Admin_Display_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Test WordPress settings APIs are called when the file is included.
	 */
	public function test_including_file_calls_wordpress_functions() {

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'settings_fields',
			array(
				'args'  => array(
					'bh-wp-autologin-urls',
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'do_settings_sections',
			array(
				'args'  => array(
					'bh-wp-autologin-urls',
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'submit_button',
			array(
				'times' => 1,
			)
		);

		// No return value: usually prints.
		\WP_Mock::userFunction(
			'settings_errors',
			array(
				'times' => 1,
			)
		);

		$example_url = 'irrelevant';

		// Don't output anything.
		ob_start();

		require_once $plugin_root_dir . '/admin/partials/admin-display.php';

		ob_end_clean();
	}
}
