<?php

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;
use BrianHenryIE\WP_Autologin_URLs\Admin\Users_List_Table;
use BrianHenryIE\WP_Autologin_URLs\Includes\REST_API;
use BrianHenryIE\WP_Autologin_URLs\Login\Login_Ajax;
use BrianHenryIE\WP_Autologin_URLs\Login\Login_Assets;
use BrianHenryIE\WP_Autologin_URLs\WooCommerce\Admin_Order_UI;
use BrianHenryIE\WP_Autologin_URLs\WooCommerce\Login_Form;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Cron;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\I18n;
use BrianHenryIE\WP_Autologin_URLs\WP_Includes\Login;
use Codeception\Stub\Expected;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Autologin_URLs\BH_WP_Autologin_URLs
 */
class BH_WP_Autologin_URLs_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
		require_once codecept_absolute_path( 'wordpress/wp-includes/rest-api/endpoints/class-wp-rest-controller.php' );
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 * @covers ::__construct
	 */
	public function test_set_locale_hooked(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_wp_login_hooks
	 */
	public function test_wp_login_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'determine_current_user',
			array( new AnyInstance( Login::class ), 'process' ),
			30
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_cron_hooks
	 */
	public function test_define_cron_hooks(): void {

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( Cron::class ), 'schedule_job' )
		);
		\WP_Mock::expectActionAdded(
			'bh_wp_autologin_urls_delete_expired_codes',
			array( new AnyInstance( Cron::class ), 'delete_expired_codes' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_define_plugins_page_hooks(): void {

		$basename = 'bh-wp-autologin-urls/bh-wp-autologin-urls.php';

		\WP_Mock::expectFilterAdded(
			"plugin_action_links_{$basename}",
			array( new AnyInstance( Plugins_Page::class ), 'action_links' ),
			10,
			4
		);
		\WP_Mock::expectFilterAdded(
			'plugin_row_meta',
			array( new AnyInstance( Plugins_Page::class ), 'row_meta' ),
			20,
			4
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::once(
					function () use ( $basename ) {
						return $basename;}
				),
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_woocommerce_admin_order_ui_hooks
	 */
	public function test_define_woocommerce_admin_order_ui_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'woocommerce_get_checkout_payment_url',
			array( new AnyInstance( Admin_Order_UI::class ), 'add_to_payment_url' ),
			10,
			2
		);

		\WP_Mock::expectFilterAdded(
			'gettext_woocommerce',
			array( new AnyInstance( Admin_Order_UI::class ), 'remove_arrow_from_link_text' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin_Order_UI::class ), 'enqueue_script' )
		);

		\WP_Mock::expectActionAdded(
			'admin_enqueue_scripts',
			array( new AnyInstance( Admin_Order_UI::class ), 'enqueue_styles' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_login_ui_hooks
	 */
	public function test_define_login_ui_hooks(): void {

		\WP_Mock::expectActionAdded(
			'login_enqueue_scripts',
			array( new AnyInstance( Login_Assets::class ), 'enqueue_styles' )
		);

		\WP_Mock::expectActionAdded(
			'login_enqueue_scripts',
			array( new AnyInstance( Login_Assets::class ), 'enqueue_scripts' )
		);

		\WP_Mock::expectActionAdded(
			'wp_ajax_nopriv_bh_wp_autologin_urls_send_magic_link',
			array( new AnyInstance( Login_Ajax::class ), 'email_magic_link' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_woocommerce_login_form_hooks
	 */
	public function test_define_woocommerce_login_form_hooks(): void {

		\WP_Mock::expectActionAdded(
			'woocommerce_before_customer_login_form',
			array( new AnyInstance( Login_Form::class ), 'enqueue_script' )
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_admin_ui_hooks
	 */
	public function test_define_users_list_table_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'user_row_actions',
			array( new AnyInstance( Users_List_Table::class ), 'add_magic_email_link' ),
			10,
			2,
		);

		\WP_Mock::expectActionAdded(
			'admin_init',
			array( new AnyInstance( Users_List_Table::class ), 'send_magic_email_link' ),
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_rest_api_hooks
	 */
	public function test_define_rest_api_hooks(): void {

		\WP_Mock::expectActionAdded(
			'rest_api_init',
			array( new AnyInstance( REST_API::class ), 'register_routes' ),
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new BH_WP_Autologin_URLs( $api, $settings, $logger );
	}
}
