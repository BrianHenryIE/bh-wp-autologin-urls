<?php
/**
 * Tests for BH_WP_Autologin_URLs.
 *
 * @package bh-wp-autologin-urls
 * @author Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Autologin_URLs\includes;

use BrianHenryIE\WP_Autologin_URLs\api\Settings_Interface;
use BrianHenryIE\WP_Autologin_URLs\BrianHenryIE\WPPB\WPPB_Loader_Interface;

/**
 * Class Test
 */
class BH_WP_Autologin_URLs_Test extends \Codeception\Test\Unit {

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
	 * The plugin version, matching the version these tests were run against.
	 *
	 * @var string Plugin version.
	 */
	private $version = '1.1.2';

	/**
	 * Verifies the plugin name, version and loader are correctly set.
	 */
	public function test_load_plugin() {

		global $plugin_root_dir;

		$mock_loader   = $this->createMock( WPPB_Loader_Interface::class );
		$mock_settings = $this->makeEmpty( Settings_Interface::class,
			array( 'get_log_level' => 'info' )
		);

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'return' => $plugin_root_dir . '/',
			)
		);

		$bh_wp_autologin_urls = new BH_WP_Autologin_URLs( $mock_loader, $mock_settings );

		$this->assertEquals( $this->plugin_name, $bh_wp_autologin_urls->get_plugin_name() );
		$this->assertEquals( $this->version, $bh_wp_autologin_urls->get_version() );

		$this->assertEquals( $mock_loader, $bh_wp_autologin_urls->get_loader() );

	}

	/**
	 * Verifies running run() on the plugin, calls the run method of the loader.
	 */
	public function test_run_plugin() {

		global $plugin_root_dir;

		$mock_loader   = $this->createMock( WPPB_Loader_Interface::class );
		$mock_settings = $this->makeEmpty( Settings_Interface::class,
			array( 'get_log_level' => 'info' )
		);

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'return' => $plugin_root_dir . '/',
			)
		);

		$bh_wp_autologin_urls = new BH_WP_Autologin_URLs( $mock_loader, $mock_settings );

		$mock_loader->expects( $this->once() )
					->method( 'run' );

		$bh_wp_autologin_urls->run();
	}

	/**
	 * Verify the plugin version will be correctly set by reading the constant
	 * set in the root plugin file.
	 */
	public function test_define_version() {

		$this->markTestSkipped('CodeCeption does not support @runInSeparateProcess. See https://github.com/Codeception/Codeception/issues/3568');

		define( 'BH_WP_AUTOLOGIN_URLS_VERSION', '0.0.1' );

		global $plugin_root_dir;

		$mock_loader   = $this->createMock( WPPB_Loader_Interface::class );
		$mock_settings = $this->createMock( Settings_Interface::class );

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'return' => $plugin_root_dir . '/',
			)
		);

		$bh_wp_autologin_urls = new BH_WP_Autologin_URLs( $mock_loader, $mock_settings );

		$this->assertEquals( '0.0.1', $bh_wp_autologin_urls->get_version() );

	}

	/**
	 * Verify the correct actions are added to the WPPB loader. This test will fail if
	 * more are added and not tested.
	 *
	 * @see \WPPB_Loader_Interface
	 */
	public function test_actions_filters_registered() {

		global $plugin_root_dir;

		/**
		 * A lazy spy to record the added hook names.
		 * Used to confirm each hook is correctly named and each action is correctly named.
		 * Doesn't deal with classes or duplicates.
		 *
		 * phpcs:disable Squiz.Commenting.FunctionComment.Missing
		 * phpcs:disable Squiz.Commenting.VariableComment.Missing
		 */
		$mock_loader = new class() implements WPPB_Loader_Interface {

			public $action_hooks   = array();
			public $action_methods = array();

			public $filter_hooks   = array();
			public $filter_methods = array();

			public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
				$this->action_hooks[]   = $hook;
				$this->action_methods[] = $callback;
			}

			public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
				$this->filter_hooks[]   = $hook;
				$this->filter_methods[] = $callback;
			}

			public function run() {
				// lol.
			}

		};

		$mock_settings = $this->makeEmpty( Settings_Interface::class,
			array( 'get_log_level' => 'info' )
		);

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'return' => $plugin_root_dir . '/',
			)
		);

		/**
		 * The action and filter hooks are added in the constructor.
		 */
		$bh_wp_autologin_urls = new BH_WP_Autologin_URLs( $mock_loader, $mock_settings );

		global $plugin_basename;

		$expected_actions        = array(
			'plugins_loaded',
			'admin_enqueue_scripts',
			'admin_menu',
			'admin_init',
			'edit_user_profile',
		);
		$expected_action_methods = array(
			'wp_init_process_autologin',
			'setup_sections',
			'setup_fields',
			'load_plugin_textdomain',
			'enqueue_styles',
			'enqueue_scripts',
			'add_settings_page',
			'make_password_available_on_user_page',
		);

		$expected_filters        = array(
			'plugin_action_links_' . $plugin_basename,
			'plugin_row_meta',
			'wp_mail',
			'add_autologin_to_message',
			'add_autologin_to_url',
		);
		$expected_filter_methods = array(
			'action_links',
			'row_meta',
			'add_autologin_links_to_email',
			'add_autologin_to_message',
			'add_autologin_to_url',
		);

		/**
		 * Compare the two arrays and print out all uncommon elements if they don't match.
		 *
		 * @see https://stackoverflow.com/a/16225577/336146
		 */
		$this->assertEquals( array_merge( array_diff( array_unique( $expected_actions ), array_unique( $mock_loader->action_hooks ) ), array_diff( array_unique( $mock_loader->action_hooks ), array_unique( $expected_actions ) ) ), array() );
		$this->assertEquals( array_merge( array_diff( array_unique( $expected_action_methods ), array_unique( $mock_loader->action_methods ) ), array_diff( array_unique( $mock_loader->action_methods ), array_unique( $expected_action_methods ) ) ), array() );
		$this->assertEquals( array_merge( array_diff( array_unique( $expected_filters ), array_unique( $mock_loader->filter_hooks ) ), array_diff( array_unique( $mock_loader->filter_hooks ), array_unique( $expected_filters ) ) ), array() );
		$this->assertEquals( array_merge( array_diff( array_unique( $expected_filter_methods ), array_unique( $mock_loader->filter_methods ) ), array_diff( array_unique( $mock_loader->filter_methods ), array_unique( $expected_filter_methods ) ) ), array() );

	}
}
