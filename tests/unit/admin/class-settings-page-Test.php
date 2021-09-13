<?php
/**
 * Tests for Admin.
 *
 * @see Admin
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\admin;

use BH_WP_Autologin_URLs\api\Settings_Interface;

/**
 * Class Admin_Test
 */
class Settings_Page_Test extends \Codeception\Test\Unit {

	protected function _before() {

		\WP_Mock::setUp();
	}


	/**
	 * The plugin name. Unlikely to change.
	 *
	 * @var string Plugin name.
	 */
	private $plugin_name = 'bh-wp-autologin-urls';

	/**
	 * The plugin version, matching the version these tests were written against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.0.0';

	/**
	 * WordPress's add_options_page should be called with 'Autologin URLs', the plugin name and the correct callback.
	 */
	public function test_add_settings_page() {

		$settings_page = new Settings_Page( $this->plugin_name, $this->version, null );

		\WP_Mock::userFunction(
			'add_options_page',
			array(
				'args'  => array(
					'Autologin URLs',
					'Autologin URLs',
					'manage_options',
					$this->plugin_name,
					array( $settings_page, 'display_plugin_admin_page' ),
				),
				'times' => 1,
			)
		);

		$settings_page->add_settings_page();
	}

	/**
	 * Test the file to be included should exist.
	 */
	public function tests_display_plugin_admin_page_file_exists() {

		global $plugin_root_dir;

		// Verify the actual file exists.
		$this->assertFileExists( $plugin_root_dir . '/admin/partials/admin-display.php' );
	}

	/**
	 * Test the function includes the file correctly.
	 */
	public function tests_display_plugin_admin_page_file_included() {

		// Feed it the wrong folder name to test it includes files properly.

		$settings_page = new Settings_Page( $this->plugin_name, $this->version, null );

		// The method first generates an example URL for the current user.
		\WP_Mock::userFunction(
			'site_url'
		);

		\WP_Mock::userFunction(
			'esc_url'
		);

		\WP_Mock::userFunction(
			'get_current_user_id'
		);

		// The it includes the template file.
		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'return' => __DIR__ . '/../',
			)
		);

		ob_start();

		$settings_page->display_plugin_admin_page();

		$printed_output = ob_get_clean();

		$this->assertEquals( 'tests_display_plugin_admin_page', $printed_output );
	}

	/**
	 * WordPress's add_settings_section should be called with the correct name.
	 */
	public function test_setup_sections() {

		$settings_page = new Settings_Page( $this->plugin_name, $this->version, null );

		\WP_Mock::userFunction(
			'add_settings_section',
			array(
				'args'  => array(
					'default',
					'Settings',
					null,
					$this->plugin_name,
				),
				'times' => 1,
			)
		);

		$settings_page->setup_sections();
	}


	/**
	 * Successful execution would mean WordPress's add_settings_field and register_settings
	 * functions being called three times each.
	 */
	public function test_setup_fields() {

		$settings_mock = $this->createMock( Settings_Interface::class );

		$settings_page = new Settings_Page( $this->plugin_name, $this->version, $settings_mock );

		\WP_Mock::userFunction(
			'add_settings_field',
			array(
				'times' => 3,
			)
		);

		\WP_Mock::userFunction(
			'register_setting',
			array(
				'times' => 3,
			)
		);

		$settings_page->setup_fields();
	}

}
