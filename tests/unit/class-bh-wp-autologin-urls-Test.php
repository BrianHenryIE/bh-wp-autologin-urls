<?php

namespace BrianHenryIE\WP_Autologin_URLs;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Autologin_URLs\Admin\Plugins_Page;
use BrianHenryIE\WP_Autologin_URLs\WooCommerce\Admin_Order_UI;
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

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( Login::class ), 'process' ),
			0
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
					function() use ( $basename ) {
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
}
