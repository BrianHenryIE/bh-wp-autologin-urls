<?php
/**
 * Tests for BH_WP_Autologin_URLs main setup class. Tests the actions are correctly added.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\Includes;

use BrianHenryIE\WP_Autologin_URLs\Admin\Admin;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;

/**
 * Class Develop_Test
 */
class BH_WP_Autologin_URLs_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Verify admin_enqueue_scripts action is correctly added for styles, at priority 10.
	 */
	public function test_action_admin_enqueue_scripts_styles() {

		$action_name       = 'admin_enqueue_scripts';
		$expected_priority = 10;

		$class_type  = Admin::class;
		$method_name = 'enqueue_styles';

		$function_is_hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertNotFalse( $function_is_hooked );
	}

	/**
	 * Verify admin_enqueue_scripts action is added for scripts, at priority 10.
	 */
	public function test_action_admin_enqueue_scripts_scripts() {

		$action_name       = 'admin_enqueue_scripts';
		$expected_priority = 10;

		$class_type  = Admin::class;
		$method_name = 'enqueue_scripts';

		$function_is_hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertNotFalse( $function_is_hooked );
	}

	/**
	 * Verify filter on wp_mail is added at priority 3.
	 */
	public function test_filter_wp_mail_add_autologin_links_to_email() {

		$action_name       = 'wp_mail';
		$expected_priority = 3;

		$class_type  = WP_Mail::class;
		$method_name = 'add_autologin_links_to_email';

		$function_is_hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertNotFalse( $function_is_hooked );
	}

	/**
	 * Verify the login functionality is hooked at plugins_loaded (I think that's as soon as possible)
	 * at priority 2 (so another plugin can nip in at 1 and unhook, if required).
	 */
	public function test_action_plugins_loaded_wp_init_process_autologin() {

		$action_name       = 'plugins_loaded';
		$expected_priority = 2;

		$class_type  = Login::class;
		$method_name = 'wp_init_process_autologin';

		$function_is_hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertNotFalse( $function_is_hooked );
	}

	/**
	 * Verify action to call load textdomain is added.
	 */
	public function test_action_plugins_loaded_load_plugin_textdomain() {

		$action_name       = 'plugins_loaded';
		$expected_priority = 10;

		$class_type  = I18n::class;
		$method_name = 'load_plugin_textdomain';

		$function_is_hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertNotFalse( $function_is_hooked );
	}


	/**
	 * Ensure the `plugin_action_links` function is correctly added to the `plugin_action_links_*` fitler.
	 */
	public function test_add_filter_plugin_action_links() {

		global $plugin_basename;

		$action_name       = 'plugin_action_links_' . $plugin_basename;
		$expected_priority = 10;

		$class_type  = Plugins_Page::class;
		$method_name = 'action_links';

		$function_is_hooked = $this->is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority );

		$this->assertNotFalse( $function_is_hooked );

	}


	protected function is_function_hooked_on_action( $class_type, $method_name, $action_name, $expected_priority = 10 ) {

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
