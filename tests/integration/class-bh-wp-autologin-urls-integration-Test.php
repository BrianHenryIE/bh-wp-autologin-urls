<?php
/**
 * Tests for BH_WP_Autologin_URLs main setup class. Tests the actions are correctly added.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\WP_Includes;

use BrianHenryIE\WP_Autologin_URLs\Admin\Admin_Assets;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;

/**
 * Class Develop_Test
 */
class BH_WP_Autologin_URLs_Integration_Test extends \Codeception\TestCase\WPTestCase {

	public function hooks() {
		global $plugin_basename;
		$hooks = array(
			array( 'init', I18n::class, 'load_plugin_textdomain' ),
			array( 'admin_enqueue_scripts', Admin_Assets::class, 'enqueue_styles' ),
			array( 'admin_enqueue_scripts', Admin_Assets::class, 'enqueue_scripts' ),
			array( 'wp_mail', WP_Mail::class, 'add_autologin_links_to_email', 3 ),
			array( 'plugins_loaded', Login::class, 'wp_init_process_autologin', 0 ),
			array( 'plugin_action_links_' . $plugin_basename, Plugins_Page::class, 'action_links' ),
		);
		return $hooks;
	}

	/**
	 * @dataProvider hooks
	 */

	protected function is_function_hooked_on_action( string $class_type, string $method_name, string $action_name, int $expected_priority = 10 ): bool {

		global $wp_filter;

		$this->assertArrayHasKey( $action_name, $wp_filter, "$method_name definitely not hooked to $action_name" );

		$actions_hooked = $wp_filter[ $action_name ];

		$this->assertArrayHasKey( $expected_priority, $actions_hooked, "$method_name definitely not hooked to $action_name priority $expected_priority" );

		$hooked_method = null;
		foreach ( $actions_hooked[ $expected_priority ] as $action ) {
			$action_function = $action['function'];
			if ( is_array( $action_function ) ) {
				if ( $action_function[0] instanceof $class_type ) {
					if ( $method_name === $action_function[1] ) {
						$hooked_method = $action_function[1];
						break;
					}
				}
			}
		}

		$this->assertNotNull( $hooked_method, "No methods on an instance of $class_type hooked to $action_name" );

		$this->assertEquals( $method_name, $hooked_method, "Unexpected method name for $class_type class hooked to $action_name" );

		return true;
	}
}
