<?php
/**
 * Tests for BH_WP_Autologin_URLs main setup class. Tests the actions are correctly added.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Autologin_URLs\includes;

/**
 * Class Develop_Test
 */
class BH_WP_Autologin_URLs_Develop_Test extends \WP_UnitTestCase {

	/**
	 * Verify admin_enqueue_scripts action is correctly added for styles, at priority 10.
	 */
	public function test_action_admin_enqueue_scripts_styles() {

		$action_name       = 'admin_enqueue_scripts';
		$expected_priority = 10;

		$bh_wp_autologin_urls = $GLOBALS['bh-wp-autologin-urls'];

		$class = $bh_wp_autologin_urls->admin;

		$function = array( $class, 'enqueue_styles' );

		$actual_action_priority = has_action( $action_name, $function );

		$this->assertNotFalse( $actual_action_priority );

		$this->assertEquals( $expected_priority, $actual_action_priority );

	}

	/**
	 * Verify admin_enqueue_scripts action is added for scripts, at priority 10.
	 */
	public function test_action_admin_enqueue_scripts_scripts() {

		$filter_name       = 'admin_enqueue_scripts';
		$expected_priority = 10;

		$bh_wp_autologin_urls = $GLOBALS['bh-wp-autologin-urls'];

		$class = $bh_wp_autologin_urls->admin;

		$function = array( $class, 'enqueue_scripts' );

		$actual_filter_priority = has_filter( $filter_name, $function );

		$this->assertNotFalse( $actual_filter_priority );

		$this->assertEquals( $expected_priority, $actual_filter_priority );

	}

	/**
	 * Verify filter on wp_mail is added at priority 3.
	 */
	public function test_filter_wp_mail_add_autologin_links_to_email() {

		$filter_name       = 'wp_mail';
		$expected_priority = 3;

		$bh_wp_autologin_urls = $GLOBALS['bh-wp-autologin-urls'];

		$plugin_wp_mail = $bh_wp_autologin_urls->wp_mail;

		$function = array( $plugin_wp_mail, 'add_autologin_links_to_email' );

		$actual_filter_priority = has_filter( $filter_name, $function );

		$this->assertNotFalse( $actual_filter_priority );

		$this->assertEquals( $expected_priority, $actual_filter_priority );

	}

	/**
	 * Verify the login functionality is hooked at plugins_loaded (I think that's as soon as possible)
	 * at priority 2 (so another plugin can nip in at 1 and unhook, if required).
	 */
	public function test_action_plugins_loaded_wp_init_process_autologin() {

		$action_name       = 'plugins_loaded';
		$expected_priority = 2;

		$bh_wp_autologin_urls = $GLOBALS['bh-wp-autologin-urls'];

		$plugin_login = $bh_wp_autologin_urls->login;

		$function = array( $plugin_login, 'wp_init_process_autologin' );

		$actual_action_priority = has_action( $action_name, $function );

		$this->assertNotFalse( $actual_action_priority );

		$this->assertEquals( $expected_priority, $actual_action_priority );

	}

	/**
	 * Verify action to call load textdomain is added.
	 */
	public function test_action_plugins_loaded_load_plugin_textdomain() {

		$action_name       = 'plugins_loaded';
		$expected_priority = 10;

		$bh_wp_autologin_urls = $GLOBALS['bh-wp-autologin-urls'];

		$class = $bh_wp_autologin_urls->i18n;

		$function = array( $class, 'load_plugin_textdomain' );

		$actual_action_priority = has_action( $action_name, $function );

		$this->assertNotFalse( $actual_action_priority );

		$this->assertEquals( $expected_priority, $actual_action_priority );

	}


	/**
	 * Ensure the `plugin_action_links` function is correctly added to the `plugin_action_links_*` fitler.
	 */
	public function test_add_filter_plugin_action_links() {

		global $plugin_basename;

		$filter_name       = 'plugin_action_links_' . $plugin_basename;
		$expected_priority = 10;

		$bh_wp_autologin_urls = $GLOBALS['bh-wp-autologin-urls'];

		$function = array( $bh_wp_autologin_urls->plugins_page, 'action_links' );

		$actual_filter_priority = has_filter( $filter_name, $function );

		$this->assertNotFalse( $actual_filter_priority );

		$this->assertEquals( $expected_priority, $actual_filter_priority );
	}

}
