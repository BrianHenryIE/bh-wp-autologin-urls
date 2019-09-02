<?php
/**
 * Tests for the included settings page HTML/PHP file
 *
 * Ensures the template calls the correct WordPress APIs.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\admin\partials;

/**
 * Class BH_WP_Autologin_URLs_Admin_Display_Test
 */
class BH_WP_Autologin_URLs_Admin_Display_Test extends \WP_Mock\Tools\TestCase {

	/**
	 * Test WordPress settings APIs are called when the file is included.
	 *
	 * @runInSeparateProcess
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

		$example_url = 'irrelevant';

		// Don't output anything.
		ob_start();

		require_once $plugin_root_dir . '/admin/partials/admin-display.php';

		ob_end_clean();

		$this->assertConditionsMet();

	}
}
